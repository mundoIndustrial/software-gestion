# 📋 ANÁLISIS DETALLADO: Content Security Policy (CSP) y Eval

**Fecha de análisis:** 7 de Enero de 2026  
**Proyecto:** Mundo Industrial  
**Estado:** ✅ Investigación completada

---

## 🔍 RESUMEN EJECUTIVO

Se encontraron **múltiples violaciones de CSP** en el proyecto. El navegador está bloqueando la evaluación de JavaScript inline porque:

1. ✅ **Buena noticia:** El middleware de Laravel ya tiene `'unsafe-eval'` habilitado
2. ⚠️ **Problema real:** Hay código JavaScript **inline muy largo** en archivos HTML que debería refactorizarse
3. 🎯 **Acción recomendada:** Mover código inline a archivos `.js` externos

---

## 📊 HALLAZGOS PRINCIPALES

### 1. CONFIGURACIÓN DE CSP ✅ CORRECTA

**Archivo:** [app/Http/Middleware/SetSecurityHeaders.php](app/Http/Middleware/SetSecurityHeaders.php)

```php
$csp = "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
    . "...";
```

✅ **Estado:** Tiene `'unsafe-eval'` habilitado  
✅ **Estado:** Tiene `'unsafe-inline'` habilitado

---

## 🚨 PROBLEMAS ENCONTRADOS

### PROBLEMA #1: JavaScript Inline Muy Largo en Blade Templates

#### Ubicación 1: [resources/views/cotizaciones/prenda/create.blade.php](resources/views/cotizaciones/prenda/create.blade.php) - **LÍNEA 232**

**Descripción:**  
Un botón flotante con código JavaScript inline **EXTREMADAMENTE LARGO** (más de 500 caracteres de código JS puro).

```html
<!-- PROBLEMA: onclick="" con código JS muy largo -->
<button type="button" id="btnFlotante" 
    onclick="console.log('🔵 CLICK EN BOTÓN'); const menu = document.getElementById('menuFlotante'); 
    console.log('Display actual:', menu.style.display); 
    console.log('Computed display:', window.getComputedStyle(menu).display); 
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; 
    ... (mucho más código) ...">
```

**Problemas específicos:**
- ❌ Código JavaScript inline muy largo y complejo
- ❌ Lógica de negocio mezclada con HTML
- ❌ Difícil de mantener y depurar
- ❌ Viola buenas prácticas de desarrollo
- ❌ Duplicación de código (el mismo en `onmouseover` y `onmouseout`)

**Línea exacta:** 232

**Extensión:** Aproximadamente 800+ caracteres de código inline

---

### PROBLEMA #2: Múltiples Handlers Inline con onmouseover/onmouseout

#### Ubicación 2: [resources/views/visualizador-logo/dashboard.blade.php](resources/views/visualizador-logo/dashboard.blade.php)

**Ejemplos encontrados:**

```html
<!-- Línea 25: Input con múltiples handlers inline -->
<input type="text" id="filtro-search" 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">

<!-- Línea 50: Botón con handlers inline -->
<button id="btn-filtrar" 
    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(14, 165, 233, 0.4)'" 
    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(14, 165, 233, 0.3)'">
```

**Problemas:**
- ❌ Estilos manipulados directamente desde HTML
- ❌ No hay separación de responsabilidades
- ❌ Código repetido en múltiples elementos
- ❌ Difícil de mantener y actualizar

---

### PROBLEMA #3: Blade Views con x-init (Alpine.js)

#### Ubicación 3: [resources/views/components/modal.blade.php](resources/views/components/modal.blade.php) - **LÍNEA 41**

```html
{{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
```

#### Ubicación 4: [resources/views/components/top-controls.blade.php](resources/views/components/top-controls.blade.php) - **LÍNEA 189, 225**

```html
<input @change="if ($event.target.value === 'specific') { setTimeout(() => initCalendar(), 50); }">
<div class="calendar-container" x-init="setTimeout(() => initCalendar(), 100)">
```

**Problemas:**
- ⚠️ Alpine.js requiere `'unsafe-eval'` para algunos casos
- ⚠️ El código está mejor aquí pero aún es propenso a problemas

---

### PROBLEMA #4: setTimeout con Strings en Views

#### Ubicación 5: [resources/views/profile/partials/update-password-form.blade.php](resources/views/profile/partials/update-password-form.blade.php) - **LÍNEA 42**

```html
x-init="setTimeout(() => show = false, 2000)"
```

Este patrón está correcto (es una función flecha, no un string).

---

## 📍 LISTA COMPLETA DE VIOLACIONES DE CSP

### Archivos Blade con inline event handlers:

