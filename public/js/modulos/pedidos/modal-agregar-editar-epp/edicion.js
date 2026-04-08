/**
 * Funcion exclusiva para editar EPP en vista de nuevo pedido
 * Evita conflictos con el sistema de cotizacion
 */
function abrirModalEditarEPPNuevo(epp) {
    console.log('[abrirModalEditarEPPNuevo] Iniciando edicion para:', epp);
    console.log('[abrirModalEditarEPPNuevo] EPP recibido:', JSON.stringify(epp, null, 2));
    
    // Abrir modal
    const modal = document.getElementById('modalAgregarEPP');
    console.log('[abrirModalEditarEPPNuevo] Modal encontrado:', !!modal);
    
    if (!modal) {
        console.error('[abrirModalEditarEPPNuevo] Modal no encontrado');
        return;
    }
    
    // Resetear modal primero
    console.log('[abrirModalEditarEPPNuevo] Resetear modal...');
    resetearModalAgregarEPP();
    
    // Modo edicion
    globalThis.__EPP_MODO_EDICION__ = true;
    globalThis.__EPP_EDICION_ID__ = epp.epp_id || epp.id;
    console.log('[abrirModalEditarEPPNuevo] Modo edicion establecido:', {
        '__EPP_MODO_EDICION__': globalThis.__EPP_MODO_EDICION__,
        '__EPP_EDICION_ID__': globalThis.__EPP_EDICION_ID__
    });
    
    // Cambiar titulo del modal
    const titulo = modal.querySelector('.modal-header h3');
    console.log('[abrirModalEditarEPPNuevo] titulo encontrado:', !!titulo);
    
    if (titulo) {
        titulo.textContent = 'Editar EPP';
        console.log('[abrirModalEditarEPPNuevo] titulo cambiado a: Editar EPP');
    }
    
    // Ocultar seccion de "EPP Agregados" en modo EDICION
    const listaEPPAgregados = document.getElementById('listaEPPAgregados');
    console.log('[abrirModalEditarEPPNuevo] Lista EPP Agregados encontrada:', !!listaEPPAgregados);
    
    if (listaEPPAgregados) {
        listaEPPAgregados.style.display = 'none';
        console.log('[abrirModalEditarEPPNuevo] Lista EPP Agregados oculta');
    }
    
    // Ocultar buscador en modo EDICION
    const buscadorSection = document.getElementById('buscadorEPPSection');
    if (buscadorSection) {
        buscadorSection.style.display = 'none';
        console.log('[abrirModalEditarEPPNuevo] Buscador ocultado en modo EDICION');
    }
    const formularioCrearEPPEl = document.getElementById('formularioCrearEPP');
    if (formularioCrearEPPEl) {
        formularioCrearEPPEl.style.display = 'none';
    }
    
    // Mostrar seccion de fotos
    const seccionFotosEPPEl = document.getElementById('seccionFotosEPP');
    if (seccionFotosEPPEl) {
        seccionFotosEPPEl.style.display = 'block';
    }
    
    // Cargar datos del EPP
    mostrarProductoEPP({
        id: epp.epp_id || epp.id,
        nombre_completo: epp.nombre_epp || epp.nombre,
        nombre: epp.nombre_epp || epp.nombre,
        imagen: epp.imagen || '',
        tallas: epp.tallas || []
    });
    
    // Cargar cantidad y observaciones
    const cantidadInput = document.getElementById('cantidadEPP');
    const observacionesInput = document.getElementById('observacionesEPP');
    
    if (cantidadInput) {
        cantidadInput.value = epp.cantidad || 1;
    }
    if (observacionesInput) {
        observacionesInput.value = epp.observaciones || '-';
    }
    
    // Establecer variable eppEnEdicion para que guardarEdicionEPP funcione
    globalThis.eppEnEdicion = epp;
    console.log(' [editarEPPAgregado - cotizacion] globalThis.eppEnEdicion asignado:', epp);
    console.log('[abrirModalEditarEPPNuevo] eppEnEdicion establecido:', epp);
    
    // Cargar imagenes si existen
    console.log('[abrirModalEditarEPPNuevo] Verificando imagenes del EPP:', {
        tieneImagenes: !!(epp.imagenes && Array.isArray(epp.imagenes)),
        cantidadImagenes: epp.imagenes?.length || 0,
        imagenes: epp.imagenes
    });
    
    // Limpiar contenedor de imagenes antes de cargar nuevas en modo EDICION
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        // Limpiar todas las imagenes existentes excepto el mensaje inicial
        const imagenesExistentes = contenedor.querySelectorAll('.foto-epp-item');
        imagenesExistentes.forEach(img => img.remove());
        
        // Restaurar mensaje inicial
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        
        console.log('[abrirModalEditarEPPNuevo] Contenedor de imagenes limpiado');
    }
    
    if (epp.imagenes && Array.isArray(epp.imagenes)) {
        globalThis.fotosEPP = [];
        console.log('[abrirModalEditarEPPNuevo] Iniciando carga de imagenes...');
        
        epp.imagenes.forEach((img, index) => {
            console.log(`[abrirModalEditarEPPNuevo] Procesando imagen ${index + 1}:`, img);
            
            // Convertir imagenes existentes al formato esperado
            const imagenObj = {
                id: img.id || Date.now(),
                previewUrl: img.previewUrl || img.ruta_web || img.url || img.base64, // Priorizar blob URL
                nombre: img.nombre || 'imagen.jpg',
                file: null, // No hay archivo original en EDICION
                extension: (img.nombre || '').split('.').pop().toLowerCase() || 'jpg',
                pedido_epp_id: epp.pedido_epp_id || null,
                ruta_original: img.ruta_original || null,
                ruta_webp: img.ruta_webp || null,
                principal: img.principal || 0,
                orden: img.orden || 0
            };
            
            console.log(`[abrirModalEditarEPPNuevo] ImagenObj creado:`, imagenObj);
            
            // Verificar si la URL es valida antes de agregar
            if (imagenObj.previewUrl) {
                // Permitir imagenes blob URLs y URLs que no sean temporales
                if (imagenObj.previewUrl.startsWith('blob:') || !imagenObj.previewUrl.includes('temp/epp/')) {
                    globalThis.fotosEPP.push(imagenObj);
                    console.log(`[abrirModalEditarEPPNuevo] Imagen agregada a globalThis.fotosEPP: ${imagenObj.previewUrl}`);
                    console.log(`[abrirModalEditarEPPNuevo] Total imagenes en globalThis.fotosEPP: ${globalThis.fotosEPP.length}`);
                    
                    mostrarVistaPreviaFoto(imagenObj);
                    console.log(`[abrirModalEditarEPPNuevo] mostrarVistaPreviaFoto llamado para imagen ${index + 1}`);
                } else if (imagenObj.previewUrl.includes('temp/epp/')) {
                    // Para imagenes temporales, mostrar warning y skip
                    console.warn('[abrirModalEditarEPPNuevo] Imagen temporal no disponible:', imagenObj.previewUrl);
                }
            } else {
                console.warn(`[abrirModalEditarEPPNuevo] Imagen sin previewUrl:`, img);
            }
        });
        
        console.log(`[abrirModalEditarEPPNuevo] Proceso de imagenes completado. Total en globalThis.fotosEPP: ${globalThis.fotosEPP.length}`);
    } else {
        console.log('[abrirModalEditarEPPNuevo] No hay imagenes para cargar');
    }
    
    // Ocultar botones de agregar/finalizar
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    
    if (btnAgregar) btnAgregar.style.display = 'none';
    if (btnFinalizar) btnFinalizar.style.display = 'none';
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'block';
        btnGuardarCambios.disabled = false; // Habilitar boton en modo EDICION
        console.log('[abrirModalEditarEPPNuevo] boton Guardar Cambios habilitado');
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    console.log('[abrirModalEditarEPPNuevo] Modal abierto para EDICION');
}
