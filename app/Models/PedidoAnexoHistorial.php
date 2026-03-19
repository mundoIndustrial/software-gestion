<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAnexoHistorial extends Model
{
    protected $table = 'pedido_anexos_historial';

    protected $fillable = [
        'pedido_produccion_id',
        'tipo',
        'referencia_id',
        'descripcion',
        'detalle',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Registra que se agregó una prenda nueva en un pedido ya existente.
     */
    public static function registrarPrendaNueva(
        int $pedidoProduccionId,
        ?int $prendaId,
        string $nombrePrenda = 'PRENDA'
    ): self {
        return self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => $prendaId ?? 0,
            'descripcion'          => 'PRENDA NUEVA: ' . strtoupper($nombrePrenda),
            'created_by'           => auth()->id(),
        ]);
    }

    /**
     * Registra que se editó una prenda de un pedido ya existente.
     */
    public static function registrarPrendaEditada(
        int $pedidoProduccionId,
        int $prendaId,
        string $nombrePrenda = 'PRENDA',
        string $accion = '',
        ?string $detalle = null
    ): self {
        $descripcion = 'PRENDA EDITADA: ' . strtoupper($nombrePrenda);
        if ($accion !== '') {
            $descripcion .= ' (' . $accion . ')';
        }
        return self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => $prendaId,
            'descripcion'          => $descripcion,
            'detalle'              => $detalle,
            'created_by'           => auth()->id(),
        ]);
    }

    /**
     * Registra que se agregó un EPP nuevo en un pedido ya existente.
     */
    public static function registrarEppNuevo(
        int $pedidoProduccionId,
        int $pedidoEppId,
        int $eppId
    ): self {
        return self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'EPP',
            'referencia_id'        => $pedidoEppId,
            'descripcion'          => 'EPP NUEVO #' . $eppId,
            'created_by'           => auth()->id(),
        ]);
    }

    /**
     * Registra que se editó un EPP de un pedido ya existente.
     */
    public static function registrarEppEditado(
        int $pedidoProduccionId,
        int $pedidoEppId,
        int $eppId
    ): self {
        return self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'EPP',
            'referencia_id'        => $pedidoEppId,
            'descripcion'          => 'EPP EDITADO #' . $eppId,
            'created_by'           => auth()->id(),
        ]);
    }
}
