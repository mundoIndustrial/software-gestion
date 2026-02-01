/**
 * ENDPOINT API RECOMENDADO - /api/tipos-cotizacion
 * 
 * Estructura de respuesta esperada por CotizacionPrendaConfig
 * 
 * Implementar este endpoint en el backend para sincronizar tipos
 */

// ============================================================================
// CONTROLADOR BACKEND (Laravel) - EJEMPLO
// ============================================================================

/*
En app/Http/Controllers/Api/TiposCotizacionController.php:

<?php

namespace App\Http\Controllers\Api;

use App\Models\TiposCotizacion;
use Illuminate\Http\JsonResponse;

class TiposCotizacionController extends Controller
{
    /**
     * Obtener todos los tipos de cotización
     * Incluye flag de bodega para sincronización con frontend
     */
    public function index(): JsonResponse
    {
        $tipos = TiposCotizacion::select(
            'id',
            'codigo',
            'nombre',
            'descripcion',
            'activo'
        )
        ->where('activo', true)
        ->orderBy('nombre')
        ->get()
        ->map(function ($tipo) {
            return [
                'id'              => $tipo->id,
                'nombre'          => $tipo->nombre,
                'codigo'          => $tipo->codigo,
                'descripcion'     => $tipo->descripcion,
                'requiere_bodega' => in_array(
                    $tipo->nombre,
                    ['Reflectivo', 'Logo']
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $tipos,
            'message' => 'Tipos de cotización obtenidos correctamente'
        ]);
    }

    /**
     * Obtener un tipo específico
     */
    public function show(int $id): JsonResponse
    {
        $tipo = TiposCotizacion::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $tipo->id,
                'nombre'          => $tipo->nombre,
                'codigo'          => $tipo->codigo,
                'descripcion'     => $tipo->descripcion,
                'requiere_bodega' => in_array(
                    $tipo->nombre,
                    ['Reflectivo', 'Logo']
                ),
            ]
        ]);
    }
}

// En routes/api.php:
Route::get('/tipos-cotizacion', [TiposCotizacionController::class, 'index']);
Route::get('/tipos-cotizacion/{id}', [TiposCotizacionController::class, 'show']);

*/

// ============================================================================
// ESTRUCTURA DE RESPUESTA JSON
// ============================================================================

// Respuesta exitosa
const RESPUESTA_EXITOSA = {
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Reflectivo",
            "codigo": "REF",
            "descripcion": "Cotización para prendas reflectivas de seguridad",
            "requiere_bodega": true
        },
        {
            "id": 2,
            "nombre": "Logo",
            "codigo": "LOG",
            "descripcion": "Cotización para prendas con logo personalizado",
            "requiere_bodega": true
        },
        {
            "id": 3,
            "nombre": "Estándar",
            "codigo": "EST",
            "descripcion": "Cotización estándar de confección",
            "requiere_bodega": false
        },
        {
            "id": 4,
            "nombre": "Bordado Premium",
            "codigo": "BRD",
            "descripcion": "Cotización para prendas con bordado",
            "requiere_bodega": false
        }
    ],
    "message": "Tipos de cotización obtenidos correctamente"
};

// ============================================================================
// ESTRUCTURA DE MIGRACIÓN LARAVEL
// ============================================================================

/*
Database/Migrations/YYYY_MM_DD_hhmmss_create_tipos_cotizacion_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insertar tipos base
        DB::table('tipos_cotizacion')->insert([
            [
                'codigo'      => 'REF',
                'nombre'      => 'Reflectivo',
                'descripcion' => 'Prendas con material reflectivo',
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'codigo'      => 'LOG',
                'nombre'      => 'Logo',
                'descripcion' => 'Prendas con logo personalizado',
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'codigo'      => 'EST',
                'nombre'      => 'Estándar',
                'descripcion' => 'Cotización estándar',
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cotizacion');
    }
};

*/

// ============================================================================
// INICIALIZACIÓN EN HTML
// ============================================================================

