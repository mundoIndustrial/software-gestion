# ⚡ SOLUCIONES RÁPIDAS PARA CSP/EVAL

**Objetivo:** Proporcionar soluciones listas para copiar y pegar

---

## 📋 CONTENIDO

1. Solución para el botón flotante (CRÍTICA)
2. Solución para inputs con hover (ALTA)
3. Solución para modales (ALTA)
4. Solución para efectos button (MEDIA)
5. Cómo implementar cada solución

---

## 🔴 SOLUCIÓN 1: BOTÓN FLOTANTE (create.blade.php)

### Archivo a crear: [public/js/floating-menu.js](public/js/floating-menu.js)

```javascript
/**
 * FloatingMenu Module
 * Maneja el menú flotante para agregar prenda y especificaciones
 */

const FloatingMenu = {
    // Cache de elementos
    elements: {},
    
    // Inicialización
    init() {
        this.cacheElements();
        if (!this.elements.btn || !this.elements.menu) {
            console.error('❌ FloatingMenu: Elementos no encontrados');
            return;
        }
        this.attachListeners();
    },
    
    // Guardar referencias a elementos
    cacheElements() {
        this.elements.btn = document.getElementById('btnFlotante');
        this.elements.menu = document.getElementById('menuFlotante');
    },
    
    // Adjuntar event listeners
    attachListeners() {
        if (this.elements.btn) {
            this.elements.btn.addEventListener('click', (e) => this.toggle());
            this.elements.btn.addEventListener('mouseover', (e) => this.handleHover(true));
            this.elements.btn.addEventListener('mouseout', (e) => this.handleHover(false));
        }
    },
    
    // Toggle del menú
    toggle() {
        const isHidden = this.elements.menu.style.display === 'none';
        
        // Cambiar visibilidad
        this.elements.menu.style.display = isHidden ? 'block' : 'none';
        
        // Rotar botón
        const rotation = isHidden ? 'rotate(45deg)' : 'rotate(0deg)';
        this.elements.btn.style.transform = `scale(1) ${rotation}`;
        
        console.log('🔵 Menu toggleado:', isHidden ? 'ABIERTO' : 'CERRADO');
    },
    
    // Efectos de hover
    handleHover(isHovering) {
        const menuOpen = this.elements.menu.style.display === 'block';
        
        if (isHovering) {
            // Hover: sombra más fuerte y escala
            this.elements.btn.style.boxShadow = '0 6px 20px rgba(30, 64, 175, 0.5)';
            const scale = 1.1;
            const rotation = menuOpen ? 'rotate(45deg)' : 'rotate(0deg)';
            this.elements.btn.style.transform = `scale(${scale}) ${rotation}`;
        } else {
            // Normal: restaurar
            this.elements.btn.style.boxShadow = '0 4px 12px rgba(30, 64, 175, 0.4)';
            const rotation = menuOpen ? 'rotate(45deg)' : 'rotate(0deg)';
            this.elements.btn.style.transform = `scale(1) ${rotation}`;
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    FloatingMenu.init();
});

// Exportar para módulos ES6 si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FloatingMenu;
}
```

### Blade Template actualizado

**Antes:**
```html
<button type="button" id="btnFlotante" 
    onclick="console.log('🔵 CLICK EN BOTÓN'); const menu = document.getElementById('menuFlotante'); ... (800+ chars)"
    onmouseover="this.style.boxShadow=..."
    onmouseout="this.style.boxShadow=...">
```

**Después:**
```html
<!-- Dentro de resources/views/cotizaciones/prenda/create.blade.php -->

<!-- Botón principal flotante -->
<button type="button" id="btnFlotante" 
    style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;">
    <i class="fas fa-plus"></i>
</button>

<!-- Incluir el script al final del archivo -->
<script src="{{ asset('js/floating-menu.js') }}"></script>
```

---

## 🟠 SOLUCIÓN 2: INPUTS CON HOVER (dashboard.blade.php)

### Archivo a crear: [public/js/form-styling.js](public/js/form-styling.js)

