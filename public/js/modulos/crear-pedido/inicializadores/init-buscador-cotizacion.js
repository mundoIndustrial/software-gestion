/**
 * INICIALIZADOR BUSCADOR DE COTIZACIÓN - Crear Pedido Desde Cotización
 * 
 * Responsabilidades:
 * - Inicializar el buscador de cotizaciones
 * - Manejar filtros dinámicos por número y cliente
 * - Seleccionar cotización y cargar datos
 * - Mostrar modal de selección de prendas
 */

(function() {
    'use strict';

    // Control para evitar múltiples inicializaciones
    let inicializado = false;
    let esperandoServicio = false;
    let timeoutServicio = null;

    // Función para inicializar los servicios
    function inicializarServicios() {
        if (inicializado) {
            console.log('🔧 [INIT-STORAGE] Servicios ya inicializados, omitiendo...');
            return;
        }
        
        if (esperandoServicio) {
            console.log('⏳ [INIT-STORAGE] Ya esperando al servicio, omitiendo...');
            return;
        }
        
        console.log('🔧 [INIT-STORAGE] Inicializando servicios de imágenes...');
        
        // Esperar a que ImageStorageService esté disponible (puede venir del lazy loader)
        function verificarServicio() {
            if (typeof ImageStorageService !== 'undefined') {
                // Limpiar timeout si existe
                if (timeoutServicio) {
                    clearTimeout(timeoutServicio);
                    timeoutServicio = null;
                }
                
                window.imagenesPrendaStorage = new ImageStorageService(3);
                window.imagenesTelaStorage = new ImageStorageService(3);
                window.imagenesReflectivoStorage = new ImageStorageService(3);
                inicializado = true;
                esperandoServicio = false;
                console.log('✅ [INIT-STORAGE] Servicios de imágenes inicializados correctamente');
            } else {
                esperandoServicio = true;
                console.log('⏳ [INIT-STORAGE] Esperando a ImageStorageService...');
                
                // Timeout para evitar bucles infinitos (máximo 5 segundos)
                timeoutServicio = setTimeout(() => {
                    if (esperandoServicio) {
                        console.warn('⚠️ [INIT-STORAGE] Timeout esperando ImageStorageService, deteniendo espera');
                        esperandoServicio = false;
                        timeoutServicio = null;
                    }
                }, 5000);
                
                // Reintentar en 100ms
                setTimeout(verificarServicio, 100);
            }
        }
        
        verificarServicio();
        
        // Configurar asesora si existe el elemento
        const asesoraField = document.getElementById('asesora_editable');
        if (asesoraField) {
            asesoraField.value = window.asesorActualNombre || '';
        }
        
        // Mostrar botones si existe
        const btnSubmit = document.getElementById('btn-submit');
        if (btnSubmit) {
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';
        }
    }

    // Verificar si el DOM ya está cargado
    if (document.readyState === 'loading') {
        // El DOM todavía está cargando, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializarServicios);
    } else {
        // El DOM ya está cargado, ejecutar inmediatamente
        inicializarServicios();
    }

    function inicializarBuscador() {
        // Solo inicializar el buscador si hay datos de cotizaciones disponibles
        if (!window.cotizacionesData || !Array.isArray(window.cotizacionesData) || window.cotizacionesData.length === 0) {
            console.log('ℹ️ [INIT-BUSCADOR] No hay datos de cotizaciones disponibles, omitiendo inicialización del buscador');
            return;
        }
        
        console.log('🔍 [INIT-BUSCADOR] Inicializando buscador de cotizaciones...');
        
        // ========== BUSCADOR DE COTIZACIONES ==========
        const searchInput = document.getElementById('cotizacion_search_editable');
        const dropdown = document.getElementById('cotizacion_dropdown_editable');
        const selectedDiv = document.getElementById('cotizacion_selected_editable');
        const selectedText = document.getElementById('cotizacion_selected_text_editable');
        const hiddenInput = document.getElementById('cotizacion_id_editable');
        
        if (!searchInput) {
            console.log('ℹ️ [INIT-BUSCADOR] Elemento de búsqueda no encontrado, omitiendo');
            return;
        }
        
        if (!dropdown) {
            console.log('ℹ️ [INIT-BUSCADOR] Dropdown no encontrado, omitiendo');
            return;
        }
        
        console.log('📋 [INIT-BUSCADOR] Datos de cotizaciones encontrados:', window.cotizacionesData.length);

        // Transformar datos de cotizaciones del servidor
        const cotizacionesFormateadas = window.cotizacionesData.map(cot => {
            // Extraer nombre del tipo de cotización - puede venir como objeto o string
            let tipoNombre = 'N/A';
            if (cot.tipoCotizacion) {
                if (typeof cot.tipoCotizacion === 'object') {
                    tipoNombre = cot.tipoCotizacion.nombre || cot.tipoCotizacion.codigo || 'N/A';
                } else {
                    tipoNombre = cot.tipoCotizacion;
                }
            } else if (cot.tipo_cotizacion) {
                if (typeof cot.tipo_cotizacion === 'object') {
                    tipoNombre = cot.tipo_cotizacion.nombre || cot.tipo_cotizacion.codigo || 'N/A';
                } else {
                    tipoNombre = cot.tipo_cotizacion;
                }
            }
            
            return {
                id: cot.id,
                numero_cotizacion: cot.numero || cot.numero_cotizacion || `COT-${cot.id}`,
                cliente: cot.cliente?.nombre || cot.cliente || 'Sin cliente',
                asesora: cot.asesor?.name || window.asesorActualNombre || 'N/A',
                forma_pago: cot.especificaciones?.forma_pago || cot.forma_de_pago || 'N/A',
                tipo_cotizacion: tipoNombre,  // ✅ Ya extraído correctamente
                estado: cot.estado || 'APROBADO_PARA_PEDIDO',
                // Mantener datos originales para cargar después
                original: cot
            };
        });

        console.log('📋 Cotizaciones cargadas:', cotizacionesFormateadas.length);
        console.log('🔍 DEBUG - Primera cotización:', cotizacionesFormateadas[0]);  // ✅ Debug
        console.log('🔍 DEBUG - Datos originales primero:', window.cotizacionesData[0]);  // ✅ Debug
        
        let cotizacionSeleccionada = null;
        
        // Mostrar todas las cotizaciones al hacer focus
        searchInput.addEventListener('focus', function() {
            mostrarCotizaciones('');
        });
        
        // Filtrar cotizaciones al escribir
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            mostrarCotizaciones(searchTerm);
        });
        
        // Función para mostrar cotizaciones filtradas
        function mostrarCotizaciones(searchTerm) {
            if (searchTerm.length === 0) {
                renderizarDropdown(cotizacionesFormateadas);
                return;
            }
            
            const filtered = cotizacionesFormateadas.filter(cot => {
                return cot.numero_cotizacion.toLowerCase().includes(searchTerm) ||
                       cot.cliente.toLowerCase().includes(searchTerm);
            });
            
            renderizarDropdown(filtered);
        }
        
        // Función para renderizar el dropdown
        function renderizarDropdown(cotizaciones) {
            dropdown.innerHTML = '';
            dropdown.style.display = 'block';
            
            if (cotizaciones.length === 0) {
                const noResultsItem = document.createElement('div');
                noResultsItem.className = 'dropdown-item';
                noResultsItem.textContent = 'No se encontraron cotizaciones';
                noResultsItem.style.padding = '8px 12px';
                noResultsItem.style.color = '#6b7280';
                dropdown.appendChild(noResultsItem);
                return;
            }
            
            cotizaciones.forEach(cot => {  // ✅ Usar el parámetro cotizaciones, no cotizacionesFormateadas
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                item.textContent = `${cot.numero_cotizacion} - ${cot.cliente} (${cot.asesora}) - ${cot.tipo_cotizacion}`;  // ✅ Incluir tipo en el dropdown
                item.style.padding = '8px 12px';
                item.style.cursor = 'pointer';
                item.style.borderBottom = '1px solid #e5e7eb';
                
                item.addEventListener('click', function() {
                    selectedDiv.textContent = `${cot.numero_cotizacion} - ${cot.cliente}`;
                    selectedText.textContent = `${cot.numero_cotizacion} - ${cot.cliente} (${cot.asesora}) - Tipo: ${cot.tipo_cotizacion}`;  // ✅ Incluir tipo
                    
                    // ✅ Actualizar el campo de tipo de cotización
                    const tipoCotElement = document.getElementById('cotizacion_tipo_text_editable');
                    if (tipoCotElement) {
                        tipoCotElement.textContent = cot.tipo_cotizacion;
                    }
                    
                    hiddenInput.value = cot.id;
                    dropdown.style.display = 'none';
                    cotizacionSeleccionada = cot;
                    
                    // Actualizar el input del buscador con la cotización seleccionada
                    searchInput.value = `${cot.numero_cotizacion} - ${cot.cliente}`;
                    
                    console.log('✓ Cotización seleccionada:', cot);
                    
                    // Guardar para usar en agregar prendas
                    window.cotizacionSeleccionadaActual = cot;
                    
                    console.log('📦 Prendas disponibles:', cot.original?.prendas || []);
                });
                
                dropdown.appendChild(item);
            });
        }
        
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
        
        console.log('✅ [INIT-BUSCADOR] Buscador de cotizaciones inicializado correctamente');
    }

    // Verificar si el DOM ya está cargado
    if (document.readyState === 'loading') {
        // El DOM todavía está cargando, esperar al evento
        document.addEventListener('DOMContentLoaded', function() {
            inicializarServicios();
            inicializarBuscador();
        });
    } else {
        // El DOM ya está cargado, ejecutar inmediatamente
        inicializarServicios();
        inicializarBuscador();
    }
})();
