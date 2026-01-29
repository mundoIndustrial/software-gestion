<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PedidosChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, $asesorId): bool
    {
        // Solo el asesor específico puede unirse a su canal
        // O los usuarios con permisos de supervisión
        $hasAccess = $user->id == $asesorId || 
                    $user->hasRole('supervisor') || 
                    $user->hasRole('admin');

        Log::info('PedidosChannel authentication', [
            'user_id' => $user->id,
            'asesor_id' => $asesorId,
            'has_access' => $hasAccess,
            'user_roles' => $user->roles->pluck('name')->toArray(),
        ]);

        return $hasAccess;
    }
}
