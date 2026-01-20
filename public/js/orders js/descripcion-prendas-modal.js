/**
 * Manejo de descripción de prendas en modal
 * Intercepta el contenido y lo renderiza correctamente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Interceptar el evento de apertura del modal
    const originalOpenCellModal = window.openCellModal;
    
    if (originalOpenCellModal) {
        window.openCellModal = function(params) {
            // Si es descripcion_prendas, obtener contenido desde data-full-content
            if (params.column === 'descripcion_prendas') {
                const cell = document.querySelector(`[data-pedido="${params.orderId}"]`);
                if (cell) {
                    const descripcionDiv = cell.querySelector('.descripcion-preview');
                    if (descripcionDiv) {
                        const encodedContent = descripcionDiv.getAttribute('data-full-content');
                        if (encodedContent) {
                            // Decodificar desde base64
                            const decodedContent = atob(encodedContent);
                            params.content = decodedContent;
                            console.log('📦 Contenido de descripcion_prendas decodificado:', decodedContent.substring(0, 100));
                        }
                    }
                }
            }
            
            // Llamar a la función original
            return originalOpenCellModal.call(this, params);
        };
    }
    
    // También interceptar directamente en el modal input
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const modal = mutation.target;
                if (modal.classList.contains('active') && modal.id === 'cell-modal') {
                    // Modal se abrió, verificar si es descripcion_prendas
                    const input = modal.querySelector('#cell-input');
                    const column = modal.querySelector('[data-column]')?.getAttribute('data-column');
                    
                    if (column === 'descripcion_prendas' && input && input.value.trim() === '') {
                        // Intentar obtener el contenido desde la tabla
                        const table = document.querySelector('.modern-table');
                        if (table) {
                            // Buscar la fila activa
                            const activeRow = table.querySelector('tr.active') || table.querySelector('tr[data-active="true"]');
                            if (activeRow) {
                                const descCell = activeRow.querySelector('[data-column="descripcion_prendas"]');
                                if (descCell) {
                                    const descripcionDiv = descCell.querySelector('.descripcion-preview');
                                    if (descripcionDiv) {
                                        const encodedContent = descripcionDiv.getAttribute('data-full-content');
                                        if (encodedContent) {
                                            const decodedContent = atob(encodedContent);
                                            input.value = decodedContent;
                                            console.log(' Contenido de descripcion_prendas restaurado en modal');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    });
    
    // Observar cambios en el modal
    const modal = document.getElementById('cell-modal');
    if (modal) {
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }
});

// Función para renderizar descripción en el modal con HTML
function renderDescripcionPrendasEnModal(content) {
    const modal = document.getElementById('cell-modal');
    if (!modal) return;
    
    const input = modal.querySelector('#cell-input');
    if (!input) return;
    
    // Crear un div para mostrar el contenido formateado
    let displayDiv = modal.querySelector('.descripcion-display');
    if (!displayDiv) {
        displayDiv = document.createElement('div');
        displayDiv.className = 'descripcion-display';
        displayDiv.style.cssText = `
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
            font-size: 13.4px;
            line-height: 1.4;
            background: #f9f9f9;
        `;
        input.parentElement.appendChild(displayDiv);
    }
    
    // Renderizar el contenido
    displayDiv.innerHTML = content
        .replace(/\n\n/g, '</div><div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0;">')
        .replace(/^/, '<div>')
        .replace(/$/, '</div>')
        .replace(/PRENDA (\d+):/gi, '<strong>PRENDA $1:</strong>')
        .replace(/Prenda (\d+):/g, '<strong>Prenda $1:</strong>')
        .replace(/Color:/gi, '<strong>Color:</strong>')
        .replace(/Tela:/gi, '<strong>Tela:</strong>')
        .replace(/Manga:/gi, '<strong>Manga:</strong>')
        .replace(/Descripción:/gi, '<strong>Descripción:</strong>')
        .replace(/DESCRIPCION:/gi, '<strong>DESCRIPCION:</strong>')
        .replace(/Bolsillos:/gi, '<strong>Bolsillos:</strong>')
        .replace(/Reflectivo:/gi, '<strong>Reflectivo:</strong>')
        .replace(/Tallas:/gi, '<strong style="color: #d32f2f;">Tallas:</strong>')
        .replace(/Talla:/gi, '<strong style="color: #d32f2f;">Talla:</strong>');
    
    console.log(' Descripción renderizada en modal');
}
