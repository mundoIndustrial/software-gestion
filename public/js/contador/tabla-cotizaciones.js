/**
 * Gestor de tabla de cotizaciones
 * Maneja acciones, menús y paginación
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeTableActions();
    initializePagination();
});

/**
 * Inicializar acciones de tabla
 */
function initializeTableActions() {
    // Manejar clics en botones de acción (delegación para soportar filas dinámicas)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.action-view-btn');
        if (!btn) return;

        e.stopPropagation();
        const accionesCell = btn.closest('.acciones-column') || btn.parentElement;
        const menu = accionesCell ? accionesCell.querySelector('.action-menu') : null;
            
        // Cerrar otros menús
        document.querySelectorAll('.action-menu.active').forEach(m => {
            if (m !== menu) {
                m.classList.remove('active');
            }
        });

        // Toggle menú actual
        if (menu) {
            menu.classList.toggle('active');
        }
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-view-btn') && !e.target.closest('.action-menu')) {
            document.querySelectorAll('.action-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
    
    // Cerrar menú al hacer clic en un item (delegación para soportar filas dinámicas)
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.action-menu-item');
        if (!item) return;

        const menu = item.closest('.action-menu');
        if (menu) {
            menu.classList.remove('active');
        }
    });
    
    // Manejar clics en celdas de la tabla
    document.querySelectorAll('.table-cell:not(.acciones-column)').forEach(cell => {
        cell.addEventListener('click', function(e) {
            // No abrir modal si se hace clic en un botón o enlace
            if (e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            const cellContent = this.querySelector('.cell-content');
            if (cellContent) {
                const content = cellContent.textContent.trim();
                const row = this.closest('.table-row');
                
                // Obtener información de la fila
                const numero = row.getAttribute('data-numero') || 'N/A';
                const cliente = row.getAttribute('data-cliente') || 'N/A';
                
                // Determinar el tipo de celda
                let titulo = 'Información';
                if (this.hasAttribute('data-estado')) {
                    titulo = 'Estado';
                } else if (this.hasAttribute('data-numero')) {
                    titulo = 'Número de Cotización';
                } else if (this.hasAttribute('data-fecha')) {
                    titulo = 'Fecha';
                } else if (this.hasAttribute('data-cliente')) {
                    titulo = 'Cliente';
                } else if (this.hasAttribute('data-asesora')) {
                    titulo = 'Asesora';
                }
                
                mostrarModalCelda(titulo, content, numero, cliente);
            }
        });
    });
}

/**
 * Mostrar modal con contenido de celda
 */
function mostrarModalCelda(titulo, contenido, numeroCotizacion, cliente) {
    // Crear modal si no existe
    let modal = document.getElementById('modalCeldaInfo');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalCeldaInfo';
        modal.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden;">
                <div style="background: linear-gradient(135deg, #1e5ba8 0%, #1a4d8f 100%); padding: 1.5rem; color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 id="modalCeldaTitulo" style="margin: 0; font-size: 1.25rem; font-weight: 700;"></h3>
                        <button onclick="cerrarModalCelda()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            ✕
                        </button>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.85rem; opacity: 0.9;">
                        <span id="modalCeldaSubtitulo"></span>
                    </div>
                </div>
                <div style="padding: 2rem;">
                    <div style="background: #f8f9fa; border-left: 4px solid #1e5ba8; padding: 1.5rem; border-radius: 6px;">
                        <p id="modalCeldaContenido" style="margin: 0; font-size: 1.1rem; color: #333; word-wrap: break-word; white-space: pre-wrap;"></p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Cerrar al hacer clic fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalCelda();
            }
        });
    }
    
    // Actualizar contenido
    document.getElementById('modalCeldaTitulo').textContent = titulo;
    document.getElementById('modalCeldaSubtitulo').textContent = `Cotización: ${numeroCotizacion} | Cliente: ${cliente}`;
    document.getElementById('modalCeldaContenido').textContent = contenido;
    
    // Mostrar modal
    modal.style.display = 'flex';
}

/**
 * Cerrar modal de celda
 */
function cerrarModalCelda() {
    const modal = document.getElementById('modalCeldaInfo');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCelda();
    }
});

/**
 * Inicializar paginación
 */
function initializePagination() {
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = this.getAttribute('data-page');
                window.location.href = `?page=${page}`;
            }
        });
    });
}

/**
 * Sincronizar scroll horizontal del header con el contenido
 */
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.table-scroll-container');
    const tableHead = document.querySelector('.table-head');
    
    if (scrollContainer && tableHead) {
        scrollContainer.addEventListener('scroll', function() {
            tableHead.style.transform = 'translateX(' + (-this.scrollLeft) + 'px)';
        });
    }
});
