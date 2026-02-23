<?php

require_once 'vendor/autoload.php';

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'mundo_bd';
$username = 'root';
$password = '';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Conectado a la base de datos correctamente\n";
    
    // Leer el archivo SQL
    $sqlFile = 'database/scripts/vista_entregas_completas.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("El archivo $sqlFile no existe");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "📄 Archivo SQL leído correctamente\n";
    
    // Eliminar comentarios y líneas vacías
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*$/m', '', $sql);
    
    // Dividir en sentencias individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "🔄 Ejecutando " . count($statements) . " sentencias SQL...\n";
    
    foreach ($statements as $i => $statement) {
        if (!empty($statement)) {
            try {
                echo "📝 Ejecutando sentencia " . ($i + 1) . "...\n";
                $pdo->exec($statement);
                echo "✅ Sentencia " . ($i + 1) . " ejecutada correctamente\n";
            } catch (PDOException $e) {
                echo "❌ Error en sentencia " . ($i + 1) . ": " . $e->getMessage() . "\n";
                echo "📄 SQL: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    // Verificar si la vista fue creada
    $stmt = $pdo->query("SHOW TABLES LIKE 'vista_entregas_completas'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "🎉 ¡VISTA CREADA EXITOSAMENTE!\n";
        
        // Mostrar algunas filas de prueba
        echo "\n📊 Mostrando primeras 5 filas de la vista:\n";
        $stmt = $pdo->query("SELECT * FROM vista_entregas_completas LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                echo "📋 Pedido #" . $row['numero_pedido'] . " - " . $row['cliente'] . "\n";
                echo "   Estado: " . $row['estado_entrega_general'] . "\n";
                echo "   Supervisor: " . ($row['fecha_entrega_supervisor'] ? '✅' : '❌') . "\n";
                echo "   Despacho: " . ($row['fecha_entrega_despacho'] ? '✅' : '❌') . "\n\n";
            }
        } else {
            echo "⚠️  La vista está vacía (no hay datos)\n";
        }
    } else {
        echo "❌ La vista no pudo ser creada\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "🔍 Verifica:\n";
    echo "   - Que el servidor MySQL esté corriendo\n";
    echo "   - Que las credenciales sean correctas\n";
    echo "   - Que la base de datos 'mundo_bd' exista\n";
    echo "   - Que el usuario tenga permisos CREATE VIEW\n";
}

echo "\n🏁 Proceso finalizado\n";
