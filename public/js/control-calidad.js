document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');

    // Búsqueda en tiempo real
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        const query = this.value;
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            // Recargar la página con el parámetro de búsqueda
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }, 500);

        clearSearch.style.display = query ? 'block' : 'none';
    });

    // Limpiar búsqueda
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        clearSearch.style.display = 'none';
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        window.location.href = url.toString();
    });

    // Escuchar eventos en tiempo real
    if (typeof window.Echo !== 'undefined') {
        window.Echo.channel('control-calidad')
            .listen('ControlCalidadUpdated', (e) => {
                console.log('Control de Calidad actualizado:', e);
                
                if (e.action === 'added') {
                    // Agregar nueva orden a la tabla correspondiente
                    agregarOrden(e.orden, e.tipo);
                } else if (e.action === 'removed') {
                    // Remover orden de la tabla
                    removerOrden(e.orden.pedido, e.tipo);
                }
            });
    }

    function agregarOrden(orden, tipo) {
        const tbody = tipo === 'pedido' 
            ? document.querySelector('#tablaPedidos tbody')
            : document.querySelector('#tablaBodega tbody');
        
        if (!tbody) return;

        // Verificar si ya existe
        const existingRow = tbody.querySelector(`tr[data-pedido="${orden.pedido}"]`);
        if (existingRow) return;

        // Crear nueva fila
        const tr = document.createElement('tr');
        tr.setAttribute('data-pedido', orden.pedido);
        tr.classList.add('new-row-animation');
        
        const estadoBadge = orden.estado ? orden.estado.toLowerCase().replace(/ /g, '-') : 'default';
        const fechaCreacion = orden.fecha_de_creacion_de_orden 
            ? new Date(orden.fecha_de_creacion_de_orden).toLocaleDateString('es-ES')
            : '-';
        const fechaControlCalidad = orden.control_de_calidad
            ? new Date(orden.control_de_calidad).toLocaleString('es-ES')
            : '-';

        tr.innerHTML = `
            <td>
                <span class="badge badge-${estadoBadge}">
                    ${orden.estado || '-'}
                </span>
            </td>
            <td>${fechaCreacion}</td>
            <td>${orden.pedido || '-'}</td>
            <td>${orden.cliente || '-'}</td>
            <td>${orden.novedades || '-'}</td>
            <td>${fechaControlCalidad}</td>
        `;

        // Insertar al inicio de la tabla
        const firstRow = tbody.querySelector('tr:not(.empty-state)');
        if (firstRow) {
            tbody.insertBefore(tr, firstRow);
        } else {
            // Si solo hay mensaje de vacío, reemplazarlo
            const emptyRow = tbody.querySelector('tr.empty-state');
            if (emptyRow) {
                emptyRow.remove();
            }
            tbody.appendChild(tr);
        }

        // Actualizar contador del footer
        actualizarContador(tipo);

        // Remover animación después de 2 segundos
        setTimeout(() => {
            tr.classList.remove('new-row-animation');
        }, 2000);
    }

    function removerOrden(pedido, tipo) {
        const tbody = tipo === 'pedido'
            ? document.querySelector('#tablaPedidos tbody')
            : document.querySelector('#tablaBodega tbody');
        
        if (!tbody) return;

        const row = tbody.querySelector(`tr[data-pedido="${pedido}"]`);
        if (row) {
            row.classList.add('remove-row-animation');
            setTimeout(() => {
                row.remove();
                
                // Si no quedan filas, mostrar mensaje vacío
                const remainingRows = tbody.querySelectorAll('tr:not(.empty-state)');
                if (remainingRows.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <p>No hay órdenes de ${tipo === 'pedido' ? 'pedidos' : 'bodega'} en Control de Calidad</p>
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }

                // Actualizar contador del footer
                actualizarContador(tipo);
            }, 300);
        }
    }

    function actualizarContador(tipo) {
        const tbody = tipo === 'pedido'
            ? document.querySelector('#tablaPedidos tbody')
            : document.querySelector('#tablaBodega tbody');
        
        const tfoot = tipo === 'pedido'
            ? document.querySelector('#tablaPedidos tfoot td')
            : document.querySelector('#tablaBodega tfoot td');
        
        if (tbody && tfoot) {
            const count = tbody.querySelectorAll('tr:not(.empty-state)').length;
            tfoot.textContent = `Total de órdenes: ${count}`;
        }
    }
});

// Estilos para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    .new-row-animation {
        animation: slideInDown 0.5s ease-out;
        background-color: #dbeafe !important;
    }

    .remove-row-animation {
        animation: slideOutUp 0.3s ease-out;
    }
`;
document.head.appendChild(style);

