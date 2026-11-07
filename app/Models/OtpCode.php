<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Uuid;

class OtpCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'phone',
        'mode',
        'code_hash',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

public function newUniqueId(): string
{
    return (string) Uuid::v7();
}
}