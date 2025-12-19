<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogoPedidoImagen extends Model
{
    protected $table = 'logo_pedido_imagenes';

    protected $fillable = [
        'logo_pedido_id',
        'nombre_archivo',
        'url',
        'ruta_original',
        'ruta_webp',
        'tipo_archivo',
        'tamaño_archivo',
        'orden',
    ];

    protected $casts = [
        'tamaño_archivo' => 'integer',
        'orden' => 'integer',
    ];

    /**
     * Relación: Una imagen pertenece a un LogoPedido
     */
    public function logoPedido(): BelongsTo
    {
        return $this->belongsTo(LogoPedido::class, 'logo_pedido_id');
    }

    /**
     * Obtiene la URL de la imagen para mostrar en el cliente
     */
    public function getUrlMuestraAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        } elseif ($this->ruta_webp) {
            return asset('storage/' . $this->ruta_webp);
        } elseif ($this->ruta_original) {
            return asset('storage/' . $this->ruta_original);
        }
        return null;
    }
}
