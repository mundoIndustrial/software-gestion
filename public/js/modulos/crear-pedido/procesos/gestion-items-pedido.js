/**
 * Gestión de Ítems - Capa de Presentación
 * 
 * Responsabilidades:
 * - Renderizar lista de ítems del pedido
 * - Manejar agregar/eliminar ítems desde UI
 * - Recolectar datos del formulario para envío
 * - Coordinar con el backend para crear pedido
 * - Mostrar notificaciones y vista previa
 */

class GestionItemsUI {
    constructor() {
        this.api = window.pedidosAPI;
        this.items = [];
        this.inicializar();
    }

    inicializar() {
        this.attachEventListeners();
        this.cargarItems();
    }

    attachEventListeners() {
        // Agregar ítem desde cotización
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        // Agregar ítem nuevo
        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        // Vista previa
        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        // Formulario de creación
        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    async cargarItems() {
        try {
            const resultado = await this.api.obtenerItems();
            this.items = resultado.items;
            this.actualizarVistaItems();
        } catch (error) {
            console.error('Error al cargar ítems:', error);
        }
    }

    async agregarItem(itemData) {
        try {
            const resultado = await this.api.agregarItem(itemData);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem agregado correctamente', 'success');
                return true;
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
            return false;
        }
    }

    async eliminarItem(index) {
        if (!confirm('¿Eliminar este ítem?')) {
            return;
        }

        try {
            const resultado = await this.api.eliminarItem(index);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem eliminado', 'success');
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    actualizarVistaItems() {
        const container = document.getElementById('lista-items-pedido');
        const mensajeSinItems = document.getElementById('mensaje-sin-items');

        if (!container) return;

        if (this.items.length === 0) {
            container.innerHTML = '';
            if (mensajeSinItems) mensajeSinItems.style.display = 'block';
            return;
        }

        if (mensajeSinItems) mensajeSinItems.style.display = 'none';

        // Renderizar todos los items y actualizar el DOM
        this.renderizarItems();
    }

    async renderizarItems() {
        const container = document.getElementById('lista-items-pedido');
        if (!container) return;

        container.innerHTML = '';

        for (let index = 0; index < this.items.length; index++) {
            const item = this.items[index];
            try {
                const html = await this.obtenerItemCardHTML(item, index);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                container.appendChild(tempDiv.firstElementChild);
            } catch (error) {
                console.error(`Error al renderizar item ${index}:`, error);
                // Mostrar card simple como fallback
                const fallbackHTML = this.renderizarItemFallback(item, index);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = fallbackHTML;
                container.appendChild(tempDiv.firstElementChild);
            }
        }

        // Actualizar interactividad
        if (window.updateItemCardInteractions) {
            window.updateItemCardInteractions();
        }
    }

    async obtenerItemCardHTML(item, index) {
        try {
            const response = await fetch('/api/pedidos-editable/render-item-card', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    item: item,
                    index: index,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success && data.html) {
                return data.html;
            } else {
                throw new Error(data.error || 'Error al renderizar componente');
            }
        } catch (error) {
            console.error('Error al obtener HTML del item-card:', error);
            throw error;
        }
    }

    renderizarItemFallback(item, index) {
        const prenda = item.prenda?.nombre || item.prenda || 'Sin nombre';
        const origen = item.origen || 'bodega';
        const procesos = Array.isArray(item.procesos) ? item.procesos.join(', ') : (item.procesos || 'Ninguno');

        return `
            <div class="item-card" data-item-index="${index}" style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; font-weight: 600; color: #1e40af;">${prenda}</h4>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem; color: #6b7280;">
                            <strong>Origen:</strong> ${origen}
                        </p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem; color: #6b7280;">
                            <strong>Procesos:</strong> ${procesos}
                        </p>
                    </div>
                </div>
            </div>
        `;
    }


    abrirModalSeleccionPrendas() {
        // Delegar a modal-seleccion-prendas.js
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        console.log('🎯 [GestionItemsUI] abrirModalAgregarPrendaNueva() - abriendo modal');
        
        // Delegar a modal correspondiente
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            console.log('✅ [GestionItemsUI] Modal encontrado, abriendo...');
            modal.style.display = 'flex';
            
            // Asegurar que el formulario esté limpio
            const form = document.getElementById('form-prenda-nueva');
            if (form) {
                form.reset();
                console.log('🧹 [GestionItemsUI] Formulario limpiado');
            }
            
            // Limpiar storage de imágenes
            if (window.imagenesPrendaStorage) {
                window.imagenesPrendaStorage.limpiar();
                console.log('🧹 [GestionItemsUI] Storage de imágenes de prenda limpiado');
            }
        } else {
            console.error('❌ [GestionItemsUI] Modal no encontrado');
        }
    }

    agregarPrendaNueva() {
        console.log('➕ [GestionItemsUI] agregarPrendaNueva() - procesando prenda nueva');
        
        // Recopilar datos del formulario
        const nombrePrenda = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const origen = document.getElementById('nueva-prenda-origen-select')?.value;
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        
        // ✅ Determinar género basándose en el estado de tallas existente
        let genero = null;
        const tallasSeleccionadas = window.tallasSeleccionadas || {};
        
        // Verificar qué géneros tienen tallas seleccionadas
        const tienetallasDama = tallasSeleccionadas.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas.caballero?.tallas?.length > 0;
        
        if (tienetallasDama && !tieneTallasCaballero) {
            genero = 'dama';
        } else if (tieneTallasCaballero && !tienetallasDama) {
            genero = 'caballero';
        } else if (tienetallasDama && tieneTallasCaballero) {
            // Si hay tallas en ambos, es multi-género
            genero = 'unisex';
        }
        
        console.log('📋 [GestionItemsUI] Datos recopilados:', { nombrePrenda, origen, genero, tallasSeleccionadas });
        
        // Validación básica
        if (!nombrePrenda) {
            alert('Por favor ingresa el nombre de la prenda');
            document.getElementById('nueva-prenda-nombre')?.focus();
            return;
        }
        
        if (!genero) {
            alert('Por favor selecciona tallas para la prenda');
            return;
        }
        
        // Obtener imágenes del storage
        const imagenesPrenda = window.imagenesPrendaStorage?.obtenerImagenes() || [];
        console.log(`📸 [GestionItemsUI] Imágenes de prenda: ${imagenesPrenda.length}`);
        
        // Obtener procesos configurados (reflectivo, bordado, estampado, etc.)
        const procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
        console.log(`🎨 [GestionItemsUI] Procesos configurables:`, procesosConfigurables);
        
        // ✅ Obtener tallas del estado de gestion-tallas.js
        const tallasPorGenero = [];
        if (tienetallasDama) {
            tallasPorGenero.push({
                genero: 'dama',
                tallas: tallasSeleccionadas.dama.tallas,
                tipo: tallasSeleccionadas.dama.tipo
            });
        }
        if (tieneTallasCaballero) {
            tallasPorGenero.push({
                genero: 'caballero',
                tallas: tallasSeleccionadas.caballero.tallas,
                tipo: tallasSeleccionadas.caballero.tipo
            });
        }
        console.log(`📏 [GestionItemsUI] Tallas por género:`, tallasPorGenero);
        
        // Crear objeto de prenda
        const prendaNueva = {
            nombre_producto: nombrePrenda,
            descripcion: descripcion || '',
            genero: genero,
            origen: origen || 'bodega',
            imagenes: imagenesPrenda.map(img => ({
                file: img.file,
                nombre: img.nombre,
                tamaño: img.tamaño
            })),
            telas: [],
            tallas: tallasPorGenero,
            variaciones: {},
            procesos: procesosConfigurables,
            cantidadesPorTalla: {}
        };
        
        console.log('✅ [GestionItemsUI] Prenda nueva creada:', prendaNueva);
        
        // Inicializar gestor de prenda sin cotización si no existe
        if (!window.gestorPrendaSinCotizacion) {
            window.inicializarGestorPrendaSinCotizacion?.();
        }
        
        // Agregar a gestor CON los datos creados
        if (window.gestorPrendaSinCotizacion?.agregarPrenda) {
            window.gestorPrendaSinCotizacion.agregarPrenda(prendaNueva);
            console.log('✅ [GestionItemsUI] Prenda agregada al gestor con datos');
        }
        
        // Renderizar UI
        if (window.renderizarPrendasTipoPrendaSinCotizacion) {
            window.renderizarPrendasTipoPrendaSinCotizacion();
            console.log('✅ [GestionItemsUI] UI renderizada');
        }
        
        // Cerrar modal
        cerrarModalPrendaNueva();
        
        // Mostrar notificación
        this.mostrarNotificacion('Prenda agregada correctamente', 'success');
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            // Validación local del cliente
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.mostrarNotificacion('El cliente es requerido', 'error');
                clienteInput?.focus();
                return;
            }

            // Recolectar datos del formulario
            const pedidoData = this.recolectarDatosPedido();

            // Validar que haya items
            if (!pedidoData.items || pedidoData.items.length === 0) {
                this.mostrarNotificacion('Debe agregar al menos un item al pedido', 'error');
                return;
            }

            // Validar pedido
            const validacion = await this.api.validarPedido(pedidoData);
            
            if (!validacion.valid) {
                const errores = validacion.errores.join('\n');
                alert('Errores en el pedido:\n' + errores);
                return;
            }

            // Crear pedido
            const resultado = await this.api.crearPedido(pedidoData);

            if (resultado.success) {
                this.mostrarNotificacion('Pedido creado correctamente ✓', 'success');
                // Redirigir inmediatamente
                setTimeout(() => {
                    window.location.href = '/asesores/pedidos-produccion';
                }, 800);
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    recolectarDatosPedido() {
        const items = window.itemsPedido || [];
        
        // Convertir items al formato esperado por el backend
        const itemsFormato = items.map(item => {
            const baseItem = {
                tipo: item.tipo,
                prenda: item.prenda?.nombre || item.nombre || '',
                origen: item.origen || 'bodega',
                procesos: item.procesos || [],
                tallas: item.tallas || [],
                variaciones: item.variaciones || {},
            };
            
            // Si tiene imagenes, incluirlas
            if (item.imagenes && item.imagenes.length > 0) {
                baseItem.imagenes = item.imagenes;
            }
            
            // Si es cotizacion, incluir datos de cotizacion
            if (item.tipo === 'cotizacion') {
                baseItem.cotizacion_id = item.id;
                baseItem.numero_cotizacion = item.numero;
                baseItem.cliente = item.cliente;
            }
            
            return baseItem;
        });
        
        // ✅ AGREGAR PRENDAS SIN COTIZACIÓN (gestores)
        // Verificar si hay prendas sin cotización del tipo PRENDA
        if (window.gestorPrendaSinCotizacion && window.gestorPrendaSinCotizacion.obtenerActivas().length > 0) {
            console.log('🔄 Integrando prendas sin cotización (tipo PRENDA)...');
            const prendasSinCot = window.gestorPrendaSinCotizacion.obtenerActivas();
            
            prendasSinCot.forEach((prenda, prendaIndex) => {
                // Construir cantidad_talla desde generosConTallas
                const cantidadTalla = {};
                
                if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
                    // Iterate over each gender's tallas
                    Object.keys(prenda.generosConTallas).forEach(genero => {
                        const tallaDelGenero = prenda.generosConTallas[genero];
                        Object.keys(tallaDelGenero).forEach(talla => {
                            const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                            if (cantidad > 0) {
                                cantidadTalla[talla] = cantidad;
                            }
                        });
                    });
                } else if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
                    // Fallback: usar cantidadesPorTalla si existe
                    Object.keys(prenda.cantidadesPorTalla).forEach(talla => {
                        const cantidad = parseInt(prenda.cantidadesPorTalla[talla]) || 0;
                        if (cantidad > 0) {
                            cantidadTalla[talla] = cantidad;
                        }
                    });
                }
                
                // Construir variaciones
                const variaciones = {
                    manga: {
                        tipo: prenda.tipo_manga || 'No aplica',
                        observacion: prenda.obs_manga || ''
                    },
                    bolsillos: {
                        tiene: prenda.tiene_bolsillos || false,
                        observacion: prenda.obs_bolsillos || ''
                    },
                    broche: {
                        tipo: prenda.tipo_broche || 'No aplica',
                        observacion: prenda.obs_broche || ''
                    },
                    reflectivo: {
                        tiene: prenda.tiene_reflectivo || false,
                        observacion: prenda.obs_reflectivo || ''
                    }
                };
                
                // ✅ EXTRAER OBSERVACIONES para enviar al backend
                // El backend espera estos campos al nivel superior del objeto
                const obs_manga = prenda.obs_manga || variaciones.manga?.observacion || '';
                const obs_bolsillos = prenda.obs_bolsillos || variaciones.bolsillos?.observacion || '';
                const obs_broche = prenda.obs_broche || variaciones.broche?.observacion || '';
                const obs_reflectivo = prenda.obs_reflectivo || variaciones.reflectivo?.observacion || '';
                
                const itemSinCot = {
                    tipo: 'prenda_nueva',
                    prenda: prenda.nombre_producto || '',
                    descripcion: prenda.descripcion || '',
                    genero: prenda.genero || [],
                    cantidad_talla: cantidadTalla,
                    variaciones: variaciones,
                    // ✅ OBSERVACIONES AL NIVEL SUPERIOR
                    obs_manga: obs_manga,
                    obs_bolsillos: obs_bolsillos,
                    obs_broche: obs_broche,
                    obs_reflectivo: obs_reflectivo,
                    origen: prenda.origen || 'bodega', // ✅ USAR ORIGEN DEL GESTOR
                    de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1 // ✅ PASAR de_bodega
                };
                
                // Agregar fotos si existen
                // Primero verificar en fotosNuevas (fotos recién agregadas)
                let fotosParaEnviar = [];
                if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
                    fotosParaEnviar = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
                    console.log(`📸 Fotos encontradas para prenda ${prendaIndex}:`, fotosParaEnviar.length);
                }
                // Si no hay en fotosNuevas, verificar en prenda.fotos
                else if (prenda.fotos && prenda.fotos.length > 0) {
                    fotosParaEnviar = prenda.fotos;
                    console.log(`📸 Fotos encontradas en prenda.fotos:`, fotosParaEnviar.length);
                }
                
                if (fotosParaEnviar.length > 0) {
                    itemSinCot.imagenes = fotosParaEnviar;
                }
                
                // Agregar telas si existen
                if (prenda.telas && prenda.telas.length > 0) {
                    itemSinCot.telas = prenda.telas;
                    console.log(`🧵 Telas encontradas:`, prenda.telas.length);
                }
                
                // Agregar fotos de telas si existen
                if (window.gestorPrendaSinCotizacion?.telasFotosNuevas?.[prendaIndex]) {
                    itemSinCot.telasFotos = window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex];
                    console.log(`📷 Fotos de telas encontradas:`, Object.keys(itemSinCot.telasFotos).length);
                }
                
                itemsFormato.push(itemSinCot);
                console.log('✅ Prenda sin cotización agregada:', itemSinCot);
            });
        }
        
