# Implementación de Recibos Dinámicos - Documentación

## Resumen

Se ha implementado un sistema de recibos dinámicos para los pedidos de producción. Cada pedido puede generar múltiples recibos:
- **1 Recibo de COSTURA** por prenda (o COSTURA-BODEGA si el origen es bodega)
- **1 Recibo por cada PROCESO** asociado a la prenda (Bordado, Estampado, etc.)

Los recibos navegan de forma lineal con flechas (Recibo 1/9, 2/9, etc.) y reutilizan los estilos del modal de orden existente.

## Componentes Implementados

### 1. Backend

#### Repository: `PedidoProduccionRepository::obtenerDatosRecibos()`
**Ubicación**: `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php`

Prepara los datos en formato específico para ReceiptManager:
```php
[
    'numero_pedido' => '#12345',
    'cliente' => 'Cliente XYZ',
    'asesora' => 'María García',
    'forma_de_pago' => 'Efectivo',
    'fecha' => '15/01/2026',
    'prendas' => [
        [
            'numero' => 1,
            'nombre' => 'Camisa',
            'origen' => 'confección',  // ← IMPORTANTE para titulos dinámicos
            'color' => 'Azul',
            'tela' => 'Algodón',
            'tallas' => ['S' => 5, 'M' => 10],
            'procesos' => [
                [
                    'nombre' => 'Bordado',
                    'ubicaciones' => ['Pecho', 'Espalda'],
                    'observaciones' => 'Con logo'
                ]
            ]
        ]
    ]
]
```

#### Controlador: `AsesoresController::obtenerDatosRecibos()`
**Ubicación**: `app/Http/Controllers/AsesoresController.php`

Endpoint que obtiene los datos autenticando al usuario:
- GET `/asesores/pedidos/{id}/recibos-datos`

### 2. Frontend JavaScript

#### `receipt-manager.js`
**Ubicación**: `public/js/asesores/receipt-manager.js`

Clase `ReceiptManager` que:
- Genera array lineal de recibos combinando COSTURA + procesos
- Navega entre recibos con prev/next
- Renderiza dinámicamente el titulo y contenido
- Detecta origen de prenda para títulos (COSTURA vs COSTURA-BODEGA)

```javascript
// Instanciación
window.receiptManager = new ReceiptManager(datosDelServidor);

// Navegación
receiptManager.navegar('siguiente');
receiptManager.navegar('anterior');

// Cerrar
receiptManager.cerrar();
```

#### `invoice-from-list.js`
**Ubicación**: `public/js/asesores/invoice-from-list.js`

Funciones principales:

- `verRecibosDelPedido(numeroPedido, pedidoId)` - Obtiene datos del servidor y abre modal
- `crearModalRecibosDesdeListaPedidos(datos)` - Crea el modal con HTML inyectado
- `cargarReceiptManager(callback)` - Carga dinámicamente el script receipt-manager.js

#### `pedidos-dropdown-simple.js`
**Ubicación**: `public/js/asesores/pedidos-dropdown-simple.js`

Agregado botón "Ver Recibos" en el dropdown del botón "Ver" de cada fila de pedidos.

### 3. Rutas

**Ubicación**: `routes/web.php`

```php
Route::get('/pedidos/{id}/recibos-datos', [AsesoresController::class, 'obtenerDatosRecibos'])
    ->where('id', '[0-9]+')
    ->name('pedidos.recibos-datos');
```

## Flujo de Uso

1. **Usuario hace clic en "Ver Recibos"** en el dropdown de opciones de un pedido
2. **JavaScript llama a `verRecibosDelPedido()`** que:
   - Muestra spinner de carga
   - Hace fetch a `/asesores/pedidos/{id}/recibos-datos`
   - Obtiene datos en formato JSON
3. **Se crea el modal** con HTML del componente receipt-dynamic
4. **Se carga `receipt-manager.js`** e inicializa ReceiptManager
5. **Usuario ve recibos** con navegación prev/next
6. **Puede imprimir** cada recibo con el botón print

