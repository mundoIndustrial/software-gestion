/**
 * Módulo: Modal de Selección de Prendas
 * Maneja el modal para seleccionar prendas desde cotizaciones
 */

// Variables globales del modal
let prendasCotizacion = [];
let prendasSeleccionadas = [];
let cotizacionActual = null;

/**
 * Abrir modal con prendas de cotización
 */
window.abrirModalSeleccionPrendas = function(cotizacion) {

    
    cotizacionActual = cotizacion;
    prendasSeleccionadas = [];
    prendasCotizacion = [];
    
    // Inicializar itemsPedido si no existe
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    // Mostrar información de la cotización en el modal
    const numeroCot = document.getElementById('modal-cot-numero');
    const clienteCot = document.getElementById('modal-cot-cliente');
    
    if (numeroCot) {
        numeroCot.textContent = cotizacion.numero_cotizacion || 'N/A';
    }
    if (clienteCot) {
        clienteCot.textContent = cotizacion.cliente || 'Sin cliente';
    }
    
    // Mostrar modal
    const modal = document.getElementById('modal-seleccion-prendas');
    if (modal) {
        modal.style.display = 'flex';
    }
    
    // Cargar datos de la cotización desde el backend
    fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacion.id}`)
        .then(response => response.json())
        .then(data => {





            
            if (data.error) {

                alert('Error: ' + data.error);
                return;
            }
            
            // Combinar prendas normales y prendas técnicas de logo
            let prendasNormales = data.prendas || [];
            let prendasTecnicas = data.prendas_tecnicas || [];
            


            
            prendasCotizacion = [...prendasNormales, ...prendasTecnicas];


            
            // Si no hay prendas, mostrar mensaje
            if (prendasCotizacion.length === 0) {




            }
            
            renderizarPrendasModal();
        })
        .catch(error => {

            alert('Error al cargar las prendas de la cotización');
        });
};

/**
 * Renderizar prendas en el modal
 */
function renderizarPrendasModal() {
    const listaPrendas = document.getElementById('lista-prendas-modal');
    if (!listaPrendas) {

        return;
    }
    
    listaPrendas.innerHTML = '';
    

    
    if (prendasCotizacion.length === 0) {
        listaPrendas.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 2rem;">No hay prendas disponibles en esta cotización</p>';
        return;
    }
    
    prendasCotizacion.forEach((prenda, index) => {
        const prendaDiv = document.createElement('div');
        prendaDiv.className = 'prenda-item-modal';
        prendaDiv.style.cssText = 'padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem;';
        
        // Detectar procesos de la prenda
        const procesos = detectarProcesos(prenda);
        const procesosTexto = procesos.length > 0 ? procesos.join(', ') : 'Sin procesos';
        
        // Calcular cantidad total
        const cantidad = calcularCantidadTotal(prenda);
        
        // Determinar nombre de la prenda
        const nombrePrenda = prenda.nombre_producto || prenda.nombre || 'Prenda sin nombre';
        
        prendaDiv.innerHTML = `
            <div style="display: flex; align-items: start; gap: 1rem;">
                <input type="checkbox" 
                       id="prenda-${index}" 
                       onchange="window.togglePrendaSeleccion(${index})"
                       style="width: 20px; height: 20px; cursor: pointer; margin-top: 0.25rem;">
                <div style="flex: 1;">
                    <label for="prenda-${index}" style="cursor: pointer; font-weight: 600; font-size: 1rem; color: #1e40af; margin-bottom: 0.5rem; display: block;">
                        ${nombrePrenda}
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 12px; font-size: 0.875rem;">
                             ${cantidad} unidades
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem;">
                             ${procesosTexto}
                        </span>
                    </div>
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: #f9fafb; border-radius: 6px;">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">Origen de la prenda:</label>
                        <div style="display: flex; gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" 
                                       name="origen-${index}" 
                                       value="bodega" 
                                       onchange="window.actualizarOrigenPrenda(${index}, 'bodega')"
                                       ${prenda.prenda_bodega === 1 || prenda.prenda_bodega === true ? 'checked' : ''}
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span>🏪 Bodega</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" 
                                       name="origen-${index}" 
                                       value="confeccion" 
                                       onchange="window.actualizarOrigenPrenda(${index}, 'confeccion')"
                                       ${prenda.prenda_bodega === 0 || prenda.prenda_bodega === false ? 'checked' : ''}
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span>✂️ Confección</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        listaPrendas.appendChild(prendaDiv);
    });
    

}

