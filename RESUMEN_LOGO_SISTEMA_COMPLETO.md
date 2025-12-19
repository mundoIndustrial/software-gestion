# 🎨 SISTEMA LOGO PEDIDOS - RESUMEN COMPLETO

## ✨ Implementación Finalizada

El sistema LOGO está **100% operativo** con la siguiente funcionalidad:

---

## 📋 TABLAS DE BASE DE DATOS

### 1. pedidos_produccion
```
id          | Identificador único
numero_pedido | NULL para LOGO, ej: 45451 para normales
pedido_id   | (opcional, para relación inversa)
cliente_id  | Cliente
asesor_id   | Asesor creador
estado      | Pendiente, En Ejecución, etc.
...otros campos
```

### 2. logo_pedidos ⭐
```
id                   | Identificador único
pedido_id            | FK → pedidos_produccion
logo_cotizacion_id   | FK → logo_cotizaciones
numero_pedido        | LOGO-00001, LOGO-00002, etc.
descripcion          | Descripción del LOGO
tecnicas             | JSON array de técnicas
ubicaciones          | JSON array con ubicaciones/observaciones
observaciones_tecnices | Observaciones generales
timestamps           | created_at, updated_at
```

### 3. logo_pedido_imagenes
```
id                | Identificador único
logo_pedido_id    | FK → logo_pedidos
nombre_archivo    | Nombre de archivo
url               | URL pública
ruta_original     | Ruta en /storage/
ruta_webp         | Versión optimizada
tipo_archivo      | MIME type
tamaño_archivo    | Bytes
orden             | Orden en galería
timestamps        | created_at, updated_at
```

---

## 🔄 FLUJO DE CREACIÓN COMPLETO

### Paso 1: Usuario crea Pedido LOGO desde Cotización
```javascript
// JavaScript captura LogoCotizationId
logoCotizacionId = data.logo.id;  // Ej: 1

// Valida que haya datos
esLogo = (logoTecnicas.length > 0 || logoUbicaciones.length > 0);

// POST /asesores/pedidos-produccion/crear-desde-cotizacion
{
  cotizacion_id: 1,
  forma_de_pago: "Crédito",
  prendas: []  // ← VACÍO para LOGO
}

// Respuesta: { pedido_id: 11384 }
```

### Paso 2: Controller valida que es LOGO
```php
$dto = CrearPedidoProduccionDTO::fromRequest([...]);

if ($dto->esLogoPedido()) {
    // NO asignar número en pedidos_produccion
    $numeroPedido = null;
}
```

### Paso 3: Crear PedidoProduccion (SIN número)
```
INSERT INTO pedidos_produccion (
    cotizacion_id, cliente_id, asesor_id,
    numero_pedido: NULL,  ← Sin número
    ...
) VALUES (...)
```

### Paso 4: Guardar datos LOGO específicos
```javascript
POST /asesores/pedidos/guardar-logo-pedido
{
    pedido_id: 11384,
    logo_cotizacion_id: 1,
    descripcion: "...",
    tecnicas: [...],
    ubicaciones: [...],
    fotos: [...]
}
```

### Paso 5: Controlador crea LogoPedido
```php
// Genera número automático
$numero = LogoPedido::generarNumeroPedido();  // LOGO-00001

LogoPedido::create([
    pedido_id: 11384,
    logo_cotizacion_id: 1,
    numero_pedido: "LOGO-00001",
    descripcion: "...",
    tecnicas: JSON,
    ubicaciones: JSON,
]);
```

### Paso 6: Guardar imágenes
```php
// Por cada imagen:
if (existente) {
    // Solo crear referencia
} else {
    // Guardar en /storage/logo_pedidos/{logo_pedido_id}/
    // Crear referencia en BD
}
```

---

## 📊 LISTADO DE PEDIDOS

### Vista: /asesores/pedidos

#### Filtros Rápidos:
- 🏠 **Todos** - Muestra todos los pedidos
- 🎨 **Logo** - ← NUEVO: Solo pedidos LOGO
- ⏱️ **Pendientes** - Estado Pendiente
- 🔧 **En Producción** - En ejecución
- ✅ **Entregados** - Completados
- ❌ **Anulados** - Cancelados

