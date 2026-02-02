<?php
/**
 * Script para explorar DÓNDE se guardan exactamente las tallas y cantidades
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║ Explorando TALLAS Y CANTIDADES en Base de Datos              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// ========================================
// 1. Explorar prenda_cot_reflectivo (JSON: variaciones, ubicaciones)
// ========================================
echo "📍 1. TABLA: prenda_cot_reflectivo\n";
echo "─────────────────────────────────────\n";

$refData = DB::table('prenda_cot_reflectivo')->get();
if ($refData->count() > 0) {
    echo "Total registros: " . $refData->count() . "\n\n";
    foreach ($refData as $record) {
        echo "Registro ID: {$record->id}\n";
        
        if ($record->variaciones) {
            echo "  ✓ variaciones (JSON):\n";
            $variaciones = json_decode($record->variaciones, true);
            echo "    " . json_encode($variaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->ubicaciones) {
            echo "  ✓ ubicaciones (JSON):\n";
            $ubicaciones = json_decode($record->ubicaciones, true);
            echo "    " . json_encode($ubicaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->color_tela_ref) {
            echo "  ✓ color_tela_ref (JSON):\n";
            $colorTela = json_decode($record->color_tela_ref, true);
            echo "    " . json_encode($colorTela, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->descripcion) {
            echo "  ✓ descripcion: {$record->descripcion}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ Sin registros en prenda_cot_reflectivo\n\n";
}

// ========================================
// 2. Explorar reflectivo_cotizacion
// ========================================
echo "📍 2. TABLA: reflectivo_cotizacion\n";
echo "─────────────────────────────────────\n";

$reflData = DB::table('reflectivo_cotizacion')->get();
if ($reflData->count() > 0) {
    echo "Total registros: " . $reflData->count() . "\n\n";
    foreach ($reflData as $record) {
        echo "Registro ID: {$record->id}\n";
        
        if ($record->observaciones_generales) {
            echo "  ✓ observaciones_generales (JSON):\n";
            $obs = json_decode($record->observaciones_generales, true);
            echo "    " . json_encode($obs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->imagenes) {
            echo "  ✓ imagenes (JSON):\n";
            $imgs = json_decode($record->imagenes, true);
            echo "    " . json_encode($imgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->descripcion) {
            echo "  ✓ descripcion: {$record->descripcion}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ Sin registros en reflectivo_cotizacion\n\n";
}

// ========================================
// 3. Explorar prendas normales (PrendaCot) - pueden tener tallas y cantidades
// ========================================
echo "📍 3. TABLA: prenda_cot (Prendas Normales)\n";
echo "─────────────────────────────────────\n";

$prendasData = DB::table('prenda_cot')
    ->select('id', 'nombre', 'especificaciones', 'cantidad_estimada')
    ->limit(2)
    ->get();

if ($prendasData->count() > 0) {
    echo "Total registros: " . $prendasData->count() . "\n\n";
    foreach ($prendasData as $record) {
        echo "Prenda ID: {$record->id} - {$record->nombre}\n";
        
        if ($record->especificaciones) {
            echo "  ✓ especificaciones (JSON):\n";
            $esp = json_decode($record->especificaciones, true);
            echo "    " . json_encode($esp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        if ($record->cantidad_estimada) {
            echo "  ✓ cantidad_estimada: {$record->cantidad_estimada}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ Sin registros en prenda_cot\n\n";
}

// ========================================
// 4. Explorar tallas_costos_cot (si existe)
// ========================================
echo "📍 4. TABLA: tallas_costos_cot\n";
echo "─────────────────────────────────────\n";

if (Schema::hasTable('tallas_costos_cot')) {
    $tallasData = DB::table('tallas_costos_cot')->limit(2)->get();
    if ($tallasData->count() > 0) {
        echo "Total registros: " . $tallasData->count() . "\n\n";
        foreach ($tallasData as $record) {
            echo "Registro: " . json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "✓ Tabla existe pero sin registros\n\n";
    }
} else {
    echo "❌ Tabla no existe\n\n";
}

// ========================================
// 5. Consulta directa: Buscar en todas las prendas de una cotización
// ========================================
echo "📍 5. RELACIÓN COMPLETA: Cotización → Prendas → Tallas/Cantidades\n";
echo "─────────────────────────────────────\n";

$cotizaciones = DB::table('cotizaciones')
    ->where('tipo_cotizacion_id', '!=', null)
    ->with('tipoCotizacion')
    ->limit(1)
    ->get();

if ($cotizaciones->count() > 0) {
    foreach ($cotizaciones as $cot) {
        echo "Cotización ID: {$cot->id}\n";
        
        // Prendas normales
        $prendas = DB::table('prenda_cot')
            ->where('cotizacion_id', $cot->id)
            ->get();
        
        echo "  Prendas normales: " . $prendas->count() . "\n";
        foreach ($prendas as $prenda) {
            echo "    - {$prenda->nombre}\n";
            
            // Tallas de la prenda
            $tallas = DB::table('prenda_cot_talla')
                ->where('prenda_cot_id', $prenda->id)
                ->get();
            
            foreach ($tallas as $talla) {
                echo "      • Talla: {$talla->talla}, Cantidad: {$talla->cantidad}\n";
            }
        }
        
        // Reflectivos
        $reflectivos = DB::table('prenda_cot_reflectivo')
            ->where('cotizacion_id', $cot->id)
            ->get();
        
        echo "\n  Reflectivos: " . $reflectivos->count() . "\n";
        foreach ($reflectivos as $refl) {
            echo "    - Prenda ID: {$refl->prenda_cot_id}\n";
            if ($refl->variaciones) {
                $var = json_decode($refl->variaciones, true);
                echo "      Variaciones: " . json_encode($var) . "\n";
            }
        }
    }
} else {
    echo "❌ Sin cotizaciones para analizar\n\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║ CONCLUSIÓN: Estructura de Tallas y Cantidades                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "Según la BD, las tallas y cantidades se guardan en:\n\n";

echo "PRENDAS NORMALES (tipo_cotizacion: Prenda/Moda/Personal):\n";
echo "  • Tabla: prenda_cot_talla\n";
echo "  • Campos: prenda_cot_id, talla, cantidad\n\n";

echo "REFLECTIVO (tipo_cotizacion: RF):\n";
echo "  • Tabla: prenda_cot_reflectivo\n";
echo "  • Campo JSON: variaciones (contiene tallas/cantidades)\n";
echo "  • Campo JSON: ubicaciones\n\n";

echo "LOGO (tipo_cotizacion: L):\n";
echo "  • Tabla: logo_cotizaciones (solo guarda observaciones generales)\n";
echo "  • ⚠️ NO HAY TABLA PARA GUARDAR TALLAS/CANTIDADES DE LOGO\n";
echo "  • ⚠️ Tabla logo_cotizacion_tecnica_prenda fue ELIMINADA\n\n";

echo "✅ Script completado\n\n";
