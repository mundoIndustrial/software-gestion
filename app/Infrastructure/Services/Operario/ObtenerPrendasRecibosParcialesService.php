<?php

namespace App\Infrastructure\Services\Operario;

use App\Infrastructure\Repositories\Operario\OperarioRecibosRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ReciboPorPartes;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosParcialesService
{
    public function __construct(
        private readonly OperarioRecibosRepository $operarioRecibosRepository,
        private readonly ObtenerPrendasRecibosSupportService $supportService
    ) {}

    public function obtenerPrendasParcialesCostura(?User $usuario, bool $modoTodosCostura): Collection
    {
        $tipoOperario = $usuario ? $this->supportService->obtenerTipoOperario($usuario) : 'administrador-costura';
        $encargadoNormalizado = strtolower(trim((string) ($usuario?->name ?? '')));
        $esLiderReflectivo = (bool) ($usuario?->hasRole('lider-reflectivo'));

        $tiposParcial = $this->obtenerTiposParcialParaOperario($tipoOperario);
        $parciales = $this->obtenerParcialesBase($tiposParcial);
        $anexos = $this->operarioRecibosRepository->obtenerAnexosActivosPorTipos($tiposParcial);
        $procesosMap = $this->construirProcesosMapParaParciales($parciales);
        $completadosMap = $this->construirCompletadosMapParaParciales($parciales);
        $items = $this->combinarParcialesYAnexos($parciales, $anexos, $procesosMap, $completadosMap);

        return $items
            ->filter(fn (array $item) => $this->debeIncluirParcialParaOperario($item, $modoTodosCostura, $tipoOperario, $encargadoNormalizado, $esLiderReflectivo))
            ->map(fn (array $item) => $this->transformarParcialAFormatoRespuesta($item))
            ->values();
    }

    private function obtenerTiposParcialParaOperario(string $tipoOperario): array
    {
        $tiposParcial = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'costura-reflectivo') {
            $tiposParcial[] = 'REFLECTIVO';
        }

        return $tiposParcial;
    }

    private function obtenerParcialesBase(array $tiposParcial): Collection
    {
        return ReciboPorPartes::query()
            ->with(['pedido', 'prenda.tallas', 'tallas'])
            ->whereIn('tipo_recibo', $tiposParcial)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function construirProcesosMapParaParciales(Collection $parciales): array
    {
        $prendaIds = $parciales->pluck('prenda_pedido_id')->unique()->all();
        if (empty($prendaIds)) {
            return [];
        }

        $procesosMap = [];
        $procesos = $this->operarioRecibosRepository->obtenerProcesosPorPrendaIds($prendaIds);
        foreach ($procesos as $proc) {
            $key = $proc->prenda_pedido_id . '_' . $proc->numero_recibo_parcial;
            $procesosMap[$key] ??= [];
            $procesosMap[$key][] = $proc;
        }

        return $procesosMap;
    }

    private function construirCompletadosMapParaParciales(Collection $parciales): array
    {
        $parcialIds = $parciales->pluck('id')->all();
        if (empty($parcialIds)) {
            return [];
        }

        return $this->operarioRecibosRepository->obtenerParcialesCompletadosEnCorteMap($parcialIds);
    }

    private function combinarParcialesYAnexos(Collection $parciales, Collection $anexos, array $procesosMap, array $completadosMap): Collection
    {
        $items = collect();

        $parciales->each(function (ReciboPorPartes $parcial) use (&$items, $procesosMap, $completadosMap) {
            $pedido = $parcial->pedido;
            $prenda = $parcial->prenda;
            if (!$pedido || !$prenda) {
                return;
            }

            $key = $parcial->prenda_pedido_id . '_' . $parcial->consecutivo_parcial;
            $procesosCostura = collect($procesosMap[$key] ?? []);
            $procesoCostura = $procesosCostura
                ->filter(fn ($p) => strtolower(trim((string) $p->proceso)) === 'costura')
                ->sortByDesc('created_at')
                ->first();
            $procesoReciente = $procesosCostura->sortByDesc('created_at')->first();
            $areaActual = $this->resolverAreaActualParcial(isset($completadosMap[$parcial->id]), $procesoReciente);

            $items->push([
                'parcial' => $parcial,
                'pedido' => $pedido,
                'prenda' => $prenda,
                'proceso' => $procesoCostura,
                'proceso_reciente' => $procesoReciente,
                'encargado_normalizado' => strtolower(trim((string) ($procesoCostura?->encargado ?? $parcial->encargado ?? ''))),
                'es_anexo' => false,
                'area_detectada' => $areaActual,
            ]);
        });

        $anexos->each(function ($anexo) use (&$items) {
            $pedido = PedidoProduccion::find($anexo->pedido_produccion_id);
            $prenda = PrendaPedido::find($anexo->prenda_pedido_id);
            if (!$pedido || !$prenda) {
                return;
            }

            $procesoCostura = $this->operarioRecibosRepository->buscarUltimoProcesoPorNumeroPedidoPrendaReciboYProcesoSinParcial(
                (int) $pedido->numero_pedido,
                (int) $anexo->prenda_pedido_id,
                (string) $anexo->consecutivo_actual,
                'costura'
            );
            $procesoReciente = $this->operarioRecibosRepository->buscarUltimoProcesoPorNumeroPedidoPrendaReciboSinParcial(
                (int) $pedido->numero_pedido,
                (int) $anexo->prenda_pedido_id,
                (string) $anexo->consecutivo_actual
            );
            $completadoCorte = $this->operarioRecibosRepository->existeCompletadoParcialEnCorte((int) $anexo->id);
            $areaActual = $this->resolverAreaActualParcial($completadoCorte, $procesoReciente);

            $items->push([
                'parcial' => $anexo,
                'pedido' => $pedido,
                'prenda' => $prenda,
                'proceso' => $procesoCostura,
                'proceso_reciente' => $procesoReciente,
                'encargado_normalizado' => strtolower(trim((string) (($procesoCostura?->encargado) ?? $anexo->encargado ?? ''))),
                'es_anexo' => true,
                'area_detectada' => $areaActual,
            ]);
        });

        return $items;
    }

    private function resolverAreaActualParcial(bool $completadoCorte, mixed $procesoReciente): string
    {
        if ($completadoCorte) {
            return 'Costura';
        }

        if ($procesoReciente && strtolower(trim((string) $procesoReciente->proceso)) === 'costura') {
            return 'Costura';
        }

        return 'Corte';
    }

    private function debeIncluirParcialParaOperario(
        array $item,
        bool $modoTodosCostura,
        string $tipoOperario,
        string $encargadoNormalizado,
        bool $esLiderReflectivo
    ): bool {
        $encargado = $item['encargado_normalizado'];
        $tipoParcial = strtoupper(trim((string) ($item['parcial']->tipo_recibo ?? '')));
        $area = strtolower(trim((string) ($item['area_detectada'] ?? $item['parcial']->area ?? '')));

        if ($tipoOperario === 'costura-reflectivo' && $area !== 'costura') {
            return false;
        }

        if ($encargado === '') {
            if ($modoTodosCostura || $tipoOperario === 'vista-costura') {
                return true;
            }

            return $tipoOperario === 'costura-reflectivo' && $tipoParcial === 'REFLECTIVO';
        }

        if ($modoTodosCostura || $tipoOperario === 'vista-costura') {
            $encargadoUsuario = $this->operarioRecibosRepository->buscarUsuarioPorNombreNormalizado($encargado);
            return !($encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo'));
        }

        if (in_array($tipoOperario, ['costurero', 'confeccion-sobremedida'], true)) {
            return $encargado === $encargadoNormalizado;
        }

        if ($tipoOperario === 'costura-reflectivo') {
            if ($esLiderReflectivo) {
                $encargadoUsuario = $this->operarioRecibosRepository->buscarUsuarioPorNombreNormalizado($encargado);
                return $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
            }

            return $encargado === $encargadoNormalizado;
        }

        return false;
    }

    private function transformarParcialAFormatoRespuesta(array $item): array
    {
        $parcial = $item['parcial'];
        $pedido = $item['pedido'];
        $prenda = $item['prenda'];
        $proceso = $item['proceso'];
        $procesoReciente = $item['proceso_reciente'] ?? null;
        $esAnexo = $item['es_anexo'] ?? false;
        $tallas = $this->resolverTallasParcial($parcial, $esAnexo);
        $consecutivoParcial = $this->formatearConsecutivoParcial($parcial->consecutivo_parcial ?? $parcial->consecutivo_actual);
        $registroCompletadoCostura = $this->operarioRecibosRepository->obtenerCompletadoParcialEnCostura((int) $parcial->id);
        $completadoCostura = !empty($registroCompletadoCostura);
        $fechaCompletadoCostura = $registroCompletadoCostura->fecha_completado ?? null;
        $encargadoCostura = $proceso?->encargado ?: (!$esAnexo ? $parcial->encargado : null);
        $encargadoCorte = ($item['area_detectada'] ?? '') === 'Corte' ? $procesoReciente?->encargado : null;

        return [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'pedido_parcial_id' => $parcial->id,
            'es_recibo_por_partes' => !$esAnexo,
            'es_anexo' => $esAnexo,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'de_bodega' => $prenda->de_bodega ?? false,
            'tallas' => $tallas,
            'recibos' => [[
                'id' => null,
                'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                'consecutivo_actual' => $consecutivoParcial,
                'consecutivo_inicial' => $this->formatearConsecutivoParcial($parcial->consecutivo_inicial ?? $parcial->consecutivo_original),
                'consecutivo_parcial' => $consecutivoParcial,
                'notas' => $esAnexo ? 'anexo_id:' . $parcial->id : 'parcial_id:' . $parcial->id,
                'creado_en' => $parcial->created_at,
                'fecha_inicio_proceso' => $proceso?->fecha_inicio ?? null,
                'fecha_asignacion_costura' => $proceso?->fecha_de_asignacion_encargado ?? null,
                'fecha_proceso_costura_created_at' => $proceso?->created_at ?? null,
                'area' => $item['area_detectada'] ?? 'Costura',
                'proceso_id' => $proceso?->id,
                'proceso_id_costura' => $proceso?->id,
                'encargado_costura' => $encargadoCostura,
                'encargado_corte' => $encargadoCorte,
                'encargado_control_calidad' => null,
                'completado_area' => $completadoCostura,
                'completado_corte' => false,
                'completado_costura' => $completadoCostura,
                'fecha_completado_costura' => $fechaCompletadoCostura,
                'completado_control_calidad' => false,
                'es_parcial' => true,
                'pedido_parcial_id' => $parcial->id,
                'tiene_parciales' => false,
            ]],
            'total_recibos' => 1,
            'fecha_creacion' => $parcial->created_at,
        ];
    }

    private function resolverTallasParcial(mixed $parcial, bool $esAnexo): array
    {
        if ($esAnexo) {
            $tallaRows = $this->operarioRecibosRepository->obtenerTallasAnexo((int) $parcial->id);
            return $tallaRows->map(function ($talla) {
                return [
                    'id' => $talla->id,
                    'genero' => $talla->genero ?? null,
                    'talla' => $talla->talla,
                    'cantidad' => $talla->cantidad,
                    'tipo_talla' => null,
                    'es_sobremedida' => false,
                    'tela' => null,
                    'colores' => $talla->color_nombre ? [$talla->color_nombre] : [],
                ];
            })->toArray();
        }

        return $parcial->tallas->map(function ($talla) {
            return [
                'id' => $talla->id,
                'genero' => $talla->genero ?? null,
                'talla' => $talla->talla,
                'cantidad' => $talla->cantidad,
                'tipo_talla' => null,
                'es_sobremedida' => false,
                'tela' => null,
                'colores' => $talla->color_nombre ? [$talla->color_nombre] : [],
            ];
        })->toArray();
    }

    private function formatearConsecutivoParcial($valor): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        if (!str_contains($texto, '.')) {
            return $texto;
        }

        return rtrim(rtrim($texto, '0'), '.');
    }
}
