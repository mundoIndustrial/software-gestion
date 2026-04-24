<?php

$env = env('APP_ENV', 'production');

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend Feature Flags
    |--------------------------------------------------------------------------
    |
    | Flags versionados en código (sin variables dedicadas en .env).
    | Ajusta aquí el rollout por entorno.
    |
    */
    'insumos_materiales_vite_entry' => match ($env) {
        'production' => true,
        'local', 'development' => true,
        'staging' => false,
        default => false,
    },
];
