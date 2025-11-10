<?php
/**
 * Script para ejecutar el SQL generado y reemplazar todos los datos de PRODUCCION
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Buscar el archivo SQL más reciente
$sqlFiles = glob(__DIR__ . '/insert_produccion_desde_excel_*.sql');
if (empty($sqlFiles)) {
    die("❌ No se encontró ningún archivo SQL generado\n");
}

// Ordenar por fecha de modificación y tomar el más reciente
usort($sqlFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$sqlFile = $sqlFiles[0];
echo "=== EJECUTANDO SQL - REGISTRO_PISO_PRODUCCION ===\n\n";
echo "📄 Archivo SQL: " . basename($sqlFile) . "\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Leer el contenido del archivo SQL
    $sqlContent = file_get_contents($sqlFile);
    
    if (!$sqlContent) {
        die("❌ No se pudo leer el archivo SQL\n");
    }

    echo "⏳ Eliminando todos los registros actuales...\n";
    
    // Ejecutar TRUNCATE
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE registro_piso_produccion");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Registros eliminados\n\n";
    
    echo "⏳ Insertando nuevos registros desde Excel...\n";
    
    // Buscar el INSERT statement
    $insertStart = stripos($sqlContent, 'INSERT INTO registro_piso_produccion');
    if ($insertStart === false) {
        die("❌ No se encontró el INSERT statement en el archivo SQL\n");
    }
    
    // Extraer solo el INSERT (desde INSERT hasta el último ;)
    $insertStatement = substr($sqlContent, $insertStart);
    
    // Limpiar comentarios al inicio
    $lines = explode("\n", $insertStatement);
    $cleanLines = array_filter($lines, function($line) {
        return !str_starts_with(trim($line), '--');
    });
    $insertStatement = implode("\n", $cleanLines);
    
    echo "📝 Tamaño del INSERT: " . strlen($insertStatement) . " caracteres\n";
    echo "📝 Ejecutando INSERT...\n";
    
    try {
        $pdo->exec($insertStatement);
        $totalInsertados = $pdo->query("SELECT COUNT(*) FROM registro_piso_produccion")->fetchColumn();
        echo "✅ Registros insertados: $totalInsertados\n\n";
    } catch (PDOException $e) {
        echo "❌ Error al ejecutar INSERT: " . $e->getMessage() . "\n";
        echo "📝 Primeros 500 caracteres del INSERT:\n";
        echo substr($insertStatement, 0, 500) . "\n";
        exit(1);
    }
    
    // Verificar los datos insertados
    echo "=== VERIFICACIÓN DE DATOS ===\n\n";
    
    // Total por mes
    echo "REGISTROS POR MES:\n";
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_produccion
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY mes
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Mes: {$row['mes']} | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";
    
    // Total por hora
    echo "REGISTROS POR HORA:\n";
    $stmt = $pdo->query("
        SELECT 
            hora,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_produccion
        GROUP BY hora
        ORDER BY hora
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Hora: '{$row['hora']}' | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";
    
    // Total por módulo
    echo "REGISTROS POR MÓDULO:\n";
    $stmt = $pdo->query("
        SELECT 
            modulo,
            COUNT(*) as total_registros,
            SUM(cantidad) as suma_cantidad
        FROM registro_piso_produccion
        GROUP BY modulo
        ORDER BY modulo
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Módulo: {$row['modulo']} | Registros: {$row['total_registros']} | Cantidad: {$row['suma_cantidad']}\n";
    }
    echo "\n";
    
    echo "✅ PROCESO COMPLETADO EXITOSAMENTE\n";
    echo "📊 Total de registros en la base de datos: $totalInsertados\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
