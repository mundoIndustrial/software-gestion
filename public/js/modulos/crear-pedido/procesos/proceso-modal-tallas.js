/**
 * proceso-modal-tallas.js
 * Extraccion de tallas/edicion/resumen del modal de procesos.
 */
(function initProcesoModalTallas(global) {
    const procesoModalModules = global.procesoModalModules || (global.procesoModalModules = { ui: {}, imagenes: {}, persistencia: {}, tallas: {} });
    const procesoModalState = global.procesoModalState || (global.procesoModalState = { procesoActual: null, modoActual: 'crear', cambiosProceso: null });
    const procesoModalDebug = global.procesoModalDebug || function() {};
    const tallasEstandar = global.procesoModalTallasEstandar || (global.procesoModalTallasEstandar = {
        dama: ["XS", "S", "M", "L", "XL", "XXL", "XXXL", "XXXXL"],
        caballero: ["XS", "S", "M", "L", "XL", "XXL", "XXXL", "XXXXL"]
    });

globalThis.aplicarProcesoParaTodasTallas = function() {

    
    // Obtener las tallas registradas de la prenda actual (con cantidades)
    const tallasPrendaConCantidades = obtenerTallasDeLaPrenda();
    
    // Extraer solo los nombres para UI (como arrays)
    const tallasPrendaArrays = {
        dama: Object.keys(tallasPrendaConCantidades.dama || {}),
        caballero: Object.keys(tallasPrendaConCantidades.caballero || {}),
        sobremedida: tallasPrendaConCantidades.sobremedida || null
    };
    
    // Si hay sobremedida, permitir aplicar
    const hayTallasNormales = tallasPrendaArrays.dama.length > 0 || tallasPrendaArrays.caballero.length > 0;
    const haySobremedida = tallasPrendaArrays.sobremedida !== null;
    
    if (!hayTallasNormales && !haySobremedida) {
        // No hay tallas ni sobremedida - mostrar modal de advertencia
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    // Para UI, usamos arrays (nombres de tallas) o sobremedida
    globalThis.tallasSeleccionadasProceso = {
        dama: tallasPrendaArrays.dama,
        caballero: tallasPrendaArrays.caballero,
        sobremedida: tallasPrendaArrays.sobremedida
    };
    
    //  IMPORTANTE: Copiar TODAS las cantidades de la prenda al proceso
    // Esto hace que "Aplicar para todas" asigne las cantidades completas de la prenda
    globalThis.tallasCantidadesProceso = {
        dama: { ...tallasPrendaConCantidades.dama },
        caballero: { ...tallasPrendaConCantidades.caballero },
        sobremedida: tallasPrendaConCantidades.sobremedida || {}
    };
    
    procesoModalDebug(' [aplicarProcesoParaTodasTallas] Copiadas todas las tallas de la prenda al proceso:', {
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tallasSeleccionadas: globalThis.tallasSeleccionadasProceso
    });

    actualizarResumenTallasProceso();
};

// Obtener tallas registradas en la prenda del modal
function obtenerTallasDeLaPrenda() {
    procesoModalDebug('[obtenerTallasDeLaPrenda]  INICIANDO - buscando FUENTE DE VERDAD');

    const normalizarGenero = (generoRaw) => {
        const g = String(generoRaw || '').trim().toLowerCase();
        if (!g) return null;
        if (g === 'dama' || g.startsWith('dam')) return 'dama';
        if (g === 'caballero' || g.startsWith('cab')) return 'caballero';
        if (g === 'unisex' || g.startsWith('uni')) return 'sobremedida';
        return null;
    };

    const crearEstructuraTallas = () => ({ dama: {}, caballero: {}, sobremedida: null });

    const extraerTallasDesdeTabla = () => {
        const tablaBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (!tablaBody) return { tallas: null, filas: 0, colores: 0 };

        const tallas = crearEstructuraTallas();
        const filas = Array.from(tablaBody.querySelectorAll('tr[data-tipo="wizard"]'));
        let contadorFilas = 0;
        let contadorColores = 0;

        filas.forEach(fila => {
            const generoRaw = fila.querySelector('[data-field="genero"]')?.textContent.trim();
            const tallaText = fila.querySelector('[data-field="talla"]')?.textContent.trim();
            const cantidadText = fila.querySelector('[data-field="cantidad"]')?.textContent.trim();
            const colorCell = fila.querySelector('[data-field="color"]');

            if (!generoRaw || !tallaText || !cantidadText) return;

            const genero = normalizarGenero(generoRaw);
            if (!genero) return;

            const cantidad = Number(cantidadText, 10) || 0;
            if (cantidad <= 0) return;

            const colors = [];
            if (colorCell) {
                const colorDiv = colorCell.querySelector('div');
                if (colorDiv) {
                    Array.from(colorDiv.querySelectorAll('span')).forEach(span => {
                        let value = String(span.textContent || '').trim().replaceAll(/\s+/g, ' ');
                        if (!value) return;
                        let base = value.split('(')[0].trim();
                        if (!base || base === value) {
                            base = value.replaceAll(/\s*\(\d+\)\s*/g, '').trim();
                        }
                        if (base && !colors.includes(base)) colors.push(base);
                    });
                }
            }

            const asignar = (key) => {
                if (genero === 'dama') {
                    tallas.dama[key] = (tallas.dama[key] || 0) + cantidad;
                } else if (genero === 'caballero') {
                    tallas.caballero[key] = (tallas.caballero[key] || 0) + cantidad;
                } else {
                    if (!tallas.sobremedida) tallas.sobremedida = {};
                    tallas.sobremedida[key] = (tallas.sobremedida[key] || 0) + cantidad;
                }
            };

            if (colors.length > 0) {
                colors.forEach(color => asignar(`${tallaText}__${color}`));
                contadorColores++;
            } else {
                asignar(tallaText);
            }

            contadorFilas++;
        });

        return { tallas, filas: contadorFilas, colores: contadorColores };
    };

    const extraerTallasDesdeStateManager = () => {
        const datosColores = globalThis.ColoresPorTalla?.datos;

        let asignaciones = null;

        if (datosColores && Object.keys(datosColores).length > 0) {
            asignaciones = datosColores;
        } else if (globalThis.StateManager && typeof globalThis.StateManager.getAsignaciones === 'function') {
            asignaciones = globalThis.StateManager.getAsignaciones();
        }

        if (!asignaciones || typeof asignaciones !== 'object' || Object.keys(asignaciones).length === 0) return null;

        const tallas = crearEstructuraTallas();
        Object.values(asignaciones).forEach(asignacion => {
            const genero = normalizarGenero(asignacion?.genero);
            if (!genero) return;

            const talla = String(asignacion?.talla || '').trim().toUpperCase();
            if (!talla) return;

            const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
            colores.forEach(c => {
                const color = String(c?.nombre || '').trim().toUpperCase();
                const cantidad = Number(c?.cantidad, 10) || 0;
                if (!color || cantidad <= 0) return;
                const key = `${talla}__${color}`;
                tallas[genero][key] = (tallas[genero][key] || 0) + cantidad;
            });
        });

        return Object.keys(tallas.dama).length || Object.keys(tallas.caballero).length || (tallas.sobremedida && Object.keys(tallas.sobremedida).length)
            ? tallas
            : null;
    };

    const extraerTallasDesdeTablaSinColor = (tablaBody) => {
        if (!tablaBody) return null;

        const filas = Array.from(tablaBody.querySelectorAll('tr[data-tipo="wizard"]'));
        if (!filas.length) return null;

        const tallas = crearEstructuraTallas();
        filas.forEach(fila => {
            const generoRaw = fila.querySelector('[data-field="genero"]')?.textContent.trim();
            const tallaText = fila.querySelector('[data-field="talla"]')?.textContent.trim();
            const cantidadText = fila.querySelector('[data-field="cantidad"]')?.textContent.trim();
            if (!generoRaw || !tallaText || !cantidadText) return;

            const genero = normalizarGenero(generoRaw);
            if (!genero) return;

            const cantidad = Number(cantidadText, 10) || 0;
            if (cantidad <= 0) return;

            if (genero === 'dama') {
                tallas.dama[tallaText] = (tallas.dama[tallaText] || 0) + cantidad;
            } else if (genero === 'caballero') {
                tallas.caballero[tallaText] = (tallas.caballero[tallaText] || 0) + cantidad;
            } else {
                if (!tallas.sobremedida) tallas.sobremedida = {};
                tallas.sobremedida[tallaText] = (tallas.sobremedida[tallaText] || 0) + cantidad;
            }
        });

        return Object.keys(tallas.dama).length || Object.keys(tallas.caballero).length || (tallas.sobremedida && Object.keys(tallas.sobremedida).length)
            ? tallas
            : null;
    };

    const extraerTallasRelacionales = () => {
        const tallasRelacionales = globalThis.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        const hayValores = ['DAMA', 'CABALLERO', 'SOBREMEDIDA', 'UNISEX'].some(k =>
            tallasRelacionales[k] && typeof tallasRelacionales[k] === 'object' && Object.keys(tallasRelacionales[k]).length > 0
        );

        if (!hayValores) return null;

        const tallas = crearEstructuraTallas();
        if (tallasRelacionales.DAMA && Object.keys(tallasRelacionales.DAMA).length > 0) tallas.dama = { ...tallasRelacionales.DAMA };
        if (tallasRelacionales.CABALLERO && Object.keys(tallasRelacionales.CABALLERO).length > 0) tallas.caballero = { ...tallasRelacionales.CABALLERO };
        if (tallasRelacionales.SOBREMEDIDA && Object.keys(tallasRelacionales.SOBREMEDIDA).length > 0) tallas.sobremedida = { ...tallasRelacionales.SOBREMEDIDA };
        if (tallasRelacionales.UNISEX && Object.keys(tallasRelacionales.UNISEX).length > 0) {
            tallas.sobremedida = { ...tallas.sobremedida, ...tallasRelacionales.UNISEX };
        }

        return tallas;
    };

    const tablaPrimaria = extraerTallasDesdeTabla();
    if (tablaPrimaria.colores > 0) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 1 USADA: Tabla HTML (CON COLORES - Fuente Definitiva)');
        return tablaPrimaria.tallas;
    }

    const state = extraerTallasDesdeStateManager();
    if (state) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 2 USADA: StateManager/ColoresPorTalla (tabla incompleta)');
        return state;
    }

    if (tablaPrimaria.filas > 0) {
        const tablaSinColor = extraerTallasDesdeTablaSinColor(document.getElementById('tabla-resumen-asignaciones-cuerpo'));
        if (tablaSinColor) {
            procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 3 USADA: Tabla HTML (SIN COLORES - fallback)');
            return tablaSinColor;
        }
    }

    const relacionales = extraerTallasRelacionales();
    if (relacionales) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 4 USADA: tallasRelacionales (legacy)');
        return relacionales;
    }

    if (globalThis.cantidadSoloSeleccionada) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 5 USADA: cantidadSoloSeleccionada');
        return { dama: {}, caballero: {}, sobremedida: { 'UNISEX': globalThis.cantidadSoloSeleccionada } };
    }

    procesoModalDebug('[obtenerTallasDeLaPrenda]  NINGUNA FUENTE disponible - retornando vacio');
    return crearEstructuraTallas();
}

