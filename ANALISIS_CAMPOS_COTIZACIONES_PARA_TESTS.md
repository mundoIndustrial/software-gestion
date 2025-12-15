# 📋 ANÁLISIS COMPLETO: CAMPOS DE COTIZACIONES POR TIPO

**Fecha:** 14 de Diciembre de 2025
**Propósito:** Base para crear tests exhaustivos de todas las cotizaciones

---

## 🎯 TIPOS DE COTIZACIONES IDENTIFICADOS

### 1️⃣ **TIPO PRENDA** (Código: M, P, G)
- **Descripción:** Cotizaciones completas de prendas (camisas, pantalones, etc.)
- **Rutas:** `/cotizaciones-prenda/*`
- **Controller:** `CotizacionPrendaController`

### 2️⃣ **TIPO BORDADO/LOGO** (Código: BORDADO, LOGO)
- **Descripción:** Cotizaciones de bordados y logos
- **Rutas:** `/cotizaciones-bordado/*`
- **Controller:** `CotizacionBordadoController`

### 3️⃣ **TIPO REFLECTIVO**
- **Descripción:** Cotizaciones de materiales reflectivos
- **Rutas:** `/cotizaciones-reflectivo/*` (si existe)
- **Controller:** Posible - Revisar

---

## 📊 TABLA 1: COTIZACION (Tabla Principal)

### Campos Requeridos para Crear:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `asesor_id` | INT | FK → users | ✅ SÍ | 1 |
| `cliente_id` | INT | FK → clientes | ❌ NO | NULL / 123 |
| `numero_cotizacion` | STRING | UNIQUE | ❌ NO* | COT-0001 (Asignado al enviar) |
| `tipo_cotizacion_id` | INT | FK → tipos_cotizacion | ✅ SÍ | 1 (M=Muestra) |
| `tipo_venta` | STRING | Enum: M/D/X | ❌ NO | M |
| `fecha_inicio` | DATE | TIMESTAMP | ✅ SÍ | 2025-12-14 |
| `fecha_envio` | DATE | TIMESTAMP | ❌ NO | NULL (Al enviar) |
| `fecha_enviado_a_aprobador` | DATE | TIMESTAMP | ❌ NO | NULL |
| `es_borrador` | BOOLEAN | Boolean | ✅ SÍ | true/false |
| `estado` | STRING | Enum | ✅ SÍ | 'enviada', 'aceptada', 'rechazada' |
| `especificaciones` | JSON | Array | ❌ NO | {} |
| `imagenes` | JSON | Array | ❌ NO | [] |
| `tecnicas` | JSON | Array | ❌ NO | [] |
| `observaciones_tecnicas` | STRING | TEXT | ❌ NO | "" |
| `ubicaciones` | JSON | Array | ❌ NO | [] |
| `observaciones_generales` | JSON | Array | ❌ NO | [] |

---

## 📊 TABLA 2: PRENDAS_COT (Prendas dentro de Cotización)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `cotizacion_id` | INT | FK → cotizaciones | ✅ SÍ | 1 |
| `nombre_producto` | STRING | TEXT | ✅ SÍ | "Camisa Ejecutiva" |
| `descripcion` | STRING | LONGTEXT | ✅ SÍ | "Camisa de algodón calidad premium..." |
| `cantidad` | INT | INT | ✅ SÍ | 100 |

### Relaciones:
- **prenda_fotos_cot** ← Fotos de la prenda (0 o más)
- **prenda_telas_cot** ← Telas/colores (0 o más)
- **prenda_tallas_cot** ← Tallas (1 o más)
- **prenda_variantes_cot** ← Variantes (0 o más)

---

## 📊 TABLA 3: PRENDA_FOTOS_COT (Fotos de Prendas)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `prenda_cot_id` | INT | FK → prendas_cot | ✅ SÍ | 1 |
| `ruta_original` | STRING | URL | ✅ SÍ | "storage/fotos/prenda_1.jpg" |
| `ruta_webp` | STRING | URL | ❌ NO | "storage/fotos/prenda_1.webp" |
| `ruta_miniatura` | STRING | URL | ❌ NO | "storage/fotos/prenda_1_thumb.jpg" |
| `orden` | INT | INT | ✅ SÍ | 1 |
| `ancho` | INT | INT (pixels) | ❌ NO | 1920 |
| `alto` | INT | INT (pixels) | ❌ NO | 1080 |
| `tamaño` | INT | INT (bytes) | ❌ NO | 524288 |

---

