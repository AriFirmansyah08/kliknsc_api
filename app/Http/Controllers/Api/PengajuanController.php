<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PengajuanController extends Controller
{
    // POST /api/pengajuan/m2w
    public function m2w(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'namaLengkap' => ['required', 'string', 'max:60'],
            'noHp' => ['required', 'regex:/^08\d{8,11}$/'],
            'alamat' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (count(preg_split('/\s+/', trim($value))) < 5) {
                    $fail('Alamat minimal 5 kata.');
                }
            }],
            'rt' => ['required', 'digits:3'],
            'rw' => ['required', 'digits:3'],
            'provinsi' => ['required', 'string', 'max:50'],
            'kota' => ['required', 'string', 'max:50'],
            'kecamatan' => ['required', 'string', 'max:50'],
            'kelurahan' => ['required', 'string', 'max:50'],
            'sumberPk' => ['required', 'string', 'max:30'],
            'pilSumber' => ['required', 'string', 'max:50'],
        ], $this->aturan());

        $payload = [
            'api_appklikspkm2w' => [[
                'Request' => 'BUKUTAMU',
                'Data' => [[
                    'StatusBtm' => 'OPEN',
                    'NoHp' => $data['noHp'],
                    'nik' => $data['nik'],
                    'namaLengkap' => $data['namaLengkap'],
                    'alamat' => $data['alamat'],
                    'rt' => $data['rt'],
                    'rw' => $data['rw'],
                    'provinsi' => $data['provinsi'],
                    'kota' => $data['kota'],
                    'kecamatan' => $data['kecamatan'],
                    'kelurahan' => $data['kelurahan'],
                    'sumberPk' => $data['sumberPk'],
                    'pilSumber' => $data['pilSumber'],
                ]],
            ]],
        ];

        try {
            $res = Http::timeout(15)
                ->acceptJson()
                ->post(config('services.pkm2w.url'), $payload);
        } catch (ConnectionException $e) {
            Log::error('PENGAJUAN M2W: pkm2w tidak terhubung', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->gagalKirim();
        }

        if ($res->failed()) {
            Log::error('PENGAJUAN M2W: pkm2w menolak', [
                'user_id' => $request->user()->id,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            return $this->gagalKirim();
        }

        Log::info('PENGAJUAN M2W berhasil', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Pengajuan M2W terkirim', 'status' => 'terkirim']);
    }

    private function gagalKirim(): JsonResponse
    {
        return response()->json([
            'message' => 'Layanan pusat sedang tidak tersedia, silakan coba lagi',
            'status' => 'error',
        ], 502);
    }

    private function aturan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak valid.',
            'digits' => ':attribute harus :digits digit.',
            'regex' => ':attribute formatnya tidak valid.',
        ];
    }
}