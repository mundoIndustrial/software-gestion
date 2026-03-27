<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\OperarioDashboardDTO;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetOperarioDashboardUseCase
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private ObtenerPrendasRecibosService $obtenerPrendasRecibosService,
    ) {}

    public function execute(Request $request): OperarioDashboardDTO
    {
        $usuario = Auth::user();

        $verTodas = $request->boolean('todas');
        $tab = (string) $request->query('tab', 'costura');

        // Obtener prendas con recibos del operario
        $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);

        if ($verTodas || $usuario->hasRole('administrador-costura')) {
            $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibosTodosCostura();
        }
        // NOTA: vista-costura ya incluye REFLECTIVO en obtenerPrendasConRecibos(), no reemplazar

        if ($usuario->hasRole('administrador-costura') && in_array($tab, ['costura', 'sobremedida'], true)) {
            $rolSobremedidaId = \App\Models\Role::where('name', 'confeccion-sobremedida')->value('id');
            $usuariosSobremedida = collect();

            if (!empty($rolSobremedidaId)) {
                $usuariosSobremedida = \App\Models\User::query()
                    ->where(function ($q) use ($rolSobremedidaId) {
                        $q->whereJsonContains('roles_ids', (int) $rolSobremedidaId)
                            ->orWhere('role_id', (int) $rolSobremedidaId);
                    })
                    ->pluck('name')
                    ->map(function ($n) {
                        return strtolower(trim((string) $n));
                    })
                    ->filter()
                    ->unique()
                    ->values();
            }

            if ($tab === 'sobremedida') {
                $prendasConRecibos = $prendasConRecibos
                    ->map(function ($prenda) use ($usuariosSobremedida) {
                        $prenda['recibos'] = array_values(array_filter($prenda['recibos'] ?? [], function ($recibo) use ($usuariosSobremedida) {
                            $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                            if (!in_array($tipo, ['COSTURA', 'COSTURA-BODEGA', 'PARCIAL'], true)) {
                                return false;
                            }

                            $encargado = strtolower(trim((string) ($recibo['encargado_costura'] ?? '')));
                            return $encargado !== '' && $usuariosSobremedida->contains($encargado);
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
                    ->map(function ($prenda) use ($usuariosSobremedida) {
                        $prenda['recibos'] = array_values(array_filter($prenda['recibos'] ?? [], function ($recibo) use ($usuariosSobremedida) {
                            $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                            if (!in_array($tipo, ['COSTURA', 'COSTURA-BODEGA', 'PARCIAL'], true)) {
                                return false;
                            }

                            $encargado = strtolower(trim((string) ($recibo['encargado_costura'] ?? '')));
                            if ($encargado !== '' && $usuariosSobremedida->contains($encargado)) {
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

            $completadosPorId = DB::table('prenda_recibo_completado')
                ->where('area', $areaOperario)
                ->whereIn('id_recibo', $idsRecibos)
                ->pluck('fecha_completado', 'id_recibo');

            $prendasConRecibos = $prendasConRecibos->map(function ($prenda) use ($completadosPorId) {
                $prenda['recibos'] = array_map(function ($recibo) use ($completadosPorId) {
                    $idRecibo = $recibo['id'] ?? null;
                    $recibo['completado_area'] = $idRecibo ? $completadosPorId->has($idRecibo) : false;
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

            $completadosCortePorId = DB::table('prenda_recibo_completado')
                ->where('area', 'Corte')
                ->whereIn('id_recibo', $idsRecibos)
                ->pluck('fecha_completado', 'id_recibo');

            $completadosCosturaPorId = DB::table('prenda_recibo_completado')
                ->where('area', 'Costura')
                ->whereIn('id_recibo', $idsRecibos)
                ->pluck('fecha_completado', 'id_recibo');

            $completadosControlCalidadPorId = DB::table('prenda_recibo_completado')
                ->where('area', 'Control de Calidad')
                ->whereIn('id_recibo', $idsRecibos)
                ->pluck('fecha_completado', 'id_recibo');

            $prendasConRecibos = $prendasConRecibos->map(function ($prenda) use ($completadosCortePorId, $completadosCosturaPorId, $completadosControlCalidadPorId) {
                $prenda['recibos'] = array_map(function ($recibo) use ($completadosCortePorId, $completadosCosturaPorId, $completadosControlCalidadPorId) {
                    $idRecibo = $recibo['id'] ?? null;
                    $recibo['completado_corte'] = $idRecibo ? $completadosCortePorId->has($idRecibo) : false;
                    $recibo['completado_costura'] = $idRecibo ? $completadosCosturaPorId->has($idRecibo) : false;
                    $recibo['completado_control_calidad'] = $idRecibo ? $completadosControlCalidadPorId->has($idRecibo) : false;
                    return $recibo;
                }, $prenda['recibos'] ?? []);

                return $prenda;
            });
        }
        
        // También obtener los pedidos para mantener compatibilidad
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return new OperarioDashboardDTO(
            operario: $datosOperario,
            prendasConRecibos: $prendasConRecibos,
            usuario: $usuario,
            tab: $tab,
        );
    }
}
