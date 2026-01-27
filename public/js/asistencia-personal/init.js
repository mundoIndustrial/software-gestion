/**
 * Módulo Principal - Asistencia Personal
 * Inicialización y coordinación del sistema con Menú Hamburguesa
 */

const AsistenciaPersonal = (() => {
    let registrosOriginalesPorFecha = {};
    let reporteActual = null;
    let menuOpen = false;
    let registrosPorFechaActual = {};
    let fechasActuales = [];
    let vistaAnterior = 'registros'; // Guardar la vista anterior

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

            return;
        }
        
        modal.style.display = 'block';
        // Ocultar scroll de la página cuando se abre el modal
        document.body.style.overflow = 'hidden';
        menuOpen = false;
        toggleMenu(false);
        
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
                
                // Guardar datos para uso en el menú
                registrosPorFechaActual = registrosPorFecha;
                const fechas = Object.keys(registrosPorFecha).sort();
                fechasActuales = fechas;
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
        
        // Configurar botón cerrar
        const closeBtn = modal.querySelector('.btn-modal-close-detail');
        if (closeBtn && !closeBtn.dataset.listenerAttached) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'none';
                // Restaurar scroll de la página al cerrar el modal
                document.body.style.overflow = 'auto';
                menuOpen = false;
                toggleMenu(false);
            });
            closeBtn.dataset.listenerAttached = true;
        }
        
        // Cerrar modal al hacer click fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                // Restaurar scroll de la página al cerrar el modal
                document.body.style.overflow = 'auto';
                menuOpen = false;
                toggleMenu(false);
            }
        }, { once: false });
        
        // Configurar menú hamburguesa
        setupHamburgerMenu(reportId);
    }

    /**
     * Configurar menú hamburguesa
     */
    function setupHamburgerMenu(reportId) {
        const btnHamburger = document.getElementById('btnMenuHamburguesa');
        const navigationMenu = document.getElementById('navigationMenu');
        
        if (btnHamburger && !btnHamburger.dataset.listenerAttached) {
            btnHamburger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                menuOpen = !menuOpen;
                toggleMenu(menuOpen);
            });
            btnHamburger.dataset.listenerAttached = true;
        }
        
        // Agregar listeners a los items del menú
        if (navigationMenu) {
            const menuHorasTrabajadas = navigationMenu.querySelector('#menuHorasTrabajadas');
            const menuRegistros = navigationMenu.querySelector('#menuRegistros');
            const menuTotalHorasExtras = navigationMenu.querySelector('#menuTotalHorasExtras');
            
            if (menuHorasTrabajadas && !menuHorasTrabajadas.dataset.listenerAttached) {
                menuHorasTrabajadas.addEventListener('click', function(e) {
                    e.preventDefault();

                    
                    // Ocultar botón de descarga PDF
                    const btnDescargarPDFMenu = document.getElementById('btnDescargarPDFMenu');
                    if (btnDescargarPDFMenu) {
                        btnDescargarPDFMenu.style.display = 'none';
                    }
                    
                    // Si venimos de Total Horas Extras, restaurar vista anterior
                    const tabsContainer = document.querySelector('.tabs-container');
                    if (tabsContainer && tabsContainer.style.display === 'none') {
                        volverAHorasTrabajadas();
                    } else {
                        AsistenciaHorasTrabajadas.mostrarVista();
                    }
                    
                    vistaAnterior = 'horas-trabajadas';
                    menuOpen = false;
                    toggleMenu(false);
                });
                menuHorasTrabajadas.dataset.listenerAttached = true;
            }
            
            if (menuRegistros && !menuRegistros.dataset.listenerAttached) {
                menuRegistros.addEventListener('click', function(e) {
                    e.preventDefault();

                    
                    // Ocultar botón de descarga PDF
                    const btnDescargarPDFMenu = document.getElementById('btnDescargarPDFMenu');
                    if (btnDescargarPDFMenu) {
                        btnDescargarPDFMenu.style.display = 'none';
                    }
                    
                    // Si venimos de Total Horas Extras, restaurar vista anterior
                    const tabsContainer = document.querySelector('.tabs-container');
                    if (tabsContainer && tabsContainer.style.display === 'none') {
                        volverARegistros();
                    } else {
                        volverARegistros();
                    }
                    
                    vistaAnterior = 'registros';
                    menuOpen = false;
                    toggleMenu(false);
                });
                menuRegistros.dataset.listenerAttached = true;
            }
            
            if (menuTotalHorasExtras && !menuTotalHorasExtras.dataset.listenerAttached) {
                menuTotalHorasExtras.addEventListener('click', function(e) {
                    e.preventDefault();


                    
                    if (reporteActual) {
                        AsistenciaTotalHorasExtras.mostrarVista(reporteActual);
                        
                        // Mostrar botón de descargar PDF después de que se carga la vista
                        setTimeout(() => {
                            inicializarBusquedaTotalHorasExtras();
                            mostrarBotonDescargarPDF();
                        }, 100);
                    }
                    
                    menuOpen = false;
                    toggleMenu(false);
                });
                menuTotalHorasExtras.dataset.listenerAttached = true;
            }

            const menuAusenciasDelDia = navigationMenu.querySelector('#menuAusenciasDelDia');
            if (menuAusenciasDelDia && !menuAusenciasDelDia.dataset.listenerAttached) {
                menuAusenciasDelDia.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Ocultar botón de descarga PDF
                    const btnDescargarPDFMenu = document.getElementById('btnDescargarPDFMenu');
                    if (btnDescargarPDFMenu) {
                        btnDescargarPDFMenu.style.display = 'none';
                    }

                    // Cargar y mostrar ausencias del reporte actual
                    if (reporteActual && reporteActual.id) {
                        AsistenciaAbsencias.cargar(reporteActual.id);
                    } else {
                        alert('Error: No se puede cargar las ausencias sin un reporte válido');
                    }

                    menuOpen = false;
                    toggleMenu(false);
                });
                menuAusenciasDelDia.dataset.listenerAttached = true;
            }

            const menuMarcasFaltantes = navigationMenu.querySelector('#menuMarcasFaltantes');
            if (menuMarcasFaltantes && !menuMarcasFaltantes.dataset.listenerAttached) {
                menuMarcasFaltantes.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Ocultar botón de descarga PDF
                    const btnDescargarPDFMenu = document.getElementById('btnDescargarPDFMenu');
                    if (btnDescargarPDFMenu) {
                        btnDescargarPDFMenu.style.display = 'none';
                    }

                    // Cargar y mostrar marcas faltantes del reporte actual
                    if (reporteActual && reporteActual.id) {
                        AsistenciaMarcasFaltantes.cargar(reporteActual);
                    } else {
                        alert('Error: No se puede cargar las marcas faltantes sin un reporte válido');
                    }

                    menuOpen = false;
                    toggleMenu(false);
                });
                menuMarcasFaltantes.dataset.listenerAttached = true;
            }
        }
        
        // Cerrar menú al hacer click fuera
        document.addEventListener('click', function(e) {
            if (navigationMenu && btnHamburger && 
                !navigationMenu.contains(e.target) && 
                !btnHamburger.contains(e.target) && 
                menuOpen) {
                menuOpen = false;
                toggleMenu(false);
            }
        });
    }

    /**
     * Toggle menú hamburguesa
     */
    function toggleMenu(show) {
        const btnHamburger = document.getElementById('btnMenuHamburguesa');
        const navigationMenu = document.getElementById('navigationMenu');
        
        if (navigationMenu) {
            if (show) {
                navigationMenu.style.display = 'flex';
                if (btnHamburger) {
                    btnHamburger.classList.add('active');
                }
            } else {
                navigationMenu.style.display = 'none';
                if (btnHamburger) {
                    btnHamburger.classList.remove('active');
                }
            }
        }
    }

    /**
     * Volver a registros
     */
    function volverARegistros() {
        // Limpiar y reconstruir la estructura HTML estándar
        const tabContent = document.getElementById('tabContent');
        if (tabContent) {
            tabContent.innerHTML = `
                <div class="records-table-wrapper">
                    <table class="records-table" id="recordsTable">
                        <thead>
                            <tr id="recordsTableHeader">
                                <th>Persona</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTableBody">
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // Resetear la vista de horas trabajadas
        AsistenciaBusqueda.setVistaHorasTrabajadas(false);
        
        if (fechasActuales.length > 0) {
            const primerFecha = fechasActuales[0];
            const registrosTab = registrosPorFechaActual[primerFecha];
            
            // Actualizar tabs visuales
            const allTabs = document.querySelectorAll('.tab-button');
            allTabs.forEach(t => t.classList.remove('active'));
            const firstTab = document.querySelector('.tab-button:first-of-type');
            if (firstTab) {
                firstTab.classList.add('active');
            }
            
            // Mostrar la tabla de registros
            setTimeout(() => {
                AsistenciaReportDetails.mostrarTab(primerFecha, registrosTab);
            }, 100);
        }
    }

    /**
     * Volver a horas trabajadas
     */
    function volverAHorasTrabajadas() {
        // Limpiar y reconstruir la estructura HTML estándar
        const tabContent = document.getElementById('tabContent');
        if (tabContent) {
            tabContent.innerHTML = `
                <div class="records-table-wrapper">
                    <table class="records-table" id="recordsTable">
                        <thead>
                            <tr id="recordsTableHeader">
                                <th>Persona</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTableBody">
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // Actualizar tabs visuales
        const allTabs = document.querySelectorAll('.tab-button');
        allTabs.forEach(t => t.classList.remove('active'));
        const firstTab = document.querySelector('.tab-button:first-of-type');
        if (firstTab) {
            firstTab.classList.add('active');
        }
        
        // Establecer la bandera de vista de horas trabajadas
        AsistenciaBusqueda.setVistaHorasTrabajadas(true);
        
        setTimeout(() => {
            if (fechasActuales.length > 0) {
                const primerFecha = fechasActuales[0];
                const registrosTab = registrosPorFechaActual[primerFecha];
                
                // Mostrar la vista con los datos correctos
                const registrosProcesados = registrosTab.map(registro => ({
                    nombre: registro.nombre,
                    codigo: registro.codigo_persona,
                    id_rol: registro.id_rol,
                    horas: registro.horas && typeof registro.horas === 'object' ? Object.values(registro.horas) : []
                }));
                
                AsistenciaHorasTrabajadas.actualizarVista(registrosProcesados, primerFecha);
                AsistenciaBusqueda.inicializarBusquedaHoras(primerFecha);
            }
        }, 100);
    }

    /**
     * Obtener detalles del reporte desde la API
     */
    function fetchReportDetails(reportId, callback) {
        const url = `/asistencia-personal/reportes/${reportId}/detalles`;

        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {

            callback(data);
        })
        .catch(error => {

            alert('Error al cargar los detalles del reporte: ' + error.message);
        });
    }

    /**
     * Mostrar botón de descargar PDF y conectarlo con la función
     */
    function mostrarBotonDescargarPDF() {
        const btnDescargarPDFMenu = document.getElementById('btnDescargarPDFMenu');
        if (btnDescargarPDFMenu) {
            btnDescargarPDFMenu.style.display = 'flex';
            
            // Remover listeners anteriores para evitar duplicados
            const newBtn = btnDescargarPDFMenu.cloneNode(true);
            btnDescargarPDFMenu.parentNode.replaceChild(newBtn, btnDescargarPDFMenu);
            
            // Agregar listener al nuevo botón
            const btnNuevo = document.getElementById('btnDescargarPDFMenu');
            if (btnNuevo) {
                btnNuevo.addEventListener('click', function(e) {
                    e.preventDefault();

                    
                    // Obtener datos del módulo AsistenciaTotalHorasExtras
                    if (typeof AsistenciaTotalHorasExtras !== 'undefined') {
                        const personasConExtras = AsistenciaTotalHorasExtras.obtenerPersonasConExtras();
                        const todasLasFechas = AsistenciaTotalHorasExtras.obtenerTodasLasFechas();
                        


                        
                        if (personasConExtras && personasConExtras.length > 0 && todasLasFechas && todasLasFechas.length > 0) {
                            // Llamar a la función de descarga del PDF
                            if (typeof PDFGenerator !== 'undefined' && PDFGenerator.descargar) {
                                PDFGenerator.descargar(personasConExtras, todasLasFechas);
                            } else {
                                alert('Error: No se pudo inicializar el generador de PDF');

                            }
                        } else {
                            alert('No hay datos para descargar. Asegúrate de que hay personas con horas extras registradas.');

                        }
                    } else {
                        alert('Error: Módulo de horas extras no disponible');

                    }
                });
                

            }
        }
    }

    return {
        init,
        openReportDetailModal,
        fetchReportDetails
    };
})();

// Exponer fetchReportDetails globalmente para uso en otros módulos
function fetchReportDetails(reportId, callback) {
    return AsistenciaPersonal.fetchReportDetails(reportId, callback);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    AsistenciaPersonal.init();
});




