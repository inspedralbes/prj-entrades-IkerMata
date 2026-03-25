<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sessio extends Model
{
    use HasUuids;

    protected $table = 'sessions';
    protected $fillable = ['uuid', 'esdeveniment_id', 'sala_id', 'data_hora'];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function peli()
    {
        return $this->belongsTo(Peli::class);
    }

    public function sala()
    {
        return $this->belongsTo(Sala::class);
    }

    public function preus()
    {
        return $this->hasMany(PreuSessio::class);
    }
}
