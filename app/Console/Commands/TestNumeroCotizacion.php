<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class TestNumeroCotizacion extends Command
{
    protected $signature = 'test:numero-cotizacion';
    protected $description = 'Test numero_cotizacion generation';

    public function handle()
    {
        $this->info('📊 Verificando números de cotización existentes:');
        
        $cotizaciones = Cotizacion::whereNotNull('numero_cotizacion')
            ->orderBy('numero_cotizacion', 'desc')
            ->select('id', 'numero_cotizacion', 'estado', 'es_borrador')
            ->limit(10)
            ->get();
        
        if ($cotizaciones->isEmpty()) {
            $this->warn('   No hay cotizaciones enviadas');
        } else {
            foreach ($cotizaciones as $cot) {
                $this->line("  - ID: {$cot->id}, Número: {$cot->numero_cotizacion}, Estado: {$cot->estado}, EsBorrador: " . ($cot->es_borrador ? 'sí' : 'no'));
            }
        }
        
        $ultimoNumero = $cotizaciones->first()?->numero_cotizacion ?? 'ninguno';
        $this->info("\n🔢 Último número de cotización: {$ultimoNumero}");
        
        // Contar borradores
        $borradores = Cotizacion::where('es_borrador', true)->count();
        $enviadas = Cotizacion::where('es_borrador', false)->count();
        $this->info("📈 Total borradores: {$borradores}, Enviadas: {$enviadas}");
    }
}
