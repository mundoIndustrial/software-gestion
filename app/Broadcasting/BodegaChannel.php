<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class BodegaChannel
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
    public function join(User $user, $numeroPedido, $talla): bool
    {
        // Solo usuarios con rol de bodega pueden unirse a canales de bodega
        // O usuarios con permisos de supervisión
        $hasAccess = $user->hasRole('bodeguero') || 
                    $user->hasRole('Costura-Bodega') || 
                    $user->hasRole('EPP-Bodega') ||
                    $user->hasRole('supervisor') || 
                    $user->hasRole('admin');

        Log::info('BodegaChannel authentication', [
            'user_id' => $user->id,
            'numero_pedido' => $numeroPedido,
            'talla' => $talla,
            'has_access' => $hasAccess,
            'user_roles' => $user->roles->pluck('name')->toArray(),
        ]);

        return $hasAccess;
    }
}
