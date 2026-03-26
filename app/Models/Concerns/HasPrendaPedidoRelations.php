<?php

namespace App\Models\Concerns;

use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoProduccion;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PrendaEntrega;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaPedidoNovedadRecibo;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaVariantePed;
use App\Models\ProcesoPrenda;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasPrendaPedidoRelations
{
    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->pedidoProduccion();
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVariantePed::class, 'prenda_pedido_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
    }

    public function coloresTelas(): HasMany
    {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id');
    }

    public function fotosTelas(): HasManyThrough
    {
        return $this->hasManyThrough(
            PrendaFotoTelaPedido::class,
            PrendaPedidoColorTela::class,
            'prenda_pedido_id',
            'prenda_pedido_colores_telas_id'
        );
    }

    public function procesos(): HasMany
    {
        return $this->hasMany(PedidosProcesosPrendaDetalle::class, 'prenda_pedido_id');
    }

    public function procesosPrenda(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProcesoPrenda::class,
            PedidoProduccion::class,
            'id',
            'numero_pedido',
            'pedido_produccion_id',
            'numero_pedido'
        );
    }

    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaPedidoTalla::class, 'prenda_pedido_id');
    }

    public function prendaPedidoTallas(): HasMany
    {
        return $this->tallas();
    }

    public function entrega()
    {
        return $this->hasOne(PrendaEntrega::class, 'prenda_pedido_id');
    }

    public function anchoMetraje()
    {
        return $this->hasOne(PedidoAnchoGeneral::class, 'prenda_pedido_id');
    }

    public function novedadesRecibo()
    {
        return $this->hasMany(PrendaPedidoNovedadRecibo::class, 'prenda_pedido_id');
    }
}
