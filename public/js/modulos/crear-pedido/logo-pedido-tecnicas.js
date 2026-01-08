/**
 * MÓDULO: Logo Pedido - Gestión Dinámica de Prendas Técnicas
 * 
 * Renderiza y gestiona las prendas técnicas en el formulario de pedido de logo
 * Estructura basada en LogoCotizacionTecnicaPrenda y sus fotos
 */

// =========================================================
// 1. VARIABLES GLOBALES
// =========================================================

window.logoPrendasTecnicas = [];  // Array de prendas técnicas cargadas
window.tiposLogosDisponibles = [];
window.logoPedidoId = null;

// Referencias locales para facilitar acceso
let logoPrendasTecnicas = window.logoPrendasTecnicas;
let tiposLogosDisponibles = window.tiposLogosDisponibles;
let logoPedidoId = window.logoPedidoId;

// Función helper para sincronizar cambios
function sincronizarPrendasTecnicas() {
    window.logoPrendasTecnicas = logoPrendasTecnicas;
    window.tiposLogosDisponibles = tiposLogosDisponibles;
    window.logoPedidoId = logoPedidoId;
}

// =========================================================
// 2. CARGAR TIPOS DE TÉCNICAS DISPONIBLES
// =========================================================

async function cargarTiposLogosDisponibles() {
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/tipos-disponibles');
        const data = await response.json();
        
        if (data.success) {
            tiposLogosDisponibles = data.data;
            console.log('✅ Tipos de logo disponibles cargados:', tiposLogosDisponibles.length);
        }
    } catch (error) {
        console.error('❌ Error cargando tipos de logo:', error);
    }
}

// =========================================================
// 3. CARGAR PRENDAS TÉCNICAS DESDE COTIZACIÓN
// =========================================================

function cargarLogoPrendasDesdeCotizacion(prendasTecnicas) {
    console.log('🎨 cargarLogoPrendasDesdeCotizacion() INICIADO');
    console.log('   Prendas recibidas:', prendasTecnicas);
    console.log('   Cantidad de prendas:', prendasTecnicas?.length || 0);
    
    if (!prendasTecnicas || prendasTecnicas.length === 0) {
        console.log('ℹ️ No hay prendas técnicas en esta cotización');
        window.logoPrendasTecnicas = [];
        logoPrendasTecnicas = [];
        return;
    }
    
    console.log(`✅ Procesando ${prendasTecnicas.length} prendas técnicas...`);
    
    // Mapear datos del servidor al formato local
    const prendas = prendasTecnicas.map((prenda, index) => {
        // Convertir talla_cantidad de array a objeto si viene como array
        let tallaCantidad = {};
        if (Array.isArray(prenda.talla_cantidad)) {
            prenda.talla_cantidad.forEach(item => {
                tallaCantidad[item.talla] = item.cantidad;
            });
        } else if (typeof prenda.talla_cantidad === 'object') {
            tallaCantidad = prenda.talla_cantidad || {};
        }
        
        console.log(`   [Prenda ${index}] Estructura recibida:`, {
            id: prenda.id,
            tecnica: prenda.tipo_logo_nombre,
            nombre_prenda: prenda.nombre_prenda,
            ubicaciones: prenda.ubicaciones,
            talla_cantidad: tallaCantidad,
            fotos: prenda.fotos ? prenda.fotos.length : 0,
            observaciones: prenda.observaciones,
        });
        
        return {
            id: prenda.id,
            logo_cotizacion_id: prenda.logo_cotizacion_id,
            tipo_logo_id: prenda.tipo_logo_id,
            tipo_logo_nombre: prenda.tipo_logo_nombre,
            nombre_prenda: prenda.nombre_prenda,
            observaciones: prenda.observaciones,
            ubicaciones: prenda.ubicaciones || [],
            talla_cantidad: tallaCantidad,
            grupo_combinado: prenda.grupo_combinado,
            fotos: prenda.fotos || [],
            existeEnBD: true  // Marcar como que ya existe en BD
        };
    });
    
    // Actualizar ambas referencias
    window.logoPrendasTecnicas = prendas;
    logoPrendasTecnicas = prendas;
    sincronizarPrendasTecnicas();
    
    console.log('✅ Prendas técnicas cargadas COMPLETAMENTE:', logoPrendasTecnicas.length);
    console.log('   Array logoPrendasTecnicas (final):', JSON.stringify(logoPrendasTecnicas, null, 2));
}

// =========================================================
// 4. RENDERIZAR PRENDAS TÉCNICAS EN TABLA
// =========================================================

