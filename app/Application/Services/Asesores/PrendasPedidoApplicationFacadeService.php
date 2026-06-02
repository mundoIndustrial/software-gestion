<?php

namespace App\Application\Services\Asesores;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\DTOs\RenderItemCardDTO;
use App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaAlPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use App\Application\Pedidos\UseCases\RenderItemCardUseCase;
use Illuminate\Http\Request;

final class PrendasPedidoApplicationFacadeService
{
    public function __construct(
        private readonly AgregarPrendaAlPedidoUseCase $agregarPrendaUseCase,
        private readonly ObtenerPrendasPedidoUseCase $obtenerPrendasUseCase,
        private readonly ActualizarPrendaPedidoUseCase $actualizarPrendaUseCase,
        private readonly RenderItemCardUseCase $renderItemCardUseCase,
        private readonly AgregarPrendaCompletaUseCase $agregarPrendaCompletaUseCase,
        private readonly ActualizarPrendaCompletaUseCase $actualizarPrendaCompletaUseCase,
        private readonly PrepararCreacionPrendaService $prepararCreacionPrendaService,
        private readonly PrepararActualizacionPrendaService $prepararActualizacionPrendaService,
        private readonly GestionEliminarPrendaService $gestionEliminarPrendaService,
        private readonly ConsultarEdicionPrendaPedidoService $consultarEdicionPrendaPedidoService,
        private readonly PrendaPedidoEdicionAuditoriaService $prendaPedidoEdicionAuditoriaService,
        private readonly PrendaEdicionBloqueoService $prendaEdicionBloqueoService,
    ) {
    }

    public function agregarPrenda(int|string $pedidoId, array $validated): mixed
    {
        $this->validarCreacionPermitida((int) $pedidoId);

        $dto = AgregarPrendaAlPedidoDTO::fromRequest($pedidoId, $validated);
        $pedido = $this->agregarPrendaUseCase->ejecutar($dto);

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaNueva(
            (int) ($pedido->id ?? $pedidoId),
            null,
            $validated['nombre_prenda'] ?? 'PRENDA'
        );

        return $pedido;
    }

    public function obtenerPrendas(int|string $pedidoId): mixed
    {
        $dto = ObtenerPrendasPedidoDTO::fromRoute($pedidoId);
        return $this->obtenerPrendasUseCase->ejecutar($dto);
    }

    public function renderItemCard(array $validated): string
    {
        $dto = RenderItemCardDTO::fromRequest($validated);
        return $this->renderItemCardUseCase->ejecutar($dto);
    }

    public function actualizarPrenda(int|string $pedidoId, array $validated): mixed
    {
        $dto = ActualizarPrendaPedidoDTO::fromRequest($pedidoId, $validated);
        return $this->actualizarPrendaUseCase->ejecutar($dto);
    }

    public function agregarPrendaCompleta(Request $request, int $pedidoId, array $validated): mixed
    {
        $this->validarCreacionPermitida($pedidoId);

        $preparacion = $this->prepararCreacionPrendaService->preparar($request, $pedidoId, $validated);
        $validated = $preparacion['validated'];

        $dto = AgregarPrendaCompletaDTO::fromRequest(
            $pedidoId,
            $validated,
            $preparacion['imagenes_guardadas'],
            $preparacion['imagenes_existentes'],
            $preparacion['fotos_proceso_nuevo'],
            $preparacion['fotos_tela_rutas']
        );

        $prenda = $this->agregarPrendaCompletaUseCase->execute($dto);

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaNueva(
            $pedidoId,
            $prenda->id,
            $validated['nombre_prenda'] ?? 'PRENDA',
            false
        );

        return $prenda;
    }

