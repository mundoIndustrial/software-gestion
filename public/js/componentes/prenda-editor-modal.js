/**
 * Componente: Prenda Editor Modal
 * Maneja la edición de prendas en modal SweetAlert
 * 
 * Funciones públicas:
 * - abrirEditarPrendas() - Abre lista de prendas disponibles
 * - abrirEditarPrendaEspecifica(prendasIndex) - Abre formulario de edición de prenda específica
 */

/**
 * Abrir formulario para editar prendas del pedido (lista seleccionable)
 */
function abrirEditarPrendas() {
    if (!window.datosEdicionPedido) {
        Swal.fire('Error', 'No hay datos del pedido disponibles', 'error');
        return;
    }
    const datos = window.datosEdicionPedido;
    const prendas = datos.prendas || [];

    
    // Guardar prendas en variable global para acceso desde onclick
    window.prendasEdicion = {
        pedidoId: datos.id || datos.numero_pedido,
        prendas: prendas
    };
    
    let htmlListaPrendas = '<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">';
    
    if (prendas.length === 0) {
        // Mostrar botón para agregar prenda si la lista está vacía

        htmlListaPrendas += `
            <div style="text-align: center; padding: 2rem; background: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                <p style="color: #6b7280; margin: 0 0 1rem 0;">No hay prendas agregadas aún</p>
                <button onclick="abrirAgregarPrenda()" 
                    style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor='#059669'"
                    onmouseout="this.style.backgroundColor='#10b981'">
                    ➕ Agregar Prenda
                </button>
            </div>
        `;
    } else {
        // Mostrar lista de prendas

        prendas.forEach((prenda, idx) => {
            const nombrePrenda = prenda.nombre_prenda || prenda.nombre || 'Prenda sin nombre';
            const cantTallas = prenda.tallas ? Object.keys(prenda.tallas).length : 0;
            const cantProcesos = (prenda.procesos || []).length;
            
            htmlListaPrendas += `
                <button onclick="abrirEditarPrendaEspecifica(${idx})" 
                    style="background: white; border: 2px solid #3b82f6; border-radius: 8px; padding: 1rem; text-align: left; cursor: pointer; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f5f3ff'; this.style.borderColor='#7c3aed';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#1e40af';">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;">PRENDA ${idx + 1}: ${nombrePrenda.toUpperCase()}</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">${prenda.descripcion || 'Sin descripción'}</p>
                            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af;">
                                 Tallas: ${cantTallas} |  Procesos: ${cantProcesos}
                            </div>
                        </div>
                        <span style="background: #1e40af; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;"> Editar</span>
                    </div>
                </button>
            `;
        });
    }
    
    htmlListaPrendas += '</div>';
    
    Swal.fire({
        title: ' Selecciona una Prenda para Editar',
        html: htmlListaPrendas,
        width: '600px',
        showConfirmButton: false,
        confirmButtonText: 'Cerrar',
        showCancelButton: true,
        cancelButtonText: 'Volver',
        customClass: {
            container: 'swal-centered-container',
            popup: 'swal-centered-popup'
        },
        didOpen: (modal) => {
            const container = modal.closest('.swal2-container');
            if (container) {
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.height = '100vh';
                container.style.zIndex = '999998';
            }
            modal.style.marginTop = '0';
            modal.style.marginBottom = '0';
        }
    });
}

/**
 * Abrir modal de edición para una prenda específica
 * USA EL MODAL DE "AGREGAR PRENDA NUEVA" para la edición
 */
