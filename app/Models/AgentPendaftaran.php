<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPendaftaran extends Model
{
    protected $table = 'agent';
    protected $primaryKey = 'psmprm';
    protected $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'psmtgllahir' => 'date',
        'psmtgl' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->psmprm)) {
                do {
                    $id = random_int(100000000, 2147483647);
                } while (self::where('psmprm', $id)->exists());

                $model->psmprm = $id;
            }
        });
    }
}