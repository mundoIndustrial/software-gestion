#!/usr/bin/env php
<?php
/**
 * Script de Verificación: Integración EPP en Formulario Crear Pedido
 * 
 * Este script verifica que:
 * 1. Los cambios en los archivos frontend estén presentes
 * 2. El controlador backend esté correctamente configurado
 * 3. El servicio EPP esté disponible
 * 
 * Uso: php verificar_integracion_epp.php
 */

use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

$output = new ConsoleOutput();

$output->writeln('<info>🔍 VERIFICANDO INTEGRACIÓN EPP EN FORMULARIO CREAR PEDIDO</info>');
$output->writeln('');

$checks = [
    'frontend' => [],
    'backend' => [],
    'database' => [],
];

// 1. VERIFICAR CAMBIOS FRONTEND
$output->writeln('<comment>1. Verificando cambios Frontend...</comment>');

// 1.1 Verificar modal-agregar-epp.js
$modalEppFile = base_path('public/js/modulos/crear-pedido/modales/modal-agregar-epp.js');
if (file_exists($modalEppFile)) {
    $content = file_get_contents($modalEppFile);
    
    // Verificar que se agreguen items a window.itemsPedido
    if (strpos($content, 'window.itemsPedido.push(itemEPP)') !== false) {
        $checks['frontend']['itemsPedido'] = '✅ Items EPP agregados a window.itemsPedido';
        $output->writeln('<fg=green>✅</> Items EPP agregados a window.itemsPedido');
    } else {
        $checks['frontend']['itemsPedido'] = '❌ NO se agregan items a window.itemsPedido';
        $output->writeln('<fg=red>❌</> NO se agregan items a window.itemsPedido');
    }
    
    // Verificar que se remuevan items de window.itemsPedido
    if (strpos($content, 'window.itemsPedido.splice(indexToRemove, 1)') !== false) {
        $checks['frontend']['removeItems'] = '✅ Items EPP removidos de window.itemsPedido';
        $output->writeln('<fg=green>✅</> Items EPP removidos de window.itemsPedido');
    } else {
        $checks['frontend']['removeItems'] = '❌ NO se remueven items de window.itemsPedido';
        $output->writeln('<fg=red>❌</> NO se remueven items de window.itemsPedido');
    }
} else {
    $output->writeln('<fg=red>❌</> No se encontró modal-agregar-epp.js');
}

// 1.2 Verificar gestion-items-pedido.js
$output->writeln('');
$gestionItemsFile = base_path('public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js');
if (file_exists($gestionItemsFile)) {
    $content = file_get_contents($gestionItemsFile);
    
    // Verificar que procese items tipo 'epp'
    if (strpos($content, "if (item.tipo === 'epp')") !== false) {
        $checks['frontend']['processEpp'] = '✅ Procesa items tipo epp en recolectarDatosPedido()';
        $output->writeln('<fg=green>✅</> Procesa items tipo epp en recolectarDatosPedido()');
    } else {
        $checks['frontend']['processEpp'] = '❌ NO procesa items tipo epp';
        $output->writeln('<fg=red>❌</> NO procesa items tipo epp');
    }
    
    // Verificar que incluya tallas_medidas
    if (strpos($content, 'baseItem.tallas_medidas = item.tallas_medidas') !== false) {
        $checks['frontend']['tallasMedidas'] = '✅ Incluye tallas_medidas en items EPP';
        $output->writeln('<fg=green>✅</> Incluye tallas_medidas en items EPP');
    } else {
        $checks['frontend']['tallasMedidas'] = '❌ NO incluye tallas_medidas';
        $output->writeln('<fg=red>❌</> NO incluye tallas_medidas');
    }
} else {
    $output->writeln('<fg=red>❌</> No se encontró gestion-items-pedido.js');
}

// 2. VERIFICAR CAMBIOS BACKEND
$output->writeln('');
$output->writeln('<comment>2. Verificando cambios Backend...</comment>');

$controllerFile = app_path('Http/Controllers/Asesores/CrearPedidoEditableController.php');
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    
    // Verificar import de PedidoEppService
    if (strpos($content, 'use App\\Services\\PedidoEppService') !== false) {
        $checks['backend']['import'] = '✅ PedidoEppService importado';
        $output->writeln('<fg=green>✅</> PedidoEppService importado');
    } else {
        $checks['backend']['import'] = '❌ PedidoEppService NO importado';
        $output->writeln('<fg=red>❌</> PedidoEppService NO importado');
    }
    
    // Verificar inyección en constructor
    if (strpos($content, 'private PedidoEppService $eppService') !== false) {
        $checks['backend']['injection'] = '✅ PedidoEppService inyectado en constructor';
        $output->writeln('<fg=green>✅</> PedidoEppService inyectado en constructor');
    } else {
        $checks['backend']['injection'] = '❌ PedidoEppService NO inyectado';
        $output->writeln('<fg=red>❌</> PedidoEppService NO inyectado');
    }
    
    // Verificar procesamiento de EPP
    if (strpos($content, "if (\$tipo === 'epp')") !== false) {
        $checks['backend']['processEpp'] = '✅ Procesa items tipo epp';
        $output->writeln('<fg=green>✅</> Procesa items tipo epp');
    } else {
        $checks['backend']['processEpp'] = '❌ NO procesa items tipo epp';
        $output->writeln('<fg=red>❌</> NO procesa items tipo epp');
    }
    
    // Verificar guardado de EPP
    if (strpos($content, '$this->eppService->guardarEppsDelPedido($pedido, $eppsParaGuardar)') !== false) {
        $checks['backend']['saveEpp'] = '✅ Guarda EPP usando PedidoEppService';
        $output->writeln('<fg=green>✅</> Guarda EPP usando PedidoEppService');
    } else {
        $checks['backend']['saveEpp'] = '❌ NO guarda EPP';
        $output->writeln('<fg=red>❌</> NO guarda EPP');
    }
    
    // Verificar array eppsParaGuardar
    if (strpos($content, '$eppsParaGuardar = []') !== false) {
        $checks['backend']['arrayEpps'] = '✅ Array $eppsParaGuardar creado';
        $output->writeln('<fg=green>✅</> Array $eppsParaGuardar creado');
    } else {
        $checks['backend']['arrayEpps'] = '❌ Array $eppsParaGuardar NO creado';
        $output->writeln('<fg=red>❌</> Array $eppsParaGuardar NO creado');
    }
} else {
    $output->writeln('<fg=red>❌</> No se encontró CrearPedidoEditableController');
}

