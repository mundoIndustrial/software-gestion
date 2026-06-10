/**
 * EJEMPLO DE INTEGRACIÓN JAVASCRIPT
 * Para: resources/views/cotizaciones/bordado/create.blade.php
 * 
 * Este archivo muestra cómo integrar los endpoints API en la interfaz
 */

// =========================================================
// 1. VARIABLES GLOBALES
// =========================================================

let tecnicasAgregadas = [];
let tiposDisponibles = [];
let logoCotizacionId = null;

const DROPZONE_PASTE_HINT_TEXT_LOGO = 'Pega la imagen con Ctrl+V';
let dropzoneDestinoPegadoLogo = null;
let pasteListenerRegistradoLogo = false;
let __logoUltimoPasteTs = 0;
let __logoUltimoPasteSig = '';

function clipboardTieneImagenLogo() {
    try {
        if (!navigator.clipboard || typeof navigator.clipboard.read !== 'function') {
            return Promise.resolve(false);
        }
        return navigator.clipboard.read().then((items) => {
            for (const item of items) {
                if (!item || !item.types) continue;
                if (item.types.some(t => typeof t === 'string' && t.startsWith('image/'))) {
                    return true;
                }
            }
            return false;
        }).catch(() => false);
    } catch (_) {
        return Promise.resolve(false);
    }
}

function aplicarPasteHintVisualLogo(dropzone) {
    if (!dropzone) return;
    if (!dropzone.style.position || dropzone.style.position === 'static') {
        dropzone.style.position = 'relative';
    }
    let overlay = dropzone.querySelector('[data-dropzone-paste-overlay="1"]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.setAttribute('data-dropzone-paste-overlay', '1');
        overlay.style.cssText = [
            'position:absolute',
            'inset:0',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'background:rgba(16,185,129,0.10)',
            'border-radius:6px',
            'font-weight:800',
            'color:#047857',
            'letter-spacing:0.3px',
            'pointer-events:none',
            'text-transform:uppercase',
            'font-size:0.78rem'
        ].join(';');
        overlay.textContent = DROPZONE_PASTE_HINT_TEXT_LOGO;
        dropzone.appendChild(overlay);
    }
}

function limpiarPasteHintVisualLogo(dropzone) {
    if (!dropzone) return;
    const overlay = dropzone.querySelector('[data-dropzone-paste-overlay="1"]');
    if (overlay) overlay.remove();
}

function adjuntarPasteHintEnDropzoneLogo(dropzone) {
    if (!dropzone) return;
    if (dropzone.getAttribute('data-paste-hint-bound') === '1') return;
    dropzone.setAttribute('data-paste-hint-bound', '1');

    dropzone.addEventListener('mouseenter', () => {
        dropzoneDestinoPegadoLogo = dropzone;
        clipboardTieneImagenLogo().then((tiene) => {
            if (!dropzone.matches(':hover')) return;
            const tieneDragOverlay = !!dropzone.querySelector('[data-dropzone-overlay="1"]');
            if (tieneDragOverlay) return;
            if (tiene) {
                aplicarPasteHintVisualLogo(dropzone);
            }
        });
    });

    dropzone.addEventListener('mouseleave', () => {
        if (dropzoneDestinoPegadoLogo === dropzone) {
            dropzoneDestinoPegadoLogo = null;
        }
        limpiarPasteHintVisualLogo(dropzone);
    });

    dropzone.addEventListener('dragover', () => {
        limpiarPasteHintVisualLogo(dropzone);
    });

    if (!pasteListenerRegistradoLogo) {
        pasteListenerRegistradoLogo = true;
        document.addEventListener('paste', (e) => {
            try {
                if (e && e.__logoPasteHandled) return;
                if (e) e.__logoPasteHandled = true;

                if (!dropzoneDestinoPegadoLogo) return;

                const dtItems = e.clipboardData && e.clipboardData.items ? Array.from(e.clipboardData.items) : [];
                let archivos = dtItems
                    .filter(i => i && i.kind === 'file')
                    .map(i => i.getAsFile && i.getAsFile())
                    .filter(f => f && f.type && f.type.startsWith('image/'));

                if (archivos.length === 0) return;

                const vistos = new Set();
                archivos = archivos.filter((f) => {
                    const sig = `${f.type}|${f.size}|${f.lastModified || 0}`;
                    if (vistos.has(sig)) return false;
                    vistos.add(sig);
                    return true;
                });

                const sigEvento = archivos.map(f => `${f.type}|${f.size}|${f.lastModified || 0}`).join(';;');
                const ahora = Date.now();
                if (__logoUltimoPasteSig === sigEvento && (ahora - __logoUltimoPasteTs) < 500) {
                    return;
                }
                __logoUltimoPasteSig = sigEvento;
                __logoUltimoPasteTs = ahora;

                const input = dropzoneDestinoPegadoLogo.querySelector('input[type="file"]')
                    || (dropzoneDestinoPegadoLogo.closest('label') ? dropzoneDestinoPegadoLogo.closest('label').querySelector('input[type="file"]') : null);
                if (!input) return;

                try {
                    if (typeof e.preventDefault === 'function') e.preventDefault();
                    if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                    if (typeof e.stopPropagation === 'function') e.stopPropagation();
                } catch (_) {}

                try { input.value = ''; } catch (_) {}

                const dataTransfer = new DataTransfer();
                archivos.forEach((f) => dataTransfer.items.add(f));
                input.files = dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));

                try {
                    limpiarPasteHintVisualLogo(dropzoneDestinoPegadoLogo);
                } catch (_) {}
            } catch (_) {}
        });
    }
}

// IDs de fotos (tabla logo_cotizacion_tecnica_prendas_fotos) a eliminar al guardar/enviar
window.tecnicasFotosAEliminar = window.tecnicasFotosAEliminar || [];

/**
 * Fase 2: URL para previews sin data URL (preview_url nuevo, data_url legacy o string).
 */
function cotizacionLogoImgSrc(img) {
    if (!img) return '';
    if (typeof img === 'string') return img;
    return img.preview_url || img.data_url || '';
}

// =========================================================
// 2. CARGAR TIPOS DE TÉCNICAS AL INICIALIZAR
// =========================================================

async function cargarTiposDisponibles() {
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/tipos-disponibles');
        const data = await response.json();
        
        if (data.success) {
            tiposDisponibles = data.data;
            renderizarSelectTecnicas();
        }
    } catch (error) {

    }
}

function configurarDropzoneImagenes({ dropzone, input, previewContainer, maximo = null }) {
    const imagenesAgregadas = [];

    const actualizarPrevisualizaciones = () => {
        if (!previewContainer) return;

        imagenesAgregadas.forEach((f) => {
            if (f && f.__cotPreviewBlobUrl) {
                try {
                    URL.revokeObjectURL(f.__cotPreviewBlobUrl);
                } catch (_) {}
                delete f.__cotPreviewBlobUrl;
            }
        });

        previewContainer.innerHTML = '';

        imagenesAgregadas.forEach((archivo, idx) => {
            const objectUrl = URL.createObjectURL(archivo);
            archivo.__cotPreviewBlobUrl = objectUrl;

            const preview = document.createElement('div');
            preview.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';

            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
            btnEliminar.textContent = '×';
            btnEliminar.addEventListener('click', (ev) => {
                ev.preventDefault();
                if (archivo.__cotPreviewBlobUrl) {
                    try {
                        URL.revokeObjectURL(archivo.__cotPreviewBlobUrl);
                    } catch (_) {}
                    delete archivo.__cotPreviewBlobUrl;
                }
                imagenesAgregadas.splice(idx, 1);
                actualizarPrevisualizaciones();
            });

            const img = document.createElement('img');
            img.src = objectUrl;
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';

            preview.appendChild(img);
            preview.appendChild(btnEliminar);
            previewContainer.appendChild(preview);
        });
    };

    const agregarImagenes = (archivos) => {
        const imagenes = (archivos || []).filter(f => f && f.type && f.type.startsWith('image/'));
        if (imagenes.length === 0) {
            return;
        }

        for (const archivo of imagenes) {
            imagenesAgregadas.push(archivo);
        }

        actualizarPrevisualizaciones();
    };

    if (dropzone && input) {
        dropzone.addEventListener('click', () => input.click());

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.style.background = '#e8f1ff';
            dropzone.style.borderColor = '#1e40af';
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.style.background = '#fafafa';
            dropzone.style.borderColor = '#ddd';
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.style.background = '#fafafa';
            dropzone.style.borderColor = '#ddd';
            agregarImagenes(Array.from(e.dataTransfer.files || []));
        });

        input.addEventListener('change', (e) => {
            agregarImagenes(Array.from(e.target.files || []));
            e.target.value = '';
        });
    }

    return imagenesAgregadas;
}

function guardarEdicionPrenda() {
    const listaPrendas = document.getElementById('listaPrendas');
    const prendaDiv = listaPrendas ? listaPrendas.querySelector('.prenda-item') : null;

    if (!prendaDiv) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el formulario de edición'
        });
        return;
    }

    const nombreInput = prendaDiv.querySelector('.nombre_prenda');
    const observacionesInput = prendaDiv.querySelector('.observaciones');
    const nuevoNombre = nombreInput ? nombreInput.value.trim().toUpperCase() : '';
    const nuevasObs = observacionesInput ? observacionesInput.value.trim() : '';

    if (!nuevoNombre) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo vacío',
            text: 'Completa el nombre de la prenda'
        });
        return;
    }

    const originalNombre = window.prendaNombreOriginalEdicion || (window.datosPrendaEdicion ? window.datosPrendaEdicion.nombre_prenda : null);
    const indicesPrevios = Array.isArray(window.tecnicaIndicesEdicion) ? window.tecnicaIndicesEdicion : [];
    const tecnicasEditData = (window.datosPrendaEdicion && window.datosPrendaEdicion.tecnicas_imagenes) ? window.datosPrendaEdicion.tecnicas_imagenes : {};
    const logoCompartidoEnabled = !!(window.datosPrendaEdicion && window.datosPrendaEdicion.logoCompartido && window.datosPrendaEdicion.logoCompartido.enabled);
    const logoCompartidoData = (window.datosPrendaEdicion && window.datosPrendaEdicion.logoCompartido) ? window.datosPrendaEdicion.logoCompartido : { enabled: false, items: {}, files: {} };

    // Técnicas seleccionadas actualmente en el modal (por tipo_logo_id)
    const selectedIds = Array.isArray(window.tecnicasSeleccionadasEdicionIds)
        ? window.tecnicasSeleccionadasEdicionIds
        : [];
    const selectedSet = new Set(selectedIds.map(n => Number(n)).filter(n => !Number.isNaN(n)));

    if (selectedSet.size === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Técnica requerida',
            text: 'Debes seleccionar al menos una técnica para guardar los cambios.'
        });
        return;
    }

    // 1) Remover la prenda de técnicas deseleccionadas
    tecnicasAgregadas.forEach((tecnicaData) => {
        const tipoId = tecnicaData && tecnicaData.tipo_logo ? Number(tecnicaData.tipo_logo.id) : NaN;
        if (Number.isNaN(tipoId)) return;
        if (selectedSet.has(tipoId)) return;
        if (!Array.isArray(tecnicaData.prendas)) return;
        tecnicaData.prendas = tecnicaData.prendas.filter((p) => {
            if (!p) return false;
            if (originalNombre && p.nombre_prenda === originalNombre) return false;
            if (!originalNombre && p.nombre_prenda === nuevoNombre) return false;
            return true;
        });
    });

    // Limpiar técnicas vacías
    for (let i = tecnicasAgregadas.length - 1; i >= 0; i--) {
        const t = tecnicasAgregadas[i];
        if (t && Array.isArray(t.prendas) && t.prendas.length === 0) {
            tecnicasAgregadas.splice(i, 1);
        }
    }

    // 2) Upsert de la prenda en cada técnica seleccionada
    selectedSet.forEach((tipoId) => {
        const tipoFromCatalogo = (Array.isArray(tiposDisponibles) ? tiposDisponibles : []).find(t => Number(t.id) === Number(tipoId));
        if (!tipoFromCatalogo) return;

        // Crear técnica si no existe
        let tecnicaData = (Array.isArray(tecnicasAgregadas) ? tecnicasAgregadas : [])
            .find(t => t && t.tipo_logo && Number(t.tipo_logo.id) === Number(tipoId));
        if (!tecnicaData) {
            tecnicaData = {
                tipo_logo: { id: tipoFromCatalogo.id, nombre: tipoFromCatalogo.nombre, color: tipoFromCatalogo.color },
                prendas: [],
                observacionesGenerales: ''
            };
            tecnicasAgregadas.push(tecnicaData);
        }
        if (!Array.isArray(tecnicaData.prendas)) tecnicaData.prendas = [];

        const nombreTecnica = tecnicaData.tipo_logo ? tecnicaData.tipo_logo.nombre : null;
        const dataTecnica = nombreTecnica ? tecnicasEditData[nombreTecnica] : null;

        // Capturar estado previo de esta prenda en esta técnica (para conservar imágenes si no se tocaron)
        const prendaPrev = tecnicaData.prendas.find((p) => {
            if (!p) return false;
            if (originalNombre) return p.nombre_prenda === originalNombre;
            return p.nombre_prenda === nuevoNombre;
        }) || null;

        // Actualizar logos compartidos a nivel de técnica
        if (logoCompartidoEnabled) {
            tecnicaData.logosCompartidos = tecnicaData.logosCompartidos || {};
            Object.keys(logoCompartidoData.items || {}).forEach((clave) => {
                const file = (logoCompartidoData.files && logoCompartidoData.files[clave]) ? logoCompartidoData.files[clave] : null;
                if (file instanceof File) {
                    tecnicaData.logosCompartidos[clave] = file;
                }
            });
        } else {
            tecnicaData.logosCompartidos = {};
        }

        // Data base para guardar
        const ubicaciones = dataTecnica && Array.isArray(dataTecnica.ubicaciones)
            ? dataTecnica.ubicaciones
            : (prendaPrev && Array.isArray(prendaPrev.ubicaciones) ? prendaPrev.ubicaciones : []);

        const touchedImagenes = !!(dataTecnica && dataTecnica.__touchedImagenes);
        const imgsFromModal = dataTecnica && Array.isArray(dataTecnica.imagenes_data_urls) ? dataTecnica.imagenes_data_urls : [];
        const imgsPrev = prendaPrev && Array.isArray(prendaPrev.imagenes_data_urls) ? prendaPrev.imagenes_data_urls : [];
        const imgs = (touchedImagenes || imgsFromModal.length > 0)
            ? imgsFromModal
            : imgsPrev;

        // Mantener objetos si vienen con metadata (id), o convertir strings a {data_url}
        const imagenesPropias = imgs.map((u) => (u && typeof u === 'object') ? u : ({ data_url: u }));

        // Mantener Files reales (solo imágenes NUEVAS agregadas en el modal)
        // Usar imagenes_files_meta porque es la fuente de verdad y se actualiza al eliminar.
        const filesMeta = dataTecnica && Array.isArray(dataTecnica.imagenes_files_meta) ? dataTecnica.imagenes_files_meta : [];
        const imagenesFiles = filesMeta
            .map(m => (m && m.file) ? m.file : null)
            .filter(f => f instanceof File);

        // Si hay logo compartido activo, agregar imágenes compartidas que correspondan a esta técnica
        let imagenesCompartidas = [];
        if (logoCompartidoEnabled && nombreTecnica) {
            Object.entries(logoCompartidoData.items || {}).forEach(([clave, item]) => {
                const tecnicasCompartidas = Array.isArray(item.tecnicasCompartidas) ? item.tecnicasCompartidas : [];
                if (!tecnicasCompartidas.includes(nombreTecnica)) return;
                const dataUrl = item.previewUrl || null;
                if (!dataUrl) return;
                imagenesCompartidas.push({
                    data_url: dataUrl,
                    esCompartida: true,
                    nombreCompartido: clave,
                    tecnicasCompartidas
                });
            });
        }

        const prendaNueva = {
            // Preservar ID de la prenda técnica para que backend haga UPDATE y no CREATE
            // (si se crea una nueva, las fotos existentes quedan vinculadas al ID anterior y "desaparecen").
            id: (dataTecnica && dataTecnica.id_prenda_tecnica) ? dataTecnica.id_prenda_tecnica : (prendaPrev ? (prendaPrev.id || null) : null),
            nombre_prenda: nuevoNombre,
            observaciones: nuevasObs,
            talla_cantidad: [],
            ubicaciones: ubicaciones,
            imagenes_files: imagenesFiles,
            imagenes_data_urls: [...imagenesPropias, ...imagenesCompartidas]
        };

        // Si se renombra, evitar que queden prendas duplicadas con el nombre anterior
        // dentro de técnicas seleccionadas. El render agrupa por nombre.
        tecnicaData.prendas = tecnicaData.prendas.filter((p) => {
            if (!p) return false;
            if (originalNombre && p.nombre_prenda === originalNombre) return false;
            if (p.nombre_prenda === nuevoNombre) return false;
            return true;
        });

        tecnicaData.prendas.push(prendaNueva);
    });

    // Reset flags de edición
    window.modoEdicionPrenda = false;
    window.datosPrendaEdicion = null;
    window.tecnicasInfoEdicion = null;
    window.tecnicaIndicesEdicion = null;
    window.prendaNombreOriginalEdicion = null;
    window.tecnicasSeleccionadasEdicionIds = null;

    cerrarModalAgregarTecnica();
    try {
        window.tecnicasAgregadas = tecnicasAgregadas;
    } catch (_) {}
    renderizarTecnicasAgregadas();
}

// =========================================================
// 3. RENDERIZAR CHECKBOXES DE TÉCNICAS
// =========================================================

function renderizarSelectTecnicas() {
    const container = document.getElementById('tecnicas-checkboxes');
    
    if (!container) {

        return;
    }
    
    container.innerHTML = '';
    
    tiposDisponibles.forEach(tipo => {
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: 4px; border: 1px solid #e5e7eb; transition: all 0.2s;';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = tipo.id;
        checkbox.className = 'tecnica-checkbox';
        checkbox.style.cssText = 'cursor: pointer; width: 18px; height: 18px;';
        
        const span = document.createElement('span');
        span.textContent = tipo.nombre;
        span.style.cssText = 'font-weight: 500; color: #333;';
        
        label.appendChild(checkbox);
        label.appendChild(span);
        
        // Hover effect
        label.addEventListener('mouseover', () => {
            label.style.backgroundColor = '#f3f4f6';
            if (tipo.color) {
                label.style.borderColor = tipo.color;
            }
        });
        label.addEventListener('mouseout', () => {
            label.style.backgroundColor = 'transparent';
            label.style.borderColor = '#e5e7eb';
        });
        
        container.appendChild(label);
    });
}

