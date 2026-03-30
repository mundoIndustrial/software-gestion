<?php

namespace App\Application\Pedidos\UseCases;

/**
 * DTO: Output para Validar Pedido
 * FASE 2 - Encapsula resultado de validación
 * Responsabilidades:
 * - Mantener estado de validación (éxito/error)
 * - Almacenar errores de validación
 * - Serializar para respuesta HTTP
 * @package App\Application\UseCases\Pedidos
 */
class ValidarPedidoOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $clienteId,
        public readonly ?string $message,
        public readonly array $errors = [],
    ) {}

    /**
     * Factory: Crear output exitoso
     * @param int $clienteId
     * @param string $message
     * @return self
     */
    public static function success(int $clienteId, string $message = 'Pedido válido'): self
    {
        return new self(
            success: true,
            clienteId: $clienteId,
            message: $message,
            errors: [],
        );
    }

    /**
     * Factory: Crear output con errores
     * @param array $errors Lista de errores de validación
     * @param string $message
     * @return self
     */
    public static function failure(array $errors, string $message = 'Validación fallida'): self
    {
        return new self(
            success: false,
            clienteId: null,
            message: $message,
            errors: $errors,
        );
    }

    /**
     * Factory: Crear output con excepción
     * @param \Exception $exception
     * @param string $message
     * @return self
     */
    public static function fromException(\Exception $exception, string $message = 'Error'): self
    {
        return new self(
            success: false,
            clienteId: null,
            message: $message . ': ' . $exception->getMessage(),
            errors: [],
        );
    }

    /**
     * Serializar a array para respuesta JSON
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'cliente_id' => $this->clienteId,
            'errors' => $this->errors,
        ];
    }

    /**
     * Obtener solo errores (para debugging)
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Verificar si tiene errores
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}

