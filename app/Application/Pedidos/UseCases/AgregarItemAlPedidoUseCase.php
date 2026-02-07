<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Commands\AgregarItemAlPedidoCommand;
use App\Domain\Pedidos\CommandHandlers\AgregarItemAlPedidoHandler;
use App\Domain\Pedidos\Repositories\ItemPedidoRepository;
use App\Domain\Pedidos\DomainServices\GestorItemsPedidoDomainService;

/**
 * Application Service: AgregarItemAlPedidoUseCase
 * 
 * Caso de uso: Agregar un item (Prenda o EPP) a un pedido
 * 
 * Entrada: Datos del formulario
 * Salida: Item creado + lista actualizada y ordenada
 */
class AgregarItemAlPedidoUseCase
{
    public function __construct(
        private AgregarItemAlPedidoHandler $handler,
        private ItemPedidoRepository $itemRepository,
        private GestorItemsPedidoDomainService $gestorItems
    ) {}

    public function ejecutar(array $input): array
    {
        // Validar entrada
        $this->validarInput($input);

        // Crear comando de dominio
        $comando = new AgregarItemAlPedidoCommand(
            pedidoId: (int) $input['pedido_id'],
            tipo: $input['tipo'],
            referenciaId: (int) $input['referencia_id'],
            nombre: $input['nombre'],
            descripcion: $input['descripcion'] ?? null,
            datosPresentacion: $input['datos_presentacion'] ?? []
        );

        // Ejecutar comando (handle persiste el item)
        $item = $this->handler->ejecutar($comando);

        // Obtener items del pedido actualizados y ordenados
        $itemsActualizados = $this->itemRepository->obtenerPorPedidoOrdenados($input['pedido_id']);

        return [
            'success' => true,
            'item' => $item->aArray(),
            'items' => $itemsActualizados,
            'message' => 'Item agregado correctamente'
        ];
    }

    private function validarInput(array $input): void
    {
        $errores = [];

        if (empty($input['pedido_id'])) {
            $errores[] = 'pedido_id es requerido';
        }

        if (empty($input['tipo']) || !in_array($input['tipo'], ['prenda', 'epp'])) {
            $errores[] = 'tipo debe ser "prenda" o "epp"';
        }

        if (empty($input['referencia_id'])) {
            $errores[] = 'referencia_id es requerido';
        }

        if (empty($input['nombre'])) {
            $errores[] = 'nombre es requerido';
        }

        if ($errores) {
            throw new \InvalidArgumentException('Validación fallida: ' . implode(', ', $errores));
        }
    }
}
