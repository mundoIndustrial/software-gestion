// Debug CSS - Detectar conflictos de estilos en Cartera de Pedidos
// Ejecutar lo más pronto posible
console.clear();
console.log('%c=== DEBUG CSS CARTERA PEDIDOS ===', 'color: #ff6b6b; font-size: 16px; font-weight: bold; background: #fff3cd; padding: 10px;');

// Función para mostrar información detallada
function debugElement(selector, name) {
    const el = document.querySelector(selector);
    if (el) {
        const style = window.getComputedStyle(el);
        console.group(`%c${name}`, 'color: #0066cc; font-weight: bold; font-size: 13px;');
        console.log('Selector:', selector);
        console.log('Elemento:', el);
        console.log('Display:', style.display);
        console.log('Position:', style.position);
        console.log('Z-index:', style.zIndex);
        console.log('Top:', style.top, 'Bottom:', style.bottom);
        console.log('Width:', style.width, 'Height:', style.height);
        console.log('Margin:', style.margin);
        console.log('Padding:', style.padding);
        console.log('Overflow:', style.overflow, 'Overflow-Y:', style.overflowY);
        console.log('Background:', style.backgroundColor);
        console.log('Flex:', style.flex);
        console.log('---');
        console.groupEnd();
    } else {
        console.warn(`%c ${name} NO ENCONTRADO: ${selector}`, 'color: #dc2626; font-weight: bold;');
    }
}

// Debugear elementos principales
console.group('%c ESTRUCTURA DEL DOM', 'color: #059669; font-weight: bold; font-size: 13px;');
debugElement('body', 'BODY');
debugElement('.main-content', 'Main Content');
debugElement('header.top-nav', 'Header (Top Nav)');
debugElement('.content-area', 'Content Area');
debugElement('.cartera-pedidos-container', 'Cartera Container');
debugElement('.table-container', 'Table Container');
debugElement('.modern-table-wrapper', 'Table Wrapper');
console.groupEnd();

// Verificar orden de elementos en el DOM
console.group('%c🔍 ORDEN EN EL DOM (children del main-content)', 'color: #7c3aed; font-weight: bold; font-size: 13px;');
const mainContent = document.querySelector('.main-content');
if (mainContent) {
    const children = mainContent.children;
    console.log(`Total de hijos: ${children.length}`);
    for (let i = 0; i < children.length; i++) {
        const child = children[i];
        const style = window.getComputedStyle(child);
        console.log(`${i + 1}. ${child.tagName} (${child.className}) - Display: ${style.display}, Z-index: ${style.zIndex}`);
    }
} else {
    console.error('main-content NO EXISTE');
}
console.groupEnd();

// Verificar z-index de elementos
console.group('%c⬆️ Z-INDEX DE ELEMENTOS', 'color: #f59e0b; font-weight: bold; font-size: 13px;');
const elementsWithZIndex = document.querySelectorAll('[style*="z-index"], [class*="modal"], [class*="overlay"]');
console.log(`Elementos con z-index explícito: ${elementsWithZIndex.length}`);
elementsWithZIndex.forEach((el, idx) => {
    const zIndex = window.getComputedStyle(el).zIndex;
    if (zIndex !== 'auto') {
        console.log(`${idx + 1}. Z-index: ${zIndex} - ${el.className || el.tagName}`);
    }
});
console.groupEnd();

// Hojas de estilo
console.group('%c HOJAS DE ESTILO CARGADAS', 'color: #3b82f6; font-weight: bold; font-size: 13px;');
Array.from(document.styleSheets).forEach((sheet, idx) => {
    try {
        const href = sheet.href || 'inline';
        console.log(`${idx + 1}. ${href}`);
    } catch (e) {
        console.log(`${idx + 1}. (Bloqueada por CORS)`);
    }
});
console.groupEnd();

// Variables CSS
console.group('%c VARIABLES CSS', 'color: #10b981; font-weight: bold; font-size: 13px;');
const root = document.documentElement;
const style = getComputedStyle(root);
const vars = ['--primary', '--primary-hover', '--surface-white', '--surface-gray', '--border-light'];
vars.forEach(v => {
    const val = style.getPropertyValue(v).trim();
    console.log(`${v}: ${val || '(no definido)'}`);
});
console.groupEnd();

// Verificar clases disponibles
console.group('%c CLASES CSS DISPONIBLES', 'color: #06b6d4; font-weight: bold; font-size: 13px;');
const classes = [
    'cartera-pedidos-container',
    'table-container',
    'modern-table-wrapper',
    'table-head',
    'table-body',
    'modal-overlay',
    'btn-success',
    'btn-danger'
];
classes.forEach(cls => {
    const exists = document.querySelector(`.${cls}`);
    console.log(`${exists ? '' : ''} .${cls}`);
});
console.groupEnd();

// Verificar si hay conflictos de posición
console.group('%c POSIBLES CONFLICTOS', 'color: #ef4444; font-weight: bold; font-size: 13px;');
const allElements = document.querySelectorAll('*');
let absoluteCount = 0;
let fixedCount = 0;
allElements.forEach(el => {
    const pos = window.getComputedStyle(el).position;
    if (pos === 'absolute') absoluteCount++;
    if (pos === 'fixed') fixedCount++;
});
console.log(`Elementos con position: absolute: ${absoluteCount}`);
console.log(`Elementos con position: fixed: ${fixedCount}`);
console.log(`Total de elementos en la página: ${allElements.length}`);
console.groupEnd();

// Mostrar tabla y header específicamente
console.group('%c🔴 ANÁLISIS TABLA vs HEADER', 'color: #991b1b; font-weight: bold; font-size: 13px;');
const header = document.querySelector('header.top-nav');
const tableWrapper = document.querySelector('.modern-table-wrapper');
if (header && tableWrapper) {
    const headerRect = header.getBoundingClientRect();
    const tableRect = tableWrapper.getBoundingClientRect();
    console.log('Header:', {
        top: headerRect.top,
        bottom: headerRect.bottom,
        height: headerRect.height,
        zIndex: window.getComputedStyle(header).zIndex
    });
    console.log('Table:', {
        top: tableRect.top,
        bottom: tableRect.bottom,
        height: tableRect.height,
        zIndex: window.getComputedStyle(tableWrapper).zIndex
    });
    if (tableRect.top < headerRect.bottom) {
        console.warn('%c🚨 ¡CONFLICTO! La tabla se superpone con el header', 'background: #fca5a5; color: #991b1b; font-weight: bold; padding: 5px;');
    }
}
console.groupEnd();

console.log('%c=== FIN DEBUG ===', 'color: #10b981; font-size: 12px; font-weight: bold;');
console.log('%cAbre la pestaña Elements/Inspector para ver la estructura del DOM', 'color: #6366f1; font-style: italic;');
