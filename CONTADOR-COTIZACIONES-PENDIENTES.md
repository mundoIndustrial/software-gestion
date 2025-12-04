# ✅ SISTEMA DE CONTADOR DE COTIZACIONES PENDIENTES

## 🎯 Objetivo
En el rol CONTADOR, mostrar solo cotizaciones PENDIENTES (estado ENVIADA_CONTADOR) con un contador en notificaciones que se decrementa cuando se aprueben o rechacen.

## ✅ IMPLEMENTACIÓN COMPLETADA

### 1. **Filtro de Cotizaciones Pendientes**
- ✅ Solo muestra cotizaciones en estado `ENVIADA_CONTADOR`
- ✅ Las cotizaciones aprobadas o rechazadas desaparecen automáticamente
- ✅ Las cotizaciones en corrección van a "Cotizaciones a Revisar"

### 2. **Contador en Notificaciones**
- ✅ Badge rojo con número en el menú "Pendientes"
- ✅ Se actualiza automáticamente cada 30 segundos
- ✅ Se oculta cuando no hay cotizaciones pendientes
- ✅ Se muestra cuando hay cotizaciones pendientes

### 3. **Endpoint JSON**
- ✅ `GET /contador/cotizaciones-pendientes-count`
- ✅ Retorna: `{ success: true, count: N, message: "..." }`
- ✅ Requiere autenticación (rol contador o admin)

## 📊 FLUJO COMPLETO

### Paso 1: Usuario accede a Contador
```
1. Abre /contador/dashboard
2. Layout carga script de contador
3. Script ejecuta cargarContadorPendientes()
4. Fetch a /contador/cotizaciones-pendientes-count
5. Badge se actualiza con el número de pendientes
```

### Paso 2: Usuario aprueba cotización
```
1. Hace clic en "Aprobar" en una cotización
2. Estado cambia: ENVIADA_CONTADOR → APROBADA_CONTADOR
3. Cotización desaparece de la tabla
4. Cada 30 segundos, el contador se recalcula
5. Badge se decrementa automáticamente
```

### Paso 3: Usuario rechaza cotización
```
1. Hace clic en "Rechazar" en una cotización
2. Estado cambia: ENVIADA_CONTADOR → EN_CORRECCION
3. Cotización desaparece de "Pendientes"
4. Cotización aparece en "Cotizaciones a Revisar"
5. Badge se decrementa automáticamente
```

## 🧪 CÓMO PROBAR

### Test 1: Verificar que solo muestra ENVIADA_CONTADOR
```
1. Ir a /contador/dashboard
2. Verificar que la tabla solo muestra cotizaciones con estado ENVIADA_CONTADOR
3. Las demás cotizaciones NO deben aparecer
```

### Test 2: Verificar que el badge se muestra
```
1. Ir a /contador/dashboard
2. Buscar el badge rojo en el menú "Pendientes"
3. El badge debe mostrar el número de cotizaciones pendientes
4. Si no hay pendientes, el badge debe estar oculto
```

### Test 3: Verificar que el contador se actualiza
```
1. Abrir /contador/dashboard en 2 navegadores
2. En el navegador 1, aprobar una cotización
3. En el navegador 2, esperar 30 segundos
4. El badge debe decrementarse automáticamente
5. La tabla debe actualizarse sin recargar
```

### Test 4: Verificar el endpoint JSON
```
1. Abrir DevTools (F12)
2. Ir a la pestaña Network
3. Recargar la página
4. Buscar la petición a /contador/cotizaciones-pendientes-count
5. Verificar que retorna JSON con count > 0
```

### Test 5: Verificar que el badge se oculta
```
1. Aprobar todas las cotizaciones pendientes
2. Esperar 30 segundos
3. El badge debe desaparecer del menú "Pendientes"
4. La tabla debe estar vacía
```

## 📝 ARCHIVOS MODIFICADOS

### 1. app/Http/Controllers/ContadorController.php
- Método `index()`: Filtra SOLO `ENVIADA_CONTADOR`
- Método `cotizacionesPendientesCount()`: Retorna JSON con contador

### 2. routes/web.php
- Ruta: `GET /contador/cotizaciones-pendientes-count`

### 3. resources/views/contador/sidebar.blade.php
- Badge: `#cotizacionesPendientesCount` en menú "Pendientes"

### 4. resources/views/layouts/contador.blade.php
- Script: Carga contador cada 30 segundos

## 🔄 ESTADOS DE COTIZACIÓN

| Estado | Mostrado en Pendientes | Mostrado en Revisar | Acción |
|--------|----------------------|-------------------|--------|
| ENVIADA_CONTADOR | ✅ Sí | ❌ No | Aprobar o Rechazar |
| APROBADA_CONTADOR | ❌ No | ❌ No | Enviada a Aprobador |
| APROBADA_COTIZACIONES | ❌ No | ❌ No | Lista para Pedido |
| EN_CORRECCION | ❌ No | ✅ Sí | Corregir y Reenviar |
| CONVERTIDA_PEDIDO | ❌ No | ❌ No | Convertida a Pedido |
| FINALIZADA | ❌ No | ❌ No | Finalizada |

## 📊 DATOS ESPERADOS

### Respuesta del endpoint
```json
{
  "success": true,
  "count": 5,
  "message": "Hay 5 cotización(es) pendiente(s) por revisar"
}
```

### Si no hay pendientes
```json
{
  "success": true,
  "count": 0,
  "message": "No hay cotizaciones pendientes"
}
```

## ✨ CARACTERÍSTICAS

✅ Contador en tiempo real
✅ Badge se actualiza automáticamente
✅ Solo muestra cotizaciones PENDIENTES
✅ Badge se oculta si no hay pendientes
✅ Integrado en el sidebar
✅ Sin necesidad de recargar la página
✅ Endpoint JSON seguro

## 🚀 PRÓXIMOS PASOS (Opcionales)

1. Agregar notificación por email cuando hay nuevas cotizaciones
2. Agregar sonido de alerta cuando llega una nueva cotización
3. Agregar filtro por cliente o asesora
4. Agregar búsqueda de cotizaciones
5. Agregar exportación a Excel

## 📅 Fecha: 4 de Diciembre de 2025
## 🎯 Estado: COMPLETADO ✅