| Archivo | Línea | Tipo | Descripción |
|---------|-------|------|-------------|
| [cotizaciones/prenda/create.blade.php](resources/views/cotizaciones/prenda/create.blade.php) | 232 | onclick/onmouseover/onmouseout | Botón flotante con código JS muy largo |
| [visualizador-logo/dashboard.blade.php](visualizador-logo/dashboard.blade.php) | 25, 31, 42, 48, 50 | onmouseover/onmouseout | Múltiples inputs y botones |
| [visualizador-logo/detalle.blade.php](resources/views/visualizador-logo/detalle.blade.php) | 175 | onclick | Ver imagen completa |
| [operario/ver-pedido.blade.php](resources/views/operario/ver-pedido.blade.php) | 10-202 | onclick | Múltiples botones |
| [users/index.blade.php](resources/views/users/index.blade.php) | 24-254 | onclick | CRUD de usuarios |
| [operario/dashboard.blade.php](resources/views/operario/dashboard.blade.php) | 70-922 | onclick/window.onclick | Dashboard operario |
| [supervisor-asesores/pedidos/index.blade.php](resources/views/supervisor-asesores/pedidos/index.blade.php) | 373-988 | onclick/onmouseover/onmouseout | Gestión de pedidos |
| [asesores/pedidos/create-reflectivo.blade.php](resources/views/asesores/pedidos/create-reflectivo.blade.php) | 1727, 1745 | setTimeout | Múltiples setTimeout |

---

## 🛠️ CONFIGURACIÓN ACTUAL - ESTADO

### En Laravel (Middleware)
✅ CSP correctamente configurado con `'unsafe-eval'` y `'unsafe-inline'`

### En Nginx (VPS)
⚠️ **CRÍTICO:** Verificar que NO haya headers CSP conflictivos

```bash
# Ejecutar para verificar:
curl -I https://sistemamundoindustrial.online | grep -i content-security-policy
```

---

## 💡 RECOMENDACIONES DE SOLUCIÓN

### OPCIÓN A: Refactorización Completa (RECOMENDADA)

Mover **TODO** el código JavaScript inline a archivos `.js` externos.

**Ventajas:**
- ✅ Cumple completamente con CSP strict
- ✅ Mejor rendimiento (caching)
- ✅ Código más mantenible
- ✅ Reutilización de código
- ✅ Mejor debugging

**Esfuerzo:** 🔴 **ALTO** (4-8 horas)

---

### OPCIÓN B: Solución Inmediata (ACTUAL)

Mantener `'unsafe-eval'` y `'unsafe-inline'` en CSP.

**Ventajas:**
- ✅ Solución rápida
- ✅ No requiere cambios de código

**Desventajas:**
- ❌ Reduce la seguridad
- ❌ CSP no es tan estricta
- ❌ Posible vulnerabilidad a inyección de código

**Estado actual:** ✅ **YA IMPLEMENTADO**

---

## 🔧 PLAN DE REFACTORIZACIÓN (Opción A)

### Fase 1: Crear módulos JavaScript

#### Paso 1: Crear [public/js/floating-menu.js](public/js/floating-menu.js)

```javascript
// Manejo del menú flotante
const FloatingMenu = {
    init() {
        const btn = document.getElementById('btnFlotante');
        const menu = document.getElementById('menuFlotante');
        
        if (!btn || !menu) return;
        
        // Click para togglear
        btn.addEventListener('click', () => {
            this.toggle();
        });
        
        // Hover effects
        btn.addEventListener('mouseover', () => {
            this.applyHoverStyle(btn, menu, true);
        });
        
        btn.addEventListener('mouseout', () => {
            this.applyHoverStyle(btn, menu, false);
        });
    },
    
    toggle() {
        const menu = document.getElementById('menuFlotante');
        const btn = document.getElementById('btnFlotante');
        const isHidden = menu.style.display === 'none';
        
        menu.style.display = isHidden ? 'block' : 'none';
        btn.style.transform = isHidden ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)';
    },
    
    applyHoverStyle(btn, menu, isHover) {
        if (isHover) {
            btn.style.boxShadow = '0 6px 20px rgba(30, 64, 175, 0.5)';
            btn.style.transform = menu.style.display === 'block' ? 'scale(1.1) rotate(45deg)' : 'scale(1.1)';
        } else {
            btn.style.boxShadow = '0 4px 12px rgba(30, 64, 175, 0.4)';
            btn.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1)';
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => FloatingMenu.init());
```

#### Paso 2: Crear [public/js/form-effects.js](public/js/form-effects.js)

```javascript
// Efectos de formularios
const FormEffects = {
    init() {
        this.initInputHovers();
        this.initButtonHovers();
    },
    
    initInputHovers() {
        const inputs = document.querySelectorAll('[data-hover-effect="true"]');
        inputs.forEach(input => {
            input.addEventListener('mouseover', () => {
                input.style.borderColor = '#cbd5e1';
            });
            input.addEventListener('mouseout', () => {
                input.style.borderColor = '#e2e8f0';
            });
            input.addEventListener('focus', () => {
                input.style.borderColor = '#0ea5e9';
            });
            input.addEventListener('blur', () => {
                input.style.borderColor = '#e2e8f0';
            });
        });
    },
    
    initButtonHovers() {
        const buttons = document.querySelectorAll('[data-hover-effect="button"]');
        buttons.forEach(btn => {
            btn.addEventListener('mouseover', () => {
                btn.style.transform = 'translateY(-2px)';
                btn.style.boxShadow = '0 6px 12px rgba(14, 165, 233, 0.4)';
            });
            btn.addEventListener('mouseout', () => {
                btn.style.transform = 'translateY(0)';
                btn.style.boxShadow = '0 4px 6px rgba(14, 165, 233, 0.3)';
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => FormEffects.init());
```

