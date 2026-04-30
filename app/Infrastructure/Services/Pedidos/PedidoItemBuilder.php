<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PedidoItemBuilder
{
    public function __construct(
        private PedidoTipoPrendaService $pedidoTipoPrendaService,
    ) {}

    private function resolverTipoFlujoTallas(array $itemData): string
    {
        $flujo = strtolower((string) ($itemData['flujo'] ?? ''));
        if ($flujo === 'wizard') {
            return 'talla_color';
        }

        $asignaciones = $itemData['asignaciones_colores']
            ?? $itemData['asignacionesColoresPorTalla']
            ?? $itemData['asignacionesColores']
            ?? null;
        if (is_string($asignaciones)) {
            $asignaciones = json_decode($asignaciones, true);
        }
        if (is_array($asignaciones) && !empty($asignaciones)) {
            return 'talla_color';
        }

        $cantidadTalla = $itemData['cantidad_talla'] ?? $itemData['tallas'] ?? null;
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true);
        }

        if (is_array($cantidadTalla)) {
            foreach ($cantidadTalla as $generoData) {
                if (!is_array($generoData)) {
                    continue;
                }

                foreach (array_keys($generoData) as $tallaKey) {
                    if (is_string($tallaKey) && str_contains($tallaKey, '__')) {
                        return 'talla_color';
                    }
                }
            }

            return !empty($cantidadTalla) ? 'normal' : 'sin_tallas';
        }

        return 'normal';
    }

    public function crearBase(PedidoProduccion $pedido, array $itemData): PrendaPedido
    {
        $nombrePrenda = $itemData['nombre_prenda'] ?? 'SIN NOMBRE';
        $this->pedidoTipoPrendaService->asegurarTipo($nombrePrenda);

        $localId = trim((string) ($itemData['_local_id'] ?? $itemData['local_id'] ?? ''));
        $descripcionOriginal = $itemData['descripcion'] ?? null;
        $descripcionNormalizada = $this->normalizarDescripcionMultilinea($descripcionOriginal);

        Log::info('[PedidoItemBuilder] Diagnóstico descripción prenda', [
            'pedido_id' => $pedido->id,
            'nombre_prenda' => $nombrePrenda,
            'descripcion_original' => $descripcionOriginal,
            'descripcion_original_visible' => $this->representarCaracteresControl($descripcionOriginal),
            'descripcion_original_hex' => $this->textoAHex($descripcionOriginal),
            'descripcion_normalizada' => $descripcionNormalizada,
            'descripcion_normalizada_visible' => $this->representarCaracteresControl($descripcionNormalizada),
            'descripcion_normalizada_hex' => $this->textoAHex($descripcionNormalizada),
        ]);

        $payload = [
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => $nombrePrenda,
            'descripcion' => $descripcionNormalizada,
            'de_bodega' => $itemData['de_bodega'] ?? 0,
            'local_id' => $localId !== '' ? $localId : null,
        ];

        if (Schema::hasColumn('prendas_pedido', 'tipo_flujo_tallas')) {
            $payload['tipo_flujo_tallas'] = $this->resolverTipoFlujoTallas($itemData);
        }

        $prenda = PrendaPedido::create($payload);

        Log::info('[PedidoItemBuilder] Prenda base creada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'nombre' => $prenda->nombre_prenda,
        ]);

        return $prenda;
    }

    /**
     * Asegura un formato consistente de saltos de línea en DB:
     * - CRLF/CR -> LF
     * - conserva los Enter del usuario
     * - recorta solo bordes del bloque completo
     */
    private function normalizarDescripcionMultilinea(mixed $descripcion): ?string
    {
        if ($descripcion === null) {
            return null;
        }

        $texto = str_replace(["\r\n", "\r"], "\n", (string) $descripcion);
        $texto = trim($texto);

        return $texto === '' ? null : $texto;
    }

    private function representarCaracteresControl(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = (string) $valor;
        $texto = str_replace("\r", '\\r', $texto);
        $texto = str_replace("\n", '\\n', $texto);

        return $texto;
    }

    private function textoAHex(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return bin2hex((string) $valor);
    }
}