// =========================================================
// 4. ABRIR MODAL PARA AGREGAR TÉCNICA
// =========================================================

function abrirModalAgregarTecnica() {
    // Resetear modo edición para asegurar que no se arrastre estado previo
    window.modoEdicionPrenda = false;
    window.datosPrendaEdicion = null;
    window.tecnicasInfoEdicion = null;
    window.tecnicaIndicesEdicion = null;
    window.prendaNombreOriginalEdicion = null;
    window.tecnicasSeleccionadasEdicionIds = null;

    // Obtener técnicas seleccionadas
    const checkboxes = document.querySelectorAll('.tecnica-checkbox:checked');
    const tecnicasSeleccionadas = Array.from(checkboxes).map(cb => {
        const id = parseInt(cb.value);
        return tiposDisponibles.find(t => t.id === id);
    });
    
    if (tecnicasSeleccionadas.length === 0) {
        abrirModalValidacionTecnica();
        return;
    }
    
    // Si solo seleccionó 1 técnica, flujo simple
    if (tecnicasSeleccionadas.length === 1) {
        abrirModalSimpleTecnica(tecnicasSeleccionadas[0]);
    } else {
        // Si seleccionó múltiples, ir DIRECTO a datos iguales (sin pregunta)
        abrirModalDatosIguales(tecnicasSeleccionadas);
    }
}

function abrirModalSimpleTecnica(tipo) {
    // Resetear cualquier estado previo de técnicas combinadas
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;

    // Obtener el nombre de la técnica
    const nombreElement = document.getElementById('tecnicaSeleccionadaNombre');
    if (nombreElement) {
        nombreElement.textContent = tipo.nombre;
    }
    
    // Limpiar prendas del modal
    const listaPrendas = document.getElementById('listaPrendas');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
    }
    
    // Agregar una prenda por defecto
    agregarFilaPrenda();
    
    // Mostrar/ocultar mensaje de sin prendas
    const noPrendasMsg = document.getElementById('noPrendasMsg');
    if (noPrendasMsg) {
        noPrendasMsg.style.display = 'none';
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function abrirModalDatosIguales(tecnicas) {
    // Guardar técnicas combinadas en contexto global temporal
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'iguales';
    
    // Modal MINIMALISTA tipo TNS
    Swal.fire({
        title: 'Técnicas Combinadas',
        width: '1000px',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 70vh; overflow-y: auto;">
                
                <!-- PRENDA ÚNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Prenda</h3>
                    <div style="position: relative;">
                        <input type="text" id="dNombrePrenda" placeholder="POLO, CAMISA, PANTALÓN..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;" autocomplete="off">
                        <div id="dListaSugerencias" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; max-height: 150px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                    </div>
                </div>
                
                <!-- UBICACIONES POR TÉCNICA + BOTONES TALLAS -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Ubicaciones</h3>
                    <div id="dUbicacionesPorTecnica" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- OBSERVACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones</h3>
                    <textarea id="dObservaciones" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                
                <!-- LOGO COMPARTIDO (Nuevo) -->
                <div style="margin-bottom: 25px; padding: 12px; background: #f0f7ff; border: 2px solid #dbeafe; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                        <input type="checkbox" id="dUsarLogoCompartido" style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 600; color: #0369a1;">Usar el mismo logo en múltiples técnicas</span>
                    </label>
                    <div id="dSeccionLogoCompartido" style="display: none;">
                        <button type="button" id="dBtnAgregarLogoCompartido" style="
                            background: #0284c7;
                            color: white;
                            border: none;
                            padding: 10px 16px;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.9rem;
                            width: 100%;
                            margin-bottom: 12px;
                        ">+ Agregar Logo Compartido</button>
                        <div id="dLogosCompartidosContenedor" style="display: grid; gap: 10px;">
                            <!-- Se agregan logos compartidos aquí -->
                        </div>
                    </div>
                </div>
                
                <!-- IMÁGENES POR TÉCNICA -->
                <div style="margin-top: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Imágenes por Técnica</h3>
                    <div id="dImagenesPorTecnica" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: (modal) => {
            const inputPrenda = document.getElementById('dNombrePrenda');
            const listaSugerencias = document.getElementById('dListaSugerencias');
            const ubicacionesPorTecnicaDiv = document.getElementById('dUbicacionesPorTecnica');
            
            let prendasDisponibles = [];
            
            // Cargar prendas para autocomplete
            fetch('/api/logo-cotizacion-tecnicas/prendas')
                .then(r => r.json())
                .then(data => {
                    prendasDisponibles = data.data || [];

                })
                .catch(e => console.warn('No se pudo cargar prendas:', e));
            
            // Autocomplete
            inputPrenda.addEventListener('input', (e) => {
                const valor = e.target.value.trim().toUpperCase();
                
                if (valor.length === 0) {
                    listaSugerencias.style.display = 'none';
                    return;
                }
                
                const sugerencias = prendasDisponibles.filter(p => p.includes(valor));
                
                if (sugerencias.length === 0) {
                    listaSugerencias.style.display = 'none';
                    return;
                }
                
                listaSugerencias.innerHTML = sugerencias.map(p => 
                    `<div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.1s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'" onclick="document.getElementById('dNombrePrenda').value='${p}'; document.getElementById('dListaSugerencias').style.display='none';">${p}</div>`
                ).join('');
                listaSugerencias.style.display = 'block';
            });
            
            // Cerrar sugerencias al hacer click afuera
            document.addEventListener('click', (e) => {
                if (e.target !== inputPrenda && e.target !== listaSugerencias) {
                    listaSugerencias.style.display = 'none';
                }
            });
            
            // Crear inputs de ubicación por técnica
            tecnicas.forEach((tecnica, idx) => {
                // Ubicación con múltiples valores
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;';
                labelUbicacion.textContent = tecnica.nombre + ' - Ubicaciones';
                
                const inputDiv = document.createElement('div');
                inputDiv.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacionInput-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                const btnAgregar = document.createElement('button');
                btnAgregar.type = 'button';
                btnAgregar.textContent = '+ Agregar';
                btnAgregar.style.cssText = 'background: #0066cc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap;';
                btnAgregar.className = 'dBtnAgregarUbicacion-' + idx;
                
                inputDiv.appendChild(inputUbicacion);
                inputDiv.appendChild(btnAgregar);
                
                const ubicacionesContainer = document.createElement('div');
                ubicacionesContainer.className = 'dUbicacionesList-' + idx;
                ubicacionesContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputDiv);
                divUbicacion.appendChild(ubicacionesContainer);
                
                ubicacionesPorTecnicaDiv.appendChild(divUbicacion);
                
                // Manejar agregar ubicaciones
                let ubicacionesTecnica = [];
                btnAgregar.addEventListener('click', (e) => {
                    e.preventDefault();
                    const ubicacion = inputUbicacion.value.trim().toUpperCase();
                    
                    if (!ubicacion) {
                        Swal.showValidationMessage('Escribe una ubicación primero');
                        return;
                    }
                    
                    if (ubicacionesTecnica.includes(ubicacion)) {
                        Swal.showValidationMessage('Esta ubicación ya fue agregada');
                        return;
                    }
                    
                    ubicacionesTecnica.push(ubicacion);
                    inputUbicacion.value = '';
                    
                    // Actualizar vista
                    actualizarUbicacionesList();
                    inputUbicacion.focus();
                });
                
                inputUbicacion.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnAgregar.click();
                    }
                });
                
                function actualizarUbicacionesList() {
                    ubicacionesContainer.innerHTML = ubicacionesTecnica.map((ub, i) => `
                        <span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                            ${ub}
                            <button type="button" data-ubicacion-idx="${i}" style="background: none; border: none; color: #0369a1; cursor: pointer; font-weight: 700; padding: 0; margin-left: 0.25rem;">✕</button>
                        </span>
                    `).join('');
                    
                    // Agregar event listeners a los botones de eliminar
                    ubicacionesContainer.querySelectorAll('button[data-ubicacion-idx]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const idxAEliminar = parseInt(btn.getAttribute('data-ubicacion-idx'));
                            ubicacionesTecnica.splice(idxAEliminar, 1);
                            actualizarUbicacionesList();
                        });
                    });
                }
                
                // Guardar referencia global para acceso en preConfirm
                window['dUbicacionesTecnica' + idx] = ubicacionesTecnica;
                
                // Guardar referencias de los inputs y lista para el preConfirm
                inputUbicacion.setAttribute('data-tecnica-idx', idx);
                inputUbicacion.setAttribute('data-ubicaciones-list', 'dUbicacionesList-' + idx);
            });
            
            // Crear inputs de imagen por técnica con Drag and Drop
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                tecnicas.forEach((tecnica, idx) => {
                    // Array para almacenar imágenes de esta técnica
                    const imagenesAgregadas = [];
                    
                    const divImagen = document.createElement('div');
                    divImagen.setAttribute('data-tecnica-idx', idx);
                    divImagen.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                    divImagen.innerHTML = `
                        <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                            Imágenes - ${tecnica.nombre}
                        </label>
                        <div class="dImagenesDropzone-${idx}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                            <div style="margin-bottom: 6px; font-size: 1.3rem;"></div>
                            <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                            <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar</p>
                            <input type="file" class="dImagenTecnicaInput-${idx}" accept="image/*" multiple style="display: none;" />
                        </div>
                        <div class="dImagenesPreview-${idx}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                            <!-- Previsualizaciones aquí -->
                        </div>
                    `;
                    
                    imagesPorTecnicaDiv.appendChild(divImagen);
                    
                    // Setup Drag and Drop
                    const dropzone = divImagen.querySelector(`.dImagenesDropzone-${idx}`);
                    const input = divImagen.querySelector(`.dImagenTecnicaInput-${idx}`);
                    const previewContainer = divImagen.querySelector(`.dImagenesPreview-${idx}`);
                    
                    // Click en dropzone
                    dropzone.addEventListener('click', () => input.click());
                    
                    // Drag over
                    dropzone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropzone.style.background = '#e8f1ff';
                        dropzone.style.borderColor = '#1e40af';
                    });
                    
                    dropzone.addEventListener('dragleave', () => {
                        dropzone.style.background = '#fafafa';
                        dropzone.style.borderColor = '#ddd';
                    });
                    
                    // Drop
                    dropzone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropzone.style.background = '#fafafa';
                        dropzone.style.borderColor = '#ddd';
                        agregarImagenesDelDropzone(Array.from(e.dataTransfer.files));
                    });
                    
                    // Cambio en input file
                    input.addEventListener('change', (e) => {
                        agregarImagenesDelDropzone(Array.from(e.target.files));
                    });
                    
                    function agregarImagenesDelDropzone(archivos) {
                        // Filtrar solo imágenes
                        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
                        
                        if (imagenes.length === 0) {
                            return;
                        }
                        
                        // Agregar todas las imágenes recibidas
                        for (const archivo of imagenes) {
                            imagenesAgregadas.push(archivo);
                        }
                        
                        // Actualizar previsualizaciones
                        actualizarPrevisualizaciones();
                    }
                    
                    function actualizarPrevisualizaciones() {
                        imagenesAgregadas.forEach((f) => {
                            if (f && f.__cotPreviewBlobUrl) {
                                try {
                                    URL.revokeObjectURL(f.__cotPreviewBlobUrl);
                                } catch (_) {}
                                delete f.__cotPreviewBlobUrl;
                            }
                        });

                        previewContainer.innerHTML = '';

                        imagenesAgregadas.forEach((archivo, imgIdx) => {
                            const objectUrl = URL.createObjectURL(archivo);
                            archivo.__cotPreviewBlobUrl = objectUrl;

                            const preview = document.createElement('div');
                            preview.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                            const btnEliminar = document.createElement('button');
                            btnEliminar.type = 'button';
                            btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
                            btnEliminar.textContent = '×';
                            btnEliminar.addEventListener('click', (e) => {
                                e.preventDefault();
                                if (archivo.__cotPreviewBlobUrl) {
                                    try {
                                        URL.revokeObjectURL(archivo.__cotPreviewBlobUrl);
                                    } catch (_) {}
                                    delete archivo.__cotPreviewBlobUrl;
                                }
                                imagenesAgregadas.splice(imgIdx, 1);
                                actualizarPrevisualizaciones();
                            });

                            const img = document.createElement('img');
                            img.src = objectUrl;
                            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';

                            preview.appendChild(img);
                            preview.appendChild(btnEliminar);
                            previewContainer.appendChild(preview);
                        });
                    }
                    
                    // Guardar referencia al array de imágenes para extracción posterior
                    divImagen.imagenesAgregadas = imagenesAgregadas;
                    divImagen.dataset.tecnicaIdx = idx;
                });
            }
            
            // ========================================
            // LÓGICA DE LOGO COMPARTIDO
            // ========================================
            const checkboxLogoCompartido = document.getElementById('dUsarLogoCompartido');
            const seccionLogoCompartido = document.getElementById('dSeccionLogoCompartido');
            const btnAgregarLogoCompartido = document.getElementById('dBtnAgregarLogoCompartido');
            
            // Almacenar logos compartidos en contexto
            const logosCompartidos = {};
            window.logosCompartidosCotizacionIndividual = logosCompartidos;
            
            checkboxLogoCompartido.addEventListener('change', (e) => {
                if (e.target.checked) {
                    seccionLogoCompartido.style.display = 'block';
                } else {
                    seccionLogoCompartido.style.display = 'none';
                    // Limpiar logos compartidos
                    for (let key in logosCompartidos) {
                        delete logosCompartidos[key];
                    }
                    document.getElementById('dLogosCompartidosContenedor').innerHTML = '';
                }
            });
            
            btnAgregarLogoCompartido.addEventListener('click', (e) => {
                e.preventDefault();
                abrirModalLogoCompartido(tecnicas, logosCompartidos);
            });
        },
        preConfirm: () => {
            // Seguridad anti doble click
            if (window.__guardandoTecnica) {
                Swal.showValidationMessage('Guardando...');
                return false;
            }
            window.__guardandoTecnica = true;
            const confirmBtn = Swal.getConfirmButton();
            if (confirmBtn) confirmBtn.disabled = true;

            // Validar prenda
            const nombrePrenda = document.getElementById('dNombrePrenda').value.trim().toUpperCase();
            if (!nombrePrenda) {
                Swal.showValidationMessage('Completa el nombre de la prenda');
                window.__guardandoTecnica = false;
                if (confirmBtn) confirmBtn.disabled = false;
                return false;
            }
            
            // Guardar prenda en historial (async, no bloquea)
            fetch('/api/logo-cotizacion-tecnicas/prendas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ nombre: nombrePrenda })
            }).catch(e => console.warn(' No se pudo guardar prenda en historial:', e));
            
            // Validar ubicaciones
            const ubicacionesPorTecnica = {};
            let valido = true;
            
            window.tecnicasCombinadas.forEach((tecnica, idx) => {
                const ubicacionesList = window['dUbicacionesTecnica' + idx] || [];
                if (!ubicacionesList || ubicacionesList.length === 0) {
                    Swal.showValidationMessage(`Agrega al menos una ubicación para ${tecnica.nombre}`);
                    valido = false;
                    return;
                }
                ubicacionesPorTecnica[idx] = ubicacionesList;
            });
            
            if (!valido) {
                window.__guardandoTecnica = false;
                if (confirmBtn) confirmBtn.disabled = false;
                return false;
            }
            
            // Recopilar imágenes por técnica desde el nuevo sistema de drag and drop
            const imagenesPorTecnica = {};
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                const divImagenes = imagesPorTecnicaDiv.querySelectorAll('[data-tecnica-idx]');
                divImagenes.forEach((divImagen, idx) => {
                    const imagenesAgregadas = [];
                    const idxTecnica = parseInt(divImagen.getAttribute('data-tecnica-idx'));
                    if (divImagen.imagenesAgregadas && divImagen.imagenesAgregadas.length > 0) {
                        imagenesPorTecnica[idxTecnica] = divImagen.imagenesAgregadas;
                    }
                });
            }
            
            // Las variaciones están deshabilitadas
            const variacionesPrenda = {};
            
            // Las telas están deshabilitadas
            const telas = [];
            
            return {
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('dObservaciones').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
                imagenesPorTecnica: imagenesPorTecnica,
                logosCompartidos: window.logosCompartidosCotizacionIndividual || {},
                variaciones_prenda: null,
                telas: null  // Telas deshabilitadas
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Cerrar el modal inmediatamente
            Swal.close();
            // Guardar después de cerrar
            guardarTecnicaCombinada(result.value);

            // Liberar lock después de un tiempo razonable (guardarTecnicaCombinada usa Promises internas)
            setTimeout(() => {
                window.__guardandoTecnica = false;
            }, 1500);
        } else {
            // Cancelado o cerrado: liberar lock y limpiar estado
            window.__guardandoTecnica = false;
            window.tecnicasCombinadas = null;
            window.modoTecnicasCombinadas = null;
        }
    });
}

// =========================================================
// 4.3 MODAL LOGO COMPARTIDO
// =========================================================

