// Variables globales para tallas
const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

/**
 * Actualiza el input oculto genero_id con los géneros seleccionados
 */
function actualizarGeneroSeleccionado(checkbox) {
    const productoCard = checkbox.closest('.producto-card');
    if (!productoCard) {
        console.warn('No se encontró .producto-card');
        return;
    }
    
    const generoInput = productoCard.querySelector('.genero-id-hidden');
    if (!generoInput) {
        console.warn('No se encontró .genero-id-hidden');
        return;
    }
    
    // Obtener todos los checkboxes marcados
    const generoSelectors = checkbox.closest('.talla-genero-selectores');
    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
    
    // Construir array con los IDs de género
    const generosIds = [];
    checkboxesMarcados.forEach(cb => {
        if (cb.value === 'dama') {
            generosIds.push('2');  // DAMA = ID 2
        } else if (cb.value === 'caballero') {
            generosIds.push('1');  // CABALLERO = ID 1
        }
    });
    
    // Guardar como array JSON para manejar múltiples géneros
    generoInput.value = JSON.stringify(generosIds);
    console.log(`Géneros actualizados: ${generosIds.join(', ')} => IDs: [${generosIds.join(', ')}]`);
}

function asegurarTabsGeneroRango(container) {
    const generoSelectors = container.querySelector('.talla-genero-selectores');
    if (!generoSelectors) return;

    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
    const tabsExistentes = container.querySelector('.tabs-genero-container');

    if (checkboxesMarcados.length <= 1) {
        if (tabsExistentes) tabsExistentes.remove();
        if (checkboxesMarcados.length === 1) {
            container.dataset.generoActivoRango = checkboxesMarcados[0].value;
        } else {
            delete container.dataset.generoActivoRango;
        }
        return;
    }

    const tabsContainer = document.createElement('div');
    tabsContainer.className = 'tabs-genero-container';
    tabsContainer.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #0066cc;';

    const tabsData = [];
    const generoActivoActual = (container.dataset.generoActivoRango || '').trim();
    checkboxesMarcados.forEach((checkbox, index) => {
        const genero = checkbox.value;
        const esActivo = generoActivoActual ? generoActivoActual === genero : index === 0;

        const tab = document.createElement('button');
        tab.type = 'button';
        tab.textContent = genero === 'dama' ? 'DAMA' : 'CABALLERO';
        tab.style.cssText = `padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid ${esActivo ? '#0066cc' : 'white'}; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;`;
        tab.className = 'tab-genero' + (esActivo ? ' activo' : '');
        tab.dataset.genero = genero;

        tabsContainer.appendChild(tab);
        tabsData.push({ tab, genero });

        tab.onclick = function(e) {
            e.preventDefault();

            tabsData.forEach(({ tab: t }) => {
                t.style.borderBottom = '3px solid white';
                t.classList.remove('activo');
            });
            tab.style.borderBottom = '3px solid #0066cc';
            tab.classList.add('activo');

            container.dataset.generoActivoRango = genero;
            actualizarSelectoresRango(container);
        };
    });

    if (tabsExistentes) tabsExistentes.replaceWith(tabsContainer);
    else {
        const rangoSelectors = container.querySelector('.talla-rango-selectors');
        if (rangoSelectors && rangoSelectors.parentElement) {
            rangoSelectors.parentElement.insertBefore(tabsContainer, rangoSelectors);
        } else {
            container.insertBefore(tabsContainer, container.firstChild);
        }
    }

    // Activar tab por defecto: el activo actual si existe, si no el primero
    const tabInicial = tabsData.find(t => t.genero === generoActivoActual)?.tab || tabsData[0].tab;
    tabInicial.click();
}