async function abrirEditarPrendaEspecifica(prendasIndex) {
    console.log('🔥 [EDITAR-PRENDA] Abriendo modal de edición con índice:', prendasIndex);
    
    if (!window.prendasEdicion) {
        console.error(' No hay datos de prendas disponibles');
        Swal.fire('Error', 'No hay datos de prendas disponibles', 'error');
        return;
    }
    
    const prenda = window.prendasEdicion.prendas[prendasIndex];
    const pedidoId = window.prendasEdicion.pedidoId;
    
    if (!prenda) {
        console.error(' Prenda no encontrada en índice:', prendasIndex);
        Swal.fire('Error', 'Prenda no encontrada', 'error');
        return;
    }
    
    console.log('✅ [EDITAR-PRENDA] Prenda encontrada:', {
        nombre: prenda.nombre_prenda,
        id: prenda.id,
        pedidoId: pedidoId
    });
    
    try {
        // OBTENER DATOS FRESCOS DE LA BD
        console.log('📡 [EDITAR-PRENDA] Obteniendo datos frescos del servidor...');
        const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}/prenda/${prenda.id}/datos`);
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: No se pudieron obtener los datos`);
        }
        
        const resultado = await response.json();
        
        if (!resultado.success || !resultado.prenda) {
            throw new Error('No se recibieron datos válidos del servidor');
        }
        
        const prendaCompleta = resultado.prenda;
        console.log('✅ [EDITAR-PRENDA] Datos del servidor recibidos:', {
            tallas_dama: prendaCompleta.tallas_dama?.length || 0,
            tallas_caballero: prendaCompleta.tallas_caballero?.length || 0,
            colores_telas: prendaCompleta.colores_telas?.length || 0,
            variantes: prendaCompleta.variantes?.length || 0,
            procesos: prendaCompleta.procesos?.length || 0
        });
        
        // TRANSFORMAR DATOS PARA EL MODAL
        console.log('🔄 [EDITAR-PRENDA] Transformando datos para el modal...');
        
        // Función auxiliar para agregar /storage/ a URLs
        const agregarStorage = (url) => {
            if (!url) return '';
            if (url.startsWith('/')) return url;
            if (url.startsWith('http')) return url;
            return '/storage/' + url;
        };

        // Transformar colores/telas a telasAgregadas
        const telasAgregadas = [];
        if (prendaCompleta.colores_telas && Array.isArray(prendaCompleta.colores_telas)) {
            prendaCompleta.colores_telas.forEach((ct) => {
                console.log('🔍 [TRANSFORMAR-COLORES-TELAS] Color-tela objeto:', ct);
                telasAgregadas.push({
                    id: ct.id,  // ID de la relación prenda_pedido_colores_telas
                    color_id: ct.color_id,
                    color: ct.color?.nombre || ct.color_nombre || '',
                    codigo_color: ct.color?.codigo || ct.color_codigo || '',
                    tela_id: ct.tela_id,
                    tela: ct.tela?.nombre || ct.tela_nombre || '',
                    nombre_tela: ct.tela?.nombre || ct.tela_nombre || '',
                    referencia: ct.referencia || ct.tela?.referencia || ct.tela_referencia || '',
                    imagenes_count: (ct.imagenes_tela || ct.fotos_tela || []).length,
                    imagenes: (ct.imagenes_tela || ct.fotos_tela || []).map(f => {
                        const urlConStorage = agregarStorage(f.ruta_webp || f.ruta_original);
                        return {
                            id: f.id,  // ID de la foto
                            url: urlConStorage,
                            ruta: urlConStorage,
                            urlDesdeDB: true,
                            ruta_original: f.ruta_original,
                            ruta_webp: f.ruta_webp
                        };
                    })
                });
                console.log('✅ [TRANSFORMAR-COLORES-TELAS] Tela transformada:', telasAgregadas[telasAgregadas.length - 1]);
            });
        }
        
        console.log('✅ [EDITAR-PRENDA] telasAgregadas:', telasAgregadas.length, 'items');
        
        // Transformar tallas
        const tallasPorGenero = {};
        
        if (prendaCompleta.tallas_dama && Array.isArray(prendaCompleta.tallas_dama)) {
            tallasPorGenero.DAMA = {};
            prendaCompleta.tallas_dama.forEach(t => {
                tallasPorGenero.DAMA[t.talla] = t.cantidad || 0;
            });
        }
        
        if (prendaCompleta.tallas_caballero && Array.isArray(prendaCompleta.tallas_caballero)) {
            tallasPorGenero.CABALLERO = {};
            prendaCompleta.tallas_caballero.forEach(t => {
                tallasPorGenero.CABALLERO[t.talla] = t.cantidad || 0;
            });
        }
        
        console.log('✅ [EDITAR-PRENDA] Tallas por género:', tallasPorGenero);
        
        // Transformar tallas a array para compatibilidad con el modal
        const tallasArray = [];
        Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                if (cantidad > 0) {
                    tallasArray.push({
                        genero: genero,
                        talla: talla,
                        cantidad: cantidad
                    });
                }
            });
        });
        
        // Transformar a estructura con cantidades para generosConTallas
        const generosConTallasEstructura = {};
        Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
            const tieneTallas = Object.values(tallas).some(cant => cant > 0);
            if (tieneTallas) {
                generosConTallasEstructura[genero.toLowerCase()] = {
                    cantidades: tallas
                };
            }
        });
        
        console.log('✅ [EDITAR-PRENDA] Tallas como array:', tallasArray);
        let variantes = {};
        if (prendaCompleta.variantes && Array.isArray(prendaCompleta.variantes) && prendaCompleta.variantes.length > 0) {
            const v = prendaCompleta.variantes[0]; // Primera variante (si hay múltiples)
            variantes = {
                tipo_manga: v.tipo_manga || '',
                tipo_manga_id: v.tipo_manga_id,
                obs_manga: v.manga_obs || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                obs_bolsillos: v.bolsillos_obs || '',
                tipo_broche: v.tipo_broche_boton || '',
                tipo_broche_id: v.tipo_broche_boton_id,
                obs_broche: v.broche_boton_obs || ''
            };
        }
        
        console.log('✅ [EDITAR-PRENDA] Variantes transformadas:', variantes);
        
        // Preparar datos para el modal
        const prendaParaEditar = {
            nombre_prenda: prendaCompleta.nombre_prenda || '',
            nombre_producto: prendaCompleta.nombre_prenda || '',
            descripcion: prendaCompleta.descripcion || '',
            origen: prenda.origen || 'bodega',
            imagenes: (prendaCompleta.imagenes || []).map(img => {
                const url = typeof img === 'string' ? img : (img.ruta_webp || img.ruta_original || img.url);
                return {
                    url: agregarStorage(url),
                    ruta: agregarStorage(url),
                    urlDesdeDB: true
                };
            }),
            telasAgregadas: telasAgregadas,
            tallas: tallasArray,
            generosConTallas: generosConTallasEstructura,
            procesos: (prendaCompleta.procesos || []).map(proc => {
                console.log(' [EDITAR-PRENDA-PROCESOS] Proceso bruto del servidor:', {
                    ...proc,
                    imagenes: `Array(${proc.imagenes?.length || 0})`
                });
                
                // El backend retorna 'tipo' directamente (ej: 'Reflectivo')
                const tipoProcesoBackend = proc.tipo || proc.tipo_proceso || '';
                
                console.log(' [EDITAR-PRENDA-PROCESOS] Transformando proceso:', {
                    procesoId: proc.id,
                    tipoBackend: tipoProcesoBackend,
                    nombre: proc.nombre,
                    nombre_proceso: proc.nombre_proceso,
                    tieneImagenes: !!proc.imagenes,
                    countImagenes: proc.imagenes?.length || 0,
                    tieneUbicaciones: !!proc.ubicaciones,
                    ubicaciones: proc.ubicaciones
                });
                
                const procesoTransformado = {
                    ...proc,
                    imagenes: (proc.imagenes || []).map(img => {
                        // Manejar tanto strings como objetos
                        let url = '';
                        if (typeof img === 'string') {
                            // Es un string directo (ruta)
                            url = img;
                        } else if (typeof img === 'object' && img) {
                            // Es un objeto, extraer ruta
                            url = img.ruta_webp || img.ruta_original || img.url || img.ruta || '';
                        }
                        
                        console.log('  📸 Imagen transformada:', {
                            original: img,
                            urlExtraida: url,
                            urlConStorage: agregarStorage(url)
                        });
                        
                        return {
                            ...(typeof img === 'object' ? img : {}),
                            ruta_webp: agregarStorage(url),
                            ruta_original: agregarStorage(url),
                            url: agregarStorage(url)
                        };
                    })
                };
                
                console.log('  Proceso transformado:', procesoTransformado);
                return procesoTransformado;
            }),
            variantes: variantes
        };
        
        console.log('✅ [EDITAR-PRENDA] Datos listos para cargar en modal:', Object.keys(prendaParaEditar));
        console.log('🔬 [EDITAR-PRENDA] Procesos para modal:', prendaParaEditar.procesos);
        console.log('🖼️ [EDITAR-PRENDA] Imágenes para modal:', prendaParaEditar.imagenes);
        console.log('📊 [EDITAR-PRENDA] Respuesta completa del servidor:', resultado.prenda);
        
        // Cerrar el modal de seleccionar prenda
        Swal.close();
        
        // Guardar en global
        window.prendaEnEdicion = {
            pedidoId: pedidoId,
            prendasIndex: prendasIndex,
            prendaOriginal: JSON.parse(JSON.stringify(prenda)),
            esEdicion: true
        };
        
        // Abrir modal con datos transformados
        if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
            console.log('✅ [EDITAR-PRENDA] Abriendo modal con GestionItemsUI');
            
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            
            window.gestionItemsUI.prendaEditIndex = prendasIndex;
            window.gestionItemsUI.prendaEnModoEdicion = true;
            window.gestionItemsUI.abrirModalAgregarPrendaNueva();
            
            if (typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
                console.log('✅ [EDITAR-PRENDA] Cargando datos en modal');
                window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
            }
            
            console.log('✅ [EDITAR-PRENDA] Modal abierto exitosamente');
            return;
        }
        
        console.error(' GestionItemsUI no disponible');
        Swal.fire('Error', 'No se pudo abrir el modal de edición', 'error');
        
    } catch (error) {
        console.error(' [EDITAR-PRENDA] Error:', error);
        Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
    }
}