/*
En blade template (resources/views/crear-pedido.blade.php):

<!DOCTYPE html>
<html>
<head>
    <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
    <script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>
</head>
<body>
    <!-- Contenido... -->

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Opción 1: Inicialización inteligente (recomendado)
            await CotizacionPrendaConfig.inicializarConRetroalimentacion();

            // Opción 2: O inicializar directamente desde API
            // await CotizacionPrendaConfig.inicializarDesdeAPI();

            // Iniciar sincronización automática cada 5 minutos
            const syncId = CotizacionPrendaConfig.iniciarSincronizacionAutomatica(300000);

            // Al descargar (cleanup)
            window.addEventListener('beforeunload', () => {
                CotizacionPrendaConfig.detenerSincronizacionAutomatica(syncId);
            });

            // Ver estado
            CotizacionPrendaConfig.mostrarEstado();
        });
    </script>
</body>
</html>

*/

// ============================================================================
// INICIALIZACIÓN DESDE OBJETO (SIN API)
// ============================================================================

/*
Si no tienes endpoint API disponible, puedes pasar los datos directo:

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tiposDesdeHTML = [
        { id: 1, nombre: 'Reflectivo', requiere_bodega: true },
        { id: 2, nombre: 'Logo', requiere_bodega: true },
        { id: 3, nombre: 'Estándar', requiere_bodega: false },
    ];

    CotizacionPrendaConfig.inicializarDesdeObjeto(tiposDesdeHTML);
    CotizacionPrendaConfig.mostrarEstado();
});
</script>

*/

// ============================================================================
// TESTING DE LA INTEGRACIÓN
// ============================================================================

/*
En consola del navegador después de cargar la página:

// Ver tipos actuales
CotizacionPrendaConfig.mostrarEstado();

// Simular agregar un tipo nuevo
CotizacionPrendaHandler.registrarTipoBodega(5, 'Nuevo Tipo');

// Guardar en localStorage
CotizacionPrendaConfig.guardarEnStorage();

// Verificar que se guardó
localStorage.getItem('tipos-cotizacion-bodega');

// Prueba de origen automático
const testPrenda = { nombre: 'Test' };
const testCotizacion = { tipo_cotizacion_id: 'Reflectivo' };
const resultado = CotizacionPrendaHandler.prepararPrendaParaEdicion(
    testPrenda,
    testCotizacion
);
console.log(resultado.origen); // Debería ser 'bodega'

*/

// ============================================================================
// EXPLICACIÓN TÉCNICA
// ============================================================================

/*

¿Por qué esta estructura?

1. **requiere_bodega como flag**:
   - Evita lógica duplicada en frontend y backend
   - Facilita cambios sin recompilar frontend
   - Documenta la intención del tipo de cotización

2. **Respuesta separada por 'data'**:
   - Estándar de API REST
   - Permite agregar metadata (paginación, etc.)
   - Compatible con diferentes frameworks

3. **Sincronización automática**:
   - Mantiene frontend actualizado sin recargar
   - Importante si agregan tipos en tiempo real
   - Usa localStorage como caché

4. **Fallback a valores por defecto**:
   - Si la API falla, el sistema sigue funcionando
   - Los tipos 'Reflectivo' y 'Logo' siempre están disponibles
   - Experiencia robusta para el usuario

*/

// ============================================================================
// QUERIES SQL ÚTILES PARA TESTING
// ============================================================================

/*

-- Ver todos los tipos
SELECT id, nombre, codigo, activo FROM tipos_cotizacion;

-- Ver qué prendas tienen origen 'bodega'
SELECT p.id, p.nombre, p.origen, c.numero_cotizacion, tc.nombre as tipo_cotizacion
FROM prendas p
LEFT JOIN cotizaciones c ON p.cotizacion_id = c.id
LEFT JOIN tipos_cotizacion tc ON c.tipo_cotizacion_id = tc.id
WHERE p.origen = 'bodega';

-- Ver cotizaciones por tipo
SELECT id, numero_cotizacion, tipo_cotizacion_id 
FROM cotizaciones 
WHERE tipo_cotizacion_id IN (
    SELECT id FROM tipos_cotizacion WHERE nombre IN ('Reflectivo', 'Logo')
);

-- Contar prendas por origen en una cotización
SELECT 
    c.numero_cotizacion,
    tc.nombre as tipo,
    COUNT(CASE WHEN p.origen = 'bodega' THEN 1 END) as bodega,
    COUNT(CASE WHEN p.origen = 'confeccion' THEN 1 END) as confeccion,
    COUNT(*) as total
FROM cotizaciones c
LEFT JOIN tipos_cotizacion tc ON c.tipo_cotizacion_id = tc.id
LEFT JOIN prendas p ON c.id = p.cotizacion_id
GROUP BY c.id, c.numero_cotizacion, tc.nombre;

*/
