<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiempoCiclo extends Model
{
    use HasFactory;

    protected $fillable = ['tela_id', 'maquina_id', 'tiempo_ciclo'];

    protected $casts = [
        'tiempo_ciclo' => 'decimal:2',
    ];

    public function tela()
    {
        return $this->belongsTo(Tela::class);
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }
}
