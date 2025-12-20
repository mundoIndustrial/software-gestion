# Testing: API Logo Pedidos por ID

## Test 1: Verificar que la ruta existe

```bash
# En Laravel
php artisan route:list | grep logo-pedidos
```

Resultado esperado:
```
GET|HEAD  /api/logo-pedidos/{id}  ...  api.logo-pedidos.show
```

---

## Test 2: Probar manualmente en browser

### Paso 1: Consola del navegador
```javascript
// Ver si el botón tiene el atributo correcto
document.querySelector('[data-es-logo="1"]')?.getAttribute('data-pedido-id')
// Resultado esperado: "15" (o el ID correspondiente)
```

### Paso 2: Click en "Recibo de Logo"
Abre DevTools → Console → Click en el botón "Ver" de un pedido de logo → Click en "Recibo de Logo"

Deberías ver logs como:
```
🔴 [MODAL LOGO] Abriendo modal de bordados para ID: 15
🔴 [MODAL LOGO] Haciendo fetch a /api/logo-pedidos/15
✅ [MODAL LOGO] Datos del LogoPedido obtenidos: {...}
```

### Paso 3: Verificar respuesta de API
```javascript
// Directamente en la consola:
fetch('/api/logo-pedidos/15').then(r => r.json()).then(d => console.log(d))
```

Deberías ver el objeto LogoPedido completo con:
- id
- numero_pedido
- cliente
- asesora
- descripcion
- tecnicas
- ubicaciones
- forma_de_pago
- fecha_de_creacion_de_orden
- encargado_orden
- etc.

---

## Test 3: Verificar logs del servidor

### En storage/logs/laravel.log
Deberías ver cuando hagas click en "Recibo de Logo":

```log
[2024-...] ...INFO: 🔍 [API] showLogoPedidoById buscando ID: 15 {"cliente":" ... "}
[2024-...] ...INFO: ✅ [PASO 1 API] Completados datos desde PedidoProduccion #11399
[2024-...] ...INFO: ✅ [PASO 2 API] Completados datos desde LogoCotizacion #107
[2024-...] ...INFO: ✅ [API] LogoPedido ID 15 respondido correctamente {"cliente":" ... ","asesora":" ... "}
```

---

## Test 4: Casos de Prueba

| Caso | Pasos | Resultado Esperado |
|------|-------|-------------------|
| LogoPedido completo | Click en "Recibo de Logo" | Modal muestra todos los datos |
| LogoPedido sin cliente (debe venir de PedidoProduccion) | Click en "Recibo de Logo" | Cliente completado desde PedidoProduccion |
| LogoPedido sin descripción (debe venir de LogoCotizacion) | Click en "Recibo de Logo" | Descripción completada desde LogoCotizacion |
| ID no existe | Inspeccionar red, hacer fetch a /api/logo-pedidos/99999 | Error 404 {"error":"LogoPedido no encontrado"} |
| Error en base de datos | Simular con log de error | Error 500 con mensaje descriptivo |

---

## Checklist de Implementación

- [x] Ruta /api/logo-pedidos/{id} agregada a web.php
- [x] Método showLogoPedidoById() agregado al controlador
- [x] LogoCotizacion importado en el controlador
- [x] Frontend extrae data-pedido-id del botón
- [x] verFacturaLogo() recibe ID y hace fetch a /api/logo-pedidos/{id}
- [x] Fallback logic de 3 pasos implementado
- [x] Logging en cada paso
- [x] Error handling con try-catch
- [x] JSON response con datos completos

---

## Validación Final

Después de implementar, verifica:

1. ✅ No hay errores de compilación/syntax
2. ✅ Ruta está registrada correctamente
3. ✅ Método existe en el controlador
4. ✅ Imports están correctos
5. ✅ Frontend pasa ID numérico
6. ✅ API retorna JSON válido
7. ✅ Modal se abre y muestra datos
8. ✅ Logs aparecen en storage/logs/laravel.log
