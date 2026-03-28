<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Despacho\Services\DespachoValidadorServiceContract;

use App\Application\Pedidos\Despacho\DTOs\DespachoParcialesDTO;
use App\Domain\Pedidos\Despacho\Exceptions\DespachoInvalidoException;
use App\Models\PedidoEpp;
use App\Models\PrendaPedidoTalla;

/**
 * Valida y procesa despachos parciales dentro del flujo de aplicación.
 */
class DespachoValidadorService implements DespachoValidadorServiceContract
{
    /**
     * @throws DespachoInvalidoException
     */
    public function validarDespacho(DespachoParcialesDTO $despacho): void
    {
        // Sin validación de parciales: la tabla despacho_parciales ya no guarda campos
        // de pendientes/parciales. Se valida lo mínimo (tipo) en el flujo superior.
    }

    /**
     * @param DespachoParcialesDTO[] $despachos
     *
     * @throws DespachoInvalidoException
     */
    public function validarMultiplesDespachos(array $despachos): void
    {
        foreach ($despachos as $despacho) {
            $this->validarDespacho($despacho);
        }
    }

    public function procesarDespacho(DespachoParcialesDTO $despacho, ?string $clienteEmpresa = null): void
    {
        $this->validarDespacho($despacho);

        \Log::info('Despacho procesado', [
            'tipo' => $despacho->tipo,
            'id' => $despacho->id,
            'cliente_empresa' => $clienteEmpresa,
            'timestamp' => now(),
        ]);
    }

    /**
     * @throws DespachoInvalidoException
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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {DespachoValidadorService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
