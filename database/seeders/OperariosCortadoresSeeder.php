<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperariosCortadoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea los operarios de corte con IDs fijos:
     * - ID 3: PAOLA
     * - ID 4: JULIAN
     * - ID 5: ADRIAN
     * 
     * Todos con role_id = 3 (cortador)
     */
    public function run(): void
    {
        // Obtener el ID del rol "cortador" (debería ser 3 según RolesSeeder)
        $roleCortadorId = DB::table('roles')->where('name', 'cortador')->value('id');

        if (!$roleCortadorId) {
            $this->command->error('❌ No se encontró el rol "cortador". Ejecuta primero RolesSeeder.');
            return;
        }

        // Operarios de corte con IDs específicos
        $operarios = [
            [
                'id' => 3,
                'name' => 'PAOLA',
                'email' => 'paola@mundoindustrial.com',
                'password' => Hash::make('paola123'), // Cambiar en producción
                'role_id' => $roleCortadorId,
            ],
            [
                'id' => 4,
                'name' => 'JULIAN',
                'email' => 'julian@mundoindustrial.com',
                'password' => Hash::make('julian123'), // Cambiar en producción
                'role_id' => $roleCortadorId,
            ],
            [
                'id' => 5,
                'name' => 'ADRIAN',
                'email' => 'adrian@mundoindustrial.com',
                'password' => Hash::make('adrian123'), // Cambiar en producción
                'role_id' => $roleCortadorId,
            ],
        ];

        foreach ($operarios as $operario) {
            // Verificar si ya existe el usuario con ese ID
            $exists = DB::table('users')->where('id', $operario['id'])->exists();
            
            if (!$exists) {
                DB::table('users')->insert([
                    'id' => $operario['id'],
                    'name' => $operario['name'],
                    'email' => $operario['email'],
                    'password' => $operario['password'],
                    'role_id' => $operario['role_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("✅ Operario creado: {$operario['name']} (ID: {$operario['id']})");
            } else {
                $this->command->warn("⚠️ El operario con ID {$operario['id']} ya existe. Se omite.");
            }
        }

        // Resetear el auto_increment para que el siguiente usuario tenga ID 6
        DB::statement('ALTER TABLE users AUTO_INCREMENT = 6');
        
        $this->command->info('✅ Seeder de operarios de corte completado.');
    }
}