function actualizarTabsGeneroLetrasSinModo(container) {
    const generoSelectors = container.querySelector('.talla-genero-selectores');
    const botonesDiv = container.querySelector('.talla-botones-container');
    if (!generoSelectors || !botonesDiv) return;

    // Eliminar pestañas anteriores si existen
    const tabsAnteriores = container.querySelector('.tabs-genero-container');
    if (tabsAnteriores) {
        tabsAnteriores.remove();
    }

    // Obtener géneros seleccionados
    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');

    // Guardar el género activo para usarlo luego (cuando se escoja modo)
    if (checkboxesMarcados.length === 1) {
        container.dataset.generoActivoLetras = checkboxesMarcados[0].value;
        return;
    }

    if (checkboxesMarcados.length === 0) {
        delete container.dataset.generoActivoLetras;
        return;
    }

    // Múltiples géneros: crear tabs
    const tabsContainer = document.createElement('div');
    tabsContainer.className = 'tabs-genero-container';
    tabsContainer.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #0066cc;';

    const tabsData = [];

    checkboxesMarcados.forEach((checkbox, index) => {
        const genero = checkbox.value;
        const esPrimero = index === 0;

        const tab = document.createElement('button');
        tab.type = 'button';
        tab.textContent = genero === 'dama' ? 'DAMA' : 'CABALLERO';
        tab.style.cssText = `padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid ${esPrimero ? '#0066cc' : 'white'}; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;`;
        tab.className = 'tab-genero' + (esPrimero ? ' activo' : '');
        tab.dataset.genero = genero;

        tabsContainer.appendChild(tab);
        tabsData.push({ tab, genero });

        tab.onclick = function(e) {
            e.preventDefault();

            tabsData.forEach(({ tab: t }) => {
                t.style.borderBottom = '3px solid white';
                t.classList.remove('activo');
            });
            tab.style.borderBottom = '3px solid #0066cc';
            tab.classList.add('activo');

            container.dataset.generoActivoLetras = genero;
        };
    });

    // Insertar pestañas antes del contenedor de botones
    botonesDiv.parentElement.insertBefore(tabsContainer, botonesDiv);

    // Activar el primero por defecto
    tabsData[0].tab.click();
}

/**
 * Actualiza el selector de tallas basado en el tipo seleccionado
 */
