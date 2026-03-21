<?php

namespace App\Domain\Pedidos\Services;

use App\Services\RegistroOrdenNumberService;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

/**
 * Domain Service: ValidadorNumeroPedidoService
 * 
 * Responsabilidad: Validar números de pedido según reglas de negocio
 * Patrón: Domain Service (orquesta repo + value objects)
 */
class ValidadorNumeroPedidoService
{
    public function __construct(
        private RegistroOrdenNumberService $numberService,
    ) {}

    /**
     * Validar si un número es el siguiente esperado
     */
    public function esProximoEsperado(int $numero): bool
    {
        $proximoInfo = $this->numberService->getNextPedidoInfo();
        return $numero === $proximoInfo['next_pedido'];
    }

    /**
     * Validar si un número es válido
     */
    public function esValido(int $numero): bool
    {
        try {
            new NumeroPedido($numero);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Obtener el próximo número de pedido
     */
    public function obtenerProximo(): NumeroPedido
    {
        $proximoNumero = $this->numberService->getNextNumber();
        return new NumeroPedido($proximoNumero);
    }

    /**
     * Validar número con opción de permitir cualquier número
     */
    public function validarConOpcion(int $numero, bool $permitirCualquier = false): array
    {
        if (!$this->esValido($numero)) {
            return [
                'valido' => false,
                'mensaje' => 'Número de pedido inválido',
            ];
        }

        if ($permitirCualquier) {
            return ['valido' => true];
        }

        if (!$this->esProximoEsperado($numero)) {
            $proximoInfo = $this->numberService->getNextPedidoInfo();
            return [
                'valido' => false,
                'mensaje' => "Número esperado: {$proximoInfo['next_pedido']}",
                'proximo_esperado' => $proximoInfo['next_pedido'],
            ];
        }

        return ['valido' => true];
    }
}
