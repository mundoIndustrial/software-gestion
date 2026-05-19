<?php

namespace App\Domain\Operario\Services;

interface OperarioPrendasRecibosReadService
{
    public function obtenerPrendasConRecibos(\App\Models\User $usuario, ?string $filtroRecibo = null): \Illuminate\Support\Collection;

    public function obtenerPrendasConRecibosTodosCostura(): \Illuminate\Support\Collection;
    
    public function obtenerPrendasConRecibosBodegaCortador(\App\Models\User $usuario): \Illuminate\Support\Collection;
    
    public function obtenerConteoPrendasConRecibosBodegaCortador(\App\Models\User $usuario): int;
}
