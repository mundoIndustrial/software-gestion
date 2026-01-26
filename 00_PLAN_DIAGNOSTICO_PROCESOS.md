# PLAN DE ACCIÓN - DIAGNÓSTICO DE PROCESOS FALTANTES

##  OBJETIVO

Identificar el punto EXACTO donde `prenda.procesos` desaparece entre el backend y la modal de recibos.

---

## 📝 LO QUE YA VERIFICAMOS

✅ **Backend (PedidoProduccionRepository.php línea 814):**
```php
'procesos' => $procesos,  // AQUÍ se incluye procesos en la respuesta
```
Procesos ESTÁ siendo incluido en el array que se devuelve.

✅ **Eager loading (obtenerPorId línea 30):**
```php
'prendas.procesos',
'prendas.procesos.tipoProceso',
'prendas.procesos.imagenes',
```
Las relaciones ESTÁN siendo cargadas.

✅ **Frontend (invoice-from-list.js):**
- Línea 540: Se recibe JSON del endpoint
- Línea 576: Se pasa directamente a `crearModalRecibosDesdeListaPedidos()`
- NO hay transformación entre ambas líneas

✅ **receipt-manager.js:**
- Línea 88: Verifica `prenda.procesos`
- Línea 614: Itera procesos
- El código ESTÁ listo para recibir procesos

 **EL PROBLEMA:** Entre línea 540 y línea 88, procesos desaparece

---

##  CAMBIOS QUE REALICÉ

### 1. Backend - Agregué logging super detallado

**Archivo:** `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php`
**Ubicación:** Línea ~900 (justo antes del `return $datos;`)

**Qué hace:** 
- Log: `[RECIBOS-REPO] Datos retornados`
- Muestra exactamente si `procesos` está presente en el backend
- Muestra el count de procesos
- Muestra el primer proceso

**Salida esperada:**
```
[RECIBOS-REPO] Datos retornados
>>>>> PROCESOS_DEBUG >>>>>
├─ tiene_procesos_key: SI o NO
├─ procesos_es_null: SI o NO
├─ procesos_es_array: SI o NO
├─ procesos_count: número
└─ procesos_primero: { nombre_proceso, tipo_proceso }
```

---

### 2. Controller - Agregué logging antes de response

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/ReciboController.php`
**Ubicación:** Línea ~52 (en la función `datos()`)

**Qué hace:**
- Log: `[RECIBO-CONTROLLER] Antes de JSON response`
- Verifica qué va a enviar JSON al navegador
- Muestra si procesos tiene items o está vacío

**Salida esperada:**
```
[RECIBO-CONTROLLER] Antes de JSON response
├─ tiene_procesos: SI o NO
├─ procesos_count: número
└─ procesos_valor: [array] o UNDEFINED
```

---

##  QUÉ NECESITAS HACER

### PASO 1: Ver los logs del backend

1. Abre el archivo: `storage/logs/laravel.log`
2. Ves al final del archivo (líneas más recientes)
3. Busca las líneas con:
   - `[RECIBO-CONTROLLER] Antes de JSON response`
   - `[RECIBOS-REPO] Datos retornados`
4. **Copia esas líneas y pégalas aquí o en un documento**

---

### PASO 2: Capturar respuesta JSON en el navegador

1. Abre DevTools (F12)
2. Vete a **Network** tab
3. Haz clic en "Ver Recibos"
4. Busca el request: `/asesores/pedidos/{id}/recibos-datos`
5. Haz clic en él
6. Abre la pestaña **Response**
7. Busca la palabra `procesos`

**¿QUÉ SIGNIFICA?**
- Si ves `"procesos": [...]` → **El backend lo envía**
- Si NO ves `procesos` → **El backend no lo envía**

---

### PASO 3: Ejecutar auto-diagnóstico en console

1. Abre DevTools (F12)
2. Ve a **Console**
3. Abre el archivo: `SCRIPT_AUTO_DIAGNOSTICO_CONSOLE.md`
4. Copia TODO el código JavaScript de ese archivo
5. Pégalo en la console y presiona Enter
6. **Copia TODO el output y comparte conmigo**

---

## 📊 ESCENARIOS POSIBLES

### Escenario A: Procesos está en backend pero NO en navegador

**Síntomas:**
- Logs backend: `[RECIBOS-REPO] tiene_procesos_key: SI`, `procesos_count: 3`
- Network Response: `"procesos": [...]`
- Console: `¿Tiene clave "procesos"?  NO`

**Causa:** Hay un transformador/normalizador frontend quitando procesos

**Solución:** Buscar en `invoice-from-list.js` qué está quitando procesos

---

### Escenario B: Procesos NO está en backend

**Síntomas:**
- Logs backend: `[RECIBOS-REPO] tiene_procesos_key: NO`, `procesos_es_null: SI`
- Network Response: No aparece `"procesos"`

**Causa:** El backend no está cargando procesos correctamente

**Solución:** Agregar más logging en la iteración de procesos para ver si se cargan

---

### Escenario C: Procesos existe pero es null

**Síntomas:**
- Logs backend: `procesos_es_null: SI`
- Network Response: `"procesos": null`

**Causa:** La relación no devuelve datos

**Solución:** Verificar que la relación `procesos()` en PrendaPedido devuelve datos

---

## 📋 LISTA DE VERIFICACIÓN

- [ ] He abierto `storage/logs/laravel.log`
- [ ] He buscado `[RECIBOS-REPO] Datos retornados` en los logs
- [ ] He copiado las líneas de logs relevantes
- [ ] He abierto DevTools (F12) en el navegador
- [ ] He visto la respuesta JSON en la pestaña Network
- [ ] He ejecutado el script auto-diagnóstico en la console
- [ ] He copiado TODO el output de la console
- [ ] Estoy listo para compartir resultados

---

## 📤 CÓMO COMPARTIR LOS RESULTADOS

**Comparte conmigo:**

1. **Logs del backend** (`storage/logs/laravel.log`)
   - Líneas con `[RECIBO-CONTROLLER]` y `[RECIBOS-REPO]`

2. **Respuesta JSON** (Network tab)
   - Screenshot mostrando si aparece `"procesos"`
   - O el JSON completo

3. **Output de console**
   - El resultado completo del script auto-diagnóstico
   - Especialmente la sección de PROCESOS

4. **El ID del pedido que estás probando**
   - Para correlacionar con los logs

---

## 🎓 REFERENCIAS

- `DIAGNOSTICO_PROCESOS_PUNTO_EXACTO.md` - Guía completa de diagnóstico
- `SCRIPT_AUTO_DIAGNOSTICO_CONSOLE.md` - Script para ejecutar en console
- Backend cambios: `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php`
- Backend cambios: `app/Infrastructure/Http/Controllers/Asesores/ReciboController.php`

---

## ⏰ PRÓXIMAS ACCIONES

Una vez que compartas los resultados de los logs y console:

1. **Identifycaré el punto exacto** donde procesos se pierde
2. **Proporcionaré el código corregido** (con garantía de que procesos aparecerá)
3. **Agregaré tests** para que esto no vuelva a pasar
4. **Documentaré la solución** para futuras referencias

---

## 💡 TIPS

- **Los logs se actualizan en tiempo real:** Después de hacer clic en "Ver Recibos", ve al log y presiona F5 para refrescar
- **Si no ves `[RECIBOS-REPO]`:** Quizás el logging no está habilitado, intenta llenar `LOG_LEVEL=debug` en `.env`
- **El script de console:** Espera 2-3 segundos después de hacer clic en "Ver Recibos" antes de ejecutarlo, para que ReceiptManager esté cargado

