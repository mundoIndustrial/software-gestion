<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\OperarioDashboardDTO;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Domain\Operario\Services\OperarioDashboardReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $prendasConRecibos = collect();
        $recibosCompletados = collect();
        $recibosBodegaPendientes = collect();
        $recibosBodegaCompletados = collect();

        // Para cortadores, manejamos la lógica de carga perezosa para optimizar el rendimiento
        if ($usuario->hasRole('cortador')) {
            // Recibos completados (se filtran luego según el tab)
            $recibosCompletadosRaw = $this->dashboardReadService->obtenerRecibosCompletadosPorOperario($usuario->name);
            if ($recibosCompletadosRaw->isEmpty()) {
                $recibosCompletadosRaw = $this->dashboardReadService->obtenerRecibosCompletadosPorOperario('CORTADORES');
            }

            // Separar conteos de completados
            $recibosBodegaCompletadosCount = $recibosCompletadosRaw->filter(function ($r) {
                return strtoupper((string) ($r['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA';
            })->count();

            // Solo cargar la colección completa si el tab es bodega o completados general
            if ($tab === 'completado-bodega') {
                $recibosBodegaCompletados = $recibosCompletadosRaw->filter(function ($r) {
                    return strtoupper((string) ($r['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA';
                });
            } elseif ($tab === 'completados') {
                $recibosCompletados = $recibosCompletadosRaw->filter(function ($r) {
                    return strtoupper((string) ($r['tipo_recibo'] ?? '')) !== 'CORTE-PARA-BODEGA';
                });
            }

            // Pendientes Bodega: Solo cargar la colección completa si estamos en ese tab
            if ($tab === 'pendiente-bodega') {
                $recibosBodegaPendientes = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosBodegaCortador($usuario);
                $recibosBodegaPendientesCount = $recibosBodegaPendientes->count();
            } else {
                // Si no es el tab, solo traemos el conteo (más liviano)
                $recibosBodegaPendientesCount = $this->obtenerPrendasRecibosService->obtenerConteoRecibosBodegaCortador($usuario);
            }
        }

        // Obtener prendas con recibos del operario (Pendientes / Otros tabs)
        if (!$usuario->hasRole('cortador') || in_array($tab, ['pendientes', 'pendiente-bodega'], true) || $usuario->hasRole('cortador')) {
            // Solo cargar prendas con recibos si NO es un tab de bodega o si es específicamente el de pendientes
            if ($tab === 'pendientes' || !$usuario->hasRole('cortador')) {
                $prendasConRecibosRaw = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);
                
                if ($usuario->hasRole('cortador')) {
                    $prendasConRecibos = $prendasConRecibosRaw->filter(function ($p) {
                        $reciboPrincipal = $p['recibos'][0] ?? null;
                        $tipo = strtoupper(trim((string) ($reciboPrincipal['tipo_recibo'] ?? '')));
                        return $tipo !== 'CORTE-PARA-BODEGA';
                    });
                    $pendientesPedidosCount = $prendasConRecibos->count();
                } else {
                    $prendasConRecibos = $prendasConRecibosRaw;
                    $pendientesPedidosCount = $prendasConRecibos->count();
                }
            } elseif ($tab === 'pendiente-bodega') {
                $prendasConRecibos = $recibosBodegaPendientes;
                // Calculamos el contador de pedidos normales también para el badge
                $prendasConRecibosRaw = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);
                $pendientesPedidosCount = $prendasConRecibosRaw->filter(function ($p) {
                    $reciboPrincipal = $p['recibos'][0] ?? null;
                    $tipo = strtoupper(trim((string) ($reciboPrincipal['tipo_recibo'] ?? '')));
                    return $tipo !== 'CORTE-PARA-BODEGA';
                })->count();
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
                                
                                if (!in_array($tipo, ['COSTURA', 'COSTURA-BODEGA', 'PARCIAL'], true)) {
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
                                
                                if (!in_array($tipo, ['COSTURA', 'COSTURA-BODEGA', 'PARCIAL'], true)) {
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
            }
        }

        // También obtener los pedidos para mantener compatibilidad
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return new OperarioDashboardDTO(
            operario: $datosOperario,
            prendasConRecibos: $prendasConRecibos,
            usuario: $usuario,
            tab: $tab,
            pendientesPedidosCount: $pendientesPedidosCount ?? 0,
            recibosCompletados: $recibosCompletados,
            recibosBodegaCompletados: $recibosBodegaCompletados ?? collect(),
            recibosBodegaPendientesCount: $recibosBodegaPendientesCount ?? 0,
        );
    }
}

