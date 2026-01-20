<?php

/**
 * DIAGNÓSTICO AVANZADO - Analiza qué se guarda y qué FALTA
 * 
 * Status 200 pero no se guarda TODO
 */

define('BASE_PATH', __DIR__ . '/../..');

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO AVANZADO - ¿QUÉ SE GUARDA Y QUÉ FALTA?             ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// ===== 1. ANALIZAR ÚLTIMOS PEDIDOS =====
echo " 1. ANALIZANDO ÚLTIMOS PEDIDOS CREADOS\n";
echo str_repeat("─", 70) . "\n";

$pedidosDir = BASE_PATH . '/storage/app/public/pedidos';
if (is_dir($pedidosDir)) {
    $carpetas = array_filter(scandir($pedidosDir), fn($f) => $f !== '.' && $f !== '..' && is_dir($pedidosDir . '/' . $f));
    rsort($carpetas, SORT_NUMERIC);
    
    if (!empty($carpetas)) {
        $ultimos = array_slice($carpetas, 0, 3);
        
        foreach ($ultimos as $idx => $pedidoId) {
            echo "\n📌 PEDIDO $pedidoId (Carpeta más reciente #" . ($idx + 1) . ")\n";
            echo str_repeat("─", 50) . "\n";
            
            $pedidoPath = $pedidosDir . '/' . $pedidoId;
            
            // Analizar estructura
            $subdirs = array_filter(scandir($pedidoPath), fn($f) => 
                $f !== '.' && $f !== '..' && is_dir($pedidoPath . '/' . $f)
            );
            
            echo "📁 Carpetas en pedido:\n";
            foreach ($subdirs as $subdir) {
                $subPath = $pedidoPath . '/' . $subdir;
                $files = array_filter(scandir($subPath), fn($f) => 
                    $f !== '.' && $f !== '..' && is_file($subPath . '/' . $f)
                );
                
                $totalSize = 0;
                foreach ($files as $file) {
                    $totalSize += filesize($subPath . '/' . $file);
                }
                
                echo "   • /$subdir: " . count($files) . " archivo(s), " . 
                     number_format($totalSize / 1024, 2) . " KB\n";
                
                if (count($files) > 0) {
                    foreach ($files as $file) {
                        echo "      - $file\n";
                    }
                }
            }
            
            echo "\n";
        }
    }
} else {
    echo " Carpeta de pedidos no existe\n";
}

// ===== 2. REVISAR LOGS ESPECÍFICOS =====
echo "2. REVISANDO LOGS PARA ERRORES DE GUARDADO\n";
echo str_repeat("─", 70) . "\n";

$logFile = BASE_PATH . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    
    // Buscar patterns específicos
    $patterns = [
        '/guardar.*imagen/i' => 'Guardado de imágenes',
        '/save.*failed/i' => 'Fallos al guardar',
        '/validation/i' => 'Validación fallida',
        '/exception/i' => 'Excepciones',
        '/error.*prenda/i' => 'Errores en prendas',
        '/error.*tela/i' => 'Errores en telas',
        '/error.*proceso/i' => 'Errores en procesos',
    ];
    
    echo "\n BÚSQUEDA DE ERRORES:\n";
    
    $encontrados = false;
    foreach ($patterns as $pattern => $desc) {
        if (preg_match($pattern, $content)) {
            echo "     Encontrado: $desc\n";
            $encontrados = true;
        }
    }
    
    if (!$encontrados) {
        echo "    No se encontraron errores típicos de guardado\n";
    }
}

// ===== 3. CREAR FORMULARIO DE PRUEBA =====
echo "\n\n3. CÓMO HACER DEBUG DETALLADO\n";
echo str_repeat("─", 70) . "\n";

$debugScript = <<<'DEBUG'
// Copiar en consola del navegador (F12) ANTES de guardar
window.DEBUG_GUARDADO = true;

