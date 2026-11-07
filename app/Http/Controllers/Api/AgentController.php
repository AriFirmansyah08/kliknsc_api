<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\Contracts\KsAgentRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AgentController extends Controller
{
    // GET /api/agent/status
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            $status = 'none';
        } elseif ($agent->status === Agent::STATUS_DRAFT) {
            $status = 'none_draft';
        } else {
            $status = $agent->status;
        }

        return response()->json([
            'status' => $status,
            'user' => $user->only('name', 'phone'),
            'agent' => $agent ? [
                'nama_lengkap' => $agent->nama_lengkap,
                'kode_agen' => $agent->kode_agen,
                'jenis_agen' => $agent->jenis_agen,
                'kba_nama' => $agent->kba_nama,
                'kba_phone' => $agent->kba_phone,
                'submitted_at' => $agent->submitted_at?->toIso8601String(),
            ] : null,
        ]);
    }

    // POST /api/agent/submit
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if ($user->agent && $user->agent->status !== Agent::STATUS_DRAFT) {
            return response()->json(['message' => 'Pendaftaran agen sudah pernah dikirim'], 422);
        }

        Agent::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap' => $data['nama_lengkap'],
                'jenis_agen' => 'perorangan',
                'status' => Agent::STATUS_PENDING,
                'submitted_at' => now(),
            ]
        );

        return response()->json(['message' => 'Pendaftaran dikirim', 'status' => 'pending']);
    }

    // POST /api/agent/cek-nomor  body: { no_telp }
public function cekNomor(Request $request, KsAgentRepositoryInterface $ksAgent): JsonResponse
{
    $data = $request->validate([
        'no_telp' => ['required', 'string', 'regex:/^08\d{8,11}$/'],
    ]);

    try {
        $ada = $ksAgent->adaNoTelp($data['no_telp']);
    } catch (RuntimeException $e) {
        return response()->json(['message' => 'Layanan sedang tidak tersedia'], 503);
    }

    return response()->json(['ada' => $ada]);
}


// POST /api/agent/verifikasi  body: { no_telp, nik, password }
public function verifikasi(Request $request, KsAgentRepositoryInterface $ksAgent): JsonResponse
{
    $data = $request->validate([
        'no_telp' => ['required', 'regex:/^08\d{8,11}$/'],
        'nik' => ['required', 'digits:16'],
        'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
    ]);

    if (User::where('phone', $data['no_telp'])->exists()) {
        return response()->json(['message' => 'Akun sudah terdaftar, silakan login'], 422);
    }

    $kunciGagal = 'verif-gagal:' . $data['no_telp'];
    $gagal = (int) Cache::get($kunciGagal, 0);
    if ($gagal >= 3) {
        return response()->json(['message' => 'Percobaan NIK habis, hubungi CS'], 429);
    }

    try {
        $agen = $ksAgent->cari($data['no_telp']);
    } catch (RuntimeException $e) {
        return response()->json(['message' => 'Layanan sedang tidak tersedia'], 503);
    }

    if (!$agen) {
        return response()->json(['message' => 'Nomor tidak ditemukan sebagai agen'], 404);
    }

    if ((string) $agen['NoKtp'] !== $data['nik']) {
        $gagal++;
        Cache::put($kunciGagal, $gagal, now()->addDay());

        return response()->json([
            'message' => 'NIK tidak cocok. Sisa ' . (3 - $gagal) . ' percobaan',
        ], 422);
    }

    Cache::forget($kunciGagal);

    $token = DB::transaction(function () use ($data, $agen) {
        $user = User::create([
            'phone' => $data['no_telp'],
            'password' => $data['password'],
            'name' => $agen['NamaAgen'] ?? null,
        ]);

        Agent::create([
            'user_id' => $user->id,
            'nama_lengkap' => $agen['NamaAgen'] ?? '-',
            'jenis_agen' => strtolower($agen['JnsAgen'] ?? 'perorangan'),
            'status' => 'active',
            'submitted_at' => now(),
            'activated_at' => now(),
        ]);

        return $user->createToken('app')->plainTextToken;
    });

    return response()->json([
        'message' => 'Verifikasi berhasil',
        'token' => $token,
    ]);
}

}