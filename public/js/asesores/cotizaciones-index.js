/**
 * Cotizaciones Index - Funcionalidad de UI
 * Maneja tabs, filtrado, navegación entre secciones
 */

/**
 * Alterna entre tabs de Cotizaciones y Borradores
 * @param {string} tab - 'cotizaciones' o 'borradores'
 */
function mostrarTab(tab) {
    document.getElementById('tab-cotizaciones').style.display = 'none';
    document.getElementById('tab-borradores').style.display = 'none';
    document.getElementById('tab-' + tab).style.display = 'block';
}

/**
 * Muestra secciones según el tipo de cotización
 * @param {string} tipo - 'todas', 'P', 'L', 'PL'
 */
function mostrarTipo(tipo) {
    console.log('🎯 mostrarTipo() llamado con tipo:', tipo);
    
    // Actualizar estado visual de las pastillas
    const botones = document.querySelectorAll('.cotizacion-tab-btn');
    console.log('🔘 Botones encontrados:', botones.length);
    
    botones.forEach(btn => {
        if (btn.getAttribute('data-tipo') === tipo) {
            // Activar botón
            btn.classList.add('active');
            console.log('✅ Botón activado:', tipo);
        } else {
            // Desactivar botón
            btn.classList.remove('active');
            console.log('⚪ Botón desactivado:', btn.getAttribute('data-tipo'));
        }
    });
    
    // Determina cuál tab está activo
    const tabCotizaciones = document.getElementById('tab-cotizaciones');
    const tabBorradores = document.getElementById('tab-borradores');
    
    console.log('📍 Tab Cotizaciones encontrado:', !!tabCotizaciones);
    console.log('📍 Tab Borradores encontrado:', !!tabBorradores);
    
    // Verificar el display actual
    if (tabCotizaciones) {
        console.log('📊 Tab Cotizaciones display:', window.getComputedStyle(tabCotizaciones).display);
    }
    if (tabBorradores) {
        console.log('📊 Tab Borradores display:', window.getComputedStyle(tabBorradores).display);
    }
    
    const esCotizacionesActivo = tabCotizaciones && window.getComputedStyle(tabCotizaciones).display === 'block';
    const esBorradoresActivo = tabBorradores && window.getComputedStyle(tabBorradores).display === 'block';
    
    console.log('✅ Cotizaciones activo:', esCotizacionesActivo);
    console.log('✅ Borradores activo:', esBorradoresActivo);
    
    // Si ninguno está activo, mostrar cotizaciones por defecto
    if (!esCotizacionesActivo && !esBorradoresActivo) {
        console.warn('⚠️ Ningún tab activo, mostrando cotizaciones por defecto');
        if (tabCotizaciones) {
            tabCotizaciones.style.display = 'block';
        }
    }
    
    // Oculta todas las secciones
    const seccionesTodas = document.querySelectorAll('.seccion-tipo');
    console.log('🔍 Secciones encontradas:', seccionesTodas.length);
    seccionesTodas.forEach(sec => sec.style.display = 'none');
    
    // Mapeo de tipos a IDs de secciones
    const secciones = {
        'todas': { cot: 'seccion-todas', bor: 'seccion-bor-todas' },
        'P': { cot: 'seccion-prenda', bor: 'seccion-bor-prenda' },
        'L': { cot: 'seccion-logo', bor: 'seccion-bor-logo' },
        'PL': { cot: 'seccion-combinada', bor: 'seccion-bor-combinada' },
        'RF': { cot: 'seccion-rf', bor: 'seccion-bor-rf' }
    };
    
    console.log('🗺️ Secciones mapeadas:', secciones);
    console.log('🔎 Tipo solicitado existe en mapeo:', !!secciones[tipo]);
    
    if (secciones[tipo]) {
        console.log('✅ Mostrando sección para tipo:', tipo);
        // Muestra solo la sección correspondiente al tab activo
        if (esCotizacionesActivo) {
            const cotElement = document.getElementById(secciones[tipo].cot);
            console.log('🔍 Elemento cotizaciones encontrado:', !!cotElement, 'ID:', secciones[tipo].cot);
            if (cotElement) {
                cotElement.style.display = 'block';
                console.log('✅ Mostrando cotizaciones:', secciones[tipo].cot);
            } else {
                console.error('❌ Elemento cotizaciones NO encontrado:', secciones[tipo].cot);
            }
        }
        if (esBorradoresActivo) {
            const borElement = document.getElementById(secciones[tipo].bor);
            console.log('🔍 Elemento borradores encontrado:', !!borElement, 'ID:', secciones[tipo].bor);
            if (borElement) {
                borElement.style.display = 'block';
                console.log('✅ Mostrando borradores:', secciones[tipo].bor);
            } else {
                console.error('❌ Elemento borradores NO encontrado:', secciones[tipo].bor);
            }
        }
    } else {
        console.error('❌ Tipo no encontrado en mapeo:', tipo);
    }
}

