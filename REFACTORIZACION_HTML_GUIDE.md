# Refactorización de HTML en crear-pedido-editable.js

## Problema Actual

El archivo `crear-pedido-editable.js` tiene **4,240 líneas** con:
- Mucha construcción de HTML inline con template literals
- HTML mezclado con lógica de JavaScript
- Difícil de mantener y actualizar
- Complejidad para debuguear estilos CSS

### Ejemplos del HTML actual:
- Línea 325: Contenedor de prendas (`prendasContainer.innerHTML`)
- Línea 771-773: Variables de HTML para tabs
- Línea 803+: Construcción de tabs con estilos inline
- Línea 875+: Construcción de contenedor de prendas
- Línea 986+: Tabla de tallas
- Línea 1070+: Tabla de variaciones
- Línea 1170+: Tabla de telas
- Línea 2400+: Sección completa de logo

---

## Solución Implementada

Se creó **`templates-pedido.js`** con funciones reutilizables que retornan templates HTML.

### Estructura del archivo de templates:

```javascript
window.templates = {
    tabsContainer: () => {...},
    tabButton: (label, icon, isActive) => {...},
    tabContentWrapper: () => {...},
    prendaHeader: (index, nombre) => {...},
    prendaGaleria: (index, fotoPrincipal, fotos, restantes) => {...},
    tableHeader: (columns, hasAddButton, buttonText, onClick) => {...},
    tallaRow: (index, talla) => {...},
    variacionRow: (index, varIdx, variacion, inputHtml) => {...},
    telaRow: (index, telaIdx, tela, fotosHtml) => {...},
    logoHeader: () => {...},
    logoDescripcion: (value) => {...},
    logoFotosGaleria: () => {...},
    logoUbicacionesTabla: () => {...}
}
```

---

## Cómo Refactorizar (Pasos)

### 1. **Incluir el nuevo archivo en HTML**

En el HTML donde se carga `crear-pedido-editable.js`, agregar antes:

```html
<script src="public/js/templates-pedido.js"></script>
<script src="public/js/crear-pedido-editable.js"></script>
```

### 2. **Reemplazar construcción de tabs (Línea ~800)**

**Antes:**
```javascript
html += `<div style="
    display: flex;
    gap: 0;
    ...
>`;

if (tienePrendas) {
    html += `<button type="button" class="tab-button-editable active" ...>...</button>`;
}
```

**Después:**
```javascript
html += window.templates.tabsContainer();

if (tienePrendas) {
    html += window.templates.tabButton('PRENDAS', 'fas fa-box', true);
}
if (tieneLogoPrendas) {
    html += window.templates.tabButton('LOGO', 'fas fa-tools', false);
}

html += '</div>';
html += window.templates.tabContentWrapper();
```

### 3. **Reemplazar tablas de tallas (Línea ~984)**

**Antes:**
```javascript
let tallasHtml = '';
if (tallas.length > 0) {
    tallasHtml = '<div style="margin-top: 1.5rem; ...>';
    tallasHtml += '<div style="padding: 0.75rem ...>';
    tallasHtml += '<div style="display: flex; gap: 1rem; flex: 1;">...';
    // Muchas líneas de HTML
    tallas.forEach(talla => {
        tallasHtml += `<div style="padding: 1rem; ...">...`;
    });
}
```

**Después:**
```javascript
let tallasHtml = '';
if (tallas.length > 0) {
    tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
    tallasHtml += window.templates.tableHeader(
        [
            { name: 'Talla', flex: '1.5' },
            { name: 'Cantidad', flex: '1' },
            { name: 'Acción', flex: '100px' }
        ],
        true,
        '+',
        `mostrarModalAgregarTalla(${index})`
    );
    
    tallas.forEach(talla => {
        tallasHtml += window.templates.tallaRow(index, talla);
    });
    
    tallasHtml += '</div>';
}
```

### 4. **Reemplazar tabla de variaciones (Línea ~1070)**

**Antes:**
```javascript
if (variacionesArray.length > 0) {
    variacionesHtml = '<div style="margin-top: 1.5rem; ...>';
    variacionesHtml += '<div style="padding: 0.5rem ...>';
    variacionesHtml += '<div>📋 Variaciones</div>';
    // Más HTML...
    variacionesArray.forEach((variacion, varIdx) => {
        variacionesHtml += `<div style="padding: 0.6rem ...">...`;
    });
}
```

**Después:**
```javascript
if (variacionesArray.length > 0) {
    variacionesHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
    variacionesHtml += window.templates.tableHeader(
        [
            { name: '📋 Variaciones', flex: '1' },
            { name: 'Valor', flex: '80px' },
            { name: 'Observaciones', flex: '1.2fr' },
            { name: 'Acción', flex: '45px' }
        ]
    );
    
    variacionesArray.forEach((variacion, varIdx) => {
        let inputHtml = variacion.esCheckbox ? 
            `<select data-field="${variacion.campo}" ...>...</select>` :
            `<input type="text" value="${variacion.valor}" ...>`;
        
        variacionesHtml += window.templates.variacionRow(index, varIdx, variacion, inputHtml);
    });
    
    variacionesHtml += '</div>';
}
```

