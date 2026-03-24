<?php

/**
 * Asistencia Personal Routes
 * Rutas públicas y API para asistencia de personal
 */
require base_path('routes/asistencia-personal.php');

/**
 * Ordenes Routes
 * Gestión de órdenes DDD con estados y transiciones
 */
require base_path('routes/ordenes.php');

/**
 * Prendas y Pedidos - Rutas Públicas (Lectura)
 * Consulta de prendas, pedidos y áreas
 */
require base_path('routes/prendas-pedidos-public.php');

/**
 * Prendas y Pedidos - Rutas Protegidas (Escritura)
 * Creación, actualización y eliminación de prendas y pedidos
 */
require base_path('routes/prendas-pedidos-protected.php');

/**
 * Procesos Routes
 * Gestión de procesos de prendas, cambios de estado e imágenes
 */
require base_path('routes/procesos.php');

/**
 * EPP Management Routes
 * Gestión de Equipos de Protección Personal (Web + API)
 */
require base_path('routes/epp.php');

/**
 * Cotizaciones Management Routes
 * Gestión completa de cotizaciones en módulo separado
 */
require base_path('routes/cotizaciones.php');

/**
 * API Routes for Pedidos Editables (DDD - Gestión de Ítems)
 * 
 * Prefix: /api/pedidos-editable
 * Auth: auth, role:asesor
 * Controller: App\Http\Controllers\Asesores\CrearPedidoEditableController
 */
require base_path('routes/api-pedidos-editable.php');

/**
 * Operario API Routes
 * Rutas públicas para consulta de pedidos
 */
require base_path('routes/operario.php');

/**
 * Personal y Horarios Routes
 * Gestión de personal, roles y horarios
 */
require base_path('routes/personal-horarios.php');

/**
 * Artículos Import Routes
 * Importación de artículos y EPP
 */
require base_path('routes/articulos-import.php');


/**
 * Prenda Editor Routes
 * Gestión de edición de prendas, tallas y variaciones
 */
require base_path('routes/prendas-editor.php');

/**
 * Colores por Talla Routes
 * Gestión de asignaciones de colores a tallas
 */
require base_path('routes/colores-por-talla.php');

/**
 * Prenda Entregas Routes
 * Gestión de estado de entrega de prendas
 */
require base_path('routes/prendas-entregas.php');

/**
 * Usuarios Routes
 * Gestión de usuarios por rol
 */
require base_path('routes/usuarios.php');

/**
 * Insumos Routes
 * Gestión de insumos y cálculo de demoras
 */
require base_path('routes/insumos.php');
