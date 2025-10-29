<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Crear un registro de prueba simulado
$registro = (object)[
    'id' => 888,
    'cantidad' => 50,
    'meta' => 100,
    'eficiencia' => 50,
    'hora' => (object)['hora' => '09:00'],
    'operario' => (object)['name' => 'TEST INMEDIATO'],
];

echo "🧪 Enviando evento INMEDIATO (sin cola)...\n";
echo "Registro: " . json_encode($registro, JSON_PRETTY_PRINT) . "\n\n";

// Disparar el evento INMEDIATAMENTE (sin cola)
event(new \App\Events\CorteRecordCreated($registro));

echo "✅ Evento enviado INMEDIATAMENTE!\n";
echo "Debería aparecer AHORA en el navegador.\n";
