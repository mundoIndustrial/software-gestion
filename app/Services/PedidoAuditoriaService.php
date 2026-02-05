<?php

namespace App\Services;

use App\Models\PedidoAuditoria;

class PedidoAuditoriaService
{
    /**
     * Registrar imagen agregada a prenda
     */
    public static function registrarImagenPrendaAgregada($pedidoId, $prendalPedidoId, $imagenId, $rutaImagen)
    {
        return PedidoAuditoria::registrarCambioImagenPrenda(
            $pedidoId,
            $prendalPedidoId,
            'AGREGADA_IMAGEN_PRENDA',
            $imagenId,
            $rutaImagen,
            ['accion' => 'Imagen agregada a la prenda']
        );
    }

    /**
     * Registrar imagen eliminada de prenda
     */
    public static function registrarImagenPrendaEliminada($pedidoId, $prendalPedidoId, $imagenId, $rutaImagen)
    {
        return PedidoAuditoria::registrarCambioImagenPrenda(
            $pedidoId,
            $prendalPedidoId,
            'ELIMINADA_IMAGEN_PRENDA',
            $imagenId,
            $rutaImagen,
            ['accion' => 'Imagen eliminada de la prenda']
        );
    }

    /**
     * Registrar reordenamiento de imágenes de prenda
     */
    public static function registrarReordenImagenesPrenda($pedidoId, $prendalPedidoId, $detalles)
    {
        return PedidoAuditoria::registrarCambioImagenPrenda(
            $pedidoId,
            $prendalPedidoId,
            'REORDENADAS_IMAGENES_PRENDA',
            null,
            null,
            $detalles
        );
    }

    /**
     * Registrar imagen agregada a proceso
     */
    public static function registrarImagenProcesoAgregada($pedidoId, $procesoPrendaDetalleId, $imagenId, $rutaImagen)
    {
        return PedidoAuditoria::registrarCambioImagenProceso(
            $pedidoId,
            $procesoPrendaDetalleId,
            'AGREGADA_IMAGEN_PROCESO',
            $imagenId,
            $rutaImagen,
            ['accion' => 'Imagen agregada al proceso']
        );
    }

    /**
     * Registrar imagen eliminada de proceso
     */
    public static function registrarImagenProcesoEliminada($pedidoId, $procesoPrendaDetalleId, $imagenId, $rutaImagen)
    {
        return PedidoAuditoria::registrarCambioImagenProceso(
            $pedidoId,
            $procesoPrendaDetalleId,
            'ELIMINADA_IMAGEN_PROCESO',
            $imagenId,
            $rutaImagen,
            ['accion' => 'Imagen eliminada del proceso']
        );
    }

    /**
     * Registrar reordenamiento de imágenes de proceso
     */
    public static function registrarReordenImagenesProceso($pedidoId, $procesoPrendaDetalleId, $detalles)
    {
        return PedidoAuditoria::registrarCambioImagenProceso(
            $pedidoId,
            $procesoPrendaDetalleId,
            'REORDENADAS_IMAGENES_PROCESO',
            null,
            null,
            $detalles
        );
    }

    /**
     * Registrar cambio de imagen principal en proceso
     */
    public static function registrarImagenPrincipalProcesoActualizada($pedidoId, $procesoPrendaDetalleId, $imagenId, $rutaImagen)
    {
        return PedidoAuditoria::registrarCambioImagenProceso(
            $pedidoId,
            $procesoPrendaDetalleId,
            'CAMBIO_IMAGEN_PRINCIPAL_PROCESO',
            $imagenId,
            $rutaImagen,
            ['accion' => 'Imagen principal del proceso actualizada']
        );
    }

    /**
     * Registrar cambio genérico del pedido
     */
    public static function registrarCambio($pedidoId, $tipoCambio, $usuarioId = null, $valorNuevo = null, $valorAnterior = null, $observaciones = null)
    {
        return PedidoAuditoria::registrarCambio(
            pedidoId: $pedidoId,
            tipoCambio: $tipoCambio,
            usuarioId: $usuarioId ?? auth()->id(),
            valorNuevo: $valorNuevo,
            valorAnterior: $valorAnterior,
            observaciones: $observaciones
        );
    }

    /**
     * Obtener historial completo del pedido
     */
    public static function obtenerHistorial($pedidoId, $limit = 50)
    {
        return PedidoAuditoria::where('pedidos_produccion_id', $pedidoId)
            ->with(['usuario', 'pedidoProduccion'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si el pedido tuvo cambios en imágenes recientemente
     */
    public static function tuvoChangiosEnImagenesRecientes($pedidoId, $horasAtras = 48)
    {
        return PedidoAuditoria::where('pedidos_produccion_id', $pedidoId)
            ->whereIn('tipo_cambio', [
                'AGREGADA_IMAGEN_PRENDA',
                'ELIMINADA_IMAGEN_PRENDA',
                'AGREGADA_IMAGEN_PROCESO',
                'ELIMINADA_IMAGEN_PROCESO',
            ])
            ->where('created_at', '>=', now()->subHours($horasAtras))
            ->exists();
    }

    /**
     * Obtener cambios recientes del pedido
     */
    public static function obtenerCambiosRecientes($pedidoId, $horas = 24)
    {
        return PedidoAuditoria::where('pedidos_produccion_id', $pedidoId)
            ->where('created_at', '>=', now()->subHours($horas))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
