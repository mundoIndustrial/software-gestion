#  Plan: Recibos de Costura Dinámicos por Prenda y Proceso

## 🎯 Objetivo

Implementar un sistema de recibos donde **cada recibo corresponde a un proceso específico**:

### Estructura de Recibos

**Para cada PRENDA:**
1. **Recibo 1**: COSTURA o COSTURA-BODEGA 
   - Si origen = "confección": "RECIBO DE COSTURA" (se envía a confeccionar)
   - Si origen = "bodega": "RECIBO DE COSTURA-BODEGA" (va de bodega)
2. **Recibo 2**: BORDADO (si la prenda tiene proceso bordado)
3. **Recibo 3**: ESTAMPADO (si la prenda tiene proceso estampado)
4. **Recibo N**: Otros procesos (reflectivo, sublimado, etc.)

### Ejemplo Real

```
PEDIDO #45703 - TRYTR
│
├─ PRENDA 1: CAMISETA (origen: confección)
│  ├─ Recibo 1/4: COSTURA
│  ├─ Recibo 2/4: BORDADO
│  ├─ Recibo 3/4: ESTAMPADO
│  └─ Recibo 4/4: REFLECTIVO
│
├─ PRENDA 2: PANTALÓN (origen: bodega)
│  ├─ Recibo 1/2: COSTURA-BODEGA
│  └─ Recibo 2/2: REFLECTIVO
│
└─ PRENDA 3: POLO (origen: confección)
   └─ Recibo 1/1: COSTURA
```

**Nota sobre Títulos de Recibo:**
- Para COSTURA (confección): "RECIBO DE COSTURA"
- Para COSTURA (bodega): "RECIBO DE COSTURA-BODEGA"
- Para BORDADO: "RECIBO DE BORDADO"
- Para ESTAMPADO: "RECIBO DE ESTAMPADO"
- Para REFLECTIVO: "RECIBO DE REFLECTIVO"
- Etc. (para cada tipo de proceso)

---

## 🏗️ Arquitectura Propuesta

### 1. **Estructura de Navegación**

```
NAVEGACIÓN LINEAL DE TODOS LOS RECIBOS

Recibo 1/9: COSTURA (Prenda 1)
├─ [← ANTERIOR RECIBO] [Recibo 1 de 9] [SIGUIENTE RECIBO →]
├─ Título: RECIBO DE COSTURA
├─ Subtítulo: PRENDA 1: CAMISETA
└─ Datos: Color, Tela, Talla, Cantidad

Recibo 2/9: BORDADO (Prenda 1)
├─ [← ANTERIOR RECIBO] [Recibo 2 de 9] [SIGUIENTE RECIBO →]
├─ Título: RECIBO DE BORDADO
├─ Subtítulo: PRENDA 1: CAMISETA
└─ Datos: Especificaciones de bordado, imágenes

Recibo 3/9: ESTAMPADO (Prenda 1)
├─ ...

... y así para todos los procesos de todas las prendas
```

### 2. **Componentes a Crear/Modificar**

#### A. Componente Blade: `receipt-dynamic.blade.php`

```php
<!-- Nuevo componente para recibos dinámicos -->
<div class="receipt-container">
    <!-- Header con navegación de recibos -->
    <div class="receipt-header">
        <h2 id="receipt-title">RECIBO DE COSTURA</h2>
        <p id="receipt-subtitle">PRENDA 1: CAMISETA</p>
        <div class="receipt-navigation">
            <button id="prev-receipt">← RECIBO ANTERIOR</button>
            <span id="receipt-counter">Recibo 1 de 9</span>
            <button id="next-receipt">RECIBO SIGUIENTE →</button>
        </div>
    </div>

    <!-- Contenido del recibo (reutilizar CSS de order-detail-modal.blade.php) -->
    <div class="receipt-content">
        <!-- Logo -->
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="order-logo">
        
        <!-- Fecha -->
        <div class="order-date">...</div>
        
        <!-- Información básica -->
        <div class="order-asesora">...</div>
        <div class="order-forma-pago">...</div>
        <div class="order-cliente">...</div>
        
        <!-- Descripción del proceso/prenda -->
        <div class="order-descripcion">
            <div id="process-description"></div>
        </div>
        
        <!-- Pie -->
        <div class="signature-section">...</div>
    </div>
</div>
```

#### B. Gestor JavaScript: `receipt-manager.js`

