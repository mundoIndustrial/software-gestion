<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignBodegueroRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Asigna el rol 'bodeguero' al primer usuario disponible
     * Útil para testing y demostración
     */
    public function run(): void
    {
        // Obtener el primer usuario disponible
        $user = User::first();

        if (!$user) {
            $this->command->error('❌ No hay usuarios en la base de datos');
            return;
        }

        // Obtener el rol bodeguero
        $bodegueroRole = Role::where('name', 'bodeguero')->first();

        if (!$bodegueroRole) {
            $this->command->error('❌ El rol "bodeguero" no existe. Ejecuta primero: php artisan db:seed --class=CrearRolesOperariosSeeder');
            return;
        }

        // Verificar si ya tiene el rol
        if ($user->hasRole('bodeguero')) {
            $this->command->warn('⚠️  El usuario ya tiene el rol bodeguero');
            return;
        }

        // Asignar el rol
        $user->roles()->attach($bodegueroRole);

        $this->command->info(' Rol bodeguero asignado exitosamente');
        $this->command->info("   - Usuario: {$user->name} ({$user->email})");
        $this->command->info("   - ID: {$user->id}");
        $this->command->info("   - Rol: bodeguero");
    }
}
