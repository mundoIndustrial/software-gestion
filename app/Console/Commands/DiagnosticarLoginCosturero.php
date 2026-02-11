<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class DiagnosticarLoginCosturero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:costurero-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar problemas de login con rol costurero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->line('=== DIAGNOSTICANDO PROBLEMA DE LOGIN CON ROL COSTURERO ===');
        $this->line('');

        // 1. Verificar si el rol costurero existe
        $this->info('1️⃣  VERIFICANDO ROL COSTURERO:');
        $costureroRole = Role::where('name', 'costurero')->first();
        if ($costureroRole) {
            $this->line("    Rol encontrado: ID={$costureroRole->id}, Name={$costureroRole->name}");
            $this->line('');
        } else {
            $this->error('    Rol NO encontrado');
            $this->line('');
            return;
        }

        // 2. Buscar usuarios con rol costurero
        $this->info('2️⃣  BUSCANDO USUARIOS CON ROL COSTURERO:');
        $users = User::all();
        $costureroUsers = [];

        foreach ($users as $user) {
            // Verificar roles_ids
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            if (in_array($costureroRole->id, $rolesIds)) {
                $costureroUsers[] = $user;
                $this->line("    Usuario: {$user->name} (ID: {$user->id})");
                $this->line("      Email: {$user->email}");
                $this->line("      roles_ids (raw): " . json_encode($user->roles_ids));
                $this->line("      roles_ids (parsed): " . json_encode($rolesIds));
            }
        }

        if (empty($costureroUsers)) {
            $this->line('     No hay usuarios con rol costurero');
        } else {
            $this->line('   Total: ' . count($costureroUsers) . ' usuario(s) encontrado(s)');
        }

        $this->line('');

        // 3. Probar hasRole() con cada usuario
        $this->info('3️⃣  PROBANDO hasRole() CON USUARIOS COSTURERO:');
        foreach ($costureroUsers as $user) {
            $this->line('');
            $this->line("   👤 {$user->name}:");
            
            // Recargar el usuario
            $userReloaded = User::find($user->id);
            
            $this->line("      - hasRole('costurero'): " . ($userReloaded->hasRole('costurero') ? ' true' : ' false'));
            $this->line("      - hasRole({$costureroRole->id}): " . ($userReloaded->hasRole($costureroRole->id) ? ' true' : ' false'));
            $this->line("      - hasAnyRole(['costurero']): " . ($userReloaded->hasAnyRole(['costurero']) ? ' true' : ' false'));
            
            // Obtener roles actuales
            $roles = $userReloaded->roles;
            $this->line("      - Roles actuales: " . json_encode($roles->pluck('name')->toArray()));
            
            // Verificar roles_ids
            $this->line("      - roles_ids en BD: " . json_encode($userReloaded->roles_ids));
        }

        $this->line('');

        // 4. Verificar middleware OperarioAccess
        $this->info('4️⃣  VERIFICANDO MIDDLEWARE OperarioAccess:');
        if (count($costureroUsers) > 0) {
            $testUser = $costureroUsers[0];
            
            // Simular el middleware
            if (!$testUser->hasAnyRole(['cortador', 'costurero'])) {
                $this->error("    PROBLEMA: El middleware rechazaría al usuario {$testUser->name}");
            } else {
                $this->line("    El middleware permitiría al usuario {$testUser->name}");
            }
        } else {
            $this->line('     No hay usuarios costurero para probar');
        }

        $this->line('');

        // 5. Listar todos los usuarios y sus roles
        $this->info('5️⃣  LISTADO COMPLETO DE USUARIOS Y ROLES:');
        foreach ($users as $user) {
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            $roles = Role::whereIn('id', $rolesIds)->pluck('name')->toArray();
            $rolesStr = count($roles) > 0 ? implode(', ', $roles) : 'SIN ROL';
            
            $this->line("   • {$user->name} ({$user->email}): [$rolesStr]");
        }

        $this->line('');
        $this->line(' Diagnóstico completado');
        $this->line('');
    }
}
