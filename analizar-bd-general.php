<?php
/**
 * ANALIZADOR GENERAL DE BASE DE DATOS
 * 
 * Usa conexión MySQL directa para analizar:
 * - Todas las tablas
 * - Tamaño de tablas
 * - Registros duplicados
 * - Integridad referencial
 * - Estado general
 */

// Configuración de conexión desde .env
$envFile = file_get_contents('.env');
$env = [];
foreach (preg_split("/\r\n|\n|\r/", $envFile) as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $value = trim($value, '"\'');
    $env[$key] = $value;
}

$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? 3306;
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';
$database = $env['DB_DATABASE'] ?? '';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ANÁLISIS GENERAL DE BASE DE DATOS                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // Conexión
    $mysqli = new mysqli($host, $user, $pass, $database, $port);
    
    if ($mysqli->connect_error) {
        die("❌ Error de conexión: " . $mysqli->connect_error . "\n");
    }
    
    echo "✅ Conectado a: {$database}\n";
    echo "   Host: {$host}:{$port}\n\n";

    // TEST 1: Listar todas las tablas
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 1: TODAS LAS TABLAS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $result = $mysqli->query("SELECT TABLE_NAME, TABLE_ROWS, ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS SIZE_MB 
                            FROM INFORMATION_SCHEMA.TABLES 
                            WHERE TABLE_SCHEMA = '{$database}' 
                            ORDER BY TABLE_NAME");
    
    if (!$result) {
        die("❌ Error en query: " . $mysqli->error . "\n");
    }
    
    $totalTables = $result->num_rows;
    $totalRows = 0;
    $totalSize = 0;
    
    echo "Total de tablas: {$totalTables}\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $tabla = $row['TABLE_NAME'];
        $filas = $row['TABLE_ROWS'];
        $tamaño = $row['SIZE_MB'];
        
        $totalRows += $filas;
        $totalSize += $tamaño;
        
        $icon = ($filas > 0) ? "✓" : "○";
        printf("  %s %-40s %8d registros  %8.2f MB\n", $icon, $tabla, $filas, $tamaño);
    }
    
    echo "\n  " . str_repeat("─", 60) . "\n";
    printf("  %-40s %8d registros  %8.2f MB\n", "TOTAL", $totalRows, $totalSize);
    echo "\n";

    // TEST 2: Tablas de técnicas
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 2: TABLAS DE TÉCNICAS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $tablas = ['tipo_logo_cotizaciones', 'logo_cotizacion_tecnicas', 'logo_cotizacion_tecnica_prendas'];
    
    foreach ($tablas as $tabla) {
        $result = $mysqli->query("SELECT COUNT(*) as cnt FROM `{$tabla}`");
        
        if (!$result) {
            echo "❌ {$tabla} - NO EXISTE o error en query\n";
            continue;
        }
        
        $count = $result->fetch_assoc()['cnt'];
        echo "✓ {$tabla}: {$count} registros\n";
    }
    echo "\n";

    // TEST 3: Mostrar tipos de técnicas
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 3: TIPOS DE TÉCNICAS REGISTRADOS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $result = $mysqli->query("SELECT id, codigo, nombre, activo FROM tipo_logo_cotizaciones ORDER BY id");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activo = $row['activo'] ? '✓' : '○';
            echo "  [{$row['id']}] {$activo} {$row['codigo']}: {$row['nombre']}\n";
        }
    } else {
        echo "  ⚠️  No hay tipos de técnicas registrados\n";
        echo "     Ejecutar: php artisan db:seed --class=TipoLogoCotizacionSeeder\n";
    }
    echo "\n";

    // TEST 4: Verificar duplicados
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 4: BÚSQUEDA DE DUPLICADOS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Duplicados en tipo_logo_cotizaciones
    $result = $mysqli->query("SELECT codigo, COUNT(*) as cnt FROM tipo_logo_cotizaciones GROUP BY codigo HAVING cnt > 1");
    
    if ($result && $result->num_rows > 0) {
        echo "⚠️  Códigos duplicados en tipo_logo_cotizaciones:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - {$row['codigo']}: {$row['cnt']} registros\n";
        }
    } else {
        echo "✓ Sin duplicados en tipo_logo_cotizaciones\n";
    }
    
    // Duplicados en logo_cotizacion_tecnicas
    $result = $mysqli->query("SELECT logo_cotizacion_id, tipo_logo_cotizacion_id, COUNT(*) as cnt 
                            FROM logo_cotizacion_tecnicas 
                            GROUP BY logo_cotizacion_id, tipo_logo_cotizacion_id 
                            HAVING cnt > 1");
    
    if ($result && $result->num_rows > 0) {
        echo "⚠️  Combinaciones duplicadas en logo_cotizacion_tecnicas:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - Cotización {$row['logo_cotizacion_id']}, Tipo {$row['tipo_logo_cotizacion_id']}: {$row['cnt']} registros\n";
        }
    } else {
        echo "✓ Sin combinaciones duplicadas en logo_cotizacion_tecnicas\n";
    }
    echo "\n";

    // TEST 5: Integridad referencial
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 5: INTEGRIDAD REFERENCIAL\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Técnicas sin cotización válida
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM logo_cotizacion_tecnicas lct
                            WHERE NOT EXISTS (
                                SELECT 1 FROM logo_cotizaciones lc 
                                WHERE lc.id = lct.logo_cotizacion_id
                            )");
    
    $sinCotizacion = $result->fetch_assoc()['cnt'];
    if ($sinCotizacion > 0) {
        echo "⚠️  {$sinCotizacion} técnicas sin cotización válida\n";
    } else {
        echo "✓ Todas las técnicas tienen cotización válida\n";
    }
    
    // Técnicas sin tipo válido
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM logo_cotizacion_tecnicas lct
                            WHERE NOT EXISTS (
                                SELECT 1 FROM tipo_logo_cotizaciones tlc 
                                WHERE tlc.id = lct.tipo_logo_cotizacion_id
                            )");
    
    $sinTipo = $result->fetch_assoc()['cnt'];
    if ($sinTipo > 0) {
        echo "⚠️  {$sinTipo} técnicas sin tipo válido\n";
    } else {
        echo "✓ Todas las técnicas tienen tipo válido\n";
    }
    
    // Prendas sin técnica válida
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM logo_cotizacion_tecnica_prendas lctp
                            WHERE NOT EXISTS (
                                SELECT 1 FROM logo_cotizacion_tecnicas lct 
                                WHERE lct.id = lctp.logo_cotizacion_tecnica_id
                            )");
    
    $sinTecnica = $result->fetch_assoc()['cnt'];
    if ($sinTecnica > 0) {
        echo "⚠️  {$sinTecnica} prendas sin técnica válida\n";
    } else {
        echo "✓ Todas las prendas tienen técnica válida\n";
    }
    echo "\n";

    // TEST 6: Estado de migrations
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST 6: MIGRACIONES EJECUTADAS (últimas 10)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $result = $mysqli->query("SELECT migration, batch FROM migrations ORDER BY batch DESC, id DESC LIMIT 10");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $isTecnica = (strpos($row['migration'], 'logo_cotizacion') !== false || 
                         strpos($row['migration'], 'tipo_logo') !== false);
            $icon = $isTecnica ? "📌" : "  ";
            echo "{$icon} [{$row['batch']}] {$row['migration']}\n";
        }
    }
    echo "\n";

    // RESUMEN FINAL
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  RESUMEN FINAL                                                 ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    $tablasRequeridas = ['tipo_logo_cotizaciones', 'logo_cotizacion_tecnicas', 'logo_cotizacion_tecnica_prendas'];
    $todasExisten = true;
    
    foreach ($tablasRequeridas as $tabla) {
        $result = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = '{$tabla}'");
        if ($result->num_rows === 0) {
            $todasExisten = false;
            break;
        }
    }
    
    if ($todasExisten && $sinCotizacion === 0 && $sinTipo === 0 && $sinTecnica === 0) {
        echo "✅ BASE DE DATOS EN EXCELENTE ESTADO\n\n";
        
        // Mostrar stats
        $result = $mysqli->query("SELECT COUNT(*) as cnt FROM tipo_logo_cotizaciones");
        $tiposCount = $result->fetch_assoc()['cnt'];
        
        $result = $mysqli->query("SELECT COUNT(*) as cnt FROM logo_cotizacion_tecnicas");
        $tecnicasCount = $result->fetch_assoc()['cnt'];
        
        echo "Estadísticas:\n";
        echo "  • Tipos de técnica: {$tiposCount}\n";
        echo "  • Técnicas registradas: {$tecnicasCount}\n";
        echo "  • Total registros en BD: {$totalRows}\n";
        echo "  • Tamaño BD: {$totalSize} MB\n";
        echo "\n📝 Próximos pasos:\n";
        echo "  1. Abrir http://servermi:8000/cotizaciones/bordado/create\n";
        echo "  2. Hacer clic en botón '+' de Técnicas\n";
        echo "  3. Probar agregar una técnica con prendas\n";
        echo "  4. Abrir DevTools (F12) para ver llamadas API\n";
    } else {
        echo "⚠️  BASE DE DATOS REQUIERE ATENCIÓN\n\n";
        
        if (!$todasExisten) {
            echo "❌ Faltan tablas requeridas\n";
            echo "   Ejecutar: php artisan migrate --force\n";
        }
        
        if ($sinCotizacion > 0 || $sinTipo > 0 || $sinTecnica > 0) {
            echo "❌ Hay problemas de integridad referencial\n";
            echo "   Revisar y limpiar registros huérfanos\n";
        }
    }
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  FIN DEL ANÁLISIS                                              ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    $mysqli->close();

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
