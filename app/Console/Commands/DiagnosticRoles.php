<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class DiagnosticRoles extends Command
{
    protected $signature = 'diagnostic:roles';
    protected $description = 'Diagnostica el sistema de múltiples roles';

    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DEL SISTEMA DE MÚLTIPLES ROLES ===');
        $this->newLine();

        // 1. Verificar tabla users
        $this->info('1. VERIFICANDO TABLA USERS');
        $this->line('---');
        
        $users = User::all();
        $this->line("Total de usuarios: " . count($users));
        
        foreach ($users as $user) {
            $this->line("Usuario: {$user->name} (ID: {$user->id})");
            $this->line("  - role_id: " . ($user->role_id ?? 'NULL'));
            $this->line("  - roles_ids (BD): " . json_encode($user->getRawOriginal('roles_ids')));
            $this->line("  - roles_ids (cast): " . json_encode($user->roles_ids));
            
            // Verificar si role_id existe en tabla roles
            if ($user->role_id) {
                $role = Role::find($user->role_id);
                $this->line("  - role_id válido: " . ($role ? 'SÍ (' . $role->name . ')' : 'NO'));
            }
            
            // Verificar roles_ids
            if ($user->roles_ids && is_array($user->roles_ids)) {
                $this->line("  - roles_ids count: " . count($user->roles_ids));
                foreach ($user->roles_ids as $roleId) {
                    $role = Role::find($roleId);
                    $this->line("    • ID {$roleId}: " . ($role ? $role->name : 'NO EXISTE'));
                }
            } else {
                $this->line("  - roles_ids: vacío o inválido");
            }
            
            // Verificar método roles()
            try {
                $roles = $user->roles();
                $this->line("  - roles() retorna: " . count($roles) . " roles");
                foreach ($roles as $role) {
                    $this->line("    • {$role->name}");
                }
            } catch (\Exception $e) {
                $this->error("  - roles() ERROR: " . $e->getMessage());
            }
            
            // Verificar método role()
            try {
                $role = $user->role;
                $this->line("  - role (atributo): " . ($role ? $role->name : 'NULL'));
            } catch (\Exception $e) {
                $this->error("  - role ERROR: " . $e->getMessage());
            }
            
            $this->newLine();
        }

        // 2. Verificar tabla roles
        $this->info('2. VERIFICANDO TABLA ROLES');
        $this->line('---');
        
        $roles = Role::all();
        $this->line("Total de roles: " . count($roles));
        
        foreach ($roles as $role) {
            $this->line("Rol: {$role->name} (ID: {$role->id})");
            
            try {
                $usersWithRole = $role->users()->get();
                $this->line("  - users() (role_id): " . count($usersWithRole));
            } catch (\Exception $e) {
                $this->error("  - users() ERROR: " . $e->getMessage());
            }
            
            $this->newLine();
        }

        // 3. Verificar estructura de BD
        $this->info('3. VERIFICANDO ESTRUCTURA DE BD');
        $this->line('---');
        
        $user = User::first();
        if ($user) {
            $attributes = $user->getAttributes();
            $this->line("Atributos del primer usuario:");
            foreach ($attributes as $key => $value) {
                $this->line("  - {$key}: " . gettype($value) . " = " . json_encode($value));
            }
        }

        // 4. Test: Crear usuario con múltiples roles
        $this->info('4. TEST: CREAR USUARIO CON MÚLTIPLES ROLES');
        $this->line('---');
        
        $testUser = User::create([
            'name' => 'Test User ' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => bcrypt('password'),
            'roles_ids' => [1, 2, 3],
        ]);
        
        $this->line("Usuario creado: {$testUser->name} (ID: {$testUser->id})");
        $this->line("roles_ids guardado: " . json_encode($testUser->roles_ids));
        $this->line("roles() retorna: " . count($testUser->roles()) . " roles");
        
        // Eliminar usuario de test
        $testUser->delete();
        $this->line("Usuario de test eliminado");

        $this->info('=== FIN DEL DIAGNÓSTICO ===');
    }
}
