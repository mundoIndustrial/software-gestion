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

// ========== ESTADO GLOBAL DE TALLAS (MODELO RELACIONAL) ==========
// Estructura: { GENERO: { TALLA: CANTIDAD } }
// Ejemplo: { DAMA: { S: 10, M: 15 }, CABALLERO: { 32: 20 } }
window.tallasRelacionales = window.tallasRelacionales || {
    DAMA: {},
    CABALLERO: {}
};

// Cache de catálogo de tallas desde BD
window.catálogoTallasDisponibles = null;

// Variables para rastrear el estado del modal
window.generoActualModal = null;
window.tipoTallaSeleccionado = null;

// ========== FUNCIONES PARA CARGAR TALLAS DESDE BD ==========

/**
 * Cargar catálogo de tallas disponibles desde el endpoint API
 * Se llama una sola vez al inicializar la página
 */
window.cargarCatálogoTallas = async function() {
    try {
        if (window.catálogoTallasDisponibles) {
            // Ya cargado, no hacer nada
            console.log('[gestion-tallas] Catálogo ya cargado en caché');
            return;
        }

        console.log('[gestion-tallas] Cargando catálogo de tallas desde BD...');
        
        // Obtener CSRF token del meta tag o del DOM
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('/api/tallas-disponibles', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'  // Incluir cookies de sesión
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        
        if (result.success && result.data) {
            window.catálogoTallasDisponibles = result.data;
            console.log('[gestion-tallas]  Catálogo cargado:', result.data);
        } else {
            throw new Error(result.message || 'Respuesta inválida del servidor');
        }

    } catch (error) {
        console.error('[gestion-tallas]  Error al cargar catálogo:', error);
        // Fallback a constantes hardcodeadas si falla el fetch
        window.catálogoTallasDisponibles = {
            DAMA: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            CABALLERO: ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46'],
            UNISEX: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
        };
        console.warn('[gestion-tallas]  Usando catálogo hardcodeado como fallback');
    }
};

// ========== FUNCIONES DE GESTIÓN DE TALLAS RELACIONAL ==========

/**
 * Guardar cantidad de talla en estructura relacional { GENERO: { TALLA: CANTIDAD } }
 */
window.guardarCantidadTalla = function(genero, talla, cantidad) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    const cantInt = parseInt(cantidad) || 0;
    
    if (!window.tallasRelacionales[genero]) {
        window.tallasRelacionales[genero] = {};
    }
    
    if (cantInt > 0) {
        window.tallasRelacionales[genero][talla] = cantInt;
        console.log(`[gestion-tallas]  Talla guardada: ${genero} - ${talla}: ${cantInt}`);
    } else {
        delete window.tallasRelacionales[genero][talla];
        console.log(`[gestion-tallas] 🗑️ Talla eliminada: ${genero} - ${talla}`);
    }
    
    // Log del estado actual de todas las tallas
    console.log('[gestion-tallas] 📊 Estado actual de tallasRelacionales:', window.tallasRelacionales);
};

/**
 * Obtener cantidad de una talla en estructura relacional
 */
window.obtenerCantidadTalla = function(genero, talla) {
    return window.tallasRelacionales[genero] ? (window.tallasRelacionales[genero][talla] || 0) : 0;
};

/**
 * Mostrar las tallas disponibles según el tipo seleccionado
 */
