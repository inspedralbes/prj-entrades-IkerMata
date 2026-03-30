<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Peli extends Model
{
    use HasUuids;

    protected $table = 'pelis';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['uuid', 'titol', 'descripcio', 'imatge_url', 'durada_minuts', 'estat'];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function sessions()
    {
        return $this->hasMany(Sessio::class, 'esdeveniment_id');
    }
}
