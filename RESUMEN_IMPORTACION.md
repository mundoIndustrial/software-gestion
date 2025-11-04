# 📦 Resumen: Sistema de Importación de Balanceos

## Fecha: 2025-11-04

---

## ✅ Archivos Creados

### 1. **Generador de SQL desde CSV** (Recomendado)
📄 `generar_sql_desde_excel.php`

**Uso:**
```bash
php generar_sql_desde_excel.php archivo.csv
```

**Características:**
- ✅ No requiere instalación de dependencias
- ✅ Lee archivos CSV exportados desde Excel
- ✅ Genera script SQL listo para ejecutar
- ✅ Detecta automáticamente columnas
- ✅ Genera letras automáticamente si no existen
- ✅ Calcula SAM total
- ✅ Incluye verificación al final

### 2. **Comando Artisan** (Avanzado)
📄 `app/Console/Commands/ImportarBalanceosExcel.php`

**Uso:**
```bash
# Instalar dependencia primero
composer require maatwebsite/excel

# Importar
php artisan balanceo:importar-excel archivo.xlsx --dry-run
php artisan balanceo:importar-excel archivo.xlsx
```

**Características:**
- ✅ Lee archivos Excel (.xlsx, .xls)
- ✅ Procesa múltiples hojas
- ✅ Importación directa a BD
- ✅ Modo DRY-RUN para probar
- ✅ Calcula métricas automáticamente

### 3. **Documentación**
📄 `IMPORTAR_BALANCEOS_EXCEL.md` - Guía completa de uso
📄 `RESUMEN_IMPORTACION.md` - Este archivo

### 4. **Ejemplo**
📄 `ejemplo_balanceo.csv` - Archivo de ejemplo para probar

---

## 🚀 Inicio Rápido

### Opción A: Usar el Generador SQL (Más Simple)

```bash
# 1. Exporta tu Excel como CSV
# 2. Genera el SQL
php generar_sql_desde_excel.php mi_balanceo.csv

# 3. Ejecuta el SQL generado en MySQL
# Se creará: mi_balanceo_import.sql
```

### Opción B: Usar el Comando Artisan

```bash
# 1. Instala la dependencia (solo una vez)
composer require maatwebsite/excel

# 2. Importa directamente
php artisan balanceo:importar-excel mi_balanceo.xlsx
```

---

## 📋 Formato del Archivo

### Estructura Mínima (CSV)

```csv
Prenda,NOMBRE DE LA PRENDA
Referencia,REF-UNICA-001

Letra,Operación,SAM
A,Primera operación,10.5
B,Segunda operación,15.2
C,Tercera operación,8.7
```

### Estructura Completa (CSV)

```csv
Prenda,JEANS CABALLERO
Descripción,JEAN CLÁSICO CABALLERO
Referencia,REF-JEANCAB-001
Tipo,jean
Operarios,10
Turnos,1
Horas,8.0

Letra,Operación,SAM,Máquina,Operario,Sección,Precedencia
A,Filetear aletilla,4.3,FL,LEONARDO,DEL,
B,Filetear aletillon,8.9,FL,LEONARDO,DEL,A
C,Montar cierre,6.5,PL,EDINSON,DEL,B
```

---

## 🎯 Casos de Uso

### Caso 1: Importar un solo balanceo

```bash
# Exporta la hoja de Excel como CSV
# Genera el SQL
php generar_sql_desde_excel.php jean_caballero.csv

# Ejecuta en MySQL
mysql -u usuario -p base_datos < jean_caballero_import.sql
```

### Caso 2: Importar múltiples balanceos

**Opción 1: Múltiples CSV**
```bash
php generar_sql_desde_excel.php balanceo1.csv
php generar_sql_desde_excel.php balanceo2.csv
php generar_sql_desde_excel.php balanceo3.csv

# Ejecutar todos los SQL generados
```

**Opción 2: Excel con múltiples hojas**
```bash
composer require maatwebsite/excel
php artisan balanceo:importar-excel todos_los_balanceos.xlsx
```

### Caso 3: Probar antes de importar

```bash
# Modo DRY-RUN (no guarda nada)
php artisan balanceo:importar-excel balanceo.xlsx --dry-run

# Si todo está bien, importa realmente
php artisan balanceo:importar-excel balanceo.xlsx
```

---

## 🔍 Validaciones Automáticas

El sistema valida:

1. ✅ **Columnas requeridas:** Operación y SAM
2. ✅ **Valores SAM:** Deben ser numéricos > 0
3. ✅ **Referencia única:** No duplica prendas
4. ✅ **SAM Total:** Calcula y verifica la suma
5. ✅ **Secciones:** Convierte a mayúsculas
6. ✅ **Letras:** Genera automáticamente si faltan

---

## 📊 Ejemplo Real

### Entrada: `jean_caballero.csv`