### 5. **Reemplazar tabla de telas (Línea ~1170)**

**Antes:**
```javascript
if (telasParaTabla && telasParaTabla.length > 0) {
    telasHtml = '<div style="margin-top: 1.5rem; ...>';
    telasHtml += '<div style="position: relative; padding: 0.75rem ...>';
    // Mucho HTML...
    telasParaTabla.forEach((tela, telaIdx) => {
        telasHtml += `<div style="padding: 1rem; ...">...`;
    });
}
```

**Después:**
```javascript
if (telasParaTabla && telasParaTabla.length > 0) {
    telasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
    telasHtml += window.templates.tableHeader(
        [
            { name: 'Telas', flex: '1' },
            { name: 'Color', flex: '1' },
            { name: 'Referencia', flex: '1' },
            { name: 'Fotos', flex: '120px' }
        ],
        true,
        '＋',
        `agregarFilaTela(${index})`
    );
    
    telasParaTabla.forEach((tela, telaIdx) => {
        telasHtml += window.templates.telaRow(index, telaIdx, tela, fotosTelaHtml);
    });
    
    telasHtml += '</div>';
}
```

### 6. **Reemplazar sección de logo (Línea ~2400)**

**Antes:**
```javascript
if (tieneLogoPrendas) {
    html += `<div style="margin-top: 1rem; padding: 2rem; ...">`;
    html += `<h3 style="margin: 0 0 1.5rem 0; ...">📋 Información del Logo</h3>`;
    // 100+ líneas más de HTML
}
```

**Después:**
```javascript
if (tieneLogoPrendas) {
    html += window.templates.logoHeader();
    html += window.templates.logoDescripcion(logoCotizacion.descripcion);
    html += window.templates.logoFotosGaleria();
    html += window.templates.logoTecnicasSelectorAndTable();
    html += window.templates.logoObservacionesTecnicas(logoCotizacion.observaciones_tecnicas);
    html += window.templates.logoUbicacionesTabla();
    html += '</div>'; // cierra la sección
    html += '</div>'; // cierra el tab
}
```

---

## Ventajas de la Refactorización

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Líneas de código** | 4,240 | ~2,500 (est.) |
| **Legibilidad** | Difícil (HTML + JS) | Clara (separación) |
| **Mantenimiento** | Complejo | Sencillo |
| **Cambios CSS** | Buscar en todo el archivo | Modificar en templates |
| **Reutilización** | No | Sí (funciones) |
| **Testing** | Difícil | Más fácil |

---

## Próximas Mejoras Sugeridas

1. **Extraer más templates:**
   - Modales (ubicaciones, galería, etc.)
   - Formularios de edición
   - Mensajes y alertas

2. **Sistema de componentes:**
   - Crear carpeta `components/`
   - Separar templates por tipo (forms, tables, modals)
   - Importar dinámicamente

3. **Considerar framework:**
   - Vue.js o Alpine.js para reactividad
   - Eliminar manipulación manual del DOM
   - Bindings automáticos de datos

4. **Documentación:**
   - JSDoc para funciones de templates
   - Ejemplos de uso para cada template
   - Parámetros y valores por defecto

---

## Archivo de Templates Creado

**Ubicación:** `public/js/templates-pedido.js`

**Contiene:**
- 13+ funciones de template
- 500+ líneas de código HTML limpio
- Todos los estilos inline preservados
- Compatible con el código existente

**Para usar:**
1. Incluir en HTML ANTES de `crear-pedido-editable.js`
2. Reemplazar construcciones de HTML line-by-line
3. Probar cada cambio
4. Iterar hasta completar la refactorización

---

## Ejemplo de Migración Completa

**Pasos sugeridos:**
1. ✅ Crear `templates-pedido.js` (YA HECHO)
2. ⏭️ Refactorizar sección de tabs (líneas 800-860)
3. ⏭️ Refactorizar tabla de tallas (líneas 984-1025)
4. ⏭️ Refactorizar tabla de variaciones (líneas 1070-1125)
5. ⏭️ Refactorizar tabla de telas (líneas 1170-1400)
6. ⏭️ Refactorizar sección de logo (líneas 2400+)
7. ⏭️ Agregar comentarios y documentación
8. ⏭️ Testear funcionalidad completa
9. ⏭️ Considerar componentes de mayor nivel

---

**Autor:** Refactorización de código  
**Fecha:** 2024  
**Estado:** Documento de guía para refactorización gradual
