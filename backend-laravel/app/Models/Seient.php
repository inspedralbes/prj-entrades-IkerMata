<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seient extends Model
{
    protected $fillable = ['sala_id', 'fila', 'numero', 'categoria_id'];

    public function sala()
    {
        return $this->belongsTo(Sala::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaSeient::class, 'categoria_id');
    }
}
