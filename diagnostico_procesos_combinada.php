<?php
/**
 * DIAGNÓSTICO: Estructura de Procesos en Cotización COMBINADA
 * 
 * Este script analiza:
 * 1. Estructura de cotización COMBINADA (COT-00017)
 * 2. Dónde se guardan los procesos (Bordado, Estampado, DTF, etc.)
 * 3. Cómo se relacionan con la prenda
 * 4. Qué información se extrae en el controlador
 */

// Configuración
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;
use App\Models\PrendaCotizacion;

echo "\n========================================\n";
echo "🔍 DIAGNÓSTICO: COTIZACIÓN COMBINADA COT-00017\n";
echo "========================================\n\n";

// 1. OBTENER COTIZACIÓN COMBINADA
echo "📌 PASO 1: Cargando Cotización Combinada (COT-00017)\n";
echo str_repeat("─", 50) . "\n";

$cotizacion = Cotizacion::where('numero_cotizacion', 'COT-00017')
    ->with(['cliente', 'tipoCotizacion'])
    ->first();

if (!$cotizacion) {
    echo "❌ Cotización no encontrada\n";
    exit;
}

echo "✓ Cotización encontrada:\n";
echo "  ID: " . $cotizacion->id . "\n";
echo "  Número: " . $cotizacion->numero_cotizacion . "\n";
echo "  Cliente: " . ($cotizacion->cliente->nombre ?? 'N/A') . "\n";
echo "  Tipo: " . ($cotizacion->tipoCotizacion->nombre ?? 'N/A') . "\n";
echo "  Estado: " . $cotizacion->estado . "\n\n";

// 2. PRENDAS DE LA COTIZACIÓN
echo "📌 PASO 2: Prendas en la Cotización\n";
echo str_repeat("─", 50) . "\n";

$prendas = PrendaCotizacion::where('cotizacion_id', $cotizacion->id)->get();
echo "Total de prendas: " . count($prendas) . "\n\n";

