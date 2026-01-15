/**
 * Renderizador de Tarjetas de Procesos
 * Muestra las tarjetas de procesos configurados dentro del modal de prenda
 */

const iconosProcesos = {
    reflectivo: '<span class="material-symbols-rounded" style="color: #f59e0b;">wb_twilight</span>',
    bordado: '<span class="material-symbols-rounded" style="color: #8b5cf6;">auto_awesome</span>',
    estampado: '<span class="material-symbols-rounded" style="color: #ec4899;">format_paint</span>',
    dtf: '<span class="material-symbols-rounded" style="color: #06b6d4;">print</span>',
    sublimado: '<span class="material-symbols-rounded" style="color: #3b82f6;">water_drop</span>'
};

const nombresProcesos = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Renderizar todas las tarjetas de procesos en el modal de prenda
 */
window.renderizarTarjetasProcesos = function() {
    console.log('🎨 [TARJETAS-PROCESOS] Renderizando tarjetas de procesos');
    
    const container = document.getElementById('contenedor-tarjetas-procesos');
    if (!container) {
        console.warn('⚠️ [TARJETAS-PROCESOS] Contenedor no encontrado');
        return;
    }
    
    // Obtener procesos configurados
    const procesos = window.procesosSeleccionados || {};
    const procesosConDatos = Object.keys(procesos).filter(tipo => procesos[tipo]?.datos);
    
    console.log('📊 [TARJETAS-PROCESOS] Procesos con datos:', procesosConDatos.length);
    
    if (procesosConDatos.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.875rem;">
                <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;">add_circle</span>
                No hay procesos configurados. Marca un checkbox arriba para agregar procesos.
            </div>
        `;
        container.style.display = 'block';
        return;
    }
    
    // Renderizar cada proceso como tarjeta
    let html = '';
    procesosConDatos.forEach(tipo => {
        const proceso = procesos[tipo];
        html += generarTarjetaProceso(tipo, proceso.datos);
    });
    
    container.innerHTML = html;
    container.style.display = 'block';
    
    console.log('✅ [TARJETAS-PROCESOS] Tarjetas renderizadas');
};

/**
 * Generar HTML de una tarjeta de proceso
 */
function generarTarjetaProceso(tipo, datos) {
    const icono = iconosProcesos[tipo] || 'settings';
    const nombre = nombresProcesos[tipo] || tipo.toUpperCase();
    
    // ✅ CORREGIDO: Calcular totalTallas como suma de cantidades en objetos, no length
    const damaObj = datos.tallas?.dama || {};
    const caballeroObj = datos.tallas?.caballero || {};
    const totalTallas = Object.keys(damaObj).length + Object.keys(caballeroObj).length;
    
    const ubicacionesTexto = datos.ubicaciones?.length > 0 
        ? datos.ubicaciones.join(', ') 
        : 'Sin ubicaciones';
    
    return `
        <div class="tarjeta-proceso" data-tipo="${tipo}" style="
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        ">
            <!-- Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <strong style="color: #111827; font-size: 1rem;">${nombre}</strong>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="editarProceso('${tipo}')" 
                        style="background: #f3f4f6; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Editar proceso">
                        <i class="fas fa-edit" style="font-size: 1rem; color: #6b7280;"></i>
                    </button>
                    <button type="button" onclick="eliminarTarjetaProceso('${tipo}')" 
                        style="background: #fee2e2; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                        title="Eliminar proceso">
                        <i class="fas fa-trash-alt" style="font-size: 1rem; color: #dc2626;"></i>
                    </button>
                </div>
            </div>
            
            <!-- Contenido -->
            <div style="display: grid; gap: 0.75rem;">
                ${(datos.imagenes?.length > 0) ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">IMÁGENES</div>
                        <div style="position: relative; display: inline-block;" onclick="abrirGaleriaImagenesProceso('${tipo}')">
                            <img src="${datos.imagenes[0]}" 
                                style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 2px solid #e5e7eb;" 
                                alt="Imagen del proceso">
                            ${datos.imagenes.length > 1 ? `
                                <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0, 0, 0, 0.75); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                    +${datos.imagenes.length - 1}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">UBICACIONES</div>
                    <div style="color: #374151; font-size: 0.875rem;">${ubicacionesTexto}</div>
                </div>
                
                ${totalTallas > 0 ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">TALLAS (${totalTallas})</div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
                            ${datos.tallas.dama && Object.keys(datos.tallas.dama).length > 0 ? `
                                <div>
                                    <strong style="color: #be185d; margin-right: 0.5rem;"><i class="fas fa-female"></i> Dama:</strong>
                                    ${Object.entries(datos.tallas.dama).map(([talla, cantidad]) => {
                                        return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ${talla}
                                            <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                        </span>`;
                                    }).join('')}
                                </div>
                            ` : ''}
                            ${datos.tallas.caballero && Object.keys(datos.tallas.caballero).length > 0 ? `
                                <div>
                                    <strong style="color: #1d4ed8; margin-right: 0.5rem;"><i class="fas fa-male"></i> Caballero:</strong>
                                    ${Object.entries(datos.tallas.caballero).map(([talla, cantidad]) => {
                                        return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ${talla}
                                            <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
                                        </span>`;
                                    }).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                ` : ''}
                
                ${datos.observaciones ? `
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">OBSERVACIONES</div>
                        <div style="color: #374151; font-size: 0.875rem; font-style: italic;">${datos.observaciones}</div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Eliminar una tarjeta de proceso
 */
window.eliminarTarjetaProceso = function(tipo) {
    console.log(`🗑️ [TARJETAS-PROCESOS] Eliminando proceso: ${tipo}`);
    
    // Desmarcar checkbox
    const checkbox = document.getElementById(`checkbox-${tipo}`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Eliminar de procesosSeleccionados
    if (window.procesosSeleccionados && window.procesosSeleccionados[tipo]) {
        delete window.procesosSeleccionados[tipo];
    }
    
    // Re-renderizar
    window.renderizarTarjetasProcesos();
    
    // Actualizar resumen
    if (window.actualizarResumenProcesos) {
        window.actualizarResumenProcesos();
    }
    
    console.log(`✅ [TARJETAS-PROCESOS] Proceso ${tipo} eliminado`);
};

/**
 * Editar un proceso existente
 */
window.editarProceso = function(tipo) {
    console.log(`✏️ [TARJETAS-PROCESOS] Editando proceso: ${tipo}`);
    
    // Abrir modal del proceso
    if (window.abrirModalProcesoGenerico) {
        window.abrirModalProcesoGenerico(tipo);
        
        // Cargar datos existentes en el modal
        const proceso = window.procesosSeleccionados[tipo];
        if (proceso?.datos) {
            cargarDatosProcesoEnModal(tipo, proceso.datos);
        }
    }
};

/**
 * Cargar datos de un proceso en el modal para editar
 */
function cargarDatosProcesoEnModal(tipo, datos) {
    console.log(`📝 [TARJETAS-PROCESOS] Cargando datos en modal:`, datos);
    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];
    }
    
    // Cargar imágenes (soporte para formato antiguo 'imagen' y nuevo 'imagenes')
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                preview.innerHTML = `
                    <img src="${img}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                    <button type="button" onclick="eliminarImagenProceso(${indice}); event.stopPropagation();" 
                        style="position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                        ×
                    </button>
                `;
            }
            
            if (window.imagenesProcesoActual) {
                window.imagenesProcesoActual[idx] = img;
            }
        }
    });
    
    // Cargar ubicaciones
    if (datos.ubicaciones && window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas.length = 0;
        window.ubicacionesProcesoSeleccionadas.push(...datos.ubicaciones);
        if (window.renderizarListaUbicaciones) {
            window.renderizarListaUbicaciones();
        }
    }
    
    // Cargar observaciones
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
    
    // Cargar tallas
    if (datos.tallas && window.tallasSeleccionadasProceso) {
        window.tallasSeleccionadasProceso.dama = datos.tallas.dama || [];
        window.tallasSeleccionadasProceso.caballero = datos.tallas.caballero || [];
        if (window.actualizarResumenTallasProceso) {
            window.actualizarResumenTallasProceso();
        }
    }
}

