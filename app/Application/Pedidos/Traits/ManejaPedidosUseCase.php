<?php

namespace App\Application\Pedidos\Traits;

use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;

/**
 * ManejaPedidosUseCase Trait
 * 
 * Centraliza TODA la lógica de validación y manejo de errores comunes
 * en Use Cases que trabajan con pedidos.
 * 
 * ELIMINA:
 * - 50-60 líneas de validación duplicada
 * - Mensajes de error inconsistentes (throw new Exception vs InvalidArgumentException vs DomainException)
 * - Lógica de búsqueda/validación esparcida
 * - if (!$pedido) throw new... (repetida 20+ veces)
 * 
 * ANTES: Cada Use Case tenía:
 *   $pedido = $this->repo->buscar($id);
 *   if (!$pedido) throw new InvalidArgumentException("Pedido... no encontrado");
 * 
 * AHORA: Solo:
 *   $pedido = $this->validarPedidoExiste($id);
 * 
 * Uso en Use Cases:
 *   class MiUseCase {
 *       use ManejaPedidosUseCase;
 *       
 *       public function ejecutar(int $pedidoId) {
 *           $pedido = $this->validarPedidoExiste($pedidoId);  // ← Todo validado
 *           $this->validarEstadoPermitido($pedido, 'EN_PRODUCCION');  // ← Valida estado
 *       }
 *   }
 */
trait ManejaPedidosUseCase
{
    /**
     * Validar que el pedido existe, sino lanza excepción
     * 
     * @param int|string $pedidoIdentificador ID o número del pedido
     * @param mixed $repository Repository a usar (inyectado como propiedad)
     * @return mixed Pedido validado
     * @throws \DomainException Si pedido no existe
     */
    protected function validarPedidoExiste($pedidoIdentificador, $repository = null): mixed
    {
        // Si no se especifica repository, usar la propiedad del Use Case
        $repo = $repository ?? $this->pedidoRepository ?? null;

        if (!$repo) {
            throw new \InvalidArgumentException('No hay repository disponible para validar pedido');
        }

        // Intentar obtener por ID o búsqueda personalizada
        $pedido = method_exists($repo, 'porId')
            ? $repo->porId($pedidoIdentificador)
            : $repo->find($pedidoIdentificador);

        if (!$pedido) {
            throw new \DomainException(
                EstadoPedidoCatalog::obtenerMensajeError('pedido_no_encontrado', [
                    'identificador' => $pedidoIdentificador
                ]),
                404
            );
        }

        return $pedido;
    }

    /**
     * Validar que el pedido está en un estado permitido
     * 
     * @param mixed $pedido Modelo del pedido
     * @param string|array $estadoPermitido Estado(s) permitido(s)
     * @throws \DomainException Si pedido no está en estado permitido
     */
    protected function validarEstadoPermitido($pedido, $estadoPermitido): void
    {
        $estadosPermitidos = is_array($estadoPermitido) ? $estadoPermitido : [$estadoPermitido];
        $estadoActual = $pedido->estado;

        if (!in_array($estadoActual, $estadosPermitidos, true)) {
            throw new \DomainException(
                "Operación no permitida. Pedido en estado: {$estadoActual}. " .
                "Estados permitidos: " . implode(', ', $estadosPermitidos),
                400
            );
        }
    }

    /**
     * Validar transición de estado
     * 
     * @param string $estadoActual Estado actual
     * @param string $estadoNuevo Estado a cambiar
     * @throws \DomainException Si transición no es permitida
     */
    protected function validarTransicion(string $estadoActual, string $estadoNuevo): void
    {
        if (!EstadoPedidoCatalog::esTransicionPermitida($estadoActual, $estadoNuevo)) {
            throw new \DomainException(
                EstadoPedidoCatalog::obtenerMensajeError('transicion_no_permitida', [
                    'estado_actual' => $estadoActual,
                    'estado_nuevo' => $estadoNuevo,
                ]),
                400
            );
        }
    }

    /**
     * Validar que el estado es válido
     * 
     * @param string $estado Estado a validar
     * @throws \InvalidArgumentException Si estado no es válido
     */
    protected function validarEstadoValido(string $estado): void
    {
        if (!EstadoPedidoCatalog::esValido($estado)) {
            throw new \InvalidArgumentException(
                EstadoPedidoCatalog::obtenerMensajeError('estado_invalido', [
                    'estado' => $estado,
                    'estados_validos' => implode(', ', EstadoPedidoCatalog::ESTADOS_VALIDOS),
                ]),
                400
            );
        }
    }

    /**
     * Validar que el pedido tiene prendas
     * 
     * @param mixed $pedido Modelo del pedido
     * @throws \DomainException Si pedido no tiene prendas
     */
    protected function validarTienePrendas($pedido): void
    {
        $totalPrendas = method_exists($pedido, 'prendas')
            ? $pedido->prendas()->count()
            : ($pedido->totalPrendas ?? 0);

        if ($totalPrendas === 0) {
            throw new \DomainException(
                EstadoPedidoCatalog::obtenerMensajeError('pedido_no_tiene_prendas', [
                    'identificador' => $pedido->numero_pedido ?? $pedido->id,
                ]),
                400
            );
        }
    }

    /**
     * Validar que un objeto existe
     * 
     * @param mixed $objeto Objeto a validar
     * @param string $tipoObjeto Nombre del tipo (ej: "Prenda", "EPP")
     * @param int|string $identificador Identificador del objeto
     * @throws \DomainException Si objeto no existe
     */
    protected function validarObjetoExiste($objeto, string $tipoObjeto, $identificador): void
    {
        if (!$objeto) {
            throw new \DomainException(
                EstadoPedidoCatalog::obtenerMensajeError('pedido_no_encontrado', [
                    'identificador' => "$tipoObjeto {$identificador}"
                ]),
                404
            );
        }
    }

    /**
     * Validar que un valor no está vacío
     * 
     * @param mixed $valor Valor a validar
     * @param string $nombreCampo Nombre del campo (para mensaje de error)
     * @throws \InvalidArgumentException Si valor está vacío
     */
    protected function validarNoVacio($valor, string $nombreCampo): void
    {
        if (empty($valor)) {
            throw new \InvalidArgumentException(
                EstadoPedidoCatalog::obtenerMensajeError('validacion_fallida', [
                    'razon' => "$nombreCampo no puede estar vacío"
                ]),
                400
            );
        }
    }

    /**
     * Validar que un valor numérico es positivo
     * 
     * @param int|float $valor Valor a validar
     * @param string $nombreCampo Nombre del campo
     * @throws \InvalidArgumentException Si valor no es positivo
     */
    protected function validarPositivo($valor, string $nombreCampo): void
    {
        if (!is_numeric($valor) || $valor <= 0) {
            throw new \InvalidArgumentException(
                EstadoPedidoCatalog::obtenerMensajeError('validacion_fallida', [
                    'razon' => "$nombreCampo debe ser un número positivo"
                ]),
                400
            );
        }
    }
}
