# 📦 Sistema de Importación de Datos de Corte

## 🎯 Descripción

Sistema completo para importar datos desde Google Sheets (Excel) a la tabla `registro_piso_corte` de la base de datos, con **creación automática** de todos los registros necesarios.

## ✨ Características Principales

### 🚀 Creación Automática
- ✅ **Operarios** - Se crean automáticamente con role_id = 3 (cortador)
- ✅ **Máquinas** - Se crean automáticamente
- ✅ **Telas** - Se crean automáticamente (incluso múltiples telas separadas por `-` o `/`)
- ✅ **Horas** - Se crean automáticamente
- ✅ **Tiempos de ciclo** - Se calculan según el grupo de tela (Grupo 1 o Grupo 2)

### 🎨 Casos Especiales Manejados
- ✅ **Múltiples telas** - `shambray-drill` crea 2 registros separados
- ✅ **Máquina N.A** - Se guarda como `NULL` en la BD
- ✅ **Formato de hora** - `HORA 07` se convierte a `02:00pm - 03:00pm`
- ✅ **Variaciones de nombres** - `shambray` → `SHAMBRAIN`
- ✅ **Números con comas** - `3,480` → `3.48`
- ✅ **Case-insensitive** - `julian`, `Julian`, `JULIAN` → todos iguales

### 🛡️ Seguridad y Robustez
- ✅ **Prevención de duplicados** - `INSERT IGNORE` y `WHERE NOT EXISTS`
- ✅ **Validación de datos** - Fecha y orden de producción obligatorias
- ✅ **Escape de caracteres** - Previene errores de sintaxis SQL
- ✅ **Idempotencia** - Puedes ejecutar el SQL múltiples veces sin problemas

## 📁 Archivos del Sistema

### Scripts
- **`scripts/google-apps-script-corte.js`** - Script principal de Google Apps Script

### Documentación
- **`README_IMPORTACION_CORTE.md`** - Este archivo (resumen general)
- **`GUIA_RAPIDA_IMPORTACION.md`** - Guía rápida de uso
- **`CASOS_ESPECIALES_MANEJADOS.md`** - Detalle de todos los casos especiales
- **`INSTRUCCIONES_IMPORTACION_CORTE.md`** - Guía paso a paso completa
- **`RESUMEN_IMPLEMENTACION_CORTE.md`** - Resumen técnico de implementación

### Seeders (Opcionales)
- **`database/seeders/OperariosCortadoresSeeder.php`** - Seeder opcional para IDs fijos
- **`database/seeders/RolesSeeder.php`** - Necesario para crear rol "cortador"

## 🚀 Inicio Rápido

### 1. Preparar Base de Datos (Solo primera vez)

```bash
# Solo necesitas crear el rol "cortador"
php artisan db:seed --class=RolesSeeder
```

### 2. Configurar Google Apps Script

1. Abre tu Google Sheet con la hoja **"CORTE"**
2. Ve a **Extensiones > Apps Script**
3. Copia el contenido de `scripts/google-apps-script-corte.js`
4. Pégalo en el editor
5. Guarda (Ctrl+S)

### 3. Ejecutar el Script

1. Selecciona `generarYGuardarSQLenDrive` en el menú
2. Haz clic en **Ejecutar** (▶️)
3. Autoriza los permisos (primera vez)
4. Descarga el archivo SQL generado

### 4. Importar a la Base de Datos

```bash
mysql -u usuario -p base_datos < archivo_generado.sql
```

## 📊 Estructura de Datos del Excel

### Columnas Requeridas (Hoja "CORTE")

| # | Columna | Ejemplo | Notas |
|---|---------|---------|-------|
| 1 | Marca temporal | 31/10/2025 9:27:04 | Automático |
| 2 | FECHA | 30/10/2025 | **Obligatorio** |
| 3 | ORDEN DE PRODUCCIÓN | 44971-44978 | **Obligatorio** |
| 4 | HORA | HORA 07 | Se normaliza automáticamente |
| 5 | OPERARIO | JULIAN | Se crea si no existe |
| 6 | MAQUINA | VERTICAL o N.A | NULL si es N.A |
| 7 | PORCIÓN DE TIEMPO | 1.00 | Numérico |
| 8 | CANTIDAD PRODUCIDA | 29 | Entero |
| 9 | TIEMPO DE CICLO | 114 | Numérico |
| 10 | PARADAS PROGRAMADAS | NINGUNA | Texto |
| 11 | TIEMPO DE PARA PROGRAMADA | 0 | Numérico |
| 12 | PARADAS NO PROGRAMADAS | APUNTES | Texto |
| 13 | TIEMPO DE PARADA NO PROGRAMADA | 120 | Numérico |
| 14 | TIPO DE EXTENDIDO | TRAZO LARGO | Texto |
| 15 | NUMERO DE CAPAS | 31 | Entero |
| 16 | TIEMPO EXTENDIDO | 0 | Entero |
| 17 | TRAZADO | PLOTTER | Texto |
| 18 | TIEMPO DE TRAZADO | 0 | Numérico |
| 19 | ACTIVIDAD | CORTAR | Texto |
| 20 | Columna 19 | - | No usada |
| 21 | TELA | DRILL o shambray-drill | Se crea si no existe |
| 22 | TIEMPO DISPONIBLE | 3480 | Numérico |
| 23 | META | 30.52631579 | Numérico |
| 24 | EFICIENCIA | 0.95 | Numérico |
| 25 | NOVEDAD | - | Texto |

