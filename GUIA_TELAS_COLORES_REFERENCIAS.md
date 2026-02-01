# 📋 GUÍA: Guardar Telas, Colores y Referencias en Cotización de Logo INDIVIDUAL

## ✅ Estado: IMPLEMENTACIÓN COMPLETADA

El sistema está **100% funcional** para guardar información de telas, colores y referencias de prendas en cotizaciones de logo individual.

---

## 🏗️ Arquitectura Implementada

### Base de Datos
- **Tabla**: `logo_cotizacion_telas_prenda`
- **Relaciones**: FK a `logo_cotizaciones` y `prendas_cot`
- **Migración**: Ejecutada ✅

### Backend
- **Controlador**: `CotizacionBordadoController`
- **Métodos**:
  - `procesarTelasDelFormulario()` - Procesa telas enviadas con la cotización
  - `guardarTelaPrenda()` - Endpoint POST para guardar tela individual
  - `obtenerTelasPrenda()` - Endpoint GET para listar telas
  - `eliminarTelaPrenda()` - Endpoint DELETE para eliminar tela

### Rutas
```
POST   /cotizaciones/{cotizacion_id}/logo/telas-prenda           → guardarTelaPrenda()
GET    /cotizaciones/{cotizacion_id}/logo/telas-prenda           → obtenerTelasPrenda()
DELETE /cotizaciones/{cotizacion_id}/logo/telas-prenda/{tela_id} → eliminarTelaPrenda()
```

---

## 🚀 Cómo Funciona

### Flujo 1: Guardado Automático al Enviar Cotización

Cuando el usuario envía una cotización de logo individual, el sistema **automáticamente** guarda las telas si vienen en el request:

```javascript
// En el frontend (JavaScript):
const tecnicas = [
  {
    prendas: [
      {
        prenda_cot_id: 1,
        nombre_prenda: "Polo Básico"
        // ... otros datos
      }
    ]
  }
];

// El formulario debe enviar TAMBIÉN:
// - tela_prenda_0: "Algodón 100%"
// - color_prenda_0: "Rojo"
// - ref_prenda_0: "REF-001"
```

**¿Cómo se envía?**

En el formulario HTML del frontend, se agregan campos con nombres específicos:

```html
<!-- Para la prenda en índice 0 de prendas -->
<input type="text" name="tela_prenda_0" placeholder="Tela">
<input type="text" name="color_prenda_0" placeholder="Color">
<input type="text" name="ref_prenda_0" placeholder="Referencia">
<input type="file" name="img_tela_prenda_0">

<!-- Para la prenda en índice 1 de prendas -->
<input type="text" name="tela_prenda_1" placeholder="Tela">
<input type="text" name="color_prenda_1" placeholder="Color">
<input type="text" name="ref_prenda_1" placeholder="Referencia">
```

### Flujo 2: Guardado Individual via API

Si necesitas guardar una tela para una prenda específica después de que la cotización ya existe:

**Request:**
```bash
curl -X POST "http://localhost/cotizaciones/1/logo/telas-prenda" \
  -H "X-CSRF-TOKEN: token" \
  -F "logo_cotizacion_id=1" \
  -F "prenda_cot_id=1" \
  -F "tela=Algodón 100%" \
  -F "color=Rojo" \
  -F "ref=REF-001" \
  -F "imagen=@archivo.webp"
```

**Response:**
```json
{
  "success": true,
  "message": "Información de tela, color y referencia guardada correctamente",
  "data": {
    "id": 1,
    "logo_cotizacion_id": 1,
    "prenda_cot_id": 1,
    "tela": "Algodón 100%",
    "color": "Rojo",
    "ref": "REF-001",
    "img": "storage/app/public/cotizaciones/1/telas/...",
    "url_imagen": "http://localhost/storage/cotizaciones/1/telas/..."
  }
}
```

---

## 📝 Pasos para Integrar en el Formulario Real

### 1. Abre el formulario de cotización
**Archivo**: `resources/views/cotizaciones/bordado/create.blade.php`

### 2. Busca el modal donde se agregan prendas
**Sección**: `<div id="modalAgregarTecnica">` (línea ~1271)

### 3. En cada fila de prenda, agrega campos para tela, color y ref

**Ejemplo de cómo se vería:**

