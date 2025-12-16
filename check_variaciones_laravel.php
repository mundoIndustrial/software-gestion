<?php
// check_variaciones_laravel.php
// Usa artisan para ejecutar mediante Laravel

require_once 'bootstrap/app.php';

$kernel = app(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo str_repeat("=", 80) . "\n";
echo "🔍 DEBUG - VERIFICANDO VARIACIONES EN BASE DE DATOS\n";
echo str_repeat("=", 80) . "\n\n";

// Obtener cotización más reciente
$cot = \App\Models\Cotizacion::latest()->first();

if (!$cot) {
    echo "❌ No hay cotizaciones\n";
    exit(1);
}

echo "📋 COTIZACIÓN MÁS RECIENTE:\n";
echo "  ID: {$cot->id}\n";
echo "  Número: {$cot->numero_cotizacion}\n";
echo "  Estado: {$cot->estado}\n";
echo "  Es Borrador: " . ($cot->es_borrador ? 'Sí' : 'No') . "\n";
echo "  Creada: {$cot->created_at}\n\n";

// Prendas
echo "📦 PRENDAS:\n";
$prendas = $cot->prendas;
echo "   Total: " . count($prendas) . "\n\n";

foreach ($prendas as $idx => $prenda) {
    echo "   🧥 PRENDA #{$prenda->id}: {$prenda->nombre_producto}\n";
    
    // Variantes
    $variantes = $prenda->variantes()->get();
    
    if (count($variantes) === 0) {
        echo "      ⚠️  SIN VARIACIONES ← ¡AQUÍ ESTÁ EL PROBLEMA!\n";
    } else {
        echo "      ✅ Total de variantes: " . count($variantes) . "\n";
        foreach ($variantes as $var) {
            $generoNombre = $var->genero_id === null ? "NULL (Ambos)" : ($var->genero_id == 1 ? "Dama" : ($var->genero_id == 2 ? "Caballero" : $var->genero_id));
            echo "         - ID: {$var->id}, Género: {$generoNombre}, Color: {$var->color}, Tela: {$var->tela}\n";
            
            // Tallas
            $tallas = $var->tallas()->pluck('talla')->toArray();
            if (count($tallas) > 0) {
                $tallasList = implode(", ", $tallas);
                echo "            📏 Tallas: $tallasList\n";
            } else {
                echo "            📏 Tallas: (ninguna)\n";
            }
        }
    }
    
    // Fotos
    $fotos = $prenda->fotos()->count();
    echo "      📸 Fotos: $fotos\n";
    
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "✅ ANÁLISIS COMPLETADO\n";
echo str_repeat("=", 80) . "\n\n";

echo "🔧 INTERPRETACIÓN:\n\n";
echo "Si ves 'SIN VARIACIONES':\n";
echo "  → Las variantes NO se guardaron en prenda_variantes_cot\n";
echo "  → El backend recibió genero_id como NULL o vacío\n";
echo "  → O el selector de género no funcionó correctamente\n\n";

echo "Si ves variantes pero son pocas:\n";
echo "  → Recibió genero_id = 1 (Dama) o 2 (Caballero)\n";
echo "  → Solo generó una variante para un género\n\n";

echo "Si ves 1 variante con genero_id = NULL:\n";
echo "  → ¡ESTO SIGNIFICA QUE FUNCIONÓ CORRECTAMENTE!\n";
echo "  → genero_id = NULL es lo que queremos para \"ambos\"\n\n";

echo "=== PRÓXIMA PRUEBA ===\n";
echo "1. Ve a 'Crear Cotización'\n";
echo "2. Selecciona Tipo: M, Cliente: cualquiera\n";
echo "3. Agrégale una Prenda\n";
echo "4. En TALLAS: selecciona 'NÚMEROS (DAMA/CABALLERO)'\n";
echo "5. Luego selecciona Género: 'Ambos (Dama y Caballero)'\n";
echo "6. Selecciona 2-3 tallas de DAMA y 2-3 de CABALLERO\n";
echo "7. Haz CLIC en GUARDAR\n";
echo "8. Ejecuta este script de nuevo:\n";
echo "   php check_variaciones_laravel.php\n\n";
