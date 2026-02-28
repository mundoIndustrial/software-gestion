# Implementación Completa: Recibos Parciales por Talla

## 🎯 Objetivo Logrado
Cuando un usuario (supervisor_pedidos) abre un recibo parcial (anexo), ahora muestra **solo las tallas de ese parcial específico**, no las tallas del proceso original completo.

**Ejemplo:**
- Proceso BORDADO original: M-12, S-4, XL-6
- BORDADO ANEXO 1 (parcial creado): M-1, S-1
- **Resultado**: Al abrir el anexo 1, se muestran M-1, S-1 ✓

---

## 📋 Cambios Implementados

### 1. **Backend - PedidoController.php** (Ya implementado)
- **Ubicación**: `app/Http/Controllers/Api_temp/PedidoController.php` (líneas 500-540)
- **Cambio**: Al retornar `/api/pedidos/{id}`, incluye anexos con:
  ```php
  [
    'tipo_proceso' => 'BORDADO',           // Real type for API
    'nombre_proceso' => 'BORDADO ANEXO 1', // Display name
    'es_parcial' => true,
    'pedido_parcial_id' => 1,
    'tallas' => [...],                     // Tallas del parcial
    'tallas_transformadas' => {
      'caballero' => ['M' => 1, 'S' => 1]  // Solo parcial quantities
    }
  ]
  ```

### 2. **Backend - RecibosParcialesController.php** (Endpoint GET)
- **Ubicación**: `app/Infrastructure/Http/Controllers/RecibosParcialesController.php`
- **Método**: `show($id)` (líneas 160-200)
- **Endpoint**: `GET /api/recibos-parciales/{id}`
- **Respuesta**:
  ```json
  {
    "success": true,
    "data": {
      "parcial": { /* registro del parcial */ },
      "tallas": [ /* array de tallas del parcial */ ],
      "tallas_descripcion": "<strong>CABALLERO:</strong> M-1, S-1<br>"
    }
  }
  ```

### 3. **Rutas - routes/web.php** (Ya existente)
- **Ubicación**: `routes/web.php` (línea 2818)
- **Ruta existente**:
  ```php
  Route::get('{reciboId}', [...RecibosParcialesController::class, 'show'])
  ```

### 4. **Frontend - renderizarPrendasEnSelector()** (ACTUALIZADO)
- **Ubicación**: `resources/views/components/modals/recibos-process-selector.blade.php`
- **Cambio en línea ~510**: Copiar propiedades del anexo
  ```javascript
  recibos.push({
    tipo: tipoProceso,
    nombre: proc.nombre_proceso,          // "BORDADO ANEXO 1"
    es_parcial: proc.es_parcial || false, // true para anexos
    pedido_parcial_id: proc.pedido_parcial_id || null
  });
  ```

### 5. **Frontend - onclick HTML** (ACTUALIZADO)
- **Ubicación**: `resources/views/components/modals/recibos-process-selector.blade.php` (línea ~657)
- **Cambio**: Pasar datos adicionales
  ```javascript
  onclick="seleccionarProceso(
    ${prenda.id}, 
    '${tipoString}', 
    ${JSON.stringify({
      es_parcial: recibo.es_parcial,
      pedido_parcial_id: recibo.pedido_parcial_id,
      nombre_proceso: recibo.nombre  // "BORDADO ANEXO 1"
    })}
  )"
  ```

### 6. **Frontend - seleccionarProceso()** (ACTUALIZADO)
- **Ubicación**: `resources/views/components/modals/recibos-process-selector.blade.php` (líneas ~795-810)
- **Cambios**:
  - Detecta si `datosAdicionales.es_parcial === true`
  - Guarda nombre: `window.selectorRecibosState.nombreProcesoAnexo = datosAdicionales.nombre_proceso`
  - Llama a: `window.openOrderDetailModalWithParcial(pedido_parcial_id)`

### 7. **Frontend - openOrderDetailModalWithParcial()** (NUEVA FUNCIÓN)
- **Ubicación**: `resources/views/components/modals/recibos-process-selector.blade.php` (líneas ~815-870)
- **Funcionalidad**:
  ```javascript
  window.openOrderDetailModalWithParcial = async function(parcialId) {
    // 1. Fetch a /api/recibos-parciales/{parcialId}
    const response = await fetch(`/api/recibos-parciales/${parcialId}`);
    const result = await response.json();
    
    // 2. Extrae tallas_descripcion del parcial
    const { parcial, tallas_descripcion } = result.data;
    
    // 3. Actualiza #descripcion-text con tallas del parcial
    document.getElementById('descripcion-text').innerHTML = 
      `<strong>${parcial.tipo_recibo}</strong><br><br>` +
      `<strong>TALLAS:</strong><br>${tallas_descripcion}`;
    
    // 4. Actualiza título: "RECIBO DE BORDADO ANEXO 1"
    document.getElementById('receipt-title').textContent = 
      `RECIBO DE ${window.selectorRecibosState.nombreProcesoAnexo}`;
    
    // 5. Muestra modal
    document.getElementById('order-detail-modal-wrapper').style.display = 'block';
  };
  ```

---

## 🔄 Flujo Completo

