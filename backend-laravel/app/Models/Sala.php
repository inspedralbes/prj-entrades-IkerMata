<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sala extends Model
{
    protected $table = 'sales';
    protected $fillable = ['nom', 'capacitat'];

    public function seients()
    {
        return $this->hasMany(Seient::class);
    }
}
