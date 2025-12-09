// Variables globales para tallas
const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

/**
 * Actualiza el selector de tallas basado en el tipo seleccionado
 */
function actualizarSelectTallas(select) {
    console.log('🔵 actualizarSelectTallas() llamado');
    
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const generoSelect = container.querySelector('.talla-genero-select');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const tipo = select.value;
    
    console.log('📋 Tipo seleccionado:', tipo);
    console.log('📋 Elementos encontrados:', {
        tallaBotones: !!tallaBotones,
        botonesDiv: !!botonesDiv,
        generoSelect: !!generoSelect,
        modoSelect: !!modoSelect,
        tallaRangoSelectors: !!tallaRangoSelectors
    });
    
    // LIMPIAR COMPLETAMENTE TODO ANTES DE CAMBIAR
    // 1. Limpiar botones
    botonesDiv.innerHTML = '';
    console.log('✅ botonesDiv limpiado');
    
    // 2. Ocultar todos los elementos
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    modoSelect.style.display = 'none';
    console.log('✅ tallaBotones, tallaRangoSelectors y modoSelect ocultados');
    
    // 3. Resetear género
    if (generoSelect) {
        generoSelect.style.display = 'none';
        generoSelect.value = '';  // RESETEAR GÉNERO
        console.log('✅ generoSelect ocultado y reseteado');
    }
    
    // 4. Resetear modo
    modoSelect.value = '';
    console.log('✅ modoSelect reseteado');
    
    // 5. Remover TODOS los event listeners anteriores
    if (modoSelect._handlerLetras) {
        modoSelect.removeEventListener('change', modoSelect._handlerLetras);
        modoSelect._handlerLetras = null;
    }
    if (modoSelect._handlerNumeros) {
        modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        modoSelect._handlerNumeros = null;
    }
    if (modoSelect._handler) {
        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = null;
    }
    console.log('✅ Todos los event listeners del modoSelect removidos');
    
    // 6. Remover event listeners del generoSelect
    if (generoSelect) {
        if (generoSelect._handlerLetras) {
            generoSelect.removeEventListener('change', generoSelect._handlerLetras);
            generoSelect._handlerLetras = null;
        }
        if (generoSelect._handler) {
            generoSelect.removeEventListener('change', generoSelect._handler);
            generoSelect._handler = null;
        }
        console.log('✅ Todos los event listeners del generoSelect removidos');
    }
    
    console.log('✅ LIMPIEZA COMPLETA FINALIZADA');
    
    if (tipo === 'letra') {
        console.log('📝 Configurando LETRAS');
        if (generoSelect) {
            generoSelect.style.display = 'block';
            generoSelect.value = '';  // Asegurar que esté vacío
            console.log('✅ generoSelect mostrado para LETRAS');
        }
        
        if (generoSelect) {
            console.log('📝 Agregando evento onchange para GÉNERO (LETRAS)');
            generoSelect._handlerLetras = function() {
                console.log('📝 Género seleccionado (LETRAS):', this.value);
                actualizarBotonesPorGeneroLetras(container, this.value);
            };
            generoSelect.addEventListener('change', generoSelect._handlerLetras);
        }
        
    } else if (tipo === 'numero') {
        console.log('🔢 Configurando NÚMEROS');
        if (generoSelect) {
            generoSelect.style.display = 'block';
            generoSelect.value = '';  // Asegurar que esté vacío
            console.log('✅ generoSelect mostrado para NÚMEROS');
        }
        
        if (generoSelect) {
            console.log('🔢 Agregando evento onchange para GÉNERO (NÚMEROS)');
            generoSelect._handler = function() {
                console.log('🔢 Género seleccionado (NÚMEROS):', this.value);
                actualizarBotonesPorGenero(container, this.value);
            };
            generoSelect.addEventListener('change', generoSelect._handler);
        }
    }
}

/**
 * Actualiza el modo de selección de tallas para letras
 */