function abrirModalLogoCompartido(tecnicas, logosCompartidos) {
    const checkboxesTecnicas = tecnicas.map((tecnica, idx) => `
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer;">
            <input type="checkbox" class="dCheckTecnicaCompartidaLogo" data-tecnica-idx="${idx}" data-tecnica-nombre="${tecnica.nombre}" style="width: 18px; height: 18px; cursor: pointer;">
            <label style="flex: 1; cursor: pointer; margin: 0; font-weight: 500; color: #333;">${tecnica.nombre}</label>
        </div>
    `).join('');
    
    // Modal emergente flotante
    const modalHTML = `
        <div id="dModalLogoCompartidoEmergente" style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            max-height: 80vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 3000;
            padding: 30px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-y: auto;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #333;">Logos Compartidos</h2>
                <button type="button" id="dBtnCerrarModalLogoCompartido" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">✕</button>
            </div>
            
            <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: #666;">Selecciona las técnicas que comparten el mismo logo:</p>
            
            <div style="display: grid; gap: 10px; margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
                ${checkboxesTecnicas}
            </div>
            
            <div style="border-top: 1px solid #eee; padding-top: 15px; margin-bottom: 20px;">
                <label style="font-weight: 600; display: block; margin-bottom: 10px; color: #333;">Imagen del Logo:</label>
                <div class="dImagenesLogoCompartidoDropzone" style="
                    border: 2px dashed #ddd;
                    border-radius: 6px;
                    padding: 20px;
                    text-align: center;
                    background: #fafafa;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    <div style="margin-bottom: 8px; font-size: 1.3rem;"></div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imagen aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar</p>
                    <input type="file" class="dImagenLogoCompartidoInput" accept="image/*" style="display: none;" />
                </div>
                <div class="dImagenLogoCompartidoPreview" style="margin-top: 12px; display: flex; justify-content: center;">
                    <!-- Preview de imagen -->
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button type="button" id="dBtnCancelarLogoCompartido" style="
                    background: #f0f0f0;
                    color: #333;
                    border: 1px solid #ddd;
                    padding: 10px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.9rem;
                ">Cancelar</button>
                <button type="button" id="dBtnGuardarLogoCompartido" style="
                    background: #0284c7;
                    color: white;
                    border: none;
                    padding: 10px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.9rem;
                ">Guardar Logo Compartido</button>
            </div>
        </div>
        
        <!-- Backdrop -->
        <div id="dBackdropLogoCompartido" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 2999;
        "></div>
    `;
    
    // Insertar en DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modalElement = document.getElementById('dModalLogoCompartidoEmergente');
    const backdropElement = document.getElementById('dBackdropLogoCompartido');
    const btnCerrar = document.getElementById('dBtnCerrarModalLogoCompartido');
    const btnCancelar = document.getElementById('dBtnCancelarLogoCompartido');
    const btnGuardar = document.getElementById('dBtnGuardarLogoCompartido');
    const dropzone = modalElement.querySelector('.dImagenesLogoCompartidoDropzone');
    const inputFile = modalElement.querySelector('.dImagenLogoCompartidoInput');
    const previewDiv = modalElement.querySelector('.dImagenLogoCompartidoPreview');
    const tecnicasCheckboxes = modalElement.querySelectorAll('.dCheckTecnicaCompartidaLogo');
    
    let imagenSeleccionada = null;
    let previewBlobUrlLogoCompartido = null;

    // Setup dropzone
    dropzone.addEventListener('click', () => inputFile.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#0284c7';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        procesarArchivoLogoCompartido(Array.from(e.dataTransfer.files));
    });
    
    inputFile.addEventListener('change', (e) => {
        procesarArchivoLogoCompartido(Array.from(e.target.files));
    });
    
    function procesarArchivoLogoCompartido(archivos) {
        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
        if (imagenes.length > 0) {
            imagenSeleccionada = imagenes[0];
            mostrarPreviewLogoCompartido();
        }
    }
    
    function mostrarPreviewLogoCompartido() {
        if (!imagenSeleccionada) return;

        if (previewBlobUrlLogoCompartido) {
            try {
                URL.revokeObjectURL(previewBlobUrlLogoCompartido);
            } catch (_) {}
            previewBlobUrlLogoCompartido = null;
        }
        previewBlobUrlLogoCompartido = URL.createObjectURL(imagenSeleccionada);
        previewDiv.innerHTML = `
                <div style="position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 2px solid #0284c7;">
                    <img src="${previewBlobUrlLogoCompartido}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            `;
    }

    function cerrarModal() {
        if (previewBlobUrlLogoCompartido) {
            try {
                URL.revokeObjectURL(previewBlobUrlLogoCompartido);
            } catch (_) {}
            previewBlobUrlLogoCompartido = null;
        }
        modalElement.remove();
        backdropElement.remove();
    }
    
    function guardarYCerrar() {
        const tecnicasSeleccionadas = Array.from(tecnicasCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-tecnica-nombre'));
        
        if (tecnicasSeleccionadas.length < 2) {
            alert('Selecciona al menos 2 técnicas');
            return;
        }
        
        if (!imagenSeleccionada) {
            alert('Sube una imagen para las técnicas compartidas');
            return;
        }
        
        // Procesar
        const clave = tecnicasSeleccionadas.sort().join('-');
        logosCompartidos[clave] = imagenSeleccionada;

        try {
            document.dispatchEvent(new CustomEvent('logoCompartido:guardado', {
                detail: {
                    clave,
                    file: imagenSeleccionada,
                    tecnicas: tecnicasSeleccionadas
                }
            }));
        } catch (_) {}
        
        console.log(' DEBUG - Logo guardado en modal:', {
            clave: clave,
            imagen: imagenSeleccionada,
            imagenNombre: imagenSeleccionada.name,
            logosCompartidos: logosCompartidos,
            windowLogos: window.logosCompartidosCotizacionIndividual
        });
        
        // Actualizar UI
        mostrarLogosCompartidosAgregados(logosCompartidos, tecnicasSeleccionadas);
        cerrarModal();
    }
    
    // Event listeners
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    btnGuardar.addEventListener('click', guardarYCerrar);
    backdropElement.addEventListener('click', cerrarModal);

    // Soportar pegar Ctrl+V dentro del dropzone de logo compartido
    try {
        adjuntarPasteHintEnDropzoneLogo(dropzone);
    } catch (_) {}
}

function renderizarLogosCompartidosEdicion(prendaDiv, datosPrenda) {
    if (!prendaDiv || !datosPrenda || !datosPrenda.logoCompartido) return;
    const seccion = prendaDiv.querySelector('#editSeccionLogoCompartido');
    if (!seccion) return;

    // Mantener el botón + Agregar
    const btnAgregar = seccion.querySelector('#editBtnAgregarLogoCompartido');

    // Eliminar filas actuales
    seccion.querySelectorAll('.edit-logo-compartido-row').forEach(el => el.remove());

    const items = datosPrenda.logoCompartido.items || {};
    Object.entries(items).forEach(([clave, item]) => {
        const tecnicasTxt = (item.tecnicasCompartidas || []).join(' + ');
        const previewUrl = item.previewUrl || '';

        const row = document.createElement('div');
        row.className = 'edit-logo-compartido-row';
        row.setAttribute('data-logo-clave', clave);
        row.style.cssText = 'border: 1px solid #dbeafe; background: #f0f7ff; border-radius: 6px; padding: 10px;';
        row.innerHTML = `
            <div style="display:flex; justify-content: space-between; gap: 10px; align-items: flex-start; margin-bottom: 6px;">
                <div style="font-weight: 700; color: #0369a1;">Logo compartido: ${tecnicasTxt}</div>
                <button type="button" class="edit-logo-compartido-del" data-logo-clave="${clave}" style="background: #fee2e2; color:#991b1b; border:1px solid #fecaca; padding: 6px 10px; border-radius: 6px; cursor:pointer; font-weight: 800;">Eliminar</button>
            </div>
            <div style="display: grid; grid-template-columns: 110px 1fr; gap: 10px; align-items: center;">
                <div class="edit-logo-compartido-preview" data-logo-clave="${clave}" style="width: 110px; height: 110px; border-radius: 6px; overflow: hidden; border: 1px solid #bfdbfe; background: white; display: flex; align-items: center; justify-content: center;">
                    ${previewUrl ? `<img src="${previewUrl}" style="width: 100%; height: 100%; object-fit: cover;" />` : `<span style="color:#64748b; font-size:0.8rem;">Sin imagen</span>`}
                </div>
                <div class="edit-logo-compartido-inputwrap" data-logo-clave="${clave}" style="position: relative;">
                    <input type="file" class="edit-input-logo-compartido" data-logo-clave="${clave}" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #0284c7; border-radius: 4px; cursor: pointer; box-sizing: border-box; font-size: 0.85rem;">
                    <div style="margin-top: 6px; color: #64748b; font-size: 0.8rem;">Reemplazar logo compartido (o pega con Ctrl+V)</div>
                </div>
            </div>
        `;

        seccion.appendChild(row);
    });

    if (btnAgregar && btnAgregar.parentNode === seccion) {
        // Asegurar que el botón quede arriba
        seccion.insertBefore(btnAgregar, seccion.firstChild);
    }

    // Re-bind listeners (reusa los existentes)
    try {
        agregarEventListenersEdicionPrenda(prendaDiv, datosPrenda);
    } catch (_) {}
}

function mostrarLogosCompartidosAgregados(logosCompartidos, tecnicasNuevas) {
    const contenedor = document.getElementById('dLogosCompartidosContenedor');
    if (!contenedor) return;
    
    // Reconstruir contenedor
    contenedor.innerHTML = '';
    
    // Obtener el div de imágenes por técnica (para ocultar/mostrar dropzones)
    const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
    const tecnicas = window.tecnicasCombinadas || [];
    
    // Primero, mostrar TODOS los dropzones (estado inicial)
    if (imagesPorTecnicaDiv) {
        tecnicas.forEach((tecnica, idx) => {
            const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${idx}"]`);
            if (divTecnica) {
                divTecnica.style.display = 'block';
            }
        });
    }
    
    // Ahora, ocultar los que tienen logos compartidos
    for (let clave in logosCompartidos) {
        const tecnicasArray = clave.split('-');
        const imagen = logosCompartidos[clave];
        
        // PASO 1: Ocultar los contenedores de las técnicas que comparten logo
        tecnicasArray.forEach(nombreTecnica => {
            const tecnicaIdx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
            if (tecnicaIdx >= 0 && imagesPorTecnicaDiv) {
                const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${tecnicaIdx}"]`);
                if (divTecnica) {
                    divTecnica.style.display = 'none';
                }
            }
        });
        
        // PASO 2: Crear tarjeta del logo compartido (COMPACTA COMO PASO 3)
        const divLogo = document.createElement('div');
        divLogo.className = 'dLogoCompartidoItem';
        divLogo.style.cssText = 'padding: 8px 12px; background: #e0f2fe; border: 2px solid #0284c7; border-radius: 4px; display: flex; gap: 10px; align-items: center; margin-bottom: 8px;';
        divLogo.setAttribute('data-clave', clave);
        
        // Crear preview ANTES que el label (para que el texto vaya a la derecha)
        const previewDiv = document.createElement('div');
        previewDiv.style.cssText = 'flex: 0 0 auto;';
        previewDiv.className = `dImagenCompartidaPreview-${clave}`;
        
        const img = document.createElement('img');
        img.style.cssText = 'width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid #0284c7;';

        const blobUrl = URL.createObjectURL(imagen);
        divLogo.setAttribute('data-preview-blob-url', blobUrl);
        img.src = blobUrl;

        previewDiv.appendChild(img);
        
        // Label con texto (a la derecha de la imagen)
        const labelDiv = document.createElement('div');
        labelDiv.style.cssText = 'flex: 1; min-width: 0;';
        labelDiv.innerHTML = `
            <label style="font-weight: 600; font-size: 0.9rem; color: #0c4a6e; display: block; margin: 0; word-break: break-word;">
                ✓ Logo compartido - ${tecnicasArray.join(' - ')}
            </label>
        `;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = '✕';
        btnEliminar.style.cssText = 'background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 1.1rem; flex: 0 0 auto; line-height: 1;';
        btnEliminar.className = `dBtnEliminarCompartida-${clave}`;
        
        btnEliminar.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Eliminar del objeto de logos
            delete logosCompartidos[clave];

            const blobAttr = divLogo.getAttribute('data-preview-blob-url');
            if (blobAttr) {
                try {
                    URL.revokeObjectURL(blobAttr);
                } catch (_) {}
                divLogo.removeAttribute('data-preview-blob-url');
            }

            // Mostrar nuevamente los dropzones de las técnicas
            tecnicasArray.forEach(nombreTecnica => {
                const idx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
                if (idx >= 0 && imagesPorTecnicaDiv) {
                    const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${idx}"]`);
                    if (divTecnica) {
                        divTecnica.style.display = 'block';
                    }
                }
            });
            
            // Eliminar la tarjeta
            divLogo.remove();
            
            // Actualizar referencia global
            window.logosCompartidosCotizacionIndividual = logosCompartidos;
        });
        
        divLogo.appendChild(previewDiv);
        divLogo.appendChild(labelDiv);
        divLogo.appendChild(btnEliminar);
        
        contenedor.appendChild(divLogo);
    }
}

// =========================================================
// 5. FORMULARIO DINÁMICO DE PRENDAS EN MODAL
// =========================================================

function agregarFilaPrenda() {
    const container = document.getElementById('listaPrendas');
    
    if (!container) {

        return;
    }
    
    const numeroPrenda = container.children.length + 1;
    const prendasIndex = numeroPrenda - 1;
    
    const fila = document.createElement('div');
    fila.className = 'prenda-item';
    fila.style.cssText = 'margin-bottom: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 600; color: #333; font-size: 0.9rem;">
                <span>Prenda <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="this.closest('.prenda-item').remove(); actualizarNumeracionPrendas();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar prenda">
                    ✕
                </button>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Nombre de prenda</label>
                <input type="text" class="nombre_prenda" placeholder="CAMISA, PANTALÓN, POLO..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Ubicaciones</label>
                <div style="display: flex; gap: 6px; margin-bottom: 8px;">
                    <input type="text" class="ubicacion-input" placeholder="PECHO, ESPALDA, MANGA..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
                    <button type="button" class="btn-agregar-ubicacion" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem;">
                        + Agregar
                    </button>
                </div>
                <div class="ubicaciones-lista" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 24px; align-content: flex-start;">
                    <!-- Ubicaciones agregadas aquí como tags -->
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Observaciones Generales</label>
                <textarea class="observaciones" rows="2" placeholder="Detalles adicionales (opcional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
            </div>
            
                        
                        
            <!-- Imágenes de Prenda (máximo 3) con Drag and Drop -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Imágenes</label>
                <div class="imagenes-dropzone-${prendasIndex}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 24px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                    <div style="margin-bottom: 8px; font-size: 1.5rem;"></div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333;">Arrastra imágenes aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar</p>
                    <input type="file" class="imagen-prenda-input-${prendasIndex}" accept="image/*" multiple style="display: none;" />
                </div>
                <div class="imagenes-preview-${prendasIndex}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                    <!-- Previsualizaciones aquí -->
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    const dropzone = fila.querySelector(`.imagenes-dropzone-${prendasIndex}`);
    const input = fila.querySelector(`.imagen-prenda-input-${prendasIndex}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-${prendasIndex}`);
    const imagenesAgregadas = configurarDropzoneImagenes({ dropzone, input, previewContainer });

    Object.defineProperty(fila, 'imagenesAgregadas', {
        get: function() { return imagenesAgregadas; }
    });
}

// =========================================================
// 5.5 ACTUALIZAR NUMERACIÓN DE PRENDAS
// =========================================================

function actualizarNumeracionPrendas() {
    const prendas = document.querySelectorAll('.prenda-item');
    prendas.forEach((prenda, index) => {
        const numeroPrendaElement = prenda.querySelector('.numero-prenda');
        if (numeroPrendaElement) {
            numeroPrendaElement.textContent = index + 1;
        }
    });
}

// 5.6 ELIMINAR FILA DE PRENDA
// =========================================================

function eliminarFilaPrenda(prendasIndex) {
    const item = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (item) {
        item.remove();
        
        // Mostrar mensaje de sin prendas si no hay más prendas
        const listaPrendas = document.getElementById('listaPrendas');
        if (listaPrendas && listaPrendas.children.length === 0) {
            const noPrendasMsg = document.getElementById('noPrendasMsg');
            if (noPrendasMsg) {
                noPrendasMsg.style.display = 'block';
            }
        }
    }
}

// Agregar event listeners para ubicaciones cuando se agrega una prenda
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-agregar-ubicacion') || (e.target.closest('.btn-agregar-ubicacion'))) {
        e.preventDefault();
        const btn = e.target.closest('.btn-agregar-ubicacion');
        const input = btn.previousElementSibling;
        const ubicacion = input.value.trim();
        
        if (!ubicacion) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Escribe una ubicación antes de agregar',
                confirmButtonColor: '#1e40af'
            });
            return;
        }
        
        const lista = btn.closest('div').nextElementSibling;
        
        // Verificar que no esté duplicada (case-insensitive)
        const existentes = Array.from(lista.querySelectorAll('[data-ubicacion]')).map(tag => tag.getAttribute('data-ubicacion').toLowerCase());
        if (existentes.includes(ubicacion.toLowerCase())) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya fue agregada',
                confirmButtonColor: '#1e40af'
            });
            return;
        }
        
        // Crear tag de ubicación
        const tag = document.createElement('div');
        tag.setAttribute('data-ubicacion', ubicacion);
        tag.style.cssText = 'background: #f0f0f0; color: #333; padding: 6px 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; font-weight: 500;';
        tag.innerHTML = `
            ${ubicacion}
            <button type="button" style="background: none; border: none; color: #999; cursor: pointer; font-size: 0.9rem; padding: 0; line-height: 1; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">×</button>
        `;
        
        // Eliminar ubicación al hacer click en ×
        tag.querySelector('button').addEventListener('click', function(e) {
            e.preventDefault();
            tag.remove();
            input.focus();
        });
        
        lista.appendChild(tag);
        input.value = '';
        input.focus();
    }
});

// =========================================================
// 6. GUARDAR TÉCNICA CON PRENDAS
// =========================================================

async function guardarTecnica() {
    // Lock anti doble click
    if (window.__guardandoTecnica) {
        return;
    }
    window.__guardandoTecnica = true;
    // Fallback: liberar lock aunque alguna rama sea async por Promises internas
    setTimeout(() => {
        window.__guardandoTecnica = false;
    }, 1200);

    // MODO EDICIÓN: actualizar prenda(s) existente(s) en tecnicasAgregadas
    if (window.modoEdicionPrenda) {
        guardarEdicionPrenda();
        return;
    }

    // Verificar si estamos en modo técnicas combinadas
    if (window.modoTecnicasCombinadas === 'iguales') {

        guardarTecnicaCombinada();
        return;
    }

    // Modo simple: una única técnica
    guardarTecnicaSimple();
}

