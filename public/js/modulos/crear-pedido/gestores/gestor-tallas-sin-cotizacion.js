// Variables globales para tallas - EXACTAMENTE COMO EN tallas.js
const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

/**
 * Actualiza el selector de tallas basado en el tipo seleccionado
 * Obtiene el género de la prenda seleccionada anteriormente
 */
function actualizarSelectTallasSinCot(select) {
    console.log('🔵 actualizarSelectTallasSinCot() llamado');
    
    const container = select.closest('.tipo-prenda-row');
    if (!container) {
        console.warn(' No se encontró .tipo-prenda-row');
        return;
    }
    
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const tipo = select.value;
    
    // 🆕 Obtener el género de la prenda seleccionada
    const prendaCard = container.closest('.prenda-card-editable');
    const generoSelect = prendaCard ? prendaCard.querySelector('.prenda-genero') : null;
    const generoSeleccionado = generoSelect ? generoSelect.value : '';
    
    console.log(' Tipo seleccionado:', tipo);
    console.log(' Género de prenda seleccionado:', generoSeleccionado);
    console.log(' Elementos encontrados:', {
        tallaBotones: !!tallaBotones,
        botonesDiv: !!botonesDiv,
        modoSelect: !!modoSelect,
        tallaRangoSelectors: !!tallaRangoSelectors,
        generoSelect: !!generoSelect
    });
    
    // LIMPIAR COMPLETAMENTE TODO ANTES DE CAMBIAR
    // 1. Limpiar botones
    botonesDiv.innerHTML = '';
    console.log(' botonesDiv limpiado');
    
    // 2. Ocultar todos los elementos
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    modoSelect.style.display = 'none';
    console.log(' tallaBotones, tallaRangoSelectors y modoSelect ocultados');
    
    // 3. Resetear modo
    modoSelect.value = '';
    console.log(' modoSelect reseteado');
    
    // 4. Remover TODOS los event listeners anteriores
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
    console.log(' Todos los event listeners del modoSelect removidos');
    
    console.log(' LIMPIEZA COMPLETA FINALIZADA');
    
    if (tipo === 'letra') {
        console.log(' Configurando LETRAS');
        
        // Mostrar selector de modo para LETRAS
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual';
        console.log(' modoSelect MOSTRADO para LETRAS');
        
        // Agregar event listener al modoSelect para LETRAS
        modoSelect._handlerLetras = function() {
            console.log(' Modo cambiado para LETRAS:', this.value);
            actualizarModoLetrasSinCot(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerLetras);
        console.log(' Event listener agregado a modoSelect para LETRAS');
        
        // Mostrar botones de talla directamente para LETRAS
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        // Crear botones de LETRAS
        console.log(' Creando botones de LETRAS');
        botonesDiv.innerHTML = '';
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
        console.log(' Botones de LETRAS creados');
        
    } else if (tipo === 'numero') {
        console.log('🔢 Configurando NÚMEROS');
        
        // Si ya hay género seleccionado en la prenda, usarlo directamente
        if (generoSeleccionado && (generoSeleccionado === 'Dama' || generoSeleccionado === 'Caballero')) {
            console.log('🔢 Usando género preseleccionado de la prenda:', generoSeleccionado);
            const genero = generoSeleccionado === 'Dama' ? 'dama' : 'caballero';
            actualizarBotonesPorGeneroSinCot(container, genero);
        } else {
            console.log('🔢 No hay género preseleccionado, esperando selección');
            // Mostrar selector de modo sin mostrar botones aún
            modoSelect.style.display = 'none';
            console.warn(' Por favor selecciona un género en la prenda antes de elegir tallas');
        }
    }
}

/**
 * Actualiza botones de tallas por género (NÚMEROS)
 * REPLICACIÓN EXACTA de actualizarBotonesPorGenero() de tallas.js
 */
function actualizarBotonesPorGeneroSinCot(container, genero) {
    console.log('🔢 Género seleccionado:', genero);
    
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    
    let tallas = [];
    if (genero === 'dama') {
        tallas = tallasDama;
    } else if (genero === 'caballero') {
        tallas = tallasCaballero;
    }
    
    // Mostrar modo
    if (modoSelect) {
        modoSelect.style.display = 'block';
        modoSelect.value = '';
        
        // Remover listeners anteriores
        if (modoSelect._handlerNumeros) {
            modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        }
        
        modoSelect._handlerNumeros = function() {
            console.log('🔢 Modo cambiado para NÚMEROS:', this.value);
            actualizarModoNumerosSinCot(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerNumeros);
    }
    
    // Limpiar estado anterior
    botonesDiv.innerHTML = '';
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
}

/**
 * Actualiza modo para LETRAS
 * REPLICACIÓN EXACTA de actualizarModoLetras() de tallas.js
 */
function actualizarModoLetrasSinCot(container, modo) {
    console.log(' Modo LETRAS:', modo);
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
        actualizarSelectoresRangoLetrasSinCot(container);
    } else {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

/**
 * Actualiza selectores de rango para LETRAS
 * REPLICACIÓN EXACTA de actualizarSelectoresRangoLetras() de tallas.js
 */
function actualizarSelectoresRangoLetrasSinCot(container) {
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
 * Actualiza modo para NÚMEROS
 * REPLICACIÓN EXACTA de actualizarModoNumeros() pero para números
 */
function actualizarModoNumerosSinCot(container, modo) {
    console.log('🔢 Modo NÚMEROS:', modo);
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    
    // 🆕 Obtener el género de la prenda seleccionada (no del selector de género)
    const prendaCard = container.closest('.prenda-card-editable');
    const generoSelect = prendaCard ? prendaCard.querySelector('.prenda-genero') : null;
    const genero = generoSelect ? (generoSelect.value === 'Dama' ? 'dama' : 'caballero') : '';
    
    console.log('🔢 Género obtenido:', genero);
    
    botonesDiv.innerHTML = '';
    
    if (modo === 'manual') {
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        const tallas = genero === 'dama' ? tallasDama : tallasCaballero;
        
        tallas.forEach(talla => {
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
        actualizarSelectoresRangoNumerosSinCot(container, genero);
    } else {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

/**
 * Actualiza selectores de rango para NÚMEROS
 */
function actualizarSelectoresRangoNumerosSinCot(container, genero) {
    console.log(' actualizarSelectoresRangoNumerosSinCot() llamado con género:', genero);
    
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    
    let tallas = [];
    if (genero === 'dama') {
        console.log(' Usando tallas DAMA para rango');
        tallas = tallasDama;
    } else if (genero === 'caballero') {
        console.log(' Usando tallas CABALLERO para rango');
        tallas = tallasCaballero;
    }
    
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
    
    console.log(' Rango actualizado con', tallas.length, 'tallas');
}

/**
 * Agrega tallas seleccionadas manualmente
 * REPLICACIÓN EXACTA de agregarTallasSeleccionadas() de tallas.js
 */
function agregarTallasSeleccionadasSinCot(btn) {
    const container = btn.closest('.tipo-prenda-row');
    if (!container) return;
    
    const botonesDiv = container.querySelector('.talla-botones-container');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    const botones = botonesDiv.querySelectorAll('.talla-btn.activo');
    if (botones.length === 0) {
        alert('Selecciona al menos una talla');
        return;
    }
    
    botones.forEach(btn => {
        const talla = btn.dataset.talla;
        crearTagTallaSinCot(tallasAgregadas, talla, container);
    });
    
    // Deseleccionar botones
    botones.forEach(btn => {
        btn.classList.remove('activo');
        btn.style.background = 'white';
        btn.style.color = '#0066cc';
    });
    
    tallasSection.style.display = 'block';
}

/**
 * Agrega tallas desde rango
 * REPLICACIÓN EXACTA de agregarTallasRango() de tallas.js
 */
function agregarTallasRangoSinCot(btn) {
    const container = btn.closest('.tipo-prenda-row');
    if (!container) return;
    
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    // 🆕 Obtener el género de la prenda seleccionada
    const prendaCard = container.closest('.prenda-card-editable');
    const generoSelect = prendaCard ? prendaCard.querySelector('.prenda-genero') : null;
    
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
        const genero = generoSelect ? (generoSelect.value === 'Dama' ? 'dama' : 'caballero') : '';
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
        crearTagTallaSinCot(tallasAgregadas, talla, container);
    });
    
    tallasSection.style.display = 'block';
}

/**
 * Crea un tag para una talla agregada
 * SINCRONIZA AUTOMÁTICAMENTE CON EL GESTOR
 */
function crearTagTallaSinCot(container, talla, prenda) {
    // Verificar si ya existe
    const existing = container.querySelector(`[data-talla="${talla}"]`);
    if (existing) {
        alert(`Talla ${talla} ya fue agregada`);
        return;
    }
    
    const tag = document.createElement('span');
    tag.className = 'talla-tag';
    tag.setAttribute('data-talla', talla);
    tag.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.8rem; background: #0066cc; color: white; border-radius: 20px; font-size: 0.85rem; font-weight: 600;';
    
    const text = document.createElement('span');
    text.textContent = talla;
    tag.appendChild(text);
    
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = '<i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>';
    btn.style.cssText = 'background: none; border: none; color: white; cursor: pointer; font-size: 0.9rem; padding: 0.2rem 0.4rem; margin: 0; display: flex; align-items: center; justify-content: center; transition: transform 0.2s ease;';
    btn.title = 'Eliminar talla';
    btn.onmouseover = function() { this.style.transform = 'scale(1.2)'; };
    btn.onmouseout = function() { this.style.transform = 'scale(1)'; };
    btn.onclick = function(e) {
        e.preventDefault();
        eliminarTallaDeLaTablaSinCot(talla, prenda);
    };
    tag.appendChild(btn);
    
    container.appendChild(tag);
    
    // CRÍTICO: Sincronizar con el gestor de prendas
    const prendaIndex = parseInt(prenda.getAttribute('data-prenda-index') || -1);
    if (prendaIndex >= 0 && window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
        console.log(' Talla sincronizada con gestor:', talla, 'Prenda:', prendaIndex);
    }
    
    actualizarTallasHiddenSinCot(prenda);
    
    // NUEVO: Crear o actualizar tabla de cantidades
    crearOActualizarTablaCantidadesSinCot(prenda, prendaIndex);
}

/**
 * Actualiza el input hidden con las tallas
 */
function actualizarTallasHiddenSinCot(container) {
    const hiddenInput = container.querySelector('.tallas-hidden');
    if (!hiddenInput) return;
    
    const tags = container.querySelectorAll('.talla-tag');
    const tallas = Array.from(tags).map(tag => tag.dataset.talla);
    
    hiddenInput.value = JSON.stringify(tallas);
    console.log(' Tallas actualizadas:', tallas);
}

/**
 * Elimina una talla de la tabla y su tag correspondiente
 * SINCRONIZA AUTOMÁTICAMENTE CON EL GESTOR
 */
function eliminarTallaDeLaTablaSinCot(talla, container) {
    // 1. Buscar y eliminar el tag de la talla
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tag = tallasAgregadas ? tallasAgregadas.querySelector(`[data-talla="${talla}"]`) : null;
    
    if (tag) {
        tag.remove();
        console.log('🗑️ Tag de talla eliminado:', talla);
    }
    
    // 2. Sincronizar eliminación con el gestor de prendas
    const prendaIndex = parseInt(container.getAttribute('data-prenda-index') || -1);
    if (prendaIndex >= 0 && window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
        console.log('🗑️ Talla eliminada del gestor:', talla, 'Prenda:', prendaIndex);
    }
    
    // 3. Actualizar inputs hidden
    actualizarTallasHiddenSinCot(container);
    
    // 4. Actualizar tabla de cantidades
    crearOActualizarTablaCantidadesSinCot(container, prendaIndex);
}

/**
 * Crea o actualiza la tabla de cantidades para tallas
 * Se ejecuta dinámicamente cuando se agregan/eliminan tallas
 */
function crearOActualizarTablaCantidadesSinCot(container, prendaIndex) {
    const prenda = prendaIndex >= 0 && window.gestorPrendaSinCotizacion 
        ? window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex)
        : null;
    
    if (!prenda || !prenda.tallas || prenda.tallas.length === 0) {
        // Si no hay tallas, eliminar tabla si existe
        const tablaExistente = container.querySelector('.tabla-cantidades-tallas');
        if (tablaExistente) {
            tablaExistente.remove();
        }
        return;
    }
    
    // Buscar o crear contenedor de tabla dentro de tallas-section
    const tallasSection = container.querySelector('.tallas-section');
    if (!tallasSection) {
        console.warn(' No se encontró .tallas-section');
        return;
    }
    
    let tabla = tallasSection.querySelector('.tabla-cantidades-tallas');
    
    if (!tabla) {
        // Crear tabla nueva
        tabla = document.createElement('div');
        tabla.className = 'tabla-cantidades-tallas';
        tabla.style.cssText = 'margin-top: 1rem; overflow-x: auto;';
        
        // Insertar al inicio de tallas-section
        tallasSection.insertBefore(tabla, tallasSection.firstChild);
    }
    
    // Generar HTML de la tabla
    const htmlTabla = `
        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 6px; overflow: hidden;">
            <thead style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                <tr>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; font-size: 0.85rem; color: white;">Talla</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; font-size: 0.85rem; color: white;">Cantidad</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; font-size: 0.85rem; color: white;">Acción</th>
                </tr>
            </thead>
            <tbody>
                ${prenda.tallas.map(talla => {
                    const cantidad = prenda.cantidadesPorTalla?.[talla] || 0;
                    return `
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 0.75rem 1rem; font-weight: 600; color: #0066cc;">${talla}</td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <input type="number" 
                                       min="0" 
                                       value="${cantidad}" 
                                       class="talla-cantidad-input" 
                                       data-talla="${talla}" 
                                       data-prenda="${prendaIndex}"
                                       placeholder="0"
                                       style="width: 80px; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; text-align: center; font-weight: 600;">
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <button type="button" 
                                        class="btn-eliminar-talla" 
                                        onclick="eliminarTallaDeLaTablaSinCot('${talla}', this.closest('.tipo-prenda-row'))"
                                        style="padding: 0.4rem 0.8rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.8rem; white-space: nowrap;">
                                    ✕ Quitar
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
    
    tabla.innerHTML = htmlTabla;
    
    // Adjuntar listeners a los inputs nuevos
    tabla.querySelectorAll('.talla-cantidad-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            window.gestorPrendaSinCotizacion.actualizarCantidadTalla(index, talla, cantidad);
            console.log(` Cantidad actualizada - Prenda: ${index}, Talla: ${talla}, Cantidad: ${cantidad}`);
        });
        
        input.addEventListener('input', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            window.gestorPrendaSinCotizacion.actualizarCantidadTalla(index, talla, cantidad);
        });
    });
    
    console.log(' Tabla de cantidades actualizada');
}