// Interceptar POST a crear-pedido
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const [url, config] = args;
    
    if (url.includes('crear-pedido')) {
        console.log('\n═══════════════════════════════════════════');
        console.log('REQUEST ENVIADO AL SERVIDOR:');
        console.log('═══════════════════════════════════════════\n');
        
        if (config.body instanceof FormData) {
            let detalles = {
                prendas: 0,
                telas: 0,
                procesos: 0,
                imagenes: 0,
                campos: []
            };
            
            for (let [key, val] of config.body.entries()) {
                if (val instanceof File) {
                    detalles.imagenes++;
                    console.log('📷 Archivo:', key, '=', val.name, '(' + val.size + ' bytes)');
                } else if (key.includes('prenda')) {
                    detalles.prendas++;
                } else if (key.includes('tela')) {
                    detalles.telas++;
                } else if (key.includes('proceso')) {
                    detalles.procesos++;
                } else {
                    detalles.campos.push(key);
                }
            }
            
            console.log('\n RESUMEN:');
            console.log('   Prendas:', detalles.prendas);
            console.log('   Telas:', detalles.telas);
            console.log('   Procesos:', detalles.procesos);
            console.log('   Imágenes:', detalles.imagenes);
            console.log('   Otros campos:', detalles.campos.length);
        }
    }
    
    // Hacer el request original
    const response = await originalFetch(...args);
    
    if (url.includes('crear-pedido')) {
        const clonedResponse = response.clone();
        const json = await clonedResponse.json();
        
        console.log('\n═══════════════════════════════════════════');
        console.log('RESPUESTA DEL SERVIDOR:');
        console.log('═══════════════════════════════════════════\n');
        console.log('Status:', response.status);
        console.log('Respuesta:', json);
        
        if (json.success) {
            console.log('\n GUARDADO EXITOSO');
            console.log('ID Pedido:', json.pedido_id || json.id);
        } else {
            console.log('\n GUARDADO FALLIDO');
            console.log('Error:', json.error || json.message);
        }
    }
    
    return response;
};

console.log(' DEBUG ACTIVADO - Ahora guarda el pedido');
DEBUG;

echo "\n💻 SCRIPT DE DEBUG:\n";
echo "Copia esto en la consola (F12) ANTES de guardar:\n\n";
echo $debugScript;

// ===== 4. QUÉ VERIFICAR =====
echo "\n\n4. CHECKLIST - ¿QUÉ PODRÍA NO GUARDARSE?\n";
echo str_repeat("─", 70) . "\n";

$checklist = [
    '❓ ¿Se guardan las PRENDAS?' => 'Revisa si hay filas en tabla pedido_prenda',
    '❓ ¿Se guardan las FOTOS DE PRENDA?' => 'Revisa carpeta /prendas/ dentro del pedido',
    '❓ ¿Se guardan las TELAS?' => 'Revisa tabla pedido_prenda_fotos_tela',
    '❓ ¿Se guardan las FOTOS DE TELA?' => 'Revisa carpeta /telas/ dentro del pedido',
    '❓ ¿Se guardan los PROCESOS?' => 'Revisa tabla pedido_prenda_proceso',
    '❓ ¿Se guardan las FOTOS DE PROCESOS?' => 'Revisa carpeta /procesos/ dentro del pedido',
    '❓ ¿Se guardan las VARIACIONES?' => 'Revisa campos en pedido_prenda (manga, bolsillos, etc)',
    '❓ ¿Se guardan las CANTIDADES POR TALLA?' => 'Revisa tabla pedido_prenda_cantidad_talla',
];

foreach ($checklist as $pregunta => $verificar) {
    echo "$pregunta\n";
    echo "   → $verificar\n\n";
}

// ===== 5. COMANDOS PARA VER LA BD =====
echo "\n5. COMANDOS PARA REVISAR LA BD\n";
echo str_repeat("─", 70) . "\n";

$comandos = <<<'SQL'
# Conectate con:
# mysql -u usuario -p basedatos

# Ver último pedido creado:
SELECT id, numero_pedido, created_at FROM pedidos_produccion ORDER BY id DESC LIMIT 1;

# Ver prendas del pedido (cambia el ID):
SELECT id, nombre_producto, created_at FROM pedido_prenda WHERE pedido_id = 45731;

# Ver fotos de prenda:
SELECT * FROM pedido_prenda_fotos_pedido WHERE prenda_id = XXX;

# Ver telas del pedido:
SELECT * FROM pedido_prenda_fotos_tela WHERE prenda_id = XXX;

# Ver procesos del pedido:
SELECT tipo, datos FROM pedido_prenda_proceso WHERE prenda_id = XXX;

# Ver cantidades por talla:
SELECT * FROM pedido_prenda_cantidad_talla WHERE prenda_id = XXX;

SQL;

echo $comandos;

echo "\n\n Diagnóstico completado\n\n";

?>
