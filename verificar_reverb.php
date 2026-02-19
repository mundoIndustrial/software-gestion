<?php

// Script para verificar el estado de Reverb
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Verificando estado de Reverb\n";
echo "==============================\n\n";

try {
    // Verificar configuración
    echo "📋 Configuración de Reverb:\n";
    echo "   REVERB_APP_ID: " . env('REVERB_APP_ID') . "\n";
    echo "   REVERB_APP_KEY: " . env('REVERB_APP_KEY') . "\n";
    echo "   REVERB_HOST: " . env('REVERB_HOST') . "\n";
    echo "   REVERB_PORT: " . env('REVERB_PORT') . "\n";
    echo "   REVERB_SCHEME: " . env('REVERB_SCHEME') . "\n\n";
    
    // Verificar si el servidor Reverb está accesible
    $reverbUrl = env('REVERB_SCHEME', 'http') . '://' . env('REVERB_HOST', 'localhost') . ':' . env('REVERB_PORT', 8080);
    
    echo "🌐 Probando conexión a Reverb en: $reverbUrl\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    
    $response = @file_get_contents($reverbUrl, false, $context);
    
    if ($response !== false) {
        echo "✅ Reverb está respondiendo\n";
        echo "   Response: " . substr($response, 0, 100) . "...\n\n";
    } else {
        echo "❌ Reverb no está respondiendo\n";
        $error = error_get_last();
        if ($error) {
            echo "   Error: " . $error['message'] . "\n";
        }
        echo "\n";
    }
    
    // Verificar si el proceso Reverb está corriendo
    echo "🔍 Verificando proceso de Reverb...\n";
    
    // Buscar procesos de reverb
    $processOutput = shell_exec("ps aux | grep reverb");
    
    if (strpos($processOutput, 'reverb') !== false && strpos($processOutput, 'grep') === false) {
        echo "✅ Se encontraron procesos de Reverb:\n";
        echo $processOutput . "\n";
    } else {
        echo "❌ No se encontraron procesos de Reverb corriendo\n\n";
        
        echo "🚀 Para iniciar Reverb, ejecuta:\n";
        echo "   php artisan reverb:start\n\n";
        
        echo "🔄 O en segundo plano:\n";
        echo "   nohup php artisan reverb:start > /dev/null 2>&1 &\n\n";
    }
    
    // Verificar configuración de broadcasting
    echo "📡 Configuración de Broadcasting:\n";
    echo "   BROADCAST_DRIVER: " . env('BROADCAST_DRIVER') . "\n";
    echo "   BROADCAST_CONNECTION: " . env('BROADCAST_CONNECTION') . "\n\n";
    
    // Probar broadcast simple
    echo "🧪 Probando broadcast simple...\n";
    
    try {
        $testEvent = new \App\Events\PedidoActualizado(
            \App\Models\PedidoProduccion::find(1),
            \App\Models\User::find(82),
            ['test' => 'broadcast'],
            'test'
        );
        
        // Solo crear el evento, no emitirlo
        echo "✅ Evento PedidoActualizado se puede crear\n";
        echo "   Canales: ";
        foreach ($testEvent->broadcastOn() as $channel) {
            echo get_class($channel) . ":" . $channel->name . " ";
        }
        echo "\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Error al crear evento: " . $e->getMessage() . "\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n✅ Verificación completada\n";
