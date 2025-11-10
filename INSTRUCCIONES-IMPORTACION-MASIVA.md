# 📊 Importación Masiva de Datos desde Excel

Este script permite importar de forma masiva los datos de los 3 archivos Excel principales del sistema:

1. **CONTROL DE PISO POLOS** → Tabla `registro_piso_polo`
2. **CONTROL DE PISO PRODUCCION** → Tabla `registro_piso_produccion`
3. **CLASICO (Balanceos)** → Tablas `prendas`, `balanceos`, `operaciones_balanceo`

---

## 🚀 Formas de Ejecutar

### Opción 1: Archivo Batch Simple (Recomendado)

Doble clic en el archivo:
```
importar-todo.bat
```

Este archivo importará los 3 archivos Excel automáticamente desde la carpeta `resources/`.

---

### Opción 2: Archivo Batch con Opciones Avanzadas

Doble clic en el archivo:
```
importar-todo-opciones.bat
```

Este archivo te permite elegir:
- Importar TODO
- Importar TODO y LIMPIAR datos existentes
- Modo DRY-RUN (simular sin guardar)
- Importar solo POLOS
- Importar solo PRODUCCION
- Importar solo BALANCEOS

---

### Opción 3: Comando Artisan Manual

Abre una terminal en la carpeta del proyecto y ejecuta:

#### Importar todo (sin limpiar):
```bash
php artisan importar:todo-excel
```

#### Importar todo y limpiar datos existentes:
```bash
php artisan importar:todo-excel --limpiar
```

#### Modo DRY-RUN (simular sin guardar):
```bash
php artisan importar:todo-excel --dry-run
```

#### Especificar rutas personalizadas:
```bash
php artisan importar:todo-excel --polo="ruta/al/archivo.xlsx" --produccion="ruta/al/archivo.xlsx" --balanceo="ruta/al/archivo.xlsx"
```

---

## 📁 Ubicación de los Archivos

Por defecto, el script busca los archivos en la carpeta `resources/`:

- `resources/CONTROL DE PISO POLOS (Respuestas) .xlsx`
- `resources/CONTROL DE PISO PRODUCCION (respuestas) (1).xlsx`
- `resources/clasico (1).xlsx`

Si los archivos están en otra ubicación, usa la opción `--polo`, `--produccion` o `--balanceo` para especificar la ruta.

---

## ⚙️ Opciones del Comando

| Opción | Descripción |
|--------|-------------|
| `--polo=ruta` | Ruta al archivo Excel de POLOS |
| `--produccion=ruta` | Ruta al archivo Excel de PRODUCCION |
| `--balanceo=ruta` | Ruta al archivo Excel de BALANCEO |
| `--dry-run` | Simular sin guardar en la base de datos |
| `--limpiar` | Eliminar todos los registros antes de importar |

---

## 📋 Formato de los Archivos Excel

### POLOS y PRODUCCION

**Hoja:** `REGISTRO`

**Columnas esperadas:**
- FECHA
- MODULO
- ORDEN DE PRODUCCIÓN
- HORA
- TIEMPO DE CICLO
- PORCIÓN DE TIEMPO
- CANTIDAD PRODUCIDA
- PARADAS PROGRAMADAS
- PARADAS NO PROGRAMADAS
- TIEMPO DE PARADA NO PROGRAMADA
- NÚMERO DE OPERARIOS
- TIEMPO PARA PROG
- TIEMPO DISP
- META
- EFICIENCIA

---

### BALANCEOS (CLASICO)

**Hojas:** Cada hoja representa un balanceo diferente

**Columnas esperadas:**
- LETRA
- OPERACIÓN
- PRECEDENCIA
- MAQUINA
- SAM
- OPERARIO
- OP
- SECCIÓN

---

## ✅ Validaciones

El script realiza las siguientes validaciones:

### Para POLOS y PRODUCCION:
- Descarta filas completamente vacías
- Requiere al menos FECHA u ORDEN DE PRODUCCIÓN
- Convierte valores numéricos correctamente
- Maneja valores NULL

### Para BALANCEOS:
- Busca automáticamente los encabezados en el archivo
- Valida que exista la columna SAM
- Descarta filas con SAM > 500 (probablemente totales)
- Valida secciones (DEL, TRAS, ENS, OTRO)
- Calcula métricas automáticamente

---

## 📊 Resumen Final

Al finalizar, el script muestra un resumen con:
- Total de registros procesados
- Total de registros descartados
- Estadísticas por tipo de importación

---

## ⚠️ Advertencias

1. **Modo LIMPIAR**: Elimina SOLO los datos de las tablas específicas:
   - `registro_piso_polo`
   - `registro_piso_produccion`
   - `operaciones_balanceo`
   - `balanceos`
   - `prendas`
   
   **Las demás tablas NO son afectadas** (usuarios, roles, configuraciones, etc.)

2. **Modo DRY-RUN**: Útil para verificar que los archivos se lean correctamente sin modificar la base de datos.

3. **Tiempo de ejecución**: La importación puede tardar varios minutos dependiendo del tamaño de los archivos.

---

## 🔧 Solución de Problemas

### Error: "El archivo no existe"
- Verifica que los archivos estén en la carpeta `resources/`
- O especifica la ruta completa con las opciones `--polo`, `--produccion`, `--balanceo`

### Error: "No se encontró la hoja REGISTRO"
- Verifica que el archivo Excel tenga una hoja llamada "REGISTRO"
- Para balanceos, cada hoja se procesa automáticamente

### Error: "No se encontró la columna SAM"
- Verifica que el archivo de balanceos tenga una columna llamada "SAM"
- Los encabezados deben estar en mayúsculas o el script los convertirá

---

## 📝 Notas

- El script reutiliza el código existente de `ejecutar_insert_polo.php` y `ejecutar_insert_produccion.php`
- Los datos se insertan en lotes para mejorar el rendimiento
- Se mantiene la compatibilidad con el formato de los archivos Excel existentes
- El script es compatible con Laravel y usa Eloquent ORM

---

## 👨‍💻 Desarrollado para

**Mundo Industrial - Sistema de Gestión de Producción**

Versión: 2.0
Fecha: Noviembre 2025
