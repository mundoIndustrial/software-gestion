# Formulario Editable de Pedidos de Producción - GUÍA DE IMPLEMENTACIÓN

## 📋 Descripción General

Se ha implementado una **nueva versión mejorada y editable** del formulario de creación de pedidos de producción. Esta versión permite:

✅ **Visualizar todas las imágenes** asociadas a cada prenda de la cotización  
✅ **Editar campos** de cada prenda en tiempo real  
✅ **Eliminar prendas** que no desees incluir en el pedido  
✅ **Gestionar tallas** (agregar/quitar cantidades por talla)  
✅ **Modificar información** de género, tela, color, descripción, etc.  

---

## 🌐 Acceso a la Nueva Funcionalidad

### URL de la Nueva Vista Editable
```
http://servermi:8000/asesores/pedidos-produccion/crear-editable
```

### URL de la Vista Original (Sin cambios)
```
http://servermi:8000/asesores/pedidos-produccion/crear
```

---

## 🔧 Archivos Implementados

### 1. **Vista Blade** - `crear-desde-cotizacion-editable.blade.php`
- **Ubicación**: `resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php`
- **Descripción**: Interfaz HTML del formulario editable con estilos optimizados
- **Características**:
  - Grid responsivo para mostrar prendas
  - Imagen principal con fotos adicionales en miniatura
  - Campos editables para cada prenda
  - Sección de tallas editable
  - Selector de género con checkboxes
  - Resumen visual de cada prenda

### 2. **JavaScript Frontend** - `crear-pedido-editable.js`
- **Ubicación**: `public/js/crear-pedido-editable.js`
- **Descripción**: Lógica de interacción del formulario
- **Funcionalidades**:
  - Búsqueda y selección de cotizaciones
  - Carga dinámica de prendas vía AJAX
  - Renderizado editable de prendas
  - Eliminación de prendas (marca índices internamente)
  - Eliminación de tallas
  - Recopilación y envío de datos editados

### 3. **Controlador** - `PedidosProduccionController.php`
- **Nuevo Método**: `crearFormEditable()`
- **Ubicación**: `app/Http/Controllers/Asesores/PedidosProduccionController.php`
- **Descripción**: Devuelve la vista editable con cotizaciones disponibles

### 4. **Rutas**
- **Archivo**: `routes/web.php` y `routes/asesores/pedidos.php`
- **Nuevas Rutas**:
  ```php
  Route::get('/pedidos-produccion/crear-editable', 
      [PedidosProduccionController::class, 'crearFormEditable'])
      ->name('pedidos-produccion.crear-editable');
  
  Route::get('/obtener-datos-cotizacion/{cotizacion_id}',
      [PedidoProduccionController::class, 'obtenerDatosCotizacion'])
      ->name('obtener-datos-cotizacion');
  ```

---

## 📐 Flujo de Uso

### 1️⃣ Paso 1: Seleccionar Cotización
- El usuario entra a `/asesores/pedidos-produccion/crear-editable`
- Busca una cotización por número, cliente o asesor
- Selecciona la cotización deseada

### 2️⃣ Paso 2: Información del Pedido se Carga Automáticamente
- Número de cotización
- Cliente
- Asesora
- Forma de pago
- (El número de pedido se asigna automáticamente al guardar)

### 3️⃣ Paso 3: Editar Prendas
Para cada prenda el usuario puede:

**📝 Campos Editables:**
- ✏️ Nombre del producto
- ✏️ Descripción
- ✏️ Tela
- ✏️ Color
- ✏️ Género (Dama/Caballero - checkboxes múltiples)

**📊 Tallas - Cantidades:**
- Ingresar cantidad numérica para cada talla disponible
- Quitar tallas individuales si es necesario

**📷 Imágenes:**
- Ver imagen principal (clickeable para ampliar en modal)
- Ver miniaturas de imágenes adicionales
- Todas las fotos se adjuntarán automáticamente al pedido

**🗑️ Eliminar Prenda Completa:**
- Botón "🗑️ Eliminar Prenda" en la esquina superior derecha
- La prenda se marca como eliminada internamente (no se envía al servidor)

### 4️⃣ Paso 4: Crear Pedido
- Revisar toda la información editada
- Hacer clic en "✓ Crear Pedido de Producción"
- El sistema envía:
  - Solo las prendas NO eliminadas
  - Con los valores editados (nombre, descripción, tela, color, etc.)
  - Con las cantidades por talla ingresadas
  - Con todas las imágenes asociadas

---

## 🎨 Características Visuales

### Tarjeta de Prenda (Prenda Card)
```
┌─────────────────────────────────────────┐
│ 🧥 Prenda 1: [Nombre] ([variaciones])   │ 🗑️ Eliminar
├─────────────────────────────────────────┤
│ ┌─────────────────┐  ┌─────────────────┐│
│ │                 │  │ Nombre Producto ││
│ │                 │  │ Descripción     ││
│ │  Imagen         │  │ Tela            ││
│ │  Principal      │  │ Color           ││
│ │                 │  │ Género: □ □     ││
│ │                 │  │                 ││
│ └─────────────────┘  │ Tallas:         ││
│ [Mini] [Mini]        │ XS: [  ]        ││
│ [Mini] [Mini]        │ S:  [  ]        ││
│                      │ M:  [  ]        ││
│                      │ L:  [  ]        ││
│                      │ XL: [  ]        ││
│                      └─────────────────┘│
│                                         │
│ 📊 Resumen: [Tallas] [Fotos] [etc]    │
└─────────────────────────────────────────┘
```