window.mostrarTallasDisponibles = function(tipo) {

    
    const container = document.getElementById('container-tallas-disponibles');
    if (!container) return;
    
    container.innerHTML = '';
    
    let tallasAMostrar = [];
    
    // Usar catálogo cargado desde BD, con fallback a constantes
    if (!window.catálogoTallasDisponibles) {
        console.warn('[gestion-tallas]  Catálogo no cargado, usando constantes');
        // Fallback a constantes si no se cargó el catálogo
        if (tipo === 'letra') {
            tallasAMostrar = TALLAS_LETRAS || [];
        } else if (tipo === 'numero') {
            const genero = window.generoActualModal;
            tallasAMostrar = genero === 'DAMA' ? (TALLAS_NUMEROS_DAMA || []) : (TALLAS_NUMEROS_CABALLERO || []);
        }
    } else {
        // Usar catálogo cargado desde BD
        const genero = window.generoActualModal;
        if (tipo === 'letra') {
            // Mostrar tallas de letra (XS, S, M, L, XL, etc.) - generalmente DAMA
            tallasAMostrar = window.catálogoTallasDisponibles['DAMA'] || [];
        } else if (tipo === 'numero') {
            // Mostrar tallas de número (28, 30, 32, etc.) - generalmente CABALLERO
            tallasAMostrar = window.catálogoTallasDisponibles['CABALLERO'] || [];
        }
    }
    
    // Crear grid de tallas
    const grid = document.createElement('div');
    grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.75rem;';
    
    const tallasDic = window.tallasRelacionales[window.generoActualModal] || {};
    
    tallasAMostrar.forEach(talla => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.talla = talla;
        
        const isSelected = tallasDic.hasOwnProperty(talla);
        
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
            const isCurrentlySelected = tallasDic.hasOwnProperty(talla);
            
            // Asegurar que el objeto del género existe
            if (!window.tallasRelacionales[window.generoActualModal]) {
                window.tallasRelacionales[window.generoActualModal] = {};
            }
            
            if (isCurrentlySelected) {
                // Deseleccionar: eliminar talla
                delete window.tallasRelacionales[window.generoActualModal][talla];
                console.log(`[gestion-tallas]  Talla deseleccionada: ${window.generoActualModal} - ${talla}`);
                btn.style.borderColor = '#d1d5db';
                btn.style.background = 'white';
                btn.style.color = '#1f2937';

            } else {
                // Seleccionar: agregar talla con cantidad 0
                window.tallasRelacionales[window.generoActualModal][talla] = 0;
                console.log(`[gestion-tallas]  Talla seleccionada: ${window.generoActualModal} - ${talla}`);
                btn.style.borderColor = '#0066cc';
                btn.style.background = '#0066cc';
                btn.style.color = 'white';

            }
            console.log('[gestion-tallas] 📊 Tallas actuales del modal:', window.tallasRelacionales[window.generoActualModal]);
        };
        
        grid.appendChild(btn);
    });
    
    container.appendChild(grid);
};

/**
 * Seleccionar tipo de talla (LETRA o NÚMERO)
 */
window.seleccionarTipoTalla = function(tipo) {

    
    window.tipoTallaSeleccionado = tipo;
    
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

        } else {
            btnNumero.style.background = '#0066cc';
            btnNumero.style.borderColor = '#0066cc';
            btnNumero.style.color = 'white';
            btnLetra.style.background = 'white';
            btnLetra.style.borderColor = '#d1d5db';
            btnLetra.style.color = '#1f2937';

        }
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
 */
window.abrirModalSeleccionarTallas = async function(genero) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    // Cargar catálogo de tallas si no está cargado
    await window.cargarCatálogoTallas();
    
    window.generoActualModal = genero;
    
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
    const icon = genero === 'DAMA' ? 'woman' : 'man';
    headerContent.innerHTML = `<span class="material-symbols-rounded" style="font-size: 1.5rem;">${icon}</span><h2 style="margin: 0; font-size: 1.25rem;">Seleccionar Tallas ${genero}</h2>`;
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
    
    content.appendChild(selectorTipo);
    
    // ========== CONTENEDOR DE TALLAS DISPONIBLES ==========
    const tallaContainer = document.createElement('div');
    tallaContainer.id = 'container-tallas-disponibles';
    tallaContainer.style.cssText = 'display: none;';
    
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
        // Asegurar que el género existe en el objeto, sino crear un objeto vacío
        if (!window.tallasRelacionales[genero]) {
            window.tallasRelacionales[genero] = {};
        }
        
        if (Object.keys(window.tallasRelacionales[genero]).length === 0) {
            console.warn('[gestion-tallas]  No hay tallas seleccionadas para', genero);
            alert(' Debes seleccionar al menos una talla');
            return;
        }
        
        console.log(`[gestion-tallas]  Confirmando tallas para ${genero}:`, window.tallasRelacionales[genero]);
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
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    const modal = document.getElementById(`modal-tallas-${genero}`);
    if (modal) {
        modal.remove();
    }
    window.generoActualModal = null;
    window.tipoTallaSeleccionado = null;
};

