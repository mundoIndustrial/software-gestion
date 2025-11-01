<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_maquina'];

    public function registrosPisoCorte()
    {
        return $this->hasMany(RegistroPisoCorte::class);
    }

    public function tiempoCiclos()
    {
        return $this->hasMany(TiempoCiclo::class);
    }
}
