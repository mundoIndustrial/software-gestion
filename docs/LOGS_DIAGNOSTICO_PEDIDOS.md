# 📊 LOGS DE DIAGNÓSTICO - CREACIÓN DE PEDIDOS

## 🎯 Objetivo
Identificar cuellos de botella y latencias en la creación de pedidos en:
- `http://localhost:8000/asesores/pedidos-editable/crear-nuevo`
- `http://localhost:8000/asesores/pedidos-editable/crear-desde-cotizacion`

## 📍 Dónde encontrar los logs

```
storage/logs/laravel.log
```

## 🚀 Flujo de Logs Instrumentados

### 1️⃣ CARGA DE PÁGINA - `crearNuevo()` o `crearDesdeCotizacion()`

**Log inicial:**
```
[CREAR-PEDIDO-NUEVO] ⏱️ INICIANDO CARGA DE PÁGINA
[CREAR-DESDE-COTIZACION] ⏱️ INICIANDO CARGA DE PÁGINA
```

**Componentes medidos:**
- ✅ `[CREAR-PEDIDO-NUEVO] 📏 Tallas cargadas` → tiempo_ms
- ✅ `[CREAR-PEDIDO-NUEVO] 📦 Pedidos existentes cargados` → tiempo_ms
- ✅ `[CREAR-PEDIDO-NUEVO] 👥 Clientes cargados` → tiempo_ms
- ✅ `[CREAR-DESDE-COTIZACION] 📋 Cotizaciones cargadas (CON RELACIONES)` → tiempo_ms ⚠️ **CRÍTICO**

**Log final:**
```
[CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA
{
  "tiempo_total_ms": 1234.56,
  "tiempo_tallas_ms": 50,
  "tiempo_pedidos_ms": 150,
  "tiempo_clientes_ms": 200,
  "tiempo_view_ms": 100,
  "resumen": "Tallas: 50ms | Pedidos: 150ms | Clientes: 200ms | View: 100ms | TOTAL: 1234.56ms"
}
```

**Interpretación:**
- Si `tiempo_cotizaciones_ms` > 2000ms → **Cuello de botella en query de cotizaciones**
- Si `tiempo_clientes_ms` > 1000ms → **Cuello de botella en tabla clientes (sin índices)**
- Si `tiempo_view_ms` > 500ms → **Problema en renderizado de vista**

---

### 2️⃣ CREACIÓN DE PEDIDO - POST `/asesores/pedidos-editable/crear`

**Log inicial:**
```
[CREAR-PEDIDO] ⏱️ INICIANDO CREACIÓN TRANSACCIONAL
```

**Pasos desglosados con microtiming:**

```
[CREAR-PEDIDO] ✅ PASO 1: JSON decodificado → tiempo_ms: 5
[CREAR-PEDIDO] ✅ PASO 2: Cliente obtenido/creado → tiempo_ms: 50
[CREAR-PEDIDO] ✅ PASO 3: Pedido normalizado (DTO) → tiempo_ms: 30
[CREAR-PEDIDO] ✅ PASO 5: Pedido base creado → tiempo_ms: 200
[CREAR-PEDIDO] ✅ PASO 6: Carpetas creadas → tiempo_ms: 100
[CREAR-PEDIDO] ✅ PASO 7: Imágenes mapeadas y creadas → tiempo_ms: 5000 ⚠️ CRÍTICO
[CREAR-PEDIDO] ✅ PASO 7B: Imágenes de EPPs procesadas → tiempo_ms: 2000 ⚠️ CRÍTICO
[CREAR-PEDIDO] ✅ PASO 8: Cálculo de cantidades → tiempo_ms: 100
```

