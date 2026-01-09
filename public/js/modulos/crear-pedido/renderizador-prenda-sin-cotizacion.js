/**
 * RENDERIZADOR DE PRENDAS SIN COTIZACIÓN - Tipo PRENDA
 * 
 * Maneja toda la lógica de renderización HTML para prendas de tipo PRENDA
 * cuando no hay cotización previa.
 * 
 * RESPONSABILIDADES:
 * - Renderizar tarjetas de prenda con todos los campos
 * - Renderizar secciones de tallas
 * - Renderizar secciones de variaciones
 * - Renderizar secciones de telas
 * - Renderizar galerías de fotos
 * - Gestionar interacciones de usuario
 * - Sincronizar datos entre UI y gestor
 */

// =====================================================
// HELPERS PARA AGREGAR TALLAS POR GÉNERO CON FLUJO INTERACTIVO
// =====================================================

/**
 * Obtener el tipo de talla del otro género (si existe)
 */
window.obtenerTipoTallaDelOtroGenero = function(prendaIndex, generoActual) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return null;
    
    const otroGenero = generoActual === 'dama' ? 'caballero' : 'dama';
    
    // Buscar si hay tallas del otro género con tipo LETRA
    const tallasLetra = prendaCard.querySelectorAll(
        `.talla-cantidad-genero[data-prenda="${prendaIndex}"][data-genero="${otroGenero}"][data-tipo-talla="letra"]`
    );
    if (tallasLetra.length > 0) return 'letra';
    
    // Buscar si hay tallas del otro género con tipo NÚMERO
    const tallasNumero = prendaCard.querySelectorAll(
        `.talla-cantidad-genero[data-prenda="${prendaIndex}"][data-genero="${otroGenero}"][data-tipo-talla="numero"]`
    );
    if (tallasNumero.length > 0) return 'numero';
    
    return null;
};

/**
 * Agregar talla(s) a un género - Flujo interactivo
 * Paso 1: Elegir tipo de talla (LETRA o NÚMERO)
 * Paso 2: Elegir método (MANUAL o RANGO)
 * Paso 3: Seleccionar tallas
 * 
 * RESTRICCIÓN: Si el otro género ya tiene tallas, debe ser del mismo tipo
 */
window.agregarTallaParaGenero = function(prendaIndex, genero) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    // Verificar qué tipo de talla usa el otro género (si es que tiene)
    const tipoDelOtroGenero = obtenerTipoTallaDelOtroGenero(prendaIndex, genero);
    const otroGenero = genero === 'dama' ? 'caballero' : 'dama';
    
    if (tipoDelOtroGenero) {
        // Si el otro género ya tiene tallas, forzar el mismo tipo
        const tipoLabel = tipoDelOtroGenero === 'letra' ? 'LETRA' : 'NÚMERO';
        Swal.fire({
            icon: 'info',
            title: 'Tipo de Talla Definido',
            html: `
                <p style="margin: 0 0 1rem 0;">El género <strong>${otroGenero.charAt(0).toUpperCase() + otroGenero.slice(1)}</strong> ya usa tallas por <strong>${tipoLabel}</strong>.</p>
                <p style="margin: 0; color: #666;">Este género también debe usar el mismo tipo.</p>
            `
        }).then(() => {
            agregarTallasPorMetodo(prendaIndex, genero, tipoDelOtroGenero);
        });
        return;
    }
    
    // Paso 1: Seleccionar tipo de talla (si no hay restricción)
    Swal.fire({
        title: 'Tipo de Talla',
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-letra" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-font" style="color: #0066cc;"></i></div>
                    <div>LETRA</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">XS, S, M, L, XL...</div>
                </button>
                <button type="button" id="btn-numero" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-hashtag" style="color: #0066cc;"></i></div>
                    <div>NÚMERO</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">6, 8, 10, 12...</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-letra').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodo(prendaIndex, genero, 'letra');
            });
            document.getElementById('btn-numero').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodo(prendaIndex, genero, 'numero');
            });
        }
    });
};

/**
 * Paso 2 y 3: Seleccionar método (MANUAL o RANGO) y luego las tallas
 */
window.agregarTallasPorMetodo = function(prendaIndex, genero, tipoTalla) {
    // Definir tallas disponibles según TIPO y GÉNERO
    // LETRA: ambos géneros usan lo mismo
    // NÚMERO: diferentes números para cada género
    const tallasLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46'];
    
    let tallasPorTipo;
    if (tipoTalla === 'letra') {
        tallasPorTipo = tallasLetra;
    } else {
        // Para números, usar diferentes según género
        tallasPorTipo = (genero === 'dama') ? tallasDama : tallasCaballero;
    }
    
    // Obtener tallas ya agregadas
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    const tallasActuales = Array.from(prendaCard.querySelectorAll(`.talla-cantidad-genero[data-prenda="${prendaIndex}"][data-genero="${genero}"]`))
        .map(input => input.dataset.talla);
    
    const tallasDisponibles = tallasPorTipo.filter(talla => !tallasActuales.includes(talla));
    
    if (tallasDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin tallas disponibles',
            text: `Ya tienes todas las tallas de ${tipoTalla === 'letra' ? 'LETRA' : 'NÚMERO'} agregadas`
        });
        return;
    }
    
    // Paso 2: Seleccionar método (MANUAL o RANGO) - Ambos tipos tienen esta opción
    Swal.fire({
        title: 'Método de Selección',
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-manual" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-hand-pointer" style="color: #0066cc;"></i></div>
                    <div>MANUAL</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Una por una</div>
                </button>
                <button type="button" id="btn-rango" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-sliders-h" style="color: #0066cc;"></i></div>
                    <div>RANGO</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Desde... hasta</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-manual').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasManual(prendaIndex, genero, tallasDisponibles, tipoTalla);
            });
            document.getElementById('btn-rango').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasRango(prendaIndex, genero, tallasPorTipo, tallasActuales, tipoTalla);
            });
        }
    });
};

