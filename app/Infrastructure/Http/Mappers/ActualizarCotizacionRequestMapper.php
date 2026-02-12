<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Cotizacion\DTOs\ActualizarCotizacionRequestDTO;
use Illuminate\Http\Request;

final class ActualizarCotizacionRequestMapper
{
    public function map(Request $request): ActualizarCotizacionRequestDTO
    {
        $clienteId = $request->input('cliente_id');
        $clienteId = is_numeric($clienteId) ? (int) $clienteId : null;

        $nombreCliente = $request->input('cliente');
        $nombreCliente = is_string($nombreCliente) ? trim($nombreCliente) : null;

        $tipo = $request->input('tipo'); // 'borrador'|'enviada' (front)
        $esBorradorRaw = $request->input('es_borrador');

        $esBorrador = false;
        if (is_string($tipo) && strtolower(trim($tipo)) === 'borrador') {
            $esBorrador = true;
        }

        if ($esBorradorRaw !== null) {
            if (is_bool($esBorradorRaw)) {
                $esBorrador = $esBorradorRaw;
            } elseif (is_numeric($esBorradorRaw)) {
                $esBorrador = ((int) $esBorradorRaw) === 1;
            } elseif (is_string($esBorradorRaw)) {
                $esBorrador = in_array(strtolower(trim($esBorradorRaw)), ['1', 'true', 'yes', 'on'], true);
            }
        }

        $tipoVenta = $request->input('tipo_venta');
        $tipoVenta = is_string($tipoVenta) ? $tipoVenta : null;

        $tipoCotizacionCodigo = $request->input('tipo_cotizacion');
        $tipoCotizacionCodigo = is_string($tipoCotizacionCodigo) ? $tipoCotizacionCodigo : null;

        $especificaciones = $request->input('especificaciones', []);
        if (is_string($especificaciones)) {
            $especificaciones = json_decode($especificaciones, true) ?? [];
        }
        if (!is_array($especificaciones)) {
            $especificaciones = [];
        }

        $allData = $request->all();
        $prendasRecibidas = $allData['prendas'] ?? $request->input('prendas', []);
        if (is_string($prendasRecibidas)) {
            $prendasRecibidas = json_decode($prendasRecibidas, true) ?? [];
        }
        if (!is_array($prendasRecibidas)) {
            $prendasRecibidas = [];
        }

        return new ActualizarCotizacionRequestDTO(
            clienteId: $clienteId,
            nombreCliente: $nombreCliente,
            esBorrador: $esBorrador,
            tipoVenta: $tipoVenta,
            tipoCotizacionCodigo: $tipoCotizacionCodigo,
            especificaciones: $especificaciones,
            prendasRecibidas: $prendasRecibidas,
        );
    }
}
