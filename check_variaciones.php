<?php
/**
 * DEBUG SCRIPT - Verificar estado de variaciones en BD
 * Ejecutar: php check_variaciones.php
 */

// Conexión a BD
$conexion = new mysqli('localhost', 'root', '', 'mundoindustrial');

if ($conexion->connect_error) {
    die("❌ Error de conexión: " . $conexion->connect_error);
}

echo str_repeat("=", 80) . PHP_EOL;
echo "🔍 DEBUG - VERIFICANDO VARIACIONES EN BASE DE DATOS" . PHP_EOL;
echo str_repeat("=", 80) . PHP_EOL . PHP_EOL;

// Obtener la cotización más reciente
echo "📋 COTIZACIÓN MÁS RECIENTE:" . PHP_EOL;
$sql = "SELECT id, numero_cotizacion, estado, es_borrador, created_at FROM cotizacion ORDER BY id DESC LIMIT 1";
$resultado = $conexion->query($sql);
$cot = $resultado->fetch_assoc();

if (!$cot) {
    echo "❌ No hay cotizaciones\n";
    exit(1);
}

echo "  ID: {$cot['id']}\n";
echo "  Número: {$cot['numero_cotizacion']}\n";
echo "  Estado: {$cot['estado']}\n";
echo "  Es Borrador: {$cot['es_borrador']}\n";
echo "  Creada: {$cot['created_at']}\n\n";

$cotizacionId = $cot['id'];

// Verificar prendas
echo "📦 PRENDAS DE COTIZACIÓN #$cotizacionId:" . PHP_EOL;
$sql = "SELECT id, nombre_producto, cantidad FROM prenda_cot WHERE cotizacion_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $cotizacionId);
$stmt->execute();
$prendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "   Total de prendas: " . count($prendas) . "\n\n";

foreach ($prendas as $prenda) {
    echo "   🧥 PRENDA #{$prenda['id']}: {$prenda['nombre_producto']} (Cantidad: {$prenda['cantidad']})\n";
    
    // Variantes de esta prenda
    $sql = "SELECT id, genero_id, color, tela, tipo_manga_id, tiene_bolsillos, tiene_reflectivo FROM prenda_variantes_cot WHERE prenda_cot_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $prenda['id']);
    $stmt->execute();
    $variantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (count($variantes) === 0) {
        echo "      ⚠️  SIN VARIACIONES (es aquí donde dice 'Sin variaciones')\n";
    } else {
        echo "      ✅ Total de variantes: " . count($variantes) . "\n";
        foreach ($variantes as $var) {
            $generoNombre = $var['genero_id'] === null ? "NULL (Ambos)" : ($var['genero_id'] == 1 ? "Dama" : ($var['genero_id'] == 2 ? "Caballero" : $var['genero_id']));
            echo "         - ID: {$var['id']}, Género: {$generoNombre}, Color: {$var['color']}, Tela: {$var['tela']}\n";
            
            // Tallas de esta variante
            $sql = "SELECT id, talla FROM prenda_tallas_cot WHERE prenda_variante_cot_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $var['id']);
            $stmt->execute();
            $tallas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (count($tallas) > 0) {
                $tallasList = implode(", ", array_column($tallas, 'talla'));
                echo "            Tallas: $tallasList\n";
            }
        }
    }
    
    // Fotos
    $sql = "SELECT COUNT(*) as total FROM prenda_fotos WHERE prenda_cot_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $prenda['id']);
    $stmt->execute();
    $fotos = $stmt->get_result()->fetch_assoc();
    echo "      📸 Fotos: {$fotos['total']}\n\n";
}

echo str_repeat("=", 80) . PHP_EOL;
echo "✅ ANÁLISIS COMPLETADO" . PHP_EOL;
echo str_repeat("=", 80) . PHP_EOL;

echo "\n🔧 PRÓXIMOS PASOS:\n";
echo "1. Si dice 'SIN VARIACIONES' para alguna prenda:\n";
echo "   → Las variantes no se crearon en prenda_variantes_cot\n";
echo "   → Revisa si genero_id llega NULL desde el frontend\n\n";
echo "2. Si dice 'Total de variantes: 0':\n";
echo "   → El backend no está creando variantes\n";
echo "   → Revisa los logs de Laravel: tail -f storage/logs/laravel.log\n\n";
echo "3. Si ves variantes pero tallas es '0':\n";
echo "   → Las variantes existen pero no tienen tallas asignadas\n";
echo "   → Revisa si las tallas llegan al backend\n\n";
echo "4. Para hacer una PRUEBA NUEVA:\n";
echo "   → Ve a 'Crear Cotización'\n";
echo "   → Selecciona: Tipo Venta 'M', Cliente, Prenda\n";
echo "   → En TALLAS: Selecciona 'NÚMEROS (DAMA/CABALLERO)'\n";
echo "   → Luego selecciona Género: 'Ambos (Dama y Caballero)'\n";
echo "   → Selecciona tallas de AMBOS géneros\n";
echo "   → Haz CLIC en GUARDAR\n";
echo "   → Luego ejecuta este script de nuevo\n\n";

$conexion->close();
