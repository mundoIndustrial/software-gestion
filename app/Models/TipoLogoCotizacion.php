<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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

    protected static function booted(): void
    {
        if (!Schema::hasTable((new self())->getTable())) {
            return;
        }

        // La tabla tiene UNIQUE(nombre) y UNIQUE(codigo), por eso normalizamos
        // a un unico catalogo con codigos largos para evitar conflictos legacy.
        self::query()->upsert([
            [
                'nombre' => 'BORDADO',
                'codigo' => 'BORDADO',
                'descripcion' => 'Bordado tradicional',
                'color' => '#e74c3c',
                'orden' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'ESTAMPADO',
                'codigo' => 'ESTAMPADO',
                'descripcion' => 'Estampado textil',
                'color' => '#3498db',
                'orden' => 2,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'SUBLIMADO',
                'codigo' => 'SUBLIMADO',
                'descripcion' => 'Sublimado',
                'color' => '#f39c12',
                'orden' => 3,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'DTF',
                'codigo' => 'DTF',
                'descripcion' => 'Direct to Film',
                'color' => '#9b59b6',
                'orden' => 4,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['nombre'], ['codigo', 'descripcion', 'color', 'orden', 'activo', 'updated_at']);
    }

    public function logoCotizacionTecnicas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnica::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', $codigo);
    }
}
