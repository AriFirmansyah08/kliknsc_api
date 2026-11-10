<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Exceptions\HttpResponseException;

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
    private const OTP_TTL_MINUTES = 1;
    private const MAX_ATTEMPTS = 3;
    private const TOKEN_TTL_MINUTES = 10;
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
                'status' => 'none',
                'phone' => $data['phone'],
                'akses' => 'dtagen',
                'password' => $data['password'],
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



    // POST /api/password/reset
public function passwordReset(Request $request): JsonResponse
{
    $data = $request->validate([
        'phone' => ['required', self::PHONE_RULE],
        'otp' => ['required', 'digits:6'],
        'password' => ['required', self::PASSWORD_RULE],
    ]);

    $user = User::where('phone', $data['phone'])->first();
    $otp = $user ? $this->findValidOtp($data['phone'], $data['otp'], 'reset') : null;

    if (!$user || !$otp) {
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

    DB::transaction(function () use ($user, $otp, $data) {
        $otp->update(['used_at' => now()]);
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $user->tokens()->delete();
        PasswordResetLog::create(['user_id' => $user->id]);
    });

    $remaining = self::MAX_RESET_PER_MONTH - ($used + 1);

    Log::info('AUTH password reset berhasil', [
        'user_id' => $user->id,
        'phone' => $this->maskPhone($data['phone']),
        'sisa_bulan_ini' => $remaining,
        'ip' => $request->ip(),
    ]);

    return response()->json([
        'message' => 'Password berhasil diganti',
        'remaining' => $remaining,
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

    private function findValidOtp(string $phone, string $otp, string $mode): OtpCode
{
    $record = OtpCode::where('phone', $phone)
        ->where('mode', $mode)
        ->whereNull('used_at')
        ->latest()
        ->first();

    if (!$record || $record->expires_at->isPast()) {
        $this->gagalOtp('Kode OTP sudah kedaluwarsa, minta kode baru');
    }

    if ($record->attempts >= self::MAX_ATTEMPTS) {
        $this->gagalOtp('Percobaan habis, minta kode OTP baru');
    }

    if (!hash_equals($record->code_hash, hash('sha256', $otp))) {
        $record->increment('attempts');
        $sisa = self::MAX_ATTEMPTS - $record->attempts;

        Log::warning('AUTH otp salah', [
            'phone' => $this->maskPhone($phone),
            'mode' => $mode,
            'percobaan' => $record->attempts,
        ]);

        $this->gagalOtp($sisa > 0
            ? "Kode OTP salah. Sisa percobaan: {$sisa}"
            : 'Percobaan habis, minta kode OTP baru');
    }

    return $record;
}

private function gagalOtp(string $pesan): never
{
    throw new HttpResponseException(response()->json(['message' => $pesan], 422));
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

    // POST /api/password/change (butuh login)
public function passwordChange(Request $request): JsonResponse
{
    $data = $request->validate([
        'current_password' => ['required', 'string'],
        'password' => ['required', self::PASSWORD_RULE, 'different:current_password'],
    ]);

    $user = $request->user();
    if (!Hash::check($data['current_password'], $user->password)) {
        return response()->json(['message' => 'Password lama salah'], 422);
    }

    $user->forceFill(['password' => Hash::make($data['password'])])->save();
    // Cabut token di perangkat lain, token yang dipakai sekarang tetap hidup
    $current = $user->currentAccessToken();
    $user->tokens()->where('id', '!=', $current->id)->delete();
    return response()->json(['message' => 'Password berhasil diganti']);
}
}