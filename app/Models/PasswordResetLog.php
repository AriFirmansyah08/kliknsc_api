<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Uuid;

class PasswordResetLog extends Model
{
    use HasUuids;

    protected $table = 'password_reset_logs';

    protected $guarded = [];

    public function newUniqueId(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public function uniqueIds(): array
    {
        return ['id'];
    }
}