<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;
use App\Application\Cotizacion\Services\ObtenerOCrearClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class StoreCotizacionRequestMapper
{
    public function __construct(
        private ObtenerOCrearClienteService $obtenerOCrearClienteService,
    ) {
    }

    public function map(Request $request, int $usuarioId): CrearCotizacionDTO
    {
        $allData = $request->all();

        $prendasRecibidas = $allData['prendas']
            ?? $allData['productos_friendly']
            ?? $request->input('prendas', $request->input('productos_friendly', []));

        $especificacionesRecibidas = $request->input('especificaciones', []);
        if (is_string($especificacionesRecibidas)) {
            $especificacionesRecibidas = json_decode($especificacionesRecibidas, true) ?? [];
        } elseif (!is_array($especificacionesRecibidas)) {
            $especificacionesRecibidas = [];
        }

        $categoriasRequeridas = ['forma_pago', 'disponibilidad', 'regimen', 'se_ha_vendido', 'ultima_venta', 'flete'];
        foreach ($categoriasRequeridas as $categoria) {
            if (!isset($especificacionesRecibidas[$categoria])) {
                $especificacionesRecibidas[$categoria] = [];
            }
        }

        $clienteId = $request->input('cliente_id');
        $nombreCliente = $request->input('cliente');

        if ($nombreCliente && !$clienteId) {
            $cliente = $this->obtenerOCrearClienteService->ejecutar($nombreCliente);
            $clienteId = $cliente->id;

            Log::info('Cliente creado/obtenido', [
                'cliente_id' => $clienteId,
                'nombre' => $nombreCliente,
            ]);
        }

        $accion = $request->input('accion');
        $esBorrador = $request->input('es_borrador');

        if ($esBorrador === null) {
            $esBorrador = ($accion === 'guardar');
        } else {
            if (is_bool($esBorrador)) {
                $esBorrador = $esBorrador;
            } elseif (is_numeric($esBorrador)) {
                $esBorrador = ((int) $esBorrador) === 1;
            } elseif (is_string($esBorrador)) {
                $esBorradorLower = strtolower(trim($esBorrador));
                $esBorrador = in_array($esBorradorLower, ['1', 'true', 'yes', 'on'], true);
            } else {
                $esBorrador = false;
            }
        }

        $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

        $tipoCotizacion = $request->input('tipo_cotizacion', 'PL');

        return CrearCotizacionDTO::desdeArray([
            'usuario_id' => $usuarioId,
            'tipo' => $tipoCotizacion,
            'cliente_id' => $clienteId,
            'prendas' => $prendasRecibidas,
            'logo' => $request->input('logo', []),
            'tipo_venta' => $request->input('tipo_venta', 'M'),
            'especificaciones' => $especificacionesRecibidas,
            'es_borrador' => $esBorrador,
            'estado' => $estado,
            'numero_cotizacion' => null,
        ]);
    }
}