/**
 * Elimina un borrador con confirmación SweetAlert
 * @param {number} id - ID del borrador a eliminar
 */
function eliminarBorrador(id) {
    Swal.fire({
        title: '¿Eliminar borrador?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal-custom-popup',
            title: 'swal-custom-title',
            confirmButton: 'swal-custom-confirm',
            cancelButton: 'swal-custom-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/asesores/cotizaciones/${id}/borrador`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animación de eliminación
                    const rows = document.querySelectorAll('table tbody tr');
                    rows.forEach(row => {
                        const cell = row.querySelector(`a[onclick*="eliminarBorrador(${id})"]`);
                        if (cell) {
                            row.style.transition = 'opacity 0.3s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        }
                    });
                    
                    // Toast de éxito
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '¡Borrador eliminado!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        },
                        customClass: {
                            popup: 'swal-toast-popup',
                            title: 'swal-toast-title'
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar el borrador',
                        icon: 'error',
                        confirmButtonColor: '#1e40af',
                        customClass: {
                            popup: 'swal-custom-popup',
                            title: 'swal-custom-title',
                            confirmButton: 'swal-custom-confirm'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al eliminar el borrador',
                    icon: 'error',
                    confirmButtonColor: '#1e40af',
                    customClass: {
                        popup: 'swal-custom-popup',
                        title: 'swal-custom-title',
                        confirmButton: 'swal-custom-confirm'
                    }
                });
            });
        }
    });
}

/**
 * Elimina una cotización enviada con confirmación SweetAlert
 * @param {number} id - ID de la cotización a eliminar
 */
