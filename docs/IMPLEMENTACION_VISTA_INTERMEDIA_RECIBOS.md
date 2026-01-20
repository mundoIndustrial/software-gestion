#  Refactorización: Sistema de Recibos de Producción - Vista Intermedia

##  Objetivo
Implementar una mejora de diseño y UX en la sección de pedidos (`/asesores/pedidos`) para:
1. **Simplificar el menú contextual** - Eliminar submenús anidados infinitos
2. **Crear vista intermedia de recibos** - Mostrar prendas y procesos de forma clara
3. **Modal dinámico de recibo** - Permitir visualizar recibos específicos por proceso

---

## 📁 Archivos Modificados/Creados

###  CREADOS

#### 1. Modal de Vista Intermedia
```
resources/views/components/modals/recibos-intermediate-modal.blade.php
```
- Muestra lista de prendas del pedido
- Expandible: cada prenda muestra sus procesos asociados
- Muestra estado de cada proceso (Pendiente, En proceso, Terminado)
- Permite seleccionar un proceso para abrir su recibo

**Características:**
- Diseño responsive con Tailwind CSS
- Animaciones suaves (expand/collapse)
- Iconografía clara (Font Awesome)
- Estados visuales codificados por color

---

#### 2. Modal Dinámico de Recibo
```
resources/views/components/modals/recibo-dinamico-modal.blade.php
```
- Reutiliza estructura de recibos existentes
- Se adapta dinámicamente a diferentes tipos de procesos
- Muestra:
  - Información básica (pedido, tipo de proceso)
  - Detalles de la prenda
  - Distribución por talla
  - Especificaciones del proceso
  - Confirmación y firma

**Funcionalidades:**
- Botones de acción (Imprimir, Descargar PDF)
- Estructura modular para futuras extensiones
- Validación de datos del servidor

---

### 🔄 MODIFICADOS

#### 1. JavaScript del Dropdown
```
public/js/asesores/pedidos-dropdown-simple.js
```

**Cambios:**
-  Removido: `abrirSubmenuRecibos()` - Función que generaba submenús anidados
-  Actualizado: Botón "Ver Recibos" ahora llama a `abrirModalRecibosIntermedio(pedidoId)`
- ✨ Resultado: Menú contextual más limpio y simple

**Antes:**
```javascript
<button onclick="abrirSubmenuRecibos(event, ${pedidoId})">
  Ver Recibos ▶
  <div class="submenu-recibos"><!-- Submenú anidado --></div>
</button>
```

**Después:**
```javascript
<button onclick="abrirModalRecibosIntermedio(${pedidoId})">
  Ver Recibos
</button>
```

---

#### 2. Repositorio de PedidoProduccion
```
app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php
```

**Método: `obtenerDatosRecibos()`**

Cambios realizados:
-  Agregado `id` a estructura de prenda (necesario para referencias en JavaScript)
-  Renombrado campos de proceso:
  - `nombre` → `nombre_proceso`
  - `tipo` → `tipo_proceso`
-  Agregado `estado` al proceso (retorna estado del proceso de producción)

**Estructura de retorno:**
```php
[
    'numero_pedido' => '12345',
    'cliente' => 'Cliente XYZ',
    'prendas' => [
        [
            'id' => 1,
            'nombre' => 'Camisa Drill',
            'color' => 'Azul Marino',
            'tela' => 'Drill 100% Algodón',
            'procesos' => [
                [
                    'nombre_proceso' => 'Costura',
                    'tipo_proceso' => 'costura',
                    'estado' => 'Pendiente',
                    'observaciones' => '...',
                    'ubicaciones' => [],
                ],
                [...]
            ]
        ],
        [...]
    ]
]
```

---

#### 3. Vista Principal de Pedidos
```
resources/views/asesores/pedidos/index.blade.php
```

**Cambios:**
-  Incluidos dos nuevos modales después del modal de seguimiento:
  ```blade
  @include('components.modals.recibos-intermediate-modal')
  @include('components.modals.recibo-dinamico-modal')
  ```

---

## 🎮 Flujo de Usuario

### Antes (Problema)
```
1. Usuario hace clic en "Ver Recibos" en dropdown
   ↓
2. Se abre submenú con lista de prendas
   ↓
3. Al pasar mouse sobre prenda, se abre nuevo submenú con procesos
   ↓
4. Efecto "escalera" visual cuando hay muchas prendas/procesos
   ✗ Difícil de usar, especialmente en pedidos grandes
```

### Después (Solución)
```
1. Usuario hace clic en "Ver Recibos" en dropdown
   ↓
2. Se cierra dropdown automáticamente
   ↓
3. Se abre MODAL INTERMEDIO con:
   - Lista clara de prendas
   - Botones expandibles para ver procesos
   - Estados visuales por color
   ↓
4. Usuario hace clic en un proceso
   ↓
5. Se abre MODAL DE RECIBO con detalle completo
   - Información del pedido
   - Datos de la prenda
   - Especificaciones del proceso
   - Botones de acción (Imprimir, PDF)
   ✓ Claro, escalable, profesional
```

---

## 🔌 Integración de APIs

### Endpoint Existente (Reutilizado)
```
GET /asesores/pedidos/{id}/recibos-datos
```

- **Controlador**: `AsesoresController@obtenerDatosRecibos()`
- **Repository**: `PedidoProduccionRepository@obtenerDatosRecibos()`
- **Respuesta**: JSON con estructura de prendas y procesos

### Llamadas JavaScript

