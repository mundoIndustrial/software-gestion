/**
 * SISTEMA DE COTIZACIONES - ORQUESTACIÓN E INICIALIZACIÓN
 * Responsabilidad: Inicializar el sistema, gestionar el ciclo de vida
 */

// Variables globales
window.imagenesEnMemoria = { 
    prenda: [], 
    tela: [], 
    logo: [],
    prendaConIndice: [],  // Fotos de prendas con índice
    telaConIndice: []     // Fotos de telas con índice
};
window.especificacionesSeleccionadas = {};

// Mapeo de géneros a IDs (desde generos_prenda tabla)
const GENEROS_MAP = {
    'dama': 2,
    'caballero': 1
};

console.log('🔵 Sistema de cotizaciones inicializado');
console.log('📸 imagenesEnMemoria inicializado:', window.imagenesEnMemoria);

// ============ GESTIÓN DE TIPO DE COTIZACIÓN ============

/**
 * Seleccionar tipo de cotización desde las pastillas
 */
function seleccionarTipoCotizacion(tipo) {
    console.log('🎯 Seleccionando tipo de cotización:', tipo);
    
    // Mapear tipo de pastilla a tipo_venta
    const mapeos = {
        'prenda': 'M',        // Mercadería
        'logo': 'D',          // Diseño
        'prenda-bordado': 'X' // Especial
    };
    
    const tipoVenta = mapeos[tipo];
    
    if (!tipoVenta) {
        console.error('❌ Tipo de cotización desconocido:', tipo);
        return;
    }
    
    // Actualizar el input oculto
    document.getElementById('tipo_venta').value = tipoVenta;
    
    // Guardar en localStorage
    localStorage.setItem('tipo_cotizacion_seleccionado', tipo);
    localStorage.setItem('tipo_venta', tipoVenta);
    
    console.log(`✓ Tipo de cotización configurado: ${tipo} (${tipoVenta})`);
    
    // Mostrar confirmación visual
    mostrarNotificacionTipoCotizacion(tipo);
}

/**
 * Mostrar notificación de tipo seleccionado
 */
function mostrarNotificacionTipoCotizacion(tipo) {
    const info = {
        'prenda': '👕 Prendas Sin Logo',
        'logo': '🎨 Solo Logos',
        'prenda-bordado': '✨ Prendas Con Bordado/Logo'
    };
    
    const mensaje = info[tipo] || 'Tipo desconocido';
    
    // Mostrar toast
    console.log(`ℹ️ ${mensaje} seleccionado`);
}

// ============ INICIALIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado - Inicializando cotizaciones');
    
    // Ocultar navbar
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = 'none';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = 'none';
    
    // Inicializar funciones
    cargarDatosDelBorrador();
    mostrarFechaActual();
    configurarDragAndDrop();
});

window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = '';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = '';
});

// ============ CONVERTIR IMÁGENES A BASE64 ============

/**
 * Convertir un File object a Data URL (Base64)
 */
