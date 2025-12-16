<?php
// Verificar que todos los campos se guardan correctamente
try {
    $mysqli = new mysqli('localhost', 'root', '29522628', 'mundo_bd');
    
    echo "VERIFICACIÓN COMPLETA DE CAMPOS EN PRENDAS_PEDIDO\n";
    echo "=================================================\n\n";
    
    // Obtener última prenda
    $result = $mysqli->query("
        SELECT 
            id, numero_pedido, nombre_prenda, cantidad, descripcion,
            color_id, tela_id, tipo_manga_id, tipo_broche_id,
            tiene_bolsillos, tiene_reflectivo, descripcion_variaciones, cantidad_talla,
            created_at
        FROM prendas_pedido 
        ORDER BY id DESC 
        LIMIT 1
    ");
    
    if ($result->num_rows == 0) {
        echo "No hay prendas en la BD";
        exit;
    }
    
    $prenda = $result->fetch_assoc();
    
    echo "DATOS DE LA ÚLTIMA PRENDA:\n";
    echo "=========================\n\n";
    
    // Campos básicos
    echo "CAMPOS BÁSICOS:\n";
    echo "  ID: " . $prenda['id'] . "\n";
    echo "  Número Pedido: " . $prenda['numero_pedido'] . "\n";
    echo "  Nombre: " . $prenda['nombre_prenda'] . "\n";
    echo "  Cantidad: " . ($prenda['cantidad'] ?? 'NULL ⚠️') . "\n";
    echo "  Descripción: " . (strlen($prenda['descripcion'] ?? '') > 0 ? substr($prenda['descripcion'], 0, 60) . '...' : 'NULL ⚠️') . "\n";
    echo "  Descripción Variaciones: " . (strlen($prenda['descripcion_variaciones'] ?? '') > 0 ? substr($prenda['descripcion_variaciones'], 0, 60) . '...' : 'NULL ⚠️') . "\n";
    echo "  Cantidad Talla (JSON): " . (strlen($prenda['cantidad_talla'] ?? '') > 0 ? substr($prenda['cantidad_talla'], 0, 60) . '...' : 'NULL ⚠️') . "\n\n";
    
    // IDs de referencias
    echo "CAMPOS DE REFERENCIAS (Foreign Keys):\n";
    echo "  Color ID: " . (isset($prenda['color_id']) && $prenda['color_id'] ? $prenda['color_id'] : 'NULL ⚠️') . "\n";
    echo "  Tela ID: " . (isset($prenda['tela_id']) && $prenda['tela_id'] ? $prenda['tela_id'] : 'NULL ⚠️') . "\n";
    echo "  Tipo Manga ID: " . (isset($prenda['tipo_manga_id']) && $prenda['tipo_manga_id'] ? $prenda['tipo_manga_id'] : 'NULL ⚠️') . "\n";
    echo "  Tipo Broche ID: " . (isset($prenda['tipo_broche_id']) && $prenda['tipo_broche_id'] ? $prenda['tipo_broche_id'] : 'NULL ⚠️') . "\n\n";
    
    // Booleanos
    echo "CAMPOS BOOLEANOS:\n";
    echo "  Tiene Bolsillos: " . ($prenda['tiene_bolsillos'] ? 'Sí (1)' : 'No (0)') . "\n";
    echo "  Tiene Reflectivo: " . ($prenda['tiene_reflectivo'] ? 'Sí (1)' : 'No (0)') . "\n\n";
    
    // Timestamps
    echo "TIMESTAMPS:\n";
    echo "  Creado: " . $prenda['created_at'] . "\n\n";
    
    // Resumen
    echo "RESUMEN DE VALIDACIÓN:\n";
    echo "=====================\n";
    $campos_ok = 0;
    $campos_total = 14;
    
    if ($prenda['numero_pedido']) $campos_ok++;
    if ($prenda['nombre_prenda']) $campos_ok++;
    if ($prenda['descripcion']) $campos_ok++;
    if ($prenda['cantidad']) $campos_ok++;
    if ($prenda['cantidad_talla']) $campos_ok++;
    if ($prenda['descripcion_variaciones']) $campos_ok++;
    if (isset($prenda['color_id']) && $prenda['color_id']) $campos_ok++;
    if (isset($prenda['tela_id']) && $prenda['tela_id']) $campos_ok++;
    if (isset($prenda['tipo_manga_id']) && $prenda['tipo_manga_id']) $campos_ok++;
    if (isset($prenda['tipo_broche_id']) && $prenda['tipo_broche_id']) $campos_ok++;
    if ($prenda['tiene_bolsillos'] !== null) $campos_ok++;
    if ($prenda['tiene_reflectivo'] !== null) $campos_ok++;
    if ($prenda['created_at']) $campos_ok++;
    if ($prenda['id']) $campos_ok++;
    
    echo "Campos guardados: " . $campos_ok . "/" . $campos_total . "\n";
    
    if ($campos_ok >= 10) {
        echo "✅ La mayoría de campos se están guardando\n";
    } else {
        echo "⚠️ Faltan campos por guardar\n";
    }
    
    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