function abrirEditarProcesoEspecifico(prendasIndex, procesoIndex) {
    if (!window.prendasEdicion) {
        Swal.fire('Error', 'No hay datos de prendas disponibles', 'error');
        return;
    }
    
    const prenda = window.prendasEdicion.prendas[prendasIndex];
    const proceso = prenda.procesos[procesoIndex];
    
    if (!proceso) {
        Swal.fire('Error', 'Proceso no encontrado', 'error');
        return;
    }
    

    
    const tipoProc = proceso.nombre || proceso.nombre_proceso || proceso.descripcion || proceso.tipo_proceso || `Proceso ${procesoIndex + 1}`;
    
    // HTML para imágenes del proceso
    let htmlImagenes = '';
    if (proceso.imagenes && proceso.imagenes.length > 0) {
        htmlImagenes = '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;"><strong style="color: #1f2937; display: block; margin-bottom: 0.75rem;"> Imágenes del Proceso:</strong>';
        htmlImagenes += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">';
        htmlImagenes += proceso.imagenes.map((img, imgIdx) => `
            <div style="position: relative; border: 2px dashed #e5e7eb; border-radius: 6px; overflow: hidden; aspect-ratio: 1; background: #f5f5f5;">
                <img src="${img.url || img.ruta || img.path || ''}" alt="Proceso img ${imgIdx + 1}" style="width: 100%; height: 100%; object-fit: cover;">
                <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; font-size: 0.75rem; cursor: pointer;">
                    <input type="file" class="imagen-proceso-input" data-img-idx="${imgIdx}" style="display: none;" accept="image/*">
                     Cambiar
                </label>
            </div>
        `).join('');
        htmlImagenes += '</div></div>';
    }
    
    // HTML para ubicaciones
    let htmlUbicaciones = '';
    if (proceso.ubicaciones && proceso.ubicaciones.length > 0) {
        htmlUbicaciones = '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;"><strong style="color: #1f2937; display: block; margin-bottom: 0.75rem;"> Ubicaciones:</strong>';
        htmlUbicaciones += '<ul style="margin: 0; padding-left: 1.5rem; color: #374151; font-size: 0.9rem;">';
        htmlUbicaciones += proceso.ubicaciones.map(ub => `<li>${ub.nombre || ub.descripcion || 'Ubicación sin nombre'}</li>`).join('');
        htmlUbicaciones += '</ul></div>';
    }
    
    const html = `
        <div style="text-align: left;">
            <div style="background: #f3e8ff; border-left: 4px solid #7c3aed; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #1f2937; font-size: 1.1rem;"> PROCESO: ${tipoProc.toUpperCase()}</h3>
            </div>
            
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Observaciones del Proceso:</label>
                <textarea id="editObservacionesProceso" style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; font-size: 0.9rem; min-height: 100px;" placeholder="Agrega observaciones del proceso...">${proceso.observaciones || ''}</textarea>
            </div>
            
            ${htmlImagenes}
            ${htmlUbicaciones}
        </div>
    `;
    
    Swal.fire({
        title: ` Editar Proceso - ${tipoProc.toUpperCase()}`,
        html: html,
        width: '600px',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: ' Guardar Cambios',
        confirmButtonColor: '#7c3aed',
        cancelButtonText: 'Volver',
        customClass: {
            container: 'swal-centered-container',
            popup: 'swal-centered-popup'
        },
        didOpen: (modal) => {
            const container = modal.closest('.swal2-container');
            if (container) {
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.height = '100vh';
                container.style.zIndex = '999998';
            }
            modal.style.marginTop = '0';
            modal.style.marginBottom = '0';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Recopilar imágenes modificadas
            const imagenesActualizadas = [];
            document.querySelectorAll('.imagen-proceso-input').forEach(input => {
                const imgIdx = parseInt(input.dataset.imgIdx) || 0;
                if (input.files && input.files[0]) {
                    imagenesActualizadas.push({
                        index: imgIdx,
                        file: input.files[0],
                        nombre: input.files[0].name
                    });
                }
            });
            
            const cambiosProceso = {
                prendasIndex: prendasIndex,
                procesoIndex: procesoIndex,
                observaciones: document.getElementById('editObservacionesProceso').value,
                imagenesActualizadas: imagenesActualizadas
            };
            

            Swal.fire('', ' Proceso actualizado correctamente', 'success');
        }
    });
}

