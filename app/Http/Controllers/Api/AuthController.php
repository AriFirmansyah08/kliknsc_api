<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const MAX_RESET_PER_MONTH = 3;
    private const OTP_TTL_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const TOKEN_TTL_MINUTES = 5;

    private const PHONE_RULE = 'regex:/^08\d{8,11}$/';
    private const PASSWORD_RULE = 'regex:/^(?=.*[A-Za-z])(?=.*\d).{8,}$/';

    public function otpRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', self::PHONE_RULE],
            'mode' => ['required', Rule::in(['register', 'reset'])],
            'channel' => ['nullable', Rule::in(['whatsapp', 'sms'])],
        ]);

        $exists = User::where('phone', $data['phone'])->exists();

        if ($data['mode'] === 'register' && $exists) {
            Log::info('AUTH otp_request ditolak: nomor sudah terdaftar', [
                'phone' => $this->maskPhone($data['phone']),
                'mode' => 'register',
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Nomor HP sudah terdaftar'], 422);
        }

        if ($data['mode'] === 'reset' && !$exists) {
            Log::info('AUTH otp_request reset untuk nomor tidak terdaftar', [
                'phone' => $this->maskPhone($data['phone']),
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Jika nomor terdaftar, kode OTP dikirim']);
        }

        $code = (string) random_int(100000, 999999);

        OtpCode::where('phone', $data['phone'])
            ->where('mode', $data['mode'])
            ->whereNull('used_at')
            ->delete();

        OtpCode::create([
            'phone' => $data['phone'],
            'mode' => $data['mode'],
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);
        Log::info('AUTH OTP', [
            'phone' => $data['phone'],
            'mode' => $data['mode'],
            'channel' => $data['channel'] ?? 'sms',
            'kode' => $code,
        ]);
        Log::info('AUTH otp_request dibuat', [
            'phone' => $this->maskPhone($data['phone']),
            'mode' => $data['mode'],
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Kode OTP dikirim']);
    }

    // POST /api/otp/verify
    public function otpVerify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', self::PHONE_RULE],
            'otp' => ['required', 'digits:6'],
            'mode' => ['required', Rule::in(['register', 'reset'])],
        ]);

        if (!$this->findValidOtp($data['phone'], $data['otp'], $data['mode'])) {
            return response()->json(['message' => 'Kode OTP salah atau sudah kedaluwarsa'], 422);
        }

        return response()->json(['message' => 'OTP valid']);
    }

    // POST /api/register
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', self::PHONE_RULE, 'unique:users,phone'],
            'password' => ['required', self::PASSWORD_RULE],
            'otp' => ['required', 'digits:6'],
        ]);

        $otp = $this->findValidOtp($data['phone'], $data['otp'], 'register');
        if (!$otp) {
            return response()->json(['message' => 'Kode OTP salah atau sudah kedaluwarsa'], 422);
        }

        $user = DB::transaction(function () use ($data, $otp) {
            $otp->update(['used_at' => now()]);

            return User::create([
                'name' => $data['phone'],
                'phone' => $data['phone'],
                'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
            ]);
        });

        Log::info('AUTH register berhasil', [
            'user_id' => $user->id,
            'phone' => $this->maskPhone($data['phone']),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Akun berhasil dibuat',
            'token' => $this->issueToken($user),
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            'user' => $user->only('id', 'phone'),
        ], 201);
    }

    // POST /api/login
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::warning('AUTH login gagal', [
                'phone' => $this->maskPhone($data['phone']),
                'ip' => $request->ip(),
                'alasan' => $user ? 'password salah' : 'nomor tidak terdaftar',
            ]);
            return response()->json(['message' => 'Nomor HP atau password salah'], 422);
        }

        Log::info('AUTH login berhasil', [
            'user_id' => $user->id,
            'phone' => $this->maskPhone($data['phone']),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $this->issueToken($user),
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            'user' => $user->only('id', 'phone'),
        ]);
    }

    // POST /api/password/reset
    public function passwordReset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', self::PHONE_RULE],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', self::PASSWORD_RULE],
        ]);

        $user = User::where('phone', $data['phone'])->first();
        if (!$user) {
            return response()->json(['message' => 'Kode OTP salah atau sudah kedaluwarsa'], 422);
        }

        $used = PasswordResetLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($used >= self::MAX_RESET_PER_MONTH) {
            Log::warning('AUTH password reset ditolak: batas bulanan', [
                'user_id' => $user->id,
                'phone' => $this->maskPhone($data['phone']),
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'message' => 'Reset password bulan ini sudah mencapai batas 3 kali',
            ], 429);
        }

        $otp = $this->findValidOtp($data['phone'], $data['otp'], 'reset');
        if (!$otp) {
            return response()->json(['message' => 'Kode OTP salah atau sudah kedaluwarsa'], 422);
        }

        DB::transaction(function () use ($user, $otp, $data) {
            $otp->update(['used_at' => now()]);
            $user->update(['password' => $data['password']]);
            PasswordResetLog::create(['user_id' => $user->id]);
        });

        Log::info('AUTH password reset berhasil', [
            'user_id' => $user->id,
            'phone' => $this->maskPhone($data['phone']),
            'sisa_bulan_ini' => self::MAX_RESET_PER_MONTH - ($used + 1),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Password berhasil diganti',
            'remaining' => self::MAX_RESET_PER_MONTH - ($used + 1),
        ]);
    }

    // POST /api/logout (butuh token)
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        Log::info('AUTH logout', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json(['message' => 'Logout berhasil']);
    }

    private function findValidOtp(string $phone, string $otp, string $mode): ?OtpCode
    {
        $record = OtpCode::where('phone', $phone)
            ->where('mode', $mode)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$record || $record->expires_at->isPast() || $record->attempts >= self::MAX_ATTEMPTS) {
            return null;
        }

        if (!hash_equals($record->code_hash, hash('sha256', $otp))) {
            $record->increment('attempts');
            Log::warning('AUTH otp salah', [
                'phone' => $this->maskPhone($phone),
                'mode' => $mode,
                'percobaan' => $record->attempts + 1,
            ]);
            return null;
        }
        return $record;
    }

    private function issueToken(User $user): string
    {
        return $user->createToken('mobile', ['*'], now()->addMinutes(self::TOKEN_TTL_MINUTES))
            ->plainTextToken;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        return substr($phone, 0, 4) . str_repeat('*', max($len - 7, 0)) . substr($phone, -3);
    }
}