**Log final con resumen:**
```
[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA - RESUMEN TOTAL
{
  "tiempo_total_ms": 7500,
  "desglose_pasos": {
    "paso_1_json_ms": 5,
    "paso_2_cliente_ms": 50,
    "paso_3_dto_ms": 30,
    "paso_5_pedido_base_ms": 200,
    "paso_6_carpetas_ms": 100,
    "paso_7_imagenes_ms": 5000,
    "paso_7b_epps_ms": 2000,
    "paso_8_calculo_ms": 100
  },
  "resumen": "JSON: 5ms | Cliente: 50ms | DTO: 30ms | PedidoBase: 200ms | Carpetas: 100ms | Imágenes: 5000ms | EPPs: 2000ms | Cálculo: 100ms | TOTAL: 7500ms"
}
```

**Interpretación:**
- ⚠️ Si `paso_7_imagenes_ms` > 3000ms → **Cuello de botella en procesamiento de imágenes**
- ⚠️ Si `paso_7b_epps_ms` > 1500ms → **Cuello de botella en EPPs**
- ✅ Si `paso_5_pedido_base_ms` > 500ms → Problema en creación del modelo

---

### 3️⃣ RESOLUCIÓN DE IMÁGENES - `ResolutorImagenesService`

**Log inicial:**
```
[RESOLVER-IMAGENES] 📸 INICIANDO EXTRACCIÓN DE IMÁGENES
{
  "archivos_en_request": 10,
  "imagenes_en_dto": 10,
  "tiempo_extraccion_ms": 50
}
```

**Por cada imagen procesada:**
```
[RESOLVER-IMAGENES] ✅ Imagen procesada
{
  "imagen_uid": "img-uuid-abc",
  "ruta": "pedidos/123/prenda/img123.webp",
  "tiempo_guardado_ms": 150
}
```

**Log final:**
```
[RESOLVER-IMAGENES] ✅ Extracción completada
{
  "imagenes_procesadas": 10,
  "imagenes_esperadas": 10,
  "diferencia": 0,
  "tiempo_total_ms": 2000,
  "resumen": "Extracción archivos: 50ms | Procesamiento: 1950ms | TOTAL: 2000ms"
}
```

**Interpretación:**
- Si `diferencia > 0` → **Imágenes perdidas en FormData**
- Si promedio `tiempo_guardado_ms` > 200ms → **Problema en conversión WebP o disco**

---

### 4️⃣ PROCESAMIENTO DE IMÁGENES - `ImageUploadService`

**Por cada imagen:**
```
[IMAGE-UPLOAD] 📤 Iniciando guardado de imagen
{
  "pedido_id": 123,
  "tipo": "prendas",
  "file_size_kb": 500
}

[IMAGE-UPLOAD] ✅ Imagen guardada directamente
{
  "tiempo_total_ms": 150,
  "desglose": {
    "validacion_ms": 5,
    "carga_imagen_ms": 20,
    "guardado_webp_ms": 125
  }
}
```

**Interpretación:**
- Si `guardado_webp_ms` > 200ms → **Problema en conversión WebP (CPU/GD)**
- Si `carga_imagen_ms` > 100ms → **Imagen muy grande**

---

### 5️⃣ MAPEO DE IMÁGENES - `MapeoImagenesService`

```
[MAPEO-IMAGENES] 📸 INICIANDO MAPEO DE IMÁGENES
{
  "pedido_id": 123,
  "prendas": 5,
  "timestamp": "2026-01-29 21:30:45"
}

[MAPEO-IMAGENES] ✅ Mapeo UID→Ruta completado
{
  "imagenes_mapeadas": 15,
  "tiempo_resolver_ms": 2000
}

[MAPEO-IMAGENES] ✨ MAPEO COMPLETADO
{
  "tiempo_total_ms": 2150,
  "resumen": "Resolver: 2000ms | Registros BD: 150ms | TOTAL: 2150ms"
}
```

---

## 🔍 Cómo Analizar los Logs

### 📋 Comando para filtrar logs de creación

```bash
# Ver todos los logs de creación de pedidos
tail -f storage/logs/laravel.log | grep "CREAR-PEDIDO"

# Ver logs de imágenes
tail -f storage/logs/laravel.log | grep "IMAGE-UPLOAD\|RESOLVER-IMAGENES\|MAPEO-IMAGENES"

# Ver resumen rápido (sin debug)
tail -100 storage/logs/laravel.log | grep "✨\|⏱️\|⚠️" | tail -20
```