function guardarTecnicaSimple() {
    const checkboxes = document.querySelectorAll('.tecnica-checkbox:checked');
    const tiposIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (tiposIds.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Selecciona un tipo de técnica'
        });
        return;
    }
    
    const tipoId = tiposIds[0];
    
    let prendas;
    try {
        prendas = extraerPrendasDelModal();
    } catch (error) {
        // La validación ya mostró el error con SweetAlert

        return;
    }
    
    if (!prendas || prendas.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Agrega al menos una prenda'
        });
        return;
    }
    
    const tipo = tiposDisponibles.find(t => t.id === tipoId);
    
    // Crear técnica SIMPLE (sin procesar a data URLs, mantener Files)
    const nuevaTecnica = {
        tipo_logo: tipo,
        prendas: prendas,
        es_combinada: false,  // ← IMPORTANTE: Marcar como simple
        grupo_combinado: null
    };
    

    tecnicasAgregadas.push(nuevaTecnica);
    window.tecnicasAgregadas = tecnicasAgregadas;  //  Sincronizar global

    
    // Cerrar modal y actualizar
    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
}

function guardarTecnicaCombinada(datosForm) {
    const tecnicas = window.tecnicasCombinadas;
    
    console.log(' DEBUG - guardarTecnicaCombinada() llamado con datosForm:', !!datosForm);

    
    // Si no se pasa datosForm, construirlo desde el formulario actual
    if (!datosForm) {
        console.log(' DEBUG - Construyendo datosForm desde formulario...');
        // Validar que haya nombre de prenda
        const nombrePrenda = document.querySelector('.nombre_prenda')?.value.trim();
        if (!nombrePrenda) {

            return;
        }
        
        // Obtener ubicaciones
        const ubicacionesPorTecnica = {};
        tecnicas.forEach((tecnica, idx) => {
            const ubicacion = document.querySelector(`.dUbicacion-${idx}`)?.value.trim() || '';
            ubicacionesPorTecnica[idx] = [ubicacion];
        });
        
        // Obtener imágenes
        const imagenesPorTecnica = {};
        const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
        if (imagesPorTecnicaDiv) {
            const divImagenes = imagesPorTecnicaDiv.querySelectorAll('[data-tecnica-idx]');

            divImagenes.forEach(div => {
                const idx = parseInt(div.getAttribute('data-tecnica-idx'));
                console.log(`  Div ${idx}:`, {
                    tiene_imagenesAgregadas: !!div.imagenesAgregadas,
                    cantidad: div.imagenesAgregadas ? div.imagenesAgregadas.length : 0,
                    contenido: div.imagenesAgregadas ? div.imagenesAgregadas.map(f => f.name) : []
                });
                if (div.imagenesAgregadas && div.imagenesAgregadas.length > 0) {
                    imagenesPorTecnica[idx] = div.imagenesAgregadas;

                }
            });
        }
        
        // Obtener telas (COLOR, TELA, REFERENCIA E IMAGEN)
        const telas = [];
        const tbodyTelas = document.getElementById('dTablaTelasMulti');
        console.log(' DEBUG - Buscando tabla de telas:', {
            tbodyTelasEncontrado: !!tbodyTelas,
            elementId: 'dTablaTelasMulti'
        });
        
        if (tbodyTelas) {
            const filasTelaMulti = tbodyTelas.querySelectorAll('.fila-tela-multi');
            console.log(` DEBUG - Filas de telas encontradas: ${filasTelaMulti.length}`);
            
            filasTelaMulti.forEach((filaTela, telaIdx) => {
                const color = filaTela.querySelector('.input-color-multi')?.value?.trim();
                const tela = filaTela.querySelector('.input-tela-multi')?.value?.trim();
                const referencia = filaTela.querySelector('.input-referencia-multi')?.value?.trim();
                const inputFile = filaTela.querySelector('.input-file-tela-multi');
                
                // Guardar archivo directamente (no usar FileReader async)
                const archivo = (inputFile && inputFile.files.length > 0) 
                    ? inputFile.files[0] 
                    : null;
                
                if (color || tela || referencia) {
                    telas.push({
                        color: color || null,
                        tela: tela || null,
                        referencia: referencia || null,
                        archivo: archivo
                    });
                }
            });
        }
        
        console.log(` DEBUG - Telas extraídas para datosForm:`, telas);
        
        datosForm = {
            nombre_prenda: nombrePrenda,
            observaciones: document.getElementById('dObservaciones')?.value.trim() || '',
            ubicacionesPorTecnica: ubicacionesPorTecnica,
            imagenesPorTecnica: imagenesPorTecnica,
            telas: telas.length > 0 ? telas : null
        };
    }
    

    
    // Generar un ID único para el grupo combinado (basado en timestamp en milisegundos)
    // Esto garantiza unicidad a nivel de usuario y sesión
    const grupoId = Math.floor(Date.now() / 1000) + Math.floor(Math.random() * 10000);
    
    // Fase 2: previews en memoria con blob: (sin base64 en imagenes_data_urls)
    const imagenesProcessadas = {};
    for (const idx in datosForm.imagenesPorTecnica) {
        const archivos = datosForm.imagenesPorTecnica[idx];
        if (archivos && archivos.length > 0) {
            imagenesProcessadas[idx] = archivos.map((archivo) => {
                const preview_url = URL.createObjectURL(archivo);
                return {
                    preview_url,
                    nombre: archivo.name
                };
            });
        }
    }

    const logosCompartidosForm = datosForm.logosCompartidos || {};
    const logosProcessados = [];
    for (const clave in logosCompartidosForm) {
        const archivo = logosCompartidosForm[clave];
        if (!(archivo instanceof File)) {
            continue;
        }
        const tecnicasCompartidas = clave.split('-');
        logosProcessados.push({
            preview_url: URL.createObjectURL(archivo),
            nombre: archivo.name,
            tecnicasCompartidas,
            clave
        });
    }

    tecnicas.forEach((tipo, idx) => {
        const ubicacionesTecnica = datosForm.ubicacionesPorTecnica[idx] || [];
        const imagenesTecnica = imagenesProcessadas[idx] || [];

        const logosParaEstaTecnica = logosProcessados.filter(logo =>
            logo.tecnicasCompartidas.includes(tipo.nombre)
        );

        const todasLasImagenes = [
            ...imagenesTecnica.map(img => ({
                ...img,
                esCompartida: false
            })),
            ...logosParaEstaTecnica.map((logo, logoIdx) => ({
                preview_url: logo.preview_url,
                nombre: logo.nombre,
                nombreCompartido: logo.clave,
                tecnicasCompartidas: logo.tecnicasCompartidas,
                esCompartida: true,
                imagenIndex: imagenesTecnica.length + logoIdx
            }))
        ];

        const nuevaTecnica = {
            tipo_logo: tipo,
            prendas: [{
                nombre_prenda: datosForm.nombre_prenda,
                observaciones: datosForm.observaciones,
                ubicaciones: ubicacionesTecnica,
                talla_cantidad: [],
                variaciones_prenda: datosForm.variaciones_prenda || null,
                imagenes_files: datosForm.imagenesPorTecnica[idx] || [],
                imagenes_data_urls: todasLasImagenes,
                telas: datosForm.telas || null
            }],
            es_combinada: true,
            grupo_combinado: grupoId,
            logosCompartidos: datosForm.logosCompartidos
        };

        console.log(' DEBUG - nuevaTecnica armada:', {
            tecnica: tipo.nombre,
            logosCompartidos: datosForm.logosCompartidos,
            claves: Object.keys(datosForm.logosCompartidos || {})
        });

        tecnicasAgregadas.push(nuevaTecnica);
    });

    window.tecnicasAgregadas = tecnicasAgregadas;

    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;
    window.logosCompartidosCotizacionIndividual = {};

    const checkboxes = document.querySelectorAll('input[type="checkbox"][data-tecnica-id]');
    checkboxes.forEach(cb => cb.checked = false);

    renderizarTecnicasAgregadas();

    if (typeof renderizarLogoPrendasTecnicas === 'function') {
        renderizarLogoPrendasTecnicas();
    }
}

function extraerPrendasDelModal() {
    const prendas = [];
    const prendasRows = document.querySelectorAll('#listaPrendas > div');
    
    prendasRows.forEach((fila, index) => {
        const nombrePrenda = fila.querySelector('.nombre_prenda')?.value;
        const observaciones = fila.querySelector('.observaciones')?.value;
        
        const ubicacionesChecked = Array.from(
            fila.querySelectorAll('.ubicaciones-lista')[0]?.querySelectorAll('[data-ubicacion]') || []
        ).map(tag => tag.getAttribute('data-ubicacion'));
        
        // Recopilar imágenes del array almacenado en la fila
        const imagenesArray = fila.imagenesAgregadas || [];
        
        // Validar datos
        if (!nombrePrenda || nombrePrenda.trim() === '') {
            Swal.fire({
                icon: 'error',
                title: 'Campo vacío',
                text: `Prenda ${index + 1}: Rellena el nombre de la prenda`
            });
            throw new Error('VALIDATION_ERROR');
        }
        
        if (ubicacionesChecked.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin ubicaciones',
                text: `${nombrePrenda}: Debes seleccionar al menos una ubicación`
            });
            throw new Error('VALIDATION_ERROR');
        }
        
        // Las tallas son opcionales, no validar si están vacías
        
        // Las variaciones están deshabilitadas
        const variacionesPrenda = {};
        
        // Las telas están deshabilitadas
        const telas = [];
        

        
        prendas.push({
            nombre_prenda: nombrePrenda,
            observaciones: observaciones,
            ubicaciones: ubicacionesChecked,
            talla_cantidad: [],
            imagenes_files: imagenesArray,  // Array de File objects
            variaciones_prenda: null,  // Variaciones deshabilitadas
            telas: null  // Telas deshabilitadas
        });
        

    });
    
    return prendas.length > 0 ? prendas : null;
}

// =========================================================
// 6.5 GUARDAR TODAS LAS TÉCNICAS EN BD (cuando se envía formulario)
// =========================================================

