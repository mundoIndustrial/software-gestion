<?php

namespace App\Models;

use App\Models\News;
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
        string $nombrePrenda = 'PRENDA',
        bool $notificar = true
    ): self {
        $anexo = self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => $prendaId ?? 0,
            'descripcion'          => 'PRENDA NUEVA: ' . strtoupper($nombrePrenda),
            'created_by'           => auth()->id(),
        ]);

        if ($notificar) {
            // Notificar a bodega
            $pedido = $anexo->pedido;
            News::create([
                'event_type' => 'prenda_agregada',
                'table_name' => 'pedido_anexos_historial',
                'record_id' => $anexo->id,
                'description' => "Se ha AGREGADO la prenda " . strtoupper($nombrePrenda) . " a la Orden #" . ($pedido->numero_pedido ?? $pedido->id),
                'user_id' => auth()->id(),
                'pedido' => $pedido->numero_pedido ?? $pedido->id,
            ]);
        }

        return $anexo;
    }

    /**
     * Registra que se editó una prenda de un pedido ya existente.
     */
    public static function registrarPrendaEditada(
        int $pedidoProduccionId,
        int $prendaId,
        string $nombrePrenda = 'PRENDA',
        string $accion = '',
        ?string $detalle = null,
        bool $notificar = true
    ): self {
        $descripcion = 'PRENDA EDITADA: ' . strtoupper($nombrePrenda);
        if ($accion !== '') {
            $descripcion .= ' (' . $accion . ')';
        }
        $anexo = self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'PRENDA',
            'referencia_id'        => $prendaId,
            'descripcion'          => $descripcion,
            'detalle'              => $detalle,
            'created_by'           => auth()->id(),
        ]);

        if ($notificar) {
            // Notificar a bodega
            $pedido = $anexo->pedido;
            News::create([
                'event_type' => 'prenda_modificada',
                'table_name' => 'pedido_anexos_historial',
                'record_id' => $anexo->id,
                'description' => "Se ha MODIFICADO la prenda " . strtoupper($nombrePrenda) . " en la Orden #" . ($pedido->numero_pedido ?? $pedido->id),
                'user_id' => auth()->id(),
                'pedido' => $pedido->numero_pedido ?? $pedido->id,
            ]);
        }

        return $anexo;
    }

    /**
     * Registra que se agregó un EPP nuevo en un pedido ya existente.
     */
    public static function registrarEppNuevo(
        int $pedidoProduccionId,
        int $pedidoEppId,
        int $eppId,
        bool $notificar = true
    ): self {
        $anexo = self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'EPP',
            'referencia_id'        => $pedidoEppId,
            'descripcion'          => 'EPP NUEVO #' . $eppId,
            'created_by'           => auth()->id(),
        ]);

        if ($notificar) {
            // Notificar a bodega
            $pedido = $anexo->pedido;
            News::create([
                'event_type' => 'epp_agregado',
                'table_name' => 'pedido_anexos_historial',
                'record_id' => $anexo->id,
                'description' => "Se ha AGREGADO un EPP a la Orden #" . ($pedido->numero_pedido ?? $pedido->id),
                'user_id' => auth()->id(),
                'pedido' => $pedido->numero_pedido ?? $pedido->id,
            ]);
        }

        return $anexo;
    }

    /**
     * Registra que se editó un EPP de un pedido ya existente.
     */
    public static function registrarEppEditado(
        int $pedidoProduccionId,
        int $pedidoEppId,
        int $eppId,
        bool $notificar = true
    ): self {
        $anexo = self::create([
            'pedido_produccion_id' => $pedidoProduccionId,
            'tipo'                 => 'EPP',
            'referencia_id'        => $pedidoEppId,
            'descripcion'          => 'EPP EDITADO #' . $eppId,
            'created_by'           => auth()->id(),
        ]);

        if ($notificar) {
            // Notificar a bodega
            $pedido = $anexo->pedido;
            News::create([
                'event_type' => 'epp_modificado',
                'table_name' => 'pedido_anexos_historial',
                'record_id' => $anexo->id,
                'description' => "Se ha MODIFICADO un EPP en la Orden #" . ($pedido->numero_pedido ?? $pedido->id),
                'user_id' => auth()->id(),
                'pedido' => $pedido->numero_pedido ?? $pedido->id,
            ]);
        }

        return $anexo;
    }
}
