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
                    <tr style="background: #1e40af; color: white;">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Técnica(s)</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Prenda</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Ubicaciones</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Observaciones</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Imagen</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Talla</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Cantidad</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; border-bottom: 2px solid #1e40af; font-size: 1.1rem; color: white !important;">Acciones</th>
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
            // Agrupar técnicas
            const tecnicas = [...new Set(items.map(item => item.prenda.tipo_logo_nombre))];
            const nombreTecnicas = tecnicas.join(' + ');
            
            console.log(`   ✨ Grupo COMBINADO encontrado: ${nombreTecnicas}`);
            
            // Recopilar todas las fotos del grupo
            const todasLasFotos = [];
            items.forEach(item => {
                if (item.prenda.fotos && item.prenda.fotos.length > 0) {
                    todasLasFotos.push(...item.prenda.fotos);
                    console.log(`      Fotos de ${item.prenda.tipo_logo_nombre}: ${item.prenda.fotos.length}`);
                }
            });
            console.log(`   Total fotos en grupo: ${todasLasFotos.length}`);
            
            // Recopilar todas las tallas únicas del grupo
            const todasLasTallas = {};
            items.forEach(item => {
                if (item.prenda.talla_cantidad) {
                    Object.assign(todasLasTallas, item.prenda.talla_cantidad);
                    console.log(`      Tallas de ${item.prenda.tipo_logo_nombre}:`, Object.keys(item.prenda.talla_cantidad));
                }
            });
            console.log(`   Total tallas en grupo:`, Object.keys(todasLasTallas));
            
            // Fila única para el grupo combinado
            html += `
                <tr style="background: #f0f7ff; border: 2px solid #0284c7;">
                    <td style="padding: 1.2rem; vertical-align: top; color: #0c4a6e; font-weight: 700; font-size: 1rem;">
                        🔗 COMBINADA<br>
                        <span style="font-size: 0.9rem; font-weight: 600; display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
                            ${tecnicas.map(t => `<span style="background: #bfdbfe; color: #1e40af; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.85rem;">${t}</span>`).join('')}
                        </span>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; color: #1e293b; font-size: 1rem;">
                        ${items[0].prenda.nombre_prenda || '—'}
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; color: #475569; font-size: 0.95rem;">
                        ${items[0].prenda.ubicaciones && items[0].prenda.ubicaciones.length > 0 
                            ? items[0].prenda.ubicaciones.join(', ')
                            : '—'
                        }
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; color: #475569; font-size: 0.95rem;">
                        ${items[0].prenda.observaciones || '—'}
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        ${todasLasFotos.length > 0
                            ? `<div style="display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                <img src="${todasLasFotos[0].ruta_webp || todasLasFotos[0].url}" alt="Foto" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; cursor: pointer;" onclick="abrirModalEditarPrendaTecnica(${items[0].index})">
                                <span style="font-size: 0.8rem; color: #94a3b8;">Primera imagen</span>
                                <span style="font-size: 0.75rem; color: #cbd5e1;">${todasLasFotos.length}/${todasLasFotos.length}</span>
                            </div>`
                            : '<span style="color: #94a3b8; font-size: 0.9rem;">—</span>'
                        }
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: center;">
                            ${Object.keys(todasLasTallas).map(talla => 
                                `<span style="background: #e0e7ff; color: #3730a3; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.85rem; font-weight: 600;">${talla}</span>`
                            ).join('')}
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: center;">
                            ${Object.values(todasLasTallas).map(cant => 
                                `<span style="color: #475569; font-weight: 600;">${cant}</span>`
                            ).join('')}
                        </div>
                    </td>
                    <td style="padding: 1.2rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                            ${items.map((item, idx) => `
                                <button type="button" onclick="abrirModalEditarPrendaTecnica(${item.index})" title="Editar ${item.prenda.tipo_logo_nombre}" style="
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
                                    position: relative;
                                    title="${item.prenda.tipo_logo_nombre}"
                                " onmouseover="this.style.background='#1e3a8a'" onmouseout="this.style.background='#1e40af'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            `).join('')}
                            <button type="button" onclick="eliminarPrendaTecnicaLogo(${items[0].index})" title="Eliminar grupo" style="
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
                        ${prenda.tipo_logo_nombre || '—'}
                    </td>
                    <td style="padding: 1rem; vertical-align: top; color: #1e293b; font-size: 1rem;">
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
                        ${prenda.fotos && prenda.fotos.length > 0
                            ? `<div style="display: flex; flex-direction: column; align-items: center; gap: 0.3rem;">
                                <img src="${prenda.fotos[0].ruta_webp || prenda.fotos[0].url}" alt="Foto" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; cursor: pointer;" onclick="abrirModalEditarPrendaTecnica(${index})">
                                <span style="font-size: 0.8rem; color: #94a3b8;">Primera imagen</span>
                                <span style="font-size: 0.75rem; color: #cbd5e1;">${prenda.fotos.length}/${prenda.fotos.length}</span>
                            </div>`
                            : '<span style="color: #94a3b8; font-size: 0.9rem;">—</span>'
                        }
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: center;">
                            ${prenda.talla_cantidad && Object.keys(prenda.talla_cantidad).length > 0
                                ? Object.keys(prenda.talla_cantidad).map(talla => 
                                    `<span style="background: #e0e7ff; color: #3730a3; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.85rem; font-weight: 600;">${talla}</span>`
                                ).join('')
                                : '<span>—</span>'
                            }
                        </div>
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 0.3rem; align-items: center;">
                            ${prenda.talla_cantidad && Object.keys(prenda.talla_cantidad).length > 0
                                ? Object.values(prenda.talla_cantidad).map(cant => 
                                    `<span style="color: #475569; font-weight: 600;">${cant}</span>`
                                ).join('')
                                : '<span>—</span>'
                            }
                        </div>
                    </td>
                    <td style="padding: 1rem; vertical-align: top; text-align: center;">
                        <button type="button" onclick="abrirModalEditarPrendaTecnica(${index})" title="Editar" style="
                            background: #1e40af;
                            color: white;
                            border: none;
                            padding: 0.5rem 0.7rem;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 1rem;
                            margin-right: 0.5rem;
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
                            padding: 0.5rem 0.7rem;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 1rem;
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
// 4.1 ABRIR MODAL PARA EDITAR PRENDA TÉCNICA
// =========================================================

window.abrirModalEditarPrendaTecnica = function(index) {
    const prenda = logoPrendasTecnicas[index];
    
    if (!prenda) {
        console.error('❌ Prenda no encontrada en índice:', index);
        return;
    }
    
    console.log('📝 Editando prenda técnica:', index, prenda);
    
    // Crear modal
    const modalHtml = crearModalEdicionPrenda(prenda, index);
    
    // Insertar modal directamente en el body
    let modalContainer = document.getElementById('modal-edicion-prenda-logo');
    if (modalContainer) {
        modalContainer.remove();
    }
    
    modalContainer = document.createElement('div');
    modalContainer.id = 'modal-edicion-prenda-logo';
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    
    // Mostrar modal
    const modal = document.getElementById(`modal-prenda-${index}`);
    if (modal) {
        modal.style.display = 'flex';
        
        // Configurar drag-and-drop para fotos después de que el modal esté visible
        setTimeout(() => {
            const dropZone = document.querySelector(`.drop-zone-edicion-${index}`);
            const fileInput = document.querySelector(`.file-input-edicion-${index}`);
            
            if (dropZone && fileInput) {
                // Función para procesar archivos
                function procesarArchivos(files) {
                    const archivos = Array.from(files).filter(file => file.type.startsWith('image/'));
                    
                    if (archivos.length === 0) {
                        alert('Por favor selecciona solo imágenes');
                        return;
                    }
                    
                    archivos.forEach(file => {
                        const reader = new FileReader();
                        
                        reader.onload = function(event) {
                            // Agregar foto directamente a la prenda
                            prenda.fotos.push({
                                preview: event.target.result,
                                url: event.target.result,
                                ruta_webp: event.target.result,
                                nuevo: true
                            });
                            
                            // Renderizar la galería actualizada
                            const galeriaDiv = modal.querySelector('[style*="grid-template-columns"]');
                            if (galeriaDiv && galeriaDiv.querySelector('p')) {
                                galeriaDiv.querySelector('p').remove();
                            }
                            
                            // Crear elemento de foto
                            const fotoDiv = document.createElement('div');
                            fotoDiv.style.cssText = 'position: relative; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;';
                            fotoDiv.innerHTML = `
                                <img src="${event.target.result}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                                <button type="button" onclick="eliminarFotoDePrenda(${index}, ${prenda.fotos.length - 1})" style="
                                    position: absolute;
                                    top: 2px;
                                    right: 2px;
                                    background: #ef4444;
                                    color: white;
                                    border: none;
                                    width: 20px;
                                    height: 20px;
                                    font-size: 0.7rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border-radius: 3px;
                                ">
                                    ✕
                                </button>
                            `;
                            
                            if (galeriaDiv) {
                                galeriaDiv.appendChild(fotoDiv);
                            }
                            
                            // Actualizar contador
                            const label = modal.querySelector('label');
                            if (label) {
                                label.textContent = `Fotos (${prenda.fotos.length})`;
                            }
                        };
                        
                        reader.readAsDataURL(file);
                    });
                    
                    fileInput.value = '';
                }

                // Click en zona drag-drop
                dropZone.addEventListener('click', function() {
                    fileInput.click();
                });

                // Cambio en input file
                fileInput.addEventListener('change', function(e) {
                    procesarArchivos(e.target.files);
                });

                // Drag over
                dropZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.background = '#e0f2fe';
                    dropZone.style.borderColor = '#0284c7';
                });

                // Drag leave
                dropZone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.background = '#f0f7ff';
                    dropZone.style.borderColor = '#1e40af';
                });

                // Drop
                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.background = '#f0f7ff';
                    dropZone.style.borderColor = '#1e40af';
                    
                    const files = e.dataTransfer.files;
                    procesarArchivos(files);
                });
            }
        }, 0);
    }
};

// =========================================================
// 4.2 CREAR MODAL DE EDICIÓN
// =========================================================

function crearModalEdicionPrenda(prenda, index) {
    const ubicacionesDisponibles = [
        'PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO',
        'PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO',
        'FRENTE', 'LATERAL', 'TRASERA'
    ];
    
    return `
        <div id="modal-prenda-${index}" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1rem;
            margin: 0;
            box-sizing: border-box;
        " onclick="if(event.target.id === 'modal-prenda-${index}') cerrarModalEditarPrendaTecnica(${index})">
            <div style="
                background: white;
                padding: 2rem;
                max-width: 600px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                border: 1px solid #1e40af;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                margin: 0;
            " onclick="event.stopPropagation()">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd;">
                    <h2 style="margin: 0; font-size: 1.3rem; color: #1e40af; font-weight: 700;">
                        Editar Prenda
                    </h2>
                    <button type="button" onclick="cerrarModalEditarPrendaTecnica(${index})" style="
                        background: none;
                        border: none;
                        font-size: 1.3rem;
                        cursor: pointer;
                        color: #333;
                    ">
                        ✕
                    </button>
                </div>
                
                <form id="form-edicion-prenda-${index}" style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <!-- Nombre de Prenda -->
                    <div>
                        <label style="display: block; font-weight: 700; margin-bottom: 0.3rem; color: #333; font-size: 0.9rem;">
                            Nombre de Prenda
                        </label>
                        <input type="text" id="nombre-prenda-${index}" value="${prenda.nombre_prenda}" style="
                            width: 100%;
                            padding: 0.6rem;
                            border: 1px solid #ddd;
                            font-size: 0.9rem;
                            box-sizing: border-box;
                        ">
                    </div>
                    
                    <!-- Observaciones -->
                    <div>
                        <label style="display: block; font-weight: 700; margin-bottom: 0.3rem; color: #333; font-size: 0.9rem;">
                            Observaciones
                        </label>
                        <textarea id="obs-prenda-${index}" style="
                            width: 100%;
                            padding: 0.6rem;
                            border: 1px solid #ddd;
                            font-size: 0.9rem;
                            min-height: 80px;
                            resize: vertical;
                            box-sizing: border-box;
                        ">${prenda.observaciones || ''}</textarea>
                    </div>
                    
                    <!-- Ubicaciones -->
                    <div>
                        <label style="display: block; font-weight: 700; margin-bottom: 0.7rem; color: #333; font-size: 0.9rem;">
                            Ubicaciones
                        </label>
                        
                        <!-- Input para agregar nuevas ubicaciones -->
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.8rem;">
                            <input 
                                type="text" 
                                id="ubicacion-input-${index}" 
                                list="ubicaciones-list-${index}"
                                placeholder="Escribe una ubicación..."
                                style="
                                    flex: 1;
                                    padding: 0.6rem;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    font-size: 0.9rem;
                                    box-sizing: border-box;
                                ">
                            <button 
                                type="button" 
                                onclick="agregarUbicacionPrenda(${index})"
                                style="
                                    background: #1e40af;
                                    color: white;
                                    border: none;
                                    padding: 0.6rem 1rem;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-weight: 600;
                                    font-size: 0.9rem;
                                    transition: background 0.2s;
                                "
                                onmouseover="this.style.background='#1e3a8a'"
                                onmouseout="this.style.background='#1e40af'">
                                + Agregar
                            </button>
                        </div>
                        
                        <datalist id="ubicaciones-list-${index}">
                            ${ubicacionesDisponibles.map(ub => `<option value="${ub}">`).join('')}
                        </datalist>
                        
                        <!-- Tags de ubicaciones -->
                        <div id="ubicaciones-tags-${index}" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            ${prenda.ubicaciones && prenda.ubicaciones.length > 0 
                                ? prenda.ubicaciones.map(ub => `
                                    <span class="ubicacion-tag-${index}" data-ubicacion="${ub}" style="
                                        background: #0ea5e9;
                                        color: white;
                                        padding: 0.4rem 0.8rem;
                                        border-radius: 20px;
                                        font-size: 0.85rem;
                                        display: inline-flex;
                                        align-items: center;
                                        gap: 0.5rem;
                                        font-weight: 500;
                                    ">
                                        ${ub}
                                        <button 
                                            type="button"
                                            onclick="eliminarUbicacionPrenda(${index}, '${ub}')"
                                            style="
                                                background: none;
                                                border: none;
                                                color: white;
                                                cursor: pointer;
                                                font-size: 1.1rem;
                                                padding: 0;
                                                width: 20px;
                                                height: 20px;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                            ">
                                            ×
                                        </button>
                                    </span>
                                `).join('')
                                : '<span style="color: #999; font-size: 0.85rem;">Sin ubicaciones</span>'
                            }
                        </div>
                    </div>
                    
                    <!-- Tallas y Cantidades -->
                    <div>
                        <label style="display: block; font-weight: 700; margin-bottom: 0.7rem; color: #333; font-size: 0.9rem;">
                            Tallas y Cantidades
                        </label>
                        <div id="tallas-container-${index}" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 150px; overflow-y: auto;">
                            ${Object.entries(prenda.talla_cantidad || {}).map(([talla, cantidad]) => `
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" value="${talla}" disabled style="
                                        flex: 1;
                                        padding: 0.4rem;
                                        border: 1px solid #ddd;
                                        background: #f5f5f5;
                                        color: #666;
                                        font-size: 0.85rem;
                                    ">
                                    <input type="number" class="cantidad-talla-${index}" data-talla="${talla}" value="${cantidad}" min="0" style="
                                        width: 70px;
                                        padding: 0.4rem;
                                        border: 1px solid #ddd;
                                        font-size: 0.85rem;
                                    ">
                                    <button type="button" onclick="quitarTallaPrenda(${index}, '${talla}')" style="
                                        background: #ddd;
                                        color: #333;
                                        border: none;
                                        padding: 0.4rem 0.6rem;
                                        cursor: pointer;
                                        font-size: 0.8rem;
                                    ">
                                        ✕
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" onclick="abrirAgregarTallaPrenda(${index})" style="
                            width: 100%;
                            margin-top: 0.5rem;
                            padding: 0.5rem;
                            background: white;
                            border: 1px solid #1e40af;
                            color: #1e40af;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.85rem;
                        ">
                            + Agregar Talla
                        </button>
                    </div>
                    
                    <!-- Fotos -->
                    <div>
                        <label style="display: block; font-weight: 700; margin-bottom: 0.7rem; color: #333; font-size: 0.9rem;">
                            Fotos (${prenda.fotos ? prenda.fotos.length : 0})
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; margin-bottom: 0.8rem;">
                            ${prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos.map((foto, fotoIdx) => `
                                <div style="position: relative; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                    <img src="${foto.ruta_webp || foto.url}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                                    <button type="button" onclick="eliminarFotoDePrenda(${index}, ${fotoIdx})" style="
                                        position: absolute;
                                        top: 2px;
                                        right: 2px;
                                        background: #ef4444;
                                        color: white;
                                        border: none;
                                        width: 20px;
                                        height: 20px;
                                        font-size: 0.7rem;
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        border-radius: 3px;
                                    ">
                                        ✕
                                    </button>
                                </div>
                            `).join('') : ''}
                        </div>
                        <!-- Zona Drag and Drop para Fotos -->
                        <div class="drop-zone-edicion-${index}" style="
                            border: 2px dashed #1e40af;
                            border-radius: 6px;
                            padding: 1.2rem;
                            text-align: center;
                            background: #f0f7ff;
                            cursor: pointer;
                            transition: all 0.3s;
                            user-select: none;
                        ">
                            <div style="font-size: 1.8rem; margin-bottom: 0.4rem;">📸</div>
                            <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.2rem; font-size: 0.9rem;">
                                Arrastra fotos aquí
                            </div>
                            <div style="color: #6b7280; font-size: 0.8rem;">
                                o haz clic para seleccionar
                            </div>
                            <input type="file" class="file-input-edicion-${index}" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 0.8rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                        <button type="button" onclick="guardarEdicionPrenda(${index})" style="
                            flex: 1;
                            background: #1e40af;
                            color: white;
                            border: none;
                            padding: 0.6rem;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 0.9rem;
                        ">
                            Guardar
                        </button>
                        <button type="button" onclick="cerrarModalEditarPrendaTecnica(${index})" style="
                            flex: 1;
                            background: #ddd;
                            color: #333;
                            border: none;
                            padding: 0.6rem;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 0.9rem;
                        ">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
}

// =========================================================
// 4.3 CERRAR MODAL Y GUARDAR CAMBIOS
// =========================================================

window.cerrarModalEditarPrendaTecnica = function(index) {
    const modal = document.getElementById(`modal-prenda-${index}`);
    if (modal) {
        modal.remove();
    }
};

window.guardarEdicionPrenda = function(index) {
    const prenda = logoPrendasTecnicas[index];
    if (!prenda) return;
    
    // Actualizar nombre
    const nombreInput = document.getElementById(`nombre-prenda-${index}`);
    if (nombreInput) {
        prenda.nombre_prenda = nombreInput.value;
    }
    
    // Actualizar observaciones
    const obsInput = document.getElementById(`obs-prenda-${index}`);
    if (obsInput) {
        prenda.observaciones = obsInput.value;
    }
    
    // Actualizar ubicaciones desde los tags
    const ubicacionesTags = document.querySelectorAll(`.ubicacion-tag-${index}`);
    prenda.ubicaciones = Array.from(ubicacionesTags).map(tag => tag.dataset.ubicacion);
    
    // Actualizar cantidades de tallas
    const cantidadInputs = document.querySelectorAll(`.cantidad-talla-${index}`);
    cantidadInputs.forEach(input => {
        const talla = input.dataset.talla;
        const cantidad = parseInt(input.value) || 0;
        prenda.talla_cantidad[talla] = cantidad;
    });
    
    console.log('✅ Prenda guardada:', prenda);
    
    // Sincronizar cambios
    window.logoPrendasTecnicas = logoPrendasTecnicas;
    sincronizarPrendasTecnicas();
    
    // Cerrar modal
    cerrarModalEditarPrendaTecnica(index);
    
    // Renderizar nuevamente
    renderizarLogoPrendasTecnicas();
};

// =========================================================
// 4.4 FUNCIONES AUXILIARES DE EDICIÓN - UBICACIONES
// =========================================================

window.agregarUbicacionPrenda = function(index) {
    const input = document.getElementById(`ubicacion-input-${index}`);
    const ubicacionText = input.value.trim().toUpperCase();
    
    if (!ubicacionText) {
        alert('Por favor ingresa una ubicación');
        return;
    }
    
    // Verificar si ya existe
    const tagsContainer = document.getElementById(`ubicaciones-tags-${index}`);
    const existente = tagsContainer.querySelector(`[data-ubicacion="${ubicacionText}"]`);
    
    if (existente) {
        alert('Esta ubicación ya está agregada');
        return;
    }
    
    // Limpiar el tag "Sin ubicaciones" si existe
    const sinUbicacionesSpan = tagsContainer.querySelector('span:not([data-ubicacion])');
    if (sinUbicacionesSpan && sinUbicacionesSpan.textContent.includes('Sin ubicaciones')) {
        sinUbicacionesSpan.remove();
    }
    
    // Crear el nuevo tag
    const newTag = document.createElement('span');
    newTag.className = `ubicacion-tag-${index}`;
    newTag.dataset.ubicacion = ubicacionText;
    newTag.style.cssText = `
        background: #0ea5e9;
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    `;
    newTag.innerHTML = `
        ${ubicacionText}
        <button 
            type="button"
            onclick="eliminarUbicacionPrenda(${index}, '${ubicacionText}')"
            style="
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                font-size: 1.1rem;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
            ×
        </button>
    `;
    
    tagsContainer.appendChild(newTag);
    input.value = '';
    input.focus();
    
    console.log(`✅ Ubicación "${ubicacionText}" agregada`);
};

window.eliminarUbicacionPrenda = function(index, ubicacion) {
    const tag = document.querySelector(`.ubicacion-tag-${index}[data-ubicacion="${ubicacion}"]`);
    if (tag) {
        tag.remove();
        
        // Verificar si no hay más ubicaciones
        const tagsContainer = document.getElementById(`ubicaciones-tags-${index}`);
        if (tagsContainer.querySelectorAll('[data-ubicacion]').length === 0) {
            tagsContainer.innerHTML = '<span style="color: #999; font-size: 0.85rem;">Sin ubicaciones</span>';
        }
        
        console.log(`✅ Ubicación "${ubicacion}" eliminada`);
    }
};

// =========================================================
// 4.5 FUNCIONES AUXILIARES DE EDICIÓN - TALLAS Y FOTOS
// =========================================================

window.quitarTallaPrenda = function(index, talla) {
    const prenda = logoPrendasTecnicas[index];
    if (prenda && prenda.talla_cantidad) {
        delete prenda.talla_cantidad[talla];
        console.log(`✅ Talla ${talla} eliminada`);
        // Actualizar modal
        const modal = document.getElementById(`modal-prenda-${index}`);
        if (modal) {
            cerrarModalEditarPrendaTecnica(index);
            abrirModalEditarPrendaTecnica(index);
        }
    }
};

window.eliminarFotoDePrenda = function(index, fotoIdx) {
    const prenda = logoPrendasTecnicas[index];
    if (prenda && prenda.fotos) {
        prenda.fotos.splice(fotoIdx, 1);
        console.log(`✅ Foto ${fotoIdx} eliminada`);
        // Actualizar modal
        const modal = document.getElementById(`modal-prenda-${index}`);
        if (modal) {
            cerrarModalEditarPrendaTecnica(index);
            abrirModalEditarPrendaTecnica(index);
        }
    }
};

window.abrirAgregarTallaPrenda = function(index) {
    const talla = prompt('Ingresa la talla:');
    if (talla && talla.trim()) {
        const cantidad = prompt('Ingresa la cantidad:', '0');
        const prenda = logoPrendasTecnicas[index];
        if (prenda && prenda.talla_cantidad) {
            prenda.talla_cantidad[talla.trim()] = parseInt(cantidad) || 0;
            console.log(`✅ Talla ${talla} agregada`);
            // Actualizar modal
            const modal = document.getElementById(`modal-prenda-${index}`);
            if (modal) {
                cerrarModalEditarPrendaTecnica(index);
                abrirModalEditarPrendaTecnica(index);
            }
        }
    }
};

window.abrirModalFotosPrenda = function(index) {
    console.log('📸 Abriendo modal de fotos para prenda:', index);
    
    const prenda = logoPrendasTecnicas[index];
    if (!prenda) {
        console.error('Prenda no encontrada');
        return;
    }

    Swal.fire({
        title: 'Agregar Fotos a la Prenda',
        width: '550px',
        html: `
            <div style="text-align: left;">
                <!-- Galería actual -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 0.95rem;">
                        Fotos actuales (${prenda.fotos ? prenda.fotos.length : 0})
                    </label>
                    <div id="galeriaFotosActuales" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px; margin-bottom: 1rem;">
                        ${prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos.map((foto, idx) => `
                            <div style="position: relative; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                <img src="${foto.ruta_webp || foto.url}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                                <button type="button" onclick="this.parentElement.remove()" style="
                                    position: absolute;
                                    top: 2px;
                                    right: 2px;
                                    background: #ef4444;
                                    color: white;
                                    border: none;
                                    width: 20px;
                                    height: 20px;
                                    font-size: 0.7rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border-radius: 3px;
                                ">
                                    ✕
                                </button>
                            </div>
                        `).join('') : '<p style="color: #999; font-size: 0.9rem;">Sin fotos aún</p>'}
                    </div>
                </div>

                <!-- Zona Drag and Drop para nuevas fotos -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 0.95rem;">
                        Agregar nuevas fotos
                    </label>
                    <div id="modalDropZone" style="
                        border: 2px dashed #1e40af;
                        border-radius: 8px;
                        padding: 2rem;
                        text-align: center;
                        background: #f0f7ff;
                        cursor: pointer;
                        transition: all 0.3s;
                        user-select: none;
                    ">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📸</div>
                        <div style="font-weight: 700; color: #1e40af; margin-bottom: 0.3rem; font-size: 0.95rem;">
                            Arrastra fotos aquí
                        </div>
                        <div style="color: #6b7280; font-size: 0.85rem;">
                            o haz clic para seleccionar
                        </div>
                        <input type="file" id="inputFotosModalPrenda" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                    </div>
                </div>

                <!-- Vista previa de nuevas fotos -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 0.95rem;">
                        Nuevas fotos a agregar
                    </label>
                    <div id="previewFotosModalPrenda" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px;"></div>
                </div>
            </div>
        `,
        didOpen: (modal) => {
            const dropZone = document.getElementById('modalDropZone');
            const fileInput = document.getElementById('inputFotosModalPrenda');
            const previewDiv = document.getElementById('previewFotosModalPrenda');
            let fotosTemporales = [];

            // Función para procesar archivos
            function procesarArchivos(files) {
                const archivos = Array.from(files).filter(file => file.type.startsWith('image/'));
                
                if (archivos.length === 0) {
                    Swal.showValidationMessage('Por favor selecciona solo imágenes');
                    return;
                }
                
                archivos.forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        fotosTemporales.push({
                            preview: event.target.result,
                            nombre: file.name
                        });

                        const previewHTML = document.createElement('div');
                        previewHTML.style.cssText = 'position: relative; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;';
                        previewHTML.innerHTML = `
                            <img src="${event.target.result}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                            <button type="button" 
                                onclick="this.parentElement.remove()"
                                style="
                                    position: absolute;
                                    top: 2px;
                                    right: 2px;
                                    background: #ef4444;
                                    color: white;
                                    border: none;
                                    width: 20px;
                                    height: 20px;
                                    font-size: 0.7rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border-radius: 3px;
                                ">
                                ✕
                            </button>
                        `;
                        previewDiv.appendChild(previewHTML);
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Click en zona drag-drop
            dropZone.addEventListener('click', function() {
                fileInput.click();
            });

            // Cambio en input file
            fileInput.addEventListener('change', function(e) {
                procesarArchivos(e.target.files);
                fileInput.value = '';
            });

            // Drag over
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#e0f2fe';
                dropZone.style.borderColor = '#0284c7';
            });

            // Drag leave
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#f0f7ff';
                dropZone.style.borderColor = '#1e40af';
            });

            // Drop
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#f0f7ff';
                dropZone.style.borderColor = '#1e40af';
                
                const files = e.dataTransfer.files;
                procesarArchivos(files);
            });
        },
        showCancelButton: true,
        confirmButtonText: 'Guardar Fotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const previews = document.querySelectorAll('#previewFotosModalPrenda > div');
            if (previews.length === 0) {
                Swal.showValidationMessage('Agrega al menos una foto nueva');
                return false;
            }
            
            return Array.from(previews).map((el) => {
                const img = el.querySelector('img');
                return {
                    preview: img.src,
                    url: img.src,
                    ruta_webp: img.src,
                    nuevo: true
                };
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Agregar fotos a la prenda
            logoPrendasTecnicas[index].fotos.push(...result.value);
            
            // Sincronizar y renderizar
            window.logoPrendasTecnicas = logoPrendasTecnicas;
            sincronizarPrendasTecnicas();
            renderizarLogoPrendasTecnicas();
            
            // Reabrirse el modal de edición
            setTimeout(() => {
                abrirModalEditarPrendaTecnica(index);
            }, 300);
        }
    });
};

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

// =========================================================
// 6. INICIALIZACIÓN
// =========================================================

console.log('✅ logo-pedido-tecnicas.js completamente cargado - CÓDIGO ÚTIL HASTA LÍNEA 575');

/*
 * ════════════════════════════════════════════════════════════════
 * CÓDIGO ANTIGUO ARCHIVADO - NO USAR
 * ════════════════════════════════════════════════════════════════
 * 
 * Las siguientes funciones fueron reemplazadas por un nuevo sistema
 * de modal dinámico (ver líneas 250-427):
 * 
 * - crearFormularioEdicionPrenda() → REEMPLAZADO por crearModalEdicionPrenda()
 * - toggleFormularioEdicionPrenda() → REEMPLAZADO por modal modal system
 * - abrirModalAgregarPrendaTecnicaLogo() → REEMPLAZADO
 * - mostrarModalFormularioPrendaTecnica() → REEMPLAZADO
 * 
 * Se mantiene este código como referencia histórica solamente.
 * ════════════════════════════════════════════════════════════════
 */

/* FUNCIÓN ANTIGUA (eliminada):
function crearFormularioEdicionPrenda(prenda, index) {
    const ubicacionesDisponibles = [
        'PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO',
        'PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO',
        'FRENTE', 'LATERAL', 'TRASERA'
    ];
    
    let html = `<form id="form-edicion-${index}" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Nombre de Prenda -->
        <div>
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.9rem;">
                Nombre de Prenda
            </label>
            <input type="text" id="nombre-prenda-${index}" value="${prenda.nombre_prenda}" style="
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                font-size: 0.95rem;
                transition: all 0.2s;
                box-sizing: border-box;
            " onfocus="this.style.borderColor='#1e40af'; this.style.boxShadow='0 0 0 3px rgba(30, 64, 175, 0.1)'"
               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>
        
        <!-- Observaciones -->
        <div>
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.9rem;">
                Observaciones
            </label>
            <textarea id="obs-prenda-${index}" style="
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                font-size: 0.95rem;
                min-height: 80px;
                resize: vertical;
                box-sizing: border-box;
                transition: all 0.2s;
            " onfocus="this.style.borderColor='#1e40af'; this.style.boxShadow='0 0 0 3px rgba(30, 64, 175, 0.1)'"
               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">${prenda.observaciones || ''}</textarea>
        </div>
        
        <!-- Ubicaciones -->
        <div>
            <label style="display: block; font-weight: 700; margin-bottom: 1rem; color: #1f2937; font-size: 0.9rem;">
                Ubicaciones
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem;">
                ${ubicacionesDisponibles.map(ub => `
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: all 0.2s; background: #f9fafb;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">
                        <input type="checkbox" class="ubicacion-check-${index}" value="${ub}" 
                            ${prenda.ubicaciones && prenda.ubicaciones.includes(ub) ? 'checked' : ''}
                            style="width: 16px; height: 16px; cursor: pointer;">
                        <span style="font-size: 0.85rem; color: #374151; font-weight: 500;">${ub}</span>
                    </label>
                `).join('')}
            </div>
        </div>
        
        <!-- Tallas y Cantidades -->
        <div>
            <label style="display: block; font-weight: 700; margin-bottom: 1rem; color: #1f2937; font-size: 0.9rem;">
                Tallas y Cantidades
            </label>
            <div id="tallas-container-${index}" style="display: flex; flex-direction: column; gap: 0.75rem;">
                ${Object.entries(prenda.talla_cantidad || {}).map(([talla, cantidad]) => `
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="text" value="${talla}" class="talla-nombre-${index}" placeholder="Talla" style="
                            flex: 0.5;
                            padding: 0.6rem;
                            border: 2px solid #e5e7eb;
                            border-radius: 6px;
                            font-size: 0.85rem;
                            transition: all 0.2s;
                        " onfocus="this.style.borderColor='#1e40af'" onblur="this.style.borderColor='#e5e7eb'">
                        <input type="number" value="${cantidad}" min="0" class="talla-cantidad-${index}" placeholder="Cantidad" style="
                            flex: 1;
                            padding: 0.6rem;
                            border: 2px solid #e5e7eb;
                            border-radius: 6px;
                            font-size: 0.85rem;
                            transition: all 0.2s;
                        " onfocus="this.style.borderColor='#1e40af'" onblur="this.style.borderColor='#e5e7eb'">
                        <button type="button" onclick="eliminarFilaTalla(${index}, this)" style="
                            background: #ef4444;
                            color: white;
                            border: none;
                            padding: 0.6rem 0.8rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.8rem;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                            ✕
                        </button>
                    </div>
                `).join('')}
            </div>
            <button type="button" onclick="agregarFilaTallaEdicion(${index})" style="
                background: #1e40af;
                color: white;
                border: none;
                padding: 0.6rem 1rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                margin-top: 0.75rem;
                transition: all 0.2s;
            " onmouseover="this.style.background='#1e3a8a'" onmouseout="this.style.background='#1e40af'">
                + Agregar Talla
            </button>
        </div>
        
        <!-- Fotos -->
        <div>
            <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1f2937; font-size: 0.9rem;">
                Fotos de la Prenda
            </label>
            <div id="galeria-prenda-${index}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                ${prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos.map((foto, fotoIdx) => `
                    <div style="position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1; background: #f3f4f6; border: 2px solid #e5e7eb;">
                        <img src="${foto.ruta_webp || foto.url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                        <button type="button" onclick="eliminarFotoDePrenda(${index}, ${fotoIdx})" style="
                            position: absolute;
                            top: 4px;
                            right: 4px;
                            background: rgba(239, 68, 68, 0.9);
                            color: white;
                            border: none;
                            width: 28px;
                            height: 28px;
                            border-radius: 50%;
                            cursor: pointer;
                            font-weight: bold;
                            font-size: 1rem;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='rgba(220, 38, 38, 1)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.9)'">
                            ✕
                        </button>
                    </div>
                `).join('') : '<p style="grid-column: 1/-1; text-align: center; color: #9ca3af; font-size: 0.9rem;">Sin fotos aún</p>'}
            </div>
            <button type="button" onclick="abrirModalFotosPrenda(${index})" style="
                width: 100%;
                background: #f3f4f6;
                color: #374151;
                border: 2px dashed #d1d5db;
                padding: 1rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.9rem;
                transition: all 0.2s;
            " onmouseover="this.style.background='#e5e7eb'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'">
                📸 Agregar/Cambiar Fotos
            </button>
        </div>
        
        <!-- Botones -->
        <div style="display: flex; gap: 0.75rem; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #f3f4f6;">
            <button type="button" onclick="guardarEdicionPrenda(${index})" style="
                flex: 1;
                background: #16a34a;
                color: white;
                border: none;
                padding: 0.75rem 1rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 700;
                font-size: 0.95rem;
                transition: all 0.2s;
            " onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                ✅ Guardar Cambios
            </button>
            <button type="button" onclick="toggleFormularioEdicionPrenda(${index})" style="
                flex: 1;
                background: #ef4444;
                color: white;
                border: none;
                padding: 0.75rem 1rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 700;
                font-size: 0.95rem;
                transition: all 0.2s;
            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                ✕ Cancelar
            </button>
        </div>
    </form>`;
    
    return html;
}

// =========================================================
// 4.2 TOGGLE FORMULARIO DE EDICIÓN
// =========================================================

function toggleFormularioEdicionPrenda(index) {
    const resumen = document.getElementById(`resumen-prenda-${index}`);
    const formulario = document.getElementById(`formulario-edicion-${index}`);
    
    if (!resumen || !formulario) return;
    
    if (formulario.style.display === 'none') {
        formulario.style.display = 'block';
        resumen.style.display = 'none';
    } else {
        formulario.style.display = 'none';
        resumen.style.display = 'block';
    }
}

// =========================================================
// 4.3 GUARDAR EDICIÓN DE PRENDA
// =========================================================

function guardarEdicionPrenda(index) {
    const nombrePrenda = document.getElementById(`nombre-prenda-${index}`)?.value.trim();
    const observaciones = document.getElementById(`obs-prenda-${index}`)?.value.trim();
    
    if (!nombrePrenda) {
        Swal.fire('Error', 'El nombre de la prenda es requerido', 'error');
        return;
    }
    
    // Recopilar ubicaciones seleccionadas
    const ubicacionesChecks = document.querySelectorAll(`.ubicacion-check-${index}:checked`);
    const ubicaciones = Array.from(ubicacionesChecks).map(cb => cb.value);
    
    if (ubicaciones.length === 0) {
        Swal.fire('Error', 'Selecciona al menos una ubicación', 'error');
        return;
    }
    
    // Recopilar tallas
    const tallaNombres = document.querySelectorAll(`.talla-nombre-${index}`);
    const tallaCantidades = document.querySelectorAll(`.talla-cantidad-${index}`);
    const tallaCantidad = {};
    
    tallaNombres.forEach((input, idx) => {
        const talla = input.value.trim();
        const cantidad = parseInt(tallaCantidades[idx].value) || 0;
        if (talla && cantidad > 0) {
            tallaCantidad[talla] = cantidad;
        }
    });
    
    if (Object.keys(tallaCantidad).length === 0) {
        Swal.fire('Error', 'Agrega al menos una talla con cantidad', 'error');
        return;
    }
    
    // Actualizar prenda en array global
    logoPrendasTecnicas[index] = {
        ...logoPrendasTecnicas[index],
        nombre_prenda: nombrePrenda,
        observaciones: observaciones,
        ubicaciones: ubicaciones,
        talla_cantidad: tallaCantidad,
        modificada: true
    };
    
    renderizarLogoPrendasTecnicas();
    Swal.fire('Éxito', 'Prenda actualizada correctamente', 'success');
}

// =========================================================
// 4.4 AGREGAR FILA DE TALLA EN EDICIÓN
// =========================================================

function agregarFilaTallaEdicion(index) {
    const container = document.getElementById(`tallas-container-${index}`);
    if (!container) return;
    
    const nuevoDiv = document.createElement('div');
    nuevoDiv.style.cssText = 'display: flex; gap: 0.75rem; align-items: center;';
    nuevoDiv.innerHTML = `
        <input type="text" class="talla-nombre-${index}" placeholder="Talla" style="
            flex: 0.5;
            padding: 0.6rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
        " onfocus="this.style.borderColor='#1e40af'" onblur="this.style.borderColor='#e5e7eb'">
        <input type="number" min="0" class="talla-cantidad-${index}" placeholder="Cantidad" style="
            flex: 1;
            padding: 0.6rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
        " onfocus="this.style.borderColor='#1e40af'" onblur="this.style.borderColor='#e5e7eb'">
        <button type="button" onclick="eliminarFilaTalla(${index}, this)" style="
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s;
        " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
            ✕
        </button>
    `;
    
    container.appendChild(nuevoDiv);
}

function eliminarFilaTalla(index, button) {
    button.parentElement.remove();
}

/**
 * Agregar fotos a una prenda desde el formulario de edición
 */
function abrirModalFotosPrenda(indexPrenda) {
    Swal.fire({
        title: 'Agregar Fotos a la Prenda',
        width: '500px',
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                        Selecciona fotos (máximo 5, formatos: JPG, PNG, WebP)
                    </label>
                    <input type="file" 
                        id="inputFotosModalPrenda" 
                        accept="image/jpeg,image/png,image/webp"
                        multiple 
                        style="display: block; width: 100%; padding: 8px; border: 2px dashed #0066cc; border-radius: 4px; cursor: pointer;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                        Vista previa:
                    </label>
                    <div id="previewFotosModalPrenda" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px;"></div>
                </div>
            </div>
        `,
        didOpen: (modal) => {
            const inputFotos = document.getElementById('inputFotosModalPrenda');
            const previewDiv = document.getElementById('previewFotosModalPrenda');
            let fotosTemporales = [];
            
            inputFotos.addEventListener('change', function(e) {
                fotosTemporales = [];
                previewDiv.innerHTML = '';
                
                if (this.files.length > 5) {
                    Swal.showValidationMessage(`Máximo 5 fotos. Seleccionaste ${this.files.length}`);
                    return;
                }
                
                Array.from(this.files).forEach((file, idx) => {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        fotosTemporales.push({
                            file: file,
                            preview: event.target.result,
                            nombre: file.name
                        });
                        
                        const previewHTML = document.createElement('div');
                        previewHTML.style.cssText = 'position: relative; border-radius: 4px; overflow: hidden; background: #f0f0f0; aspect-ratio: 1;';
                        previewHTML.innerHTML = `
                            <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" 
                                onclick="this.parentElement.remove()"
                                style="position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center;">
                                ✕
                            </button>
                        `;
                        previewDiv.appendChild(previewHTML);
                    };
                    reader.readAsDataURL(file);
                });
            });
        },
        showCancelButton: true,
        confirmButtonText: 'Agregar Fotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const previews = document.querySelectorAll('#previewFotosModalPrenda > div');
            if (previews.length === 0) {
                Swal.showValidationMessage('Selecciona al menos una foto');
                return false;
            }
            
            return Array.from(previews).map((el, idx) => {
                const img = el.querySelector('img');
                return {
                    preview: img.src,
                    nombre: `foto-${idx}`
                };
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Agregar fotos a la prenda
            logoPrendasTecnicas[indexPrenda].fotos.push(...result.value.map(foto => ({
                ...foto,
                nuevo: true
            })));
            
            // Actualizar el formulario de edición
            const galeriaDiv = document.getElementById(`galeria-prenda-${indexPrenda}`);
            if (galeriaDiv) {
                let html = '';
                logoPrendasTecnicas[indexPrenda].fotos.forEach((foto, fotoIdx) => {
                    html += `
                        <div style="position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1; background: #f3f4f6; border: 2px solid #e5e7eb;">
                            <img src="${foto.ruta_webp || foto.preview || foto.url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" onclick="eliminarFotoDePrenda(${indexPrenda}, ${fotoIdx})" style="
                                position: absolute;
                                top: 4px;
                                right: 4px;
                                background: rgba(239, 68, 68, 0.9);
                                color: white;
                                border: none;
                                width: 28px;
                                height: 28px;
                                border-radius: 50%;
                                cursor: pointer;
                                font-weight: bold;
                                font-size: 1rem;
                                transition: all 0.2s;
                            " onmouseover="this.style.background='rgba(220, 38, 38, 1)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.9)'">
                                ✕
                            </button>
                        </div>
                    `;
                });
                galeriaDiv.innerHTML = html || '<p style="grid-column: 1/-1; text-align: center; color: #9ca3af; font-size: 0.9rem;">Sin fotos aún</p>';
            }
        }
    });
}

/**
 * Eliminar foto de una prenda
 */
function eliminarFotoDePrenda(indexPrenda, indexFoto) {
    logoPrendasTecnicas[indexPrenda].fotos.splice(indexFoto, 1);
    
    // Actualizar galería visual
    const galeriaDiv = document.getElementById(`galeria-prenda-${indexPrenda}`);
    if (galeriaDiv) {
        let html = '';
        logoPrendasTecnicas[indexPrenda].fotos.forEach((foto, fotoIdx) => {
            html += `
                <div style="position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1; background: #f3f4f6; border: 2px solid #e5e7eb;">
                    <img src="${foto.ruta_webp || foto.preview || foto.url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" onclick="eliminarFotoDePrenda(${indexPrenda}, ${fotoIdx})" style="
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        background: rgba(239, 68, 68, 0.9);
                        color: white;
                        border: none;
                        width: 28px;
                        height: 28px;
                        border-radius: 50%;
                        cursor: pointer;
                        font-weight: bold;
                        font-size: 1rem;
                    ">
                        ✕
                    </button>
                </div>
            `;
        });
        galeriaDiv.innerHTML = html || '<p style="grid-column: 1/-1; text-align: center; color: #9ca3af; font-size: 0.9rem;">Sin fotos aún</p>';
    }
}

// =========================================================
// 5. AGREGAR/EDITAR PRENDA TÉCNICA (MODAL SIMPLIFICADO)
// =========================================================

function abrirModalAgregarPrendaTecnicaLogo() {
    // Cargar tipos disponibles si no están
    if (tiposLogosDisponibles.length === 0) {
        cargarTiposLogosDisponibles().then(() => {
            mostrarModalFormularioPrendaTecnica();
        });
    } else {
        mostrarModalFormularioPrendaTecnica();
    }
}

function mostrarModalFormularioPrendaTecnica(indexEditar = null) {
    const datosActuales = indexEditar !== null ? logoPrendasTecnicas[indexEditar] : null;
    const ubicacionesDisponibles = [
        'PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO',
        'PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO',
        'FRENTE', 'LATERAL', 'TRASERA'
    ];
    
    // Select de tipos
    const selectTipos = `
        <select id="modalTipoLogoPrenda" style="width: 100%; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; margin-bottom: 1rem; transition: all 0.2s;" onfocus="this.style.borderColor='#1e40af'; this.style.boxShadow='0 0 0 3px rgba(30, 64, 175, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
            <option value="">-- Selecciona una técnica --</option>
            ${tiposLogosDisponibles.map(tipo => `
                <option value="${tipo.id}" ${datosActuales && datosActuales.tipo_logo_id === tipo.id ? 'selected' : ''}>
                    ${tipo.nombre}
                </option>
            `).join('')}
        </select>
    `;
    
    Swal.fire({
        title: indexEditar !== null ? 'Editar Prenda Técnica' : 'Agregar Nueva Prenda Técnica',
        width: '650px',
        html: `
            <div style="text-align: left; max-height: 700px; overflow-y: auto;">
                <!-- Técnica -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Técnica:</label>
                    ${selectTipos}
                </div>
                
                <!-- Nombre de Prenda -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Nombre de Prenda:</label>
                    <input type="text" id="modalNombrePrendaTecnica" placeholder="Ej: Camisa, Gorro, Bolsa" 
                        value="${datosActuales ? datosActuales.nombre_prenda : ''}"
                        style="width: 100%; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; transition: all 0.2s;" onfocus="this.style.borderColor='#1e40af'; this.style.boxShadow='0 0 0 3px rgba(30, 64, 175, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                </div>
                
                <!-- Ubicaciones con sistema de tags -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.7rem; color: #1f2937; font-size: 0.95rem;">Ubicaciones:</label>
                    
                    <!-- Input para agregar nuevas ubicaciones -->
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.8rem;">
                        <input 
                            type="text" 
                            id="ubicacion-input-modal" 
                            list="ubicaciones-list-modal"
                            placeholder="Escribe una ubicación..."
                            style="
                                flex: 1;
                                padding: 0.6rem;
                                border: 1px solid #ddd;
                                border-radius: 4px;
                                font-size: 0.9rem;
                                box-sizing: border-box;
                            ">
                        <button 
                            type="button" 
                            id="btn-agregar-ubicacion-modal"
                            style="
                                background: #1e40af;
                                color: white;
                                border: none;
                                padding: 0.6rem 1rem;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.9rem;
                                transition: background 0.2s;
                            "
                            onmouseover="this.style.background='#1e3a8a'"
                            onmouseout="this.style.background='#1e40af'">
                            + Agregar
                        </button>
                    </div>
                    
                    <datalist id="ubicaciones-list-modal">
                        ${ubicacionesDisponibles.map(ub => `<option value="${ub}">`).join('')}
                    </datalist>
                    
                    <!-- Tags de ubicaciones -->
                    <div id="ubicaciones-tags-modal" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        ${datosActuales && datosActuales.ubicaciones && datosActuales.ubicaciones.length > 0 
                            ? datosActuales.ubicaciones.map(ub => `
                                <span class="ubicacion-tag-modal" data-ubicacion="${ub}" style="
                                    background: #0ea5e9;
                                    color: white;
                                    padding: 0.4rem 0.8rem;
                                    border-radius: 20px;
                                    font-size: 0.85rem;
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    font-weight: 500;
                                ">
                                    ${ub}
                                    <button 
                                        type="button"
                                        onclick="this.parentElement.remove()"
                                        style="
                                            background: none;
                                            border: none;
                                            color: white;
                                            cursor: pointer;
                                            font-size: 1.1rem;
                                            padding: 0;
                                            width: 20px;
                                            height: 20px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                        ">
                                        ×
                                    </button>
                                </span>
                            `).join('')
                            : '<span style="color: #999; font-size: 0.85rem;">Sin ubicaciones</span>'
                        }
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Observaciones:</label>
                    <textarea id="modalObservacionesPrendaTecnica" placeholder="Detalles adicionales (opcional)"
                        style="width: 100%; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; min-height: 60px; resize: vertical; transition: all 0.2s;" onfocus="this.style.borderColor='#1e40af'; this.style.boxShadow='0 0 0 3px rgba(30, 64, 175, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
${datosActuales ? datosActuales.observaciones : ''}</textarea>
                </div>
                
                <!-- Tallas y Cantidades -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Tallas y Cantidades:</label>
                    <div id="modal-tallas-container" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 150px; overflow-y: auto; margin-bottom: 0.5rem;">
                        ${datosActuales && Object.keys(datosActuales.talla_cantidad || {}).length > 0
                            ? Object.entries(datosActuales.talla_cantidad).map(([talla, cantidad]) => `
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" value="${talla}" disabled style="flex: 1; padding: 0.4rem; border: 1px solid #ddd; background: #f5f5f5; color: #666; font-size: 0.85rem; border-radius: 4px;">
                                    <input type="number" class="modal-cantidad-talla" data-talla="${talla}" value="${cantidad}" min="0" style="width: 70px; padding: 0.4rem; border: 1px solid #ddd; font-size: 0.85rem; border-radius: 4px;">
                                    <button type="button" onclick="this.parentElement.remove()" style="background: #ddd; color: #333; border: none; padding: 0.4rem 0.6rem; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">✕</button>
                                </div>
                            `).join('')
                            : ''
                        }
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="modal-nueva-talla" placeholder="Ej: S, M, L, XL" style="flex: 1; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <input type="number" id="modal-nueva-cantidad" placeholder="Cantidad" min="0" value="0" style="width: 80px; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <button type="button" id="modal-btn-agregar-talla" style="background: #1e40af; color: white; border: none; padding: 0.6rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">+ Agregar</button>
                    </div>
                </div>

                <!-- Fotos -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.7rem; color: #1f2937; font-size: 0.95rem;">
                        Fotos (${datosActuales && datosActuales.fotos ? datosActuales.fotos.length : 0})
                    </label>
                    <div id="modal-galeria-prenda" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; margin-bottom: 0.8rem;">
                        ${datosActuales && datosActuales.fotos && datosActuales.fotos.length > 0 ? datosActuales.fotos.map((foto, fotoIdx) => `
                            <div style="position: relative; border: 1px solid #ddd;">
                                <img src="${foto.ruta_webp || foto.url}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                                <button type="button" onclick="this.parentElement.remove()" style="
                                    position: absolute;
                                    top: 2px;
                                    right: 2px;
                                    background: #ef4444;
                                    color: white;
                                    border: none;
                                    width: 20px;
                                    height: 20px;
                                    font-size: 0.7rem;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">
                                    ✕
                                </button>
                            </div>
                        `).join('') : ''}
                    </div>
                    <!-- Zona Drag and Drop -->
                    <div id="modal-drop-zone" style="
                        border: 2px dashed #1e40af;
                        border-radius: 8px;
                        padding: 2rem;
                        text-align: center;
                        background: #f0f7ff;
                        cursor: pointer;
                        transition: all 0.3s;
                        user-select: none;
                    ">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📸</div>
                        <div style="font-weight: 700; color: #1e40af; margin-bottom: 0.3rem; font-size: 0.95rem;">
                            Arrastra fotos aquí
                        </div>
                        <div style="color: #6b7280; font-size: 0.85rem; margin-bottom: 0.8rem;">
                            o haz clic para seleccionar (JPG, PNG, WebP)
                        </div>
                        <input type="file" id="modal-file-input" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">
                    </div>
                </div>
            </div>
        `,
        didOpen: (modal) => {
            // ============================================
            // Manejar UBICACIONES dinámicas
            // ============================================
            const btnAgregarUbicacion = document.getElementById('btn-agregar-ubicacion-modal');
            const inputUbicacion = document.getElementById('ubicacion-input-modal');
            const tagsContainer = document.getElementById('ubicaciones-tags-modal');

            btnAgregarUbicacion.addEventListener('click', function() {
                const ubicacionText = inputUbicacion.value.trim().toUpperCase();
                
                if (!ubicacionText) {
                    alert('Por favor ingresa una ubicación');
                    return;
                }
                
                // Verificar si ya existe
                const existente = tagsContainer.querySelector(`[data-ubicacion="${ubicacionText}"]`);
                
                if (existente) {
                    alert('Esta ubicación ya está agregada');
                    return;
                }
                
                // Limpiar el tag "Sin ubicaciones" si existe
                const sinUbicacionesSpan = tagsContainer.querySelector('span:not([data-ubicacion])');
                if (sinUbicacionesSpan && sinUbicacionesSpan.textContent.includes('Sin ubicaciones')) {
                    sinUbicacionesSpan.remove();
                }
                
                // Crear el nuevo tag
                const newTag = document.createElement('span');
                newTag.className = 'ubicacion-tag-modal';
                newTag.dataset.ubicacion = ubicacionText;
                newTag.style.cssText = `
                    background: #0ea5e9;
                    color: white;
                    padding: 0.4rem 0.8rem;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-weight: 500;
                `;
                newTag.innerHTML = `
                    ${ubicacionText}
                    <button 
                        type="button"
                        onclick="this.parentElement.remove()"
                        style="
                            background: none;
                            border: none;
                            color: white;
                            cursor: pointer;
                            font-size: 1.1rem;
                            padding: 0;
                            width: 20px;
                            height: 20px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                        ×
                    </button>
                `;
                
                tagsContainer.appendChild(newTag);
                inputUbicacion.value = '';
                inputUbicacion.focus();
            });

            // Permitir agregar ubicación con Enter
            inputUbicacion.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    btnAgregarUbicacion.click();
                }
            });

            // ============================================
            // Manejar TALLAS dinámicamente
            // ============================================
            const btnAgregarTalla = document.getElementById('modal-btn-agregar-talla');
            const inputTalla = document.getElementById('modal-nueva-talla');
            const inputCantidad = document.getElementById('modal-nueva-cantidad');
            const container = document.getElementById('modal-tallas-container');
            
            btnAgregarTalla.addEventListener('click', function() {
                const talla = inputTalla.value.trim().toUpperCase();
                const cantidad = parseInt(inputCantidad.value) || 0;
                
                if (!talla) {
                    alert('Ingresa una talla');
                    return;
                }
                
                // Verificar si ya existe
                const existe = container.querySelector(`input[data-talla="${talla}"]`);
                if (existe) {
                    alert('Esta talla ya existe');
                    return;
                }
                
                // Agregar nuevo campo
                const newRow = document.createElement('div');
                newRow.style.cssText = 'display: flex; gap: 0.5rem; align-items: center;';
                newRow.innerHTML = `
                    <input type="text" value="${talla}" disabled style="flex: 1; padding: 0.4rem; border: 1px solid #ddd; background: #f5f5f5; color: #666; font-size: 0.85rem; border-radius: 4px;">
                    <input type="number" class="modal-cantidad-talla" data-talla="${talla}" value="${cantidad}" min="0" style="width: 70px; padding: 0.4rem; border: 1px solid #ddd; font-size: 0.85rem; border-radius: 4px;">
                    <button type="button" onclick="this.parentElement.remove()" style="background: #ddd; color: #333; border: none; padding: 0.4rem 0.6rem; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">✕</button>
                `;
                
                container.appendChild(newRow);
                inputTalla.value = '';
                inputCantidad.value = '0';
                inputTalla.focus();
            });
            
            // Permitir agregar talla con Enter
            inputTalla.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    btnAgregarTalla.click();
                }
            });

            // ============================================
            // Manejar FOTOS con Drag and Drop
            // ============================================
            const dropZone = document.getElementById('modal-drop-zone');
            const fileInput = document.getElementById('modal-file-input');
            const galeriaDiv = document.getElementById('modal-galeria-prenda');

            // Función para procesar archivos
            function procesarArchivos(files) {
                const archivos = Array.from(files).filter(file => file.type.startsWith('image/'));
                
                if (archivos.length === 0) {
                    alert('Por favor selecciona solo imágenes');
                    return;
                }
                
                archivos.forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        // Crear elemento de foto
                        const fotoDiv = document.createElement('div');
                        fotoDiv.style.cssText = 'position: relative; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;';
                        fotoDiv.innerHTML = `
                            <img src="${event.target.result}" style="width: 100%; height: 80px; object-fit: cover;" alt="Foto">
                            <button type="button" onclick="this.parentElement.remove()" style="
                                position: absolute;
                                top: 2px;
                                right: 2px;
                                background: #ef4444;
                                color: white;
                                border: none;
                                width: 20px;
                                height: 20px;
                                font-size: 0.7rem;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 3px;
                            ">
                                ✕
                            </button>
                        `;
                        
                        galeriaDiv.appendChild(fotoDiv);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }

            // Click en zona drag-drop
            dropZone.addEventListener('click', function() {
                fileInput.click();
            });

            // Cambio en input file
            fileInput.addEventListener('change', function(e) {
                procesarArchivos(e.target.files);
                fileInput.value = '';
            });

            // Drag over
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#e0f2fe';
                dropZone.style.borderColor = '#0284c7';
            });

            // Drag leave
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#f0f7ff';
                dropZone.style.borderColor = '#1e40af';
            });

            // Drop
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.background = '#f0f7ff';
                dropZone.style.borderColor = '#1e40af';
                
                const files = e.dataTransfer.files;
                procesarArchivos(files);
            });
        },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const tipoLogoId = parseInt(document.getElementById('modalTipoLogoPrenda').value);
            const nombrePrenda = document.getElementById('modalNombrePrendaTecnica').value.trim();
            
            if (!tipoLogoId) {
                Swal.showValidationMessage('Selecciona una técnica');
                return false;
            }
            if (!nombrePrenda) {
                Swal.showValidationMessage('Ingresa el nombre de la prenda');
                return false;
            }
            
            // Obtener nombre de técnica
            const tipoLogo = tiposLogosDisponibles.find(t => t.id === tipoLogoId);
            
            // Obtener ubicaciones desde los tags
            const ubicacionesTags = document.querySelectorAll('.ubicacion-tag-modal');
            const ubicaciones = Array.from(ubicacionesTags).map(tag => tag.dataset.ubicacion);
            
            if (ubicaciones.length === 0) {
                Swal.showValidationMessage('Agrega al menos una ubicación');
                return false;
            }
            
            // Obtener tallas y cantidades
            const tallaCantidad = {};
            document.querySelectorAll('.modal-cantidad-talla').forEach(input => {
                const talla = input.dataset.talla;
                const cantidad = parseInt(input.value) || 0;
                tallaCantidad[talla] = cantidad;
            });

            if (Object.keys(tallaCantidad).length === 0) {
                Swal.showValidationMessage('Agrega al menos una talla con cantidad');
                return false;
            }
            
            return {
                tipo_logo_id: tipoLogoId,
                tipo_logo_nombre: tipoLogo ? tipoLogo.nombre : 'N/A',
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('modalObservacionesPrendaTecnica').value.trim(),
                ubicaciones: ubicaciones,
                talla_cantidad: tallaCantidad,
                fotos: []
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (indexEditar !== null) {
                // Actualizar prenda existente
                logoPrendasTecnicas[indexEditar] = {
                    ...logoPrendasTecnicas[indexEditar],
                    tipo_logo_id: result.value.tipo_logo_id,
                    tipo_logo_nombre: result.value.tipo_logo_nombre,
                    nombre_prenda: result.value.nombre_prenda,
                    observaciones: result.value.observaciones,
                    ubicaciones: result.value.ubicaciones,
                    talla_cantidad: result.value.talla_cantidad,
                    modificada: true
                };
            } else {
                // Agregar nueva prenda
                logoPrendasTecnicas.push({
                    ...result.value,
                    id: null,
                    grupo_combinado: null,
                    existeEnBD: false,
                    fotos: []
                });
            }
            
            window.logoPrendasTecnicas = logoPrendasTecnicas;
            sincronizarPrendasTecnicas();
            renderizarLogoPrendasTecnicas();
            
            // Abrir automáticamente el formulario de edición de la prenda para agregar fotos
            setTimeout(() => {
                const indexNueva = indexEditar !== null ? indexEditar : logoPrendasTecnicas.length - 1;
                abrirModalEditarPrendaTecnica(indexNueva);
            }, 300);
        }
    });
}



// =========================================================
// 6. ELIMINAR PRENDA TÉCNICA
// =========================================================

function eliminarPrendaTecnicaLogo(index) {
    Swal.fire({
        title: '¿Eliminar esta prenda técnica?',
        text: `Se eliminará: ${logoPrendasTecnicas[index].tipo_logo_nombre} - ${logoPrendasTecnicas[index].nombre_prenda}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            logoPrendasTecnicas.splice(index, 1);
            renderizarLogoPrendasTecnicas();
            Swal.fire('Eliminada', 'La prenda técnica ha sido eliminada', 'success');
        }
    });
}