async function guardarTecnicasEnBD() {
    if (tecnicasAgregadas.length === 0) {
        return true; // Sin errores, continuar
    }
    
    const logoCotId = document.getElementById('logoCotizacionId')?.value;
    
    if (!logoCotId) {

        return false;
    }
    
    try {
        for (const tecnica of tecnicasAgregadas) {

            
            // Validar que tenga tipo_logo
            if (!tecnica.tipo_logo || !tecnica.tipo_logo.id) {

                continue;
            }
            



            
            // Crear FormData para enviar archivos
            const formData = new FormData();
            formData.append('logo_cotizacion_id', parseInt(logoCotId));
            formData.append('tipo_logo_id', tecnica.tipo_logo.id);
            formData.append('es_combinada', tecnica.es_combinada || false);
            
            // Solo agregar grupo_combinado si tiene valor
            if (tecnica.grupo_combinado !== null && tecnica.grupo_combinado !== undefined) {
                formData.append('grupo_combinado', tecnica.grupo_combinado);
            }
            
            // Agregar datos de prendas (sin imágenes File)
            const prendasSinArchivos = tecnica.prendas.map(prenda => ({
                nombre_prenda: prenda.nombre_prenda,
                observaciones: prenda.observaciones,
                ubicaciones: prenda.ubicaciones,
                talla_cantidad: prenda.talla_cantidad,
                variaciones_prenda: prenda.variaciones_prenda || null,
                imagenes_data_urls: prenda.imagenes_data_urls || [] // URLs para BD si las hay
            }));
            

            prendasSinArchivos.forEach((p, idx) => {


            });
            
            formData.append('prendas', JSON.stringify(prendasSinArchivos));
            
            // Agregar archivos de imágenes de cada prenda
            let totalArchivos = 0;
            let imagenesCompartidasProcesadas = {};
            
            // PASO 1: Procesar logos compartidos (si existen) - SOLO UNA VEZ
            const logosCompartidos = tecnica.logosCompartidos || {};
            console.log(' DEBUG - Logos compartidos recibidos:', {
                tecnica: tecnica.tipo_logo.nombre,
                logosCompartidos: logosCompartidos,
                claves: Object.keys(logosCompartidos),
                cantidad: Object.keys(logosCompartidos).length
            });
            const logosProcesados = {}; // Para almacenar qué claves ya procesamos
            
            for (let clave in logosCompartidos) {
                // Si ya procesamos este logo, saltar
                if (logosProcesados[clave]) continue;
                
                const archivo = logosCompartidos[clave];
                const tecnicasCompartidas = clave.split('-');
                
                // Enviar el archivo una única vez
                const fieldName = `imagenes_logo_compartido_${totalArchivos}`;
                formData.append(fieldName, archivo);
                
                // Enviar metadatos con la información de qué técnicas comparten este logo
                formData.append(`logo_compartido_metadata_${totalArchivos}`, JSON.stringify({
                    nombreCompartido: clave,
                    tecnicasCompartidas: tecnicasCompartidas,
                    nombreArchivo: archivo.name
                }));
                
                logosProcesados[clave] = true;
                imagenesCompartidasProcesadas[clave] = true;
                totalArchivos++;
            }
            
            // PASO 2: Procesar imágenes propias (no compartidas)
            tecnica.prendas.forEach((prenda, prendasIdx) => {
                if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {

                    prenda.imagenes_files.forEach((archivo, imgIdx) => {
                        const fieldName = `imagenes_prenda_${prendasIdx}_${imgIdx}`;

                        formData.append(fieldName, archivo);
                        totalArchivos++;
                    });
                }
            });
            // DEBUG: Mostrar contenido de FormData

            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {

                } else {

                }
            }
            

            const response = await fetch('/api/logo-cotizacion-tecnicas/agregar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            

            const data = await response.json();
            

            
            if (!data.success) {
                console.error(' Guardar Técnica - Error DETALLADO:', { 
                    response: data,
                    errors: JSON.stringify(data.errors || {}, null, 2),
                    message: data.message
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Error 422 - Validación',
                    html: '<pre style="text-align: left; font-size: 12px">' + JSON.stringify(data.errors || {}, null, 2) + '</pre>',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
            

        }
        
        tecnicasAgregadas = []; // Limpiar array temporal

        return true;
        
    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar técnicas: ' + (error.message || 'Error desconocido')
        });
        return false;
    }
}

// =========================================================
// 7. CARGAR Y RENDERIZAR TÉCNICAS AGREGADAS
// =========================================================

function cargarTecnicasAgregadas() {
    // Solo renderiza lo que está en tecnicasAgregadas (sin llamar a API)
    renderizarTecnicasAgregadas();
}

function renderizarTecnicasAgregadas() {
    const container = document.getElementById('tecnicas_agregadas');
    const sinTecnicas = document.getElementById('sin_tecnicas');
    
    if (!container) {
        return;
    }
    
    container.innerHTML = '';
    
    if (tecnicasAgregadas.length === 0) {
        if (sinTecnicas) sinTecnicas.style.display = 'block';
        return;
    }
    
    if (sinTecnicas) sinTecnicas.style.display = 'none';
    
    // PASO 1: Agrupar por NOMBRE DE PRENDA (no por técnica)
    const prendasMap = {};
    const imagenesParaCargar = [];
    tecnicasAgregadas.forEach((tecnica, tecnicaIndex) => {
        tecnica.prendas.forEach(prenda => {
            const nombrePrenda = prenda.nombre_prenda || 'SIN NOMBRE';
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: prenda.observaciones,
                    tecnicas: [],
                    imagenes: [],
                    _imagenesKeys: new Set(),
                    _imagenesDataKeys: new Set(),
                    _imgIndexByData: new Map(),
                    _logosCompartidosYaProcesados: new Set() // Deduplicación por prenda
                };
            }

            const agregarImagenDedupe = (imgObj) => {
                const dataKey = imgObj.data || '';
                const tecnicaKey = imgObj.tecnica || '';
                const compartidaKey = imgObj.esCompartida ? 'C' : 'N';
                
                // Generar una clave única basada en el contenido de la imagen (URL/Base64)
                // Esto evita que la misma imagen se renderice dos veces en la misma tarjeta
                const dataOnlyKey = `${compartidaKey}|${dataKey}`;

                // Si la imagen ya fue agregada a esta prenda, no la agregamos de nuevo
                if (prendasMap[nombrePrenda]._imagenesDataKeys.has(dataOnlyKey)) return;

                // Caso especial: Si una imagen normal (N) ya existe pero con una URL idéntica, 
                // podría ser que sea una imagen compartida que no fue marcada como tal (ej. desde borrador)
                if (compartidaKey === 'N') {
                    const prevIndex = prendasMap[nombrePrenda]._imgIndexByData.get(dataOnlyKey);
                    if (typeof prevIndex === 'number') {
                        return; // Ya existe
                    }
                }

                prendasMap[nombrePrenda]._imagenesDataKeys.add(dataOnlyKey);
                prendasMap[nombrePrenda].imagenes.push(imgObj);
                
                if (dataKey) {
                    prendasMap[nombrePrenda]._imgIndexByData.set(dataOnlyKey, prendasMap[nombrePrenda].imagenes.length - 1);
                }
            };
            
            // Agregar técnica y sus imágenes
            prendasMap[nombrePrenda].tecnicas.push({
                tecnica: tecnica,
                tecnicaIndex: tecnicaIndex
            });
            
            // Procesar imágenes_data_urls (pueden venir del paso 3 con referencias a tecnicas)
            if (prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0) {
                prenda.imagenes_data_urls.forEach(img => {
                    const esCompartida = img.esCompartida === true || !!img.nombreCompartido;
                    
                    if (esCompartida) {
                        const claveCompartida = img.nombreCompartido || 'compartida';
                        // Deduplicar logos compartidos POR PRENDA
                        if (!prendasMap[nombrePrenda]._logosCompartidosYaProcesados.has(claveCompartida)) {
                            prendasMap[nombrePrenda]._logosCompartidosYaProcesados.add(claveCompartida);
                            agregarImagenDedupe({
                                data: cotizacionLogoImgSrc(img) || img,
                                tecnica: '⭐ COMPARTIDA',
                                tecnicaColor: '#e0a853',
                                esCompartida: true,
                                nombreCompartido: img.nombreCompartido || null,
                                tecnicasCompartidas: img.tecnicasCompartidas || []
                            });
                        }
                    } else {
                        // Imágenes normales
                        agregarImagenDedupe({
                            data: cotizacionLogoImgSrc(img) || img,
                            tecnica: tecnica.tipo_logo.nombre,
                            tecnicaColor: tecnica.tipo_logo.color,
                            esCompartida: false
                        });
                    }
                });
            }

            // Procesar imágenes_files (solo si no hay data_urls para evitar duplicados visuales)
            const tieneDataUrls = prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0;
            if (!tieneDataUrls && prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                prenda.imagenes_files.forEach(archivo => {
                    imagenesParaCargar.push({
                        archivo: archivo,
                        nombrePrenda: nombrePrenda,
                        tecnica: tecnica.tipo_logo.nombre,
                        tecnicaColor: tecnica.tipo_logo.color,
                        esCompartida: false
                    });
                });
            }
        });
    });
    
    
    // Procesar las imágenes de File objects de forma asíncrona
    imagenesParaCargar.forEach(imgData => {
        const prendaEntry = prendasMap[imgData.nombrePrenda];
        if (prendaEntry) {
            const data = URL.createObjectURL(imgData.archivo);
            const dataOnlyKey = `N|${data || ''}`;
            if (!prendaEntry._imagenesDataKeys.has(dataOnlyKey)) {
                prendaEntry._imagenesDataKeys.add(dataOnlyKey);
                const key = `N|${imgData.tecnica || ''}|${data || ''}`;
                prendaEntry._imagenesKeys.add(key);
                prendaEntry.imagenes.push({
                    data,
                    tecnica: imgData.tecnica,
                    tecnicaColor: imgData.tecnicaColor,
                    esCompartida: false
                });
            }
        }

        const tarjeta = document.querySelector(`[data-prenda-nombre="${imgData.nombrePrenda}"]`);
        if (tarjeta) {
            const imgSection = tarjeta.querySelector('.imagenes-section');
            if (imgSection && prendasMap[imgData.nombrePrenda].imagenes.length > 0) {
                imgSection.style.display = 'block';
                const divGrid = imgSection.querySelector('div:last-child');
                if (divGrid) {
                    divGrid.innerHTML = prendasMap[imgData.nombrePrenda].imagenes.map((img, imgIdx) => `
                            <div style="display:flex; flex-direction: column; gap: 0.25rem; align-items: center; width: 100%; max-width: 120px;">
                                <div style="position: relative; border-radius: 4px; overflow: hidden; border: 2px solid ${img.tecnicaColor}; aspect-ratio: 1; width: 100%;">
                                    <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Imagen prenda">
                                </div>
                                <div style="font-size: 0.72rem; font-weight: 800; color: #334155; text-align: center; line-height: 1.05;">${img.esCompartida ? ('Logo compartido ' + ((img.tecnicasCompartidas && Array.isArray(img.tecnicasCompartidas) && img.tecnicasCompartidas.length > 0) ? img.tecnicasCompartidas.join('-') : '')) : (img.tecnica || '')}</div>
                            </div>
                        `).join('');
                }
            }
        }
    });
    
    // NUEVO DISEÑO: Contenedor con tarjetas - ANCHO FIJO como en la imagen
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; margin-bottom: 20px; max-width: 1400px;';
    
    // PASO 2: Renderizar TARJETAS por prenda
    let prendasContador = 0;
    Object.entries(prendasMap).forEach(([nombrePrenda, datosPrenda]) => {
        prendasContador++;
        
        // TARJETA DE LA PRENDA
        const tarjeta = document.createElement('div');
        tarjeta.setAttribute('data-prenda-nombre', nombrePrenda);
        tarjeta.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        `;
        tarjeta.onmouseover = function() {
            this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.15)';
            this.style.transform = 'translateY(-2px)';
        };
        tarjeta.onmouseout = function() {
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            this.style.transform = 'translateY(0)';
        };
        
        // HEADER CON TÉCNICAS Y NOMBRE - DISEÑO ACTUALIZADO COMO LA IMAGEN
        let headerHTML = '<div style="background: linear-gradient(135deg, #0a5ba3 0%, #084b8a 100%); color: white; padding: 1.2rem; border-bottom: 1px solid #ddd;">';
        headerHTML += '<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">';
        headerHTML += '<div>';
        headerHTML += '<h4 style="margin: 0 0 0.8rem 0; font-size: 1.1rem; font-weight: 700;">Prenda ' + prendasContador + ': ' + nombrePrenda + '</h4>';
        headerHTML += '<div style="display: flex; flex-wrap: wrap; gap: 0.2rem; align-items: center;" class="tecnicas-header">';
        
        datosPrenda.tecnicas.forEach((tecData, idx) => {
            const tecnica = tecData.tecnica;
            headerHTML += `
                <span style="
                    background: ${tecnica.tipo_logo.color};
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 0.9rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                " class="tecnica-badge" data-tecnica-idx="${tecData.tecnicaIndex}">
                    ${tecnica.tipo_logo.nombre}
                    ${datosPrenda.tecnicas.length > 1 ? `<button type="button" class="btn-eliminar-tecnica" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; padding: 0 4px; border-radius: 2px; font-size: 1rem;" data-tecnica-idx="${tecData.tecnicaIndex}">✕</button>` : ''}
                </span>
            `;
        });
        
        headerHTML += '</div>';
        headerHTML += '</div>';
        
        // Botones de editar y eliminar en el header
        headerHTML += '<div style="display: flex; gap: 0.6rem; flex-shrink: 0;">';
        headerHTML += `<button type="button" class="btn-editar-prenda" style="
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        " data-tecnica-indices="${datosPrenda.tecnicas.map(t => t.tecnicaIndex).join(',')}" data-prenda-nombre="${nombrePrenda}" title="Editar">
            <i class="fas fa-edit" style="font-size: 1rem;"></i>
        </button>`;
        headerHTML += `<button type="button" class="btn-eliminar-prenda" style="
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        " data-tecnica-indices="${datosPrenda.tecnicas.map(t => t.tecnicaIndex).join(',')}" data-prenda-nombre="${nombrePrenda}" title="Eliminar">
            <i class="fas fa-trash" style="font-size: 1rem;"></i>
        </button>`;
        headerHTML += '</div>';
        headerHTML += '</div></div>';
        
        // CUERPO CON CONTENIDO DE LA PRENDA - DISEÑO ACTUALIZADO
        let bodyHTML = '<div style="padding: 1.5rem;">';
        
        // SECCIÓN DE UBICACIONES (por técnica - SOLO de la prenda actual)
        const ubicacionesPorTecnica = {};
        datosPrenda.tecnicas.forEach(tecData => {
            const tecnica = tecData.tecnica;
            if (tecnica.prendas && tecnica.prendas.length > 0) {
                // Filtrar solo la prenda que coincide con nombrePrenda
                tecnica.prendas.forEach(p => {
                    if (p.nombre_prenda === nombrePrenda && p.ubicaciones && p.ubicaciones.length > 0) {
                        if (!ubicacionesPorTecnica[tecnica.tipo_logo.nombre]) {
                            ubicacionesPorTecnica[tecnica.tipo_logo.nombre] = [];
                        }
                        ubicacionesPorTecnica[tecnica.tipo_logo.nombre].push(...p.ubicaciones);
                    }
                });
            }
        });
        
        if (Object.keys(ubicacionesPorTecnica).length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1.2rem;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Ubicaciones:
                    </h6>
            `;
            
            Object.entries(ubicacionesPorTecnica).forEach(([nombreTec, ubicaciones]) => {
                bodyHTML += `
                    <div style="margin-bottom: 0.8rem; padding-left: 1rem; border-left: 4px solid #3b82f6;">
                        ${ubicaciones.map(ub => `
                            <div style="
                                font-size: 0.9rem;
                                color: #1e293b;
                                margin-bottom: 0.3rem;
                                text-transform: capitalize;
                            ">
                                • ${nombreTec} - ${ub}
                            </div>
                        `).join('')}
                    </div>
                `;
            });
            
            bodyHTML += '</div>';
        }
        
        // SECCIÓN DE IMÁGENES CON INDICADOR DE TÉCNICA
        if (datosPrenda.imagenes && datosPrenda.imagenes.length > 0) {
            bodyHTML += `
                <div class="imagenes-section" style="margin: 1.2rem 0;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Imágenes:
                    </h6>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        ${datosPrenda.imagenes.map((img, imgIdx) => {
                            // Renderizar diferente si es compartida
                            if (img.esCompartida) {
                                return `
                                    <div style="display:flex; flex-direction: column; gap: 0.25rem; align-items: center; width: auto; max-width: 120px; flex: 0 1 auto;">
                                        <div style="position: relative; border-radius: 4px; overflow: hidden; border: 3px solid #e0a853; aspect-ratio: 1; width: 100%; box-shadow: 0 0 8px rgba(224, 168, 83, 0.4);">
                                            <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Logo compartido">
                                        </div>
                                        <div style="font-size: 0.72rem; font-weight: 900; color: #a16207; text-align: center; line-height: 1.05;">Logo compartido ${(img.tecnicasCompartidas && Array.isArray(img.tecnicasCompartidas) && img.tecnicasCompartidas.length > 0) ? img.tecnicasCompartidas.join('-') : ''}</div>
                                    </div>
                                `;
                            } else {
                                return `
                                    <div style="display:flex; flex-direction: column; gap: 0.25rem; align-items: center; width: auto; max-width: 120px; flex: 0 1 auto;">
                                        <div style="position: relative; border-radius: 4px; overflow: hidden; border: 2px solid ${img.tecnicaColor}; aspect-ratio: 1; width: 100%;">
                                            <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Imagen prenda">
                                        </div>
                                        <div style="font-size: 0.72rem; font-weight: 800; color: #334155; text-align: center; line-height: 1.05;">${img.tecnica || ''}</div>
                                    </div>
                                `;
                            }
                        }).join('')}
                    </div>
                </div>
            `;
        } else {
            // Crear sección vacía pero oculta para poder actualizar después
            bodyHTML += `
                <div class="imagenes-section" style="margin: 1.2rem 0; display: none;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Imágenes:
                    </h6>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.2rem;">
                    </div>
                </div>
            `;
        }
        
        // VARIACIONES DE PRENDA
        // Obtener variaciones de la primera prenda que coincida (todas deben tener las mismas)
        let variacionesPrenda = null;
        datosPrenda.tecnicas.forEach(tecData => {
            const tecnica = tecData.tecnica;
            if (tecnica.prendas && tecnica.prendas.length > 0) {
                tecnica.prendas.forEach(p => {
                    if (p.nombre_prenda === nombrePrenda) {
                        if (p.variaciones_prenda && Object.keys(p.variaciones_prenda).length > 0) {
                            variacionesPrenda = p.variaciones_prenda;
                        }
                    }
                });
            }
        });
        
        // Las telas están deshabilitadas
        let telasPrenda = [];
        
        if (variacionesPrenda && Object.keys(variacionesPrenda).length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1rem; border: 1px solid #e0e7ff; border-radius: 6px; padding: 0.8rem; background: #f8f9ff;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #3730a3; display: block; margin-bottom: 0.6rem;">
                         Variaciones:
                    </span>
                    <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse;">
                        <tbody>
            `;
            
            // MANGA
            if (variacionesPrenda.manga && variacionesPrenda.manga.opcion) {
                bodyHTML += `
                    <tr style="border-bottom: 1px solid #e0e7ff;">
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Manga:</td>
                        <td style="padding: 0.4rem; color: #475569;">${variacionesPrenda.manga.opcion}</td>
                        ${variacionesPrenda.manga.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.manga.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            // BOLSILLOS
            if (variacionesPrenda.bolsillos && variacionesPrenda.bolsillos.opcion) {
                bodyHTML += `
                    <tr style="border-bottom: 1px solid #e0e7ff;">
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Bolsillos:</td>
                        <td style="padding: 0.4rem; color: #475569;">${variacionesPrenda.bolsillos.opcion}</td>
                        ${variacionesPrenda.bolsillos.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.bolsillos.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            // BROCHE/BOTÓN
            if (variacionesPrenda.broche_boton && variacionesPrenda.broche_boton.opcion) {
                const brocheColor = variacionesPrenda.broche_boton.opcion === 'BOTÓN' ? '#059669' : '#dc2626';
                bodyHTML += `
                    <tr>
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Broche/Botón:</td>
                        <td style="padding: 0.4rem;">
                            <span style="
                                background: ${brocheColor};
                                color: white;
                                padding: 0.3rem 0.6rem;
                                border-radius: 3px;
                                font-weight: 600;
                                display: inline-block;
                                font-size: 0.75rem;
                            ">
                                ${variacionesPrenda.broche_boton.opcion}
                            </span>
                        </td>
                        ${variacionesPrenda.broche_boton.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.broche_boton.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            bodyHTML += `
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        bodyHTML += '</div>';
        
        tarjeta.innerHTML = headerHTML + bodyHTML;
        contenedor.appendChild(tarjeta);
        
        // PROCESAR ARCHIVOS DE TELAS (File objects -> data URLs)
        if (datosPrenda.tecnicas && datosPrenda.tecnicas.length > 0) {
            datosPrenda.tecnicas.forEach(tecData => {
                const tecnica = tecData.tecnica;
                if (tecnica.prendas && tecnica.prendas.length > 0) {
                    tecnica.prendas.forEach(p => {
                        if (p.nombre_prenda === nombrePrenda && p.telas && p.telas.length > 0) {
                            p.telas.forEach((tela, telaIdx) => {
                                if (tela.archivo && tela.archivo instanceof File) {
                                    try {
                                        if (tela.__previewBlobUrl) {
                                            URL.revokeObjectURL(tela.__previewBlobUrl);
                                        }
                                    } catch (_) {}
                                    const u = URL.createObjectURL(tela.archivo);
                                    tela.__previewBlobUrl = u;
                                    tela.imagen = u;

                                    const row = tarjeta.querySelector(`[data-tela-idx="${telaIdx}"]`);
                                    if (row) {
                                        const tdImagen = row.querySelector('td:last-child');
                                        if (tdImagen) {
                                            tdImagen.innerHTML = `<img src="${u}" style="max-width: 60px; max-height: 60px; border-radius: 4px; border: 1px solid #ddd;" alt="Imagen tela">`;
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }
    });
    
    container.appendChild(contenedor);
    
    // EVENT LISTENERS para botones en el header
    document.querySelectorAll('.btn-editar-prenda').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const indicesStr = e.currentTarget.getAttribute('data-tecnica-indices');
            const nombrePrenda = e.currentTarget.getAttribute('data-prenda-nombre');
            const indices = indicesStr.split(',').map(Number);
            abrirModalEditarPrenda(indices, nombrePrenda);
        });
    });
    
    document.querySelectorAll('.btn-eliminar-prenda').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const indicesStr = e.currentTarget.getAttribute('data-tecnica-indices');
            const nombrePrenda = e.currentTarget.getAttribute('data-prenda-nombre');
            const indices = indicesStr.split(',').map(Number);
            eliminarPrendaDelGrupo(indices, nombrePrenda);
        });
    });
}

function eliminarPrendaDelGrupo(tecnicaIndices, nombrePrenda) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar prenda?',
        text: 'Se eliminará la prenda seleccionada',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;

        const indices = Array.isArray(tecnicaIndices) ? tecnicaIndices : [tecnicaIndices];
        indices.forEach(idx => {
            const tecnica = tecnicasAgregadas[idx];
            if (!tecnica || !Array.isArray(tecnica.prendas)) return;

            tecnica.prendas = tecnica.prendas.filter(p => p && p.nombre_prenda !== nombrePrenda);
        });

        // Si alguna técnica quedó sin prendas, eliminarla
        for (let i = tecnicasAgregadas.length - 1; i >= 0; i--) {
            const t = tecnicasAgregadas[i];
            if (t && Array.isArray(t.prendas) && t.prendas.length === 0) {
                tecnicasAgregadas.splice(i, 1);
            }
        }

        renderizarTecnicasAgregadas();
        
        // Resetear modo edición por seguridad
        window.modoEdicionPrenda = false;
        window.datosPrendaEdicion = null;
        window.tecnicasInfoEdicion = null;
        window.tecnicaIndicesEdicion = null;
        window.prendaNombreOriginalEdicion = null;
        window.tecnicasSeleccionadasEdicionIds = null;
        window.logosCompartidosCotizacionIndividual = {};
    });
}

function abrirModalEditarPrenda(tecnicaIndices, nombrePrenda) {
    // Obtener las técnicas del grupo
    const tecnicasDelGrupo = tecnicaIndices.map(idx => tecnicasAgregadas[idx]);
    
    if (tecnicasDelGrupo.length === 0) {
        console.error(' No se encontraron las técnicas para editar');
        return;
    }
    
    // Buscar los datos de la prenda a editar
    let datosPrenda = null;
    let tecnicasInfo = [];
    const sharedPorUrl = new Map();
    
    tecnicasDelGrupo.forEach((tecnica, tecnicaIdx) => {
        tecnica.prendas.forEach(prenda => {
            if (prenda.nombre_prenda === nombrePrenda) {
                if (!datosPrenda) {
                    datosPrenda = {
                        nombre_prenda: prenda.nombre_prenda,
                        observaciones: prenda.observaciones || '',
                        tecnicas_imagenes: {},
                        logoCompartido: {
                            enabled: false,
                            items: {},
                            files: {}
                        }
                    };
                }

                // Detectar logos compartidos por metadata dentro de imagenes_data_urls
                const imagenesData = Array.isArray(prenda.imagenes_data_urls) ? prenda.imagenes_data_urls : [];
                const imagenesPropias = [];
                imagenesData.forEach(img => {
                    const esObj = img && typeof img === 'object';
                    const esCompartida = esObj && (img.esCompartida === true || !!img.nombreCompartido);
                    if (esCompartida) {
                        const clave = img.nombreCompartido || 'compartida';
                        if (!datosPrenda.logoCompartido.items[clave]) {
                            datosPrenda.logoCompartido.items[clave] = {
                                tecnicasCompartidas: Array.isArray(img.tecnicasCompartidas) ? img.tecnicasCompartidas : (clave.split('-').map(t => t.trim()).filter(Boolean)),
                                previewUrl: cotizacionLogoImgSrc(img) || ''
                            };
                        }
                        datosPrenda.logoCompartido.enabled = true;
                    } else {
                        const url = cotizacionLogoImgSrc(img) || (typeof img === 'string' ? img : '');
                        if (url) {
                            // Mantener id si viene de BD
                            if (esObj && (img.id || cotizacionLogoImgSrc(img))) {
                                imagenesPropias.push({
                                    id: img.id || null,
                                    preview_url: cotizacionLogoImgSrc(img) || url,
                                    data_url: img.data_url || undefined
                                });
                            } else {
                                imagenesPropias.push(url);
                            }

                            const tecnicaNombre = tecnica && tecnica.tipo_logo && tecnica.tipo_logo.nombre ? tecnica.tipo_logo.nombre : '';
                            const u = String(url || '').trim();
                            if (u && tecnicaNombre) {
                                if (!sharedPorUrl.has(u)) {
                                    sharedPorUrl.set(u, new Set());
                                }
                                sharedPorUrl.get(u).add(tecnicaNombre);
                            }
                        }
                    }
                });

                // Si técnica tiene logosCompartidos en memoria, guardarlos para reemplazo
                if (tecnica.logosCompartidos && Object.keys(tecnica.logosCompartidos).length > 0) {
                    Object.keys(tecnica.logosCompartidos).forEach((clave) => {
                        const file = tecnica.logosCompartidos[clave];
                        if (file instanceof File) {
                            datosPrenda.logoCompartido.files[clave] = file;
                        }
                        if (!datosPrenda.logoCompartido.items[clave]) {
                            datosPrenda.logoCompartido.items[clave] = {
                                tecnicasCompartidas: clave.split('-').map(t => t.trim()).filter(Boolean),
                                previewUrl: ''
                            };
                        }
                        datosPrenda.logoCompartido.enabled = true;
                    });
                }

                // Almacenar imágenes por técnica (SOLO PROPIAS)
                datosPrenda.tecnicas_imagenes[tecnica.tipo_logo.nombre] = {
                    id_prenda_tecnica: prenda.id || null,
                    imagenes_data_urls: imagenesPropias,
                    imagenes_files: prenda.imagenes_files || [],
                    tipo_logo: tecnica.tipo_logo,
                    tecnicaIndex: tecnicaIndices[tecnicaIdx],
                    ubicaciones: prenda.ubicaciones || []
                };
                
                tecnicasInfo.push({
                    tecnicaIndex: tecnicaIndices[tecnicaIdx],
                    tipo_logo: tecnica.tipo_logo,
                    ubicaciones: prenda.ubicaciones || [],
                    imagenes_data_urls: prenda.imagenes_data_urls || [],
                    imagenes_files: prenda.imagenes_files || []
                });
            }
        });
    });

    sharedPorUrl.forEach((setTecnicas, url) => {
        const tecnicas = Array.from(setTecnicas).filter(Boolean);
        if (tecnicas.length < 2) return;
        const clave = tecnicas.join('-');
        if (!datosPrenda.logoCompartido.items[clave]) {
            datosPrenda.logoCompartido.items[clave] = {
                tecnicasCompartidas: tecnicas,
                previewUrl: url
            };
        } else {
            if (!datosPrenda.logoCompartido.items[clave].previewUrl) {
                datosPrenda.logoCompartido.items[clave].previewUrl = url;
            }
        }
        datosPrenda.logoCompartido.enabled = true;
    });
    
    if (!datosPrenda) {
        console.error(' No se encontró la prenda:', nombrePrenda);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró la prenda especificada'
        });
        return;
    }
    
    // Usar el mismo modal que para agregar pero con datos existentes
    abrirModalSimpleTecnicaConDatos(tecnicasDelGrupo[0], datosPrenda, tecnicasInfo, tecnicaIndices);
}

function abrirModalSimpleTecnicaConDatos(tipo, datosPrenda, tecnicasInfo, tecnicaIndices) {
    // Establecer modo edición
    window.modoEdicionPrenda = true;
    window.datosPrendaEdicion = datosPrenda;
    window.tecnicasInfoEdicion = tecnicasInfo;
    window.tecnicaIndicesEdicion = tecnicaIndices;
    window.prendaNombreOriginalEdicion = datosPrenda && datosPrenda.nombre_prenda ? datosPrenda.nombre_prenda : null;
    
    // Actualizar el nombre de la técnica en el modal
    const nombreElement = document.getElementById('tecnicaSeleccionadaNombre');
    if (nombreElement) {
        nombreElement.textContent = tipo.nombre;
    }
    
    // Limpiar y cargar prendas del modal con datos existentes
    const listaPrendas = document.getElementById('listaPrendas');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
        
        // Agregar la prenda con datos existentes
        agregarFilaPrendaConDatos(datosPrenda);
    }
    
    // Mostrar/ocultar mensaje de sin prendas
    const noPrendasMsg = document.getElementById('noPrendasMsg');
    if (noPrendasMsg) {
        noPrendasMsg.style.display = 'none';
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function agregarFilaPrendaConDatos(datosPrenda) {
    const listaPrendas = document.getElementById('listaPrendas');
    if (!listaPrendas) return;
    
    const prendaDiv = document.createElement('div');
    prendaDiv.className = 'prenda-item';
    prendaDiv.style.cssText = 'border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9f9f9;';
    
    // Sección Logo Compartido (si aplica)
    let logoCompartidoHTML = '';
    if (datosPrenda && datosPrenda.logoCompartido && Object.keys(datosPrenda.logoCompartido.items || {}).length > 0) {
        const filas = Object.entries(datosPrenda.logoCompartido.items).map(([clave, item]) => {
            const tecnicasTxt = (item.tecnicasCompartidas || []).join(' + ');
            const previewUrl = item.previewUrl || '';
            return `
                <div class="edit-logo-compartido-row" data-logo-clave="${clave}" style="border: 1px solid #dbeafe; background: #f0f7ff; border-radius: 6px; padding: 10px;">
                    <div style="display:flex; justify-content: space-between; gap: 10px; align-items: flex-start; margin-bottom: 6px;">
                        <div style="font-weight: 700; color: #0369a1;">Logo compartido: ${tecnicasTxt}</div>
                        <button type="button" class="edit-logo-compartido-del" data-logo-clave="${clave}" style="background: #fee2e2; color:#991b1b; border:1px solid #fecaca; padding: 6px 10px; border-radius: 6px; cursor:pointer; font-weight: 800;">Eliminar</button>
                    </div>
                    <div style="display: grid; grid-template-columns: 110px 1fr; gap: 10px; align-items: center;">
                        <div class="edit-logo-compartido-preview" data-logo-clave="${clave}" style="width: 110px; height: 110px; border-radius: 6px; overflow: hidden; border: 1px solid #bfdbfe; background: white; display: flex; align-items: center; justify-content: center;">
                            ${previewUrl ? `<img src="${previewUrl}" style="width: 100%; height: 100%; object-fit: cover;" />` : `<span style="color:#64748b; font-size:0.8rem;">Sin imagen</span>`}
                        </div>
                        <div class="edit-logo-compartido-inputwrap" data-logo-clave="${clave}" style="position: relative;">
                            <input type="file" class="edit-input-logo-compartido" data-logo-clave="${clave}" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #0284c7; border-radius: 4px; cursor: pointer; box-sizing: border-box; font-size: 0.85rem;">
                            <div style="margin-top: 6px; color: #64748b; font-size: 0.8rem;">Reemplazar logo compartido (o pega con Ctrl+V)</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const enabled = !!datosPrenda.logoCompartido.enabled;
        logoCompartidoHTML = `
            <div style="margin-bottom: 16px; padding: 12px; border: 2px solid #dbeafe; border-radius: 8px; background: #f8fbff;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom: 10px;">
                    <input type="checkbox" id="editUsarLogoCompartido" ${enabled ? 'checked' : ''} style="width:18px; height:18px; cursor:pointer;" />
                    <span style="font-weight:700; color:#0369a1;">Usar logo compartido</span>
                </label>
                <div id="editSeccionLogoCompartido" style="display: ${enabled ? 'grid' : 'none'}; gap: 10px;">
                    <button type="button" id="editBtnAgregarLogoCompartido" style="background:#0284c7; color:white; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; font-weight:800; width: 100%;">+ Agregar Logo Compartido</button>
                    ${filas}
                </div>
            </div>
        `;
    } else {
        const enabled = !!(datosPrenda && datosPrenda.logoCompartido && datosPrenda.logoCompartido.enabled);
        logoCompartidoHTML = `
            <div style="margin-bottom: 16px; padding: 12px; border: 2px solid #dbeafe; border-radius: 8px; background: #f8fbff;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom: 10px;">
                    <input type="checkbox" id="editUsarLogoCompartido" ${enabled ? 'checked' : ''} style="width:18px; height:18px; cursor:pointer;" />
                    <span style="font-weight:700; color:#0369a1;">Usar logo compartido</span>
                </label>
                <div id="editSeccionLogoCompartido" style="display: ${enabled ? 'grid' : 'none'}; gap: 10px;">
                    <button type="button" id="editBtnAgregarLogoCompartido" style="background:#0284c7; color:white; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; font-weight:800; width: 100%;">+ Agregar Logo Compartido</button>
                </div>
            </div>
        `;
    }

    // ==============================================
    // Selector de técnicas (editar): permite agregar/quitar técnicas dinámicamente
    // ==============================================
    const tecnicasInicialesNombres = datosPrenda && datosPrenda.tecnicas_imagenes
        ? Object.keys(datosPrenda.tecnicas_imagenes)
        : [];

    const idsIniciales = (Array.isArray(tiposDisponibles) ? tiposDisponibles : [])
        .filter(t => tecnicasInicialesNombres.includes(String(t.nombre)))
        .map(t => Number(t.id))
        .filter(n => !Number.isNaN(n));

    window.tecnicasSeleccionadasEdicionIds = Array.isArray(window.tecnicasSeleccionadasEdicionIds)
        ? window.tecnicasSeleccionadasEdicionIds
        : idsIniciales;

    const selectorTecnicasHTML = `
        <div style="margin-bottom: 16px; padding: 12px; border: 1px solid #e2e8f0; background: #ffffff; border-radius: 8px;">
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 10px;">Técnicas</div>
            <div id="editTecnicasSelector" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;"></div>
            <div id="editTecnicasError" style="display:none; margin-top: 8px; color:#b91c1c; font-weight:800; font-size: 0.85rem;"></div>
        </div>
    `;
    
    prendaDiv.innerHTML = `
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Nombre de la Prenda</label>
            <input type="text" class="nombre_prenda" value="${datosPrenda.nombre_prenda || ''}" placeholder="POLO, CAMISA, PANTALÓN..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;">
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Observaciones</label>
            <textarea class="observaciones" placeholder="Detalles adicionales" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${datosPrenda.observaciones || ''}</textarea>
        </div>
        
        ${logoCompartidoHTML}
        ${selectorTecnicasHTML}
        <div class="edit-tecnicas-dinamicas"></div>
    `;
    
    listaPrendas.appendChild(prendaDiv);
    
    // Render inicial de técnicas dinámicas + listeners
    renderizarBloquesTecnicasEdicion(prendaDiv, datosPrenda);
    agregarEventListenersSelectorTecnicasEdicion(prendaDiv, datosPrenda);

    // Mantener lógica existente de logo compartido (reemplazo/eliminar/toggle) y precargas,
    // pero evitando duplicar listeners (ver guards en agregarEventListenersEdicionPrenda)
    try {
        agregarEventListenersEdicionPrenda(prendaDiv, datosPrenda);
    } catch (_) {}
}

function agregarEventListenersSelectorTecnicasEdicion(prendaDiv, datosPrenda) {
    const root = prendaDiv ? prendaDiv.querySelector('#editTecnicasSelector') : null;
    const err = prendaDiv ? prendaDiv.querySelector('#editTecnicasError') : null;
    if (!root || root.getAttribute('data-bound') === '1') return;
    root.setAttribute('data-bound', '1');

    const setErr = (msg) => {
        if (!err) return;
        if (!msg) {
            err.style.display = 'none';
            err.textContent = '';
            return;
        }
        err.style.display = 'block';
        err.textContent = msg;
    };

    const renderSelector = () => {
        root.innerHTML = '';
        const seleccionadas = new Set(Array.isArray(window.tecnicasSeleccionadasEdicionIds)
            ? window.tecnicasSeleccionadasEdicionIds.map(n => Number(n)).filter(n => !Number.isNaN(n))
            : []);

        (Array.isArray(tiposDisponibles) ? tiposDisponibles : []).forEach((tipo) => {
            if (!tipo || tipo.id === undefined || tipo.id === null) return;
            const id = Number(tipo.id);
            const nombre = String(tipo.nombre || '').trim();
            if (!nombre || Number.isNaN(id)) return;

            const label = document.createElement('label');
            label.style.cssText = 'display:flex; align-items:center; gap:8px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px; cursor:pointer; user-select:none; font-weight: 800; color:#0f172a; background:#f8fafc;';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'edit-tecnica-checkbox';
            cb.value = String(id);
            cb.checked = seleccionadas.has(id);
            cb.style.cssText = 'width: 18px; height: 18px; cursor:pointer;';

            cb.addEventListener('change', () => {
                const ids = Array.from(root.querySelectorAll('input.edit-tecnica-checkbox:checked'))
                    .map(x => parseInt(x.value, 10))
                    .filter(n => !Number.isNaN(n));
                window.tecnicasSeleccionadasEdicionIds = ids;
                if (ids.length > 0) setErr('');
                renderizarBloquesTecnicasEdicion(prendaDiv, datosPrenda);
            });

            const span = document.createElement('span');
            span.textContent = nombre;

            label.appendChild(cb);
            label.appendChild(span);
            root.appendChild(label);
        });
    };

    renderSelector();

    // Validación inicial
    const ids0 = Array.isArray(window.tecnicasSeleccionadasEdicionIds) ? window.tecnicasSeleccionadasEdicionIds : [];
    if (ids0.length === 0) {
        setErr('Debes seleccionar al menos una técnica.');
    }
}

function renderizarBloquesTecnicasEdicion(prendaDiv, datosPrenda) {
    const cont = prendaDiv ? prendaDiv.querySelector('.edit-tecnicas-dinamicas') : null;
    if (!cont || !datosPrenda) return;

    const selectedIds = Array.isArray(window.tecnicasSeleccionadasEdicionIds)
        ? window.tecnicasSeleccionadasEdicionIds.map(n => Number(n)).filter(n => !Number.isNaN(n))
        : [];

    const selectedTipos = (Array.isArray(tiposDisponibles) ? tiposDisponibles : [])
        .filter(t => t && selectedIds.includes(Number(t.id)));

    // Asegurar estructura por técnica (no borrar estado al deseleccionar)
    datosPrenda.tecnicas_imagenes = datosPrenda.tecnicas_imagenes || {};
    selectedTipos.forEach((tipo) => {
        const nombre = String(tipo.nombre || '').trim();
        if (!nombre) return;
        if (!datosPrenda.tecnicas_imagenes[nombre]) {
            datosPrenda.tecnicas_imagenes[nombre] = {
                imagenes_data_urls: [],
                imagenes_files: [],
                tipo_logo: { id: tipo.id, nombre: tipo.nombre, color: tipo.color },
                ubicaciones: []
            };
        }
    });

    const ocultarImagenes = !!(datosPrenda.logoCompartido && datosPrenda.logoCompartido.enabled);

    cont.innerHTML = selectedTipos.map((tipo) => {
        const nombreTecnica = String(tipo.nombre || '').trim();
        if (!nombreTecnica) return '';
        const datosTecnica = datosPrenda.tecnicas_imagenes[nombreTecnica] || {};
        const color = (datosTecnica.tipo_logo && datosTecnica.tipo_logo.color) ? datosTecnica.tipo_logo.color : (tipo.color || '#333');
        const imagenesTecnica = (datosTecnica.imagenes_data_urls || []).map(img => cotizacionLogoImgSrc(img));
        const ubicacionesTecnica = Array.isArray(datosTecnica.ubicaciones) ? datosTecnica.ubicaciones : [];

        return `
            <div class="tecnica-edit-block" data-tecnica="${nombreTecnica}" style="margin-bottom: 18px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background: white;">
                <div style="font-weight: 700; margin-bottom: 10px; color: ${color};">${nombreTecnica}</div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">Ubicaciones (${nombreTecnica})</label>
                    <textarea class="ubicaciones-tecnica" data-tecnica="${nombreTecnica}" rows="2" placeholder="Ej: PECHO, ESPALDA, MANGA" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${ubicacionesTecnica.join(', ')}</textarea>
                </div>

                <div class="tecnica-imagenes-block" style="margin-bottom: 6px; display: ${ocultarImagenes ? 'none' : 'block'};">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: ${color};">Imágenes - ${nombreTecnica}</label>
                    <div class="imagenes-preview" data-tecnica="${nombreTecnica}" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px;">
                        ${imagenesTecnica.map((img, idx) => `
                            <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx}">
                                <img src="${img}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                            </div>
                        `).join('')}
                    </div>
                    <div class="edit-dropzone" data-tecnica="${nombreTecnica}" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 14px; text-align: center; background: #ffffff; cursor: pointer;">
                        <div style="font-size: 1.2rem; margin-bottom: 6px;"></div>
                        <div style="font-weight: 700; color: #334155;">Arrastra imágenes aquí o haz clic</div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">(solo imágenes, máximo 3)</div>
                        <input type="file" class="input-imagenes" data-tecnica="${nombreTecnica}" accept="image/*" multiple style="display:none;" />
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Bind listeners de estas secciones (re-render -> re-bind)
    agregarEventListenersEdicionPrendaTecnicas(prendaDiv, datosPrenda);
}

function agregarEventListenersEdicionPrendaTecnicas(prendaDiv, datosPrenda) {
    if (!prendaDiv || !datosPrenda) return;

    // Ubicaciones
    prendaDiv.querySelectorAll('.ubicaciones-tecnica').forEach((textarea) => {
        if (textarea.getAttribute('data-bound') === '1') return;
        textarea.setAttribute('data-bound', '1');
        textarea.addEventListener('input', () => {
            const tecnica = textarea.getAttribute('data-tecnica');
            if (!tecnica || !datosPrenda.tecnicas_imagenes || !datosPrenda.tecnicas_imagenes[tecnica]) return;
            const ubicaciones = (textarea.value || '')
                .split(',')
                .map(u => u.trim().toUpperCase())
                .filter(u => u.length > 0);
            datosPrenda.tecnicas_imagenes[tecnica].ubicaciones = ubicaciones;
        });
    });

    // Inputs imágenes (solo files)
    prendaDiv.querySelectorAll('.input-imagenes').forEach((input) => {
        if (input.getAttribute('data-bound') === '1') return;
        input.setAttribute('data-bound', '1');

        const nombreTecnica = input.getAttribute('data-tecnica');
        const dropzone = prendaDiv.querySelector(`.edit-dropzone[data-tecnica="${nombreTecnica}"]`);

        if (dropzone && dropzone.getAttribute('data-bound') !== '1') {
            dropzone.setAttribute('data-bound', '1');
            dropzone.addEventListener('click', () => input.click());

            try {
                adjuntarPasteHintEnDropzoneLogo(dropzone);
            } catch (_) {}

            dropzone.addEventListener('dragover', (ev) => {
                ev.preventDefault();
                dropzone.style.background = '#eff6ff';
                dropzone.style.borderColor = '#2563eb';
                try { limpiarPasteHintVisualLogo(dropzone); } catch (_) {}
            });
            dropzone.addEventListener('dragleave', () => {
                dropzone.style.background = '#ffffff';
                dropzone.style.borderColor = '#cbd5e1';
            });
            dropzone.addEventListener('drop', (ev) => {
                ev.preventDefault();
                dropzone.style.background = '#ffffff';
                dropzone.style.borderColor = '#cbd5e1';
                try { limpiarPasteHintVisualLogo(dropzone); } catch (_) {}
                const files = Array.from((ev.dataTransfer && ev.dataTransfer.files) ? ev.dataTransfer.files : []);
                if (files.length > 0) {
                    try { input.value = ''; } catch (_) {}
                    const dt = new DataTransfer();
                    files.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        input.addEventListener('change', (e) => {
            const files = Array.from(e.target.files || []);
            files.forEach((file) => {
                if (!file || !file.type || !file.type.startsWith('image/')) return;
                if (!datosPrenda.tecnicas_imagenes || !datosPrenda.tecnicas_imagenes[nombreTecnica]) return;
                const datosT = datosPrenda.tecnicas_imagenes[nombreTecnica];

                if (!Array.isArray(datosT.imagenes_data_urls)) datosT.imagenes_data_urls = [];
                if (!Array.isArray(datosT.imagenes_files_meta)) datosT.imagenes_files_meta = [];

                const previewUrl = URL.createObjectURL(file);
                datosT.imagenes_data_urls.push({ preview_url: previewUrl });
                datosT.imagenes_files_meta.push({ file, preview_url: previewUrl });
                datosT.imagenes_files = (datosT.imagenes_files_meta || []).map(x => x.file).filter(f => f instanceof File);
                datosT.__touchedImagenes = true;

                renderizarBloquesTecnicasEdicion(prendaDiv, datosPrenda);
            });
            e.target.value = '';
        });
    });

    // Botones eliminar imagen
    prendaDiv.querySelectorAll('.btn-eliminar-imagen').forEach((btn) => {
        if (btn.getAttribute('data-bound') === '1') return;
        btn.setAttribute('data-bound', '1');

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tecnica = btn.getAttribute('data-tecnica');
            const idx = parseInt(btn.getAttribute('data-idx'), 10);
            if (!tecnica || Number.isNaN(idx)) return;
            if (!datosPrenda.tecnicas_imagenes || !datosPrenda.tecnicas_imagenes[tecnica]) return;
            const datosT = datosPrenda.tecnicas_imagenes[tecnica];
            const removed = (Array.isArray(datosT.imagenes_data_urls) ? datosT.imagenes_data_urls : [])[idx];
            if (Array.isArray(datosT.imagenes_data_urls)) {
                datosT.imagenes_data_urls.splice(idx, 1);
            }

            const removedId = removed && typeof removed === 'object' ? (removed.id || null) : null;
            if (removedId) {
                window.tecnicasFotosAEliminar = window.tecnicasFotosAEliminar || [];
                if (!window.tecnicasFotosAEliminar.includes(removedId)) {
                    window.tecnicasFotosAEliminar.push(removedId);
                }
            }

            if (removed && Array.isArray(datosT.imagenes_files_meta)) {
                const removedSrc = cotizacionLogoImgSrc(removed);
                if (typeof removedSrc === 'string' && removedSrc.startsWith('blob:')) {
                    try {
                        URL.revokeObjectURL(removedSrc);
                    } catch (_) {}
                }
                datosT.imagenes_files_meta = datosT.imagenes_files_meta.filter(m => m && cotizacionLogoImgSrc(m) !== removedSrc);
                datosT.imagenes_files = (datosT.imagenes_files_meta || []).map(m => m.file).filter(f => f instanceof File);
            }

            datosT.__touchedImagenes = true;

            renderizarBloquesTecnicasEdicion(prendaDiv, datosPrenda);
        });
    });
}

function agregarEventListenersEdicionPrenda(prendaDiv, datosPrenda) {
    // Ubicaciones por técnica (textarea por técnica)
    prendaDiv.querySelectorAll('.ubicaciones-tecnica').forEach(textarea => {
        if (textarea.getAttribute('data-bound') === '1') return;
        textarea.setAttribute('data-bound', '1');
        textarea.addEventListener('input', (e) => {
            const tecnica = textarea.getAttribute('data-tecnica');
            if (!tecnica || !datosPrenda.tecnicas_imagenes || !datosPrenda.tecnicas_imagenes[tecnica]) return;
            const ubicaciones = textarea.value
                .split(',')
                .map(u => u.trim().toUpperCase())
                .filter(u => u.length > 0);
            datosPrenda.tecnicas_imagenes[tecnica].ubicaciones = ubicaciones;
        });
    });
    
    // Event listeners para imágenes por técnica
    const inputsImagenes = prendaDiv.querySelectorAll('.input-imagenes');
    
    inputsImagenes.forEach(input => {
        if (input.getAttribute('data-bound') === '1') return;
        input.setAttribute('data-bound', '1');
        const nombreTecnica = input.getAttribute('data-tecnica');
        const bloque = input.closest('.tecnica-edit-block');
        const imagenesPreview = bloque ? bloque.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`) : null;
        
        input.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);

            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls) {
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls = [];
                    }

                    if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta) {
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta = [];
                    }
                    if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files) {
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files = [];
                    }

                    const previewUrl = URL.createObjectURL(file);
                    datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls.push({ preview_url: previewUrl });

                    datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta.push({
                        file,
                        preview_url: previewUrl
                    });
                    datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files =
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta
                            .map(x => x.file)
                            .filter(f => f instanceof File);

                    actualizarImagenesTecnica(nombreTecnica);
                }
            });

            e.target.value = '';
        });
        
        function actualizarImagenesTecnica(tecnicaNombre) {
            const datosTecnica = datosPrenda.tecnicas_imagenes[tecnicaNombre];
            const imagenesTecnica = datosTecnica.imagenes_data_urls || [];
            
            const previewContainer = prendaDiv.querySelector(`.imagenes-preview[data-tecnica="${tecnicaNombre}"]`) || imagenesPreview;
            
            if (previewContainer) {
                previewContainer.innerHTML = imagenesTecnica.map((img, idx) => {
                    const src = cotizacionLogoImgSrc(img);
                    return `
                    <div style="position: relative;" class="imagen-item" data-tecnica="${tecnicaNombre}" data-idx="${idx}">
                        <img src="${src}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <button type="button" class="btn-eliminar-imagen" data-tecnica="${tecnicaNombre}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                    </div>
                    `;
                }).join('');
                
                // Agregar listeners a los nuevos botones de eliminar imagen
                previewContainer.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                    if (btn.getAttribute('data-bound') === '1') return;
                    btn.setAttribute('data-bound', '1');
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const tecnica = btn.getAttribute('data-tecnica');
                        const idx = parseInt(btn.getAttribute('data-idx'));
                        const datosT = datosPrenda.tecnicas_imagenes[tecnica];
                        const removed = (datosT.imagenes_data_urls || [])[idx];
                        if (Array.isArray(datosT.imagenes_data_urls)) {
                            datosT.imagenes_data_urls.splice(idx, 1);
                        }

                        // Si es imagen EXISTENTE en BD, trackear su ID para borrado
                        const removedId = removed && typeof removed === 'object' ? (removed.id || null) : null;
                        if (removedId) {
                            window.tecnicasFotosAEliminar = window.tecnicasFotosAEliminar || [];
                            if (!window.tecnicasFotosAEliminar.includes(removedId)) {
                                window.tecnicasFotosAEliminar.push(removedId);
                            }
                        }

                        // Si la imagen eliminada corresponde a un File nuevo, removerlo también
                        if (removed && Array.isArray(datosT.imagenes_files_meta)) {
                            const removedSrc = cotizacionLogoImgSrc(removed);
                            if (typeof removedSrc === 'string' && removedSrc.startsWith('blob:')) {
                                try {
                                    URL.revokeObjectURL(removedSrc);
                                } catch (_) {}
                            }
                            datosT.imagenes_files_meta = datosT.imagenes_files_meta.filter(m => m && cotizacionLogoImgSrc(m) !== removedSrc);
                            datosT.imagenes_files = (datosT.imagenes_files_meta || [])
                                .map(m => m.file)
                                .filter(f => f instanceof File);
                        }
                        actualizarImagenesTecnica(tecnica);
                    });
                });
            }
        }
        
        // Agregar listeners iniciales para eliminar imágenes
        const imagenesPreviewContainer = bloque ? bloque.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`) : null;
        if (imagenesPreviewContainer) {
            imagenesPreviewContainer.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                if (btn.getAttribute('data-bound') === '1') return;
                btn.setAttribute('data-bound', '1');
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tecnica = btn.getAttribute('data-tecnica');
                    const idx = parseInt(btn.getAttribute('data-idx'));
                    if (datosPrenda.tecnicas_imagenes && datosPrenda.tecnicas_imagenes[tecnica]) {
                        const datosT = datosPrenda.tecnicas_imagenes[tecnica];
                        const removed = (datosT.imagenes_data_urls || [])[idx];
                        if (Array.isArray(datosT.imagenes_data_urls)) {
                            datosT.imagenes_data_urls.splice(idx, 1);
                        }
                        const removedId = removed && typeof removed === 'object' ? (removed.id || null) : null;
                        if (removedId) {
                            window.tecnicasFotosAEliminar = window.tecnicasFotosAEliminar || [];
                            if (!window.tecnicasFotosAEliminar.includes(removedId)) {
                                window.tecnicasFotosAEliminar.push(removedId);
                            }
                        }
                        if (removed && Array.isArray(datosT.imagenes_files_meta)) {
                            const removedSrc = cotizacionLogoImgSrc(removed);
                            if (typeof removedSrc === 'string' && removedSrc.startsWith('blob:')) {
                                try {
                                    URL.revokeObjectURL(removedSrc);
                                } catch (_) {}
                            }
                            datosT.imagenes_files_meta = datosT.imagenes_files_meta.filter(m => m && cotizacionLogoImgSrc(m) !== removedSrc);
                            datosT.imagenes_files = (datosT.imagenes_files_meta || [])
                                .map(m => m.file)
                                .filter(f => f instanceof File);
                        }
                    }
                    actualizarImagenesTecnica(tecnica);
                });
            });
        }
    });

    // Toggle logo compartido (oculta/muestra secciones de imágenes por técnica)
    const chkLogoCompartido = prendaDiv.querySelector('#editUsarLogoCompartido');
    const seccionLogoCompartido = prendaDiv.querySelector('#editSeccionLogoCompartido');
    if (chkLogoCompartido && datosPrenda && datosPrenda.logoCompartido) {
        chkLogoCompartido.addEventListener('change', () => {
            datosPrenda.logoCompartido.enabled = chkLogoCompartido.checked;
            if (seccionLogoCompartido) {
                seccionLogoCompartido.style.display = chkLogoCompartido.checked ? 'grid' : 'none';
            }
            prendaDiv.querySelectorAll('.tecnica-imagenes-block').forEach(div => {
                div.style.display = chkLogoCompartido.checked ? 'none' : 'block';
            });
        });
    }

    // Agregar nuevo logo compartido desde edición
    const btnAgregarLogoCompartidoEd = prendaDiv.querySelector('#editBtnAgregarLogoCompartido');
    if (btnAgregarLogoCompartidoEd && btnAgregarLogoCompartidoEd.getAttribute('data-bound') !== '1') {
        btnAgregarLogoCompartidoEd.setAttribute('data-bound', '1');
        btnAgregarLogoCompartidoEd.addEventListener('click', (ev) => {
            ev.preventDefault();
            if (typeof abrirModalLogoCompartido !== 'function') {
                return;
            }

            // Armar lista de técnicas seleccionadas actuales (por tiposDisponibles)
            const selectedIds = Array.isArray(window.tecnicasSeleccionadasEdicionIds)
                ? window.tecnicasSeleccionadasEdicionIds.map(n => Number(n)).filter(n => !Number.isNaN(n))
                : [];
            const tecnicasSel = (Array.isArray(tiposDisponibles) ? tiposDisponibles : [])
                .filter(t => t && selectedIds.includes(Number(t.id)))
                .map(t => ({ id: t.id, nombre: t.nombre, color: t.color }));

            // Contexto de logos compartidos (mismo formato que creación)
            datosPrenda.logoCompartido = datosPrenda.logoCompartido || { enabled: true, items: {}, files: {} };
            datosPrenda.logoCompartido.items = datosPrenda.logoCompartido.items || {};
            datosPrenda.logoCompartido.files = datosPrenda.logoCompartido.files || {};
            datosPrenda.logoCompartido.enabled = true;

            const handler = (evt) => {
                try {
                    const d = evt && evt.detail ? evt.detail : null;
                    if (!d || !d.clave || !(d.file instanceof File)) return;

                    datosPrenda.logoCompartido.files[d.clave] = d.file;
                    const tecnicasCompartidas = Array.isArray(d.tecnicas) ? d.tecnicas : d.clave.split('-').map(t => t.trim()).filter(Boolean);

                    const prevItem = datosPrenda.logoCompartido.items[d.clave];
                    const prevUrl = prevItem && typeof prevItem.previewUrl === 'string' && prevItem.previewUrl.startsWith('blob:')
                        ? prevItem.previewUrl
                        : null;
                    if (prevUrl) {
                        try {
                            URL.revokeObjectURL(prevUrl);
                        } catch (_) {}
                    }
                    const url = URL.createObjectURL(d.file);
                    datosPrenda.logoCompartido.items[d.clave] = {
                        tecnicasCompartidas,
                        previewUrl: url
                    };

                    const chk = prendaDiv.querySelector('#editUsarLogoCompartido');
                    const sec = prendaDiv.querySelector('#editSeccionLogoCompartido');
                    if (chk) chk.checked = true;
                    if (sec) sec.style.display = 'grid';
                    datosPrenda.logoCompartido.enabled = true;

                    renderizarLogosCompartidosEdicion(prendaDiv, datosPrenda);
                } catch (_) {}
            };

            try {
                document.addEventListener('logoCompartido:guardado', handler, { once: true });
            } catch (_) {
                // fallback
                document.addEventListener('logoCompartido:guardado', handler);
            }

            // Pasar el DESTINO correcto (files) para que el modal guarde el archivo
            abrirModalLogoCompartido(tecnicasSel, datosPrenda.logoCompartido.files);
        });
    }

    // Permitir Ctrl+V en el input de reemplazo de cada logo compartido
    prendaDiv.querySelectorAll('.edit-logo-compartido-inputwrap').forEach((wrap) => {
        if (!wrap || wrap.getAttribute('data-paste-bound') === '1') return;
        wrap.setAttribute('data-paste-bound', '1');
        try {
            adjuntarPasteHintEnDropzoneLogo(wrap);
        } catch (_) {}
    });

    // Reemplazo de logo compartido
    prendaDiv.querySelectorAll('.edit-input-logo-compartido').forEach(input => {
        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            if (!file) return;
            const clave = input.getAttribute('data-logo-clave');
            if (!clave || !datosPrenda.logoCompartido) return;
            datosPrenda.logoCompartido.files[clave] = file;
            const prevItem = datosPrenda.logoCompartido.items[clave];
            const prevUrl = prevItem && typeof prevItem.previewUrl === 'string' && prevItem.previewUrl.startsWith('blob:')
                ? prevItem.previewUrl
                : null;
            if (prevUrl) {
                try {
                    URL.revokeObjectURL(prevUrl);
                } catch (_) {}
            }
            const url = URL.createObjectURL(file);
            if (!datosPrenda.logoCompartido.items[clave]) {
                datosPrenda.logoCompartido.items[clave] = { tecnicasCompartidas: clave.split('-').map(t => t.trim()).filter(Boolean), previewUrl: url };
            } else {
                datosPrenda.logoCompartido.items[clave].previewUrl = url;
            }
            const preview = prendaDiv.querySelector(`.edit-logo-compartido-preview[data-logo-clave="${clave}"]`);
            if (preview) {
                preview.innerHTML = `<img src="${url}" style="width: 100%; height: 100%; object-fit: cover;" />`;
            }
        });
    });

    prendaDiv.querySelectorAll('.edit-logo-compartido-del').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const clave = btn.getAttribute('data-logo-clave');
            if (!clave || !datosPrenda.logoCompartido) return;
            if (datosPrenda.logoCompartido.items) {
                delete datosPrenda.logoCompartido.items[clave];
            }
            if (datosPrenda.logoCompartido.files) {
                delete datosPrenda.logoCompartido.files[clave];
            }

            const row = prendaDiv.querySelector(`.edit-logo-compartido-row[data-logo-clave="${clave}"]`);
            if (row && row.parentNode) {
                row.parentNode.removeChild(row);
            }

            if (Object.keys(datosPrenda.logoCompartido.items || {}).length === 0) {
                datosPrenda.logoCompartido.enabled = false;
                const chk = prendaDiv.querySelector('#editUsarLogoCompartido');
                const sec = prendaDiv.querySelector('#editSeccionLogoCompartido');
                if (chk) chk.checked = false;
                if (sec) sec.style.display = 'none';
                prendaDiv.querySelectorAll('.tecnica-imagenes-block').forEach(div => {
                    div.style.display = 'block';
                });
            }
        });
    });

    // Precargar previews desde imagenes_files si imagenes_data_urls está vacío (caso 1 técnica / imágenes como File)
    if (datosPrenda && datosPrenda.tecnicas_imagenes) {
        Object.entries(datosPrenda.tecnicas_imagenes).forEach(([nombreTecnica, datosTecnica]) => {
            if (!datosTecnica) return;
            const tieneDataUrls = Array.isArray(datosTecnica.imagenes_data_urls) && datosTecnica.imagenes_data_urls.length > 0;
            const files = Array.isArray(datosTecnica.imagenes_files) ? datosTecnica.imagenes_files : [];
            if (tieneDataUrls || files.length === 0) return;

            // Inicializar array
            datosTecnica.imagenes_data_urls = [];

            files.forEach((file) => {
                if (!(file instanceof File)) return;
                const previewUrl = URL.createObjectURL(file);
                const entry = { preview_url: previewUrl };
                datosTecnica.imagenes_data_urls.push(entry);
                const preview = prendaDiv.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`);
                if (preview) {
                    preview.innerHTML = datosTecnica.imagenes_data_urls.map((img, idx) => {
                        const src = cotizacionLogoImgSrc(img);
                        return `
                            <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx}">
                                <img src="${src}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                            </div>
                        `;
                    }).join('');

                    preview.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                        btn.addEventListener('click', (clickE) => {
                            clickE.preventDefault();
                            const idx = parseInt(btn.getAttribute('data-idx'), 10);
                            const removed = datosTecnica.imagenes_data_urls[idx];
                            const rs = cotizacionLogoImgSrc(removed);
                            if (typeof rs === 'string' && rs.startsWith('blob:')) {
                                try {
                                    URL.revokeObjectURL(rs);
                                } catch (_) {}
                            }
                            datosTecnica.imagenes_data_urls.splice(idx, 1);
                            preview.innerHTML = datosTecnica.imagenes_data_urls.map((img2, idx2) => {
                                const src2 = cotizacionLogoImgSrc(img2);
                                return `
                                    <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx2}">
                                        <img src="${src2}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                        <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx2}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                                    </div>
                                `;
                            }).join('');
                        });
                    });
                }
            });
        });
    }

    // No hay ubicaciones compartidas; las textareas ya quedan vinculadas.
}

