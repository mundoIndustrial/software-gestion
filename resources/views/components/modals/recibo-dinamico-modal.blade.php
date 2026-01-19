{{-- 
    Modal de Recibo Dinámico
    Carga y muestra recibos de producción específicos (prenda + proceso)
    Se reutiliza para todos los procesos (costura, bordado, estampado, reflectivo, etc.)
--}}
<div id="recibo-dinamico-modal" 
     style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 99999; overflow-y: auto;">
    
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: white; border-radius: 12px; max-width: 900px; width: 100%; max-height: 95vh; overflow-y: auto; box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold;">Recibo de <span id="recibo-tipo-proceso">Proceso</span></h2>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.9rem;">
                        Pedido <span id="recibo-numero-pedido" style="font-weight: bold;">#-</span> | 
                        Prenda: <span id="recibo-nombre-prenda" style="font-weight: bold;">-</span>
                    </p>
                </div>
                <button onclick="cerrarModalRecibo()" style="background: none; border: none; color: white; font-size: 2rem; cursor: pointer; padding: 0; line-height: 1; width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                    &times;
                </button>
            </div>
            
            <!-- Content -->
            <div style="padding: 2rem;">
                
                <!-- Loading State -->
                <div id="recibo-loading" style="display: none; text-align: center; padding: 2rem;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p style="margin-top: 1rem; color: #6b7280;">Cargando recibo...</p>
                </div>
                
                <!-- Error State -->
                <div id="recibo-error" style="display: none; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 1rem; color: #dc2626;">
                    <strong>Error:</strong> <span id="recibo-error-message"></span>
                </div>
                
                <!-- Recibo Content -->
                <div id="recibo-content" style="display: none;">
                    <!-- Se llenará dinámicamente -->
                </div>
                
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 1rem 2rem; border-top: 1px solid #e5e7eb; border-radius: 0 0 12px 12px; display: flex; justify-content: space-between; align-items: center;">
                <div id="recibo-actions" style="display: flex; gap: 0.75rem;">
                    <!-- Se llenará dinámicamente con botones de acciones -->
                </div>
                <button onclick="cerrarModalRecibo()" style="background: #e5e7eb; color: #374151; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
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
        
        #recibo-dinamico-modal .recibo-seccion {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        #recibo-dinamico-modal .recibo-seccion-titulo {
            background: #f3e8ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            font-weight: 600;
            color: #5b21b6;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        #recibo-dinamico-modal .recibo-seccion-contenido {
            padding: 1rem;
        }
        
        #recibo-dinamico-modal .recibo-fila {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        #recibo-dinamico-modal .recibo-fila.full {
            grid-template-columns: 1fr;
        }
        
        #recibo-dinamico-modal .recibo-campo {
            display: flex;
            flex-direction: column;
        }
        
        #recibo-dinamico-modal .recibo-campo-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
        
        #recibo-dinamico-modal .recibo-campo-valor {
            font-size: 1rem;
            color: #1f2937;
            padding: 0.5rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        
        #recibo-dinamico-modal .estado-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        #recibo-dinamico-modal .estado-pendiente {
            background: #fee2e2;
            color: #991b1b;
        }
        
        #recibo-dinamico-modal .estado-en-proceso {
            background: #fef3c7;
            color: #92400e;
        }
        
        #recibo-dinamico-modal .estado-terminado {
            background: #dcfce7;
            color: #15803d;
        }
        
        #recibo-dinamico-modal .botones-grupo {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        #recibo-dinamico-modal .boton-accion {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        #recibo-dinamico-modal .boton-primario {
            background: #3b82f6;
            color: white;
        }
        
        #recibo-dinamico-modal .boton-primario:hover {
            background: #6d28d9;
        }
        
        #recibo-dinamico-modal .boton-secundario {
            background: #e5e7eb;
            color: #374151;
        }
        
        #recibo-dinamico-modal .boton-secundario:hover {
            background: #d1d5db;
        }
    </style>
</div>

