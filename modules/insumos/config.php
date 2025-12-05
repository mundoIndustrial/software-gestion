<?php

/**
 * Configuración del Módulo Insumos
 * 
 * Este archivo centraliza toda la configuración del módulo insumos
 * incluyendo rutas, namespaces y configuraciones específicas
 */

return [
    'name' => 'Insumos',
    'description' => 'Módulo de gestión de materiales e insumos',
    'version' => '1.0.0',
    
    /**
     * Configuración de rutas
     */
    'routes' => [
        'prefix' => 'insumos',
        'middleware' => ['auth', 'insumos-access'],
    ],

    /**
     * Namespaces
     */
    'namespaces' => [
        'controllers' => 'Modules\\Insumos\\Backend\\Controllers',
        'services' => 'Modules\\Insumos\\Backend\\Services',
        'repositories' => 'Modules\\Insumos\\Backend\\Repositories',
        'models' => 'Modules\\Insumos\\Backend\\Models',
    ],

    /**
     * Rutas de vistas
     */
    'views' => [
        'path' => 'modules.insumos.frontend.views',
        'namespace' => 'insumos',
    ],

    /**
     * Assets
     */
    'assets' => [
        'js' => '/js/modules/insumos',
        'css' => '/css/modules/insumos',
    ],

    /**
     * Permisos y roles
     */
    'permissions' => [
        'view' => 'insumos.view',
        'create' => 'insumos.create',
        'edit' => 'insumos.edit',
        'delete' => 'insumos.delete',
    ],

    /**
     * Estados permitidos
     */
    'estados_permitidos' => [
        'No iniciado',
        'En Ejecución',
        'Anulada',
    ],

    /**
     * Áreas permitidas
     */
    'areas_permitidas' => [
        'Corte',
        'Creación de orden',
        'Creación',
    ],

    /**
     * Columnas permitidas en filtros
     */
    'columnas_filtro' => [
        'numero_pedido',
        'cliente',
        'descripcion',
        'estado',
        'area',
        'fecha_de_creacion_de_orden',
    ],
];
