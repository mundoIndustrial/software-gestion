<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\ProduccionRecordCreated;
use App\Events\PoloRecordCreated;
use App\Events\CorteRecordCreated;

echo "🧪 Probando Broadcasting en Tiempo Real\n";
echo "========================================\n\n";

// Verificar configuración de broadcasting
echo "📡 Configuración de Broadcasting:\n";
echo "   Driver: " . config('broadcasting.default') . "\n";
echo "   Reverb Host: " . config('broadcasting.connections.reverb.options.host') . "\n";
echo "   Reverb Port: " . config('broadcasting.connections.reverb.options.port') . "\n";
echo "   Reverb Scheme: " . config('broadcasting.connections.reverb.options.scheme') . "\n\n";

// Crear un registro de prueba para Producción
echo "🔵 Emitiendo evento de Producción...\n";
try {
    $testRegistro = (object)[
        'id' => 99999,
        'modulo' => 'TEST-MODULE',
        'orden_produccion' => 'TEST-001',
        'hora' => '07:00 - 08:00',
        'cantidad' => 100,
        'meta' => 90,
        'eficiencia' => 1.11
    ];
    
    broadcast(new ProduccionRecordCreated($testRegistro));
    echo "   ✅ Evento ProduccionRecordCreated emitido\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Crear un registro de prueba para Polos
echo "🟢 Emitiendo evento de Polos...\n";
try {
    $testRegistro = (object)[
        'id' => 99998,
        'modulo' => 'TEST-POLO',
        'orden_produccion' => 'TEST-002',
        'hora' => '08:00 - 09:00',
        'cantidad' => 80,
        'meta' => 75,
        'eficiencia' => 1.07
    ];
    
    broadcast(new PoloRecordCreated($testRegistro));
    echo "   ✅ Evento PoloRecordCreated emitido\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Crear un registro de prueba para Corte
echo "🟡 Emitiendo evento de Corte...\n";
try {
    $testRegistro = (object)[
        'id' => 99997,
        'modulo' => 'TEST-CORTE',
        'orden_produccion' => 'TEST-003',
        'hora' => (object)['hora' => '09:00 - 10:00'],
        'operario' => (object)['name' => 'TEST OPERATOR'],
        'maquina' => (object)['nombre_maquina' => 'TEST MACHINE'],
        'tela' => (object)['nombre_tela' => 'TEST FABRIC'],
        'cantidad' => 120,
        'meta' => 100,
        'eficiencia' => 1.20
    ];
    
    broadcast(new CorteRecordCreated($testRegistro));
    echo "   ✅ Evento CorteRecordCreated emitido\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "========================================\n";
echo "✅ Prueba completada\n";
echo "\n";
echo "📝 Instrucciones:\n";
echo "   1. Asegúrate de que Reverb esté corriendo: php artisan reverb:start\n";
echo "   2. Abre la vista fullscreen en tu navegador\n";
echo "   3. Abre la consola del navegador (F12)\n";
echo "   4. Ejecuta este script: php test-broadcast-realtime.php\n";
echo "   5. Verifica que los eventos aparezcan en la consola del navegador\n";
echo "\n";
