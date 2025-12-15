# 📋 GUÍA: SUITE COMPLETA DE TESTS DE COTIZACIONES

**Fecha:** 14 de Diciembre de 2025  
**Última Actualización:** 2025-12-14  
**Versión:** 1.0

---

## 🎯 OBJETIVO

Suite completa de tests que valida:
- ✅ Creación de **11 cotizaciones por tipo** (M, P, G, Bordado)
- ✅ Todos los **campos incluidos** (Prendas, Telas, Fotos, Tallas, Variantes)
- ✅ **numero_cotizacion secuencial** y único
- ✅ **Concurrencia** (múltiples asesores simultáneamente)
- ✅ **Integridad de datos** y constraints
- ✅ **Performance** y limits

---

## 📁 ARCHIVOS CREADOS

### 1. **ANALISIS_CAMPOS_COTIZACIONES_PARA_TESTS.md**
Documento completo con:
- Análisis de todos los campos por tabla
- Tipos de cotización disponibles
- Estrategia de test
- Campos críticos a validar

📍 **Ubicación:** `c:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial\ANALISIS_CAMPOS_COTIZACIONES_PARA_TESTS.md`

### 2. **CotizacionesCompleteTest.php**
Suite principal con 6 tests:
1. **test_crear_11_cotizaciones_tipo_muestra()** - Tipo M (Muestra)
2. **test_crear_11_cotizaciones_tipo_prototipo()** - Tipo P (Prototipo)
3. **test_crear_11_cotizaciones_tipo_grande()** - Tipo G (Grande)
4. **test_crear_11_cotizaciones_tipo_bordado()** - Tipo Bordado
5. **test_numero_cotizacion_secuencial_global()** - Validación global
6. **test_concurrencia_multiples_asesores()** - 3 asesores × 11 = 33 cotizaciones

📍 **Ubicación:** `tests/Feature/Cotizacion/CotizacionesCompleteTest.php`

**Cotizaciones que crea:** 44 + 33 (concurrencia) = **77 total**

### 3. **CotizacionesIntegrityTest.php**
Suite de integridad con 12 tests:
1. **test_numero_cotizacion_debe_ser_unico()** - UNIQUE constraint
2. **test_tipo_cotizacion_id_debe_ser_valido()** - FK válida
3. **test_asesor_id_debe_ser_valido()** - FK válida
4. **test_eliminar_cotizacion_elimina_prendas_en_cascada()** - CASCADE delete
5. **test_campos_json_deben_tener_estructura_valida()** - JSON structure
6. **test_tallas_validas()** - Enum de tallas
7. **test_fotos_prenda_estructura_completa()** - 5 fotos por prenda
8. **test_telas_multiples_json_structure()** - JSON complejo
9. **test_estado_cotizacion_valores_validos()** - Enum de estados
10. **test_es_borrador_boolean_field()** - Boolean behavior
11. **test_relacion_cotizacion_prendas()** - One-to-Many
12. **test_numero_cotizacion_opcional_en_borrador()** - Campos opcionales

📍 **Ubicación:** `tests/Feature/Cotizacion/CotizacionesIntegrityTest.php`

### 4. **CotizacionesConcurrencyTest.php**
Suite de concurrencia y casos extremos con 8 tests:
1. **test_100_cotizaciones_secuenciales_sin_duplicados()** - 100 secuencial
2. **test_concurrencia_3_asesores_intercalado()** - 3 asesores intercalados
3. **test_rollback_si_falla_creacion_prendas()** - Transacciones
4. **test_numero_cotizacion_inmutable_una_vez_asignado()** - Inmutabilidad
5. **test_cotizacion_con_maximas_prendas_y_fotos()** - 10 prendas × 10 fotos
6. **test_multiples_tipos_cotizacion_sin_conflictos()** - 5×3 tipos
7. **test_performance_50_cotizaciones_completas()** - Performance (<30s)
8. **test_soft_delete_cotizaciones()** - Soft delete behavior

