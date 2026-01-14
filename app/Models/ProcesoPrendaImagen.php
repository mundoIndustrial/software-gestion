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

    public function obtenerRutaCompleta(): string
    {
        return asset('storage/' . $this->ruta);
    }

    public function obtenerNombreDescarga(): string
    {
        return $this->nombre_original;
    }
}
