<?php

/**
 * CONFIGURACIÓN: Sistema de Cálculo de Días
 * 
 * Este archivo define la configuración para el cálculo de días hábiles.
 * Se puede publicar en config/dias.php para hacer cambios sin afectar el código.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Cálculo de Días Hábiles
    |--------------------------------------------------------------------------
    |
    | Configuración para el cálculo automático de días en procesos.
    |
    */

    'habiles' => [
        'enabled' => true,
        
        /**
         * Excluir fines de semana (sábado y domingo)
         */
        'excluir_fines_de_semana' => true,
        
        /**
         * Excluir festivos nacionales
         */
        'excluir_festivos' => true,
        
        /**
         * Restar el día de inicio del cálculo (como en tabla_original)
         */
        'restar_dia_inicio' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Festivos Nacionales Fijos
    |--------------------------------------------------------------------------
    |
    | Define los festivos fijos que se excluyen del cálculo.
    | Formato: 'mes-día' => 'Nombre del festivo'
    |
    */

    'festivos_fijos' => [
        '01-01' => 'Año Nuevo',
        '05-01' => 'Día del Trabajo',
        '07-01' => 'Día de la Independencia',
        '07-20' => 'Grito de Independencia',
        '08-07' => 'Batalla de Boyacá',
        '12-08' => 'Inmaculada Concepción',
        '12-25' => 'Navidad',
    ],

    /**
     * Festivos movibles por año
     * Se pueden agregar manualmente o calcular dinámicamente
     */
    'festivos_movibles' => [
        // Ejemplo: 2025 => [
        //     '04-18' => 'Viernes Santo',
        //     '05-29' => 'Ascensión',
        // ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Caché
    |--------------------------------------------------------------------------
    |
    | Configuración de caché para festivos.
    |
    */

    'cache' => [
        'enabled' => true,
        'driver' => 'file',
        'ttl' => 31536000, // 1 año en segundos
        'key_prefix' => 'festivos_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatos
    |--------------------------------------------------------------------------
    |
    | Formato de salida para días calculados.
    |
    */

    'formatos' => [
        /**
         * Formato singular
         */
        'singular' => '{numero} día',
        
        /**
         * Formato plural
         */
        'plural' => '{numero} días',
        
        /**
         * Separador para listas de procesos
         */
        'separador_lista' => ', ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Procesos (Áreas)
    |--------------------------------------------------------------------------
    |
    | Mapeo de procesos/áreas en el sistema.
    |
    */

    'procesos' => [
        'Creación Orden' => 'Creación Orden',
        'Inventario' => 'Inventario',
        'Insumos y Telas' => 'Insumos y Telas',
        'Corte' => 'Corte',
        'Bordado' => 'Bordado',
        'Estampado' => 'Estampado',
        'Costura' => 'Costura',
        'Reflectivo' => 'Reflectivo',
        'Lavandería' => 'Lavandería',
        'Arreglos' => 'Arreglos',
        'Control Calidad' => 'Control Calidad',
        'Entrega' => 'Entrega',
        'Despacho' => 'Despacho',
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites y Alertas
    |--------------------------------------------------------------------------
    |
    | Configuración para alertas de retraso.
    |
    */

    'alertas' => [
        /**
         * Días permitidos de retraso antes de alertar
         */
        'tolerancia_dias' => 0,
        
        /**
         * Porcentaje de retraso permitido
         */
        'tolerancia_porcentaje' => 10,
        
        /**
         * Alertar si está en retraso
         */
        'alertar_retraso' => true,
        
        /**
         * Alertar si se acerca a la fecha estimada
         */
        'alertar_proximo' => true,
        'dias_anticipacion' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Registrar cálculos y cambios.
    |
    */

    'logging' => [
        'enabled' => false,
        'channel' => 'single',
        'level' => 'debug',
    ],
];
