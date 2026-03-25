<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreuSessio extends Model
{
    protected $table = 'preus_sessio';
    protected $fillable = ['sessio_id', 'categoria_id', 'preu'];

    public function sessio()
    {
        return $this->belongsTo(Sessio::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaSeient::class, 'categoria_id');
    }
}