// Mostrar modal de advertencia cuando no hay tallas seleccionadas
function mostrarModalAdvertenciaTallas() {
    const html = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h3 style="color: #dc2626; margin-bottom: 1rem;">Sin Tallas Seleccionadas</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                Debes seleccionar al menos una talla y su cantidad en la prenda 
                antes de aplicar el proceso.
            </p>
            <p style="color: #6b7280; margin-bottom: 2rem; font-size: 0.875rem;">
                Agrega tallas en la seccion "TALLAS Y CANTIDADES" del formulario.
            </p>
            <button type="button" class="btn btn-primary" onclick="cerrarModalAdvertencia()" style="padding: 0.75rem 2rem;">
                <span class="material-symbols-rounded">check</span>Entendido
            </button>
        </div>
    `;
    
    // Crear modal temporal
    const modal = document.createElement('div');
    modal.id = 'modal-advertencia-tallas';
    modal.className = 'modal-overlay';
    modal.style.zIndex = '100002';
    modal.innerHTML = `
        <div class="modal-container modal-sm">
            <div class="modal-header" style="background: #fef2f2; border-bottom: 2px solid #fecaca;">
                <h3 class="modal-title" style="color: #dc2626;">
                    <span class="material-symbols-rounded">warning</span>Advertencia
                </h3>
                <button class="modal-close-btn" onclick="cerrarModalAdvertencia()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                ${html}
            </div>
        </div>
    `;
    
    procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Creando modal');
    
    // Agregar seguro a getComputedStyle
    const swal2Container = document.querySelector('.swal2-container');
    if (swal2Container) {
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] z-index swal2:', globalThis.getComputedStyle(swal2Container).zIndex);
    } else {
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Sin swal2-container activo');
    }
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    

    setTimeout(() => {
        modal.style.setProperty('z-index', '9999999999', 'important');
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Modal visible');
    }, 10);
}

// Cerrar modal de advertencia
globalThis.cerrarModalAdvertencia = function() {
    const modal = document.getElementById('modal-advertencia-tallas');
    if (modal) {
        modal.remove();
    }
};

// Abrir editor de tallas especificas
globalThis.abrirEditorTallasEspecificas = function() {

    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {

        return;
    }
    
    // Obtener tallas registradas en la prenda (retorna objetos {talla: cantidad} o {talla__color: cantidad})
    const tallasPrenda = obtenerTallasDeLaPrenda();
    
    // Validar que haya tallas seleccionadas - son OBJETOS, no arrays
    const tallasDamaArray = Object.keys(tallasPrenda.dama || {});
    const tallasCaballeroArray = Object.keys(tallasPrenda.caballero || {});
    const haySobremedida = tallasPrenda.sobremedida !== null;
    
    if (tallasDamaArray.length === 0 && tallasCaballeroArray.length === 0 && !haySobremedida) {
        mostrarModalAdvertenciaTallas();
        return;
    }
    


    
    // Renderizar tallas DAMA (solo las seleccionadas en la prenda)
    const containerDama = document.getElementById('tallas-dama-container');
    if (containerDama) {
        containerDama.innerHTML = '';
        containerDama.style.display = 'grid';
        containerDama.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerDama.style.gap = '0.75rem';
        
        if (tallasDamaArray.length === 0) {
            containerDama.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas DAMA seleccionadas en la prenda</p>';
        } else {
            tallasDamaArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.dama.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.dama[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.dama?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuanto esta asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'dama', procesoModalState.procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="dama" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="dama"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #be185d; border-radius: 8px; text-align: center; font-weight: 800; background: #fce7f3; color: #be185d;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar informacion sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerDama.appendChild(label);
            });
        }
    }
    
    // Renderizar tallas CABALLERO (solo las seleccionadas en la prenda)
    const containerCaballero = document.getElementById('tallas-caballero-container');
    if (containerCaballero) {
        containerCaballero.innerHTML = '';
        containerCaballero.style.display = 'grid';
        containerCaballero.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerCaballero.style.gap = '0.75rem';
        
        if (tallasCaballeroArray.length === 0) {
            containerCaballero.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas CABALLERO seleccionadas en la prenda</p>';
        } else {
            tallasCaballeroArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.caballero.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.caballero[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.caballero?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuanto esta asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'caballero', procesoModalState.procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="caballero" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="caballero"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #1d4ed8; border-radius: 8px; text-align: center; font-weight: 800; background: #dbeafe; color: #1d4ed8;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar informacion sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerCaballero.appendChild(label);
            });
        }
    }
    
    // Mostrar modal editor
    modalEditor.style.display = 'flex';
    
    //  DIAGNOSTICO Z-INDEX
    procesoModalDebug(' [EDITOR-TALLAS] Abriendo modal de edicion de tallas...');
    procesoModalDebug(' [EDITOR-TALLAS] Z-index INICIAL (style.zIndex):', modalEditor.style.zIndex || 'NO DEFINIDO');
    procesoModalDebug(' [EDITOR-TALLAS] Z-index COMPUTADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Obtener z-index del modal principal
    const modalPrincipal = document.getElementById('modal-proceso-generico');
    if (modalPrincipal) {
        procesoModalDebug(' [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (style):', modalPrincipal.style.zIndex || 'NO DEFINIDO');
        procesoModalDebug(' [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (computed):', globalThis.getComputedStyle(modalPrincipal).zIndex);
    }
    
    // Forzar z-index aun mas alto
    const zIndexEditorActual = parseInt(globalThis.getComputedStyle(modalEditor).zIndex) || 100002;
    const zIndexPrincipalActual = parseInt(globalThis.getComputedStyle(modalPrincipal).zIndex) || 999999999;
    const nuevoZIndexEditor = zIndexPrincipalActual + 1;
    
    procesoModalDebug(' [EDITOR-TALLAS] Z-index EDITOR actual:', zIndexEditorActual);
    procesoModalDebug(' [EDITOR-TALLAS] Z-index PRINCIPAL actual:', zIndexPrincipalActual);
    procesoModalDebug(' [EDITOR-TALLAS] ASIGNANDO nuevo Z-index al editor:', nuevoZIndexEditor);
    
    // Aplicar z-index forzado
    modalEditor.style.zIndex = nuevoZIndexEditor.toString();
    procesoModalDebug(' [EDITOR-TALLAS] Z-index FORZADO a:', modalEditor.style.zIndex);
    procesoModalDebug(' [EDITOR-TALLAS] Z-index VERIFICADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Verificar contexto de apilamiento
    procesoModalDebug(' [EDITOR-TALLAS] CONTEXTO DE APILAMIENTO:');
    procesoModalDebug('   - Modal Principal display:', globalThis.getComputedStyle(modalPrincipal).display);
    procesoModalDebug('   - Modal Principal position:', globalThis.getComputedStyle(modalPrincipal).position);
    procesoModalDebug('   - Editor display:', globalThis.getComputedStyle(modalEditor).display);
    procesoModalDebug('   - Editor position:', globalThis.getComputedStyle(modalEditor).position);
    
    // Listar todos los elementos con z-index alto en la pagina
    procesoModalDebug(' [EDITOR-TALLAS] ELEMENTOS CON Z-INDEX ALTO:');
    document.querySelectorAll('[style*="z-index"], [class*="modal"], [class*="overlay"]').forEach((el, idx) => {
        const zIdx = globalThis.getComputedStyle(el).zIndex;
        if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > 100) {
            procesoModalDebug(`   ${idx}. ${el.id || el.className || el.tagName} - Z-index: ${zIdx}, Display: ${globalThis.getComputedStyle(el).display}`);
        }
    });

};

// Calcular cantidad ya asignada en OTROS procesos para una talla
function calcularCantidadAsignadaOtrosProcesos(talla, generoKey, procesoActualExcluir) {
    let totalAsignado = 0;
    const procesosDetalle = [];
    
    // Recorrer TODOS los procesos
    if (globalThis.procesosSeleccionados) {
        Object.entries(globalThis.procesosSeleccionados).forEach(([tipoProceso, datosProc]) => {
            // Excluir el proceso actual
            if (tipoProceso === procesoActualExcluir) {
                return;
            }
            
            if (datosProc?.datos?.tallas) {
                const generoTallas = datosProc.datos.tallas[generoKey] || {};
                const cantidadEnEsteProceso = generoTallas[talla] || 0;
                
                if (cantidadEnEsteProceso > 0) {
                    totalAsignado += cantidadEnEsteProceso;
                    procesosDetalle.push({
                        nombre: tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1),
                        cantidad: cantidadEnEsteProceso
                    });
                }
            }
        });
    }
    
    return { totalAsignado, procesosDetalle };
}

// Actualizar cantidad de talla en el modal de proceso
globalThis.actualizarCantidadTallaProceso = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    
    // Obtener la cantidad maxima disponible en la prenda
    const tallasPrenda = obtenerTallasDeLaPrenda();
    const generoKey = genero.toLowerCase();
    const cantidadDisponibleEnPrenda = tallasPrenda[generoKey]?.[talla] || 0;
    
    //  LOGICA CORREGIDA: Las mismas prendas pueden recibir MULTIPLES procesos
    // NO hay limite entre procesos. Solo validamos contra la cantidad total de la prenda.
    // Ejemplo: 20 camisas talla S pueden tener:
    //   - 10 con Bordado
    //   - 15 con Estampado (son las MISMAS u OTRAS camisas, lo importante es que NO superen 20 total)
    
    procesoModalDebug(` [actualizarCantidadTallaProceso] Validacion para ${talla}/${genero}:`, {
        cantidadIntentada: cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        procesoActual: procesoModalState.procesoActual,
        nota: 'Sin limite entre procesos - mismas prendas pueden recibir multiples procesos'
    });
    
    // VALIDACION: Solo permitir que NO supere la cantidad total de la prenda
    if (cantidad > cantidadDisponibleEnPrenda) {
        console.warn(` [actualizarCantidadTallaProceso] Cantidad ${cantidad} supera disponible en PRENDA ${cantidadDisponibleEnPrenda}`);
        
        // Mostrar error INLINE en rojo debajo del input
        input.style.borderColor = '#dc2626';
        input.style.backgroundColor = '#fee2e2';
        
        // Buscar el label padre que contiene 
        const label = input.closest('label');
        procesoModalDebug(' [ERROR-CSS] Label encontrado:', !!label);
        
        // Buscar o crear wrapper para mantener el grid ordenado
        let wrapper = label?.closest('.talla-error-wrapper');
        procesoModalDebug(' [ERROR-CSS] Wrapper existente:', !!wrapper);
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'talla-error-wrapper';
            wrapper.style.cssText = 'display: contents;';
            
            if (label?.parentNode) {
                // Reemplazar label con wrapper en el DOM
                label.parentNode.insertBefore(wrapper, label);
                // Meter label dentro del wrapper
                wrapper.appendChild(label);
                procesoModalDebug(' [ERROR-CSS] Wrapper CREADO y label MOVIDO dentro');
            }
        }
        
        // Buscar o crear elemento de error dentro del wrapper
        let errorDiv = wrapper?.querySelector('.error-cantidad');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-cantidad';
            errorDiv.style.cssText = 'color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; font-weight: 600; padding: 0 0.5rem; width: 100%; display: block;';
            if (wrapper) {
                wrapper.appendChild(errorDiv);
                procesoModalDebug(' [ERROR-CSS] ErrorDiv CREADO dentro del wrapper');
            }
        }
        
        procesoModalDebug(' [ERROR-CSS] ErrorDiv despues de crear:');
        procesoModalDebug('   - Existe:', !!errorDiv);
        procesoModalDebug('   - Display (style):', errorDiv.style.display);
        procesoModalDebug('   - Display (computed):', globalThis.getComputedStyle(errorDiv).display);
        
        errorDiv.textContent = ` Maximo: ${cantidadDisponibleEnPrenda} unidades`;
        errorDiv.style.display = 'block';
        
        procesoModalDebug(' [ERROR-CSS] Mensaje asignado');
        
        // Limpiar el campo (dejar en 0)
        input.value = 0;
        return;
        
      
    } else {
        // Limpiar error si la cantidad es valida
        input.style.borderColor = '#be185d';
        input.style.backgroundColor = '#fce7f3';
        let errorDiv = input.parentNode.querySelector('.error-cantidad');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    // Actualizar SOLO en la estructura de TALLAS DEL PROCESO
    // NO tocar globalThis.tallasRelacionales (que son las tallas de la PRENDA)
    const generoMinuscula = genero.toLowerCase();
    if (!globalThis.tallasCantidadesProceso[generoMinuscula]) {
        globalThis.tallasCantidadesProceso[generoMinuscula] = {};
    }
    
    if (cantidad > 0) {
        globalThis.tallasCantidadesProceso[generoMinuscula][talla] = cantidad;
    } else {
        // Si la cantidad es 0, eliminar la talla de las cantidades del proceso
        delete globalThis.tallasCantidadesProceso[generoMinuscula][talla];
    }
    
    // Limpiar estilos de error si la validacion paso
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    
    procesoModalDebug(' [actualizarCantidadTallaProceso] Actualizado en tallasCantidadesProceso:', {
        genero,
        talla,
        cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        estructuraActual: globalThis.tallasCantidadesProceso
    });
};

// Cerrar editor de tallas
globalThis.cerrarEditorTallas = function() {
    const modal = document.getElementById('modal-editor-tallas');
    if (modal) {
        procesoModalDebug(' [EDITOR-TALLAS] Cerrando modal...');
        procesoModalDebug(' [EDITOR-TALLAS] Z-index ANTES de cerrar:', globalThis.getComputedStyle(modal).zIndex);
        modal.style.display = 'none';
        procesoModalDebug(' [EDITOR-TALLAS] Modal cerrado. Display:', globalThis.getComputedStyle(modal).display);
    }

};

// Guardar tallas seleccionadas desde el editor
globalThis.guardarTallasSeleccionadas = function() {

    procesoModalDebug(' [guardarTallasSeleccionadas] INICIANDO guardado de tallas...');
    procesoModalDebug(' [guardarTallasSeleccionadas] Proceso actual:', procesoModalState.procesoActual);
    procesoModalDebug(' [guardarTallasSeleccionadas] Modo:', procesoModalState.modoActual);
    
    // Recopilar tallas DAMA
    const checksDama = document.querySelectorAll('input[data-genero="dama"]:checked');
    globalThis.tallasSeleccionadasProceso.dama = Array.from(checksDama).map(cb => cb.value);
    procesoModalDebug(' [guardarTallasSeleccionadas] Tallas DAMA seleccionadas:', globalThis.tallasSeleccionadasProceso.dama);
    
    // Recopilar tallas CABALLERO
    const checksCaballero = document.querySelectorAll('input[data-genero="caballero"]:checked');
    globalThis.tallasSeleccionadasProceso.caballero = Array.from(checksCaballero).map(cb => cb.value);
    procesoModalDebug(' [guardarTallasSeleccionadas] Tallas CABALLERO seleccionadas:', globalThis.tallasSeleccionadasProceso.caballero);
    procesoModalDebug(' [guardarTallasSeleccionadas] Cantidades por talla (proceso):', globalThis.tallasCantidadesProceso);
    
    // IMPORTANTE: Actualizar el objeto del proceso con las tallas y cantidades
    // para que no pierda los datos cuando se cierre el modal
    if (procesoModalState.procesoActual && globalThis.procesosSeleccionados[procesoModalState.procesoActual]?.datos) {
        globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos.tallas = {
            dama: globalThis.tallasCantidadesProceso.dama || {},
            caballero: globalThis.tallasCantidadesProceso.caballero || {},
            sobremedida: globalThis.tallasCantidadesProceso.sobremedida || {}
        };
        
        procesoModalDebug(` [guardarTallasSeleccionadas] Tallas guardadas en proceso "${procesoModalState.procesoActual}":`, {
            tallas: globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos.tallas,
            tallasCantidadesProceso: globalThis.tallasCantidadesProceso
        });
    } else {
        console.warn(` [guardarTallasSeleccionadas] NO SE PUDO GUARDAR: procesoActual="${procesoModalState.procesoActual}", procesosSeleccionados exists=${!!globalThis.procesosSeleccionados}`);
    }

    procesoModalDebug(' [guardarTallasSeleccionadas] ESTADO ANTES DE CERRAR MODAL:');
    procesoModalDebug('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    procesoModalDebug('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    // Cerrar editor y actualizar resumen
    cerrarEditorTallas();
    
    procesoModalDebug(' [guardarTallasSeleccionadas] ESTADO DESPUES DE CERRAR MODAL:');
    procesoModalDebug('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    procesoModalDebug('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    actualizarResumenTallasProceso();
    procesoModalDebug(' [guardarTallasSeleccionadas] GUARDADO COMPLETADO');
};
procesoModalModules.tallas.guardarTallasSeleccionadas = globalThis.guardarTallasSeleccionadas;

// Actualizar resumen de tallas
globalThis.actualizarResumenTallasProceso = function() {
    procesoModalDebug('[actualizarResumenTallasProceso]  Iniciando renderizacion de resumen...');
    
    const resumen = document.getElementById('proceso-tallas-resumen');
    procesoModalDebug('[actualizarResumenTallasProceso]  Elemento resumen encontrado?:', !!resumen);
    
    if (!resumen) {
        console.warn('[actualizarResumenTallasProceso]  NO SE ENCONTRO elemento #proceso-tallas-resumen');
        return;
    }
    
    procesoModalDebug('[actualizarResumenTallasProceso]  globalThis.tallasSeleccionadasProceso:', globalThis.tallasSeleccionadasProceso);
    procesoModalDebug('[actualizarResumenTallasProceso]  globalThis.tallasCantidadesProceso:', globalThis.tallasCantidadesProceso);
    
    const totalTallas = globalThis.tallasSeleccionadasProceso.dama.length + globalThis.tallasSeleccionadasProceso.caballero.length;
    const haySobremedida = globalThis.tallasSeleccionadasProceso.sobremedida && Object.keys(globalThis.tallasSeleccionadasProceso.sobremedida).length > 0;
    procesoModalDebug('[actualizarResumenTallasProceso] Total de tallas seleccionadas:', totalTallas, ' | Hay sobremedida:', haySobremedida);
    
    if (totalTallas === 0 && !haySobremedida) {
        procesoModalDebug('[actualizarResumenTallasProceso]  No hay tallas ni sobremedida seleccionadas, mostrando placeholder');
        resumen.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    
    // Obtener cantidades desde tallasCantidadesProceso (ESTRUCTURA DEL PROCESO, NO DE LA PRENDA)
    const tallasProceso = globalThis.tallasCantidadesProceso || { dama: {}, caballero: {} };
    procesoModalDebug('[actualizarResumenTallasProceso]  tallasProceso para renderizar:', tallasProceso);

    const formatearTallaKey = (tallaKey) => {
        const parts = String(tallaKey).split('__');
        const talla = (parts[0] || tallaKey);
        const color = (parts[1] || null);
        return color ? `${talla} - ${color}` : talla;
    };
    
    if (globalThis.tallasSeleccionadasProceso.dama.length > 0) {
        procesoModalDebug('[actualizarResumenTallasProceso] Renderizando DAMA:', globalThis.tallasSeleccionadasProceso.dama);
        const tallasDamaHTML = globalThis.tallasSeleccionadasProceso.dama.map(t => {
            const cantidad = tallasProceso.dama?.[t] || 0;
            procesoModalDebug(`[actualizarResumenTallasProceso]  DAMA ${t}: cantidad=${cantidad}`);
            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #be185d; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-female"></i> DAMA (${globalThis.tallasSeleccionadasProceso.dama.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasDamaHTML}
                </div>
            </div>
        `;
    }
    
    if (globalThis.tallasSeleccionadasProceso.caballero.length > 0) {
        procesoModalDebug('[actualizarResumenTallasProceso] Renderizando CABALLERO:', globalThis.tallasSeleccionadasProceso.caballero);
        const tallasCaballeroHTML = globalThis.tallasSeleccionadasProceso.caballero.map(t => {
            const cantidad = tallasProceso.caballero?.[t] || 0;
            procesoModalDebug(`[actualizarResumenTallasProceso]  CABALLERO ${t}: cantidad=${cantidad}`);
            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #1d4ed8; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-male"></i> CABALLERO (${globalThis.tallasSeleccionadasProceso.caballero.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasCaballeroHTML}
                </div>
            </div>
        `;
    }
    
    // AGREGAR SOBREMEDIDA AL RESUMEN
    if (haySobremedida && globalThis.tallasCantidadesProceso.sobremedida) {
        procesoModalDebug('[actualizarResumenTallasProceso]  Renderizando SOBREMEDIDA:', globalThis.tallasCantidadesProceso.sobremedida);
        
        const sobremedidaHTML = Object.entries(globalThis.tallasCantidadesProceso.sobremedida).map(([genero, cantidad]) => {
            procesoModalDebug(`[actualizarResumenTallasProceso]  SOBREMEDIDA ${genero}: ${cantidad}`);
            const colorMap = {
                'DAMA': { bg: '#fce7f3', text: '#be185d' },
                'CABALLERO': { bg: '#dbeafe', text: '#1d4ed8' },
                'UNISEX': { bg: '#f3e8ff', text: '#7c3aed' }
            };
            const colores = colorMap[genero] || { bg: '#e5e7eb', text: '#374151' };
            
            return `<span style="background: ${colores.bg}; color: ${colores.text}; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${genero}
                <span style="background: ${colores.text}; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #0066cc; margin-bottom: 0.5rem; display: block;">
                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">straighten</span> SOBREMEDIDA
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${sobremedidaHTML}
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML generado (length):', html.length);
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML preview:', html.substring(0, 200) + '...');
    resumen.innerHTML = html;
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML inyectado en DOM');
    procesoModalDebug('[actualizarResumenTallasProceso]  innerHTML actual:', resumen.innerHTML.substring(0, 200));
};
procesoModalModules.tallas.actualizarResumen = globalThis.actualizarResumenTallasProceso;

})(globalThis);

