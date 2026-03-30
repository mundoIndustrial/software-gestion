<?php

namespace App\Application\Pedidos\UseCases;

/**
 * ActualizarBorradorOutput
 * DTO para encapsular los datos de salida después de actualizar un borrador
 * @package App\Application\UseCases\Pedidos
 */
class ActualizarBorradorOutput
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $pedido_id = null,
        public ?string $numero_pedido = null,
        public ?string $estado = null,
        public ?string $redirect_url = null,
        public float $tiempo_ms = 0,
    ) {}

    /**
     * Convertir a array para respuesta JSON

     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->pedido_id !== null) {
            $result['pedido_id'] = $this->pedido_id;
        }

        if ($this->numero_pedido !== null) {
            $result['numero_pedido'] = $this->numero_pedido;
        }

        if ($this->estado !== null) {
            $result['estado'] = $this->estado;
        }

        if ($this->redirect_url !== null) {
            $result['redirect_url'] = $this->redirect_url;
        }

        if ($this->tiempo_ms > 0) {
            $result['tiempo_ms'] = $this->tiempo_ms;
        }

        return $result;
    }
}

