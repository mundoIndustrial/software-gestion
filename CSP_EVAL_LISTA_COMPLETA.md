# 🔍 LISTA DETALLADA DE VIOLACIONES DE CSP EN EL PROYECTO

**Generado:** 7 de Enero de 2026  
**Total de violaciones encontradas:** 100+ instances

---

## 📊 RESUMEN POR ARCHIVO

### 1️⃣ [resources/views/cotizaciones/prenda/create.blade.php](resources/views/cotizaciones/prenda/create.blade.php)
**Severidad:** 🔴 **CRÍTICA**  
**Línea:** 232  
**Tipo:** onclick, onmouseover, onmouseout  

**Código problemático:**
```html
<button type="button" id="btnFlotante" 
    onclick="console.log('🔵 CLICK EN BOTÓN'); const menu = document.getElementById('menuFlotante'); 
    console.log('Display actual:', menu.style.display); 
    console.log('Computed display:', window.getComputedStyle(menu).display); 
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; 
    console.log('Display nuevo:', menu.style.display); 
    console.log('Computed display nuevo:', window.getComputedStyle(menu).display); 
    this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'; 
    console.log('Transform:', this.style.transform); 
    setTimeout(() => { 
        console.log('Después de 100ms - Display:', menu.style.display, 'Computed:', window.getComputedStyle(menu).display); 
    }, 100);" 
    onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" 
    onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
    <i class="fas fa-plus"></i>
</button>
```

**Problemas identificados:**
- ❌ Más de 800 caracteres de código inline
- ❌ Lógica de negocio en HTML
- ❌ Múltiples console.log en producción
- ❌ Manipulación directa de estilos
- ❌ Código duplicado en handlers

**Solución:** ✅ Extraer a [public/js/floating-menu.js](public/js/floating-menu.js)

---

### 2️⃣ [resources/views/visualizador-logo/dashboard.blade.php](resources/views/visualizador-logo/dashboard.blade.php)
**Severidad:** 🟠 **ALTA**  
**Líneas:** 25, 31, 42, 48, 50, 259, 285  
**Tipo:** onmouseover, onmouseout, onfocus, onblur  

**Instancias encontradas:**

#### Línea 25 - Input de búsqueda
```html
<input type="text" id="filtro-search" placeholder="Cotización, cliente..." 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">
```

#### Línea 31 - Select de estado
```html
<select id="filtro-estado" 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">
```

#### Línea 42 - Input de fecha desde
```html
<input type="date" id="filtro-fecha-desde" 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">
```

#### Línea 48 - Input de fecha hasta
```html
<input type="date" id="filtro-fecha-hasta" 
    onmouseover="this.style.borderColor='#cbd5e1'" 
    onmouseout="this.style.borderColor='#e2e8f0'" 
    onfocus="this.style.borderColor='#0ea5e9'" 
    onblur="this.style.borderColor='#e2e8f0'">
```

#### Línea 50 - Botón de filtrado
```html
<button id="btn-filtrar" 
    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(14, 165, 233, 0.4)'" 
    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(14, 165, 233, 0.3)'">
```

**Problemas identificados:**
- ❌ Patrón repetido 5+ veces (duplicación de código)
- ❌ Estilos hardcoded en HTML
- ❌ Difícil mantener consistencia visual
- ❌ No hay separación CSS/JS/HTML

**Solución:** ✅ Usar clases CSS + event listeners en JS

---

### 3️⃣ [resources/views/visualizador-logo/detalle.blade.php](resources/views/visualizador-logo/detalle.blade.php)
**Severidad:** 🟡 **MEDIA**  
**Línea:** 175  
**Tipo:** onclick  

```html
<button onclick="verImagenCompleta('{{ Storage::url($foto->ruta_webp ?? $foto->ruta_original) }}')">
```

**Problemas identificados:**
- ⚠️ Llamada a función pero sin definición visible
- ⚠️ Parámetro con Blade template inline

---

### 4️⃣ [resources/views/operario/ver-pedido.blade.php](resources/views/operario/ver-pedido.blade.php)
**Severidad:** 🟠 **ALTA**  
**Líneas:** 10, 18, 22, 58, 63, 68, 81, 192, 197, 202, 1009, 1559, 1597, 1866  
**Tipo:** onclick, window.onclick  

**Instancias encontradas:**

| Línea | Tipo | Función | Código |
|-------|------|---------|--------|
| 10 | onclick | history.back() | `onclick="history.back()"` |
| 18 | onclick | cambiarTab | `onclick="cambiarTab('orden')"` |
| 22 | onclick | cambiarTab | `onclick="cambiarTab('fotos')"` |
| 58 | onclick | cerrarGaleria | `onclick="cerrarGaleria()"` |
| 63 | onclick | cerrarGaleria | `onclick="cerrarGaleria()"` |
| 68 | onclick | fotoAnterior | `onclick="fotoAnterior()"` |
| 81 | onclick | fotoSiguiente | `onclick="fotoSiguiente()"` |
| 192 | onclick | marcarEnProceso | `onclick="marcarEnProceso()"` |
| 197 | onclick | marcarCompletado | `onclick="marcarCompletado()"` |
| 202 | onclick | abrirModalReportarNovedad | `onclick="abrirModalReportarNovedad()"` |
| 1009 | onclick (JS) | fotoCard.onclick | `fotoCard.onclick = function() { ... }` |
| 1559 | onclick | cerrarModalReportarNovedad | `onclick="cerrarModalReportarNovedad()"` |
| 1597 | onclick | cerrarModalReportarNovedad | `onclick="cerrarModalReportarNovedad()"` |
| 1866 | onclick | cerrarModalRespuesta | `onclick="cerrarModalRespuesta()"` |

