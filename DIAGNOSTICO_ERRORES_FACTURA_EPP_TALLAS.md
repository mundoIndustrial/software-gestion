# DIAGNÓSTICO COMPLETO: Errores de Factura, EPP y Cálculo de Tallas

**Fecha:** 26 de Enero, 2026  
**Sistema:** Laravel + DDD + CQRS  
**Status:** 🔴 3 Problemas Críticos Identificados y Localizados

---

## 📊 RESUMEN EJECUTIVO

| Problema | Severidad | Ubicación | Causa Raíz | Impacto |
|----------|-----------|-----------|-----------|---------|
| **PROBLEMA 1** | 🔴 CRÍTICO | `CrearPedidoEditableController.php` L1384 | Consulta a tabla legacy `prenda_pedido_tallas` vacía | Cantidades = 0, factura rota |
| **PROBLEMA 2** | 🟠 ALTO | `epp-service.js` L106 | Parámetro `codigo` falta en firma | JS error, edición EPP imposible |
| **PROBLEMA 3** | 🟡 MEDIO | `PedidoProduccionRepository.php` L380+ | Manejo incompleto de EPP sin imagen | Posible 500 si EPP vacío |

---

## 🔴 PROBLEMA 1: CÁLCULO DE CANTIDADES INCORRECTO

### Ubicación Exacta
- **Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`
- **Línea:** 1384-1410
- **Método:** `calcularCantidadTotalPrendas(int $pedidoId): int`

### Código Actual (ERRÓNEO)
```php
private function calcularCantidadTotalPrendas(int $pedidoId): int
{
    try {
        // Primero verificar si existen prendas para este pedido
        $prendasCount = DB::table('prendas_pedido')
            ->where('pedido_produccion_id', $pedidoId)
            ->count();
        
        // Si no hay prendas, retornar 0 sin hacer query a prendas_pedido_tallas
        if ($prendasCount === 0) {
            return 0;
        }
        
        //  PROBLEMA: prendas_pedido_tallas NO EXISTE EN FLUJO ACTUAL
        $cantidad = DB::table('prendas_pedido_tallas')
            ->whereIn('prenda_pedido_id', function($query) use ($pedidoId) {
                $query->select('id')
                    ->from('prendas_pedido')
                    ->where('pedido_produccion_id', $pedidoId);
            })
            ->sum('cantidad');

        return (int) $cantidad;  //  SIEMPRE DEVUELVE 0
    } catch (\Exception $e) {
        Log::warning('[CrearPedidoEditableController] calcularCantidadTotalPrendas - Error', [
            'pedido_id' => $pedidoId,
            'error' => $e->getMessage(),  // Ver logs: tabla no existe
        ]);
        return 0;
    }
}
```

### Problema Exacto
1. **Tabla legacy:** `prenda_pedido_tallas` no se usa en flujo actual
2. **Tabla real:** `pedidos_procesos_prenda_tallas` contiene las tallas reales
3. **Relación:** `pedido → prenda → proceso → tallas`

### Evidencia en Logs
```log
[2026-01-26 09:20:24] local.WARNING: [CrearPedidoEditableController] calcularCantidadTotalPrendas - Error 
{
  "pedido_id":2719,
  "error":"SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mundo_bd.prendas_pedido_tallas' doesn't exist"
}
```

### Impacto
-  `cantidad_prendas = 0` (debería ser 30)
-  `cantidad_total = 0` (debería ser 30)
-  Factura rompe (cantidad vacía)
-  Pedido mal registrado

### Solución Requerida
Cambiar query para consultar `pedidos_procesos_prenda_tallas`:

```php
// Relación correcta:
// pedidos_procesos_prenda_tallas
// └── proceso_prenda_detalle_id → procesos_prenda_detalle
//     └── prenda_pedido_id → prendas_pedido
//         └── pedido_produccion_id = $pedidoId

SELECT SUM(pppt.cantidad)
FROM pedidos_procesos_prenda_tallas pppt
INNER JOIN procesos_prenda_detalle ppd ON pppt.proceso_prenda_detalle_id = ppd.id
INNER JOIN prendas_pedido pp ON ppd.prenda_pedido_id = pp.id
WHERE pp.pedido_produccion_id = $pedidoId
```

---

## 🔴 PROBLEMA 2: EDICIÓN DE EPP BLOQUEADA (JS ERROR)

### Ubicación Exacta
- **Archivo Principal:** `public/js/modulos/crear-pedido/epp/services/epp-service.js`
- **Línea:** 106
- **Método:** `editarEPPFormulario()`

- **Archivo Secundario:** `public/js/modulos/crear-pedido/epp/epp-init.js`
- **Línea:** 95
- **Llamada:** `editarEPPFormulario()`

### Código Actual (ERRÓNEO)

**epp-service.js línea 106:**
```javascript
editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes) {
    //  PROBLEMA: Se usa $codigo pero NO está como parámetro
    this.stateManager.iniciarEdicion(id, false);
    this.stateManager.setProductoSeleccionado({ id, nombre, codigo, categoria });  //  codigo SIN DEFINIR
    this.stateManager.guardarDatosItem(id, { id, nombre, codigo, categoria, cantidad, observaciones, imagenes });
    // ...
}
```

**epp-init.js línea 95 (llamada):**
```javascript
function editarItemEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    // LOS PARÁMETROS VAN: 7 parámetros
    window.eppService?.editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes);
}
```

**modal-editar-epp.blade.php línea 108 (llamada):**
```php
window.eppService.editarEPPFormulario(
    datosEpp.id,
    datosEpp.nombre,
    datosEpp.codigo,           // ← PARÁMETRO 3
    datosEpp.categoria,        // ← PARÁMETRO 4
    epp.cantidad || 0,
    epp.observaciones || '',
    imagenes
);
```

### Problema Exacto
1. **Firma actual:** `editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes)` (5 params)
2. **Llamada:** `editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)` (7 params)
3. **Resultado:** `codigo` y `categoria` no están disponibles → ReferenceError

### Error JavaScript Resultante
```
Uncaught ReferenceError: codigo is not defined
    at EppService.editarEPPFormulario (epp-service.js:106)
