<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\User;

class DebugCotizacionesPorAsesor extends Command
{
    protected $signature = 'debug:cotizaciones-asesor {asesor_id?}';
    protected $description = 'Debug de cotizaciones por asesor';

    public function handle()
    {
        $asesor_id = $this->argument('asesor_id');

        if (!$asesor_id) {
            // Obtener todos los asesores
            $asesores = User::all();
            $this->info("=== LISTANDO TODOS LOS USUARIOS ===\n");
            foreach ($asesores as $user) {
                $this->line("ID: {$user->id} | Nombre: {$user->name} | Email: {$user->email}");
            }
            return;
        }

        $this->info("=== DEBUG DE COTIZACIONES PARA ASESOR_ID: $asesor_id ===\n");

        $asesor = User::find($asesor_id);
        if (!$asesor) {
            $this->error("Asesor no encontrado");
            return;
        }

        $this->info("ASESOR: {$asesor->name}");
        $this->info("EMAIL: {$asesor->email}\n");

        // Todas las cotizaciones del asesor
        $todas = Cotizacion::where('asesor_id', $asesor_id)->get();
        $this->info("Total de cotizaciones: " . $todas->count());
        foreach ($todas as $cot) {
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            $this->line("  ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$cot->estado} | CLIENTE: $cliente");
        }

        // Cotizaciones aprobadas
        $this->info("\n=== COTIZACIONES APROBADAS (Lo que vería en crear-desde-cotizacion-editable) ===");
        $aprobadas = Cotizacion::where('asesor_id', $asesor_id)
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->info("Total APROBADAS: " . $aprobadas->count());
        foreach ($aprobadas as $cot) {
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            $this->line("  ✅ ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | CLIENTE: $cliente | PRENDAS: " . $cot->prendasCotizaciones->count());
        }

        if ($aprobadas->count() === 0) {
            $this->warn("\n⚠️ NO HAY COTIZACIONES APROBADAS PARA ESTE ASESOR");
            $this->line("\nPARA PROBAR, PUEDES EJECUTAR:");
            $this->line("php artisan debug:actualizar-estado-cotizaciones $asesor_id");
        }
    }
}
