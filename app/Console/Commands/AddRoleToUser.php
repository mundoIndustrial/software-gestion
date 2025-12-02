<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AddRoleToUser extends Command
{
    protected $signature = 'role:add {userId} {roleId}';
    protected $description = 'Agregar un rol a un usuario';

    public function handle()
    {
        $userId = $this->argument('userId');
        $roleId = $this->argument('roleId');

        // Verificar que el usuario existe
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuario con ID {$userId} no encontrado");
            return 1;
        }

        // Verificar que el rol existe
        $role = Role::find($roleId);
        if (!$role) {
            $this->error("Rol con ID {$roleId} no encontrado");
            return 1;
        }

        $this->info("=== ANTES ===");
        $this->line("Usuario: {$user->name} (ID: {$user->id})");
        $this->line("roles_ids actual: " . json_encode($user->roles_ids));
        $this->line("Roles actuales:");
        foreach ($user->roles() as $r) {
            $this->line("  • {$r->name}");
        }

        // Agregar el rol
        $user->addRole($roleId);

        // Recargar el usuario
        $user->refresh();

        $this->info("\n=== DESPUÉS ===");
        $this->line("Usuario: {$user->name} (ID: {$user->id})");
        $this->line("roles_ids nuevo: " . json_encode($user->roles_ids));
        $this->line("Roles nuevos:");
        foreach ($user->roles() as $r) {
            $this->line("  • {$r->name}");
        }

        $this->info("\n✅ Rol agregado correctamente");
        return 0;
    }
}