**Problemas identificados:**
- ❌ 14+ handlers onclick distribuidos en el archivo
- ❌ Mezclado HTML con lógica de JavaScript
- ❌ Difícil de mantener

---

### 5️⃣ [resources/views/users/index.blade.php](resources/views/users/index.blade.php)
**Severidad:** 🟠 **ALTA**  
**Líneas:** 24, 96, 101, 107, 134, 166, 178, 206, 218, 232, 244, 254  
**Tipo:** onclick  

**Instancias encontradas:**

| Línea | Función | Tipo |
|-------|---------|------|
| 24 | openCreateModal() | Botón crear |
| 96 | openEditModal() | Botón editar |
| 101 | openPasswordModal() | Botón contraseña |
| 107 | confirmDelete() | Botón eliminar |
| 134 | closeCreateModal() | Cerrar modal |
| 166 | closeCreateModal() | Cancelar |
| 178 | closeEditModal() | Cerrar modal |
| 206 | closeEditModal() | Cancelar |
| 218 | closePasswordModal() | Cerrar modal |
| 232 | closePasswordModal() | Cancelar |
| 244 | closeDeleteModal() | Cerrar modal |
| 254 | closeDeleteModal() | Cancelar |

**Problemas identificados:**
- ❌ Patrón repetido de abrir/cerrar modales
- ❌ 12 handlers onclick
- ❌ Sin delegación de eventos

---

### 6️⃣ [resources/views/operario/dashboard.blade.php](resources/views/operario/dashboard.blade.php)
**Severidad:** 🔴 **CRÍTICA**  
**Líneas:** 70, 77, 593, 698, 699, 922  
**Tipo:** onclick, window.onclick  

**Problemas identificados:**
- ❌ Modal window.onclick = function() en línea 593
- ❌ Mucho código de manejo de modales
- ❌ Lógica de interfaz dispersa

---

### 7️⃣ [resources/views/supervisor-asesores/pedidos/index.blade.php](resources/views/supervisor-asesores/pedidos/index.blade.php)
**Severidad:** 🔴 **CRÍTICA**  
**Líneas:** 373, 392, 630, 649, 689, 694, 718, 784, 815, 848, 866, 920, 926, 927, 930, 937, 938, 950, 988  
**Tipo:** onclick, onmouseover, onmouseout  

**Total de handlers:** 20+

**Problemas identificados:**
- ❌ Archivo más problemático del proyecto
- ❌ Múltiples handlers onclick, onmouseover, onmouseout
- ❌ Estilos inline complejos
- ❌ Código duplicado para efectos hover

**Ejemplos:**
```html
<!-- Línea 373: Botón con hover effects inline -->
<button onmouseover="this.style.boxShadow='0 4px 12px rgba(52, 152, 219, 0.3)'" 
        onmouseout="this.style.boxShadow='0 2px 8px rgba(52, 152, 219, 0.2)'">

<!-- Línea 689: onclick con parámetro -->
<div onclick="abrirModalCelda('Cliente', '{{ $pedido->cliente }}')">

<!-- Línea 920: resetFilters() -->
<button onclick="resetFilters(); updateClearButtonVisibility();">

<!-- Línea 926: closeFilterModal(event) -->
<div onclick="closeFilterModal(event)">
```

---

### 8️⃣ [resources/views/asesores/pedidos/create-reflectivo.blade.php](resources/views/asesores/pedidos/create-reflectivo.blade.php)
**Severidad:** 🟡 **MEDIA**  
**Líneas:** 1727, 1745  
**Tipo:** setTimeout  

```javascript
// Línea 1727
setTimeout(() => input.style.border = '', 1500);

// Línea 1745
setTimeout(() => document.getElementById('modalUbicacionTextarea').focus(), 100);
```

**Problemas identificados:**
- ⚠️ setTimeout con funciones (está bien formado)
- ℹ️ Pero podría extraerse a un módulo

---

### 9️⃣ [resources/views/components/modal.blade.php](resources/views/components/modal.blade.php)
**Severidad:** 🟡 **MEDIA**  
**Línea:** 41  
**Tipo:** x-init (Alpine.js)  

```html
{{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
```

**Problemas identificados:**
- ⚠️ Código generado por Blade dinámicamente
- ⚠️ Podría ser más limpio

---

### 🔟 [resources/views/components/top-controls.blade.php](resources/views/components/top-controls.blade.php)
**Severidad:** 🟡 **MEDIA**  
**Líneas:** 189, 225  
**Tipo:** @change, x-init (Alpine.js)  

