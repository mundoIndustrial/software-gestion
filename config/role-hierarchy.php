<?php

/**
 * Configuración de Jerarquía de Roles
 * 
 * Define qué roles heredan permisos de otros roles.
 * Un rol que hereda puede acceder a todo lo que su rol padre puede acceder.
 * 
 * Estructura: 'rol_hijo' => ['rol_padre_1', 'rol_padre_2', ...]
 * 
 * Ejemplo:
 *   'supervisor_pedidos' => ['asesor']  // supervisor_pedidos hereda de asesor
 *   'admin' => ['supervisor_pedidos', 'asesor']  // admin hereda de multiple roles
 */
return [
    'hierarchy' => [
        // supervisor_pedidos hereda todos los permisos de asesor
        'supervisor_pedidos' => ['asesor'],
        
        // Agregar más jerarquías según sea necesario:
        // 'gerente' => ['supervisor_pedidos', 'asesor'],
        // 'admin' => ['gerente', 'supervisor_pedidos', 'asesor'],
    ],
];