function eliminarCotizacion(id) {
    console.log('🗑️ eliminarCotizacion() llamado con id:', id);
    Swal.fire({
        title: '¿Eliminar cotización?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal-custom-popup',
            title: 'swal-custom-title',
            confirmButton: 'swal-custom-confirm',
            cancelButton: 'swal-custom-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('✅ Usuario confirmó eliminación, enviando DELETE a /asesores/cotizaciones/' + id);
            fetch(`/asesores/cotizaciones/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                console.log('📡 Respuesta recibida, status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 Datos de respuesta:', data);
                if (data.success) {
                    console.log('✅ Eliminación exitosa, removiendo fila de la tabla');
                    // Animación de eliminación
                    const rows = document.querySelectorAll('table tbody tr');
                    console.log('🔍 Total de filas encontradas:', rows.length);
                    let rowRemoved = false;
                    rows.forEach(row => {
                        if (!rowRemoved) {
                            const cell = row.querySelector(`a[onclick*="eliminarCotizacion(${id})"]`);
                            if (cell) {
                                console.log('🎯 Fila encontrada, animando eliminación');
                                row.style.transition = 'opacity 0.3s ease';
                                row.style.opacity = '0';
                                setTimeout(() => {
                                    row.remove();
                                    console.log('✅ Fila removida del DOM');
                                }, 300);
                                rowRemoved = true;
                            }
                        }
                    });
                    
                    // Toast de éxito
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '¡Cotización eliminada!',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        },
                        customClass: {
                            popup: 'swal-toast-popup',
                            title: 'swal-toast-title'
                        }
                    });
                } else {
                    console.error('❌ Error en respuesta:', data.message);
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar la cotización',
                        icon: 'error',
                        confirmButtonColor: '#1e40af',
                        customClass: {
                            popup: 'swal-custom-popup',
                            title: 'swal-custom-title',
                            confirmButton: 'swal-custom-confirm'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('❌ Error en fetch:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al eliminar la cotización',
                    icon: 'error',
                    confirmButtonColor: '#1e40af',
                    customClass: {
                        popup: 'swal-custom-popup',
                        title: 'swal-custom-title',
                        confirmButton: 'swal-custom-confirm'
                    }
                });
            });
        }
    });
}

/**
 * Filtra las filas de la tabla actual en tiempo real
 * @param {string} searchTerm - Término de búsqueda
 */
function filtrarTablaEnVista(searchTerm) {
    const searchTermLower = searchTerm.toLowerCase();
    const tables = document.querySelectorAll('table tbody');
    
    tables.forEach(tbody => {
        const rows = tbody.querySelectorAll('tr');
        let rowsVisibles = 0;
        
        rows.forEach(row => {
            // Obtener el contenido de todas las celdas
            const cells = row.querySelectorAll('td');
            let rowText = '';
            cells.forEach(cell => {
                rowText += cell.textContent + ' ';
            });
            
            // Comparar con término de búsqueda
            const matches = rowText.toLowerCase().includes(searchTermLower);
            row.style.display = matches ? '' : 'none';
            
            if (matches) rowsVisibles++;
        });
        
        // Mostrar mensaje si no hay resultados
        const parent = tbody.closest('div[id^="tab-contenedor-"]');
        if (parent) {
            let emptyMsg = parent.querySelector('.empty-message');
            if (rowsVisibles === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-message';
                    emptyMsg.style.cssText = 'background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;';
                    emptyMsg.innerHTML = '<p style="margin: 0; color: #666;">🔍 No se encontraron resultados para: <strong>' + searchTerm + '</strong></p>';
                    parent.insertBefore(emptyMsg, tbody.closest('div'));
                }
                emptyMsg.style.display = 'block';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        }
    });
}

/**
 * Inicialización al cargar el DOM
 */
document.addEventListener('DOMContentLoaded', () => {
    // Obtener parámetro 'tab' de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    // Si viene con tab=borradores, mostrar ese tab
    if (tabParam === 'borradores') {
        mostrarTab('borradores');
        // Mostrar la primera sección de borradores (Todas)
        const seccionBorTodas = document.getElementById('seccion-bor-todas');
        if (seccionBorTodas) {
            seccionBorTodas.style.display = 'block';
        }
    } else {
        // Mostrar la sección inicial de cotizaciones (Todas)
        const seccionTodas = document.getElementById('seccion-todas');
        if (seccionTodas) {
            seccionTodas.style.display = 'block';
        }
    }
    
    // Agregar listener para filtrado en tiempo real del buscador
    const buscadorInput = document.getElementById('buscador');
    if (buscadorInput) {
        buscadorInput.addEventListener('input', function() {
            filtrarTablaEnVista(this.value);
        });
        
        // Si hay un término de búsqueda en la URL, mostrarlo en el input y filtrar
        const searchParam = urlParams.get('search');
        if (searchParam) {
            buscadorInput.value = decodeURIComponent(searchParam);
            // Nota: El filtrado servidor ya está aplicado, esto es solo para re-filtrar en la vista si es necesario
        }
    }
    
    // Mostrar/ocultar botón de limpiar filtros según estado
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    if (btnLimpiar) {
        setInterval(() => {
            if (typeof filtroEmbudo !== 'undefined' && Object.keys(filtroEmbudo.filtrosActivos).length > 0) {
                btnLimpiar.style.display = 'flex';
            } else {
                btnLimpiar.style.display = 'none';
            }
        }, 100);
    }
});
