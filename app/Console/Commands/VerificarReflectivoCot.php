<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaCotReflectivo;

class VerificarReflectivoCot extends Command
{
    protected $signature = 'verificar:reflectivo {cotizacion_id=7}';
    protected $description = 'Verifica datos de prenda_cot_reflectivo para una cotización';

    public function handle()
    {
        $cotizacionId = $this->argument('cotizacion_id');
        
        $this->line('');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->line('  VERIFICACIÓN DE DATOS REFLECTIVO - COTIZACIÓN #' . $cotizacionId);
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->line('');

        // Verificar cotización
        $cotizacion = Cotizacion::find($cotizacionId);

        if (!$cotizacion) {
            $this->error('❌ Cotización ID ' . $cotizacionId . ' no encontrada');
            return 1;
        }

        $this->info('📋 INFORMACIÓN DE LA COTIZACIÓN');
        $this->line('───────────────────────────────────────────────────────────────');
        $this->line('ID: ' . $cotizacion->id);
        $this->line('Número: ' . $cotizacion->numero_cotizacion);
        $this->line('Cliente: ' . ($cotizacion->cliente?->nombre ?? 'N/A'));
        $this->line('Tipo: ID=' . $cotizacion->tipo_cotizacion_id . ' | Nombre=' . ($cotizacion->tipoCotizacion?->nombre ?? 'N/A'));
        $this->line('Estado: ' . $cotizacion->estado);
        $this->line('');

        // Obtener prendas
        $prendas = PrendaCot::where('cotizacion_id', $cotizacionId)->get();
        
        $this->info('📦 PRENDAS DE LA COTIZACIÓN');
        $this->line('───────────────────────────────────────────────────────────────');
        $this->line('Total de prendas: ' . $prendas->count());
        $this->line('');

        if ($prendas->isEmpty()) {
            $this->warn('⚠️  No hay prendas en esta cotización');
            return 0;
        }

        // Para cada prenda, mostrar datos
        foreach ($prendas as $index => $prenda) {
            $this->info('🧥 [PRENDA ' . ($index + 1) . ']');
            $this->line('───────────────────────────────────────────────────────────────');
            $this->line('ID: ' . $prenda->id);
            $this->line('Nombre: ' . $prenda->nombre_producto);
            $this->line('');

            // Buscar datos en prenda_cot_reflectivo
            $prendaReflectivo = PrendaCotReflectivo::where([
                'cotizacion_id' => $cotizacionId,
                'prenda_cot_id' => $prenda->id
            ])->first();

            if (!$prendaReflectivo) {
                $this->warn('⚠️  No hay registro en prenda_cot_reflectivo');
                $this->line('');
                continue;
            }

            $this->line('✅ Registro en prenda_cot_reflectivo encontrado');
            $this->line('');

            // Telas, Colores y Referencias
            $this->info('   🧵 TELAS / COLORES / REFERENCIAS:');
            $this->line('   ┌─────────────────────────────────────────────────────────');
            
            if ($prendaReflectivo->color_tela_ref) {
                $colorTelaRef = $prendaReflectivo->color_tela_ref;
                $this->line('   Tipo en PHP: ' . gettype($colorTelaRef));
                if (is_array($colorTelaRef)) {
                    $this->line('   ✅ Es un Array (' . count($colorTelaRef) . ' elementos)');
                    foreach ($colorTelaRef as $idx => $item) {
                        $this->line('   ');
                        $this->line('   [' . $idx . '] Tela: ' . ($item['tela'] ?? 'N/A'));
                        $this->line('       Color: ' . ($item['color'] ?? 'N/A'));
                        $this->line('       Referencia: ' . ($item['referencia'] ?? 'N/A'));
                    }
                } else {
                    $this->warn('   ⚠️  Es una String (debería ser Array)');
                    $this->line('   Valor: ' . $colorTelaRef);
                    $this->line('   Intentando decodificar JSON...');
                    $decoded = json_decode($colorTelaRef, true);
                    if ($decoded) {
                        $this->info('   ✅ JSON decodificado correctamente');
                        foreach ($decoded as $idx => $item) {
                            $this->line('   ');
                            $this->line('   [' . $idx . '] Tela: ' . ($item['tela'] ?? 'N/A'));
                            $this->line('       Color: ' . ($item['color'] ?? 'N/A'));
                            $this->line('       Referencia: ' . ($item['referencia'] ?? 'N/A'));
                        }
                    }
                }
            } else {
                $this->warn('   ⚠️  Sin datos (NULL)');
            }
            $this->line('   └─────────────────────────────────────────────────────────');
            $this->line('');

            // Variaciones
            $this->info('   📐 VARIACIONES:');
            $this->line('   ┌─────────────────────────────────────────────────────────');
            
            if ($prendaReflectivo->variaciones) {
                $variaciones = $prendaReflectivo->variaciones;
                if (is_array($variaciones)) {
                    $this->line('   Tipo: Array (' . count($variaciones) . ' elementos)');
                    foreach ($variaciones as $idx => $variacion) {
                        $this->line('   [' . $idx . '] ' . json_encode($variacion, JSON_UNESCAPED_UNICODE));
                    }
                } else {
                    $this->line('   Tipo: String');
                    $this->line('   ' . $variaciones);
                }
            } else {
                $this->warn('   ⚠️  Sin datos (NULL)');
            }
            $this->line('   └─────────────────────────────────────────────────────────');
            $this->line('');

            // Ubicaciones
            $this->info('   📍 UBICACIONES:');
            $this->line('   ┌─────────────────────────────────────────────────────────');
            
            if ($prendaReflectivo->ubicaciones) {
                $ubicaciones = $prendaReflectivo->ubicaciones;
                if (is_array($ubicaciones)) {
                    $this->line('   Tipo: Array (' . count($ubicaciones) . ' elementos)');
                    foreach ($ubicaciones as $idx => $ubicacion) {
                        $this->line('   [' . $idx . '] Ubicación: ' . ($ubicacion['ubicacion'] ?? 'N/A'));
                        $this->line('       Descripción: ' . ($ubicacion['descripcion'] ?? 'N/A'));
                    }
                } else {
                    $this->line('   Tipo: String');
                    $this->line('   ' . $ubicaciones);
                }
            } else {
                $this->warn('   ⚠️  Sin datos (NULL)');
            }
            $this->line('   └─────────────────────────────────────────────────────────');
            $this->line('');

            // Descripción
            $this->info('   📝 DESCRIPCIÓN:');
            $this->line('   ┌─────────────────────────────────────────────────────────');
            
            if ($prendaReflectivo->descripcion) {
                $this->line('   ' . $prendaReflectivo->descripcion);
            } else {
                $this->warn('   ⚠️  Sin descripción (NULL)');
            }
            $this->line('   └─────────────────────────────────────────────────────────');
            $this->line('');
            $this->line('');
        }

        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('  ✅ VERIFICACIÓN COMPLETADA');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->line('');

        return 0;
    }
}