    public function actualizarPrendaCompleta(Request $request, int $pedidoId, array $validated): mixed
    {
        $prendaId = (int) ($validated['prenda_id'] ?? 0);
        if ($prendaId > 0) {
            $this->validarEdicionPermitida($pedidoId, $prendaId);
        }

        $preparacion = $this->prepararActualizacionPrendaService->preparar($request, $pedidoId, $validated);
        $validated = $preparacion['validated'];

        $dto = ActualizarPrendaCompletaDTO::fromRequest(
            $validated['prenda_id'],
            $validated,
            $preparacion['imagenes_guardadas'],
            $preparacion['imagenes_existentes'],
            $preparacion['fotos_telas_procesadas'],
            $preparacion['fotos_proceso_nuevo'],
            $preparacion['fotos_color_procesadas'],
            $preparacion['fotos_proceso_tallas_nuevo']
        );

        $prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);
        $prenda = $prenda->fresh([
            'fotos',
            'tallas',
            'variantes.tipoManga',
            'variantes.tipoBroche',
            'coloresTelas.color',
            'coloresTelas.tela',
            'coloresTelas.fotos',
            'fotosTelas',
            'procesos.tipoProceso',
            'procesos.imagenes',
            'procesos.tallas',
        ]);

        $this->prendaPedidoEdicionAuditoriaService->registrarPrendaEditada(
            $pedidoId,
            $prenda->id,
            $prenda->nombre_prenda ?? $validated['nombre_prenda'] ?? 'PRENDA',
            'prenda completa',
            null,
            false
        );

        return $prenda;
    }

    public function eliminarImagen(int $pedidoId, string $tipo, int $id): array
    {
        return $this->gestionEliminarPrendaService->eliminarImagen($pedidoId, $tipo, $id);
    }

    public function eliminarPrenda(int $pedidoId, int $prendaId, string $motivo): array
    {
        $this->validarEliminacionPermitida($pedidoId, $prendaId);
        return $this->gestionEliminarPrendaService->eliminarPrenda($pedidoId, $prendaId, $motivo);
    }

    public function obtenerDatosPrendaEdicion(int $pedidoId, int $prendaId): array
    {
        $this->validarEdicionPermitida($pedidoId, $prendaId);
        return $this->consultarEdicionPrendaPedidoService->obtenerDatosPrendaEdicion($pedidoId, $prendaId);
    }

    public function obtenerDatosEdicion(int $pedidoId): array
    {
        return $this->consultarEdicionPrendaPedidoService->obtenerDatosEdicion($pedidoId);
    }

    private function validarEdicionPermitida(int $pedidoId, int $prendaId): void
    {
        $bloqueo = $this->prendaEdicionBloqueoService->evaluar($pedidoId, $prendaId);
        if (!($bloqueo['bloqueada'] ?? false)) {
            return;
        }

        $mensaje = (string) ($bloqueo['mensaje']
            ?? $this->prendaEdicionBloqueoService->mensajeBloqueo('editar', $bloqueo['area'] ?? null));
        throw new \DomainException($mensaje);
    }

    private function validarEliminacionPermitida(int $pedidoId, int $prendaId): void
    {
        $bloqueo = $this->prendaEdicionBloqueoService->evaluar($pedidoId, $prendaId);
        if (!($bloqueo['bloqueada'] ?? false)) {
            return;
        }

        if (!empty($bloqueo['estado_pedido'])) {
            throw new \DomainException(
                $this->prendaEdicionBloqueoService->mensajeBloqueoPorEstadoPedido('eliminar', $bloqueo['estado_pedido'])
            );
        }

        throw new \DomainException(
            $this->prendaEdicionBloqueoService->mensajeBloqueo('eliminar', $bloqueo['area'] ?? null)
        );
    }

    private function validarCreacionPermitida(int $pedidoId): void
    {
        $pedido = \App\Models\PedidoProduccion::query()
            ->select(['id', 'estado'])
            ->findOrFail($pedidoId);

        $estadoNormalizado = strtoupper(trim(str_replace(' ', '_', (string) $pedido->estado)));
        if ($estadoNormalizado !== 'EN_EJECUCION') {
            return;
        }

        throw new \DomainException(
            'No se pueden agregar prendas cuando el pedido está en ejecución.'
        );
    }
}