/**
 * Detectar procesos de una prenda
 */
function detectarProcesos(prenda) {
    const procesos = [];
    
    // Para prendas técnicas de logo
    if (prenda.tipo_logo_nombre) {
        const tipoLogo = prenda.tipo_logo_nombre.toLowerCase();
        if (tipoLogo.includes('bordado')) procesos.push('Bordado');
        if (tipoLogo.includes('estampado')) procesos.push('Estampado');
        if (tipoLogo.includes('dtf')) procesos.push('DTF');
        if (tipoLogo.includes('sublimado')) procesos.push('Sublimado');
        if (tipoLogo.includes('reflectivo')) procesos.push('Reflectivo');
    }
    
    // Para prendas normales (desde variantes)
    if (prenda.variantes) {
        const variante = Array.isArray(prenda.variantes) ? prenda.variantes[0] : prenda.variantes;
        if (variante) {
            if (variante.aplica_bordado) procesos.push('Bordado');
            if (variante.aplica_estampado) procesos.push('Estampado');
            if (variante.tiene_reflectivo) procesos.push('Reflectivo');
        }
    }
    
    return procesos;
}

/**
 * Toggle selección de prenda
 */
window.togglePrendaSeleccion = function(index) {
    const checkbox = document.getElementById(`prenda-${index}`);
    const prenda = prendasCotizacion[index];
    
    if (checkbox.checked) {
        // Determinar origen según prenda_bodega
        const origenInicial = prenda.prenda_bodega === 1 || prenda.prenda_bodega === true ? 'bodega' : 'confeccion';
        
        // Agregar a seleccionadas con origen correcto
        prendasSeleccionadas.push({
            index: index,
            prenda: prenda,
            origen: origenInicial
        });

    } else {
        // Remover de seleccionadas
        prendasSeleccionadas = prendasSeleccionadas.filter(p => p.index !== index);

    }
    

};

/**
 * Actualizar origen de prenda
 */
window.actualizarOrigenPrenda = function(index, origen) {
    const prendaSeleccionada = prendasSeleccionadas.find(p => p.index === index);
    if (prendaSeleccionada) {
        prendaSeleccionada.origen = origen;

    }
};

/**
 * Cerrar modal
 */
window.cerrarModalPrendas = function() {
    const modal = document.getElementById('modal-seleccion-prendas');
    if (modal) {
        modal.style.display = 'none';
    }
    prendasSeleccionadas = [];
    prendasCotizacion = [];
    cotizacionActual = null;
};

/**
 * Agregar prendas seleccionadas al pedido
 */