function actualizarModoLetras(container, modo) {
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    
    botonesDiv.innerHTML = '';
    
    if (modo === 'manual') {
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        tallasLetras.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
    } else if (modo === 'rango') {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'flex';
        actualizarSelectoresRangoLetras(container);
    } else {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

/**
 * Actualiza los selectores de rango para letras
 */
function actualizarSelectoresRangoLetras(container) {
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    
    desdeSelect.innerHTML = '<option value="">Desde</option>';
    hastaSelect.innerHTML = '<option value="">Hasta</option>';
    
    tallasLetras.forEach(talla => {
        const optDesde = document.createElement('option');
        optDesde.value = talla;
        optDesde.textContent = talla;
        desdeSelect.appendChild(optDesde);
        
        const optHasta = document.createElement('option');
        optHasta.value = talla;
        optHasta.textContent = talla;
        hastaSelect.appendChild(optHasta);
    });
}

/**
 * Actualiza el modo de selección de tallas
 */
function actualizarModoTallas(select) {
    console.log('🎯 actualizarModoTallas() llamado');
    console.log('🎯 select.value:', select.value);
    
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modo = select.value;
    
    console.log('🎯 Modo:', modo);
    console.log('🎯 Elementos:', {
        tallaBotones: !!tallaBotones,
        tallaRangoSelectors: !!tallaRangoSelectors,
        botonesDiv: !!botonesDiv
    });
    
    if (modo === 'manual') {
        console.log('✅ Mostrando BOTONES (manual)');
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        const botones = botonesDiv.querySelectorAll('.talla-btn');
        const tallasMostradas = Array.from(botones).map(btn => btn.textContent);
        console.log('📍 TALLAS MOSTRADAS EN MANUAL:', tallasMostradas);
        console.log('📍 Total de botones:', botones.length);
        
    } else if (modo === 'rango') {
        console.log('✅ Mostrando RANGO');
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'flex';
        
        console.log('📊 Antes de actualizarSelectoresRango()');
        actualizarSelectoresRango(container);
        
        const desdeSelect = container.querySelector('.talla-desde');
        const hastaSelect = container.querySelector('.talla-hasta');
        const optionsDesde = Array.from(desdeSelect.querySelectorAll('option')).map(opt => opt.value).filter(v => v);
        const optionsHasta = Array.from(hastaSelect.querySelectorAll('option')).map(opt => opt.value).filter(v => v);
        console.log('📍 TALLAS EN RANGO DESDE:', optionsDesde);
        console.log('📍 TALLAS EN RANGO HASTA:', optionsHasta);
        
    } else {
        console.log('⚠️ Modo no reconocido, ocultando todo');
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

/**
 * Actualiza los selectores de rango
 */
function actualizarSelectoresRango(container) {
    console.log('📊 actualizarSelectoresRango() llamado');
    
    const generoSelect = container.querySelector('.talla-genero-select');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const genero = generoSelect.value;
    
    console.log('📊 Género en rango:', genero);
    console.log('📊 Elementos encontrados:', {
        generoSelect: !!generoSelect,
        desdeSelect: !!desdeSelect,
        hastaSelect: !!hastaSelect
    });
    
    let tallas = [];
    if (genero === 'dama') {
        console.log('📊 Usando tallas DAMA para rango');
        tallas = tallasDama;
    } else if (genero === 'caballero') {
        console.log('📊 Usando tallas CABALLERO para rango');
        tallas = tallasCaballero;
    } else {
        console.log('⚠️ Género no reconocido en rango:', genero);
    }
    
    console.log('📊 Tallas a mostrar en rango:', tallas);
    
    desdeSelect.innerHTML = '<option value="">Desde</option>';
    hastaSelect.innerHTML = '<option value="">Hasta</option>';
    
    tallas.forEach(talla => {
        const optDesde = document.createElement('option');
        optDesde.value = talla;
        optDesde.textContent = talla;
        desdeSelect.appendChild(optDesde);
        
        const optHasta = document.createElement('option');
        optHasta.value = talla;
        optHasta.textContent = talla;
        hastaSelect.appendChild(optHasta);
    });
    
    console.log('✅ Rango actualizado con', tallas.length, 'tallas');
}

/**
 * Agrega tallas desde un rango seleccionado
 */
function agregarTallasRango(btn) {
    const container = btn.closest('.producto-section');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const generoSelect = container.querySelector('.talla-genero-select');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    const desde = desdeSelect.value;
    const hasta = hastaSelect.value;
    
    if (!desde || !hasta) {
        alert('Por favor selecciona rango desde y hasta');
        return;
    }
    
    let tallas = [];
    let esLetra = false;
    
    if (tallasLetras.includes(desde)) {
        tallas = tallasLetras;
        esLetra = true;
    } else {
        const genero = generoSelect.value;
        if (genero === 'dama') {
            tallas = tallasDama;
        } else if (genero === 'caballero') {
            tallas = tallasCaballero;
        }
    }
    
    const desdeIdx = tallas.indexOf(desde);
    const hastaIdx = tallas.indexOf(hasta);
    
    if (desdeIdx === -1 || hastaIdx === -1 || desdeIdx > hastaIdx) {
        alert('Rango inválido');
        return;
    }
    
    const tallasRango = tallas.slice(desdeIdx, hastaIdx + 1);
    
    tallasRango.forEach(talla => {
        const valor = talla;
        
        const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
            tag.querySelector('span').textContent === valor
        );
        
        if (!existe) {
            const tag = document.createElement('div');
            tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
            tag.innerHTML = `
                <span>${valor}</span>
                <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
            `;
            tallasAgregadas.appendChild(tag);
        }
    });
    
    tallasSection.style.display = 'block';
    actualizarTallasHidden(container);
}

/**
 * Actualiza los botones según el género seleccionado
 */
function actualizarBotonesPorGenero(container, genero) {
    console.log('🔍 actualizarBotonesPorGenero() llamado con genero:', genero);
    
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    
    console.log('🔍 Elementos encontrados:', {
        tallaBotones: !!tallaBotones,
        botonesDiv: !!botonesDiv,
        modoSelect: !!modoSelect,
        tallaRangoSelectors: !!tallaRangoSelectors
    });
    console.log('🔍 Valor actual de modoSelect:', modoSelect.value);
    
    // LIMPIAR COMPLETAMENTE ANTES DE CAMBIAR
    botonesDiv.innerHTML = '';
    console.log('✅ botonesDiv limpiado');
    
    // Resetear modoSelect
    modoSelect.value = '';
    console.log('✅ modoSelect reseteado');
    
    // Remover listeners anteriores
    if (modoSelect._handlerLetras) {
        modoSelect.removeEventListener('change', modoSelect._handlerLetras);
        modoSelect._handlerLetras = null;
    }
    
    modoSelect.style.display = 'block';
    console.log('✅ modoSelect mostrado (valor actual:', modoSelect.value, ')');
    
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    console.log('✅ tallaBotones y tallaRangoSelectors ocultados');
    
    if (genero === 'dama') {
        console.log('👩 DAMA seleccionado');
        tallaBotones.style.display = 'block';
        botonesDiv.innerHTML = '';
        console.log('👩 Agregando botones de DAMA:', tallasDama);
        tallasDama.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
        
        console.log('👩 Agregando evento onchange al modoSelect para DAMA');
        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = function() {
            console.log('👩 DAMA: Modo cambiado a:', this.value);
            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
        
    } else if (genero === 'caballero') {
        console.log('👨 CABALLERO seleccionado');
        tallaBotones.style.display = 'block';
        botonesDiv.innerHTML = '';
        console.log('👨 Agregando botones de CABALLERO:', tallasCaballero);
        tallasCaballero.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
        
        console.log('👨 Agregando evento onchange al modoSelect para CABALLERO');
        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = function() {
            console.log('👨 CABALLERO: Modo cambiado a:', this.value);
            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
        
    } else {
        tallaBotones.style.display = 'none';
    }
}

/**
 * Actualiza los botones para género en selección de letras
 */
function actualizarBotonesPorGeneroLetras(container, genero) {
    console.log('📝 actualizarBotonesPorGeneroLetras() llamado con genero:', genero);
    
    const modoSelect = container.querySelector('.talla-modo-select');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    
    // LIMPIAR COMPLETAMENTE ANTES DE CAMBIAR
    botonesDiv.innerHTML = '';
    console.log('📝 botonesDiv limpiado');
    
    // Ocultar secciones
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    console.log('📝 tallaBotones y tallaRangoSelectors ocultados');
    
    // Resetear el modoSelect al cambiar de género en LETRAS
    modoSelect.value = '';
    console.log('📝 Valor actual de modoSelect:', modoSelect.value);
    
    // Remover listeners anteriores
    if (modoSelect._handler) {
        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = null;
    }
    if (modoSelect._handlerNumeros) {
        modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        modoSelect._handlerNumeros = null;
    }
    
    modoSelect.style.display = 'block';
    console.log('📝 modoSelect mostrado (valor actual:', modoSelect.value, ')');
    
    console.log('📝 Agregando evento onchange al modoSelect para LETRAS');
    modoSelect._handlerLetras = function() {
        console.log('📝 LETRAS: Modo cambiado a:', this.value);
        actualizarModoLetras(container, this.value);
    };
    modoSelect.addEventListener('change', modoSelect._handlerLetras);
}

/**
 * Agrega las tallas seleccionadas
 */
function agregarTallasSeleccionadas(btn) {
    const container = btn.closest('.producto-section');
    const botonesActivos = container.querySelectorAll('.talla-btn.activo');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    if (botonesActivos.length === 0) {
        alert('Por favor selecciona al menos una talla');
        return;
    }
    
    botonesActivos.forEach(boton => {
        const talla = boton.dataset.talla;
        
        const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
            tag.querySelector('span').textContent === talla
        );
        
        if (!existe) {
            const tag = document.createElement('div');
            tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
            tag.innerHTML = `
                <span>${talla}</span>
                <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
            `;
            
            tallasAgregadas.appendChild(tag);
        }
    });
    
    tallasSection.style.display = 'block';
    
    actualizarTallasHidden(container);
    
    botonesActivos.forEach(boton => {
        boton.classList.remove('activo');
        boton.style.background = 'white';
        boton.style.color = '#0066cc';
    });
}

/**
 * Actualiza el campo hidden con las tallas seleccionadas
 */
function actualizarTallasHidden(container) {
    if (!container) {
        console.warn('⚠️ Container no encontrado en actualizarTallasHidden');
        return;
    }
    
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasHidden = container.querySelector('.tallas-hidden');
    
    if (!tallasAgregadas || !tallasHidden) {
        console.warn('⚠️ Elementos de tallas no encontrados');
        return;
    }
    
    const tallas = [];
    
    tallasAgregadas.querySelectorAll('div').forEach(tag => {
        const span = tag.querySelector('span');
        if (span) {
            tallas.push(span.textContent);
        }
    });
    
    tallasHidden.value = tallas.join(', ');
}
