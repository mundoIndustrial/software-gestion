# 🚀 Guía Completa de Migración de Datos - Mundo Industrial

## 📋 Descripción General

Esta guía documenta el proceso completo de migración de datos históricos desde las tablas antiguas (`tabla_original` y `registros_por_orden`) hacia la nueva arquitectura normalizada del sistema.

## 🎯 Objetivo

Migrar **TODA** la información histórica de pedidos, prendas y procesos desde el sistema antiguo al nuevo, manteniendo la integridad de datos y respetando los pedidos creados desde el nuevo sistema (aquellos con `cotizacion_id`).

## 📊 Análisis de Base de Datos

### Tablas de Origen (Sistema Antiguo)

1. **`tabla_original`** - Contiene información de pedidos y procesos
   - Pedidos únicos
   - Asesoras
   - Clientes
   - Fechas de procesos
   - Encargados de cada proceso
   - Estados y áreas

2. **`registros_por_orden`** - Contiene información de prendas y tallas
   - Prendas por pedido
   - Cantidades por talla
   - Descripciones

### Tablas de Destino (Nueva Arquitectura)

1. **`pedidos_produccion`** - Pedidos normalizados
2. **`prendas_pedido`** - Prendas con cantidades por talla (JSON)
3. **`procesos_prenda`** - Historial de procesos por pedido

### Estado Actual (Según Análisis)

- **179 cotizaciones** en el sistema
- **70 borradores** de cotizaciones
- **117 fotos** de prendas
- **101 fotos** de telas
- **106 fotos** de logos
- **25 tablas vacías** que podrían eliminarse

## 🔧 Comando de Migración

### Archivo Creado

**`app/Console/Commands/MigrarDatosCompleto.php`**

### Uso del Comando

```bash
# 1. ANÁLISIS PREVIO (recomendado)
php artisan migrar:datos-completo --analyze

# 2. SIMULACIÓN (dry-run)
php artisan migrar:datos-completo --dry-run

# 3. MIGRACIÓN REAL
php artisan migrar:datos-completo

# 4. VALIDACIÓN POST-MIGRACIÓN
php artisan migrar:datos-completo --validate

# 5. MIGRACIÓN FORZADA (elimina TODO, incluso pedidos con cotizacion_id)
php artisan migrar:datos-completo --force
```

## 📝 Proceso de Migración (6 Pasos)

### PASO 0: Limpieza de Datos Existentes

**Modo Normal:**
- Elimina procesos de pedidos SIN `cotizacion_id`
- Elimina prendas de pedidos SIN `cotizacion_id`
- Elimina pedidos SIN `cotizacion_id`
- **RESPETA** pedidos con `cotizacion_id` (creados desde el nuevo sistema)

**Modo Force (`--force`):**
- Elimina **TODOS** los datos
- Usar solo si necesitas empezar desde cero

### PASO 1: Migrar Usuarios (Asesoras)

**Origen:** `tabla_original.asesora`

**Proceso:**
1. Extrae asesoras únicas de `tabla_original`
2. Verifica si ya existen en `users`
3. Crea nuevos usuarios si no existen:
   - `name`: Nombre de la asesora
   - `email`: nombre.asesora@mundoindustrial.local
   - `password`: password123 (encriptado)
   - `role_id`: 2 (Asesora)

**Resultado:** Mapeo de nombres → IDs para usar en pedidos

### PASO 2: Migrar Clientes

**Origen:** `tabla_original.cliente`

**Proceso:**
1. Extrae clientes únicos de `tabla_original`
2. Verifica si ya existen en `clientes`
3. Crea nuevos clientes si no existen:
   - `nombre`: Nombre del cliente
   - `estado`: 'Activo'

**Resultado:** Mapeo de nombres → IDs para usar en pedidos

### PASO 3: Migrar Pedidos

**Origen:** `tabla_original`