📍 **Ubicación:** `tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php`

---

## 🚀 CÓMO EJECUTAR

### Opción 1: Ejecutar TODOS los tests de cotizaciones

```bash
php artisan test tests/Feature/Cotizacion/
```

### Opción 2: Ejecutar suite específica

```bash
# Suite completa (44 + 33 cotizaciones)
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php

# Suite de integridad (validaciones)
php artisan test tests/Feature/Cotizacion/CotizacionesIntegrityTest.php

# Suite de concurrencia (100+ cotizaciones)
php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php
```

### Opción 3: Ejecutar test específico

```bash
# Test de tipo Muestra
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_crear_11_cotizaciones_tipo_muestra

# Test de secuencialidad
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_numero_cotizacion_secuencial_global

# Test de concurrencia
php artisan test tests/Feature/Cotizacion/CotizacionesCompleteTest.php --filter=test_concurrencia_multiples_asesores

# Test de 100 secuencial
php artisan test tests/Feature/Cotizacion/CotizacionesConcurrencyTest.php --filter=test_100_cotizaciones_secuenciales_sin_duplicados
```

### Opción 4: Ejecutar con output detallado

```bash
php artisan test tests/Feature/Cotizacion/ --verbose
```

### Opción 5: Ejecutar con output en HTML

```bash
php artisan test tests/Feature/Cotizacion/ --coverage --coverage-html=coverage
# Abre: coverage/index.html en el navegador
```

---

## 📊 ESTADÍSTICAS DE TESTS

### CotizacionesCompleteTest.php
| Descripción | Valor |
|--|--|
| Total Tests | 6 |
| Cotizaciones por tipo | 11 |
| Tipos de cotización | 4 (M, P, G, Bordado) |
| Cotizaciones Subtipo Muestra | 11 |
| Cotizaciones Subtipo Prototipo | 11 |
| Cotizaciones Subtipo Grande | 11 |
| Cotizaciones Subtipo Bordado | 11 |
| Cotizaciones Concurrencia | 33 (3 asesores × 11) |
| **Total Cotizaciones** | **77** |
| Prendas por Muestra | 1 |
| Prendas por Prototipo | 2 |
| Prendas por Grande | 3 |
| **Total Prendas** | ~200 |
| Fotos por Prenda | 3-5 |
| **Total Fotos** | ~600 |

### CotizacionesIntegrityTest.php
| Descripción | Valor |
|--|--|
| Total Tests | 12 |
| Validaciones UNIQUE | 1 |
| Validaciones FK | 2 |
| Validaciones CASCADE | 1 |
| Validaciones JSON | 2 |
| Validaciones Enum | 2 |
| Validaciones Relación | 1 |
| Validaciones Campos Opcionales | 1 |

### CotizacionesConcurrencyTest.php
| Descripción | Valor |
|--|--|
| Total Tests | 8 |
| Cotizaciones Secuencial | 100 |
| Cotizaciones Concurrencia | 33 |
| Cotizaciones Mixtas | 50 |
| Máximo Prendas en 1 Cotización | 10 |
| Máximo Fotos por Prenda | 10 |
| **Total Cotizaciones** | **183** |

### **TOTAL GENERAL**
- **Total Tests:** 26
- **Total Cotizaciones Creadas:** 77 + 183 = **260**
- **Total Prendas:** ~800+
- **Total Fotos:** ~2,000+
- **Tiempo Estimado:** 2-5 minutos (depende del servidor)

---

## ✅ QUÉ VALIDA CADA SUITE

