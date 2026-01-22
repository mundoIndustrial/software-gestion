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
    console.log(' Abriendo lista de prendas:', prendas.length);
    
    // Guardar prendas en variable global para acceso desde onclick
    window.prendasEdicion = {
        pedidoId: datos.id || datos.numero_pedido,
        prendas: prendas
    };
    
    let htmlListaPrendas = '<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">';
    
    if (prendas.length === 0) {
        // Mostrar botón para agregar prenda si la lista está vacía
        console.log(' Sin prendas - mostrando botón para agregar');
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
        console.log(' Con prendas - mostrando lista');
        prendas.forEach((prenda, idx) => {
            const nombrePrenda = prenda.nombre_prenda || prenda.nombre || 'Prenda sin nombre';
            const cantTallas = prenda.tallas ? Object.keys(prenda.tallas).length : 0;
            const cantProcesos = (prenda.procesos || []).length;
            
            htmlListaPrendas += `
                <button onclick="abrirEditarPrendaEspecifica(${idx})" 
                    style="background: white; border: 2px solid #3b82f6; border-radius: 8px; padding: 1rem; text-align: left; cursor: pointer; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f5f3ff'; this.style.borderColor='#7c3aed';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#8b5cf6';">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;">PRENDA ${idx + 1}: ${nombrePrenda.toUpperCase()}</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">${prenda.descripcion || 'Sin descripción'}</p>
                            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af;">
                                📏 Tallas: ${cantTallas} |  Procesos: ${cantProcesos}
                            </div>
                        </div>
                        <span style="background: #8b5cf6; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;"> Editar</span>
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
        cancelButtonText: 'Volver'
    });
}

/**
 * Abrir modal de edición para una prenda específica
 * Usa modal dinámico para evitar conflictos CSS
 */
function abrirEditarPrendaEspecifica(prendasIndex) {
    console.log(' [PRENDA EDITOR] abrirEditarPrendaEspecifica() llamado con índice:', prendasIndex);
    
    if (!window.prendasEdicion) {
        console.error(' window.prendasEdicion no existe');
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
    
    console.log('  Editando prenda:', prenda);
    console.log('  Estructura de prenda - claves disponibles:', Object.keys(prenda));
    console.log('  prenda.telasAgregadas:', prenda.telasAgregadas);
    console.log('  prenda.variantes:', prenda.variantes);
    
    //  USAR MODAL DINÁMICO (sin conflictos CSS)
    if (window.modalPrendaDinamico) {
        console.log(' Abriendo modal dinámico...');
        window.modalPrendaDinamico.abrir();
    } else {
        console.error(' Modal dinámico no disponible');
        Swal.fire('Error', 'Modal no disponible', 'error');
        return;
    }
    
    // Preparar datos en formato compatible con el modal
    // IMPORTANTE: Usar telasAgregadas que viene del backend con estructura correcta
    const telasAgregadas = prenda.telasAgregadas || [];
    console.log('  Telas agregadas finales:', telasAgregadas);
    
    // Mapear variantes del nuevo endpoint a formato esperado por el frontend
    // Manejar tanto estructuras de array como de objeto
    let variantesFormateadas = [];
    
    if (Array.isArray(prenda.variantes)) {
        // Si es array (del backend de pedidos)
        variantesFormateadas = prenda.variantes.map(v => ({
            id: v.id,
            talla: v.talla || '',
            cantidad: v.cantidad || 0,
            genero: v.genero || '',
            color_id: v.color_id,
            color_nombre: v.color_nombre,
            tela_id: v.tela_id,
            tela_nombre: v.tela_nombre,
            tipo_manga_id: v.tipo_manga_id,
            tipo_manga_nombre: v.tipo_manga_nombre,
            tipo_broche_id: v.tipo_broche_id,
            tipo_broche_nombre: v.tipo_broche_nombre,
            manga_obs: v.manga_obs || '',
            broche_boton_obs: v.broche_boton_obs || '',
            bolsillos_obs: v.bolsillos_obs || '',
            tiene_bolsillos: v.tiene_bolsillos || false
        }));
    } else if (prenda.variantes && typeof prenda.variantes === 'object') {
        // Si es objeto (de prenda nueva creada en memoria)
        variantesFormateadas = [{
            manga: prenda.variantes.manga || '',
            obs_manga: prenda.variantes.obs_manga || '',
            tiene_bolsillos: prenda.variantes.tiene_bolsillos || false,
            obs_bolsillos: prenda.variantes.obs_bolsillos || '',
            broche: prenda.variantes.broche || '',
            obs_broche: prenda.variantes.obs_broche || ''
        }];
    }
    
    const prendaParaEditar = {
        nombre_prenda: prenda.nombre_prenda || prenda.nombre_producto || prenda.nombre || '',
        nombre_producto: prenda.nombre_prenda || prenda.nombre_producto || prenda.nombre || '',
        descripcion: prenda.descripcion || '',
        origen: prenda.origen || 'bodega',
        imagenes: prenda.imagenes || [],
        telasAgregadas: telasAgregadas,
        tallas: prenda.generosConTallas || prenda.tallas || prenda.tallas_estructura || {},
        procesos: prenda.procesos || [],
        //  Datos de tela
        tela: prenda.tela || '',
        color: prenda.color || '',
        ref: prenda.ref || '',
        referencia: prenda.referencia || '',
        imagen_tela: prenda.imagen_tela || null,
        imagenes_tela: prenda.imagenes_tela || [],
        //  Variantes formateadas
        variantes: variantesFormateadas,
        tallas_estructura: prenda.tallas || prenda.tallas_estructura || null,
        //  Datos de variaciones
        obs_manga: prenda.obs_manga || '',
        obs_bolsillos: prenda.obs_bolsillos || '',
        obs_broche: prenda.obs_broche || '',
        obs_reflectivo: prenda.obs_reflectivo || '',
        tiene_reflectivo: prenda.tiene_reflectivo || false
    };
    
    console.log('  prendaParaEditar preparada:', prendaParaEditar);
    console.log('  prendaParaEditar.nombre_prenda:', prendaParaEditar.nombre_prenda);
    console.log('  prendaParaEditar.imagenes:', prendaParaEditar.imagenes);
    console.log('  prendaParaEditar.telasAgregadas:', prendaParaEditar.telasAgregadas);
    
    // Guardar datos de edición en global
    window.prendaEnEdicion = {
        pedidoId: pedidoId,
        prendasIndex: prendasIndex,
        prendaOriginal: JSON.parse(JSON.stringify(prenda))
    };
    
    // ESTRATEGIA 1: Usar GestionItemsUI si existe (mejor opción - misma apariencia que crear-nuevo)
    console.log(' Buscando GestionItemsUI para usar igual modal que en crear-nuevo...');
    if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
        console.log(' GestionItemsUI ENCONTRADO - Usando para abrir modal igual a crear-nuevo');
        
        // Mover el modal al body para evitar que se vea clipeado
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal && modal.parentElement !== document.body) {
            console.log('🔄 Moviendo modal al body para mejor visualización...');
            document.body.appendChild(modal);
        }
        
        // Indicar que estamos editando
        window.gestionItemsUI.prendaEditIndex = prendasIndex;
        
        // Abrir el modal
        window.gestionItemsUI.abrirModalAgregarPrendaNueva();
        
        // Cargar datos en el modal
        if (typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
            console.log(' Cargando datos en modal con GestionItemsUI');
            window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
        }
        
        console.log(' Modal abierto usando GestionItemsUI - Se ve igual a crear-nuevo');
        return;
    }
    
    console.warn(' GestionItemsUI no disponible, intentando fallback manual');
    console.log('🔄 Abriendo modal con método manual...');
    
    // ESTRATEGIA 2: Fallback manual si GestionItemsUI no está disponible
    const modal = window.obtenerModalPrendaNueva();
    if (!modal) {
        console.error(' No se pudo encontrar el modal de crear prendas');
        Swal.fire('Error', 'No se pudo abrir el modal de edición. Por favor, intenta nuevamente.', 'error');
        return;
    }
    
    console.log(' Modal encontrado, abriendo...');
    
    // PASO 1: Limpiar formulario
    limpiarFormularioPrendaNueva();
    
    // PASO 2: Cargar datos en el formulario
    cargarPrendaEnFormularioModal(prendaParaEditar);
    
    // PASO 3: Mostrar modal
    modal.style.display = 'flex';
    modal.style.position = 'fixed';
    modal.style.zIndex = '999999';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.right = '0';
    modal.style.bottom = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
    
    console.log(' Modal abierto manualmente');
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
    
    console.log(' Editando proceso:', proceso);
    
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
        cancelButtonText: 'Volver'
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
            
            console.log(' Guardando cambios de proceso:', cambiosProceso);
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
    //  Usar modal dinámico
    if (window.modalPrendaDinamico) {
        window.modalPrendaDinamico.cerrar();
    } else {
        console.error(' Modal dinámico no disponible');
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
        console.log(' Modal encontrado en document.getElementById()');
        return modal;
    }
    
    console.log(' Modal no encontrado en document actual, buscando en otros contextos...');
    
    // Buscar en iframes
    const iframes = document.querySelectorAll('iframe');
    console.log(` Buscando en ${iframes.length} iframes...`);
    for (let iframe of iframes) {
        try {
            modal = iframe.contentDocument?.getElementById('modal-agregar-prenda-nueva');
            if (modal) {
                console.log(' Modal encontrado en iframe');
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
                console.log(' Modal encontrado en parent window');
                return modal;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    
    console.log(' Modal NO encontrado en ningún contexto');
    console.log(' Elementos disponibles en el documento:');
    console.log('   - Total de divs:', document.querySelectorAll('div').length);
    const modalsEnDOM = Array.from(document.querySelectorAll('[id*="modal"]')).map(el => el.id);
    console.log('   - IDs que contienen "modal":', modalsEnDOM);
    if (modalsEnDOM.length === 0) {
        console.warn(' NO HAY NINGUN MODAL EN EL DOM - El archivo blade probablemente no fue incluido');
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
    
    console.log(' Datos básicos de prenda cargados en formulario');
    
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

console.log(' [PRENDA EDITOR] prenda-editor-modal.js cargado correctamente');
