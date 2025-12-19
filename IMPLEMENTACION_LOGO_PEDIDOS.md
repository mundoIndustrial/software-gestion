# 🎨 Implementación de LOGO Pedidos - Guía Completa

## Estado Actual
Se han completado **TODOS LOS COMPONENTES** necesarios para guardar pedidos de LOGO en la base de datos.

---

## 📋 Archivos Creados/Modificados

### 1. **Migraciones (Database)**
✅ `database/migrations/2025_12_19_create_logo_pedidos_table.php`
- Tabla `logo_pedidos` con campos para:
  - `numero_pedido` (LOGO-00001, LOGO-00002, etc.)
  - `descripcion`, `tecnicas`, `ubicaciones`, `observaciones_tecnicas`
  - Foreign keys a `pedido_produccions` y `logo_cotizacions`

✅ `database/migrations/2025_12_19_create_logo_pedido_imagenes_table.php`
- Tabla `logo_pedido_imagenes` (tabla hija) con:
  - Referencia a `logo_pedidos`
  - Campos para URL, rutas de almacenamiento
  - Campo `orden` para ordenar imágenes en galería

### 2. **Modelos (Eloquent)**
✅ `app/Models/LogoPedido.php`
- Relaciones: `pedidoProduccion()`, `logoCotizacion()`, `imagenes()`
- Método `generarNumeroPedido()` para secuencia LOGO-00001
- Casting JSON para `tecnicas` y `ubicaciones`

✅ `app/Models/LogoPedidoImagen.php`
- Relación: `logoPedido()`
- Accessor `getUrlMuestraAttribute()` para obtener URL correcta

### 3. **Backend - Controlador**
✅ `app/Http/Controllers/Asesores/PedidoProduccionController.php`
- Nuevo método: `guardarLogoPedido()` (líneas 579-750)
  - Validación de datos
  - Creación de registro LogoPedido
  - Procesamiento de imágenes (base64 → almacenamiento)
  - Creación de referencias en `logo_pedido_imagenes`
  - Logging detallado con emojis para debugging

### 4. **Rutas**
✅ `routes/asesores/pedidos.php`
- Nueva ruta: `POST /pedidos/guardar-logo-pedido`
- Maps a `PedidoProduccionController@guardarLogoPedido`

### 5. **Frontend - JavaScript**
✅ `public/js/crear-pedido-editable.js` (líneas 1763-1890)
- Detecta si es LOGO comparando arrays globales
- Crea el pedido primero usando endpoint existente
- Luego guarda datos LOGO usando nuevo endpoint
- Captura de datos desde:
  - `logoTecnicasSeleccionadas`
  - `logoSeccionesSeleccionadas` (ubicaciones)
  - `logoFotosSeleccionadas`
  - Campos de descripción y observaciones

---

## 🚀 Pasos para Activar

### Paso 1: Ejecutar Migraciones
```bash
php artisan migrate
```

Esto creará las dos tablas:
- `logo_pedidos`
- `logo_pedido_imagenes`

