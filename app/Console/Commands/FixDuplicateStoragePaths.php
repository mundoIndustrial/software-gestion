<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateStoragePaths extends Command
{
    protected $signature = 'fix:duplicate-storage-paths';
    protected $description = 'Corrige rutas duplicadas /storage/storage/ en las tablas de imágenes de pedidos';

    public function handle()
    {
        $this->info('Iniciando corrección de rutas duplicadas...');

        try {
            // 1. Corregir tabla prenda_fotos_pedido
            $this->info('Corrigiendo tabla prenda_fotos_pedido...');
            
            $count1 = DB::table('prenda_fotos_pedido')
                ->where('ruta_webp', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_webp: $count1 registros corregidos");

            $count2 = DB::table('prenda_fotos_pedido')
                ->where('ruta_original', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_original' => DB::raw("REPLACE(ruta_original, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_original: $count2 registros corregidos");

            $count3 = DB::table('prenda_fotos_pedido')
                ->where('ruta_miniatura', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_miniatura' => DB::raw("REPLACE(ruta_miniatura, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_miniatura: $count3 registros corregidos");

            // 2. Corregir tabla prenda_fotos_tela_pedido
            $this->info('Corrigiendo tabla prenda_fotos_tela_pedido...');
            
            $count4 = DB::table('prenda_fotos_tela_pedido')
                ->where('ruta_webp', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_webp: $count4 registros corregidos");

            $count5 = DB::table('prenda_fotos_tela_pedido')
                ->where('ruta_original', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_original' => DB::raw("REPLACE(ruta_original, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_original: $count5 registros corregidos");

            $count6 = DB::table('prenda_fotos_tela_pedido')
                ->where('ruta_miniatura', 'LIKE', '%/storage/storage/%')
                ->update(['ruta_miniatura' => DB::raw("REPLACE(ruta_miniatura, '/storage/storage/', '/storage/')")]);
            
            $this->info("  - ruta_miniatura: $count6 registros corregidos");

            // 3. Verificar que se corrigieron
            $this->info('Verificando correcciones...');
            
            $duplicadosPrenda = DB::table('prenda_fotos_pedido')
                ->where('ruta_webp', 'LIKE', '%/storage/storage/%')
                ->count();
            
            $duplicadosTela = DB::table('prenda_fotos_tela_pedido')
                ->where('ruta_webp', 'LIKE', '%/storage/storage/%')
                ->count();

            if ($duplicadosPrenda === 0 && $duplicadosTela === 0) {
                $this->info(' Todas las rutas duplicadas han sido corregidas exitosamente');
                $this->info("Total de registros corregidos: " . ($count1 + $count2 + $count3 + $count4 + $count5 + $count6));
                return 0;
            } else {
                $this->error(" Aún hay rutas duplicadas: prenda_fotos_pedido=$duplicadosPrenda, prenda_fotos_tela_pedido=$duplicadosTela");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error al corregir rutas: ' . $e->getMessage());
            return 1;
        }
    }
}
