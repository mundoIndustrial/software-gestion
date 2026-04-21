/**
 * SISTEMA DE COTIZACIONES - ORQUESTACIÓN E INICIALIZACIÓN
 * Responsabilidad: Inicializar el sistema, gestionar el ciclo de vida
 */

// Variables globales
globalThis.imagenesEnMemoria = { 
    prenda: [], 
    tela: [], 
    logo: [],
    prendaConIndice: [],  // Fotos de prendas con índice
    telaConIndice: []     // Fotos de telas con índice
};
globalThis.especificacionesSeleccionadas = {};

// Inicializar registro de fotos eliminadas (solo para edición, no para nuevas)
globalThis.fotosEliminadasServidor = {
    prendas: [],
    telas: []
};

// Mapeo de géneros a IDs (desde generos_prenda tabla)
const GENEROS_MAP = {
    'dama': 2,
    'caballero': 1
};





// ============ GESTIÓN DE TIPO DE COTIZACIÓN ============

/**
 * Seleccionar tipo de cotización desde las pastillas
 */
function seleccionarTipoCotizacion(tipo) {

    
    // Mapear tipo de pastilla a tipo_venta
    const mapeos = {
        'prenda': 'M',        // Mercadería
        'logo': 'D',          // Diseño
        'prenda-bordado': 'X' // Especial
    };
    
    const tipoVenta = mapeos[tipo];
    
    if (!tipoVenta) {

        return;
    }
    
    // Actualizar el input oculto
    document.getElementById('tipo_venta').value = tipoVenta;
    
    // Guardar en localStorage
    localStorage.setItem('tipo_cotizacion_seleccionado', tipo);
    localStorage.setItem('tipo_venta', tipoVenta);
    

    
    // Mostrar confirmación visual
    mostrarNotificacionTipoCotizacion(tipo);
}

/**
 * Mostrar notificación de tipo seleccionado
 */
function mostrarNotificacionTipoCotizacion(tipo) {
    const info = {
        'prenda': 'Prendas Sin Logo',
        'logo': ' Solo Logos',
        'prenda-bordado': ' Prendas Con Bordado/Logo'
    };
    
    const mensaje = info[tipo] || 'Tipo desconocido';
    
    // Mostrar toast
    return mensaje;
}

// ============ INICIALIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    // No ejecutar en supervisor-pedidos o cartera-pedidos
    if (globalThis.location.pathname.includes('supervisor-pedidos') || 
        globalThis.location.pathname.includes('cartera-pedidos')) {
        return;
    }
    
    // Ocultar navbar
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = 'none';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = 'none';
    
    // Inicializar funciones
    mostrarFechaActual();
    configurarDragAndDrop();
});

globalThis.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = '';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = '';
});

// ============ NAVEGACIÓN ============

function irAlPaso(paso) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) formStep.classList.add('active');
    
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    // En el stepper visual, el botón REVISAR siempre es data-step="4",
    // pero el contenido de revisar es el form-step data-step="5".
    const pasoVisual = (paso === 5) ? 4 : paso;
    const stepElement = document.querySelector(`.step[data-step="${pasoVisual}"]`);
    if (stepElement) stepElement.classList.add('active');
    
    // Si es el paso 4 (REFLECTIVO), agregar la primera prenda vacía si no existe ninguna
    if (paso === 4) {

        setTimeout(() => {
            const container = document.getElementById('prendas_reflectivo_container');
            if (container && container.children.length === 0) {

                if (typeof agregarPrendaReflectivoPaso4 === 'function') {
                    agregarPrendaReflectivoPaso4();

                }
            } else {

            }
        }, 100);
    }
    
    // Si es el paso 5 (REVISAR COTIZACIÓN), actualizar resumen completo
    if (paso === 5) {

        console.log(' [irAlPaso] Navegando al paso 5 (Revisar)');
        
        setTimeout(() => {
            // Usar la nueva función de resumen completo si está disponible
            if (typeof actualizarResumenPaso5Completo === 'function') {
                console.log(' [irAlPaso] Llamando a actualizarResumenPaso5Completo()');
                actualizarResumenPaso5Completo();
            } else {
                console.log(' [irAlPaso] actualizarResumenPaso5Completo no disponible, usando fallback');
                // Fallback a la función antigua
                actualizarResumenFriendly();
            }
        }, 200);
    }
}

// ============ UTILIDADES ============

function mostrarFechaActual() {
    const el = document.getElementById('fechaActual');
    if (el) {
        const hoy = new Date();
        // Si es un input de tipo date, establecer el value en formato YYYY-MM-DD
        if (el.type === 'date') {
            const año = hoy.getFullYear();
            const mes = String(hoy.getMonth() + 1).padStart(2, '0');
            const dia = String(hoy.getDate()).padStart(2, '0');
            el.value = `${año}-${mes}-${dia}`;
        } else {
            // Si es un span, mostrar en formato DD/MM/YYYY
            el.textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
        }
    }
}