function renderizarLogoPrendasTecnicas() {
    const container = document.getElementById('logo-prendas-tecnicas-container');
    
    console.log('📊 renderizarLogoPrendasTecnicas() INICIADA');
    console.log('   Container encontrado:', !!container);
    console.log('   Total de prendas:', logoPrendasTecnicas.length);
    console.log('   Array completo:', JSON.stringify(logoPrendasTecnicas, null, 2));
    
    if (!container) {
        console.warn('⚠️ Contenedor #logo-prendas-tecnicas-container no encontrado');
        return;
    }
    
    if (logoPrendasTecnicas.length === 0) {
        container.innerHTML = `
            <div style="padding: 2rem; text-align: center; background: #f5f5f5; border: 1px solid #ddd;">
                <p style="color: #666; margin: 0; font-size: 0.9rem;">No hay prendas técnicas para mostrar</p>
            </div>
        `;
        return;
    }
    
    // Agrupar prendas por grupo_combinado (si existe)
    // Una prenda sin grupo_combinado es un grupo por sí sola
    const grupos = {};
    logoPrendasTecnicas.forEach((prenda, index) => {
        const grupoId = prenda.grupo_combinado || `individual_${index}`;
        console.log(`   [Prenda ${index}] grupo_combinado=${prenda.grupo_combinado}, grupoId=${grupoId}, tipo=${prenda.tipo_logo_nombre}`);
        if (!grupos[grupoId]) {
            grupos[grupoId] = [];
        }
        grupos[grupoId].push({ prenda, index });
    });
    
    console.log('📦 Grupos detectados:', Object.keys(grupos).length);
    Object.entries(grupos).forEach(([grupoId, items]) => {
        const esGrupoCombinado = grupoId.startsWith('combinado_');
        console.log(`   Grupo "${grupoId}": ${items.length} prenda(s), esGrupoCombinado=${esGrupoCombinado}`);
        items.forEach((item, idx) => {
            console.log(`      [${idx}] ${item.prenda.tipo_logo_nombre} - ${item.prenda.nombre_prenda}, fotos=${item.prenda.fotos?.length || 0}, tallas=${Object.keys(item.prenda.talla_cantidad || {}).length}`);
        });
    });
    
    // Renderizar como tabla con agrupación
    let html = `
        <div style="overflow-x: auto; margin-bottom: 2rem;">
            <table style="
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
                font-size: 0.9rem;
                background: white;
            ">
                <thead>
                    <tr style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white;">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Técnica(s)</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Prenda</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Ubicaciones</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Observaciones</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Tallas</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Imágenes</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e3a8a; font-size: 1.1rem; color: white !important;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Renderizar por grupos
    Object.entries(grupos).forEach(([grupoId, items]) => {
        // Un grupo es combinado si:
        // 1. No comienza con "individual_" (es decir, tiene un grupo_combinado asignado)
        // 2. Tiene más de una prenda
        const esGrupoCombinado = !grupoId.startsWith('individual_') && items.length > 1;
        console.log(`🎨 Renderizando grupoId="${grupoId}", esGrupoCombinado=${esGrupoCombinado}, items=${items.length}`);
        
        if (esGrupoCombinado) {
            // RENDERIZAR GRUPO COMBINADO: UNA SOLA FILA POR PRENDA, MOSTRANDO TÉCNICAS/UBICACIONES/FOTOS POR TÉCNICA DENTRO
            console.log(`   ✨ Grupo COMBINADO encontrado con ${items.length} técnicas`);
            
            // Usar datos de la primera prenda (nombre, observaciones)
            const { prenda: prendaPrincipal, index: indexPrincipal } = items[0];
            
            // Calcular tallas agrupadas del grupo completo
            const talasAgrupadas = {};
            items.forEach(item => {
                if (item.prenda.talla_cantidad) {
                    Object.entries(item.prenda.talla_cantidad).forEach(([talla, cantidad]) => {
                        talasAgrupadas[talla] = (talasAgrupadas[talla] || 0) + cantidad;
                    });
                }
            });
            
            // Generar HTML de técnicas
            const tecnicasHTML = items.map(item => 
                `<span style="background: #0ea5e9; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; display: inline-block;">${item.prenda.tipo_logo_nombre}</span>`
            ).join('');
            
            // Generar HTML de ubicaciones POR TÉCNICA
            const ubicacionesHTML = items.map(item => {
                const ubicaciones = item.prenda.ubicaciones && item.prenda.ubicaciones.length > 0 
                    ? item.prenda.ubicaciones 
                    : [];
                return `
                    <div style="border-left: 3px solid #0ea5e9; padding-left: 0.8rem; margin-bottom: 0.6rem;">
                        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600; margin-bottom: 0.3rem;">${item.prenda.tipo_logo_nombre}:</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                            ${ubicaciones.length > 0 
                                ? ubicaciones.map(u => `<span style="background: #dbeafe; color: #0369a1; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">${u}</span>`).join('')
                                : '<span style="color: #94a3b8; font-size: 0.85rem;">—</span>'
                            }
                        </div>
                    </div>
                `;
            }).join('');
            
            // Generar HTML de fotos POR TÉCNICA
            const fotosHTML = items.map(item => {
                const fotos = item.prenda.fotos && item.prenda.fotos.length > 0 ? item.prenda.fotos : [];
                return `
                    <div style="border-top: 2px solid #e2e8f0; padding-top: 0.8rem; margin-bottom: 0.8rem;">
                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">${item.prenda.tipo_logo_nombre}</div>
                        ${fotos.length > 0 
                            ? `<div style="display: flex; flex-wrap: wrap; gap: 0.4rem; justify-content: center;">
                                ${fotos.map(foto => `
                                    <img src="${foto.ruta_webp || foto.url}" alt="${item.prenda.tipo_logo_nombre}" 
                                        style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" 
                                        onmouseover="this.style.transform='scale(1.15)'; this.style.borderColor='#0ea5e9';" 
                                        onmouseout="this.style.transform='scale(1)'; this.style.borderColor='#e2e8f0';" 
                                        onclick="abrirModalEditarPrendaTecnica(${item.index})">
                                `).join('')}
                            </div>
                            <div style="margin-top: 0.3rem; font-size: 0.75rem; color: #64748b;">${fotos.length} imagen(es)</div>`
                            : '<span style="color: #94a3b8; font-size: 0.75rem;">—</span>'
                        }
                    </div>
                `;
            }).join('');
            
            // Una sola fila para el grupo COMBINADA
            html += `
                <tr style="background: #f0f7ff; border: 2px solid #0284c7;">
                    <td style="padding: 1.2rem; vertical-align: top; color: #0c4a6e; font-weight: 700; font-size: 1rem;">
                        <div style="margin-bottom: 0.5rem;">🔗 COMBINADA</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            ${tecnicasHTML}
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; color: #1e293b; font-size: 1rem; font-weight: 500;">
                        ${prendaPrincipal.nombre_prenda || '—'}
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; font-size: 0.95rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                            ${ubicacionesHTML}
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; color: #475569; font-size: 0.95rem;">
                        ${prendaPrincipal.observaciones || '—'}
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                            ${Object.keys(talasAgrupadas).length > 0
                                ? Object.keys(talasAgrupadas).map(talla => 
                                    `<div style="background: #dbeafe; color: #0369a1; padding: 0.5rem 1rem; border-radius: 4px; font-size: 1.05rem; font-weight: 700;">
                                        ${talla}: <strong>${talasAgrupadas[talla]}</strong>
                                    </div>`
                                ).join('')
                                : '<span>—</span>'
                            }
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                            ${fotosHTML}
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                            <button type="button" onclick="abrirModalEditarPrendaTecnica(${indexPrincipal})" title="Editar grupo COMBINADA" style="
                                background: #1e40af;
                                color: white;
                                border: none;
                                padding: 0.3rem;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.8rem;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 32px;
                                height: 32px;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#1e3a8a'" onmouseout="this.style.background='#1e40af'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="eliminarPrendaTecnicaLogo(${indexPrincipal})" title="Eliminar grupo COMBINADA" style="
                                background: #ef4444;
                                color: white;
                                border: none;
                                padding: 0.3rem;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.8rem;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 32px;
                                height: 32px;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            // Prenda individual
            const { prenda, index } = items[0];
            console.log(`   📍 Prenda individual: ${prenda.tipo_logo_nombre}`);
            html += `
                <tr style="border-bottom: 1px solid #e2e8f0; background: #ffffff;">
                    <td style="padding: 1rem; vertical-align: top; color: #1e293b; font-weight: 600; font-size: 0.95rem;">
                        <span style="background: #0ea5e9; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; display: inline-block;">
                            ${prenda.tipo_logo_nombre || '—'}
                        </span>
                    </td>
                    <td style="padding: 1rem; vertical-align: top; color: #1e293b; font-size: 1rem; font-weight: 500;">
                        ${prenda.nombre_prenda || '—'}
                    </td>
                    <td style="padding: 1rem; vertical-align: top; color: #475569; font-size: 0.95rem;">
                        ${prenda.ubicaciones && prenda.ubicaciones.length > 0 
                            ? prenda.ubicaciones.join(', ')
                            : '—'
                        }
                    </td>
                    <td style="padding: 1rem; vertical-align: top; color: #475569; font-size: 0.95rem;">
                        ${prenda.observaciones || '—'}
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: center;">
                            ${prenda.talla_cantidad && Object.keys(prenda.talla_cantidad).length > 0
                                ? Object.keys(prenda.talla_cantidad).map(talla => 
                                    `<div style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 4px; margin-bottom: 0.3rem; font-size: 0.95rem; font-weight: 600;">
                                        ${talla}: <strong>${prenda.talla_cantidad[talla]}</strong>
                                    </div>`
                                ).join('')
                                : '<span style="color: #94a3b8; font-size: 0.95rem;">—</span>'
                            }
                        </div>
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        ${prenda.fotos && prenda.fotos.length > 0
                            ? `<div style="display: flex; flex-wrap: wrap; gap: 0.4rem; justify-content: center;">
                                ${prenda.fotos.map(foto => `
                                    <img src="${foto.ruta_webp || foto.url}" alt="Foto" 
                                        style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" 
                                        onmouseover="this.style.transform='scale(1.15)'; this.style.borderColor='#0ea5e9';" 
                                        onmouseout="this.style.transform='scale(1)'; this.style.borderColor='#e2e8f0';" 
                                        onclick="abrirModalEditarPrendaTecnica(${index})">
                                `).join('')}
                            </div>
                            <div style="margin-top: 0.3rem; font-size: 0.75rem; color: #64748b;">
                                ${prenda.fotos.length} imagen(es)
                            </div>`
                            : '<span style="color: #94a3b8; font-size: 0.75rem;">—</span>'
                        }
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                            <button type="button" onclick="abrirModalEditarPrendaTecnica(${index})" title="Editar" style="
                                background: #1e40af;
                                color: white;
                                border: none;
                                padding: 0.3rem;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.8rem;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 36px;
                                height: 36px;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#1e3a8a'" onmouseout="this.style.background='#1e40af'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="eliminarPrendaTecnicaLogo(${index})" title="Eliminar" style="
                                background: #ef4444;
                                color: white;
                                border: none;
                                padding: 0.3rem;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.8rem;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 36px;
                                height: 36px;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    console.log('✅ Tabla HTML generada completamente');
    container.innerHTML = html;
    sincronizarPrendasTecnicas();
}

// =========================================================
// 4.0.5 FUNCIÓN AUXILIAR: OBTENER COLOR POR TÉCNICA
// =========================================================

function obtenerColorTecnica(tipoLogoId) {
    // Mapa de colores por ID de técnica
    const coloresMap = {
        1: '#3b82f6',     // Bordado - azul
        2: '#ef4444',     // Sublimado - rojo
        3: '#f59e0b',     // Estampado - naranja
        4: '#10b981',     // Puff/3D - verde
        5: '#8b5cf6',     // Fotoprendas - púrpura
        6: '#ec4899'      // Otros - rosa
    };
    
    // Si el tipoLogoId está en el mapa, devolver su color
    if (coloresMap[tipoLogoId]) {
        return coloresMap[tipoLogoId];
    }
    
    // Si no está en el mapa, generar un color basado en el ID
    const colores = Object.values(coloresMap);
    return colores[tipoLogoId % colores.length];
}

// =========================================================
// 4.1 ABRIR MODAL PARA EDITAR PRENDA TÉCNICA
// =========================================================

window.abrirModalEditarPrendaTecnica = function(index) {
    const prenda = logoPrendasTecnicas[index];
    
    if (!prenda) {
        console.error('❌ Prenda no encontrada en índice:', index);
        return;
    }
    
    console.log('📝 Editando prenda técnica:', index, prenda);
    
    // Detectar si es grupo COMBINADO y obtener todas las técnicas del grupo
    const esGrupoCombinado = prenda.grupo_combinado && logoPrendasTecnicas.some(p => p.grupo_combinado === prenda.grupo_combinado && p.tipo_logo_id !== prenda.tipo_logo_id);
    let tecnicasDelGrupo = [];
    
    if (esGrupoCombinado) {
        // Obtener TODAS las técnicas del grupo (deben tener el mismo nombre_prenda)
        tecnicasDelGrupo = logoPrendasTecnicas.filter(p => 
            p.grupo_combinado === prenda.grupo_combinado
        );
        console.log('🔗 Grupo combinado detectado. Técnicas en el grupo:', tecnicasDelGrupo.length);
    } else {
        // Si no es combinado, solo editar esta técnica
        tecnicasDelGrupo = [prenda];
        console.log('📌 Prenda individual, no es grupo combinado');
    }
    
    // GENERAR HTML DEL MODAL CON TODAS LAS TÉCNICAS
    let htmlTecnicas = '';
    tecnicasDelGrupo.forEach((tecnica, idxTecnica) => {
        const colorTecnica = obtenerColorTecnica(tecnica.tipo_logo_id);
        htmlTecnicas += `
            <div style="margin-bottom: 1.5rem; padding: 1rem; border-left: 4px solid ${colorTecnica}; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin: 0 0 1rem 0; color: ${colorTecnica}; font-weight: 700; font-size: 0.95rem;">
                    ${tecnica.tipo_logo_nombre}
                </h4>
                
                <!-- Ubicaciones por Técnica -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.9rem;">
                        Ubicaciones:
                    </label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="text" class="ubicacionInput" data-tecnica-idx="${idxTecnica}" placeholder="Escribe una ubicación..." 
                            style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
                        <button type="button" class="btnAgregarUbicacion" data-tecnica-idx="${idxTecnica}" style="
                            background: ${colorTecnica}; 
                            color: white; 
                            border: none; 
                            padding: 0.5rem 0.8rem; 
                            border-radius: 4px; 
                            cursor: pointer; 
                            font-weight: 600; 
                            font-size: 0.85rem;">
                            + Ubicación
                        </button>
                    </div>
                    <div class="ubicacionesDiv" data-tecnica-idx="${idxTecnica}" style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        ${(tecnica.ubicaciones || []).map(ub => `
                            <span class="ubicacion-tag-modal" data-ubicacion="${ub}" style="
                                background: ${colorTecnica}; 
                                color: white; 
                                padding: 0.3rem 0.6rem; 
                                border-radius: 16px; 
                                font-size: 0.8rem; 
                                display: inline-flex; 
                                align-items: center; 
                                gap: 0.4rem; 
                                font-weight: 500;">
                                ${ub}
                                <button type="button" onclick="this.parentElement.remove()" style="
                                    background: none; 
                                    border: none; 
                                    color: white; 
                                    cursor: pointer; 
                                    font-size: 0.95rem; 
                                    padding: 0; 
                                    width: 18px; 
                                    height: 18px; 
                                    display: flex; 
                                    align-items: center; 
                                    justify-content: center;">
                                    ×
                                </button>
                            </span>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Fotos por Técnica -->
                <div>
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.9rem;">
                        Fotos (${tecnica.fotos?.length || 0}):
                    </label>
                    <div class="fotosGaleria" data-tecnica-idx="${idxTecnica}" style="
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
                        gap: 0.4rem;
                        margin-bottom: 0.8rem;
                    ">
                        ${(tecnica.fotos || []).map((foto, idxFoto) => {
                            // Buscar el índice real en logoPrendasTecnicas para poder eliminar correctamente
                            const indicePrendaReal = logoPrendasTecnicas.findIndex(p => p === tecnica);
                            return `
                                <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6;">
                                    <img src="${foto.ruta_webp || foto.url || foto}" alt="Foto" style="width: 100%; height: 70px; object-fit: cover;">
                                    <button type="button" onclick="eliminarFotoDePrenda(${indicePrendaReal}, ${idxFoto})" style="
                                        position: absolute;
                                        top: 1px;
                                        right: 1px;
                                        background: #ef4444;
                                        color: white;
                                        border: none;
                                        width: 18px;
                                        height: 18px;
                                        font-size: 0.65rem;
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        border-radius: 2px;
                                    ">
                                        ✕
                                    </button>
                                </div>
                            `;
                        }).join('')}
                    </div>
                    <div class="dropZone" data-tecnica-idx="${idxTecnica}" style="
                        border: 2px dashed ${colorTecnica};
                        border-radius: 4px;
                        padding: 0.8rem;
                        text-align: center;
                        background: rgba(${colorTecnica}, 0.05);
                        cursor: pointer;
                        transition: all 0.3s;
                    ">
                        <div style="font-size: 1.4rem; margin-bottom: 0.3rem;">📸</div>
                        <div style="font-weight: 600; color: ${colorTecnica}; font-size: 0.8rem;">
                            Arrastra fotos aquí
                        </div>
                    </div>
                    <input type="file" class="fileInput" data-tecnica-idx="${idxTecnica}" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                </div>
            </div>
        `;
    });
    
    const htmlModal = `
        <div style="text-align: left;">
            <!-- Nombre de Prenda (COMPARTIDO) -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">
                    Nombre de Prenda:
                </label>
                <input type="text" id="modalNombrePrendaTecnica" placeholder="Ej: Camisa, Gorro, Bolsa" 
                    value="${prenda.nombre_prenda || ''}"
                    style="width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
            </div>

            <!-- Observaciones (COMPARTIDO) -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">
                    Observaciones (opcional):
                </label>
                <textarea id="modalObservacionesPrendaTecnica" placeholder="Ej: Hilo especial, acabado mate..."
                    style="width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; min-height: 60px; box-sizing: border-box; resize: vertical;">
${prenda.observaciones || ''}
                </textarea>
            </div>

            <!-- TÉCNICAS CON SUS UBICACIONES Y FOTOS -->
            ${htmlTecnicas}

            <!-- Tallas y Cantidades (COMPARTIDO) -->
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">
                    Tallas y Cantidades (General):
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.8rem;">
                    <input type="text" id="modalNewTalla" placeholder="Ej: S, M, L, XL" 
                        style="padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                    <input type="number" id="modalNewCantidad" placeholder="Cantidad" min="1" 
                        style="padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                </div>
                <button type="button" id="btnAgregarTalla" style="
                    width: 100%; 
                    background: #16a34a; 
                    color: white; 
                    border: none; 
                    padding: 0.6rem; 
                    border-radius: 4px; 
                    cursor: pointer; 
                    font-weight: 600; 
                    margin-bottom: 0.8rem;">
                    + Agregar Talla
                </button>
                <div id="modalTallasDiv" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${Object.entries(prenda.talla_cantidad || {}).map(([talla, cant]) => `
                        <div class="modal-talla-item" style="
                            background: #dbeafe; 
                            color: #0369a1; 
                            padding: 0.5rem 0.8rem; 
                            border-radius: 4px; 
                            font-weight: 600; 
                            display: inline-flex; 
                            align-items: center; 
                            gap: 0.5rem;">
                            ${talla}: <strong>${cant}</strong>
                            <button type="button" onclick="this.parentElement.remove()" style="
                                background: none; 
                                border: none; 
                                color: #0369a1; 
                                cursor: pointer; 
                                font-size: 1rem;">
                                ×
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: esGrupoCombinado ? `Editar Prenda - ${tecnicasDelGrupo.length} Técnicas Combinadas` : `Editar Prenda - ${prenda.tipo_logo_nombre}`,
        html: htmlModal,
        width: '750px',
        maxHeight: '90vh',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e40af',
        cancelButtonColor: '#6b7280',
        didOpen: () => {
            // EVENT LISTENERS PARA CADA TÉCNICA
            tecnicasDelGrupo.forEach((tecnica, idxTecnica) => {
                const indicePrendaReal = logoPrendasTecnicas.indexOf(tecnica);
                const dropZone = document.querySelector(`.dropZone[data-tecnica-idx="${idxTecnica}"]`);
                const fileInput = document.querySelector(`.fileInput[data-tecnica-idx="${idxTecnica}"]`);
                const fotosGaleria = document.querySelector(`.fotosGaleria[data-tecnica-idx="${idxTecnica}"]`);
                const btnAgregarUbicacion = document.querySelector(`.btnAgregarUbicacion[data-tecnica-idx="${idxTecnica}"]`);
                const ubicacionInput = document.querySelector(`.ubicacionInput[data-tecnica-idx="${idxTecnica}"]`);
                const ubicacionesDiv = document.querySelector(`.ubicacionesDiv[data-tecnica-idx="${idxTecnica}"]`);
                
                // Drag-drop de fotos
                if (dropZone && fileInput) {
                    dropZone.addEventListener('click', () => fileInput.click());
                    
                    dropZone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropZone.style.background = 'rgba(0, 0, 0, 0.05)';
                    });
                    
                    dropZone.addEventListener('dragleave', () => {
                        dropZone.style.background = '';
                    });
                    
                    dropZone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropZone.style.background = '';
                        Array.from(e.dataTransfer.files).forEach(file => agregarFotoaTecnica(file, tecnica, indicePrendaReal, fotosGaleria));
                    });
                    
                    fileInput.addEventListener('change', (e) => {
                        Array.from(e.target.files).forEach(file => agregarFotoaTecnica(file, tecnica, indicePrendaReal, fotosGaleria));
                    });
                }
                
                // Agregar ubicaciones
                if (btnAgregarUbicacion) {
                    btnAgregarUbicacion.addEventListener('click', (e) => {
                        e.preventDefault();
                        const ubicacion = ubicacionInput.value.trim().toUpperCase();
                        
                        if (!ubicacion) {
                            Swal.showValidationMessage('Escribe una ubicación primero');
                            return;
                        }
                        
                        const existentes = Array.from(ubicacionesDiv.querySelectorAll('[data-ubicacion]'))
                            .map(tag => tag.getAttribute('data-ubicacion').toLowerCase());
                        
                        if (existentes.includes(ubicacion.toLowerCase())) {
                            Swal.showValidationMessage('Esta ubicación ya existe para esta técnica');
                            return;
                        }
                        
                        const colorTecnica = obtenerColorTecnica(tecnica.tipo_logo_id);
                        const tag = document.createElement('span');
                        tag.setAttribute('data-ubicacion', ubicacion);
                        tag.className = 'ubicacion-tag-modal';
                        tag.style.cssText = `
                            background: ${colorTecnica}; 
                            color: white; 
                            padding: 0.3rem 0.6rem; 
                            border-radius: 16px; 
                            font-size: 0.8rem; 
                            display: inline-flex; 
                            align-items: center; 
                            gap: 0.4rem; 
                            font-weight: 500;
                        `;
                        tag.innerHTML = `
                            ${ubicacion}
                            <button type="button" onclick="this.parentElement.remove()" style="
                                background: none; 
                                border: none; 
                                color: white; 
                                cursor: pointer; 
                                font-size: 0.95rem; 
                                padding: 0; 
                                width: 18px; 
                                height: 18px; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center;">
                                ×
                            </button>
                        `;
                        
                        ubicacionesDiv.appendChild(tag);
                        ubicacionInput.value = '';
                        ubicacionInput.focus();
                    });
                }
            });
            
            // EVENTOS COMPARTIDOS (Nombre, Observaciones, Tallas)
            const btnAgregarTalla = document.getElementById('btnAgregarTalla');
            const tallaCantidadContainer = document.getElementById('modalTallasDiv');
            
            if (btnAgregarTalla) {
                btnAgregarTalla.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tallInput = document.getElementById('modalNewTalla');
                    const cantInput = document.getElementById('modalNewCantidad');
                    const talla = tallInput.value.trim().toUpperCase();
                    const cantidad = parseInt(cantInput.value) || 1;
                    
                    if (!talla) {
                        Swal.showValidationMessage('Ingresa una talla');
                        return;
                    }
                    
                    // Verificar duplicados
                    const existentes = Array.from(tallaCantidadContainer.querySelectorAll('.modal-talla-item'));
                    if (existentes.some(el => el.textContent.includes(talla + ':'))) {
                        Swal.showValidationMessage('Esta talla ya fue agregada');
                        return;
                    }
                    
                    const tallaDiv = document.createElement('div');
                    tallaDiv.className = 'modal-talla-item';
                    tallaDiv.style.cssText = `
                        background: #dbeafe; 
                        color: #0369a1; 
                        padding: 0.5rem 0.8rem; 
                        border-radius: 4px; 
                        font-weight: 600; 
                        display: inline-flex; 
                        align-items: center; 
                        gap: 0.5rem;
                    `;
                    tallaDiv.innerHTML = `
                        ${talla}: <strong>${cantidad}</strong>
                        <button type="button" onclick="this.parentElement.remove()" style="
                            background: none; 
                            border: none; 
                            color: #0369a1; 
                            cursor: pointer; 
                            font-size: 1rem;">
                            ×
                        </button>
                    `;
                    
                    tallaCantidadContainer.appendChild(tallaDiv);
                    tallInput.value = '';
                    cantInput.value = '1';
                    tallInput.focus();
                });
            }
        },
        preConfirm: () => {
            const nombre = document.getElementById('modalNombrePrendaTecnica').value.trim();
            const observaciones = document.getElementById('modalObservacionesPrendaTecnica').value.trim();
            
            if (!nombre) {
                Swal.showValidationMessage('El nombre de la prenda es obligatorio');
                return false;
            }
            
            // Validar que al menos una técnica tenga ubicaciones
            let tieneAlMenosUnaUbicacion = false;
            tecnicasDelGrupo.forEach((tecnica, idx) => {
                const ubicacionesDiv = document.querySelector(`.ubicacionesDiv[data-tecnica-idx="${idx}"]`);
                if (ubicacionesDiv && ubicacionesDiv.querySelectorAll('[data-ubicacion]').length > 0) {
                    tieneAlMenosUnaUbicacion = true;
                }
            });
            
            if (!tieneAlMenosUnaUbicacion) {
                Swal.showValidationMessage('Debes agregar al menos una ubicación en alguna técnica');
                return false;
            }
            
            // Recopilar tallas
            const tallasDiv = document.getElementById('modalTallasDiv');
            const tallasItems = Array.from(tallasDiv.querySelectorAll('.modal-talla-item'));
            const nuevasTallas = {};
            
            tallasItems.forEach(item => {
                const texto = item.innerText;
                const [talla, cantidad] = texto.split(':').map(s => s.trim());
                nuevasTallas[talla] = parseInt(cantidad) || 1;
            });
            
            // Retornar datos actualizados para TODAS las técnicas
            return {
                nombre,
                observaciones,
                tecnicas: tecnicasDelGrupo.map((tecnica, idx) => {
                    const ubicacionesDiv = document.querySelector(`.ubicacionesDiv[data-tecnica-idx="${idx}"]`);
                    const ubicaciones = Array.from(ubicacionesDiv.querySelectorAll('[data-ubicacion]'))
                        .map(tag => tag.getAttribute('data-ubicacion'));
                    
                    return {
                        tipo_logo_id: tecnica.tipo_logo_id,
                        tipo_logo_nombre: tecnica.tipo_logo_nombre,
                        ubicaciones,
                        fotos: tecnica.fotos
                    };
                })
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const datos = result.value;
            
            // Actualizar TODAS las técnicas del grupo
            datos.tecnicas.forEach((dataTecnica, idxTecnica) => {
                const tecnicaReal = tecnicasDelGrupo[idxTecnica];
                const indicePrendaReal = logoPrendasTecnicas.indexOf(tecnicaReal);
                
                if (indicePrendaReal !== -1) {
                    logoPrendasTecnicas[indicePrendaReal].nombre_prenda = datos.nombre;
                    logoPrendasTecnicas[indicePrendaReal].observaciones = datos.observaciones;
                    logoPrendasTecnicas[indicePrendaReal].ubicaciones = dataTecnica.ubicaciones;
                    
                    // Actualizar tallas para TODAS las técnicas
                    const nuevasTallasCantidad = {};
                    datos.tecnicas.forEach(t => {
                        // Las tallas son compartidas entre todas las técnicas
                    });
                    
                    // Convertir tallas a objeto si es necesario
                    const tallasDiv = document.getElementById('modalTallasDiv');
                    const tallasItems = Array.from(tallasDiv.querySelectorAll('.modal-talla-item'));
                    const nuevasTallas = {};
                    tallasItems.forEach(item => {
                        const texto = item.innerText;
                        const [talla, cantidad] = texto.split(':').map(s => s.trim());
                        nuevasTallas[talla] = parseInt(cantidad) || 1;
                    });
                    logoPrendasTecnicas[indicePrendaReal].talla_cantidad = nuevasTallas;
                }
            });
            
            // Re-renderizar tabla
            renderizarLogoPrendasTecnicas();
            sincronizarPrendasTecnicas();
        }
    });
};

