<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Revertir el prefijo /storage/ de las rutas en las tablas de fotos
        
        // Fotos de prenda
        DB::table('prenda_fotos_pedido')
            ->orderBy('id')
            ->each(function ($foto) {
                $updated = [];
                if ($foto->ruta_original && str_starts_with($foto->ruta_original, '/storage/')) {
                    $updated['ruta_original'] = substr($foto->ruta_original, 9); // Remover /storage/
                }
                if ($foto->ruta_webp && str_starts_with($foto->ruta_webp, '/storage/')) {
                    $updated['ruta_webp'] = substr($foto->ruta_webp, 9); // Remover /storage/
                }
                if (!empty($updated)) {
                    DB::table('prenda_fotos_pedido')->where('id', $foto->id)->update($updated);
                }
            });
        
        // Fotos de logo
        DB::table('prenda_fotos_logo_pedido')
            ->orderBy('id')
            ->each(function ($foto) {
                $updated = [];
                if ($foto->ruta_original && str_starts_with($foto->ruta_original, '/storage/')) {
                    $updated['ruta_original'] = substr($foto->ruta_original, 9);
                }
                if ($foto->ruta_webp && str_starts_with($foto->ruta_webp, '/storage/')) {
                    $updated['ruta_webp'] = substr($foto->ruta_webp, 9);
                }
                if (!empty($updated)) {
                    DB::table('prenda_fotos_logo_pedido')->where('id', $foto->id)->update($updated);
                }
            });
        
        // Fotos de tela
        DB::table('prenda_fotos_tela_pedido')
            ->orderBy('id')
            ->each(function ($foto) {
                $updated = [];
                if ($foto->ruta_original && str_starts_with($foto->ruta_original, '/storage/')) {
                    $updated['ruta_original'] = substr($foto->ruta_original, 9);
                }
                if ($foto->ruta_webp && str_starts_with($foto->ruta_webp, '/storage/')) {
                    $updated['ruta_webp'] = substr($foto->ruta_webp, 9);
                }
                if (!empty($updated)) {
                    DB::table('prenda_fotos_tela_pedido')->where('id', $foto->id)->update($updated);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en reverso
    }
};