function actualizarResumenFriendly() {





    
    // 1. INFORMACIÓN DEL CLIENTE
    const cliente = document.getElementById('cliente');
    const resumenCliente = document.getElementById('resumen_cliente');
    if (resumenCliente && cliente) {
        resumenCliente.textContent = cliente.value || '-';

    }
    
    // 2. FECHA
    const resumenFecha = document.getElementById('resumen_fecha');
    if (resumenFecha) {
        const fechaInput = document.getElementById('fechaActual');
        let fechaTexto = '-';
        
        if (fechaInput && fechaInput.value) {
            // Convertir de YYYY-MM-DD a DD/MM/YYYY
            const partes = fechaInput.value.split('-');
            if (partes.length === 3) {
                fechaTexto = `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        } else {
            // Si no hay fecha seleccionada, usar la de hoy
            const hoy = new Date();
            fechaTexto = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
        }
        
        resumenFecha.textContent = fechaTexto;

    }
    
    // 3. TIPO DE COTIZACIÓN (DINÁMICO según contenido)
    const resumenTipo = document.getElementById('resumen_tipo');
    if (resumenTipo) {
        // Detectar qué hay en el formulario
        const tienePrendas = document.querySelectorAll('.producto-card').length > 0;
        const tieneLogo = document.getElementById('descripcion_logo')?.value?.trim() || false;
        const tieneTecnicas = document.querySelectorAll('#tecnicas_seleccionadas .tecnica-badge').length > 0;
        
        let tipoDetectado = '';
        if (tienePrendas && (tieneLogo || tieneTecnicas)) {
            tipoDetectado = ' Combinada';
        } else if (tienePrendas) {
            tipoDetectado = 'Solo Prendas';
        } else if (tieneLogo || tieneTecnicas) {
            tipoDetectado = ' Solo Logo/Bordado';
        } else {
            tipoDetectado = '-';
        }
        
        resumenTipo.textContent = tipoDetectado;

    }
    
    // 4. RESUMEN DE PRENDAS (Solo si hay prendas)
    const resumenPrendas = document.getElementById('resumen_prendas');
    if (resumenPrendas) {
        const prendas = document.querySelectorAll('.producto-card');

        
        // Buscar el contenedor padre (el div con background #f0f7ff)
        const resumenPrendasContainer = resumenPrendas.parentElement;
        
        if (prendas.length === 0) {
            if (resumenPrendasContainer) resumenPrendasContainer.style.display = 'none';
            resumenPrendas.innerHTML = '';
        } else {
            if (resumenPrendasContainer) resumenPrendasContainer.style.display = 'block';
            resumenPrendas.innerHTML = '';
            prendas.forEach((prenda, index) => {
                const nombre = prenda.querySelector('input[name*="nombre_producto"]')?.value || 'Sin nombre';
                const descripcion = prenda.querySelector('textarea[name*="descripcion"]')?.value || '';
                
                // Obtener tallas desde guardadas primero, luego desde DOM
                let tallasTexto = 'Sin tallas';
                let generoTexto = '';
                
                if (globalThis.variacionesGuardadas && globalThis.variacionesGuardadas[index]) {
                    const varGuardadas = globalThis.variacionesGuardadas[index];
                    if (varGuardadas.tallas && varGuardadas.tallas.trim() !== '') {
                        tallasTexto = varGuardadas.tallas;

                    }
                    if (varGuardadas.genero) {
                        generoTexto = varGuardadas.genero;

                    }
                } 
                
                // Si aún no hay tallas, buscar en DOM desde el input hidden
                if (tallasTexto === 'Sin tallas') {
                    const tallasHiddenInput = prenda.querySelector('input[name*="tallas"][type="hidden"]');
                    if (tallasHiddenInput && tallasHiddenInput.value?.trim()) {
                        tallasTexto = tallasHiddenInput.value;

                    }
                    
                    // Si aún no hay, buscar botones activos (fallback)
                    if (tallasTexto === 'Sin tallas') {
                        const tallas = prenda.querySelectorAll('button[data-talla].active');
                        if (tallas.length > 0) {
                            tallasTexto = Array.from(tallas).map(t => t.textContent).join(', ');

                        }
                    }
                }
                
                // Buscar género en DOM si no está en guardadas
                if (!generoTexto) {
                    const generoSelect = prenda.querySelector('select.talla-genero-select');
                    if (generoSelect && generoSelect.value) {
                        generoTexto = generoSelect.options[generoSelect.selectedIndex]?.text || '';

                    }
                }
                
                // Obtener variaciones desde globalThis.variacionesGuardadas (si existe)
                let telasHTML = '';
                let otrasVariacionesHTML = '';
                let prendaBodegaHTML = '';
                
                if (globalThis.variacionesGuardadas && globalThis.variacionesGuardadas[index]) {
                    const varGuardadas = globalThis.variacionesGuardadas[index];

                    
                    // ====== SECCIÓN DE TELAS (Agrupadas) ======
                    let telas = [];
                    if (varGuardadas.color || varGuardadas.tela || varGuardadas.referencia) {
                        telas.push({
                            color: varGuardadas.color || '-',
                            tela: varGuardadas.tela || '-',
                            referencia: varGuardadas.referencia || '-'
                        });
                    }
                    
                    if (telas.length > 0) {
                        telasHTML = '<div style="margin-bottom: 8px;"><small style="color: #666;"><strong> Telas:</strong></small><div style="margin-top: 6px;">';
                        telas.forEach(t => {
                            telasHTML += `<div style="background: #f0f8ff; padding: 8px; border-radius: 4px; margin-bottom: 6px; border-left: 3px solid #0066cc;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 0.85rem;">
                                    <div><span style="color: #64748b;">Color:</span> <strong>${t.color}</strong></div>
                                    <div><span style="color: #64748b;">Tela:</span> <strong>${t.tela}</strong></div>
                                    <div><span style="color: #64748b;">Ref:</span> <strong>${t.referencia}</strong></div>
                                </div>
                            </div>`;
                        });
                        telasHTML += '</div></div>';
                    }
                    
                    // ====== OTRAS VARIACIONES (Manga, Bolsillos, Broche, Reflectivo) ======
                    let otrasVariaciones = [];
                    
                    if (varGuardadas.manga) {
                        let mangaTexto = varGuardadas.manga;
                        if (varGuardadas.obsManga) mangaTexto += ` (${varGuardadas.obsManga})`;
                        otrasVariaciones.push(`Manga: ${mangaTexto}`);
                    }
                    
                    if (varGuardadas.bolsillos) {
                        let bolsillosTexto = 'Bolsillos';
                        if (varGuardadas.obsBolsillos) bolsillosTexto += ` (${varGuardadas.obsBolsillos})`;
                        otrasVariaciones.push(bolsillosTexto);
                    }
                    
                    // Mostrar solo UNA VEZ el tipo de cierre (evitar duplicados)
                    if (varGuardadas.broche) {
                        let cierreTexto = varGuardadas.broche;
                        if (varGuardadas.obsBroche) cierreTexto += ` (${varGuardadas.obsBroche})`;
                        otrasVariaciones.push(cierreTexto);
                    }
                    
                    if (varGuardadas.reflectivo) {
                        let reflectivoTexto = 'Reflectivo';
                        if (varGuardadas.obsReflectivo) reflectivoTexto += ` (${varGuardadas.obsReflectivo})`;
                        otrasVariaciones.push(reflectivoTexto);
                    }

                    // PRENDA DE BODEGA desde datos guardados
                    if (varGuardadas.prendaBodega) {
                        prendaBodegaHTML = `<div style="padding: 10px 12px; background: #dcfce7; border-radius: 6px; border-left: 3px solid #16a34a; margin-bottom: 10px;">
                            <small style="color: #15803d; font-weight: 600;"><i class="fas fa-warehouse" style="margin-right: 6px;"></i> Viene de bodega: <strong>Sí</strong></small>
                        </div>`;
                    }
                    
                    if (otrasVariaciones.length > 0) {
                        otrasVariacionesHTML = `<div style="padding-left: 10px; border-left: 2px solid #95a5a6;">
                            <small style="color: #666;"><strong>Otros atributos:</strong></small><br>
                            <small style="color: #666; display: block; margin-top: 4px;">${otrasVariaciones.map(v => `• ${v}`).join('<br>')}</small>
                        </div>`;
                    }
                } else {
                    // Si no hay datos guardados, buscar en el DOM
                    // ====== TELAS DESDE DOM ======
                    let telasDesdeDOM = [];
                    
                    // Buscar filas de tela en la tabla (fila-tela)
                    const filasTelaDOM = prenda.querySelectorAll('.fila-tela');
                    
                    filasTelaDOM.forEach(fila => {
                        const colorInput = fila.querySelector('.color-input');
                        const telaInput = fila.querySelector('.tela-input');
                        const refInput = fila.querySelector('.referencia-input');
                        
                        const color = colorInput?.value?.trim() || '-';
                        const tela = telaInput?.value?.trim() || '-';
                        const ref = refInput?.value?.trim() || '-';
                        
                        if (color !== '-' || tela !== '-' || ref !== '-') {
                            telasDesdeDOM.push({ color, tela, ref });
                        }
                    });
                    
                    if (telasDesdeDOM.length > 0) {
                        telasHTML = '<div style="margin-bottom: 8px;"><small style="color: #666;"><strong> Telas:</strong></small><div style="margin-top: 6px;">';
                        telasDesdeDOM.forEach(t => {
                            telasHTML += `<div style="background: #f0f8ff; padding: 8px; border-radius: 4px; margin-bottom: 6px; border-left: 3px solid #0066cc;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 0.85rem;">
                                    <div><span style="color: #64748b;">Color:</span> <strong>${t.color}</strong></div>
                                    <div><span style="color: #64748b;">Tela:</span> <strong>${t.tela}</strong></div>
                                    <div><span style="color: #64748b;">Ref:</span> <strong>${t.ref}</strong></div>
                                </div>
                            </div>`;
                        });
                        telasHTML += '</div></div>';
                    }
                    
                    // ====== OTRAS VARIACIONES DESDE DOM ======
                    let otrasVariacionesDesdeDOM = [];
                    
                    // MANGA - buscar el checkbox y el tipo de manga
                    const mangaCheckbox = prenda.querySelector('input[name*="aplica_manga"]');
                    if (mangaCheckbox?.checked) {
                        const mangaSelect = prenda.querySelector('select[name*="tipo_manga_id"]');
                        const mangaObsInput = prenda.querySelector('input[name*="obs_manga"]');
                        
                        let mangaTexto = 'Manga';
                        if (mangaSelect?.value) {
                            const selectedOption = mangaSelect.options[mangaSelect.selectedIndex];
                            if (selectedOption?.text) {
                                mangaTexto = selectedOption.text;
                            }
                        }
                        
                        if (mangaObsInput?.value?.trim()) {
                            mangaTexto += ` (${mangaObsInput.value.trim()})`;
                        }
                        otrasVariacionesDesdeDOM.push(mangaTexto);
                    }
                    
                    // BOLSILLOS - buscar el checkbox y observación
                    const bolsillosCheckbox = prenda.querySelector('input[name*="aplica_bolsillos"]');
                    if (bolsillosCheckbox?.checked) {
                        const bolsillosObsInput = prenda.querySelector('input[name*="obs_bolsillos"]');
                        
                        let bolsillosTexto = 'Bolsillos';
                        if (bolsillosObsInput?.value?.trim()) {
                            bolsillosTexto += ` (${bolsillosObsInput.value.trim()})`;
                        }
                        otrasVariacionesDesdeDOM.push(bolsillosTexto);
                    }
                    
                    // BROCHE/BOTÓN - evitar duplicados
                    const brocheSet = new Set();
                    const brocheCheckbox = prenda.querySelector('input[name*="aplica_broche"]');
                    if (brocheCheckbox?.checked) {
                        const brocheSelect = prenda.querySelector('select[name*="tipo_broche_id"]');
                        const brocheObsInput = prenda.querySelector('input[name*="obs_broche"]');
                        
                        if (brocheSelect?.value) {
                            let brocheTexto = brocheSelect.options[brocheSelect.selectedIndex]?.text || '';
                            if (brocheTexto && brocheTexto !== '-' && brocheTexto !== 'Seleccionar...') {
                                if (brocheObsInput?.value?.trim()) {
                                    brocheTexto += ` (${brocheObsInput.value.trim()})`;
                                }
                                brocheSet.add(brocheTexto);
                            }
                        }
                    }
                    brocheSet.forEach(broche => otrasVariacionesDesdeDOM.push(broche));
                    
                    // REFLECTIVO - buscar el checkbox y observación
                    const reflectivoCheckbox = prenda.querySelector('input[name*="aplica_reflectivo"]');
                    if (reflectivoCheckbox?.checked) {
                        const reflectivoObsInput = prenda.querySelector('input[name*="obs_reflectivo"]');
                        
                        let reflectivoTexto = 'Reflectivo';
                        if (reflectivoObsInput?.value?.trim()) {
                            reflectivoTexto += ` (${reflectivoObsInput.value.trim()})`;
                        }
                        otrasVariacionesDesdeDOM.push(reflectivoTexto);
                    }

                    // PRENDA DE BODEGA - buscar el checkbox
                    const prendaBodegaCheckbox = prenda.querySelector('input[name*="prenda_bodega"]');
                    if (prendaBodegaCheckbox?.checked) {
                        prendaBodegaHTML = `<div style="padding: 10px 12px; background: #dcfce7; border-radius: 6px; border-left: 3px solid #16a34a; margin-bottom: 10px;">
                            <small style="color: #15803d; font-weight: 600;"><i class="fas fa-warehouse" style="margin-right: 6px;"></i> Viene de bodega: <strong>Sí</strong></small>
                        </div>`;
                    }
                    
                    if (otrasVariacionesDesdeDOM.length > 0) {
                        otrasVariacionesHTML = `<div style="padding-left: 10px; border-left: 2px solid #95a5a6;">
                            <small style="color: #666;"><strong>Otros atributos:</strong></small><br>
                            <small style="color: #666; display: block; margin-top: 4px;">${otrasVariacionesDesdeDOM.map(v => `• ${v}`).join('<br>')}</small>
                        </div>`;
                    }
                }
                
                const div = document.createElement('div');
                div.style.cssText = 'padding: 15px; background: #fff; border-left: 4px solid #3498db; border-radius: 4px; margin-bottom: 10px;';
                
                let html = `<div style="margin-bottom: 12px;">
                    <strong style="font-size: 1.05rem; color: #0066cc;">Prenda ${index + 1}: ${nombre}</strong>
                </div>`;
                
                if (descripcion) {
                    html += `<div style="margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #95a5a6;">
                        <small style="color: #666;"><strong>Descripción:</strong> ${descripcion}</small>
                    </div>`;
                }
                
                html += `<div style="margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #95a5a6;">
                    <small style="color: #666;"><strong>Tallas:</strong> ${tallasTexto}</small>`;
                if (generoTexto) {
                    html += `<small style="color: #999; margin-left: 12px;">(${generoTexto})</small>`;
                }
                html += `</div>`;
                
                // Agregar telas agrupadas
                if (telasHTML) {
                    html += `<div style="margin-bottom: 10px; padding-left: 10px; border-left: 2px solid #95a5a6;">
                        ${telasHTML}
                    </div>`;
                }

                // Agregar prenda de bodega si está marcada
                if (prendaBodegaHTML) {
                    html += prendaBodegaHTML;
                }
                
                // Agregar otras variaciones
                if (otrasVariacionesHTML) {
                    html += `<div style="margin-bottom: 0; padding-left: 0;">
                        ${otrasVariacionesHTML}
                    </div>`;
                }
                
                div.innerHTML = html;
                resumenPrendas.appendChild(div);
            });

        }
    }
    
    // 5. DESCRIPCIÓN DEL LOGO (Solo si hay logo/bordado)
    const resumenLogDesc = document.getElementById('resumen_logo_desc');
    const resumenLogoContainer = resumenLogDesc?.closest('div[style*="background"]');
    if (resumenLogDesc && resumenLogoContainer) {
        const descLogo = document.getElementById('descripcion_logo');
        const tecnicas = document.querySelectorAll('#tecnicas_seleccionadas .tecnica-badge');
        
        if (!descLogo?.value?.trim() && tecnicas.length === 0) {
            resumenLogoContainer.style.display = 'none';
        } else {
            resumenLogoContainer.style.display = 'block';
            const texto = descLogo?.value || '-';
            resumenLogDesc.textContent = texto;

        }
    }
    
    // 6. TÉCNICAS Y OBSERVACIÓN
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    if (resumenTecnicas) {
        let tecnicasArray = globalThis.tecnicasGuardadas || [];
        
        // Si no hay técnicas guardadas, buscar en el DOM
        if (tecnicasArray.length === 0) {
            const tecnicasSeleccionadas = document.querySelectorAll('#tecnicas_seleccionadas > div');
            tecnicasSeleccionadas.forEach(div => {
                const input = div.querySelector('input[name="tecnicas[]"]');
                if (input && input.value?.trim()) {
                    tecnicasArray.push(input.value);
                }
            });

        }
        resumenTecnicas.innerHTML = '';
        
        if (tecnicasArray.length === 0) {
            resumenTecnicas.innerHTML = '<p style="margin: 0; font-size: 0.95rem; color: #999; padding: 8px 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px;">No especificadas</p>';
        } else {
            // Obtener observación de técnicas (desde global si existe)
            let obsTecnicas = globalThis.obsTecnicasGuardadas || '';
            
            // Si no está en global, intentar desde el formulario (Paso 3)
            if (!obsTecnicas) {
                const obsTecnicasInput = document.getElementById('observaciones_tecnicas');
                obsTecnicas = obsTecnicasInput?.value?.trim() || '';
            }
            
            // Crear texto con técnicas
            let tecnicasTexto = tecnicasArray.join(', ');
            
            // Agregar observación si existe
            if (obsTecnicas) {
                tecnicasTexto += `<br><span style="font-size: 0.85rem; opacity: 0.8; display: block; margin-top: 4px;">Observación: ${obsTecnicas}</span>`;
            }
            
            const p = document.createElement('p');
            p.style.cssText = 'margin: 0; font-size: 0.95rem; color: #555; padding: 8px 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px;';
            p.innerHTML = tecnicasTexto;
            resumenTecnicas.appendChild(p);
        }

    }
    
    // 6B. UBICACIONES EN LOGO
    const resumenLogoUbicaciones = document.getElementById('resumen_logo_ubicaciones');
    const resumenLogoUbicacionesContainer = document.getElementById('resumen_logo_ubicaciones_container');
    if (resumenLogoUbicaciones && resumenLogoUbicacionesContainer) {
        resumenLogoUbicaciones.innerHTML = '';
        
        let ubicacionesArray = globalThis.ubicacionesGuardadas || [];
        if (ubicacionesArray.length === 0) {
            resumenLogoUbicacionesContainer.style.display = 'none';
        } else {
            resumenLogoUbicacionesContainer.style.display = 'block';
            
            ubicacionesArray.forEach((ubicacion, idx) => {
                // Soportar tanto formato antiguo (seccion) como nuevo (ubicacion)
                const seccionNombre = ubicacion.ubicacion || ubicacion.seccion || 'Sección sin nombre';
                
                // Las opciones pueden venir como array o como string separado por comas
                let opcionesTexto = '';
                if (Array.isArray(ubicacion.opciones)) {
                    opcionesTexto = ubicacion.opciones.join(', ');
                } else if (typeof ubicacion.opciones === 'string') {
                    opcionesTexto = ubicacion.opciones;
                } else if (Array.isArray(ubicacion.ubicaciones_seleccionadas)) {
                    opcionesTexto = ubicacion.ubicaciones_seleccionadas.join(', ');
                }
                
                const obs = ubicacion.observaciones || '';
                
                const divResumen = document.createElement('div');
                divResumen.style.cssText = 'padding: 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px; margin-bottom: 8px; font-size: 0.95rem;';
                
                let obsHTML = '';
                if (obs) {
                    obsHTML = `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ecf0f1; font-size: 0.85rem; color: #555;"><strong>Obs:</strong> ${obs}</div>`;
                }
                
                // Mostrar tallas si existen
                let tallasHTML = '';
                if (ubicacion.tallas && Array.isArray(ubicacion.tallas) && ubicacion.tallas.length > 0) {
                    tallasHTML = '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ecf0f1;"><strong style="font-size: 0.85rem; color: #0066cc;">Tallas:</strong>';
                    tallasHTML += '<div style="margin-left: 12px; font-size: 0.85rem; color: #555;">';
                    ubicacion.tallas.forEach(talla => {
                        const tallaName = typeof talla === 'object' ? (talla.talla || talla.nombre) : talla;
                        const tallaQty = typeof talla === 'object' ? (talla.cantidad || '') : '';
                        tallasHTML += tallaQty ? `${tallaName} (${tallaQty}) ` : `${tallaName} `;
                    });
                    tallasHTML += '</div></div>';
                }
                
                divResumen.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 4px; color: #0066cc; font-size: 1rem;"> ${seccionNombre}</div>
                    ${opcionesTexto ? `<div style="margin-bottom: 4px; margin-left: 12px; color: #555;"><strong style="font-size: 0.85rem;">Ubicaciones:</strong> ${opcionesTexto}</div>` : ''}
                    ${tallasHTML}
                    ${obsHTML}
                `;
                resumenLogoUbicaciones.appendChild(divResumen);
            });

        }
    }
    
    // 7. UBICACIONES
    const resumenUbicacionesContainer = document.getElementById('resumen_ubicaciones_container');
    const resumenUbicaciones = document.getElementById('resumen_ubicaciones');
    if (resumenUbicaciones && resumenUbicacionesContainer) {
        const ubicacionesElements = document.querySelectorAll('#ubicaciones_seleccionadas > div');
        resumenUbicaciones.innerHTML = '';
        
        if (ubicacionesElements.length === 0) {
            resumenUbicacionesContainer.style.display = 'none';
        } else {
            resumenUbicacionesContainer.style.display = 'block';
            ubicacionesElements.forEach(ub => {
                const seccion = ub.querySelector('strong')?.textContent || '';
                const ubicaciones = Array.from(ub.querySelectorAll('span')).map(s => s.textContent).join(', ');
                
                const div = document.createElement('div');
                div.style.cssText = 'padding: 10px; background: #fff; border-left: 4px solid #e74c3c; border-radius: 4px;';
                div.innerHTML = `<strong>${seccion}</strong><br><small style="color: #666;">${ubicaciones}</small>`;
                resumenUbicaciones.appendChild(div);
            });

        }
    }
    
    // 8. ESPECIFICACIONES
    const resumenEspecificacionesContainer = document.getElementById('resumen_especificaciones_container');
    const resumenEspecificaciones = document.getElementById('resumen_especificaciones');
    if (resumenEspecificaciones && resumenEspecificacionesContainer && globalThis.especificacionesSeleccionadas) {
        resumenEspecificaciones.innerHTML = '';
        const especKeys = Object.keys(globalThis.especificacionesSeleccionadas || {});
        
        if (especKeys.length === 0) {
            resumenEspecificacionesContainer.style.display = 'none';
        } else {
            resumenEspecificacionesContainer.style.display = 'block';
            
            // Crear tabla para especificaciones
            let tableHTML = `
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #3498db; color: white;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Categoría</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Valor</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            Object.entries(globalThis.especificacionesSeleccionadas).forEach(([categoria, valores]) => {
                if (Array.isArray(valores) && valores.length > 0) {
                    const categoriaNombre = categoria.replace(/_/g, ' ').toUpperCase();
                    valores.forEach((val, idx) => {
                        tableHTML += `
                            <tr style="border: 1px solid #ddd; ${idx % 2 === 0 ? 'background: #f9f9f9;' : ''}">
                                <td style="padding: 10px; border: 1px solid #ddd;"><strong>${categoriaNombre}</strong></td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${val.valor}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${val.observacion || '-'}</td>
                            </tr>
                        `;
                    });
                }
            });
            
            tableHTML += `
                    </tbody>
                </table>
            `;
            
            resumenEspecificaciones.innerHTML = tableHTML;

        }
    }
    

}

