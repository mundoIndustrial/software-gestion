<?php
/**
 * Script para verificar la columna META en octubre - PRODUCCIÓN
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

    echo "=== VERIFICACIÓN META - OCTUBRE - PRODUCCIÓN ===\n\n";

    // 1. Suma total de META en octubre
    echo "1. SUMA TOTAL DE META EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            SUM(meta) as suma_meta,
            COUNT(*) as total_registros
        FROM registro_piso_produccion
        WHERE fecha >= '2025-10-01' AND fecha <= '2025-10-31'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Suma META: {$result['suma_meta']}\n";
    echo "   Total registros: {$result['total_registros']}\n\n";

    // 2. Suma de META por módulo en octubre
    echo "2. SUMA DE META POR MÓDULO EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            modulo,
            SUM(meta) as suma_meta,
            COUNT(*) as total_registros
        FROM registro_piso_produccion
        WHERE fecha >= '2025-10-01' AND fecha <= '2025-10-31'
        GROUP BY modulo
        ORDER BY modulo
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Módulo: {$row['modulo']} | Suma META: {$row['suma_meta']} | Registros: {$row['total_registros']}\n";
    }
    echo "\n";

    // 3. Ver algunos valores de META para verificar decimales
    echo "3. PRIMEROS 20 REGISTROS DE OCTUBRE CON META:\n";
    $stmt = $pdo->query("
        SELECT 
            id,
            fecha,
            modulo,
            orden_produccion,
            hora,
            meta,
            cantidad
        FROM registro_piso_produccion
        WHERE fecha >= '2025-10-01' AND fecha <= '2025-10-31'
        ORDER BY fecha, modulo, hora
        LIMIT 20
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   ID: {$row['id']} | Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Hora: {$row['hora']} | META: {$row['meta']} | Cantidad: {$row['cantidad']}\n";
    }
    echo "\n";

    // 4. Verificar si hay valores de META con decimales
    echo "4. REGISTROS CON META DECIMAL (no enteros):\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_con_decimales
        FROM registro_piso_produccion
        WHERE fecha >= '2025-10-01' AND fecha <= '2025-10-31'
          AND meta != FLOOR(meta)
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total registros con META decimal: {$result['total_con_decimales']}\n\n";

    // 5. Ver algunos registros con META decimal
    echo "5. EJEMPLOS DE REGISTROS CON META DECIMAL:\n";
    $stmt = $pdo->query("
        SELECT 
            id,
            fecha,
            modulo,
            hora,
            meta
        FROM registro_piso_produccion
        WHERE fecha >= '2025-10-01' AND fecha <= '2025-10-31'
          AND meta != FLOOR(meta)
        LIMIT 10
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   ID: {$row['id']} | Fecha: {$row['fecha']} | Módulo: {$row['modulo']} | Hora: {$row['hora']} | META: {$row['meta']}\n";
    }

    echo "\n=== FIN DE VERIFICACIÓN ===\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
