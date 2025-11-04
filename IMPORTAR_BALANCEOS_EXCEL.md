# 📊 Importar Balanceos desde Excel

## Fecha: 2025-11-04

Existen **2 métodos** para importar balanceos desde Excel:

---

## Método 1: Generador de SQL (Recomendado - No requiere instalación)

### Paso 1: Preparar el archivo Excel

Exporta tu hoja de Excel como **CSV** con el siguiente formato:

```csv
Prenda,JEANS CABALLERO
Descripción,JEAN CLÁSICO CABALLERO
Referencia,REF-JEANCAB-001
Tipo,pantalon
Operarios,10
Turnos,1
Horas,8.0

Letra,Operación,SAM,Máquina,Operario,Sección,Precedencia
A,Filetear aletilla,4.3,FL,LEONARDO,DEL,
B,Filetear aletillon,8.9,FL,LEONARDO,DEL,
C,Montar cierre a aletilla,6.5,PL,EDINSON,DEL,
...
```

**Campos opcionales:**
- Si no hay `Letra`, se generará automáticamente (A, B, C...)
- Si no hay `Máquina`, `Operario`, `Sección` o `Precedencia`, se dejarán vacíos

### Paso 2: Generar el script SQL

```bash
php generar_sql_desde_excel.php archivo.csv
```

**Salida:**
```
📂 Leyendo archivo: archivo.csv
📊 Filas leídas: 35

👕 Prenda: JEANS CABALLERO
📝 Referencia: REF-JEANCAB-001
👥 Operarios: 10 | Turnos: 1 | Horas: 8.0

📋 Columnas detectadas:
   Operación: Col 2
   SAM: Col 3

✅ Operaciones encontradas: 28
⏱️  SAM Total: 678.5

✅ Script SQL generado: archivo_import.sql
💡 Ejecuta el script en MySQL para importar el balanceo
```

### Paso 3: Ejecutar el script SQL

Abre MySQL Workbench o tu cliente de base de datos y ejecuta el archivo generado:

```sql
-- archivo_import.sql
```

El script:
1. ✅ Crea la prenda (si no existe)
2. ✅ Crea el balanceo
3. ✅ Inserta todas las operaciones
4. ✅ Calcula automáticamente las métricas
5. ✅ Muestra un resumen de verificación

---

## Método 2: Comando Artisan (Requiere instalación)

### Paso 1: Instalar dependencia

```bash
composer require maatwebsite/excel
```

### Paso 2: Preparar el archivo Excel

El archivo Excel (.xlsx o .xls) debe tener el mismo formato que el CSV del Método 1.

**Cada hoja** del Excel se importará como un balanceo diferente.

### Paso 3: Importar con el comando

```bash
# Modo DRY-RUN (simular sin guardar)
php artisan balanceo:importar-excel archivo.xlsx --dry-run

# Importar realmente
php artisan balanceo:importar-excel archivo.xlsx
```

**Salida:**
```
📂 Leyendo archivo: archivo.xlsx
📊 Hojas encontradas: 3

============================================================
📄 Procesando hoja 1
👕 Prenda: JEANS CABALLERO
📝 Referencia: REF-JEANCAB-001
👥 Operarios: 10 | Turnos: 1 | Horas: 8.0
📋 Columnas detectadas:
   Letra: Col 1
   Operación: Col 2
   SAM: Col 3
✅ Operaciones encontradas: 28
⏱️  SAM Total: 678.5

📝 Muestra de operaciones:
   A: Filetear aletilla - SAM: 4.3
   B: Filetear aletillon - SAM: 8.9
   C: Montar cierre a aletilla - SAM: 6.5
   ... y 25 más

💾 Prenda guardada: ID 5
💾 Balanceo creado: ID 8
💾 Operaciones creadas: 28

📊 Métricas calculadas:
   SAM Total: 678.5
   Meta Teórica: 424
   Meta Real (90%): 381.60
   Meta Sugerida (85%): 360

✅ Balanceo importado exitosamente
```

---

## Formato del Excel/CSV

### Sección 1: Información de la Prenda (Opcional)

```
Prenda          | JEANS CABALLERO
Descripción     | JEAN CLÁSICO CABALLERO
Referencia      | REF-JEANCAB-001
Tipo            | pantalon
Operarios       | 10
Turnos          | 1
Horas           | 8.0
```

**Si no se proporciona:**
- Nombre: Se genera automáticamente
- Referencia: Se genera con hash único
- Tipo: `pantalon` por defecto
- Operarios: 10
- Turnos: 1
- Horas: 8.0

### Sección 2: Encabezados de Operaciones (Requerido)

**Columnas reconocidas:**

| Columna | Alias Aceptados | Requerido |
|---------|----------------|-----------|
| Letra | `letra`, `op`, `n°`, `no`, `#` | No* |
| Operación | `operacion`, `operación`, `descripcion`, `descripción` | **Sí** |
| SAM | `sam`, `tiempo`, `min` | **Sí** |
| Máquina | `maquina`, `máquina`, `maq` | No |
| Operario | `operario`, `trabajador` | No |
| Sección | `seccion`, `sección`, `área`, `area` | No |
| Precedencia | `precedencia`, `prec`, `dep` | No |

