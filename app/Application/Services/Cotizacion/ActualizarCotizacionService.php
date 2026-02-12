<?php

namespace App\Application\Services\Cotizacion;

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Application\Cotizacion\Services\ObtenerOCrearClienteService;
use App\Application\Cotizacion\DTOs\ActualizarCotizacionRequestDTO;
use App\Models\Cotizacion;
use Carbon\Carbon;
use App\Application\Services\Cotizacion\CotizacionBorradorSyncService;

final class ActualizarCotizacionService
{
    public function __construct(
        private readonly ObtenerOCrearClienteService $obtenerOCrearClienteService,
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly CotizacionBorradorSyncService $cotizacionBorradorSyncService,
    ) {
    }

    public function ejecutar(Cotizacion $cotizacion, ActualizarCotizacionRequestDTO $dto): Cotizacion
    {
        $clienteId = $dto->clienteId;
        $nombreCliente = $dto->nombreCliente;

        if ($nombreCliente && !$clienteId) {
            $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
            $clienteId = $cliente->id;
        }

        $esBorrador = $dto->esBorrador;
        $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

        $numeroCotizacion = $cotizacion->numero_cotizacion;
        if (!$esBorrador && !$numeroCotizacion) {
            $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear((int) $cotizacion->asesor_id);
            $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
        }

        $tipoCotizacionEnviado = $dto->tipoCotizacionCodigo;
        $tipoCotizacionId = $cotizacion->tipo_cotizacion_id;

        if ($tipoCotizacionEnviado === 'P') {
            $tipoCotizacionId = 3;
        } elseif ($tipoCotizacionEnviado === 'PL' || $tipoCotizacionEnviado === 'PB') {
            $tipoCotizacionId = 1;
        } elseif ($tipoCotizacionEnviado === 'L') {
            $tipoCotizacionId = 2;
        } else {
            $tipoCotizacionId = 1;
        }

        $datosActualizar = [
            'cliente_id' => $clienteId,
            'tipo_venta' => $dto->tipoVenta,
            'es_borrador' => $esBorrador,
            'estado' => $estado,
            'numero_cotizacion' => $numeroCotizacion,
            'tipo_cotizacion_id' => $tipoCotizacionId,
            'fecha_envio' => !$esBorrador ? Carbon::now('America/Bogota') : null,
        ];

        $especificacionesNuevas = $dto->especificaciones;

        if (!empty($especificacionesNuevas)) {
            $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
            foreach ($categoriasRequeridas as $categoria) {
                if (!isset($especificacionesNuevas[$categoria])) {
                    $especificacionesNuevas[$categoria] = [];
                }
            }

            $datosActualizar['especificaciones'] = $especificacionesNuevas;
        }

        $cotizacion->update($datosActualizar);

        if (!empty($dto->prendasRecibidas) && is_array($dto->prendasRecibidas)) {
            $this->cotizacionBorradorSyncService->sincronizarPrendasCotizacion($cotizacion, $dto->prendasRecibidas);
        }

        return $cotizacion;
    }
}
