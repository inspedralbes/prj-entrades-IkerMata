<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaSeient extends Model
{
    protected $table = 'categories_seients';
    protected $fillable = ['nom', 'color_hex'];
}