function eliminarTecnicaDelGrupo(tecnicaIndices) {
    // tecnicaIndices es un array de índices de técnicas a eliminar
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnica(s)?',
        text: 'Se eliminarán las técnicas seleccionadas',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        // Eliminar en orden inverso para no afectar los índices
        const indicesSorted = Array.isArray(tecnicaIndices) ? tecnicaIndices.sort((a, b) => b - a) : [tecnicaIndices];
        indicesSorted.forEach(idx => {
            if (tecnicasAgregadas[idx]) {
                tecnicasAgregadas.splice(idx, 1);
            }
        });
        

        renderizarTecnicasAgregadas();
    });
}

async function eliminarTecnica(tecnicaIndex) {
    const confirmacion = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnica?',
        text: 'Esta acción no se puede deshacer',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!confirmacion.isConfirmed) return;
    
    // Si es una técnica temporal (sin ID de BD), eliminar del array
    if (typeof tecnicaIndex === 'number') {
        eliminarTecnicaTemporal(tecnicaIndex);
        return;
    }
    
    // Si es una técnica guardada en BD, hacer DELETE
    try {
        const response = await fetch(
            `/api/logo-cotizacion-tecnicas/${tecnicaIndex}`,
            { 
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        );
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminada',
                text: 'Técnica eliminada correctamente'
            });
            cargarTecnicasAgregadas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {

    }
}

