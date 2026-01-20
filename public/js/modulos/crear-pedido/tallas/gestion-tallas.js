/**
 * GESTIÓN DE TALLAS Y CANTIDADES
 * Sistema centralizado para manejar tallas, géneros y cantidades de prendas
 * 
 * FLUJO CORRECTO:
 * 1. Se abre modal del género
 * 2. Si el otro género ya tiene tipo de talla, se SINCRONIZA automáticamente
 * 3. Si no, se muestran botones para elegir TIPO DE TALLA (LETRA o NÚMERO)
 * 4. Una vez seleccionado el tipo, se muestran las tallas disponibles
 * 5. Se seleccionan las tallas deseadas
 * 6. Se confirma y se crea la tarjeta con el tipo mostrado
 */

// ========== ESTADO GLOBAL DE TALLAS ==========
window.tallasSeleccionadas = window.tallasSeleccionadas || {
    dama: { tallas: [], tipo: null },
    caballero: { tallas: [], tipo: null }
};

window.cantidadesTallas = window.cantidadesTallas || {};

// Variables para rastrear el estado del modal
window.generoActualModal = null;
window.tipoTallaSeleccionado = null;

// ========== FUNCIONES DE GESTIÓN DE TALLAS ==========

/**
 * Guardar cantidad de talla cuando cambia el input
 */
window.guardarCantidadTalla = function(input) {
    const cantidad = parseInt(input.value) || 0;
    const key = input.dataset.key; // Formato: "genero-talla"
    
    if (cantidad > 0) {
        window.cantidadesTallas[key] = cantidad;
        // BACKUP PERMANENTE: Guardar en variable que persista para preview
        if (!window._TALLAS_BACKUP_PERMANENTE) {
            window._TALLAS_BACKUP_PERMANENTE = {};
        }
        window._TALLAS_BACKUP_PERMANENTE[key] = cantidad;
        console.log('💾 [TALLA] Guardada cantidad:', key, '=', cantidad);
        console.log('📌 [BACKUP] Tallas persistentes:', window._TALLAS_BACKUP_PERMANENTE);
    } else {
        delete window.cantidadesTallas[key];
        if (window._TALLAS_BACKUP_PERMANENTE) {
            delete window._TALLAS_BACKUP_PERMANENTE[key];
        }
        console.log('🗑️ [TALLA] Eliminada cantidad:', key);
    }
};

/**
 * Obtener cantidad guardada de una talla
 */
window.obtenerCantidadTalla = function(genero, talla) {
    const key = `${genero}-${talla}`;
    return window.cantidadesTallas[key] || 0;
};

/**
 * Mostrar las tallas disponibles según el tipo seleccionado
 */
window.mostrarTallasDisponibles = function(tipo) {
    console.log(' [TALLAS] Mostrando tallas tipo:', tipo);
    
    const container = document.getElementById('container-tallas-disponibles');
    if (!container) return;
    
    container.innerHTML = '';
    
    let tallasAMostrar = [];
    
    if (tipo === 'letra') {
        tallasAMostrar = TALLAS_LETRAS;
    } else if (tipo === 'numero') {
        const genero = window.generoActualModal;
        tallasAMostrar = genero === 'dama' ? TALLAS_NUMEROS_DAMA : TALLAS_NUMEROS_CABALLERO;
    }
    
    // Crear grid de tallas
    const grid = document.createElement('div');
    grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;';
    
    tallasAMostrar.forEach(talla => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.talla = talla;
        
        const isSelected = window.tallasSeleccionadas[window.generoActualModal].tallas.includes(talla);
        
        btn.style.cssText = `
            padding: 0.75rem;
            border: 2px solid ${isSelected ? '#0066cc' : '#d1d5db'};
            background: ${isSelected ? '#0066cc' : 'white'};
            color: ${isSelected ? 'white' : '#1f2937'};
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        `;
        
        btn.textContent = talla;
        btn.onclick = () => {
            const isCurrentlySelected = window.tallasSeleccionadas[window.generoActualModal].tallas.includes(talla);
            
            if (isCurrentlySelected) {
                // Deseleccionar
                window.tallasSeleccionadas[window.generoActualModal].tallas = 
                    window.tallasSeleccionadas[window.generoActualModal].tallas.filter(t => t !== talla);
                btn.style.borderColor = '#d1d5db';
                btn.style.background = 'white';
                btn.style.color = '#1f2937';
                console.log(' [TALLA] Deseleccionada:', talla);
            } else {
                // Seleccionar
                window.tallasSeleccionadas[window.generoActualModal].tallas.push(talla);
                btn.style.borderColor = '#0066cc';
                btn.style.background = '#0066cc';
                btn.style.color = 'white';
                console.log(' [TALLA] Seleccionada:', talla);
            }
        };
        
        grid.appendChild(btn);
    });
    
    container.appendChild(grid);
};

