<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class YusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el rol asesor
        $roleAsesor = Role::where('name', 'asesor')->first();
        
        if (!$roleAsesor) {
            $this->command->error('❌ El rol "asesor" no existe.');
            $this->command->error('   Ejecuta primero: php artisan db:seed --class=RolesSeeder');
            return;
        }
        
        $this->command->info('✅ Rol "asesor" encontrado (ID: ' . $roleAsesor->id . ')');
        
        // Verificar si ya existe el usuario
        $existingUser = User::where('email', 'yus@gmail.com')->first();
        
        if ($existingUser) {
            $this->command->warn('⚠️  El usuario ya existe:');
            $this->command->info('   Nombre: ' . $existingUser->name);
            $this->command->info('   Email: ' . $existingUser->email);
            $this->command->info('   Rol: ' . ($existingUser->role ? $existingUser->role->name : 'Sin rol'));
            
            // Verificar si tiene el rol correcto
            if ($existingUser->role_id !== $roleAsesor->id) {
                $this->command->warn('   ⚠️  El usuario no tiene el rol de asesor. Actualizando...');
                $existingUser->role_id = $roleAsesor->id;
                $existingUser->save();
                $this->command->info('   ✅ Rol actualizado correctamente');
            }
            
            return;
        }
        
        // Crear usuario
        $user = User::create([
            'name' => 'Yus',
            'email' => 'yus@gmail.com',
            'password' => bcrypt('12345678'),
            'role_id' => $roleAsesor->id
        ]);
        
        $this->command->info('');
        $this->command->info('✅ Usuario creado exitosamente:');
        $this->command->info('   Nombre: ' . $user->name);
        $this->command->info('   Email: ' . $user->email);
        $this->command->info('   Password: 12345678');
        $this->command->info('   Rol: ' . $user->role->name);
        $this->command->info('');
        $this->command->info('🌐 Accede al sistema:');
        $this->command->info('   URL: http://localhost:8000/login');
        $this->command->info('   Dashboard: http://localhost:8000/asesores/dashboard');
        $this->command->info('');
    }
}
