// Variables globales para tallas
const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

/**
 * Actualiza el input oculto genero_id con el género seleccionado
 */
function actualizarGeneroSeleccionado(select) {
    const productoCard = select.closest('.producto-card');
    if (!productoCard) {
        console.warn('No se encontró .producto-card');
        return;
    }
    
    const generoInput = productoCard.querySelector('.genero-id-hidden');
    if (!generoInput) {
        console.warn('No se encontró .genero-id-hidden');
        return;
    }
    
    const generoValue = select.value;

    
    // Mapear valores de género a IDs (según generos_prenda)
    let generoId = '';
    if (generoValue === 'dama') {
        generoId = '2';  // DAMA = ID 2
    } else if (generoValue === 'caballero') {
        generoId = '1';  // CABALLERO = ID 1
    }
    
    generoInput.value = generoId;
    console.log(`Género actualizado: ${generoValue} => ID: ${generoId}`);
}

/**
 * Actualiza el selector de tallas basado en el tipo seleccionado
 */
function actualizarSelectTallas(select) {

    
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const generoSelect = container.querySelector('.talla-genero-select');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    const tipo = select.value;
    // LIMPIAR COMPLETAMENTE TODO ANTES DE CAMBIAR
    // 1. Limpiar botones
    botonesDiv.innerHTML = '';

    
    // 2. Limpiar tallas agregadas (NUEVA LÍNEA)
    tallasAgregadas.querySelectorAll('div').forEach(div => {
        div.remove();
    });
    tallasSection.style.display = 'none';
    
    // Actualizar el campo hidden para que esté vacío
    const tallasHidden = container.querySelector('.tallas-hidden');
    if (tallasHidden) {
        tallasHidden.value = '';

    }
    

    
    // 3. Ocultar todos los elementos
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    modoSelect.style.display = 'none';

    
    // 4. Resetear género
    if (generoSelect) {
        generoSelect.style.display = 'none';
        generoSelect.value = '';  // RESETEAR GÉNERO

    }
    
    // 5. Resetear modo
    modoSelect.value = '';

    
    // 6. Remover TODOS los event listeners anteriores
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

    }
    

    
    if (tipo === 'letra') {

        // LETRAS ahora muestra selector de género
        if (generoSelect) {
            generoSelect.style.display = 'block';
            generoSelect.value = '';

        }
        
        // Mostrar selector de modo para LETRAS
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual';

        
        // Agregar event listener al modoSelect para LETRAS
        modoSelect._handlerLetras = function() {

            actualizarModoLetras(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerLetras);

        
        // Mostrar botones de talla directamente para LETRAS
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        // Crear botones de LETRAS

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

        
    } else if (tipo === 'numero') {

        if (generoSelect) {
            generoSelect.style.display = 'block';
            generoSelect.value = '';  // Asegurar que esté vacío

        }
        
        if (generoSelect) {

            generoSelect._handler = function() {

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


    
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modo = select.value;
    if (modo === 'manual') {

        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        const botones = botonesDiv.querySelectorAll('.talla-btn');
        const tallasMostradas = Array.from(botones).map(btn => btn.textContent);


        
    } else if (modo === 'rango') {

        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'flex';
        

        actualizarSelectoresRango(container);
        
        const desdeSelect = container.querySelector('.talla-desde');
        const hastaSelect = container.querySelector('.talla-hasta');
        const optionsDesde = Array.from(desdeSelect.querySelectorAll('option')).map(opt => opt.value).filter(v => v);
        const optionsHasta = Array.from(hastaSelect.querySelectorAll('option')).map(opt => opt.value).filter(v => v);


        
    } else {

        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

/**
 * Actualiza los selectores de rango
 */
function actualizarSelectoresRango(container) {

    
    const generoSelect = container.querySelector('.talla-genero-select');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const genero = generoSelect.value;
    let tallas = [];
    if (genero === 'dama') {

        tallas = tallasDama;
    } else if (genero === 'caballero') {

        tallas = tallasCaballero;
    } else {

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
    

    
    // LIMPIAR todos los divs previamente agregados
    tallasAgregadas.querySelectorAll('div').forEach(div => {

        div.remove();
    });
    
    tallasRango.forEach(talla => {
        const valor = talla;
        
        const tag = document.createElement('div');
        tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
        tag.innerHTML = `
            <span>${valor}</span>
            <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
        `;
        

        tallasAgregadas.appendChild(tag);
    });
    
    tallasSection.style.display = 'block';
    
    const tallasHidden = container.querySelector('.tallas-hidden');
    actualizarTallasHidden(container);
    

}

/**
 * Actualiza los botones según el género seleccionado
 */
function actualizarBotonesPorGenero(container, genero) {

    
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    // LIMPIAR COMPLETAMENTE ANTES DE CAMBIAR
    botonesDiv.innerHTML = '';

    
    // Resetear modoSelect
    modoSelect.value = '';

    
    // Remover listeners anteriores
    if (modoSelect._handlerLetras) {
        modoSelect.removeEventListener('change', modoSelect._handlerLetras);
        modoSelect._handlerLetras = null;
    }
    
    modoSelect.style.display = 'block';

    
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';

    
    if (genero === 'dama') {

        tallaBotones.style.display = 'block';
        botonesDiv.innerHTML = '';

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
        

        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = function() {

            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
        
    } else if (genero === 'caballero') {

        tallaBotones.style.display = 'block';
        botonesDiv.innerHTML = '';

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
        

        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = function() {

            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
        
    } else if (genero === 'ambos') {

        
        // Crear pestañas para Dama y Caballero
        const tabsContainer = document.createElement('div');
        tabsContainer.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #0066cc;';
        
        const tabDama = document.createElement('button');
        tabDama.type = 'button';
        tabDama.textContent = '👩 DAMA';
        tabDama.style.cssText = 'padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;';
        tabDama.className = 'tab-genero activo';
        tabDama.dataset.genero = 'dama';
        
        const tabCaballero = document.createElement('button');
        tabCaballero.type = 'button';
        tabCaballero.textContent = '👨 CABALLERO';
        tabCaballero.style.cssText = 'padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;';
        tabCaballero.className = 'tab-genero';
        tabCaballero.dataset.genero = 'caballero';
        
        tabsContainer.appendChild(tabDama);
        tabsContainer.appendChild(tabCaballero);
        botonesDiv.parentElement.insertBefore(tabsContainer, botonesDiv);
        
        // Inicializar con DAMA
        botonesDiv.innerHTML = '';

        tallasDama.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.dataset.genero = 'dama';
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
        
        tallaBotones.style.display = 'block';
        
        // Event listeners para las pestañas
        tabDama.onclick = function(e) {
            e.preventDefault();
            tabDama.style.borderBottom = '3px solid #0066cc';
            tabCaballero.style.borderBottom = '3px solid white';
            botonesDiv.innerHTML = '';
            
            tallasDama.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.dataset.genero = 'dama';
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

        };
        
        tabCaballero.onclick = function(e) {
            e.preventDefault();
            tabDama.style.borderBottom = '3px solid white';
            tabCaballero.style.borderBottom = '3px solid #0066cc';
            botonesDiv.innerHTML = '';
            
            tallasCaballero.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.dataset.genero = 'caballero';
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

        };
        
    } else {
        tallaBotones.style.display = 'none';
    }
}

/**
 * Actualiza los botones para género en selección de letras
 */
function actualizarBotonesPorGeneroLetras(container, genero) {

    
    const modoSelect = container.querySelector('.talla-modo-select');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    
    // LIMPIAR COMPLETAMENTE ANTES DE CAMBIAR
    botonesDiv.innerHTML = '';

    
    // Eliminar pestañas anteriores si existen
    const tabsAnteriores = container.querySelector('.tabs-genero-letras');
    if (tabsAnteriores) {
        tabsAnteriores.remove();
    }
    
    // Ocultar secciones
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';

    
    // Resetear el modoSelect al cambiar de género en LETRAS
    modoSelect.value = '';

    
    // Remover listeners anteriores
    if (modoSelect._handler) {
        modoSelect.removeEventListener('change', modoSelect._handler);
        modoSelect._handler = null;
    }
    if (modoSelect._handlerNumeros) {
        modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        modoSelect._handlerNumeros = null;
    }
    
    // Para "ambos", mostrar directamente sin necesidad de modo
    if (genero === 'ambos') {

        modoSelect.style.display = 'block';
        modoSelect._handlerLetras = function() {

            actualizarModoLetras(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerLetras);
    } else {
        modoSelect.style.display = 'block';

        

        modoSelect._handlerLetras = function() {

            actualizarModoLetras(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerLetras);
    }
}

/**
 * Agrega las tallas seleccionadas
 */
function agregarTallasSeleccionadas(btn) {
    const container = btn.closest('.producto-section');
    const botonesActivos = container.querySelectorAll('.talla-btn.activo');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    const tallasHidden = container.querySelector('.tallas-hidden');
    



    
    if (botonesActivos.length === 0) {
        alert('Por favor selecciona al menos una talla');
        return;
    }
    
    // LIMPIAR todos los divs previamente agregados (pero NO el input hidden)
    tallasAgregadas.querySelectorAll('div').forEach(div => {

        div.remove();
    });
    
    botonesActivos.forEach(boton => {
        const talla = boton.dataset.talla;
        
        const tag = document.createElement('div');
        tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
        tag.innerHTML = `
            <span>${talla}</span>
            <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
        `;
        

        tallasAgregadas.appendChild(tag);
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

        return;
    }
    
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasHidden = container.querySelector('.tallas-hidden');
    
    if (!tallasAgregadas || !tallasHidden) {

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

