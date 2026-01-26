<?php

namespace App\Console\Commands;

use App\Domain\Pedidos\Services\ImagenRelocalizadorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestImagenRelocalizador extends Command
{
    protected $signature = 'test:imagen-relocalizador';
    protected $description = 'Prueba el servicio de relocalización de imágenes con rutas antiguas y nuevas';

    public function handle()
    {
        $servicio = app(ImagenRelocalizadorService::class);

        $this->info('🧪 Test: ImagenRelocalizadorService');
        $this->info('');

        // Test 1: Formato ANTIGUO (prendas/2026/01/archivo.jfif)
        $this->info('TEST 1: Formato ANTIGUO (prendas/2026/01/...)');
        
        $rutasAntiguas = [
            'prendas/2026/01/1769372084_697679b4c2a2d.jfif',
            'telas/2026/01/1769372084_697679b4c5df9.jfif',
        ];

        foreach ($rutasAntiguas as $ruta) {
            // Crear archivo de prueba
            Storage::disk('public')->put($ruta, 'test content');
            $this->line("  ✓ Archivo creado: {$ruta}");
        }

        $this->info('');
        $this->info('Relocalizando...');
        
        $rutasFinales = $servicio->relocalizarImagenes(2753, $rutasAntiguas);

        $this->info('');
        if (!empty($rutasFinales)) {
            $this->line('✅ Relocalización EXITOSA:');
            foreach ($rutasFinales as $ruta) {
                $this->line("  → {$ruta}");
                if (Storage::disk('public')->exists($ruta)) {
                    $this->line("    ✓ Archivo existe en almacenamiento");
                } else {
                    $this->line("    ✗ FALLA: Archivo NO existe");
                }
            }
        } else {
            $this->error(' NO se relocalizaron archivos');
        }

        $this->info('');
        $this->info('TEST 2: Formato NUEVO (prendas/temp/uuid/...)');
        
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $rutasNuevas = [
            "prendas/temp/{$uuid}/webp/prenda_0_20260125_xyz.webp",
            "telas/temp/{$uuid}/webp/tela_0_20260125_abc.webp",
        ];

        foreach ($rutasNuevas as $ruta) {
            Storage::disk('public')->put($ruta, 'test content');
            $this->line("  ✓ Archivo creado: {$ruta}");
        }

        $this->info('');
        $this->info('Relocalizando...');
        
        $rutasFinales2 = $servicio->relocalizarImagenes(2753, $rutasNuevas);

        $this->info('');
        if (!empty($rutasFinales2)) {
            $this->line('✅ Relocalización EXITOSA:');
            foreach ($rutasFinales2 as $ruta) {
                $this->line("  → {$ruta}");
                if (Storage::disk('public')->exists($ruta)) {
                    $this->line("    ✓ Archivo existe");
                } else {
                    $this->line("    ✗ FALLA: Archivo NO existe");
                }
            }
        } else {
            $this->error(' NO se relocalizaron archivos');
        }

        $this->info('');
        $this->info('🎉 Tests completados');
    }
}