foreach ($prendas as $idx => $prenda) {
    echo "🧵 PRENDA #" . ($idx + 1) . ": " . $prenda->nombre_producto . "\n";
    echo "   ID: " . $prenda->id . "\n";
    
    // 3. PROCESOS DE LA PRENDA - TÉCNICAS DE LOGO
    echo "\n   📍 TÉCNICAS DE LOGO (Bordado, Estampado, DTF, etc):\n";
    $logosTecnicas = DB::table('logo_cotizaciones_tecnicas')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "   Total técnicas: " . count($logosTecnicas) . "\n";
    
    if (count($logosTecnicas) > 0) {
        foreach ($logosTecnicas as $logoIdx => $logo) {
            echo "\n   🎨 Técnica #" . ($logoIdx + 1) . ":\n";
            echo "      - ID: " . $logo->id . "\n";
            echo "      - Tipo Logo ID: " . $logo->tipo_logo_id . "\n";
            
            // Obtener nombre de tipo logo
            $tipoLogo = DB::table('tipos_logos')->find($logo->tipo_logo_id);
            echo "      - Nombre: " . ($tipoLogo->nombre ?? 'N/A') . "\n";
            echo "      - Ubicaciones JSON: " . $logo->ubicaciones . "\n";
            echo "      - Observaciones: " . $logo->observaciones . "\n";
            
            // Fotos de la técnica
            $fotos = DB::table('logo_cotizaciones_tecnicas_fotos')
                ->where('logo_cotizacion_tecnica_prenda_id', $logo->id)
                ->get();
            echo "      - Fotos: " . count($fotos) . "\n";
            
            if (count($fotos) > 0) {
                foreach ($fotos as $fotoIdx => $foto) {
                    echo "        [" . ($fotoIdx + 1) . "] " . $foto->ruta_original . "\n";
                }
            }
        }
    } else {
        echo "   ⚠️ SIN TÉCNICAS DE LOGO REGISTRADAS\n";
    }
    
    // 4. REFLECTIVO
    echo "\n   📍 REFLECTIVO:\n";
    $reflectivos = DB::table('prenda_cot_reflectivo')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    
    echo "   Total reflectivos: " . count($reflectivos) . "\n";
    
    if (count($reflectivos) > 0) {
        foreach ($reflectivos as $refIdx => $reflectivo) {
            echo "\n   🔷 Reflectivo #" . ($refIdx + 1) . ":\n";
            echo "      - ID: " . $reflectivo->id . "\n";
            echo "      - Ubicaciones: " . $reflectivo->ubicaciones . "\n";
            
            // Reflectivo cotización
            $reflectivoCot = DB::table('reflectivo_cotizacion')
                ->where('prenda_cot_id', $prenda->id)
                ->first();
            
            if ($reflectivoCot) {
                echo "      - Observaciones: " . $reflectivoCot->observaciones_generales . "\n";
                
                $fotosReflectivo = DB::table('reflectivo_fotos_cotizacion')
                    ->where('reflectivo_cotizacion_id', $reflectivoCot->id)
                    ->get();
                echo "      - Fotos: " . count($fotosReflectivo) . "\n";
            }
        }
    } else {
        echo "   ⚠️ SIN REFLECTIVO REGISTRADO\n";
    }
    
    // 5. TELAS
    echo "\n   📍 TELAS:\n";
    $telas = DB::table('prenda_tela_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    echo "   Total telas: " . count($telas) . "\n";
    
    if (count($telas) > 0) {
        foreach ($telas as $telaIdx => $tela) {
            echo "   [" . ($telaIdx + 1) . "] Tela: " . $tela->tela_id . ", Color: " . $tela->color_id . "\n";
        }
    }
    
    echo "\n" . str_repeat("─", 50) . "\n";
}

// 6. COMPARAR CON LO QUE DEVUELVE EL CONTROLADOR
echo "\n📌 PASO 3: Verificar respuesta del Controlador\n";
echo str_repeat("─", 50) . "\n";

$cotizacionId = $cotizacion->id;
$prendaId = $prendas[0]->id ?? null;

if ($prendaId) {
    echo "Llamando endpoint:\n";
    echo "GET /asesores/pedidos-produccion/obtener-prenda-completa/" . $cotizacionId . "/" . $prendaId . "\n\n";
    
    // Simular lo que hace el controlador
    $cotizacionData = Cotizacion::with([
        'prendas' => function($query) use ($prendaId) {
            $query->where('id', $prendaId)
                ->with([
                    'telas' => function($q) {
                        $q->with([
                            'color:id,nombre,codigo',
                            'tela:id,nombre,referencia,descripcion'
                        ]);
                    },
                    'fotos:id,prenda_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                    'telaFotos:id,prenda_cot_id,prenda_tela_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                    'variantes' => function($q) {
                        $q->with([
                            'manga:id,nombre',
                            'broche:id,nombre',
                            'genero:id,nombre'
                        ]);
                    },
                    'tallas:id,prenda_cot_id,talla,cantidad',
                    'prendaCotReflectivo:id,prenda_cot_id,ubicaciones',
                    'logoCotizacionesTecnicas' => function($q) {
                        $q->with([
                            'tipoLogo:id,nombre',
                            'fotos:id,logo_cotizacion_tecnica_prenda_id,ruta_original,ruta_webp,ruta_miniatura,orden'
                        ]);
                    }
                ]);
        }
    ])->find($cotizacionId);
    
    $prendaData = $cotizacionData->prendas[0] ?? null;
    
    if ($prendaData) {
        echo "✓ Prenda cargada del controlador:\n";
        echo "  - logoCotizacionesTecnicas: " . count($prendaData->logoCotizacionesTecnicas) . "\n";
        echo "  - prendaCotReflectivo: " . count($prendaData->prendaCotReflectivo) . "\n";
        
        echo "\n🔍 RELACIONES DISPONIBLES:\n";
        if (count($prendaData->logoCotizacionesTecnicas) > 0) {
            echo "  ✓ logoCotizacionesTecnicas está disponible\n";
            foreach ($prendaData->logoCotizacionesTecnicas as $logo) {
                echo "    - Técnica: " . ($logo->tipoLogo->nombre ?? 'N/A') . "\n";
                echo "      Fotos: " . count($logo->fotos) . "\n";
            }
        } else {
            echo "  ❌ logoCotizacionesTecnicas VACÍO\n";
        }
        
        if (count($prendaData->prendaCotReflectivo) > 0) {
            echo "  ✓ prendaCotReflectivo está disponible\n";
        } else {
            echo "  ❌ prendaCotReflectivo VACÍO\n";
        }
    }
}

echo "\n========================================\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "========================================\n\n";
?>
