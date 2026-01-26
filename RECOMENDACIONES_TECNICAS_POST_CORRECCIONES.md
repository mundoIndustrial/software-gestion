# RECOMENDACIONES TÉCNICAS Y ARQUITECTURA

**Sistema:** Laravel DDD + CQRS  
**Fecha:** 26 de Enero, 2026  
**Alcance:** Mejoras de robustez y mantenibilidad post-correcciones

---

## 🏗️ ARQUITECTURA ACTUAL

```
┌─────────────────────────────────────────────┐
│ Frontend (Blade + JavaScript)               │
├─────────────────────────────────────────────┤
│ Controllers (AsesoresController, etc.)      │
│ ↓                                           │
│ UseCases (ObtenerFacturaUseCase, etc.)     │
│ ↓                                           │
│ Repositories (PedidoProduccionRepository)  │
│ ↓                                           │
│ Models & Database                          │
└─────────────────────────────────────────────┘
```

### Flujo de Datos - Factura

```
Cliente solicita factura
    ↓
AsesoresController::obtenerDatosFactura()
    ↓
ObtenerFacturaUseCase::ejecutar()
    ↓
PedidoProduccionRepository::obtenerDatosFactura()
    ├─ Prendas
    │  ├─ Tallas (pedidos_procesos_prenda_tallas) CORREGIDO
    │  ├─ Procesos
    │  └─ Imágenes
    └─ EPPs
       ├─ Validación defensiva CORREGIDO
       ├─ Imágenes
       └─ Tallas
    ↓
Response JSON → Frontend → Vista Factura
```

---

## ⚡ PROBLEMAS IDENTIFICADOS Y SOLUCIONADOS

### 1. Desalineación Tabla Legacy vs Actual

**Problema:**
- Sistema creado con dos versiones de tallas
- Legacy: `prenda_pedido_tallas` (por prenda, sin procesos)
- Actual: `pedidos_procesos_prenda_tallas` (por proceso)
- Código aún consultaba tabla legacy → 0 cantidades

**Solución Implementada:**
```php
//  ANTES
DB::table('prendas_pedido_tallas')

// DESPUÉS
DB::table('pedidos_procesos_prenda_tallas as pppt')
    ->join('procesos_prenda_detalle as ppd', ...)
    ->join('prendas_pedido as pp', ...)
```

**Lección:**
-  Necesario limpiar totalmente tabla legacy o documentar su uso
-  Los cálculos deben estar siempre centrados en tabla actual

---

### 2. Parámetros Desincronizados en JavaScript

**Problema:**
- Firma: `editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes)`
- Llamada: `editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)`
- Variables `codigo` y `categoria` no definidas → ReferenceError

**Solución Implementada:**
```javascript
//  ANTES
editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes) {
    this.stateManager.setProductoSeleccionado({ id, nombre, codigo, categoria });
}

// DESPUÉS
editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    this.stateManager.setProductoSeleccionado({ id, nombre, codigo, categoria });
}
```

**Lección:**
-  Firmas de función y llamadas deben estar sincronizadas
-  Usar linter JS + IDE para detectar parámetros indefinidos
-  Los parámetros deben ser documentados explícitamente

---

### 3. Falta de Validación Defensiva en EPP

**Problema:**
- Si `$pedidoEpp->epp` es null (relación no cargada), código falla
- Si `tallas_medidas` es null, puede haber error
- Si imagen no existe, silencio

**Solución Implementada:**
```php
//  ANTES
$epp = $pedidoEpp->epp;
$eppFormato = [
    'nombre' => $epp->nombre_completo ?? '',  // Falla si $epp null
];

// DESPUÉS
$epp = $pedidoEpp->epp;

if (!$epp) {
    \Log::warning('[FACTURA] EPP sin relación válida, saltando', [
        'pedido_epp_id' => $pedidoEpp->id,
    ]);
    continue;
}

$eppFormato = [
    'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',  // Dos niveles fallback
];

try {
    // Procesamiento de imágenes
} catch (\Exception $e) {
    \Log::warning('[FACTURA] Error imágenes', [
        'error' => $e->getMessage(),
    ]);
}
```

**Lección:**
-  Siempre validar relaciones antes de usar propiedades
-  Multiple fallbacks para campos opcionales
-  Graceful degradation > fatal errors

---

## 🛡️ MEJORAS DE ROBUSTEZ

### 1. Validación de Relaciones

**Actual (POST-CORRECCIÓN):**
```php
if (!$epp) {
    \Log::warning('[FACTURA] EPP sin relación válida');
    continue;
}
```

**Mejorable A:**
```php
// Usar scope de relación eager-loaded
public function obtenerDatosFactura(int $pedidoId): array
{
    $pedido = $this->obtenerPorId($pedidoId)
        ->load('prendas', 'prendas.procesos', 'prendas.procesos.tallas', 
               'epps', 'epps.epp', 'epps.epp.categoria');
    
    // Ahora todas las relaciones están hydratadas
}
```

