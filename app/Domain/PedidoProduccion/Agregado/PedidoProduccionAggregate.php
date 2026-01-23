<?php

namespace App\Domain\PedidoProduccion\Agregado;

use DateTime;
use InvalidArgumentException;

/**
 * PedidoProduccionAggregate
 * 
 * Raíz del agregado para gestión de pedidos en producción
 * Encapsula toda la lógica de negocio para pedidos
 * 
 * Responsabilidades:
 * - Crear pedidos válidos
 * - Cambiar estados de pedidos
 * - Validar transiciones de estado
 * - Gestionar prendas en pedido
 * - Encapsular reglas de negocio
 */
class PedidoProduccionAggregate
{
    // Estados válidos
    private const ESTADO_PENDIENTE = 'pendiente';
    private const ESTADO_CONFIRMADO = 'confirmado';
    private const ESTADO_EN_PRODUCCION = 'en_produccion';
    private const ESTADO_COMPLETADO = 'completado';
    private const ESTADO_ANULADO = 'anulado';

    private string $id;
    private string $numeroPedido;
    private string $cliente;
    private string $estado;
    private DateTime $fecha;
    private ?string $razonAnulacion = null;
    private array $prendas = [];
    private DateTime $fechaActualizacion;
    private ?DateTime $fechaConfirmacion = null;

    /**
     * Constructor privado - usar factory methods
     */
    private function __construct()
    {
    }

    /**
     * Factory: Crear nuevo pedido
     */
    public static function crear(array $datos): self
    {
        $instance = new self();
        
        $instance->id = $datos['id'] ?? uniqid('ped_', true);
        $instance->numeroPedido = self::validarNumeroPedido($datos['numero_pedido'] ?? '');
        $instance->cliente = self::validarCliente($datos['cliente'] ?? '');
        $instance->estado = self::ESTADO_PENDIENTE;
        $instance->fecha = $datos['fecha'] ?? new DateTime();
        $instance->fechaActualizacion = new DateTime();
        
        return $instance;
    }

    /**
     * Factory: Restaurar desde BD (Reconstitución)
     */
    public static function restaurarDesdeBD(array $datos): self
    {
        $instance = new self();
        
        $instance->id = $datos['id'];
        $instance->numeroPedido = $datos['numero_pedido'];
        $instance->cliente = $datos['cliente'];
        $instance->estado = $datos['estado'];
        $instance->fecha = new DateTime($datos['fecha']);
        $instance->fechaActualizacion = new DateTime($datos['fecha_actualizacion']);
        $instance->razonAnulacion = $datos['razon_anulacion'] ?? null;
        $instance->fechaConfirmacion = isset($datos['fecha_confirmacion']) 
            ? new DateTime($datos['fecha_confirmacion']) 
            : null;
        
        return $instance;
    }