```

### Impacto
-  Botón "Editar EPP" genera JS error
-  Modal no se abre
-  Usuario no puede editar EPPs
-  Edición bloqueada completamente

### Solución Requerida
Actualizar firma del método para incluir `codigo` y `categoria`:

```javascript
editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    // Ahora codigo y categoria están disponibles
    this.stateManager.iniciarEdicion(id, false);
    this.stateManager.setProductoSeleccionado({ id, nombre, codigo, categoria });
    // ...
}
```

---

## 🟡 PROBLEMA 3: FACTURA CON EPP (POTENCIAL)

### Ubicación Exacta
- **Archivo:** `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php`
- **Línea:** 380-420 (sección de EPPs)
- **Método:** `obtenerDatosFactura(int $pedidoId): array`

### Código Actual (POTENCIALMENTE FRÁGIL)

```php
// Línea 380+
$datos['epps'] = [];
try {
    if ($pedido->epps) {
        foreach ($pedido->epps as $pedidoEpp) {
            $epp = $pedidoEpp->epp;
            
            //  Si $epp es null, falla
            $eppFormato = [
                'id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'nombre' => $epp->nombre_completo ?? '',  //  Si $epp null → 500
                'categoria' => $epp->categoria?->nombre ?? $epp->categoria ?? '',
                // ...
            ];
        }
    }
} catch (\Exception $e) {
    // Silencia el error pero no lo reporta
}
```

### Problemas Potenciales
1.  Si `$pedidoEpp->epp` es null (relación no cargada)
2.  Si `$epp->categoria` no existe
3.  Si `tallas_medidas` es null

### Impacto
- Puede causar 500 si EPP sin relación válida
- Factura falla silenciosamente

### Validación Requerida
Agregar guards defensivos:

```php
if (!$epp) {
    \Log::warning('[FACTURA] EPP no relacionado', ['pedido_epp_id' => $pedidoEpp->id]);
    continue;
}
```

---

## SOLUCIONES APLICABLES

### Solución 1: calcularCantidadTotalPrendas()
**Cambiar:** Consulta a `pedidos_procesos_prenda_tallas` en lugar de `prenda_pedido_tallas`

**Archivo:** `CrearPedidoEditableController.php` (Línea 1384-1410)

**Cambio:**
-  `FROM prendas_pedido_tallas`
- `FROM pedidos_procesos_prenda_tallas`
- Agregar JOIN a `procesos_prenda_detalle` → `prendas_pedido`

---

### Solución 2: editarEPPFormulario()
**Cambiar:** Firma del método para incluir `codigo` y `categoria`

**Archivo:** `epp-service.js` (Línea 106)

**Cambio:**
```javascript
//  Actual:
editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes)

// Nuevo:
editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes)
```

---

### Solución 3: Validación de EPP en obtenerDatosFactura()
**Cambiar:** Agregar guards defensivos para EPP

**Archivo:** `PedidoProduccionRepository.php` (Línea 380+)

**Cambio:**
```php
// Agregar validación de $epp antes de usarlo
if (!$epp) {
    \Log::warning('[FACTURA] EPP sin relación válida', ['pedido_epp_id' => $pedidoEpp->id]);
    continue;
}
```

---

## 🎯 CHECKLIST DE IMPLEMENTACIÓN

- [ ] Corregir `calcularCantidadTotalPrendas()` en `CrearPedidoEditableController.php`
- [ ] Actualizar firma de `editarEPPFormulario()` en `epp-service.js`
- [ ] Agregar validación defensiva en `obtenerDatosFactura()` para EPP
- [ ] Testing: Crear pedido con prendas y verificar cantidad
- [ ] Testing: Editar EPP en modal
- [ ] Testing: Generar factura con EPPs
- [ ] Verificar logs sin warnings

---

## 📋 REFERENCIAS DE BASE DE DATOS

### Estructura Real de Tallas (Actual)

```
pedido_produccion
└── prendas_pedido
    └── procesos_prenda_detalle (procesos)
        └── pedidos_procesos_prenda_tallas (TALLAS REALES)
            ├── proceso_prenda_detalle_id
            ├── genero
            ├── talla
            └── cantidad
```

### Tabla Legacy (No Usada)
```
prenda_pedido_tallas  ← VACÍA, NO SE CREA EN FLUJO ACTUAL
```

---

## 📝 NOTAS TÉCNICAS

1. **Sistema de tallas dual:**
   - Legacy: `prenda_pedido_tallas` (por prenda, sin procesos)
   - Actual: `pedidos_procesos_prenda_tallas` (por proceso de prenda)

2. **El flujo actual usa:**
   - Prendas con procesos
   - Tallas asociadas a procesos (no a prendas directamente)
   - Esto es más flexible para producción

3. **Los cálculos deben apuntar a tabla actual**, no legacy

---

##  SIGUIENTE PASO

Proceder a implementar las 3 soluciones en orden:
1. Calcular cantidades correctamente
2. Arreglar parámetros JS
3. Agregar validaciones defensivas
