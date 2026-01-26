<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Eloquent: Epp
 * 
 * Capa de persistencia - Encapsula acceso a tabla "epps"
 * Utilizado por EppRepository para mapear a agregados de dominio
 */
class Epp extends Model
{
        use HasFactory;

    protected $fillable = [
        'nombre_completo',
        'marca',
        'tipo',
        'talla',
        'color',
        'descripcion',
        'activo',
        'categoria_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tipo' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * NOTA: Los campos 'codigo' y 'categoria_id' NO existen en la tabla
     * La tabla solo tiene: id, nombre_completo, marca, tipo, talla, color, descripcion, activo, created_at, updated_at
     */

    /**
     * Relación: Un EPP tiene muchas imágenes (DESACTIVADA: tabla epp_imagenes no existe)
     * Esta relación está comentada para evitar errores SQL
     * Las imágenes de EPP se almacenan en pedido_epp_imagenes
     */
    // public function imagenes(): HasMany
    // {
    //     return $this->hasMany(EppImagen::class, 'epp_id');
    // }

    /**
     * Relación: Un EPP puede estar en muchos pedidos (a través de PedidoEpp)
     */
    public function pedidosEpp(): HasMany
    {
        return $this->hasMany(PedidoEpp::class);
    }

    /**
     * Relación: Un EPP puede estar en muchos pedidos (relación indirecta)
     */
    public function pedidos(): BelongsToMany
    {
        return $this->belongsToMany(
            Pedido::class,
            'pedido_epp',
            'epp_id',
            'pedido_id'
        )->using(PedidoEpp::class)
         ->withPivot('cantidad', 'tallas_medidas', 'observaciones')
         ->withTimestamps();
    }

    /**
     * Obtener la imagen principal (DESACTIVADA: tabla epp_imagenes no existe)
     */
    // public function imagenPrincipal(): ?EppImagen
    // {
    //     return $this->imagenes()->where('principal', true)->first();
    // }

    /**
     * Scope: EPP activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Buscar por código o nombre
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre_completo', 'like', "%{$termino}%")
              ->orWhere('codigo', 'like', "%{$termino}%")
              ->orWhere('marca', 'like', "%{$termino}%");
        });
    }

    /**
     * Scope: Por categoría
     */
    public function scopePorCategoria($query, string $categoria)
    {
        return $query->whereHas('categoria', fn($q) => $q->where('codigo', $categoria));
    }
}
