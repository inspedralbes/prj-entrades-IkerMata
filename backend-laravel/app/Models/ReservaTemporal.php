<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaTemporal extends Model
{
    protected $table = 'reserves_temporals';

    protected $fillable = [
        'seient_id',
        'sessio_id',
        'usuari_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function seient()
    {
        return $this->belongsTo(Seient::class);
    }

    public function sessio()
    {
        return $this->belongsTo(Sessio::class);
    }

    public function usuari()
    {
        return $this->belongsTo(User::class, 'usuari_id');
    }
}