### 🔴 Escenarios de Problemas Comunes

#### 1. **Página tarda mucho en cargar inicialmente**
```
Buscar: [CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA
Si tiempo_total_ms > 5000ms:
  → Si tiempo_cotizaciones_ms > 2000ms → Optimizar query de cotizaciones
  → Si tiempo_clientes_ms > 1000ms → Agregar índices en tabla clientes
  → Si tiempo_view_ms > 500ms → Optimizar blade template
```

#### 2. **Guardar pedido tarda mucho**
```
Buscar: [CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA
Si tiempo_total_ms > 10000ms:
  → Si paso_7_imagenes_ms > 3000ms → Reducir tamaño de imágenes
  → Si paso_7b_epps_ms > 1500ms → Revisar procesamiento de EPPs
  → Si paso_5_pedido_base_ms > 500ms → Problema en triggers DB
```

#### 3. **Imágenes no se guardan**
```
Buscar: [RESOLVER-IMAGENES] ✅ Extracción completada
Si imagenes_esperadas > imagenes_procesadas:
  → Imagen superior: [RESOLVER-IMAGENES] ❌ ERROR CRÍTICO
  → Problema en FormData del frontend o archivos perdidos
```

#### 4. **Conversión WebP lenta**
```
Buscar: [IMAGE-UPLOAD] ✅ Imagen guardada directamente
Si guardado_webp_ms > 300ms (promedio):
  → Aumentar memoria PHP
  → Reducir resolución de imágenes
  → Verificar CPU disponible
```

---

## 📊 Métricas de Referencia (Tiempos Esperados)

| Componente | Normal | Alerta | Crítico |
|---|---|---|---|
| Carga inicial (sin cotizaciones) | < 500ms | 500-2000ms | > 2000ms |
| Carga de cotizaciones | < 1000ms | 1-2000ms | > 2000ms |
| Carga de clientes | < 300ms | 300-1000ms | > 1000ms |
| Creación pedido (sin imágenes) | < 500ms | 500-1000ms | > 1000ms |
| Procesamiento imagen (500KB) | < 200ms | 200-400ms | > 400ms |
| Mapeo de imágenes | < 3000ms | 3-5000ms | > 5000ms |
| **TOTAL creación (5 imágenes)** | < 3000ms | 3-6000ms | > 6000ms |

---

## 🛠️ Cómo Optimizar Basándose en Logs

### Si PASO 7 (Imágenes) es lento:
1. Verificar tamaño de archivos subidos
2. Revisar `ImageUploadService.php` línea ~95 (conversión WebP)
3. Considerar hacer procesamiento en background con Queue

### Si COTIZACIONES es lento:
1. Agregar índices en tabla `cotizaciones` (asesor_id, estado)
2. Optimizar eager loading en `with(['cliente', 'prendas'...])`
3. Limitar cantidad de prendas por cotización

### Si CLIENTES es lento:
1. Agregar índice en `nombre` y `asesor_id` si es aplicable
2. Usar paginación si hay > 1000 clientes
3. Considerar caché

---

## 📝 Notas Importantes

- ✅ Los logs usan prefijo `[CREAR-PEDIDO]`, `[RESOLVER-IMAGENES]`, etc. para fácil filtrado
- ✅ Todos los tiempos están en **milisegundos (ms)**
- ✅ Los logs incluyen "resumen" en una línea para análisis rápido
- ⚠️ **NO dejar estos logs en producción** - aumentan overhead (2-5%)
- 🔐 Después de debugging, considerar cambiar `Log::info()` a `Log::debug()` para reducir ruido

---

## 🎯 Próximos Pasos

1. **Reproducir el problema** con datos reales
2. **Revisar los logs** buscando los prefijos `[CREAR-PEDIDO]`
3. **Identificar el paso más lento** usando el desglose de tiempos
4. **Aplicar optimizaciones** según la sección "Cómo Optimizar"
5. **Verificar mejoras** comparando logs antes/después

¡Éxito en el debugging! 🚀
