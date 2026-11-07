<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;
use Symfony\Component\Uid\Uuid;

class PersonalAccessToken extends SanctumToken
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return (string) Uuid::v7();
    }
}