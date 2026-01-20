<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * MIGRACIÓN DE DATOS: Normalizar prendas_pedido
     * 
     * Esta migración ejecuta DESPUÉS de que prendas_pedido se haya normalizado.
     * 
     * Objetivo: Migrar datos de variantes de prendas_pedido a prenda_variantes
     * 
     * Flujo:
     * 1. Para cada prenda en prendas_pedido
     * 2. Si tiene color_id, tela_id, tipo_manga_id, etc → crear VARIANTE
     * 3. Procesar cantidad_talla JSON para crear variantes por talla
     * 4. Preservar observaciones (manga_obs, bolsillos_obs, broche_obs)
     */
    public function up(): void
    {
        \Log::info('🔄 [Migración de Datos] Iniciando migración de variantes a tabla hija...');

        try {
            // Obtener todas las prendas con datos que deben migrarse
            // Solo usar cantidad_talla ya que las otras columnas fueron eliminadas
            $prendas = DB::table('prendas_pedido')
                ->whereNotNull('cantidad_talla')
                ->get();

            $variantesCreadas = 0;
            $prendasProcesadas = 0;

            foreach ($prendas as $prenda) {
                \Log::info(" Procesando prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})");

                // Decodificar cantidad_talla si es JSON
                $cantidadTalla = [];
                if (!empty($prenda->cantidad_talla)) {
                    if (is_string($prenda->cantidad_talla)) {
                        $cantidadTalla = json_decode($prenda->cantidad_talla, true) ?? [];
                    } else {
                        $cantidadTalla = (array)$prenda->cantidad_talla;
                    }
                }

                \Log::debug("  Cantidad por talla:", $cantidadTalla);

                // Crear una variante POR CADA TALLA desde el JSON
                if (!empty($cantidadTalla)) {
                    foreach ($cantidadTalla as $talla => $cantidad) {
                        $this->crearVariante(
                            $prenda->id,
                            $talla,
                            (int)$cantidad,
                            null,  // color_id eliminado
                            null,  // tela_id eliminado
                            null,  // tipo_manga_id eliminado
                            null,  // tipo_broche_id eliminado
                            null,  // manga_obs eliminado
                            null,  // broche_obs eliminado
                            false, // tiene_bolsillos eliminado
                            null   // bolsillos_obs eliminado
                        );
                        $variantesCreadas++;
                    }
                } else {
                    // Si cantidad_talla está vacío o es NULL, crear una variante genérica
                    $this->crearVariante(
                        $prenda->id,
                        'N/A',  // talla genérica
                        0,      // cantidad
                        null,   // color_id
                        null,   // tela_id
                        null,   // tipo_manga_id
                        null,   // tipo_broche_id
                        null,   // manga_obs
                        null,   // broche_obs
                        false,  // tiene_bolsillos
                        null    // bolsillos_obs
                    );
                    $variantesCreadas++;
                }

                $prendasProcesadas++;
            }

            \Log::info(" [Migración de Datos] Completada", [
                'prendas_procesadas' => $prendasProcesadas,
                'variantes_creadas' => $variantesCreadas,
            ]);
        } catch (\Exception $e) {
            \Log::error(' [Migración de Datos] Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear una variante en la tabla prenda_pedido_variantes
     * 
     * @param $prendaId
     * @param $talla
     * @param $cantidad
     * @param $colorId
     * @param $telaId
     * @param $tipoMangaId
     * @param $tipoBrocheId
     * @param $mangaObs
     * @param $brocheObs
     * @param $tieneBolsillos
     * @param $bolsillosObs
     */
    private function crearVariante(
        $prendaId,
        $talla,
        $cantidad,
        $colorId,
        $telaId,
        $tipoMangaId,
        $tipoBrocheId,
        $mangaObs,
        $brocheObs,
        $tieneBolsillos,
        $bolsillosObs
    ): void {
        try {
            // Verificar si ya existe esta variante (evitar duplicados)
            $existe = DB::table('prenda_pedido_variantes')
                ->where('prenda_pedido_id', $prendaId)
                ->where('talla', $talla)
                ->where('color_id', $colorId)
                ->where('tela_id', $telaId)
                ->where('tipo_manga_id', $tipoMangaId)
                ->where('tipo_broche_boton_id', $tipoBrocheId)
                ->exists();

            if ($existe) {
                \Log::debug("   Variante ya existe (skipped): Talla={$talla}");
                return;
            }

            // Insertar variante
            DB::table('prenda_pedido_variantes')->insert([
                'prenda_pedido_id' => $prendaId,
                'talla' => $talla,
                'cantidad' => $cantidad,
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $tipoMangaId,
                'tipo_broche_boton_id' => $tipoBrocheId,
                'manga_obs' => $mangaObs,
                'broche_boton_obs' => $brocheObs,
                'tiene_bolsillos' => $tieneBolsillos ? 1 : 0,
                'bolsillos_obs' => $bolsillosObs,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::debug("   Variante creada: Talla={$talla}, Cantidad={$cantidad}");
        } catch (\Exception $e) {
            \Log::error("   Error creando variante", [
                'talla' => $talla,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Log::warning(' [Migración de Datos] ROLLBACK: Eliminando variantes creadas...');

        try {
            // Eliminar todas las variantes creadas en esta migración
            // (Nota: Si se ejecutó down de la migración anterior, las variantes se eliminan automáticamente)
            DB::table('prenda_pedido_variantes')->truncate();
            \Log::info(' Variantes eliminadas');
        } catch (\Exception $e) {
            \Log::error(' Error en rollback', [
                'error' => $e->getMessage(),
            ]);
        }
    }
};