/**
 * Paso 3A: Selección MANUAL (una por una)
 */
window.seleccionarTallasManual = function(prendaIndex, genero, tallasDisponibles, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    
    Swal.fire({
        title: `Agregar Tallas - ${generoLabel} (MANUAL)`,
        html: `
            <div style="max-height: 400px; overflow-y: auto; padding: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 0.5rem;">
                    ${tallasDisponibles.map(talla => `
                        <button type="button" class="btn-talla-manual" data-talla="${talla}" 
                                style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">
                            ${talla}
                        </button>
                    `).join('')}
                </div>
            </div>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="font-size: 0.85rem; color: #666; font-weight: 500;">Tallas seleccionadas: <span id="contador-tallas">0</span></div>
                <div id="lista-tallas-seleccionadas" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const tallasSeleccionadas = new Set();
            
            document.querySelectorAll('.btn-talla-manual').forEach(btn => {
                btn.addEventListener('click', function() {
                    const talla = this.dataset.talla;
                    
                    if (tallasSeleccionadas.has(talla)) {
                        tallasSeleccionadas.delete(talla);
                        this.style.background = 'white';
                        this.style.borderColor = '#e5e7eb';
                        this.classList.remove('btn-talla-seleccionada');
                    } else {
                        tallasSeleccionadas.add(talla);
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                        this.style.borderColor = '#0066cc';
                        this.classList.add('btn-talla-seleccionada');
                    }
                    
                    // Actualizar contador y lista
                    document.getElementById('contador-tallas').textContent = tallasSeleccionadas.size;
                    document.getElementById('lista-tallas-seleccionadas').innerHTML = 
                        Array.from(tallasSeleccionadas).map(t => `<span style="background: #e3f2fd; color: #0066cc; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">${t}</span>`).join('');
                });
            });
        },
        preConfirm: () => {
            const contador = parseInt(document.getElementById('contador-tallas').textContent);
            if (contador === 0) {
                Swal.showValidationMessage('Selecciona al menos una talla');
                return false;
            }
            return Array.from(document.querySelectorAll('.btn-talla-manual.btn-talla-seleccionada')).map(btn => btn.dataset.talla);
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            agregarTallasAlGenero(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Paso 3B: Selección por RANGO (desde... hasta)
 */
window.seleccionarTallasRango = function(prendaIndex, genero, todasLasTallas, tallasActuales, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    const tallasDisponibles = todasLasTallas.filter(t => !tallasActuales.includes(t));
    
    Swal.fire({
        title: `Agregar Tallas por Rango - ${generoLabel}`,
        html: `
            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Desde:</label>
                    <select id="talla-inicio" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Hasta:</label>
                    <select id="talla-fin" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div style="background: #f0f7ff; padding: 0.75rem; border-radius: 4px; font-size: 0.85rem; color: #1e3a8a; font-weight: 500;">
                    Tallas a agregar: <span id="preview-rango">0</span>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const selectInicio = document.getElementById('talla-inicio');
            const selectFin = document.getElementById('talla-fin');
            const preview = document.getElementById('preview-rango');
            
            const actualizarPreview = () => {
                const inicio = selectInicio.value;
                const fin = selectFin.value;
                
                if (inicio && fin) {
                    const idxInicio = todasLasTallas.indexOf(inicio);
                    const idxFin = todasLasTallas.indexOf(fin);
                    
                    if (idxInicio >= 0 && idxFin >= 0) {
                        const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
                        const rango = todasLasTallas.slice(min, max + 1);
                        preview.textContent = rango.filter(t => !tallasActuales.includes(t)).length;
                    }
                } else {
                    preview.textContent = '0';
                }
            };
            
            selectInicio.addEventListener('change', actualizarPreview);
            selectFin.addEventListener('change', actualizarPreview);
        },
        preConfirm: () => {
            const inicio = document.getElementById('talla-inicio').value;
            const fin = document.getElementById('talla-fin').value;
            
            if (!inicio || !fin) {
                Swal.showValidationMessage('Selecciona talla inicial y final');
                return false;
            }
            
            const idxInicio = todasLasTallas.indexOf(inicio);
            const idxFin = todasLasTallas.indexOf(fin);
            const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
            const rango = todasLasTallas.slice(min, max + 1);
            
            return rango.filter(t => !tallasActuales.includes(t));
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.length > 0) {
            agregarTallasAlGenero(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Agregar tallas al género (después de seleccionarlas)
 */
window.agregarTallasAlGenero = function(prendaIndex, genero, tallas, tipoTalla) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    // Crear inputs para cada talla (solo si no existen)
    tallas.forEach(talla => {
        // ✅ Verificar si ya existe una talla con este valor (buscar en ambas clases)
        const existente = prendaCard.querySelector(
            `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"],
            .talla-cantidad-genero-hidden[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`
        );
        if (existente) {
            console.warn(`⚠️ Talla ${talla} ya existe para ${genero}`);
            return; // Saltar si ya existe
        }
        
        const inputTalla = document.createElement('input');
        inputTalla.type = 'hidden';
        inputTalla.name = `cantidades_genero[${prendaIndex}][${genero}][${talla}]`;
        // ✅ USAR CLASE CONSISTENTE: talla-cantidad-genero-editable
        inputTalla.className = 'talla-cantidad-genero-editable';
        inputTalla.value = '0';
        inputTalla.dataset.talla = talla;
        inputTalla.dataset.genero = genero;
        inputTalla.dataset.prenda = prendaIndex;
        inputTalla.dataset.tipoTalla = tipoTalla;  // Guardar el tipo de talla
        
        prendaCard.appendChild(inputTalla);
        
        console.log(`✅ Input creado para ${genero} ${talla}`);
        
        // ✅ CRÍTICO: Agregar talla al gestor para que aparezca en validación
        if (window.gestorPrendaSinCotizacion) {
            window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
        }
    });
    
    // Re-renderizar la sección del género
    renderizarTallasDelGenero(prendaIndex, genero);
    
    Swal.fire({
        icon: 'success',
        title: 'Tallas agregadas',
        text: `Se agregaron ${tallas.length} talla(s) a ${genero}`,
        timer: 1500,
        showConfirmButton: false
    });
};

/**
 * Sincronizar datos de UI con el gestor ANTES de renderizar
 * CRÍTICO: Se debe llamar ANTES de renderizar para no perder datos
 */
function sincronizarDatosAntesDERenderizar() {
    if (!window.gestorPrendaSinCotizacion) return;

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendas.forEach((prenda, prendaIndex) => {
        // Sincronizar nombre, descripción y género
        const inputNombre = document.querySelector(`.prenda-nombre[data-prenda="${prendaIndex}"]`);
        const inputDesc = document.querySelector(`.prenda-descripcion[data-prenda="${prendaIndex}"]`);
        const selectGenero = document.querySelector(`.prenda-genero[data-prenda="${prendaIndex}"]`);
        
        if (inputNombre && inputNombre.value) {
            prenda.nombre_producto = inputNombre.value;
        }
        if (inputDesc && inputDesc.value) {
            prenda.descripcion = inputDesc.value;
        }
        if (selectGenero && selectGenero.value) {
            prenda.genero = selectGenero.value;
        }
        
        // ✅ SINCRONIZAR TALLAS POR GÉNERO (NUEVO - para no perder las tallas cuando hay error)
        document.querySelectorAll(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"]`).forEach(input => {
            const genero = input.dataset.genero;
            const talla = input.dataset.talla;
            const cantidad = parseInt(input.value) || 0;
            
            // Asegurar que la estructura existe
            if (!prenda.generosConTallas) {
                prenda.generosConTallas = {};
            }
            if (!prenda.generosConTallas[genero]) {
                prenda.generosConTallas[genero] = {};
            }
            
            // Actualizar la cantidad
            prenda.generosConTallas[genero][talla] = cantidad;
            console.log(`✅ Sincronizado: Prenda ${prendaIndex}, ${genero} ${talla}: ${cantidad}`);
        });
        
        // Sincronizar cantidades de tallas (estructura antigua - para compatibilidad)
        document.querySelectorAll(`.talla-cantidad[data-prenda="${prendaIndex}"]`).forEach(input => {
            const talla = input.dataset.talla;
            const cantidad = parseInt(input.value) || 0;
            if (prenda.cantidadesPorTalla) {
                prenda.cantidadesPorTalla[talla] = cantidad;
            }
        });

        // Sincronizar datos de telas
        document.querySelectorAll(`[data-prenda-index="${prendaIndex}"] [data-tela-index]`).forEach(row => {
            const telaIdx = parseInt(row.dataset.telaIndex);
            const inputNombreTela = row.querySelector('.tela-nombre');
            const inputColor = row.querySelector('.tela-color');
            const inputReferencia = row.querySelector('.tela-referencia');
            
            if (prenda.variantes?.telas_multiples?.[telaIdx]) {
                if (inputNombreTela?.value) prenda.variantes.telas_multiples[telaIdx].nombre_tela = inputNombreTela.value;
                if (inputColor?.value) prenda.variantes.telas_multiples[telaIdx].color = inputColor.value;
                if (inputReferencia?.value) prenda.variantes.telas_multiples[telaIdx].referencia = inputReferencia.value;
            }
        });

        // Sincronizar variaciones
        document.querySelectorAll(`[data-prenda-index="${prendaIndex}"] [data-field]`).forEach(field => {
            const nombreCampo = field.dataset.field;
            if (nombreCampo && !nombreCampo.includes('_obs')) {
                let valor = field.value || field.textContent;
                
                // Convertir a booleano si es campo tipo checkbox
                if (nombreCampo.includes('tiene_')) {
                    valor = (valor === 'Sí' || valor === 'true' || valor === true);
                }
                
                if (prenda.variantes && nombreCampo in prenda.variantes) {
                    prenda.variantes[nombreCampo] = valor;
                    prenda[nombreCampo] = valor;
                }
            }
        });
    });

    logWithEmoji('🔄', 'Datos sincronizados antes de renderizar (incluyendo tallas por género)');
}

/**
 * Renderizar todas las prendas de tipo PRENDA sin cotización
 */
function renderizarPrendasTipoPrendaSinCotizacion() {
    const container = document.getElementById('prendas-container-editable');
    if (!container || !window.gestorPrendaSinCotizacion) return;

    // Actualizar título dinámico según el tipo de pedido
    const tituloPrendasDinamico = document.getElementById('titulo-prendas-dinamico');
    if (tituloPrendasDinamico) {
        // Detectar el tipo de pedido actual
        const tipoPedidoActual = window.gestorPrendaSinCotizacion.tipoPedidoActual || 'PRENDA';
        
        if (tipoPedidoActual.toUpperCase().includes('REFLECTIVO') || tipoPedidoActual.toUpperCase() === 'RF') {
            tituloPrendasDinamico.textContent = 'Prendas Reflectivo';
        } else if (tipoPedidoActual.toUpperCase() === 'PRENDA') {
            tituloPrendasDinamico.textContent = 'Prendas';
        } else {
            tituloPrendasDinamico.textContent = 'Prendas Técnicas del Logo';
        }
        console.log('✅ Título dinámico actualizado:', tituloPrendasDinamico.textContent);
    }

    // 🔴 CRÍTICO: Sincronizar datos ANTES de renderizar
    sincronizarDatosAntesDERenderizar();

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();

    if (prendas.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 2rem;">
                <p style="color: #6b7280; margin-bottom: 1rem;">No hay prendas agregadas.</p>
            </div>
        `;
        return;
    }

    let html = '';
    prendas.forEach((prenda, index) => {
        html += renderizarPrendaTipoPrenda(prenda, index);
    });

    html += `
        <div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        </div>
    `;

    container.innerHTML = html;

    // ✅ CRÍTICO: Recrear inputs ocultos de tallas por género después de renderizar
    // Esto asegura que siempre estén disponibles para sincronización
    recrearInputsOcultosDeGeneros(prendas);

    // Re-attach event listeners después de renderizar
    setTimeout(() => {
        attachPrendaTipoPrendaListeners(prendas);
        // ✅ Actualizar contenedores de tallas por género
        prendas.forEach((prenda, idx) => {
            if (Array.isArray(prenda.genero) && prenda.genero.length > 0) {
                actualizarContenedorTallasPorGenero(idx, prenda);
            }
        });
    }, 100);
}

/**
 * Renderizar una tarjeta individual de prenda tipo PRENDA
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de la tarjeta
 */
function renderizarPrendaTipoPrenda(prenda, index) {
    const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevas(index);
    const fotos = [...(prenda.fotos || []), ...fotosNuevas];
    const fotoPrincipal = fotos.length > 0 ? fotos[0] : null;
    const fotosAdicionales = fotos.length > 1 ? fotos.slice(1) : [];

    // HTML de fotos
    let fotosHtml = '';
    if (fotoPrincipal) {
        const fotoUrl = typeof fotoPrincipal === 'string' ? fotoPrincipal : (fotoPrincipal.url || fotoPrincipal.ruta_webp || fotoPrincipal.ruta_original || '');
        const restantes = fotosAdicionales.length;
        fotosHtml = `
            <div style="position: relative; width: 100%; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.75rem 0.75rem 0.6rem 0.75rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">Galería de la prenda</div>
                    <button type="button"
                            onclick="abrirModalAgregarFotosPrendaTipo(${index})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);">
                        ＋
                    </button>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <div style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #d0d0d0; background: white; flex-shrink: 0; position: relative;">
                        <img src="${fotoUrl}" alt="Foto de prenda"
                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" 
                             ondblclick="abrirGaleriaPrendaTipo(${index})" />
                        ${restantes > 0 ? `<span style="position: absolute; bottom: 6px; right: 6px; background: #1e40af; color: white; padding: 2px 6px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">+${restantes}</span>` : ''}
                        <button type="button" onclick="eliminarImagenPrendaTipo(this, ${index})"
                                style="position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem;">
                        <p style="margin: 0; font-size: 0.9rem; color: #1e3a8a; font-weight: 600;">Fotos agregadas: ${fotos.length}</p>
                        <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">Doble click en la imagen para ver galería</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        fotosHtml = `
            <div style="border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 1.5rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;"><i class="fas fa-image"></i></div>
                <p style="color: #1e3a8a; font-weight: 600; margin: 0 0 1rem 0;">Sin fotos de prenda</p>
                <button type="button" onclick="abrirModalAgregarFotosPrendaTipo(${index})"
                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-plus"></i> Agregar Foto
                </button>
            </div>
        `;
    }

    // HTML de tallas
    let tallasHtml = renderizarTallasPrendaTipo(prenda, index);

    // HTML de variaciones
    let variacionesHtml = renderizarVariacionesPrendaTipo(prenda, index);

    // HTML de telas
    let telasHtml = renderizarTelasPrendaTipo(prenda, index);

    return `
        <div class="prenda-card-editable" data-prenda-index="${index}" style="margin-bottom: 2rem;">
            <div class="prenda-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                <div class="prenda-title" style="font-weight: 700; font-size: 1.125rem; color: #333;">
                    Prenda ${index + 1}: ${prenda.nombre_producto || 'Sin nombre'}
                </div>
            </div>

            <!-- Contenido principal (2 columnas: Información + Fotos) -->
            <div class="prenda-content" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem; align-items: start; margin-bottom: 1.5rem;">
                <!-- Información de la prenda -->
                <div class="prenda-info-section" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;">Nombre del Producto:</label>
                        <input type="text" 
                               name="nombre_producto[${index}]" 
                               value="${prenda.nombre_producto || ''}"
                               class="prenda-nombre"
                               data-prenda="${index}" 
                               style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                    </div>

                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;">Descripción:</label>
                        <textarea name="descripcion[${index}]" 
                                  class="prenda-descripcion"
                                  data-prenda="${index}" 
                                  style="min-height: 110px; width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">${prenda.descripcion || ''}</textarea>
                    </div>

                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;"><i class="fas fa-venus"></i> Selecciona género(s):</label>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin: 0.5rem 0;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="dama" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer; width: 18px; height: 18px;" ${(Array.isArray(prenda.genero) && prenda.genero.includes('dama')) || prenda.genero === 'Dama' ? 'checked' : ''}>
                                <span style="font-size: 0.9rem; color: #374151; font-weight: 500;"><i class="fas fa-user"></i> Dama</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="caballero" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer; width: 18px; height: 18px;" ${(Array.isArray(prenda.genero) && prenda.genero.includes('caballero')) || prenda.genero === 'Caballero' ? 'checked' : ''}>
                                <span style="font-size: 0.9rem; color: #374151; font-weight: 500;"><i class="fas fa-user"></i> Caballero</span>
                            </label>
                        </div>
                        
                        <!-- CONTENEDOR DINÁMICO DE TALLAS POR GÉNERO -->
                        <div class="tallas-por-genero-container" style="margin-top: 1rem;">
                            <!-- Se llena dinámicamente cuando se selecciona un género -->
                        </div>
                    </div>
                </div>

                <!-- Fotos -->
                <div class="prenda-fotos-section" style="width: 100%;">
                    ${fotosHtml}
                </div>
            </div>

            <!-- Secciones de Detalles -->
            ${tallasHtml}
            ${variacionesHtml}
            <div data-section="telas">${telasHtml}</div>

            <!-- Sección De Bodega (Separada) -->
            ${renderizarBodegaPrendaTipo(prenda, index)}
        </div>
    `;
}

/**
 * Renderizar sección de tallas
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de tallas
 */
function renderizarTallasPrendaTipo(prenda, index) {
    let html = `
        <div class="tipo-prenda-row" data-prenda-index="${index}" style="margin-top: 1.5rem;">
            <!-- TALLAS AGREGADAS POR GÉNERO USANDO MODAL -->
            
            <!-- Contenedor para agregar tallas por género (modal) -->
            <div class="tallas-por-genero-container" style="margin-bottom: 1.5rem;"></div>
        </div>
    `;
    
    return html;
}

/**
 * Renderizar sección de variaciones (manga, broche, bolsillos, reflectivo)
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de variaciones
 */
function renderizarVariacionesPrendaTipo(prenda, index) {
    const variantes = prenda.variantes || {};
    const variacionesArray = [];

    // Manga
    if (variantes.tipo_manga !== undefined) {
        variacionesArray.push({
            tipo: 'Manga',
            valor: variantes.tipo_manga || '',
            obs: variantes.obs_manga || '',
            campo: 'tipo_manga',
            esCheckbox: false,
            opciones: ['No aplica', 'Corta', 'Larga']
        });
    }

    // Broche/Botón
    variacionesArray.push({
        tipo: 'Broche/Botón',
        valor: variantes.tipo_broche || '',
        obs: variantes.obs_broche || '',
        campo: 'tipo_broche',
        esCheckbox: false,
        opciones: ['No aplica', 'Broche', 'Botón']
    });

    // Bolsillos
    if (variantes.tiene_bolsillos !== undefined) {
        variacionesArray.push({
            tipo: 'Bolsillos',
            valor: variantes.tiene_bolsillos ? 'Sí' : 'No',
            obs: variantes.obs_bolsillos || '',
            campo: 'tiene_bolsillos',
            esCheckbox: true
        });
    }

    // Reflectivo
    if (variantes.tiene_reflectivo !== undefined) {
        variacionesArray.push({
            tipo: 'Reflectivo',
            valor: variantes.tiene_reflectivo ? 'Sí' : 'No',
            obs: variantes.obs_reflectivo || '',
            campo: 'tiene_reflectivo',
            esCheckbox: true
        });
    }

    if (variacionesArray.length === 0) {
        return '';
    }

    let html = `
        <div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
            <div style="padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">
                <div>📋 Variaciones</div>
                <div style="text-align: center;">Valor</div>
                <div>Observaciones</div>
                <div style="text-align: center;">Acción</div>
            </div>
    `;

    variacionesArray.forEach((variacion, varIdx) => {
        let inputHtml = '';
        
        if (variacion.esCheckbox) {
            const isYes = variacion.valor === 'Sí' || variacion.valor === true;
            inputHtml = `
                <select data-field="${variacion.campo}" data-prenda="${index}" data-variacion="${varIdx}"
                        style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    <option value="No" ${!isYes ? 'selected' : ''}>No</option>
                    <option value="Sí" ${isYes ? 'selected' : ''}>Sí</option>
                </select>
            `;
        } else {
            let selectOptions = '<option value="">-- Seleccionar --</option>';
            const selectedValue = variacion.valor?.trim() || '';
            
            variacion.opciones?.forEach(opcion => {
                const isSelected = selectedValue === opcion ? 'selected' : '';
                selectOptions += `<option value="${opcion}" ${isSelected}>${opcion}</option>`;
            });
            
            if (selectedValue && !variacion.opciones.includes(selectedValue)) {
                selectOptions += `<option value="${selectedValue}" selected>${selectedValue}</option>`;
            }
            
            inputHtml = `
                <select data-field="${variacion.campo}" data-prenda="${index}" data-variacion="${varIdx}"
                        style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    ${selectOptions}
                </select>
            `;
        }

        html += `
            <div style="padding: 0.6rem 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">
                <div style="font-weight: 500; color: #1f2937;">${variacion.tipo}</div>
                <div style="text-align: center;">
                    ${inputHtml}
                </div>
                <div>
                    <textarea class="variacion-obs"
                              data-field="${variacion.campo}_obs"
                              data-prenda="${index}"
                              data-variacion="${varIdx}"
                              style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; min-height: 36px; resize: vertical; font-family: inherit; box-sizing: border-box;"
                              placeholder="...">${variacion.obs || ''}</textarea>
                </div>
                <div style="text-align: center;">
                    <button type="button" onclick="eliminarVariacionPrendaTipo(${index}, ${varIdx})"
                            class="btn-eliminar-variacion"
                            style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.5rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">
                        ✕
                    </button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
}

/**
 * Renderizar sección de telas
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de telas
 */
function renderizarTelasPrendaTipo(prenda, index) {
    const telas = prenda.variantes?.telas_multiples || [];

    if (!telas || telas.length === 0) {
        return `
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 6px; border-left: 4px solid #0066cc;">
                <div style="font-weight: 600; margin-bottom: 1rem; color: #333;">🎨 Telas y Colores</div>
                <p style="color: #999; margin: 0; margin-bottom: 1rem;">No hay telas agregadas. Haz clic en el botón para agregar.</p>
                <button type="button" onclick="agregarTelaPrendaTipo(${index})"
                        style="margin-bottom: 1rem; background: #0066cc; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Tela
                </button>
            </div>
        `;
    }

    let html = `
        <div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
            <div style="position: relative; padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; width: 100%;">
                <div>🎨 Telas</div>
                <div>Color</div>
                <div>Referencia</div>
                <div style="text-align: center;">Fotos</div>
                <button type="button" onclick="agregarTelaPrendaTipo(${index})"
                        style="position: absolute; top: 10px; right: 12px; background: white; color: #0052a3; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-weight: 900; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.18);">
                    ＋
                </button>
            </div>
    `;

    telas.forEach((tela, telaIdx) => {
        const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevasTela(index, telaIdx);
        const fotosTelaJSON = prenda.telaFotos?.filter(f => f.tela_id === tela.id) || [];
        const fotosDeTela = [...fotosTelaJSON, ...fotosNuevas];
        const fotoPrincipal = fotosDeTela.length > 0 ? fotosDeTela[0] : null;
        const restantes = Math.max(0, fotosDeTela.length - 1);

        let fotosTelaHtml = '';
        if (fotoPrincipal) {
            const fotoUrl = typeof fotoPrincipal === 'string' ? fotoPrincipal : (fotoPrincipal.url || fotoPrincipal.ruta_webp || '');
            fotosTelaHtml = `
                <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column; align-items: center; gap: 0.4rem;">
                    <div style="position: relative; width: 90px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #d0d0d0; background: white;">
                        ${fotoUrl ? `<img src="${fotoUrl}" alt="Foto de tela" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" ondblclick="abrirGaleriaTexturaTipo(${index}, ${telaIdx})">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Sin foto</div>'}
                        ${restantes > 0 ? `<span style="position:absolute; bottom:6px; right:6px; background:#1e40af; color:white; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:700;">+${restantes}</span>` : ''}
                        <button type="button" onclick="eliminarImagenTelaTipo(this, ${index}, ${telaIdx})"
                                style="position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                    </div>
                    <button type="button" onclick="abrirModalAgregarFotosTelaType(${index}, ${telaIdx})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);">＋</button>
                </div>
            `;
        } else {
            fotosTelaHtml = `
                <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                    <div style="font-size: 0.8rem; color: #1e3a8a; font-weight: 600; text-align: center;">Sin fotos</div>
                    <button type="button" onclick="abrirModalAgregarFotosTelaType(${index}, ${telaIdx})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);">＋</button>
                </div>
            `;
        }

        const nombreTela = tela.nombre_tela || '';
        const colorTela = typeof tela.color === 'object' ? (tela.color?.nombre || '') : (tela.color || '');
        const referencia = tela.referencia || '';

        html += `
            <div style="padding: 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; width: 100%;" data-tela-index="${telaIdx}">
                <input type="text" value="${nombreTela}" placeholder="Nombre de tela"
                       class="tela-nombre"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <input type="text" value="${colorTela}" placeholder="Color"
                       class="tela-color"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <input type="text" value="${referencia}" placeholder="Referencia"
                       class="tela-referencia"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    ${fotosTelaHtml}
                    <button type="button" onclick="eliminarTelaPrendaTipo(${index}, ${telaIdx})"
                            class="btn-eliminar-tela"
                            style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; width: 100%;">
                        ✕ Quitar
                    </button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    
    return html;
}

/**
 * Adjuntar event listeners a los elementos renderizados
 * @param {Array} prendas - Prendas a monitorear
 */
function attachPrendaTipoPrendaListeners(prendas) {
    // Listeners para cambios en nombre de producto
    document.querySelectorAll('.prenda-nombre').forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const nuevoNombre = e.target.value;
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.nombre_producto = nuevoNombre;
            }
        });
    });

    // Listeners para cambios en descripción
    document.querySelectorAll('.prenda-descripcion').forEach(textarea => {
        textarea.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.descripcion = e.target.value;
            }
        });
    });

    // Listeners para cambios en género (CHECKBOXES MÚLTIPLES)
    document.querySelectorAll('.genero-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const prendaIndex = parseInt(e.target.dataset.prenda);
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
            if (!prenda) return;

            // Obtener todos los géneros seleccionados para esta prenda
            const prendaCard = e.target.closest('.prenda-card-editable');
            const generosCheckboxes = prendaCard.querySelectorAll('.genero-checkbox:checked');
            const generosSeleccionados = Array.from(generosCheckboxes).map(cb => cb.value);
            
            // Actualizar prenda
            prenda.genero = generosSeleccionados;
            
            // Inicializar estructura generosConTallas si no existe
            if (!prenda.generosConTallas || Object.keys(prenda.generosConTallas).length === 0) {
                prenda.generosConTallas = {};
            }
            
            // Agregar los nuevos géneros seleccionados a la estructura
            generosSeleccionados.forEach(genero => {
                if (!prenda.generosConTallas[genero]) {
                    prenda.generosConTallas[genero] = {};
                }
            });
            
            // Remover géneros deseleccionados
            Object.keys(prenda.generosConTallas).forEach(genero => {
                if (!generosSeleccionados.includes(genero)) {
                    delete prenda.generosConTallas[genero];
                }
            });
            
            logWithEmoji('✅', `Géneros actualizados para prenda ${prendaIndex}:`, generosSeleccionados);
            
            // Actualizar el contenedor dinámico de tallas por género
            actualizarContenedorTallasPorGenero(prendaIndex, prenda);
        });
    });

    // Listeners para cambios en género (COMPATIBILIDAD CON SELECT ANTIGUO)
    document.querySelectorAll('.prenda-genero').forEach(select => {
        select.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.genero = e.target.value;
                console.log('✅ Género actualizado para prenda', index, ':', prenda.genero);
            }
        });
    });

    // Listeners para cambios en cantidad de talla (compatibilidad con ambas clases)
    document.querySelectorAll('.talla-cantidad, .talla-cantidad-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            window.gestorPrendaSinCotizacion.actualizarCantidadTalla(index, talla, cantidad);
            console.log(`✅ Cantidad actualizada - Prenda: ${index}, Talla: ${talla}, Cantidad: ${cantidad}`);
        });
    });
    
    // Listeners en tiempo real para cantidad (input event)
    document.querySelectorAll('.talla-cantidad, .talla-cantidad-input').forEach(input => {
        input.addEventListener('input', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            window.gestorPrendaSinCotizacion.actualizarCantidadTalla(index, talla, cantidad);
        });
    });

    // Listeners para cambios en valores de variaciones (selects)
    document.querySelectorAll('[data-field][data-prenda][data-variacion]:not([data-field$="_obs"])').forEach(select => {
        if (select.tagName === 'SELECT') {
            select.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.prenda);
                const field = e.target.dataset.field;
                const varIdx = parseInt(e.target.dataset.variacion);
                const valor = e.target.value;
                
                const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
                if (prenda && prenda.variantes) {
                    prenda.variantes[field] = valor;
                    logWithEmoji('📝', `Variación ${field} actualizada a: ${valor}`);
                }
            });
        }
    });

    // Listeners para cambios en observaciones de variaciones (textareas)
    document.querySelectorAll('.variacion-obs').forEach(textarea => {
        textarea.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const field = e.target.dataset.field; // Ej: "tiene_bolsillos_obs"
            const valor = e.target.value;
            
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda && prenda.variantes) {
                prenda.variantes[field] = valor;
                logWithEmoji('📝', `Observación ${field} actualizada`);
            }
        });
    });

    logWithEmoji('🔗', 'Event listeners adjunados a prendas tipo PRENDA');
}

