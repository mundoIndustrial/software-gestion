<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use App\Models\Role;

// Obtener rol costurero
$role = Role::where('name', 'costurero')->first();

if (!$role) {
    echo "Error: Rol 'costurero' no encontrado\n";
    exit(1);
}

// Verificar si el usuario ya existe
$existingUser = User::where('email', 'costura-reflectivo@mundoindustrial.com')->first();
if ($existingUser) {
    echo "El usuario ya existe: {$existingUser->name} (ID: {$existingUser->id})\n";
    exit(0);
}

// Crear usuario
$user = User::create([
    'name' => 'Costura-Reflectivo',
    'email' => 'costura-reflectivo@mundoindustrial.com',
    'password' => bcrypt('password123'),
    'roles_ids' => [$role->id]
]);

echo "✅ Usuario creado exitosamente:\n";
echo "   Nombre: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   ID: {$user->id}\n";
echo "   Rol: {$role->name}\n";
