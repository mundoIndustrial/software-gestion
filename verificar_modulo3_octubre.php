<?php
/**
 * Script para verificar MÓDULO 3 en OCTUBRE - HORA 08
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

    echo "=== VERIFICACIÓN MÓDULO 3 - OCTUBRE - HORA 08 ===\n\n";

    // 1. Total MÓDULO 3 en octubre
    echo "1. TOTAL MÓDULO 3 EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE modulo = 'MODULO 3'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total registros: {$result['total_registros']}\n";
    echo "   Suma cantidad: {$result['suma_cantidad']}\n\n";

    // 2. MÓDULO 3 - HORA 08 en octubre
    echo "2. MÓDULO 3 - HORA 08 EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE modulo = 'MODULO 3'
          AND hora = 'HORA 08'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total registros: {$result['total_registros']}\n";
    echo "   Suma cantidad: {$result['suma_cantidad']}\n\n";

    // 3. Detalle de registros MÓDULO 3 - HORA 08 en octubre
    echo "3. DETALLE MÓDULO 3 - HORA 08 EN OCTUBRE:\n";
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
        WHERE modulo = 'MODULO 3'
          AND hora = 'HORA 08'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
        ORDER BY fecha
    ");
    $total = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   ID: {$row['id']} | Fecha: {$row['fecha']} | Orden: {$row['orden_produccion']} | Cantidad: {$row['cantidad']}\n";
        $total += $row['cantidad'];
    }
    echo "   TOTAL CANTIDAD: $total\n\n";

    // 4. Todos los registros de MÓDULO 3 en octubre por hora
    echo "4. MÓDULO 3 EN OCTUBRE POR HORA:\n";
    $stmt = $pdo->query("
        SELECT 
            hora,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE modulo = 'MODULO 3'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
        GROUP BY hora
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Hora: '{$row['hora']}' | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";

    // 5. Verificar si hay registros con variantes de HORA 08
    echo "5. VARIANTES DE HORA 08 EN MÓDULO 3 - OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT DISTINCT hora
        FROM registro_piso_polo
        WHERE modulo = 'MODULO 3'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
          AND (hora LIKE '%08%' OR hora LIKE '%8%')
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   '{$row['hora']}'\n";
    }
    echo "\n";

    // 6. Total de todos los módulos en HORA 08 - octubre
    echo "6. TODOS LOS MÓDULOS - HORA 08 EN OCTUBRE:\n";
    $stmt = $pdo->query("
        SELECT 
            modulo,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_polo
        WHERE hora = 'HORA 08'
          AND fecha >= '2025-10-01' AND fecha <= '2025-10-31'
        GROUP BY modulo
        ORDER BY modulo
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Módulo: {$row['modulo']} | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }

    echo "\n=== FIN DE VERIFICACIÓN ===\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
