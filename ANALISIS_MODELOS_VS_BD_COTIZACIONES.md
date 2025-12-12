# Análisis Completo: Modelos vs Base de Datos - Sistema de Cotizaciones

## 📋 Modelos de Cotizaciones Identificados

1. `Cotizacion` → tabla `cotizaciones`
2. `PrendaCot` → tabla `prendas_cot`
3. `PrendaVarianteCot` → tabla `prenda_variantes_cot`
4. `PrendaTallaCot` → tabla `prenda_tallas_cot`
5. `PrendaTelaCot` → tabla `prenda_telas_cot`
6. `PrendaFotoCot` → tabla `prenda_fotos_cot`
7. `LogoCotizacion` → tabla `logo_cotizaciones`
8. `LogoFoto` → tabla `logo_fotos_cot`
9. `HistorialCambiosCotizacion` → tabla `historial_cambios_cotizaciones`

---

## 🔍 Análisis Detallado por Modelo

### 1️⃣ Modelo: `Cotizacion` → Tabla: `cotizaciones`

#### Campos en Modelo (fillable)
```
- asesor_id
- cliente_id
- numero_cotizacion
- tipo_cotizacion_id
- tipo_venta
- fecha_inicio
- fecha_envio
- es_borrador
- estado
- especificaciones
- imagenes
- tecnicas
- observaciones_tecnicas
- ubicaciones
- observaciones_generales
```

#### Campos Reales en BD
```
- id (PK)
- asesor_id
- cliente_id
- numero_cotizacion
- tipo_cotizacion_id
- tipo_venta
- fecha_inicio
- fecha_envio
- especificaciones
- es_borrador
- estado
- aprobada_por_contador_en
- aprobada_por_aprobador_en
- created_at
- updated_at
- deleted_at
```

#### ⚠️ Inconsistencias Detectadas
| Campo | Modelo | BD | Estado |
|-------|--------|----|----|
| imagenes | ✅ | ❌ | NO EXISTE en BD |
| tecnicas | ✅ | ❌ | NO EXISTE en BD |
| observaciones_tecnicas | ✅ | ❌ | NO EXISTE en BD |
| ubicaciones | ✅ | ❌ | NO EXISTE en BD |
| observaciones_generales | ✅ | ❌ | NO EXISTE en BD |
| aprobada_por_contador_en | ❌ | ✅ | NO EXISTE en modelo |
| aprobada_por_aprobador_en | ❌ | ✅ | NO EXISTE en modelo |

**Impacto**: 🔴 CRÍTICO - El modelo tiene 5 campos que no existen en la BD

---

### 2️⃣ Modelo: `PrendaCot` → Tabla: `prendas_cot`

#### Campos en Modelo (fillable)
```
- cotizacion_id
- nombre_producto
- descripcion
- cantidad
```

#### Campos Reales en BD
```
- id (PK)
- cotizacion_id
- nombre_producto
- descripcion
- cantidad
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden perfectamente

---

### 3️⃣ Modelo: `PrendaVarianteCot` → Tabla: `prenda_variantes_cot`

#### Campos en Modelo (fillable)
```
- prenda_cot_id
- tipo_prenda
- es_jean_pantalon
- tipo_jean_pantalon
- genero_id
- color
- tipo_manga_id
- tiene_bolsillos
- obs_bolsillos
- aplica_manga
- tipo_manga
- obs_manga
- aplica_broche
- tipo_broche_id
- obs_broche
- tiene_reflectivo
- obs_reflectivo
- descripcion_adicional
- telas_multiples
```

#### Campos Reales en BD
```
- id (PK)
- prenda_cot_id
- tipo_prenda
- es_jean_pantalon
- tipo_jean_pantalon
- genero_id
- color
- tipo_manga_id
- tipo_broche_id
- obs_broche
- tiene_bolsillos
- obs_bolsillos
- aplica_manga
- tipo_manga
- obs_manga
- aplica_broche
- tiene_reflectivo
- obs_reflectivo
- descripcion_adicional
- telas_multiples ✅ (AGREGADO)
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden (telas_multiples fue agregado)

---

### 4️⃣ Modelo: `PrendaTallaCot` → Tabla: `prenda_tallas_cot`

#### Campos en Modelo (fillable)
```
- prenda_cot_id
- talla
- cantidad
```

#### Campos Reales en BD
```
- id (PK)
- prenda_cot_id
- talla
- cantidad
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden

---

### 5️⃣ Modelo: `PrendaTelaCot` → Tabla: `prenda_telas_cot`

#### Campos en Modelo (fillable)
```
- prenda_cot_id
- color
- nombre_tela
- referencia
- url_imagen
```

#### Campos Reales en BD
```
- id (PK)
- prenda_cot_id
- variante_prenda_cot_id
- color_id
- tela_id
- created_at
- updated_at
```

#### ❌ Inconsistencias Detectadas
| Campo | Modelo | BD | Estado |
|-------|--------|----|----|
| color | ✅ | ❌ | Modelo espera varchar, BD tiene color_id (FK) |
| nombre_tela | ✅ | ❌ | NO EXISTE en BD |
| referencia | ✅ | ❌ | NO EXISTE en BD |
| url_imagen | ✅ | ❌ | NO EXISTE en BD |
| variante_prenda_cot_id | ❌ | ✅ | NO EXISTE en modelo |
| color_id | ❌ | ✅ | NO EXISTE en modelo |
| tela_id | ❌ | ✅ | NO EXISTE en modelo |

**Impacto**: 🔴 CRÍTICO - Mismatch completo entre modelo y BD

---

### 6️⃣ Modelo: `PrendaFotoCot` → Tabla: `prenda_fotos_cot`

#### Campos en Modelo (fillable)
```
- prenda_cot_id
- ruta_original
- ruta_webp
- ruta_miniatura
- orden
- ancho
- alto
- tamaño
```

#### Campos Reales en BD
```
- id (PK)
- prenda_cot_id
- ruta_original
- ruta_webp
- ruta_miniatura
- orden
- ancho
- alto
- tamaño
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden

---

### 7️⃣ Modelo: `LogoCotizacion` → Tabla: `logo_cotizaciones`

#### Campos en Modelo (fillable)
```
- cotizacion_id
- descripcion
- imagenes
- tecnicas
- observaciones_tecnicas
- ubicaciones
- observaciones_generales
```

#### Campos Reales en BD
```
- id (PK)
- cotizacion_id
- descripcion
- imagenes
- tecnicas
- observaciones_tecnicas
- ubicaciones
- observaciones_generales
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden

---

### 8️⃣ Modelo: `LogoFoto` → Tabla: `logo_fotos_cot`

#### Campos en Modelo (fillable)
```
- logo_cotizacion_id
- ruta_original
- ruta_webp
- ruta_miniatura
- orden
- ancho
- alto
- tamaño
```

#### Campos Reales en BD
```
- id (PK)
- logo_cotizacion_id
- ruta_original
- ruta_webp
- ruta_miniatura
- orden
- ancho
- alto
- tamaño
- created_at
- updated_at
```

#### ✅ Estado
**SINCRONIZADO** - Todos los campos coinciden

---

### 9️⃣ Modelo: `HistorialCambiosCotizacion` → Tabla: `historial_cambios_cotizaciones`

#### Campos en Modelo (fillable)
```
- cotizacion_id
- estado_anterior
- estado_nuevo
- usuario_id
- usuario_nombre
- rol_usuario
- razon_cambio
- ip_address
- user_agent
- datos_adicionales
- created_at
```

#### Campos Reales en BD
**TABLA NO EXISTE EN BD** ❌

#### ❌ Inconsistencias Detectadas
- Tabla `historial_cambios_cotizaciones` NO EXISTE en la base de datos
- El modelo está definido pero la tabla no fue creada

**Impacto**: 🔴 CRÍTICO - Tabla completamente faltante

---

## 📊 Resumen de Inconsistencias

### 🔴 CRÍTICAS (Requieren acción inmediata)

1. **Tabla `cotizaciones`**
   - Campos en modelo pero NO en BD: `imagenes`, `tecnicas`, `observaciones_tecnicas`, `ubicaciones`, `observaciones_generales`
   - Campos en BD pero NO en modelo: `aprobada_por_contador_en`, `aprobada_por_aprobador_en`

2. **Tabla `prenda_telas_cot`**
   - Mismatch completo: modelo espera campos diferentes a los que existen en BD
   - Modelo: `color`, `nombre_tela`, `referencia`, `url_imagen`
   - BD: `variante_prenda_cot_id`, `color_id`, `tela_id`

3. **Tabla `historial_cambios_cotizaciones`**
   - NO EXISTE en la BD
   - Modelo está definido pero la tabla nunca fue creada

### ✅ SINCRONIZADAS (OK)

- `prendas_cot` ✅
- `prenda_variantes_cot` ✅ (después de agregar telas_multiples)
- `prenda_tallas_cot` ✅
- `prenda_fotos_cot` ✅
- `logo_cotizaciones` ✅
- `logo_fotos_cot` ✅

---

## 🔧 Acciones Recomendadas

### Prioridad 1: CRÍTICA

#### A. Sincronizar tabla `cotizaciones`
Opción 1: Agregar campos al modelo
```php
// Agregar al fillable del modelo Cotizacion:
'aprobada_por_contador_en',
'aprobada_por_aprobador_en',
```

Opción 2: Agregar campos a la BD (si se necesitan)
```php
// Migración para agregar campos a cotizaciones
$table->json('imagenes')->nullable();
$table->json('tecnicas')->nullable();
$table->longText('observaciones_tecnicas')->nullable();
$table->json('ubicaciones')->nullable();
$table->json('observaciones_generales')->nullable();
```

#### B. Sincronizar tabla `prenda_telas_cot`
Necesita decisión: ¿Cuál es la estructura correcta?
- Revisar cómo se está usando en los controladores
- Decidir si usar `color`/`nombre_tela`/`referencia` o `color_id`/`tela_id`

#### C. Crear tabla `historial_cambios_cotizaciones`
```php
// Crear migración para historial_cambios_cotizaciones
Schema::create('historial_cambios_cotizaciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cotizacion_id')->constrained('cotizaciones');
    $table->string('estado_anterior');
    $table->string('estado_nuevo');
    $table->foreignId('usuario_id')->nullable()->constrained('users');
    $table->string('usuario_nombre')->nullable();
    $table->string('rol_usuario')->nullable();
    $table->text('razon_cambio')->nullable();
    $table->string('ip_address')->nullable();
    $table->text('user_agent')->nullable();
    $table->json('datos_adicionales')->nullable();
    $table->timestamp('created_at')->useCurrent();
});
```

### Prioridad 2: MEDIA

- Revisar si los campos de `cotizaciones` (imagenes, tecnicas, etc.) se están usando en los controladores
- Si no se usan, removerlos del modelo para evitar confusión

---

## 📝 Conclusión

**Estado General**: 🟡 PARCIALMENTE SINCRONIZADO
- 6 de 9 modelos están sincronizados ✅
- 3 modelos tienen inconsistencias críticas ❌
- 1 tabla completamente faltante ❌