```javascript
/**
 * FormStyling Module
 * Maneja estilos dinámicos para inputs y botones
 */

const FormStyling = {
    // Colores por defecto
    colors: {
        border: '#e2e8f0',
        borderHover: '#cbd5e1',
        borderFocus: '#0ea5e9'
    },
    
    // Inicializar
    init() {
        this.initInputs();
        this.initButtons();
    },
    
    // Inicializar inputs con estilos
    initInputs() {
        const inputs = document.querySelectorAll(
            'input[type="text"], input[type="date"], input[type="email"], input[type="password"], select'
        );
        
        inputs.forEach(input => {
            if (input.getAttribute('data-form-styling') === 'false') return;
            
            // Hover
            input.addEventListener('mouseover', () => {
                input.style.borderColor = this.colors.borderHover;
            });
            
            input.addEventListener('mouseout', () => {
                // Si no está focused, restaurar color original
                if (document.activeElement !== input) {
                    input.style.borderColor = this.colors.border;
                }
            });
            
            // Focus
            input.addEventListener('focus', () => {
                input.style.borderColor = this.colors.borderFocus;
            });
            
            input.addEventListener('blur', () => {
                input.style.borderColor = this.colors.border;
            });
        });
    },
    
    // Inicializar botones con efectos
    initButtons() {
        const buttons = document.querySelectorAll('[data-button-hover="true"]');
        
        buttons.forEach(btn => {
            const originalShadow = btn.style.boxShadow || '0 4px 6px rgba(14, 165, 233, 0.3)';
            const hoverShadow = '0 6px 12px rgba(14, 165, 233, 0.4)';
            
            btn.addEventListener('mouseover', () => {
                btn.style.transform = 'translateY(-2px)';
                btn.style.boxShadow = hoverShadow;
            });
            
            btn.addEventListener('mouseout', () => {
                btn.style.transform = 'translateY(0)';
                btn.style.boxShadow = originalShadow;
            });
        });
    }
};

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    FormStyling.init();
});
```

### Blade Template actualizado

**Antes:**
```html
<input type="text" id="filtro-search" 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">
```

**Después:**
```html
<input type="text" id="filtro-search" placeholder="Cotización, cliente..." 
    style="width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; background: #f8fafc;">

<!-- Similar para otros inputs -->

<!-- Incluir al final -->
<script src="{{ asset('js/form-styling.js') }}"></script>
```

**Beneficios:**
- ✅ Un script para todos los inputs
- ✅ Fácil de mantener
- ✅ Reutilizable en todo el proyecto

---

## 🟠 SOLUCIÓN 3: MODALES (operario/dashboard.blade.php)

### Archivo a crear: [public/js/modal-manager.js](public/js/modal-manager.js)

```javascript
/**
 * ModalManager Module
 * Maneja apertura/cierre de modales de forma centralizada
 */

const ModalManager = {
    // Registro de modales
    modals: {},
    
    // Registrar un modal
    register(modalId, options = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`❌ Modal no encontrado: ${modalId}`);
            return;
        }
        
        this.modals[modalId] = {
            element: modal,
            isOpen: false,
            closeOnOverlay: options.closeOnOverlay !== false,
            onOpen: options.onOpen || null,
            onClose: options.onClose || null
        };
        
        // Agregar listener para cerrar con overlay
        if (this.modals[modalId].closeOnOverlay) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close(modalId);
                }
            });
        }
    },
    
    // Abrir modal
    open(modalId) {
        if (!this.modals[modalId]) {
            console.error(`❌ Modal no registrado: ${modalId}`);
            return;
        }
        
        const modal = this.modals[modalId];
        modal.element.style.display = 'flex';
        modal.isOpen = true;
        
        if (modal.onOpen) modal.onOpen();
        console.log(`✅ Modal abierto: ${modalId}`);
    },
    
    // Cerrar modal
    close(modalId) {
        if (!this.modals[modalId]) {
            console.error(`❌ Modal no registrado: ${modalId}`);
            return;
        }
        
        const modal = this.modals[modalId];
        modal.element.style.display = 'none';
        modal.isOpen = false;
        
        if (modal.onClose) modal.onClose();
        console.log(`✅ Modal cerrado: ${modalId}`);
    },
    
    // Toggle modal
    toggle(modalId) {
        if (this.modals[modalId].isOpen) {
            this.close(modalId);
        } else {
            this.open(modalId);
        }
    },
    
    // Cerrar todos los modales
    closeAll() {
        Object.keys(this.modals).forEach(modalId => {
            this.close(modalId);
        });
    }
};

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    // Registrar modales
    ModalManager.register('modalReportar', {
        closeOnOverlay: true,
        onOpen: () => console.log('📋 Modal de reporte abierto'),
        onClose: () => console.log('✅ Modal de reporte cerrado')
    });
    
    // Agregar listeners a botones
    const btnsAbrir = document.querySelectorAll('[data-modal-toggle]');
    btnsAbrir.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = btn.getAttribute('data-modal-toggle');
            ModalManager.toggle(modalId);
        });
    });
});
```