/**
 * Abrir galería de imágenes del proceso (con navegación)
 */
window.abrirGaleriaImagenesProceso = function(tipoProceso) {
    const proceso = window.procesosSeleccionados[tipoProceso];
    if (!proceso?.datos?.imagenes || proceso.datos.imagenes.length === 0) {
        return;
    }
    
    const imagenes = proceso.datos.imagenes;
    let indiceActual = 0;
    
    const galeria = document.createElement('div');
    galeria.id = 'galeria-proceso-modal';
    galeria.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 10003; display: flex; flex-direction: column; align-items: center; justify-content: center;';
    
    galeria.innerHTML = `
        <!-- Barra superior -->
        <div style="position: absolute; top: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 10004;">
            <div style="color: white; font-size: 1rem; font-weight: 600;">
                <span class="material-symbols-rounded" style="vertical-align: middle;">photo_library</span>
                Galería de Imágenes - ${tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1)}
            </div>
            <div style="color: white; font-size: 0.9rem;">
                <span id="galeria-contador">1</span> / ${imagenes.length}
            </div>
            <button onclick="cerrarGaleriaImagenesProceso()" style="background: #dc2626; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                ×
            </button>
        </div>
        
        <!-- Imagen principal -->
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 4rem 2rem 2rem 2rem; width: 100%;">
            <img id="galeria-imagen-actual" src="${imagenes[0]}" style="max-width: 85vw; max-height: 80vh; border-radius: 8px; object-fit: contain;">
        </div>
        
        <!-- Navegación -->
        ${imagenes.length > 1 ? `
            <button id="galeria-btn-anterior" onclick="navegarGaleriaImagenesProceso(-1)" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                ‹
            </button>
            <button id="galeria-btn-siguiente" onclick="navegarGaleriaImagenesProceso(1)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                ›
            </button>
            
            <!-- Miniaturas -->
            <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; padding: 0.75rem; background: rgba(0,0,0,0.6); border-radius: 8px; backdrop-filter: blur(10px);">
                ${imagenes.map((img, idx) => `
                    <img src="${img}" 
                         onclick="irAImagenProceso(${idx})" 
                         data-indice="${idx}"
                         class="miniatura-galeria-proceso"
                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${idx === 0 ? '#0ea5e9' : 'transparent'}; opacity: ${idx === 0 ? '1' : '0.6'};">
                `).join('')}
            </div>
        ` : ''}
    `;
    
    // Almacenar estado en el elemento
    galeria.dataset.indiceActual = '0';
    galeria.dataset.totalImagenes = imagenes.length;
    
    // Guardar referencia de imágenes
    window.imagenesGaleriaProceso = imagenes;
    
    document.body.appendChild(galeria);
};