function actualizarSelectTallas(select) {

    
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const generoSelectors = container.querySelector('.talla-genero-selectores');
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
    if (generoSelectors) {
        generoSelectors.style.display = 'none';
        // Limpiar checkboxes
        generoSelectors.querySelectorAll('.talla-genero-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
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

    
    // 6. Remover event listeners de los checkboxes de género
    if (generoSelectors) {
        generoSelectors.querySelectorAll('.talla-genero-checkbox').forEach(checkbox => {
            if (checkbox._handler) {
                checkbox.removeEventListener('change', checkbox._handler);
                checkbox._handler = null;
            }
        });
    }
    

    
    if (tipo === 'letra') {

        // LETRAS ahora muestra selector de género
        if (generoSelectors) {
            generoSelectors.style.display = 'block';
        }

        // Al cambiar géneros en LETRAS, generar tabs inmediatamente (sin depender del modo)
        if (generoSelectors) {
            const checkboxes = generoSelectors.querySelectorAll('.talla-genero-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox._handler = function() {
                    actualizarTabsGeneroLetrasSinModo(container);

                    // Si ya hay modo seleccionado, refrescar la UI del modo con el nuevo set de géneros
                    if (modoSelect && modoSelect.value) {
                        actualizarModoLetras(container, modoSelect.value);
                    }
                };
                checkbox.addEventListener('change', checkbox._handler);
            });

            // Estado inicial (por si ya venían marcados por edición)
            actualizarTabsGeneroLetrasSinModo(container);
        }
        
        // Mostrar selector de modo para LETRAS
        modoSelect.style.display = 'block';
        modoSelect.value = ''; // Cambiado a vacío para que muestre "Selecciona modo"

        
        // Agregar event listener al modoSelect para LETRAS
        modoSelect._handlerLetras = function() {

            actualizarModoLetras(container, this.value);
        };
        modoSelect.addEventListener('change', modoSelect._handlerLetras);

        
        // Mostrar botones de talla directamente para LETRAS
        tallaBotones.style.display = 'none'; // Cambiado a none para que no muestre nada hasta seleccionar modo
        tallaRangoSelectors.style.display = 'none';
        
        // No llamar a actualizarModoLetras automáticamente, esperar a que el usuario seleccione un modo

        
    } else if (tipo === 'numero') {

        if (generoSelectors) {
            generoSelectors.style.display = 'block';
        }
        
        if (generoSelectors) {
            // Agregar event listeners a los checkboxes
            const checkboxes = generoSelectors.querySelectorAll('.talla-genero-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox._handler = function() {
                    actualizarBotonesPorGenero(container);
                };
                checkbox.addEventListener('change', checkbox._handler);
            });
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
    const generoSelectors = container.querySelector('.talla-genero-selectores');
    
    botonesDiv.innerHTML = '';
    
    // Eliminar pestañas anteriores si existen
    const tabsAnteriores = container.querySelector('.tabs-genero-container');
    if (tabsAnteriores) {
        tabsAnteriores.remove();
    }
    
    // Obtener géneros seleccionados
    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
    
    if (modo === 'manual') {
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        if (checkboxesMarcados.length === 0) {
            // No hay géneros seleccionados
            tallaBotones.style.display = 'none';
            return;
        }
        
        if (checkboxesMarcados.length === 1) {
            // Solo un género seleccionado - comportamiento normal
            const genero = checkboxesMarcados[0].value;
            
            tallasLetras.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.dataset.genero = genero;
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
            
        } else {
            // Múltiples géneros seleccionados - crear pestañas
            const tabsContainer = document.createElement('div');
            tabsContainer.className = 'tabs-genero-container';
            tabsContainer.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #0066cc;';
            
            const tabsData = [];
            
            checkboxesMarcados.forEach((checkbox, index) => {
                const genero = checkbox.value;
                const esPrimero = index === 0;
                
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.textContent = genero === 'dama' ? 'DAMA' : 'CABALLERO';
                tab.style.cssText = `padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid ${esPrimero ? '#0066cc' : 'white'}; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;`;
                tab.className = 'tab-genero' + (esPrimero ? ' activo' : '');
                tab.dataset.genero = genero;
                
                tabsContainer.appendChild(tab);
                tabsData.push({ tab, genero });
                
                // Event listener para la pestaña
                tab.onclick = function(e) {
                    e.preventDefault();
                    
                    // Actualizar estilos de pestañas
                    tabsData.forEach(({ tab: t }) => {
                        t.style.borderBottom = '3px solid white';
                        t.classList.remove('activo');
                    });
                    tab.style.borderBottom = '3px solid #0066cc';
                    tab.classList.add('activo');
                    
                    // Actualizar botones de tallas
                    botonesDiv.innerHTML = '';
                    
                    tallasLetras.forEach(talla => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = talla;
                        btn.className = 'talla-btn';
                        btn.dataset.talla = talla;
                        btn.dataset.genero = genero;
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
            });
            
            // Insertar pestañas antes de los botones
            botonesDiv.parentElement.insertBefore(tabsContainer, botonesDiv);
            
            // Mostrar botones del primer género por defecto
            tabsData[0].tab.click();
        }
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
        

        asegurarTabsGeneroRango(container);
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

    
    const generoSelectors = container.querySelector('.talla-genero-selectores');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    
    // Obtener géneros seleccionados
    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');

    const obtenerGeneroActivoRango = () => {
        const activo = (container.dataset.generoActivoRango || '').trim();
        if (activo === 'dama' || activo === 'caballero') return activo;
        if (checkboxesMarcados.length > 0) return checkboxesMarcados[0].value;
        return '';
    };

    const generoActivo = obtenerGeneroActivoRango();
    let tallas = [];
    if (generoActivo === 'dama') {
        tallas = tallasDama.slice();
    } else if (generoActivo === 'caballero') {
        tallas = tallasCaballero.slice();
    } else {
        tallas = [];
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
    const generoSelectors = container.querySelector('.talla-genero-selectores');
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
        const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');

        const generoActivo = (container.dataset.generoActivoRango || '').trim() || (checkboxesMarcados.length > 0 ? checkboxesMarcados[0].value : '');
        const tallasGenero = generoActivo === 'dama' ? tallasDama : generoActivo === 'caballero' ? tallasCaballero : [];
        tallas = tallasGenero.slice();
    }
    
    const desdeIdx = tallas.indexOf(desde);
    const hastaIdx = tallas.indexOf(hasta);
    
    if (desdeIdx === -1 || hastaIdx === -1 || desdeIdx > hastaIdx) {
        alert('Rango inválido');
        return;
    }
    
    const tallasRango = tallas.slice(desdeIdx, hastaIdx + 1);
    
    // Obtener tallas existentes por género
    let tallasExistentes = {};
    tallasAgregadas.querySelectorAll('.grupo-genero-tallas').forEach(grupo => {
        const genero = grupo.dataset.genero;
        const tallas = [];
        grupo.querySelectorAll('.talla-item').forEach(item => {
            tallas.push(item.dataset.talla);
        });
        tallasExistentes[genero] = tallas;
    });
    
    // Determinar género(s) para las tallas de rango
    if (esLetra) {
        const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
        const generoActivo = (container.dataset.generoActivoLetras || container.dataset.generoActivoRango || '').trim() || (checkboxesMarcados.length > 0 ? checkboxesMarcados[0].value : '');
        if (generoActivo) {
            if (!tallasExistentes[generoActivo]) tallasExistentes[generoActivo] = [];
            tallasRango.forEach(talla => {
                if (!tallasExistentes[generoActivo].includes(talla)) {
                    tallasExistentes[generoActivo].push(talla);
                }
            });
        }
    } else {
        const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
        const generoActivo = (container.dataset.generoActivoRango || '').trim() || (checkboxesMarcados.length > 0 ? checkboxesMarcados[0].value : '');
        if (generoActivo) {
            if (!tallasExistentes[generoActivo]) tallasExistentes[generoActivo] = [];
            tallasRango.forEach(talla => {
                if (!tallasExistentes[generoActivo].includes(talla)) {
                    tallasExistentes[generoActivo].push(talla);
                }
            });
        }
    }
    
    // Limpiar y reconstruir la vista
    tallasAgregadas.innerHTML = '';
    
    // Crear secciones por género
    Object.keys(tallasExistentes).forEach(genero => {
        if (tallasExistentes[genero].length > 0) {
            const grupoDiv = document.createElement('div');
            grupoDiv.className = 'grupo-genero-tallas';
            grupoDiv.dataset.genero = genero;
            grupoDiv.style.cssText = 'margin-bottom: 1rem;';
            
            // Título del género
            const titulo = document.createElement('div');
            titulo.style.cssText = 'font-weight: 600; color: #0066cc; margin-bottom: 0.5rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;';
            titulo.innerHTML = genero === 'dama' ? 'Tallas Dama:' : genero === 'caballero' ? 'Tallas Caballero:' : 'Tallas:';
            grupoDiv.appendChild(titulo);
            
            // Contenedor de tallas de este género
            const tallasGeneroDiv = document.createElement('div');
            tallasGeneroDiv.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
            
            // Agregar tallas de este género
            tallasExistentes[genero].forEach(talla => {
                const tag = document.createElement('div');
                tag.className = 'talla-item';
                tag.dataset.talla = talla;
                tag.dataset.genero = genero;
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('.talla-item').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                tallasGeneroDiv.appendChild(tag);
            });
            
            grupoDiv.appendChild(tallasGeneroDiv);
            tallasAgregadas.appendChild(grupoDiv);
        }
    });
    
    tallasSection.style.display = 'block';
    actualizarTallasHidden(container);

    // Si hay múltiples géneros seleccionados, avanzar al siguiente tab para facilitar
    // la captura del rango del segundo género (p.ej. Dama -> Caballero).
    const generoSelectorsTabs = container.querySelector('.talla-genero-selectores');
    if (generoSelectorsTabs) {
        const checkboxesMarcadosTabs = generoSelectorsTabs.querySelectorAll('.talla-genero-checkbox:checked');
        if (checkboxesMarcadosTabs.length > 1) {
            const generoActivo = (container.dataset.generoActivoRango || '').trim();
            const tabs = Array.from(container.querySelectorAll('.tabs-genero-container .tab-genero'));
            const idx = tabs.findIndex(t => t.dataset.genero === generoActivo);
            if (idx !== -1 && tabs.length > 1) {
                const siguiente = tabs[(idx + 1) % tabs.length];
                if (siguiente) {
                    siguiente.click();
                }
            }
        }
    }
}

/**
 * Actualiza los botones según los géneros seleccionados
 */
function actualizarBotonesPorGenero(container) {
    
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const generoSelectors = container.querySelector('.talla-genero-selectores');
    
    // LIMPIAR COMPLETAMENTE ANTES DE CAMBIAR
    botonesDiv.innerHTML = '';
    
    // Eliminar pestañas anteriores si existen
    const tabsAnteriores = container.querySelector('.tabs-genero-container');
    if (tabsAnteriores) {
        tabsAnteriores.remove();
    }
    
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
    
    // Obtener géneros seleccionados
    const checkboxesMarcados = generoSelectors.querySelectorAll('.talla-genero-checkbox:checked');
    
    if (checkboxesMarcados.length === 0) {
        // No hay géneros seleccionados
        tallaBotones.style.display = 'none';
        return;
    }
    
    if (checkboxesMarcados.length === 1) {
        // Solo un género seleccionado - comportamiento normal
        const genero = checkboxesMarcados[0].value;
        
        tallaBotones.style.display = 'block';
        const tallas = genero === 'dama' ? tallasDama : tallasCaballero;
        
        tallas.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.dataset.genero = genero;
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
        
        // Agregar event listener para modo
        modoSelect._handler = function() {
            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
        
    } else {
        // Múltiples géneros seleccionados - crear pestañas
        const tabsContainer = document.createElement('div');
        tabsContainer.className = 'tabs-genero-container';
        tabsContainer.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #0066cc;';
        
        const tabsData = [];
        
        checkboxesMarcados.forEach((checkbox, index) => {
            const genero = checkbox.value;
            const esPrimero = index === 0;
            
            const tab = document.createElement('button');
            tab.type = 'button';
            tab.textContent = genero === 'dama' ? 'DAMA' : 'CABALLERO';
            tab.style.cssText = `padding: 8px 16px; background: white; color: #0066cc; border: none; border-bottom: 3px solid ${esPrimero ? '#0066cc' : 'white'}; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;`;
            tab.className = 'tab-genero' + (esPrimero ? ' activo' : '');
            tab.dataset.genero = genero;
            
            tabsContainer.appendChild(tab);
            tabsData.push({ tab, genero });
            
            // Event listener para la pestaña
            tab.onclick = function(e) {
                e.preventDefault();
                
                // Actualizar estilos de pestañas
                tabsData.forEach(({ tab: t }) => {
                    t.style.borderBottom = '3px solid white';
                    t.classList.remove('activo');
                });
                tab.style.borderBottom = '3px solid #0066cc';
                tab.classList.add('activo');
                
                // Actualizar botones de tallas
                botonesDiv.innerHTML = '';
                const tallas = genero === 'dama' ? tallasDama : tallasCaballero;
                
                tallas.forEach(talla => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = talla;
                    btn.className = 'talla-btn';
                    btn.dataset.talla = talla;
                    btn.dataset.genero = genero;
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
        });
        
        // Insertar pestañas antes de los botones
        botonesDiv.parentElement.insertBefore(tabsContainer, botonesDiv);
        
        // Mostrar botones del primer género por defecto
        tallaBotones.style.display = 'block';
        tabsData[0].tab.click();
        
        // Agregar event listener para modo
        modoSelect._handler = function() {
            actualizarModoTallas(this);
        };
        modoSelect.addEventListener('change', modoSelect._handler);
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
 * Agrega las tallas seleccionadas manteniendo separación por género
 */
function agregarTallasSeleccionadas(btn) {
    console.log('🔘 Botón agregar tallas seleccionadas presionado');
    
    const container = btn.closest('.producto-section');
    const productoCard = btn.closest('.producto-card'); // Buscar en toda la tarjeta
    console.log(' Container encontrado:', !!container);
    console.log('🃏 Producto card encontrado:', !!productoCard);
    
    const botonesActivos = container.querySelectorAll('.talla-btn.activo');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    const tallasHidden = productoCard.querySelector('.tallas-hidden'); // Buscar en toda la tarjeta
    
    console.log(' Elementos encontrados:');
    console.log('  - tallas-agregadas:', !!tallasAgregadas);
    console.log('  - tallas-section:', !!tallasSection);
    console.log('  - tallas-hidden (en producto-card):', !!tallasHidden, tallasHidden);
    
    console.log(' Botones activos encontrados:', botonesActivos.length);
    botonesActivos.forEach(boton => {
        console.log('  - Talla activa:', boton.dataset.talla, 'Género:', boton.dataset.genero);
    });
    
    if (botonesActivos.length === 0) {
        alert('Por favor selecciona al menos una talla');
        return;
    }
    
    // Obtener tallas existentes por género
    let tallasExistentes = {};
    tallasAgregadas.querySelectorAll('.grupo-genero-tallas').forEach(grupo => {
        const genero = grupo.dataset.genero;
        const tallas = [];
        grupo.querySelectorAll('.talla-item').forEach(item => {
            tallas.push(item.dataset.talla);
        });
        tallasExistentes[genero] = tallas;
    });
    
    // Agrupar tallas nuevas por género
    const tallasNuevasPorGenero = {};
    botonesActivos.forEach(boton => {
        const talla = boton.dataset.talla;
        const genero = boton.dataset.genero || 'general';
        
        if (!tallasNuevasPorGenero[genero]) {
            tallasNuevasPorGenero[genero] = [];
        }
        tallasNuevasPorGenero[genero].push(talla);
    });
    
    // Combinar tallas existentes con nuevas (sin duplicados)
    Object.keys(tallasNuevasPorGenero).forEach(genero => {
        if (!tallasExistentes[genero]) {
            tallasExistentes[genero] = [];
        }
        tallasNuevasPorGenero[genero].forEach(talla => {
            if (!tallasExistentes[genero].includes(talla)) {
                tallasExistentes[genero].push(talla);
            }
        });
    });
    
    // Limpiar y reconstruir la vista
    tallasAgregadas.innerHTML = '';
    
    // Crear secciones por género
    Object.keys(tallasExistentes).forEach(genero => {
        if (tallasExistentes[genero].length > 0) {
            const grupoDiv = document.createElement('div');
            grupoDiv.className = 'grupo-genero-tallas';
            grupoDiv.dataset.genero = genero;
            grupoDiv.style.cssText = 'margin-bottom: 1rem;';
            
            // Título del género
            const titulo = document.createElement('div');
            titulo.style.cssText = 'font-weight: 600; color: #0066cc; margin-bottom: 0.5rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;';
            titulo.innerHTML = genero === 'dama' ? 'Tallas Dama:' : genero === 'caballero' ? 'Tallas Caballero:' : 'Tallas:';
            grupoDiv.appendChild(titulo);
            
            // Contenedor de tallas de este género
            const tallasGeneroDiv = document.createElement('div');
            tallasGeneroDiv.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
            
            // Agregar tallas de este género
            tallasExistentes[genero].forEach(talla => {
                const tag = document.createElement('div');
                tag.className = 'talla-item';
                tag.dataset.talla = talla;
                tag.dataset.genero = genero;
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('.talla-item').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                tallasGeneroDiv.appendChild(tag);
            });
            
            grupoDiv.appendChild(tallasGeneroDiv);
            tallasAgregadas.appendChild(grupoDiv);
        }
    });
    
    tallasSection.style.display = 'block';
    actualizarTallasHidden(container);
    
    // Limpiar botones activos
    botonesActivos.forEach(boton => {
        boton.classList.remove('activo');
        boton.style.background = 'white';
        boton.style.color = '#0066cc';
    });
}

/**
 * Actualiza el campo hidden con las tallas seleccionadas por género
 */
function actualizarTallasHidden(container) {
    console.log(' Actualizando campo hidden de tallas');
    console.log(' Container:', container);
    
    if (!container) {
        console.warn(' Container no encontrado');
        return;
    }
    
    // Buscar el campo hidden en toda la tarjeta de producto
    const productoCard = container.closest('.producto-card');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    
    // Si el campo hidden no existe, crearlo dinámicamente
    let tallasHidden = productoCard ? productoCard.querySelector('.tallas-hidden') : null;
    if (!tallasHidden && productoCard) {
        console.log(' Creando campo hidden dinámicamente');
        tallasHidden = document.createElement('input');
        tallasHidden.type = 'hidden';
        tallasHidden.name = 'productos_friendly[][tallas]';
        tallasHidden.className = 'tallas-hidden';
        tallasHidden.value = '';
        
        // Agregarlo DESPUÉS de tallas-agregadas para que no se elimine
        if (tallasAgregadas) {
            tallasAgregadas.parentNode.insertBefore(tallasHidden, tallasAgregadas.nextSibling);
        } else {
            productoCard.appendChild(tallasHidden);
        }
    }
    
    console.log(' Elementos encontrados:');
    console.log('  - tallas-agregadas:', !!tallasAgregadas);
    console.log('🃏 Producto card:', !!productoCard);
    console.log('  - tallas-hidden:', !!tallasHidden, tallasHidden);
    
    if (!tallasAgregadas || !tallasHidden) {
        console.warn(' No se encontraron elementos de tallas');
        return;
    }
    
    const tallasPorGenero = {};
    
    // Recopilar tallas por género
    tallasAgregadas.querySelectorAll('.grupo-genero-tallas').forEach(grupo => {
        const genero = grupo.dataset.genero;
        const tallas = [];
        grupo.querySelectorAll('.talla-item').forEach(item => {
            tallas.push(item.dataset.talla);
        });
        if (tallas.length > 0) {
            tallasPorGenero[genero] = tallas;
        }
    });
    
    // Formatear como JSON para mantener estructura por género
    tallasHidden.value = JSON.stringify(tallasPorGenero);
    console.log(' Tallas guardadas por género:', tallasPorGenero);
    console.log(' Valor del campo hidden:', tallasHidden.value);
}