### Blade Template actualizado

**Antes:**
```html
<button type="button" class="btn-reportar-pendiente" 
    onclick="abrirModalReportar('{{ $pedido['numero_pedido'] }}', '{{ $pedido['cliente'] }}')">
```

**Después:**
```html
<button type="button" class="btn-reportar-pendiente" 
    data-modal-toggle="modalReportar"
    data-pedido="{{ $pedido['numero_pedido'] }}"
    data-cliente="{{ $pedido['cliente'] }}">
    Reportar
</button>

<!-- Modal -->
<div id="modalReportar" class="modal-overlay">
    <div class="modal-content">
        <!-- Contenido -->
    </div>
</div>

<!-- Script -->
<script src="{{ asset('js/modal-manager.js') }}"></script>
```

---

## 🟡 SOLUCIÓN 4: EFECTOS DE BOTONES (supervisor/pedidos)

### Archivo a crear: [public/js/button-effects.js](public/js/button-effects.js)

```javascript
/**
 * ButtonEffects Module
 * Maneja efectos hover para botones
 */

const ButtonEffects = {
    // Efectos predefinidos
    effects: {
        primary: {
            normal: {
                shadow: '0 4px 12px rgba(52, 152, 219, 0.3)',
                transform: 'translateY(0) scale(1)'
            },
            hover: {
                shadow: '0 6px 20px rgba(52, 152, 219, 0.5)',
                transform: 'translateY(-2px) scale(1.05)'
            }
        },
        success: {
            normal: {
                shadow: '0 4px 6px rgba(16, 185, 129, 0.25)',
                transform: 'translateY(0)',
                background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
            },
            hover: {
                shadow: '0 8px 12px rgba(16, 185, 129, 0.4)',
                transform: 'translateY(-2px)',
                background: 'linear-gradient(135deg, #059669 0%, #047857 100%)'
            }
        },
        danger: {
            normal: {
                background: 'white',
                color: '#e74c3c'
            },
            hover: {
                background: '#e74c3c',
                color: 'white'
            }
        }
    },
    
    // Inicializar
    init() {
        this.attachEffects();
    },
    
    // Adjuntar efectos a botones
    attachEffects() {
        // Botones con clase data-effect
        const buttons = document.querySelectorAll('[data-button-effect]');
        
        buttons.forEach(btn => {
            const effectName = btn.getAttribute('data-button-effect');
            const effect = this.effects[effectName];
            
            if (!effect) return;
            
            btn.addEventListener('mouseover', () => {
                this.applyEffect(btn, effect.hover);
            });
            
            btn.addEventListener('mouseout', () => {
                this.applyEffect(btn, effect.normal);
            });
        });
    },
    
    // Aplicar efecto a un botón
    applyEffect(btn, effect) {
        if (effect.shadow) btn.style.boxShadow = effect.shadow;
        if (effect.transform) btn.style.transform = effect.transform;
        if (effect.background) btn.style.background = effect.background;
        if (effect.color) btn.style.color = effect.color;
    }
};

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    ButtonEffects.init();
});
```

