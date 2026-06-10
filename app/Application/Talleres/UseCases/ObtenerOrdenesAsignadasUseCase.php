<?php

namespace App\Application\Talleres\UseCases;

use App\Domain\Talleres\Repositories\OrdenTallerRepositoryInterface;
use App\Domain\Talleres\Services\CalculadorProgresoServiceContract;
use App\Domain\Talleres\Services\FiltroOrdenesServiceContract;
use App\Domain\Talleres\ValueObjects\NumeroRecibo;
use App\Application\Talleres\DTOs\OrdenDTO;
use App\Application\Talleres\DTOs\DistribucionDTO;
use Illuminate\Support\Collection;

class ObtenerOrdenesAsignadasUseCase
{
    public function __construct(
        private OrdenTallerRepositoryInterface $repository,
        private CalculadorProgresoServiceContract $calculador,
        private FiltroOrdenesServiceContract $filtro
    ) {}

    public function execute(string $search = '', int $page = 1, string $tab = 'pedidos'): array
    {
        // 1. Obtener órdenes del repositorio
        $todosRecibos = $this->repository->obtenerAsignadas($search);
        $todosRecibos = $this->filtrarPorTab($todosRecibos, $tab);
        $todosRecibos = $todosRecibos
            ->sortByDesc(function ($recibo) {
                return (float) ($recibo->numero_recibo ?? 0);
            })
            ->values();

        if ($todosRecibos->isEmpty()) {
            return [
                'ordenes' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1
                ]
            ];
        }

        // 2. Agrupar por número de recibo base
        $recibosAgrupados = $this->agruparPorNumeroBase($todosRecibos);

        // 3. Obtener cantidades totales
        $cantidadesTotales = $this->obtenerCantidadesTotales($todosRecibos);

        // 4. Obtener entregas por talla
        $entregasPorTalla = $this->obtenerEntregasPorTalla($todosRecibos);

        // 5. Transformar a DTOs
        $ordenesFormateadas = $this->transformarADTOs(
            $recibosAgrupados,
            $cantidadesTotales,
            $entregasPorTalla
        );
        $ordenesFormateadas = collect($ordenesFormateadas)
            ->sortByDesc(function (array $orden) {
                return (float) ($orden['numero_recibo'] ?? 0);
            })
            ->values()
            ->all();

