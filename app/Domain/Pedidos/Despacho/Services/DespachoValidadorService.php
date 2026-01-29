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
        // Validación mínima: solo valores negativos
        if ($despacho->parcial1 < 0 || $despacho->parcial2 < 0 || $despacho->parcial3 < 0) {
            throw new DespachoInvalidoException(
                "Parciales no pueden ser negativos: P1={$despacho->parcial1}, P2={$despacho->parcial2}, P3={$despacho->parcial3}"
            );
        }

        // NOTA: No validamos contra cantidad disponible porque los campos de pendiente
        // se ingresan manualmente sin cálculos automáticos
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
            'parcial_1' => $despacho->parcial1,
            'parcial_2' => $despacho->parcial2,
            'parcial_3' => $despacho->parcial3,
            'total_despachado' => $despacho->getTotalDespachado(),
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