/**
 * Navegar en la galería de imágenes del proceso
 */
window.navegarGaleriaImagenesProceso = function(direccion) {
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria || !window.imagenesGaleriaProceso) return;
    
    const imagenes = window.imagenesGaleriaProceso;
    let indiceActual = parseInt(galeria.dataset.indiceActual);
    
    indiceActual += direccion;
    
    // Ciclo: volver al inicio/final
    if (indiceActual < 0) indiceActual = imagenes.length - 1;
    if (indiceActual >= imagenes.length) indiceActual = 0;
    
    galeria.dataset.indiceActual = indiceActual;
    
    // Actualizar imagen
    const imgElement = document.getElementById('galeria-imagen-actual');
    if (imgElement) {
        imgElement.src = imagenes[indiceActual];
    }
    
    // Actualizar contador
    const contador = document.getElementById('galeria-contador');
    if (contador) {
        contador.textContent = indiceActual + 1;
    }
    
    // Actualizar miniaturas
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((mini, idx) => {
        if (idx === indiceActual) {
            mini.style.border = '2px solid #0ea5e9';
            mini.style.opacity = '1';
        } else {
            mini.style.border = '2px solid transparent';
            mini.style.opacity = '0.6';
        }
    });
};

/**
 * Ir a una imagen específica en la galería
 */
window.irAImagenProceso = function(indice) {
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria || !window.imagenesGaleriaProceso) return;
    
    const imagenes = window.imagenesGaleriaProceso;
    
    galeria.dataset.indiceActual = indice;
    
    // Actualizar imagen
    const imgElement = document.getElementById('galeria-imagen-actual');
    if (imgElement) {
        imgElement.src = imagenes[indice];
    }
    
    // Actualizar contador
    const contador = document.getElementById('galeria-contador');
    if (contador) {
        contador.textContent = indice + 1;
    }
    
    // Actualizar miniaturas
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((mini, idx) => {
        if (idx === indice) {
            mini.style.border = '2px solid #0ea5e9';
            mini.style.opacity = '1';
        } else {
            mini.style.border = '2px solid transparent';
            mini.style.opacity = '0.6';
        }
    });
};

