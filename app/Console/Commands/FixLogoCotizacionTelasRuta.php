<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LogoCotizacionTelasPrenda;
use Illuminate\Support\Facades\DB;

class FixLogoCotizacionTelasRuta extends Command
{
    protected $signature = 'fix:logo-cotizacion-telas-ruta';
    protected $description = 'Corrige las rutas de imágenes en logo_cotizacion_telas_prenda';

    public function handle()
    {
        $this->info('🔧 Corrigiendo rutas en logo_cotizacion_telas_prenda...');

        $telas = LogoCotizacionTelasPrenda::whereNotNull('img')->get();
        
        $this->info("📊 Total de registros con imagen: " . $telas->count());

        $actualizado = 0;
        foreach ($telas as $tela) {
            if ($tela->img && strpos($tela->img, 'storage/app/public/') === 0) {
                // Convertir de 'storage/app/public/cotizaciones/...' a '/storage/cotizaciones/...'
                $rutaCorregida = '/storage/' . str_replace('storage/app/public/', '', $tela->img);
                
                $tela->update(['img' => $rutaCorregida]);
                
                $this->line("  ✅ ID {$tela->id}: {$tela->img} → {$rutaCorregida}");
                $actualizado++;
            } else {
                $this->line("  ℹ️ ID {$tela->id}: Ya tiene ruta correcta o está vacío");
            }
        }

        $this->info("\n✅ Actualización completada");
        $this->info("   Registros actualizados: {$actualizado}");
    }
}
