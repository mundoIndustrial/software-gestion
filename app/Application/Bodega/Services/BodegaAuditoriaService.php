<?php

namespace App\Application\Bodega\Services;

use App\Models\BodegaAuditoria;
use App\Models\EppBodegaAuditoria;
use App\Models\CosturaBodegaAuditoria;
use App\Models\BodegaDetallesTalla;
use App\Models\EppBodegaDetalle;
use App\Models\CosturaBodegaDetalle;
use Illuminate\Http\Request;

class BodegaAuditoriaService
{
    private BodegaRoleService $roleService;

    public function __construct(BodegaRoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Registrar auditoría para cambios en detalles de bodega
     */
    public function registrarAuditoria(array $datosCambio, array $rolesDelUsuario): void
    {
        $auditoriaModelClass = $this->roleService->getAuditoriaModelClass($rolesDelUsuario);
        
        $auditoriaModelClass::create([
            'numero_pedido' => $datosCambio['numero_pedido'],
            'talla' => $datosCambio['talla'],
            'prenda_nombre' => $datosCambio['prenda_nombre'] ?? null,
            'estado_anterior' => $datosCambio['estado_anterior'] ?? null,
            'estado_nuevo' => $datosCambio['estado_nuevo'],
            'usuario_id' => $datosCambio['usuario_id'],
            'usuario_nombre' => $datosCambio['usuario_nombre'],
            'descripcion_cambio' => $datosCambio['descripcion_cambio'] ?? "Cambio de estado",
        ]);
    }

    /**
     * Registrar auditoría general para cualquier campo
     */
    public function registrarAuditoriaGeneral(
        int $detalleId,
        string $numeroPedido,
        string $talla,
        string $campo,
        $valorAnterior,
        $valorNuevo,
        int $usuarioId,
        string $usuarioNombre,
        Request $request
    ): void {
        // Convertir null y strings vacíos a representación consistente
        $valorAnteriorDisplay = ($valorAnterior === null || $valorAnterior === '') ? '' : $valorAnterior;
        $valorNuevoDisplay = ($valorNuevo === null || $valorNuevo === '') ? '' : $valorNuevo;
        
        // Solo registrar si realmente cambió
        if ($valorAnteriorDisplay !== $valorNuevoDisplay) {
            BodegaAuditoria::create([
                'bodega_detalles_talla_id' => $detalleId,
                'numero_pedido' => $numeroPedido,
                'talla' => $talla,
                'campo_modificado' => $campo,
                'valor_anterior' => $valorAnteriorDisplay,
                'valor_nuevo' => $valorNuevoDisplay,
                'usuario_id' => $usuarioId,
                'usuario_nombre' => $usuarioNombre,
                'ip_address' => $request->ip(),
                'accion' => 'update',
                'descripcion' => ucfirst($campo) . ' cambió de "' . ($valorAnteriorDisplay ?: 'vacío') . '" a "' . ($valorNuevoDisplay ?: 'vacío') . '"',
            ]);
        }
    }

    /**
     * Validar optimistic locking
     */
    public function validarOptimisticLocking(?string $lastUpdatedAt): ?\Carbon\Carbon
    {
        if (!empty($lastUpdatedAt)) {
            return \Carbon\Carbon::parse($lastUpdatedAt);
        }
        
        return null;
    }

    /**
     * Verificar si hay conflicto de concurrencia
     */
    public function hayConflictoConcurrencia($detalle, ?\Carbon\Carbon $lastUpdatedAt): bool
    {
        if (!$detalle || !$lastUpdatedAt) {
            return false;
        }
        
        $detalleUpdatedAt = \Carbon\Carbon::parse($detalle->updated_at);
        
        return $detalleUpdatedAt->greaterThan($lastUpdatedAt);
    }

    /**
     * Obtener historial de cambios para un pedido y talla
     */
    public function obtenerHistorialCambios(string $numeroPedido, string $talla, array $rolesDelUsuario): array
    {
        $auditoriaModelClass = $this->roleService->getAuditoriaModelClass($rolesDelUsuario);
        
        $cambios = $auditoriaModelClass::where('numero_pedido', $numeroPedido)
            ->where('talla', $talla)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cambio) {
                return [
                    'id' => $cambio->id,
                    'estado_anterior' => $cambio->estado_anterior,
                    'estado_nuevo' => $cambio->estado_nuevo,
                    'usuario_nombre' => $cambio->usuario_nombre,
                    'descripcion_cambio' => $cambio->descripcion_cambio,
                    'fecha' => $cambio->created_at->format('d/m/Y'),
                    'hora' => $cambio->created_at->format('H:i:s'),
                    'fecha_completa' => $cambio->created_at->format('d/m/Y H:i:s'),
                ];
            });
        
