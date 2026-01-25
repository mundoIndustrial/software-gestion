# 🧪 GUÍA DE PRUEBA - BUG DE PROCESOS

## 1️⃣ PRUEBA RÁPIDA EN EL NAVEGADOR

### Paso 1: Limpiar cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Paso 2: Abrir la aplicación
1. Ve a `/asesores/pedidos`
2. Busca un pedido que tenga procesos
3. Haz clic en **"Ver Recibos"**

### Paso 3: Verificar que aparecen los procesos
✅ Deberías ver:
- Título de cada proceso (BORDADO, ESTAMPADO, etc.)
- Imágenes del proceso
- Tallas del proceso
- Ubicaciones

❌ Si no aparecen:
- Abre DevTools (F12)
- Ve a **Console**
- Ejecuta:
```javascript
console.log(window.receiptManager.datosFactura.prendas[0].procesos);
```

---

## 2️⃣ PRUEBA EN NETWORK (DevTools)

### Paso 1: Abrir DevTools (F12)
### Paso 2: Ir a pestaña **Network**
### Paso 3: Hacer clic en "Ver Recibos"
### Paso 4: Buscar el request `/asesores/pedidos/{id}/recibos-datos`
### Paso 5: Abrir pestaña **Response**
### Paso 6: Buscar la palabra `"nombre"`

**Debes ver:**
```json
{
  "prendas": [
    {
      "procesos": [
        {
          "nombre": "BORDADO",
          "tipo": "BORDADO",
          "nombre_proceso": "BORDADO",
          "tipo_proceso": "BORDADO",
          ...
        }
      ]
    }
  ]
}
```

---

## 3️⃣ PRUEBA CON SCRIPT DE CONSOLE

Después de hacer clic en "Ver Recibos":

```javascript
setTimeout(() => {
    const prenda = window.receiptManager.datosFactura.prendas[0];
    console.log('=== VERIFICACIÓN DE PROCESOS ===');
    console.log('Prenda:', prenda.nombre);
    console.log('Procesos count:', prenda.procesos.length);
    
    if (prenda.procesos.length > 0) {
        const proc = prenda.procesos[0];
        console.log('Primer proceso:');
        console.log('  - nombre:', proc.nombre);
        console.log('  - tipo:', proc.tipo);
        console.log('  - nombre_proceso:', proc.nombre_proceso);
        console.log('  - tipo_proceso:', proc.tipo_proceso);
        console.log('  - imagenes:', proc.imagenes.length, 'imágenes');
        console.log('  - tallas:', Object.keys(proc.tallas));
    }
}, 2000);
```

---

## 4️⃣ PRUEBA AUTOMATIZADA CON PHPUNIT

```bash
# Ejecutar test específico
php artisan test tests/Feature/ProcesosRenderTest.php

# Ejecutar con verbose
php artisan test tests/Feature/ProcesosRenderTest.php --verbose

# Ejecutar un test específico
php artisan test tests/Feature/ProcesosRenderTest.php::test_obtenerDatosRecibos_incluye_campos_nombre_tipo
```

**Resultado esperado:**
```
✓ test_obtenerDatosRecibos_incluye_campos_nombre_tipo ✓ PASSED
✓ test_obtenerDatosFactura_incluye_campos_nombre_tipo ✓ PASSED
✓ test_procesos_incluyen_imagenes ✓ PASSED
✓ test_procesos_incluyen_tallas_estructura ✓ PASSED

4 tests passed
```

---

## 5️⃣ VERIFICAR ESTRUCTURA DE DATOS

Ejecuta esto en una terminal con tinker:

```bash
php artisan tinker
```

```php
// Obtener un pedido con procesos
$pedido = \App\Models\PedidoProduccion::with('prendas.procesos')->first();

// Obtener datos de recibos
$repo = new \App\Domain\Pedidos\Repositories\PedidoProduccionRepository();
$datos = $repo->obtenerDatosRecibos($pedido->id);

// Verificar estructura
dd($datos['prendas'][0]['procesos'][0] ?? null);
```

**Debes ver:**
```
[
  "nombre" => "BORDADO"
  "tipo" => "BORDADO"
  "nombre_proceso" => "BORDADO"
  "tipo_proceso" => "BORDADO"
  "tallas" => [...]
  "imagenes" => [...]
  "ubicaciones" => [...]
  "observaciones" => "..."
  "estado" => "Pendiente"
]
```

---

## 6️⃣ VERIFICAR LOGS

Después de hacer clic en "Ver Recibos":

```bash
tail -f storage/logs/laravel.log | grep "RECIBOS-REPO\|RECIBO-CONTROLLER"
```

**Debes ver:**
```
[2026-01-25 10:30:45] local.INFO: [RECIBOS-REPO] Datos retornados {"prendas_count":2,"epps_count":0,"procesos_debug":{"nombre_prenda":"CAMISETA","tiene_procesos_key":"SI","procesos_es_null":"NO","procesos_es_array":"SI","procesos_count":3,"procesos_primero":{"nombre":"BORDADO","tipo":"BORDADO",...}}}

[2026-01-25 10:30:45] local.INFO: [RECIBO-CONTROLLER] Antes de JSON response: {"tiene_procesos":"SI","procesos_count":3,"procesos_valor":[...]}
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [ ] Los procesos aparecen en la modal de recibos
- [ ] Los títulos de procesos son visibles (BORDADO, ESTAMPADO, etc.)
- [ ] Las imágenes de procesos se cargan
- [ ] Las tallas del proceso aparecen
- [ ] Las ubicaciones se muestran
- [ ] El Network tab muestra campos `nombre` y `tipo`
- [ ] El console log muestra `procesos_es_array: SI`
- [ ] Los tests de PHPUnit pasan todos
- [ ] No hay errores en la consola del navegador
- [ ] No hay errores en `storage/logs/laravel.log`

---

## 🐛 Si Algo No Funciona

### Síntoma: "No veo procesos en la modal"
**Solución:**
1. Ejecuta: `php artisan cache:clear && php artisan view:clear`
2. Actualiza la página (Ctrl+Shift+R para limpiar caché del navegador)
3. Verifica DevTools → Network → busca `/recibos-datos` y mira Response

### Síntoma: "Los procesos aparecen pero sin imágenes"
**Causa:** Rutas de imágenes incorrectas
**Solución:** Revisa que `public/storage` esté enlazado:
```bash
php artisan storage:link
```

### Síntoma: "Error en los tests"
**Causa:** Base de datos sin procesos
**Solución:** Crea un pedido con procesos primero en la aplicación, luego ejecuta tests

### Síntoma: "Aparecer campo 'tipo' pero no 'nombre'"
**Causa:** Cambios no se aplicaron
**Solución:** 
1. Verifica que editaste las líneas correctas en `PedidoProduccionRepository.php`
2. Ejecuta: `php artisan config:clear`
3. Reinicia servidor PHP

---

## 📞 Contacto

Si encuentras problemas:
1. Revisa `storage/logs/laravel.log` para errores
2. Ejecuta los tests automatizados para confirmar estructura
3. Abre DevTools y captura el JSON response del Network tab