## Características

### Títulos Dinámicos

Los títulos se generan automáticamente basados en:

```javascript
// Para recibos de COSTURA (procesoIndex === null)
if (prenda.origen.toLowerCase() === 'bodega') {
    titulo = "RECIBO DE COSTURA-BODEGA"
} else {
    titulo = "RECIBO DE COSTURA"
}

// Para recibos de procesos
titulo = `RECIBO DE ${proceso.nombre.toUpperCase()}`
// Ej: "RECIBO DE BORDADO", "RECIBO DE ESTAMPADO"
```

### Contenido Contextual

Cada tipo de recibo muestra diferente información:

**Recibo de COSTURA:**
- Nombre de prenda
- Color
- Tela
- Origen (Confección/Bodega)
- Tallas y cantidades

**Recibo de PROCESO:**
- Nombre del proceso
- Nombre de la prenda
- Observaciones
- Ubicaciones (pecho, espalda, etc.)
- Referencia a imágenes

### Navegación Lineal

Los recibos están numerados secuencialmente:
- Recibo 1/9 (Primera COSTURA)
- Recibo 2/9 (Primer proceso de la prenda 1)
- Recibo 3/9 (Segundo proceso de la prenda 1)
- etc.

Las flechas solo aparecen cuando hay más recibos disponibles.

## Estilos

Se reutilizan completamente los estilos del modal de órdenes existente:
- `order-detail-modal.css` - Estilos generales del modal
- Grid de fecha, información del cliente
- Separadores y tipografía consistente

## Debugging

Abrir consola del navegador (F12) para ver logs:

```
📋 [RECEIPT MANAGER] Inicializado
📊 Total de recibos: 9
📄 Recibos: Array(9)
📄 Renderizando recibo 1/9: {...}
```

## Testing Manual

1. Navegar a Asesores > Pedidos
2. Hacer clic en botón "Ver" (ojo azul) de un pedido
3. Seleccionar "Ver Recibos" del dropdown
4. Verificar que se abra modal con recibos
5. Probar navegación con flechas
6. Probar botón Imprimir
7. Probar botón Cerrar

## Notas Técnicas

### Campo `origen` en prendas

Es crítico que el repository incluya el campo `origen` en cada prenda para que se generen títulos correctos:

```php
'prendas' => [
    [
        'origen' => $prenda->origen ?? 'confección',  // ← IMPORTANTE
        // ... otros campos
    ]
]
```

### Carga Dinámica de Scripts

`receipt-manager.js` se carga dinámicamente solo cuando se abre un modal de recibos, no en cada carga de página.

### Modal Overlay

El modal utiliza un overlay de semi-transparencia que se puede cerrar haciendo clic fuera del contenido.

## Mejoras Futuras

- [ ] Agregar vista previa de imágenes del proceso en modal ampliado
- [ ] Exportar recibos a PDF individual
- [ ] Agregar marcas de agua con estado del recibo
- [ ] Historial de recibos impresos
- [ ] QR con información del recibo

## Archivos Modificados

- ✅ `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php` - Agregado método `obtenerDatosRecibos()`
- ✅ `app/Http/Controllers/AsesoresController.php` - Agregado método `obtenerDatosRecibos()`
- ✅ `routes/web.php` - Agregada ruta `/pedidos/{id}/recibos-datos`
- ✅ `public/js/asesores/invoice-from-list.js` - Agregadas funciones de recibos
- ✅ `public/js/asesores/pedidos-dropdown-simple.js` - Agregado botón en dropdown
- ✅ `public/js/asesores/receipt-manager.js` - NUEVO archivo con clase ReceiptManager

## Archivos Existentes (Sin cambios)

- `resources/views/components/orders-components/receipt-dynamic.blade.php` - Template referencia
- `public/css/order-detail-modal.css` - Estilos reutilizados
- `resources/views/asesores/pedidos/index.blade.php` - Sin cambios necesarios
