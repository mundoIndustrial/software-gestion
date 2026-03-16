<?php

namespace App\Application\Pedidos\Services;

use App\Application\Pedidos\Contracts\PedidoFilterService;
use Illuminate\Support\Facades\Log;

/**
 * PedidoFilterServiceImpl
 * 
 * Implementación de filtrado de datos según roles
 */
class PedidoFilterServiceImpl implements PedidoFilterService
{
    public function aplicarFiltrosPorRol(array $datos, ?string $rol = null): array
    {
        if (!$rol) {
            return $datos;
        }

        switch (strtolower($rol)) {
            case 'bodeguero':
                return $this->filtrarParaBodeguero($datos);
            case 'insumos':
                return $this->filtrarParaInsumos($datos);
            default:
                return $datos;
        }
    }

    public function puedeVerPedido(int $pedidoId, string $rol): bool
    {
        if (!$rol || $rol === 'administrador') {
            return true;
        }

        // Los demás roles pueden ver cualquier pedido
        // Aquí puedes agregar lógica específica si es necesario
        return true;
    }

    public function tieneProcesoCosturaBodega(array $prendas): bool
    {
        if (!is_array($prendas)) {
            return false;
        }

        foreach ($prendas as $prenda) {
            if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
                continue;
            }

            foreach ($prenda['procesos'] as $proceso) {
                $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                $tipoLower = strtolower(trim($tipoProceso));

                if ($tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega') {
                    return true;
                }
            }
        }

        return false;
    }

    private function filtrarParaBodeguero(array $datos): array
    {
        if (!isset($datos['estado'])) {
            return $datos;
        }

        $estadoPedido = strtolower($datos['estado'] ?? '');

        // Verificar estados bloqueados
        if ($estadoPedido === 'pendiente_cartera' || $estadoPedido === 'rechazado_cartera') {
            Log::warning('[PedidoFilterService] 🔐 Bodeguero bloqueado', [
                'estado' => $datos['estado']
            ]);
            throw new \DomainException('No puedes ver recibos de pedidos en estado ' . $datos['estado']);
        }

        // Filtrar procesos: solo COSTURA-BODEGA
        if (isset($datos['prendas']) && is_array($datos['prendas'])) {
            foreach ($datos['prendas'] as &$prenda) {
                if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                    $procesosFiltrados = array_filter($prenda['procesos'], function ($proceso) {
                        $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                        $tipoLower = strtolower(trim($tipoProceso));
                        return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
                    });

                    $prenda['procesos'] = array_values($procesosFiltrados);
                }
            }
            unset($prenda);

            // Verificar que hay al menos un proceso COSTURA-BODEGA
            if (!$this->tieneProcesoCosturaBodega($datos['prendas'])) {
                throw new \DomainException('Este pedido no tiene procesos de COSTURA-BODEGA disponibles');
            }
        }

        return $datos;
    }

    private function filtrarParaInsumos(array $datos): array
    {
        $referer = request()->headers->get('referer', '');
        $vieneDeRegistros = str_contains($referer, '/registros/');
        $vieneDeInsumos = str_contains($referer, '/insumos/materiales');

        // No filtrar si viene de registros o desde insumos/materiales
        if ($vieneDeRegistros || $vieneDeInsumos) {
            return $datos;
        }

        // Filtrar: solo prendas con de_bodega = false
        if (isset($datos['prendas']) && is_array($datos['prendas'])) {
            $prendasFiltradas = array_filter($datos['prendas'], function ($prenda) {
                $deBodega = $prenda['de_bodega'] ?? false;
                if (is_string($deBodega)) {
                    $deBodega = (bool)intval($deBodega);
                }
                return !$deBodega;
            });

            $datos['prendas'] = array_values($prendasFiltradas);

            if (empty($prendasFiltradas)) {
                throw new \DomainException('Este pedido no tiene prendas disponibles para insumos (todas son de bodega)');
            }
        }

        return $datos;
    }
}
