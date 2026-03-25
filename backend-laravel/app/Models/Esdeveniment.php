<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Esdeveniment extends Model
{
    use HasUuids;

    protected $fillable = ['uuid', 'titol', 'descripcio', 'imatge_url', 'durada_minuts', 'estat'];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function sessions()
    {
        return $this->hasMany(Sessio::class);
    }
}
