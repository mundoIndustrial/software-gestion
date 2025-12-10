<?php

/**
 * RUTAS API PARA PRENDAS - Arquitectura Limpia
 * 
 * Agregar estas rutas a: routes/api.php
 */

// ============================================
// RUTAS API PARA PRENDAS (CON AUTENTICACIÓN)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    
    // CRUD de prendas
    Route::apiResource('prendas', PrendaController::class);
    
    // Búsqueda de prendas
    Route::get('prendas/search', [PrendaController::class, 'search']);
    
    // Estadísticas de prendas
    Route::get('prendas/stats', [PrendaController::class, 'estadisticas']);
    
});

// ============================================
// ENDPOINTS DISPONIBLES
// ============================================

/**
 * GET /api/prendas
 * Listar prendas con paginación
 * 
 * Query Parameters:
 * - page: número de página (default: 1)
 * - per_page: items por página (default: 15)
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": 1,
 *       "nombre_producto": "Camisa Drill",
 *       "descripcion": "Camisa de trabajo",
 *       "tipo_prenda": {...},
 *       "genero": {...},
 *       "tallas": [...],
 *       "variantes": [...],
 *       "fotos": [...]
 *     }
 *   ],
 *   "pagination": {
 *     "total": 50,
 *     "per_page": 15,
 *     "current_page": 1,
 *     "last_page": 4
 *   }
 * }
 */

/**
 * POST /api/prendas
 * Crear nueva prenda
 * 
 * Content-Type: multipart/form-data
 * 
 * Body Parameters:
 * - nombre_producto: string (required)
 * - descripcion: string (required)
 * - tipo_prenda: string (required) - CAMISA, PANTALON, etc.
 * - genero: string (optional)
 * - tallas[]: array (required) - XS, S, M, L, XL, XXL, XXXL, XXXXL
 * - variantes[n][tipo_manga_id]: integer (optional)
 * - variantes[n][tipo_broche_id]: integer (optional)
 * - variantes[n][tiene_bolsillos]: boolean (optional)
 * - variantes[n][tiene_reflectivo]: boolean (optional)
 * - telas[n][nombre]: string (required)
 * - telas[n][referencia]: string (required)
 * - telas[n][color]: string (required)
 * - telas[n][foto]: file (optional)
 * - fotos[n][archivo]: file (required)
 * - fotos[n][tipo]: string (required) - foto_prenda, foto_tela
 * 
 * Response: 201 Created
 * {
 *   "success": true,
 *   "data": {...},
 *   "message": "Prenda creada exitosamente"
 * }
 */

/**
 * GET /api/prendas/{id}
 * Obtener prenda por ID
 * 
 * Response: 200 OK
 * {
 *   "success": true,
 *   "data": {
 *     "id": 1,
 *     "nombre_producto": "Camisa Drill",
 *     "descripcion": "Camisa de trabajo",
 *     "tipo_prenda": {...},
 *     "genero": {...},
 *     "tallas": [...],
 *     "variantes": [...],
 *     "fotos": [...]
 *   }
 * }
 */

/**
 * PUT /api/prendas/{id}
 * Actualizar prenda
 * 
 * Content-Type: multipart/form-data
 * 
 * Body: Mismo que POST /api/prendas
 * 
 * Response: 200 OK
 * {
 *   "success": true,
 *   "data": {...},
 *   "message": "Prenda actualizada exitosamente"
 * }
 */

/**
 * DELETE /api/prendas/{id}
 * Eliminar prenda
 * 
 * Response: 200 OK
 * {
 *   "success": true,
 *   "message": "Prenda eliminada exitosamente"
 * }
 */

/**
 * GET /api/prendas/search?q=camisa
 * Buscar prendas por término
 * 
 * Query Parameters:
 * - q: término de búsqueda (required)
 * - page: número de página (default: 1)
 * - per_page: items por página (default: 15)
 * 
 * Response: 200 OK
 * {
 *   "success": true,
 *   "data": [...],
 *   "pagination": {...}
 * }
 */

/**
 * GET /api/prendas/stats
 * Obtener estadísticas de prendas
 * 
 * Response: 200 OK
 * {
 *   "success": true,
 *   "data": {
 *     "total": 50,
 *     "activas": 45,
 *     "inactivas": 5,
 *     "por_tipo": [
 *       {
 *         "tipo_prenda_id": 1,
 *         "cantidad": 15
 *       }
 *     ]
 *   }
 * }
 */

// ============================================
// CÓDIGOS DE RESPUESTA HTTP
// ============================================

/**
 * 200 OK - Solicitud exitosa
 * 201 Created - Recurso creado exitosamente
 * 400 Bad Request - Datos inválidos
 * 401 Unauthorized - No autenticado
 * 403 Forbidden - No autorizado
 * 404 Not Found - Recurso no encontrado
 * 422 Unprocessable Entity - Error de validación
 * 500 Internal Server Error - Error del servidor
 */

// ============================================
// ESTRUCTURA DE RESPUESTA DE ERROR
// ============================================

/**
 * {
 *   "success": false,
 *   "message": "Error al crear prenda: ...",
 *   "errors": {
 *     "nombre_producto": ["El nombre es requerido"],
 *     "tallas": ["Debe seleccionar al menos una talla"]
 *   }
 * }
 */

// ============================================
// EJEMPLO CURL
// ============================================

/**
 * curl -X POST http://localhost:8000/api/prendas \
 *   -H "Authorization: Bearer {token}" \
 *   -H "Content-Type: multipart/form-data" \
 *   -F "nombre_producto=Camisa Drill" \
 *   -F "descripcion=Camisa de trabajo en drill" \
 *   -F "tipo_prenda=CAMISA" \
 *   -F "genero=dama" \
 *   -F "tallas[]=M" \
 *   -F "tallas[]=L" \
 *   -F "variantes[0][tipo_manga_id]=1" \
 *   -F "variantes[0][tipo_broche_id]=1" \
 *   -F "variantes[0][tiene_bolsillos]=true" \
 *   -F "telas[0][nombre]=Drill" \
 *   -F "telas[0][referencia]=DR-001" \
 *   -F "telas[0][color]=Azul" \
 *   -F "fotos[0][archivo]=@/path/to/image.jpg" \
 *   -F "fotos[0][tipo]=foto_prenda"
 */