### Paso 2: Verificar Modelo LogoCotizacion
El modelo `LogoCotizacion` debe existir. Si no existe, crear:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoCotizacion extends Model
{
    protected $table = 'logo_cotizacions';
    protected $fillable = ['cotizacion_id', 'fotos', /* otros campos */];
}
```

### Paso 3: Limpiar Cache (Opcional)
```bash
php artisan config:cache
php artisan route:cache
```

---

## 📊 Flujo de Datos

### Cuando usuario crea un LOGO Pedido:

1. **Frontend detecta LOGO**
   ```javascript
   const esLogo = logoTecnicasSeleccionadas.length > 0 || 
                  logoSeccionesSeleccionadas.length > 0 || 
                  logoFotosSeleccionadas.length > 0;
   ```

2. **Crea pedido base**
   ```
   POST /asesores/pedidos-produccion/crear-desde-cotizacion/{id}
   Body: { cotizacion_id, forma_de_pago, prendas: [] }
   Response: { success, pedido_id, logo_cotizacion_id }
   ```

3. **Guarda datos LOGO**
   ```
   POST /asesores/pedidos/guardar-logo-pedido
   Body: {
     pedido_id,
     logo_cotizacion_id,
     descripcion,
     tecnicas: ["BORDADO", "DTF"],
     ubicaciones: [{ubicacion: "CAMISA", opciones: [...], observaciones: "..."}],
     observaciones_tecnicas,
     fotos: [{url, existing, id}, ...]
   }
   ```

4. **Procesa imágenes**
   - Si `existing: true`: Solo crea referencia en BD
   - Si `existing: false`: Convierte base64 → archivo en `/storage/logo_pedidos/{id}/`

5. **Retorna confirmación**
   ```json
   {
     "success": true,
     "message": "LOGO Pedido guardado correctamente",
     "logo_pedido": {
       "id": 1,
       "numero_pedido": "LOGO-00001",
       "descripcion": "...",
       "imagenes_count": 3
     }
   }
   ```

---

## 🗄️ Estructura de Datos en BD

### Tabla `logo_pedidos`
```sql
+-----+----------+--------------------+---------+----------+---------------------+
| id  | pedido_id | numero_pedido      | descripcion | tecnicas | ubicaciones    |
+-----+----------+--------------------+---------+----------+---------------------+
| 1   | 42       | LOGO-00001         | "..."   | JSON     | JSON              |
+-----+----------+--------------------+---------+----------+---------------------+
```

### Tabla `logo_pedido_imagenes`
```sql
+----+-----------------+------------------+----+----------+
| id | logo_pedido_id  | nombre_archivo   | url | orden    |
+----+-----------------+------------------+----+----------+
| 1  | 1               | logo_1_xxx.jpg   | /storage/... | 1 |
+----+-----------------+------------------+----+----------+
```

---

## 🔍 Validación Post-Implementación

### Verificar que funciona:

1. **En Base de Datos**
   ```sql
   SELECT * FROM logo_pedidos;
   SELECT * FROM logo_pedido_imagenes;
   ```

2. **En Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Buscar logs con emojis 🎨 para LOGO operations

3. **En Postman/Insomnia**
   ```
   POST http://localhost:8000/asesores/pedidos/guardar-logo-pedido
   Headers: {
     "Content-Type": "application/json",
     "X-CSRF-TOKEN": "<token>"
   }
   Body: {
     "pedido_id": 1,
     "descripcion": "Logo test",
     "tecnicas": ["BORDADO"],
     "ubicaciones": [{
       "ubicacion": "CAMISA",
       "opciones": ["PECHO"],
       "observaciones": "Test"
     }],
     "fotos": []
   }
   ```

---

## 🐛 Debugging

### Logs importantes
- 🎨 `[PedidoProduccionController]` - Información LOGO
- ✅ `LogoPedido creado exitosamente`
- 📸 `Imagen nueva guardada en storage`
- ❌ `Error guardando LOGO Pedido`

### Rutas de almacenamiento
- **Imágenes LOGO**: `storage/app/logo_pedidos/{logo_pedido_id}/`
- **URLs públicas**: `/storage/logo_pedidos/{id}/filename.jpg`

---

## 📝 Notas Importantes

1. **Numero de Pedido**: Se genera automáticamente en formato LOGO-00001, LOGO-00002, etc.
2. **Imágenes**: Soporta base64 y referencias a imágenes existentes de cotización
3. **JSON Columns**: `tecnicas` y `ubicaciones` se guardan como JSON en BD
4. **Relaciones**: Usa `onDelete('cascade')` para limpiar automáticamente

---

## ✨ Características Implementadas

- ✅ Formulario dinámico LOGO en frontend
- ✅ Campos editables (descripción, técnicas, ubicaciones, observaciones)
- ✅ Galería de imágenes con add/delete
- ✅ Modal avanzado para ubicaciones con opciones personalizadas
- ✅ Generación automática de número LOGO
- ✅ Almacenamiento seguro de imágenes
- ✅ Relaciones BD con cascadas
- ✅ Logging detallado
- ✅ Manejo de errores y validaciones
- ✅ Respuestas JSON estándar

---

## 🎯 Próximos Pasos (Opcionales)

1. Crear vistas para mostrar LOGO pedidos creados
2. Implementar búsqueda de LOGO pedidos (por numero_pedido, descripción)
3. Exportar LOGO pedidos a PDF
4. Dashboard con estadísticas de LOGO pedidos
5. Historial de cambios a LOGO pedidos

---

**Autor**: Asistente de IA  
**Fecha**: 2025-12-19  
**Estado**: ✅ Completado
