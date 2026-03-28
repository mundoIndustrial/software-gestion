<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\CambiarEstadoPedidoUseCaseContract;

use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Cambiar Estado del Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait + EstadoPedidoCatalog
 * 
 * Antes: 58 lineas (15 lineas de lógica + 43 de validación y transiciones hardcodeadas)
 * despues: 28 lineas (solo lógica de negocio)
 * Reducción: 52%
 * 
 * Beneficios:
 * - Validaciones centralizadas
 * - Transiciones en unico lugar (EstadoPedidoCatalog)
 * - Mensajes de error consistentes
 * - Menos código repetido
 */
final class CambiarEstadoPedidoUseCase implements CambiarEstadoPedidoUseCaseContract
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(CambiarEstadoPedidoDTO $dto)
    {
        Log::info('[CambiarEstadoPedidoUseCase] Iniciando cambio de estado', [
            'pedido_id' => $dto->pedidoId,
            'nuevo_estado' => $dto->nuevoEstado,
        ]);

        // Obtener modelo Eloquent directamente (no Aggregate) porque se actualiza la BD
        $pedido = \App\Models\PedidoProduccion::findOrFail($dto->pedidoId);

        // CENTRALIZADO: Validar estado es valido (trait + catalog)
        $this->validarEstadoValido($dto->nuevoEstado);

        // CENTRALIZADO: Validar transición es permitida (trait + catalog)
        $this->validarTransicion($pedido->estado ?? 'PENDIENTE_SUPERVISOR', $dto->nuevoEstado);

        $estadoAnterior = (string) ($pedido->estado ?? '');

        // Actualizar estado
        $pedido->estado = $dto->nuevoEstado;
        if ($dto->razon) {
            $pedido->razon_cambio_estado = $dto->razon;
        }
        $pedido->save();

        if ($dto->registrarNovedad) {
            $nombreUsuario = $dto->nombreUsuario ?: 'Sistema';
            $novedad = "Estado cambiado de '{$estadoAnterior}' a '{$dto->nuevoEstado}' por {$nombreUsuario}";
            $pedido->novedades = !empty($pedido->novedades)
                ? $pedido->novedades . "\n\n" . $novedad
                : $novedad;
            $pedido->save();
        }

        Log::info('[CambiarEstadoPedidoUseCase] Estado cambiado exitosamente', [
            'pedido_id' => $pedido->id,
            'nuevo_estado' => $pedido->estado,
        ]);

        return $pedido;
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {CambiarEstadoPedidoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