        console.log('📦 Items para enviar:', itemsFormato);
        
        return {
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            items: itemsFormato,
        };
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        const clase = tipo === 'error' ? 'alert-danger' : tipo === 'success' ? 'alert-success' : 'alert-info';
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert ${clase}`;
        notificacion.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 6px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notificacion.textContent = mensaje;

        document.body.appendChild(notificacion);

        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }

    mostrarVistaPreviaFactura() {
        // Crear modal
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
        
        const contenedor = document.createElement('div');
        contenedor.style.cssText = 'background: white; border-radius: 12px; width: 90%; max-width: 1000px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
        
        // Header
        const header = document.createElement('div');
        header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 2rem; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;';
        
        const titulo = document.createElement('h2');
        titulo.textContent = '📋 Vista Previa del Pedido';
        titulo.style.cssText = 'margin: 0; font-size: 1.5rem;';
        header.appendChild(titulo);
        
        const btnCerrar = document.createElement('button');
        btnCerrar.innerHTML = '✕';
        btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; padding: 0.75rem 1.25rem; cursor: pointer; font-size: 1.5rem; font-weight: bold;';
        btnCerrar.onclick = () => modal.remove();
        header.appendChild(btnCerrar);
        
        contenedor.appendChild(header);
        
        // Contenido
        const contenido = document.createElement('div');
        contenido.style.cssText = 'padding: 2rem;';
        
        // Información del pedido
        const infoPedido = document.createElement('div');
        infoPedido.style.cssText = 'background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #0066cc;';
        
        const cliente = document.getElementById('cliente_editable')?.value || 'No especificado';
        const asesora = document.getElementById('asesora_editable')?.value || 'No especificado';
        const forma = document.getElementById('forma_de_pago_editable')?.value || 'No especificado';
        
        infoPedido.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Cliente</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${cliente}</p>
                </div>
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Asesora</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${asesora}</p>
                </div>
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Forma de Pago</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${forma}</p>
                </div>
            </div>
        `;
        
