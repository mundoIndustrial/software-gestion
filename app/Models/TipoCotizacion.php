<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCotizacion extends Model
{
    protected $table = 'tipos_cotizacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    // Constantes para los IDs de tipos de cotización
    // Se obtienen dinámicamente según el código
    public static function getIdPorCodigo(string $codigo): ?int
    {
        $tipo = self::where('codigo', $codigo)->first();
        return $tipo?->id;
    }

    /**
     * Relación con Cotizaciones
     */
    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'tipo_cotizacion_id');
    }
}
