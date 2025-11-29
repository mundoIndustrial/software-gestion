<?php

// Script simple para verificar las columnas sin Laravel

// Cargar configuración de .env
$dotenv = parse_ini_file(__DIR__ . '/.env');

$host = $dotenv['DB_HOST'] ?? 'localhost';
$database = $dotenv['DB_DATABASE'] ?? '';
$user = $dotenv['DB_USERNAME'] ?? 'root';
$password = $dotenv['DB_PASSWORD'] ?? '';

try {
    // Conectar a la BD
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Obtener las columnas de la tabla
    $stmt = $pdo->query("DESCRIBE materiales_orden_insumos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ VERIFICACIÓN DE MIGRACIÓN - materiales_orden_insumos       ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $nuevasColumnas = ['fecha_orden', 'fecha_pago', 'fecha_despacho', 'observaciones', 'dias_demora'];
    $columnasEncontradas = [];

    foreach ($columns as $column) {
        $columnName = $column['Field'];
        
        if (in_array($columnName, $nuevasColumnas)) {
            $columnasEncontradas[] = $columnName;
            echo "✅ " . str_pad($columnName, 25) . " | Tipo: " . str_pad($column['Type'], 15) . " | Nulo: " . ($column['Null'] === 'YES' ? 'SÍ' : 'NO') . "\n";
        }
    }

    echo "\n" . str_repeat("─", 66) . "\n";
    echo "📊 RESUMEN:\n";
    echo "   Total de nuevas columnas encontradas: " . count($columnasEncontradas) . " / " . count($nuevasColumnas) . "\n";

    if (count($columnasEncontradas) === count($nuevasColumnas)) {
        echo "\n✅ ¡MIGRACIÓN EJECUTADA CORRECTAMENTE!\n";
        echo "   Todas las columnas se crearon exitosamente.\n\n";
        echo "📋 COLUMNAS CREADAS:\n";
        foreach ($columnasEncontradas as $col) {
            echo "   ✅ " . $col . "\n";
        }
    } else {
        echo "\n⚠️  Columnas faltantes:\n";
        foreach ($nuevasColumnas as $col) {
            if (!in_array($col, $columnasEncontradas)) {
                echo "   ❌ " . $col . "\n";
            }
        }
    }

    echo "\n" . str_repeat("─", 66) . "\n";
    echo "\n📋 TODAS LAS COLUMNAS DE LA TABLA:\n\n";

    foreach ($columns as $column) {
        echo "   • " . str_pad($column['Field'], 30) . " | " . str_pad($column['Type'], 20) . " | Nulo: " . str_pad($column['Null'], 3) . "\n";
    }

    echo "\n" . str_repeat("═", 66) . "\n";
    echo "✅ Verificación completada exitosamente\n";
    echo str_repeat("═", 66) . "\n\n";

} catch (Exception $e) {
    echo "❌ Error al conectar a la BD: " . $e->getMessage() . "\n";
    exit(1);
}
