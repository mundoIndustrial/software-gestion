<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Cargar la aplicación
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Obtener usuario si está autenticado
if (auth()->check()) {
    $user = auth()->user();
    echo "Usuario: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Roles: " . json_encode($user->roles()->pluck('name')->toArray()) . "\n";
} else {
    echo "No hay usuario autenticado\n";
}