## 📊 TABLA 4: PRENDA_TELAS_COT (Telas/Colores de Prendas)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `prenda_cot_id` | INT | FK → prendas_cot | ✅ SÍ | 1 |
| `variante_prenda_cot_id` | INT | FK → prenda_variantes_cot | ❌ NO | 1 |
| `color_id` | INT | FK → colores | ❌ NO | 15 |
| `tela_id` | INT | FK → telas | ❌ NO | 8 |

---

## 📊 TABLA 5: PRENDA_TALLAS_COT (Tallas)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `prenda_cot_id` | INT | FK → prendas_cot | ✅ SÍ | 1 |
| `talla` | STRING | Enum (XS-5XL) | ✅ SÍ | "M" |
| `cantidad` | INT | INT | ✅ SÍ | 25 |

### Tallas Válidas:
- **Ropa Estándar:** XS, S, M, L, XL, 2XL, 3XL, 4XL, 5XL
- **Números:** 28, 30, 32, 34, 36, 38, 40, 42, 44, 46
- **Otro:** Personalizado

---

## 📊 TABLA 6: PRENDA_VARIANTES_COT (Variantes de Prendas)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `prenda_cot_id` | INT | FK → prendas_cot | ✅ SÍ | 1 |
| `tipo_prenda` | STRING | Enum | ✅ SÍ | "camisa" |
| `es_jean_pantalon` | BOOLEAN | Boolean | ❌ NO | false |
| `tipo_jean_pantalon` | STRING | Enum | ❌ NO | NULL |
| `genero_id` | INT | FK → generos | ✅ SÍ | 1 (Masculino) |
| `color` | STRING | Enum/Color | ✅ SÍ | "Azul" |
| `tipo_manga_id` | INT | FK → tipos_manga | ❌ NO | 1 |
| `aplica_manga` | BOOLEAN | Boolean | ❌ NO | true |
| `tipo_manga` | STRING | Enum | ❌ NO | "corta" |
| `obs_manga` | STRING | TEXT | ❌ NO | "" |
| `aplica_broche` | BOOLEAN | Boolean | ❌ NO | false |
| `tipo_broche_id` | INT | FK → tipos_broche | ❌ NO | NULL |
| `obs_broche` | STRING | TEXT | ❌ NO | "" |
| `tiene_bolsillos` | BOOLEAN | Boolean | ❌ NO | true |
| `obs_bolsillos` | STRING | TEXT | ❌ NO | "" |
| `tiene_reflectivo` | BOOLEAN | Boolean | ❌ NO | false |
| `obs_reflectivo` | STRING | TEXT | ❌ NO | "" |
| `descripcion_adicional` | STRING | TEXT | ❌ NO | "" |
| `telas_multiples` | JSON | Array | ❌ NO | [] |

### Valores Válidos:

**tipo_prenda:**
- camisa, pantalon, chaqueta, chaleco, overol, gorro, guantes, etc.

**genero:**
- Masculino, Femenino, Unisex

**tipo_manga:**
- corta, larga, tres_cuartos, sin_manga

**tipo_broche:**
- botones, cierre, abroches, etc.

---

## 📊 TABLA 7: LOGO_COTIZACIONES (Logos en Cotización)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `cotizacion_id` | INT | FK → cotizaciones | ✅ SÍ | 1 |
| `descripcion` | STRING | TEXT | ✅ SÍ | "Logo bordado en pecho" |
| `imagenes` | JSON | Array | ✅ SÍ | ["url1", "url2"] |
| `tecnicas` | JSON | Array | ✅ SÍ | ["bordado"] |
| `observaciones_tecnicas` | STRING | TEXT | ❌ NO | "" |
| `ubicaciones` | JSON | Array | ✅ SÍ | ["pecho", "espalda"] |
| `observaciones_generales` | JSON | Array | ❌ NO | [] |

---

## 📊 TABLA 8: LOGO_FOTOS_COT (Fotos de Logos)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `logo_cotizacion_id` | INT | FK → logo_cotizaciones | ✅ SÍ | 1 |
| `ruta_original` | STRING | URL | ✅ SÍ | "storage/logos/logo_1.png" |
| `ruta_webp` | STRING | URL | ❌ NO | "storage/logos/logo_1.webp" |
| `ruta_miniatura` | STRING | URL | ❌ NO | "storage/logos/logo_1_thumb.png" |
| `orden` | INT | INT | ✅ SÍ | 1 |
| `ancho` | INT | INT (pixels) | ❌ NO | 500 |
| `alto` | INT | INT (pixels) | ❌ NO | 500 |
| `tamaño` | INT | INT (bytes) | ❌ NO | 102400 |

---

## 📊 TABLA 9: REFLECTIVO_FOTOS_COTIZACION (Fotos de Reflectivo)

### Campos Requeridos:

