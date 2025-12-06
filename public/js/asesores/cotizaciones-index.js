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
 * @param {string} tipo - 'todas', 'P', 'B', 'PB'
 */
function mostrarTipo(tipo) {
    // Determina cuál tab está activo
    const tabCotizaciones = document.getElementById('tab-cotizaciones');
    const tabBorradores = document.getElementById('tab-borradores');
    
    const esCotizacionesActivo = tabCotizaciones.style.display === 'block';
    const esBorradoresActivo = tabBorradores.style.display === 'block';
    
    // Oculta todas las secciones
    document.querySelectorAll('.seccion-tipo').forEach(sec => sec.style.display = 'none');
    
    // Mapeo de tipos a IDs de secciones
    const secciones = {
        'todas': { cot: 'seccion-todas', bor: 'seccion-bor-todas' },
        'P': { cot: 'seccion-prenda', bor: 'seccion-bor-prenda' },
        'B': { cot: 'seccion-logo', bor: 'seccion-bor-logo' },
        'PB': { cot: 'seccion-pb', bor: 'seccion-bor-pb' }
    };
    
    if (secciones[tipo]) {
        // Muestra solo la sección correspondiente al tab activo
        if (esCotizacionesActivo) {
            const cotElement = document.getElementById(secciones[tipo].cot);
            if (cotElement) cotElement.style.display = 'block';
        }
        if (esBorradoresActivo) {
            const borElement = document.getElementById(secciones[tipo].bor);
            if (borElement) borElement.style.display = 'block';
        }
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
