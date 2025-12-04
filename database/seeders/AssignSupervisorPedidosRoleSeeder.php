<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AssignSupervisorPedidosRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Este seeder asigna el rol supervisor_pedidos a usuarios específicos.
     * Modifica los IDs según sea necesario.
     */
    public function run(): void
    {
        // Obtener el ID del rol supervisor_pedidos
        $roleId = DB::table('roles')
            ->where('name', 'supervisor_pedidos')
            ->value('id');

        if (!$roleId) {
            $this->command->error('❌ El rol "supervisor_pedidos" no existe. Ejecuta primero: php artisan db:seed --class=SupervisorPedidosRoleSeeder');
            return;
        }

        // Obtener el primer usuario (admin o el que sea)
        $user = User::first();

        if ($user) {
            // Asignar rol_id (rol principal)
            $user->role_id = $roleId;

            // Asignar también en roles_ids (array de múltiples roles)
            $rolesIds = $user->roles_ids ?? [];
            if (!in_array($roleId, $rolesIds)) {
                $rolesIds[] = $roleId;
            }
            $user->roles_ids = $rolesIds;

            $user->save();
            $this->command->info("✅ Rol 'supervisor_pedidos' asignado al usuario: {$user->name} (ID: {$user->id})");
            $this->command->info("   - role_id: {$roleId}");
            $this->command->info("   - roles_ids: " . json_encode($rolesIds));
        } else {
            $this->command->error('❌ No hay usuarios en la base de datos.');
        }
    }
}
