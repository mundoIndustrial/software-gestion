# 🔍 DIAGNÓSTICO DEFINITIVO - PUNTO EXACTO DONDE PROCESOS SE PIERDE

## SITUACIÓN ACTUAL

Hemos rastreado completamente el flujo:

✅ **Backend devuelve procesos** - Verificado en línea 814 de `PedidoProduccionRepository.php`
✅ **Eager loading correcto** - Se cargan relaciones en línea 30 de `obtenerPorId()`  
✅ **Frontend NO transforma datos** - Verificado: No hay normalizador entre fetch y ReceiptManager
 **Falta en navegador** - El usuario ve procesos vacío en la modal

---

## PLAN DE DIAGNÓSTICO DEFINITIVO

### PASO 1: VER LOGS DEL BACKEND

**Acción:**
1. Abre `storage/logs/laravel.log` en tu proyecto
2. Revisa las líneas MÁS RECIENTES (las del último cuando hagas clic en "Ver Recibos")
3. Busca las líneas que digan `[RECIBO-CONTROLLER]` y `[RECIBOS-REPO]`

**Qué buscar:**
```
[RECIBO-CONTROLLER] Antes de JSON response:
├─ tiene_procesos: SI o NO
├─ procesos_count: número o N/A
└─ procesos_valor: [array] o UNDEFINED

[RECIBOS-REPO] Datos retornados
└─ >>>>> PROCESOS_DEBUG >>>>>
   ├─ tiene_procesos_key: SI o NO
   ├─ procesos_es_null: SI o NO
   ├─ procesos_es_array: SI o NO
   ├─ procesos_count: número
   └─ procesos_primero: { nombre_proceso, tipo_proceso }
```

**¿QUÉ SIGNIFICA?**
- Si `tiene_procesos_key: NO` → El backend NO está mandando procesos
- Si `procesos_es_null: SI` → El backend mandó `procesos: null` 
- Si `procesos_es_array: SI` y `procesos_count: > 0` → El backend SÍ mandó procesos

---

### PASO 2: CAPTURAR LA RESPUESTA JSON DEL NAVEGADOR

**Acción:**
1. Abre Developer Tools (F12)
2. Vete a pestaña **Network**
3. Haz clic en "Ver Recibos" del pedido
4. Busca el request a `/asesores/pedidos/{id}/recibos-datos`
5. Haz clic en ese request
6. Abre la pestaña **Response** (no Preview)
7. Busca en el JSON la palabra `"procesos"`

**¿QUÉ BUSCAR?**
```json
{
  "prendas": [
    {
      "nombre": "CAMISETA XYZ",
      "procesos": [  ← ESTO DEBE ESTAR AQUÍ
        {
          "nombre_proceso": "BORDADO",
          "tipo_proceso": "BORDADO"
        }
      ]
    }
  ]
}
```

- ¿Aparece `"procesos"`? → **SÍ: El backend lo envía**
- ¿No aparece? → **NO: El backend NO lo envía**

---

### PASO 3: VERIFICAR EN CONSOLE DEL NAVEGADOR

**Acción:**
1. En Developer Tools, pestaña **Console**
2. Ejecuta esto después de hacer clic en "Ver Recibos" (antes de que aparezca la modal):
```javascript
// Espera a que se cargue el ReceiptManager
setTimeout(() => {
    if (window.receiptManager && window.receiptManager.datosFactura) {
        const prenda = window.receiptManager.datosFactura.prendas[0];
        console.group('✅ VERIFICACIÓN FINAL');
        console.log('Primera prenda:', prenda.nombre);
        console.log('¿Tiene procesos?', 'procesos' in prenda);
        console.log('procesos es:', prenda.procesos);
        console.log('¿Es array?', Array.isArray(prenda.procesos));
        console.log('Count:', prenda.procesos ? prenda.procesos.length : 'N/A');
        if (prenda.procesos && prenda.procesos.length > 0) {
            console.log('Primer proceso:', prenda.procesos[0]);
        }
        console.groupEnd();
    } else {
        console.error('receiptManager no cargado');
    }
}, 2000);
```

**Resultado esperado:**
```
✅ VERIFICACIÓN FINAL
Primera prenda: CAMISETA
¿Tiene procesos? true o false
procesos es: Array(3) o undefined
¿Es array? true o false
Count: 3 o N/A
Primer proceso: {nombre_proceso: 'BORDADO', ...}
```

