/**
 * Entregas Talleres Module - Detalle JS
 */

document.addEventListener('DOMContentLoaded', function() {
    // Logic for Search View (Index)
    const searchInput = document.getElementById('main-search');
    const nextBtn = document.querySelector('.btn-primary');

    if (searchInput && nextBtn) {
        searchInput.addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                nextBtn.style.background = '#2450ef';
                nextBtn.style.opacity = '1';
            } else {
                nextBtn.style.background = '#94a3b8';
                nextBtn.style.opacity = '0.7';
            }
        });
    }
});

function promptDelivery(talla, disponible, genero, color, safeId) {
    if (disponible <= 0) {
        Swal.fire({
            title: 'No hay más unidades',
            text: 'Ya se han entregado todas las unidades de esta talla.',
            icon: 'warning',
            confirmButtonColor: '#2450ef'
        });
        return;
    }

    Swal.fire({
        title: `Entrega Talla ${talla}`,
        text: `¿Cuántas unidades vas a entregar? (Máximo ${disponible})`,
        input: 'number',
        inputValue: '',
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2450ef',
        preConfirm: (value) => {
            if (!value || value < 1 || value > disponible) {
                Swal.showValidationMessage(`Ingresa una cantidad válida entre 1 y ${disponible}`);
            }
            return value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            registrarEntrega(talla, parseInt(result.value), genero, color, safeId);
        }
    });
}

async function registrarEntrega(talla, cantidad, genero, color, safeId) {
    const container = document.getElementById('recibo-data');
    const reciboId = container.dataset.id;
    const esParcial = container.dataset.parcial;
    const routeRegistrar = container.dataset.routeRegistrar;

    try {
        const response = await fetch(routeRegistrar, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                recibo_id: reciboId,
                es_parcial: esParcial,
                talla: talla,
                cantidad: cantidad,
                genero: genero,
                color: color
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.completado) {
                Swal.fire({
                    title: '¡Recibo Completado!',
                    text: 'Todas las tallas han sido entregadas correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#2450ef'
                });
            } else {
                Swal.fire({
                    title: '¡Registrado!',
                    text: 'La entrega se guardó correctamente.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            // Actualizar UI localmente usando el safeId
            const deliveredEl = document.getElementById(`delivered-${safeId}`);
            const itemEl = document.getElementById(`talla-item-${safeId}`);
            const statusContainer = document.getElementById(`status-container-${safeId}`);

            if (deliveredEl && itemEl && statusContainer) {
                const currentDelivered = parseInt(deliveredEl.innerText) || 0;
                const totalText = deliveredEl.nextElementSibling.innerText;
                const total = parseInt(totalText.replace(/[^\d]/g, '')) || 0;
                
                const newDelivered = currentDelivered + cantidad;
                deliveredEl.innerText = newDelivered;
                
                if (newDelivered >= total) {
                    itemEl.classList.add('completed');
                    statusContainer.innerHTML = 'COMPLETADO';
                    
                    const btnAdd = itemEl.querySelector('.btn-add');
                    if (btnAdd) {
                        btnAdd.outerHTML = `
                            <div class="btn-completed">
                                <span class="material-symbols-rounded">check</span>
                            </div>
                        `;
                    }
                } else {
                    const disponiblesEl = document.getElementById(`disponibles-${safeId}`);
                    if (disponiblesEl) {
                        disponiblesEl.innerText = total - newDelivered;
                    } else {
                        statusContainer.innerHTML = `<span id="disponibles-${safeId}">${total - newDelivered}</span> DISPONIBLES`;
                    }
                }
            }

        } else {
            Swal.fire('Error', data.message || 'No se pudo registrar la entrega', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Ocurrió un error en la comunicación con el servidor', 'error');
    }
}

async function loadHistorial() {
    const container = document.getElementById('historial-items-container');
    const dataContainer = document.getElementById('recibo-data');
    const reciboId = dataContainer.dataset.id;
    const esParcial = dataContainer.dataset.parcial;
    const routeHistorialBase = dataContainer.dataset.routeHistorial;

    container.innerHTML = '<div style="text-align:center; padding: 20px;">Cargando...</div>';
    
    try {
        const response = await fetch(`${routeHistorialBase}?es_parcial=${esParcial}`);
        const items = await response.json();
        
        if (items.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: #666;">No hay entregas registradas</div>';
            return;
        }

        container.innerHTML = '';
        items.forEach(item => {
            const html = `
                <div class="historial-item">
                    <div class="historial-info">
                        <div class="historial-title">${item.cantidad_total} unidades</div>
                        <div class="historial-date">${item.fecha} • <b>${item.encargado}</b></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${item.detalle}</div>
                    </div>
                    <div class="historial-actions">
                        <button class="delete-historial-btn" onclick="deleteEntrega(${item.id})">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });
    } catch (error) {
        container.innerHTML = '<div style="text-align:center; color:red;">Error al cargar historial</div>';
    }
}

async function deleteEntrega(id) {
    const dataContainer = document.getElementById('recibo-data');
    const routeEliminarBase = dataContainer.dataset.routeEliminar.replace(':id', id);

    const result = await Swal.fire({
        title: '¿Eliminar entrega?',
        text: "Esta acción no se puede deshacer y el recibo dejará de estar marcado como completado si lo estaba.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(routeEliminarBase, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    title: 'Eliminado',
                    text: data.message,
                    icon: 'success',
                    timer: 1500
                });
                location.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'No se pudo eliminar la entrega', 'error');
        }
    }
}

function openHistorial() {
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('historial-modal').classList.add('show');
    loadHistorial();
}

function closeHistorial() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('historial-modal').classList.remove('show');
}
