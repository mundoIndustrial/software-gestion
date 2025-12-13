# 📱 Implementación de Recibo Móvil en HTML

## Resumen
Se implementó un sistema dual de renderizado de recibos (facturas de costura):
- **Desktop (≥768px)**: HTML convertido a imagen con html2canvas
- **Mobile (<768px)**: HTML nativo directo (evita deformación)

## Cambios Realizados

### 1. Archivo: `resources/views/operario/ver-pedido.blade.php`

#### Estructura HTML (líneas 35-45)
Se dividió el contenedor original en dos versiones:

```html
<!-- Versión Desktop: HTML a Imagen -->
<div id="factura-container-desktop" class="factura-container" style="display: none;">
    <div id="factura-html" class="pedido-modal-html" style="position: absolute; left: -9999px; top: -9999px; width: 764px;">
        @include('components.orders-components.order-detail-modal')
    </div>
    <img id="factura-imagen" src="" alt="Factura" class="factura-img">
</div>

<!-- Versión Móvil: HTML directo -->
<div id="factura-container-mobile" style="display: none; width: 100%; display: flex; justify-content: center;">
    @include('components.orders-components.order-detail-modal-mobile')
</div>
```

#### JavaScript (líneas 896-942)
Se agregó lógica de detección de dispositivo:

```javascript
// Detectar si es móvil
function esMobile() {
    return window.innerWidth < 768;
}

// Generar imagen al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    llenarDatosModal();
    
    if (esMobile()) {
        // En móvil: mostrar HTML nativo, no convertir a imagen
        console.log('📱 Dispositivo móvil detectado - usando HTML nativo');
        const containerMobile = document.getElementById('factura-container-mobile');
        const containerDesktop = document.getElementById('factura-container-desktop');
        
        if (containerMobile) containerMobile.style.display = 'block';
        if (containerDesktop) containerDesktop.style.display = 'none';
        
        // Poblar datos en modal móvil
        const pedido = {
            fecha: '{{ $pedido['fecha_creacion'] ?? now()->format('Y-m-d') }}',
            asesora: '{{ $pedido['asesora'] ?? 'N/A' }}',
            formaPago: '{{ $pedido['forma_pago'] ?? 'N/A' }}',
            prenda: '{{ $pedido['descripcion'] ?? 'N/A' }}',
            cliente: '{{ $pedido['cliente'] }}',
            numeroPedido: '{{ $pedido['numero_pedido'] }}',
            encargado: '{{ auth()->user()->name ?? 'N/A' }}',
            prendasEntregadas: '{{ $pedido['cantidad'] ?? 0 }}/{{ $pedido['cantidad'] ?? 0 }}',
            descripcion: '{{ $pedido['descripcion'] ?? 'N/A' }}'
        };
        
        // Llenar la plantilla móvil
        if (window.llenarReciboCosturaMobile) {
            window.llenarReciboCosturaMobile(pedido);
        }
    } else {
        // En desktop: mostrar imagen generada
        console.log('🖥️ Dispositivo desktop detectado - generando imagen');
        const containerMobile = document.getElementById('factura-container-mobile');
        const containerDesktop = document.getElementById('factura-container-desktop');
        
        if (containerMobile) containerMobile.style.display = 'none';
        if (containerDesktop) containerDesktop.style.display = 'block';
        
        setTimeout(generarImagenFactura, 500);
    }
});
```

### 2. Archivo: `resources/views/components/orders-components/order-detail-modal-mobile.blade.php` (NUEVO)

Archivo nuevo que contiene la plantilla móvil con:

#### Estilos (líneas 1-130)
- Contenedor responsivo con ancho máximo 400px
- Bordes negros 3px
- Boxes de fecha con borde
- Fuentes y espaciados optimizados para móvil

#### HTML (líneas 131-177)
Estructura simplificada con 7 secciones:
1. **Header**: Logo, título "RECIBO DE COSTURA", fecha
2. **Info**: Asesora, forma de pago, prenda
3. **Descripción**: Campo de descripción
4. **Título**: "RECIBO DE COSTURA"
5. **Número Pedido**: Número con formato #XXXXX
6. **Cliente**: Nombre del cliente
7. **Footer**: Encargado y prendas entregadas

#### JavaScript (líneas 178-197)
Función global para poblar datos:

