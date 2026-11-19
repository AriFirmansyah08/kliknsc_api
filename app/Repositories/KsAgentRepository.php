<?php

namespace App\Repositories;

use App\Repositories\Contracts\KsAgentRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KsAgentRepository implements KsAgentRepositoryInterface
{
    public function adaNoTelp(string $noTelp): bool
    {
        return $this->cari($noTelp) !== null;
    }

    public function cari(string $noTelp): ?array
    {
        $cari = $this->normalize($noTelp);
        foreach ($this->semuaAgen() as $agen) {
            if ($this->normalize((string) ($agen['NoTelp'] ?? '')) === $cari) {
                return $agen;
            }
        }
        return null;
    }

    private function semuaAgen(): array
    {
        return Cache::remember('ksapi:agen', now()->addMinutes(10), function () {
            $res = Http::timeout(15)->acceptJson()->post(config('services.ksapi.url'), [
                'api_kliknscdtagent' => [['DTAGEN' => 'DTAGEN']],
            ]);

            if ($res->failed()) {
                throw new RuntimeException('Layanan ksapisvr tidak merespons');
            }
            $list = [];
            preg_match_all('/\{[^{}]*\}/', $res->body(), $m);
            foreach ($m[0] as $objek) {
                $data = json_decode($objek, true);
                if (is_array($data) && isset($data['NoTelp'])) {
                    $list[] = $data;
                }
            }
            return $list;
        });
    }

    private function normalize(string $v): string
    {
        return preg_replace('/\D/', '', $v);
    }

    // KsAgentRepository.php
public function semuaNipReferensi(): array
{
    return Cache::remember('ksapi:nip-referensi', now()->addMinutes(10), function () {
        $res = Http::timeout(15)->acceptJson()->post(config('services.ksapi.url'), [
            'api_appkliknipref' => [['NIPREF' => 'NIPREF']],
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Layanan ksapisvr tidak merespons');
        }

        $list = [];
        preg_match_all('/\{[^{}]*\}/', $res->body(), $m);
        foreach ($m[0] as $objek) {
            $data = json_decode($objek, true);
            if (is_array($data) && isset($data['Nip'])) {
                $list[] = $data;
            }
        }
        return $list;
    });
}
}