/**
 * Seleccionar tipo de talla (LETRA o NÚMERO)
 */
window.seleccionarTipoTalla = function(tipo) {
    console.log('🎯 [TIPO TALLA] Seleccionado:', tipo);
    
    window.tipoTallaSeleccionado = tipo;
    
    // Verificar si el tipo está cambiando
    const tipoAnterior = window.tallasSeleccionadas[window.generoActualModal].tipo;
    const estaCambiandoTipo = tipoAnterior && tipoAnterior !== tipo;
    
    // Guardar el tipo en el estado
    window.tallasSeleccionadas[window.generoActualModal].tipo = tipo;
    
    // Actualizar botones
    const btnLetra = document.getElementById('btn-tipo-letra');
    const btnNumero = document.getElementById('btn-tipo-numero');
    
    if (btnLetra && btnNumero) {
        if (tipo === 'letra') {
            btnLetra.style.background = '#0066cc';
            btnLetra.style.borderColor = '#0066cc';
            btnLetra.style.color = 'white';
            btnNumero.style.background = 'white';
            btnNumero.style.borderColor = '#d1d5db';
            btnNumero.style.color = '#1f2937';
            console.log('📝 [TIPO TALLA] Modo LETRA activado');
        } else {
            btnNumero.style.background = '#0066cc';
            btnNumero.style.borderColor = '#0066cc';
            btnNumero.style.color = 'white';
            btnLetra.style.background = 'white';
            btnLetra.style.borderColor = '#d1d5db';
            btnLetra.style.color = '#1f2937';
            console.log('🔢 [TIPO TALLA] Modo NÚMERO activado');
        }
    }
    
    // SOLO limpiar tallas si el tipo está CAMBIANDO (no si es el mismo tipo en edición)
    if (estaCambiandoTipo) {
        console.log('⚠️ [TIPO TALLA] Tipo cambió de', tipoAnterior, 'a', tipo, '- Limpiando tallas');
        window.tallasSeleccionadas[window.generoActualModal].tallas = [];
    } else if (!tipoAnterior) {
        console.log(' [TIPO TALLA] Primera selección de tipo, inicializando tallas vacías');
    } else {
        console.log(' [TIPO TALLA] Mismo tipo, preservando tallas seleccionadas:', window.tallasSeleccionadas[window.generoActualModal].tallas);
    }
    
    // Mostrar las tallas disponibles del tipo seleccionado
    mostrarTallasDisponibles(tipo);
};

/**
 * Ocultar selectores de tipo y mostrar contenedor de tallas
 */
window.ocultarSelectorTipo = function() {
    const selectorDiv = document.getElementById('selector-tipo-talla');
    const tallasDiv = document.getElementById('container-tallas-disponibles');
    
    if (selectorDiv) {
        selectorDiv.style.display = 'none';
    }
    if (tallasDiv) {
        tallasDiv.style.display = 'block';
    }
};

/**
 * Mostrar selectores de tipo
 */
window.mostrarSelectorTipo = function() {
    const selectorDiv = document.getElementById('selector-tipo-talla');
    const tallasDiv = document.getElementById('container-tallas-disponibles');
    
    if (selectorDiv) {
        selectorDiv.style.display = 'block';
    }
    if (tallasDiv) {
        tallasDiv.style.display = 'none';
    }
};

/**
 * Abrir modal para seleccionar tallas de un género
 * SINCRONIZACIÓN: Si el otro género ya tiene tipo, se usa automáticamente
 */