| Campo | Tipo | Validación | Requerido | Ejemplo |
|-------|------|-----------|-----------|---------|
| `cotizacion_id` | INT | FK → cotizaciones | ✅ SÍ | 1 |
| `ruta_original` | STRING | URL | ✅ SÍ | "storage/reflectivo/r_1.png" |
| `ruta_webp` | STRING | URL | ❌ NO | "storage/reflectivo/r_1.webp" |
| `ruta_miniatura` | STRING | URL | ❌ NO | "storage/reflectivo/r_1_thumb.png" |
| `orden` | INT | INT | ✅ SÍ | 1 |
| `ancho` | INT | INT (pixels) | ❌ NO | 400 |
| `alto` | INT | INT (pixels) | ❌ NO | 400 |
| `tamaño` | INT | INT (bytes) | ❌ NO | 51200 |

---

## 🔑 TIPOS DE COTIZACION (Tabla)

| ID | Código | Nombre | Descripción |
|---|--------|--------|-------------|
| 1 | M | Muestra | Cotización de muestra |
| 2 | P | Prototipo | Cotización de prototipo |
| 3 | G | Grande | Cotización grande |

---

## 🎯 ESTRATEGIA DE TEST

### Test 1: TIPO MUESTRA (M) - 11 Cotizaciones
```
Nombre: "MUESTRA_001" a "MUESTRA_011"
Campos Incluídos:
  ✅ Información básica (cliente, asesor)
  ✅ 1 Prenda Camisa
  ✅ 3 Fotos de prenda
  ✅ 2 Telas/Colores
  ✅ 3 Tallas (S, M, L)
  ✅ 1 Variante completa
  ✅ Validar numero_cotizacion secuencial
```

### Test 2: TIPO PROTOTIPO (P) - 11 Cotizaciones
```
Nombre: "PROTOTIPO_001" a "PROTOTIPO_011"
Campos Incluídos:
  ✅ Información básica
  ✅ 2 Prendas (Camisa + Pantalón)
  ✅ 4 Fotos por prenda
  ✅ 3 Telas por prenda
  ✅ 4 Tallas (XS, S, M, L)
  ✅ Variantes complejas
  ✅ Validar numero_cotizacion secuencial
```

### Test 3: TIPO GRANDE (G) - 11 Cotizaciones
```
Nombre: "GRANDE_001" a "GRANDE_011"
Campos Incluídos:
  ✅ Información básica
  ✅ 3 Prendas (Camisa + Pantalón + Chaqueta)
  ✅ 5 Fotos por prenda
  ✅ 4 Telas por prenda
  ✅ 6 Tallas (XS-2XL)
  ✅ Variantes con opciones (bolsillos, mangas, etc.)
  ✅ Validar numero_cotizacion secuencial
```

### Test 4: TIPO BORDADO - 11 Cotizaciones
```
Nombre: "BORDADO_001" a "BORDADO_011"
Campos Incluídos:
  ✅ Información básica
  ✅ Logo principal + 3 ubicaciones
  ✅ 4 Fotos de logo
  ✅ Técnicas de bordado
  ✅ Observaciones técnicas
  ✅ Validar numero_cotizacion secuencial
```

### Test 5: CONCURRENCIA - Mismo Asesor, Múltiples Cotizaciones Simultáneas
```
Simular:
  - 3 asesores haciendo 11 cotizaciones c/u = 33 cotizaciones
  - Al mismo tiempo (usando async/await o parallelización)
  - Validar que numero_cotizacion sea único y secuencial
  - Sin colisiones ni duplicados
```

---

## 🔢 CAMPOS CRÍTICOS A VALIDAR

1. ✅ **numero_cotizacion** → UNIQUE, SECUENCIAL
2. ✅ **asesor_id** → DEBE ser válido (FK)
3. ✅ **tipo_cotizacion_id** → DEBE estar en tipos_cotizacion
4. ✅ **fecha_inicio** → TIMESTAMP válido
5. ✅ **es_borrador** → BOOLEAN
6. ✅ **estado** → Debe ser uno de: 'enviada', 'aceptada', 'rechazada'
7. ✅ **Fotos** → Rutas válidas, existentes
8. ✅ **JSON fields** → Estructura válida
9. ✅ **FK Integrity** → Todas las claves foráneas válidas
10. ✅ **Tallas** → Solo valores válidos

---

## 📝 RESUMEN

- **Total Cotizaciones a Crear:** 11 × 4 tipos + 11 × 3 asesores (concurrencia) = **77 cotizaciones**
- **Total Prendas:** ~200
- **Total Fotos:** ~600
- **Total Relaciones:** ~1000+
- **Objetivo:** Validar integridad, secuencialidad y concurrencia sin errores

