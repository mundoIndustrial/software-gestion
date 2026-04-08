<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class CheckAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:admin-role {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar roles del usuario admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'admin@admin.com';

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email {$email} no encontrado");
            return 1;
        }

        $this->info("Usuario: {$user->name} ({$user->email})");
        $this->info("ID: {$user->id}");

        // Verificar rol principal (role_id)
        if ($user->role_id) {
            $role = Role::find($user->role_id);
            $this->info("Rol principal (role_id): " . ($role ? $role->name : 'No encontrado'));
        } else {
            $this->info("Rol principal (role_id): No asignado");
        }

        // Verificar roles múltiples (roles_ids)
        $this->info("Roles múltiples (roles_ids): " . json_encode($user->roles_ids));

        // Verificar si tiene rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $hasAdminRole = in_array($adminRole->id, $user->roles_ids ?? []);
            $this->info("¿Tiene rol admin?: " . ($hasAdminRole ? 'SÍ' : 'NO'));
            $this->info("ID del rol admin: {$adminRole->id}");
        } else {
            $this->error("Rol 'admin' no existe en la base de datos");
        }

        // Verificar método hasRole
        $this->info("hasRole('admin'): " . ($user->hasRole('admin') ? 'SÍ' : 'NO'));

        return 0;
    }
}