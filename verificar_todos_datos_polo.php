<?php
/**
 * Script para verificar TODOS los datos en registro_piso_polo
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== VERIFICACIÓN COMPLETA - REGISTRO_PISO_POLO ===\n\n";

    // 1. Total de registros
    echo "1. TOTAL DE REGISTROS:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad,
            MIN(fecha) as fecha_minima,
            MAX(fecha) as fecha_maxima
        FROM registro_piso_polo
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total registros: {$result['total_registros']}\n";
    echo "   Suma cantidad: {$result['suma_cantidad']}\n";
    echo "   Fecha mínima: {$result['fecha_minima']}\n";
    echo "   Fecha máxima: {$result['fecha_maxima']}\n\n";

    // 2. Registros por mes
    echo "2. REGISTROS POR MES:\n";
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY mes
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Mes: {$row['mes']} | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";

    // 3. Registros por hora (todas las horas)
    echo "3. REGISTROS POR HORA (TODAS):\n";
    $stmt = $pdo->query("
        SELECT 
            hora,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        GROUP BY hora
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Hora: '{$row['hora']}' | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";

    // 4. Buscar registros de HORA 8 en cualquier formato
    echo "4. REGISTROS DE HORA 8 (TODAS LAS VARIANTES):\n";
    $stmt = $pdo->query("
        SELECT 
            fecha,
            modulo,
            orden_produccion,
            hora,
            cantidad,
            created_at
        FROM registro_piso_polo
        WHERE hora LIKE '%8%' OR hora LIKE '%08%'
        ORDER BY fecha, created_at
        LIMIT 50
    ");
    $totalHora8 = 0;
    $cantidadHora8 = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Orden: {$row['orden_produccion']} | Hora: '{$row['hora']}' | Cantidad: {$row['cantidad']}\n";
        $totalHora8++;
        $cantidadHora8 += $row['cantidad'];
    }
    echo "   TOTAL HORA 8 (primeros 50): {$totalHora8} registros | Cantidad total: {$cantidadHora8}\n\n";

    // 5. Últimos 20 registros insertados
    echo "5. ÚLTIMOS 20 REGISTROS INSERTADOS:\n";
    $stmt = $pdo->query("
        SELECT 
            id,
            fecha,
            modulo,
            orden_produccion,
            hora,
            cantidad,
            created_at
        FROM registro_piso_polo
        ORDER BY created_at DESC
        LIMIT 20
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   ID: {$row['id']} | Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Orden: {$row['orden_produccion']} | Hora: '{$row['hora']}' | Cantidad: {$row['cantidad']} | Creado: {$row['created_at']}\n";
    }
    echo "\n";

    // 6. Registros por fecha de creación (últimos 3 meses)
    echo "6. REGISTROS POR FECHA DE CREACIÓN (ÚLTIMOS 3 MESES):\n";
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as fecha_insercion,
            COUNT(*) as registros_insertados,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY DATE(created_at)
        ORDER BY fecha_insercion DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Fecha inserción: {$row['fecha_insercion']} | Registros: {$row['registros_insertados']} | Cantidad: {$row['suma_cantidad']}\n";
    }

    echo "\n=== FIN DE VERIFICACIÓN ===\n";

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
