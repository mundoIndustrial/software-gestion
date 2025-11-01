# 🚀 Guía Rápida - Importación de Datos de Corte

## ✨ Nueva Funcionalidad: Creación Automática

El script ahora **crea automáticamente** todos los registros necesarios si no existen:

- ✅ **Operarios** - Se crean con role_id = 3 (cortador)
- ✅ **Máquinas** - Se crean automáticamente
- ✅ **Telas** - Se crean automáticamente
- ✅ **Horas** - Se crean automáticamente
- ✅ **Tiempos de ciclo** - Se calculan según el seeder (Grupo 1 o Grupo 2)

**Ya NO necesitas ejecutar seeders manualmente** (excepto RolesSeeder para crear el rol "cortador")

## 📋 Pasos Simplificados

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

## 🎯 Cómo Funciona

### Estructura del SQL Generado

El archivo SQL tiene 2 secciones:

```sql
-- ===== CREAR REGISTROS BASE SI NO EXISTEN =====

-- Crear operario: PAOLA
INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at)
SELECT 'PAOLA', 'paola@mundoindustrial.com', '...', 
       (SELECT id FROM roles WHERE name = 'cortador' LIMIT 1),
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE UPPER(name) = 'PAOLA');

-- Crear máquina: BANANA
INSERT IGNORE INTO maquinas (nombre_maquina, created_at, updated_at)
SELECT 'BANANA', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM maquinas WHERE nombre_maquina = 'BANANA');

-- Crear tela: NAFLIX
INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)
SELECT 'NAFLIX', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM telas WHERE nombre_tela = 'NAFLIX');

-- Crear tiempo_ciclo: NAFLIX + BANANA
INSERT IGNORE INTO tiempo_ciclos (tela_id, maquina_id, tiempo_ciclo, created_at, updated_at)
SELECT 
  (SELECT id FROM telas WHERE nombre_tela = 'NAFLIX' LIMIT 1),
  (SELECT id FROM maquinas WHERE nombre_maquina = 'BANANA' LIMIT 1),
  97,
  NOW(), NOW()
WHERE NOT EXISTS (...);

-- ===== INSERTAR REGISTROS DE CORTE =====

INSERT INTO registro_piso_corte (...)
SELECT 
  '2024-10-15',
  'OP-12345',
  ...,
  (SELECT id FROM horas WHERE rango = '08:00am - 09:00am' LIMIT 1),
  (SELECT id FROM users WHERE UPPER(name) = 'PAOLA' LIMIT 1),
  (SELECT id FROM maquinas WHERE nombre_maquina = 'BANANA' LIMIT 1),
  (SELECT id FROM telas WHERE nombre_tela = 'NAFLIX' LIMIT 1),
  NOW(), NOW();
```

### Tiempos de Ciclo Automáticos

El script calcula automáticamente los tiempos de ciclo según el grupo de tela:

**Grupo 1** (31 telas):
- BANANA: 97
- VERTICAL: 130
- TIJERAS: 97

Telas: NAFLIX, POLUX, POLO, SHELSY, HIDROTECH, ALFONSO, MADRIGAL, SPORTWEAR, NATIVA, SUDADERA, OXFORD VESTIR, PANTALON DE VESTIR, BRAGAS, CONJUNTO ANTIFLUIDO, BRAGAS DRILL, SPEED, PIQUE, IGNIFUGO, COFIAS, BOLSA QUIRURGICA, FORROS, TOP PLUX, NOVACRUM, CEDACRON, DACRON, ENTRETELA, NAUTICA, CHAQUETA ORION, MICRO TITAN, SPRAY RIB, DOBLE PUNTO

**Grupo 2** (12 telas):
- BANANA: 45
- VERTICAL: 114
- TIJERAS: 45

Telas: OXFORD, DRILL, GOLIAT, BOLSILLO, SANSON, PANTALON ORION, SEGAL WIKING, JEANS, SHAMBRAIN, NAPOLES, DACRUM, RETACEO DRILL

**Telas nuevas** (no en los grupos):
- Valor por defecto: 97 para todas las máquinas

## ✅ Ventajas del Nuevo Sistema

### 1. Sin Errores de Foreign Key
- Ya no hay errores por IDs faltantes
- Todo se crea automáticamente

