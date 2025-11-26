<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=mundo_bd', 'root', '123456');
    
    // Obtener última cotización
    $stmt = $pdo->prepare('
        SELECT c.id, c.numero_cotizacion, c.cliente, COUNT(vp.id) as variantes
        FROM cotizaciones c
        LEFT JOIN prendas_cotizaciones pc ON c.id = pc.cotizacion_id
        LEFT JOIN variantes_prenda vp ON pc.id = vp.prenda_cotizacion_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 1
    ');
    $stmt->execute();
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== ÚLTIMA COTIZACIÓN ===\n";
    echo "ID: {$cotizacion['id']}\n";
    echo "Número: {$cotizacion['numero_cotizacion']}\n";
    echo "Cliente: {$cotizacion['cliente']}\n";
    echo "Variantes: {$cotizacion['variantes']}\n\n";
    
    // Obtener detalles de variantes
    $stmt = $pdo->prepare('
        SELECT 
            vp.id,
            vp.tipo_manga_id,
            tm.nombre as manga_nombre,
            vp.tela_id,
            tp.nombre as tela_nombre,
            tp.referencia,
            vp.color_id,
            cp.nombre as color_nombre,
            vp.tiene_bolsillos,
            vp.tiene_reflectivo,
            vp.descripcion_adicional
        FROM variantes_prenda vp
        LEFT JOIN tipos_manga tm ON vp.tipo_manga_id = tm.id
        LEFT JOIN telas_prenda tp ON vp.tela_id = tp.id
        LEFT JOIN colores_prenda cp ON vp.color_id = cp.id
        WHERE vp.prenda_cotizacion_id IN (
            SELECT id FROM prendas_cotizaciones WHERE cotizacion_id = ?
        )
    ');
    $stmt->execute([$cotizacion['id']]);
    $variantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== VARIANTES DETALLE ===\n";
    foreach ($variantes as $i => $v) {
        echo "\nVariante " . ($i+1) . ":\n";
        echo "  Manga: {$v['manga_nombre']} (ID: {$v['tipo_manga_id']})\n";
        echo "  Tela: {$v['tela_nombre']} (ID: {$v['tela_id']})\n";
        echo "  Referencia: {$v['referencia']}\n";
        echo "  Color: {$v['color_nombre']} (ID: {$v['color_id']})\n";
        echo "  Bolsillos: " . ($v['tiene_bolsillos'] ? 'Sí' : 'No') . "\n";
        echo "  Reflectivo: " . ($v['tiene_reflectivo'] ? 'Sí' : 'No') . "\n";
        echo "  Descripción: {$v['descripcion_adicional']}\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
