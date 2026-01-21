<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PrendaPedido;

class TestVariantesGenero extends Command
{
    protected $signature = 'test:variantes-genero';
    protected $description = 'Test de variantes con múltiples géneros (genero-talla)';

    public function handle()
    {
        $this->info("\n========================================");
        $this->info("🧪 TEST: Variantes con Género-Talla");
        $this->info("========================================\n");

        try {
            //  BUSCAR PRENDAS CON MÚLTIPLES GÉNEROS
            $this->info(" Buscando prendas con múltiples géneros...");
            
            $prendas = PrendaPedido::all();
            
            $prendasMultiplesGeneros = [];
            foreach ($prendas as $prenda) {
                $cantidadTalla = $prenda->cantidad_talla;
                
                if (is_string($cantidadTalla)) {
                    $cantidadTalla = json_decode($cantidadTalla, true);
                }
                
                if (is_array($cantidadTalla) && count($cantidadTalla) > 1) {
                    $prendasMultiplesGeneros[] = [
                        'id' => $prenda->id,
                        'nombre' => $prenda->nombre_prenda,
                        'cantidad_talla' => $cantidadTalla,
                        'generos' => array_keys($cantidadTalla),
                    ];
                }
            }
            
            if (empty($prendasMultiplesGeneros)) {
                $this->warn(" No hay prendas con múltiples géneros en BD");
                $this->info("\nCreando prenda de prueba...");
                
                // Crear prenda de prueba
                $pedidoId = DB::table('pedidos_produccion')->first()?->id;
                if (!$pedidoId) {
                    $this->error(" No hay pedidos en BD");
                    return 1;
                }
                
                $prenda = PrendaPedido::create([
                    'pedido_produccion_id' => $pedidoId,
                    'nombre_prenda' => 'Camiseta Test Géneros',
                    'descripcion' => 'Prueba de múltiples géneros',
                    'cantidad_talla' => json_encode([
                        'dama' => ['S' => 10, 'M' => 15, 'L' => 20],
                        'caballero' => ['S' => 8, 'M' => 12, 'L' => 18],
                    ]),
                    'genero' => 'unisex',
                    'de_bodega' => 1,
                ]);
                
                $this->info(" Prenda creada: ID {$prenda->id}");
                $prendasMultiplesGeneros[] = [
                    'id' => $prenda->id,
                    'nombre' => $prenda->nombre_prenda,
                    'cantidad_talla' => json_decode($prenda->cantidad_talla, true),
                    'generos' => ['dama', 'caballero'],
                ];
            }
            
            $this->info(" Encontradas " . count($prendasMultiplesGeneros) . " prenda(s) con múltiples géneros\n");

            //  VERIFICAR VARIANTES
            $this->info(" Verificando variantes guardadas...\n");
            
            $todasOk = true;
            
            foreach ($prendasMultiplesGeneros as $prendasInfo) {
                $prendasId = $prendasInfo['id'];
                $prendasNombre = $prendasInfo['nombre'];
                $cantidadTalla = $prendasInfo['cantidad_talla'];
                $generosEsperados = $prendasInfo['generos'];
                
                $this->line(" Prenda: {$prendasNombre} (ID: {$prendasId})");
                $this->line("   Cantidad Talla: " . json_encode($cantidadTalla));
                
                // Obtener variantes
                $variantes = DB::table('prenda_pedido_variantes')
                    ->where('prenda_pedido_id', $prendasId)
                    ->orderBy('talla')
                    ->get();
                
                $this->line("   Variantes en BD: " . $variantes->count());
                
                if ($variantes->count() === 0) {
                    $this->error("    SIN VARIANTES");
                    $todasOk = false;
                    continue;
                }
                
                // Verificar que cada combinación genero-talla existe
                $variatesExpectadas = [];
                $variatesEncontradas = [];
                
                foreach ($generosEsperados as $genero) {
                    $tallasPorGenero = $cantidadTalla[$genero] ?? [];
                    if (is_array($tallasPorGenero)) {
                        foreach ($tallasPorGenero as $talla => $cantidad) {
                            $variatesExpectadas[] = "{$genero}-{$talla}";
                        }
                    }
                }
                
                foreach ($variantes as $var) {
                    $variatesEncontradas[] = $var->talla;
                    $this->line("     • Talla: {$var->talla}, Cantidad: {$var->cantidad}");
                }
                
                // Comparar
                $faltantes = array_diff($variatesExpectadas, $variatesEncontradas);
                $extras = array_diff($variatesEncontradas, $variatesExpectadas);
                
                if (!empty($faltantes)) {
                    $this->error("    Variantes faltantes: " . implode(', ', $faltantes));
                    $todasOk = false;
                }
                
                if (!empty($extras)) {
                    $this->warn("    Variantes extras: " . implode(', ', $extras));
                    $todasOk = false;
                }
                
                if (empty($faltantes) && empty($extras)) {
                    $this->info("    Todas las variantes correctas");
                }
                
                $this->line("");
            }

            //  RESULTADO FINAL
            $this->info("========================================");
            if ($todasOk) {
                $this->info(" TEST EXITOSO: Variantes con genero-talla funcionan correctamente");
            } else {
                $this->error(" TEST FALLÓ: Hay problemas con las variantes");
            }
            $this->info("========================================\n");
            
            return $todasOk ? 0 : 1;

        } catch (\Exception $e) {
            $this->error(" Error: {$e->getMessage()}");
            $this->line("\nStack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
