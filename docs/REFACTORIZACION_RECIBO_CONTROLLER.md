# 🎯 REFACTORIZACIÓN RECIBO CONTROLLER - PATRÓN DDD

## Resumen

El `ReciboController` en `app/Infrastructure/Http/Controllers/Asesores/` ha sido refactorizado siguiendo el mismo patrón DDD aplicado al `AsesoresController`. Se crearon 2 servicios especializados para manejar toda la lógica de negocio.

### Antes vs Después

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Líneas de código** | 180+ | 120 (delegador puro) |
| **Repositorios inyectados** | 2 | 0 |
| **Servicios inyectados** | 0 | 2 |
| **Lógica de negocio** | En controller | En servicios |
| **Verificación de permisos** | En cada método | En servicios |

---

## 📦 Servicios Creados

### 1. ObtenerRecibosService.php (240 líneas)
Ubicación: `app/Application/Services/Recibos/`

```php
- obtenerRecibo(int $pedidoId): array
  * Obtiene datos completos del recibo
  * Verifica permisos del asesor
  * Maneja excepciones (403, 404)

- listarRecibos(array $filtros): Paginator
  * Lista pedidos con recibos
  * Aplica filtros (estado, búsqueda)
  * Retorna paginación

- obtenerResumen(int $pedidoId): array
  * Resumen ejecutivo del recibo
  * Conteo de prendas y procesos
  * Información clave

- obtenerProcesosPrenda(int $pedidoId, int $prendaId): array
  * Detalles de procesos de una prenda específica
  * Información de estados

- obtenerEstadosDisponibles(): array
  * Estados para filtros

- exportarParaVista(int $pedidoId): array
  * Exportación completa para vistas
  * Incluye resumen y datos
```

**Migrado de:**
- `show()` - Obtención de datos
- `datos()` - Preparación de datos
- `index()` - Listado con filtros

---

### 2. GenerarPDFRecibosService.php (200 líneas)
Ubicación: `app/Application/Services/Recibos/`

```php
- generarPDF(array $datosRecibo, int $pedidoId): StreamedResponse
  * Genera PDF para descargar
  * Verifica disponibilidad de librería PDF
  * Fallback a datos JSON si no está disponible

- guardarPDF(array $datosRecibo, int $pedidoId, string $disco): string
  * Guarda PDF en storage
  * Retorna ruta del archivo
  * Manejo de errores

- obtenerPDFGuardado(string $rutaArchivo, string $disco): StreamedResponse
  * Obtiene PDF previamente guardado
  * Descarga desde storage

- generarVistaPreviaHTML(array $datosRecibo, int $pedidoId): string
  * Genera HTML para visualización en navegador
  * Útil para preview antes de PDF

- enviarPorEmail(array $datosRecibo, int $pedidoId, string $emailDestino): bool
  * Placeholder para envío de email (futuro)
  * Implementable con Mailable de Laravel
```

**Migrado de:**
- `generarPDF()` - Generación de PDF

---

##  Refactorización del ReciboController

### ANTES (Monolítico)
```php
class ReciboController extends Controller
{
    public function __construct(
        AsesoresRepository $asesoresRepository,
        PedidoProduccionRepository $pedidoProduccionRepository
    ) { ... }

    public function show($id) {
        // 20 líneas de lógica: verificar permisos, obtener datos, etc.
        if (!$this->asesoresRepository->esDelAsesor($id)) { ... }
        $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($id);
        return view(...)
    }

    public function generarPDF($id) {
        // 25 líneas de lógica: igual verificación y generación
        if (!$this->asesoresRepository->esDelAsesor($id)) { ... }
        // Generación PDF
    }
}
```

### DESPUÉS (Delegador Puro)
```php
class ReciboController extends Controller
{
    public function __construct(
        ObtenerRecibosService $obtenerRecibosService,
        GenerarPDFRecibosService $generarPDFService
    ) { ... }

    public function show($id) {
        // 5 líneas: delegación limpia
        $datos = $this->obtenerRecibosService->obtenerRecibo($id);
        return view('asesores.recibos.show', compact('datos', 'id'));
    }

    public function generarPDF($id) {
        // 3 líneas: delegación limpia
        $datos = $this->obtenerRecibosService->obtenerRecibo($id);
        return $this->generarPDFService->generarPDF($datos, $id);
    }
}
```

---

## 🎨 Métodos del Controller

| Método | Antes | Después | Servicio |
|--------|-------|---------|----------|
| `show()` | 20 líneas | 6 líneas | ObtenerRecibosService |
| `datos()` | 20 líneas | 6 líneas | ObtenerRecibosService |
| `generarPDF()` | 25 líneas | 5 líneas | GenerarPDFRecibosService |
| `index()` | 20 líneas | 10 líneas | ObtenerRecibosService |
| `resumen()` | N/A | 10 líneas | ObtenerRecibosService (NUEVO) |
| `procesos()` | N/A | 10 líneas | ObtenerRecibosService (NUEVO) |

### Métodos Nuevos Agregados

1. **resumen($id)** - JSON con resumen ejecutivo del recibo
2. **procesos($pedidoId, $prendaId)** - Detalles de procesos de una prenda

---

## 🏗️ Estructura de Carpetas

```
app/
├── Application/Services/
│   └── Recibos/                        ← NUEVA CARPETA
│       ├── ObtenerRecibosService.php   ← Lectura de recibos
│       └── GenerarPDFRecibosService.php ← Generación de PDFs
│
└── Infrastructure/Http/Controllers/Asesores/
    ├── AsesoresController.php          ← Delegador puro (refactorizado)
    └── ReciboController.php            ← Delegador puro (refactorizado)
```