**Campos migrados:**
- `numero_pedido` ← `pedido`
- `asesor_id` ← mapeo de `asesora`
- `cliente_id` ← mapeo de `cliente`
- `cliente` ← `cliente` (nombre)
- `estado` ← `estado`
- `fecha_de_creacion_de_orden` ← `fecha_de_creacion_de_orden`
- `dia_de_entrega` ← `dia_de_entrega`
- `fecha_estimada_de_entrega` ← `fecha_estimada_de_entrega`
- `area` ← `area`
- `novedades` ← `novedades`
- `forma_de_pago` ← `forma_de_pago`

**Validaciones:**
- Salta pedidos que ya tienen `cotizacion_id`
- Parsea fechas correctamente (evita '0000-00-00')

### PASO 4: Migrar Prendas

**Origen:** `registros_por_orden`

**Proceso:**
1. Agrupa registros por: `pedido`, `prenda`, `descripcion`
2. Suma cantidades totales
3. Consolida tallas en formato JSON:
   ```json
   {
     "S": 10,
     "M": 15,
     "L": 20,
     "XL": 5
   }
   ```

**Campos migrados:**
- `nombre_prenda` ← `prenda`
- `numero_pedido` ← `pedido`
- `cantidad` ← SUM(`cantidad`)
- `descripcion` ← `descripcion`
- `cantidad_talla` ← JSON de tallas

**Validaciones:**
- Salta prendas sin nombre
- Salta prendas de pedidos con `cotizacion_id`
- Verifica que el pedido exista

### PASO 5: Migrar Procesos

**Origen:** `tabla_original`

**Mapeo de Procesos:**

| Proceso | Campo Fecha | Campo Encargado | Campo Días |
|---------|-------------|-----------------|------------|
| Creación de Orden | `fecha_de_creacion_de_orden` | `encargado_orden` | `dias_orden` |
| Insumos y Telas | `insumos_y_telas` | `encargados_insumos` | `dias_insumos` |
| Corte | `corte` | `encargados_de_corte` | `dias_corte` |
| Bordado | `bordado` | `codigo_de_bordado` | `dias_bordado` |
| Estampado | `estampado` | `encargados_estampado` | `dias_estampado` |
| Costura | `costura` | `modulo` | `dias_costura` |
| Reflectivo | `reflectivo` | `encargado_reflectivo` | `total_de_dias_reflectivo` |
| Lavandería | `lavanderia` | `encargado_lavanderia` | `dias_lavanderia` |
| Arreglos | `arreglos` | `encargado_arreglos` | `total_de_dias_arreglos` |
| Control Calidad | `control_de_calidad` | `encargados_calidad` | `dias_c_c` |
| Entrega | `entrega` | `encargados_entrega` | - |
| Despacho | `despacho` | `column_52` | - |

**Proceso:**
1. Para cada pedido, busca su registro en `tabla_original`
2. Extrae cada proceso que tenga fecha válida
3. Crea registro en `procesos_prenda`:
   - `numero_pedido`
   - `proceso`
   - `fecha_inicio` = fecha del proceso
   - `fecha_fin` = fecha del proceso
   - `encargado`
   - `dias_duracion`
   - `estado_proceso` = 'Completado'

**Validaciones:**
- Salta pedidos con `cotizacion_id`
- Ignora fechas '0000-00-00'
- Valida que las fechas sean razonables (2000-2100)

### PASO 6: Actualizar Áreas y Fechas

**Proceso:**
1. Para cada pedido sin `cotizacion_id`
2. Busca el proceso más reciente (por fecha y ID)
3. Actualiza en `pedidos_produccion`:
   - `area` = nombre del último proceso
   - `fecha_ultimo_proceso` = fecha del último proceso

## 🔍 Validación Post-Migración

El comando incluye validación automática que verifica:

### Integridad Referencial

1. **Pedidos sin asesor:** Detecta pedidos sin `asesor_id`
2. **Pedidos sin cliente:** Detecta pedidos sin `cliente_id`
3. **Prendas huérfanas:** Prendas sin pedido asociado
4. **Procesos huérfanos:** Procesos sin pedido asociado

