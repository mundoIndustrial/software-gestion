# Análisis de Tablas de Cotizaciones - Sistema DDD

## 📊 Resumen General

### Tablas Existentes del Sistema DDD (terminan en "_cot")

#### ✅ Tabla: `cotizaciones` (ANTIGUA - Sistema Anterior)
- **Registros**: 42
- **Campos principales**:
  - `id` (PK)
  - `asesor_id` (FK → users)
  - `cliente_id` (FK → clientes)
  - `numero_cotizacion` (varchar)
  - `tipo_cotizacion_id` (FK → tipos_cotizacion)
  - `tipo_venta` (enum: M, D, X)
  - `fecha_inicio` (datetime)
  - `fecha_envio` (date)
  - `especificaciones` (json)
  - `es_borrador` (tinyint)
  - `estado` (varchar)
  - `aprobada_por_contador_en` (timestamp)
  - `aprobada_por_aprobador_en` (timestamp)
  - `created_at`, `updated_at`, `deleted_at`

#### ✅ Tabla: `prendas_cot` (NUEVA - Sistema DDD)
- **Registros**: 17
- **Campos**:
  - `id` (PK)
  - `cotizacion_id` (FK → cotizaciones)
  - `nombre_producto` (varchar)
  - `descripcion` (text)
  - `cantidad` (int)
  - `created_at`, `updated_at`

#### ✅ Tabla: `prenda_variantes_cot` (NUEVA - Sistema DDD)
- **Registros**: 13
- **Campos**:
  - `id` (PK)
  - `prenda_cot_id` (FK → prendas_cot)
  - `tipo_prenda` (varchar)
  - `es_jean_pantalon` (tinyint)
  - `tipo_jean_pantalon` (varchar)
  - `genero_id` (FK → generos_prenda)
  - `color` (varchar)
  - `tipo_manga_id` (FK → tipos_manga)
  - `tipo_broche_id` (FK → tipos_broche)
  - `obs_broche` (text)
  - `tiene_bolsillos` (tinyint)
  - `obs_bolsillos` (text)
  - `aplica_manga` (tinyint)
  - `tipo_manga` (varchar)
  - `obs_manga` (text)
  - `aplica_broche` (tinyint)
  - `tiene_reflectivo` (tinyint)
  - `obs_reflectivo` (text)
  - `descripcion_adicional` (text)
  - `created_at`, `updated_at`

#### ✅ Tabla: `prenda_telas_cot` (NUEVA - Sistema DDD)
- **Registros**: 0
- **Campos**:
  - `id` (PK)
  - `prenda_cot_id` (FK → prendas_cot)
  - `variante_prenda_cot_id` (FK → prenda_variantes_cot) ✅ EXISTE
  - `color_id` (FK → colores)
  - `tela_id` (FK → telas)
  - `created_at`, `updated_at`

#### ✅ Tabla: `prenda_tallas_cot` (NUEVA - Sistema DDD)
- **Registros**: 69
- **Campos**:
  - `id` (PK)
  - `prenda_cot_id` (FK → prendas_cot)
  - `talla` (varchar)
  - `cantidad` (int)
  - `created_at`, `updated_at`

#### ✅ Tabla: `prenda_fotos_cot` (NUEVA - Sistema DDD)
- **Registros**: 6
- **Campos**:
  - `id` (PK)
  - `prenda_cot_id` (FK → prendas_cot)
  - `ruta_original` (varchar)
  - `ruta_webp` (varchar)
  - `ruta_miniatura` (varchar)
  - `orden` (int)
  - `ancho` (int)
  - `alto` (int)
  - `tamaño` (int)
  - `created_at`, `updated_at`

#### ✅ Tabla: `prenda_tela_fotos_cot` (NUEVA - Sistema DDD)
- **Registros**: 7
- **Campos**:
  - `id` (PK)
  - `prenda_cot_id` (FK → prendas_cot)
  - `ruta_original` (varchar)
  - `ruta_webp` (varchar)
  - `ruta_miniatura` (varchar)
  - `orden` (int)
  - `ancho` (int)
  - `alto` (int)
  - `tamaño` (int)
  - `created_at`, `updated_at`

