# 📊 ANÁLISIS DE ESTRUCTURA DE PRENDAS Y COTIZACIONES

## 🎯 PROBLEMA IDENTIFICADO

Cuando se envía una cotización, las prendas, variaciones e imágenes deben guardarse de forma normalizada. Actualmente hay **dos enfoques conflictivos**:

### Enfoque 1: Normalizado (Recomendado - DDD)
- **Tabla:** `prendas_cot` (prendas principales)
- **Relaciones:**
  - `prenda_fotos_cot` (fotos de prendas)
  - `prenda_telas_cot` (telas/colores)
  - `prenda_tallas_cot` (tallas)
  - `prenda_variantes_cot` (variantes)

### Enfoque 2: JSON (Actual - Legacy)
- **Campo:** `cotizaciones.productos` (JSON)
- **Almacena:** Todo en un solo campo JSON

---

## 📋 TABLAS ACTUALES

### Tabla: `cotizaciones`
```
id (PK)
asesor_id (FK → users)
cliente_id (FK → clientes) ✅ NUEVO
numero_cotizacion
tipo_cotizacion_id (FK → tipos_cotizacion)
tipo_venta (enum M,D,X) ✅ NUEVO
fecha_inicio
fecha_envio
especificaciones (JSON) ✅ NUEVO
es_borrador
estado
created_at, updated_at, deleted_at
```

### Tabla: `prendas_cot` (Normalizada)
```
id (PK)
cotizacion_id (FK → cotizaciones)
nombre_producto
descripcion
cantidad
created_at, updated_at
```

### Tabla: `prenda_fotos_cot` (Imágenes)
```
id (PK)
prenda_id (FK → prendas_cot)
url
nombre
created_at, updated_at
```

### Tabla: `prenda_telas_cot` (Telas/Colores)
```
id (PK)
prenda_id (FK → prendas_cot)
color
nombre_tela
referencia
url_imagen
created_at, updated_at
```

### Tabla: `prenda_tallas_cot` (Tallas)
```
id (PK)
prenda_id (FK → prendas_cot)
talla
cantidad
created_at, updated_at
```

### Tabla: `prenda_variantes_cot` (Variantes)
```
id (PK)
prenda_id (FK → prendas_cot)
tipo_prenda
es_jean_pantalon
tipo_jean_pantalon
genero_id
color
tiene_bolsillos
obs_bolsillos
aplica_manga
tipo_manga
obs_manga
aplica_broche
tipo_broche_id
obs_broche
tiene_reflectivo
obs_reflectivo
descripcion_adicional
created_at, updated_at
```

---

## 🔄 FLUJO DE GUARDADO (Propuesto - DDD)

### Cuando se envía una cotización:

```
1. Crear Cotización
   ├─ asesor_id (del usuario logueado)
   ├─ cliente_id (obtener/crear cliente)
   ├─ tipo_venta (M, D, X)
   ├─ especificaciones (array JSON)
   └─ fecha_inicio (auto)

2. Para cada prenda en el formulario:
   ├─ Crear registro en prendas_cot
   │  └─ nombre_producto, descripcion, cantidad
   │
   ├─ Guardar fotos en prenda_fotos_cot
   │  └─ url, nombre
   │
   ├─ Guardar telas en prenda_telas_cot
   │  └─ color, nombre_tela, referencia, url_imagen
   │
   ├─ Guardar tallas en prenda_tallas_cot
   │  └─ talla, cantidad
   │
   └─ Guardar variantes en prenda_variantes_cot
      └─ tipo_prenda, genero_id, tipo_manga, tipo_broche, etc.
```

---

## 🗑️ TABLAS A ELIMINAR (Legacy)

Si migramos completamente a DDD normalizado, podemos eliminar:

1. ❌ `cotizaciones.productos` (JSON) - Usar `prendas_cot` en su lugar
2. ❌ `prendas_cotizaciones` (si existe) - Usar `prendas_cot` en su lugar
3. ❌ `prenda_cotizacion_friendly` (si existe) - Usar `prendas_cot` en su lugar

---

## ✅ TABLAS A MANTENER (Normalizadas)

1. ✅ `prendas_cot` - Prendas principales
2. ✅ `prenda_fotos_cot` - Imágenes de prendas
3. ✅ `prenda_telas_cot` - Telas/colores
4. ✅ `prenda_tallas_cot` - Tallas
5. ✅ `prenda_variantes_cot` - Variantes

---

## 🎯 RECOMENDACIÓN

### Opción A: Migración Completa a DDD (Recomendado)
- Usar tablas normalizadas para prendas
- Eliminar campo `productos` JSON de cotizaciones
- Crear servicio `GuardarPrendasCotizacionService`
- Actualizar Entity `Cotizacion` para usar relaciones

**Ventajas:**
- ✅ Datos normalizados
- ✅ Consultas más eficientes
- ✅ Integridad referencial
- ✅ Fácil de mantener

**Desventajas:**
- ⚠️ Requiere migración de datos
- ⚠️ Más tablas

### Opción B: Mantener JSON (Actual)
- Guardar prendas en `cotizaciones.productos` como JSON
- Mantener compatibilidad con código actual
- Procesar JSON en aplicación

**Ventajas:**
- ✅ Cambios mínimos
- ✅ Rápido de implementar

**Desventajas:**
- ❌ Datos desnormalizados
- ❌ Difícil de consultar
- ❌ Problemas de integridad

---

## 📝 PRÓXIMOS PASOS

1. **Decidir enfoque:** DDD normalizado vs JSON
2. **Si DDD:** Crear servicio para guardar prendas
3. **Actualizar controlador** para usar el nuevo servicio
4. **Migrar datos** existentes si es necesario
5. **Eliminar tablas legacy** cuando esté completo

