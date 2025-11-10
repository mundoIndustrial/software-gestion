# Análisis de Base de Datos - Problemas y Violaciones de Normalización

**Fecha:** 10 de Noviembre, 2025  
**Proyecto:** Mundo Industrial - Sistema de Gestión de Producción  
**Versión:** 4.0

---

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Tablas Analizadas](#tablas-analizadas)
3. [Violaciones de Formas Normales](#violaciones-de-formas-normales)
4. [Problemas de Diseño](#problemas-de-diseño)
5. [Recomendaciones de Normalización](#recomendaciones-de-normalización)

---

## 🎯 Resumen Ejecutivo

### Estado Actual
El sistema tiene **29 migraciones** con múltiples problemas de normalización que afectan:
- **Integridad de datos**
- **Rendimiento de consultas**
- **Mantenibilidad del código**
- **Escalabilidad futura**

### Problemas Críticos Identificados
- ❌ **Violación 1NF**: Campos con múltiples valores separados por comas
- ❌ **Violación 2NF**: Dependencias parciales en claves compuestas
- ❌ **Violación 3NF**: Dependencias transitivas y datos calculados almacenados
- ❌ **Falta de claves foráneas**: Relaciones sin integridad referencial
- ❌ **Duplicación de datos**: Información redundante en múltiples tablas
- ❌ **Tipos de datos incorrectos**: Uso inadecuado de STRING para datos numéricos

---

## 📊 Tablas Analizadas

### 1. `tabla_original` - ⚠️ CRÍTICO

**Archivo:** `2025_09_23_152226_create_tabla_original_table.php`

#### Problemas Identificados

##### 1.1 Violación de 1NF (Primera Forma Normal)
```php
// ❌ PROBLEMA: Múltiples valores en un solo campo
$table->string('encargados_inventario', 55)->nullable();
$table->string('encargados_insumos', 56)->nullable();
$table->string('encargados_de_corte', 71)->nullable();
$table->string('encargados_estampado', 61)->nullable();
$table->string('encargados_marras', 56)->nullable();
$table->string('encargados_calidad', 94)->nullable();
$table->string('encargados_entrega', 67)->nullable();
```

**Impacto:**
- Imposible hacer búsquedas eficientes por encargado
- No se puede garantizar integridad referencial
- Dificulta reportes y estadísticas por persona

##### 1.2 Violación de 3NF (Tercera Forma Normal)
```php
// ❌ PROBLEMA: Datos calculados almacenados
$table->string('total_de_dias_', 50)->nullable();
$table->string('dias_orden', 50)->nullable();
$table->string('dias_inventario', 50)->nullable();
$table->string('dias_insumos', 50)->nullable();
$table->string('dias_corte', 50)->nullable();
$table->string('dias_bordado', 50)->nullable();
$table->string('dias_estampado', 50)->nullable();
$table->string('dias_costura', 56)->nullable();
$table->string('total_de_dias_reflectivo', 50)->nullable();
$table->string('dias_lavanderia', 50)->nullable();
$table->string('total_de_dias_arreglos', 50)->nullable();
$table->string('total_de_dias_marras', 50)->nullable();
$table->string('dias_c_c', 50)->nullable();
```

**Impacto:**
- Datos duplicados que pueden desincronizarse
- Lógica de negocio en base de datos (calculado en modelo)
- Desperdicio de espacio en disco

##### 1.3 Tipos de Datos Incorrectos
```php
// ❌ PROBLEMA: Usar STRING para números
$table->string('cantidad', 56)->nullable();  // Debería ser INTEGER
$table->string('dias_orden', 50)->nullable(); // Debería ser INTEGER
$table->string('modulo', 68)->nullable();     // Debería ser FOREIGN KEY
```

##### 1.4 Falta de Normalización de Entidades
```php
// ❌ PROBLEMA: Cliente como string en lugar de relación
$table->string('cliente', 96)->nullable();

// ❌ PROBLEMA: Asesora como string en lugar de relación
$table->string('asesora', 111)->nullable();
```

#### Solución Propuesta

```sql
-- ✅ SOLUCIÓN: Normalizar tabla_original

-- Tabla principal de órdenes
CREATE TABLE ordenes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido INT UNIQUE NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    asesora_id BIGINT UNSIGNED NOT NULL,
    estado ENUM('Entregado', 'En Ejecución', 'No iniciado', 'Anulada'),
    area ENUM('Corte', 'Control-Calidad', 'Costura', 'Bordado', etc.),
    descripcion TEXT,
    cantidad INT NOT NULL,
    forma_pago VARCHAR(100),
    fecha_creacion DATE NOT NULL,
    fecha_despacho DATE,
    novedades TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (asesora_id) REFERENCES users(id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion)
);

-- Tabla de etapas de producción
CREATE TABLE etapas_orden (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    orden_id BIGINT UNSIGNED NOT NULL,
    etapa ENUM('Orden', 'Inventario', 'Insumos', 'Corte', 'Bordado', 
               'Estampado', 'Costura', 'Reflectivo', 'Lavandería', 
               'Arreglos', 'Marras', 'Calidad', 'Entrega'),
    fecha_inicio DATE,
    fecha_fin DATE,
    dias_habiles INT GENERATED ALWAYS AS (
        DATEDIFF(fecha_fin, fecha_inicio)
    ) STORED,
    estado ENUM('Pendiente', 'En Proceso', 'Completado'),
    observaciones TEXT,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    INDEX idx_orden_etapa (orden_id, etapa)
);

-- Tabla de encargados por etapa (relación muchos a muchos)
CREATE TABLE encargados_etapa (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    etapa_orden_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rol ENUM('Principal', 'Asistente'),
    created_at TIMESTAMP,
    FOREIGN KEY (etapa_orden_id) REFERENCES etapas_orden(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_encargado_etapa (etapa_orden_id, user_id)
);

-- Tabla de clientes (normalizada)
CREATE TABLE clientes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    nit VARCHAR(50),
    telefono VARCHAR(50),
    email VARCHAR(255),
    direccion TEXT,
    ciudad VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_nombre (nombre)
);
```

---

### 2. `registro_piso_produccion` y `registro_piso_polo` - ⚠️ DUPLICACIÓN

**Archivos:**
- `2025_10_15_150514_create_registro_piso_produccion_table.php`
- `2025_10_15_214502_create_registro_piso_polo_table.php`

#### Problemas Identificados

##### 2.1 Duplicación de Estructura (DRY Violation)
```php
// ❌ PROBLEMA: Dos tablas idénticas
// registro_piso_produccion
Schema::create('registro_piso_produccion', function (Blueprint $table) {
    $table->id();
    $table->date('fecha');
    $table->string('modulo');
    $table->string('orden_produccion');
    // ... 15 campos más idénticos
});

// registro_piso_polo
Schema::create('registro_piso_polo', function (Blueprint $table) {
    $table->id();
    $table->date('fecha');
    $table->string('modulo');
    $table->string('orden_produccion');
    // ... 15 campos más idénticos
});
```

**Impacto:**
- Código duplicado en controladores
- Mantenimiento doble
- Inconsistencias entre tablas

##### 2.2 Falta de Relaciones
```php
// ❌ PROBLEMA: Campos sin foreign keys
$table->string('modulo');           // Debería ser modulo_id
$table->string('orden_produccion'); // Debería ser orden_id
$table->string('hora', 50);         // Debería ser hora_id
```

##### 2.3 Datos Calculados Almacenados
```php
// ❌ PROBLEMA: Campos calculados que deberían ser virtuales
$table->double('tiempo_disponible')->nullable()->default(0.00);
$table->double('meta');
$table->double('eficiencia');
```

#### Solución Propuesta

```sql
-- ✅ SOLUCIÓN: Una sola tabla con tipo de producción

CREATE TABLE registros_produccion (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    fecha DATE NOT NULL,
    tipo_produccion ENUM('Produccion', 'Polo') NOT NULL,
    modulo_id BIGINT UNSIGNED NOT NULL,
    orden_id BIGINT UNSIGNED NOT NULL,
    hora_id BIGINT UNSIGNED NOT NULL,
    tiempo_ciclo DECIMAL(8,2) NOT NULL,
    porcion_tiempo DECIMAL(8,2) NOT NULL,
    cantidad INT NOT NULL,
    numero_operarios INT NOT NULL,
    paradas_programadas VARCHAR(255),
    paradas_no_programadas VARCHAR(255),
    tiempo_parada_no_programada DECIMAL(8,2),
    tiempo_para_programada DECIMAL(8,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Campos calculados como columnas virtuales
    tiempo_disponible DECIMAL(10,2) GENERATED ALWAYS AS (
        (3600 * porcion_tiempo * numero_operarios) - 
        (COALESCE(tiempo_parada_no_programada, 0) + tiempo_para_programada)
    ) VIRTUAL,
    
    meta DECIMAL(10,2) GENERATED ALWAYS AS (
        tiempo_disponible / tiempo_ciclo
    ) VIRTUAL,
    
    eficiencia DECIMAL(5,2) GENERATED ALWAYS AS (
        (cantidad / meta) * 100
    ) VIRTUAL,
    
    FOREIGN KEY (modulo_id) REFERENCES modulos(id),
    FOREIGN KEY (orden_id) REFERENCES ordenes(id),
    FOREIGN KEY (hora_id) REFERENCES horas(id),
    INDEX idx_fecha_tipo (fecha, tipo_produccion),
    INDEX idx_modulo (modulo_id),
    INDEX idx_orden (orden_id)
);

-- Tabla de módulos (nueva)
CREATE TABLE modulos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('Produccion', 'Polo', 'Ambos') DEFAULT 'Ambos',
    capacidad_operarios INT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

### 3. `registro_piso_corte` - ✅ MEJOR DISEÑADO

**Archivo:** `2025_10_28_162020_create_registro_piso_corte_table.php`

#### Aspectos Positivos
```php
// ✅ BIEN: Usa foreign keys
$table->foreignId('hora_id')->constrained('horas')->onDelete('cascade');
$table->foreignId('operario_id')->constrained('users')->onDelete('cascade');
$table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
$table->foreignId('tela_id')->constrained('telas')->onDelete('cascade');
```

#### Problemas Menores
```php
// ⚠️ MEJORABLE: Orden sin foreign key
$table->string('orden_produccion'); // Debería ser orden_id

// ⚠️ MEJORABLE: Datos calculados almacenados
$table->decimal('tiempo_disponible', 8, 2)->default(0.00);
$table->decimal('meta', 8, 2);
$table->decimal('eficiencia', 5, 2);
```

#### Mejora Propuesta
```sql
-- ✅ MEJORA: Agregar foreign key y columnas virtuales

ALTER TABLE registro_piso_corte 
    ADD COLUMN orden_id BIGINT UNSIGNED AFTER fecha,
    ADD FOREIGN KEY (orden_id) REFERENCES ordenes(id);

-- Convertir campos calculados a virtuales (requiere recrear tabla)
CREATE TABLE registro_piso_corte_new (
    -- ... campos existentes ...
    
    tiempo_disponible DECIMAL(10,2) GENERATED ALWAYS AS (
        (3600 * porcion_tiempo) - 
        (tiempo_para_programada + COALESCE(tiempo_parada_no_programada, 0) + 
         COALESCE(tiempo_extendido, 0) + COALESCE(tiempo_trazado, 0))
    ) VIRTUAL,
    
    meta DECIMAL(10,2) GENERATED ALWAYS AS (
        tiempo_disponible / tiempo_ciclo
    ) VIRTUAL,
    
    eficiencia DECIMAL(5,2) GENERATED ALWAYS AS (
        (cantidad / meta) * 100
    ) VIRTUAL
);
```

---

### 4. `entregas_pedido_costura` y Tablas Relacionadas - ⚠️ NORMALIZACIÓN PARCIAL

**Archivos:**
- `2025_10_03_145339_create_entregas_pedido_costura_table.php`
- `2025_10_03_205353_create_entrega_pedido_corte_table.php`
- `2025_10_03_145346_create_entregas_bodega_costura_table.php`
- `2025_10_03_205403_create_entrega_bodega_corte_table.php`

#### Problemas Identificados

##### 4.1 Duplicación de Información
```php
// ❌ PROBLEMA: Datos del pedido duplicados en cada entrega
$table->string('cliente', 84);      // Ya está en tabla_original
$table->string('prenda', 158);      // Ya está en registros_por_orden
$table->text('descripcion');        // Ya está en registros_por_orden
$table->string('talla', 69);        // Ya está en registros_por_orden
```

##### 4.2 Cuatro Tablas Similares
```php
// ❌ PROBLEMA: Cuatro tablas con estructura casi idéntica
// - entregas_pedido_costura
// - entregas_pedido_corte
// - entregas_bodega_costura
// - entregas_bodega_corte
```

#### Solución Propuesta

```sql
-- ✅ SOLUCIÓN: Una tabla unificada de entregas

CREATE TABLE entregas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('Pedido', 'Bodega') NOT NULL,
    area ENUM('Costura', 'Corte') NOT NULL,
    pedido INT NOT NULL,
    item_orden_id BIGINT UNSIGNED NOT NULL, -- Referencia a items de la orden
    cantidad_entregada INT NOT NULL,
    fecha_entrega DATE NOT NULL,
    responsable_id BIGINT UNSIGNED NOT NULL,
    mes_ano VARCHAR(7) NOT NULL, -- YYYY-MM
    observaciones TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (pedido) REFERENCES ordenes(pedido),
    FOREIGN KEY (item_orden_id) REFERENCES items_orden(id),
    FOREIGN KEY (responsable_id) REFERENCES users(id),
    INDEX idx_tipo_area (tipo, area),
    INDEX idx_fecha_entrega (fecha_entrega),
    INDEX idx_pedido (pedido)
);

-- Tabla de items de orden (nueva)
CREATE TABLE items_orden (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    orden_id BIGINT UNSIGNED NOT NULL,
    prenda_id BIGINT UNSIGNED,
    descripcion TEXT,
    talla VARCHAR(50),
    cantidad_solicitada INT NOT NULL,
    cantidad_producida INT DEFAULT 0,
    cantidad_entregada INT DEFAULT 0,
    estado ENUM('Pendiente', 'En Producción', 'Completado'),
    created_at TIMESTAMP,
    
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (prenda_id) REFERENCES prendas(id),
    INDEX idx_orden (orden_id),
    INDEX idx_estado (estado)
);
```

---

### 5. `registros_por_orden` - ⚠️ DISEÑO INCONSISTENTE

**Archivo:** `2025_09_23_152227_create_registros_por_orden_table.php`

#### Problemas Identificados

```php
// ❌ PROBLEMA: Mezcla de tipos de datos
$table->string('cantidad', 60);                    // Debería ser INT
$table->string('total_producido_por_talla', 62);   // Debería ser INT
$table->integer('total_pendiente_por_talla');      // ✅ Correcto

// ❌ PROBLEMA: Datos duplicados de tabla_original
$table->string('cliente', 96);     // Ya está en tabla_original
$table->text('descripcion');       // Ya está en tabla_original

// ❌ PROBLEMA: Falta de normalización
$table->string('prenda', 168);     // Debería ser prenda_id
$table->string('costurero', 61);   // Debería ser user_id
```

#### Solución Propuesta

```sql
-- ✅ SOLUCIÓN: Simplificar y normalizar

CREATE TABLE items_orden_detalle (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    item_orden_id BIGINT UNSIGNED NOT NULL,
    costurero_id BIGINT UNSIGNED,
    cantidad_producida INT DEFAULT 0,
    fecha_completado DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (item_orden_id) REFERENCES items_orden(id) ON DELETE CASCADE,
    FOREIGN KEY (costurero_id) REFERENCES users(id),
    INDEX idx_item (item_orden_id),
    INDEX idx_costurero (costurero_id)
);
```

---

## 🔍 Resumen de Violaciones por Forma Normal

### Primera Forma Normal (1NF)
| Tabla | Campo | Problema |
|-------|-------|----------|
| `tabla_original` | `encargados_*` | Múltiples valores separados por comas |
| `registro_piso_produccion` | `paradas_programadas` | Lista de paradas como string |
| `registro_piso_corte` | `paradas_no_programadas` | Lista de paradas como string |

### Segunda Forma Normal (2NF)
| Tabla | Problema |
|-------|----------|
| `entregas_pedido_costura` | Datos del pedido duplicados (dependencia parcial) |
| `registros_por_orden` | Cliente y descripción duplicados |

### Tercera Forma Normal (3NF)
| Tabla | Campo | Problema |
|-------|-------|----------|
| `tabla_original` | `total_de_dias_`, `dias_*` | Datos calculados almacenados |
| `registro_piso_produccion` | `tiempo_disponible`, `meta`, `eficiencia` | Dependencias transitivas |
| `registro_piso_corte` | `tiempo_disponible`, `meta`, `eficiencia` | Dependencias transitivas |

---

## 📈 Impacto de los Problemas

### Rendimiento
- ❌ **Consultas lentas**: Falta de índices en campos frecuentemente consultados
- ❌ **Joins ineficientes**: Uso de strings en lugar de foreign keys
- ❌ **Espacio desperdiciado**: Datos calculados almacenados

### Integridad
- ❌ **Datos inconsistentes**: Sin foreign keys en muchas relaciones
- ❌ **Duplicación**: Información redundante que puede desincronizarse
- ❌ **Validación débil**: Tipos de datos incorrectos permiten datos inválidos

### Mantenibilidad
- ❌ **Código duplicado**: Tablas similares requieren lógica duplicada
- ❌ **Difícil de extender**: Agregar funcionalidad requiere cambios en múltiples lugares
- ❌ **Testing complejo**: Difícil crear datos de prueba consistentes

---

## ✅ Recomendaciones Prioritarias

### Prioridad ALTA (Crítico)
1. **Normalizar `tabla_original`**
   - Separar en `ordenes`, `etapas_orden`, `encargados_etapa`
   - Crear tabla `clientes` normalizada
   - Agregar foreign keys apropiadas

2. **Unificar tablas de producción**
   - Combinar `registro_piso_produccion` y `registro_piso_polo`
   - Usar columnas virtuales para campos calculados

3. **Unificar tablas de entregas**
   - Combinar las 4 tablas de entregas en una sola
   - Eliminar duplicación de datos

### Prioridad MEDIA
4. **Normalizar `registros_por_orden`**
   - Crear tabla `items_orden` centralizada
   - Eliminar duplicación de datos del pedido

5. **Agregar foreign keys faltantes**
   - `orden_produccion` → `orden_id`
   - `modulo` → `modulo_id`
   - `costurero` → `user_id`

### Prioridad BAJA
6. **Optimizar tipos de datos**
   - Convertir strings numéricos a INT/DECIMAL
   - Usar ENUM donde sea apropiado
   - Agregar índices en campos de búsqueda frecuente

---

## 📝 Notas Finales

Este análisis identifica **problemas estructurales graves** que afectan la escalabilidad y mantenibilidad del sistema. Se recomienda:

1. **No hacer cambios directos en producción**
2. **Crear migraciones de refactorización progresivas**
3. **Mantener compatibilidad con código existente durante transición**
4. **Implementar tests antes de refactorizar**
5. **Documentar cada cambio en el esquema**

**Próximo documento:** `02-ANALISIS-SOLID-DDD.md`