Funciones principales:
- `agregarRecibo(prenda, proceso)` - Agregar recibo a la lista
- `navegarRecibo(direccion)` - Navegar anterior/siguiente
- `generarTituloRecibo()` - Generar título según proceso Y origen
- `generarContenidoRecibo()` - Generar contenido según tipo
- `imprimirRecibo()` - Imprimir recibo actual

**Regla para Título de COSTURA:**
```
Si es primer recibo de prenda (procesoIndex = null):
  - Si origen === 'bodega': "RECIBO DE COSTURA-BODEGA"
  - Si origen === 'confección': "RECIBO DE COSTURA"
```

#### C. Integración en Factura: `invoice-preview-live.js`

Agregar botón "Ver Recibos" que:
- Abre modal con recibos dinámicos
- Muestra selector de prenda
- Muestra tabs de procesos

---

## 📊 Estructura de Datos

### Formato de Datos para Recibos

```javascript
// Array de recibos generado desde los datos de prendas y procesos
const recibos = [
    {
        numero: 1,
        total: 9,
        prendaIndex: 0,
        procesoIndex: null,  // null = COSTURA (sin proceso específico)
        prenda: {
            id: 1,
            numero: 1,
            nombre: "CAMISETA",
            origen: "confección",  // ← IMPORTANTE: determina tipo de costura
            color: "Azul",
            tela: "Algodón 100%",
            cantidad_talla: { S: 10, M: 20, L: 15 },
            descripcion: "..."
        },
        proceso: null,  // Para recibo de costura
        // Título generado dinámicamente según origen:
        titulo: "RECIBO DE COSTURA",  // Porque origen = "confección"
        subtitulo: "PRENDA 1: CAMISETA"
    },
    
    {
        numero: 2,
        total: 9,
        prendaIndex: 0,
        procesoIndex: 0,  // Primer proceso
        prenda: {
            id: 1,
            numero: 1,
            nombre: "CAMISETA",
            origen: "confección",
            color: "Azul",
            tela: "Algodón 100%",
            cantidad_talla: { S: 10, M: 20, L: 15 }
        },
        proceso: {
            tipo: "bordado",
            nombre: "BORDADO",
            especificaciones: [...],
            imagenes: [...]
        },
        titulo: "RECIBO DE BORDADO",
        subtitulo: "PRENDA 1: CAMISETA"
    },
    
    // Ejemplo de prenda de bodega:
    {
        numero: 5,
        total: 9,
        prendaIndex: 1,
        procesoIndex: null,  // null = COSTURA
        prenda: {
            id: 2,
            numero: 2,
            nombre: "PANTALÓN",
            origen: "bodega",  // ← Prenda de bodega
            color: "Negro",
            tela: "Drill",
            cantidad_talla: { 28: 5, 30: 10, 32: 8 }
        },
        proceso: null,
        // Título generado dinámicamente según origen:
        titulo: "RECIBO DE COSTURA-BODEGA",  // Porque origen = "bodega"
        subtitulo: "PRENDA 2: PANTALÓN"
    },
    
    // ... más recibos
];
```

---

## 🎨 Diseño Visual

### Recibo Base (reutilizar actual)

```
┌─────────────────────────────────────┐
│  LOGO MUNDO INDUSTRIAL              │
│                           [FECHA]   │
│                                     │
│  ASESORA: Juan Pérez               │
│  FORMA DE PAGO: Crédito a 30 días  │
│  CLIENTE: TRYTR                     │
│                                     │
│  RECIBO DE BORDADO                  │
│  PRENDA 1: CAMISETA                 │
│                                     │
│  DESCRIPCIÓN:                       │
│  [Detalles específicos del bordado] │
│  - Ubicación, diseño, cantidad      │
│  - Imágenes del bordado             │
│                                     │
│  PEDIDO #45703                      │
├─────────────────────────────────────┤
│  ENCARGADO: _____  ENTREGADAS: ____ │
└─────────────────────────────────────┘
```

### Con Navegación de Recibos

```
[← RECIBO ANTERIOR] [Recibo 2 de 9: BORDADO] [SIGUIENTE RECIBO →]

┌─────────────────────────────────────┐
│         RECIBO DE BORDADO           │
│         PRENDA 1: CAMISETA          │
│                                     │
│  [Contenido específico del bordado] │
│                                     │
│  PEDIDO #45703                      │
├─────────────────────────────────────┤
│  ENCARGADO: _____ ENTREGADAS: _____ │
└─────────────────────────────────────┘
```

