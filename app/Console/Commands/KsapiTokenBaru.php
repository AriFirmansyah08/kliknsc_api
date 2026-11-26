<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KsapiTokenBaru extends Command
{
    protected $signature = 'ksapi:token-baru';

    protected $description = 'Buat KSAPI_CALLBACK_TOKEN baru dan tulis ke .env';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error("File .env tidak ditemukan di: {$envPath}");
            return self::FAILURE;
        }

        $token = bin2hex(random_bytes(24));
        $isi = file_get_contents($envPath);

        if (preg_match('/^KSAPI_CALLBACK_TOKEN=/m', $isi)) {
            $baru = preg_replace(
                '/^KSAPI_CALLBACK_TOKEN=[^\r\n]*/m',
                "KSAPI_CALLBACK_TOKEN={$token}",
                $isi,
                1,
            );
        } else {
            $baru = rtrim($isi, "\r\n") . "\r\nKSAPI_CALLBACK_TOKEN={$token}\r\n";
        }

        file_put_contents($envPath, $baru);

        $this->call('config:clear');

        $this->info("Token baru: {$token}");
        $this->line('Panjang: ' . strlen($token) . ', awalan: ' . substr($token, 0, 6));

        return self::SUCCESS;
    }
}