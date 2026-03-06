<?php

/**
 * Script para asignar el rol gestor_epp a un usuario
 * 
 * Uso:
 *   php asignar-rol-gestor-epp.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "═══════════════════════════════════════════════════════\n";
echo "   ASIGNACIÓN DE ROL GESTOR EPP\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Obtener el rol gestor_epp
$gestorEppRole = Role::where('name', 'gestor_epp')->first();

if (!$gestorEppRole) {
    echo "❌ Error: El rol 'gestor_epp' no existe en la base de datos.\n";
    echo "   Por favor ejecuta: php artisan db:seed --class=GestorEppRoleSeeder\n";
    exit(1);
}

echo "✅ Rol 'gestor_epp' encontrado (ID: {$gestorEppRole->id})\n\n";

// Listar usuarios disponibles
echo "Usuarios disponibles:\n";
echo "───────────────────────────────────────────────────────\n";

$users = User::all();
foreach ($users as $user) {
    $currentRoles = [];
    if (!empty($user->roles_ids)) {
        $roleIds = is_array($user->roles_ids) ? $user->roles_ids : json_decode($user->roles_ids, true);
        $currentRoles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
    }
    
    $rolesStr = empty($currentRoles) ? 'Sin roles' : implode(', ', $currentRoles);
    echo sprintf("  %d) %s <%s> - %s\n", $user->id, $user->name, $user->email, $rolesStr);
}

echo "\n";
echo "Ingrese el ID del usuario al que desea asignar el rol 'gestor_epp': ";
$userId = trim(fgets(STDIN));

if (!is_numeric($userId)) {
    echo "❌ Error: Debe ingresar un número válido.\n";
    exit(1);
}

$user = User::find($userId);

if (!$user) {
    echo "❌ Error: Usuario no encontrado.\n";
    exit(1);
}

// Obtener roles actuales
$rolesIds = is_array($user->roles_ids) ? $user->roles_ids : json_decode($user->roles_ids ?? '[]', true);

// Verificar si ya tiene el rol
if (in_array($gestorEppRole->id, $rolesIds)) {
    echo "⚠️  El usuario '{$user->email}' ya tiene el rol 'gestor_epp'.\n";
    exit(0);
}

// Asignar solo el rol gestor_epp (excluir otros roles)
$user->roles_ids = [$gestorEppRole->id];
$user->save();

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "✅ ROL ASIGNADO EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Usuario: {$user->name} ({$user->email})\n";
echo "Rol: gestor_epp (ID: {$gestorEppRole->id})\n";
echo "\n";
echo "💡 El usuario ahora solo puede acceder a /epp\n";
echo "   Ruta: http://localhost:8000/epp\n";
echo "═══════════════════════════════════════════════════════\n";
