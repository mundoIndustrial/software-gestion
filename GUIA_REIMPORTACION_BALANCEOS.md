# Guía de Reimportación de Balanceos

## Cambios Realizados

### 1. Corrección de Fórmula Meta Teórica
- **Antes:** Usaba `floor()` (truncado)
- **Ahora:** Usa `round()` (redondeo estándar)
- **Resultado:** Coincide exactamente con Excel

### 2. Preservación de Precisión en Importación
- **Antes:** Redondeaba a 3 decimales
- **Ahora:** Preserva la precisión completa de Excel
- **Resultado:** Los valores SAM se guardan exactamente como están en Excel

### 3. Opción de Limpieza de Tablas
- **Nueva opción:** `--limpiar`
- **Función:** Elimina TODOS los balanceos antes de importar
- **Uso:** Evita duplicados en reimportaciones completas

## Comandos Disponibles

### Opción 1: Reimportar TODO desde cero (RECOMENDADO)

```bash
# Eliminar todos los balanceos e importar desde Excel
php artisan balanceo:importar-excel ruta/al/archivo.xlsx --limpiar
```

**⚠️ ADVERTENCIA:** Esto eliminará TODOS los balanceos, operaciones y prendas existentes.

### Opción 2: Importar sin eliminar (puede crear duplicados)

```bash
# Importar sin eliminar datos existentes
php artisan balanceo:importar-excel ruta/al/archivo.xlsx
```

### Opción 3: Simular importación (DRY-RUN)

```bash
# Ver qué se importaría sin guardar en BD
php artisan balanceo:importar-excel ruta/al/archivo.xlsx --dry-run
```

### Opción 4: Recalcular balanceos existentes

```bash
# Recalcular todos los balanceos con las nuevas fórmulas
php artisan balanceo:recalcular

# Recalcular un balanceo específico
php artisan balanceo:recalcular 4
```

## Pasos para Reimportar "JEANS CERAMICA ITALIA"

### Si tienes el archivo Excel completo:

```bash
# 1. Reimportar TODO (elimina duplicados automáticamente)
php artisan balanceo:importar-excel ruta/al/archivo.xlsx --limpiar
```

### Si solo quieres actualizar un balanceo:

```bash
# 1. Eliminar el balanceo específico
php artisan balanceo:reimportar 4

# 2. Importar solo esa hoja del Excel
php artisan balanceo:importar-excel ruta/al/archivo.xlsx
```

## Verificación

Después de importar, verifica los valores:

```bash
# Ver detalles del balanceo
php verificar_sam_detallado.php 4

# Listar todos los balanceos
php listar_balanceos.php
```

## Problema Resuelto

### Antes:
- Excel: SAM = 784.2
- Software: SAM = 783.9
- Diferencia: 0.3 segundos

### Después:
- Excel: SAM = 784.2
- Software: SAM = 784.2
- Diferencia: 0.0 segundos ✅

## Notas Importantes

1. **Backup:** Antes de usar `--limpiar`, asegúrate de tener un backup de tu base de datos
2. **Precisión:** Los valores ahora se guardan con la precisión exacta de Excel
3. **Fórmulas:** La Meta Teórica ahora usa `round()` en lugar de `floor()`
4. **SAM Total:** Se redondea a 1 decimal para evitar errores acumulados de punto flotante