### Estadísticas Finales

- Total de pedidos migrados
- Total de prendas migradas
- Total de procesos migrados

## 📊 Ejemplo de Ejecución

```bash
# 1. Primero, analiza los datos
php artisan migrar:datos-completo --analyze

# Salida esperada:
# 📊 ANÁLISIS DE DATOS A MIGRAR
# ================================================================================
# 
# 📋 TABLA_ORIGINAL:
#    Total registros: 1,234
#    Pedidos únicos: 456
#    Asesoras únicas: 12
#    Clientes únicos: 89
# 
# 📋 REGISTROS_POR_ORDEN:
#    Total registros: 2,345
#    Pedidos con prendas: 450
#    Prendas únicas: 890
# 
# 📋 PEDIDOS_PRODUCCION (ACTUALES):
#    Total pedidos: 179
#    Con cotizacion_id: 70 (NO se tocarán)
#    Sin cotizacion_id: 109 (serán reemplazados)

# 2. Simula la migración
php artisan migrar:datos-completo --dry-run

# 3. Ejecuta la migración real
php artisan migrar:datos-completo

# Salida esperada:
# 🚀 MIGRACIÓN COMPLETA DE DATOS - MUNDO INDUSTRIAL
# ================================================================================
# 
# 🧹 PASO 0: Limpiando datos existentes...
#    ℹ️  Pedidos con cotizacion_id: 70 (NO se tocarán)
#    ✓ Procesos eliminados: 234
#    ✓ Prendas eliminadas: 567
#    ✓ Pedidos eliminados: 109
# 
# 👥 PASO 1: Migrando Usuarios (Asesoras)...
#    📊 Asesoras encontradas: 12
#    ✅ Creados: 2, Existentes: 10
# 
# 🏢 PASO 2: Migrando Clientes...
#    📊 Clientes encontrados: 89
#    ✅ Creados: 15, Existentes: 74
# 
# 📦 PASO 3: Migrando Pedidos...
#    📊 Pedidos a migrar: 456
#    ✅ Migrados: 386, Saltados: 70
# 
# 👕 PASO 4: Migrando Prendas...
#    📊 Prendas a migrar: 890
#    ✅ Migradas: 850, Saltadas: 40
# 
# ⚙️  PASO 5: Migrando Procesos...
#    📊 Procesando 456 pedidos
#    ✅ Migrados: 2,340, Saltados: 420
# 
# 🔄 PASO 6: Actualizando áreas y fechas...
#    📊 Actualizando 386 pedidos
#    ✅ Áreas actualizadas
# 
# ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE

# 4. Valida la integridad
php artisan migrar:datos-completo --validate

# Salida esperada:
# 🔍 VALIDANDO INTEGRIDAD DE LA MIGRACIÓN
# ================================================================================
# 
# ✅ VALIDACIÓN EXITOSA: No se encontraron problemas de integridad
# 
# 📊 ESTADÍSTICAS FINALES:
#    Total pedidos: 456
#    Total prendas: 850
#    Total procesos: 2,340
```

## ⚠️ Consideraciones Importantes

### Respeto a Datos Nuevos

El comando **SIEMPRE** respeta pedidos con `cotizacion_id`:
- Estos pedidos fueron creados desde el nuevo sistema
- Tienen relaciones con cotizaciones, prendas_cot, logos, etc.
- NO deben ser modificados ni eliminados

### Modo Force

El flag `--force` es **DESTRUCTIVO**:
- Elimina **TODOS** los datos, incluyendo pedidos con `cotizacion_id`
- Usar solo si necesitas empezar completamente desde cero
- Requiere confirmación explícita

### Parseo de Fechas

El comando maneja correctamente:
- Fechas inválidas: '0000-00-00', '0000-00-00 00:00:00'
- Fechas fuera de rango: < 2000 o > 2100
- Diferentes formatos de fecha