/**
 * ✅ CRÍTICO: Recrear inputs ocultos de tallas por género después de renderizar
 * Esto asegura que siempre existan en el DOM para sincronización
 * @param {Array} prendas - Array de prendas
 */
function recrearInputsOcultosDeGeneros(prendas) {
    prendas.forEach((prenda, prendaIndex) => {
        const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendaCard) return;
        
        // Solo procesar si hay generosConTallas
        if (!prenda.generosConTallas || Object.keys(prenda.generosConTallas).length === 0) {
            return;
        }
        
        // Para cada género y sus tallas, crear/verificar inputs ocultos
        Object.entries(prenda.generosConTallas).forEach(([genero, tallas]) => {
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                // Verificar si el input ya existe
                const existente = prendaCard.querySelector(
                    `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`
                );
                
                if (!existente) {
                    // Crear el input oculto
                    const inputTalla = document.createElement('input');
                    inputTalla.type = 'hidden';
                    inputTalla.name = `cantidades_genero[${prendaIndex}][${genero}][${talla}]`;
                    inputTalla.className = 'talla-cantidad-genero-editable';
                    inputTalla.value = cantidad;
                    inputTalla.dataset.talla = talla;
                    inputTalla.dataset.genero = genero;
                    inputTalla.dataset.prenda = prendaIndex;
                    
                    prendaCard.appendChild(inputTalla);
                    console.log(`✅ Input recreado: Prenda ${prendaIndex}, ${genero} ${talla}: ${cantidad}`);
                } else {
                    // Actualizar valor si existe
                    existente.value = cantidad;
                }
            });
        });
    });
    
    logWithEmoji('🔧', 'Inputs ocultos de tallas por género recreados');
}

