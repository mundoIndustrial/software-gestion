# Instrucciones para Importación de Datos de Corte

## 📋 Resumen

Este documento explica cómo importar datos desde Excel (Google Sheets) a la tabla `registro_piso_corte` de la base de datos.

## 🗂️ Estructura de la Tabla

### Columnas de Excel (Hoja "CORTE")
```
1.  Marca temporal
2.  FECHA
3.  ORDEN DE PRODUCCIÓN
4.  HORA
5.  OPERARIO
6.  MAQUINA
7.  PORCIÓN DE TIEMPO
8.  CANTIDAD PRODUCIDA
9.  TIEMPO DE CICLO
10. PARADAS PROGRAMADAS
11. TIEMPO DE PARA PROGRAMADA
12. PARADAS NO PROGRAMADAS
13. TIEMPO DE PARADA NO PROGRAMADA
14. TIPO DE EXTENDIDO
15. NUMERO DE CAPAS
16. TIEMPO EXTENDIDO
17. TRAZADO
18. TIEMPO DE TRAZADO
19. ACTIVIDAD
20. Columna 19 (no usada)
21. TELA
22. TIEMPO DISPONIBLE
23. META
24. EFICIENCIA
25. NOVEDAD
```

### Tabla en Base de Datos: `registro_piso_corte`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| fecha | date | Fecha del registro |
| orden_produccion | varchar(255) | Orden de producción |
| porcion_tiempo | double | Porción de tiempo |
| cantidad | int | Cantidad producida |
| tiempo_ciclo | double | Tiempo de ciclo |
| paradas_programadas | varchar(255) | Paradas programadas |
| tiempo_para_programada | double | Tiempo de parada programada |
| paradas_no_programadas | varchar(255) | Paradas no programadas |
| tiempo_parada_no_programada | double | Tiempo de parada no programada |
| tipo_extendido | varchar(255) | Tipo de extendido |
| numero_capas | int | Número de capas |
| tiempo_extendido | int | Tiempo de extendido |
| trazado | varchar(255) | Trazado |
| tiempo_trazado | decimal(8,2) | Tiempo de trazado |
| actividad | varchar(255) | Actividad |
| tiempo_disponible | double | Tiempo disponible |
| meta | double | Meta |
| eficiencia | double | Eficiencia |
| **hora_id** | bigint | FK a tabla `horas` |
| **operario_id** | bigint | FK a tabla `users` |
| **maquina_id** | bigint | FK a tabla `maquinas` |
| **tela_id** | bigint | FK a tabla `telas` |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

## 👥 Operarios con IDs Fijos

Los operarios de corte tienen IDs fijos en la tabla `users`:

| Nombre | ID | Email | Role |
|--------|----|----|------|
| PAOLA | 3 | paola@mundoindustrial.com | cortador (role_id=3) |
| JULIAN | 4 | julian@mundoindustrial.com | cortador (role_id=3) |
| ADRIAN | 5 | adrian@mundoindustrial.com | cortador (role_id=3) |

**Contraseñas por defecto:** `{nombre}123` (ejemplo: `paola123`)

⚠️ **IMPORTANTE:** Cambiar las contraseñas en producción.

## 🚀 Pasos para Importar Datos

### 1. Ejecutar Seeders (Primera vez)

```bash
php artisan db:seed --class=RolesSeeder
php artisan db:seed --class=OperariosCortadoresSeeder
php artisan db:seed --class=HorasSeeder
php artisan db:seed --class=MaquinasTelasSeeder
```

O ejecutar todos los seeders:

```bash
php artisan db:seed
```

### 2. Configurar el Script de Google Apps Script

1. Abre tu Google Sheet con la hoja "CORTE"
2. Ve a **Extensiones > Apps Script**
3. Copia el contenido del archivo `scripts/google-apps-script-corte.js`
4. Pégalo en el editor de Apps Script

### 3. Ajustar Mapeos (IMPORTANTE)

Antes de ejecutar el script, debes ajustar las funciones de mapeo según tus datos:

#### a) Mapeo de Horas (`mapearHora`)

Revisa cómo están definidas las horas en tu `HorasSeeder` y ajusta la función:

```javascript
function mapearHora(hora) {
  const mapeoHoras = {
    '06:00': 1,
    '07:00': 2,
    '08:00': 3,
    '09:00': 4,
    // ... etc
  };
  return mapeoHoras[hora] || 1;
}
```

#### b) Mapeo de Máquinas (`mapearMaquina`)

Ajusta según tu `MaquinasSeeder`:

```javascript
function mapearMaquina(maquina) {
  const mapeoMaquinas = {
    'CORTADORA 1': 1,
    'CORTADORA 2': 2,
    'MESA DE CORTE 1': 3,
    // ... etc
  };
  return mapeoMaquinas[maquina.toUpperCase()] || 1;
}
```

#### c) Mapeo de Telas (`mapearTela`)

Ajusta según tu `TelasSeeder`:

```javascript
function mapearTela(tela) {
  const mapeoTelas = {
    'ALGODÓN': 1,
    'POLIÉSTER': 2,
    'LYCRA': 3,
    // ... etc
  };
  return mapeoTelas[tela.toUpperCase()] || 1;
}
```

### 4. Ejecutar el Script

1. En el editor de Apps Script, selecciona la función `generarYGuardarSQLenDrive`
2. Haz clic en **Ejecutar** (▶️)
3. Autoriza los permisos necesarios (primera vez)
4. El script generará un archivo `.sql` en tu Google Drive

### 5. Importar el SQL a la Base de Datos

```bash
# Opción 1: Desde línea de comandos
mysql -u usuario -p nombre_base_datos < archivo_generado.sql

# Opción 2: Desde phpMyAdmin
# Importar > Seleccionar archivo > Ejecutar
```

## 🔍 Verificación

Después de importar, verifica los datos:

```sql
-- Ver total de registros
SELECT COUNT(*) FROM registro_piso_corte;

-- Ver registros por operario
SELECT u.name, COUNT(*) as total
FROM registro_piso_corte rpc
JOIN users u ON rpc.operario_id = u.id
GROUP BY u.name;

-- Ver últimos 10 registros
SELECT * FROM registro_piso_corte
ORDER BY created_at DESC
LIMIT 10;
```

## ⚠️ Notas Importantes

1. **Nombres de Operarios:** El script solo reconoce PAOLA, JULIAN y ADRIAN (case-insensitive)
2. **Valores NULL:** Los campos vacíos se convierten en 0 o cadenas vacías según el tipo
3. **Errores:** El script mostrará un resumen de errores al final si hay filas problemáticas
4. **Backup:** Siempre haz backup de tu base de datos antes de importar datos masivos

## 🐛 Solución de Problemas

### Error: "Operario no reconocido"
- Verifica que el nombre en Excel sea exactamente PAOLA, JULIAN o ADRIAN
- Revisa que no haya espacios extra

### Error: "Foreign key constraint fails"
- Asegúrate de haber ejecutado todos los seeders primero
- Verifica que los IDs de hora, máquina y tela existan en sus respectivas tablas

### Error: "Duplicate entry"
- Puede haber registros duplicados en el Excel
- Considera agregar validación de unicidad si es necesario

## 📞 Soporte

Para más información, consulta:
- `database/seeders/OperariosCortadoresSeeder.php`
- `scripts/google-apps-script-corte.js`
- Migraciones en `database/migrations/`