/**
 * Cerrar galería de imágenes del proceso
 */
window.cerrarGaleriaImagenesProceso = function() {
    const galeria = document.getElementById('galeria-proceso-modal');
    if (galeria) {
        galeria.remove();
    }
    window.imagenesGaleriaProceso = null;
};

/**
 * Mostrar imagen del proceso en grande (fallback para compatibilidad)
 */
window.mostrarImagenProcesoGrande = function(imagenUrl) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; align-items: center; justify-content: center; z-index: 10003; padding: 2rem;';
    
    modal.innerHTML = `
        <img src="${imagenUrl}" style="max-width: 90vw; max-height: 90vh; border-radius: 8px; object-fit: contain;">
        <button onclick="this.parentElement.remove()" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>
        </button>
    `;
    
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
    
    document.body.appendChild(modal);
};

/**
 * Abrir galería de imágenes del proceso
 */
window.abrirGaleriaImagenesProceso = function(tipoProceso) {
    const proceso = window.procesosSeleccionados[tipoProceso];
    if (!proceso?.datos?.imagenes || proceso.datos.imagenes.length === 0) return;
    
    const imagenes = proceso.datos.imagenes;
    const galeria = document.createElement('div');
    galeria.id = 'galeria-proceso-modal';
    galeria.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 10003; display: flex; flex-direction: column; align-items: center; justify-content: center;';
    
    galeria.innerHTML = `
        <div style="position: absolute; top: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 10004;">
            <div style="color: white; font-size: 1rem; font-weight: 600;">
                <i class="fas fa-images" style="margin-right: 0.5rem;"></i>
                Galería - ${tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1)}
            </div>
            <div style="color: white; font-size: 0.9rem;"><span id="galeria-contador">1</span> / ${imagenes.length}</div>
            <button onclick="cerrarGaleriaImagenesProceso()" style="background: #dc2626; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 4rem 2rem 2rem 2rem; width: 100%;">
            <img id="galeria-imagen-actual" src="${imagenes[0]}" style="max-width: 85vw; max-height: 80vh; border-radius: 8px; object-fit: contain;">
        </div>
        ${imagenes.length > 1 ? `
            <button onclick="navegarGaleriaImagenesProceso(-1)" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">‹</button>
            <button onclick="navegarGaleriaImagenesProceso(1)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">›</button>
            <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; padding: 0.75rem; background: rgba(0,0,0,0.6); border-radius: 8px;">
                ${imagenes.map((img, idx) => `<img src="${img}" onclick="irAImagenProceso(${idx})" class="miniatura-galeria-proceso" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${idx === 0 ? '#0ea5e9' : 'transparent'}; opacity: ${idx === 0 ? '1' : '0.6'};">`).join('')}
            </div>
        ` : ''}
    `;
    
    galeria.dataset.indiceActual = '0';
    window.imagenesGaleriaProceso = imagenes;
    document.body.appendChild(galeria);
};

window.navegarGaleriaImagenesProceso = function(direccion) {
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria || !window.imagenesGaleriaProceso) return;
    
    let indice = parseInt(galeria.dataset.indiceActual) + direccion;
    if (indice < 0) indice = window.imagenesGaleriaProceso.length - 1;
    if (indice >= window.imagenesGaleriaProceso.length) indice = 0;
    
    galeria.dataset.indiceActual = indice;
    document.getElementById('galeria-imagen-actual').src = window.imagenesGaleriaProceso[indice];
    document.getElementById('galeria-contador').textContent = indice + 1;
    
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
        m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
        m.style.opacity = i === indice ? '1' : '0.6';
    });
};

window.irAImagenProceso = function(indice) {
    const galeria = document.getElementById('galeria-proceso-modal');
    if (!galeria) return;
    
    galeria.dataset.indiceActual = indice;
    document.getElementById('galeria-imagen-actual').src = window.imagenesGaleriaProceso[indice];
    document.getElementById('galeria-contador').textContent = indice + 1;
    
    document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
        m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
        m.style.opacity = i === indice ? '1' : '0.6';
    });
};

window.cerrarGaleriaImagenesProceso = function() {
    document.getElementById('galeria-proceso-modal')?.remove();
    window.imagenesGaleriaProceso = null;
};

console.log('✅ [TARJETAS-PROCESOS] Módulo renderizador-tarjetas-procesos.js cargado');