// 3. VERIFICAR BASE DE DATOS
$output->writeln('');
$output->writeln('<comment>3. Verificando Base de Datos...</comment>');

try {
    // Usar la aplicación Laravel para acceder a DB
    $app = app();
    
    // Verificar tabla pedido_epp
    $tablaExiste = $app['db']->select(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedido_epp'",
        [env('DB_DATABASE')]
    );
    
    if ($tablaExiste) {
        $checks['database']['table'] = '✅ Tabla pedido_epp existe';
        $output->writeln('<fg=green>✅</> Tabla pedido_epp existe');
        
        // Listar columnas
        $columnas = $app['db']->select("DESCRIBE pedido_epp");
        $columnasNombres = array_map(fn($col) => $col->Field, $columnas);
        $output->writeln('   Columnas: ' . implode(', ', $columnasNombres));
    } else {
        $checks['database']['table'] = '❌ Tabla pedido_epp NO existe';
        $output->writeln('<fg=red>❌</> Tabla pedido_epp NO existe');
    }
    
    // Verificar tabla pedido_epp_imagenes
    $tablaImagenesExiste = $app['db']->select(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedido_epp_imagenes'",
        [env('DB_DATABASE')]
    );
    
    if ($tablaImagenesExiste) {
        $checks['database']['table_imagenes'] = '✅ Tabla pedido_epp_imagenes existe';
        $output->writeln('<fg=green>✅</> Tabla pedido_epp_imagenes existe');
    } else {
        $checks['database']['table_imagenes'] = '❌ Tabla pedido_epp_imagenes NO existe';
        $output->writeln('<fg=red>❌</> Tabla pedido_epp_imagenes NO existe');
    }
    
} catch (\Exception $e) {
    $output->writeln('<fg=yellow>⚠️  Saltando verificación de BD (BD no disponible)</> ');
}

// 4. VERIFICAR SERVICIO EPP
$output->writeln('');
$output->writeln('<comment>4. Verificando Servicio EPP...</comment>');

$serviceFile = app_path('Services/PedidoEppService.php');
if (file_exists($serviceFile)) {
    $content = file_get_contents($serviceFile);
    
    // Verificar función guardarEppsDelPedido
    if (strpos($content, 'public function guardarEppsDelPedido') !== false) {
        $checks['service']['saveMethod'] = '✅ Método guardarEppsDelPedido() existe';
        $output->writeln('<fg=green>✅</> Método guardarEppsDelPedido() existe');
    } else {
        $checks['service']['saveMethod'] = '❌ Método guardarEppsDelPedido() NO existe';
        $output->writeln('<fg=red>❌</> Método guardarEppsDelPedido() NO existe');
    }
} else {
    $output->writeln('<fg=red>❌</> No se encontró PedidoEppService.php');
}

// RESUMEN
$output->writeln('');
$output->writeln('<info>═══════════════════════════════════════════════════════════</info>');
$output->writeln('<info>RESUMEN DE VERIFICACIÓN</info>');
$output->writeln('<info>═══════════════════════════════════════════════════════════</info>');

$totalChecks = 0;
$passedChecks = 0;

foreach ($checks as $section => $items) {
    foreach ($items as $check => $result) {
        $totalChecks++;
        if (strpos($result, '✅') === 0) {
            $passedChecks++;
        }
    }
}

$output->writeln('');
$output->writeln("Verificaciones completadas: <fg=green>{$passedChecks}/{$totalChecks}</> pasadas");

if ($passedChecks === $totalChecks) {
    $output->writeln('<fg=green>✅ TODAS LAS VERIFICACIONES PASARON</>');
    $output->writeln('');
    $output->writeln('<comment>Próximos pasos:</comment>');
    $output->writeln('1. Acceder a /asesores/pedidos-produccion/crear-nuevo');
    $output->writeln('2. Agregar un EPP mediante el modal');
    $output->writeln('3. Enviar el formulario');
    $output->writeln('4. Verificar en la BD que el EPP se guardó correctamente');
    $output->writeln('5. Revisar logs en storage/logs/laravel.log');
    exit(0);
} else {
    $output->writeln('<fg=red>❌ ALGUNAS VERIFICACIONES FALLARON</>');
    exit(1);
}
