# SISTEMA DE LOGO PEDIDOS - IMPLEMENTACIÓN COMPLETA

## 📋 Resumen de la Lógica

El sistema LOGO divide la información del pedido en **3 tablas principales**:

### 1. **pedidos_produccion**
- **Propósito**: Información general del pedido
- **Almacena**: Cliente, asesor, forma de pago, estado, fechas, etc.
- **Número usado**: `numero_pedido` (ej: 45451)
- **Nota**: Aquí se crea el pedido PRIMERO cuando el usuario hace "Crear Pedido"

### 2. **logo_pedidos**
- **Propósito**: Información ESPECÍFICA del LOGO
- **Almacena**:
  - `pedido_id` → FK a pedidos_produccion
  - `logo_cotizacion_id` → FK a logo_cotizaciones
  - `numero_pedido` → Secuencia LOGO-00001, LOGO-00002, etc.
  - `descripcion` → Descripción del LOGO
  - `tecnicas` → JSON con técnicas seleccionadas
  - `ubicaciones` → JSON con ubicaciones y observaciones
  - `observaciones_tecnicas` → Observaciones generales
  - `timestamps` → created_at, updated_at
- **Número usado**: `numero_pedido` (ej: LOGO-00001)

### 3. **logo_pedido_imagenes**
- **Propósito**: Almacenar referencias de imágenes
- **Almacena**:
  - `logo_pedido_id` → FK a logo_pedidos
  - `nombre_archivo` → Nombre de la imagen
  - `url` → URL para acceder a la imagen
  - `ruta_original` → Ruta en `/storage/logo_pedidos/{logo_pedido_id}/`
  - `ruta_webp` → Versión optimizada (opcional)
  - `tipo_archivo` → MIME type (image/jpeg, etc.)
  - `tamaño_archivo` → Tamaño en bytes
  - `orden` → Orden de aparición
  - `timestamps` → created_at, updated_at

---

## 🔄 Flujo de Guardado Completo

### Paso 1: Crear PedidoProduccion
```
Usuario → Formulario → POST /asesores/pedidos-produccion/crear-desde-cotizacion
         ↓
    Valida datos
         ↓
    Crea registro en pedidos_produccion
         ↓
    Retorna: { success: true, pedido_id: XXX, numero_pedido: 45451 }
```

### Paso 2: Guardar LOGO específico
```
JavaScript recibe pedido_id y número LOGO ya capturado
         ↓
    POST /asesores/pedidos/guardar-logo-pedido
    Body: {
        pedido_id: 11384,
        logo_cotizacion_id: 1,
        descripcion: "...",
        tecnicas: [...],
        ubicaciones: [...],
        fotos: [...]
    }
         ↓
    Controller valida: pedido_id existe en pedidos_produccion
    Controller valida: logo_cotizacion_id existe en logo_cotizaciones
         ↓
    Crea registro en logo_pedidos (genera LOGO-00001)
         ↓
    Por cada foto:
    - Si es existente: solo crea referencia en logo_pedido_imagenes
    - Si es nueva: guarda en /storage/logo_pedidos/{logo_pedido_id}/ y crea referencia
         ↓
    Retorna: { success: true, logo_pedido: {...} }
```

---

## 📊 Número de Pedido Mostrable

Cuando se consulta un pedido, el **número que se muestra** depende del tipo:

### Para Pedidos NORMALES:
```php
$pedido = PedidoProduccion::find(11384);
echo $pedido->numero_pedido_mostrable;  // Output: 45451
```

### Para Pedidos LOGO:
```php
$pedido = PedidoProduccion::find(11384);
// Se cargará automáticamente el numero de LOGO
echo $pedido->numero_pedido_mostrable;  // Output: LOGO-00001
echo $pedido->esLogo();                 // Output: true
```

### Métodos Disponibles en PedidoProduccion:

- `logoPedidos()` → Relación HasMany con LogoPedido
- `logoPedido()` → Obtiene el primer (y único) LogoPedido
- `esLogo()` → Boolean que indica si es LOGO
- `getNumeroPedidoMostrable()` → Obtiene el número correcto
- `numero_pedido_mostrable` → Accessor (disponible en JSON)

