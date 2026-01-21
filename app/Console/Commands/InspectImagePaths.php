<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectImagePaths extends Command
{
    protected $signature = 'inspect:image-paths {pedido_id}';
    protected $description = 'Inspecciona las rutas de imágenes guardadas en la BD para un pedido específico';

    public function handle()
    {
        $pedidoId = $this->argument('pedido_id');
        
        $this->info("Inspeccionando rutas de imágenes para pedido: $pedidoId");
        $this->line('');

        // Obtener prendas del pedido
        $prendas = DB::table('prendas_pedido')
            ->where('numero_pedido', $pedidoId)
            ->get(['id', 'nombre_prenda']);

        if ($prendas->isEmpty()) {
            $this->error("No se encontraron prendas para el pedido $pedidoId");
            return 1;
        }

        foreach ($prendas as $prenda) {
            $this->info("📌 Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})");
            
            // Fotos de prenda
            $fotosPrenda = DB::table('prenda_fotos_pedido')
                ->where('prenda_pedido_id', $prenda->id)
                ->get(['id', 'ruta_webp', 'ruta_original', 'ruta_miniatura']);

            if ($fotosPrenda->isNotEmpty()) {
                $this->line('  📸 Fotos de Prenda:');
                foreach ($fotosPrenda as $foto) {
                    $this->line("    - ID: {$foto->id}");
                    $this->line("      ruta_webp: {$foto->ruta_webp}");
                    $this->line("      ruta_original: {$foto->ruta_original}");
                    if ($foto->ruta_miniatura) {
                        $this->line("      ruta_miniatura: {$foto->ruta_miniatura}");
                    }
                }
            }

            // Fotos de tela
            $fotosTela = DB::table('prenda_fotos_tela_pedido')
                ->where('prenda_pedido_id', $prenda->id)
                ->get(['id', 'ruta_webp', 'ruta_original', 'ruta_miniatura']);

            if ($fotosTela->isNotEmpty()) {
                $this->line('  🧵 Fotos de Tela:');
                foreach ($fotosTela as $foto) {
                    $this->line("    - ID: {$foto->id}");
                    $this->line("      ruta_webp: {$foto->ruta_webp}");
                    $this->line("      ruta_original: {$foto->ruta_original}");
                    if ($foto->ruta_miniatura) {
                        $this->line("      ruta_miniatura: {$foto->ruta_miniatura}");
                    }
                }
            }

            $this->line('');
        }

        return 0;
    }
}
