<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;

class AnalizarCotizaciones extends Command
{
    protected $signature = 'analizar:cotizaciones {--usuario_id= : ID del usuario (asesor)}';
    protected $description = 'Analiza las cotizaciones en la base de datos';

    public function handle()
    {
        $this->info(' ANALIZANDO COTIZACIONES EN LA BASE DE DATOS');
        $this->line('');

        // Obtener usuario_id del argumento o usar el actual
        $usuarioId = $this->option('usuario_id');

        // Estadísticas generales
        $this->line(' ESTADÍSTICAS GENERALES:');
        $totalCotizaciones = Cotizacion::count();
        $this->info("   Total de cotizaciones: {$totalCotizaciones}");

        if ($usuarioId) {
            $usuarioCotizaciones = Cotizacion::where('asesor_id', $usuarioId)->count();
            $this->info("   Cotizaciones del usuario {$usuarioId}: {$usuarioCotizaciones}");
        }

        $this->line('');

        // Análisis por es_borrador
        $this->line(' ANÁLISIS POR ESTADO (es_borrador):');
        $borradores = Cotizacion::where('es_borrador', 1)->count();
        $enviadas = Cotizacion::where('es_borrador', 0)->count();
        $this->info("   Borradores (es_borrador = 1): {$borradores}");
        $this->info("   Enviadas (es_borrador = 0): {$enviadas}");

        if ($usuarioId) {
            $usuarioBorradores = Cotizacion::where('asesor_id', $usuarioId)->where('es_borrador', 1)->count();
            $usuarioEnviadas = Cotizacion::where('asesor_id', $usuarioId)->where('es_borrador', 0)->count();
            $this->info("   Borradores del usuario {$usuarioId}: {$usuarioBorradores}");
            $this->info("   Enviadas del usuario {$usuarioId}: {$usuarioEnviadas}");
        }

        $this->line('');

        // Análisis por tipo_venta
        $this->line(' ANÁLISIS POR TIPO_VENTA:');
        $tipoVentas = Cotizacion::select('tipo_venta', DB::raw('count(*) as total'))
            ->groupBy('tipo_venta')
            ->get();

        if ($tipoVentas->count() > 0) {
            foreach ($tipoVentas as $tipo) {
                $this->info("   Tipo Venta '{$tipo->tipo_venta}': {$tipo->total}");
            }
        } else {
            $this->warn("   No hay datos de tipo_venta");
        }

        $this->line('');

        // Análisis por estado
        $this->line(' ANÁLISIS POR ESTADO:');
        $estados = Cotizacion::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        foreach ($estados as $estado) {
            $this->info("   Estado '{$estado->estado}': {$estado->total}");
        }

        $this->line('');

        // Últimas 10 cotizaciones
        $this->line('📅 ÚLTIMAS 10 COTIZACIONES:');
        $ultimasCotizaciones = Cotizacion::orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'asesor_id', 'numero_cotizacion', 'tipo_venta', 'cliente', 'es_borrador', 'estado', 'created_at']);

        $this->table(
            ['ID', 'Asesor ID', 'Número', 'Tipo Venta', 'Cliente', 'Borrador', 'Estado', 'Creada'],
            $ultimasCotizaciones->map(fn($c) => [
                $c->id,
                $c->asesor_id,
                $c->numero_cotizacion ?? 'N/A',
                $c->tipo_venta ?? 'N/A',
                substr($c->cliente, 0, 20),
                $c->es_borrador ? 'Sí' : 'No',
                $c->estado,
                $c->created_at->format('d/m/Y H:i'),
            ])->toArray()
        );

        $this->line('');

        // Análisis detallado por usuario
        if ($usuarioId) {
            $this->line(" ANÁLISIS DETALLADO DEL USUARIO {$usuarioId}:");
            $cotizacionesUsuario = Cotizacion::where('asesor_id', $usuarioId)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'numero_cotizacion', 'tipo_venta', 'cliente', 'es_borrador', 'estado', 'created_at']);

            if ($cotizacionesUsuario->count() > 0) {
                $this->table(
                    ['ID', 'Número', 'Tipo Venta', 'Cliente', 'Borrador', 'Estado', 'Creada'],
                    $cotizacionesUsuario->map(fn($c) => [
                        $c->id,
                        $c->numero_cotizacion ?? 'N/A',
                        $c->tipo_venta ?? 'N/A',
                        substr($c->cliente, 0, 20),
                        $c->es_borrador ? 'Sí' : 'No',
                        $c->estado,
                        $c->created_at->format('d/m/Y H:i'),
                    ])->toArray()
                );
            } else {
                $this->warn("   No hay cotizaciones para este usuario");
            }
        }

        $this->line('');
        $this->info(' Análisis completado');
    }
}
