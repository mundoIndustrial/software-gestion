<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Application\Pedidos\Services\PedidoEppDetalleBuilder;
use App\Application\Pedidos\Services\PedidoPrendaDetalleBuilder;
use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Pedido
 * Caso de uso orquestador para lectura enriquecida del pedido.
 */
class ObtenerPedidoUseCase extends AbstractObtenerUseCase
{
    private bool $filtrarProcesosPendientes = false;

    public function __construct(
        PedidoRepository $pedidoRepository,
        private readonly PedidoDetalleReadService $pedidoDetalleReadService,
        ?PedidoPrendaDetalleBuilder $prendaDetalleBuilder = null,
        ?PedidoEppDetalleBuilder $eppDetalleBuilder = null
    ) {
        parent::__construct($pedidoRepository);
        $this->prendaDetalleBuilder = $prendaDetalleBuilder ?? new PedidoPrendaDetalleBuilder($this->pedidoDetalleReadService);
        $this->eppDetalleBuilder = $eppDetalleBuilder ?? new PedidoEppDetalleBuilder();
    }

    private readonly PedidoPrendaDetalleBuilder $prendaDetalleBuilder;
    private readonly PedidoEppDetalleBuilder $eppDetalleBuilder;

    public function ejecutar(int $pedidoId, bool $filtrarProcesosPendientes = false): PedidoResponseDTO
    {
        $this->filtrarProcesosPendientes = $filtrarProcesosPendientes;
        return $this->obtenerYEnriquecer($pedidoId);
    }

    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => true,
            'incluirEpps' => true,
            'incluirProcesos' => true,
            'incluirImagenes' => true,
        ];
    }

    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        $respuesta = $this->crearRespuestaFallback($datosEnriquecidos);

        try {
            $usuario = Auth::user();
            $esCortador = $usuario && $usuario->hasRole('cortador');
            $esApiOperario = false;

            try {
                $esApiOperario = request()->is('api/operario/*');
            } catch (\Exception $e) {
                $esApiOperario = false;
            }

            $filtrarPrendasBodega = $esCortador && !$esApiOperario;

            $modeloEloquent = $this->pedidoDetalleReadService
                ->findPedidoByIdConRelaciones((int) $pedidoId, $filtrarPrendasBodega);

            if (!$modeloEloquent) {
                Log::warning('Pedido no encontrado', ['pedido_id' => $pedidoId]);
                $respuesta = $this->crearRespuestaPedidoNoEncontrado();
            } else {
                $prendasCompletas = $this->prendaDetalleBuilder
                    ->construirPrendasCompletas($modeloEloquent, $modeloEloquent->estado, $this->filtrarProcesosPendientes);

                // IMPORTANTE: Usar eppsConTrashed para incluir EPPs soft-deleted (necesario para historial de homologaciones)
                $modeloEloquent->setRelation('epps', $modeloEloquent->eppsConTrashed()->get());
                $eppsCompletos = $this->eppDetalleBuilder
                    ->construirEppsCompletos($modeloEloquent);

                $respuesta = new PedidoResponseDTO(
                    id: $datosEnriquecidos['id'],
                    numero: $datosEnriquecidos['numero'],
                    clienteId: $datosEnriquecidos['clienteId'],
                    cliente: $modeloEloquent->cliente,
                    asesor: $modeloEloquent->asesor?->name,
                    estado: $datosEnriquecidos['estado'],
                    descripcion: $datosEnriquecidos['descripcion'],
                    totalPrendas: $datosEnriquecidos['totalPrendas'],
                    totalArticulos: $datosEnriquecidos['totalArticulos'],
                    prendas: $prendasCompletas,
                    epps: $eppsCompletos,
                    formaDePago: $datosEnriquecidos['forma_de_pago'] ?? null,
                    fechaCreacion: $modeloEloquent->created_at?->format('d/m/Y'),
                    area: $modeloEloquent->area ?? 'Sin especificar',
                    mensaje: 'Pedido obtenido exitosamente'
                );
            }
        } catch (\Error|\RuntimeException $e) {
            $respuesta = $this->crearRespuestaFallback($datosEnriquecidos);
        } catch (\Throwable $e) {
            $respuesta = $this->crearRespuestaFallback($datosEnriquecidos);
        }

        return $respuesta;
    }

    private function crearRespuestaPedidoNoEncontrado(): PedidoResponseDTO
    {
        return new PedidoResponseDTO(
            id: null,
            numero: null,
            clienteId: null,
            cliente: null,
            asesor: null,
            estado: null,
            descripcion: null,
            totalPrendas: 0,
            totalArticulos: 0,
            prendas: [],
            epps: [],
            formaDePago: null,
            fechaCreacion: null,
            area: null,
            mensaje: 'Pedido no encontrado'
        );
    }

    private function crearRespuestaFallback(array $datosEnriquecidos): PedidoResponseDTO
    {
        return new PedidoResponseDTO(
            id: $datosEnriquecidos['id'] ?? null,
            numero: $datosEnriquecidos['numero'] ?? null,
            clienteId: $datosEnriquecidos['clienteId'] ?? null,
            estado: $datosEnriquecidos['estado'] ?? null,
            descripcion: $datosEnriquecidos['descripcion'] ?? null,
            totalPrendas: $datosEnriquecidos['totalPrendas'] ?? 0,
            totalArticulos: $datosEnriquecidos['totalArticulos'] ?? 0,
            cliente: null,
            asesor: null,
            prendas: $datosEnriquecidos['prendas'] ?? [],
            epps: $datosEnriquecidos['epps'] ?? [],
            formaDePago: $datosEnriquecidos['forma_de_pago'] ?? null,
            fechaCreacion: null,
            area: null,
            mensaje: 'Pedido obtenido exitosamente'
        );
    }
}