/**
 * Agregar una nueva fila de tela a la tabla
 */
function agregarFilaTela() {
    const filasTelas = document.getElementById('filasTelas');
    const numFila = filasTelas.children.length;
    
    const fila = document.createElement('div');
    fila.style.cssText = 'display: grid; grid-template-columns: 0.5fr 0.5fr 0.5fr 0.3fr; gap: 0.5rem; margin-bottom: 0.5rem;';
    fila.className = 'fila-tela';
    fila.innerHTML = `
        <input type="text" class="tela-name" placeholder="Nombre tela" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <input type="text" class="tela-color" placeholder="Color" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <input type="text" class="tela-ref" placeholder="Referencia" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <button type="button" class="btn-eliminar-fila-tela" onclick="eliminarFilaTela(this)" style="background: #ef4444; color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">✕</button>
    `;
    
    filasTelas.appendChild(fila);
}

/**
 * Eliminar fila de tela
 */
function eliminarFilaTela(btn) {
    btn.closest('.fila-tela').remove();
}

/**
 * Cerrar modal de prendas
 */
function cerrarModalPrendaNueva() {
    // Cerrar el modal directamente
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('active');
    }
    
    // Resetear botón de guardar
    const btnGuardar = document.getElementById('btn-guardar-prenda');
    if (btnGuardar) {
        btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
    }
    
    // Limpiar formulario del modal
    const form = document.getElementById('form-prenda-nueva');
    if (form) {
        form.reset();
    }
}

