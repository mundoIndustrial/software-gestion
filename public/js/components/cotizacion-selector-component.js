/**
 * CotizacionSelectorComponent
 * 
 * Componente compartido para búsqueda y selección de cotizaciones.
 * Usado en ambas vistas: editable y no-editable.
 * 
 * Funcionalidades:
 * - Búsqueda de cotizaciones con filtrado
 * - Selección de cotización
 * - Carga de datos de cotización (prendas/logo)
 * - Actualización de campos del formulario
 * 
 * @author Sistema de Refactorización
 * @date 2026-01-12
 */

(function() {
    'use strict';

    class CotizacionSelectorComponent {
        constructor() {
            this.searchInput = null;
            this.hiddenInput = null;
            this.dropdown = null;
            this.selectedDiv = null;
            this.selectedText = null;
            this.cotizaciones = [];
            this.onSeleccionCallback = null;
        }

        /**
         * Inicializar el componente
         */
        init(config) {
            this.searchInput = document.getElementById(config.searchInputId || 'cotizacion_search');
            this.hiddenInput = document.getElementById(config.hiddenInputId || 'cotizacion_id');
            this.dropdown = document.getElementById(config.dropdownId || 'cotizacion_dropdown');
            this.selectedDiv = document.getElementById(config.selectedDivId || 'cotizacion_selected');
            this.selectedText = document.getElementById(config.selectedTextId || 'cotizacion_selected_text');
            this.cotizaciones = config.cotizaciones || window.cotizacionesData || [];
            this.onSeleccionCallback = config.onSeleccion || null;

            if (!this.searchInput || !this.hiddenInput || !this.dropdown) {
                return;
            }

            this._attachEventListeners();
        }

        /**
         * Adjuntar event listeners
         */
        _attachEventListeners() {
            // Búsqueda
            this.searchInput.addEventListener('input', (e) => {
                this._mostrarOpciones(e.target.value);
            });

            // Click en input
            this.searchInput.addEventListener('click', () => {
                this._mostrarOpciones();
            });

            // Focus en input
            this.searchInput.addEventListener('focus', () => {
                if (this.searchInput.value === '') {
                    this._mostrarOpciones();
                }
            });

            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', (e) => {
                if (e.target !== this.searchInput && e.target !== this.dropdown) {
                    this.dropdown.style.display = 'none';
                }
            });
        }

        /**
         * Mostrar opciones filtradas
         */
        _mostrarOpciones(filtro = '') {
            const filtroLower = filtro.toLowerCase();
            const opciones = this.cotizaciones.filter(cot => {
                return cot.numero.toLowerCase().includes(filtroLower) ||
                       (cot.numero_cotizacion && cot.numero_cotizacion.toLowerCase().includes(filtroLower)) ||
                       cot.cliente.toLowerCase().includes(filtroLower);
            });

            if (this.cotizaciones.length === 0) {
                this.dropdown.innerHTML = '<div style="padding: 1rem; color: #ef4444; text-align: center;"><strong>⚠️ No hay cotizaciones aprobadas</strong><br><small>No tienes cotizaciones en estado APROBADA_COTIZACIONES o APROBADO_PARA_PEDIDO</small></div>';
            } else if (opciones.length === 0) {
                this.dropdown.innerHTML = `<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones<br><small>Total disponibles: ${this.cotizaciones.length}</small></div>`;
            } else {
                this.dropdown.innerHTML = opciones.map(cot => {
                    const escape = (val) => {
                        if (!val) return '';
                        return String(val).replace(/'/g, "\\'");
                    };
                    
                    return `
                        <div onclick="window.CotizacionSelectorComponent.seleccionar(${cot.id}, '${escape(cot.numero)}', '${escape(cot.cliente)}', '${escape(cot.asesora)}', '${escape(cot.formaPago)}', ${cot.prendasCount})" 
                             style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" 
                             onmouseover="this.style.background = '#f0f9ff'" 
                             onmouseout="this.style.background = 'white'">
                            <div style="font-weight: 600; color: #1f2937;">
                                ${cot.numero}${cot.numero_cotizacion ? ` <span style="color: #0066cc; font-size: 0.875rem;">(${cot.numero_cotizacion})</span>` : ''}
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                Cliente: <strong>${cot.cliente}</strong> | ${cot.prendasCount} prendas
                            </div>
                            ${cot.formaPago ? `<div style="font-size: 0.75rem; color: #9ca3af;">Forma de pago: ${cot.formaPago}</div>` : ''}
                        </div>
                    `;
                }).join('');
            }

            this.dropdown.style.display = 'block';
        }

        /**
         * Seleccionar una cotización
         */
        seleccionar(id, numero, cliente, asesora, formaPago, prendasCount) {
            this.hiddenInput.value = id;
            this.searchInput.value = `${numero} - ${cliente}`;
            this.dropdown.style.display = 'none';
            
            // Mostrar resumen
            if (this.selectedDiv && this.selectedText) {
                this.selectedDiv.style.display = 'block';
                this.selectedText.textContent = `${numero} - ${cliente} (${prendasCount} prendas)`;
            }
            
            // Actualizar campos de información
            const numeroCotizacionInput = document.getElementById('numero_cotizacion');
            const clienteInput = document.getElementById('cliente');
            const asesoraInput = document.getElementById('asesora');
            const formaPagoInput = document.getElementById('forma_de_pago');

            if (numeroCotizacionInput) numeroCotizacionInput.value = numero;
            if (clienteInput) clienteInput.value = cliente;
            if (asesoraInput) asesoraInput.value = asesora;
            if (formaPagoInput) formaPagoInput.value = formaPago || '';

            // Cargar datos de la cotización
            this._cargarDatosCotizacion(id);
        }

        /**
         * Cargar datos de la cotización desde el servidor
         */
        _cargarDatosCotizacion(id) {
            fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📥 Datos de cotización recibidos:', data);
                
                // Actualizar forma de pago con los datos del servidor
                const formaPagoInput = document.getElementById('forma_de_pago');
                if (data.forma_pago && formaPagoInput) {
                    formaPagoInput.value = data.forma_pago;
                }
                
                // Ejecutar callback personalizado si existe
                if (this.onSeleccionCallback && typeof this.onSeleccionCallback === 'function') {
                    this.onSeleccionCallback(data);
                }
            })
            .catch(error => {
                console.error('❌ Error al cargar cotización:', error);
                const prendasContainer = document.getElementById('prendas-container');
                if (prendasContainer) {
                    prendasContainer.innerHTML = '<p class="text-red-500">Error al cargar los datos: ' + error.message + '</p>';
                }
            });
        }
    }

    // Crear instancia global
    window.CotizacionSelectorComponent = new CotizacionSelectorComponent();

})();
