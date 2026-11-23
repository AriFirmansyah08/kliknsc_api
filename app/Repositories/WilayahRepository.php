<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WilayahRepository
{
    private const FIELDS = ['Provinsi', 'Kota', 'Kecamatan', 'Kelurahan'];

    public function provinsi(): array
    {
        return $this->daftar('wilayah.provinsi', [], 'Provinsi');
    }

    public function kota(string $provinsi): array
    {
        return $this->daftar(
            'wilayah.kota.' . $provinsi,
            ['Provinsi' => $provinsi],
            'Kota'
        );
    }

    public function kecamatan(string $kota, string $provinsi): array
    {
        return $this->daftar(
            'wilayah.kecamatan.' . $provinsi . '.' . $kota,
            ['Provinsi' => $provinsi, 'Kota' => $kota],
            'Kecamatan'
        );
    }

    public function kelurahan(string $kecamatan, string $kota, string $provinsi): array
    {
        return $this->daftar(
            'wilayah.kelurahan.' . $provinsi . '.' . $kota . '.' . $kecamatan,
            ['Provinsi' => $provinsi, 'Kota' => $kota, 'Kecamatan' => $kecamatan],
            'Kelurahan'
        );
    }

    /**
     * @param string $level nama field di respons API: Provinsi|Kota|Kecamatan|Kelurahan
     */
    private function daftar(string $cacheKey, array $terisi, string $level): array
    {
        return Cache::remember($cacheKey, now()->addDay(), function () use ($terisi, $level) {
            $filter = array_merge(array_fill_keys(self::FIELDS, ''), $terisi);

            $res = Http::timeout(15)->acceptJson()->post(config('services.ksapi.url'), [
                'api_appklikwilayah' => [$filter],
            ]);

            if ($res->failed()) {
                throw new RuntimeException('Layanan ksapisvr tidak merespons');
            }

            $list = [];
preg_match_all('/\{[^{}]*\}/', $res->body(), $m);
foreach ($m[0] as $objek) {
    $data = json_decode($objek, true);
    if (!is_array($data) || empty($data[$level])) {
        continue;
    }

    $nama = trim($data[$level]);

    // Hanya level Provinsi yang bisa berisi kode angka
    if ($level === 'Provinsi' && !preg_match('/[A-Za-z]/', $nama)) {
        continue;
    }

    $list[] = ['value' => $nama, 'label' => $nama];
}

return collect($list)->unique('value')->sortBy('label')->values()->all();
        });
    }
}