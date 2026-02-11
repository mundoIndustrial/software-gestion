<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class AssignBodegaRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bodega:assign-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a bodega role (costura-bodega or epp-bodega) to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');
        
        // Validar que el rol sea válido
        if (!in_array($roleName, ['Costura-Bodega', 'EPP-Bodega'])) {
            $this->error(" Rol inválido. Debe ser 'Costura-Bodega' o 'EPP-Bodega'");
            return;
        }
        
        // Buscar el usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error(" Usuario con email '{$email}' no encontrado");
            return;
        }
        
        // Buscar el rol
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error(" Rol '{$roleName}' no encontrado en la BD");
            return;
        }
        
        // Obtener roles_ids actuales
        $rolesIds = is_array($user->roles_ids) 
            ? $user->roles_ids 
            : json_decode($user->roles_ids ?? '[]', true);
        
        // Verificar si ya tiene el rol
        if (in_array($role->id, $rolesIds)) {
            $this->info("  Usuario '{$email}' ya tiene el rol '{$roleName}'");
            return;
        }
        
        // Agregar el rol
        $rolesIds[] = $role->id;
        $user->roles_ids = $rolesIds;
        $user->save();
        
        $this->info(" Rol '{$roleName}' asignado a '{$email}'");
        $this->info("   Roles del usuario: " . implode(', ', 
            Role::whereIn('id', $rolesIds)->pluck('name')->toArray()
        ));
    }
}
