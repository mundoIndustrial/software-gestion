<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorHoraExtra extends Model
{
    protected $table = 'valor_hora_extra';
    
    protected $fillable = [
        'codigo_persona',
        'id_reporte',
        'valor',
    ];

    /**
     * Relación con el modelo Personal
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'codigo_persona', 'codigo_persona');
    }

    /**
     * Relación con el modelo ReportePersonal
     */
    public function reporte()
    {
        return $this->belongsTo(\App\Models\ReportePersonal::class, 'id_reporte', 'id');
    }
}