window.agregarPrendasSeleccionadas = function() {

    
    if (prendasSeleccionadas.length === 0) {
        alert('Por favor selecciona al menos una prenda');
        return;
    }
    
    // Inicializar itemsPedido si no existe
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    prendasSeleccionadas.forEach(({ prenda, origen }) => {
        const procesos = detectarProcesos(prenda);
        const cantidad = calcularCantidadTotal(prenda);
        const nombrePrenda = prenda.nombre_producto || prenda.nombre_prenda || 'Prenda sin nombre';
        
        // Estructura COMPLETA de la prenda con TODA la información
        const prendaData = {
            nombre: nombrePrenda,
            cantidad: cantidad,
            descripcion: prenda.descripcion || '',
            texto_personalizado_tallas: prenda.texto_personalizado_tallas || '',
            
            // Telas COMPLETAS
            telas: prenda.telas && Array.isArray(prenda.telas) ? prenda.telas.map(t => ({
                id: t.id,
                color: t.color ? { id: t.color.id, nombre: t.color.nombre } : null,
                tela: t.tela ? { id: t.tela.id, nombre: t.tela.nombre } : null,
                referencia: t.referencia || '',
                fotos: t.fotos || []
            })) : [],
            
            // Fotos de la prenda
            fotos: prenda.fotos || [],
            
            // Variaciones/Especificaciones COMPLETAS
            variaciones: prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0
                ? {
                    aplica_manga: prenda.variantes[0].aplica_manga || false,
                    tipo_manga_id: prenda.variantes[0].tipo_manga_id,
                    obs_manga: prenda.variantes[0].obs_manga || '',
                    
                    tiene_bolsillos: prenda.variantes[0].tiene_bolsillos || false,
                    obs_bolsillos: prenda.variantes[0].obs_bolsillos || '',
                    
                    aplica_broche: prenda.variantes[0].aplica_broche || false,
                    tipo_broche_id: prenda.variantes[0].tipo_broche_id,
                    obs_broche: prenda.variantes[0].obs_broche || '',
                    
                    tiene_reflectivo: prenda.variantes[0].tiene_reflectivo || false,
                    obs_reflectivo: prenda.variantes[0].obs_reflectivo || ''
                }
                : {
                    aplica_manga: false,
                    tiene_bolsillos: false,
                    aplica_broche: false,
                    tiene_reflectivo: false,
                    obs_manga: '',
                    obs_bolsillos: '',
                    obs_broche: '',
                    obs_reflectivo: ''
                }
        };
        
        // Convertir tallas al formato esperado (MANTENER COMPATIBILIDAD)
        let tallas = [];
        if (prenda.talla_cantidad) {
            try {
                let tallasData = prenda.talla_cantidad;
                if (typeof tallasData === 'string') {
                    tallasData = JSON.parse(tallasData);
                }
                if (Array.isArray(tallasData) && tallasData.length > 0) {
                    tallas = tallasData.map(t => ({
                        talla: t.talla || t,
                        cantidad: t.cantidad || 0
                    }));
                }
            } catch (e) {}
        }
        
        if (tallas.length === 0 && prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
            tallas = prenda.tallas.map(t => ({
                talla: t.talla || t,
                cantidad: t.cantidad || 0
            }));
        }
        
        
        // REGLA DE SPLIT: Si tiene procesos, crear 2 ítems
        if (procesos.length > 0) {
            // ÍTEM 1: Prenda BASE (sin procesos)
            window.itemsPedido.push({
                tipo: 'cotizacion',
                id: cotizacionActual.id,
                numero: cotizacionActual.numero_cotizacion,
                cliente: cotizacionActual.cliente,
                prenda: prendaData,
                origen: origen,
                procesos: [],
                es_proceso: false,
                tallas: tallas,
                data: cotizacionActual
            });
            
            // ÍTEM 2: Prenda PROCESO (con procesos)
            window.itemsPedido.push({
                tipo: 'cotizacion',
                id: cotizacionActual.id,
                numero: cotizacionActual.numero_cotizacion,
                cliente: cotizacionActual.cliente,
                prenda: prendaData,
                origen: origen,
                procesos: procesos,
                es_proceso: true,
                tallas: tallas,
                data: cotizacionActual
            });
            

        } else {
            // Sin procesos: 1 solo ítem
            window.itemsPedido.push({
                tipo: 'cotizacion',
                id: cotizacionActual.id,
                numero: cotizacionActual.numero_cotizacion,
                cliente: cotizacionActual.cliente,
                prenda: prendaData,
                origen: origen,
                procesos: [],
                es_proceso: false,
                tallas: tallas,
                data: cotizacionActual
            });
            

        }
    });
    

    
    // Actualizar vista - Renderizar items directamente en el DOM
    console.log('📝 Intentando renderizar items en el DOM...');
    renderizarItemsCotizacionEnDOM();
    
    // Cerrar modal
    window.cerrarModalPrendas();
};

