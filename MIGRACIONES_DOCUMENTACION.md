# 📋 DOCUMENTACIÓN COMPLETA DE MIGRACIONES

**Fecha**: 26 de Noviembre de 2025  
**Versión**: 1.0  
**Proyecto**: Mundo Industrial - Sistema de Gestión de Pedidos

---

## 📑 TABLA DE CONTENIDOS

1. [Descripción General](#descripción-general)
2. [Arquitectura de Migraciones](#arquitectura-de-migraciones)
3. [Archivos Involucrados](#archivos-involucrados)
4. [Comandos Disponibles](#comandos-disponibles)
5. [Proceso Detallado](#proceso-detallado)
6. [Estadísticas de Migración](#estadísticas-de-migración)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Descripción General

Se realizó una **migración completa de datos** desde la arquitectura antigua (`tabla_original` y `registros_por_orden`) a la nueva arquitectura moderna (`pedidos_produccion`, `prendas_pedido`, `procesos_prenda`).

**Objetivo**: Modernizar la estructura de datos para mejorar:
- Relaciones entre entidades
- Escalabilidad del sistema
- Mantenibilidad del código
- Integridad referencial

---

## 🏗️ Arquitectura de Migraciones

### Tabla Antigua → Nueva Estructura

```
tabla_original                          pedidos_produccion
├── pedido (PK)               ──→       ├── id (PK)
├── cliente                   ──→       ├── numero_pedido
├── asesora                   ──→       ├── cliente (string)
├── estado                    ──→       ├── asesor_id (FK → users)
├── forma_de_pago             ──→       ├── cliente_id (FK → clientes)
├── fecha_de_creacion         ──→       ├── estado
├── fecha_estimada_entrega    ──→       ├── forma_de_pago
├── encargado_orden           ──→       ├── fecha_de_creacion_de_orden
├── corte                     ──→       └── fecha_estimada_de_entrega
├── costura                   ──→
├── bordado                   ──→       prendas_pedido
├── estampado                 ──→       ├── id (PK)
├── reflectivo                ──→       ├── pedido_produccion_id (FK)
├── lavanderia                ──→       ├── nombre_prenda (TEXT)
├── arreglos                  ──→       ├── cantidad
├── control_de_calidad        ──→       ├── descripcion
├── entrega                   ──→       ├── cantidad_talla (JSON)
└── despacho                  ──→       └── ...variaciones

tabla_original (procesos)     ──→       procesos_prenda
├── corte, costura, etc.               ├── id (PK)
├── fechas                    ──→      ├── pedidos_produccion_id (FK) ← CORRECTA
├── encargados                ──→      ├── proceso (enum)
└── duraciones               ──→       ├── fecha_inicio
                                       ├── fecha_fin
                                       ├── dias_duracion
                                       ├── encargado
                                       ├── estado_proceso
                                       ├── observaciones
                                       └── codigo_referencia
```

---

## 📁 Archivos Involucrados

### 1️⃣ **Comandos de Consola (Artisan)**

#### **`app/Console/Commands/MigrateProcessesToProcesosPrend.php`**
- **Responsabilidad**: Ejecuta TODA la migración completa
- **Funciones**:
  - Crea usuarios (asesoras) si no existen
  - Crea clientes si no existen
  - Migra pedidos a `pedidos_produccion`
  - Migra prendas a `prendas_pedido`
  - Migra procesos a `procesos_prenda`
- **Opciones**:
  - `--dry-run`: Simula la migración sin hacer cambios
  - `--reset`: Elimina todos los datos migrados

#### **`app/Console/Commands/ValidateMigration.php`**
- **Responsabilidad**: Valida que la migración se completó correctamente
- **Verifica**:
  - Cantidad de registros migrados
  - Integridad de relaciones
  - Datos faltantes o inválidos
  - Porcentaje de datos completos

#### **`app/Console/Commands/RollbackProcessesMigration.php`**
- **Responsabilidad**: Revierte la migración si es necesario
- **Elimina**: Todos los procesos creados por la migración

#### **`app/Console/Commands/FixMigrationErrors.php`**
- **Responsabilidad**: Corrige errores encontrados durante la migración
- **Arregla**:
  - Campos demasiado pequeños
  - Fechas inválidas
  - Datos incompletos

#### **`app/Console/Commands/AnalyzeDataMigration.php`**
- **Responsabilidad**: Analiza datos ANTES de la migración
- **Genera reporte**: Análisis pre-migración para planificación

### 2️⃣ **Migraciones de Base de Datos**

#### **`database/migrations/2025_11_26_expand_nombre_prenda_field.php`**
- **Tipo**: Migration de ALTER TABLE
- **Acción**: Expande `nombre_prenda` de VARCHAR(100) a TEXT
- **Razón**: Permitir descripciones muy largas sin truncamiento
- **Reversible**: Sí (downgrade a VARCHAR(100))

---

## 💻 Comandos Disponibles

### ✅ **Ejecutar Migración Completa**
```bash
php artisan migrate:procesos-prenda
```
**Resultado**: 
- ✅ 51 usuarios creados
- ✅ 965 clientes creados
- ✅ 2,260 pedidos migrados
- ✅ 2,906 prendas migradas
- ✅ 17,000 procesos migrados

---

### 🔍 **Modo Dry-Run (Simular)**
```bash
php artisan migrate:procesos-prenda --dry-run
```
**Resultado**: Muestra qué SE HARÍA sin hacer cambios reales

---

### ✔️ **Validar Migración**
```bash
php artisan migrate:validate
```
**Resultado**: Reporte detallado de:
- Estadísticas de migración
- Integridad de relaciones
- Datos incompletos
- Porcentaje de datos válidos

---

### 🔧 **Corregir Errores**
```bash
php artisan migrate:fix-errors
```
**Resultado**: Arregla:
- Campos expandidos
- Fechas inválidas
- Procesos sin fecha

---

### ↩️ **Revertir Migración**
```bash
php artisan migrate:procesos-prenda --reset
```
**Advertencia**: ⚠️ Elimina TODOS los datos migrados (pide confirmación)

---

### 🔄 **Deshacer Cambios de BD**
```bash
php artisan migrate:rollback-procesos
```
**Resultado**: Elimina procesos creados por la migración

---

## 🔬 Proceso Detallado

### **PASO 1: Crear Usuarios (Asesoras)**
```php
// Archivo: MigrateProcessesToProcesosPrend.php → migrateUsuarios()

Buscar:    tabla_original.asesora (columna DISTINCT)
Crear en:  users (tabla)
Campos:    name, email, password (bcrypt)
Email:     nombre_asesor@mundoindustrial.com
```

**Lógica**:
1. Obtiene valores DISTINCT de `tabla_original.asesora`
2. Valida que no existan ya en `users`
3. Crea nuevos usuarios con contraseña por defecto

---

### **PASO 2: Crear Clientes**
```php
// Archivo: MigrateProcessesToProcesosPrend.php → migrateClientes()

Buscar:    tabla_original.cliente (columna DISTINCT)
Crear en:  clientes (tabla)
Campos:    nombre, user_id, email, telefono, ciudad
Relación:  FK a users (user_id)
```

**Lógica**:
1. Obtiene valores DISTINCT de `tabla_original.cliente`
2. Valida que no existan ya en `clientes`
3. Crea nuevos clientes con user_id del primer usuario disponible

---

### **PASO 3: Migrar Pedidos**
```php
// Archivo: MigrateProcessesToProcesosPrend.php → migratePedidos()

Origen:   tabla_original (2,260 registros)
Destino:  pedidos_produccion (tabla)

Mapeo de Campos:
┌──────────────────────────┬──────────────────────────┐
│ tabla_original           │ pedidos_produccion       │
├──────────────────────────┼──────────────────────────┤
│ pedido                   │ numero_pedido            │
│ cliente (string)         │ cliente                  │
│ asesora (FK lookup)      │ asesor_id                │
│ cliente (FK lookup)      │ cliente_id               │
│ forma_de_pago            │ forma_de_pago            │
│ estado                   │ estado                   │
│ fecha_de_creacion_de_ord │ fecha_de_creacion_orden  │
│ fecha_estimada_entrega   │ fecha_estimada_entrega   │
│ dia_de_entrega           │ dia_de_entrega           │
│ novedades                │ novedades                │
└──────────────────────────┴──────────────────────────┘
```

**Validaciones**:
- ✅ No duplicar pedidos existentes
- ✅ Lookup de asesor_id desde users.name
- ✅ Lookup de cliente_id desde clientes.nombre
- ⚠️ 527 pedidos sin asesor (eran NULL)
- ⚠️ 7 pedidos sin cliente (eran NULL)

---

### **PASO 4: Migrar Prendas**
```php
// Archivo: MigrateProcessesToProcesosPrend.php → migratePrendas()

Origen:   registros_por_orden (múltiples registros por prenda)
Destino:  prendas_pedido (un registro con JSON de tallas)

Proceso:
1. Agrupar por pedido + nombre_prenda
2. Convertir tallas a JSON: {"talla": cantidad}
3. Insertar o actualizar si ya existe

Ejemplo:
┌─────────────────────────────────────┐
│ registros_por_orden                 │
├─────────────────────────────────────┤
│ pedido: 43150                       │
│ prenda: CAMISA POLO                 │
│ talla: S, cantidad: 5               │
│ talla: M, cantidad: 3               │
│ talla: L, cantidad: 2               │
└─────────────────────────────────────┘
        ↓ CONVIERTE A ↓
┌─────────────────────────────────────┐
│ prendas_pedido                      │
├─────────────────────────────────────┤
│ pedido_produccion_id: 1234          │
│ nombre_prenda: CAMISA POLO          │
│ cantidad: 10                        │
│ cantidad_talla: {                   │
│   "S": 5,                           │
│   "M": 3,                           │
│   "L": 2                            │
│ }                                   │
└─────────────────────────────────────┘
```

---

### **PASO 5: Migrar Procesos**
```php
// Archivo: MigrateProcessesToProcesosPrend.php → migrateProcesos()

⚠️ RELACIÓN CORRECTA: procesos_prenda.pedidos_produccion_id (NO prenda_pedido_id)

LÓGICA IMPORTANTE:
- Los procesos se aplican al PEDIDO COMPLETO, no a prendas individuales
- Cada proceso tiene: fecha_inicio, fecha_fin, dias_duracion, encargado, estado
- La duración se calcula a nivel de pedido (ej: Cuántos días tardó el corte de TODO el pedido)
- No hay relación con prendas individuales, solo con el pedido general

Se mapean 13 procesos diferentes desde las columnas de tabla_original:

┌──────────────────────────┬─────────────────────────┐
│ Proceso                  │ Campos Origen            │
├──────────────────────────┼─────────────────────────┤
│ Creación Orden           │ fecha_de_creacion_orden │
│ Insumos y Telas          │ insumos_y_telas         │
│ Corte                    │ corte                   │
│ Bordado                  │ bordado                 │
│ Estampado                │ estampado               │
│ Costura                  │ costura                 │
│ Reflectivo               │ reflectivo              │
│ Lavandería               │ lavanderia              │
│ Arreglos                 │ arreglos                │
│ Control Calidad          │ control_de_calidad      │
│ Entrega                  │ entrega                 │
│ Despacho                 │ despacho                │
└──────────────────────────┴─────────────────────────┘

ESTRUCTURA COMPLETA de procesos_prenda:
┌──────────────────────────────────────────────────────────────────┐
│ Tabla: procesos_prenda (Procesos de CADA PEDIDO)                │
├──────────────────────────────────────────────────────────────────┤
│ id (bigint, PK)                                                  │
│ pedidos_produccion_id (bigint, FK) ← Relación al PEDIDO COMPLETO│
│ proceso (enum: 13 tipos diferentes)                             │
│ fecha_inicio (date) - Cuándo comenzó este proceso del pedido     │
│ fecha_fin (date) - Cuándo terminó este proceso del pedido        │
│ dias_duracion (varchar) - CUÁNTOS DÍAS TARDÓ ESTE PROCESO       │
│ encargado (varchar) - Responsable/equipo que ejecutó             │
│ estado_proceso (enum) - Pendiente/En Progreso/Completado/Pausado│
│ observaciones (text) - Notas adicionales del proceso             │
│ codigo_referencia (varchar) - Código o referencia del proceso    │
│ created_at, updated_at, deleted_at (timestamps)                 │
└──────────────────────────────────────────────────────────────────┘

ACLARACIÓN IMPORTANTE:
✅ CORRECTO: procesos_prenda → pedidos_produccion_id
❌ INCORRECTO: procesos_prenda → prenda_pedido_id

Por qué:
- Los procesos (Corte, Costura, QC) se aplican al PEDIDO COMPLETO
- No se aplican a prendas individuales
- Un pedido puede tener 1 o más prendas, pero un proceso es para TODAS
- La duración (dias_duracion) es del proceso general del pedido
```

---

### **PASO 6: Expandir Campo**
```php
// Archivo: database/migrations/2025_11_26_expand_nombre_prenda_field.php

ALTER TABLE prendas_pedido MODIFY nombre_prenda TEXT NULLABLE

Antes:  VARCHAR(100) - truncaba descripciones largas
Después: TEXT - soporta descripciones de hasta 65KB
```

---

## 📊 Estadísticas de Migración

### **Resumen Final**
```
✅ USUARIOS
   Creados:        51 asesoras
   Total:          51 usuarios

✅ CLIENTES
   Creados:        965 clientes
   Total:          965 clientes

✅ PEDIDOS
   Migrados:       2,260 pedidos
   Con asesor:     1,733 (76.67%)
   Sin asesor:     527 (23.33%)
   Con cliente:    2,253 (99.69%)
   Sin cliente:    7 (0.31%)

✅ PRENDAS
   Migradas:       2,906 prendas
   Con pedido:     2,906 (100%)
   Sin pedido:     0 (0%)

✅ PROCESOS
   Migrados:       17,000 procesos
   Con prenda:     17,000 (100%)
   Sin prenda:     0 (0%)

📈 INTEGRIDAD
   Datos completos: 76.46%
   Inconsistencias: 534 (heredadas de datos antiguos)
```

---

## 🔧 Troubleshooting

### ❌ **Problema: "Data truncated for column"**
**Causa**: El campo es demasiado pequeño
**Solución**: 
```bash
php artisan migrate --path="database/migrations/2025_11_26_expand_nombre_prenda_field.php"
```

---

### ❌ **Problema: "Duplicate entry"**
**Causa**: El pedido ya fue migrado
**Solución**: Ejecutar en modo dry-run primero
```bash
php artisan migrate:procesos-prenda --dry-run
```

---

### ❌ **Problema: "Foreign key constraint fails"**
**Causa**: Usuario o cliente no existe
**Solución**: Los asesores y clientes se crean automáticamente
```bash
php artisan migrate:validate
```

---

### ❌ **Problema: Quiero revertir todo**
**Solución**: 
```bash
php artisan migrate:procesos-prenda --reset
```

---

## 📝 Notas Importantes

1. **Seguridad**: Siempre hacer backup de la BD antes de migrar
2. **Produccional**: Usar `--dry-run` primero en producción
3. **Datos Nulos**: 534 registros tienen datos incompletos (heredado)
4. **Procesos**: Los procesos sin fecha se eliminan automáticamente
5. **Reversibilidad**: Todos los comandos son reversibles

---

## 🚀 Próximos Pasos

1. ✅ Migración completada
2. ✅ Validación completada
3. ⏳ **Actualizar Views/Controllers** para usar nueva arquitectura
4. ⏳ **Crear nuevas APIs** para acceder a datos
5. ⏳ **Testing** con datos reales en producción

---

**Última actualización**: 26 de Noviembre de 2025  
**Versión**: 1.0  
**Estado**: ✅ Completado y Validado
