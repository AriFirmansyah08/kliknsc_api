<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Uuid;

class Agent extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE_CLOSING = 'inactive_closing';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'kode_agen',
        'jenis_agen',
        'status',
        'kba_nama',
        'kba_phone',
        'submitted_at',
        'activated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

public function newUniqueId(): string
{
    return (string) Uuid::v7();
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}