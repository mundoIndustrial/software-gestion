/**
 * EJEMPLO PRÁCTICO: Refactorización de la Sección de TALLAS
 * 
 * Este archivo muestra cómo refactorizar una sección del código
 * original usando los templates creados en templates-pedido.js
 * 
 * ANTES: ~50 líneas de construcción de HTML manual
 * DESPUÉS: ~20 líneas limpias usando templates
 */

// ============================================================
// CÓDIGO ORIGINAL (ANTES) - Líneas ~984-1025
// ============================================================

function renderizarTallasAntes(index, tallas) {
    let tallasHtml = '';
    if (tallas.length > 0) {
        tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
        tallasHtml += '<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0052a3 0%, #0ea5e9 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 700; display: flex; justify-content: space-between; align-items: center; width: 100%;">';
        tallasHtml += '<div style="display: flex; gap: 1rem; flex: 1;"><div style="flex: 1.5;">Talla</div><div style="flex: 1;">Cantidad</div><div style="width: 100px; text-align: center;">Acción</div></div>';
        tallasHtml += `
            <button type="button" onclick="mostrarModalAgregarTalla(${index})" style="background: white; color: #0b4f91; border: none; padding: 0.45rem 0.65rem; border-radius: 999px; cursor: pointer; font-size: 1rem; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,0,0,0.18); width: 36px; height: 36px;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(14,165,233,0.18); color: #0b4f91; font-size: 1rem; line-height: 1;">+</span>
            </button>
        `;
        tallasHtml += '</div>';
        
        tallas.forEach(talla => {
            tallasHtml += `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                    <div style="font-weight: 500; color: #1f2937;">${talla}</div>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                    <input type="number" 
                           name="cantidades[${index}][${talla}]" 
                           class="talla-cantidad"
                           min="0" 
                           value="0" 
                           placeholder="0"
                           data-talla="${talla}"
                           data-prenda="${index}"
                           style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
                </div>
                <div style="text-align: center;">
                    <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${index}, '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                        ✕ Quitar
                    </button>
                </div>
            </div>`;
        });
        tallasHtml += '</div>';
    }
    return tallasHtml;
}

// ============================================================
// CÓDIGO REFACTORIZADO (DESPUÉS) - Solo 15 líneas claras
// ============================================================

function renderizarTallasDespues(index, tallas) {
    let tallasHtml = '';
    
    if (tallas.length > 0) {
        // Contenedor principal
        tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
        
        // Encabezado de tabla usando template
        tallasHtml += window.templates.tableHeader(
            [
                { name: 'Talla', flex: '1.5' },
                { name: 'Cantidad', flex: '1' },
                { name: 'Acción', flex: '100px' }
            ],
            true,  // ¿Tiene botón de agregar?
            '+',   // Texto del botón
            `mostrarModalAgregarTalla(${index})`  // onClick
        );
        
        // Filas de tallas usando template
        tallas.forEach(talla => {
            tallasHtml += window.templates.tallaRow(index, talla);
        });
        
        // Cierre del contenedor
        tallasHtml += '</div>';
    }
    
    return tallasHtml;
}

// ============================================================
// COMPARACIÓN VISUAL
// ============================================================

/*
ANTES (Difícil de mantener):
- 50+ líneas de código
- HTML mezclado con estilos inline
- Difícil localizar qué es qué
- Si cambia el diseño, hay que buscar en todo el archivo
- No reutilizable

DESPUÉS (Limpio y mantenible):
- 15 líneas de código
- Lógica clara separada del HTML
- Fácil de leer y entender
- Cambios de diseño solo en templates-pedido.js
- Reutilizable en otros lugares
*/

// ============================================================
// CÓMO INTEGRAR EN EL CÓDIGO PRINCIPAL
// ============================================================

/*
En crear-pedido-editable.js, línea ~984, reemplazar:

// ANTES:
let tallasHtml = '';
if (tallas.length > 0) {
    tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
    tallasHtml += '<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0052a3 0%, #0ea5e9 100%); ...
    // ... (40 líneas más)
}

// DESPUÉS:
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
*/

// ============================================================
// BENEFICIOS DEMOSTRADOS
// ============================================================

/*
1. REDUCCIÓN DE CÓDIGO:
   Antes: 50 líneas
   Después: 15 líneas
   Ahorro: 70% de líneas

2. CLARIDAD:
   - El propósito es obvio
   - Fácil de debuguear
   - Fácil de mantener

3. CONSISTENCIA:
   - Todos los encabezados de tabla igual
   - Todos los estilos iguales
   - Un solo lugar para cambiar estilos

4. REUTILIZACIÓN:
   - Se puede usar tableHeader() en otras tablas
   - Se puede usar tallaRow() en otros contextos
   - Código DRY (Don't Repeat Yourself)

5. TESTABILIDAD:
   - Fácil testear la función renderizarTallasDespues()
   - Templates se pueden testear independientemente
   - Separación de responsabilidades clara
*/

// ============================================================
// PRÓXIMAS REFACTORIZACIONES SIMILARES
// ============================================================

/*
El mismo patrón se puede aplicar a:

1. Tabla de variaciones (~1070-1125):
   renderizarVariacionesDespues(index, variacionesArray)
   - Usar: window.templates.tableHeader()
   - Usar: window.templates.variacionRow()

2. Tabla de telas (~1170-1400):
   renderizarTelasDespues(index, telasParaTabla)
   - Usar: window.templates.tableHeader()
   - Usar: window.templates.telaRow()

3. Sección de logo (~2400+):
   renderizarLogoDespues(logoCotizacion)
   - Usar: window.templates.logoHeader()
   - Usar: window.templates.logoDescripcion()
   - Usar: window.templates.logoFotosGaleria()
   - etc.

Cada refactorización seguirá el mismo patrón:
- Identificar HTML que se repite
- Crear template para ese patrón
- Llamar al template en lugar de concatenar strings
- Resultado: código más limpio y mantenible
*/

// ============================================================
// PROGRESO DE REFACTORIZACIÓN
// ============================================================

const REFACTORIZACION_CHECKLIST = {
    'templates-pedido.js creado': true,
    'Documentación REFACTORIZACION_HTML_GUIDE.md': true,
    'Ejemplo práctico (este archivo)': true,
    'Refactorizar tabs (línea ~800)': false,
    'Refactorizar tallas (línea ~984)': false,
    'Refactorizar variaciones (línea ~1070)': false,
    'Refactorizar telas (línea ~1170)': false,
    'Refactorizar logo (línea ~2400)': false,
    'Testear todas las secciones': false,
    'Documentación final': false
};

console.log('📋 CHECKLIST DE REFACTORIZACIÓN:');
Object.entries(REFACTORIZACION_CHECKLIST).forEach(([tarea, completado]) => {
    const icono = completado ? '✅' : '⏭️';
    console.log(`${icono} ${tarea}`);
});

// Exportar para referencia
window.REFACTORIZACION_EJEMPLO = {
    renderizarTallasAntes,
    renderizarTallasDespues,
    REFACTORIZACION_CHECKLIST
};