### Consolidación de Tallas

Las tallas se consolidan en formato JSON:
```json
{
  "S": 10,
  "M": 15,
  "L": 20,
  "XL": 5,
  "SIN_TALLA": 2
}
```

## 🐛 Solución de Problemas

### Error: "Tabla 'tabla_original' no existe"

**Causa:** La tabla fuente no existe en la base de datos

**Solución:** Verifica que las tablas antiguas aún existan:
```sql
SHOW TABLES LIKE 'tabla_original';
SHOW TABLES LIKE 'registros_por_orden';
```

### Error: Foreign Key Constraint

**Causa:** Integridad referencial violada

**Solución:** El comando desactiva temporalmente las foreign keys durante la limpieza

### Prendas sin nombre

**Comportamiento:** Se saltan automáticamente

**Estadística:** Aparece en "Prendas saltadas"

### Pedidos duplicados

**Prevención:** El comando limpia datos existentes antes de migrar

**Validación:** Usa `--validate` para verificar

## 📈 Métricas de Éxito

Una migración exitosa debe mostrar:

1. ✅ **Cero errores** en el resumen
2. ✅ **Validación exitosa** sin problemas de integridad
3. ✅ **Pedidos con cotizacion_id preservados**
4. ✅ **Todas las prendas con pedido asociado**
5. ✅ **Todos los procesos con pedido asociado**

## 🔄 Rollback

Si necesitas revertir la migración:

```bash
# 1. Restaurar backup de base de datos (recomendado)
mysql -u usuario -p nombre_bd < backup_antes_migracion.sql

# 2. O limpiar manualmente
php artisan migrar:datos-completo --force
# (Esto eliminará TODO, luego puedes volver a migrar)
```

## 📚 Archivos Relacionados

- **Comando principal:** `app/Console/Commands/MigrarDatosCompleto.php`
- **Análisis de BD:** `scripts/analizar_db_completo.php`
- **Documentación análisis:** `ANALISIS_BASE_DATOS_COMPLETO.md`
- **Comandos anteriores:**
  - `app/Console/Commands/MigrarProcesosCorrectamente.php`
  - `app/Console/Commands/MigrateTablaOriginalCompleto.php`

## 💡 Recomendaciones

### Antes de Migrar

1. ✅ **Hacer backup completo de la base de datos**
2. ✅ Ejecutar análisis: `--analyze`
3. ✅ Ejecutar simulación: `--dry-run`
4. ✅ Revisar logs de errores

### Durante la Migración

1. ✅ Monitorear la consola para errores
2. ✅ Verificar las barras de progreso
3. ✅ Revisar estadísticas de "saltados"

### Después de Migrar

1. ✅ Ejecutar validación: `--validate`
2. ✅ Verificar manualmente algunos pedidos
3. ✅ Revisar logs: `storage/logs/laravel.log`
4. ✅ Probar funcionalidad del sistema

## 🎯 Próximos Pasos

Después de una migración exitosa:

1. **Limpiar tablas antiguas** (opcional):
   ```sql
   -- Solo si estás seguro de que todo funciona
   DROP TABLE tabla_original;
   DROP TABLE registros_por_orden;
   ```

2. **Optimizar base de datos**:
   ```sql
   OPTIMIZE TABLE pedidos_produccion;
   OPTIMIZE TABLE prendas_pedido;
   OPTIMIZE TABLE procesos_prenda;
   ```

3. **Actualizar índices** (si es necesario):
   ```sql
   ALTER TABLE procesos_prenda ADD INDEX idx_fecha (fecha_inicio);
   ```

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Ejecuta el análisis de base de datos: `php scripts/analizar_db_completo.php`
3. Verifica la documentación de análisis: `ANALISIS_BASE_DATOS_COMPLETO.md`

---

**Última actualización:** Diciembre 18, 2025
**Versión:** 1.0
**Estado:** ✅ Listo para producción