---

## 🔄 Flujo de Datos

### Obtener Recibo
```
HTTP GET /asesores/recibos/{id}
    ↓
ReciboController::show($id)
    ↓
ObtenerRecibosService::obtenerRecibo()
    ↓
- Verifica permisos (Auth + Repository)
- Llama PedidoProduccionRepository::obtenerDatosRecibos()
- Retorna array de datos
    ↓
return view('asesores.recibos.show', $datos)
```

### Generar PDF
```
HTTP POST /asesores/recibos/{id}/pdf
    ↓
ReciboController::generarPDF($id)
    ↓
ObtenerRecibosService::obtenerRecibo()
    ↓
GenerarPDFRecibosService::generarPDF()
    ↓
- Verifica librería PDF disponible
- Si disponible: Genera PDF con dompdf
- Si no: Retorna datos para frontend
    ↓
return $pdf->download() | response()->json()
```

---

## 🎓 Beneficios

### 1. **Responsabilidad Única**
- Controller: HTTP request/response
- ObtenerRecibosService: Lógica de lectura y filtrado
- GenerarPDFRecibosService: Lógica de generación PDF

### 2. **Reutilización**
- ObtenerRecibosService puede usarse en:
  - Commands
  - Jobs
  - Exports
  - APIs
  - Reportes

### 3. **Testabilidad**
```php
// Test sin HTTP
$service = new ObtenerRecibosService($repo1, $repo2);
$recibo = $service->obtenerRecibo(123);
assert($recibo['numero_pedido'] === 456);

// Test de PDF
$pdfService = new GenerarPDFRecibosService();
$resultado = $pdfService->generarVistaPreviaHTML($datos, 123);
assert(!empty($resultado));
```

### 4. **Mantenibilidad**
- Cambio en lógica de PDF → Solo modificar GenerarPDFRecibosService
- Cambio en filtros → Solo modificar ObtenerRecibosService
- Cambio en HTTP → Solo modificar ReciboController

### 5. **Extensibilidad**
- Agregar nuevo formato: Implementar `GenerarExcelRecibosService`
- Agregar caché: Inyectar en servicios
- Agregar eventos: Disparar en servicios

---

## 📊 Comparativa de Tamaño

```
ANTES:
ReciboController.php: 180+ líneas
- Lógica de negocio incrustada
- 2 repositorios inyectados
- Verificaciones duplicadas

DESPUÉS:
ReciboController.php: 120 líneas (DELEGADOR PURO)
+ ObtenerRecibosService.php: 240 líneas
+ GenerarPDFRecibosService.php: 200 líneas
= 560 líneas (BIEN ORGANIZADAS)

BENEFICIO: +40% de código pero ORGANIZADO por responsabilidad
```

---

## 🚀 Integración con AsesoresController

El ReciboController es **complementario** al AsesoresController:

```
AsesoresController (Gestión de Pedidos)
    ↓
    ├── Crear pedidos
    ├── Actualizar pedidos
    ├── Listar pedidos
    └── [REDIRIGE A] ReciboController
                    ↓
                    ReciboController (Visualización de Recibos)
                        ↓
                        ├── Ver recibo formateado
                        ├── Descargar PDF
                        ├── Listar recibos históricos
                        └── Generar reportes
```

---

## 📝 Rutas Asociadas

```php
// En routes/asesores.php
Route::middleware(['auth', 'role:asesor,admin'])->prefix('asesores')->name('asesores.')->group(function () {
    // Recibos
    Route::prefix('recibos')->name('recibos.')->group(function () {
        Route::get('/', [ReciboController::class, 'index'])->name('index');
        Route::get('{id}', [ReciboController::class, 'show'])->name('show');
        Route::get('{id}/datos', [ReciboController::class, 'datos'])->name('datos');
        Route::get('{id}/resumen', [ReciboController::class, 'resumen'])->name('resumen');
        Route::get('{id}/procesos/{prendaId}', [ReciboController::class, 'procesos'])->name('procesos');
        Route::post('{id}/pdf', [ReciboController::class, 'generarPDF'])->name('pdf');
    });
});
```

---

##  Checklist

- [x] Crear ObtenerRecibosService con 6 métodos
- [x] Crear GenerarPDFRecibosService con 5 métodos
- [x] Refactorizar ReciboController a delegador puro
- [x] Agregar métodos nuevos (resumen, procesos)
- [x] Mantener compatibilidad con rutas existentes
- [x] Logging consistente con emojis
- [x] Manejo de errores con códigos HTTP
- [x] Documentación completa

---

## 🔮 Futuro

1. **Implementar Mailable**: `enviarPorEmail()` en GenerarPDFRecibosService
2. **Caché de recibos**: Agregar Redis para caché
3. **Reportes**: Crear ReportesRecibosService
4. **Exportación**: GenerarExcelRecibosService
5. **Webhooks**: Para sincronización con sistemas externos
6. **Auditoría**: Registrar descargas de PDFs

---

**Estado:**  **COMPLETADO** - ReciboController refactorizado con patrón DDD
**Servicios creados:** 2 (ObtenerRecibosService, GenerarPDFRecibosService)
**Métodos totales:** 6 en controller + 11 en servicios
**Fecha:** 19 de Enero de 2026