---

## 🔄 Flujo de Navegación

### 1. Usuario abre factura de pedido
```
Factura (invoice-preview-live.js)
    │
    ├─ [BOTÓN]  Ver Recibos de Procesos
    │
    └─ ABRE: Modal/Vista con recibos dinámicos
```

### 2. Usuario en vista de recibos
```
[← ANTERIOR] [Recibo 1 de 9: COSTURA] [SIGUIENTE →]

Recibo actual: COSTURA de PRENDA 1 (CAMISETA)
├─ Datos: Color, Tela, Talla, Cantidad
├─ Botón: IMPRIMIR
└─ Botón: CERRAR

Clic en [SIGUIENTE]:
├─ Avanza a Recibo 2 de 9: BORDADO
├─ Actualiza título a "RECIBO DE BORDADO"
├─ Muestra datos del bordado (especificaciones, imágenes)
└─ Y así sucesivamente

Clic en [ANTERIOR]:
├─ Retrocede al recibo anterior
├─ Actualiza todo dinámicamente
└─ Si está en recibo 1, el botón se desactiva
```

### 3. Imprimir
```
Usuario en cualquier recibo
  ↓
Clic en "IMPRIMIR"
  ↓
Se imprime recibo actual
  ↓
Usuario puede continuar navegando
```

---

## 🛠️ Implementación por Fases

### **FASE 1: Crear Componente Base**
-  Crear `receipt-dynamic.blade.php`
-  Crear `receipt-manager.js` (gestor de recibos)
-  Crear `receipt-dynamic.css` (basado en order-detail-modal.css)
-  Crear función para generar array de recibos desde datos del pedido

### **FASE 2: Lógica de Navegación**
-  Implementar navegación anterior/siguiente entre recibos
-  Actualizar dinámicamente título, subtítulo y contenido
-  Mostrar contador "Recibo X de Y"
-  Desactivar botón anterior en primer recibo
-  Desactivar botón siguiente en último recibo

### **FASE 3: Generación de Contenido**
-  Para COSTURA: mostrar datos de prenda (color, tela, talla, cantidad)
-  Para PROCESOS: mostrar especificaciones del proceso + imágenes
-  Cambiar título según tipo de recibo
-  Formatear descripción según tipo

### **FASE 4: Integración con Factura**
-  Agregar botón en `invoice-preview-live.js`
-  Pasar datos desde factura a recibos
-  Abrir modal/vista con recibos
-  Implementar función de impresión

### **FASE 5: Refinamiento**
-  Mejorar estilos
-  Testing con pedidos reales
-  Ajustes de responsive design

---

## 📝 Cambios Necesarios en Archivos Existentes

### 1. `invoice-preview-live.js` (línea ~1350)
Agregar botón "Ver Recibos":
```javascript
// Después del botón de imprimir, agregar:
<button onclick="abrirRecibosModal(${JSON.stringify(datos)})">
     Ver Recibos de Procesos
</button>
```

### 2. `PedidoProduccionRepository.php`
Ya tiene la estructura necesaria en método `obtenerParaFactura()`:
-  Carga prendas con procesos
-  Incluye especificaciones
-  Incluye imágenes

### 3. Controlador (si existe endpoint para recibos)
Crear endpoint opcional:
```php
GET /api/pedidos/{id}/recibos
// Retorna datos formateados para recibos
```

---

## 🎯 Ejemplo de Uso

### Generar Array de Recibos

```javascript
function generarRecibos(datosFactura) {
    const recibos = [];
    
    // Iterar cada prenda
    datosFactura.prendas.forEach((prenda, prendaIdx) => {
        // 1. Agregar recibo de COSTURA para la prenda
        // El título varía según el origen
        let tituloCostura = "RECIBO DE COSTURA";
        if (prenda.origen && prenda.origen.toLowerCase() === 'bodega') {
            tituloCostura = "RECIBO DE COSTURA-BODEGA";
        }
        
        recibos.push({
            numero: recibos.length + 1,
            prendaIndex: prendaIdx,
            procesoIndex: null,
            prenda: prenda,
            proceso: null,
            titulo: tituloCostura,
            subtitulo: `PRENDA ${prenda.numero}: ${prenda.nombre}`
        });
        
        // 2. Agregar recibo para cada PROCESO de la prenda
        if (prenda.procesos && Array.isArray(prenda.procesos)) {
            prenda.procesos.forEach((proceso, procesoIdx) => {
                recibos.push({
                    numero: recibos.length + 1,
                    prendaIndex: prendaIdx,
                    procesoIndex: procesoIdx,
                    prenda: prenda,
                    proceso: proceso,
                    titulo: `RECIBO DE ${proceso.nombre.toUpperCase()}`,
                    subtitulo: `PRENDA ${prenda.numero}: ${prenda.nombre}`
                });
            });
        }
    });
    
    // Actualizar total en cada recibo
    const total = recibos.length;
    recibos.forEach(r => r.total = total);
    
    return recibos;
}
```

