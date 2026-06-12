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
                $claveTalla = $this->construirClaveTalla(
                    $recibo->numero_recibo,
                    (string) ($recibo->talla_nombre ?? ''),
                    (string) ($recibo->genero_nombre ?? ''),
                    (string) ($recibo->color_nombre ?? '')
                );
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
            $esReciboBodega = strtoupper(trim((string) ($primerRecibo->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';
            $prendaBodegaId = isset($primerRecibo->prenda_bodega_id) ? (int) $primerRecibo->prenda_bodega_id : null;

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
                pedidoProduccionId: $esReciboBodega || !isset($primerRecibo->pedido_produccion_id) ? null : (int) $primerRecibo->pedido_produccion_id,
                prendaId: $esReciboBodega || !isset($primerRecibo->prenda_id) ? null : (int) $primerRecibo->prenda_id,
                prendaBodegaId: $esReciboBodega ? $prendaBodegaId : null,
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
        $esReciboBodega = strtoupper(trim((string) ($grupo[0]->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';

        // Agrupar por número de parte
        foreach ($grupo as $recibo) {
            if (!isset($partesPorNumero[$recibo->numero_recibo])) {
                $partesPorNumero[$recibo->numero_recibo] = [];
            }
            $partesPorNumero[$recibo->numero_recibo][] = $recibo;
        }

        // Procesar cada parte
        foreach ($partesPorNumero as $numeroParte => $recibos) {
            // Agrupar por variante: talla + genero + color en bodega, talla sola en costura
            $variantesPorClave = [];
            
            foreach ($recibos as $recibo) {
                $tallaNombre = (string) ($recibo->talla_nombre ?? 'N/A');
                $generoNombre = (string) ($recibo->genero_nombre ?? '');
                $colorNombre = (string) ($recibo->color_nombre ?? '');
                $claveVariante = $esReciboBodega
                    ? $this->construirClaveTalla($recibo->numero_recibo, $tallaNombre, $generoNombre, $colorNombre)
                    : $tallaNombre;

                if (!isset($variantesPorClave[$claveVariante])) {
                    $variantesPorClave[$claveVariante] = $recibo;
                }
            }

            // Crear distribución para cada variante única
            foreach ($variantesPorClave as $claveVariante => $recibo) {
                $cantidadTotal = $recibo->cantidad_talla ?? 0;
                $tallaNombre = (string) ($recibo->talla_nombre ?? 'N/A');
                $generoNombre = (string) ($recibo->genero_nombre ?? '');
                $colorNombre = (string) ($recibo->color_nombre ?? '');
                $clave = $esReciboBodega
                    ? $this->construirClaveTalla($recibo->numero_recibo, $tallaNombre, $generoNombre, $colorNombre)
                    : $recibo->numero_recibo . '|' . $tallaNombre;
                $cantidadEntregada = $entregasPorTalla[$clave] ?? 0;
                $estado = strtoupper(trim((string) ($recibo->estado ?? '')));

                $progreso = $this->calculador->calcularProgreso($cantidadEntregada, $cantidadTotal);

                $etiquetaTalla = $esReciboBodega
                    ? implode(' / ', array_filter([
                        strtoupper(trim($generoNombre)) ?: null,
                        strtoupper(trim($colorNombre)) ?: null,
                        strtoupper(trim($tallaNombre)) ?: null,
                    ]))
                    : $tallaNombre;

                $distribucion[] = [
                    'numero_recibo_parte' => $numeroParte,
                    'recibo_parcial_id' => $recibo->id ?? null,
                    'estado' => $estado,
                    'talla' => $tallaNombre,
                    'etiqueta_talla' => $etiquetaTalla,
                    'genero' => $generoNombre,
                    'color_nombre' => $colorNombre,
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

    private function construirClaveTalla(string|int $numeroRecibo, string $talla, string $genero = '', string $color = ''): string
    {
        return strtoupper(trim((string) $numeroRecibo)) . '|'
            . strtoupper(trim($talla)) . '|'
            . strtoupper(trim($genero)) . '|'
            . strtoupper(trim($color));
    }
}
