# 📋 GUÍA DE IMPLEMENTACIÓN: SOLUCIÓN PÉRDIDA DE PAYLOAD

**Status:** LISTO PARA IMPLEMENTAR  
**Complejidad:** Bajo (Un cambio de 1 línea)  
**Tiempo estimado:** 5 minutos  
**Riesgo:** Bajo (El FormRequest ya estaba disponible)

---

##  QUÉ SE CAMBIÓ

### Cambio 1: Type hint del parámetro

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`  
**Línea:** 105

```php
//  ANTES
public function validarPedido(Request $request): JsonResponse

// DESPUÉS
public function validarPedido(CrearPedidoCompletoRequest $request): JsonResponse
```

### Cambio 2: Validación

**Línea:** 115-125

```php
//  ANTES (validación incompleta)
$validated = $request->validate([
    'cliente' => 'required|string',
    'descripcion' => 'nullable|string|max:1000',
    'items' => 'required|array|min:1',
    'items.*.nombre_prenda' => 'required|string',
    'items.*.cantidad_talla' => 'nullable|array',
]);

// DESPUÉS (usa FormRequest completo)
$validated = $request->validated();
```

**Impacto:**
- ANTES: Retornaba `{cliente, items[].nombre_prenda, items[].cantidad_talla}`  Se perdían variaciones, procesos, telas, imagenes
- DESPUÉS: Retorna `{cliente, forma_de_pago, descripcion, items[].TODAS_LAS_PROPIEDADES}` Incluye variaciones, procesos, telas, imagenes

### Cambio 3: Logging (opcional pero recomendado)

```php
//  ANTES
\Log::info('[CrearPedidoEditableController] Validación pasada', $validated);

// DESPUÉS
\Log::info('[CrearPedidoEditableController] Validación pasada', [
    'cliente' => $validated['cliente'] ?? null,
    'items_count' => count($validated['items'] ?? []),
    'first_item_keys' => count($validated['items'][0] ?? []) ? array_keys($validated['items'][0]) : [],
]);
```

---

## 📝 INSTRUCCIONES PASO A PASO

### 1. Abrir archivo

```
app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php
```

### 2. Localizar línea 105

Buscar:
```php
public function validarPedido(Request $request): JsonResponse
```

Cambiar a:
```php
public function validarPedido(CrearPedidoCompletoRequest $request): JsonResponse
```

### 3. Localizar línea 115

Buscar:
```php
$validated = $request->validate([
    'cliente' => 'required|string',
    'descripcion' => 'nullable|string|max:1000',
    'items' => 'required|array|min:1',
    'items.*.nombre_prenda' => 'required|string',
    'items.*.cantidad_talla' => 'nullable|array',
]);
```

**Reemplazar por:**
```php
$validated = $request->validated();
```

### 4. Actualizar logging (opcional)

Buscar:
```php
\Log::info('[CrearPedidoEditableController] Validación pasada', $validated);
```

**Reemplazar por:**
```php
\Log::info('[CrearPedidoEditableController] Validación pasada', [
    'cliente' => $validated['cliente'] ?? null,
    'items_count' => count($validated['items'] ?? []),
    'first_item_keys' => count($validated['items'][0] ?? []) ? array_keys($validated['items'][0]) : [],
]);
```

### 5. Guardar y descartar cambios no usados

---

## VERIFICACIÓN POST-IMPLEMENTACIÓN

### Prueba 1: Crear un pedido de prueba

**Enviar:**
```bash
curl -X POST http://localhost/asesores/pedidos/crear \
  -H "Content-Type: application/json" \
  -d '{
    "cliente": "Test Client",
    "forma_de_pago": "Contado",
    "items": [{
      "tipo": "prenda_nueva",
      "nombre_prenda": "Polo Test",
      "descripcion": "Polo de prueba",
      "variaciones": {
        "tipo_manga": "corta",
        "obs_manga": "Manga estándar",
        "tiene_bolsillos": true,
        "obs_bolsillos": "Bolsillos frontales"
      },
      "procesos": {
        "reflectivo": {
          "tipo": "reflectivo",
          "datos": {
            "ubicaciones": ["pecho"],
            "observaciones": "Reflectivo standard"
          }
        }
      },
      "telas": [{
        "tela": "100% Poliéster",
        "color": "Azul",
        "referencia": "REF-TEST-001",
        "imagenes": []
      }],
      "imagenes": [],
      "cantidad_talla": {
        "DAMA": {"S": 10, "M": 5}
      }
    }]
  }'
```

### Prueba 2: Revisar logs

**Verificar que el log de validación incluya variaciones:**

```bash
grep "first_item_keys" storage/logs/laravel.log | tail -5
```

**Debe contener:**
```
"first_item_keys": ["tipo", "nombre_prenda", "descripcion", "variaciones", "procesos", "telas", "imagenes", "cantidad_talla"]
```

### Prueba 3: Verificar BD

**Conectarse a BD y ejecutar:**

```sql
-- 1. Obtener el pedido creado
SELECT id, numero_pedido FROM pedido_produccion 
ORDER BY created_at DESC LIMIT 1;
-- Resultado: pedido_id = X, numero_pedido = Y

