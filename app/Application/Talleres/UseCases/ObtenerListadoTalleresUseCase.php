<?php

namespace App\Application\Talleres\UseCases;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ObtenerListadoTalleresUseCase
{
    public function execute($search = null, $perPage = 9, $activo = null)
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

        if ($activo !== null) {
            if ($activo == 1) {
                $query->where(function($q) {
                    $q->where('taller_config.activo', 1)
                      ->orWhereNull('taller_config.activo');
                });
            } else {
                $query->where('taller_config.activo', 0);
            }
        }

        return $query->orderBy('users.name', 'asc')->paginate($perPage);
    }
}