        // 6. Paginar
        return $this->filtro->paginar($ordenesFormateadas, $page, 10);
    }

    private function filtrarPorTab(Collection $recibos, string $tab): Collection
    {
        $tabNormalizado = strtolower(trim($tab));

        if ($tabNormalizado === 'bodega') {
            return $recibos->filter(function ($recibo) {
                return strtoupper(trim((string) ($recibo->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';
            })->values();
        }

        if ($tabNormalizado === 'pedidos') {
            return $recibos->filter(function ($recibo) {
                return strtoupper(trim((string) ($recibo->tipo_recibo ?? ''))) !== 'CORTE-PARA-BODEGA';
            })->values();
        }

        return $recibos->values();
    }

    private function agruparPorNumeroBase(Collection $recibos): array
    {
        $agrupados = [];

        foreach ($recibos as $recibo) {
            $numeroRecibo = new NumeroRecibo($recibo->numero_recibo);
            $numeroBase = $numeroRecibo->getNumeroBase();

            if (!isset($agrupados[$numeroBase])) {
                $agrupados[$numeroBase] = [];
            }

            $agrupados[$numeroBase][] = $recibo;
        }

        return $agrupados;
    }

    private function obtenerCantidadesTotales(Collection $recibos): array
    {
        $cantidades = [];
        $idsParciales = [];
        $tallasNormalesProcesadas = [];

        foreach ($recibos as $recibo) {
            if ($recibo->es_parcial) {
                $idsParciales[] = $recibo->id;
            } else {
                $claveTalla = $recibo->numero_recibo . '|' . ($recibo->talla_nombre ?? 'N/A');
                if (!isset($tallasNormalesProcesadas[$claveTalla])) {
                    $cantidades[$recibo->numero_recibo] = ($cantidades[$recibo->numero_recibo] ?? 0) + (int) ($recibo->cantidad_talla ?? 0);
                    $tallasNormalesProcesadas[$claveTalla] = true;
                }
            }
        }

        // Obtener cantidades parciales
        if (!empty($idsParciales)) {
            $cantidadesParciales = $this->repository->obtenerCantidadesTotales($idsParciales, [], true);
            foreach ($recibos as $recibo) {
                if ($recibo->es_parcial && isset($cantidadesParciales[$recibo->id])) {
                    $cantidades[$recibo->numero_recibo] = $cantidadesParciales[$recibo->id];
                }
            }
        }

        return $cantidades;
    }

    private function obtenerEntregasPorTalla(Collection $recibos): array
    {
        $idsNormales = [];
        $idsParciales = [];

        foreach ($recibos as $recibo) {
            if ($recibo->es_parcial) {
                $idsParciales[] = $recibo->id;
            } else {
                $idsNormales[] = $recibo->id;
            }
        }

        $entregas = [];

        // Obtener entregas normales para TODOS los IDs
        if (!empty($idsNormales)) {
            $query = new \App\Infrastructure\Talleres\Queries\ObtenerEntregasPorTallaQuery($idsNormales, false);
            $entregasNormales = $query->execute();
            $entregas = array_merge($entregas, $entregasNormales);
        }

        // Obtener entregas parciales para TODOS los IDs
        if (!empty($idsParciales)) {
            $query = new \App\Infrastructure\Talleres\Queries\ObtenerEntregasPorTallaQuery($idsParciales, true);
            $entregasParciales = $query->execute();
            $entregas = array_merge($entregas, $entregasParciales);
        }

        return $entregas;
    }

    private function transformarADTOs(array $recibosAgrupados, array $cantidadesTotales, array $entregasPorTalla): array
    {
        $ordenes = [];

        foreach ($recibosAgrupados as $numeroBase => $grupo) {
            $primerRecibo = $grupo[0];
            $numerosUnicos = count(array_unique(array_map(fn($r) => $r->numero_recibo, $grupo)));
            $tieneAlMenosUnParcial = collect($grupo)->contains(fn($r) => (bool) ($r->es_parcial ?? false));
            $esDividido = $numerosUnicos > 1 || $tieneAlMenosUnParcial;

            // Calcular cantidades totales - SOLO UNA VEZ por número de recibo
            $cantidadTotalOriginal = 0;
            $cantidadEntregadaTotal = 0;
            $recibosYaProcesados = [];
            $tallasYaProcesadas = []; // Evitar contar la misma talla múltiples veces

            foreach ($grupo as $recibo) {
                // Obtener cantidad total solo una vez por número de recibo
                if (!isset($recibosYaProcesados[$recibo->numero_recibo])) {
                    $cantidadTotalOriginal += $cantidadesTotales[$recibo->numero_recibo] ?? 0;
                    $recibosYaProcesados[$recibo->numero_recibo] = true;
                }

                // Obtener entregas únicas por talla usando numero_recibo (no id)
                $claveTalla = $recibo->numero_recibo . '|' . $recibo->talla_nombre;
                if (!isset($tallasYaProcesadas[$claveTalla])) {
                    $cantidadEntregadaTotal += $entregasPorTalla[$claveTalla] ?? 0;
                    $tallasYaProcesadas[$claveTalla] = true;
                }
            }

            // Calcular progreso
            $progreso = $this->calculador->calcularProgreso($cantidadEntregadaTotal, $cantidadTotalOriginal);

            // Obtener detalles de distribución
            $distribucionDetalles = $this->obtenerDistribucionDetalles($grupo, $cantidadesTotales, $entregasPorTalla);

            // Crear DTO
            $orden = new OrdenDTO(
                id: $primerRecibo->id ?? 0,
                numeroRecibo: $numeroBase,
                cliente: $primerRecibo->cliente ?? 'N/A',
                tipoRecibo: (string) ($primerRecibo->tipo_recibo ?? ''),
                descripcion: $primerRecibo->nombre_prenda ?? 'N/A',
                cantidadTotal: $cantidadTotalOriginal,
                cantidadEntregada: $cantidadEntregadaTotal,
                porcentaje: $progreso->getPorcentaje(),
                color: $progreso->getColor(),
                esDividido: $esDividido,
                encargadoDisplay: $esDividido ? 'Distribuido en talleres' : ($primerRecibo->taller_encargado ?? 'Sin asignar'),
                distribucion: $esDividido ? 'Ver Distribución' : 'No Aplica',
                distribucionDetalles: $distribucionDetalles,
                pedidoProduccionId: isset($primerRecibo->pedido_produccion_id) ? (int) $primerRecibo->pedido_produccion_id : null,
                prendaId: isset($primerRecibo->prenda_id) ? (int) $primerRecibo->prenda_id : null,
                fechaSalida: isset($primerRecibo->fecha_salida) ? (string) $primerRecibo->fecha_salida : null
            );

            $ordenes[] = $orden->toArray();
        }

        return $ordenes;
    }

    private function obtenerDistribucionDetalles(array $grupo, array $cantidadesTotales, array $entregasPorTalla): array
    {
        $distribucion = [];
        $partesPorNumero = [];

        // Agrupar por número de parte
        foreach ($grupo as $recibo) {
            if (!isset($partesPorNumero[$recibo->numero_recibo])) {
                $partesPorNumero[$recibo->numero_recibo] = [];
            }
            $partesPorNumero[$recibo->numero_recibo][] = $recibo;
        }

        // Procesar cada parte
        foreach ($partesPorNumero as $numeroParte => $recibos) {
            // Agrupar por talla para evitar duplicados
            $tallasPorNombre = [];
            
            foreach ($recibos as $recibo) {
                $tallaNombre = $recibo->talla_nombre ?? 'N/A';
                
                if (!isset($tallasPorNombre[$tallaNombre])) {
                    $tallasPorNombre[$tallaNombre] = $recibo;
                }
            }

            // Crear distribución para cada talla única
            foreach ($tallasPorNombre as $tallaNombre => $recibo) {
                $cantidadTotal = $recibo->cantidad_talla ?? 0;
                $clave = $recibo->numero_recibo . '|' . $tallaNombre;
                $cantidadEntregada = $entregasPorTalla[$clave] ?? 0;

                $progreso = $this->calculador->calcularProgreso($cantidadEntregada, $cantidadTotal);

                $distribucion[] = [
                    'numero_recibo_parte' => $numeroParte,
                    'recibo_parcial_id' => $recibo->id ?? null,
                    'talla' => $tallaNombre,
                    'cantidad' => $cantidadTotal,
                    'taller_nombre' => $recibo->taller_encargado ?? 'Sin asignar',
                    'cantidad_entregada' => $cantidadEntregada,
                    'porcentaje' => $progreso->getPorcentaje(),
                    'color' => $progreso->getColor(),
                    'fecha_salida' => isset($recibo->fecha_salida) ? (string) $recibo->fecha_salida : null
                ];
            }
        }

        return $distribucion;
    }
}
