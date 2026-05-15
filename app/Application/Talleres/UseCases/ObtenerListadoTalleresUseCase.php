<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ObtenerListadoTalleresUseCase
{
    public function execute($search = null, $perPage = 9)
    {
        $roleTaller = Role::where('name', 'taller')->first();
        
        if (!$roleTaller) {
            return collect([]);
        }

        $query = User::whereJsonContains('roles_ids', $roleTaller->id)
            ->leftJoin('taller_config', 'users.id', '=', 'taller_config.user_id')
            ->select('users.*', DB::raw('IFNULL(taller_config.activo, 1) as activo'));

        if ($search) {
            $query->where('users.name', 'like', '%' . $search . '%');
        }

        return $query->orderBy('users.name', 'asc')->paginate($perPage);
    }
}