### Suite: CotizacionesCompleteTest
```
✅ Crear 11 cotizaciones tipo MUESTRA completas
   - Cliente, asesor, tipo cotización
   - 1 Prenda con 3 fotos, 2 telas, 3 tallas
   - Variante con genero, color, manga, broche, bolsillos
   - numero_cotizacion secuencial y único

✅ Crear 11 cotizaciones tipo PROTOTIPO completas
   - 2 Prendas (Camisa + Pantalón)
   - 4 fotos por prenda, 3 telas, 4 tallas
   - numero_cotizacion secuencial

✅ Crear 11 cotizaciones tipo GRANDE completas
   - 3 Prendas (Camisa + Pantalón + Chaqueta)
   - 5 fotos por prenda, 4 telas, 6 tallas
   - numero_cotizacion secuencial

✅ Crear 11 cotizaciones tipo BORDADO completas
   - Logo principal con 4 fotos
   - 3 ubicaciones (pecho, espalda, manga)
   - Técnicas de bordado
   - numero_cotizacion secuencial

✅ Validar numero_cotizacion GLOBAL
   - Todos los 44 números son únicos
   - Todos están presentes en BD

✅ Validar CONCURRENCIA con 3 asesores
   - Cada asesor crea 11 cotizaciones
   - Intercaladas (simultáneas)
   - 33 total, todos con números únicos
```

### Suite: CotizacionesIntegrityTest
```
✅ UNIQUE Constraint
   - numero_cotizacion no puede repetirse
   
✅ Foreign Keys
   - tipo_cotizacion_id debe existir
   - asesor_id debe existir
   - Cascade delete funciona
   
✅ JSON Fields
   - Campos JSON guardan estructura correcta
   - Arrays anidados funcionan
   
✅ Enums y Validaciones
   - Tallas válidas (XS-5XL)
   - Estados válidos (enviada, aceptada, rechazada)
   - Boolean fields (es_borrador)
   
✅ Relaciones One-to-Many
   - Cotización → Prendas
   - Prenda → Fotos
   - Prenda → Tallas
   
✅ Campos Opcionales
   - numero_cotizacion es NULL en borradores
   - Puede asignarse después
```

### Suite: CotizacionesConcurrencyTest
```
✅ SECUENCIALIDAD
   - 100 cotizaciones creadas sin duplicados
   - Todos los números en orden
   
✅ CONCURRENCIA INTERCALADA
   - 3 asesores creen intercalados
   - 33 cotizaciones sin race conditions
   
✅ TRANSACCIONES
   - Rollback funciona si falla
   - Estados inconsistentes no existen
   
✅ MÁXIMA COMPLEJIDAD
   - 10 prendas × 10 fotos cada una
   - 50 tallas (5 por prenda)
   - Sistema aguanta carga
   
✅ MÚLTIPLES TIPOS
   - 5 cotizaciones M
   - 5 cotizaciones P
   - 5 cotizaciones G
   - Todos funcionan juntos
   
✅ PERFORMANCE
   - 50 cotizaciones completas en <30 segundos
   - Promedio <0.6s por cotización
   
✅ SOFT DELETE
   - Eliminar no borra permanentemente
   - Recuperación posible
```

---

## 🔍 INTERPRETACIÓN DE RESULTADOS

### ✅ Resultado Exitoso

```
Tests\Feature\Cotizacion\CotizacionesCompleteTest
✓ test_crear_11_cotizaciones_tipo_muestra
✓ test_crear_11_cotizaciones_tipo_prototipo
✓ test_crear_11_cotizaciones_tipo_grande
✓ test_crear_11_cotizaciones_tipo_bordado
✓ test_numero_cotizacion_secuencial_global
✓ test_concurrencia_multiples_asesores

OK (6 tests, 18 assertions)

✅ TEST MUESTRA: 11 cotizaciones creadas con éxito
   Números: COT-001, COT-002, ..., COT-011

✅ TEST SECUENCIAL: Todos los números son únicos
   Total cotizaciones: 44

✅ TEST CONCURRENCIA: 3 Asesores × 11 Cotizaciones = 33 Total
   Primeros números: COT-ASYNC-A-01, COT-ASYNC-B-01, ...
```

### ❌ Resultado Fallido - Duplicados en numero_cotizacion

