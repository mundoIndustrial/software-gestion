# Análisis Completo de Base de Datos - Mundo Industrial

## 📋 Descripción

Script completo para analizar la estructura y datos de la base de datos del sistema Mundo Industrial.

## 🚀 Uso

```bash
php scripts/analizar_db_completo.php
```

## 📊 Información que Analiza

### 1. **Información General**
- Nombre de la base de datos
- Tamaño total en MB
- Número total de tablas

### 2. **Listado de Tablas**
- Todas las tablas ordenadas por tamaño
- Número de registros por tabla
- Tamaño de datos e índices
- Motor de almacenamiento y collation

### 3. **Tablas de Cotizaciones**
- Identifica todas las tablas relacionadas con cotizaciones
- Muestra registros y tamaño de cada una

### 4. **Tablas de Pedidos**
- Identifica todas las tablas relacionadas con pedidos
- Muestra registros y tamaño de cada una

### 5. **Estructura Detallada**
Analiza la estructura de las tablas principales:
- `cotizaciones`
- `prendas_cot`
- `logo_cotizaciones`
- `reflectivo_cotizacion`
- `pedido_produccion`
- `prendas_pedido`
- `logo_pedido`

Para cada tabla muestra:
- Nombre de columna
- Tipo de dato
- Si acepta NULL
- Claves (PRIMARY, FOREIGN, INDEX)
- Extras (auto_increment, etc.)

### 6. **Relaciones (Foreign Keys)**
- Lista todas las foreign keys definidas
- Muestra la relación entre tablas
- Formato: `tabla.columna → tabla_referenciada.columna`

### 7. **Índices Definidos**
- Muestra todos los índices de las tablas principales
- Distingue entre índices únicos y regulares
- Lista las columnas incluidas en cada índice

### 8. **Análisis de Datos - Cotizaciones**
Estadísticas de cotizaciones:
- Total de cotizaciones
- Distribución por tipo (P, L, PL, R)
- Distribución por estado (borrador, pendiente, aprobado, rechazado)

### 9. **Análisis de Datos - Pedidos**
Estadísticas de pedidos:
- Total de pedidos
- Cantidad total de prendas
- Distribución por estado

### 10. **Análisis de Integridad**
Detecta registros huérfanos:
- Prendas sin cotización
- Logos sin cotización
- Variantes sin prenda
- Tallas sin prenda

### 11. **Análisis de Imágenes**
Cuenta registros en tablas de imágenes:
- `prenda_fotos_cot` - Fotos de prendas
- `prenda_tela_fotos_cot` - Fotos de telas
- `logo_fotos_cot` - Fotos de logos
- `reflectivo_fotos_cotizacion` - Fotos de reflectivos

### 12. **Análisis de Campos JSON**
Verifica campos JSON en tablas principales:
- `cotizaciones`: especificaciones, telas_multiples, genero
- `prendas_cot`: genero, telas_multiples
- `reflectivo_cotizacion`: especificaciones

Muestra cuántos registros tienen datos vs NULL.

### 13. **Tablas Vacías**
Lista todas las tablas sin registros que podrían eliminarse.

### 14. **Resumen y Recomendaciones**
Genera recomendaciones automáticas:
- Falta de foreign keys
- Tablas vacías que podrían eliminarse
- Tablas grandes sin índices suficientes

## 📈 Resultados del Último Análisis

### Hallazgos Principales:

**✅ Datos Existentes:**
- 179 cotizaciones totales (70 borradores)
- 117 fotos de prendas
- 101 fotos de telas
- 106 fotos de logos
- 65 fotos de reflectivos

**⚠️ Problemas Detectados:**
1. **Tipo de Cotizaciones**: Todas las 179 cotizaciones tienen tipo NULL
   - Deberían tener valores: P, L, PL, o R
   - 70 son borradores

2. **Tablas Vacías**: 25 tablas sin registros
   - Considerar eliminar tablas no utilizadas
   - Ejemplos: `tipo_prendas`, `tela_fotos_pedido`, `logo_ped`, etc.

3. **Índices Faltantes**:
   - `procesos_prenda`: 12,908 registros con pocos índices
   - `procesos_historial`: 12,803 registros con pocos índices

4. **Campos JSON No Existentes**:
   - `cotizaciones.telas_multiples`: NO EXISTE
   - `cotizaciones.genero`: NO EXISTE
   - `prendas_cot.genero`: NO EXISTE
   - `prendas_cot.telas_multiples`: NO EXISTE
   - `reflectivo_cotizacion.especificaciones`: NO EXISTE

**✅ Integridad de Datos:**
- Todas las prendas tienen cotización asociada
- Todos los logos tienen cotización asociada
- No hay registros huérfanos detectados

## 🔧 Archivos Relacionados

- **Script principal**: `scripts/analizar_db_completo.php`
- **Script anterior**: `scripts/analizar_base_datos.php`
- **Migraciones**: `database/migrations/`

## 💡 Recomendaciones de Acción

1. **Corregir tipos de cotizaciones**:
   ```sql
   UPDATE cotizaciones SET tipo = 'P' WHERE tipo IS NULL AND EXISTS (SELECT 1 FROM prendas_cot WHERE cotizacion_id = cotizaciones.id);
   UPDATE cotizaciones SET tipo = 'L' WHERE tipo IS NULL AND EXISTS (SELECT 1 FROM logo_cotizaciones WHERE cotizacion_id = cotizaciones.id);
   ```

2. **Agregar índices faltantes**:
   ```sql
   ALTER TABLE procesos_prenda ADD INDEX idx_operario_id (operario_id);
   ALTER TABLE procesos_prenda ADD INDEX idx_fecha (fecha);
   ```

3. **Limpiar tablas vacías**: Evaluar si las 25 tablas vacías son necesarias

4. **Verificar campos JSON**: Confirmar si los campos JSON faltantes son necesarios o si se movieron a otras tablas

## 📝 Notas

- El script usa Laravel Facades para acceder a la base de datos
- Requiere que la aplicación esté correctamente configurada
- No modifica datos, solo lee y analiza
- La salida está formateada para fácil lectura en consola