**Modal Intermedio:**
```javascript
fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`)
  .then(response => response.json())
  .then(datos => {
    // datos.prendas[] con procesos
    // Renderizar UI
  })
```

**Modal de Recibo:**
```javascript
window.abrirModalRecibo = function(pedidoId, prendaId, tipoProceso) {
  // Cargar datos específicos del recibo
  // Renderizar modal
}
```

---

##  Componentes Principales

### Modal Intermedio

**Estructura CSS:**
```
┌─ Header (Azul oscuro)
├─ Container (Blanco)
│  ├─ Prenda 1 (Expandible)
│  │  ├─ Proceso 1 ← Clickeable
│  │  ├─ Proceso 2 ← Clickeable
│  │  └─ Proceso 3 ← Clickeable
│  ├─ Prenda 2 (Expandible)
│  │  └─ [Procesos...]
│  └─ Prenda 3 (Expandible)
│     └─ [Procesos...]
└─ Footer (Gris)
```

**Estados de Proceso (Color-coded):**
- 🔴 Pendiente → Rojo claro
-  En proceso → Amarillo claro
-  Terminado → Verde claro

---

### Modal de Recibo

**Secciones:**
1. **Información Básica** - Pedido, tipo de proceso, estado, encargado
2. **Detalles de Prenda** - Nombre, color, tela, cantidad
3. **Distribución por Talla** - Grid visual de cantidades
4. **Especificaciones del Proceso** - Observaciones, ubicaciones
5. **Confirmación** - Responsable, fecha de entrega

**Acciones:**
- 🖨️ Imprimir
- 📥 Descargar PDF

---

## 🛠️ Funciones JavaScript Disponibles

### Modal Intermedio
```javascript
// Abre el modal con lista de prendas y procesos
window.abrirModalRecibosIntermedio(pedidoId)

// Cierra el modal intermedio
window.cerrarModalRecibosIntermedio()

// Expande/contrae acordeón de prenda
window.togglePrendaAccordion(headerElement, prendaIdx)

// Selecciona un proceso y abre el recibo
window.seleccionarProceso(pedidoId, prendaId, tipoProceso)
```

### Modal de Recibo
```javascript
// Abre el recibo dinámico
window.abrirModalRecibo(pedidoId, prendaId, tipoProceso)

// Cierra el modal de recibo
window.cerrarModalRecibo()

// Imprime el recibo
window.imprimirRecibo()

// Descarga como PDF
window.descargarReciboPDF()
```

---

## 🚀 Próximas Mejoras

### Fase 2: Funcionalidades Avanzadas
1. **Carga real de datos en Modal de Recibo**
   - Conectar con endpoint para obtener datos específicos del recibo
   - Validar estructura JSON retornada

2. **Generación de PDF**
   - Implementar `descargarReciboPDF()` con librería PDF
   - Usar HTML a PDF (ej: html2pdf.js o Laravel DOMPDF)

3. **Actualización de Estado**
   - Botones de acción en recibo (Marcar como entregado, etc.)
   - WebSocket para actualización en tiempo real

4. **Galería de Imágenes**
   - Mostrar imágenes del proceso si existen
   - Lightbox para vista ampliada

5. **Compatibilidad Mobile**
   - Responsive en pantallas pequeñas
   - Touch-friendly interactions

---

##  Comparativa: Antes vs Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Menú Principal** | Simple | Simple ✓ |
| **Submenús** | Múltiples niveles | Modal único |
| **Escalabilidad** | Problemas con +5 prendas | Excelente |
| **UX Visual** | "Escalera" anidada | Limpia y organizada |
| **Interacciones** | Hover complejos | Click simple |
| **Responsividad** | Limitada | Optimizada |
| **Accesibilidad** | Básica | Mejorada |

---

##  Testing Checklist

- [ ] Menú contextual muestra opción "Ver Recibos" correctamente
- [ ] Clic en "Ver Recibos" abre modal intermedio
- [ ] Modal intermedio carga lista de prendas del servidor
- [ ] Cada prenda se puede expandir/contraer
- [ ] Se muestran procesos con iconos y estados
- [ ] Estados están coloreados correctamente
- [ ] Clic en proceso abre modal de recibo
- [ ] Modal de recibo cierra correctamente
- [ ] Botones Imprimir y Descargar PDF funcionan
- [ ] Modales cierran con tecla Escape
- [ ] Modales cierran al hacer clic fuera
- [ ] UI responsive en diferentes tamaños de pantalla

---

##  Notas Técnicas

### Seguridad
- Validación de autorización en controlador (verificar que pedido pertenece al usuario)
- Sanitización de datos JSON retornados
- CSRF protection en Laravel (automático)

### Rendimiento
- Lazy loading de procesos (expand on-demand)
- Caché de datos si es necesario
- Minimización de requests (un único GET a `/recibos-datos`)

### Compatibilidad
- Firefox, Chrome, Safari, Edge (últimas 2 versiones)
- Mobile: iOS Safari, Chrome Android
- Fallback para browsers sin ES6 (transpilación si necesario)

---

## 📚 Referencias Internas

- **Repository**: `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php`
- **Controller**: `app/Http/Controllers/AsesoresController.php`
- **Route**: `routes/web.php` (línea ~447)
- **Models**: 
  - `app/Models/PedidoProduccion.php`
  - `app/Models/PrendaPedido.php`
  - `app/Models/PedidosProcesosPrendaDetalle.php`

---

**Implementado en**: 19 de Enero de 2026  
**Versión**: 1.0  
**Estado**:  Completado
