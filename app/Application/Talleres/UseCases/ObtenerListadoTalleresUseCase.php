<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ObtenerListadoTalleresUseCase
{
    public function execute()
    {
        $roleTaller = Role::where('name', 'taller')->first();
        
        if (!$roleTaller) {
            return collect([]);
        }

        return User::whereJsonContains('roles_ids', $roleTaller->id)
            ->leftJoin('taller_config', 'users.id', '=', 'taller_config.user_id')
            ->select('users.*', DB::raw('IFNULL(taller_config.activo, 1) as activo'))
            ->get();
    }
}
