<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentPendaftaran;
use App\Repositories\Contracts\KsAgentRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PengajuanController extends Controller
{
    // POST /api/pengajuan/m2w
    public function m2w(Request $request, KsAgentRepositoryInterface $ksAgent): JsonResponse
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

        $akun = $request->user();

        // data agen yang login, diambil dari server
        $agen = AgentPendaftaran::where('psmkode', $akun->phone)->first();

        if (!$agen) {
            return response()->json([
                'message' => 'Data agen tidak ditemukan',
                'status' => 'error',
            ], 422);
        }

        // cabang diambil dari data referensi NIP, sama seperti saat agen didaftarkan
        try {
            $cabang = $this->cariCabangNip($ksAgent, (string) $agen->psmkdtmp2);
        } catch (RuntimeException $e) {
            Log::error('PENGAJUAN M2W: referensi NIP tidak tersedia', [
                'user_id' => $akun->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Layanan sedang tidak tersedia, coba lagi nanti',
                'status' => 'error',
            ], 503);
        }

        if ($cabang === null) {
            return response()->json([
                'message' => 'Cabang agen tidak ditemukan di data referensi NIP',
                'status' => 'error',
            ], 422);
        }

        $payload = [
            'api_appklikspkm2w' => [[
                'Request' => 'BUKUTAMU',
            ]],
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

                // identitas penginput (agen), dari server
                'nikAgent' => $agen->psmktp,
                'lokasiJual' => $cabang,
                'nipAgen' => $agen->psmkdtmp2,
                'namaAgen' => $agen->psmnama,
                'noHpAgen' => $akun->phone,
            ]],
        ];

        try {
            $res = Http::timeout(15)
                ->acceptJson()
                ->beforeSending(function ($request) {
                    $body = $request->body();
                    $body = preg_replace('/"nik":"(\d{6})\d{6}(\d{4})"/', '"nik":"$1******$2"', $body);
                    $body = preg_replace('/"NoHp":"(\d{4})\d+(\d{2})"/', '"NoHp":"$1****$2"', $body);

                    Log::info('PENGAJUAN M2W payload dikirim', [
                        'url' => $request->url(),
                        'body' => $body,
                    ]);
                })
                ->post(config('services.pkm2w.url'), $payload);
        } catch (ConnectionException $e) {
            Log::error('PENGAJUAN M2W: pkm2w tidak terhubung', [
                'user_id' => $akun->id,
                'error' => $e->getMessage(),
            ]);

            return $this->gagalKirim();
        }

        if ($res->failed()) {
            Log::error('PENGAJUAN M2W: pkm2w menolak', [
                'user_id' => $akun->id,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            return $this->gagalKirim();
        }

        Log::info('PENGAJUAN M2W berhasil', [
            'user_id' => $akun->id,
            'ip' => $request->ip(),
            'status' => $res->status(),
            'response' => $res->body(),
        ]);

        return response()->json(['message' => 'Pengajuan M2W terkirim', 'status' => 'terkirim']);
    }

        public function hmc(Request $request, KsAgentRepositoryInterface $ksAgent): JsonResponse
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

        $akun = $request->user();

        // data agen yang login, diambil dari server
        $agen = AgentPendaftaran::where('psmkode', $akun->phone)->first();

        if (!$agen) {
            return response()->json([
                'message' => 'Data agen tidak ditemukan',
                'status' => 'error',
            ], 422);
        }

        // cabang diambil dari data referensi NIP, sama seperti saat agen didaftarkan
        try {
            $cabang = $this->cariCabangNip($ksAgent, (string) $agen->psmkdtmp2);
        } catch (RuntimeException $e) {
            Log::error('PENGAJUAN HMC: referensi NIP tidak tersedia', [
                'user_id' => $akun->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Layanan sedang tidak tersedia, coba lagi nanti',
                'status' => 'error',
            ], 503);
        }

        if ($cabang === null) {
            return response()->json([
                'message' => 'Cabang agen tidak ditemukan di data referensi NIP',
                'status' => 'error',
            ], 422);
        }

        $payload = [
            'api_appklikspkhmc' => [[
                'Request' => 'BUKUTAMU',
            ]],
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

                // identitas penginput (agen), dari server
                'nikAgent' => $agen->psmktp,
                'lokasiJual' => $cabang,
                'nipAgen' => $agen->psmkdtmp2,
                'namaAgen' => $agen->psmnama,
                'noHpAgen' => $akun->phone,
            ]],
        ];

        try {
            $res = Http::timeout(15)
                ->acceptJson()
                ->beforeSending(function ($request) {
                    $body = $request->body();
                    $body = preg_replace('/"nik":"(\d{6})\d{6}(\d{4})"/', '"nik":"$1******$2"', $body);
                    $body = preg_replace('/"NoHp":"(\d{4})\d+(\d{2})"/', '"NoHp":"$1****$2"', $body);

                    Log::info('PENGAJUAN HMC payload dikirim', [
                        'url' => $request->url(),
                        'body' => $body,
                    ]);
                })
                ->post(config('services.pkhmc.url'), $payload);
        } catch (ConnectionException $e) {
            Log::error('PENGAJUAN HMC: pkhmc tidak terhubung', [
                'user_id' => $akun->id,
                'error' => $e->getMessage(),
            ]);

            return $this->gagalKirim();
        }

        if ($res->failed()) {
            Log::error('PENGAJUAN HMC: pkhmc menolak', [
                'user_id' => $akun->id,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            return $this->gagalKirim();
        }

        Log::info('PENGAJUAN HMC berhasil', [
            'user_id' => $akun->id,
            'ip' => $request->ip(),
            'status' => $res->status(),
            'response' => $res->body(),
        ]);

        return response()->json(['message' => 'Pengajuan HMC terkirim', 'status' => 'terkirim']);
    }

    private function cariCabangNip(KsAgentRepositoryInterface $ksAgent, string $nip): ?string
    {
        $nipCari = strtoupper(trim($nip));

        foreach ($ksAgent->semuaNipReferensi() as $item) {
            if (strtoupper(trim((string) ($item['Nip'] ?? ''))) === $nipCari) {
                $cabang = trim((string) ($item['Cabang'] ?? ''));

                return $cabang !== '' ? $cabang : null;
            }
        }

        return null;
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