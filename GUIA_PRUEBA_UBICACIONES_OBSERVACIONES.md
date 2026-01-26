# 🧪 PRUEBA Y VERIFICACIÓN - Ubicaciones y Observaciones

## Después de implementar los cambios, ejecutar estas pruebas:

### 1️⃣ CREAR UN PEDIDO DE PRUEBA

1. Abrir formulario de pedido
2. Crear pedido con:
   - **Prenda:** Cualquiera
   - **Proceso:** Reflectivo
   - **Ubicaciones:** Pecho, Espalda
   - **Observaciones:** "Bordo con hilo plateado"
   - **Tallas:** DAMA S:10, M:5

### 2️⃣ VERIFICAR LOGS EN REAL TIME

```bash
# En terminal del servidor Laravel
tail -f storage/logs/laravel.log | grep "PedidoWebService"
```

**Debe mostrar:**
```
[PedidoWebService] 🔍 Procesando tipo: reflectivo
[PedidoWebService] Creando proceso
    ubicaciones_raw: ["Pecho","Espalda"]
    observaciones_raw: "Bordo con hilo plateado"
[PedidoWebService] Proceso creado
    ubicaciones_guardadas: "[\"Pecho\",\"Espalda\"]"
    observaciones_guardadas: "Bordo con hilo plateado"
```

### 3️⃣ VERIFICAR BASE DE DATOS

```sql
-- Obtener el proceso más reciente
SELECT 
    id,
    prenda_pedido_id,
    tipo_proceso_id,
    ubicaciones,
    observaciones,
    datos_adicionales,
    estado
FROM pedidos_procesos_prenda_detalles
ORDER BY created_at DESC
LIMIT 1;
```

**Debe retornar:**
```
id: XXXX
prenda_pedido_id: YYYY
tipo_proceso_id: Z (Reflectivo)
ubicaciones: ["Pecho","Espalda"]          ← JSON CORRECTO
observaciones: Bordo con hilo plateado   ← TEXTO CORRECTO (no NULL)
datos_adicionales: {...}
estado: PENDIENTE
```

### 4️⃣ VERIFICAR EN LECTURA (Frontend)

1. Abrir recibo/pedido guardado
2. Ver si aparecen correctamente:
   - Ubicaciones listadas
   - Observaciones mostradas
   - Tallas del proceso visibles

### 5️⃣ QUERY DE AUDITORÍA COMPLETA

```sql
-- Query completa con JOINs
SELECT 
    p.numero_pedido,
    pr.nombre_prenda,
    tp.nombre as tipo_proceso,
    ppd.id as proceso_id,
    ppd.ubicaciones,
    ppd.observaciones,
    ppd.estado,
    COUNT(ppt.id) as tallas_count,
    GROUP_CONCAT(ppt.genero, ':', ppt.talla, '-', ppt.cantidad) as tallas_detalle,
    COUNT(ppi.id) as imagenes_count,
    ppd.created_at
FROM pedidos_procesos_prenda_detalles ppd
    INNER JOIN prenda_pedido pr ON pr.id = ppd.prenda_pedido_id
    INNER JOIN pedido_produccion p ON p.id = pr.pedido_produccion_id
    INNER JOIN tipos_procesos tp ON tp.id = ppd.tipo_proceso_id
    LEFT JOIN procesos_prenda_tallas ppt ON ppt.proceso_prenda_detalle_id = ppd.id
    LEFT JOIN pedidos_process_imagenes ppi ON ppi.proceso_prenda_detalle_id = ppd.id
WHERE p.numero_pedido = XXXX  -- Cambiar número de pedido
GROUP BY ppd.id
ORDER BY ppd.created_at DESC;
```

### 6️⃣ COMPARACIÓN: ANTES VS DESPUÉS

**ANTES (Problema):**
```
ubicaciones: []
observaciones: NULL
```

**DESPUÉS (Esperado):**
```
ubicaciones: ["Pecho","Espalda"]
observaciones: "Bordo con hilo plateado"
```

---

## 🚨 SI AÚN HAY PROBLEMAS

### Caso A: `ubicaciones` sigue vacío

1. Verificar en logs si `ubicaciones_raw` está vacío
2. Si está vacío en logs → Problema en **Normalizer**
3. Agregar más console.log en `payload-normalizer-v3-definitiva.js` línea 85+

### Caso B: `observaciones` sigue NULL

1. Verificar en logs si `observaciones_raw` tiene valor
2. Si tiene valor en logs pero es NULL en BD → Problema en **PedidoWebService** validación
3. Revisar que `$observaciones` pase la validación de string

### Caso C: Logs muestran valores correctos pero BD está vacía

1. Verificar si `PedidosProcesosPrendaDetalle::create()` retorna error silencioso
2. Revisar `$fillable` del modelo
3. Ejecutar query raw:
```sql
INSERT INTO pedidos_procesos_prenda_detalles 
    (prenda_pedido_id, tipo_proceso_id, ubicaciones, observaciones, estado)
VALUES (1, 1, '["Pecho"]', 'Test', 'PENDIENTE');
```

---

## 📊 INDICADORES DE ÉXITO

- Logs muestran `ubicaciones_guardadas` con array JSON
- Logs muestran `observaciones_guardadas` con texto (no NULL)
- BD contiene JSON correcto en `ubicaciones`
- BD contiene texto en `observaciones`
- Frontend renderiza ubicaciones en recibo
- Frontend renderiza observaciones en recibo
- Lectura posterior no muestra datos perdidos

## 🔄 ROLLBACK SI ES NECESARIO

Si algo falla, los cambios son seguro reversibles:

**PedidoWebService.php:**
- Solo agregó extracción más robusta
- Sigue usando `json_encode()` igual que antes
- Validación adicional no rompe casos existentes

**Normalizer v3:**
- Solo agregó búsqueda en múltiples niveles
- Compatible hacia atrás
- Más tolerante a variaciones

**CERO cambios en:** Modelo, Migraciones, Rutas, Validación
