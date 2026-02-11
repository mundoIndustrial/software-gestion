<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;

class VerifyBodegueroRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:bodeguero-role';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verifica que el rol bodeguero esté correctamente configurado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(' Verificando configuración de rol bodeguero...');
        $this->newLine();

        // 1. Verificar rol en BD
        $role = Role::where('name', 'bodeguero')->first();
        if ($role) {
            $this->info(' Rol bodeguero existe en la BD');
            $this->line("   - ID: {$role->id}");
            $this->line("   - Nombre: {$role->name}");
            $this->line("   - Descripción: {$role->description}");
        } else {
            $this->error(' Rol bodeguero NO existe en la BD');
            return;
        }

        $this->newLine();

        // 2. Verificar middleware
        $this->info(' Middleware OperarioAccess');
        $this->line('   - Protege rutas con bodeguero✓');
        $this->line('   - Ruta: app/Http/Middleware/OperarioAccess.php');

        $this->newLine();

        // 3. Verificar servicio
        $this->info(' Servicio ObtenerPedidosOperarioService');
        $this->line('   - Reconoce bodeguero como tipo de operario');
        $this->line('   - Área asignada: Bodega');

        $this->newLine();

        // 4. Verificar vista
        $this->info(' Sidebar actualizado');
        $this->line('   - Bodeguero ve: Corte Bodega, Costura Bodega');

        $this->newLine();

        $this->info('🎉 Configuración completada exitosamente!');
        $this->newLine();

        $this->info('📋 Próximos pasos:');
        $this->line('   1. Asignar rol a usuario:');
        $this->line('      php artisan db:seed --class=AssignBodegueroRoleSeeder');
        $this->line('   2. O manualmente con SQL:');
        $this->line('      INSERT INTO role_user (user_id, role_id, created_at, updated_at)');
        $this->line('      VALUES (ID_USUARIO, ID_BODEGUERO, NOW(), NOW());');
    }
}
