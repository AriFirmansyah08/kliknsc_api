<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentPendaftaran;
use App\Models\User;
use App\Repositories\Contracts\KsAgentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AgentController extends Controller
{
    private const MAX_NIK_SALAH = 3;
    private const TOKEN_TTL_MINUTES = 720;

    // GET /api/agent/status
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendaftaran = AgentPendaftaran::where('psmkode', $user->phone)->first();

        return response()->json([
            'status' => $user->status ?? 'none',
            'user' => $user->only('name', 'phone'),
            'agent' => $pendaftaran ? [
                'nama_lengkap' => $pendaftaran->psmnama,
                'kode_agen' => $pendaftaran->psmkdtmp2,
                'jenis_agen' => $pendaftaran->psmtype,
                'kba_nama' => null,
                'kba_phone' => null,
                'submitted_at' => $pendaftaran->psmtgl,
            ] : null,
        ]);
    }

    // POST /api/agent/submit_app
    public function submitApp(Request $request): JsonResponse
    {
        $request->merge($this->kapital($request->all()));

        $data = $request->validate(
            $this->aturanDaftar($request),
            $this->aturan()
        );

        $user = $request->user();

        try {
            DB::transaction(function () use ($user, $data) {
                $this->simpanPendaftaran($user->phone, $data);
                $user->update(['status' => 'pending']);

                $this->kirimKeKsapi($user->phone, $data);
            });
        } catch (RuntimeException $e) {
            Log::error('AGENT submit_app gagal kirim ke KSAPI', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Layanan pusat sedang tidak tersedia, silakan coba lagi',
                'status' => 'error',
            ], 502);
        }

        Cache::forget('ksapi:agen');

        Log::info('AGENT submit_app berhasil', [
            'user_id' => $user->id,
            'jenis_agen' => $data['jenisAgen'],
            'status' => 'pending',
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Data pendaftaran tersimpan', 'status' => 'pending']);
    }

    public function addAgent(Request $request): JsonResponse
    {
        $request->validate([
            'api_appkliknsc' => ['required', 'array', 'min:1'],
            'api_appkliknsc.0.Request' => ['required', 'in:KLIKNSC'],
            'Data' => ['required', 'array', 'size:1'],
            'Data.0' => ['required', 'array'],
        ]);

        $request->merge($request->input('Data.0', []));
        $request->merge($this->kapital($request->all()));

        $data = $request->validate(
            array_merge(
                ['NoHp' => ['required', 'string', 'regex:/^08\d{8,11}$/']],
                $this->aturanDaftar($request)
            ),
            $this->aturan()
        );

        $this->simpanPendaftaran($data['NoHp'], $data);

        Log::info('AGENT addAgent berhasil', [
            'nohp' => $data['NoHp'],
            'nama' => $data['namaLengkap'],
            'jenis_agen' => $data['jenisAgen'],
            'ip' => $request->ip(),
        ]);

        return response()->json(['Status' => 'Ok']);
    }

    private function aturanDaftar(Request $request): array
    {
        return [
            'jenisAgen' => ['required', 'in:PERORANGAN,CORPORATE'],
            'nip' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'regex:/^\d+$/', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('jenisAgen') === 'CORPORATE') {
                    if (!preg_match('/^\d{15}$/', $value)) {
                        $fail('NIK corporate harus 15 digit.');
                    }
                } elseif (!preg_match('/^\d{16}$/', $value)) {
                    $fail('NIK harus 16 digit.');
                }
            }],

            'namaLengkap' => ['required', 'string', 'max:60'],
            'kotaLahir' => ['required', 'string', 'max:50'],
            'tanggalLahir' => ['required', 'date', 'before:today'],
            'jenisKelamin' => ['required', 'in:LAKI-LAKI,PEREMPUAN'],
            'golDarah' => ['required', 'in:O,A,B,AB'],
            'kewarganegaraan' => ['required', 'in:WNI,WNA'],
            'agama' => ['required', 'string', 'max:20'],
            'statusKawin' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:60'],

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
            'kodePos' => ['required', 'digits:5'],

            'punyaNpwp' => ['required', 'in:YA,TIDAK'],
            'nomorNpwp' => ['required_if:punyaNpwp,YA', 'nullable', 'digits:15'],
            'bank' => ['required', 'string', Rule::exists('bank', 'tbdkode'), Rule::notIn(['BANK_SEABANK', 'BANK_JAGO'])],

            'nomorRekening' => ['required', 'string', 'max:20'],
            'namaRekening' => ['required', 'string', 'max:30'],

            'pekerjaan' => ['required', 'string', 'max:50'],
            'subPekerjaan' => ['required', 'string', 'max:50'],
            'pernahOtomotif' => ['required', 'in:YA,TIDAK'],
            'unitPerBulan' => ['required_if:pernahOtomotif,YA', 'nullable', 'integer', 'min:0', 'max:32767'],
            'tahuDari' => ['required', 'string', 'max:30'],

            'consent' => ['required', 'accepted'],
        ];
    }

    private function aturan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak valid.',
            'digits' => ':attribute harus :digits digit.',
            'email' => 'Format email tidak valid.',
            'accepted' => 'Persetujuan wajib dicentang.',
            'bank.not_in' => 'Bank ini tidak bisa dipilih.',
        ];
    }

        // Semua teks jadi kapital, kecuali email dan consent
    private function kapital(array $input): array
    {
        foreach ($input as $key => $value) {
            if (in_array($key, ['email', 'consent'], true) || !is_string($value)) {
                continue;
            }
            $input[$key] = mb_strtoupper($value);
        }
        return $input;
    }

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

        if ($gagal >= self::MAX_NIK_SALAH) {
            return $this->habisNik();
        }

        try {
            $agen = $ksAgent->cari($data['no_telp']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => 'Layanan sedang tidak tersedia, coba lagi nanti'], 503);
        }

        if (!$agen) {
            return response()->json(['message' => 'Nomor tidak ditemukan sebagai agen'], 404);
        }

        if ((string) $agen['NoKtp'] !== $data['nik']) {
            $gagal++;
            Cache::put($kunciGagal, $gagal, now()->addDay());

            if ($gagal >= self::MAX_NIK_SALAH) {
                return $this->habisNik();
            }

            return response()->json([
                'message' => 'NIK tidak cocok. Sisa ' . (self::MAX_NIK_SALAH - $gagal) . ' percobaan',
            ], 422);
        }

        Cache::forget($kunciGagal);
        $token = DB::transaction(function () use ($data, $agen) {
            $user = User::create([
                'phone' => $data['no_telp'],
                'password' => $data['password'],
                'name' => $agen['NamaAgen'] ?? null,
                'status' => 'active',
            ]);

            return $user->createToken('mobile', ['*'], now()->addMinutes(self::TOKEN_TTL_MINUTES))
                ->plainTextToken;
        });

        return response()->json([
            'message' => 'Verifikasi berhasil',
            'token' => $token,
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
        ]);
    }

    private function habisNik(): JsonResponse
    {
        return response()->json([
            'message' => 'Percobaan NIK habis. Silakan login lagi.',
            'habis' => true,
        ], 429);
    }

    // ===== helper foto =====
    public function uploadDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'ktp' => 'required|string',
            'selfie' => 'required|string',
        ]);

        $pendaftaran = AgentPendaftaran::where('psmkode', $request->user()->phone)->firstOrFail();

        $pendaftaran->psmnokk = $this->simpanFoto($pendaftaran, 'ktp', $request->input('ktp'));
        $pendaftaran->psmins = $this->simpanFoto($pendaftaran, 'selfie', $request->input('selfie'));
        $pendaftaran->save();

        return response()->json([
            'message' => 'Dokumen tersimpan',
            'ktp_path' => 'agent/' . $pendaftaran->psmnokk,
            'selfie_path' => 'agent/' . $pendaftaran->psmins,
        ]);
    }

    private function simpanFoto(AgentPendaftaran $pendaftaran, string $jenis, string $base64): string
    {
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);

        $biner = base64_decode($base64, true);
        if ($biner === false) {
            throw ValidationException::withMessages([$jenis => "Foto {$jenis} tidak valid"]);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($biner);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
        if ($ext === null) {
            throw ValidationException::withMessages([$jenis => "Foto {$jenis} harus JPG atau PNG"]);
        }

        // 8 digit terakhir nomor HP + _1 (ktp) atau _2 (selfie)
        $digitAkhir = substr(preg_replace('/\D/', '', $pendaftaran->psmkode), -8);
        $nama = $digitAkhir . '_' . ($jenis === 'ktp' ? '1' : '2');

        // hapus versi lama (jpg atau png) supaya tidak dobel
        foreach (['jpg', 'png'] as $e) {
            Storage::disk('local')->delete("agent/{$nama}.{$e}");
        }

        $file = "{$nama}.{$ext}";
        Storage::disk('local')->put("agent/{$file}", $biner);

        return $file;
    }

    private function simpanPendaftaran(string $phone, array $data): void
    {
        AgentPendaftaran::updateOrCreate(
            ['psmkode' => $phone],
            [
                'psmtype' => strtolower($data['jenisAgen']),
                'psmsts' => 'INPG',
                'psmhp' => null,
                'psmnama' => $data['namaLengkap'],
                'psmtmplahir' => $data['kotaLahir'],
                'psmtgllahir' => $data['tanggalLahir'],
                'psmgender' => $data['jenisKelamin'] === 'LAKI-LAKI' ? 'L' : 'P',
                'psmktp' => $data['nik'],
                'psmwn' => $data['kewarganegaraan'],
                'psmagama' => $data['agama'],
                'psmstsnikah' => $data['statusKawin'],
                'psmgoldrh' => $data['golDarah'],
                'psmemail' => $data['email'] ?? null,
                'psmalamat' => $data['alamat'],
                'psmrt' => $data['rt'],
                'psmrw' => $data['rw'],
                'psmprop' => $data['provinsi'],
                'psmkota' => $data['kota'],
                'psmkec' => $data['kecamatan'],
                'psmkel' => $data['kelurahan'],
                'psmkdpos' => $data['kodePos'],
                'psmnpwp' => $data['punyaNpwp'] === 'YA' ? $data['nomorNpwp'] : null,
                'psmbank' => $data['bank'],
                'psmrekbank' => $data['nomorRekening'],
                'psmiddeb' => $data['namaRekening'],
                'psmkdtmp2' => $data['nip'],
                'psmoto' => $data['pernahOtomotif'],
                'psmjmltlk' => $data['pernahOtomotif'] === 'YA' ? $data['unitPerBulan'] : null,
                'psmtgl' => now()->toDateString(),
            ]
        );
    }
    private function kirimKeKsapi(string $noHp, array $data): void
    {
        $payload = [
            'NoHp' => $noHp,
            'jenisAgen' => $data['jenisAgen'],
            'nip' => $data['nip'],
            'nik' => $data['nik'],
            'namaLengkap' => $data['namaLengkap'],
            'kotaLahir' => $data['kotaLahir'],
            'tanggalLahir' => $data['tanggalLahir'],
            'jenisKelamin' => $data['jenisKelamin'] === 'LAKI-LAKI' ? 'L' : 'P',
            'golDarah' => $data['golDarah'],
            'kewarganegaraan' => $data['kewarganegaraan'],
            'agama' => $data['agama'],
            'statusKawin' => $data['statusKawin'],
            'email' => $data['email'] ?? '',
            'alamat' => $data['alamat'],
            'rt' => $data['rt'],
            'rw' => $data['rw'],
            'provinsi' => $data['provinsi'],
            'kota' => $data['kota'],
            'kecamatan' => $data['kecamatan'],
            'kelurahan' => $data['kelurahan'],
            'kodePos' => $data['kodePos'],
            'punyaNpwp' => $data['punyaNpwp'],
            'nomorNpwp' => $data['nomorNpwp'] ?? '',
            'bank' => $data['bank'],
            'nomorRekening' => $data['nomorRekening'],
            'namaRekening' => $data['namaRekening'],
            'pekerjaan' => $data['pekerjaan'],
            'subPekerjaan' => $data['subPekerjaan'],
            'pernahOtomotif' => $data['pernahOtomotif'],
            'unitPerBulan' => (string) ($data['unitPerBulan'] ?? ''),
            'tahuDari' => $data['tahuDari'],
        ];

        try {
    $res = Http::timeout(15)
        ->acceptJson()
        ->post(config('services.ksapi.url'), [
            'api_appkliknsc' => [['Request' => 'KLIKNSC']],
            'Data' => [$payload],
        ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('ksapisvr tidak bisa dihubungi: ' . $e->getMessage(), 0, $e);
        }

        if ($res->failed()) {
            throw new RuntimeException('ksapisvr menolak atau tidak merespons: HTTP ' . $res->status());
        }
    }

public function nipReferensi(KsAgentRepositoryInterface $ksAgent): JsonResponse
{
    try {
        $list = $ksAgent->semuaNipReferensi();
    } catch (RuntimeException $e) {
        return response()->json(['message' => 'Layanan sedang tidak tersedia'], 503);
    }

    return response()->json([
        'data' => array_map(fn ($item) => [
            'nip'     => $item['Nip'],
            'nama'    => $item['Nama'],
            'cabang'  => $item['Cabang'],
            'jabatan' => $item['Jabatan'],
        ], $list),
    ]);
}

}