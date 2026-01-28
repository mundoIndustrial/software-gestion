<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Anular Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * Ahora también gestiona la actualización del campo 'novedades' con formato: NOMBRE-ROL-FECHATIME-MOTIVO
 * 
 * Antes: 45 líneas
 * Después: 15 líneas
 * Reducción: 67%
 */
class AnularProduccionPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    private string $razon = 'Sin especificar';
    private string $nombreUsuario = 'Sistema';
    private string $rolUsuario = 'Sin rol';
    private AnularProduccionPedidoDTO $dto;

    public function __construct(
        PedidoRepository $pedidoRepository,
        string $razon = 'Sin especificar'
    ) {
        parent::__construct($pedidoRepository);
        $this->razon = $razon;
    }

    /**
     * Ejecutar anulación con DTO que contiene id, razón e información del usuario
     */
    public function ejecutarConDTO(AnularProduccionPedidoDTO $dto): PedidoResponseDTO
    {
        $this->dto = $dto;
        $this->razon = $dto->razon;
        $this->nombreUsuario = $dto->nombreUsuario;
        $this->rolUsuario = $dto->rolUsuario;
        return $this->ejecutar($dto->id);
    }

    protected function aplicarTransicion($pedido): void
    {
        $pedido->anular($this->razon);
        
        // Construir la novedad con el formato: NOMBRE-ROL-FECHATIME-MOTIVO
        $novedad = $this->construirNovedad();
        
        // Agregar la novedad al pedido (concatena con registros previos)
        $pedido->agregarNovedad($novedad);
    }

    /**
     * Construir la novedad en el formato especificado: Anulada NOMBRE-ROL-FECHATIME-MOTIVO
     */
    private function construirNovedad(): string
    {
        $fechaActual = now()->format('d/m/Y H:i');
        return "Pedido Anulado: {$this->nombreUsuario}-{$this->rolUsuario}-{$fechaActual}- {$this->razon}";
    }

    protected function obtenerMensaje(): string
    {
        return 'Producción del pedido anulada exitosamente';
    }
}