### Blade Template actualizado

**Antes:**
```html
<button onmouseover="this.style.boxShadow='0 4px 12px rgba(52, 152, 219, 0.3)'" 
        onmouseout="this.style.boxShadow='0 2px 8px rgba(52, 152, 219, 0.2)'">
```

**Después:**
```html
<button data-button-effect="primary" class="btn-primary">
    Editar
</button>

<button data-button-effect="success" class="btn-success">
    Guardar
</button>

<button data-button-effect="danger" class="btn-danger">
    Eliminar
</button>

<!-- Script -->
<script src="{{ asset('js/button-effects.js') }}"></script>
```

---

## 📝 GUÍA DE IMPLEMENTACIÓN

### Paso 1: Crear los archivos JavaScript

```bash
# En tu terminal, dentro del proyecto
cd public/js
touch floating-menu.js form-styling.js modal-manager.js button-effects.js
```

### Paso 2: Copiar el código

Copia el código de cada módulo en su archivo correspondiente.

### Paso 3: Actualizar Blade Templates

Para cada vista problemática:

1. Localizar los handlers inline (`onclick`, `onmouseover`, etc.)
2. Reemplazarlos con `data-*` attributes
3. Incluir el script al final del template

**Ejemplo completo:**

```blade
<!-- Antes -->
<button onclick="myFunction()" onmouseover="this.style.color='blue'">Click</button>

<!-- Después -->
<button id="myButton" data-action="myFunction">Click</button>

<script>
document.getElementById('myButton').addEventListener('click', () => myFunction());
</script>
```

### Paso 4: Verificar en DevTools

1. Abre DevTools (F12)
2. Ve a Network → Headers
3. Busca `Content-Security-Policy`
4. Verifica que no haya errores de CSP

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Primera etapa (Día 1)
- [ ] Crear [public/js/floating-menu.js](public/js/floating-menu.js)
- [ ] Actualizar [resources/views/cotizaciones/prenda/create.blade.php](resources/views/cotizaciones/prenda/create.blade.php)
- [ ] Probar el botón flotante

### Segunda etapa (Día 2)
- [ ] Crear [public/js/form-styling.js](public/js/form-styling.js)
- [ ] Actualizar [resources/views/visualizador-logo/dashboard.blade.php](resources/views/visualizador-logo/dashboard.blade.php)
- [ ] Probar los inputs

### Tercera etapa (Día 3)
- [ ] Crear [public/js/modal-manager.js](public/js/modal-manager.js)
- [ ] Actualizar [resources/views/operario/dashboard.blade.php](resources/views/operario/dashboard.blade.php)
- [ ] Probar los modales

### Cuarta etapa (Día 4)
- [ ] Crear [public/js/button-effects.js](public/js/button-effects.js)
- [ ] Actualizar [resources/views/supervisor-asesores/pedidos/index.blade.php](resources/views/supervisor-asesores/pedidos/index.blade.php)
- [ ] Pruebas finales

---

## 🧪 TESTING

### Test manual

```javascript
// Abrir DevTools Console (F12)

// Test FloatingMenu
FloatingMenu.init();

// Test FormStyling
FormStyling.init();

// Test ModalManager
ModalManager.open('modalReportar');
ModalManager.close('modalReportar');

// Test ButtonEffects
ButtonEffects.init();
```

### Verificar CSP

```bash
# En tu servidor
curl -I https://sistemamundoindustrial.online | grep -i content-security-policy

# Debe mostrar:
# Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' ...
```

---

## 🚀 BENEFICIOS DESPUÉS DE IMPLEMENTAR

✅ **Seguridad mejorada** - Menos código inline  
✅ **Rendimiento mejorado** - Caching de JS  
✅ **Código más limpio** - Separación de responsabilidades  
✅ **Mantenimiento más fácil** - Menos duplicación  
✅ **Debugging más sencillo** - DevTools más claros  
✅ **Reutilización** - Módulos en múltiples vistas  

---

**Generado por:** GitHub Copilot  
**Fecha:** 7 de Enero de 2026
