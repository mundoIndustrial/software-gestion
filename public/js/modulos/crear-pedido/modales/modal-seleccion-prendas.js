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
    console.log('🎨 Abriendo modal para cotización:', cotizacion.numero_cotizacion);
    
    cotizacionActual = cotizacion;
    prendasSeleccionadas = [];
    prendasCotizacion = [];
    
    // Mostrar modal
    const modal = document.getElementById('modal-seleccion-prendas');
    if (modal) {
        modal.style.display = 'flex';
    }
    
    // Cargar datos de la cotización desde el backend
    fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacion.id}`)
        .then(response => response.json())
        .then(data => {
            console.log('📦 Datos recibidos del backend:', data);
            console.log('🔍 Tipo de cotización:', data.tipo_cotizacion_codigo);
            console.log('🔍 Tiene prendas normales:', data.prendas?.length || 0);
            console.log('🔍 Tiene prendas técnicas (logo):', data.prendas_tecnicas?.length || 0);
            console.log('🔍 Tiene logo:', data.logo ? 'Sí' : 'No');
            
            if (data.error) {
                console.error('❌ Error al cargar prendas:', data.error);
                alert('Error: ' + data.error);
                return;
            }
            
            // Combinar prendas normales y prendas técnicas de logo
            let prendasNormales = data.prendas || [];
            let prendasTecnicas = data.prendas_tecnicas || [];
            
            console.log('🔍 Prendas normales:', prendasNormales);
            console.log('🔍 Prendas técnicas:', prendasTecnicas);
            
            prendasCotizacion = [...prendasNormales, ...prendasTecnicas];
            console.log('📋 Total prendas extraídas:', prendasCotizacion.length);
            console.log('📋 Prendas:', prendasCotizacion);
            
            // Si no hay prendas, mostrar mensaje
            if (prendasCotizacion.length === 0) {
                console.warn('⚠️ ADVERTENCIA: Esta cotización no tiene prendas asociadas');
                console.warn('   - Prendas normales:', prendasNormales.length);
                console.warn('   - Prendas técnicas:', prendasTecnicas.length);
                console.warn('   - Tipo de cotización:', data.tipo_cotizacion_codigo);
            }
            
            renderizarPrendasModal();
        })
        .catch(error => {
            console.error('❌ Error al cargar prendas:', error);
            alert('Error al cargar las prendas de la cotización');
        });
};

/**
 * Renderizar prendas en el modal
 */
function renderizarPrendasModal() {
    const listaPrendas = document.getElementById('lista-prendas-modal');
    if (!listaPrendas) {
        console.error('❌ No se encontró elemento lista-prendas-modal');
        return;
    }
    
    listaPrendas.innerHTML = '';
    
    console.log('🎨 Renderizando prendas en modal. Total:', prendasCotizacion.length);
    
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
        const nombrePrenda = prenda.nombre_producto || prenda.nombre_prenda || 'Prenda sin nombre';
        
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
                            📦 ${cantidad} unidades
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem;">
                            🔧 ${procesosTexto}
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
                                       checked
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span>🏪 Bodega</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" 
                                       name="origen-${index}" 
                                       value="confeccion" 
                                       onchange="window.actualizarOrigenPrenda(${index}, 'confeccion')"
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
    
    console.log('✅ Prendas renderizadas en modal');
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
        // Agregar a seleccionadas con origen por defecto
        prendasSeleccionadas.push({
            index: index,
            prenda: prenda,
            origen: 'bodega' // Por defecto bodega
        });
        console.log('✅ Prenda seleccionada:', prenda.nombre_producto || prenda.nombre_prenda);
    } else {
        // Remover de seleccionadas
        prendasSeleccionadas = prendasSeleccionadas.filter(p => p.index !== index);
        console.log('❌ Prenda deseleccionada:', prenda.nombre_producto || prenda.nombre_prenda);
    }
    
    console.log('📊 Total prendas seleccionadas:', prendasSeleccionadas.length);
};

/**
 * Actualizar origen de prenda
 */
window.actualizarOrigenPrenda = function(index, origen) {
    const prendaSeleccionada = prendasSeleccionadas.find(p => p.index === index);
    if (prendaSeleccionada) {
        prendaSeleccionada.origen = origen;
        console.log('🔄 Origen actualizado para prenda:', origen);
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
    console.log('➕ Agregando prendas seleccionadas. Total:', prendasSeleccionadas.length);
    
    if (prendasSeleccionadas.length === 0) {
        alert('Por favor selecciona al menos una prenda');
        return;
    }
    
    prendasSeleccionadas.forEach(({ prenda, origen }) => {
        const procesos = detectarProcesos(prenda);
        const cantidad = calcularCantidadTotal(prenda);
        const nombrePrenda = prenda.nombre_producto || prenda.nombre_prenda || 'Prenda sin nombre';
        
        // Construir variaciones desde el objeto prenda
        const variaciones = {};
        
        // Para prendas técnicas de logo: variaciones_prenda viene en JSON
        if (prenda.variaciones_prenda) {
            try {
                const vars = typeof prenda.variaciones_prenda === 'string' 
                    ? JSON.parse(prenda.variaciones_prenda) 
                    : prenda.variaciones_prenda;
                
                variaciones.tela = vars.tela || '';
                variaciones.color = vars.color || '';
                variaciones.referencia = vars.referencia || '';
                variaciones.manga = vars.manga || '';
                variaciones.broche = vars.broche || '';
                variaciones.bolsillos = vars.bolsillos || 'No';
            } catch (e) {
                console.warn('Error al parsear variaciones_prenda:', e);
            }
        }
        // Para prendas normales: variantes es un array
        else if (prenda.variantes && prenda.variantes.length > 0) {
            const v = prenda.variantes[0];
            if (v.telas_multiples && Array.isArray(v.telas_multiples) && v.telas_multiples.length > 0) {
                const tela = v.telas_multiples[0];
                variaciones.tela = tela.nombre_tela || tela.color || '';
                variaciones.color = v.color || '';
                variaciones.referencia = tela.referencia || '';
            }
            variaciones.manga = v.tipo_manga || '';
            variaciones.broche = v.tipo_broche || '';
            variaciones.bolsillos = v.tiene_bolsillos ? 'Sí' : 'No';
        }
        
        // Convertir tallas al formato esperado
        let tallas = [];
        console.log('🔍 Buscando tallas en prenda:', {
            tiene_tallas: !!prenda.tallas,
            tiene_talla_cantidad: !!prenda.talla_cantidad,
            tallas: prenda.tallas,
            talla_cantidad: prenda.talla_cantidad
        });
        
        // Intentar desde talla_cantidad (puede ser JSON string o array)
        if (prenda.talla_cantidad) {
            try {
                let tallasData = prenda.talla_cantidad;
                
                // Si es string JSON, parsear
                if (typeof tallasData === 'string') {
                    tallasData = JSON.parse(tallasData);
                }
                
                // Si es array, convertir al formato esperado
                if (Array.isArray(tallasData) && tallasData.length > 0) {
                    tallas = tallasData.map(t => ({
                        talla: t.talla || t,
                        cantidad: t.cantidad || 0
                    }));
                    console.log('✅ Tallas desde prenda.talla_cantidad (JSON):', tallas);
                }
            } catch (e) {
                console.warn('⚠️ Error al parsear talla_cantidad:', e);
            }
        }
        
        // Si no encontró en talla_cantidad, intentar desde tallas
        if (tallas.length === 0 && prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
            tallas = prenda.tallas.map(t => ({
                talla: t.talla || t,
                cantidad: t.cantidad || 0
            }));
            console.log('✅ Tallas desde prenda.tallas:', tallas);
        }
        
        if (tallas.length === 0) {
            console.warn('⚠️ No se encontraron tallas en la prenda');
        }
        
        // Estructura de la prenda para el ítem
        const prendaData = {
            nombre: nombrePrenda,
            cantidad: cantidad,
            tallas: tallas,
            variaciones: variaciones
        };
        
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
            
            console.log(`✅ Prenda "${nombrePrenda}" agregada como 2 ítems (BASE + PROCESO)`);
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
            
            console.log(`✅ Prenda "${nombrePrenda}" agregada como 1 ítem (sin procesos)`);
        }
    });
    
    console.log('📊 Total ítems en pedido:', window.itemsPedido.length);
    
    // Actualizar vista
    window.actualizarVistaItems();
    
    // Cerrar modal
    window.cerrarModalPrendas();
};

/**
 * Calcular cantidad total de una prenda
 */
function calcularCantidadTotal(prenda) {
    console.log('🔢 Calculando cantidad total para:', prenda.nombre_producto || prenda.nombre_prenda);
    
    // Si tiene cantidad directa, usarla
    if (prenda.cantidad && typeof prenda.cantidad === 'number') {
        console.log('  ✅ Usando cantidad directa:', prenda.cantidad);
        return prenda.cantidad;
    }
    
    // Si tiene talla_cantidad (array de objetos)
    if (prenda.talla_cantidad && Array.isArray(prenda.talla_cantidad)) {
        const total = prenda.talla_cantidad.reduce((sum, t) => sum + (t.cantidad || 0), 0);
        console.log('  ✅ Calculado desde talla_cantidad (array):', total);
        return total;
    }
    
    // Si tiene tallas (array de objetos)
    if (prenda.tallas && Array.isArray(prenda.tallas)) {
        const total = prenda.tallas.reduce((sum, t) => sum + (t.cantidad || 0), 0);
        console.log('  ✅ Calculado desde tallas (array):', total);
        return total;
    }
    
    console.log('  ❌ No se pudo calcular, retornando 0');
    return 0;
}

console.log('✅ Módulo modal-seleccion-prendas.js cargado correctamente');