### Colores y Estados
- **Border Normal**: Gris (#e5e7eb)
- **Border en Hover**: Azul (#3b82f6)
- **Shadow en Hover**: Azul suave
- **Botón Eliminar**: Rojo (#ef4444)
- **Alerta Info**: Azul claro (#dbeafe)

---

## 📊 Estructura de Datos Enviados

Cuando se crea el pedido, se envía un JSON como este:

```json
{
  "cotizacion_id": 123,
  "forma_de_pago": "Contado",
  "prendas": [
    {
      "index": 0,
      "nombre_producto": "Camisa Polo Dama",
      "descripcion": "Polo con logo bordado...",
      "tela": "Algodón 100%",
      "color": "Azul Royal",
      "genero": ["dama"],
      "manga": "Corta",
      "cantidades": {
        "XS": 5,
        "S": 10,
        "M": 15
      },
      "fotos": ["url1.jpg", "url2.jpg", "url3.jpg"],
      "telas": ["tela_url.jpg"],
      "logos": ["logo_url.jpg"]
    },
    {
      "index": 2,
      "nombre_producto": "Pantalón Caballero",
      "descripcion": "Pantalón de trabajo...",
      "tela": "Gabardina",
      "color": "Negro",
      "genero": ["caballero"],
      "cantidades": {
        "30": 8,
        "32": 12
      },
      "fotos": ["url1.jpg"],
      "telas": [],
      "logos": []
    }
  ]
}
```

**Nota**: El `index` corresponde a la posición original de la prenda en la cotización. Las prendas con índices eliminados NO se incluyen.

---

## 🔄 Integración con Sistema Existente

### Endpoints utilizados:
1. **Obtener cotizaciones**: Reutiliza datos de `criarForm()`
2. **Cargar prendas**: `/asesores/pedidos-produccion/obtener-datos-cotizacion/{id}`
   - Devuelve todas las prendas con fotos, tallas y variantes
3. **Crear pedido**: `/asesores/pedidos-produccion/crear-desde-cotizacion/{id}`
   - Mismo endpoint que la versión anterior
   - Acepta los mismos datos (pero con valores editados)

### No hay cambios en:
- ✅ Base de datos
- ✅ Modelos
- ✅ Lógica de creación de pedidos
- ✅ Vista anterior (`crear-desde-cotizacion.blade.php`)
- ✅ Rutas existentes

---

## 🧪 Pruebas Recomendadas

1. **Búsqueda de Cotizaciones**
   - Buscar por número
   - Buscar por cliente
   - Buscar por asesora
   - Verificar que solo muestra cotizaciones aprobadas

2. **Edición de Prendas**
   - Cambiar nombre, descripción, tela, color
   - Agregar cantidades por talla
   - Cambiar género

3. **Eliminación**
   - Eliminar prenda completa
   - Quitar tallas específicas
   - Verificar que al crear el pedido no se incluyen eliminadas

4. **Imágenes**
   - Verificar que se muestran todas las fotos
   - Verificar que se puede hacer click para ampliar
   - Verificar que se incluyen en el pedido

5. **Envío**
   - Crear pedido sin cantidades (debe mostrar error)
   - Crear pedido con prendas editadas
   - Verificar que el pedido se crea correctamente en la BD

---

## 🚀 Mejoras Futuras

- [ ] Drag & drop para reordenar prendas
- [ ] Cambiar imágenes principales de prendas
- [ ] Agregar campos de observaciones por prenda
- [ ] Guardado automático como borrador
- [ ] Duplicación de prendas existentes
- [ ] Vista previa del PDF final

---

## 📞 Soporte y Debugging

### Logs Console
El JavaScript genera logs detallados en la consola del navegador (F12):
```
✅ Script de formulario editable cargado correctamente
📊 Datos de cotizaciones recibidos: [...]
📋 Datos de cotización obtenidos: {...}
🗑️ Prenda eliminada: 0
📦 Prendas a enviar: [...]
```

### Errores Comunes
1. **"Cotización no encontrada"**: Verificar ID de cotización
2. **"No hay tallas definidas"**: La cotización no tiene tallas configuradas
3. **"Sin prendas con cantidades"**: El usuario no ingresó cantidades en ninguna talla

---

## 📝 Notas de Desarrollo

- El componente mantiene state de "prendas eliminadas" usando un `Set()` de índices
- Las imágenes se muestran a través de URLs almacenadas (sin cargas de archivos nuevos)
- El formulario es completamente editable sin afectar la cotización original
- Los datos se validan tanto en frontend como en backend

---

## ✅ Checklist de Implementación

- [x] Vista Blade creada
- [x] JavaScript frontend implementado
- [x] Nuevo método en controlador
- [x] Rutas configuradas
- [x] Integración con endpoints existentes
- [x] Estilos CSS optimizados
- [x] Documentación completada
- [x] Validaciones implementadas
- [x] Manejo de errores
- [x] Logs de debug incluidos

---

**Versión**: 1.0  
**Fecha**: 17 de Diciembre de 2025  
**Estado**: ✅ Listo para producción