        contenido.appendChild(infoPedido);
        
        // Ítems
        const tituloItems = document.createElement('h3');
        tituloItems.textContent = 'Ítems del Pedido';
        tituloItems.style.cssText = 'color: #1f2937; font-size: 1.25rem; margin: 0 0 1.5rem 0; padding-bottom: 0.75rem; border-bottom: 2px solid #0066cc;';
        contenido.appendChild(tituloItems);
        
        if (window.itemsPedido && window.itemsPedido.length > 0) {
            const itemsContainer = document.createElement('div');
            itemsContainer.style.cssText = 'display: grid; grid-template-columns: 1fr; gap: 1rem;';
            
            window.itemsPedido.forEach((item, idx) => {
                const itemDiv = document.createElement('div');
                itemDiv.style.cssText = 'background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;';
                
                let tallasTex = '';
                if (item.tallas && Array.isArray(item.tallas)) {
                    const tallasPorGenero = {};
                    item.tallas.forEach(t => {
                        const genero = t.genero || 'sin-genero';
                        if (!tallasPorGenero[genero]) tallasPorGenero[genero] = [];
                        tallasPorGenero[genero].push(`${t.talla}: ${t.cantidad}`);
                    });
                    const generoArray = [];
                    Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
                        if (genero !== 'sin-genero') {
                            generoArray.push(`<strong>${genero.toUpperCase()}:</strong> ${tallas.join(', ')}`);
                        } else {
                            generoArray.push(tallas.join(', '));
                        }
                    });
                    tallasTex = generoArray.join(' | ');
                }
                
                itemDiv.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 1.15rem;">${idx + 1}. ${item.prenda?.nombre || 'Prenda'}</h4>
                            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                <strong>Origen:</strong> ${item.origen === 'bodega' ? '🏭 BODEGA' : '🪡 CONFECCIÓN'}
                            </p>
                            ${item.procesos?.length > 0 ? `
                                <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                    <strong>Procesos:</strong> ${item.procesos.join(', ')}
                                </p>
                            ` : ''}
                            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                <strong>Tallas:</strong> ${tallasTex}
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div style="background: #fef3c7; color: #92400e; padding: 0.75rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 1.1rem;">
                                📦 ${item.tallas?.reduce((sum, t) => sum + t.cantidad, 0) || 0} unidades
                            </div>
                        </div>
                    </div>
                `;
                
                itemsContainer.appendChild(itemDiv);
            });
            
            contenido.appendChild(itemsContainer);
        } else {
            const vacio = document.createElement('p');
            vacio.textContent = 'No hay ítems agregados';
            vacio.style.cssText = 'color: #6b7280; text-align: center; padding: 2rem;';
            contenido.appendChild(vacio);
        }
        
        // Botón de acción
        const footer = document.createElement('div');
        footer.style.cssText = 'padding: 2rem; display: flex; justify-content: space-between; gap: 1rem; border-top: 1px solid #e5e7eb;';
        
        const btnImpreso = document.createElement('button');
        btnImpreso.textContent = '🖨️ Imprimir';
        btnImpreso.style.cssText = 'background: #6366f1; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; font-size: 1rem;';
        btnImpreso.onclick = () => window.print();
        footer.appendChild(btnImpreso);
        
        const btnContinuar = document.createElement('button');
        btnContinuar.textContent = '✓ Continuar y Crear Pedido';
        btnContinuar.style.cssText = 'background: #10b981; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; font-size: 1rem;';
        btnContinuar.onclick = () => {
            modal.remove();
            document.getElementById('formCrearPedidoEditable')?.submit();
        };
        footer.appendChild(btnContinuar);
        
        contenedor.appendChild(contenido);
        contenedor.appendChild(footer);
        
        modal.appendChild(contenedor);
        document.body.appendChild(modal);
        
        // Cerrar al hacer click fuera
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionItemsUI = new GestionItemsUI();
});