```csv
Prenda,JEANS CABALLERO
Referencia,REF-JEANCAB-001
Operarios,10

Letra,Operación,SAM,Máquina,Operario,Sección
A,Filetear aletilla,4.3,FL,LEONARDO,DEL
B,Filetear aletillon,8.9,FL,LEONARDO,DEL
C,Montar cierre,6.5,PL,EDINSON,DEL
```

### Salida: `jean_caballero_import.sql`

```sql
-- ===============================================
-- 👕 IMPORTACIÓN: JEANS CABALLERO
-- ===============================================

INSERT INTO prendas (nombre, descripcion, referencia, tipo, activo, created_at, updated_at)
SELECT nombre, descripcion, referencia, tipo, activo, created_at, updated_at
FROM (
    SELECT
        'JEANS CABALLERO' AS nombre,
        'JEANS CABALLERO' AS descripcion,
        'REF-JEANCAB-001' AS referencia,
        'pantalon' AS tipo,
        1 AS activo,
        NOW() AS created_at,
        NOW() AS updated_at
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM prendas WHERE referencia = 'REF-JEANCAB-001'
);

SET @prenda_id = (SELECT id FROM prendas WHERE referencia = 'REF-JEANCAB-001');

INSERT INTO balanceos (...)
VALUES (@prenda_id, '1.0', 10, 1, 8.00, ...);

SET @balanceo_id = LAST_INSERT_ID();

INSERT INTO operaciones_balanceo (...)
VALUES
(@balanceo_id, 'A', 'Filetear aletilla', '', 'FL', 4.3, 'LEONARDO', NULL, 'DEL', 0, NOW(), NOW()),
(@balanceo_id, 'B', 'Filetear aletillon', '', 'FL', 8.9, 'LEONARDO', NULL, 'DEL', 1, NOW(), NOW()),
(@balanceo_id, 'C', 'Montar cierre', '', 'PL', 6.5, 'EDINSON', NULL, 'DEL', 2, NOW(), NOW());

UPDATE balanceos b
SET b.sam_total = (SELECT SUM(o.sam) FROM operaciones_balanceo o WHERE o.balanceo_id = b.id)
WHERE b.id = @balanceo_id;

SELECT b.id, p.nombre, ROUND(b.sam_total, 1) AS sam_total
FROM balanceos b
JOIN prendas p ON b.prenda_id = p.id
WHERE b.id = @balanceo_id;
```

---

## 🛠️ Solución de Problemas

### Problema: "No se encontraron encabezados"

**Solución:** Asegúrate de tener al menos las columnas `Operación` y `SAM`

### Problema: "SAM Total incorrecto"

**Solución:** 
- Usa punto (`.`) como decimal, no coma (`,`)
- Elimina símbolos como `s`, `seg`, `$`
- Ejemplo: `4.3` ✅ no `4,3` ❌

### Problema: "Error al leer CSV"

**Solución:**
- Exporta como CSV UTF-8
- Usa coma (`,`) como separador
- No uses comillas dobles en los valores

### Problema: "Prenda duplicada"

**Solución:**
- Cambia la referencia a una única
- O elimina la prenda existente primero

---

## 📈 Ventajas del Sistema

1. ✅ **Rápido:** Importa 100+ operaciones en segundos
2. ✅ **Seguro:** Valida datos antes de insertar
3. ✅ **Flexible:** Acepta CSV y Excel
4. ✅ **Automático:** Calcula métricas automáticamente
5. ✅ **Verificable:** Modo DRY-RUN para probar
6. ✅ **Reutilizable:** Scripts SQL guardados

---

## 🎓 Mejores Prácticas

1. **Siempre usa DRY-RUN primero** para verificar
2. **Mantén referencias únicas** para cada prenda
3. **Exporta como CSV UTF-8** para evitar problemas
4. **Revisa el SAM Total** antes de importar
5. **Haz backup** de la BD antes de importaciones masivas
6. **Usa el generador SQL** para importaciones únicas
7. **Usa el comando Artisan** para importaciones frecuentes

---

## 📞 Comandos Útiles

```bash
# Generar SQL desde CSV
php generar_sql_desde_excel.php archivo.csv

# Importar con Artisan (DRY-RUN)
php artisan balanceo:importar-excel archivo.xlsx --dry-run

# Importar con Artisan (REAL)
php artisan balanceo:importar-excel archivo.xlsx

# Recalcular métricas después
php artisan balanceo:recalcular

# Recalcular un balanceo específico
php artisan balanceo:recalcular 5
```

---

## ✨ Próximos Pasos

Después de importar:

1. ✅ Verifica las métricas en `/balanceo`
2. ✅ Ajusta operarios, turnos y horas si es necesario
3. ✅ Revisa el cuello de botella
4. ✅ Activa el redondeo si lo prefieres
5. ✅ Exporta reportes si es necesario

---

## 🎉 ¡Listo!

Ya tienes un sistema completo para importar balanceos desde Excel de forma masiva y automática.

**¿Dudas?** Revisa `IMPORTAR_BALANCEOS_EXCEL.md` para más detalles.