// =========================================================
// 7.5 FUNCIONES PARA TABLA COLOR, TELA Y REFERENCIA (LOGO)
// =========================================================

function agregarFilaTelaLogo(boton) {
    const prendasItem = boton.closest('.prenda-item');
    const tbody = prendasItem.querySelector('.telas-tbody-logo');
    
    if (!tbody) return;
    
    const numFila = tbody.querySelectorAll('tr').length + 1;
    
    const fila = document.createElement('tr');
    fila.className = 'fila-tela-logo';
    fila.style.cssText = 'background: white;';
    fila.innerHTML = `
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-color-logo" placeholder="Ej: Azul, Rojo..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-tela-logo" placeholder="Ej: Algodón, Poliéster..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-referencia-logo" placeholder="Ej: REF-001..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <div class="dropzone-tela-logo" style="border: 1px dashed #ddd; padding: 6px; border-radius: 3px; background: #fafafa; cursor: pointer; font-size: 0.75rem; color: #999; transition: all 0.2s;" title="Haz clic o arrastra una imagen">
                 Imagen
            </div>
            <input type="file" class="input-file-tela-logo" accept="image/*" style="display: none;">
            <div class="preview-tela-logo" style="margin-top: 4px; display: none; position: relative; width: 60px; height: 60px; border-radius: 3px; overflow: hidden; border: 1px solid #ddd;">
                <img style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" class="btn-eliminar-tela-logo" onclick="this.closest('.fila-tela-logo').querySelector('.preview-tela-logo').style.display='none'; this.closest('.fila-tela-logo').querySelector('.input-file-tela-logo').value='';" style="position: absolute; top: -6px; right: -6px; background: rgba(0,0,0,0.6); color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 0.8rem; display: none;">×</button>
            </div>
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar">
                ✕
            </button>
        </td>
    `;
    
    tbody.appendChild(fila);
    
    // Funcionalidad de imagen
    const dropzone = fila.querySelector('.dropzone-tela-logo');
    const input = fila.querySelector('.input-file-tela-logo');
    const preview = fila.querySelector('.preview-tela-logo');
    const previewImg = preview.querySelector('img');
    const previewBtn = preview.querySelector('.btn-eliminar-tela-logo');
    
    // Click en dropzone
    dropzone.addEventListener('click', () => input.click());
    
    // Drag over
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#1e40af';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    // Drop
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        if (e.dataTransfer.files[0] && e.dataTransfer.files[0].type.startsWith('image/')) {
            input.files = e.dataTransfer.files;
            manejarArchivoTelaLogo();
        }
    });
    
    // Cambio en input
    input.addEventListener('change', manejarArchivoTelaLogo);
    
    function manejarArchivoTelaLogo() {
        if (input.files[0]) {
            try {
                if (previewImg.__telaLogoBlobUrl) {
                    URL.revokeObjectURL(previewImg.__telaLogoBlobUrl);
                }
            } catch (_) {}
            previewImg.__telaLogoBlobUrl = URL.createObjectURL(input.files[0]);
            previewImg.src = previewImg.__telaLogoBlobUrl;
            preview.style.display = 'block';
            previewBtn.style.display = 'block';
            dropzone.style.display = 'none';
        }
    }
}

