<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DespachoChannel
{
    use InteractsWithSockets;

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
    public function join(): bool
    {
        // Canal público - cualquiera con rol de despacho puede unirse
        return auth()->check() && auth()->user()->hasRole(['despacho', 'admin', 'superadmin', 'Despacho']);
    }
}
