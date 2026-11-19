<?php

namespace App\Repositories\Contracts;

interface KsAgentRepositoryInterface
{
    public function adaNoTelp(string $noTelp): bool;

    public function cari(string $noTelp): ?array;

    // KsAgentRepositoryInterface.php
    public function semuaNipReferensi(): array;
}