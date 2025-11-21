// ===== FUNCIONES PARA MODAL DE COTIZACIÓN =====

/**
 * Abre el modal de detalle de cotización
 * @param {number} cotizacionId - ID de la cotización
 */
function openCotizacionModal(cotizacionId) {
    console.log('🔄 Cargando cotización:', cotizacionId);
    
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('cotizacionModal').style.display = 'flex';
            
            // Actualizar encabezado del modal
            const row = document.querySelector(`tr:has(button[onclick*="${cotizacionId}"])`);
            if (row) {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 4) {
                    document.getElementById('modalHeaderNumber').textContent = cells[0].textContent.trim();
                    document.getElementById('modalHeaderDate').textContent = cells[1].textContent.trim();
                    document.getElementById('modalHeaderClient').textContent = cells[2].textContent.trim();
                    document.getElementById('modalHeaderAdvisor').textContent = cells[3].textContent.trim();
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cierra el modal de cotización
 */
function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal al hacer clic fuera del contenido
 */
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

/**
 * Cierra el modal al presionar ESC
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cotizacionModal');
        if (modal && modal.style.display === 'flex') {
            closeCotizacionModal();
        }
    }
});

/**
 * Elimina una cotización con confirmación
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function eliminarCotizacion(cotizacionId, cliente) {
    // Mostrar confirmación con SweetAlert
    Swal.fire({
        title: '¿Eliminar cotización?',
        html: `<p style="margin: 0; font-size: 0.95rem; color: #4b5563;">¿Estás seguro de que deseas eliminar la cotización del cliente <strong>${cliente}</strong>?</p><p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #ef4444;"><strong>⚠️ Esta acción no se puede deshacer.</strong></p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceder con la eliminación
            fetch(`/contador/cotizacion/${cotizacionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Éxito',
                        text: 'La cotización ha sido eliminada correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#1e5ba8'
                    }).then(() => {
                        // Recargar la página
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar la cotización',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al eliminar la cotización',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}
