<?php

namespace App\Domain\Pedidos\Validators;

use App\Domain\Shared\Validators\Validator;
use InvalidArgumentException;

/**
 * EstadoValidator - Valida transiciones de estado
 * 
 * Responsabilidad:
 * - Validar valores de estado vÃ¡lidos
 * - Validar transiciones permitidas
 * - Validar reglas de negocio por estado
 * 
 * Estados permitidos:
 * - activo: Pedido reciÃ©n creado, se pueden agregar prendas
 * - pendiente: Esperando aprobaciÃ³n o pago
 * - completado: Pedido finalizado (no se puede cambiar)
 * - cancelado: Pedido cancelado (no se puede cambiar)
 * 
 * Transiciones permitidas:
 * - activo â†’ pendiente, completado, cancelado
 * - pendiente â†’ activo, completado
 * - completado â†’ NO PERMITIDO
 * - cancelado â†’ NO PERMITIDO
 */
class EstadoValidator implements Validator
{
    private const ESTADOS_PERMITIDOS = [
        'activo',
        'pendiente',
        'completado',
        'cancelado'
    ];

    private const TRANSICIONES_PERMITIDAS = [
        'activo' => ['pendiente', 'completado', 'cancelado'],
        'pendiente' => ['activo', 'completado'],
        'completado' => [],
        'cancelado' => []
    ];

    /**
     * Validar estado
     * 
     * @param array $data Solo valida key 'estado'
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(array $data): void
    {
        if (!isset($data['estado'])) {
            throw new InvalidArgumentException('El estado es requerido');
        }

        $this->validateEstado($data['estado']);
    }

    /**
     * Validar campo especÃ­fico
     * 
     * @param string $field
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateField(string $field, mixed $value): void
    {
        if ($field !== 'estado') {
            throw new InvalidArgumentException("Campo no reconocido: {$field}");
        }

        $this->validateEstado($value);
    }

    /**
     * Validar estado vÃ¡lido
     * 
     * @throws InvalidArgumentException
     */
    public function validateEstado(?string $estado): void
    {
        if (empty($estado)) {
            throw new InvalidArgumentException('El estado es requerido');
        }

        if (!in_array($estado, self::ESTADOS_PERMITIDOS)) {
            throw new InvalidArgumentException(
                "Estado invÃ¡lido: '{$estado}'. Permitidos: " . 
                implode(', ', self::ESTADOS_PERMITIDOS)
            );
        }
    }

    /**
     * Validar transiciÃ³n de estado
     * 
     * @param string $estadoActual Estado actual del pedido
     * @param string $nuevoEstado Estado al que se quiere transicionar
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateTransicion(string $estadoActual, string $nuevoEstado): void
    {
        $this->validateEstado($estadoActual);
        $this->validateEstado($nuevoEstado);

        // Si es el mismo estado, no hay problema
        if ($estadoActual === $nuevoEstado) {
            return;
        }

        // Verificar si la transiciÃ³n estÃ¡ permitida
        $transicionesPermitidas = self::TRANSICIONES_PERMITIDAS[$estadoActual] ?? [];
        
        if (!in_array($nuevoEstado, $transicionesPermitidas)) {
            throw new InvalidArgumentException(
                "No se puede cambiar de '{$estadoActual}' a '{$nuevoEstado}'. " .
                "Transiciones permitidas desde '{$estadoActual}': " .
                (count($transicionesPermitidas) > 0 
                    ? implode(', ', $transicionesPermitidas)
                    : 'ninguna (estado final)')
            );
        }
    }

    /**
     * Obtener estados permitidos
     * 
     * @return array
     */
    public function getEstadosPermitidos(): array
    {
        return self::ESTADOS_PERMITIDOS;
    }

    /**
     * Obtener transiciones permitidas desde un estado
     * 
     * @param string $estado
     * @return array
     * @throws InvalidArgumentException
     */
    public function getTransicionesPermitidas(string $estado): array
    {
        $this->validateEstado($estado);
        return self::TRANSICIONES_PERMITIDAS[$estado] ?? [];
    }

    /**
     * Verificar si un estado es final (no permite cambios)
     * 
     * @param string $estado
     * @return bool
     */
    public function esEstadoFinal(string $estado): bool
    {
        return count($this->getTransicionesPermitidas($estado)) === 0;
    }
}

