<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;

class CreateBodegaRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bodega:create-roles';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Crear los roles de bodega: Costura-Bodega y EPP-Bodega';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roles = [
            [
                'name' => 'Costura-Bodega',
                'description' => 'Bodeguero encargado del área de costura',
            ],
            [
                'name' => 'EPP-Bodega',
                'description' => 'Bodeguero encargado del área de EPP',
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                ['description' => $roleData['description']]
            );
            $this->info("✓ Rol '{$roleData['name']}' creado o ya existente");
        }

        $this->info("\n✅ Todos los roles de bodega han sido creados exitosamente");
    }
}