/**
 * Calcular cantidad total de una prenda
 */
function calcularCantidadTotal(prenda) {

    
    // Si tiene cantidad directa, usarla
    if (prenda.cantidad && typeof prenda.cantidad === 'number') {

        return prenda.cantidad;
    }
    
    // Si tiene talla_cantidad (array de objetos)
    if (prenda.talla_cantidad && Array.isArray(prenda.talla_cantidad)) {
        const total = prenda.talla_cantidad.reduce((sum, t) => sum + (t.cantidad || 0), 0);

        return total;
    }
    
    // Si tiene tallas (array de objetos)
    if (prenda.tallas && Array.isArray(prenda.tallas)) {
        const total = prenda.tallas.reduce((sum, t) => sum + (t.cantidad || 0), 0);

        return total;
    }
    

    return 0;
}

/**
 * Renderizar items de cotización en el DOM
 */
function renderizarItemsCotizacionEnDOM() {
    const container = document.getElementById('lista-items-pedido');
    
    if (!container) {
        console.error('❌ Contenedor lista-items-pedido no encontrado');
        return;
    }
    
    if (!window.itemsPedido || window.itemsPedido.length === 0) {
        console.warn('⚠️ No hay items para renderizar');
        return;
    }
    
    console.log('📦 Renderizando', window.itemsPedido.length, 'items en el DOM');
    
    // Renderizar cada item
    window.itemsPedido.forEach((item, idx) => {
        const itemDiv = document.createElement('div');
        itemDiv.id = `item-cotizacion-${idx}`;
        itemDiv.className = 'prenda-card';
        itemDiv.style.cssText = `
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        `;
        
        // Información básica de la prenda
        const nombrePrenda = item.prenda?.nombre || 'Prenda sin nombre';
        const cantidad = item.prenda?.cantidad || 0;
        const origen = item.origen === 'bodega' ? '🏪 Bodega' : '✂️ Confección';
        const procesosTexto = item.procesos && item.procesos.length > 0 ? item.procesos.join(', ') : 'Sin procesos';
        const tipoItem = item.es_proceso ? '🔧 Proceso' : '👕 Prenda Base';
        
        // Información adicional de la prenda
        const descripcion = item.prenda?.variaciones?.descripcion || '';
        const telas = item.prenda?.telas || [];
        const fotos = item.prenda?.fotos || [];
        const variaciones = item.prenda?.variaciones || {};
        
        // Construir HTML de telas
        let htmlTelas = '';
        if (Array.isArray(telas) && telas.length > 0) {
            htmlTelas = '<div style="margin-top: 1rem; padding: 0.75rem; background: #f0f7ff; border-radius: 6px;"><strong style="color: #0066cc;">🎨 Telas:</strong><ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">';
            telas.forEach(tela => {
                const colorNombre = tela.color?.nombre || 'Sin color';
                const telaNombre = tela.tela?.nombre || 'Sin tela';
                const referencia = tela.referencia ? ` - Ref: ${tela.referencia}` : '';
                htmlTelas += `<li style="font-size: 0.85rem; color: #374151;">${colorNombre} / ${telaNombre}${referencia}</li>`;
            });
            htmlTelas += '</ul></div>';
        }
        
        // Construir HTML de fotos
        let htmlFotos = '';
        if (Array.isArray(fotos) && fotos.length > 0) {
            htmlFotos = '<div style="margin-top: 0.75rem;"><strong style="color: #0066cc; font-size: 0.9rem;">📷 Fotos:</strong><div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">';
            fotos.forEach(foto => {
                htmlFotos += `<img src="${foto}" alt="Foto prenda" style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover; border: 1px solid #ddd;">`;
            });
            htmlFotos += '</div></div>';
        }
        
        // Construir HTML de variaciones
        let htmlVariaciones = '';
        if (Object.keys(variaciones).length > 0) {
            htmlVariaciones = '<div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-radius: 6px;"><strong style="color: #92400e;">⚙️ Especificaciones:</strong><ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.85rem;">';
            
            if (variaciones.aplica_manga && variaciones.obs_manga) {
                htmlVariaciones += `<li style="color: #374151;">👔 Manga: ${variaciones.obs_manga}</li>`;
            }
            if (variaciones.tiene_bolsillos && variaciones.obs_bolsillos) {
                htmlVariaciones += `<li style="color: #374151;">📦 Bolsillos: ${variaciones.obs_bolsillos}</li>`;
            }
            if (variaciones.aplica_broche && variaciones.obs_broche) {
                htmlVariaciones += `<li style="color: #374151;">🔗 Broche: ${variaciones.obs_broche}</li>`;
            }
            if (variaciones.tiene_reflectivo && variaciones.obs_reflectivo) {
                htmlVariaciones += `<li style="color: #374151;">✨ Reflectivo: ${variaciones.obs_reflectivo}</li>`;
            }
            
            htmlVariaciones += '</ul></div>';
        }
        
        itemDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 700; color: #1f2937;">
                        ${nombrePrenda.toUpperCase()}
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 12px; font-size: 0.875rem;">
                            📦 ${cantidad} unidades
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem;">
                            ${procesosTexto}
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #f0fdf4; color: #15803d; border-radius: 12px; font-size: 0.875rem;">
                            ${origen}
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #fef3c7; color: #92400e; border-radius: 12px; font-size: 0.875rem;">
                            ${tipoItem}
                        </span>
                    </div>
                    
                    ${descripcion ? `<div style="margin-top: 0.5rem; padding: 0.75rem; background: #f9fafb; border-left: 3px solid #0066cc; border-radius: 4px;"><strong style="color: #0066cc;">📝 Descripción:</strong><p style="margin: 0.25rem 0 0 0; color: #4b5563; font-size: 0.875rem;">${descripcion}</p></div>` : ''}
                    
                    ${htmlTelas}
                    ${htmlVariaciones}
                    ${htmlFotos}
                    
                    ${item.numero ? `<p style="margin: 0.75rem 0 0 0; color: #6b7280; font-size: 0.875rem;">📋 Cotización: <strong>${item.numero}</strong></p>` : ''}
                    ${item.cliente ? `<p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">👤 Cliente: <strong>${item.cliente}</strong></p>` : ''}
                </div>
                <button onclick="eliminarItemCotizacion(${idx})" 
                        style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;"
                        onmouseover="this.style.backgroundColor='#dc2626'"
                        onmouseout="this.style.backgroundColor='#ef4444'">
                    🗑️ Eliminar
                </button>
            </div>
        `;
        
        container.appendChild(itemDiv);
        console.log(`✓ Item ${idx} renderizado: ${nombrePrenda}`);
    });
    
    // Ocultar mensaje de sin items
    const mensajeSinItems = document.getElementById('mensaje-sin-items');
    if (mensajeSinItems && window.itemsPedido.length > 0) {
        mensajeSinItems.style.display = 'none';
    }
}

/**
 * Eliminar item de cotización
 */
window.eliminarItemCotizacion = function(index) {
    if (!confirm('¿Eliminar este ítem?')) return;
    
    if (window.itemsPedido && window.itemsPedido[index]) {
        window.itemsPedido.splice(index, 1);
        
        // Re-renderizar
        const container = document.getElementById('lista-items-pedido');
        if (container) {
            container.innerHTML = '';
            renderizarItemsCotizacionEnDOM();
        }
        
        console.log('✓ Ítem eliminado');
    }
};