window.abrirModalSeleccionarTallas = function(genero) {
    console.log('🎯 [MODAL TALLAS] Abriendo para:', genero);
    console.log('📊 [MODAL TALLAS] Estado actual:', JSON.stringify(window.tallasSeleccionadas));
    
    window.generoActualModal = genero;
    
    // Obtener el tipo de talla del género actual (si ya tiene seleccionadas)
    const tipoDelGeneroActual = window.tallasSeleccionadas[genero].tipo;
    
    // Obtener el otro género para verificar si ya tiene tipo de talla (sincronización)
    const otroGenero = genero === 'dama' ? 'caballero' : 'dama';
    const tipoDelOtroGenero = window.tallasSeleccionadas[otroGenero].tipo;
    
    console.log('🔍 [MODAL TALLAS] Sincronización - Género actual tipo:', tipoDelGeneroActual, '| Otro género:', otroGenero, 'tipo:', tipoDelOtroGenero);
    
    const modal = document.createElement('div');
    modal.id = `modal-tallas-${genero}`;
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100001;';
    
    const container = document.createElement('div');
    container.style.cssText = 'background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between;';
    
    const headerContent = document.createElement('div');
    headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    const icon = genero === 'dama' ? 'woman' : 'man';
    headerContent.innerHTML = `<span class="material-symbols-rounded" style="font-size: 1.5rem;">${icon}</span><h2 style="margin: 0; font-size: 1.25rem;">Seleccionar Tallas ${genero.toUpperCase()}</h2>`;
    header.appendChild(headerContent);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
    btnCerrar.onclick = () => cerrarModalTallas(genero);
    header.appendChild(btnCerrar);
    
    container.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;';
    
    // ========== SELECTOR DE TIPO DE TALLA ==========
    const selectorTipo = document.createElement('div');
    selectorTipo.id = 'selector-tipo-talla';
    selectorTipo.style.cssText = 'display: flex; flex-direction: column; gap: 1rem;';
    
    // SI EL GÉNERO ACTUAL YA TIENE TIPO, USARLO DIRECTAMENTE
    if (tipoDelGeneroActual) {
        console.log('🔒 [EDICIÓN] Usando tipo existente:', tipoDelGeneroActual, 'para', genero);
        
        const msgEditando = document.createElement('div');
        msgEditando.style.cssText = 'background: #dbeafe; border: 1px solid #93c5fd; border-radius: 6px; padding: 1rem; text-align: center; font-size: 0.9rem; color: #1e40af;';
        msgEditando.innerHTML = `<strong> Editando:</strong><br>Tallas ya seleccionadas como <strong>${tipoDelGeneroActual === 'letra' ? '📝 LETRA' : '🔢 NÚMERO'}</strong>`;
        selectorTipo.appendChild(msgEditando);
        
        // Seleccionar automáticamente el tipo
        setTimeout(() => {
            seleccionarTipoTalla(tipoDelGeneroActual);
            ocultarSelectorTipo();
        }, 100);
    }
    // SI EXISTE TIPO EN EL OTRO GÉNERO, SINCRONIZAR
    else if (tipoDelOtroGenero) {
        console.log('🔒 [SINCRONIZACIÓN] Usando tipo:', tipoDelOtroGenero);
        
        const msgSincro = document.createElement('div');
        msgSincro.style.cssText = 'background: #dbeafe; border: 1px solid #93c5fd; border-radius: 6px; padding: 1rem; text-align: center; font-size: 0.9rem; color: #1e40af;';
        msgSincro.innerHTML = `<strong> Tipo sincronizado:</strong><br>Se usa el tipo <strong>${tipoDelOtroGenero === 'letra' ? '📝 LETRA' : '🔢 NÚMERO'}</strong> que ya seleccionaste en ${otroGenero}`;
        selectorTipo.appendChild(msgSincro);
        
        // Seleccionar automáticamente el tipo
        setTimeout(() => {
            seleccionarTipoTalla(tipoDelOtroGenero);
            ocultarSelectorTipo();
        }, 100);
    } else {
        // MOSTRAR OPCIONES DE TIPO (LETRA o NÚMERO)
        const titleTipo = document.createElement('h3');
        titleTipo.textContent = '¿Qué tipo de tallas deseas?';
        titleTipo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.05rem; font-weight: 600;';
        selectorTipo.appendChild(titleTipo);
        
        const btnGroupTipo = document.createElement('div');
        btnGroupTipo.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;';
        
        // Botón LETRA
        const btnLetra = document.createElement('button');
        btnLetra.id = 'btn-tipo-letra';
        btnLetra.type = 'button';
        btnLetra.style.cssText = `
            padding: 1.25rem;
            border: 2px solid #d1d5db;
            background: white;
            color: #1f2937;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            hover: {
                borderColor: '#0066cc',
                background: '#f0f9ff'
            }
        `;
        btnLetra.innerHTML = '<span class="material-symbols-rounded" style="font-size: 2rem;">text_fields</span><div>LETRA</div><div style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">S, M, L, XL, XXL</div>';
        btnLetra.onclick = () => {
            seleccionarTipoTalla('letra');
            ocultarSelectorTipo();
        };
        btnLetra.onmouseover = () => {
            btnLetra.style.borderColor = '#0066cc';
            btnLetra.style.background = '#f0f9ff';
        };
        btnLetra.onmouseout = () => {
            if (window.tipoTallaSeleccionado !== 'letra') {
                btnLetra.style.borderColor = '#d1d5db';
                btnLetra.style.background = 'white';
            }
        };
        btnGroupTipo.appendChild(btnLetra);
        
        // Botón NÚMERO
        const btnNumero = document.createElement('button');
        btnNumero.id = 'btn-tipo-numero';
        btnNumero.type = 'button';
        btnNumero.style.cssText = `
            padding: 1.25rem;
            border: 2px solid #d1d5db;
            background: white;
            color: #1f2937;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        `;
        btnNumero.innerHTML = '<span class="material-symbols-rounded" style="font-size: 2rem;">tag</span><div>NÚMERO</div><div style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">34, 36, 38, 40...</div>';
        btnNumero.onclick = () => {
            seleccionarTipoTalla('numero');
            ocultarSelectorTipo();
        };
        btnNumero.onmouseover = () => {
            btnNumero.style.borderColor = '#0066cc';
            btnNumero.style.background = '#f0f9ff';
        };
        btnNumero.onmouseout = () => {
            if (window.tipoTallaSeleccionado !== 'numero') {
                btnNumero.style.borderColor = '#d1d5db';
                btnNumero.style.background = 'white';
            }
        };
        btnGroupTipo.appendChild(btnNumero);
        
        selectorTipo.appendChild(btnGroupTipo);
    }
    
    content.appendChild(selectorTipo);
    
    // ========== CONTENEDOR DE TALLAS DISPONIBLES ==========
    const tallaContainer = document.createElement('div');
    tallaContainer.id = 'container-tallas-disponibles';
    tallaContainer.style.cssText = (tipoDelGeneroActual || tipoDelOtroGenero) ? 'display: block;' : 'display: none;';
    
    content.appendChild(tallaContainer);
    container.appendChild(content);
    
    // Footer
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.type = 'button';
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = () => cerrarModalTallas(genero);
    footer.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.type = 'button';
    btnConfirmar.textContent = 'Confirmar';
    btnConfirmar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#0052a3';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#0066cc';
    btnConfirmar.onclick = () => {
        if (window.tallasSeleccionadas[genero].tallas.length === 0) {
            alert('⚠️ Debes seleccionar al menos una talla');
            return;
        }
        cerrarModalTallas(genero);
        crearTarjetaGenero(genero);
        actualizarTotalPrendas();
    };
    footer.appendChild(btnConfirmar);
    
    container.appendChild(footer);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Cerrar modal de tallas
 */
window.cerrarModalTallas = function(genero) {
    const modal = document.getElementById(`modal-tallas-${genero}`);
    if (modal) {
        modal.remove();
    }
    window.generoActualModal = null;
    window.tipoTallaSeleccionado = null;
};

/**
 * Crear tarjeta de género con tallas y cantidades
 */
window.crearTarjetaGenero = function(genero) {
    console.log(' [TARJETA] Creando tarjeta para:', genero);
    console.log('📊 [TARJETA] Tallas seleccionadas:', JSON.stringify(window.tallasSeleccionadas[genero]));
    
    if (window.tallasSeleccionadas[genero].tallas.length === 0) {
        console.warn('⚠️ [TARJETA] No hay tallas seleccionadas para', genero);
        return;
    }
    
    // Marcar botón de género como seleccionado
    const btnGenero = document.getElementById(`btn-genero-${genero}`);
    const checkMark = document.getElementById(`check-${genero}`);
    
    if (btnGenero) {
        btnGenero.dataset.selected = 'true';
        btnGenero.style.borderColor = '#0066cc';
        btnGenero.style.background = '#f0f9ff';
    }
    
    if (checkMark) {
        checkMark.style.display = 'block';
    }
    
    // Obtener contenedor
    const container = document.getElementById('tarjetas-generos-container');
    if (!container) {
        console.error(' [TARJETA] No se encontró contenedor de tarjetas');
        return;
    }
    
    // Eliminar tarjeta anterior si existe
    const tarjetaAnterior = container.querySelector(`[data-genero="${genero}"]`);
    if (tarjetaAnterior) {
        tarjetaAnterior.remove();
    }
    
    // Crear tarjeta
    const tarjeta = document.createElement('div');
    tarjeta.dataset.genero = genero;
    tarjeta.style.cssText = `
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    `;
    
    // Header de tarjeta
    const header = document.createElement('div');
    header.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; justify-content: space-between;';
    
    const headerLeft = document.createElement('div');
    headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
    const icon = genero === 'dama' ? 'woman' : 'man';
    const tipoText = window.tallasSeleccionadas[genero].tipo === 'letra' ? 'Tallas Letra' : 'Tallas Números';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">${icon}</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">${genero.toUpperCase()}</h4>
            <p style="margin: 0; font-size: 0.75rem; color: #6b7280;">${tipoText}</p>
        </div>
    `;
    header.appendChild(headerLeft);
    
    const btnGroupAcciones = document.createElement('div');
    btnGroupAcciones.style.cssText = 'display: flex; align-items: center; gap: 0.25rem;';
    
    const btnEditar = document.createElement('button');
    btnEditar.type = 'button';
    btnEditar.title = 'Editar tallas';
    btnEditar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEditar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>';
    btnEditar.onmouseover = () => {
        btnEditar.style.color = '#0066cc';
        btnEditar.style.background = '#f3f4f6';
    };
    btnEditar.onmouseout = () => {
        btnEditar.style.color = '#6b7280';
        btnEditar.style.background = 'transparent';
    };
    btnEditar.onclick = () => abrirModalSeleccionarTallas(genero);
    btnGroupAcciones.appendChild(btnEditar);
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.title = 'Eliminar tallas';
    btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>';
    btnEliminar.onmouseover = () => {
        btnEliminar.style.color = '#ef4444';
        btnEliminar.style.background = '#fee2e2';
    };
    btnEliminar.onmouseout = () => {
        btnEliminar.style.color = '#6b7280';
        btnEliminar.style.background = 'transparent';
    };
    btnEliminar.onclick = () => {
        console.log('🗑️ [ELIMINAR] Eliminando género:', genero);
        
        // Limpiar tallas del género
        window.tallasSeleccionadas[genero] = { tallas: [], tipo: null };
        console.log('📊 [ELIMINAR] Tallas limpiadas para:', genero);
        
        // Limpiar cantidades de este género
        Object.keys(window.cantidadesTallas).forEach(key => {
            if (key.startsWith(genero + '-')) {
                delete window.cantidadesTallas[key];
            }
        });
        console.log('💾 [ELIMINAR] Cantidades limpiadas para:', genero);
        
        // Remover tarjeta del DOM
        tarjeta.remove();
        
        // Desmarcar botón de género
        const btn = document.getElementById(`btn-genero-${genero}`);
        const check = document.getElementById(`check-${genero}`);
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
            btn.style.color = '#1f2937';
        }
        
        if (check) {
            check.style.display = 'none';
        }
        
        // Actualizar total
        actualizarTotalPrendas();
        console.log(' [ELIMINAR] Género eliminado correctamente');
    };
    btnGroupAcciones.appendChild(btnEliminar);
    
    header.appendChild(btnGroupAcciones);
    
    tarjeta.appendChild(header);
    
    // Grid de cantidades
    const gridCantidades = document.createElement('div');
    gridCantidades.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;';
    
    window.tallasSeleccionadas[genero].tallas.forEach(talla => {
        const key = `${genero}-${talla}`;
        const cantidadGuardada = window.cantidadesTallas[key] || 0;
        
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem;';
        
        const label = document.createElement('label');
        label.textContent = talla;
        label.style.cssText = 'font-size: 0.875rem; font-weight: 600; color: #6b7280; text-align: center;';
        
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.value = cantidadGuardada;
        input.dataset.key = key;
        input.style.cssText = 'padding: 0.5rem; border: 2px solid #0066cc; border-radius: 6px; text-align: center; font-weight: 600; font-size: 0.9rem;';
        input.onchange = () => {
            guardarCantidadTalla(input);
            actualizarTotalPrendas();
        };
        input.onkeyup = () => actualizarTotalPrendas();
        
        itemDiv.appendChild(label);
        itemDiv.appendChild(input);
        gridCantidades.appendChild(itemDiv);
    });
    
    tarjeta.appendChild(gridCantidades);
    container.appendChild(tarjeta);
};

/**
 * Actualizar total de prendas
 */
window.actualizarTotalPrendas = function() {
    let total = 0;
    document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
        total += parseInt(input.value) || 0;
    });
    
    const totalElement = document.getElementById('total-prendas');
    if (totalElement) {
        totalElement.textContent = total;
        console.log('📊 [TOTAL] Actualizado a:', total);
    }
};

/**
 * Obtener todas las tallas y cantidades seleccionadas
 */
window.obtenerTallasYCantidades = function() {
    const resultado = {};
    
    ['dama', 'caballero'].forEach(genero => {
        if (window.tallasSeleccionadas[genero].tallas.length > 0) {
            resultado[genero] = {};
            window.tallasSeleccionadas[genero].tallas.forEach(talla => {
                const key = `${genero}-${talla}`;
                resultado[genero][talla] = window.cantidadesTallas[key] || 0;
            });
        }
    });
    
    console.log('📦 [DATOS] Tallas y cantidades:', JSON.stringify(resultado));
    return resultado;
};

/**
 * Validar que se hayan seleccionado tallas
 */
window.validarTallasSeleccionadas = function() {
    const dama = window.tallasSeleccionadas.dama.tallas.length > 0;
    const caballero = window.tallasSeleccionadas.caballero.tallas.length > 0;
    
    if (!dama && !caballero) {
        console.warn('⚠️ [VALIDACIÓN] Debe seleccionar al menos tallas de un género');
        alert('⚠️ Debe seleccionar al menos tallas de un género (DAMA o CABALLERO)');
        return false;
    }
    
    console.log(' [VALIDACIÓN] Tallas válidas');
    return true;
};

/**
 * Limpiar todas las tallas y cantidades
 */
window.limpiarTallasSeleccionadas = function() {
    console.log('🧹 [LIMPIAR] Borrando todas las tallas');
    window.tallasSeleccionadas = {
        dama: { tallas: [], tipo: null },
        caballero: { tallas: [], tipo: null }
    };
    window.cantidadesTallas = {};
    
    // Actualizar UI
    const container = document.getElementById('tarjetas-generos-container');
    if (container) {
        container.innerHTML = '';
    }
    
    ['dama', 'caballero'].forEach(genero => {
        const btn = document.getElementById(`btn-genero-${genero}`);
        const check = document.getElementById(`check-${genero}`);
        
        if (btn) {
            btn.dataset.selected = 'false';
            btn.style.borderColor = '#d1d5db';
            btn.style.background = 'white';
            btn.style.color = '#1f2937';
        }
        
        if (check) {
            check.style.display = 'none';
        }
    });
    
    actualizarTotalPrendas();
    console.log(' [LIMPIAR] Limpieza completada');
};