**Beneficio:**
- Evita N+1 queries
- Relaciones garantizadas no null (si existen)
- Mejor performance

---

### 2. Validación de Integridad de Tallas

**Recomendación:**
```php
private function validarTallasConsistencia(int $pedidoId): array
{
    // Verificar que existe al menos un registro en tabla actual
    $tallasActuales = DB::table('pedidos_procesos_prenda_tallas as pppt')
        ->join('procesos_prenda_detalle as ppd', ...)
        ->where('pp.pedido_produccion_id', $pedidoId)
        ->count();
    
    // Verificar que tabla legacy está vacía
    $tallasLegacy = DB::table('prenda_pedido_tallas')
        ->whereIn('prenda_pedido_id', function($q) use ($pedidoId) {
            $q->select('id')->from('prendas_pedido')
              ->where('pedido_produccion_id', $pedidoId);
        })
        ->count();
    
    return [
        'tiene_tallas_actuales' => $tallasActuales > 0,
        'tiene_tallas_legacy' => $tallasLegacy > 0,
        'es_consistente' => !($tallasActuales > 0 && $tallasLegacy > 0),
    ];
}
```

---

### 3. Type Hints Explícitos (PHP)

**Actual:**
```php
$cantidad = DB::table(...)->value('total');
return (int) $cantidad ?? 0;
```

**Mejorable A:**
```php
private function calcularCantidadTotalPrendas(int $pedidoId): int
{
    $cantidad = DB::table(...)->value('total');
    return intval($cantidad) ?? 0;  // O: (int) $cantidad
}
```

---

### 4. Type Hints Explícitos (JavaScript)

**Actual:**
```javascript
editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    // Sin validación de tipos
}
```

**Mejorable A (JSDoc):**
```javascript
/**
 * Editar EPP desde formulario
 * @param {number} id - ID del EPP
 * @param {string} nombre - Nombre del EPP
 * @param {string} codigo - Código del EPP
 * @param {string} categoria - Categoría del EPP
 * @param {number} cantidad - Cantidad de unidades
 * @param {string} observaciones - Observaciones adicionales
 * @param {Array<Object>} imagenes - Array de imágenes
 * @returns {void}
 */
editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    // Ahora es claro qué espera cada parámetro
}
```

---

## 📊 TESTING STRATEGY

### Unit Tests (PHP)

```php
// tests/Feature/CalculoCantidadesTest.php
public function test_calcula_cantidades_desde_procesos()
{
    $pedido = PedidoFactory::with('prendas')->with('procesos')->create();
    
    $cantidad = $this->controller->calcularCantidadTotalPrendas($pedido->id);
    
    $this->assertGreaterThan(0, $cantidad);
}

public function test_factura_con_epp_sin_error()
{
    $pedido = PedidoFactory::with('epps')->create();
    
    $datos = $this->repository->obtenerDatosFactura($pedido->id);
    
    $this->assertIsArray($datos['epps']);
    $this->assertGreaterThan(0, $datos['total_items']);
}
```

### Integration Tests (Blade + JavaScript)

```javascript
// tests/Feature/EditarEppTest.js
describe('Editar EPP', () => {
    it('debe abrir modal sin errores', () => {
        const epp = {
            id: 1, nombre: 'Test', codigo: 'EPP001', 
            categoria: 'Protección', cantidad: 5, 
            observaciones: '', imagenes: []
        };
        
        expect(() => {
            window.eppService.editarEPPFormulario(...Object.values(epp));
        }).not.toThrow();
        
        expect(document.getElementById('modal-agregar-epp').classList.contains('active')).toBe(true);
    });
});
```

---

## 🔍 LOGGING STRATEGY

### Niveles de Log Implementados

```
DEBUG   - Operaciones normales (calcular cantidades, procesar tallas)
INFO    - Eventos importantes (prenda procesada, EPP agregado)
WARNING - Situaciones anómalas (EPP sin relación, tabla legacy usada)
ERROR   - Fallos graves (relación rota, error de BD)
```

### Ejemplo de Logs Esperados (Éxito)

```
[2026-01-26 10:00:00] local.DEBUG: [CrearPedidoEditableController] calcularCantidadTotalPrendas - Éxito 
{
  "pedido_id": 2719,
  "cantidad_total": 30,
  "metodo": "pedidos_procesos_prenda_tallas"
}

[2026-01-26 10:00:01] local.INFO: [FACTURA] Prenda procesada
{
  "nombre": "TRETe",
  "variantes_count": 2,
  "has_manga": true
}

[2026-01-26 10:00:02] local.DEBUG: [FACTURA] EPP procesado
{
  "id": 15,
  "nombre": "Casco de Protección",
  "cantidad": 10
}
```

### Ejemplo de Logs (Problemas)

