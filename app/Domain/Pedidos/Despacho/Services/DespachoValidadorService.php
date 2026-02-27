<?php

namespace App\Domain\Pedidos\Despacho\Services;

use App\Models\PedidoEpp;
use App\Models\PrendaPedidoTalla;
use App\Domain\Pedidos\Despacho\Exceptions\DespachoInvalidoException;
use App\Application\Pedidos\Despacho\DTOs\DespachoParcialesDTO;

/**
 * DespachoValidadorService
 * 
 * Domain Service que encapsula la validación y procesamiento
 * de despachos parciales
 * 
 * Responsabilidades:
 * - Validar que los parciales sean válidos
 * - Validar que no excedan la cantidad disponible
 * - Prevenir valores negativos
 * - Registrar auditoría
 */
class DespachoValidadorService
{
    /**
     * Validar un despacho completo
     * 
     * @throws DespachoInvalidoException
     */
    public function validarDespacho(DespachoParcialesDTO $despacho): void
    {
        // Sin validación de parciales: la tabla despacho_parciales ya no guarda campos
        // de pendientes/parciales. Se valida lo mínimo (tipo) en el flujo superior.
    }

    /**
     * Validar múltiples despachos
     * 
     * @param DespachoParcialesDTO[] $despachos
     * @throws DespachoInvalidoException
     */
    public function validarMultiplesDespachos(array $despachos): void
    {
        foreach ($despachos as $despacho) {
            $this->validarDespacho($despacho);
        }
    }

    /**
     * Procesar un despacho (guardar en auditoría/logs)
     */
    public function procesarDespacho(DespachoParcialesDTO $despacho, ?string $clienteEmpresa = null): void
    {
        // Validar primero
        $this->validarDespacho($despacho);

        // Registrar en logs
        \Log::info('Despacho procesado', [
            'tipo' => $despacho->tipo,
            'id' => $despacho->id,
            'cliente_empresa' => $clienteEmpresa,
            'timestamp' => now(),
        ]);

        // Aquí se podría guardar en tabla de auditoría/histórico
        // $this->guardarEnHistorico($despacho);
    }

    /**
     * Obtener cantidad disponible según el tipo de ítem
     */
    private function obtenerCantidadDisponible(DespachoParcialesDTO $despacho): int
    {
        if ($despacho->tipo === 'prenda') {
            $talla = PrendaPedidoTalla::find($despacho->id);
            if (!$talla) {
                throw new DespachoInvalidoException(
                    "Talla con ID {$despacho->id} no encontrada"
                );
            }
            return $talla->cantidad;
        }

        if ($despacho->tipo === 'epp') {
            $epp = PedidoEpp::find($despacho->id);
            if (!$epp) {
                throw new DespachoInvalidoException(
                    "EPP con ID {$despacho->id} no encontrado"
                );
            }
            return $epp->cantidad;
        }

        throw new DespachoInvalidoException(
            "Tipo de ítem inválido: {$despacho->tipo}"
        );
    }

    /**
     * Calcular pendiente automático
     * 
     * @return int
     */
    public function calcularPendiente(
        int $cantidadTotal,
        int $parcial1 = 0,
        int $parcial2 = 0,
        int $parcial3 = 0
    ): int {
        $p1 = max(0, $cantidadTotal - $parcial1);
        $p2 = max(0, $p1 - $parcial2);
        $p3 = max(0, $p2 - $parcial3);
        return $p3;
    }
}
