<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CompraEntrada extends Model
{
    use HasUuids;

    protected $table = 'compres_entrades';

    protected $fillable = [
        'usuari_id',
        'sessio_id',
        'seient_id',
        'preu_pagat',
        'data_compra',
    ];

    protected $casts = [
        'data_compra' => 'datetime',
        'preu_pagat' => 'decimal:2',
    ];

    public function usuari()
    {
        return $this->belongsTo(User::class, 'usuari_id');
    }

    public function sessio()
    {
        return $this->belongsTo(Sessio::class);
    }

    public function seient()
    {
        return $this->belongsTo(Seient::class);
    }
}
