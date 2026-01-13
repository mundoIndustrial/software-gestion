# 🧪 GUÍA DE PRUEBA - VARIACIONES MANGA/BROCHE

## ✅ VERIFICACIÓN EN 3 PASOS

### PASO 1: Crear un pedido con variaciones
1. Ir a **Crear Pedido Editable**
2. Agregar una prenda con:
   - Nombre: `CAMISA TEST`
   - Talla: `L` Cantidad: `5`
   - **Variaciones:**
     - Manga: `YUT`
     - Broche: `BOTON`
     - Observaciones: (cualquier texto)
3. Completar datos del cliente y hacer clic en **Crear Pedido**

### PASO 2: Verificar en la BD
Ejecutar estas queries en la base de datos:

```sql
-- 1. Verificar que la prenda se creó
SELECT id, numero_pedido, nombre_prenda, tipo_manga_id, tipo_broche_id 
FROM prenda_pedido 
WHERE numero_pedido = (SELECT MAX(numero_pedido) FROM prenda_pedido)
LIMIT 1;

-- 2. Verificar que los tipos se crearon automáticamente
SELECT id, nombre, activo FROM tipos_manga WHERE nombre = 'Yut';
SELECT id, nombre, activo FROM tipos_broche WHERE nombre = 'Boton';

-- 3. Verificar referencias completas
SELECT 
    pp.id,
    pp.nombre_prenda,
    pp.tipo_manga_id,
    tm.nombre as manga_nombre,
    pp.tipo_broche_id,
    tb.nombre as broche_nombre,
    pp.manga_obs,
    pp.broche_obs
FROM prenda_pedido pp
LEFT JOIN tipos_manga tm ON pp.tipo_manga_id = tm.id
LEFT JOIN tipos_broche tb ON pp.tipo_broche_id = tb.id
WHERE pp.numero_pedido = (SELECT MAX(numero_pedido) FROM prenda_pedido)
ORDER BY pp.id DESC
LIMIT 1;
```

### PASO 3: Verificar en la interfaz
1. Ir a **Ver Pedidos** → Buscar el pedido creado
2. En detalle de prendas debe mostrar:
   - ✅ Manga: YUT (con observación)
   - ✅ Broche: BOTON (con observación)
   - ✅ Sin valores NULL

---

## 📊 RESULTADOS ESPERADOS

### ✅ CORRECTO (Después del fix)
```
id | nombre_prenda | tipo_manga_id | tipo_broche_id | manga_nombre | broche_nombre
1  | CAMISA TEST   | 5             | 12             | Yut          | Boton
```

### ❌ INCORRECTO (Antes del fix)
```
id | nombre_prenda | tipo_manga_id | tipo_broche_id | manga_nombre | broche_nombre
1  | CAMISA TEST   | NULL          | NULL           | NULL         | NULL
```

---

## 🔍 VERIFICACIÓN DE LOGS

Ejecutar en terminal:
```bash
tail -f storage/logs/laravel.log | grep -E "✅|❌|manga|broche"
```

Deberías ver mensajes como:
```
✅ [PedidoPrendaService] Manga creada/obtenida {"nombre":"YUT","id":5}
✅ [PedidoPrendaService] Broche creado/obtenido {"nombre":"Boton","id":12}
✅ [PedidoPrendaService] Guardando prenda con observaciones {...}
```

---

## 🧬 CASOS DE PRUEBA ADICIONALES

### Caso 1: Variaciones con diferentes tipos
```json
{
  "variaciones": {
    "manga": {"tipo": "SINMANGA", "observacion": "Sin mangas"},
    "broche": {"tipo": "CIERRE", "observacion": "Cierre premium"},
    "bolsillos": "SI",
    "reflectivo": {"tipo": "PLATEADO", "observacion": "Reflectivo espalda"}
  }
}
```
✅ Debe crear: tipos_manga "Sinmanga", tipos_broche "Cierre", tipos_reflectivo "Plateado"

### Caso 2: Variación sin observación
```json
{
  "variaciones": {
    "manga": {"tipo": "CORTA"}
  }
}
```
✅ Debe crear manga pero sin observación

### Caso 3: Variación como string (compatibilidad)
```json
{
  "variaciones": {
    "manga": "MANGA_LARGA",
    "bolsillos": "NO"
  }
}
```
✅ Debe procesar como tipo directo

---

## ⚠️ CHECKLIST DE VALIDACIÓN

- [ ] El pedido se crea sin errores
- [ ] Los tipos se crean en las tablas `tipos_manga` y `tipos_broche`
- [ ] Las referencias (`tipo_manga_id`, `tipo_broche_id`) NO son NULL
- [ ] Las observaciones se guardan en `manga_obs` y `broche_obs`
- [ ] La interfaz muestra las variaciones correctamente
- [ ] Los logs muestran los mensajes de éxito (✅)
- [ ] Los nombres se normalizan (ej: "YUT" → "Yut")
- [ ] Se marcan como `activo: true` en BD

---

## 🐛 POSIBLES PROBLEMAS Y SOLUCIONES

### Problema: Aún veo NULL en tipo_manga_id
**Posibles causas:**
1. Cache de navegador. **Solución:** `Ctrl+Shift+Supr` (limpiar cache)
2. Ejecutar código viejo. **Solución:** Verificar que el archivo esté actualizado

### Problema: Error en logs "Call to undefined method"
**Posibles causas:**
1. `ColorGeneroMangaBrocheService` no se inyecta. **Solución:** Verificar `PedidosServiceProvider`
2. Errores de auto-load de clases. **Solución:** Ejecutar `php artisan clear-cache && composer dump-autoload`

### Problema: Se crean duplicados de tipos
**Posibles causas:**
1. Diferencia en mayúsculas. **Solución:** Ya se normaliza con `ucfirst(strtolower(trim()))`
2. Base de datos tenía datos previos. **Solución:** Listar tipos y consolidar si es necesario

---

## 📝 NOTAS IMPORTANTES

1. **Auto-creación es segura:** Usa `firstOrCreate()` que previene duplicados
2. **Normalización de nombres:** "YUT" → "Yut", "boton" → "Boton"
3. **Compatibilidad hacia atrás:** Si se envían IDs en lugar de nombres, también funciona
4. **Observaciones independientes:** Se guardan aunque no exista el tipo (para referencia)
5. **No requiere pre-población:** Los tipos se crean bajo demanda

---

## 📞 CONTACTO

Si hay problemas después de aplicar la corrección, verificar:
1. Que `CrearPedidoEditableController.php` tenga el código del FIX
2. Que `PedidoPrendaService.php` tenga el constructor correcto
3. Que `PedidosServiceProvider.php` inyecte `ColorGeneroMangaBrocheService`
4. Ejecutar `php artisan optimize:clear` para limpiar cache