// Exponer funciones globalmente para onclick
window.abrirEditarPrendas = abrirEditarPrendas;
window.abrirEditarPrendaEspecifica = abrirEditarPrendaEspecifica;
window.abrirEditarProcesoEspecifico = abrirEditarProcesoEspecifico;
window.agregarFilaTela = agregarFilaTela;
window.eliminarFilaTela = eliminarFilaTela;
window.cerrarModalPrendaNueva = cerrarModalPrendaNueva;
window.limpiarFormularioPrendaNueva = limpiarFormularioPrendaNueva;
window.cargarPrendaEnFormularioModal = cargarPrendaEnFormularioModal;

/**
 * Función helper: Obtener GestionItemsUI desde cualquier contexto
 * Busca la instancia en window actual, parent, iframes, etc.
 */
window.obtenerGestionItemsUI = function() {
    // Buscar en window actual
    if (window.gestionItemsUI) return window.gestionItemsUI;
    
    // Buscar en parent window
    if (window.parent && window.parent !== window && window.parent.gestionItemsUI) {
        return window.parent.gestionItemsUI;
    }
    
    // Buscar en iframes
    const iframes = document.querySelectorAll('iframe');
    for (let iframe of iframes) {
        try {
            if (iframe.contentWindow && iframe.contentWindow.gestionItemsUI) {
                return iframe.contentWindow.gestionItemsUI;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    
    return null;
};

/**
 * Función helper: Obtener modal del DOM desde cualquier contexto
 */
window.obtenerModalPrendaNueva = function() {
    // Buscar en document actual
    let modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {

        return modal;
    }
    

    
    // Buscar en iframes
    const iframes = document.querySelectorAll('iframe');

    for (let iframe of iframes) {
        try {
            modal = iframe.contentDocument?.getElementById('modal-agregar-prenda-nueva');
            if (modal) {

                return modal;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    
    // Buscar en parent
    if (window.parent && window.parent !== window) {
        try {
            modal = window.parent.document.getElementById('modal-agregar-prenda-nueva');
            if (modal) {

                return modal;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    



    const modalsEnDOM = Array.from(document.querySelectorAll('[id*="modal"]')).map(el => el.id);

    if (modalsEnDOM.length === 0) {

    }
    
    return null;
};

/**
 * Limpiar formulario del modal de prendas
 */
function limpiarFormularioPrendaNueva() {
    const form = document.getElementById('form-prenda-nueva');
    if (form) {
        form.reset();
    }
    
    // Limpiar previsualizaciones de fotos
    const prevFoto = document.getElementById('nueva-prenda-foto-preview');
    if (prevFoto) {
        prevFoto.style.backgroundImage = 'none';
        prevFoto.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Agregar</div></div>';
    }
    
    // Limpiar tabla de telas
    const tbodyTelas = document.getElementById('tbody-telas');
    if (tbodyTelas) {
        tbodyTelas.innerHTML = `
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem; text-align: center;">
                    <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                    </button>
                </td>
            </tr>
        `;
    }
    
    // Limpiar storage de imágenes
    if (window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage.limpiar();
    }
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
}

/**
 * Cargar datos de prenda en el formulario del modal
 */
function cargarPrendaEnFormularioModal(prendaData) {
    if (!prendaData) return;
    
    // Cargar datos básicos
    const nombreField = document.getElementById('nueva-prenda-nombre');
    const descripcionField = document.getElementById('nueva-prenda-descripcion');
    const origenSelect = document.getElementById('nueva-prenda-origen-select');
    
    if (nombreField) nombreField.value = prendaData.nombre_producto || prendaData.nombre_prenda || '';
    if (descripcionField) descripcionField.value = prendaData.descripcion || '';
    if (origenSelect) origenSelect.value = prendaData.origen || 'bodega';
    

    
    // Cargar imágenes de prenda si existen
    if (prendaData.imagenes && prendaData.imagenes.length > 0) {
        const prevFoto = document.getElementById('nueva-prenda-foto-preview');
        if (prevFoto && prendaData.imagenes[0]) {
            const img = prendaData.imagenes[0];
            const imgUrl = img.url || img.ruta || img.ruta_webp || '';
            if (imgUrl) {
                prevFoto.style.backgroundImage = `url('${imgUrl}')`;
                prevFoto.style.backgroundSize = 'cover';
                prevFoto.style.backgroundPosition = 'center';
            }
        }
    }
    
    // Cargar telas/variantes si existen
    if (prendaData.telasAgregadas && prendaData.telasAgregadas.length > 0) {
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
            
            prendaData.telasAgregadas.forEach((tela, idx) => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #e5e7eb';
                row.innerHTML = `
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.tela || ''}" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.color || ''}" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.referencia || ''}" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem; text-align: center;">
                        <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                        </button>
                    </td>
                </tr>
                `;
                tbodyTelas.appendChild(row);
            });
        }
    }
}


