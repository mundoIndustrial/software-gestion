{{-- 
    Modal de Vista Intermedia de Recibos
    Muestra prendas y sus procesos disponibles
    Permite seleccionar un proceso específico para ver su recibo
--}}
<div id="recibos-intermediate-modal" 
     style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 99999; overflow-y: auto;">
    
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: white; border-radius: 12px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold;">Recibos de Producción</h2>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.9rem;">Pedido <span id="intermediate-pedido-numero" style="font-weight: bold;">#-</span></p>
                </div>
                <button onclick="cerrarModalRecibosIntermedio()" style="background: none; border: none; color: white; font-size: 2rem; cursor: pointer; padding: 0; line-height: 1; width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                    &times;
                </button>
            </div>
            
            <!-- Content -->
            <div style="padding: 2rem;">
                
                <!-- Loading State -->
                <div id="intermediate-loading" style="display: none; text-align: center; padding: 2rem;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p style="margin-top: 1rem; color: #6b7280;">Cargando prendas y procesos...</p>
                </div>
                
                <!-- Error State -->
                <div id="intermediate-error" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 1rem; color: #dc2626;">
                    <strong>Error:</strong> <span id="intermediate-error-message"></span>
                </div>
                
                <!-- Prendas List -->
                <div id="intermediate-prendas-container" style="display: none;">
                    <!-- Se llenará dinámicamente -->
                </div>
                
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 1rem 2rem; border-top: 1px solid #e5e7eb; border-radius: 0 0 12px 12px; text-align: right;">
                <button onclick="cerrarModalRecibosIntermedio()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    
    <!-- CSS para animación de carga -->
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        #recibos-intermediate-modal .prenda-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        #recibos-intermediate-modal .prenda-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        #recibos-intermediate-modal .prenda-header {
            background: #f0f9ff;
            border-left: 4px solid #2563eb;
            padding: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #1e3a8a;
        }
        
        #recibos-intermediate-modal .prenda-header:hover {
            background: #e0f2fe;
        }
        
        #recibos-intermediate-modal .prenda-header .toggle-icon {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }
        
        #recibos-intermediate-modal .prenda-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        #recibos-intermediate-modal .procesos-list {
            padding: 1rem;
            background: white;
        }
        
        #recibos-intermediate-modal .proceso-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        #recibos-intermediate-modal .proceso-item:last-child {
            margin-bottom: 0;
        }
        
        #recibos-intermediate-modal .proceso-item:hover {
            background: #f0f9ff;
            border-color: #2563eb;
            transform: translateX(4px);
        }
        
        #recibos-intermediate-modal .proceso-nombre {
            font-weight: 500;
            color: #1f2937;
            flex: 1;
        }
        
        #recibos-intermediate-modal .proceso-estado {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-right: 1rem;
        }
        
        #recibos-intermediate-modal .estado-pendiente {
            background: #fee2e2;
            color: #991b1b;
        }
        
        #recibos-intermediate-modal .estado-en-proceso {
            background: #fef3c7;
            color: #92400e;
        }
        
        #recibos-intermediate-modal .estado-terminado {
            background: #dcfce7;
            color: #15803d;
        }
        
        #recibos-intermediate-modal .proceso-icono {
            color: #6b7280;
            font-size: 1.25rem;
        }
    </style>
</div>

