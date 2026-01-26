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

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar storages de imágenes
        window.imagenesPrendaStorage = new ImageStorageService(3);
        window.imagenesTelaStorage = new ImageStorageService(3);
        window.imagenesReflectivoStorage = new ImageStorageService(3);
        
        // Configurar asesora
        document.getElementById('asesora_editable').value = window.asesorActualNombre || '';
        
        // Mostrar botones
        const btnSubmit = document.getElementById('btn-submit');
        btnSubmit.textContent = '✓ Crear Pedido';
        btnSubmit.style.display = 'block';
        
        const btnVistaPrevio = document.getElementById('btn-vista-previa');
        btnVistaPrevio.style.display = 'block';

        // ========== BUSCADOR DE COTIZACIONES ==========
        const searchInput = document.getElementById('cotizacion_search_editable');
        const dropdown = document.getElementById('cotizacion_dropdown_editable');
        const selectedDiv = document.getElementById('cotizacion_selected_editable');
        const selectedText = document.getElementById('cotizacion_selected_text_editable');
        const hiddenInput = document.getElementById('cotizacion_id_editable');
        
        if (!searchInput || !window.cotizacionesData) {
            console.warn('⚠️ Buscador de cotizaciones: Datos no disponibles');
            return;
        }

        // Transformar datos de cotizaciones del servidor
        const cotizacionesFormateadas = window.cotizacionesData.map(cot => ({
            id: cot.id,
            numero_cotizacion: cot.numero || cot.numero_cotizacion || `COT-${cot.id}`,
            cliente: cot.cliente?.nombre || cot.cliente || 'Sin cliente',
            asesora: cot.asesor?.name || window.asesorActualNombre || 'N/A',
            forma_pago: cot.especificaciones?.forma_pago || cot.forma_de_pago || 'N/A',
            estado: cot.estado || 'APROBADO_PARA_PEDIDO',
            // Mantener datos originales para cargar después
            original: cot
        }));

        console.log('📋 Cotizaciones cargadas:', cotizacionesFormateadas.length);
        
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
            if (cotizaciones.length === 0) {
                dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron cotizaciones</div>';
                dropdown.style.display = 'block';
                return;
            }
            
            dropdown.innerHTML = cotizaciones.map(cot => `
                <div class="cotizacion-item" data-id="${cot.id}" style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='#f0f9ff'; this.style.borderLeft='3px solid #0066cc'" onmouseout="this.style.background='white'; this.style.borderLeft='none'">
                    <div>
                        <div style="font-weight: 700; color: #0066cc; font-size: 1rem; font-family: 'Courier New', monospace;">${cot.numero_cotizacion}</div>
                        <div style="font-size: 0.875rem; color: #374151; margin-top: 0.25rem;">
                            ${cot.cliente}
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 0.75rem; color: #9ca3af;">
                        <div>${cot.estado}</div>
                    </div>
                </div>
            `).join('');
            
            dropdown.style.display = 'block';
            
            // Agregar event listeners a los items
            dropdown.querySelectorAll('.cotizacion-item').forEach(item => {
                item.addEventListener('click', function() {
                    const cotId = parseInt(this.dataset.id);
                    const cotizacion = cotizacionesFormateadas.find(c => c.id === cotId);
                    if (cotizacion) {
                        seleccionarCotizacion(cotizacion);
                    }
                });
            });
        }
        
        // Función para seleccionar cotización
        function seleccionarCotizacion(cotizacion) {
            cotizacionSeleccionada = cotizacion;
            hiddenInput.value = cotizacion.id;
            searchInput.value = cotizacion.numero_cotizacion;
            selectedText.textContent = `${cotizacion.numero_cotizacion} - ${cotizacion.cliente}`;
            selectedDiv.style.display = 'block';
            dropdown.style.display = 'none';
            
            console.log('✓ Cotización seleccionada:', cotizacion);
            
            // Guardar para usar en agregar prendas
            window.cotizacionSeleccionadaActual = cotizacion;
            
            console.log('📦 Prendas disponibles:', cotizacion.original?.prendas || []);
        }
        
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // ========== GESTIÓN DE ÍTEMS ==========
        const seccionItems = document.getElementById('seccion-items-pedido');
        if (seccionItems) {
            seccionItems.style.display = 'block';
        }

        // ========== BOTÓN AGREGAR PRENDA ==========
        const btnAgregarPrenda = document.getElementById('btn-agregar-prenda');
        if (btnAgregarPrenda) {
            btnAgregarPrenda.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Verificar que hay cotización seleccionada
                if (!cotizacionSeleccionada) {
                    alert('⚠️ Por favor selecciona una cotización primero');
                    return;
                }
                
                console.log('📦 Abriendo modal para agregar prenda de cotización:', cotizacionSeleccionada.numero_cotizacion);
                
                // Guardar para usar en el modal
                window.cotizacionSeleccionadaActual = cotizacionSeleccionada;
                
                // Abrir modal de selección de prendas
                if (typeof window.abrirModalSeleccionPrendas === 'function') {
                    window.abrirModalSeleccionPrendas(cotizacionSeleccionada);
                } else {
                    console.warn('⚠️ Función abrirModalSeleccionPrendas no disponible');
                }
            });
        }

        console.log('✅ [Buscador de Cotización] Inicializado correctamente');
    });

})();