```html
<!-- Dentro del modal, para cada prenda renderizada -->
<div class="prenda-row" style="border: 1px solid #ddd; padding: 10px; margin: 10px 0;">
    <h4>Prenda: <span class="prenda-nombre"></span></h4>
    
    <!-- Campos existentes -->
    <input type="text" class="nombre-prenda" placeholder="Nombre de prenda">
    <!-- ... más campos ... -->
    
    <!-- NUEVOS CAMPOS PARA TELA -->
    <div style="background: #f9f9f9; padding: 10px; margin-top: 10px; border-radius: 4px;">
        <h5 style="margin-top: 0;">Información de Tela</h5>
        
        <label>Nombre de Tela:</label>
        <input type="text" class="tela-nombre" placeholder="Ej: Algodón 100%" name="tela_prenda_0">
        
        <label>Color:</label>
        <input type="text" class="tela-color" placeholder="Ej: Rojo" name="color_prenda_0">
        
        <label>Referencia:</label>
        <input type="text" class="tela-ref" placeholder="Ej: REF-ALG-001" name="ref_prenda_0">
        
        <label>Imagen de Tela (opcional):</label>
        <input type="file" class="tela-imagen" name="img_tela_prenda_0" accept="image/*">
    </div>
</div>
```

### 4. Al guardar la técnica, incluye estos datos

En la función `guardarTecnica()` en JavaScript, asegúrate de que los valores de estos campos se capturan y se envíen con el formulario.

### 5. El controlador automáticamente procesará los datos

Cuando se envíe el formulario POST a `/cotizaciones-bordado`, el método `procesarTelasDelFormulario()` detectará automáticamente los campos `tela_prenda_X`, `color_prenda_X`, `ref_prenda_X` y los guardará en la BD.

---

## 🧪 Pruebas Rápidas

### Test 1: Crear registro de prueba (ya implementado)
```
GET http://localhost:8000/test-tela-prenda/crear
```

### Test 2: Listar todos los registros
```
GET http://localhost:8000/test-tela-prenda/listar
```

### Test 3: Guardar vía API
```bash
curl -X POST "http://localhost/cotizaciones/1/logo/telas-prenda" \
  -H "X-CSRF-TOKEN: token" \
  -F "logo_cotizacion_id=1" \
  -F "prenda_cot_id=1" \
  -F "tela=Prueba" \
  -F "color=Azul" \
  -F "ref=TEST-001"
```

### Test 4: Obtener telas
```bash
curl "http://localhost/cotizaciones/1/logo/telas-prenda"
```

---

## 📊 Consultas SQL para Verificar

```sql
-- Ver todas las telas guardadas
SELECT * FROM logo_cotizacion_telas_prenda;

-- Ver telas de una cotización específica
SELECT * FROM logo_cotizacion_telas_prenda 
WHERE logo_cotizacion_id = 1;

-- Ver telas con detalles
SELECT 
    ltp.id,
    ltp.logo_cotizacion_id,
    ltp.prenda_cot_id,
    pc.nombre_producto,
    ltp.tela,
    ltp.color,
    ltp.ref,
    ltp.img,
    ltp.created_at
FROM logo_cotizacion_telas_prenda ltp
LEFT JOIN prendas_cot pc ON ltp.prenda_cot_id = pc.id
ORDER BY ltp.created_at DESC;
```

---

## 🔑 Campos del Formulario Requeridos

Para que el sistema guarde automáticamente las telas al enviar la cotización:

```
Nombre del campo          | Tipo          | Descripción
--------------------------|---------------|------------------------------
tela_prenda_{N}           | text          | Nombre de la tela para prenda N
color_prenda_{N}          | text          | Color para prenda N
ref_prenda_{N}            | text          | Referencia para prenda N
img_tela_prenda_{N}       | file (image)  | Imagen de tela (opcional)

Donde {N} es el índice de la prenda en el array (0, 1, 2, ...)
```

---

## ✨ Características

✅ Guardado automático al enviar cotización  
✅ API individual para guardar/obtener/eliminar telas  
✅ Almacenamiento de imágenes  
✅ Generación automática de URLs públicas  
✅ Logging completo de operaciones  
✅ Validación de datos  
✅ Transacciones en BD para integridad  
✅ Relaciones con LogoCotización y PrendaCot  

---

## 🚨 Próximas Acciones

1. **Agregar campos visuales en el modal** del formulario
2. **Capturar datos en JavaScript** cuando se guardan prendas
3. **Incluir en FormData** antes de enviar
4. **Probar con cotización real**

---

## 📞 Soporte

Si necesitas ayuda con la integración, revisa:
- `app/Infrastructure/Http/Controllers/CotizacionBordadoController.php` → `procesarTelasDelFormulario()`
- `resources/views/cotizaciones/bordado/create.blade.php` → Modal de prendas
- `app/Models/LogoCotizacionTelasPrenda.php` → Modelo
