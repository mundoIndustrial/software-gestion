<?php
$m = new mysqli('localhost', 'root', '29522628', 'mundo_bd');

echo "✅ RESPUESTA: ¿Se crea bien un pedido con rol asesor y se guarda todo?\n";
echo "=====================================================================\n\n";

echo "📋 FLUJO ACTUAL DE CREACIÓN DE PEDIDO:\n";
echo "======================================\n\n";

echo "1️⃣  El ASESOR accede a crear pedido desde cotización\n";
echo "   ↓\n";
echo "2️⃣  FRONTEND recolecta datos de prendas\n";
echo "   - Desde tabla 'prendas_cot' (relación cotizacion_id)\n";
echo "   - Incluye: nombre_producto, descripcion, cantidad\n";
echo "   ↓\n";
echo "3️⃣  CONTROLLER (PedidoProduccionController)\n";
echo "   - Recibe prendas del request\n";
echo "   - PROBLEMA: Intenta extraer descripcion y forma_de_pago de 'cotizaciones'\n";
echo "   - Pero esos campos NO existen en cotizaciones\n";
echo "   ↓\n";
echo "4️⃣  JOB (CrearPedidoProduccionJob)\n";
echo "   - Genera numero_pedido (secuencial)\n";
echo "   - Crea pedido en 'pedidos_produccion'\n";
echo "   - Guarda cliente, asesor_id\n";
echo "   ↓\n";
echo "5️⃣  SERVICE (PedidoPrendaService)\n";
echo "   - Guarda prendas en 'prendas_pedido'\n";
echo "   - Usa DescripcionPrendaLegacyFormatter\n";
echo "   - Extrae color_id, tela_id, tipo_manga_id\n\n";

echo "❌ PROBLEMA IDENTIFICADO:\n";
echo "==========================\n\n";
echo "El Controller intenta extraer:\n";
echo "  - cotizacion.descripcion     ← NO EXISTE\n";
echo "  - cotizacion.forma_de_pago   ← NO EXISTE\n\n";

echo "Estos datos están en DIFERENTES TABLAS:\n";
echo "  - Prendas: prendas_cot\n";
echo "  - Variantes: prenda_variantes_cot\n";
echo "  - Telas: prenda_telas_cot\n";
echo "  - Tallas: prenda_tallas_cot\n";
echo "  - Fotos: prenda_fotos_cot\n\n";

echo "✅ SOLUCIÓN:\n";
echo "=============\n\n";
echo "El Controller debe:\n";
echo "1. Extraer prendas desde 'prendas_cot' (con cotizacion_id)\n";
echo "2. Para cada prenda:\n";
echo "   - Obtener variantes de 'prenda_variantes_cot'\n";
echo "   - Obtener telas de 'prenda_telas_cot'\n";
echo "   - Obtener tallas de 'prenda_tallas_cot'\n";
echo "   - Obtener fotos de 'prenda_fotos_cot'\n";
echo "3. Pasar todos estos datos normalizados al DTO\n";
echo "4. El Service guardará todo correctamente\n\n";

echo "📊 VERIFICACIÓN DE DATOS ACTUALES:\n";
echo "====================================\n\n";

// Ver última cotización con sus prendas
$r = $m->query("
    SELECT c.id, c.numero_cotizacion
    FROM cotizaciones c
    WHERE c.id = (SELECT MAX(id) FROM cotizaciones)
");

if ($r->num_rows > 0) {
    $cot = $r->fetch_assoc();
    echo "Última Cotización: #{$cot['numero_cotizacion']}\n\n";
    
    // Ver prendas
    $r = $m->query("
        SELECT COUNT(*) as total
        FROM prendas_cot
        WHERE cotizacion_id = {$cot['id']}
    ");
    $count = $r->fetch_assoc();
    echo "✅ Prendas asociadas: {$count['total']}\n";
    
    // Ver si hay variantes
    $r = $m->query("
        SELECT COUNT(*) as total
        FROM prenda_variantes_cot pv
        JOIN prendas_cot p ON p.id = pv.prenda_cot_id
        WHERE p.cotizacion_id = {$cot['id']}
    ");
    $count = $r->fetch_assoc();
    echo "✅ Variantes: {$count['total']}\n";
    
    // Ver si hay telas
    $r = $m->query("
        SELECT COUNT(*) as total
        FROM prenda_telas_cot pt
        JOIN prendas_cot p ON p.id = pt.prenda_id
        WHERE p.cotizacion_id = {$cot['id']}
    ");
    $count = $r->fetch_assoc();
    echo "✅ Telas: {$count['total']}\n";
    
    // Ver si hay fotos
    $r = $m->query("
        SELECT COUNT(*) as total
        FROM prenda_fotos_cot pf
        JOIN prendas_cot p ON p.id = pf.prenda_id
        WHERE p.cotizacion_id = {$cot['id']}
    ");
    $count = $r->fetch_assoc();
    echo "✅ Fotos: {$count['total']}\n";
}

echo "\n\n🎯 RESPUESTA FINAL:\n";
echo "===================\n";
echo "❌ ACTUALMENTE NO se guarda bien porque el Controller tiene un error\n";
echo "✅ PERO la estructura de BD está correcta (normalizada)\n";
echo "✅ SOLO falta corregir el Controller para extraer datos de las tablas correctas\n";
