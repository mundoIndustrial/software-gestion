<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoLogoCotizacion extends Model
{
    protected $table = 'tipo_logo_cotizaciones';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'color',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación: Un tipo puede estar en múltiples cotizaciones
     */
    public function logoCotizacionTecnicas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnica::class);
    }

    /**
     * Scope: Obtener solo tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /**
     * Scope: Buscar por código
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', $codigo);
    }
}
