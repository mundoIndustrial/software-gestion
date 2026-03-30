<?php

/**
 * Asistencia Personal Routes
 * Rutas públicas y API para asistencia de personal
 */
require_once base_path('routes/asistencia-personal.php');

/**
 * Ordenes Routes
 * Gestión de órdenes DDD con estados y transiciones
 */
require_once base_path('routes/ordenes.php');

/**
 * Prendas y Pedidos - Rutas Públicas (Lectura)
 * Consulta de prendas, pedidos y áreas
 */
require_once base_path('routes/prendas-pedidos-public.php');

/**
 * Prendas y Pedidos - Rutas Protegidas (Escritura)
 * Creación, actualización y eliminación de prendas y pedidos
 */
require_once base_path('routes/api-prendas.php');
require_once base_path('routes/api-pedidos-commands.php');

/**
 * Procesos Routes
 * Gestión de procesos de prendas, cambios de estado e imágenes
 */
require_once base_path('routes/procesos.php');

/**
 * EPP Management Routes
 * Gestión de Equipos de Protección Personal (Web + API)
 */
require_once base_path('routes/epp.php');

/**
 * Cotizaciones Management Routes
 * Gestión completa de cotizaciones en módulo separado
 */
require_once base_path('routes/cotizaciones.php');
require_once base_path('routes/logo-cotizacion-tecnicas.php');

/**
 * API Routes for Pedidos (DDD - Gestión de Ítems)
 * Prefix: /api/pedidos
 * Auth: auth, role:asesor
 * Controller: App\Http\Controllers\Asesores\CrearPedidoEditableController
 */
require_once base_path('routes/api-pedidos.php');

/**
 * Personal y Horarios Routes
 * Gestión de personal, roles y horarios
 */
require_once base_path('routes/personal-horarios.php');

/**
 * Artículos Import Routes
 * Importación de artículos y EPP
 */
require_once base_path('routes/articulos-import.php');


/**
 * Prenda Editor Routes
 * Gestión de edición de prendas, tallas y variaciones
 */
require_once base_path('routes/prendas-editor.php');

/**
 * Colores por Talla Routes
 * Gestión de asignaciones de colores a tallas
 */
require_once base_path('routes/colores-por-talla.php');

/**
 * Prenda Entregas Routes
 * Gestión de estado de entrega de prendas
 */
require_once base_path('routes/prendas-entregas.php');

/**
 * Usuarios Routes
 * Gestión de usuarios por rol
 */
require_once base_path('routes/usuarios.php');

/**
 * Auth API Routes (migración gradual web -> api)
 */
require_once base_path('routes/api-auth.php');

/**
 * Supervisor Pedidos API Routes (migración gradual web -> api)
 */
require_once base_path('routes/api-supervisor-pedidos.php');

/**
 * Asesores API Routes (migración gradual web -> api)
 */
require_once base_path('routes/api-asesores.php');

