<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\UseCases\AgregarPrendaCompletaUseCaseContract;
use App\Infrastructure\Services\Pedidos\PedidoTallaBuilder;
use App\Infrastructure\Services\Pedidos\PedidoTelaBuilder;
use App\Infrastructure\Services\Pedidos\PedidoTipoPrendaService;
use App\Infrastructure\Services\Pedidos\PedidoVarianteBuilder;
use App\Infrastructure\Services\Pedidos\PrendaImageService;
use App\Infrastructure\Services\Pedidos\PrendaNovedadService;
use App\Infrastructure\Services\Pedidos\PrendaProcesoService;
use App\Models\PrendaPedido;

final class AgregarPrendaCompletaUseCase implements AgregarPrendaCompletaUseCaseContract
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
        private TransactionManagerInterface $transactionManager,
        private PrendaImageService $prendaImageService,
        private PedidoTallaBuilder $pedidoTallaBuilder,
        private PedidoTelaBuilder $pedidoTelaBuilder,
        private PedidoTipoPrendaService $pedidoTipoPrendaService,
        private PedidoVarianteBuilder $pedidoVarianteBuilder,
        private PrendaProcesoService $prendaProcesoService,
        private PrendaNovedadService $prendaNovedadService
    ) {}

    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido
    {
        return $this->transactionManager->run(function () use ($dto) {
            $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

            $this->pedidoTipoPrendaService->asegurarTipo($dto->nombre_prenda);

            $prenda = PrendaPedido::create([
                'pedido_produccion_id' => $dto->pedidoId,
                'nombre_prenda' => $dto->nombre_prenda,
                'descripcion' => $dto->descripcion,
                'de_bodega' => $dto->de_bodega,
            ]);

            $this->prendaImageService->guardarFotos(
                $prenda,
                $dto->imagenes,
                $dto->imagenesExistentes
            );

            if (!empty($dto->cantidad_talla)) {
                $this->pedidoTallaBuilder->crear($prenda, $dto->cantidad_talla);
            }

            if (!empty($dto->variantes) && is_array($dto->variantes)) {
                $this->pedidoVarianteBuilder->crear($prenda, [
                    'tipo_manga_id' => $dto->variantes['tipo_manga_id'] ?? null,
                    'tipo_broche_boton_id' => $dto->variantes['tipo_broche_boton_id'] ?? $dto->variantes['tipo_broche_id'] ?? null,
                    'obs_manga' => $dto->variantes['obs_manga'] ?? $dto->variantes['manga_obs'] ?? null,
                    'obs_broche' => $dto->variantes['obs_broche'] ?? $dto->variantes['broche_boton_obs'] ?? null,
                    'tiene_bolsillos' => $dto->variantes['tiene_bolsillos'] ?? false,
                    'obs_bolsillos' => $dto->variantes['obs_bolsillos'] ?? $dto->variantes['bolsillos_obs'] ?? null,
                ]);
            }

            if (!empty($dto->telas) && is_array($dto->telas)) {
                $this->pedidoTelaBuilder->crearDesdeFormulario($prenda, $dto->telas, $dto->fotosTelaRutas ?? []);
            }

            if (!empty($dto->procesos) && is_array($dto->procesos)) {
                $this->prendaProcesoService->crearProcesosCompletos($prenda, $dto->procesos, $dto->fotosProcesoNuevo ?? []);
            }

            $this->prendaNovedadService->guardarNovedad($prenda, $dto);

            return $prenda;
        });
    }
}





