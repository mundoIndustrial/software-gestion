<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PrendaFotoCot;
use App\Models\PrendaTelaFotoCot;
use App\Models\LogoFotoCot;

class TestBorrarImagenes extends Command
{
    protected $signature = 'test:borrar-imagenes';
    protected $description = 'Test borrado de imágenes del borrador';

    public function handle()
    {
        $this->info('🧪 TEST: Borrado de imágenes del borrador');
        
        // 1. Verificar que existan imágenes
        $this->info('\n📊 Verificando imágenes existentes:');
        
        $prendaFotos = PrendaFotoCot::count();
        $telaFotos = PrendaTelaFotoCot::count();
        $logoFotos = LogoFotoCot::count();
        
        $this->line("  Fotos de prendas: {$prendaFotos}");
        $this->line("  Fotos de telas: {$telaFotos}");
        $this->line("  Fotos de logos: {$logoFotos}");
        
        if ($prendaFotos > 0) {
            $primera = PrendaFotoCot::first();
            $this->info("\n✅ Ejemplo de foto de prenda:");
            $this->line("   ID: {$primera->id}");
            $this->line("   Ruta: {$primera->ruta_webp}");
            $this->line("   Prenda: {$primera->prenda_cot_id}");
        }
        
        if ($telaFotos > 0) {
            $primera = PrendaTelaFotoCot::first();
            $this->info("\n✅ Ejemplo de foto de tela:");
            $this->line("   ID: {$primera->id}");
            $this->line("   Ruta: {$primera->ruta_webp}");
            $this->line("   Prenda: {$primera->prenda_cot_id}");
        }
        
        if ($logoFotos > 0) {
            $primera = LogoFotoCot::first();
            $this->info("\n✅ Ejemplo de foto de logo:");
            $this->line("   ID: {$primera->id}");
            $this->line("   Ruta: {$primera->ruta_webp}");
            $this->line("   Logo: {$primera->logo_cotizacion_id}");
        }
        
        $this->info("\n📝 Rutas de endpoint para probar:");
        $this->line("  DELETE /cotizaciones/imagenes/prenda/{id}");
        $this->line("  DELETE /cotizaciones/imagenes/tela/{id}");
        $this->line("  DELETE /cotizaciones/imagenes/logo/{id}");
        
        $this->info("\n✅ TEST COMPLETADO");
    }
}
