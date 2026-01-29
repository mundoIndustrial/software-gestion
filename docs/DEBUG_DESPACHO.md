# 🔍 GUÍA DE VERIFICACIÓN - MÓDULO DESPACHO

## Paso 1: Verificar Frontend (Consola del Navegador)

1. Abre el navegador en la página de despacho
2. Presiona **F12** para abrir DevTools
3. Ve a la pestaña **Console**
4. Ingresa valores en la tabla de despacho
5. Haz clic en "Guardar Despacho"
6. En la consola verás mensajes como:
   ```
   📤 Enviando: tipo=prenda, id=2, tallaId=2, dataset= {...}
   📤 Enviando: tipo=prenda, id=1, tallaId=1, dataset= {...}
   ```

**Qué revisar:**
- ¿`tallaId` tiene un valor (ej: 1, 2, 3) o está `null`?
- ¿El `dataset` objeto muestra `{tipo: "prenda", id: "...", tallaId: "..."}`?

## Paso 2: Verificar Backend (Logs)

1. Abre `storage/logs/laravel.log`
2. Ve al final del archivo
3. Busca el texto `Datos recibidos del frontend`
4. Deberías ver:
   ```
   [2026-01-28 21:45:43] local.DEBUG: Datos recibidos del frontend {"datos_raw":{"tipo":"prenda","id":2,"talla_id":2,"pendiente_inicial":0,...}}
   ```

**Qué revisar:**
- ¿El `talla_id` llega con un valor?
- ¿Los valores de `pendiente_inicial`, `parcial_1`, etc. son los que ingresaste?

## Paso 3: Verificar Base de Datos

```sql
SELECT id, tipo_item, item_id, talla_id, pendiente_inicial, parcial_1, pendiente_1 
FROM despacho_parciales 
WHERE pedido_id = <TU_PEDIDO_ID> 
ORDER BY created_at DESC 
LIMIT 5;
```

**Qué revisar:**
- ¿`item_id` tiene el ID de `prenda_pedido_tallas` (ej: 1, 2)?
- ¿`talla_id` tiene el mismo valor que `item_id`?
- ¿Los `pendiente_*` y `parcial_*` tienen los valores correctos?

## Si algo falla:

### Si `tallaId` es NULL en la consola:
- **Causa**: El atributo `data-talla-id` en el HTML está vacío
- **Solución**: Verifica que `$fila->tallaId` tiene valor en la vista
- **Check**: En la vista `show.blade.php`, la línea con `data-talla-id="{{ $fila->tallaId }}"` debe mostrar un número

### Si `talla_id` llega NULL al backend:
- **Causa**: El frontend no lo está enviando
- **Solución**: Verifica en la consola si `tallaId` es nulo
- **Check**: El JavaScript en `guardarDespacho()` debe capturar `fila.dataset.tallaId`

### Si `talla_id` NO se guarda en BD (NULL):
- **Causa**: El backend no lo está guardando
- **Solución**: Verifica los logs para ver si llega
- **Check**: Busca `Datos recibidos del frontend` en los logs

## Información del Sistema

**Tabla: prenda_pedido_tallas**
```
id           - ID único de la talla
prenda_pedido_id - FK a prendas_pedido
genero       - DAMA, CABALLERO, UNISEX
talla        - S, M, L, XL, etc
cantidad     - Cantidad para esta talla
```

**Tabla: despacho_parciales**
```
item_id      - DEBE ser igual a prenda_pedido_tallas.id
talla_id     - Referencia a prenda_pedido_tallas.id (misma que item_id)
tipo_item    - 'prenda' o 'epp'
pendiente_inicial, parcial_1, pendiente_1, parcial_2, pendiente_2, parcial_3, pendiente_3
```

## Flujo Correcto

```
Usuario ingresa datos en tabla
        ↓
JavaScript captura: id, talla_id, pendiente_inicial, parcial_1, etc.
        ↓
Envía JSON: { "despachos": [ { "id": 2, "talla_id": 2, ... } ] }
        ↓
Backend recibe y valida
        ↓
Crea DesparChoParcialesModel con item_id=2, talla_id=2
        ↓
Guarda en BD
```

## Checklist de Requisitos

- [ ] Cada talla = 1 registro en `despacho_parciales`
- [ ] `item_id` = ID de `prenda_pedido_tallas` (la talla específica)
- [ ] `talla_id` = Referencia a la talla (misma que item_id)
- [ ] `tipo_item` = 'prenda' (automático)
- [ ] `usuario_id` = Automático (Auth::id())
- [ ] `fecha_despacho` = Automático (now())
- [ ] `pendiente_inicial`, `parcial_1-3`, `pendiente_1-3` = Exacto del usuario
- [ ] NO hay cálculos automáticos
- [ ] NO se modifica valores ingresados