#### Número Mostrado:
```
Si es LOGO:   #LOGO-00001  (de logo_pedidos.numero_pedido)
Si es Normal: #45451       (de pedidos_produccion.numero_pedido)
```

---

## 🎯 MÉTODOS DISPONIBLES

### En PedidoProduccion:
```php
$pedido->logoPedidos()              // Relación HasMany
$pedido->logoPedido()               // Primer LogoPedido
$pedido->esLogo()                   // Boolean
$pedido->getNumeroPedidoMostrable() // String número correcto
$pedido->numero_pedido_mostrable    // Accessor (JSON)
```

### En LogoPedido:
```php
$logo->pedidoProduccion()           // Relación BelongsTo
$logo->logoCotizacion()             // FK a cotización
$logo->imagenes()                   // Relación HasMany
LogoPedido::generarNumeroPedido()   // Genera LOGO-00001
```

---

## 💾 ALMACENAMIENTO DE ARCHIVOS

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
```

### URL Pública:
```
/storage/logo_pedidos/1/logo_1_1702844400_1234.jpg
```

---

## ✅ VALIDACIONES IMPLEMENTADAS

### En Controller:
- ✅ `pedido_id` existe en `pedidos_produccion`
- ✅ `logo_cotizacion_id` existe en `logo_cotizaciones`
- ✅ Arrays válidos: tecnicas, ubicaciones, fotos
- ✅ Imágenes con datos completos

### En DTO:
- ✅ `esLogoPedido()` - Sin prendas, con logo

### En Job:
- ✅ NO incrementa secuencia para LOGO
- ✅ NULL numero_pedido para LOGO

---

## 🔍 CASOS DE USO

### Caso 1: Listar SOLO pedidos LOGO
```php
$logoPedidos = PedidoProduccion::whereHas('logoPedidos')->get();

foreach ($logoPedidos as $pedido) {
    echo $pedido->numero_pedido_mostrable;  // LOGO-00001
}
```

### Caso 2: Obtener detalles completos
```php
$pedido = PedidoProduccion::with('logoPedidos.imagenes')->find(1);
$logo = $pedido->logoPedido();

echo $logo->numero_pedido;      // LOGO-00001
echo $logo->descripcion;        // Descripción
echo count($logo->tecnicas);    // Cantidad técnicas
echo count($logo->ubicaciones); // Cantidad ubicaciones

foreach ($logo->imagenes as $img) {
    echo $img->url;  // URL de imagen
}
```

### Caso 3: Buscar por número LOGO
```php
$pedido = PedidoProduccion::whereHas('logoPedidos', function($q) {
    $q->where('numero_pedido', 'LOGO-00001');
})->first();
```

---

## 🚀 ESTADO ACTUAL

| Componente | Estado | Detalles |
|-----------|--------|----------|
| Base de Datos | ✅ | 3 tablas creadas y funcionales |
| Modelos | ✅ | LogoPedido, LogoPedidoImagen con relaciones |
| Controller | ✅ | Guardado y listado operativos |
| Rutas | ✅ | POST guardar-logo-pedido registrada |
| JavaScript | ✅ | Captura y envío de datos correcto |
| Filtro LOGO | ✅ | Nuevo filtro en listado |
| Número Mostrable | ✅ | Muestra correcto según tipo |
| Imágenes | ✅ | Almacenamiento en /storage/ funcional |

---

## 📝 NOTAS IMPORTANTES

1. **LogoCotizacionId**: Se captura en el navegador cuando carga la cotización LOGO
2. **Número LOGO**: Se genera automáticamente en servidor (LOGO-00001)
3. **Imágenes**: Se guardan SOLO en `/storage/logo_pedidos/{logo_pedido_id}/`
4. **Relación**: Un pedido puede tener UN LOGO, pero UN LOGO tiene MUCHAS imágenes
5. **Número Pedido**: NULL en `pedidos_produccion` para LOGO, secuencia en `logo_pedidos`

---

## 🎉 LISTO PARA PRODUCCIÓN

El sistema está completamente implementado y probado.

**Próximos pasos opcionales:**
- Detalle de pedido LOGO con galería de imágenes
- Edición de pedido LOGO
- Reporte/exportación de pedidos LOGO
- Integración con flujo de producción