function eliminarFilaTelaLogo(boton) {
    boton.closest('.fila-tela-logo').remove();
}

// =========================================================
// 7.6 FUNCIÓN AGREGAR FILA TELA PARA MÚLTIPLES TÉCNICAS
// =========================================================

function agregarFilaTelaMultiTecnica(tbody) {
    if (!tbody) return;
    
    const numFila = tbody.querySelectorAll('tr').length + 1;
    
    const fila = document.createElement('tr');
    fila.className = 'fila-tela-multi';
    fila.style.cssText = 'border-bottom: 1px solid #eee;';
    fila.innerHTML = `
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-color-multi" placeholder="Ej: Azul, Rojo..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-tela-multi" placeholder="Ej: Algodón, Poliéster..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-referencia-multi" placeholder="Ej: REF-001..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <div class="dropzone-tela-multi" style="border: 1px dashed #ddd; padding: 6px; border-radius: 3px; background: #fafafa; cursor: pointer; font-size: 0.75rem; color: #999; transition: all 0.2s;" title="Haz clic o arrastra una imagen">
                 Imagen
            </div>
            <input type="file" class="input-file-tela-multi" accept="image/*" style="display: none;">
            <div class="preview-tela-multi" style="margin-top: 4px; display: none; position: relative; width: 60px; height: 60px; border-radius: 3px; overflow: hidden; border: 1px solid #ddd;">
                <img style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" class="btn-eliminar-tela-multi" onclick="this.closest('.fila-tela-multi').querySelector('.preview-tela-multi').style.display='none'; this.closest('.fila-tela-multi').querySelector('.input-file-tela-multi').value='';" style="position: absolute; top: -6px; right: -6px; background: rgba(0,0,0,0.6); color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 0.8rem; display: none;">×</button>
            </div>
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar">
                ✕
            </button>
        </td>
    `;
    
    tbody.appendChild(fila);
    
    // Funcionalidad de imagen
    const dropzone = fila.querySelector('.dropzone-tela-multi');
    const input = fila.querySelector('.input-file-tela-multi');
    const preview = fila.querySelector('.preview-tela-multi');
    const previewImg = preview.querySelector('img');
    const previewBtn = preview.querySelector('.btn-eliminar-tela-multi');
    
    // Click en dropzone
    dropzone.addEventListener('click', () => input.click());
    
    // Drag over
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#1e40af';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    // Drop
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        if (e.dataTransfer.files[0] && e.dataTransfer.files[0].type.startsWith('image/')) {
            input.files = e.dataTransfer.files;
            manejarArchivoTelaMulti();
        }
    });
    
    // Cambio en input
    input.addEventListener('change', manejarArchivoTelaMulti);
    
    function manejarArchivoTelaMulti() {
        if (input.files[0]) {
            try {
                if (previewImg.__telaMultiBlobUrl) {
                    URL.revokeObjectURL(previewImg.__telaMultiBlobUrl);
                }
            } catch (_) {}
            previewImg.__telaMultiBlobUrl = URL.createObjectURL(input.files[0]);
            previewImg.src = previewImg.__telaMultiBlobUrl;
            preview.style.display = 'block';
            previewBtn.style.display = 'block';
            dropzone.style.display = 'none';
        }
    }
}

// =========================================================
// 8. CERRAR MODAL
// =========================================================

function cerrarModalAgregarTecnica() {
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'none';
    }

    // Limpiar estado global para evitar que quede pegado el modo combinadas
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;
    
    // Resetear modo edición al cerrar
    window.modoEdicionPrenda = false;
    window.datosPrendaEdicion = null;
    window.tecnicasInfoEdicion = null;
    window.tecnicaIndicesEdicion = null;
    window.prendaNombreOriginalEdicion = null;
    window.tecnicasSeleccionadasEdicionIds = null;
    window.logosCompartidosCotizacionIndividual = {};

    // Liberar lock de guardado
    window.__guardandoTecnica = false;
}

// =========================================================
// 9. INICIALIZACIÓN
// =========================================================

document.addEventListener('DOMContentLoaded', async function() {
    // Obtener ID de cotización del formulario o URL
    const urlParams = new URLSearchParams(window.location.search);
    logoCotizacionId = document.getElementById('logoCotizacionId')?.value 
                    || urlParams.get('editar')
                    || null;
    
    // Cargar datos iniciales
    await cargarTiposDisponibles();
    
    if (logoCotizacionId) {
        await cargarTecnicasAgregadas();
    }
});

// =========================================================
// EXPORTAR PARA USO EN OTROS SCRIPTS
// =========================================================

window.LogoCotizacion = {
    cargarTiposDisponibles,
    abrirModalAgregarTecnica,
    agregarFilaPrenda,
    guardarTecnica,
    cerrarModalAgregarTecnica,
    cargarTecnicasAgregadas,
    eliminarTecnica,
    abrirModalValidacionTecnica,
    cerrarModalValidacionTecnica,
    guardarTecnicasEnBD,
    agregarFilaTelaLogo,
    eliminarFilaTelaLogo,
    agregarFilaTelaMultiTecnica
};

// =========================================================
// FUNCIONES DE VALIDACIÓN - MODAL
// =========================================================

function abrirModalValidacionTecnica() {

    
    const modal = document.getElementById('modalValidacionTecnica');
    


    
    if (modal) {

        modal.style.display = 'flex';

    } else {

    }
}

function cerrarModalValidacionTecnica() {

    const modal = document.getElementById('modalValidacionTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}

//  EXPORTAR PARA ACCESO GLOBAL EN create.blade.php
window.tecnicasAgregadas = tecnicasAgregadas;

// =====================================================
// FUNCIONES DE ESPECIFICACIONES
// =====================================================

// FUNCIÓN PARA ABRIR MODAL ESPECIFICACIONES
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    const especificacionesGuardadas = document.getElementById('especificaciones').value;

    // Si hay especificaciones guardadas, cargarlas en los checkboxes y observaciones
    if (especificacionesGuardadas && especificacionesGuardadas !== '{}' && especificacionesGuardadas !== '[]' && especificacionesGuardadas !== '') {
        try {
            let datos = JSON.parse(especificacionesGuardadas);
            // Soportar doble serialización: a veces viene como string con JSON adentro
            if (typeof datos === 'string') {
                const inner = datos.trim();
                if (inner.startsWith('{') || inner.startsWith('[')) {
                    datos = JSON.parse(inner);
                }
            }
            
            // Si tiene estructura de array (forma_pago, disponibilidad, etc)
            if (datos.forma_pago || datos.disponibilidad || datos.regimen) {
                // Procesar FORMA_PAGO
                if (datos.forma_pago && Array.isArray(datos.forma_pago)) {
                    datos.forma_pago.forEach((pago) => {
                        let valorNormalizado = pago.valor.toLowerCase();
                        if (valorNormalizado === 'crédito' || valorNormalizado === 'credito') {
                            valorNormalizado = 'credito';
                        }
                        
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        let checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (pago.observacion) {
                                let obsName;
                                if (valorNormalizado === 'contado') {
                                    obsName = 'tabla_orden[pago_contado_obs]';
                                } else if (valorNormalizado === 'credito') {
                                    obsName = 'tabla_orden[pago_credito_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = pago.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar DISPONIBILIDAD
                if (datos.disponibilidad && Array.isArray(datos.disponibilidad)) {
                    datos.disponibilidad.forEach((disp) => {
                        const valorNormalizado = disp.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (disp.observacion) {
                                const obsName = `tabla_orden[${valorNormalizado}_obs]`;
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = disp.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar RÉGIMEN
                if (datos.regimen && Array.isArray(datos.regimen)) {
                    datos.regimen.forEach((reg) => {
                        const valorNormalizado = reg.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (reg.observacion) {
                                let obsName;
                                if (valorNormalizado === 'común' || valorNormalizado === 'comun') {
                                    obsName = 'tabla_orden[regimen_comun_obs]';
                                } else if (valorNormalizado === 'simplificado') {
                                    obsName = 'tabla_orden[regimen_simp_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = reg.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar SE HA VENDIDO
                if (datos.se_ha_vendido && Array.isArray(datos.se_ha_vendido)) {
                    const tbodyVendido = document.querySelector('#tbody_vendido');
                    if (tbodyVendido) {
                        datos.se_ha_vendido.forEach((vendido) => {
                            const firstRow = tbodyVendido.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="vendido_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="vendido"]');
                                const obsInput = firstRow.querySelector('input[name*="vendido_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = vendido.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = vendido.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar ÚLTIMA VENTA
                if (datos.ultima_venta && Array.isArray(datos.ultima_venta)) {
                    const tbodyUltimaVenta = document.querySelector('#tbody_ultima_venta');
                    if (tbodyUltimaVenta) {
                        datos.ultima_venta.forEach((ultimaVenta) => {
                            const firstRow = tbodyUltimaVenta.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="ultima_venta_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="ultima_venta"]');
                                const obsInput = firstRow.querySelector('input[name*="ultima_venta_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = ultimaVenta.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = ultimaVenta.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar FLETE
                if (datos.flete && Array.isArray(datos.flete)) {
                    const tbodyFlete = document.querySelector('#tbody_flete');
                    if (tbodyFlete) {
                        datos.flete.forEach((flete) => {
                            const firstRow = tbodyFlete.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="flete_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="flete"]');
                                const obsInput = firstRow.querySelector('input[name*="flete_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = flete.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = flete.observacion || '';
                                }
                            }
                        });
                    }
                }
            }
        } catch (e) {
            console.log('Error al cargar especificaciones:', e);
        }
    } else {
        // Limpiar todos los checkboxes si no hay especificaciones guardadas
        document.querySelectorAll('[name^="tabla_orden"]').forEach((element) => {
            if (element.type === 'checkbox') {
                element.checked = false;
            } else if (element.type === 'text') {
                element.value = '';
            }
        });
    }
    
    if (modal) {
        modal.style.display = 'flex';
    }
}

// FUNCIÓN PARA CERRAR MODAL ESPECIFICACIONES
function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) {
        modal.style.display = 'none';
    }
}

// FUNCIÓN PARA GUARDAR ESPECIFICACIONES
function guardarEspecificacionesReflectivo() {
    // Estructura final en formato cotizaciones.especificaciones
    const especificaciones = {
        forma_pago: [],
        disponibilidad: [],
        regimen: [],
        se_ha_vendido: [],
        ultima_venta: [],
        flete: []
    };
    
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) {
        return;
    }
    
    // PROCESAR FORMA_PAGO
    const formaPagoCheckboxes = [
        { checkbox: 'contado', label: 'Contado', obsField: 'pago_contado_obs' },
        { checkbox: 'credito', label: 'Crédito', obsField: 'pago_credito_obs' }
    ];
    
    formaPagoCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.forma_pago.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR DISPONIBILIDAD
    const disponibilidadCheckboxes = [
        { checkbox: 'bodega', label: 'Bodega', obsField: 'bodega_obs' },
        { checkbox: 'cucuta', label: 'Cúcuta', obsField: 'cucuta_obs' },
        { checkbox: 'lafayette', label: 'Lafayette', obsField: 'lafayette_obs' },
        { checkbox: 'fabrica', label: 'Fábrica', obsField: 'fabrica_obs' }
    ];
    
    disponibilidadCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.disponibilidad.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR RÉGIMEN
    const regimenCheckboxes = [
        { checkbox: 'comun', label: 'Común', obsField: 'regimen_comun_obs' },
        { checkbox: 'simplificado', label: 'Simplificado', obsField: 'regimen_simp_obs' }
    ];
    
    regimenCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.regimen.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR SE HA VENDIDO
    const tbodySeHaVendido = modal.querySelector('#tbody_vendido');
    if (tbodySeHaVendido) {
        const rows = tbodySeHaVendido.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[vendido_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[vendido]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[vendido_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.se_ha_vendido.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR ÚLTIMA VENTA
    const tbodyUltimaVenta = modal.querySelector('#tbody_ultima_venta');
    if (tbodyUltimaVenta) {
        const rows = tbodyUltimaVenta.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[ultima_venta_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[ultima_venta]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[ultima_venta_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.ultima_venta.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR FLETE
    const tbodyFlete = modal.querySelector('#tbody_flete');
    if (tbodyFlete) {
        const rows = tbodyFlete.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[flete_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[flete]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[flete_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.flete.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // Convertir a JSON string y guardar en campo oculto
    const especificacionesJSON = JSON.stringify(especificaciones);
    document.getElementById('especificaciones').value = especificacionesJSON;

    cerrarModalEspecificaciones();
}

// FUNCIÓN PARA AGREGAR FILA DE ESPECIFICACIÓN
function agregarFilaEspecificacion(seccion) {
    // Función auxiliar, puede ampliarse según necesidad
}