/**
 * Actualizar dinámicamente el contenedor de tallas por género
 * Usa MODAL para seleccionar tallas específicas por género, no muestra todas
 * @param {number} prendaIndex - Índice de la prenda
 * @param {Object} prenda - Objeto de la prenda
 */
function actualizarContenedorTallasPorGenero(prendaIndex, prenda) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    const container = prendaCard.querySelector('.tallas-por-genero-container');
    if (!container) return;
    
    const generos = Array.isArray(prenda.genero) ? prenda.genero : [];
    
    if (generos.length === 0) {
        container.innerHTML = '<p style="color: #9ca3af; font-size: 0.9rem; margin-top: 0.5rem;">Selecciona al menos un género para agregar tallas</p>';
        return;
    }
    
    let html = '';
    generos.forEach((genero) => {
        const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
        
        // Encabezado de género con botón para agregar talla
        html += `
            <div style="margin-top: 1.5rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0066cc 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user"></i> ${generoLabel}
                </div>
                <button type="button" 
                        onclick="agregarTallaParaGenero(${prendaIndex}, '${genero}')"
                        style="background: white; color: #0066cc; border: none; padding: 0.4rem 0.6rem; border-radius: 999px; cursor: pointer; font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" title="Agregar talla">
                    <i class="fas fa-plus" style="font-size: 0.75rem;"></i> Talla
                </button>
            </div>
            <div class="tallas-genero-container" data-prenda="${prendaIndex}" data-genero="${genero}" style="min-height: 50px;"></div>
        `;
    });
    
    container.innerHTML = html;
    
    // Renderizar las tallas ya agregadas para cada género
    generos.forEach((genero) => {
        renderizarTallasDelGenero(prendaIndex, genero);
    });
}

