<?php

namespace App\Application\UseCases\Pedidos;

/**
 * GuardarBorradorOutput
 * 
 * DTO para la respuesta del UseCase GuardarBorradorUseCase
 * 
 * Encapsula:
 * - success: Booleano indicando éxito
 * - message: Mensaje para el usuario
 * - pedido_id: ID del pedido creado
 * - numero_pedido: Número del pedido (null para borradores sin número)
 * - estado: Estado del pedido
 * - redirect_url: URL a donde redirigir después
 * - tiempo_ms: Tiempo de ejecución en milisegundos
 * 
 * @package App\Application\UseCases\Pedidos
 */
class GuardarBorradorOutput
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $pedido_id = null,
        public ?string $numero_pedido = null,
        public ?string $estado = null,
        public ?string $redirect_url = null,
        public int $tiempo_ms = 0,
    ) {}

    /**
     * Convertir a array para respuesta JSON
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'pedido_id' => $this->pedido_id,
            'numero_pedido' => $this->numero_pedido,
            'estado' => $this->estado,
            'redirect_url' => $this->redirect_url,
            'tiempo_ms' => $this->tiempo_ms,
        ];
    }
}
