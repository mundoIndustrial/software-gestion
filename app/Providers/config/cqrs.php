<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CQRS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el patrón Command Query Responsibility Segregation
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Query Cache TTL
    |--------------------------------------------------------------------------
    |
    | Tiempo en segundos que los resultados de queries permanecen en cache.
    | Por defecto: 300 segundos (5 minutos)
    |
    */
    'cache_ttl' => env('CQRS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Enable Query Cache
    |--------------------------------------------------------------------------
    |
    | Habilitar o deshabilitar el cache de queries.
    | En desarrollo, recomendamos deshabilitarlo para ver datos en tiempo real.
    |
    */
    'enable_query_cache' => env('CQRS_ENABLE_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Command Validation
    |--------------------------------------------------------------------------
    |
    | Habilitar validación automática de commands antes de ejecutarlos.
    |
    */
    'enable_command_validation' => env('CQRS_ENABLE_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | Query Validation
    |--------------------------------------------------------------------------
    |
    | Habilitar validación automática de queries antes de ejecutarlas.
    |
    */
    'enable_query_validation' => env('CQRS_ENABLE_QUERY_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | Event Logging
    |--------------------------------------------------------------------------
    |
    | Habilitar logging de eventos de dominio generados por commands.
    |
    */
    'enable_event_logging' => env('CQRS_ENABLE_EVENT_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Habilitar monitoreo de performance para commands y queries.
    | Útil para identificar operaciones lentas.
    |
    */
    'enable_performance_monitoring' => env('CQRS_ENABLE_PERFORMANCE_MONITORING', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Tags utilizados para organizar el cache de queries.
    | Permite limpiar cache por categorías.
    |
    */
    'cache_tags' => [
        'pedidos' => 'cqrs:pedidos',
        'estadisticas' => 'cqrs:estadisticas',
        'areas' => 'cqrs:areas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuración de rate limiting para prevenir abuso de las APIs.
    |
    */
    'rate_limiting' => [
        'enabled' => env('CQRS_RATE_LIMITING', false),
        'requests_per_minute' => env('CQRS_RATE_LIMIT', 60),
        'burst_limit' => env('CQRS_BURST_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | En modo debug, se incluye información adicional en las respuestas
    | como query IDs, tiempos de ejecución, y metadata.
    |
    */
    'debug_mode' => env('CQRS_DEBUG', env('APP_DEBUG', false)),
];
