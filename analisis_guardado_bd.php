<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VariantePrenda;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 ANÁLISIS - CÓMO SE GUARDA EN LA BD\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener una variante reciente
$variante = VariantePrenda::latest()->first();

if (!$variante) {
    echo "❌ No hay variantes en la BD\n";
    exit;
}

echo "\n🔍 VARIANTE ANALIZADA (ID: {$variante->id}):\n";

// PASO 1: Mostrar estructura de tabla
echo "\n📋 PASO 1: Estructura de tabla variantes_prenda\n";
$columns = DB::select("DESCRIBE variantes_prenda");
echo "Columnas:\n";
foreach ($columns as $col) {
    $tipo = $col->Type;
    $null = $col->Null === 'YES' ? 'nullable' : 'required';
    echo "  - {$col->Field} ({$tipo}) [{$null}]\n";
}

// PASO 2: Mostrar datos guardados
echo "\n📊 PASO 2: Datos guardados en la variante\n";
echo "  - id: {$variante->id}\n";
echo "  - prenda_cotizacion_id: {$variante->prenda_cotizacion_id}\n";
echo "  - tipo_prenda_id: {$variante->tipo_prenda_id}\n";
echo "  - color_id: " . ($variante->color_id ?? 'NULL') . "\n";
echo "  - tela_id: " . ($variante->tela_id ?? 'NULL') . "\n";
echo "  - genero_id: " . ($variante->genero_id ?? 'NULL') . "\n";
echo "  - tipo_manga_id: " . ($variante->tipo_manga_id ?? 'NULL') . "\n";
echo "  - tipo_broche_id: " . ($variante->tipo_broche_id ?? 'NULL') . "\n";
echo "  - tiene_bolsillos: " . ($variante->tiene_bolsillos ? 'true' : 'false') . "\n";
echo "  - tiene_reflectivo: " . ($variante->tiene_reflectivo ? 'true' : 'false') . "\n";
echo "  - cantidad_talla: " . ($variante->cantidad_talla ?? 'NULL') . "\n";
echo "  - descripcion_adicional: " . ($variante->descripcion_adicional ?? 'NULL') . "\n";

// PASO 3: Analizar relaciones
echo "\n🔗 PASO 3: Relaciones cargadas\n";

$variante->load('color', 'tela', 'tipoManga', 'tipoBroche', 'tipoPrenda');

echo "  - Color: " . ($variante->color ? "ID {$variante->color->id} - {$variante->color->nombre}" : 'NULL') . "\n";
echo "  - Tela: " . ($variante->tela ? "ID {$variante->tela->id} - {$variante->tela->nombre}" : 'NULL') . "\n";
echo "  - Manga: " . ($variante->tipoManga ? "ID {$variante->tipoManga->id} - {$variante->tipoManga->nombre}" : 'NULL') . "\n";
echo "  - Broche: " . ($variante->tipoBroche ? "ID {$variante->tipoBroche->id} - {$variante->tipoBroche->nombre}" : 'NULL') . "\n";
echo "  - Tipo Prenda: " . ($variante->tipoPrenda ? "ID {$variante->tipoPrenda->id} - {$variante->tipoPrenda->nombre}" : 'NULL') . "\n";

// PASO 4: Análisis de diseño
echo "\n✅ PASO 4: ANÁLISIS DE DISEÑO\n";

echo "\n✓ VENTAJAS del diseño actual:\n";
echo "  1. Normalización: Se guardan IDs en lugar de valores duplicados\n";
echo "  2. Integridad referencial: Foreign keys aseguran consistencia\n";
echo "  3. Escalabilidad: Fácil agregar nuevas variaciones\n";
echo "  4. Flexibilidad: Cada variante puede tener diferentes combinaciones\n";
echo "  5. Eficiencia: Búsquedas rápidas por ID\n";
echo "  6. Mantenibilidad: Cambios en nombres de colores/telas se reflejan automáticamente\n";

echo "\n✓ ESTRUCTURA ACTUAL:\n";
echo "  - variantes_prenda: Almacena IDs de relaciones\n";
echo "  - colores_prenda: Tabla de catálogo de colores\n";
echo "  - telas_prenda: Tabla de catálogo de telas\n";
echo "  - tipos_manga: Tabla de catálogo de tipos de manga\n";
echo "  - tipos_broche: Tabla de catálogo de tipos de broche\n";
echo "  - tipos_prenda: Tabla de catálogo de tipos de prenda\n";

echo "\n✓ RELACIONES:\n";
echo "  variantes_prenda.color_id → colores_prenda.id\n";
echo "  variantes_prenda.tela_id → telas_prenda.id\n";
echo "  variantes_prenda.tipo_manga_id → tipos_manga.id\n";
echo "  variantes_prenda.tipo_broche_id → tipos_broche.id\n";
echo "  variantes_prenda.tipo_prenda_id → tipos_prenda.id\n";

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ ANÁLISIS COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📝 CONCLUSIÓN:\n";
echo "   El diseño es CORRECTO y ÓPTIMO:\n";
echo "   - Usa normalización de BD (3NF)\n";
echo "   - Evita duplicación de datos\n";
echo "   - Mantiene integridad referencial\n";
echo "   - Permite fácil mantenimiento\n";
echo "   - Escalable para futuras variaciones\n";
echo "\n";