### 2. Flexibilidad Total
- Puedes agregar nuevos operarios sin modificar código
- Nuevas máquinas se crean automáticamente
- Nuevas telas se agregan sin problemas

### 3. Idempotencia
- Puedes ejecutar el SQL múltiples veces sin duplicados
- `INSERT IGNORE` y `WHERE NOT EXISTS` previenen duplicados

### 4. Trazabilidad
- El SQL generado tiene comentarios claros
- Fácil de revisar antes de importar

## 🔍 Verificación Post-Importación

```sql
-- Ver operarios creados
SELECT id, name, email, role_id FROM users 
WHERE role_id = (SELECT id FROM roles WHERE name = 'cortador');

-- Ver máquinas creadas
SELECT * FROM maquinas ORDER BY id;

-- Ver telas creadas
SELECT * FROM telas ORDER BY id;

-- Ver tiempos de ciclo
SELECT 
  t.nombre_tela,
  m.nombre_maquina,
  tc.tiempo_ciclo
FROM tiempo_ciclos tc
JOIN telas t ON tc.tela_id = t.id
JOIN maquinas m ON tc.maquina_id = m.id
ORDER BY t.nombre_tela, m.nombre_maquina;

-- Ver registros de corte
SELECT 
  rpc.fecha,
  rpc.orden_produccion,
  u.name as operario,
  m.nombre_maquina,
  t.nombre_tela,
  rpc.cantidad
FROM registro_piso_corte rpc
LEFT JOIN users u ON rpc.operario_id = u.id
LEFT JOIN maquinas m ON rpc.maquina_id = m.id
LEFT JOIN telas t ON rpc.tela_id = t.id
ORDER BY rpc.created_at DESC
LIMIT 20;
```

## ⚠️ Notas Importantes

### Contraseñas de Operarios
Los operarios se crean con una contraseña genérica hasheada. Para cambiarla:

```sql
UPDATE users 
SET password = '$2y$10$TU_NUEVO_HASH_AQUI'
WHERE name = 'PAOLA';
```

O desde Laravel:
```php
$user = User::where('name', 'PAOLA')->first();
$user->password = Hash::make('nueva_contraseña');
$user->save();
```

### Nombres en Excel
- Los nombres se convierten a MAYÚSCULAS automáticamente
- Espacios extra se eliminan
- No importa si escribes "paola", "Paola" o "PAOLA"

### Horas
Si el rango de hora no está en el mapeo predefinido, se asigna el número 1 por defecto. Puedes agregar más rangos en la función `extraerNumeroHora()`.

## 🐛 Solución de Problemas

### Error: "Table 'roles' doesn't exist"
```bash
php artisan migrate
php artisan db:seed --class=RolesSeeder
```

### Error: "Subquery returns more than 1 row"
- Verifica que no haya duplicados en las tablas base
- Revisa nombres de operarios/máquinas/telas

### Registros no se insertan
- Verifica que el rol "cortador" exista
- Revisa el log de errores del script
- Ejecuta el SQL manualmente para ver errores específicos

## 📊 Ejemplo de Uso

### Datos en Excel (Hoja "CORTE")

| FECHA | ORDEN | HORA | OPERARIO | MAQUINA | TELA | CANTIDAD |
|-------|-------|------|----------|---------|------|----------|
| 2024-10-15 | OP-001 | 08:00am - 09:00am | PAOLA | BANANA | NAFLIX | 100 |
| 2024-10-15 | OP-002 | 09:00am - 10:00am | JULIAN | VERTICAL | DRILL | 150 |

### SQL Generado

```sql
-- Crear operarios, máquinas, telas, horas y tiempos de ciclo
-- (solo si no existen)

-- Insertar registros
INSERT INTO registro_piso_corte (...) SELECT ...;
INSERT INTO registro_piso_corte (...) SELECT ...;
```

### Resultado en BD

- 2 operarios creados (PAOLA, JULIAN)
- 2 máquinas creadas (BANANA, VERTICAL)
- 2 telas creadas (NAFLIX, DRILL)
- 2 horas creadas
- 2 tiempos de ciclo creados
- 2 registros de corte insertados

## 🎉 ¡Listo!

Ahora puedes importar datos sin preocuparte por:
- ❌ IDs faltantes
- ❌ Foreign key constraints
- ❌ Seeders complejos
- ❌ Mapeos manuales

Todo se crea automáticamente según los datos del Excel. 🚀