        return $cambios->toArray();
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function obtenerEstadisticasAuditoria(): array
    {
        try {
            // Estadísticas generales
            $statsGenerales = [
                'total_cambios' => BodegaAuditoria::count(),
                'cambios_hoy' => BodegaAuditoria::whereDate('created_at', \Carbon\Carbon::today())->count(),
                'cambios_ultima_semana' => BodegaAuditoria::where('created_at', '>=', \Carbon\Carbon::now()->subDays(7))->count(),
            ];
            
            // Estadísticas por rol
            $statsPorRol = [
                'epp' => [
                    'total_cambios' => EppBodegaAuditoria::count(),
                    'cambios_hoy' => EppBodegaAuditoria::whereDate('created_at', \Carbon\Carbon::today())->count(),
                ],
                'costura' => [
                    'total_cambios' => CosturaBodegaAuditoria::count(),
                    'cambios_hoy' => CosturaBodegaAuditoria::whereDate('created_at', \Carbon\Carbon::today())->count(),
                ],
            ];
            
            // Cambios más frecuentes
            $cambiosFrecuentes = BodegaAuditoria::selectRaw('campo_modificado, COUNT(*) as frecuencia')
                ->groupBy('campo_modificado')
                ->orderBy('frecuencia', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            // Usuarios más activos
            $usuariosActivos = BodegaAuditoria::selectRaw('usuario_nombre, COUNT(*) as cambios')
                ->groupBy('usuario_nombre')
                ->orderBy('cambios', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            return [
                'generales' => $statsGenerales,
                'por_rol' => $statsPorRol,
                'cambios_frecuentes' => $cambiosFrecuentes,
                'usuarios_activos' => $usuariosActivos,
            ];
        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadisticasAuditoria: ' . $e->getMessage());
            return [
                'generales' => ['total_cambios' => 0, 'cambios_hoy' => 0, 'cambios_ultima_semana' => 0],
                'por_rol' => ['epp' => ['total_cambios' => 0, 'cambios_hoy' => 0], 'costura' => ['total_cambios' => 0, 'cambios_hoy' => 0]],
                'cambios_frecuentes' => [],
                'usuarios_activos' => [],
            ];
        }
    }

    /**
     * Limpiar registros antiguos de auditoría
     */
    public function limpiarAuditoriaAntigua(int $dias = 90): array
    {
        try {
            $fechaLimite = \Carbon\Carbon::now()->subDays($dias);
            
            $eliminadosGenerales = BodegaAuditoria::where('created_at', '<', $fechaLimite)->delete();
            $eliminadosEpp = EppBodegaAuditoria::where('created_at', '<', $fechaLimite)->delete();
            $eliminadosCostura = CosturaBodegaAuditoria::where('created_at', '<', $fechaLimite)->delete();
            
            $totalEliminados = $eliminadosGenerales + $eliminadosEpp + $eliminadosCostura;
            
            \Log::info('Limpieza de auditoría completada', [
                'dias' => $dias,
                'fecha_limite' => $fechaLimite->toDateString(),
                'eliminados_generales' => $eliminadosGenerales,
                'eliminados_epp' => $eliminadosEpp,
                'eliminados_costura' => $eliminadosCostura,
                'total_eliminados' => $totalEliminados,
            ]);
            
            return [
                'success' => true,
                'message' => "Se eliminaron $totalEliminados registros de auditoría antiguos",
                'detalles' => [
                    'generales' => $eliminadosGenerales,
                    'epp' => $eliminadosEpp,
                    'costura' => $eliminadosCostura,
                    'total' => $totalEliminados,
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error en limpiarAuditoriaAntigua: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al limpiar registros antiguos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Exportar auditoría a CSV
     */
    public function exportarAuditoriaCSV(?string $numeroPedido = null, ?string $fechaInicio = null, ?string $fechaFin = null): string
    {
        $query = BodegaAuditoria::with(['detalle']);
        
        if ($numeroPedido) {
            $query->where('numero_pedido', $numeroPedido);
        }
        
        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }
        
        $auditorias = $query->orderBy('created_at', 'desc')->get();
        
        $csv = "Número Pedido,Talla,Campo,Valor Anterior,Valor Nuevo,Usuario,Fecha,Hora,Descripción\n";
        
        foreach ($auditorias as $auditoria) {
            $csv .= sprintf(
                "%s,%s,%s,\"%s\",\"%s\",%s,%s,%s,\"%s\"\n",
                $auditoria->numero_pedido,
                $auditoria->talla,
                $auditoria->campo_modificado,
                str_replace('"', '""', $auditoria->valor_anterior),
                str_replace('"', '""', $auditoria->valor_nuevo),
                $auditoria->usuario_nombre,
                $auditoria->created_at->format('d/m/Y'),
                $auditoria->created_at->format('H:i:s'),
                str_replace('"', '""', $auditoria->descripcion)
            );
        }
        
        return $csv;
    }
}