/**
 * Renderizar las tallas ya agregadas para un género específico
 */
function renderizarTallasDelGenero(prendaIndex, genero) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) return;
    
    const containerGenero = prendaCard.querySelector(`.tallas-genero-container[data-prenda="${prendaIndex}"][data-genero="${genero}"]`);
    if (!containerGenero) return;
    
    // ✅ CRÍTICO: Buscar inputs de tallas para este género
    // Buscar primero los inputs editables (clase correcta)
    let tallasInputs = prendaCard.querySelectorAll(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`);
    
    // Fallback: si no encuentra editables, buscar hidden (para compatibilidad)
    if (tallasInputs.length === 0) {
        tallasInputs = prendaCard.querySelectorAll(`.talla-cantidad-genero-hidden[data-prenda="${prendaIndex}"][data-genero="${genero}"]`);
    }
    
    if (tallasInputs.length === 0) {
        containerGenero.innerHTML = '<p style="padding: 0.75rem 1rem; background: white; color: #9ca3af; font-size: 0.85rem; margin: 0; border: 1px solid #e0e0e0; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">Sin tallas agregadas</p>';
        return;
    }
    
    let html = '';
    let isFirst = true;
    
    tallasInputs.forEach((input) => {
        const talla = input.dataset.talla;
        const cantidad = input.value || '0';
        
        html += `
            <div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; ${isFirst ? '' : 'border-top: none;'} display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                    <div style="font-weight: 500; color: #1f2937;">${talla}</div>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                    <input type="number" 
                           min="0" 
                           value="${cantidad}" 
                           placeholder="0"
                           class="talla-cantidad-display"
                           data-talla="${talla}"
                           data-genero="${genero}"
                           data-prenda="${prendaIndex}"
                           style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
                </div>
                <div style="text-align: center;">
                    <button type="button" class="btn-eliminar-talla-genero" onclick="eliminarTallaDelGenero(${prendaIndex}, '${genero}', '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;" title="Eliminar talla">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i> Quitar
                    </button>
                </div>
            </div>
        `;
        isFirst = false;
    });
    
    containerGenero.innerHTML = html;
    
    // Agregar listeners a los inputs de display y sincronizar con hidden
    containerGenero.querySelectorAll('.talla-cantidad-display').forEach(input => {
        input.addEventListener('change', (e) => {
            const prendaIdx = parseInt(e.target.dataset.prenda);
            const gen = e.target.dataset.genero;
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            
            // Actualizar el input oculto correspondiente (usar clase editable)
            const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIdx}"]`);
            if (prendaCard) {
                let hiddenInput = prendaCard.querySelector(`.talla-cantidad-genero-editable[data-prenda="${prendaIdx}"][data-genero="${gen}"][data-talla="${talla}"]`);
                
                // Fallback: buscar con clase hidden si no existe editable
                if (!hiddenInput) {
                    hiddenInput = prendaCard.querySelector(`.talla-cantidad-genero-hidden[data-prenda="${prendaIdx}"][data-genero="${gen}"][data-talla="${talla}"]`);
                }
                
                if (hiddenInput) {
                    hiddenInput.value = cantidad;
                }
            }
            
            // Actualizar gestor
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIdx);
            if (prenda) {
                if (!prenda.generosConTallas) {
                    prenda.generosConTallas = {};
                }
                if (!prenda.generosConTallas[gen]) {
                    prenda.generosConTallas[gen] = {};
                }
                prenda.generosConTallas[gen][talla] = cantidad;
                console.log(`✅ Cantidad actualizada - Prenda: ${prendaIdx}, Género: ${gen}, Talla: ${talla}, Cantidad: ${cantidad}`);
            }
        });
        
        input.addEventListener('input', (e) => {
            if (e.target.value < 0) e.target.value = 0;
        });
    });
}