/**
 * Crear tarjeta de género con tallas y cantidades en estructura relacional
 */
window.crearTarjetaGenero = function(genero) {
    // Normalizar género a mayúsculas para consistencia
    genero = String(genero).toUpperCase();
    
    const tallasDic = window.tallasRelacionales[genero] || {};
    
    if (Object.keys(tallasDic).length === 0) {

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
    const icon = genero === 'DAMA' ? 'woman' : 'man';
    headerLeft.innerHTML = `
        <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">${icon}</span>
        <div>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">${genero}</h4>
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

        
        // Limpiar tallas del género (estructura relacional)
        window.tallasRelacionales[genero] = {};

        
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

    };
    btnGroupAcciones.appendChild(btnEliminar);
    
    header.appendChild(btnGroupAcciones);
    
    tarjeta.appendChild(header);
    
    // Grid de cantidades en estructura relacional
    const gridCantidades = document.createElement('div');
    gridCantidades.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;';
    
    Object.entries(tallasDic).forEach(([talla, cantidad]) => {
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem;';
        
        const label = document.createElement('label');
        label.textContent = talla;
        label.style.cssText = 'font-size: 0.875rem; font-weight: 600; color: #6b7280; text-align: center;';
        
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '0';
        input.value = cantidad;
        input.style.cssText = 'padding: 0.5rem; border: 2px solid #0066cc; border-radius: 6px; text-align: center; font-weight: 600; font-size: 0.9rem;';
        input.onchange = () => {
            guardarCantidadTalla(genero, talla, input.value);
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
        console.log(`[gestion-tallas] 📦 Total de prendas actualizado: ${total}`);
    }
};

/**
 * Obtener todas las tallas y cantidades en estructura relacional
 */
window.obtenerTallasYCantidades = function() {
    // Retornar directamente la estructura relacional: { GENERO: { TALLA: CANTIDAD } }
    const resultado = {};
    
    console.log('[gestion-tallas] 🔍 Diagnóstico antes de procesar:');
    console.log('[gestion-tallas] Estado completo de tallasRelacionales:', window.tallasRelacionales);
    
    Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
        if (Object.keys(tallasObj).length > 0) {
            resultado[genero] = tallasObj;
            console.log(`[gestion-tallas]  Género ${genero} incluido en resultado:`, tallasObj);
        } else {
            console.log(`[gestion-tallas] ⏭️ Género ${genero} ignorado (vacío)`, tallasObj);
        }
    });
    
    console.log('[gestion-tallas] Tallas y cantidades FINALES a enviar:', resultado);
    return resultado;
};

/**
 * Validar que se hayan seleccionado tallas
 */
window.validarTallasSeleccionadas = function() {
    const dama = Object.keys(window.tallasRelacionales.DAMA || {}).length > 0;
    const caballero = Object.keys(window.tallasRelacionales.CABALLERO || {}).length > 0;
    
    if (!dama && !caballero) {

        alert(' Debe seleccionar al menos tallas de un género (DAMA o CABALLERO)');
        return false;
    }
    

    return true;
};

/**
 * Limpiar todas las tallas y cantidades
 */
window.limpiarTallasSeleccionadas = function() {

    
    // Resetear estructura relacional
    window.tallasRelacionales = {
        DAMA: {},
        CABALLERO: {}
    };
    

    
    // Actualizar UI
    const container = document.getElementById('tarjetas-generos-container');
    if (container) {
        container.innerHTML = '';
    }
    
    ['DAMA', 'CABALLERO'].forEach(genero => {
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

};