```
[2026-01-26 10:00:00] local.WARNING: [FACTURA] EPP sin relación válida, saltando
{
  "pedido_epp_id": 999,
  "pedido_id": 2719
}

[2026-01-26 10:00:01] local.WARNING: [FACTURA] Error obteniendo imágenes de EPP
{
  "pedido_epp_id": 15,
  "error": "Table 'pedido_epp_imagenes' doesn't exist"
}
```

---

## 📈 PERFORMANCE CONSIDERATIONS

### Query Optimization (Actual)

**La query de cálculo de cantidades:**
```php
SELECT COALESCE(SUM(pppt.cantidad), 0) as total
FROM pedidos_procesos_prenda_tallas pppt
INNER JOIN procesos_prenda_detalle ppd ON pppt.proceso_prenda_detalle_id = ppd.id
INNER JOIN prendas_pedido pp ON ppd.prenda_pedido_id = pp.id
WHERE pp.pedido_produccion_id = ?
```

**Índices recomendados:**
```sql
-- Si no existen
CREATE INDEX idx_pppt_proceso_prenda_detalle_id 
ON pedidos_procesos_prenda_tallas(proceso_prenda_detalle_id);

CREATE INDEX idx_ppd_prenda_pedido_id 
ON procesos_prenda_detalle(prenda_pedido_id);

CREATE INDEX idx_pp_pedido_produccion_id 
ON prendas_pedido(pedido_produccion_id);
```

**Verificar índices actuales:**
```sql
SHOW INDEXES FROM pedidos_procesos_prenda_tallas;
SHOW INDEXES FROM procesos_prenda_detalle;
SHOW INDEXES FROM prendas_pedido;
```

---

## 🔐 SEGURIDAD

### Validación de Entrada

**Actual (POST-CORRECCIÓN):**
```php
public function obtenerDatosFactura($id)
{
    $dto = ObtenerFacturaDTO::fromRequest((string)$id);
    // ID se convierte a string, luego int en DTO
}
```

**Validar que es seguro:**
- Input casting a `int` desde ruta
- DTO valida el pedido existe (implícito)
- Autorización en middleware (asumir)

---

## 📋 CHECKLIST FINAL

### Antes de Deploy

- [ ] Validación sintáctica PHP COMPLETADO
- [ ] Validación sintáctica JavaScript COMPLETADO
- [ ] Linting (ESLint, Psalm, PHPStan)
- [ ] Unit tests pasando
- [ ] Integration tests pasando
- [ ] Database migrations (si aplica)
- [ ] Backup de BD previo
- [ ] Logs sin WARNING previos (cleanup)

### Post-Deploy

- [ ] Monitorear logs en `storage/logs/laravel.log`
- [ ] Verificar cálculos de cantidades en algunos pedidos
- [ ] Probar edición de EPP desde interfaz
- [ ] Generar una factura de prueba
- [ ] Verificar que no hay nuevos WARNINGs en logs
- [ ] Rollback plan si hay problemas

---

## 🎯 ROAD MAP FUTURO

### Corto Plazo (1-2 semanas)
1. Agregar eager loading de relaciones en repository
2. Implementar tests automatizados para cálculos
3. Documentar estructura actual de tallas

### Mediano Plazo (1-2 meses)
1. Deprecate y remover tabla `prenda_pedido_tallas` completamente
2. Consolidar toda lógica en `pedidos_procesos_prenda_tallas`
3. Crear migraciones de limpieza
4. Agregar validaciones de integridad en modelos

### Largo Plazo (3-6 meses)
1. Refactorizar modelo de tallas para mayor flexibilidad
2. Implementar versioning de especificaciones
3. Crear audit trail de cambios en tallas
4. Agregar eventos DDD para cambios de tallas

---

## 📚 REFERENCIAS

### Archivos Modificados
- `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php` (L1384-1410)
- `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php` (L380-457)
- `public/js/modulos/crear-pedido/epp/services/epp-service.js` (L106-132)

### Archivos Relacionados (Lectura)
- `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php` (obtenerDatosRecibos)
- `app/Application/Pedidos/UseCases/ObtenerFacturaUseCase.php`
- `resources/views/asesores/pedidos/components/modal-editar-epp.blade.php`
- `public/js/modulos/crear-pedido/epp/epp-init.js`

### Base de Datos
```
Esquema de tallas (ACTUAL):
pedidos_procesos_prenda_tallas
├── proceso_prenda_detalle_id
├── genero
├── talla
└── cantidad

Esquema de tallas (LEGACY - NO USAR):
prenda_pedido_tallas
├── prenda_pedido_id
├── genero
├── talla
└── cantidad
```

---

**Documento Generado:** 2026-01-26  
**Versión:** 1.0  
**Autor:** Sistema de Auditoría Automática  
**Próxima Revisión:** 2026-02-26 (evaluación de mejoras)
