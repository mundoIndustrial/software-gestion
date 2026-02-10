/**
 * Inicialización del Sistema de Cotizaciones
 * 
 * Este archivo actúa como orquestador central para asegurar que todos los
 * módulos se carguen en el orden correcto y estén disponibles cuando se necesiten.
 */

(function() {
    'use strict';

    // Verificar que todos los módulos necesarios estén disponibles
    const requiredModules = [
        { name: 'window.routes', description: 'Rutas Laravel' },
        { name: 'window.tipoCotizacionGlobal', description: 'Tipo de cotización global' },
        { name: 'agregarProductoFriendly', description: 'Función agregarProductoFriendly' },
        { name: 'actualizarSelectTallas', description: 'Función actualizarSelectTallas' },
    ];

    // Función auxiliar para verificar si existe una propiedad anidada
    function propertyExists(obj, path) {
        return path.split('.').every(prop => !!(obj = obj?.[prop]));
    }

    // Verificar módulos con timeout
    let verificacionesCompletadas = 0;
    let maxIntentos = 0;
    const maxIntentosPermitidos = 50; // 5 segundos con 100ms de espera

    function verificarModulos() {
        maxIntentos++;
        let todosDisponibles = true;

        for (const modulo of requiredModules) {
            if (modulo.name.includes('.')) {
                // Propiedad anidada como "window.routes"
                if (!propertyExists(window, modulo.name)) {
                    todosDisponibles = false;

                }
            } else {
                // Función global
                if (typeof window[modulo.name] !== 'function') {
                    todosDisponibles = false;

                }
            }
        }

        if (todosDisponibles) {

            inicializarFormulario();
        } else if (maxIntentos < maxIntentosPermitidos) {
            // Reintentar después de 100ms
            setTimeout(verificarModulos, 100);
        } else {


        }
    }

    // Inicializar cuando el DOM esté listo
    function inicializarFormulario() {


        // Configuración global
        if (typeof window.routes === 'object') {

        }

        if (typeof window.tipoCotizacionGlobal === 'string') {

        }

        // 🔄 AGREGAR EVENT LISTENERS PARA ACTUALIZAR RESUMEN EN TIEMPO REAL
        const camposAObservar = [
            'cliente',
            'fechaActual',
            'descripcion_logo',
            'observaciones_tecnicas'
        ];

        camposAObservar.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                // Input/change para cambios
                campo.addEventListener('input', () => {

                    if (typeof actualizarResumenFriendly === 'function') actualizarResumenFriendly();
                });
                
                // Change para inputs de fecha/select
                campo.addEventListener('change', () => {

                    if (typeof actualizarResumenFriendly === 'function') actualizarResumenFriendly();
                });
            }
        });

        // Observar cambios en técnicas seleccionadas
        const tecnicasContainer = document.getElementById('tecnicas_seleccionadas');
        if (tecnicasContainer) {
            const observer = new MutationObserver(() => {

                if (typeof actualizarResumenFriendly === 'function') actualizarResumenFriendly();
            });
            observer.observe(tecnicasContainer, { childList: true, subtree: true });
        }

        // Observar adición/eliminación de productos
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            const observer = new MutationObserver(() => {

                setTimeout(() => {
                    if (typeof actualizarResumenFriendly === 'function') actualizarResumenFriendly();
                }, 100);
            });
            observer.observe(formSection, { childList: true, subtree: true });
        }

        if (typeof window.__setupPasteImagenesCotizacion !== 'function') {
            window.__setupPasteImagenesCotizacion = function() {
                let lastContainer = null;

                function getFileInputFromContainer(container) {
                    if (!container) return null;
                    if (container.matches && container.matches('input[type="file"]')) return container;
                    const input = container.querySelector ? container.querySelector('input[type="file"]') : null;
                    if (!input) return null;
                    const accept = (input.getAttribute('accept') || '').toLowerCase();
                    if (accept && !accept.includes('image')) return null;
                    return input;
                }

                function findContainerFromElement(el) {
                    if (!el || !el.closest) return null;
                    const selectors = [
                        'label',
                        '#drop_zone_imagenes',
                        '.drop-zone',
                        '[class*="Dropzone"]',
                        '[class*="dropzone"]',
                        '[class*="dImagenesDropzone"]',
                        '[class*="dImagenesCompartidasDropzone"]'
                    ];
                    for (const sel of selectors) {
                        const c = el.closest(sel);
                        if (c && getFileInputFromContainer(c)) return c;
                    }
                    const label = el.closest('label');
                    if (label && getFileInputFromContainer(label)) return label;
                    return null;
                }

                function setLastContainerFromEvent(e) {
                    const target = e.target;
                    const container = findContainerFromElement(target) || (target && target.querySelector ? (getFileInputFromContainer(target) ? target : null) : null);
                    if (container) lastContainer = container;
                }

                document.addEventListener('click', setLastContainerFromEvent, true);
                document.addEventListener('focusin', setLastContainerFromEvent, true);

                document.addEventListener('paste', async (e) => {
                    const cd = e.clipboardData;
                    if (!cd || !cd.items) return;

                    const items = Array.from(cd.items);
                    const imageItem = items.find(it => it && it.type && it.type.startsWith('image/'));
                    if (!imageItem) return;

                    const file = imageItem.getAsFile();
                    if (!file) return;

                    const active = document.activeElement;
                    const container = findContainerFromElement(active) || lastContainer;
                    const input = getFileInputFromContainer(container);
                    if (!input) return;

                    e.preventDefault();

                    const dt = new DataTransfer();
                    if (input.multiple && input.files && input.files.length > 0) {
                        Array.from(input.files).forEach(f => dt.items.add(f));
                    }
                    dt.items.add(file);
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }, true);
            };
        }

        window.__setupPasteImagenesCotizacion();

        // Aquí puedes agregar más inicializaciones específicas

    }

    // Iniciar verificación cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', verificarModulos);
    } else {
        verificarModulos();
    }
})();
