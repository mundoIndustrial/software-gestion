<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAnchoMetrajeInsumo extends Model
{
    protected $table = 'historial_ancho_metraje_insumos';

    protected $fillable = [
        'pedido_id',
        'prenda_pedido_id',
        'prenda_bodega_id',
        'consecutivo_recibo_id',
        'numero_recibo',
        'tipo_recibo',
        'tipo_evento',
        'accion',
        'modo',
        'color',
        'estado_anterior',
        'estado_nuevo',
        'ancho_anterior',
        'ancho_nuevo',
        'metraje_anterior',
        'metraje_nuevo',
        'usuario_id',
        'usuario_nombre',
        'detalles',
    ];

    protected $casts = [
        'detalles' => 'array',
        'ancho_anterior' => 'decimal:2',
        'ancho_nuevo' => 'decimal:2',
        'metraje_anterior' => 'decimal:2',
        'metraje_nuevo' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    public static function registrarCambio(array $data): self
    {
        $usuario = auth()->user();

        return self::create([
            'pedido_id' => $data['pedido_id'] ?? null,
            'prenda_pedido_id' => $data['prenda_pedido_id'] ?? null,
            'prenda_bodega_id' => $data['prenda_bodega_id'] ?? null,
            'consecutivo_recibo_id' => $data['consecutivo_recibo_id'] ?? null,
            'numero_recibo' => $data['numero_recibo'] ?? null,
            'tipo_recibo' => $data['tipo_recibo'] ?? null,
            'tipo_evento' => $data['tipo_evento'] ?? 'ancho_metraje',
            'accion' => $data['accion'] ?? 'actualizado',
            'modo' => $data['modo'] ?? null,
            'color' => $data['color'] ?? null,
            'estado_anterior' => $data['estado_anterior'] ?? null,
            'estado_nuevo' => $data['estado_nuevo'] ?? null,
            'ancho_anterior' => $data['ancho_anterior'] ?? null,
            'ancho_nuevo' => $data['ancho_nuevo'] ?? null,
            'metraje_anterior' => $data['metraje_anterior'] ?? null,
            'metraje_nuevo' => $data['metraje_nuevo'] ?? null,
            'usuario_id' => $data['usuario_id'] ?? $usuario?->id,
            'usuario_nombre' => $data['usuario_nombre'] ?? $usuario?->name,
            'detalles' => $data['detalles'] ?? null,
        ]);
    }
}