<script>
    /**
     * Abre el modal de vista intermedia de recibos
     */
    window.abrirModalRecibosIntermedio = async function(pedidoId) {
        const modal = document.getElementById('recibos-intermediate-modal');
        const loading = document.getElementById('intermediate-loading');
        const error = document.getElementById('intermediate-error');
        const container = document.getElementById('intermediate-prendas-container');
        
        // Mostrar modal
        modal.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        container.style.display = 'none';
        
        console.log(' [RECIBOS-INTERMEDIO] Abriendo modal para pedido:', pedidoId);
        
        try {
            // Obtener datos del servidor
            const response = await fetch(`/api/pedidos/${pedidoId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            const datos = result.data || result;
            console.log(' [RECIBOS-INTERMEDIO] Datos cargados:', datos);
            
            // Actualizar número de pedido
            document.getElementById('intermediate-pedido-numero').textContent = `#${datos.numero_pedido || '?'}`;
            
            // Generar HTML para prendas y procesos
            let html = '';
            
            if (!datos.prendas || datos.prendas.length === 0) {
                html = `
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #d1d5db;"></i>
                        <p>No hay prendas en este pedido</p>
                    </div>
                `;
            } else {
                datos.prendas.forEach((prenda, prendaIdx) => {
                    const procesos = prenda.procesos || [];
                    const tieneProcesos = procesos.length > 0;
                    
                    html += `
                        <div class="prenda-item">
                            <div class="prenda-header" onclick="togglePrendaAccordion(this, ${prendaIdx})">
                                <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                                    <i class="fas fa-shirt" style="color: #2563eb;"></i>
                                    <span>${prenda.nombre}</span>
                                    ${tieneProcesos ? `<span style="font-size: 0.8rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">${procesos.length} proceso${procesos.length !== 1 ? 's' : ''}</span>` : ''}
                                </div>
                                <span class="toggle-icon">▼</span>
                            </div>
                            ${tieneProcesos ? `
                                <div class="procesos-list" style="display: none;">
                                    ${procesos.map((proceso, procIdx) => `
                                        <div class="proceso-item" onclick="seleccionarProceso(${pedidoId}, ${prenda.id}, '${proceso.tipo_proceso}'); event.stopPropagation();">
                                            <span class="proceso-nombre">
                                                <i class="fas fa-cogs" style="margin-right: 0.5rem; color: #3b82f6;"></i>
                                                ${proceso.nombre_proceso}
                                            </span>
                                            <span class="proceso-estado estado-${(proceso.estado || 'pendiente').toLowerCase().replace(/ /g, '-')}">
                                                ${proceso.estado || 'Pendiente'}
                                            </span>
                                            <i class="fas fa-arrow-right" style="color: #9ca3af;"></i>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : `
                                <div class="procesos-list" style="text-align: center; color: #9ca3af; padding: 1rem;">
                                    <i class="fas fa-info-circle"></i> No hay procesos definidos
                                </div>
                            `}
                        </div>
                    `;
                });
            }
            
            container.innerHTML = html;
            
            // Cambiar estados
            loading.style.display = 'none';
            container.style.display = 'block';
            error.style.display = 'none';
            
        } catch (err) {
            console.error(' [RECIBOS-INTERMEDIO] Error:', err);
            document.getElementById('intermediate-error-message').textContent = err.message;
            
            loading.style.display = 'none';
            error.style.display = 'block';
            container.style.display = 'none';
        }
    };
    
    /**
     * Cierra el modal de vista intermedia
     */
    window.cerrarModalRecibosIntermedio = function() {
        document.getElementById('recibos-intermediate-modal').style.display = 'none';
    };
    
    /**
     * Toggle para expandir/contraer prenda
     */
    window.togglePrendaAccordion = function(headerElement, prendaIdx) {
        const procesosList = headerElement.nextElementSibling;
        
        if (!procesosList) return;
        
        // Toggle display
        if (procesosList.style.display === 'none') {
            procesosList.style.display = 'block';
            headerElement.classList.remove('collapsed');
        } else {
            procesosList.style.display = 'none';
            headerElement.classList.add('collapsed');
        }
    };
    
    //  NOTA: seleccionarProceso fue movido a recibos-process-selector.blade.php
    // para evitar conflicto de nombres. Ver recibos-process-selector.blade.php línea 363
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('recibos-intermediate-modal');
        if (e.target === modal) {
            cerrarModalRecibosIntermedio();
        }
    });
    
    // Cerrar modal con tecla Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalRecibosIntermedio();
        }
    });
</script>
