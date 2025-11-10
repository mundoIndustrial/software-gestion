# 🏢 Diseño ERP Profesional - Sistema de Pedidos

## 📋 Resumen

Se ha creado un nuevo sistema de diseño profesional tipo ERP empresarial para el módulo de pedidos, con un aspecto corporativo, moderno y altamente funcional.

## 🎨 Características del Nuevo Diseño

### 1. **Paleta de Colores Profesional**
```css
Azul Corporativo: #0066CC (Principal)
Verde Éxito: #00A86B (Acciones positivas)
Rojo Alerta: #E63946 (Acciones críticas)
Naranja Advertencia: #F77F00 (Alertas)
Gris Neutro: #F5F7FA (Fondos)
```

### 2. **Header Profesional**
- Gradiente azul corporativo
- Barra de acento verde
- Metadatos del pedido (fecha, usuario, estado)
- Tipografía clara y legible

### 3. **Sistema de Pestañas**
```
┌─────────────────────────────────────────────────────┐
│ 📋 Información General │ 👕 Productos │ 📊 Resumen │
└─────────────────────────────────────────────────────┘
```

### 4. **Secciones Colapsables**
Cada sección puede expandirse/contraerse:
- ✅ Información del Cliente
- ✅ Detalles del Producto
- ✅ Tallas y Cantidades
- ✅ Configuración de Telas
- ✅ Personalización (Bordados/Estampados)

### 5. **Tarjetas de Producto**
- Diseño en tarjetas individuales
- Número de producto destacado
- Bordes que cambian al hover
- Sombras sutiles profesionales

## 🔧 Implementación

### Paso 1: Agregar el CSS al Layout

En `resources/views/layouts/app.blade.php` o en la vista de pedidos:

```html
<!-- Agregar después del CSS existente -->
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos-erp.css') }}">
```

### Paso 2: Estructura HTML del Formulario

```html
<div class="erp-form-container">
    <!-- Header Profesional -->
    <div class="erp-form-header">
        <h1 class="erp-form-title">Nuevo Pedido</h1>
        <p class="erp-form-subtitle">Complete la información del pedido</p>
        <div class="erp-form-meta">
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">calendar_today</span>
                <span>{{ date('d/m/Y') }}</span>
            </div>
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">person</span>
                <span>{{ Auth::user()->name }}</span>
            </div>
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">tag</span>
                <span>Pedido #{{ $siguientePedido }}</span>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="erp-tabs">
        <button class="erp-tab active" data-tab="general">
            <span class="material-symbols-rounded">info</span>
            Información General
        </button>
        <button class="erp-tab" data-tab="productos">
            <span class="material-symbols-rounded">inventory_2</span>
            Productos
        </button>
        <button class="erp-tab" data-tab="resumen">
            <span class="material-symbols-rounded">summarize</span>
            Resumen
        </button>
    </div>

    <form id="pedidoForm" method="POST">
        @csrf
        
        <!-- Contenido de Pestañas -->
        <div class="erp-tab-content" data-content="general">
            
            <!-- Sección Colapsable: Información del Cliente -->
            <div class="erp-section">
                <div class="erp-section-header">
                    <div class="erp-section-title">
                        <span class="material-symbols-rounded">business</span>
                        Información del Cliente
                        <span class="erp-section-badge">Requerido</span>
                    </div>
                    <div class="erp-section-toggle">
                        <span class="material-symbols-rounded">expand_more</span>
                    </div>
                </div>
                <div class="erp-section-body">
                    <div class="erp-form-grid cols-2">
                        <div class="erp-form-group">
                            <label class="erp-label required">
                                <span class="material-symbols-rounded">business</span>
                                Nombre del Cliente
                            </label>
                            <input type="text" 
                                   name="cliente" 
                                   class="erp-input" 
                                   placeholder="Ej: INVERSIONES EVAN"
                                   required>
                        </div>
                        
                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">phone</span>
                                Teléfono
                            </label>
                            <input type="tel" 
                                   name="telefono" 
                                   class="erp-input" 
                                   placeholder="Ej: 300 123 4567">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Detalles del Pedido -->
            <div class="erp-section">
                <div class="erp-section-header">
                    <div class="erp-section-title">
                        <span class="material-symbols-rounded">description</span>
                        Detalles del Pedido
                    </div>
                    <div class="erp-section-toggle">
                        <span class="material-symbols-rounded">expand_more</span>
                    </div>
                </div>
                <div class="erp-section-body">
                    <div class="erp-form-grid cols-3">
                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">event</span>
                                Fecha de Entrega
                            </label>
                            <input type="date" 
                                   name="fecha_entrega" 
                                   class="erp-input">
                        </div>
                        
                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">flag</span>
                                Estado
                            </label>
                            <select name="estado" class="erp-select">
                                <option value="No iniciado">No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                                <option value="Entregado">Entregado</option>
                            </select>
                        </div>
                        
                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">payments</span>
                                Forma de Pago
                            </label>
                            <select name="forma_pago" class="erp-select">
                                <option value="Contado">Contado</option>
                                <option value="Crédito">Crédito</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pestaña de Productos -->
        <div class="erp-tab-content" data-content="productos" style="display:none;">
            
            <div id="productosContainer">
                <!-- Tarjeta de Producto -->
                <div class="erp-product-card">
                    <div class="erp-product-header">
                        <div class="erp-product-number">1</div>
                        <div class="erp-product-actions">
                            <button type="button" class="erp-btn erp-btn-sm erp-btn-danger">
                                <span class="material-symbols-rounded">delete</span>
                                Eliminar
                            </button>
                        </div>
                    </div>
                    
                    <div class="erp-form-grid cols-2">
                        <div class="erp-form-group full-width">
                            <label class="erp-label required">
                                <span class="material-symbols-rounded">checkroom</span>
                                Tipo de Prenda
                            </label>
                            <input type="text" 
                                   name="productos[][nombre_producto]" 
                                   class="erp-input" 
                                   placeholder="Ej: CAMISA TIPO POLO"
                                   required>
                        </div>
                        
                        <!-- Más campos del producto -->
                    </div>
                </div>
            </div>
            
            <button type="button" class="erp-btn erp-btn-primary erp-btn-lg">
                <span class="material-symbols-rounded">add_circle</span>
                Agregar Producto
            </button>

        </div>

        <!-- Pestaña de Resumen -->
        <div class="erp-tab-content" data-content="resumen" style="display:none;">
            <div class="erp-summary">
                <div class="erp-summary-grid">
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Total Productos</div>
                        <div class="erp-summary-value" id="totalProductos">0</div>
                    </div>
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Total Unidades</div>
                        <div class="erp-summary-value" id="totalUnidades">0</div>
                    </div>
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Valor Total</div>
                        <div class="erp-summary-value" id="valorTotal">$0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones del Formulario -->
        <div class="erp-form-actions">
            <div class="erp-actions-left">
                <a href="{{ route('asesores.pedidos.index') }}" class="erp-btn erp-btn-secondary">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Cancelar
                </a>
            </div>
            <div class="erp-actions-right">
                <button type="button" class="erp-btn erp-btn-secondary">
                    <span class="material-symbols-rounded">save</span>
                    Guardar Borrador
                </button>
                <button type="submit" class="erp-btn erp-btn-success erp-btn-lg">
                    <span class="material-symbols-rounded">check_circle</span>
                    Crear Pedido
                </button>
            </div>
        </div>

    </form>
</div>
```

