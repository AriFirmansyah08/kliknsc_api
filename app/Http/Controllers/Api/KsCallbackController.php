<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentPendaftaran;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KsCallbackController extends Controller
{
    private const STATUS_BISA_DIUBAH = ['pending', 'active', 'rejected'];

    // POST /api/ksapi/agent-verifikasi, dipanggil SCT3203 saat hasil verifikasi terbentuk
    public function agentVerifikasi(Request $request): JsonResponse
    {
        $tokenServer = (string) config('services.ksapi.callback_token');

        if ($tokenServer === '' || !hash_equals($tokenServer, (string) $request->input('token'))) {
            return response()->json(['Status' => 'Error', 'Pesan' => 'Token tidak valid'], 401);
        }

        $data = $request->validate([
            'no_telp' => ['required', 'string', 'regex:/^08\d{8,11}$/'],
            'nik' => ['required', 'regex:/^\d{15,16}$/'],
            'hasil' => ['required', 'in:VALID,INVALID'],
        ]);

        $pendaftaran = AgentPendaftaran::where('psmkode', $data['no_telp'])
            ->where('psmktp', $data['nik'])
            ->first();
        $user = User::where('phone', $data['no_telp'])->first();

        if (!$pendaftaran || !$user) {
            return response()->json(['Status' => 'Error', 'Pesan' => 'Agen tidak ditemukan'], 404);
        }

        if (!in_array($user->status, self::STATUS_BISA_DIUBAH, true)) {
            return response()->json(['Status' => 'Error', 'Pesan' => 'Status agen tidak bisa diubah'], 409);
        }

        $statusLama = $user->status;
        $statusBaru = $data['hasil'] === 'VALID' ? 'active' : 'rejected';

        if ($statusLama !== $statusBaru) {
            $user->update(['status' => $statusBaru]);
        }

        Log::info('AGENT hasil verifikasi dari KSystem', [
            'user_id' => $user->id,
            'hasil' => $data['hasil'],
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
        ]);

        return response()->json(['Status' => 'Ok', 'StatusAgen' => $statusBaru]);
    }
}
