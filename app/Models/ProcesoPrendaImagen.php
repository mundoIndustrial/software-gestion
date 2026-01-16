<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcesoPrendaImagen extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_procesos_imagenes';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'ruta',
        'nombre_original',
        'tipo_mime',
        'tamaño',
        'ancho',
        'alto',
        'hash_md5',
        'orden',
        'es_principal',
        'descripcion',
    ];

    protected $casts = [
        'tamaño' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'es_principal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['url'];

    // Relationships
    public function procesoPrendaDetalle(): BelongsTo
    {
        return $this->belongsTo(ProcesoPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }

    // Scopes
    public function scopePrincipal($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    public function scopePorProceso($query, $procesoPrendaDetalleId)
    {
        return $query->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);
    }

    public function scopePorHash($query, $hash)
    {
        return $query->where('hash_md5', $hash);
    }

    // Métodos
    public function marcarComoPrincipal(): self
    {
        $this->es_principal = true;
        return $this;
    }

    public function desmarcarComoPrincipal(): self
    {
        $this->es_principal = false;
        return $this;
    }

    public function esImagenPrincipal(): bool
    {
        return (bool) $this->es_principal;
    }

    /**
     * Accessor para obtener la URL completa de la imagen
     * Soporta tanto rutas relativas (storage/...) como URLs completas (http://...)
     */
    public function getUrlAttribute(): string
    {
        $ruta = $this->ruta_webp ?? $this->ruta_original ?? $this->ruta;
        
        if (!$ruta) {
            return '';
        }
        
        // Si ya es una URL completa, devolverla tal cual
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }
        
        // Si ya comienza con /storage/, devolverla tal cual
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        
        // Si comienza con 'storage/', agregar el /
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }

    public function obtenerRutaCompleta(): string
    {
        return $this->url;
    }
}
