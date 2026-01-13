/**
 * Módulo: Gestión de Ítems del Pedido
 * Maneja la lista de ítems, renderizado y actualización
 */

// Array global de ítems del pedido
window.itemsPedido = [];

/**
 * Actualizar vista de ítems
 */
window.actualizarVistaItems = function() {
    const listaItems = document.getElementById('lista-items-pedido');
    const mensajeSinItems = document.getElementById('mensaje-sin-items');

    console.log('🔄 Actualizando vista de ítems. Total:', window.itemsPedido.length);
    console.log('📦 Ítems actuales:', window.itemsPedido);

    if (window.itemsPedido.length === 0) {
        console.log('  ℹ️ No hay ítems, mostrando mensaje');
        mensajeSinItems.style.display = 'block';
        listaItems.style.display = 'none';
    } else {
        console.log('  ✅ Hay ítems, renderizando lista');
        mensajeSinItems.style.display = 'none';
        listaItems.style.display = 'flex';
        renderizarItems();
    }
};

/**
 * Renderizar lista de ítems - Nuevo diseño con prendas y procesos unificados
 */
function renderizarItems() {
    const listaItems = document.getElementById('lista-items-pedido');

    console.log('🎨 Renderizando ítems. Total:', window.itemsPedido.length);
    listaItems.innerHTML = '';

    // Agrupar ítems por prenda (sin procesos) y procesos
    const prendas = window.itemsPedido.filter(item => !item.es_proceso);
    const procesos = window.itemsPedido.filter(item => item.es_proceso);

    let numeroItem = 1;

    // Renderizar prendas con sus procesos en un solo contenedor
    prendas.forEach((prenda, prendaIndex) => {
        const procesosDeEstaPrenda = procesos.filter(p => 
            p.prenda?.nombre === prenda.prenda?.nombre && 
            p.origen === prenda.origen
        );

        const contenedorDiv = crearContenedorPrendaConProcesos(prenda, procesosDeEstaPrenda, numeroItem, prendaIndex);
        listaItems.appendChild(contenedorDiv);
        numeroItem += 1 + procesosDeEstaPrenda.length;
    });

    console.log('🎨 Renderizado completado. Elementos en listaItems:', listaItems.children.length);
}

/**
 * Crear contenedor unificado de prenda con sus procesos
 */