```javascript
window.llenarReciboCosturaMobile = function(data) {
    // Fecha
    if (data.fecha) {
        const fecha = new Date(data.fecha);
        document.getElementById('mobile-fecha-dia').textContent = fecha.getDate();
        document.getElementById('mobile-fecha-mes').textContent = fecha.getMonth() + 1;
        document.getElementById('mobile-fecha-year').textContent = fecha.getFullYear();
    }

    // Información básica
    document.getElementById('mobile-asesora').textContent = data.asesora || 'N/A';
    document.getElementById('mobile-forma-pago').textContent = data.formaPago || 'N/A';
    document.getElementById('mobile-prenda').textContent = data.prenda || 'N/A';
    document.getElementById('mobile-cliente').textContent = data.cliente || 'N/A';
    document.getElementById('mobile-numero-pedido').textContent = '#' + (data.numeroPedido || '');
    document.getElementById('mobile-encargado').textContent = data.encargado || '-';
    document.getElementById('mobile-prendas-entregadas').textContent = data.prendasEntregadas || '0/0';

    // Descripción
    const descripcionHTML = data.descripcion || '<em>Sin descripción</em>';
    document.getElementById('mobile-descripcion').innerHTML = descripcionHTML;
};
```

## Cómo Funciona

### 1. Carga de Página (DOMContentLoaded)
```
┌─────────────────────────────────────────┐
│ 1. Llenar datos en modal desktop        │
│    (orden original - llenarDatosModal)  │
└──────────────┬──────────────────────────┘
               │
        ┌──────▼──────┐
        │ ¿Es Mobile? │
        └──────┬──────┘
               │
        ┌──────┴──────────────┐
        │                     │
      SÍ                      NO
        │                     │
    ┌───▼──────────┐   ┌──────▼──────────┐
    │ Mostrar      │   │ Mostrar imagen  │
    │ HTML nativo  │   │ generada        │
    │ Poblar datos │   │ (html2canvas)   │
    └───────────────┘   └─────────────────┘
```

### 2. Rendering Desktop
1. Modal HTML oculto off-screen (left: -9999px)
2. html2canvas convierte a imagen (764px width)
3. Imagen se muestra en contenedor
4. Usuario puede hacer zoom/pan en contenedor

### 3. Rendering Mobile
1. Contenedor mobile muestra HTML directo
2. Función `llenarReciboCosturaMobile()` popula datos
3. Estilos responsive optimizados para móvil
4. No hay conversión a imagen → sin deformación
5. Pinch-zoom nativo del navegador funciona

## Ventajas de la Solución

### ✅ Sin Deformación en Mobile
- HTML nativo renderiza correctamente en cualquier tamaño
- No hay conversión html2canvas que cause distorsiones

### ✅ Mejor Rendimiento en Mobile
- No gasta recursos en conversión de imagen
- Menor consumo de memoria
- Carga más rápido

### ✅ Experiencia Nativa
- Zoom pinch nativo del navegador
- Puede seleccionar texto
- Mejor interactividad

### ✅ Mantenibilidad
- Código separado para desktop/mobile
- Fácil de modificar estilos según dispositivo
- Reutiliza misma fuente de datos

## Testing

### Para Probar en Desktop
1. Ir a `http://localhost:8000/operario/pedido/43881`
2. Ver recibo como imagen (html2canvas)
3. Probar zoom con Ctrl+wheel o pinch-zoom

### Para Probar en Mobile
1. Abrir DevTools (F12)
2. Activar Device Emulation (Ctrl+Shift+M)
3. Seleccionar dispositivo móvil
4. Refrescar página
5. Ver recibo como HTML nativo
6. Probar pinch-zoom

### Breakpoint
- **Desktop**: window.innerWidth >= 768px
- **Mobile**: window.innerWidth < 768px

## Archivos Modificados
- ✅ `resources/views/operario/ver-pedido.blade.php` - Lógica de detección + contenedores
- ✅ `resources/views/components/orders-components/order-detail-modal-mobile.blade.php` - NUEVO

## Próximos Pasos (Opcionales)
1. Agregar botón para cambiar entre vista mobile/desktop manualmente
2. Cachear HTML renderizado en mobile (similar a imagen en desktop)
3. Agregar exportación a PDF desde mobile
4. Optimizar estilos según feedback del usuario