### Clase ReceiptManager

```javascript
class ReceiptManager {
    constructor(recibos, datosFactura) {
        this.recibos = recibos;
        this.datosFactura = datosFactura;
        this.indexActual = 0;
    }

    navegar(direccion) {
        if (direccion === 'siguiente' && this.indexActual < this.recibos.length - 1) {
            this.indexActual++;
            this.renderizar();
        } else if (direccion === 'anterior' && this.indexActual > 0) {
            this.indexActual--;
            this.renderizar();
        }
    }

    renderizar() {
        const recibo = this.recibos[this.indexActual];
        
        // Actualizar contador
        document.getElementById('receipt-counter').textContent = 
            `Recibo ${recibo.numero} de ${recibo.total}`;
        
        // Actualizar título
        document.getElementById('receipt-title').textContent = recibo.titulo;
        document.getElementById('receipt-subtitle').textContent = recibo.subtitulo;
        
        // Generar contenido según tipo
        const contenido = this.generarContenido(recibo);
        document.getElementById('process-description').innerHTML = contenido;
        
        // Actualizar estado de botones
        this.actualizarBotones();
    }

    generarContenido(recibo) {
        if (recibo.procesoIndex === null) {
            // Es recibo de COSTURA - mostrar datos de prenda
            return this.contenidoCostura(recibo.prenda);
        } else {
            // Es recibo de PROCESO - mostrar datos del proceso
            return this.contenidoProceso(recibo.proceso, recibo.prenda);
        }
    }

    contenidoCostura(prenda) {
        let html = `<strong>Color:</strong> ${prenda.color}<br>`;
        html += `<strong>Tela:</strong> ${prenda.tela}<br>`;
        
        // Mostrar origen si aplica
        if (prenda.origen) {
            const origenTexto = prenda.origen.toLowerCase() === 'bodega' 
                ? 'BODEGA' 
                : 'CONFECCIÓN';
            html += `<strong>Origen:</strong> ${origenTexto}<br>`;
        }
        
        if (prenda.cantidad_talla) {
            html += `<strong>Tallas:</strong><br>`;
            Object.entries(prenda.cantidad_talla).forEach(([talla, cant]) => {
                html += `${talla}: ${cant} | `;
            });
        }
        
        return html;
    }

    contenidoProceso(proceso, prenda) {
        let html = `<strong>${proceso.nombre}:</strong><br>`;
        
        if (proceso.especificaciones) {
            html += proceso.especificaciones.join('<br>') + '<br>';
        }
        
        if (proceso.imagenes && proceso.imagenes.length > 0) {
            html += '<strong>Imágenes:</strong><br>';
            proceso.imagenes.forEach(img => {
                html += `<img src="${img}" style="max-width: 150px; margin: 5px;">`;
            });
        }
        
        return html;
    }

    actualizarBotones() {
        const btnAnterior = document.getElementById('prev-receipt');
        const btnSiguiente = document.getElementById('next-receipt');
        
        btnAnterior.disabled = this.indexActual === 0;
        btnSiguiente.disabled = this.indexActual === this.recibos.length - 1;
    }

    imprimir() {
        window.print();
    }
}
```

---

##  Checklist de Implementación

- [ ] Crear `receipt-dynamic.blade.php`
- [ ] Crear `receipt-manager.js`
- [ ] Crear `receipt-dynamic.css`
- [ ] Agregar botón en `invoice-preview-live.js`
- [ ] Implementar lógica de navegación
- [ ] Implementar tabs de procesos
- [ ] Probar con pedidos reales
- [ ] Mejorar estilos móviles
- [ ] Documentar para usuarios

---

## 🚀 Próximos Pasos

1. **Confirmar con usuario** si esta estructura es correcta
2. **Comenzar Fase 1**: Crear componentes base
3. **Testing iterativo** con pedidos reales
4. **Refinamiento basado en feedback**