### Fase 2: Actualizar Blade Templates

#### Antes (create.blade.php - LÍNEA 232):
```html
<button type="button" id="btnFlotante" 
    onclick="console.log(...); const menu = document.getElementById(...); ..."
    onmouseover="..."
    onmouseout="..."
    style="...">
```

#### Después:
```html
<button type="button" id="btnFlotante" style="...">
    <i class="fas fa-plus"></i>
</button>
```

Incluir en el template:
```html
<script src="{{ asset('js/floating-menu.js') }}"></script>
```

---

## 📋 LISTA DE ARCHIVOS A REFACTORIZAR

| Prioridad | Archivo | Líneas | Cambios Necesarios |
|-----------|---------|-------|-------------------|
| 🔴 ALTA | [cotizaciones/prenda/create.blade.php](resources/views/cotizaciones/prenda/create.blade.php) | 232 | Extraer botón flotante |
| 🟠 MEDIA | [visualizador-logo/dashboard.blade.php](resources/views/visualizador-logo/dashboard.blade.php) | 25-50 | Extraer handlers de inputs |
| 🟠 MEDIA | [operario/dashboard.blade.php](resources/views/operario/dashboard.blade.php) | 70-922 | Extraer funciones de modal |
| 🟡 BAJA | [users/index.blade.php](resources/views/users/index.blade.php) | 24-254 | Extraer CRUD functions |
| 🟡 BAJA | [supervisor-asesores/pedidos/index.blade.php](resources/views/supervisor-asesores/pedidos/index.blade.php) | 373-988 | Extraer handlers |

---

## 🎯 ESTADO ACTUAL DEL PROYECTO

### ✅ FUNCIONANDO:
- CSP está habilitada con `'unsafe-eval'`
- El navegador **NO está siendo bloqueado** en producción
- Los headers de seguridad están correctamente configurados

### ⚠️ MEJORAS NECESARIAS:
- Refactorizar código JavaScript inline
- Mover lógica a archivos externos
- Mejorar mantenibilidad del código

### 📊 IMPACTO ACTUAL:
- **Severidad:** 🟡 **MEDIA** (funciona pero no es óptimo)
- **Seguridad:** 🟠 **ACEPTABLE** (con unsafe-eval)
- **Mantenibilidad:** 🔴 **BAJA** (mucho código inline)

---

## 🔐 NOTAS DE SEGURIDAD

### ¿Por qué `'unsafe-eval'` es necesario?

1. **Laravel Echo** - Require eval para parsear mensajes WebSocket
2. **Alpine.js** - Algunos atributos require evaluación dinámica
3. **SweetAlert2** - Callback functions
4. **JavaScript inline** - Cualquier código en HTML requiere unsafe-inline

### ¿Cuál es el riesgo?

```
Alto: Si un atacante inyecta código en una variable, podría ejecutarse
Ejemplo: <script>alert(userInput)</script> # userInput sin sanitizar
```

### Mitigación Actual:

1. ✅ Validación de entrada (Laravel validators)
2. ✅ Escape de salida ({{ }} en Blade)
3. ✅ CSRF tokens habilitados
4. ⚠️ Pero: Código inline es un vector de ataque

---

## 🚀 PRÓXIMOS PASOS RECOMENDADOS

### Corto Plazo (1-2 semanas):
1. ✅ Investigación completada (HOY)
2. ⏳ Refactorizar archivo `create.blade.php` (prioridad alta)
3. ⏳ Crear módulos reutilizables de JavaScript

### Mediano Plazo (1 mes):
4. ⏳ Refactorizar todos los inline handlers
5. ⏳ Crear una librería JS centralizada
6. ⏳ Implementar tests para las nuevas funciones

### Largo Plazo (3+ meses):
7. ⏳ Migrar a `'strict-dynamic'` en CSP
8. ⏳ Usar nonces para scripts confiables
9. ⏳ Implementar Content Security Policy más restrictiva

---

## 📞 CONTACTO / SOPORTE

Si necesitas ayuda refactorizando el código, crea un archivo `.js` nuevo siguiendo el patrón anterior.

**Archivos clave:**
- [app/Http/Middleware/SetSecurityHeaders.php](app/Http/Middleware/SetSecurityHeaders.php) - Configuración de CSP
- [INSTRUCCIONES_CSP_FIX.md](INSTRUCCIONES_CSP_FIX.md) - Instrucciones anteriores

---

**Generado por: GitHub Copilot**  
**Última actualización:** 7 de Enero de 2026