function convertirArchivoABase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            console.log(`✓ Archivo convertido a Base64:`, file.name, `(${(reader.result.length / 1024).toFixed(2)} KB)`);
            resolve({
                nombre: file.name,
                base64: reader.result,
                tipo: file.type,
                size: file.size
            });
        };
        reader.onerror = (error) => {
            console.error('❌ Error al leer archivo:', file.name, error);
            reject(error);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Convertir todas las imágenes de un producto a Base64
 */
async function convertirImagenesProducto(producto) {
    console.log(`📸 Convirtiendo imágenes del producto: ${producto.nombre_producto}`);
    
    // Convertir fotos de prenda
    if (producto.fotos && producto.fotos.length > 0) {
        try {
            producto.fotos_base64 = await Promise.all(
                producto.fotos.map(foto => convertirArchivoABase64(foto))
            );
            console.log(`✓ ${producto.fotos_base64.length} fotos de prenda convertidas`);
        } catch (error) {
            console.error('❌ Error al convertir fotos de prenda:', error);
            producto.fotos_base64 = [];
        }
    } else {
        producto.fotos_base64 = [];
    }
    
    // Convertir telas
    if (producto.telas && producto.telas.length > 0) {
        try {
            producto.telas_base64 = await Promise.all(
                producto.telas.map(tela => convertirArchivoABase64(tela))
            );
            console.log(`✓ ${producto.telas_base64.length} telas convertidas`);
        } catch (error) {
            console.error('❌ Error al convertir telas:', error);
            producto.telas_base64 = [];
        }
    } else {
        producto.telas_base64 = [];
    }
    
    // Eliminar los File objects originales (no se pueden serializar en JSON)
    delete producto.fotos;
    delete producto.telas;
    
    return producto;
}

// ============ NAVEGACIÓN ============

function irAlPaso(paso) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) formStep.classList.add('active');
    
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    const stepElement = document.querySelector(`.step[data-step="${paso}"]`);
    if (stepElement) stepElement.classList.add('active');
    
    // Si es el paso 4 (REVISAR COTIZACIÓN), actualizar resumen completo
    if (paso === 4) {
        console.log('🎯 Navegando al PASO 4: REVISAR COTIZACIÓN');
        setTimeout(() => {
            // Actualizar el resumen dinámico del paso 4
            console.log('✅ Llamando a actualizarResumenFriendly() para Paso 4');
            actualizarResumenFriendly();
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
    console.log('🔄 Actualizando resumen del paso 4...');
    
    // 1. INFORMACIÓN DEL CLIENTE
    const cliente = document.getElementById('cliente');
    const resumenCliente = document.getElementById('resumen_cliente');
    if (resumenCliente && cliente) {
        resumenCliente.textContent = cliente.value || '-';
        console.log('✅ Cliente actualizado:', cliente.value);
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
        console.log('✅ Fecha actualizada:', fechaTexto);
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
            tipoDetectado = '🎯 Combinada';
        } else if (tienePrendas) {
            tipoDetectado = '👕 Solo Prendas';
        } else if (tieneLogo || tieneTecnicas) {
            tipoDetectado = '🎨 Solo Logo/Bordado';
        } else {
            tipoDetectado = '-';
        }
        
        resumenTipo.textContent = tipoDetectado;
        console.log('✅ Tipo actualizado (dinámico):', tipoDetectado);
    }
    
    // 4. RESUMEN DE PRENDAS (Solo si hay prendas)
    const resumenPrendas = document.getElementById('resumen_prendas');
    const resumenPrendasContainer = resumenPrendas?.closest('div[style*="background"]');
    if (resumenPrendas) {
        const prendas = document.querySelectorAll('.producto-card');
        console.log('📦 Prendas encontradas:', prendas.length);
        
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
                if (window.variacionesGuardadas && window.variacionesGuardadas[index]) {
                    const varGuardadas = window.variacionesGuardadas[index];
                    if (varGuardadas.tallas && varGuardadas.tallas.trim() !== '') {
                        tallasTexto = varGuardadas.tallas;
                        console.log('📏 Tallas desde guardadas:', tallasTexto);
                    }
                } 
                
                // Si aún no hay tallas, buscar en DOM
                if (tallasTexto === 'Sin tallas') {
                    const tallas = prenda.querySelectorAll('button[data-talla].active');
                    if (tallas.length > 0) {
                        tallasTexto = Array.from(tallas).map(t => t.textContent).join(', ');
                        console.log('📏 Tallas desde DOM:', tallasTexto);
                    }
                }
                
                // Obtener variaciones desde window.variacionesGuardadas (si existe)
                let variacionesHTML = '';
                
                if (window.variacionesGuardadas && window.variacionesGuardadas[index]) {
                    const varGuardadas = window.variacionesGuardadas[index];
                    console.log('🎨 Variaciones desde guardadas:', varGuardadas);
                    
                    // Construir HTML para variaciones CON observaciones
                    let variacionesArray = [];
                    
                    if (varGuardadas.color) variacionesArray.push(`Color: ${varGuardadas.color}`);
                    if (varGuardadas.tela) variacionesArray.push(`Tela: ${varGuardadas.tela}`);
                    if (varGuardadas.referencia) variacionesArray.push(`Ref: ${varGuardadas.referencia}`);
                    
                    // Manga - solo mostrar si existe nombre
                    if (varGuardadas.manga) {
                        let mangaTexto = varGuardadas.manga;
                        if (varGuardadas.obsManga) mangaTexto += ` (${varGuardadas.obsManga})`;
                        variacionesArray.push(`Manga: ${mangaTexto}`);
                    }
                    
                    // Bolsillos - sin el "Sí", ya está implícito
                    if (varGuardadas.bolsillos) {
                        let bolsillosTexto = 'Bolsillos';
                        if (varGuardadas.obsBolsillos) bolsillosTexto += ` (${varGuardadas.obsBolsillos})`;
                        variacionesArray.push(bolsillosTexto);
                    }
                    
                    // Tipo de cierre (Botón, Broche, etc) - solo mostrar el nombre sin prefijo
                    if (varGuardadas.broche) {
                        let cierreTexto = varGuardadas.broche;
                        if (varGuardadas.obsBroche) cierreTexto += ` (${varGuardadas.obsBroche})`;
                        variacionesArray.push(cierreTexto);
                    }
                    
                    // Reflectivo - sin el "Sí"
                    if (varGuardadas.reflectivo) {
                        let reflectivoTexto = 'Reflectivo';
                        if (varGuardadas.obsReflectivo) reflectivoTexto += ` (${varGuardadas.obsReflectivo})`;
                        variacionesArray.push(reflectivoTexto);
                    }
                    
                    variacionesHTML = variacionesArray.join(' | ');
                } else {
                    // Si no hay datos guardados, buscar en el DOM
                    let variacionesArray = [];
                    
                    const colorInputs = prenda.querySelectorAll('input[name*="color"]');
                    if (colorInputs.length > 0) {
                        colorInputs.forEach(inp => {
                            if (inp.value) variacionesArray.push(`Color: ${inp.value}`);
                        });
                    }
                    
                    const telaSelects = prenda.querySelectorAll('select[name*="tela"]');
                    if (telaSelects.length > 0) {
                        telaSelects.forEach(sel => {
                            if (sel.value) variacionesArray.push(`Tela: ${sel.value}`);
                        });
                    }
                    
                    const refInputs = prenda.querySelectorAll('input[name*="referencia"]');
                    if (refInputs.length > 0) {
                        refInputs.forEach(inp => {
                            if (inp.value) variacionesArray.push(`Ref: ${inp.value}`);
                        });
                    }
                    
                    const mangaInputs = prenda.querySelectorAll('input[name*="tipo_manga"]:not([type="hidden"])');
                    if (mangaInputs.length > 0) {
                        mangaInputs.forEach(inp => {
                            if (inp.value) variacionesArray.push(`Manga: ${inp.value}`);
                        });
                    }
                    
                    const brocheSelects = prenda.querySelectorAll('select[name*="tipo_broche"]');
                    if (brocheSelects.length > 0) {
                        brocheSelects.forEach(sel => {
                            const selectedText = sel.options[sel.selectedIndex]?.text || '';
                            if (selectedText && selectedText !== '-') variacionesArray.push(`Broche: ${selectedText}`);
                        });
                    }
                    
                    const bolsillosCheckbox = prenda.querySelector('input[name*="tiene_bolsillos"]');
                    if (bolsillosCheckbox?.checked) variacionesArray.push('Bolsillos: Sí');
                    
                    const reflectivoCheckbox = prenda.querySelector('input[name*="tiene_reflectivo"]');
                    if (reflectivoCheckbox?.checked) variacionesArray.push('Reflectivo: Sí');
                    
                    variacionesHTML = variacionesArray.join(' | ');
                }
                
                const div = document.createElement('div');
                div.style.cssText = 'padding: 15px; background: #fff; border-left: 4px solid #3498db; border-radius: 4px; margin-bottom: 10px;';
                
                let html = `<div style="margin-bottom: 8px;">
                    <strong style="font-size: 1.05rem; color: #0066cc;">👕 Prenda ${index + 1}: ${nombre}</strong>
                </div>`;
                
                if (descripcion) {
                    html += `<div style="margin-bottom: 6px; padding-left: 10px; border-left: 2px solid #95a5a6;">
                        <small style="color: #666;"><strong>Descripción:</strong> ${descripcion}</small>
                    </div>`;
                }
                
                html += `<div style="margin-bottom: 6px; padding-left: 10px; border-left: 2px solid #95a5a6;">
                    <small style="color: #666;"><strong>Tallas:</strong> ${tallasTexto}</small>
                </div>`;
                
                if (variacionesHTML) {
                    html += `<div style="padding-left: 10px; border-left: 2px solid #95a5a6;">
                        <small style="color: #666;"><strong>Variaciones:</strong></small><br>
                        <small style="color: #666; display: block; margin-top: 4px;">${variacionesHTML.split(' | ').map(v => `• ${v}`).join('<br>')}</small>
                    </div>`;
                }
                
                div.innerHTML = html;
                resumenPrendas.appendChild(div);
            });
            console.log('✅ Prendas mostradas en resumen');
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
            console.log('✅ Descripción logo actualizada');
        }
    }
    
    // 6. TÉCNICAS Y OBSERVACIÓN
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    if (resumenTecnicas) {
        // Usar variable global si está disponible (desde cargar-borrador.js)
        let tecnicasArray = window.tecnicasGuardadas || [];
        
        console.log('🎨 DEBUG Técnicas desde global:', {
            tecnicasGuardadas: window.tecnicasGuardadas,
            cantidad: tecnicasArray.length
        });
        
        resumenTecnicas.innerHTML = '';
        
        if (tecnicasArray.length === 0) {
            resumenTecnicas.innerHTML = '<p style="margin: 0; font-size: 0.95rem; color: #999; padding: 8px 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px;">No especificadas</p>';
        } else {
            // Obtener observación de técnicas (desde global si existe)
            let obsTecnicas = window.obsTecnicasGuardadas || '';
            
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
        console.log('✅ Técnicas actualizado');
    }
    
    // 6B. UBICACIONES EN LOGO
    const resumenLogoUbicacionesContainer = document.getElementById('resumen_logo_ubicaciones_container');
    const resumenLogoUbicaciones = document.getElementById('resumen_logo_ubicaciones');
    if (resumenLogoUbicaciones && resumenLogoUbicacionesContainer) {
        resumenLogoUbicaciones.innerHTML = '';
        
        // Usar variable global si está disponible (desde cargar-borrador.js)
        let ubicacionesArray = window.ubicacionesGuardadas || [];
        
        console.log('📍 DEBUG Ubicaciones desde global:', {
            ubicacionesGuardadas: window.ubicacionesGuardadas,
            cantidad: ubicacionesArray.length
        });
        
        if (ubicacionesArray.length === 0) {
            resumenLogoUbicacionesContainer.style.display = 'none';
        } else {
            resumenLogoUbicacionesContainer.style.display = 'block';
            
            ubicacionesArray.forEach((ubicacion, idx) => {
                const seccionNombre = ubicacion.seccion || 'Sección';
                const ubicacionesTexto = (ubicacion.ubicaciones_seleccionadas || []).join(', ') || 'Sin ubicaciones';
                const obs = ubicacion.observaciones ? ubicacion.observaciones : '';
                
                const divResumen = document.createElement('div');
                divResumen.style.cssText = 'padding: 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px; margin-bottom: 8px; font-size: 0.95rem;';
                
                let obsHTML = '';
                if (obs) {
                    obsHTML = `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ecf0f1; font-size: 0.85rem; color: #555;"><strong>Obs:</strong> ${obs}</div>`;
                }
                
                divResumen.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 4px; color: #0066cc;">${seccionNombre}</div>
                    <div style="margin-left: 12px; color: #555;">${ubicacionesTexto}</div>
                    ${obsHTML}
                `;
                resumenLogoUbicaciones.appendChild(divResumen);
            });
            console.log('✅ Ubicaciones en LOGO actualizadas');
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
            console.log('✅ Ubicaciones actualizadas');
        }
    }
    
    // 8. ESPECIFICACIONES
    const resumenEspecificacionesContainer = document.getElementById('resumen_especificaciones_container');
    const resumenEspecificaciones = document.getElementById('resumen_especificaciones');
    if (resumenEspecificaciones && resumenEspecificacionesContainer && window.especificacionesSeleccionadas) {
        resumenEspecificaciones.innerHTML = '';
        const especKeys = Object.keys(window.especificacionesSeleccionadas || {});
        
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
            
            Object.entries(window.especificacionesSeleccionadas).forEach(([categoria, valores]) => {
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
            console.log('✅ Especificaciones en tabla actualizadas');
        }
    }
    
    console.log('✅ Resumen del paso 4 completamente actualizado');
}

function cargarDatosDelBorrador() {
    // Implementar si es necesario cargar datos de un borrador existente
}

function recopilarDatos() {
    const cliente = document.getElementById('cliente');
    if (!cliente) {
        console.error('❌ Campo cliente no encontrado');
        return null;
    }
    
    const clienteValue = cliente.value;
    const productos = [];
    
    console.log('📦 Total de prendas encontradas:', document.querySelectorAll('.producto-card').length);
    
    document.querySelectorAll('.producto-card').forEach((item, index) => {
        console.log(`📦 Procesando prenda ${index + 1}...`);
        const nombre = item.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = item.querySelector('textarea[name*="descripcion"]')?.value || '';
        const cantidad = item.querySelector('input[name*="cantidad"]')?.value || 1;
        
        // Obtener tallas seleccionadas (desde botones activos)
        const tallasSeleccionadas = [];
        
        // Buscar tallas en el campo hidden que se actualiza con agregarTallasSeleccionadas()
        const tallasHidden = item.querySelector('input[name*="tallas"][type="hidden"]');
        if (tallasHidden && tallasHidden.value) {
            // Las tallas están separadas por comas en el campo hidden
            tallasSeleccionadas.push(...tallasHidden.value.split(', ').filter(t => t.trim()));
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
            console.log(`📸 Fotos desde fotosSeleccionadas[${productoId}]:`, fotos.length, 'archivos');
        }
        
        // Opción 2: Desde window.imagenesEnMemoria.prendaConIndice (con índice de prenda)
        let fotosConIndice = [];
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
            fotosConIndice = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
            console.log(`📸 Fotos desde prendaConIndice (índice ${index}):`, fotosConIndice.length);
            
            // Si hay fotos con índice, usarlas en lugar de fotosSeleccionadas
            if (fotosConIndice.length > 0) {
                fotos = fotosConIndice.map(p => p.file);
                console.log(`📸 Usando fotos de prendaConIndice:`, fotos.length, 'archivos');
            }
        }
        
        // Obtener telas de esta prenda (desde telasSeleccionadas o telaConIndice)
        let telas = [];
        
        // OPCIÓN 1: Buscar en window.telasSeleccionadas (la estructura correcta)
        if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
            const telasObj = window.telasSeleccionadas[productoId];
            console.log('🧵 DEBUG - telasSeleccionadas encontrado:', telasObj);
            
            // telasObj es un objeto con índices como claves: {'0': [files], '1': [files]}
            for (let telaIdx in telasObj) {
                if (telasObj.hasOwnProperty(telaIdx) && Array.isArray(telasObj[telaIdx])) {
                    const fotosDelaTela = telasObj[telaIdx];
                    console.log(`🧵 Tela ${telaIdx}: ${fotosDelaTela.length} fotos`);
                    
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
            console.log(`✅ Telas desde telasSeleccionadas: ${telas.length} archivos`);
        }
        
        // OPCIÓN 2: Fallback - Buscar en window.imagenesEnMemoria.telaConIndice (compatibilidad)
        if (telas.length === 0 && window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
            const telasEncontradas = window.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
            if (telasEncontradas.length > 0) {
                telas = telasEncontradas.map(t => t.file);
                console.log(`🧵 Telas desde telaConIndice (fallback): ${telas.length} archivos`);
            }
        }
        
        console.log('📋 Recopilando prenda:', {
            nombre: nombre,
            tallas: tallasSeleccionadas,
            fotos_desde_fotosSeleccionadas: fotos,
            fotos_desde_prendaConIndice: fotosConIndice.length,
            telas: telas,
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
                
                console.log(`🔍 DEBUG Tela ${telaIndex}:`, {
                    colorInput_encontrado: !!colorInput,
                    telaInput_encontrado: !!telaInput,
                    referenciaInput_encontrado: !!referenciaInput,
                    color,
                    tela,
                    referencia
                });
                
                // Solo agregar si al menos uno de los campos tiene valor
                if (color || tela || referencia) {
                    telasFila.push({
                        indice: telaIndex,
                        color: color,
                        tela: tela,
                        referencia: referencia
                    });
                    console.log(`🧵 Tela ${telaIndex + 1} capturada:`, { color, tela, referencia });
                }
            });
        }
        
        // Guardar las telas en variantes
        if (telasFila.length > 0) {
            variantes.telas_multiples = telasFila;
            console.log(`📝 Total de telas capturadas: ${telasFila.length}`);
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
            
            console.log('🔍 Buscando manga:', {
                checkbox_checked: mangaCheckbox.checked,
                tipo: mangaIdInput?.tagName,
                mangaId_encontrado: !!mangaId,
                mangaId_value: mangaId,
                mangaNombre_encontrado: !!mangaNombre,
                mangaNombre_value: mangaNombre
            });
            
            // Guardar el tipo de manga ID (ID del manga seleccionado)
            if (mangaId) {
                variantes.tipo_manga_id = mangaId;
                console.log('✅ tipo_manga_id capturado:', mangaId);
            }
            
            // Guardar el tipo de manga nombre (nombre del manga seleccionado)
            if (mangaNombre) {
                variantes.tipo_manga = mangaNombre;
                console.log('✅ tipo_manga capturado:', mangaNombre);
            }
            
            // Capturar observación de manga SOLO SI CHECKBOX ESTÁ CHECKED
            const mangaObs = item.querySelector('input[name*="obs_manga"]');
            if (mangaObs && mangaObs.value) {
                variantes.obs_manga = mangaObs.value;
                observacionesVariantes.push(`Manga: ${mangaObs.value}`);
                console.log('✅ obs_manga capturada:', mangaObs.value);
            }
        } else {
            console.log('ℹ️ Manga NO seleccionado - obs_manga NO se captura');
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
                console.log('✅ obs_bolsillos capturada:', bolsillosObs.value);
            }
            console.log('✅ Bolsillos SELECCIONADO');
        } else {
            variantes.tiene_bolsillos = false;
            console.log('ℹ️ Bolsillos NO seleccionado - obs_bolsillos NO se captura');
        }
        
        // Broche/Botón - SOLO SI ESTÁ CHECKED
        const brocheCheckbox = item.querySelector('input[name*="aplica_broche"]');
        if (brocheCheckbox && brocheCheckbox.checked) {
            const brocheSelect = item.querySelector('select[name*="tipo_broche_id"]');
            
            console.log('🔍 Buscando broche:', {
                checkbox_checked: brocheCheckbox.checked,
                brocheSelect_encontrado: !!brocheSelect,
                brocheSelect_value: brocheSelect?.value,
                brocheSelect_text: brocheSelect?.options[brocheSelect?.selectedIndex]?.text
            });
            
            // Guardar el tipo_broche_id (1 para Broche, 2 para Botón)
            if (brocheSelect && brocheSelect.value) {
                variantes.tipo_broche_id = brocheSelect.value;
                console.log('✅ tipo_broche_id capturado:', brocheSelect.value);
            }
            
            // Capturar observación de broche SOLO SI CHECKBOX ESTÁ CHECKED
            const brocheObs = item.querySelector('input[name*="obs_broche"]');
            if (brocheObs && brocheObs.value) {
                variantes.obs_broche = brocheObs.value;
                observacionesVariantes.push(`Broche: ${brocheObs.value}`);
                console.log('✅ obs_broche capturada:', brocheObs.value);
            }
        } else {
            console.log('ℹ️ Broche NO seleccionado - obs_broche NO se captura');
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
                console.log('✅ obs_reflectivo capturada:', reflectivoObs.value);
            }
            console.log('✅ Reflectivo SELECCIONADO');
        } else {
            variantes.tiene_reflectivo = false;
            console.log('ℹ️ Reflectivo NO seleccionado - obs_reflectivo NO se captura');
        }
        
        // Agregar todas las observaciones como descripción_adicional
        if (observacionesVariantes.length > 0) {
            variantes.descripcion_adicional = observacionesVariantes.join(' | ');
            console.log('📝 descripcion_adicional construida:', {
                observacionesCount: observacionesVariantes.length,
                observaciones: observacionesVariantes,
                descripcion_adicional: variantes.descripcion_adicional
            });
        } else {
            console.log('ℹ️ Sin observaciones de variantes para agregar a descripcion_adicional');
        }
        
        // ✅ CAPTURAR GENERO_ID desde el input hidden (IMPORTANTE para "ambos")
        // NOTA: Solo se captura si tiene un valor definido
        const generoIdInput = item.querySelector('.genero-id-hidden');
        if (generoIdInput && generoIdInput.value) {
            // Solo asignar si tiene valor (no incluir la clave si está vacío)
            variantes.genero_id = generoIdInput.value;
            console.log('✅ genero_id capturado:', variantes.genero_id);
        } else {
            // Si no existe o está vacío, NO incluir la clave en variantes
            // genero_id = null en backend significa "aplica a ambos géneros"
            console.log('ℹ️ genero_id vacío/no encontrado - no se incluye en variantes (null = ambos)');
        }
        
        console.log('📝 RESUMEN VARIANTES CAPTURADAS:', {
            '✅ Color': variantes.color || '(vacío)',
            '✅ Tela': variantes.tela || '(vacío)',
            '✅ Referencia': variantes.referencia || '(vacío)',
            '👥 Género ID': variantes.genero_id || '(NO CAPTURADO)',
            '🎽 Tipo Manga ID': variantes.tipo_manga_id || '(NO CAPTURADO)',
            '🎽 Manga Nombre': variantes.manga_nombre || '(NO CAPTURADO)',
            '🎽 Obs Manga': variantes.obs_manga || '(vacío)',
            '👖 Tiene Bolsillos': variantes.tiene_bolsillos || false,
            '👖 Obs Bolsillos': variantes.obs_bolsillos || '(vacío)',
            '🔗 Tipo Broche ID': variantes.tipo_broche_id || '(vacío)',
            '🔗 Obs Broche': variantes.obs_broche || '(vacío)',
            '⭐ Tiene Reflectivo': variantes.tiene_reflectivo || false,
            '⭐ Obs Reflectivo': variantes.obs_reflectivo || '(vacío)',
            '📝 Descripción Adicional': variantes.descripcion_adicional || '(vacío)',
            'Todas las keys': Object.keys(variantes)
        });
        
        if (nombre.trim()) {
            const producto = {
                nombre_producto: nombre,
                descripcion: descripcion,
                cantidad: parseInt(cantidad) || 1,
                tallas: tallasSeleccionadas,
                fotos: fotos,
                telas: telas,
                variantes: variantes
            };
            
            console.log('✅ PRODUCTO AGREGADO:', {
                nombre: nombre,
                tallas: tallasSeleccionadas.length,
                fotos: fotos.length,
                telas: telas.length,
                variantes_keys: Object.keys(variantes)
            });
            
            productos.push(producto);
        }
    });
    
    console.log('📦 RESUMEN PRODUCTOS RECOPILADOS:');
    productos.forEach((prod, idx) => {
        console.log(`  [${idx + 1}] ${prod.nombre_producto}:`, {
            '📸 Fotos': prod.fotos.length,
            '🧵 Telas': prod.telas.length,
            '📏 Tallas': prod.tallas.length,
            '🎨 Variantes': Object.keys(prod.variantes).length
        });
    });
    
    // Verificar imágenes en memoria
    console.log('📸 IMÁGENES EN MEMORIA:', {
        'prendaConIndice': window.imagenesEnMemoria?.prendaConIndice?.length || 0,
        'telaConIndice': window.imagenesEnMemoria?.telaConIndice?.length || 0,
        'logo': window.imagenesEnMemoria?.logo?.length || 0
    });
    
    // ========== PASO 4: LOGO ==========
    
    // Recopilar técnicas
    const contenedorTecnicas = document.getElementById('tecnicas_seleccionadas');
    console.log('🎨 Contenedor técnicas encontrado:', !!contenedorTecnicas);
    if (contenedorTecnicas) {
        console.log('🎨 innerHTML del contenedor:', contenedorTecnicas.innerHTML);
        console.log('🎨 Número de children:', contenedorTecnicas.children.length);
    }
    
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas > div').forEach(tag => {
        const input = tag.querySelector('input[name="tecnicas[]"]');
        if (input) {
            console.log('🎨 Input encontrado:', input.value);
            tecnicas.push(input.value);
        }
    });
    console.log('🎨 Técnicas recopiladas:', tecnicas);
    console.log('🎨 Elementos encontrados:', document.querySelectorAll('#tecnicas_seleccionadas > div').length);
    
    // Recopilar observaciones técnicas
    const observaciones_tecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    console.log('📝 Observaciones técnicas:', observaciones_tecnicas);
    
    // Recopilar ubicaciones desde seccionesSeleccionadasFriendly (guardadas en memoria)
    const ubicaciones = [];
    
    // Verificar si existe seccionesSeleccionadasFriendly (variable global de especificaciones.js)
    if (typeof seccionesSeleccionadasFriendly !== 'undefined' && Array.isArray(seccionesSeleccionadasFriendly)) {
        seccionesSeleccionadasFriendly.forEach(seccion => {
            if (seccion.ubicacion && seccion.opciones && seccion.opciones.length > 0) {
                ubicaciones.push({
                    seccion: seccion.ubicacion,
                    ubicaciones_seleccionadas: seccion.opciones,
                    observaciones: seccion.observaciones || ''
                });
            }
        });
    }
    
    console.log('📍 Ubicaciones recopiladas:', ubicaciones);
    console.log('📍 seccionesSeleccionadasFriendly:', typeof seccionesSeleccionadasFriendly !== 'undefined' ? seccionesSeleccionadasFriendly : 'NO DEFINIDO');
    
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
            const esModoTexto = textModeDiv && textModeDiv.style.display !== 'none';
            const esModoCheckbox = checkboxModeDiv && checkboxModeDiv.style.display !== 'none';
            
            if (esModoTexto) {
                // Modo texto: guardar objeto con tipo, texto y valor
                observaciones_generales.push({
                    tipo: 'texto',
                    texto: texto,
                    valor: valorInput?.value || ''
                });
                console.log('📝 Modo TEXTO:', texto, '=', valorInput?.value);
            } else if (esModoCheckbox) {
                // Modo checkbox: guardar objeto con tipo, texto y valor
                observaciones_generales.push({
                    tipo: 'checkbox',
                    texto: texto,
                    valor: checkboxInput?.checked ? 'on' : ''
                });
                console.log('✓ Modo CHECK:', texto, '=', checkboxInput?.checked ? 'checked' : 'unchecked');
            } else {
                // Por defecto, asumir modo checkbox
                observaciones_generales.push({
                    tipo: 'checkbox',
                    texto: texto,
                    valor: checkboxInput?.checked ? 'on' : ''
                });
            }
        }
    });
    console.log('💬 Observaciones generales recopiladas:', observaciones_generales);
    console.log('💬 Observaciones #observaciones_lista divs encontrados:', document.querySelectorAll('#observaciones_lista > div').length);
    
    // Obtener la fecha seleccionada
    const fechaInput = document.getElementById('fechaActual');
    let fechaCotizacion = null;
    if (fechaInput && fechaInput.value) {
        fechaCotizacion = fechaInput.value; // Formato YYYY-MM-DD
    }
    
    // Capturar imágenes de logo desde memoria
    const logoImagenes = window.imagenesEnMemoria?.logo || [];
    
    // Obtener descripción del logo
    const descripcionLogo = document.getElementById('descripcion_logo')?.value || '';
    console.log('📝 Descripción del logo capturada:', {
        elemento_encontrado: !!document.getElementById('descripcion_logo'),
        valor: descripcionLogo,
        longitud: descripcionLogo.length
    });

    // ========== PASO 4: REFLECTIVO ==========

    // Recopilar datos del reflectivo
    const descripcionReflectivo = document.getElementById('descripcion_reflectivo')?.value || '';
    const ubicacionReflectivo = document.getElementById('ubicacion_reflectivo')?.value || '';
    
    console.log('✨ Datos del reflectivo capturados:', {
        descripcion: descripcionReflectivo,
        ubicacion: ubicacionReflectivo
    });

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
    const reflectivoImagenes = window.imagenesEnMemoria?.reflectivo || [];
    
    return { 
        cliente: clienteValue, 
        fecha_cotizacion: fechaCotizacion,
        productos: productos,
        tecnicas: tecnicas,
        observaciones_tecnicas,
        ubicaciones,
        observaciones_generales,
        descripcion_logo: descripcionLogo,
        especificaciones: window.especificacionesSeleccionadas || {},
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

/**
 * Procesar imágenes del formulario y convertirlas a Base64
 * Retorna una promesa con el data actualizado
 */
async function procesarImagenesABase64(datos) {
    console.log('🖼️ Iniciando procesamiento de imágenes a Base64...');
    
    if (!datos.productos || datos.productos.length === 0) {
        console.log('✓ Sin productos a procesar');
        return datos;
    }
    
    try {
        // Procesar cada producto
        for (let i = 0; i < datos.productos.length; i++) {
            const producto = datos.productos[i];
            console.log(`📦 Procesando producto ${i + 1}/${datos.productos.length}: ${producto.nombre_producto}`);
            
            // Procesar fotos de prenda
            if (producto.fotos && producto.fotos.length > 0) {
                console.log(`  📸 Convirtiendo ${producto.fotos.length} foto(s) de prenda...`);
                producto.fotos_base64 = await Promise.all(
                    producto.fotos.map((foto, idx) => {
                        console.log(`    [${idx + 1}/${producto.fotos.length}] Procesando foto prenda...`);
                        return convertirArchivoABase64(foto);
                    })
                );
                console.log(`  ✅ ${producto.fotos_base64.length} foto(s) de prenda procesadas`);
            } else {
                producto.fotos_base64 = [];
            }
            
            // Procesar telas
            if (producto.telas && producto.telas.length > 0) {
                console.log(`  🧵 Convirtiendo ${producto.telas.length} tela(s)...`);
                producto.telas_base64 = await Promise.all(
                    producto.telas.map((tela, idx) => {
                        console.log(`    [${idx + 1}/${producto.telas.length}] Procesando tela...`);
                        return convertirArchivoABase64(tela);
                    })
                );
                console.log(`  ✅ ${producto.telas_base64.length} tela(s) procesada(s)`);
            } else {
                producto.telas_base64 = [];
            }
            
            // Eliminar los File objects (no se pueden serializar en JSON)
            delete producto.fotos;
            delete producto.telas;
        }
        
        // Procesar imágenes de logo
        if (datos.logo && datos.logo.imagenes && datos.logo.imagenes.length > 0) {
            console.log(`📸 Convirtiendo ${datos.logo.imagenes.length} imagen(es) de logo...`);
            datos.logo.imagenes_base64 = await Promise.all(
                datos.logo.imagenes.map((imagen, idx) => {
                    console.log(`    [${idx + 1}/${datos.logo.imagenes.length}] Procesando imagen logo...`);
                    return convertirArchivoABase64(imagen);
                })
            );
            console.log(`  ✅ ${datos.logo.imagenes_base64.length} imagen(es) de logo procesadas`);
            // Eliminar los File objects
            delete datos.logo.imagenes;
        } else {
            if (datos.logo) {
                datos.logo.imagenes_base64 = [];
            }
        }

        // Procesar imágenes de reflectivo
        if (datos.reflectivo && datos.reflectivo.imagenes && datos.reflectivo.imagenes.length > 0) {
            console.log(`📸 Convirtiendo ${datos.reflectivo.imagenes.length} imagen(es) de reflectivo...`);
            datos.reflectivo.imagenes_base64 = await Promise.all(
                datos.reflectivo.imagenes.map((imagen, idx) => {
                    console.log(`    [${idx + 1}/${datos.reflectivo.imagenes.length}] Procesando imagen reflectivo...`);
                    return convertirArchivoABase64(imagen);
                })
            );
            console.log(`  ✅ ${datos.reflectivo.imagenes_base64.length} imagen(es) de reflectivo procesadas`);
            // Eliminar los File objects
            delete datos.reflectivo.imagenes;
        } else {
            if (datos.reflectivo) {
                datos.reflectivo.imagenes_base64 = [];
            }
        }
        
        console.log('✅ TODAS LAS IMÁGENES PROCESADAS', {
            'productos': datos.productos.length,
            'fotos_procesadas': datos.productos.reduce((sum, p) => sum + (p.fotos_base64?.length || 0), 0),
            'telas_procesadas': datos.productos.reduce((sum, p) => sum + (p.telas_base64?.length || 0), 0),
            'logo_procesadas': datos.logo?.imagenes_base64?.length || 0,
            'reflectivo_procesadas': datos.reflectivo?.imagenes_base64?.length || 0
        });
        
        return datos;
    } catch (error) {
        console.error('❌ Error al procesar imágenes:', error);
        throw error;
    }
}