function crearContenedorPrendaConProcesos(prenda, procesosDeEstaPrenda, numero, prendaIndex) {
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'background: white; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 1.5rem; overflow: hidden;';

    const origen = prenda.origen === 'bodega' ? 'BODEGA' : 'CONFECCIÓN';
    const origenColor = prenda.origen === 'bodega' ? '#fef3c7' : '#dcfce7';
    const origenTextColor = prenda.origen === 'bodega' ? '#92400e' : '#166534';

    // Construir HTML de telas
    let telasHTML = '';
    if (prenda.prenda?.telas && Array.isArray(prenda.prenda.telas)) {
        telasHTML = prenda.prenda.telas.map(t => {
            let telaStr = t.tela || '';
            if (t.color) telaStr += ` (${t.color})`;
            if (t.referencia) telaStr += ` - Ref: ${t.referencia}`;
            return telaStr;
        }).join('<br>');
    }

    // Construir variaciones mejoradas
    let variacionesHTML = '';
    if (prenda.variaciones && Object.keys(prenda.variaciones).length > 0) {
        const vars = prenda.variaciones;
        const varsList = [];
        
        // Manga
        if (vars.manga) {
            let mangaStr = '';
            if (typeof vars.manga === 'object') {
                mangaStr = `Manga: ${vars.manga.tipo || 'No especificado'}`;
                if (vars.manga.observacion) {
                    mangaStr += ` (${vars.manga.observacion})`;
                }
            } else if (vars.manga) {
                mangaStr = `Manga: ${vars.manga}`;
            }
            if (mangaStr) varsList.push(mangaStr);
        }
        
        // Bolsillos
        if (vars.bolsillos) {
            let bolsillosStr = '';
            if (typeof vars.bolsillos === 'object') {
                bolsillosStr = `Bolsillos: ${vars.bolsillos.tipo || vars.bolsillos}`;
                if (vars.bolsillos.observacion) {
                    bolsillosStr += ` (${vars.bolsillos.observacion})`;
                }
            } else if (vars.bolsillos) {
                bolsillosStr = `Bolsillos: ${vars.bolsillos}`;
            }
            if (bolsillosStr) varsList.push(bolsillosStr);
        }
        
        // Broche/Botón
        if (vars.broche) {
            let brocheStr = '';
            if (typeof vars.broche === 'object') {
                brocheStr = `Broche: ${vars.broche.tipo || 'No especificado'}`;
                if (vars.broche.observacion) {
                    brocheStr += ` (${vars.broche.observacion})`;
                }
            } else if (vars.broche) {
                brocheStr = `Broche: ${vars.broche}`;
            }
            if (brocheStr) varsList.push(brocheStr);
        }
        
        if (varsList.length > 0) {
            variacionesHTML = `
                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem;">
                    <strong>📋 Variaciones:</strong><br>
                    ${varsList.map(v => `   • ${v}`).join('<br>')}
                </div>
            `;
        }
    }

    // Construir tallas y cantidades agrupadas por género
    let tallasHTML = '';
    if (prenda.tallas && Array.isArray(prenda.tallas)) {
        const tallasPorGenero = {};
        prenda.tallas.forEach(t => {
            const genero = t.genero || 'sin-genero';
            if (!tallasPorGenero[genero]) {
                tallasPorGenero[genero] = [];
            }
            tallasPorGenero[genero].push(`${t.talla}: ${t.cantidad}`);
        });
        
        const generoArray = [];
        Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
            if (genero !== 'sin-genero') {
                generoArray.push(`${genero.toUpperCase()} ${tallas.join(', ')}`);
            } else {
                generoArray.push(tallas.join(', '));
            }
        });
        tallasHTML = generoArray.join('; ');
    }

    // Header de la prenda
    let imagenHTML = '';
    if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
        const primeraImagen = prenda.imagenes[0];
        const cantidadImagenes = prenda.imagenes.length;
        imagenHTML = `
            <div style="margin-right: 1rem; position: relative;">
                <img src="${primeraImagen.data || primeraImagen}" style="width: 80px; height: 80px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;" onclick="mostrarGaleriaImagenItem(${prendaIndex})">
                ${cantidadImagenes > 1 ? `<span style="position: absolute; bottom: 5px; right: 5px; background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">+${cantidadImagenes - 1}</span>` : ''}
            </div>
        `;
    }

    const headerHTML = `
        <div style="padding: 1.25rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: start;">
            <div style="display: flex; gap: 1rem; flex: 1;">
                ${imagenHTML}
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 1.1rem; margin-bottom: 0.5rem;">
                        ${numero}. ${prenda.prenda?.nombre || 'Prenda'}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                        ${prenda.tipo === 'cotizacion' ? `
                            <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                                📋 ${prenda.numero}
                            </span>
                        ` : ''}
                        <span style="padding: 0.25rem 0.75rem; background: ${origenColor}; color: ${origenTextColor}; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                            ${origen}
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                            📦 ${prenda.prenda?.cantidad || 0} unidades
                        </span>
                    </div>
                    ${prenda.tipo === 'cotizacion' ? `
                        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                            <strong>Cliente:</strong> ${prenda.cliente}
                        </div>
                    ` : ''}
                    ${telasHTML ? `
                        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                            <strong>Telas:</strong><br>
                            ${telasHTML}
                        </div>
                    ` : ''}
                    ${variacionesHTML}
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem;">
                        <strong>Tallas y Cantidades:</strong> ${tallasHTML || 'No especificadas'}
                    </div>
                </div>
            </div>
            <button type="button" onclick="window.eliminarItem(${prendaIndex})" style="padding: 0.5rem 0.75rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left: 1rem; height: fit-content;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                ✕
            </button>
        </div>
    `;

    contenedor.innerHTML = headerHTML;

    // Agregar procesos dentro del contenedor
    if (procesosDeEstaPrenda.length > 0) {
        const procesosContainer = document.createElement('div');
        procesosContainer.style.cssText = 'background: #fafbfc;';

        procesosDeEstaPrenda.forEach((proceso, procesoIndex) => {
            const procesoDiv = document.createElement('div');
            procesoDiv.style.cssText = 'padding: 1rem; border-top: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;';
            procesoDiv.onmouseover = () => procesoDiv.style.background = '#f3f4f6';
            procesoDiv.onmouseout = () => procesoDiv.style.background = 'transparent';

            const idCollapse = `proceso-${numero}-${procesoIndex}`;
            const procesosNombre = proceso.procesos?.join(', ') || 'Proceso';
            let tallasProcesoHTML = '';
            if (proceso.tallas && Array.isArray(proceso.tallas)) {
                tallasProcesoHTML = proceso.tallas.map(t => `${t.talla}: ${t.cantidad}`).join(' | ');
            }

            procesoDiv.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;" onclick="const el = document.getElementById('${idCollapse}'); el.style.display = el.style.display === 'none' ? 'block' : 'none'; this.querySelector('span').textContent = el.style.display === 'none' ? '▼' : '▲';">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #9f1239; font-size: 0.95rem;">
                            ${procesosNombre}
                        </div>
                    </div>
                    <span style="font-size: 1rem; color: #6b7280; margin-left: 1rem;">▼</span>
                </div>
                <div id="${idCollapse}" style="display: none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                    <strong>Tallas y Cantidades:</strong> ${tallasProcesoHTML || 'No especificadas'}
                </div>
            `;

            procesosContainer.appendChild(procesoDiv);
        });

        contenedor.appendChild(procesosContainer);
    }

    return contenedor;
}

/**
 * Determinar categoría del ítem
 */
function determinarCategoria(item) {
    if (!item.procesos || item.procesos.length === 0) {
        return item.origen === 'bodega' ? 'COSTURA-BODEGA' : 'COSTURA-CONFECCIÓN';
    }

    if (item.procesos.length > 1) {
        return 'COMBINADO';
    }

    const proceso = item.procesos[0].toLowerCase();
    if (proceso.includes('bordado')) return 'BORDADO';
    if (proceso.includes('estampado') || proceso.includes('dtf') || proceso.includes('sublimado')) return 'ESTAMPADO';
    if (proceso.includes('reflectivo')) return 'REFLECTIVO';

    return 'OTRO';
}

/**
 * Obtener colores según categoría
 */
function obtenerColorCategoria(categoria) {
    const colores = {
        'COSTURA-BODEGA': { bg: '#fef3c7', text: '#92400e' },
        'COSTURA-CONFECCIÓN': { bg: '#dcfce7', text: '#166534' },
        'BORDADO': { bg: '#dbeafe', text: '#1e40af' },
        'ESTAMPADO': { bg: '#fce7f3', text: '#9f1239' },
        'REFLECTIVO': { bg: '#fef9c3', text: '#854d0e' },
        'COMBINADO': { bg: '#e9d5ff', text: '#6b21a8' },
        'OTRO': { bg: '#f3f4f6', text: '#374151' }
    };

    return colores[categoria] || colores['OTRO'];
}

/**
 * Eliminar ítem
 */
window.eliminarItem = function(index) {
    window.itemsPedido.splice(index, 1);
    window.actualizarVistaItems();
};

/**
 * Función global para obtener ítems del pedido
 */
window.obtenerItemsPedido = function() {
    return window.itemsPedido;
};

/**
 * Función global para verificar si hay ítems
 */
window.tieneItems = function() {
    return window.itemsPedido.length > 0;
};

/**
 * Mostrar galería de imágenes de un item
 */
window.mostrarGaleriaImagenItem = function(prendaIndex) {
    const prenda = window.itemsPedido[prendaIndex];
    if (!prenda || !prenda.imagenes || prenda.imagenes.length === 0) {
        console.warn('❌ No hay imágenes para mostrar');
        return;
    }

    let indiceActual = 0;
    let modalClosed = false;

    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
    modal.onclick = function(e) {
        if (e.target === modal && !modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };

    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';

    // Contenedor de imagen - Ocupa casi toda la pantalla
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';

    const img = document.createElement('img');
    img.src = prenda.imagenes[0].data || prenda.imagenes[0];
    img.style.cssText = 'width: 90%; height: 90%; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    imgContainer.appendChild(img);

    container.appendChild(imgContainer);

    // Barra de herramientas con controles
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';

    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + prenda.imagenes.length) % prenda.imagenes.length;
        img.src = prenda.imagenes[indiceActual].data || prenda.imagenes[indiceActual];
        contador.textContent = (indiceActual + 1) + ' de ' + prenda.imagenes.length;
    };
    toolbar.appendChild(btnAnterior);

    // Contador
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + prenda.imagenes.length;
    toolbar.appendChild(contador);

    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % prenda.imagenes.length;
        img.src = prenda.imagenes[indiceActual].data || prenda.imagenes[indiceActual];
        contador.textContent = (indiceActual + 1) + ' de ' + prenda.imagenes.length;
    };
    toolbar.appendChild(btnSiguiente);

    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    btnCerrar.onclick = () => {
        if (!modalClosed) {
            modalClosed = true;
            modal.remove();
        }
    };
    toolbar.appendChild(btnCerrar);

    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);

    // Soporte para navegación con teclas
    const manejarTeclas = function(e) {
        if (e.key === 'ArrowLeft') {
            btnAnterior.click();
        } else if (e.key === 'ArrowRight') {
            btnSiguiente.click();
        } else if (e.key === 'Escape') {
            if (!modalClosed) {
                modalClosed = true;
                modal.remove();
            }
            document.removeEventListener('keydown', manejarTeclas);
        }
    };
    document.addEventListener('keydown', manejarTeclas);

    modal.addEventListener('remove', function() {
        document.removeEventListener('keydown', manejarTeclas);
    });
};

console.log('✅ Módulo gestion-items-pedido.js cargado correctamente');
