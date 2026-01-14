<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProceso extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_procesos';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'color',
        'icono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Un tipo de proceso tiene muchos procesos de prenda
     */
    public function procesosPrenda(): HasMany
    {
        return $this->hasMany(ProcesoPrendaDetalle::class, 'tipo_proceso_id');
    }

    /**
     * Scope: Procesos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener por slug
     */
    public static function porSlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }
}