---

## ESCENARIOS Y SOLUCIONES

### ESCENARIO A: Backend devuelve procesos, browser los recibe, pero ReceiptManager no los ve

**Síntoma:**
- Logs del backend: `tiene_procesos: SI`, `procesos_count: 3`
- Response JSON: `"procesos": [...]`
- Console del navegador: `¿Tiene procesos? false`

**Causa:** Hay un transformador en el frontend que está quitando procesos
**Solución:** Ver archivo `public/js/asesores/invoice-from-list.js` línea 576, buscar si `crearModalRecibosDesdeListaPedidos()` modifica datos

---

### ESCENARIO B: Backend NO devuelve procesos

**Síntoma:**
- Logs del backend: `tiene_procesos: NO`, `procesos_es_null: SI`
- Response JSON: No aparece `"procesos"` o aparece como `null`

**Causa:** El backend no está cargando procesos correctamente
**Solución:** 
1. Verificar que `$prenda->procesos` tenga datos en línea 614
2. Agregar `dd($prenda->procesos);` en línea 614 para debug

---

### ESCENARIO C: Procesos existe en backend pero no en el Modelo

**Síntoma:**
- Logs del backend: `procesos_es_array: NO`, `procesos_es_null: SI`
- Línea 614 devuelve un array vacío

**Causa:** La relación `procesos` en el modelo no está devolviendo datos
**Solución:**
```php
// En línea 614 de PedidoProduccionRepository.php
\Log::info('DEBUG PROCESOS:', [
    'prenda_id' => $prenda->id,
    'tiene_relacion_procesos' => method_exists($prenda, 'procesos'),
    'procesos_query' => $prenda->procesos()->count(),
    'procesos_collection' => $prenda->procesos->count(),
    'procesos_items' => $prenda->procesos->toArray(),
]);
```

---

## ¿CÓMO REPORTAR LOS RESULTADOS?

**Comparte conmigo:**

1. **De los logs (`storage/logs/laravel.log`):**
   - Las líneas que contienen `[RECIBO-CONTROLLER]` y `[RECIBOS-REPO]`
   - Los valores de: `tiene_procesos`, `procesos_count`, `procesos_es_array`

2. **Del Network (DevTools):**
   - Una captura de pantalla del JSON response mostrando si aparece `"procesos"`
   - O copia el JSON completo de la response

3. **De la Console:**
   - El output de ejecutar el script de verificación
   - Especialmente el valor de `¿Tiene procesos?` y `procesos es:`

4. **El número de pedido que estás probando**
   - Para que yo pueda correlacionar con los logs

---

## ARCHIVOS QUE FUERON MODIFICADOS

✏️ Agregué logs en:
- `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php` (línea ~900)
- `app/Infrastructure/Http/Controllers/Asesores/ReciboController.php` (línea ~52)

Los logs aparecerán en: `storage/logs/laravel.log`

---

## PRÓXIMOS PASOS DESPUÉS DEL DIAGNÓSTICO

1. **Si procesos ESTÁ en el backend pero NO en el navegador:**
   - Buscar transformador/normalizador en frontend
   - Verificar si hay middleware quitando campos

2. **Si procesos NO está en el backend:**
   - Agregar más logging en `obtenerDatosRecibos()`
   - Verificar relación `procesos` en el modelo PrendaPedido
   - Ejecutar queries directas en DB

3. **Una vez identificado el problema:**
   - Proporcionar código corrected completo
   - Agregar tests para evitar que vuelva a pasar

---

## RESUMEN DE LA INVESTIGACIÓN PREVIA

✅ Completado:
- Auditoría de `PedidoProduccionRepository::obtenerDatosRecibos()` - Confirmed procesos included
- Auditoría de eager loading en `obtenerPorId()` - Confirmed procesos loaded
- Búsqueda de normalizadores frontend - None found
- Búsqueda de transformadores en invoice-from-list.js - None found
- Verificación de middlewares - None filtering fields
- Verificación de Resources - None used

Falta:
- Ver logs reales del servidor ← **TÚ NECESITAS HACERLO**
- Ver respuesta JSON en Network tab ← **TÚ NECESITAS HACERLO**
- Ver estado en ReceiptManager en console ← **TÚ NECESITAS HACERLO**
