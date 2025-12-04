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
        title: '¿Eliminar cotización completamente?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ¿Estás seguro de que deseas eliminar la cotización del cliente <strong>${cliente}</strong>?
                </p>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #92400e; font-weight: 600;">
                        ⚠️ Se eliminarán PERMANENTEMENTE:
                    </p>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.85rem; color: #92400e;">
                        <li><strong>Base de datos:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Registro de cotización</li>
                                <li>Todas las prendas relacionadas</li>
                                <li>Información de LOGO</li>
                                <li>Pedidos de producción asociados</li>
                                <li>Historial de cambios</li>
                            </ul>
                        </li>
                        <li style="margin-top: 0.5rem;"><strong>Servidor:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Carpeta: <code style="background: #fff3cd; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                <li>Todas las imágenes de prendas</li>
                                <li>Todas las imágenes de telas</li>
                                <li>Todas las imágenes de LOGO</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #ef4444; font-weight: 600;">
                    ❌ Esta acción NO se puede deshacer. Se eliminarán todos los datos y archivos.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar TODO',
        cancelButtonText: 'Cancelar',
        width: '550px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: `
                    <div style="text-align: left; color: #666;">
                        <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Por favor espera mientras se elimina:</p>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                            <li>Registros de la base de datos</li>
                            <li>Carpeta de imágenes del servidor</li>
                            <li>Todos los archivos relacionados</li>
                        </ul>
                    </div>
                `,
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                        title: '✓ Eliminado Completamente',
                        html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-weight: 600;">✅ Se eliminaron:</p>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                                    <li>Cotización de la base de datos</li>
                                    <li>Todas las prendas relacionadas</li>
                                    <li>Información de LOGO</li>
                                    <li>Pedidos de producción</li>
                                    <li>Historial de cambios</li>
                                    <li>Carpeta <code style="background: #f0f0f0; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                    <li>Todas las imágenes almacenadas</li>
                                </ul>
                            </div>
                        `,
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
                    text: 'Error al eliminar la cotización. Por favor intenta de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

/**
 * Aprueba la cotización directamente desde la tabla (sin abrir modal)
 * @param {number} cotizacionId - ID de la cotización
 */
function aprobarCotizacionEnLinea(cotizacionId) {
    // Mostrar confirmación
    Swal.fire({
        title: '¿Aprobar cotización?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ¿Estás seguro de que deseas aprobar esta cotización?
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                        ℹ️ La cotización será enviada al área de Aprobación de Cotizaciones
                    </p>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        width: '450px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Aprobando cotización...',
                html: 'Por favor espera mientras se procesa la aprobación',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación
            fetch(`/cotizaciones/${cotizacionId}/aprobar-contador`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Encontrar y remover el botón de la fila
                    const btnAprobar = document.querySelector(`button[onclick="aprobarCotizacionEnLinea(${cotizacionId})"]`);
                    if (btnAprobar) {
                        btnAprobar.style.transition = 'all 0.3s ease-out';
                        btnAprobar.style.opacity = '0';
                        btnAprobar.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            btnAprobar.remove();
                        }, 300);
                    }
                    
                    Swal.fire({
                        title: '✓ Cotización Aprobada',
                        html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                    ✅ La cotización ha sido aprobada correctamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha enviado notificación al área de Aprobación de Cotizaciones
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Aprobada por Contador
                                </p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#1e5ba8',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo aprobar la cotización',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Error al aprobar la cotización. Por favor intenta de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

