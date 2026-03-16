<?php

namespace App\Application\RegistrosOrdenes\Services;

use App\Application\RegistrosOrdenes\Contracts\TransformacionOrdenService;
use App\Constants\AreaOptions;

/**
 * TransformacionOrdenServiceImpl
 * 
 * Implementación para transformación de órdenes
 * Extrae la lógica de transformación del controlador
 */
class TransformacionOrdenServiceImpl implements TransformacionOrdenService
{
    public function transformarParaListado($orden, array $areasMap = [], array $encargadosMap = []): array
    {
        $ordenArray = $orden->toArray();

        // Agregar información de área y encargado
        $numeroPedido = $orden->numero_pedido;
        $ordenArray['area'] = $areasMap[$numeroPedido]['area'] ?? '-';
        $ordenArray['encargado_orden'] = $encargadosMap[$numeroPedido] ?? '-';

        // Campos sensibles a filtrar
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];
        
        foreach ($camposOcultosGlobal as $campo) {
            unset($ordenArray[$campo]);
        }

        // Para no-asesores, ocultar cotización
        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            unset($ordenArray['cotizacion_id']);
            unset($ordenArray['numero_cotizacion']);
        }

        // Agregar nombre de asesor
        if ($orden->asesora) {
            $ordenArray['asesor'] = $orden->asesora->name ?? '';
            $ordenArray['asesora'] = $orden->asesora->name ?? '';
        } else {
            $ordenArray['asesor'] = '';
            $ordenArray['asesora'] = '';
        }

        return $ordenArray;
    }

    public function transformarParaDetalle($orden): array
    {
        $ordenArray = $orden->toArray();

        // Verificar si es cotización
        $esCotizacion = !empty($orden->cotizacion_id);
        $ordenArray['es_cotizacion'] = $esCotizacion;

        // Campos a ocultar
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];
        $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];

        // Agregar nombre de asesor
        if ($orden->asesora) {
            $ordenArray['asesor'] = $orden->asesora->name ?? '';
            $ordenArray['asesora'] = $orden->asesora->name ?? '';
        } else {
            $ordenArray['asesor'] = '';
            $ordenArray['asesora'] = '';
        }

        // Agregar cliente
        try {
            $cliente = \App\Models\Cliente::find($ordenArray['cliente_id'] ?? null);
            $ordenArray['cliente_nombre'] = $cliente ? $cliente->nombre : ($ordenArray['cliente'] ?? '');
        } catch (\Exception $e) {
            $ordenArray['cliente_nombre'] = $ordenArray['cliente'] ?? '';
        }

        // Eliminar campos ocultos
        foreach ($camposOcultosGlobal as $campo) {
            unset($ordenArray[$campo]);
        }

        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($ordenArray[$campo]);
            }
        }

        return $ordenArray;
    }

    public function transformarPrendas($prendas): array
    {
        $resultado = [];

        foreach ($prendas as $prenda) {
            $prendasArray = $prenda->toArray();

            // Normalizar fotos
            if (isset($prendasArray['fotos']) && is_array($prendasArray['fotos'])) {
                $prendasArray['fotos'] = array_map(fn($foto) => [
                    'id' => $foto['id'] ?? null,
                    'ruta' => $this->normalizarRuta($foto['ruta'] ?? ''),
                ], $prendasArray['fotos']);
            }

            $resultado[] = $prendasArray;
        }

        return $resultado;
    }

    private function normalizarRuta($ruta): ?string
    {
        if (empty($ruta)) {
            return null;
        }

        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }

        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }

        return '/storage/' . ltrim($ruta, '/');
    }
}
