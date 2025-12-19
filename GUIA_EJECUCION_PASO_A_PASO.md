# 🎯 GUÍA DE EJECUCIÓN PASO A PASO

## 🚀 Pasos para Activar LOGO Pedidos

### PASO 1: Verificar Archivos (5 minutos)

```bash
# Abrir PowerShell o CMD
cd c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial

# Ejecutar verificación
php check_logo_implementation.php
```

**Resultado esperado**:
```
═══════════════════════════════════════════════════════════════
🎨  VERIFICACIÓN DE IMPLEMENTACIÓN LOGO PEDIDOS
═══════════════════════════════════════════════════════════════

📋 Modelos
──────────────────────────────────────────────────
   ✅ SÍ   LogoPedido
   ✅ SÍ   LogoPedidoImagen
```

---

### PASO 2: Ejecutar Migraciones (5 minutos)

```bash
# En la terminal, en el directorio del proyecto
php artisan migrate
```

**Resultado esperado**:
```
Migrating: 2025_12_19_create_logo_pedidos_table
Migrated:  2025_12_19_create_logo_pedidos_table (0.52s)

Migrating: 2025_12_19_create_logo_pedido_imagenes_table
Migrated:  2025_12_19_create_logo_pedido_imagenes_table (0.48s)
```

---

### PASO 3: Verificar Tablas en BD (5 minutos)

**Opción A: PHPMyAdmin**
1. Abrir: `http://localhost/phpmyadmin`
2. Seleccionar base de datos del proyecto
3. Buscar tablas:
   - ✅ `logo_pedidos`
   - ✅ `logo_pedido_imagenes`

**Opción B: Tinker (Laravel)**
```bash
php artisan tinker

# Dentro de tinker:
>>> DB::table('logo_pedidos')->count()
0

>>> DB::table('logo_pedido_imagenes')->count()
0

>>> exit
```

**Resultado esperado**:
```
Ambas tablas existen y están vacías (count = 0)
```

---

### PASO 4: Verificar Modelos (3 minutos)

```bash
php artisan tinker

# Dentro de tinker:
>>> use App\Models\LogoPedido;
>>> LogoPedido::generarNumeroPedido()
"LOGO-00001"

>>> use App\Models\LogoPedidoImagen;
>>> LogoPedidoImagen::first()
null

>>> exit
```

**Resultado esperado**:
```
✅ Modelos cargados correctamente
✅ Método generarNumeroPedido() retorna: LOGO-00001
```

---

### PASO 5: Probar en Navegador (15-20 minutos)

#### 5A. Abrir la UI
1. Ir a: `http://localhost:8000/asesores/pedidos-produccion/crear-desde-cotizacion`
2. **Verificar**:
   - ✅ Página carga sin errores
   - ✅ Campo de búsqueda visible
   - ✅ Buscador de cotizaciones funciona

#### 5B. Buscar Cotización LOGO
1. En el buscador, buscar una cotización que sea de tipo **LOGO** (tipo_cotizacion_codigo = 'L')
2. **Verificar**:
   - ✅ Se muestra en el dropdown
   - ✅ Cliente y Asesor visibles

#### 5C. Seleccionar Cotización LOGO
1. Click en la cotización LOGO del dropdown
2. **Verificar**:
   - ✅ Se carga la cotización
   - ✅ El título cambia a "**3 Información del Logo**"
   - ✅ El formulario LOGO aparece

#### 5D. Ver Formulario LOGO
Se debe mostrar 5 secciones:
```
✅ Descripción (textarea)
✅ Imágenes (galería)
✅ Técnicas (selector)
✅ Observaciones Técnicas (textarea)
✅ Ubicación (modal)
```

---

### PASO 6: Llenar Formulario (5 minutos)

#### 6A. Completar Descripción
1. Escribir en el campo "Descripción":
   ```
   Logo bordado de la empresa, colores corporativos
   ```

#### 6B. Agregar Imágenes
1. Click "Agregar Imágenes"
2. Seleccionar 1-2 imágenes de tu PC
3. **Verificar**:
   - ✅ Imágenes aparecen en la galería
   - ✅ Botón eliminar visible al pasar mouse

#### 6C. Seleccionar Técnicas
1. Click dropdown "Técnicas"
2. Seleccionar: **BORDADO**
3. **Verificar**:
   - ✅ Aparece como badge azul
   - ✅ Botón eliminar visible
4. Agregar otra: **DTF**

#### 6D. Agregar Ubicación
1. Click "Agregar Ubicación"
2. Seleccionar: **CAMISA**
3. Seleccionar opciones: **PECHO** y **ESPALDA**
4. Escribir observación: "Logo principal del cliente"
5. Click "Guardar"
6. **Verificar**:
   - ✅ Aparece como tarjeta
   - ✅ Botones Editar/Eliminar visibles

#### 6E. Agregar Observaciones Técnicas
1. Escribir:
   ```
   Usar hilo rojo para contraste con el blanco
   ```

---

### PASO 7: Enviar Formulario (2 minutos)

1. **Abrir DevTools** (F12)
2. Ir a pestaña **Console**
3. Click botón **"Crear Pedido"**
4. **Esperar** y observar logs en consola:
   ```
   🎨 Enviando formulario...
   🎨 [LOGO] Preparando datos de LOGO para enviar
   ✅ [LOGO] Pedido creado:
   🎨 [LOGO] Datos del LOGO pedido a guardar:
   ✅ [LOGO] Respuesta del servidor:
   ```

5. **Verificar**:
   - ✅ Aparece modal "¡Éxito!"
   - ✅ Muestra: "Pedido de LOGO creado exitosamente"
   - ✅ Muestra número como: "Número de LOGO: LOGO-00001"

