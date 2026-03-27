<?php

/**
 * DIAGNÓSTICO SIMPLE - SIN LARAVEL
 * Solo analiza archivos, logs y estructura de directorios
 */

define('BASE_PATH', __DIR__ . '/../..');

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║     DIAGNÓSTICO DE GUARDADO - ANÁLISIS SIMPLE                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// ===== 1. REVISAR LOGS =====
echo "📨 1. ANALIZANDO LOGS\n";
echo str_repeat("─", 70) . "\n";

$logFile = BASE_PATH . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    echo " Log encontrado: " . number_format($size / 1024 / 1024, 2) . " MB\n\n";
    
    $lines = file($logFile);
    $ultimasLineas = array_slice($lines, -20);
    
    echo " ÚLTIMAS 20 LÍNEAS:\n";
    foreach ($ultimasLineas as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        
        if (strpos($line, 'error') !== false || strpos($line, 'Error') !== false) {
            echo " " . substr($trimmed, 0, 100) . "\n";
        } elseif (strpos($line, 'exception') !== false) {
            echo "  " . substr($trimmed, 0, 100) . "\n";
        } else {
            echo "   " . substr($trimmed, 0, 100) . "\n";
        }
    }
} else {
    echo " Log no encontrado\n";
}

echo "\n";

// ===== 2. VERIFICAR DIRECTORIOS =====
echo "📂 2. VERIFICANDO DIRECTORIOS\n";
echo str_repeat("─", 70) . "\n";

$carpetas = [
    'storage/app/public/pedidos' => 'Pedidos almacenados',
    'storage/logs' => 'Logs',
    'storage/framework' => 'Framework',
    'app/Http/Controllers/Asesores' => 'Controladores',
    'app/Application/Services' => 'Servicios',
    'public/js/modulos/crear-pedido' => 'Frontend - Crear pedido',
];

foreach ($carpetas as $rel => $desc) {
    $full = BASE_PATH . '/' . $rel;
    if (is_dir($full)) {
        $permisos = decoct(fileperms($full) & 0777);
        echo " $desc (permisos: $permisos)\n";
    } else {
        echo " $desc - NO EXISTE\n";
    }
}

echo "\n";

// ===== 3. ANALIZAR CARPETA DE PEDIDOS =====
echo " 3. CONTENIDO DE CARPETA PEDIDOS\n";
echo str_repeat("─", 70) . "\n";

$pedidosDir = BASE_PATH . '/storage/app/public/pedidos';
if (is_dir($pedidosDir)) {
    $carpetas = array_filter(scandir($pedidosDir), fn($f) => $f !== '.' && $f !== '..' && is_dir($pedidosDir . '/' . $f));
    
    if (empty($carpetas)) {
        echo "  NO HAY CARPETAS DE PEDIDOS CREADAS\n";
    } else {
        echo "📁 Carpetas de pedidos: " . count($carpetas) . "\n\n";
        
        // Mostrar últimas 5
        rsort($carpetas, SORT_NUMERIC);
        $ultimas = array_slice($carpetas, 0, 5);
        
        foreach ($ultimas as $pedidoId) {
            $pedidoPath = $pedidosDir . '/' . $pedidoId;
            
            // Contar archivos recursivamente
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pedidoPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            $count = 0;
            $totalSize = 0;
            $tipos = [];
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $count++;
                    $totalSize += $file->getSize();
                    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                    $tipos[$ext] = ($tipos[$ext] ?? 0) + 1;
                }
            }
            
            echo "   📌 Pedido $pedidoId:\n";
            echo "      • Archivos: $count\n";
            echo "      • tamano: " . number_format($totalSize / 1024, 2) . " KB\n";
            
            if (!empty($tipos)) {
                echo "      • Tipos: ";
                foreach ($tipos as $ext => $cnt) {
                    echo "$ext ($cnt) ";
                }
                echo "\n";
            }
        }
    }
} else {
    echo " Carpeta de pedidos no existe: $pedidosDir\n";
}

echo "\n";

// ===== 4. ANALIZAR ARCHIVOS CRÍTICOS =====
echo "  4. VERIFICANDO ARCHIVOS CRÍTICOS\n";
echo str_repeat("─", 70) . "\n";

$archivos = [
    'app/Http/Controllers/Asesores/CrearPedidoEditableController.php' => 'Controlador principal',
    'app/Application/Services/PedidoPrendaService.php' => 'Servicio de prendas',
    'public/js/modulos/crear-pedido/prendas/integracion-prenda-sin-cotizacion.js' => 'Integración frontend',
    'public/js/modulos/crear-pedido/gestores/gestor-prendas.js' => 'Gestor de prendas',
    'public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js' => 'Gestor de procesos',
    'public/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js' => 'Renderizador de procesos',
    'public/js/componentes/prenda-tarjeta/loader.js' => 'Módulo prenda-tarjeta (loader)',
];

foreach ($archivos as $rel => $desc) {
    $full = BASE_PATH . '/' . $rel;
    if (file_exists($full)) {
        $size = filesize($full);
        $lines = count(file($full));
        echo " $desc\n";
        echo "   • Líneas: $lines, tamano: " . number_format($size / 1024, 2) . " KB\n";
    } else {
        echo " $desc - NO EXISTE ($rel)\n";
    }
}

echo "\n";

// ===== 5. PERMISOS DE ESCRITURA =====
echo " 5. VERIFICANDO PERMISOS DE ESCRITURA\n";
echo str_repeat("─", 70) . "\n";

$pathsToCheck = [
    'storage/app/public' => 'Storage público',
    'storage/logs' => 'Logs',
];

foreach ($pathsToCheck as $rel => $desc) {
    $full = BASE_PATH . '/' . $rel;
    if (is_writable($full)) {
        echo " $desc es escribible\n";
    } else {
        echo " $desc NO es escribible - Ejecuta: chmod -R 755 $rel\n";
    }
}

echo "\n";

// ===== 6. BÚSQUEDA DE ERRORES EN LOGS =====
echo " 6. BÚSQUEDA DE ERRORES ESPECÍFICOS\n";
echo str_repeat("─", 70) . "\n";

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    
    $busquedas = [
        'CrearPedidoEditableController' => 'Errores en controlador',
        'PedidoPrendaService' => 'Errores en servicio',
        'guardarArchivo' => 'Errores al guardar archivos',
        'FormData' => 'Errores con FormData',
        'SQLSTATE' => 'Errores SQL',
    ];
    
    foreach ($busquedas as $buscar => $desc) {
        if (strpos($content, $buscar) !== false) {
            echo "  Se encontraron referencias a: $desc ($buscar)\n";
        }
    }
    
    if (strpos($content, 'error') === false && strpos($content, 'Error') === false) {
        echo " No se encontraron errores en logs\n";
    }
}

echo "\n";

// ===== 7. RECOMENDACIONES =====
echo "💡 7. PRÓXIMOS PASOS\n";
echo str_repeat("─", 70) . "\n";

echo <<<'PASOS'
1. ABRIR DevTools (F12) en el navegador
2. Ir a Network tab
3. Hacer clic en "Crear/Guardar Pedido"
4. Buscar POST request a "crear-pedido"
5. Ver respuesta del servidor (Status 200, 400, 500, etc.)
6. Si Status 500: Ver details en Network tab

Para profundizar en logs de Laravel:
   php artisan log:clear
   [Hacer la acción que falla]
   tail -f storage/logs/laravel.log

PASOS;

echo "\n Diagnóstico completado\n\n";
?>
