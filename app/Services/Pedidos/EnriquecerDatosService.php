<?php

namespace App\Services\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EnriquecerDatosService
 * 
 * Enriquece los datos de prendas que vienen del frontend con IDs faltantes
 * Busca tela_id, color_id, tipo_manga_id, tipo_broche_id por nombre
 * Si no existen, los crea automáticamente
 */
class EnriquecerDatosService
{
    /**
     * Enriquece los datos de una prenda con los IDs faltantes
     * Si no existe, crea el registro y usa su ID
     */
    public function enriquecerPrend(array $prenda): array
    {
        // Buscar/crear tela_id por nombre si no existe
        if (empty($prenda['tela_id']) && !empty($prenda['tela'])) {
            $tela = DB::table('telas_prenda')
                ->where('nombre', $prenda['tela'])
                ->first();
            
            if ($tela) {
                $prenda['tela_id'] = $tela->id;
                Log::info('✅ Tela encontrada', ['nombre' => $prenda['tela'], 'id' => $tela->id]);
            } else {
                // Crear tela nueva
                $telaId = DB::table('telas_prenda')->insertGetId([
                    'nombre' => $prenda['tela'],
                    'referencia' => $prenda['tela_referencia'] ?? '',
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $prenda['tela_id'] = $telaId;
                Log::info('✅ Tela creada', ['nombre' => $prenda['tela'], 'id' => $telaId]);
            }
        }

        // Buscar/crear color_id por nombre si no existe
        if (empty($prenda['color_id']) && !empty($prenda['color'])) {
            $color = DB::table('colores_prenda')
                ->where('nombre', $prenda['color'])
                ->first();
            
            if ($color) {
                $prenda['color_id'] = $color->id;
                Log::info('✅ Color encontrado', ['nombre' => $prenda['color'], 'id' => $color->id]);
            } else {
                // Crear color nuevo
                $colorId = DB::table('colores_prenda')->insertGetId([
                    'nombre' => $prenda['color'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $prenda['color_id'] = $colorId;
                Log::info('✅ Color creado', ['nombre' => $prenda['color'], 'id' => $colorId]);
            }
        }

        // Buscar/crear tipo_manga_id por nombre si no existe
        if (empty($prenda['tipo_manga_id']) && !empty($prenda['manga'])) {
            $manga = DB::table('tipos_manga')
                ->where('nombre', $prenda['manga'])
                ->first();
            
            if ($manga) {
                $prenda['tipo_manga_id'] = $manga->id;
                Log::info('✅ Manga encontrada', ['nombre' => $prenda['manga'], 'id' => $manga->id]);
            } else {
                // Crear tipo manga nuevo
                $mangaId = DB::table('tipos_manga')->insertGetId([
                    'nombre' => $prenda['manga'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $prenda['tipo_manga_id'] = $mangaId;
                Log::info('✅ Manga creada', ['nombre' => $prenda['manga'], 'id' => $mangaId]);
            }
        }

        // Buscar/crear tipo_broche_id por nombre si no existe
        if (empty($prenda['tipo_broche_id']) && !empty($prenda['broche'])) {
            $broche = DB::table('tipos_broche')
                ->where('nombre', $prenda['broche'])
                ->first();
            
            if ($broche) {
                $prenda['tipo_broche_id'] = $broche->id;
                Log::info('✅ Broche encontrado', ['nombre' => $prenda['broche'], 'id' => $broche->id]);
            } else {
                // Crear tipo broche nuevo
                $broqueId = DB::table('tipos_broche')->insertGetId([
                    'nombre' => $prenda['broche'],
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $prenda['tipo_broche_id'] = $broqueId;
                Log::info('✅ Broche creado', ['nombre' => $prenda['broche'], 'id' => $broqueId]);
            }
        }

        return $prenda;
    }

    /**
     * Enriquece múltiples prendas
     */
    public function enriquecerPrendas(array $prendas): array
    {
        return array_map(fn($prenda) => $this->enriquecerPrend($prenda), $prendas);
    }
}