## 🎯 Ejemplos de Uso

### Ejemplo 1: Registro Simple
```
FECHA: 30/10/2025
ORDEN: 44971
HORA: HORA 08
OPERARIO: JULIAN
MAQUINA: VERTICAL
TELA: DRILL
CANTIDAD: 29
```

**Resultado:** 1 registro en `registro_piso_corte`

### Ejemplo 2: Múltiples Telas
```
FECHA: 31/10/2025
ORDEN: 45034
HORA: HORA 07
OPERARIO: JULIAN
MAQUINA: VERTICAL
TELA: shambray-drill
CANTIDAD: 35
```

**Resultado:** 2 registros (uno por cada tela: SHAMBRAIN y DRILL)

### Ejemplo 3: Sin Máquina
```
FECHA: 31/10/2025
ORDEN: 44971
HORA: HORA 01
OPERARIO: JULIAN
MAQUINA: N.A
TELA: IGNIFUGO
ACTIVIDAD: EXTENDER/TRAZAR
```

**Resultado:** 1 registro con `maquina_id = NULL`

## 🔍 Verificación Post-Importación

```sql
-- Ver total de registros importados
SELECT COUNT(*) FROM registro_piso_corte;

-- Ver registros por operario
SELECT 
    u.name as operario,
    COUNT(*) as total_registros
FROM registro_piso_corte rpc
JOIN users u ON rpc.operario_id = u.id
GROUP BY u.name;

-- Ver últimos registros con detalles
SELECT 
    rpc.fecha,
    rpc.orden_produccion,
    u.name as operario,
    m.nombre_maquina,
    t.nombre_tela,
    rpc.cantidad,
    rpc.actividad
FROM registro_piso_corte rpc
LEFT JOIN users u ON rpc.operario_id = u.id
LEFT JOIN maquinas m ON rpc.maquina_id = m.id
LEFT JOIN telas t ON rpc.tela_id = t.id
ORDER BY rpc.created_at DESC
LIMIT 20;

-- Ver telas creadas
SELECT id, nombre_tela FROM telas ORDER BY id;

-- Ver máquinas creadas
SELECT id, nombre_maquina FROM maquinas ORDER BY id;
```

## 📚 Documentación Adicional

### Para Usuarios
- **`GUIA_RAPIDA_IMPORTACION.md`** - Guía rápida con pasos simplificados
- **`CASOS_ESPECIALES_MANEJADOS.md`** - Todos los casos especiales que maneja el script

### Para Desarrolladores
- **`INSTRUCCIONES_IMPORTACION_CORTE.md`** - Guía técnica detallada
- **`RESUMEN_IMPLEMENTACION_CORTE.md`** - Detalles de implementación
- **`scripts/google-apps-script-corte.js`** - Código fuente comentado

## ⚠️ Notas Importantes

### Múltiples Telas
Si una fila tiene `TELA1-TELA2-TELA3`, se crearán **3 registros separados**, uno por cada tela, con los mismos datos excepto el campo `tela_id`.

### Máquina N.A
Cuando la máquina es `N.A`, `N/A`, `NA`, o `NINGUNA`, el campo `maquina_id` se guarda como `NULL` y NO se crea registro en la tabla `maquinas`.

### Formato de Hora
El formato `HORA 01`, `HORA 02`, etc. se convierte automáticamente a los rangos del seeder:
- `HORA 01` → `08:00am - 09:00am`
- `HORA 07` → `02:00pm - 03:00pm`
- etc.

### Operarios Nuevos
Si un operario no existe en la base de datos, se crea automáticamente con:
- `role_id = 3` (cortador)
- Email: `{nombre}@mundoindustrial.com`
- Password genérico (cambiar en producción)

### Tiempos de Ciclo
Se calculan automáticamente según el grupo de tela:
- **Grupo 1** (31 telas): BANANA=97, VERTICAL=130, TIJERAS=97
- **Grupo 2** (12 telas): BANANA=45, VERTICAL=114, TIJERAS=45
- **Telas nuevas**: Valor por defecto=97

## 🐛 Solución de Problemas

### Error: "Table 'roles' doesn't exist"
```bash
php artisan migrate
php artisan db:seed --class=RolesSeeder
```

### Error: "Subquery returns more than 1 row"
Verifica que no haya duplicados en las tablas base (users, maquinas, telas).

### Registros no se insertan
1. Verifica que el rol "cortador" exista
2. Revisa el log de errores del script
3. Ejecuta el SQL manualmente para ver errores específicos

### Telas con nombres incorrectos
Agrega variaciones en la función `normalizarTela()` del script.

## 🎉 Ventajas del Sistema

✅ **Sin configuración manual** - Todo se crea automáticamente
✅ **Flexible** - Acepta cualquier formato de datos
✅ **Robusto** - Maneja todos los casos especiales
✅ **Sin errores** - Previene duplicados y errores SQL
✅ **Escalable** - Fácil agregar nuevos casos
✅ **Idempotente** - Puedes ejecutar múltiples veces sin problemas

## 📞 Soporte

Para más información, consulta:
- `GUIA_RAPIDA_IMPORTACION.md` - Guía de uso rápido
- `CASOS_ESPECIALES_MANEJADOS.md` - Casos especiales
- `scripts/google-apps-script-corte.js` - Código fuente

---

**Versión:** 2.0 (Con creación automática y manejo de múltiples telas)  
**Fecha:** Noviembre 2025  
**Estado:** ✅ Listo para producción