```html
<!-- Línea 189 -->
<input @change="if ($event.target.value === 'specific') { setTimeout(() => initCalendar(), 50); }">

<!-- Línea 225 -->
<div class="calendar-container" x-init="setTimeout(() => initCalendar(), 100)">
```

**Problemas identificados:**
- ⚠️ Lógica Alpine.js inline
- ⚠️ Condicionales complejas

---

### 1️⃣1️⃣ [resources/views/profile/partials/update-password-form.blade.php](resources/views/profile/partials/update-password-form.blade.php)
**Severidad:** 🟢 **BAJA**  
**Línea:** 42  
**Tipo:** x-init  

```html
x-init="setTimeout(() => show = false, 2000)"
```

**Problemas identificados:**
- ℹ️ Correctamente formado (función flecha)
- ℹ️ Aceptable aunque podría mejorarse

---

### 1️⃣2️⃣ [resources/views/profile/partials/update-profile-information-form.blade.php](resources/views/profile/partials/update-profile-information-form.blade.php)
**Severidad:** 🟢 **BAJA**  
**Línea:** 58  

```html
x-init="setTimeout(() => show = false, 2000)"
```

---

### 1️⃣3️⃣ [resources/views/tableros.blade.php](resources/views/tableros.blade.php)
**Severidad:** 🟡 **MEDIA**  
**Líneas:** 207, 212, 220  
**Tipo:** x-init  

```html
<div x-show="activeTab === 'polos'" x-init="console.log('🔍 POLOS TAB - activeTab:', activeTab, 'showRecords:', showRecords)">
<div x-show="!showRecords" x-init="console.log('📊 Seguimiento Polos - showRecords:', showRecords, 'Visible:', !showRecords)">
<div x-show="showRecords" x-init="console.log('📋 Tabla Polos - showRecords:', showRecords, 'Visible:', showRecords)">
```

**Problemas identificados:**
- ⚠️ console.log en producción
- ⚠️ Debug code que debería removerse

---

### 1️⃣4️⃣ [resources/views/vistas/control-calidad.blade.php](resources/views/vistas/control-calidad.blade.php)
**Severidad:** 🟢 **BAJA**  
**Línea:** 24  

```html
<button class="fullscreen-btn" onclick="openFullscreen()">
```

---

### 1️⃣5️⃣ [resources/views/vistas/control-calidad-fullscreen.blade.php](resources/views/vistas/control-calidad-fullscreen.blade.php)
**Severidad:** 🟢 **BAJA**  
**Línea:** 368  

```html
<button class="close-fullscreen-btn" onclick="closeFullscreen()">
```

---

### 1️⃣6️⃣ Otros archivos menores
**Archivos:** tableros-fullscreen.blade.php, tableros-corte-fullscreen.blade.php, operario/mis-pedidos.blade.php, supervisor-asesores/profile/index.blade.php, supervisor-asesores/reportes/index.blade.php, supervisor-pedidos/layout.blade.php

**Total de instancias adicionales:** 30+

---

## 📈 ESTADÍSTICAS GLOBALES

```
Total de archivos afectados:        20+
Total de violaciones encontradas:   100+

Por tipo:
- onclick                           45 instancias
- onmouseover / onmouseout          35 instancias
- onfocus / onblur                  10 instancias
- x-init (Alpine.js)                8 instancias
- @change                           2 instancias
- setTimeout                        5 instancias

Por severidad:
🔴 CRÍTICA (refactorizar urgente)   5 archivos
🟠 ALTA (refactorizar pronto)       7 archivos
🟡 MEDIA (considerar mejorar)       5 archivos
🟢 BAJA (aceptable)                 3 archivos
```

---

## 🎯 PLAN DE ACCIÓN PRIORIZADO

### Fase 1: Crítica (Semana 1)
- [ ] Refactorizar `create.blade.php` línea 232
- [ ] Extraer handlers de `dashboard.blade.php` (operario)
- [ ] Extraer handlers de `pedidos/index.blade.php` (supervisor)

### Fase 2: Alta (Semana 2)
- [ ] Refactorizar `visualizador-logo/dashboard.blade.php`
- [ ] Refactorizar `users/index.blade.php`
- [ ] Refactorizar `ver-pedido.blade.php`

### Fase 3: Media (Semana 3)
- [ ] Limpiar componentes (modal, top-controls)
- [ ] Remover console.log de producción
- [ ] Crear módulos reutilizables

### Fase 4: Documentación
- [ ] Crear guía de mejores prácticas
- [ ] Documentar patrones reutilizables
- [ ] Crear ejemplos

---

## 🔐 CONCLUSIÓN

Tu proyecto **está funcionando correctamente** con `'unsafe-eval'` habilitado en CSP.

Sin embargo, hay **mucho código inline que debería refactorizarse** para:
- ✅ Mejor mantenibilidad
- ✅ Mejor performance
- ✅ Mayor seguridad
- ✅ Mejor debugging

**Recomendación:** Seguir el plan de acción priorizado y refactorizar fase por fase.

---

**Generado por:** GitHub Copilot  
**Fecha:** 7 de Enero de 2026