---

## 💾 Almacenamiento de Imágenes

### Estructura de Carpetas:
```
/storage/logo_pedidos/
├── 1/
│   ├── logo_1_1702844400_1234.jpg
│   ├── logo_1_1702844401_5678.jpg
│   └── ...
├── 2/
│   ├── logo_2_1702844450_9012.jpg
│   └── ...
└── ...
```

### Referencia en BD:
```sql
-- logo_pedido_imagenes para logo_pedido_id = 1
SELECT * FROM logo_pedido_imagenes WHERE logo_pedido_id = 1;

-- Retorna:
-- id: 1, logo_pedido_id: 1, nombre_archivo: logo_1_1702844400_1234.jpg
--       ruta_original: logo_pedidos/1/logo_1_1702844400_1234.jpg
--       url: /storage/logo_pedidos/1/logo_1_1702844400_1234.jpg
```

---

## ✅ Validaciones Implementadas

1. **En el Controlador**:
   - ✅ `pedido_id` debe existir en `pedidos_produccion`
   - ✅ `logo_cotizacion_id` debe existir en `logo_cotizaciones`
   - ✅ Todas las imágenes deben tener datos válidos
   - ✅ Tecnicas y ubicaciones deben ser arrays válidos

2. **En el Modelo**:
   - ✅ Relaciones con FK configuradas
   - ✅ Casts JSON automáticos para tecnicas y ubicaciones
   - ✅ Generación automática de numero_pedido (LOGO-00001)

3. **En JavaScript**:
   - ✅ LogoCotizacionId se captura al cargar datos
   - ✅ Se valida que existan tecnicas, ubicaciones y fotos
   - ✅ Manejo de errores con SweetAlert2

---

## 🎯 Casos de Uso

### Caso 1: Mostrar Número en Listado de Pedidos
```php
foreach ($pedidos as $pedido) {
    echo $pedido->numero_pedido_mostrable;
    // Si es LOGO: LOGO-00001
    // Si es normal: 45451
}
```

### Caso 2: Buscar Pedido por Número
```php
// Para LOGO:
$pedido = PedidoProduccion::whereHas('logoPedidos', function($q) {
    $q->where('numero_pedido', 'LOGO-00001');
})->first();

// Para normal:
$pedido = PedidoProduccion::where('numero_pedido', '45451')->first();
```

### Caso 3: Obtener Detalles Completos del LOGO
```php
$pedido = PedidoProduccion::with('logoPedidos.imagenes')->find($id);

$logoPedido = $pedido->logoPedido();
echo $logoPedido->numero_pedido;      // LOGO-00001
echo $logoPedido->descripcion;        // Descripción
echo count($logoPedido->tecnicas);    // Cantidad de técnicas
echo count($logoPedido->ubicaciones); // Cantidad de ubicaciones
foreach ($logoPedido->imagenes as $img) {
    echo $img->url; // URL de la imagen
}
```

---

## 🔧 Configuración de Storage

Asegúrate de que:

```bash
# La carpeta sea accesible
/storage/logo_pedidos/
```

Está enlazada en `public/storage/`:
```bash
php artisan storage:link
```

---

## 📝 Notas Importantes

1. **LogoCotizacionId**: Se captura en JavaScript cuando se carga la cotización LOGO
2. **Número LOGO**: Se genera automáticamente en el servidor (LOGO-00001)
3. **Imágenes**: Se guardan SOLO en /storage/logo_pedidos/{logo_pedido_id}/
4. **Relaciones**: Un pedido puede tener UN LOGO, pero UN LOGO tiene MUCHAS imágenes

---

## ✨ Estado Actual

✅ **Tablas creadas** con estructura correcta
✅ **Modelos configurados** con relaciones
✅ **Controlador implementado** con validaciones
✅ **JavaScript actualizado** para capturar ID
✅ **Número mostrable implementado** automático
✅ **Almacenamiento de imágenes** funcional

🎉 **Sistema LOGO Pedidos completamente operativo**
