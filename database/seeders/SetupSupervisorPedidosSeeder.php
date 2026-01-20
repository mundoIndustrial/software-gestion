<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SetupSupervisorPedidosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Este seeder ejecuta todos los seeders necesarios para configurar
     * el rol supervisor_pedidos y asignarlo a usuarios.
     *
     * Uso: php artisan db:seed --class=SetupSupervisorPedidosSeeder
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando configuración de Supervisor de Pedidos...');
        $this->command->newLine();

        // Paso 1: Crear el rol
        $this->command->info('📝 Paso 1: Creando rol "supervisor_pedidos"...');
        $this->call(SupervisorPedidosRoleSeeder::class);
        $this->command->newLine();

        // Paso 2: Asignar el rol a usuarios
        $this->command->info('👤 Paso 2: Asignando rol a usuarios...');
        $this->call(AssignSupervisorPedidosRoleSeeder::class);
        $this->command->newLine();

        $this->command->info(' ¡Configuración completada exitosamente!');
        $this->command->info('🌐 Accede a: http://localhost:8000/supervisor-pedidos/');
    }
}
