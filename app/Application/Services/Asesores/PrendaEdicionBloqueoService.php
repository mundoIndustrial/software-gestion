<?php

namespace App\Application\Services\Asesores;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use Illuminate\Support\Collection;

final class PrendaEdicionBloqueoService
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $pedidoProduccionReadRepository,
        private readonly ConsecutivosRecibosRepository $consecutivosRecibosRepository,
    ) {
    }

    private const VALORES_CONSECUTIVO_PERMITIDOS = [
        'PENDIENTE_INSUMOS',
        'DEVUELTO_ASESOR',
        'DEVUELTO_ASESORA',
        'DEVUELTO_A_ASESOR',
        'DEVUELTO_A_ASESORA',
    ];

    private const ESTADOS_PEDIDO_BLOQUEADOS = [
        'EN_EJECUCION',
        'ENTREGADO',
    ];

    public function evaluar(int $pedidoId, int $prendaId): array
    {
        $estadoPedido = $this->obtenerEstadoPedido($pedidoId);
        if ($this->pedidoBloqueadoPorEstado($estadoPedido)) {
            return [
                'bloqueada' => true,
                'puede_editar' => false,
                'mensaje' => $this->mensajeBloqueoPorEstadoPedido('editar', $estadoPedido),
                'consecutivo' => null,
                'estado' => null,
                'area' => null,
                'estado_pedido' => $estadoPedido,
            ];
        }

        $registros = $this->obtenerRegistrosConsecutivo($pedidoId, $prendaId);
        if ($registros->isEmpty()) {
            // Si no hay consecutivo pero el pedido no está bloqueado, permitir editar
            return $this->respuestaPermitida($estadoPedido);
        }

        if ($this->resolverRegistroPermitido($registros)) {
            return $this->respuestaPermitida($estadoPedido);
        }

        $registroBloqueante = $registros->first();
        $area = $this->normalizarArea($registroBloqueante->area ?? null);

        return [
            'bloqueada' => true,
            'puede_editar' => false,
            'mensaje' => $this->mensajeBloqueo('editar', $area),
            'consecutivo' => $registroBloqueante->consecutivo_actual,
            'estado' => $registroBloqueante->estado,
            'area' => $area,
            'estado_pedido' => $estadoPedido,
        ];
    }

    public function mensajeBloqueo(string $accion, ?string $area): string
    {
        $accionNormalizada = trim($accion) !== '' ? trim($accion) : 'editar';
        $areaVisible = $area ?: 'produccion';
        return "Esta prenda se encuentra en {$areaVisible}, por ende no se puede {$accionNormalizada}. Comunicate con el lider de produccion.";
    }

    public function mensajeBloqueoPorEstadoPedido(string $accion, ?string $estadoPedido): string
    {
        $accionNormalizada = trim($accion) !== '' ? trim($accion) : 'editar';
        $estadoVisible = is_string($estadoPedido) && trim($estadoPedido) !== '' ? trim($estadoPedido) : 'estado no editable';
        return "Esta prenda no se puede {$accionNormalizada} porque el pedido se encuentra en estado {$estadoVisible}. Comunicate con el lider de produccion.";
    }

    private function respuestaPermitida(?string $estadoPedido): array
    {
        return [
            'bloqueada' => false,
            'puede_editar' => true,
            'mensaje' => null,
            'consecutivo' => null,
            'estado' => null,
            'area' => null,
            'estado_pedido' => $estadoPedido,
        ];
    }

    private function obtenerRegistrosConsecutivo(int $pedidoId, int $prendaId): Collection
    {
        $registrosActivos = $this->consecutivosRecibosRepository->obtenerPorPrendaYPedido($prendaId, $pedidoId)
            ->filter(fn ($r) => !empty($r->consecutivo_actual))
            ->values();

        if ($registrosActivos->isNotEmpty()) {
            return $registrosActivos;
        }

        return $this->consecutivosRecibosRepository->obtenerTodosPorPrenda($prendaId, $pedidoId)
            ->filter(fn ($r) => !empty($r->consecutivo_actual))
            ->values();
    }

    private function resolverRegistroPermitido(Collection $registros): ?object
    {
        foreach ($registros as $registro) {
            $estadoNormalizado = $this->normalizarTexto((string) ($registro->estado ?? ''));

            // Regla de habilitación: SOLO por estado del recibo.
            if (in_array($estadoNormalizado, self::VALORES_CONSECUTIVO_PERMITIDOS, true)) {
                return $registro;
            }
        }

        return null;
    }

    private function obtenerEstadoPedido(int $pedidoId): ?string
    {
        $pedido = $this->pedidoProduccionReadRepository->obtenerPedidoPorId($pedidoId);
        $estado = is_array($pedido) ? ($pedido['estado'] ?? null) : null;

        return is_string($estado) && trim($estado) !== '' ? trim($estado) : null;
    }

    private function pedidoBloqueadoPorEstado(?string $estadoPedido): bool
    {
        if (!is_string($estadoPedido) || trim($estadoPedido) === '') {
            return false;
        }

        $normalizado = $this->normalizarTexto($estadoPedido);
        return in_array($normalizado, self::ESTADOS_PEDIDO_BLOQUEADOS, true);
    }

    private function normalizarArea(?string $area): ?string
    {
        if (!is_string($area)) {
            return null;
        }

        $valor = trim($area);
        return $valor !== '' ? $valor : null;
    }

    private function normalizarTexto(string $valor): string
    {
        $s = trim($valor);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = $ascii !== false ? $ascii : $s;
        $s = strtoupper($s);
        return str_replace(' ', '_', $s);
    }
}