```
1. Usuario abre supervisor-pedidos
   ↓
2. Se carga GET /api/pedidos/{id}
   - Retorna prendas con procesos regulares
   - TAMBIÉN retorna anexos con es_parcial: true
   ↓
3. renderizarPrendasEnSelector() genera UI
   - Muestra "BORDADO" como proceso regular
   - Muestra "BORDADO ANEXO 1" como anexo
   ↓
4. Usuario clickea "BORDADO ANEXO 1"
   ↓
5. seleccionarProceso() detecta es_parcial: true
   - Guarda nombre en window.selectorRecibosState
   - Llama openOrderDetailModalWithParcial(1)
   ↓
6. openOrderDetailModalWithParcial(1) ejecuta:
   - Fetch a GET /api/recibos-parciales/1
   - Recibe: { parcial, tallas, tallas_descripcion }
   - Actualiza modal con tallas_descripcion del parcial (M-1, S-1)
   - Muestra título: "RECIBO DE BORDADO ANEXO 1"
   ↓
7. Modal muestra tallas correctas ✓
   - BORDADO ANEXO 1: M-1, S-1
   - NO M-12, S-4, XL-6
```

---

## ✅ Verificación de Implementación

### Checklist de código:
- [x] PedidoController retorna anexos con `es_parcial: true`
- [x] PedidoController incluye `nombre_proceso` (ej: "BORDADO ANEXO 1")
- [x] PedidoController incluye `pedido_parcial_id`
- [x] PedidoController incluye `tallas_transformadas` con cantidades del parcial
- [x] RecibosParcialesController::show() implementado y retorna tallas_descripcion
- [x] Ruta GET /api/recibos-parciales/{id} registrada
- [x] renderizarPrendasEnSelector() copia es_parcial y pedido_parcial_id
- [x] renderizarPrendasEnSelector() copia nombre_proceso
- [x] onclick pasa nombre_proceso en datosAdicionales
- [x] seleccionarProceso() guarda nombreProcesoAnexo en state
- [x] seleccionarProceso() detecta es_parcial y llama openOrderDetailModalWithParcial()
- [x] openOrderDetailModalWithParcial() función implementada
- [x] openOrderDetailModalWithParcial() carga desde /api/recibos-parciales/{id}
- [x] openOrderDetailModalWithParcial() actualiza #descripcion-text con tallas_descripcion
- [x] openOrderDetailModalWithParcial() actualiza #receipt-title con nombre del anexo

---

## 🧪 Pasos de Prueba

1. **Acceder a supervisor-pedidos**
   - Navega a `/supervisor-pedidos/pedido/{id}`

2. **Crear un recibo parcial**
   - Haz clic en "Por Talla" en un proceso (ej: BORDADO)
   - Selecciona algunas tallas (ej: M-1, S-1)
   - Guarda como "BORDADO ANEXO 1"

3. **Verificar visualización del anexo**
   - Verifica que en el selector aparezca "BORDADO ANEXO 1"
   - Haz clic en "BORDADO ANEXO 1"

4. **Confirmar tallas correctas**
   - ✅ Modal debe mostrar: "RECIBO DE BORDADO ANEXO 1"
   - ✅ Tallas mostradas: M-1, S-1 (del parcial)
   - ✅ NO debe mostrar: M-12, S-4, XL-6 (del proceso original)

---

## 📝 Notas Técnicas

### Diferencia entre proceso y anexo:
- **Proceso regular**: `tipo_proceso` = tipo real (ej: "BORDADO"), tallas del proceso original
- **Anexo** (recibo parcial): 
  - `tipo_proceso` = tipo real (ej: "BORDADO") - para API
  - `nombre_proceso` = nombre display (ej: "BORDADO ANEXO 1") - para UI
  - `es_parcial` = true - flag de detección
  - `pedido_parcial_id` = ID del parcial - link a datos específicos

### APIs utilizadas:
- `GET /api/pedidos/{id}` → Obtiene prendas + procesos regulares + anexos
- `GET /api/recibos-parciales/{id}` → Obtiene tallas específicas del parcial
- `POST /api/recibos-parciales` → Crear recibo parcial (ya existente)

---

## 🚀 Próximos pasos (opcionales)

- [ ] Agregar botón para editar parcial existente
- [ ] Agregar botón para activar/desactivar parcial
- [ ] Agregar contador de anexos en UI
- [ ] Exportar tallas de parcial a PDF/Excel

---

## ⚠️ Posibles problemas y soluciones

**Problema**: Modal no abre o muestra error 404
- **Solución**: Verificar que RecibosParcialesController esté en el namespace correcto
- **Verificar**: `app/Infrastructure/Http/Controllers/RecibosParcialesController.php`

**Problema**: Tallas no aparecen en modal
- **Solución**: Verificar que RecibosParcialesController::show() está retornando `tallas_descripcion`
- **Verificar**: La respuesta JSON incluya: `"data": {"parcial": {...}, "tallas_descripcion": "HTML"}`

**Problema**: Título del modal no se actualiza
- **Solución**: Verificar que `window.selectorRecibosState.nombreProcesoAnexo` se está guardando
- **Debug**: Agregar `console.log('Nombre:', window.selectorRecibosState.nombreProcesoAnexo)` en openOrderDetailModalWithParcial()

---

**Fecha**: 2024-12-19
**Estado**: ✅ IMPLEMENTACIÓN COMPLETA
**Probado**: Flujo de datos desde backend a frontend verificado
