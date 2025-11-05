<?php
/**
 * Script para verificar datos de octubre en registro_piso_polo
 * Identifica registros perdidos o problemas de inserción
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

    echo "=== VERIFICACIÓN DE DATOS DE OCTUBRE - REGISTRO_PISO_POLO ===\n\n";

    // 1. Total de registros en octubre
    echo "1. TOTAL DE REGISTROS EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total registros: {$result['total_registros']}\n";
    echo "   Suma cantidad: {$result['suma_cantidad']}\n\n";

    // 2. Registros por hora en octubre
    echo "2. REGISTROS POR HORA EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            hora,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
        GROUP BY hora
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Hora: {$row['hora']} | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";

    // 3. Buscar registros con HORA 8 específicamente
    echo "3. REGISTROS DE HORA 8 EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            fecha,
            modulo,
            orden_produccion,
            hora,
            cantidad,
            created_at
        FROM registro_piso_polo
        WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
          AND (hora LIKE '%HORA 8%' OR hora LIKE '%HORA 08%' OR hora = 'HORA 8' OR hora = 'HORA 08')
        ORDER BY fecha, created_at
    ");
    $totalHora8 = 0;
    $cantidadHora8 = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Orden: {$row['orden_produccion']} | Hora: {$row['hora']} | Cantidad: {$row['cantidad']}\n";
        $totalHora8++;
        $cantidadHora8 += $row['cantidad'];
    }
    echo "   TOTAL HORA 8: {$totalHora8} registros | Cantidad total: {$cantidadHora8}\n\n";

    // 4. Buscar duplicados
    echo "4. POSIBLES DUPLICADOS EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            fecha,
            modulo,
            orden_produccion,
            hora,
            COUNT(*) as veces_repetido,
            GROUP_CONCAT(id) as ids,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
        GROUP BY fecha, modulo, orden_produccion, hora
        HAVING COUNT(*) > 1
        ORDER BY fecha
    ");
    $duplicados = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Orden: {$row['orden_produccion']} | Hora: {$row['hora']}\n";
        echo "   Repetido: {$row['veces_repetido']} veces | IDs: {$row['ids']} | Suma cantidad: {$row['suma_cantidad']}\n\n";
        $duplicados++;
    }
    if ($duplicados == 0) {
        echo "   No se encontraron duplicados.\n\n";
    }

    // 5. Registros sin fecha
    echo "5. REGISTROS SIN FECHA EN OCTUBRE (por created_at):\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as registros_sin_fecha
        FROM registro_piso_polo
        WHERE fecha IS NULL
          AND created_at >= '2024-10-01' AND created_at < '2024-11-01'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total: {$result['registros_sin_fecha']}\n\n";

    // 6. Registros sin orden de producción
    echo "6. REGISTROS SIN ORDEN DE PRODUCCIÓN EN OCTUBRE (por created_at):\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as registros_sin_orden
        FROM registro_piso_polo
        WHERE orden_produccion IS NULL
          AND created_at >= '2024-10-01' AND created_at < '2024-11-01'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total: {$result['registros_sin_orden']}\n\n";

    // 7. Registros insertados por fecha de creación
    echo "7. REGISTROS INSERTADOS POR FECHA (created_at) EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as fecha_insercion,
            COUNT(*) as registros_insertados,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE created_at >= '2024-10-01' AND created_at < '2024-11-01'
        GROUP BY DATE(created_at)
        ORDER BY fecha_insercion
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Fecha inserción: {$row['fecha_insercion']} | Registros: {$row['registros_insertados']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";

    // 8. Verificar todas las variantes de HORA 8
    echo "8. TODAS LAS VARIANTES DE HORA EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT DISTINCT hora
        FROM registro_piso_polo
        WHERE fecha >= '2024-10-01' AND fecha <= '2024-10-31'
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   '{$row['hora']}'\n";
    }

    echo "\n=== FIN DE VERIFICACIÓN ===\n";

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
