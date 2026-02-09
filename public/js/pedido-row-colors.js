/**
 * Script: Aplicar colores condicionales a filas de pedidos
 * Basado en el atributo data-estado de cada fila
 * Compatible con: bodeguero, costura-bodega, epp-bodega
 */

(function() {
    'use strict';

    /**
     * Configuración de colores para selects
     * (igual que en bodega-pedidos.js)
     */
    const COLORES_ESTADOS = {
        'entregado': { bg: '#dbeafe', text: '#0c4a6e' },  // Azul muy claro
        'pendiente': { bg: '#fef3c7', text: '#78350f' },  // Amarillo muy claro
        'anulado': { bg: '#fee2e2', text: '#991b1b' },    // Rojo muy claro
        'anulada': { bg: '#fee2e2', text: '#991b1b' },    // Rojo muy claro
        'retrasado': { bg: '#fca5a5', text: '#7f1d1d' }   // Rojo más intenso
    };

    /**
     * Obtener la clase CSS según el estado
     */
    function obtenerClaseEstado(estado) {
        if (!estado) return null;
        
        const estadoNormalizado = estado.trim().toLowerCase();
        
        // Mapeo de estados a clases CSS
        const estadoClasesMap = {
            'entregado': 'estado-entregado',
            'pendiente': 'estado-pendiente',
            'anulado': 'estado-anulado',
            'anulada': 'estado-anulado',
            'retrasado': 'estado-retrasado',
        };
        
        return estadoClasesMap[estadoNormalizado] || null;
    }

    /**
     * Aplicar color al select de estado
     */
    function aplicarColorAlSelect(select, estado) {
        if (!select) return;
        
        const estadoNormalizado = estado.trim().toLowerCase();
        
        // Limpiar estilos previos
        select.style.backgroundColor = '';
        select.style.color = '';
        
        // Aplicar nuevo color si existe
        if (COLORES_ESTADOS[estadoNormalizado]) {
            const colores = COLORES_ESTADOS[estadoNormalizado];
            select.style.backgroundColor = colores.bg;
            select.style.color = colores.text;
        }
    }

    /**
     * Aplicar color condicional a una fila individual
     * Busca el estado en: 1) data-estado del TR, 2) select dentro del TR
     */
    function aplicarColorFila(fila) {
        if (!fila || !fila.classList.contains('pedido-row')) return;

        // No aplicar color a filas separadores de pedido
        if (fila.classList.contains('pedido-header')) return;

        // Obtener el estado: primero del atributo data-estado, luego del selector
        let estado = fila.getAttribute('data-estado');
        
        // Buscar el select de estado
        const selectEstado = fila.querySelector('.estado-select, .estado-select-readonly');
        
        // Si no hay data-estado, obtener del select
        if (!estado && selectEstado) {
            estado = selectEstado.value;
            // Guardar en data-estado para futuros usos
            fila.setAttribute('data-estado', estado);
        }

        const claseEstado = obtenerClaseEstado(estado);

        // Limpiar clases de estados anteriores
        fila.classList.remove('estado-entregado', 'estado-pendiente', 'estado-anulado', 'estado-retrasado');

        // Aplicar nueva clase si existe
        if (claseEstado) {
            fila.classList.add(claseEstado);
        }
        
        // Aplicar color al select y a las celdas (para readonly)
        if (selectEstado && estado) {
            aplicarColorAlSelect(selectEstado, estado);
            
            // Aplicar color de fondo a las celdas
            const celdas = fila.querySelectorAll('td');
            const estadoNormalizado = estado.trim().toLowerCase();
            
            // Limpiar colores anteriores de todas las celdas
            celdas.forEach(celda => {
                celda.style.backgroundColor = '';
            });
            
            // Aplicar nuevo color
            if (COLORES_ESTADOS[estadoNormalizado]) {
                const colores = COLORES_ESTADOS[estadoNormalizado];
                celdas.forEach(celda => {
                    // No colorear la celda que contiene el select
                    if (!celda.querySelector('.estado-select, .estado-select-readonly')) {
                        celda.style.backgroundColor = colores.bg;
                    }
                });
            }
        }
    }

    /**
     * Aplicar color a todas las filas de pedidos
     */
    function aplicarColoresTodosFilas() {
        const filas = document.querySelectorAll('.pedido-row:not(.pedido-header)');
        filas.forEach(fila => {
            // Sincronizar estado del selector a data-estado si no existe
            const selectEstado = fila.querySelector('.estado-select, .estado-select-readonly');
            if (selectEstado && !fila.getAttribute('data-estado')) {
                fila.setAttribute('data-estado', selectEstado.value);
            }
            
            // Aplicar color (esto ahora también colorea las celdas)
            aplicarColorFila(fila);
        });
    }

    /**
     * Observar cambios en data-estado (para actualizaciones en tiempo real)
     */
    function observarCambios() {
        const filas = document.querySelectorAll('.pedido-row:not(.pedido-header)');
        
        filas.forEach(fila => {
            // Crear un observer para detectar cambios en atributos data-*
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'data-estado') {
                        aplicarColorFila(fila);
                    }
                });
            });

            // Configurar para observar cambios de atributos
            observer.observe(fila, {
                attributes: true,
                attributeFilter: ['data-estado']
            });
        });
    }

    /**
     * Observar cambios en selectores de estado (si existe)
     * Funciona con .estado-select y .estado-select-readonly
     */
    function observarCambiosSelectores() {
        document.addEventListener('change', function(e) {
            // Si es un selector de estado
            if (e.target.classList.contains('estado-select') || 
                e.target.classList.contains('estado-select-readonly')) {
                
                const select = e.target;
                const nuevoEstado = select.value;
                const fila = select.closest('tr');
                
                if (!fila || !fila.classList.contains('pedido-row')) return;
                
                console.log('[📝 Cambio de Estado] Estado:', nuevoEstado);
                
                // Actualizar data-estado
                fila.setAttribute('data-estado', nuevoEstado);
                
                // Aplicar color (esto colorea select, fila y celdas)
                aplicarColorFila(fila);
            }
        });
    }

    /**
     * Observar cambios en el DOM (filas agregadas dinámicamente)
     */
    function observarMutacionesDOM() {
        const tableBody = document.getElementById('pedidosTableBody');
        if (!tableBody) return;

        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Nuevas filas fueron agregadas
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('pedido-row')) {
                            aplicarColorFila(node);
                        }
                    });
                }
            });
        });

        observer.observe(tableBody, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Inicialización cuando el DOM está listo
     */
    function inicializar() {
        console.log('[🎨 Colores Pedidos] Inicializando sistema de colores...');
        
        // Aplicar colores a filas existentes
        aplicarColoresTodosFilas();
        
        const filasColoreadas = document.querySelectorAll('.pedido-row[class*="estado-"]').length;
        console.log('[🎨 Colores Pedidos] Filas coloreadas:', filasColoreadas);

        // Observar cambios en datos-estado
        observarCambios();

        // Observar cambios en selectores
        observarCambiosSelectores();

        // Observar mutaciones del DOM
        observarMutacionesDOM();
        
        console.log('[🎨 Colores Pedidos] Sistema inicializado correctamente');
    }

    /**
     * Ejecutar cuando el documento esté listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

    // Exponer funciones globales para uso externo si es necesario
    window.aplicarColorFila = aplicarColorFila;
    window.aplicarColoresTodosPedidosFilas = aplicarColoresTodosFilas;
})();
