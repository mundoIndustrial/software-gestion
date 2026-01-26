<?php

namespace App\Application\Services\Asesores;

/**
 * AsesoresApplicationFacadeService
 * 
 * Facade que agrupa todos los servicios de Asesores
 * Simplifica inyección de dependencias en controller
 * Patrón: Facade + Service Locator
 */
class AsesoresApplicationFacadeService
{
    public function __construct(
        public readonly DashboardService $dashboard,
        public readonly NotificacionesService $notificaciones,
        public readonly PerfilService $perfil,
        public readonly EliminarPedidoService $eliminarPedido,
        public readonly ObtenerFotosService $obtenerFotos,
        public readonly AnularPedidoService $anularPedido,
        public readonly ObtenerPedidosService $obtenerPedidos,
        public readonly ObtenerProximoPedidoService $obtenerProximoPedido,
        public readonly ObtenerDatosFacturaService $obtenerDatosFactura,
        public readonly ObtenerDatosRecibosService $obtenerDatosRecibos,
        public readonly ProcesarFotosTelasService $procesarFotosTelas,
        public readonly GuardarPedidoLogoService $guardarPedidoLogo,
        public readonly GuardarPedidosService $guardarPedidos,
        public readonly ConfirmarPedidoService $confirmarPedido,
        public readonly ActualizarPedidoService $actualizarPedido,
        public readonly ObtenerPedidoDetalleService $obtenerPedidoDetalle,
    ) {}
}

