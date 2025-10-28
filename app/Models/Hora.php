<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hora extends Model
{
    use HasFactory;

    protected $fillable = ['hora', 'rango'];

    public function registrosPisoCorte()
    {
        return $this->hasMany(RegistroPisoCorte::class);
    }
}