function editarPrendaTecnicaLogo(index) {
    toggleFormularioEdicionPrenda(index);
}

// =========================================================
// 7. VALIDACIÓN Y ENVÍO
// =========================================================

function validarLogoPrendasTecnicas() {
    if ((window.logoPrendasTecnicas || []).length === 0) {
        console.error('❌ No hay prendas técnicas agregadas');
        return false;
    }
    
    // Validar que cada prenda tenga datos mínimos
    for (let prenda of (window.logoPrendasTecnicas || [])) {
        if (!prenda.nombre_prenda) {
            console.error('❌ Prenda sin nombre');
            return false;
        }
        if (!prenda.ubicaciones || prenda.ubicaciones.length === 0) {
            console.error('❌ Prenda sin ubicaciones:', prenda.nombre_prenda);
            return false;
        }
        if (!prenda.talla_cantidad || Object.keys(prenda.talla_cantidad).length === 0) {
            console.error('❌ Prenda sin tallas:', prenda.nombre_prenda);
            return false;
        }
    }
    
    return true;
}

function obtenerDatosLogoPrendasParaEnvio() {
    return (window.logoPrendasTecnicas || []).map(prenda => ({
        tipo_logo_id: prenda.tipo_logo_id,
        nombre_prenda: prenda.nombre_prenda,
        observaciones: prenda.observaciones || '',
        ubicaciones: prenda.ubicaciones,
        talla_cantidad: prenda.talla_cantidad,
        // Si tiene ID (existe en BD), no enviar los datos para crear
        // Solo enviar las modificaciones
        modificaciones: prenda.modificada ? {
            nombre_prenda: prenda.nombre_prenda,
            observaciones: prenda.observaciones,
            ubicaciones: prenda.ubicaciones,
            talla_cantidad: prenda.talla_cantidad
        } : null
    }));
}

// =========================================================
// 8. INICIALIZAR
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    cargarTiposLogosDisponibles();
});