function inicializarCargaEdicion() {
}

function recopilarDatos() {
    const cliente = document.getElementById('cliente');
    if (!cliente) {

        return null;
    }
    
    const clienteValue = cliente.value;
    const productos = [];
    

    
    document.querySelectorAll('.producto-card').forEach((item, index) => {

        const nombre = item.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = item.querySelector('textarea[name*="descripcion"]')?.value || '';
        const cantidad = item.querySelector('input[name*="cantidad"]')?.value || 1;
        
        // Obtener tallas seleccionadas (desde botones activos)
        const tallasSeleccionadas = [];
        
        // Buscar tallas en el campo hidden que se actualiza con agregarTallasSeleccionadas()
        const tallasHidden = item.querySelector('input[name*="tallas"][type="hidden"]');
        let tallasData = ''; // Mantener el JSON original con géneros
        
        if (tallasHidden && tallasHidden.value) {
            try {
                // Intentar parsear como JSON (nuevo formato con géneros)
                const tallasJson = JSON.parse(tallasHidden.value);
                if (typeof tallasJson === 'object' && tallasJson !== null) {
                    // Mantener el JSON original con géneros para el backend
                    tallasData = tallasHidden.value;
                    
                    // Para debugging y compatibilidad, también extraer las tallas simples
                    Object.values(tallasJson).forEach(tallasGenero => {
                        if (Array.isArray(tallasGenero)) {
                            tallasSeleccionadas.push(...tallasGenero);
                        }
                    });
                } else {
                    // Es formato antiguo separado por comas
                    tallasSeleccionadas.push(...tallasHidden.value.split(', ').filter(t => t.trim()));
                    tallasData = tallasHidden.value; // Mantener el formato original
                }
            } catch (e) {
                // Si no es JSON, usar formato antiguo separado por comas
                tallasSeleccionadas.push(...tallasHidden.value.split(', ').filter(t => t.trim()));
                tallasData = tallasHidden.value; // Mantener el formato original
            }
        }
        
        // Alternativa: buscar botones activos directamente
        if (tallasSeleccionadas.length === 0) {
            item.querySelectorAll('.talla-btn.activo').forEach(btn => {
                tallasSeleccionadas.push(btn.dataset.talla);
            });
        }
        
        // Obtener fotos de esta prenda
        const productoId = item.dataset.productoId;
        
        // Opción 1: Desde fotosSeleccionadas (archivos File objects)
        let fotos = [];
        if (fotosSeleccionadas && fotosSeleccionadas[productoId]) {
            // Guardar los archivos File completos, NO solo el nombre
            fotos = fotosSeleccionadas[productoId];

        }
        
        // Opción 2: Desde globalThis.imagenesEnMemoria.prendaConIndice (con índice de prenda)
        let fotosConIndice = [];
        if (globalThis.imagenesEnMemoria && globalThis.imagenesEnMemoria.prendaConIndice) {
            fotosConIndice = globalThis.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);

            
            // Si hay fotos con índice, usarlas en lugar de fotosSeleccionadas
            if (fotosConIndice.length > 0) {
                fotos = fotosConIndice.map(p => p.file);

            }
        }
        
        // Obtener telas de esta prenda (desde telasSeleccionadas o telaConIndice)
        let telas = [];
        
        // OPCIÓN 1: Buscar en globalThis.telasSeleccionadas (la estructura correcta)
        if (globalThis.telasSeleccionadas && globalThis.telasSeleccionadas[productoId]) {
            const telasObj = globalThis.telasSeleccionadas[productoId];

            
            // telasObj es un objeto con índices como claves: {'0': [files], '1': [files]}
            for (let telaIdx in telasObj) {
                if (telasObj.hasOwnProperty(telaIdx) && Array.isArray(telasObj[telaIdx])) {
                    const fotosDelaTela = telasObj[telaIdx];

                    
                    // Agregar cada foto con información de su índice de tela
                    fotosDelaTela.forEach((foto, fotoIdx) => {
                        if (foto instanceof File) {
                            telas.push({
                                telaIndex: parseInt(telaIdx),
                                fotoIndex: fotoIdx,
                                file: foto
                            });
                        }
                    });
                }
            }

        }
        
        // OPCIÓN 2: Fallback - Buscar en globalThis.imagenesEnMemoria.telaConIndice (compatibilidad)
        if (telas.length === 0 && globalThis.imagenesEnMemoria && globalThis.imagenesEnMemoria.telaConIndice) {
            const telasEncontradas = globalThis.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
            if (telasEncontradas.length > 0) {
                telas = telasEncontradas.map(t => t.file);

            }
        }
        
        console.log(' Recopilando prenda:', {
            nombre: nombre,
            tallas: tallasSeleccionadas,
            fotos_desde_fotosSeleccionadas: fotos,
            fotos_desde_prendaConIndice: fotosConIndice.length,
            telas: telas,
            telas_length: telas.length,
            telas_debug: telas.map((t, i) => ({indice: i, telaIndex: t.telaIndex, tiene_file: !!t.file, fileName: t.file?.name})),
            productoId: productoId,
            prendaIndex: index
        });
        
        // Capturar variaciones (color, tela, manga, reflectivo, etc.)
        const variantes = {};
        const observacionesVariantes = [];
        
        // NOTA: El género NO se captura de variantes, se maneja en el sistema de tallas
        
        // Capturar MÚLTIPLES TELAS (color, tela, referencia)
        const telasFila = [];
        const tbody = item.querySelector('.telas-tbody');
        if (tbody) {
            tbody.querySelectorAll('.fila-tela').forEach((fila, telaIndex) => {
                const colorInput = fila.querySelector('.color-input');
                const telaInput = fila.querySelector('.tela-input');
                const referenciaInput = fila.querySelector('.referencia-input');
                
                const color = colorInput?.value || '';
                const tela = telaInput?.value || '';
                const referencia = referenciaInput?.value || '';
                // Solo agregar si al menos uno de los campos tiene valor
                if (color || tela || referencia) {
                    telasFila.push({
                        indice: telaIndex,
                        color: color,
                        tela: tela,
                        referencia: referencia
                    });

                }
            });
        }
        
        // Guardar las telas en variantes
        if (telasFila.length > 0) {
            variantes.telas_multiples = telasFila;

        } else {
            // Si no hay múltiples telas, capturar la primera (compatibilidad)
            const colorInput = item.querySelector('.color-input');
            const telaInput = item.querySelector('.tela-input');
            const referenciaInput = item.querySelector('.referencia-input');
            
            if (colorInput && colorInput.value) {
                variantes.color = colorInput.value;
            }
            if (telaInput && telaInput.value) {
                variantes.tela = telaInput.value;
            }
            if (referenciaInput && referenciaInput.value) {
                variantes.referencia = referenciaInput.value;
            }
        }
        
        // Manga - SOLO SI ESTÁ CHECKED
        const mangaCheckbox = item.querySelector('input[name*="aplica_manga"]');
        if (mangaCheckbox && mangaCheckbox.checked) {
            // OPCIÓN 1: Buscar el select dinámico (variantes-prendas.js)
            let mangaIdInput = item.querySelector('select[data-variante="tipo_manga_id"]');
            let mangaId = null;
            let mangaNombre = null;
            
            // OPCIÓN 2: Si no está el select, buscar los inputs estáticos (create.blade.php)
            if (!mangaIdInput) {
                mangaIdInput = item.querySelector('.manga-id-input');
                const mangaInput = item.querySelector('.manga-input');
                
                if (mangaIdInput && mangaIdInput.value) {
                    mangaId = mangaIdInput.value;
                }
                if (mangaInput && mangaInput.value) {
                    mangaNombre = mangaInput.value;
                }
            } else {
                // Para el select, el valor es el ID directamente
                if (mangaIdInput.value) {
                    mangaId = mangaIdInput.value;
                    // Obtener el texto de la opción seleccionada
                    const selectedOption = mangaIdInput.options[mangaIdInput.selectedIndex];
                    if (selectedOption) {
                        mangaNombre = selectedOption.text;
                    }
                }
            }
            // Guardar el tipo de manga ID (ID del manga seleccionado)
            if (mangaId) {
                variantes.tipo_manga_id = mangaId;

            }
            
            // Guardar el tipo de manga nombre (nombre del manga seleccionado)
            if (mangaNombre) {
                variantes.tipo_manga = mangaNombre;

            }
            
            // Capturar observación de manga SOLO SI CHECKBOX ESTÁ CHECKED
            const mangaObs = item.querySelector('input[name*="obs_manga"]');
            if (mangaObs && mangaObs.value) {
                variantes.obs_manga = mangaObs.value;
                observacionesVariantes.push(`Manga: ${mangaObs.value}`);

            }
        } else {

            variantes.tipo_manga_id = null;
            variantes.tipo_manga = null;
        }
        
        // Bolsillos - SOLO SI ESTÁ CHECKED
        const bolsillosCheckbox = item.querySelector('input[name*="aplica_bolsillos"]');
        if (bolsillosCheckbox && bolsillosCheckbox.checked) {
            variantes.tiene_bolsillos = true;
            // Capturar observación de bolsillos SOLO SI CHECKBOX ESTÁ CHECKED
            const bolsillosObs = item.querySelector('input[name*="obs_bolsillos"]');
            if (bolsillosObs && bolsillosObs.value) {
                variantes.obs_bolsillos = bolsillosObs.value;
                observacionesVariantes.push(`Bolsillos: ${bolsillosObs.value}`);

            }

        } else {
            variantes.tiene_bolsillos = false;

        }
        
        // Broche/Botón - SOLO SI ESTÁ CHECKED
        const brocheCheckbox = item.querySelector('input[name*="aplica_broche"]');
        if (brocheCheckbox && brocheCheckbox.checked) {
            const brocheSelect = item.querySelector('select[name*="tipo_broche_id"]');
            // Guardar el tipo_broche_id (1 para Broche, 2 para Botón)
            if (brocheSelect && brocheSelect.value) {
                variantes.tipo_broche_id = brocheSelect.value;

            }
            
            // Capturar observación de broche SOLO SI CHECKBOX ESTÁ CHECKED
            const brocheObs = item.querySelector('input[name*="obs_broche"]');
            if (brocheObs && brocheObs.value) {
                variantes.obs_broche = brocheObs.value;
                observacionesVariantes.push(`Broche: ${brocheObs.value}`);

            }
        } else {

            variantes.tipo_broche_id = null;
        }
        
        // Reflectivo - SOLO SI ESTÁ CHECKED
        const reflectivoCheckbox = item.querySelector('input[name*="aplica_reflectivo"]');
        if (reflectivoCheckbox && reflectivoCheckbox.checked) {
            variantes.tiene_reflectivo = true;
            // Capturar observación de reflectivo SOLO SI CHECKBOX ESTÁ CHECKED
            const reflectivoObs = item.querySelector('input[name*="obs_reflectivo"]');
            if (reflectivoObs && reflectivoObs.value) {
                variantes.obs_reflectivo = reflectivoObs.value;
                observacionesVariantes.push(`Reflectivo: ${reflectivoObs.value}`);

            }

        } else {
            variantes.tiene_reflectivo = false;

        }
        
        // Agregar todas las observaciones como descripción_adicional
        if (observacionesVariantes.length > 0) {
            variantes.descripcion_adicional = observacionesVariantes.join(' | ');
        } else {

        }
        
        //  CAPTURAR TIPO DE JEAN/PANTALÓN

        
        // Buscar en formulario estático (productos_prenda)
        let esJeanPantalonInput = item.querySelector('.es-jean-pantalon-hidden');
        let tipoJeanPantalonSelect = item.querySelector('select[name*="tipo_jean_pantalon"]');
        


        
        // Si no se encuentran, buscar en formulario dinámico (productos_friendly)
        if (!esJeanPantalonInput) {
            esJeanPantalonInput = item.querySelector('input[name*="[variantes][es_jean_pantalon]"]');

        }
        if (!tipoJeanPantalonSelect) {
            tipoJeanPantalonSelect = item.querySelector('select[name*="[variantes][tipo_jean_pantalon]"]');

        }
        
        //  DEBUG ADICIONAL: Verificar si el contenedor existe
        const container = item.querySelector('.tipo-jean-pantalon-inline-container');

        if (container) {


            
            // Intentar encontrar directamente en el contenedor
            const hiddenInContainer = container.querySelector('.es-jean-pantalon-hidden');
            const selectInContainer = container.querySelector('select[name*="tipo_jean_pantalon"]');



            
            if (selectInContainer) {


            }
        }
        
        if (esJeanPantalonInput || tipoJeanPantalonSelect) {
            // Capturar es_jean_pantalon (0 o 1)
            if (esJeanPantalonInput) {
                variantes.es_jean_pantalon = esJeanPantalonInput.value;

            }
            
            // Capturar tipo_jean_pantalon (SKINNY, SLIM, RECTO, etc.)
            if (tipoJeanPantalonSelect && tipoJeanPantalonSelect.value) {
                variantes.tipo_jean_pantalon = tipoJeanPantalonSelect.value;

            }
        } else {

        }
        
        //  CAPTURAR GENERO_ID desde el input hidden (IMPORTANTE para "ambos")
        // NOTA: Solo se captura si tiene un valor definido
        const generoIdInput = item.querySelector('.genero-id-hidden');
        let generoNombre = '';
        
        if (generoIdInput && generoIdInput.value) {
            // Solo asignar si tiene valor (no incluir la clave si está vacío)
            variantes.genero_id = generoIdInput.value;
            
            // Mapear ID a nombre para referencia
            if (generoIdInput.value === '1') {
                generoNombre = 'Dama';
            } else if (generoIdInput.value === '2') {
                generoNombre = 'Caballero';
            }
            
            if (generoNombre) {
                variantes.genero = generoNombre;
            }

        } else {
            // Si no existe o está vacío, NO incluir la clave en variantes
            // genero_id = null en backend significa "aplica a ambos géneros"

        }

        //  CAPTURAR PRENDA DE BODEGA (checkbox)
        const prendaBodegaCheckbox = item.querySelector('input[name*="prenda_bodega"]');
        if (prendaBodegaCheckbox) {
            // Capturar si está checked (true/false)
            variantes.prenda_bodega = prendaBodegaCheckbox.checked;

        } else {

        }
        
        console.log('RESUMEN VARIANTES CAPTURADAS:', {
            ' Color': variantes.color || '(vacío)',
            ' Tela': variantes.tela || '(vacío)',
            ' Referencia': variantes.referencia || '(vacío)',
            ' Género ID': variantes.genero_id || '(NO CAPTURADO)',
            ' Es Jean/Pantalón': variantes.es_jean_pantalon || '(NO CAPTURADO)',
            ' Tipo Jean/Pantalón': variantes.tipo_jean_pantalon || '(NO CAPTURADO)',
            ' Tipo Manga ID': variantes.tipo_manga_id || '(NO CAPTURADO)',
            ' Manga Nombre': variantes.manga_nombre || '(NO CAPTURADO)',
            ' Obs Manga': variantes.obs_manga || '(vacío)',
            ' Tiene Bolsillos': variantes.tiene_bolsillos || false,
            ' Obs Bolsillos': variantes.obs_bolsillos || '(vacío)',
            ' Tipo Broche ID': variantes.tipo_broche_id || '(vacío)',
            ' Obs Broche': variantes.obs_broche || '(vacío)',
            ' Tiene Reflectivo': variantes.tiene_reflectivo || false,
            ' Obs Reflectivo': variantes.obs_reflectivo || '(vacío)',
            'Descripción Adicional': variantes.descripcion_adicional || '(vacío)',
            ' Prenda de Bodega': variantes.prenda_bodega || false,
            'Todas las keys': Object.keys(variantes)
        });
        
        if (nombre.trim()) {
            const producto = {
                productoId: productoId,
                nombre_producto: nombre,
                descripcion: descripcion,
                cantidad: parseInt(cantidad) || 1,
                tallas: tallasData,
                fotos: fotos,
                telas: telas,
                variantes: variantes
            };
            
            productos.push(producto);
        }
    });
    

    productos.forEach((prod, idx) => {
        console.log(`  [${idx + 1}] ${prod.nombre_producto}:`, {
            ' Fotos': prod.fotos.length,
            ' Telas': prod.telas.length,
            ' Tallas': prod.tallas.length,
            ' Variantes': Object.keys(prod.variantes).length
        });
    });
    
    // Verificar imágenes en memoria
    // ========== PASO 4: LOGO ==========
    
    // Recopilar técnicas
    const contenedorTecnicas = document.getElementById('tecnicas_seleccionadas');

    if (contenedorTecnicas) {


    }
    
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas > div').forEach(tag => {
        const input = tag.querySelector('input[name="tecnicas[]"]');
        if (input) {

            tecnicas.push(input.value);
        }
    });


    
    // Recopilar observaciones técnicas
    const observaciones_tecnicas = document.getElementById('observaciones_tecnicas')?.value || '';

    
    // Recopilar ubicaciones desde paso3_secciones_datos o seccionesSeleccionadasFriendly
    const ubicaciones = [];
    
    // Obtener el tipo de cotización desde el input oculto
    const tipoCotizacionInput = document.getElementById('tipo_cotizacion');
    const tipoCotizacion = tipoCotizacionInput?.value || '';
    



    
    // SI ES COTIZACIÓN COMBINADA (PL), BUSCAR EN PASO 4 (REFLECTIVO)
    if (tipoCotizacion === 'PL' && Array.isArray(globalThis.ubicacionesReflectivo) && globalThis.ubicacionesReflectivo.length > 0) {

        globalThis.ubicacionesReflectivo.forEach(ubic => {
            ubicaciones.push({
                ubicacion: ubic.ubicacion,
                descripcion: ubic.descripcion
            });
        });
    } 
    // SI NO, BUSCAR EN PASO 3 (LOGO)
    else {

    
    // Primero intentar desde el campo oculto paso3_secciones_datos (paso-tres.blade.php)
    const paso3_secciones_campo = document.getElementById('paso3_secciones_datos');
    if (paso3_secciones_campo && paso3_secciones_campo.value) {
        try {
            const paso3Secciones = JSON.parse(paso3_secciones_campo.value);
            if (Array.isArray(paso3Secciones)) {
                paso3Secciones.forEach(seccion => {
                    if (seccion.ubicacion && seccion.opciones && seccion.opciones.length > 0) {
                        ubicaciones.push({
                             ubicacion: seccion.ubicacion,
                             opciones: seccion.opciones,
                             tallas: seccion.tallas,
                             observaciones: seccion.observaciones || ''
                        });
                    }
                });
            }
        } catch (e) {

        }
    }
    
    // Si no hay datos en paso3_secciones_datos, intentar desde globalThis.seccionesSeleccionadasFriendly
    if (ubicaciones.length === 0 && typeof globalThis.seccionesSeleccionadasFriendly !== 'undefined' && Array.isArray(globalThis.seccionesSeleccionadasFriendly)) {
        globalThis.seccionesSeleccionadasFriendly.forEach(seccion => {
            if (seccion.ubicacion && seccion.opciones && seccion.opciones.length > 0) {
                ubicaciones.push({
                    ubicacion: seccion.ubicacion,
                    opciones: seccion.opciones,
                    tallas: seccion.tallas || [],
                    observaciones: seccion.observaciones || ''
                });
            }
        });
    }
    



    } // Cierre del bloque else
    
    // Recopilar observaciones generales CON TIPO Y VALOR como objetos
    const observaciones_generales = [];
    
    document.querySelectorAll('#observaciones_lista > div').forEach(obs => {
        const textoInput = obs.querySelector('input[name="observaciones_generales[]"]');
        const checkboxInput = obs.querySelector('input[name="observaciones_check[]"]');
        const valorInput = obs.querySelector('input[name="observaciones_valor[]"]');
        const checkboxModeDiv = obs.querySelector('.obs-checkbox-mode');
        const textModeDiv = obs.querySelector('.obs-text-mode');
        
        const texto = textoInput?.value || '';
        
      if (texto.trim()) {
            // Verificar si está en modo texto (si el div de texto está visible)
            const esModoTexto = textModeDiv?.style.display !== 'none';
            const esModoCheckbox = checkboxModeDiv?.style.display !== 'none';
            const checked = checkboxInput?.checked ?? false;
            const valorExtra = (valorInput?.value || '').trim();
            // Regla: si el checkbox está activo, el valor que se debe guardar es el input adicional
            // (ej. "200+"). Si no hay valor, guardar 'on' para compatibilidad.
            if (checked) {
                observaciones_generales.push({
                    tipo: 'checkbox',
                    texto: texto,
                    valor: valorExtra || 'on'
                });
            } else if (esModoTexto) {
                // Modo texto: guardar objeto con tipo, texto y valor
                observaciones_generales.push({
                    tipo: 'texto',
                    texto: texto,
                    valor: valorExtra
                });
            } else if (esModoCheckbox) {
                observaciones_generales.push({
                    tipo: 'checkbox',
                    texto: texto,
                    valor: ''
                });
            } else {
                // Por defecto, asumir modo checkbox
                observaciones_generales.push({
                    tipo: 'checkbox',
                    texto: texto,
                    valor: ''
                });
            }
        }
    });


    
    // Obtener la fecha seleccionada
    const fechaInput = document.getElementById('fechaActual');
    let fechaCotizacion = null;
    if (fechaInput && fechaInput.value) {
        fechaCotizacion = fechaInput.value; // Formato YYYY-MM-DD
    }
    
    // Capturar imágenes de logo desde memoria
    const logoImagenes = globalThis.imagenesEnMemoria?.logo || [];
    
    // Obtener descripción del logo
    const descripcionLogo = document.getElementById('descripcion_logo')?.value || '';
    console.log('Descripción del logo capturada:', {
        elemento_encontrado: !!document.getElementById('descripcion_logo'),
        valor: descripcionLogo,
        longitud: descripcionLogo.length
    });

    // ========== PASO 4: REFLECTIVO ==========

    // Recopilar datos del reflectivo
    const descripcionReflectivo = document.getElementById('descripcion_reflectivo')?.value || '';
    const ubicacionReflectivo = document.getElementById('ubicacion_reflectivo')?.value || '';
    // Recopilar observaciones generales del reflectivo
    const observacionesReflectivo = [];
    if (typeof observacionesReflectivo !== 'undefined' && Array.isArray(observacionesReflectivo)) {
        observacionesReflectivo.forEach(obs => {
            observacionesReflectivo.push({
                tipo: obs.tipo || 'texto',
                valor: obs.valor || '',
                texto: obs.texto || ''
            });
        });
    }

    // Capturar imágenes del reflectivo desde memoria
    const reflectivoImagenes = globalThis.imagenesEnMemoria?.reflectivo || [];
    
    return { 
        cliente: clienteValue, 
        fecha_cotizacion: fechaCotizacion,
        productos: productos,
        tecnicas: tecnicas,
        observaciones_tecnicas,
        ubicaciones,
        observaciones_generales,
        descripcion_logo: descripcionLogo,
        especificaciones: globalThis.especificacionesSeleccionadas || {},
        logo: {
            imagenes: logoImagenes
        },
        reflectivo: {
            descripcion: descripcionReflectivo,
            ubicacion: ubicacionReflectivo,
            observaciones_generales: observacionesReflectivo,
            imagenes: reflectivoImagenes
        }
    };
}
