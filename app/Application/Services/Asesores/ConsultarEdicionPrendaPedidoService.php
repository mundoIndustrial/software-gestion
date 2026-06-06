<?php

namespace App\Application\Services\Asesores;

use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use Illuminate\Support\Facades\Log;

final class ConsultarEdicionPrendaPedidoService
{
    public function __construct(
        private readonly ObtenerPedidoDetalleService $obtenerPedidoDetalleService,
        private readonly ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
        private readonly PrendaEdicionBloqueoService $prendaEdicionBloqueoService,
    ) {
    }

    public function obtenerDatosPrendaEdicion(int $pedidoId, int $prendaId): array
    {
        Log::info(' [PRENDA-DATOS] Llamando al servicio...', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
        ]);

        $prendaData = $this->obtenerPedidoDetalleService->obtenerPrendaConProcesos($pedidoId, $prendaId);

        Log::info(' [PRENDA-DATOS-RECIBIDOS] Datos obtenidos del servicio', [
            'procesos_count' => count($prendaData['procesos'] ?? []),
            'tallas_dama_count' => count($prendaData['tallas_dama'] ?? []),
            'tallas_caballero_count' => count($prendaData['tallas_caballero'] ?? []),
            'variantes_count' => count($prendaData['variantes'] ?? []),
            'colores_telas_count' => count($prendaData['colores_telas'] ?? []),
            'imagenes_count' => count($prendaData['imagenes'] ?? []),
            'prenda_keys' => array_keys($prendaData),
        ]);

        $pedido = $this->obtenerPedidoUseCase->ejecutar(
            ObtenerProduccionPedidoDTO::fromRequest((string) $pedidoId)
        );

        return [
            'prenda' => $prendaData,
            'pedido' => $this->mapPedidoBasico($pedido),
        ];
    }

    public function obtenerDatosEdicion(int $pedidoId): array
    {
        $datosEdicion = $this->obtenerPedidoDetalleService->obtenerParaEdicion($pedidoId);
        $datosEdicion = $this->enriquecerPedidoConBloqueos($datosEdicion, $pedidoId);
        $pedido = $datosEdicion['pedido'] ?? null;

        return [
            'pedido_id' => $pedidoId,
            'numero_pedido' => $this->pick($pedido, 'numero_pedido'),
            'cliente' => $this->pick($pedido, 'cliente'),
            'prendas_count' => $this->countPrendas($pedido),
            'data' => $datosEdicion,
        ];
    }

    private function mapPedidoBasico(mixed $pedido): array
    {
        $asesor = $this->pick($pedido, 'asesor');

        return [
            'id' => $this->pick($pedido, 'id'),
            'numero' => $this->pick($pedido, 'numero_pedido'),
            'numero_pedido' => $this->pick($pedido, 'numero_pedido'),
            'cliente' => $this->pick($pedido, 'cliente'),
            'cliente_nombre' => $this->pick($pedido, 'cliente'),
            'asesor_nombre' => $this->pick($asesor, 'name') ?? 'Sin asesor',
            'estado' => $this->pick($pedido, 'estado'),
            'fecha_creacion' => $this->formatDate($this->pick($pedido, 'created_at')),
        ];
    }

    private function countPrendas(mixed $pedido): int
    {
        $prendas = $this->pick($pedido, 'prendas');

        if (is_array($prendas)) {
            return count($prendas);
        }

        if ($prendas instanceof \Countable) {
            return count($prendas);
        }

        return 0;
    }

    private function pick(mixed $source, string $key): mixed
    {
        if (is_array($source)) {
            if (array_key_exists($key, $source)) {
                return $source[$key];
            }

            if (array_key_exists('pedido', $source) && (is_array($source['pedido']) || is_object($source['pedido']))) {
                return $this->pick($source['pedido'], $key);
            }
        }

        if (is_object($source)) {
            if (isset($source->{$key})) {
                return $source->{$key};
            }

            if (isset($source->pedido) && (is_array($source->pedido) || is_object($source->pedido))) {
                return $this->pick($source->pedido, $key);
            }
        }

        return null;
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return (new \DateTimeImmutable($value))->format('d/m/Y');
            } catch (\Throwable $e) {
                return '';
            }
        }

        return '';
    }

    private function enriquecerPedidoConBloqueos(mixed $pedido, int $pedidoId): mixed
    {
        if (is_array($pedido)) {
            if (isset($pedido['prendas']) && is_iterable($pedido['prendas'])) {
                $pedido['prendas'] = $this->enriquecerPrendasConBloqueo($pedido['prendas'], $pedidoId);
            }
            if (isset($pedido['pedido']) && (is_array($pedido['pedido']) || is_object($pedido['pedido']))) {
                $pedido['pedido'] = $this->enriquecerPedidoConBloqueos($pedido['pedido'], $pedidoId);
            }
            return $pedido;
        }

        if (is_object($pedido)) {
            if (isset($pedido->prendas) && is_iterable($pedido->prendas)) {
                $pedido->prendas = $this->enriquecerPrendasConBloqueo($pedido->prendas, $pedidoId);
            }
            if (isset($pedido->pedido) && (is_array($pedido->pedido) || is_object($pedido->pedido))) {
                $pedido->pedido = $this->enriquecerPedidoConBloqueos($pedido->pedido, $pedidoId);
            }
        }

        return $pedido;
    }

    private function enriquecerPrendasConBloqueo(iterable $prendas, int $pedidoId): iterable
    {
        $mapFn = function ($prenda) use ($pedidoId) {
            $prendaId = $this->pick($prenda, 'id') ?? $this->pick($prenda, 'prenda_pedido_id');
            if (!$prendaId) {
                return $prenda;
            }

            $bloqueo = $this->prendaEdicionBloqueoService->evaluar($pedidoId, (int) $prendaId);
            $bloqueada = (bool) ($bloqueo['bloqueada'] ?? false);
            $puedeEditar = ! $bloqueada;

            if (is_array($prenda)) {
                $prenda['puede_editar'] = $puedeEditar;
                $prenda['bloqueada_edicion'] = $bloqueada;
                $prenda['bloqueo_edicion'] = $bloqueo;
                return $prenda;
            }

            if (is_object($prenda)) {
                $prenda->puede_editar = $puedeEditar;
                $prenda->bloqueada_edicion = $bloqueada;
                $prenda->bloqueo_edicion = $bloqueo;
            }

            return $prenda;
        };

        if (is_array($prendas)) {
            return array_map($mapFn, $prendas);
        }

        if ($prendas instanceof \Illuminate\Support\Collection) {
            return $prendas->map($mapFn);
        }

        $resultado = [];
        foreach ($prendas as $prenda) {
            $resultado[] = $mapFn($prenda);
        }
        return $resultado;
    }
}