/**
 * Función helper para eliminar una talla de un género
 */
window.eliminarTallaDelGenero = function(prendaIndex, genero, talla) {
    Swal.fire({
        title: '¿Eliminar talla?',
        text: `¿Eliminar talla ${talla} de ${genero}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
            if (!prendaCard) return;
            
            // Buscar y eliminar el input hidden (usar clase específica)
            const input = prendaCard.querySelector(`.talla-cantidad-genero-hidden[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`);
            if (input) {
                input.remove();
            }
            
            // ✅ CRÍTICO: Eliminar talla del gestor
            if (window.gestorPrendaSinCotizacion) {
                window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
            }
            
            // Re-renderizar la sección del género
            renderizarTallasDelGenero(prendaIndex, genero);
        }
    });
};

/**
 * Renderizar sección de bodega (separada de telas)
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de bodega
 */
function renderizarBodegaPrendaTipo(prenda, index) {
    const deBodega = prenda.de_bodega ? true : false;
    
    return `
        <div style="margin-top: 2rem; padding: 1rem; background: linear-gradient(135deg, #f0f7ff 0%, #e0efff 100%); border: 2px solid #1e40af; border-radius: 6px;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 600; color: #1e40af;">
                <input type="checkbox" 
                       id="de_bodega_${index}"
                       class="checkbox-de-bodega"
                       data-prenda="${index}"
                       ${deBodega ? 'checked' : ''}
                       onchange="marcarPrendaDeBodega(${index}, this.checked)"
                       style="width: 18px; height: 18px; cursor: pointer; accent-color: #1e40af;">
                <i class="fas fa-warehouse" style="color: #1e40af; font-size: 1.1rem;"></i>
                <span>Esta prenda se saca de bodega</span>
            </label>
        </div>
    `;
}

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        renderizarPrendasTipoPrendaSinCotizacion,
        renderizarPrendaTipoPrenda,
        attachPrendaTipoPrendaListeners,
        actualizarContenedorTallasPorGenero
    };
}