### Paso 3: JavaScript para Pestañas y Secciones

```javascript
// Manejo de Pestañas
document.querySelectorAll('.erp-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remover active de todas las pestañas
        document.querySelectorAll('.erp-tab').forEach(t => t.classList.remove('active'));
        // Agregar active a la pestaña clickeada
        this.classList.add('active');
        
        // Ocultar todo el contenido
        document.querySelectorAll('.erp-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        
        // Mostrar el contenido correspondiente
        const tabName = this.dataset.tab;
        document.querySelector(`[data-content="${tabName}"]`).style.display = 'block';
    });
});

// Manejo de Secciones Colapsables
document.querySelectorAll('.erp-section-header').forEach(header => {
    header.addEventListener('click', function() {
        const section = this.closest('.erp-section');
        section.classList.toggle('collapsed');
    });
});
```

## 🎯 Ventajas del Nuevo Diseño

### 1. **Profesionalismo**
- Colores corporativos
- Tipografía clara
- Espaciado consistente

### 2. **Organización**
- Pestañas para separar información
- Secciones colapsables
- Agrupación lógica de campos

### 3. **Usabilidad**
- Campos claramente etiquetados
- Iconos descriptivos
- Feedback visual al hover

### 4. **Responsive**
- Se adapta a tablets
- Funcional en móviles
- Grid flexible

### 5. **Accesibilidad**
- Alto contraste
- Tamaños de fuente legibles
- Áreas de click grandes

## 📊 Comparación Antes/Después

### Antes:
```
❌ Colores brillantes (naranja)
❌ Todo en una sola vista
❌ Formulario largo y abrumador
❌ Difícil de navegar
```

### Después:
```
✅ Colores profesionales (azul corporativo)
✅ Organizado en pestañas
✅ Secciones colapsables
✅ Navegación intuitiva
✅ Aspecto de ERP empresarial
```

## 🚀 Próximos Pasos

1. ✅ Aplicar el CSS nuevo
2. ⏳ Actualizar el HTML del formulario
3. ⏳ Implementar JavaScript de pestañas
4. ⏳ Adaptar las funciones existentes
5. ⏳ Probar en diferentes dispositivos

## 💡 Tips de Implementación

1. **Migración Gradual**: Puedes mantener ambos CSS y cambiar gradualmente
2. **Compatibilidad**: El nuevo diseño es compatible con el JavaScript existente
3. **Personalización**: Puedes ajustar los colores en las variables CSS
4. **Iconos**: Usa Material Symbols Rounded para consistencia

## 📝 Notas

- El archivo `pedidos-erp.css` contiene todos los estilos necesarios
- Es independiente del CSS anterior
- Puedes usarlo junto con el CSS existente sin conflictos
- Todas las clases tienen el prefijo `erp-` para evitar colisiones

---

**¡El nuevo diseño está listo para implementarse!** 🎉
