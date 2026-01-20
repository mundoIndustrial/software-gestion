<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CrearUsuarioCosturaReflectivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener rol costurero
        $role = Role::where('name', 'costurero')->first();

        if (!$role) {
            $this->command->error('Rol "costurero" no encontrado. Por favor crea el rol primero.');
            return;
        }

        // Verificar si el usuario ya existe
        $existingUser = User::where('email', 'costura-reflectivo@mundoindustrial.com')->first();
        if ($existingUser) {
            $this->command->info("Usuario ya existe: {$existingUser->name} (ID: {$existingUser->id})");
            return;
        }

        // Crear usuario
        $user = User::create([
            'name' => 'Costura-Reflectivo',
            'email' => 'costura-reflectivo@mundoindustrial.com',
            'password' => bcrypt('password123'),
            'roles_ids' => [$role->id]
        ]);

        $this->command->info(" Usuario creado exitosamente:");
        $this->command->info("   Nombre: {$user->name}");
        $this->command->info("   Email: {$user->email}");
        $this->command->info("   ID: {$user->id}");
        $this->command->info("   Rol: {$role->name}");
    }
}