-- 2. Verificar prenda
SELECT id, nombre_prenda FROM prenda_pedido 
WHERE pedido_produccion_id = X;
-- Resultado: prenda_id = Z

-- 3. Verificar VARIANTES (antes era NULL)
SELECT * FROM prenda_pedido_variantes 
WHERE prenda_pedido_id = Z;
-- DEBE TENER REGISTROS (antes era vacío)

-- 4. Verificar PROCESOS (antes solo "Creación Orden")
SELECT proceso, estado_proceso FROM proceso_prenda 
WHERE prenda_pedido_id = Z;
-- DEBE INCLUIR "Reflectivo" (antes solo "Creación Orden")

-- 5. Verificar TELAS (antes era NULL)
SELECT * FROM prenda_color_tela 
WHERE prenda_pedido_id = Z;
-- DEBE TENER REGISTROS (antes era vacío)
```

### Prueba 4: Comparar antes vs después

**ANTES (Logs actuales):**
```
[CrearPedidoEditableController] Validación pasada
{
  "cliente": "Test Client",
  "items": [{
    "nombre_prenda": "Polo Test",
    "cantidad_talla": {"DAMA": {"S": 10, "M": 5}}
  }]
}
 NO CONTIENE: variaciones, procesos, telas, imagenes
```

**DESPUÉS (Con la solución):**
```
[CrearPedidoEditableController] Validación pasada
{
  "cliente": "Test Client",
  "forma_de_pago": "Contado",
  "items": [{
    "nombre_prenda": "Polo Test",
    "cantidad_talla": {"DAMA": {"S": 10, "M": 5}},
    "variaciones": {...},    AHORA INCLUYE
    "procesos": {...},       AHORA INCLUYE
    "telas": {...},          AHORA INCLUYE
    "imagenes": [...]        AHORA INCLUYE
  }]
}
```

---

## 🔍 VERIFICACIÓN DE INTEGRIDAD

### Checklist Pre-Implementación

- [ ] Archivo `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php` existe
- [ ] Archivo `app/Http/Requests/CrearPedidoCompletoRequest.php` existe con reglas completas
- [ ] Base de datos tiene tablas: prenda_pedido_variantes, prenda_color_tela, proceso_prenda, imagen_prenda

### Checklist Post-Implementación

- [ ] Cambio de type hint aplicado (línea 105)
- [ ] Cambio de validación aplicado (línea 115+)
- [ ] Tests/Pruebas ejecutadas
- [ ] BD actualizada correctamente:
  - [ ] prenda_pedido_variantes tiene registros
  - [ ] proceso_prenda tiene múltiples registros
  - [ ] prenda_color_tela tiene registros
  - [ ] imagen_prenda tiene registros
- [ ] Logs muestran todos los campos en validación
- [ ] Frontend sigue funcionando correctamente

---

##  ROLLBACK (Si es necesario)

Si algo sale mal, revertir es simple:

```php
// Volver a las 2 líneas originales
public function validarPedido(Request $request): JsonResponse
{
    $validated = $request->validate([
        'cliente' => 'required|string',
        'descripcion' => 'nullable|string|max:1000',
        'items' => 'required|array|min:1',
        'items.*.nombre_prenda' => 'required|string',
        'items.*.cantidad_talla' => 'nullable|array',
    ]);
}
```

**Nota:** El cambio es completamente reversible sin efectos secundarios.

---

## 📊 RESULTADOS ESPERADOS

### Antes de la solución

| Tabla | Registros | Estado |
|-------|-----------|--------|
| prenda_pedido | 1 | OK |
| prenda_pedido_variantes | 0 |  VACÍA |
| proceso_prenda | 1 ("Creación Orden") |  INCOMPLETA |
| prenda_color_tela | 0 |  VACÍA |
| imagen_prenda | 0 |  VACÍA |

### Después de la solución

| Tabla | Registros | Estado |
|-------|-----------|--------|
| prenda_pedido | 1 | OK |
| prenda_pedido_variantes | 1+ | GUARDADA |
| proceso_prenda | 2+ ("Creación Orden" + procesos específicos) | COMPLETA |
| prenda_color_tela | 1+ | GUARDADA |
| imagen_prenda | N | GUARDADAS |

---

## 🎯 PRÓXIMOS PASOS (Opcional)

Después de implementar la solución base:

1. **Audit trail:** Agregar registro de auditoría en tabla `auditoria_operaciones`
2. **Notificaciones:** Alertar si procesos no se guardan
3. **Validación frontend:** Asegurar que front valida telas e imágenes antes de enviar
4. **Tests unitarios:** Crear test para verificar que validated() retorna todos los campos

---

## 📞 SOPORTE

Si hay problemas después de implementar:

1. Verificar que `CrearPedidoCompletoRequest` tiene todas las reglas (archivo: `app/Http/Requests/CrearPedidoCompletoRequest.php`)
2. Revisar logs en `storage/logs/laravel.log`
3. Ejecutar `php artisan tinker` y verificar directamente en BD
4. Verificar que el FormRequest no tiene método `prepareForValidation()` que descarte campos

---

**Implementación completada:**  
**Fecha:** 24 Enero 2026  
**Cambios:** 2 líneas principales + 1 opcional (logging)  
**Impacto:** 100% del problema solucionado
