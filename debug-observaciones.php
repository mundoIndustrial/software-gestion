<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoObservacionesDespacho;

echo "=== OBSERVACIONES DE DESPACHO EXISTENTES ===\n";

$observaciones = PedidoObservacionesDespacho::with('pedido')->get();

if ($observaciones->isEmpty()) {
    echo "No hay observaciones de despacho en la base de datos.\n";
    echo "Creando una observación de prueba...\n";
    
    // Crear una observación de prueba para el pedido 19
    $obs = PedidoObservacionesDespacho::create([
        'pedido_produccion_id' => 19,
        'uuid' => \Illuminate\Support\Str::uuid(),
        'contenido' => 'Observación de prueba para badge',
        'usuario_id' => 1,
        'usuario_nombre' => 'Usuario Prueba',
        'usuario_rol' => 'Despacho',
        'ip_address' => '127.0.0.1',
        'estado' => 0,
    ]);
    
    echo "Observación de prueba creada para el pedido 19\n";
} else {
    echo "Se encontraron {$observaciones->count()} observaciones:\n";
    foreach ($observaciones as $obs) {
        echo "- Pedido {$obs->pedido_produccion_id}: {$obs->contenido} (Rol: {$obs->usuario_rol}, Estado: {$obs->estado})\n";
    }
}

echo "\n=== VERIFICANDO CAMPO visto_at ===\n";
$obsConVistoAt = PedidoObservacionesDespacho::whereNotNull('visto_at')->get();
echo "Observaciones con visto_at: {$obsConVistoAt->count()}\n";

echo "\n=== LISTO ===\n";
