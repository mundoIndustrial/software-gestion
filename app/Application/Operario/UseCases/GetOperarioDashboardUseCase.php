<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\OperarioDashboardDTO;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Domain\Operario\Services\OperarioDashboardReadService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class GetOperarioDashboardUseCase
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private ObtenerPrendasRecibosService $obtenerPrendasRecibosService,
        private OperarioDashboardReadService $dashboardReadService,
    ) {}

    public function execute(Request $request): OperarioDashboardDTO
    {
        $usuario = Auth::user();

        $verTodas = $request->boolean('todas');
        $defaultTab = $usuario->hasRole('cortador') ? 'pendientes' : 'costura';
        $tab = (string) $request->query('tab', $defaultTab);
        $filtroRecibo = strtolower(trim((string) $request->query('filtro', '')));
        $filtroRecibo = in_array($filtroRecibo, ['costura', 'reflectivo', 'bodega'], true) ? $filtroRecibo : null;
        $filtroEncargadoVistaCostura = strtolower(trim((string) $request->query('encargado', '')));
        $filtroEncargadoVistaCostura = in_array($filtroEncargadoVistaCostura, ['todos', 'sin-encargado', 'control-calidad'], true)
            ? $filtroEncargadoVistaCostura
            : 'todos';
        $busquedaVistaCostura = strtolower(trim((string) $request->query('q', '')));

        $prendasConRecibos = collect();
        $recibosCompletados = collect();
        $recibosBodegaPendientes = collect();
        $recibosBodegaCompletados = collect();

        // Para cortadores, manejamos la lógica de carga perezosa para optimizar el rendimiento
        if ($usuario->hasRole('cortador')) {
            // Solo cargar la colección completa si el tab es bodega o completados general
            if (in_array($tab, ['completados', 'completado-bodega'])) {
                $recibosCompletadosRaw = $this->dashboardReadService->obtenerRecibosCompletadosPorOperario($usuario->name);
                if ($recibosCompletadosRaw->isEmpty()) {
                    $recibosCompletadosRaw = $this->dashboardReadService->obtenerRecibosCompletadosPorOperario('CORTADORES');
                }

                $recibosCompletadosCount = $recibosCompletadosRaw->filter(function ($r) {
                    return strtoupper((string) ($r['tipo_recibo'] ?? '')) !== 'CORTE-PARA-BODEGA';
                })->count();

                $recibosBodegaCompletadosCount = $recibosCompletadosRaw->filter(function ($r) {
                    return strtoupper((string) ($r['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA';
                })->count();

                if ($tab === 'completado-bodega') {
                    $recibosBodegaCompletados = $recibosCompletadosRaw->filter(function ($r) {
                        return strtoupper((string) ($r['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA';
                    });
                } else {
                    $recibosCompletados = $recibosCompletadosRaw->filter(function ($r) {
                        return strtoupper((string) ($r['tipo_recibo'] ?? '')) !== 'CORTE-PARA-BODEGA';
                    });
                }
            } else {
                // Si NO estamos en una pestaña de completados, solo traemos los conteos (MUCHO más rápido)
                $counts = $this->dashboardReadService->contarRecibosCompletadosPorOperario($usuario->name);
                if ($counts['total'] === 0) {
                    $counts = $this->dashboardReadService->contarRecibosCompletadosPorOperario('CORTADORES');
                }
                $recibosCompletadosCount = $counts['normales'];
                $recibosBodegaCompletadosCount = $counts['bodega'];
            }

            // Pendientes Bodega: SIEMPRE traer el conteo para el badge
            $recibosBodegaPendientesCount = $this->obtenerPrendasRecibosService->obtenerConteoRecibosBodegaCortador($usuario);

            // Solo cargar la colección completa si estamos en ese tab
            if ($tab === 'pendiente-bodega') {
                $recibosBodegaPendientes = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosBodegaCortador($usuario);
            }
        }

        // Obtener prendas con recibos del operario (Pendientes / Otros tabs)
        if (!$usuario->hasRole('cortador') || in_array($tab, ['pendientes', 'pendiente-bodega'], true) || $usuario->hasRole('cortador')) {
            // SIEMPRE calculamos el contador de pedidos normales para el badge (si es cortador)
            if ($usuario->hasRole('cortador')) {
                $prendasConRecibosRaw = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario, $filtroRecibo);
                $pendientesPedidosCount = $prendasConRecibosRaw->filter(function ($p) {
                    $reciboPrincipal = $p['recibos'][0] ?? null;
                    $tipo = strtoupper(trim((string) ($reciboPrincipal['tipo_recibo'] ?? '')));
                    return $tipo !== 'CORTE-PARA-BODEGA';
                })->count();

                // Solo cargar en la colección principal si estamos en el tab de pendientes
                if ($tab === 'pendientes') {
                    $prendasConRecibos = $prendasConRecibosRaw->filter(function ($p) {
                        $reciboPrincipal = $p['recibos'][0] ?? null;
                        $tipo = strtoupper(trim((string) ($reciboPrincipal['tipo_recibo'] ?? '')));
                        return $tipo !== 'CORTE-PARA-BODEGA';
                    });
                } elseif ($tab === 'pendiente-bodega') {
                    $prendasConRecibos = $recibosBodegaPendientes;
                }
            } else {
                // Para otros roles (costureros, etc.), lógica normal
                $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario, $filtroRecibo);
                $pendientesPedidosCount = $prendasConRecibos->count();
            }

            if ($verTodas || $usuario->hasRole('administrador-costura')) {
                $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosTodosCostura();
            }


            if ($usuario->hasRole('administrador-costura') && in_array($tab, ['costura', 'sobremedida'], true)) {
                $usuariosSobremedida = $this->dashboardReadService->obtenerUsuariosSobremedidaNormalizados();
                $usuariosTaller = $this->dashboardReadService->obtenerUsuariosTallerNormalizados();

                if ($tab === 'sobremedida') {
                    $prendasConRecibos = $prendasConRecibos
                        ->map(function ($prenda) use ($usuariosSobremedida) {
                            $prenda['recibos'] = array_values(array_filter($prenda['recibos'] ?? [], function ($recibo) use ($usuariosSobremedida) {
                                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                                $area = strtolower(trim((string) ($recibo['area'] ?? '')));
                                
                                if ($tipo !== 'COSTURA') {
                                    return false;
                                }

                                if ($area === 'costura') {
                                    $encargado = strtolower(trim((string) ($recibo['encargado_costura'] ?? '')));
                                    return $encargado !== '' && $usuariosSobremedida->contains($encargado);
                                }
                                
                                if ($area === 'corte') {
                                    $encargado = strtolower(trim((string) ($recibo['encargado_corte'] ?? '')));
                                    return $encargado !== '' && $usuariosSobremedida->contains($encargado);
                                }

                                return false;
                            }));

                            return $prenda;
                        })
                        ->filter(function ($prenda) {
                            return !empty($prenda['recibos']);
                        })
                        ->values();
                }

                if ($tab === 'costura') {
                    $prendasConRecibos = $prendasConRecibos
                        ->map(function ($prenda) use ($usuariosSobremedida, $usuariosTaller) {
                            $prenda['recibos'] = array_values(array_filter($prenda['recibos'] ?? [], function ($recibo) use ($usuariosSobremedida, $usuariosTaller) {
                                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                                $area = strtolower(trim((string) ($recibo['area'] ?? '')));
                                
                                if ($area !== 'costura') {
                                    return false;
                                }
                                
                                if ($tipo !== 'COSTURA') {
                                    return false;
                                }

                                $encargado = strtolower(trim((string) ($recibo['encargado_costura'] ?? '')));
                                
                                // Si no tiene encargado asignado, no mostrar en la pestaña de costura para el admin
                                if ($encargado === '') {
                                    return false;
                                }

                                // Si el encargado es de sobremedida, no mostrar aquí (va a la pestaña sobremedida)
                                if ($usuariosSobremedida->contains($encargado)) {
                                    return false;
                                }

                                // Si el encargado es de taller, no mostrar aquí (administrador-costura no debe ver recibos de taller)
                                if ($usuariosTaller->contains($encargado)) {
                                    return false;
                                }

                                return true;
                            }));

                            return $prenda;
                        })
                        ->filter(function ($prenda) {
                            return !empty($prenda['recibos']);
                        })
                        ->values();
                }
            }

            if ($usuario->hasRole('administrador-costura') && $tab === 'bodega') {
                $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosBodegaVistaCostura(true);
                $cacheEsRolCostura = [];

                $prendasConRecibos = $prendasConRecibos->filter(function ($prenda) use (&$cacheEsRolCostura) {
                    $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();
                    $areaRecibo = is_array($reciboPrincipal)
                        ? strtolower(trim((string) ($reciboPrincipal['area'] ?? '')))
                        : '';

                    // En tab bodega de administrador/lider costura solo deben verse
                    // los recibos que siguen en Costura (no los que ya pasaron a C.C).
                    if ($areaRecibo !== 'costura') {
                        return false;
                    }

                    $encargadoPrenda = strtolower(trim((string) ($prenda['encargado_costura'] ?? '')));
                    $encargadoRecibo = is_array($reciboPrincipal)
                        ? strtolower(trim((string) ($reciboPrincipal['encargado_costura'] ?? '')))
                        : '';
                    $encargado = $encargadoPrenda !== '' ? $encargadoPrenda : $encargadoRecibo;

                    if ($encargado === '' || $encargado === 'sin encargado') {
                        return false;
                    }

                    if (!array_key_exists($encargado, $cacheEsRolCostura)) {
                        $encargadoUsuario = User::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargado])
                            ->first();

                        if (!$encargadoUsuario) {
                            $cacheEsRolCostura[$encargado] = false;
                        } else {
                            $esRolCosturaPermitido = $encargadoUsuario->hasAnyRole(['costurero', 'confeccion-sobremedida']);
                            $esRolReflectivo = $encargadoUsuario->hasRole('costura-reflectivo');

                            // En tab bodega de administrador-costura:
                            // mostrar solo encargados de costura, excluyendo reflectivo.
                            $cacheEsRolCostura[$encargado] = $esRolCosturaPermitido && !$esRolReflectivo;
                        }
                    }

                    return (bool) $cacheEsRolCostura[$encargado];
                })->values();
            }

            $areaOperario = $usuario->hasRole('cortador')
                ? 'Corte'
                : ($usuario->hasAnyRole(['costurero', 'confeccion-sobremedida']) ? 'Costura' : null);
            
            if ($areaOperario) {
                $idsRecibos = $prendasConRecibos
                    ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $idsParciales = $prendasConRecibos
                    ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('pedido_parcial_id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $completadosPorId = $this->dashboardReadService->obtenerCompletadosPorArea($idsRecibos, $areaOperario);
                $completadosParcialesPorId = $this->dashboardReadService->obtenerCompletadosParcialesPorArea($idsParciales, $areaOperario);

                $prendasConRecibos = $prendasConRecibos->map(function ($prenda) use ($completadosPorId, $completadosParcialesPorId) {
                    $prenda['recibos'] = array_map(function ($recibo) use ($completadosPorId, $completadosParcialesPorId) {
                        $idRecibo = $recibo['id'] ?? null;
                        $idParcial = $recibo['pedido_parcial_id'] ?? null;

                        $recibo['completado_area'] = ($idRecibo && $completadosPorId->has($idRecibo))
                            || ($idParcial && $completadosParcialesPorId->has($idParcial));
                        return $recibo;
                    }, $prenda['recibos'] ?? []);

                    return $prenda;
                });
            }

            if ($usuario->hasRole('vista-costura')) {
                $prendasBodegaVistaCostura = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosBodegaVistaCostura();

                if ($filtroRecibo === 'bodega') {
                    $prendasConRecibos = $prendasBodegaVistaCostura;
                } else {
                    $prendasConRecibos = $this->filtrarPrendasVistaCosturaVisible($prendasConRecibos, $filtroRecibo);
                }
                $vistaCosturaSinEncargadoCount = $this->contarPrendasVistaCosturaSinEncargado($prendasConRecibos, $filtroRecibo);
                $vistaCosturaBodegaSinEncargadoCount = $this->contarPrendasVistaCosturaSinEncargadoBodega($prendasBodegaVistaCostura);
                $vistaCosturaBodegaControlCalidadCount = $filtroRecibo === 'bodega'
                    ? $this->contarPrendasVistaCosturaControlCalidadBodega($prendasConRecibos)
                    : 0;

                if ($filtroRecibo === 'bodega' && $filtroEncargadoVistaCostura === 'sin-encargado') {
                    $prendasConRecibos = $this->filtrarPrendasVistaCosturaSinEncargadoBodega($prendasConRecibos);
                } elseif ($filtroRecibo !== 'bodega' && $filtroEncargadoVistaCostura === 'sin-encargado') {
                    $prendasConRecibos = $this->filtrarPrendasVistaCosturaSinEncargado($prendasConRecibos, $filtroRecibo);
                }
                if ($filtroRecibo === 'bodega' && $filtroEncargadoVistaCostura !== 'control-calidad') {
                    // En bodega/todos (y bodega/sin-encargado) no mezclar recibos que ya están en C.C.
                    $prendasConRecibos = $prendasConRecibos->filter(function (array $prenda) {
                        $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();
                        if (!is_array($reciboPrincipal)) {
                            return false;
                        }

                        $area = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                        return !in_array($area, ['control calidad', 'control de calidad'], true);
                    })->values();
                }
                if ($filtroRecibo === 'bodega' && $filtroEncargadoVistaCostura === 'control-calidad') {
                    $prendasConRecibos = $this->filtrarPrendasVistaCosturaControlCalidadBodega($prendasConRecibos);
                }

                if ($busquedaVistaCostura !== '') {
                    $prendasConRecibos = $this->filtrarPrendasPorBusquedaVistaCostura($prendasConRecibos, $busquedaVistaCostura);
                }

                $yaTieneCompletadosVista = $prendasConRecibos->contains(function ($prenda) {
                    $recibo = collect($prenda['recibos'] ?? [])->first();
                    return is_array($recibo)
                        && array_key_exists('completado_corte', $recibo)
                        && array_key_exists('completado_costura', $recibo)
                        && array_key_exists('completado_control_calidad', $recibo);
                });

                if ($yaTieneCompletadosVista) {
                    return new OperarioDashboardDTO(
                        operario: null,
                        prendasConRecibos: $prendasConRecibos,
                        usuario: $usuario,
                        tab: $tab,
                        pendientesPedidosCount: $pendientesPedidosCount ?? 0,
                        recibosCompletados: $recibosCompletados,
                        recibosCompletadosCount: $recibosCompletadosCount ?? $recibosCompletados->count(),
                        recibosBodegaCompletados: $recibosBodegaCompletados ?? collect(),
                        recibosBodegaCompletadosCount: $recibosBodegaCompletadosCount ?? ($recibosBodegaCompletados ?? collect())->count(),
                        recibosBodegaPendientesCount: $recibosBodegaPendientesCount ?? 0,
                        vistaCosturaSinEncargadoCount: $vistaCosturaSinEncargadoCount ?? 0,
                        vistaCosturaBodegaSinEncargadoCount: $vistaCosturaBodegaSinEncargadoCount ?? 0,
                        vistaCosturaBodegaControlCalidadCount: $vistaCosturaBodegaControlCalidadCount ?? 0,
                    );
                }

                $idsRecibos = $prendasConRecibos
                    ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $idsParciales = $prendasConRecibos
                    ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('pedido_parcial_id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $completadosCortePorId = $this->dashboardReadService->obtenerCompletadosPorArea($idsRecibos, 'Corte');
                $completadosCosturaPorId = $this->dashboardReadService->obtenerCompletadosPorArea($idsRecibos, 'Costura');
                $completadosControlCalidadPorId = $this->dashboardReadService->obtenerCompletadosPorArea($idsRecibos, 'Control de Calidad');

                $completadosParcialesCortePorId = $this->dashboardReadService->obtenerCompletadosParcialesPorArea($idsParciales, 'Corte');
                $completadosParcialesCosturaPorId = $this->dashboardReadService->obtenerCompletadosParcialesPorArea($idsParciales, 'Costura');
                $completadosParcialesControlCalidadPorId = $this->dashboardReadService->obtenerCompletadosParcialesPorArea($idsParciales, 'Control de Calidad');

                $prendasConRecibos = $prendasConRecibos->map(function ($prenda) use (
                    $completadosCortePorId, $completadosCosturaPorId, $completadosControlCalidadPorId,
                    $completadosParcialesCortePorId, $completadosParcialesCosturaPorId, $completadosParcialesControlCalidadPorId
                ) {
                    $prenda['recibos'] = array_map(function ($recibo) use (
                        $completadosCortePorId, $completadosCosturaPorId, $completadosControlCalidadPorId,
                        $completadosParcialesCortePorId, $completadosParcialesCosturaPorId, $completadosParcialesControlCalidadPorId
                    ) {
                        $idRecibo = $recibo['id'] ?? null;
                        $idParcial = $recibo['pedido_parcial_id'] ?? null;

                        $recibo['completado_corte'] = ($idRecibo && $completadosCortePorId->has($idRecibo))
                            || ($idParcial && $completadosParcialesCortePorId->has($idParcial));

                        $recibo['completado_costura'] = ($idRecibo && $completadosCosturaPorId->has($idRecibo))
                            || ($idParcial && $completadosParcialesCosturaPorId->has($idParcial));

                        $recibo['completado_control_calidad'] = ($idRecibo && $completadosControlCalidadPorId->has($idRecibo))
                            || ($idParcial && $completadosParcialesControlCalidadPorId->has($idParcial));

                        return $recibo;
                    }, $prenda['recibos'] ?? []);

                    return $prenda;
                });
            } elseif ($busquedaVistaCostura !== '' && ($usuario->hasRole('lider-reflectivo') || $usuario->hasRole('costura-reflectivo') || $usuario->hasRole('visualizador_ordenes_produccion'))) {
                $prendasConRecibos = $this->filtrarPrendasPorBusquedaVistaCostura($prendasConRecibos, $busquedaVistaCostura);
            }

            if (
                $filtroRecibo === 'costura'
                && ($usuario->hasRole('lider-reflectivo') || $usuario->hasRole('costura-reflectivo') || $usuario->hasRole('visualizador_ordenes_produccion'))
            ) {
                $prendasConRecibos = $this->ordenarPrendasPorCreatedAtCostura($prendasConRecibos);
            }
        }

        // Se eliminó la llamada a obtenerPedidosDelOperario por redundancia y alto costo de rendimiento
        // Los datos necesarios ya se obtienen vía ObtenerPrendasRecibosService
        $datosOperario = null;

        return new OperarioDashboardDTO(
            operario: $datosOperario,
            prendasConRecibos: $prendasConRecibos,
            usuario: $usuario,
            tab: $tab,
            pendientesPedidosCount: $pendientesPedidosCount ?? 0,
            recibosCompletados: $recibosCompletados,
            recibosCompletadosCount: $recibosCompletadosCount ?? $recibosCompletados->count(),
            recibosBodegaCompletados: $recibosBodegaCompletados ?? collect(),
            recibosBodegaCompletadosCount: $recibosBodegaCompletadosCount ?? ($recibosBodegaCompletados ?? collect())->count(),
            recibosBodegaPendientesCount: $recibosBodegaPendientesCount ?? 0,
            vistaCosturaSinEncargadoCount: $vistaCosturaSinEncargadoCount ?? 0,
            vistaCosturaBodegaSinEncargadoCount: $vistaCosturaBodegaSinEncargadoCount ?? 0,
            vistaCosturaBodegaControlCalidadCount: $vistaCosturaBodegaControlCalidadCount ?? 0,
        );
    }

    private function ordenarPrendasPorCreatedAtCostura(Collection $prendas): Collection
    {
        return $prendas
            ->sortBy(function (array $prenda) {
                $reciboCostura = collect($prenda['recibos'] ?? [])->first(function (array $recibo) {
                    return strtoupper(trim((string) ($recibo['tipo_recibo'] ?? ''))) === 'COSTURA';
                });

                $createdAt = (string) ($reciboCostura['created_at'] ?? '');
                $timestamp = strtotime($createdAt);

                return $timestamp === false ? PHP_INT_MAX : $timestamp;
            })
            ->values();
    }

    private function filtrarPrendasVistaCosturaSinEncargado(Collection $prendas, ?string $filtroRecibo): Collection
    {
        if ($filtroRecibo === 'bodega') {
            return $prendas->values();
        }

        $esReflectivo = $filtroRecibo === 'reflectivo';

        return $prendas->filter(function (array $prenda) use ($esReflectivo) {
            foreach (($prenda['recibos'] ?? []) as $recibo) {
                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));

                if ($esReflectivo) {
                    if ($tipo !== 'REFLECTIVO') {
                        continue;
                    }

                    $sinEncargadoReflectivo = empty(trim((string) ($recibo['encargado_costura'] ?? '')));

                    if ($sinEncargadoReflectivo) {
                        return true;
                    }

                    continue;
                }

                if ($tipo !== 'COSTURA') {
                    continue;
                }

                $sinEncargado = empty(trim((string) ($recibo['encargado_costura'] ?? '')));
                $completadoCorte = (bool) ($recibo['completado_corte'] ?? false);

                if ($sinEncargado && $completadoCorte) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function filtrarPrendasVistaCosturaVisible(Collection $prendas, ?string $filtroRecibo): Collection
    {
        $esReflectivo = $filtroRecibo === 'reflectivo';
        $esBodega = $filtroRecibo === 'bodega';
        $areasPermitidas = $esReflectivo || $esBodega
            ? ['costura', 'control calidad', 'control de calidad']
            : ['corte', 'costura', 'control calidad', 'control de calidad'];

        return $prendas->filter(function (array $prenda) use ($esReflectivo, $esBodega, $areasPermitidas) {
            $recibos = collect($prenda['recibos'] ?? []);
            if ($recibos->isEmpty()) {
                return false;
            }

            foreach ($recibos as $recibo) {
                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                $area = strtolower(trim((string) ($recibo['area'] ?? '')));

                if (!in_array($area, $areasPermitidas, true)) {
                    continue;
                }

                if ($esReflectivo) {
                    if ($tipo === 'REFLECTIVO') {
                        return true;
                    }
                    continue;
                }

                if ($esBodega) {
                    if ($tipo === 'CORTE-PARA-BODEGA') {
                        return true;
                    }
                    continue;
                }

                if ($tipo === 'COSTURA') {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function contarPrendasVistaCosturaSinEncargado(Collection $prendas, ?string $filtroRecibo): int
    {
        return $this->filtrarPrendasVistaCosturaSinEncargado($prendas, $filtroRecibo)->count();
    }

    private function filtrarPrendasVistaCosturaSinEncargadoBodega(Collection $prendas): Collection
    {
        return $prendas->filter(function (array $prenda) {
            return collect($prenda['recibos'] ?? [])->contains(function (array $recibo) {
                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                if ($tipo !== 'CORTE-PARA-BODEGA') {
                    return false;
                }

                return empty(trim((string) ($recibo['encargado_costura'] ?? '')));
            });
        })->values();
    }

    private function contarPrendasVistaCosturaSinEncargadoBodega(Collection $prendas): int
    {
        return $this->filtrarPrendasVistaCosturaSinEncargadoBodega($prendas)->count();
    }

    private function filtrarPrendasVistaCosturaControlCalidadBodega(Collection $prendas): Collection
    {
        return $prendas->filter(function (array $prenda) {
            return collect($prenda['recibos'] ?? [])->contains(function (array $recibo) {
                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                if ($tipo !== 'CORTE-PARA-BODEGA') {
                    return false;
                }

                $area = strtolower(trim((string) ($recibo['area'] ?? '')));
                return in_array($area, ['control calidad', 'control de calidad'], true);
            });
        })->values();
    }

    private function contarPrendasVistaCosturaControlCalidadBodega(Collection $prendas): int
    {
        return $this->filtrarPrendasVistaCosturaControlCalidadBodega($prendas)->count();
    }

    private function filtrarPrendasPorBusquedaVistaCostura(Collection $prendas, string $busqueda): Collection
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return $prendas->values();
        }

        return $prendas->filter(function (array $prenda) use ($busqueda) {
            // Buscar por nombre del cliente
            $cliente = strtolower(trim((string) ($prenda['cliente'] ?? '')));
            if ($cliente !== '' && str_contains($cliente, $busqueda)) {
                return true;
            }

            // Buscar por número de recibo (consecutivo_actual o consecutivo_parcial)
            foreach (($prenda['recibos'] ?? []) as $recibo) {
                $consecutivoActual = strtolower(trim((string) ($recibo['consecutivo_actual'] ?? '')));
                $consecutivoParcial = strtolower(trim((string) ($recibo['consecutivo_parcial'] ?? '')));

                if ($consecutivoActual !== '' && str_contains($consecutivoActual, $busqueda)) {
                    return true;
                }

                if ($consecutivoParcial !== '' && str_contains($consecutivoParcial, $busqueda)) {
                    return true;
                }
            }

            return false;
        })->values();
    }


}
