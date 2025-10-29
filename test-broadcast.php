<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Crear un registro de prueba simulado
$registro = (object)[
    'id' => 999,
    'cantidad' => 100,
    'meta' => 120,
    'eficiencia' => 83.33,
    'hora' => (object)['hora' => '08:00'],
    'operario' => (object)['name' => 'TEST OPERARIO'],
];

echo "🧪 Enviando evento de prueba...\n";
echo "Registro: " . json_encode($registro, JSON_PRETTY_PRINT) . "\n\n";

// Disparar el evento
broadcast(new \App\Events\CorteRecordCreated($registro));

echo "✅ Evento enviado a la cola!\n";
echo "Verifica que el queue worker lo procese y que aparezca en el navegador.\n";