*Si no hay columna `Letra`, se genera automáticamente (A, B, C, ...)

### Sección 3: Datos de Operaciones

```
A | Filetear aletilla           | 4.3  | FL   | LEONARDO | DEL  |
B | Filetear aletillon          | 8.9  | FL   | LEONARDO | DEL  |
C | Montar cierre a aletilla    | 6.5  | PL   | EDINSON  | DEL  |
...
```

---

## Ejemplo Completo de CSV

```csv
Prenda,JEANS CABALLERO
Descripción,JEAN CLÁSICO CABALLERO
Referencia,REF-JEANCAB-001
Tipo,pantalon
Operarios,10
Turnos,1
Horas,8.0

Letra,Operación,SAM,Máquina,Operario,Sección
A,Filetear aletilla,4.3,FL,LEONARDO,DEL
B,Filetear aletillon,8.9,FL,LEONARDO,DEL
C,Montar cierre a aletilla,6.5,PL,EDINSON,DEL
D,Montar cierre a aletillon,9.0,PL,EDINSON,DEL
E,Embonar relojera,6.2,2A,LUIS,DEL
F,Montar relojera a vista,15.6,2A,GUZMAN,DEL
G,Embonar parche x2,8.9,2A,LUIS,TRAS
H,Filetear vista x2,5.5,FL,LEONARDO,DEL
I,Montar vista a telabolsillo x2,18.9,PL,FELIPE,DEL
J,Cerrar telabolsillo x2,9.4,FL,LEONARDO,DEL
K,Pisar telabolsillo x2,14.5,PL,DIEGO,DEL
L,Parchar x2,82.4,2A,ALEXIS,TRAS
M,Hacer figura de parche x2,8.9,2A,LUIS,TRAS
N,Preparar revoque x2,40.3,PL,FELIPE,DEL
O,Pisar revoque x2,37.0,2A,LUIS,DEL
P,Montar cierre a pantalón,18.4,PL,EDINSON,DEL
Q,Encuadrilar x2,26.8,PL,DIEGO,DEL
R,Hacer J,24.8,2A,GUZMAN,DEL
S,Encajar,48.1,2A,GUZMAN,ENS
T,Cerrar entrepierna,23.4,FL,LEONARDO,ENS
U,Pegar cotilla x2,16.5,CERR,ANDERSON,ENS
V,Cerrar cola,20.6,CERR,ANDERSON,ENS
W,Hacer bota x2,37.9,PL,YAIR,ENS
X,Cerrar costados x2,38.4,CERR,ANDERSON,ENS
Y,Montar pretina,25.0,PRE,ALEXANDRA,ENS
AA,Hacer pasadores,12.2,COLL,YAIR,ENS
AB,Unir pretinas,17.4,PL,ALEXANDRA,ENS
AC,Hacer punta x2,42.5,PL,ALEXANDRA,ENS
```

---

## Ventajas de Cada Método

### Método 1: Generador SQL
- ✅ No requiere instalación de dependencias
- ✅ Genera script SQL reutilizable
- ✅ Puedes revisar el SQL antes de ejecutar
- ✅ Funciona con cualquier cliente MySQL
- ✅ Ideal para importaciones únicas

### Método 2: Comando Artisan
- ✅ Importación directa a la base de datos
- ✅ Procesa múltiples hojas de Excel
- ✅ Modo DRY-RUN para probar
- ✅ Validación automática
- ✅ Ideal para importaciones frecuentes

---

## Solución de Problemas

### Error: "No se encontraron encabezados"

**Causa:** El script no detectó las columnas `Operación` y `SAM`

**Solución:** Asegúrate de que los encabezados sean exactamente:
- `Operación` o `operacion`
- `SAM` o `sam` o `tiempo`

### Error: "SAM Total no coincide"

**Causa:** Valores SAM con formato incorrecto

**Solución:** 
- Usa punto (`.`) como separador decimal
- Elimina símbolos de moneda o unidades
- Ejemplo correcto: `4.3` no `4,3` ni `4.3s`

### Error: "Prenda duplicada"

**Causa:** Ya existe una prenda con la misma referencia

**Solución:**
- Cambia la referencia en el CSV
- O elimina la prenda existente primero

---

## Recalcular Métricas Después

Si importaste con SQL y las métricas no se calcularon:

```bash
# Recalcular un balanceo específico
php artisan balanceo:recalcular 5

# Recalcular todos
php artisan balanceo:recalcular
```

---

## Consejos

1. **Usa DRY-RUN primero** para verificar que todo esté correcto
2. **Revisa el SAM Total** antes de importar
3. **Exporta siempre como CSV UTF-8** para evitar problemas de caracteres
4. **Mantén un backup** de tu base de datos antes de importaciones masivas
5. **Usa referencias únicas** para cada prenda

---

## Próximos Pasos

Después de importar:
1. ✅ Verifica las métricas en la interfaz web
2. ✅ Ajusta operarios, turnos y horas si es necesario
3. ✅ Revisa el cuello de botella
4. ✅ Activa el redondeo si lo prefieres
