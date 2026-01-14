/**
 * Módulo Principal - Asistencia Personal
 * Inicialización y coordinación del sistema
 */

const AsistenciaPersonal = (() => {
    let registrosOriginalesPorFecha = {};
    let reporteActual = null;

    /**
     * Inicializar módulo principal
     */
    function init() {
        // Inicializar PDF Handler
        AsistenciaPDFHandler.init();
        
        // Inicializar botones de vista de reportes
        initializeReportViewButtons();
    }

    /**
     * Inicializar botones de vista de reportes
     */
    function initializeReportViewButtons() {
        const viewButtons = document.querySelectorAll('.btn-view');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.getAttribute('data-id');
                openReportDetailModal(reportId);
            });
        });
    }

    /**
     * Abrir modal de detalles del reporte
     */
    function openReportDetailModal(reportId) {
        const modal = document.getElementById('reportDetailModal');
        const modalTitle = document.getElementById('reportModalTitle');
        
        if (!modal) {
            console.error('Modal reportDetailModal no encontrado');
            return;
        }
        
        modal.style.display = 'block';
        
        fetchReportDetails(reportId, function(data) {
            if (data.success && data.reporte) {
                const reporte = data.reporte;
                reporteActual = reporte;
                
                modalTitle.textContent = `${reporte.numero_reporte} - ${reporte.nombre_reporte}`;
                
                const tabsHeader = document.getElementById('tabsHeader');
                tabsHeader.innerHTML = '';
                
                const registrosPorFecha = {};
                reporte.registros_por_persona.forEach(registro => {
                    const fecha = registro.fecha;
                    if (!registrosPorFecha[fecha]) {
                        registrosPorFecha[fecha] = [];
                    }
                    registrosPorFecha[fecha].push(registro);
                });
                
                const fechas = Object.keys(registrosPorFecha).sort();
                fechas.forEach((fecha, index) => {
                    const tabBtn = document.createElement('button');
                    tabBtn.className = 'tab-button' + (index === 0 ? ' active' : '');
                    tabBtn.setAttribute('data-fecha', fecha);
                    tabBtn.textContent = fecha;
                    tabBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const allTabs = tabsHeader.querySelectorAll('.tab-button');
                        allTabs.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        AsistenciaReportDetails.mostrarTab(fecha, registrosPorFecha[fecha]);
                    });
                    tabsHeader.appendChild(tabBtn);
                });
                
                if (fechas.length > 0) {
                    AsistenciaReportDetails.mostrarTab(fechas[0], registrosPorFecha[fechas[0]]);
                }
            } else {
                alert('Error al cargar detalles del reporte');
            }
        });
        
        // Configurar botones del modal
        setupModalButtons(reportId);
    }

    /**
     * Configurar botones del modal
     */
    function setupModalButtons(reportId) {
        const modal = document.getElementById('reportDetailModal');
        
        const closeBtn = modal.querySelector('.btn-modal-close-detail');
        if (closeBtn && !closeBtn.dataset.listenerAttached) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'none';
            });
            closeBtn.dataset.listenerAttached = true;
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        }, { once: false });
        
        const btnAusencias = document.getElementById('btnAusenciasDelDia');
        if (btnAusencias && !btnAusencias.dataset.listenerAttached) {
            btnAusencias.addEventListener('click', function(e) {
                e.preventDefault();
                AsistenciaAbsencias.cargar(reportId);
            });
            btnAusencias.dataset.listenerAttached = true;
        }
        
        const btnHoras = document.getElementById('btnHorasTrabajadas');
        if (btnHoras && !btnHoras.dataset.listenerAttached) {
            btnHoras.addEventListener('click', function(e) {
                e.preventDefault();
                AsistenciaHorasTrabajadas.mostrarVista();
            });
            btnHoras.dataset.listenerAttached = true;
        }
        
        const btnCerrar = document.getElementById('btnCerrarReporte');
        if (btnCerrar && !btnCerrar.dataset.listenerAttached) {
            btnCerrar.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'none';
            });
            btnCerrar.dataset.listenerAttached = true;
        }
        
        const btnTotalHorasExtras = document.getElementById('btnTotalHorasExtras');
        if (btnTotalHorasExtras && !btnTotalHorasExtras.dataset.listenerAttached) {
            btnTotalHorasExtras.addEventListener('click', function(e) {
                e.preventDefault();
                if (reporteActual) {
                    AsistenciaTotalHorasExtras.mostrarVista(reporteActual);
                    // Inicializar búsqueda para tabla de horas extras
                    setTimeout(() => {
                        inicializarBusquedaTotalHorasExtras();
                    }, 100);
                }
            });
            btnTotalHorasExtras.dataset.listenerAttached = true;
        }
    }

    /**
     * Obtener detalles del reporte desde la API
     */
    function fetchReportDetails(reportId, callback) {
        const url = `/asistencia-personal/reportes/${reportId}/detalles`;
        console.log('Fetching report details from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Detalles del reporte recibidos:', data);
            callback(data);
        })
        .catch(error => {
            console.error('Error al cargar detalles:', error);
            alert('Error al cargar los detalles del reporte: ' + error.message);
        });
    }

    return {
        init,
        openReportDetailModal
    };
})();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    AsistenciaPersonal.init();
});

console.log('Asistencia Personal module loaded');
