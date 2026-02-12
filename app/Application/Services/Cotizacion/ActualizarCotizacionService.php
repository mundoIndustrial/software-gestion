<?php

namespace App\Application\Services\Cotizacion;

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Application\Cotizacion\Services\ObtenerOCrearClienteService;
use App\Models\Cotizacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Application\Services\Cotizacion\CotizacionBorradorSyncService;

final class ActualizarCotizacionService
{
    public function __construct(
        private readonly ObtenerOCrearClienteService $obtenerOCrearClienteService,
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly CotizacionBorradorSyncService $cotizacionBorradorSyncService,
    ) {
    }

    public function ejecutar(Cotizacion $cotizacion, Request $request): Cotizacion
    {
        $clienteId = $request->input('cliente_id');
        $nombreCliente = $request->input('cliente');

        if ($nombreCliente && !$clienteId) {
            $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
            $clienteId = $cliente->id;
        }

        $tipo = $request->input('tipo');
        $esBorrador = ($tipo === 'borrador' || $request->input('es_borrador') === '1' || $request->input('es_borrador') === true);
        $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

        $numeroCotizacion = $cotizacion->numero_cotizacion;
        if (!$esBorrador && !$numeroCotizacion) {
            $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear((int) $cotizacion->asesor_id);
            $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
        }

        $tipoCotizacionEnviado = $request->input('tipo_cotizacion');
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
            'tipo_venta' => $request->input('tipo_venta'),
            'es_borrador' => $esBorrador,
            'estado' => $estado,
            'numero_cotizacion' => $numeroCotizacion,
            'tipo_cotizacion_id' => $tipoCotizacionId,
            'fecha_envio' => !$esBorrador ? Carbon::now('America/Bogota') : null,
        ];

        $especificacionesNuevas = $request->input('especificaciones', []);
        if (is_string($especificacionesNuevas)) {
            $especificacionesNuevas = json_decode($especificacionesNuevas, true) ?? [];
        }

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

        $allData = $request->all();
        $prendasRecibidas = $allData['prendas'] ?? $request->input('prendas', []);
        if (is_string($prendasRecibidas)) {
            $prendasRecibidas = json_decode($prendasRecibidas, true) ?? [];
        }

        if (is_array($prendasRecibidas)) {
            $this->cotizacionBorradorSyncService->sincronizarPrendasCotizacion($cotizacion, $prendasRecibidas);
        }

        return $cotizacion;
    }
}
