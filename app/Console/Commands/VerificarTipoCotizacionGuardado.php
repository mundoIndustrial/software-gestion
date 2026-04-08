<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarTipoCotizacionGuardado extends Command
{
    protected $signature = 'db:verificar-tipo-cotizacion-guardado';
    protected $description = 'Verifica que tipo_cotizacion_id se esté guardando correctamente en cotizaciones';

    public function handle()
    {
        $this->info(' VERIFICANDO tipo_cotizacion_id EN COTIZACIONES');
        $this->newLine();

        // Obtener todas las cotizaciones con su tipo
        $cotizaciones = DB::table('cotizaciones')
            ->leftJoin('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
            ->select(
                'cotizaciones.id',
                'cotizaciones.numero_cotizacion',
                'cotizaciones.tipo_cotizacion_id',
                'tipos_cotizacion.codigo',
                'tipos_cotizacion.nombre',
                'cotizaciones.cliente',
                'cotizaciones.es_borrador',
                'cotizaciones.estado'
            )
            ->orderBy('cotizaciones.id', 'desc')
            ->get();

        if ($cotizaciones->isEmpty()) {
            $this->warn(' No hay cotizaciones registradas');
            return;
        }

        $this->line(' COTIZACIONES Y SUS TIPOS:');
        $this->newLine();

        $conTipo = 0;
        $sinTipo = 0;

        foreach ($cotizaciones as $cot) {
            $this->line("ID: <fg=cyan>{$cot->id}</>");
            $numero = $cot->numero_cotizacion ?? 'Borrador';
            $this->line("   Número: {$numero}");
            $this->line("   Cliente: {$cot->cliente}");

            if ($cot->tipo_cotizacion_id) {
                $conTipo++;
                $this->line("   Tipo: <fg=green> {$cot->codigo} ({$cot->nombre})</> - ID: {$cot->tipo_cotizacion_id}");
            } else {
                $sinTipo++;
                $this->line("   Tipo: <fg=red> NO ASIGNADO</>");
            }

            $estado = $cot->es_borrador ? 'BORRADOR' : $cot->estado;
            $this->line("   Estado: {$estado}");
            $this->newLine();
        }

        // Resumen
        $this->line(' RESUMEN:');
        $this->line("   Total de cotizaciones: {$cotizaciones->count()}");
        $this->line("    Con tipo asignado: {$conTipo}");
        $this->line("    Sin tipo asignado: {$sinTipo}");
        $this->newLine();

        // Estadísticas por tipo
        $this->line('📈 DISTRIBUCIÓN POR TIPO:');
        $porTipo = DB::table('cotizaciones')
            ->leftJoin('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
            ->select('tipos_cotizacion.codigo', 'tipos_cotizacion.nombre', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('cotizaciones.tipo_cotizacion_id', 'tipos_cotizacion.codigo', 'tipos_cotizacion.nombre')
            ->get();

        foreach ($porTipo as $tipo) {
            if ($tipo->codigo) {
                $this->line("   • {$tipo->codigo} ({$tipo->nombre}): {$tipo->cantidad}");
            }
        }

        $sinAsignar = DB::table('cotizaciones')->whereNull('tipo_cotizacion_id')->count();
        if ($sinAsignar > 0) {
            $this->warn("   • Sin asignar: {$sinAsignar}");
        }

        $this->newLine();

        // Verificación de integridad
        $this->line(' VERIFICACIÓN DE INTEGRIDAD:');
        $cotizacionesSinTipoValido = DB::table('cotizaciones')
            ->whereNotNull('tipo_cotizacion_id')
            ->leftJoin('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
            ->whereNull('tipos_cotizacion.id')
            ->count();

        if ($cotizacionesSinTipoValido > 0) {
            $this->warn("    {$cotizacionesSinTipoValido} cotización(es) con tipo_cotizacion_id inválido");
        } else {
            $this->line('    Todos los tipo_cotizacion_id son válidos');
        }

        $this->newLine();
        $this->info(' VERIFICACIÓN COMPLETADA');
    }
}