<script>
    /**
     * Abre el modal de recibo dinámico
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoProceso - Tipo de proceso (ej: 'costura', 'bordado')
     */
    window.abrirModalRecibo = async function(pedidoId, prendaId, tipoProceso) {
        // ⚠️ VALIDACIÓN DEFENSIVA
        if (typeof tipoProceso !== 'string') {
            console.error('%c[RECIBO-DINAMICO] ❌ ERROR: tipoProceso DEBE ser STRING', 'color: #ef4444; font-weight: bold;', 'Recibido:', typeof tipoProceso, tipoProceso);
            alert('Error: tipo de recibo debe ser texto (STRING)');
            return;
        }
        
        if (typeof prendaId !== 'number') {
            console.error('%c[RECIBO-DINAMICO] ❌ ERROR: prendaId DEBE ser NÚMERO', 'color: #ef4444; font-weight: bold;', 'Recibido:', typeof prendaId, prendaId);
            alert('Error: ID de prenda debe ser número');
            return;
        }
        
        const modal = document.getElementById('recibo-dinamico-modal');
        const loading = document.getElementById('recibo-loading');
        const error = document.getElementById('recibo-error');
        const content = document.getElementById('recibo-content');
        
        // Mostrar modal
        modal.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        content.style.display = 'none';
        
        console.log('📄 [RECIBO-DINAMICO] Abriendo recibo:', {
            pedidoId,
            prendaId,
            tipoProceso
        });
        
        try {
            // Aquí iría la llamada al servidor para obtener datos del recibo específico
            // Por ahora, usaremos datos de demostración
            
            // Actualizar encabezado
            document.getElementById('recibo-tipo-proceso').textContent = tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
            document.getElementById('recibo-numero-pedido').textContent = `#${pedidoId}`;
            document.getElementById('recibo-nombre-prenda').textContent = 'Camisa Drill'; // Esto vendría del servidor
            
            // Generar contenido de recibo
            let html = `
                <!-- Información Básica -->
                <div class="recibo-seccion">
                    <div class="recibo-seccion-titulo">
                        <i class="fas fa-info-circle"></i>
                        Información Básica
                    </div>
                    <div class="recibo-seccion-contenido">
                        <div class="recibo-fila">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Número de Pedido</span>
                                <span class="recibo-campo-valor">#${pedidoId}</span>
                            </div>
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Tipo de Proceso</span>
                                <span class="recibo-campo-valor">${tipoProceso.toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="recibo-fila">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Estado</span>
                                <span class="recibo-campo-valor"><span class="estado-badge estado-pendiente">Pendiente</span></span>
                            </div>
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Encargado</span>
                                <span class="recibo-campo-valor">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detalles de la Prenda -->
                <div class="recibo-seccion">
                    <div class="recibo-seccion-titulo">
                        <i class="fas fa-shirt"></i>
                        Detalles de la Prenda
                    </div>
                    <div class="recibo-seccion-contenido">
                        <div class="recibo-fila">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Nombre de Prenda</span>
                                <span class="recibo-campo-valor">Camisa Drill</span>
                            </div>
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Color</span>
                                <span class="recibo-campo-valor">Azul Marino</span>
                            </div>
                        </div>
                        <div class="recibo-fila">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Tela</span>
                                <span class="recibo-campo-valor">Drill 100% Algodón</span>
                            </div>
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Cantidad Total</span>
                                <span class="recibo-campo-valor">50</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cantidades por Talla -->
                <div class="recibo-seccion">
                    <div class="recibo-seccion-titulo">
                        <i class="fas fa-ruler"></i>
                        Distribución por Talla
                    </div>
                    <div class="recibo-seccion-contenido">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem;">
                            <div style="text-align: center; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">XS</div>
                                <div style="font-weight: bold; color: #1f2937; font-size: 1.25rem;">5</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">S</div>
                                <div style="font-weight: bold; color: #1f2937; font-size: 1.25rem;">10</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">M</div>
                                <div style="font-weight: bold; color: #1f2937; font-size: 1.25rem;">15</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">L</div>
                                <div style="font-weight: bold; color: #1f2937; font-size: 1.25rem;">15</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">XL</div>
                                <div style="font-weight: bold; color: #1f2937; font-size: 1.25rem;">5</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Especificaciones del Proceso -->
                <div class="recibo-seccion">
                    <div class="recibo-seccion-titulo">
                        <i class="fas fa-cogs"></i>
                        Especificaciones del Proceso
                    </div>
                    <div class="recibo-seccion-contenido">
                        <div class="recibo-fila full">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Observaciones</span>
                                <textarea style="width: 100%; min-height: 100px; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-family: monospace; resize: none;" readonly>Costura simple. Uso de hilo color gris claro. Rematar todas las costuras internas.</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Firma y Confirmación -->
                <div class="recibo-seccion">
                    <div class="recibo-seccion-titulo">
                        <i class="fas fa-pen"></i>
                        Confirmación
                    </div>
                    <div class="recibo-seccion-contenido">
                        <div class="recibo-fila">
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Responsable</span>
                                <span class="recibo-campo-valor">-</span>
                            </div>
                            <div class="recibo-campo">
                                <span class="recibo-campo-label">Fecha de Entrega</span>
                                <span class="recibo-campo-valor">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            
            // Actualizar botones de acciones
            const actionsContainer = document.getElementById('recibo-actions');
            actionsContainer.innerHTML = `
                <button class="boton-accion boton-primario" onclick="imprimirRecibo()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <button class="boton-accion boton-primario" onclick="descargarReciboPDF()">
                    <i class="fas fa-download"></i> Descargar PDF
                </button>
            `;
            
            // Cambiar estados
            loading.style.display = 'none';
            content.style.display = 'block';
            error.style.display = 'none';
            
        } catch (err) {
            console.error('❌ [RECIBO-DINAMICO] Error:', err);
            document.getElementById('recibo-error-message').textContent = err.message;
            
            loading.style.display = 'none';
            error.style.display = 'block';
            content.style.display = 'none';
        }
    };
    
    /**
     * Cierra el modal de recibo
     */
    window.cerrarModalRecibo = function() {
        document.getElementById('recibo-dinamico-modal').style.display = 'none';
    };
    
    /**
     * Imprime el recibo
     */
    window.imprimirRecibo = function() {
        window.print();
    };
    
    /**
     * Descarga el recibo como PDF
     */
    window.descargarReciboPDF = function() {
        alert('Funcionalidad de descarga PDF - Por implementar');
    };
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('recibo-dinamico-modal');
        if (e.target === modal) {
            cerrarModalRecibo();
        }
    });
    
    // Cerrar modal con tecla Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalRecibo();
        }
    });
</script>
