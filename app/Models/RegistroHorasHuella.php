<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroHorasHuella extends Model
{
    use HasFactory;

    protected $table = 'registro_horas_huella';

    protected $fillable = [
        'id_reporte',
        'codigo_persona',
        'dia',
        'horas'
    ];

    protected $casts = [
        'horas' => 'array',
        'dia' => 'date'
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'codigo_persona', 'codigo_persona');
    }

    public function reporte()
    {
        return $this->belongsTo(ReportePersonal::class, 'id_reporte', 'id');
    }
}