    /**
     * Confirmar pedido
     * 
     * Transición: pendiente → confirmado
     */
    public function confirmar(): void
    {
        if ($this->estado === self::ESTADO_ANULADO) {
            throw new InvalidArgumentException("No se puede confirmar un pedido anulado");
        }

        if ($this->estado === self::ESTADO_CONFIRMADO) {
            throw new InvalidArgumentException("El pedido ya está confirmado");
        }

        if (empty($this->prendas)) {
            throw new InvalidArgumentException("No se puede confirmar un pedido sin prendas");
        }

        $this->estado = self::ESTADO_CONFIRMADO;
        $this->fechaConfirmacion = new DateTime();
        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Marcar como en producción
     * 
     * Transición: confirmado → en_produccion
     */
    public function marcarEnProduccion(): void
    {
        if ($this->estado !== self::ESTADO_CONFIRMADO) {
            throw new InvalidArgumentException(
                "Solo se puede marcar en producción un pedido confirmado. Estado actual: {$this->estado}"
            );
        }

        $this->estado = self::ESTADO_EN_PRODUCCION;
        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Marcar como completado
     * 
     * Transición: en_produccion → completado
     */
    public function marcarCompletado(): void
    {
        if ($this->estado !== self::ESTADO_EN_PRODUCCION) {
            throw new InvalidArgumentException(
                "Solo se puede marcar completado un pedido en producción. Estado actual: {$this->estado}"
            );
        }

        $this->estado = self::ESTADO_COMPLETADO;
        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Anular pedido
     * 
     * Transición: Desde cualquier estado (excepto completado) → anulado
     */
    public function anular(string $razon): void
    {
        if (empty($razon)) {
            throw new InvalidArgumentException("Se requiere una razón para anular el pedido");
        }

        if ($this->estado === self::ESTADO_COMPLETADO) {
            throw new InvalidArgumentException("No se puede anular un pedido completado");
        }

        if ($this->estado === self::ESTADO_ANULADO) {
            throw new InvalidArgumentException("El pedido ya está anulado");
        }

        $this->estado = self::ESTADO_ANULADO;
        $this->razonAnulacion = $razon;
        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Agregar prenda al pedido
     */
    public function agregarPrenda(array $datosPrenda): void
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new InvalidArgumentException(
                "Solo se pueden agregar prendas a un pedido pendiente"
            );
        }

        $numeroPrenda = $datosPrenda['numero'] ?? null;
        if (!$numeroPrenda) {
            throw new InvalidArgumentException("Prenda debe tener número");
        }

        // Verificar que no existe ya
        foreach ($this->prendas as $prenda) {
            if ($prenda['numero'] === $numeroPrenda) {
                throw new InvalidArgumentException("La prenda {$numeroPrenda} ya existe en el pedido");
            }
        }

        $this->prendas[] = [
            'id' => uniqid('prn_', true),
            'numero' => $numeroPrenda,
            'descripcion' => $datosPrenda['descripcion'] ?? '',
            'cantidad' => (int)($datosPrenda['cantidad'] ?? 1),
            'tallas' => $datosPrenda['tallas'] ?? [],
            'fecha_agregada' => new DateTime(),
        ];

        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Eliminar prenda del pedido
     */
    public function eliminarPrenda(string $numeroPrenda): void
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new InvalidArgumentException(
                "Solo se pueden eliminar prendas de un pedido pendiente"
            );
        }

        $encontrada = false;
        foreach ($this->prendas as $key => $prenda) {
            if ($prenda['numero'] === $numeroPrenda) {
                unset($this->prendas[$key]);
                $encontrada = true;
                break;
            }
        }

        if (!$encontrada) {
            throw new InvalidArgumentException("La prenda {$numeroPrenda} no existe en el pedido");
        }

        $this->prendas = array_values($this->prendas); // Reindexar
        $this->fechaActualizacion = new DateTime();
    }

    /**
     * Validadores privados
     */
    private static function validarNumeroPedido(string $numero): string
    {
        if (empty($numero)) {
            throw new InvalidArgumentException("Número de pedido es requerido");
        }

        if (strlen($numero) > 50) {
            throw new InvalidArgumentException("Número de pedido no puede exceder 50 caracteres");
        }

        return $numero;
    }

    private static function validarCliente(string $cliente): string
    {
        if (empty($cliente)) {
            throw new InvalidArgumentException("Cliente es requerido");
        }

        if (strlen($cliente) > 255) {
            throw new InvalidArgumentException("Cliente no puede exceder 255 caracteres");
        }

        return $cliente;
    }

    /**
     * Getters (sin setters - inmutable)
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
    }

    public function getFechaActualizacion(): DateTime
    {
        return $this->fechaActualizacion;
    }

    public function getFechaConfirmacion(): ?DateTime
    {
        return $this->fechaConfirmacion;
    }

    public function getRazonAnulacion(): ?string
    {
        return $this->razonAnulacion;
    }

    public function getPrendas(): array
    {
        return $this->prendas;
    }

    public function getCantidadPrendas(): int
    {
        return count($this->prendas);
    }

    /**
     * Validaciones de estado
     */
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaConfirmado(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADO;
    }

    public function estaEnProduccion(): bool
    {
        return $this->estado === self::ESTADO_EN_PRODUCCION;
    }

    public function estaCompletado(): bool
    {
        return $this->estado === self::ESTADO_COMPLETADO;
    }

    public function estaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    /**
     * Convertir a array para persistencia
     */
    public function aArray(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numeroPedido,
            'cliente' => $this->cliente,
            'estado' => $this->estado,
            'fecha' => $this->fecha->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => $this->fechaActualizacion->format('Y-m-d H:i:s'),
            'fecha_confirmacion' => $this->fechaConfirmacion?->format('Y-m-d H:i:s'),
            'razon_anulacion' => $this->razonAnulacion,
            'prendas' => $this->prendas,
            'cantidad_prendas' => $this->getCantidadPrendas(),
        ];
    }
}
