<?php

namespace App\Application\Pedidos\Traits;

use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;

/**
 * ManejaPedidosUseCase Trait
 * 
 * Centraliza TODA la lÃ³gica de validaciÃ³n y manejo de errores comunes
 * en Use Cases que trabajan con pedidos.
 * 
 * ELIMINA:
 * - 50-60 lÃ­neas de validaciÃ³n duplicada
 * - Mensajes de error inconsistentes (throw new Exception vs InvalidArgumentException vs DomainException)
 * - LÃ³gica de bÃºsqueda/validaciÃ³n esparcida
 * - if (!$pedido) throw new... (repetida 20+ veces)
 * 
 * ANTES: Cada Use Case tenÃ­a:
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
 *           $pedido = $this->validarPedidoExiste($pedidoId);  // â† Todo validado
 *           $this->validarEstadoPermitido($pedido, 'EN_PRODUCCION');  // â† Valida estado
 *       }
 *   }
 */
trait ManejaPedidosUseCase
{
    /**
     * Validar que el pedido existe, sino lanza excepciÃ³n
     * 
     * @param int|string $pedidoIdentificador ID o nÃºmero del pedido
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

        // Intentar obtener por ID o bÃºsqueda personalizada
        $pedido = method_exists($repo, 'obtenerPorId')
            ? $repo->obtenerPorId($pedidoIdentificador)
            : (method_exists($repo, 'porId')
                ? $repo->porId($pedidoIdentificador)
                : $repo->find($pedidoIdentificador));

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
     * Validar que el pedido estÃ¡ en un estado permitido
     * 
     * @param mixed $pedido Modelo del pedido (puede ser agregado o Eloquent)
     * @param string|array $estadoPermitido Estado(s) permitido(s)
     * @throws \DomainException Si pedido no estÃ¡ en estado permitido
     */
    protected function validarEstadoPermitido($pedido, $estadoPermitido): void
    {
        $estadosPermitidos = is_array($estadoPermitido) ? $estadoPermitido : [$estadoPermitido];
        
        // Obtener estado (funciona tanto con agregado como con Eloquent)
        $estadoActual = method_exists($pedido, 'estado')
            ? (is_callable([$pedido->estado(), 'valor']) 
                ? $pedido->estado()->valor()
                : (is_object($pedido->estado()) ? $pedido->estado()->valor() : $pedido->estado()))
            : $pedido->estado;

        if (!in_array($estadoActual, $estadosPermitidos, true)) {
            throw new \DomainException(
                "OperaciÃ³n no permitida. Pedido en estado: {$estadoActual}. " .
                "Estados permitidos: " . implode(', ', $estadosPermitidos),
                400
            );
        }
    }

    /**
     * Validar transiciÃ³n de estado
     * 
     * @param string $estadoActual Estado actual
     * @param string $estadoNuevo Estado a cambiar
     * @throws \DomainException Si transiciÃ³n no es permitida
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
     * Validar que el estado es vÃ¡lido
     * 
     * @param string $estado Estado a validar
     * @throws \InvalidArgumentException Si estado no es vÃ¡lido
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
     * @param mixed $pedido Modelo del pedido (puede ser agregado o Eloquent)
     * @throws \DomainException Si pedido no tiene prendas
     */
    protected function validarTienePrendas($pedido): void
    {
        $totalPrendas = 0;
        
        // Obtener total de prendas (funciona tanto con agregado como con Eloquent)
        if (method_exists($pedido, 'totalPrendas')) {
            // Es un agregado con getter totalPrendas()
            $totalPrendas = $pedido->totalPrendas();
        } elseif (method_exists($pedido, 'prendas')) {
            // Es un modelo Eloquent con relaciÃ³n prendas()
            $totalPrendas = is_callable([$pedido, 'prendas']) 
                ? $pedido->prendas()->count() 
                : count($pedido->prendas ?? []);
        }

        if ($totalPrendas === 0) {
            throw new \DomainException(
                EstadoPedidoCatalog::obtenerMensajeError('pedido_no_tiene_prendas', [
                    'identificador' => $pedido->numero_pedido ?? $pedido->id ?? 'desconocido',
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
     * Validar que un valor no estÃ¡ vacÃ­o
     * 
     * @param mixed $valor Valor a validar
     * @param string $nombreCampo Nombre del campo (para mensaje de error)
     * @throws \InvalidArgumentException Si valor estÃ¡ vacÃ­o
     */
    protected function validarNoVacio($valor, string $nombreCampo): void
    {
        if (empty($valor)) {
            throw new \InvalidArgumentException(
                EstadoPedidoCatalog::obtenerMensajeError('validacion_fallida', [
                    'razon' => "$nombreCampo no puede estar vacÃ­o"
                ]),
                400
            );
        }
    }

    /**
     * Validar que un valor numÃ©rico es positivo
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
                    'razon' => "$nombreCampo debe ser un nÃºmero positivo"
                ]),
                400
            );
        }
    }
}

