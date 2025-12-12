<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActualizarRutasImagenes extends Command
{
    protected $signature = 'imagenes:actualizar-rutas';
    protected $description = 'Actualiza las rutas de imágenes de /storage-serve/ a /storage/';

    public function handle()
    {
        $this->info('🔄 Actualizando rutas de imágenes en la BD...');

        // Actualizar prenda_fotos_cot
        $fotosActualizadas = DB::table('prenda_fotos_cot')
            ->where('ruta_original', 'like', '%storage-serve%')
            ->orWhere('ruta_webp', 'like', '%storage-serve%')
            ->update([
                'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
                'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
            ]);

        $this->info("✅ Fotos de prendas actualizadas: $fotosActualizadas");

        // Actualizar prenda_tela_fotos si existe
        if (DB::getSchemaBuilder()->hasTable('prenda_tela_fotos')) {
            $telasActualizadas = DB::table('prenda_tela_fotos')
                ->where('ruta_original', 'like', '%storage-serve%')
                ->orWhere('ruta_webp', 'like', '%storage-serve%')
                ->update([
                    'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
                    'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
                ]);

            $this->info("✅ Fotos de telas actualizadas: $telasActualizadas");
        } else {
            $this->info("⚠️ Tabla prenda_tela_fotos no existe, omitiendo...");
        }

        // Actualizar logo_fotos si existe
        if (DB::getSchemaBuilder()->hasTable('logo_fotos')) {
            $logoActualizadas = DB::table('logo_fotos')
                ->where('ruta_original', 'like', '%storage-serve%')
                ->orWhere('ruta_webp', 'like', '%storage-serve%')
                ->update([
                    'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
                    'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
                ]);

            $this->info("✅ Fotos de logos actualizadas: $logoActualizadas");
        } else {
            $this->info("⚠️ Tabla logo_fotos no existe, omitiendo...");
        }

        $this->info("\n✅ Todas las rutas han sido actualizadas correctamente.");
    }
}