// Helper para agregar fotos a técnica específica
function agregarFotoaTecnica(file, tecnica, indicePrendaReal, fotosGaleria) {
    const reader = new FileReader();
    reader.onload = (e) => {
        logoPrendasTecnicas[indicePrendaReal].fotos.push({
            preview: e.target.result,
            url: e.target.result,
            ruta_webp: e.target.result,
            nuevo: true
        });
        
        // Actualizar galería visualmente
        const fotoDiv = document.createElement('div');
        fotoDiv.style.cssText = 'position: relative; border-radius: 4px; overflow: hidden; background: #f3f4f6;';
        fotoDiv.innerHTML = `
            <img src="${e.target.result}" alt="Foto" style="width: 100%; height: 70px; object-fit: cover;">
            <button type="button" onclick="eliminarFotoDePrenda(${indicePrendaReal}, ${logoPrendasTecnicas[indicePrendaReal].fotos.length - 1})" style="
                position: absolute;
                top: 1px;
                right: 1px;
                background: #ef4444;
                color: white;
                border: none;
                width: 18px;
                height: 18px;
                font-size: 0.65rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 2px;">✕</button>
        `;
        fotosGaleria.appendChild(fotoDiv);
    };
    reader.readAsDataURL(file);
}

// =========================================================
// 5. FUNCIONES PARA ELIMINAR PRENDAS
// =========================================================

window.eliminarPrendaTecnicaLogo = function(index) {
    if (confirm('¿Estás seguro de que deseas eliminar esta prenda técnica?')) {
        logoPrendasTecnicas.splice(index, 1);
        window.logoPrendasTecnicas = logoPrendasTecnicas;
        sincronizarPrendasTecnicas();
        console.log('✅ Prenda técnica eliminada');
        renderizarLogoPrendasTecnicas();
    }
};

window.eliminarFotoDePrenda = function(index, fotoIdx) {
    const prenda = logoPrendasTecnicas[index];
    if (prenda && prenda.fotos) {
        prenda.fotos.splice(fotoIdx, 1);
        console.log(`✅ Foto ${fotoIdx} eliminada de prenda ${index}`);
        // No es necesario renderizar de nuevo, el modal se actualiza dinámicamente
    }
};

// =========================================================
// 6. INICIALIZACIÓN
// =========================================================

console.log('✅ logo-pedido-tecnicas.js completamente cargado');
cargarTiposLogosDisponibles();

document.addEventListener('DOMContentLoaded', function() {
    cargarTiposLogosDisponibles();
});