#### ✅ Tabla: `logo_fotos_cot` (NUEVA - Sistema DDD)
- **Registros**: 13
- **Campos**:
  - `id` (PK)
  - `logo_cotizacion_id` (FK → logo_cotizaciones)
  - `ruta_original` (varchar)
  - `ruta_webp` (varchar)
  - `ruta_miniatura` (varchar)
  - `orden` (int)
  - `ancho` (int)
  - `alto` (int)
  - `tamaño` (int)
  - `created_at`, `updated_at`

#### ✅ Tabla: `logo_cotizaciones` (NUEVA - Sistema DDD)
- **Registros**: 16
- **Campos**:
  - `id` (PK)
  - `cotizacion_id` (FK → cotizaciones)
  - `descripcion` (text)
  - `imagenes` (json)
  - `tecnicas` (json)
  - `observaciones_tecnicas` (longtext)
  - `ubicaciones` (json)
  - `observaciones_generales` (json)
  - `created_at`, `updated_at`

## ✅ Estado de Tablas del Sistema DDD

### Tablas Existentes y Funcionales
- ✅ `cotizaciones` - 42 registros
- ✅ `prendas_cot` - 17 registros
- ✅ `prenda_variantes_cot` - 13 registros
- ✅ `prenda_tallas_cot` - 69 registros
- ✅ `prenda_fotos_cot` - 6 registros
- ✅ `prenda_tela_fotos_cot` - 7 registros
- ✅ `prenda_telas_cot` - 0 registros (tabla vacía pero existe)
- ✅ `logo_fotos_cot` - 13 registros

### Inconsistencias Detectadas
1. **Tabla `prenda_telas_cot`** tiene campos diferentes en el modelo vs. la BD:
   - **Modelo** (`PrendaTelaCot`): `color`, `nombre_tela`, `referencia`, `url_imagen`
   - **BD actual**: `variante_prenda_cot_id`, `color_id`, `tela_id`
   - ⚠️ Mismatch entre modelo y estructura real
   - **Impacto**: El modelo no coincide con la estructura de la BD

2. **Tabla `logo_cotizaciones`** ✅ EXISTE
   - La tabla `logo_fotos_cot` referencia correctamente a `logo_cotizaciones`
   - Estructura verificada: 16 registros

## 📊 Resumen de Datos

### Distribución de Registros por Tabla
| Tabla | Registros | Estado |
|-------|-----------|--------|
| `cotizaciones` | 42 | ✅ Principal |
| `prendas_cot` | 17 | ✅ Activa |
| `prenda_variantes_cot` | 13 | ✅ Activa |
| `prenda_tallas_cot` | 69 | ✅ Activa |
| `prenda_fotos_cot` | 6 | ✅ Activa |
| `prenda_tela_fotos_cot` | 7 | ✅ Activa |
| `prenda_telas_cot` | 0 | ⚠️ Vacía |
| `logo_cotizaciones` | 16 | ✅ Activa |
| `logo_fotos_cot` | 13 | ✅ Activa |

### Tablas Antiguas (Sistema Anterior)
- `prendas_cotizaciones` - 9 registros
- `variantes_prenda` - 9 registros

Estas tablas pueden ser deprecadas una vez que se migre completamente al sistema DDD.

## 🔧 Acciones Recomendadas

### 1. Sincronizar Modelo `PrendaTelaCot` con la BD
- El modelo tiene campos que no existen en la BD
- Necesita actualizar el modelo o la migración para que coincidan

### 2. Verificar Uso de `prenda_telas_cot`
- La tabla está vacía (0 registros)
- Revisar si se está usando correctamente en los controladores

### 3. Revisar Migraciones Abiertas
- Verificar si hay migraciones pendientes que afecten estas tablas
