<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoraExtraAgregada extends Model
{
    protected $table = 'horas_extras_agregadas';

    protected $fillable = [
        'codigo_persona',
        'id_reporte',
        'fecha',
        'horas_agregadas',
        'novedad',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas_agregadas' => 'decimal:2',
    ];

    /**
     * Relación con Personal
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'codigo_persona', 'codigo_persona');
    }

    /**
     * Relación con ReportePersonal
     */
    public function reporte()
    {
        return $this->belongsTo(ReportePersonal::class, 'id_reporte');
    }

    /**
     * Relación con User
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
