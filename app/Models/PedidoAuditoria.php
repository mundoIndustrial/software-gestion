<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoAuditoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedidos_auditoria';

    protected $fillable = [
        'pedidos_produccion_id',
        'tipo_cambio',
        'detalles',
        'usuario_id',
        'valor_anterior',
        'valor_nuevo',
        'observaciones',
        'prenda_pedido_id',
        'proceso_prenda_detalle_id',
        'imagen_id',
        'ruta_imagen',
    ];

    protected $casts = [
        'detalles' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con PedidoProduccion
     */
    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedidos_produccion_id');
    }

    /**
     * Relación con Usuario (quién hizo el cambio)
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registrar un cambio en auditoría
     */
    public static function registrarCambio(
        $pedidoId,
        $tipoCambio,
        $usuarioId = null,
        $valorNuevo = null,
        $valorAnterior = null,
        $observaciones = null,
        $prendaPedidoId = null,
        $procesoPrendaDetalleId = null,
        $imagenId = null,
        $rutaImagen = null
    ) {
        return self::create([
            'pedidos_produccion_id' => $pedidoId,
            'tipo_cambio' => $tipoCambio,
            'usuario_id' => $usuarioId ?? auth()->id(),
            'valor_nuevo' => $valorNuevo,
            'valor_anterior' => $valorAnterior,
            'observaciones' => $observaciones,
            'prenda_pedido_id' => $prendaPedidoId,
            'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
            'imagen_id' => $imagenId,
            'ruta_imagen' => $rutaImagen,
        ]);
    }

    /**
     * Obtener historial de cambios para un pedido
     */
    public static function historialPedido($pedidoId, $limit = 10)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si el pedido tuvo cambios recientes
     */
    public static function tuvoChangiosRecientes($pedidoId, $horasAtras = 24)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->where('created_at', '>=', now()->subHours($horasAtras))
            ->exists();
    }

    /**
     * Contar cambios por tipo
     */
    public static function contarCambiosPorTipo($pedidoId)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->selectRaw('tipo_cambio, COUNT(*) as cantidad')
            ->groupBy('tipo_cambio')
            ->get();
    }

    /**
     * Registrar cambio en imagen de prenda
     */
    public static function registrarCambioImagenPrenda(
        $pedidoId,
        $prendalPedidoId,
        $tipoCambio,  // AGREGADA_IMAGEN_PRENDA, ELIMINADA_IMAGEN_PRENDA, etc.
        $imagenId = null,
        $rutaImagen = null,
        $detalles = null
    ) {
        return self::create([
            'pedidos_produccion_id' => $pedidoId,
            'tipo_cambio' => $tipoCambio,
            'prenda_pedido_id' => $prendalPedidoId,
            'imagen_id' => $imagenId,
            'ruta_imagen' => $rutaImagen,
            'detalles' => $detalles,
            'usuario_id' => auth()->id(),
        ]);
    }

    /**
     * Registrar cambio en imagen de proceso
     */
    public static function registrarCambioImagenProceso(
        $pedidoId,
        $procesoPrendaDetalleId,
        $tipoCambio,  // AGREGADA_IMAGEN_PROCESO, ELIMINADA_IMAGEN_PROCESO, etc.
        $imagenId = null,
        $rutaImagen = null,
        $detalles = null
    ) {
        return self::create([
            'pedidos_produccion_id' => $pedidoId,
            'tipo_cambio' => $tipoCambio,
            'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
            'imagen_id' => $imagenId,
            'ruta_imagen' => $rutaImagen,
            'detalles' => $detalles,
            'usuario_id' => auth()->id(),
        ]);
    }

    /**
     * Obtener historial de cambios en imágenes de un pedido
     */
    public static function historialImagenisPedido($pedidoId)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->whereIn('tipo_cambio', [
                'AGREGADA_IMAGEN_PRENDA',
                'ELIMINADA_IMAGEN_PRENDA',
                'REORDENADAS_IMAGENES_PRENDA',
                'AGREGADA_IMAGEN_PROCESO',
                'ELIMINADA_IMAGEN_PROCESO',
                'REORDENADAS_IMAGENES_PROCESO',
                'CAMBIO_IMAGEN_PRINCIPAL_PROCESO'
            ])
            ->with('usuario')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Contar imágenes agregadas por prenda
     */
    public static function contarImagenesAgregadasPorPrenda($pedidoId)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->where('tipo_cambio', 'AGREGADA_IMAGEN_PRENDA')
            ->selectRaw('prenda_pedido_id, COUNT(*) as cantidad')
            ->groupBy('prenda_pedido_id')
            ->get();
    }

    /**
     * Contar imágenes agregadas por proceso
     */
    public static function contarImagenesAgregadasPorProceso($pedidoId)
    {
        return self::where('pedidos_produccion_id', $pedidoId)
            ->where('tipo_cambio', 'AGREGADA_IMAGEN_PROCESO')
            ->selectRaw('proceso_prenda_detalle_id, COUNT(*) as cantidad')
            ->groupBy('proceso_prendra_detalle_id')
            ->get();
    }
}