```
FAILED Tests\Feature\Cotizacion\CotizacionesCompleteTest
  ✗ test_numero_cotizacion_secuencial_global
    
Expected: 44 unique values
Got: 43 unique values (1 duplicado)

Cotizaciones con numero_cotizacion duplicado:
- Cotización #5: COT-002 ← DUPLICADO
- Cotización #25: COT-002 ← DUPLICADO
```

**Solución:** Implementar lock en BD o usar transacciones para generar numero_cotizacion

### ❌ Resultado Fallido - FK inválida

```
FAILED Tests\Feature\Cotizacion\CotizacionesIntegrityTest
  ✗ test_tipo_cotizacion_id_debe_ser_valido
  
Integrity constraint violation: 1452 Cannot add or update a child row:
a foreign key constraint fails ('db'.'cotizaciones', CONSTRAINT ...
```

**Solución:** Verificar que tipos_cotizacion existan o crear seeders

### ❌ Resultado Fallido - Performance

```
FAILED Tests\Feature\Cotizacion\CotizacionesConcurrencyTest
  ✗ test_performance_50_cotizaciones_completas
  
Expected: < 30 seconds
Got: 45.32 seconds

Average: 0.91 seconds per cotización (LENTO)
```

**Solución:** Optimizar queries (eager loading, indexes, batch inserts)

---

## 🔧 TROUBLESHOOTING

### Error: "Table 'tipos_cotizacion' doesn't exist"
```bash
# Ejecutar migraciones
php artisan migrate

# O ejecutar seeders
php artisan db:seed --class=TiposCotizacionSeeder
```

### Error: "Foreign key constraint fails"
```bash
# Verificar que existan datos relacionados
php artisan tinker
> TipoCotizacion::all();
> User::first();
> Cliente::first();

# Si faltan, crear manualmente
php artisan db:seed
```

### Error: "UNIQUE constraint violation"
```bash
# Hay un problema con generación de numero_cotizacion
# Verificar:
1. Que la lógica de asignación sea atómica
2. Usar transacciones con lock

// En el código:
DB::beginTransaction();
$numero = DB::table('cotizaciones')
    ->lockForUpdate()
    ->max('id') + 1;
DB::commit();
```

### Los tests se ejecutan MUY lentamente
```bash
# Usar base de datos SQLite en memoria (más rápido)
# En phpunit.xml:
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Error: "Out of memory"
```bash
# Reducir tamaño de tests o ejecutarlos por separado
# Aumentar memoria:
php -d memory_limit=2G artisan test tests/Feature/Cotizacion/
```

---

## 📈 PRÓXIMOS PASOS

Después de que todos los tests pasen, considerar:

1. **Implementar Job de Asignación de número_cotizacion**
   - Usar Queue en lugar de sincrónico
   - Evitar race conditions
   - Validar secuencialidad con Redis

2. **Agregar más tipos de cotización**
   - REFLECTIVO
   - BORDADO_AVANZADO
   - ESPECIAL

3. **Tests de API**
   - POST /cotizaciones-prenda/store
   - PUT /cotizaciones-prenda/{id}/update
   - DELETE /cotizaciones-prenda/{id}

4. **Tests de Permisos**
   - Asesor solo puede ver sus cotizaciones
   - Supervisors pueden ver todas
   - Aprobadores pueden aprobar

5. **Tests de Reportes**
   - Generar PDF de cotización
   - Exportar a Excel
   - Enviar por email

---

## 📞 SOPORTE

Si encuentras problemas:

1. Revisar el archivo de análisis: `ANALISIS_CAMPOS_COTIZACIONES_PARA_TESTS.md`
2. Ejecutar tests con `--verbose` para más detalles
3. Revisar logs en `storage/logs/laravel.log`
4. Usar tinker para debuggear: `php artisan tinker`

---

**✅ Suite de Tests Completa y Lista para Usar**