---

### PASO 8: Verificar en BD (5 minutos)

**Opción A: PhpMyAdmin**
1. Ir a: `http://localhost/phpmyadmin`
2. Base de datos → Tabla `logo_pedidos`
3. Click "Examinar"
4. **Ver**:
   - ✅ 1 fila nueva
   - ✅ numero_pedido = "LOGO-00001"
   - ✅ descripcion = lo que escribiste
   - ✅ tecnicas = `["BORDADO", "DTF"]` (JSON)
   - ✅ ubicaciones = JSON con CAMISA

**Opción B: Terminal**
```bash
php artisan tinker

# Ver el LOGO pedido creado
>>> use App\Models\LogoPedido;
>>> $logo = LogoPedido::latest()->first();
>>> $logo->numero_pedido
"LOGO-00001"

>>> $logo->tecnicas
["BORDADO", "DTF"]

>>> $logo->descripcion
"Logo bordado de la empresa, colores corporativos"

>>> $logo->imagenes()->count()
2

>>> $logo->imagenes
[
  {
    "logo_pedido_id": 1,
    "nombre_archivo": "logo_1_xxx.jpg",
    "url": "/storage/logo_pedidos/1/...",
    ...
  }
]

>>> exit
```

---

### PASO 9: Verificar Imágenes en Storage (3 minutos)

1. **Abrir explorer**: `c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial\storage\app`
2. **Navegar a**: `logo_pedidos\1\`
3. **Ver**:
   - ✅ Directorio existe
   - ✅ Contiene 1-2 archivos `.jpg`
   - ✅ Archivos tienen tamaño > 0 bytes

---

### PASO 10: Crear Segundo LOGO Pedido (Opcional) (10 minutos)

Para verificar que la secuencia de números funciona:

1. Repetir pasos 5C-7
2. **Verificar**:
   - ✅ Nuevo LOGO número = "LOGO-00002"
   - ✅ 2 filas en tabla `logo_pedidos`
   - ✅ 2 directorios en `storage/app/logo_pedidos/`

---

## 📋 Checklist de Validación Final

- [ ] Migraciones ejecutadas sin errores
- [ ] Tablas `logo_pedidos` y `logo_pedido_imagenes` creadas
- [ ] Modelos `LogoPedido` y `LogoPedidoImagen` funcionan
- [ ] UI renderiza formulario LOGO correctamente
- [ ] Campos editables funcionan (descripción, técnicas, ubicaciones)
- [ ] Imágenes se pueden agregar (1-5)
- [ ] Imágenes se guardan en storage
- [ ] Formulario se envía correctamente
- [ ] LOGO Pedido se crea en BD con numero_pedido correcto
- [ ] JSON se guarda correctamente en BD
- [ ] Respuesta del servidor es exitosa
- [ ] Números LOGO se generan secuencialmente

**Si todos los checks son ✅, el sistema está listo.**

---

## 🐛 Troubleshooting Rápido

### Problema: "SQLSTATE[42S02]: Table 'tabla.logo_pedidos' doesn't exist"
```bash
# Ejecutar migraciones
php artisan migrate
```

### Problema: "Class 'App\Models\LogoPedido' not found"
```bash
# Limpiar cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Problema: "The file 'xxx' does not exist"
```bash
# Crear directorio de almacenamiento
mkdir -p storage/app/logo_pedidos
chmod 775 storage/app/logo_pedidos
```

### Problema: "CSRF token mismatch"
- Verificar que la página está dentro de session PHP
- No es problema si estás usando Postman (requiere token)

### Problema: Las imágenes no se guardan
- Verificar permisos: `chmod 775 storage/app`
- Verificar espacio en disco
- Revisar logs: `tail -f storage/logs/laravel.log`

---

## ⏱️ Tiempo Total Estimado

| Paso | Tiempo |
|------|--------|
| Verificación de archivos | 5 min |
| Migraciones | 5 min |
| Verificación en BD | 5 min |
| Verificación de modelos | 3 min |
| Prueba en navegador | 20 min |
| Llenar formulario | 5 min |
| Enviar y verificar | 10 min |
| **TOTAL** | **~53 minutos** |

*Nota: Si todo va bien, puede ser más rápido. Si hay errores, consulta troubleshooting.*

---

## ✅ Confirmación de Completitud

Después de terminar TODOS los pasos:

```
┌────────────────────────────────────────────┐
│         SISTEMA LOGO PEDIDOS ACTIVO        │
├────────────────────────────────────────────┤
│ Fecha de activación: [HOY]                 │
│ Primera LOGO creada: LOGO-00001            │
│ Status: ✅ 100% FUNCIONAL                  │
│                                            │
│ Próximos pasos:                            │
│ 1. Crear vistas de listado (opcional)      │
│ 2. Crear vistas de detalle (opcional)      │
│ 3. Exportar a PDF (opcional)               │
│ 4. Dashboard (opcional)                    │
└────────────────────────────────────────────┘
```

---

## 📞 Si Algo Falla

1. **Revisar logs**: `tail -f storage/logs/laravel.log`
2. **Ejecutar verificación**: `php check_logo_implementation.php`
3. **Limpiar cache**: `php artisan cache:clear`
4. **Ejecutar migraciones de nuevo**: `php artisan migrate`
5. **Revisar documentación**: `IMPLEMENTACION_LOGO_PEDIDOS.md`

---

**Versión**: 1.0  
**Fecha**: 2025-12-19  
**Status**: ✅ Listo para ejecutar
