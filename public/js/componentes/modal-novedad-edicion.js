/**
 * Modal Novedad Edición - Componente Reutilizable
 * Maneja modales para registrar novedades antes de actualizar una prenda existente
 */



class ModalNovedadEdicion {
    constructor() {
        this.pedidoId = null;
        this.prendaData = null;
        this.prendaIndex = null;
        this.zIndexMaximoForzado = 999999;
        
        // Inicializar arrays separados por flujo (NO afectarse mutuamente)
        if (!window.telasCreacion) {
            window.telasCreacion = [];  // Para flujo de CREACIÓN
        }
        if (!window.telasEdicion) {
            window.telasEdicion = [];   // Para flujo de EDICIÓN
        }
        // NO obtener usuario aquí - hacerlo cada vez que se necesite
    }

    /**
     * Obtener información del usuario actual (cada vez que se llame)
     * @private
     */
    obtenerUsuarioActual() {
        // Obtener directamente de window.usuarioAutenticado (se define en layout.blade.php)
        if (window.usuarioAutenticado) {
            return window.usuarioAutenticado;
        }
        
        // Fallback por si no está disponible
        return {
            nombre: 'Usuario',
            rol: 'Sin Rol',
            email: ''
        };
    }

    /**
     * Construir novedad con información de usuario, fecha/hora y razón
     * Mismo formato que OperarioController: [usuario - DD-MM-YYYY HH:MM:SS] descripción
     * @private
     */
    construirNovedadConMetadata(razonDelCambio) {
        // Obtener usuario en este momento
        const usuarioActual = this.obtenerUsuarioActual();
        
        const ahora = new Date();
        // Formato DD-MM-YYYY
        const dia = String(ahora.getDate()).padStart(2, '0');
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const año = ahora.getFullYear();
        const fecha = `${dia}-${mes}-${año}`;
        
        // Formato HH:MM:SS
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const hora = `${horas}:${minutos}:${segundos}`;
        
        // Formato: [usuario - DD-MM-YYYY HH:MM:SS] descripción
        const novedad = `[${usuarioActual.nombre} - ${fecha} ${hora}] ${razonDelCambio}`;
        return novedad;
    }

    forzarZIndexMaximo() {
        const container = document.querySelector('.swal2-container');
        const popup = document.querySelector('.swal2-popup');
        const backdrop = document.querySelector('.swal2-backdrop');
        if (container) container.style.zIndex = this.zIndexMaximoForzado;
        if (popup) popup.style.zIndex = this.zIndexMaximoForzado;
        if (backdrop) backdrop.style.zIndex = (this.zIndexMaximoForzado - 1);
    }

    async mostrarModalYActualizar(pedidoId, prendaData, prendaIndex) {
        this.pedidoId = pedidoId;
        this.prendaData = prendaData;
        this.prendaIndex = prendaIndex;

        return new Promise((resolve) => {
            const html = `
                <div style="text-align: left;">
                    <p style="margin: 0 0 1rem 0; color: #374151; font-size: 1rem;">
                        <strong>📝 Registra una novedad del cambio</strong>
                    </p>
                    <textarea id="modalNovedadEdicion" placeholder="Ej: Se cambió el color a rojo..." 
                              style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; 
                                     font-size: 0.95rem; min-height: 120px; font-family: inherit; resize: vertical;"></textarea>
                </div>
            `;

            Swal.fire({
                title: '📝 Registrar Cambios en Prenda',
                html: html,
                icon: 'info',
                confirmButtonText: '✓ Guardar Cambios',
                confirmButtonColor: '#3b82f6',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    this.forzarZIndexMaximo();
                    const textarea = document.getElementById('modalNovedadEdicion');
                    if (textarea) textarea.focus();
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const novedad = document.getElementById('modalNovedadEdicion').value.trim();
                    if (!novedad) {
                        Swal.fire({
                            title: ' Campo requerido',
                            html: '<p>Por favor escribe una novedad</p>',
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            resolve(this.mostrarModalYActualizar(pedidoId, prendaData, prendaIndex));
                        });
                        return;
                    }
                    // NUEVO: Aplicar cambios del buffer de procesos ANTES de guardar
                    if (typeof window.aplicarCambiosProcesosDesdeBuffer === 'function') {
                        window.aplicarCambiosProcesosDesdeBuffer();
                        console.log('[modal-novedad-edicion] ✅ Buffer de procesos aplicado');
                    }
                    // NUEVO: Construir novedad con metadata del usuario
                    const novedadConMetadata = this.construirNovedadConMetadata(novedad);
                    await this.actualizarPrendaConNovedad(novedadConMetadata);
                    resolve();
                } else {
                    resolve();
                }
            });
        });
    }

    async actualizarPrendaConNovedad(novedad) {
        this.mostrarCargando();

        try {
            const formData = new FormData();
            formData.append('nombre_prenda', this.prendaData.nombre_prenda);
            formData.append('descripcion', this.prendaData.descripcion);
            formData.append('origen', this.prendaData.origen);
            
            // IMPORTANTE: Leer tallas ACTUALIZADAS del modal (window.tallasRelacionales)
            // NO del this.prendaData inicial que puede estar desactualizado
            let tallasParaEnviar = window.tallasRelacionales || this.prendaData.tallas || {};
            
            if (tallasParaEnviar && Object.keys(tallasParaEnviar).length > 0) {
                const tallasArray = [];
                for (const [genero, tallas] of Object.entries(tallasParaEnviar)) {
                    if (typeof tallas === 'object' && tallas !== null) {
                        for (const [talla, cantidad] of Object.entries(tallas)) {
                            if (cantidad > 0) {
                                tallasArray.push({
                                    genero: genero.toLowerCase(),
                                    talla: talla,
                                    cantidad: parseInt(cantidad)
                                });
                            }
                        }
                    }
                }
                if (tallasArray.length > 0) {
                    formData.append('tallas', JSON.stringify(tallasArray));
                    console.log('[modal-novedad-edicion] ✅ Tallas ACTUALIZADAS enviadas:', tallasArray);
                }
            }
            
            // Agregar variantes si existen
            // Leer variantes ACTUALES del formulario, no de this.prendaData
            const variantesActuales = await this.obtenerVariantesDelFormulario();
            
            if (variantesActuales && Object.keys(variantesActuales).some(key => variantesActuales[key] !== null && variantesActuales[key] !== '')) {
                // Convertir variantes a formato esperado por backend: array de objetos
                const variantesArray = this.convertirVariantesAlFormatoBackend(variantesActuales);
                formData.append('variantes', JSON.stringify(variantesArray));
                console.log('[modal-novedad-edicion] Variantes enviadas:', variantesArray);
            } else {
                console.log('[modal-novedad-edicion]  No hay variantes para enviar');
            }
            
            // NUEVO: Enviar telas (MERGE pattern - conservar telas existentes + agregar nuevas)
            // FLUJO EDICIÓN: usar window.telasAgregadas (nuevo) o window.telasEdicion (legacy)
            const telasParaEnviar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
                ? window.telasAgregadas 
                : window.telasEdicion;
            
            if (telasParaEnviar && telasParaEnviar.length > 0) {
                const telasArray = telasParaEnviar.map((tela, idx) => {
                    const obj = {
                        // Datos visibles
                        color: tela.color || '',
                        tela: tela.tela || '',
                        referencia: tela.referencia || '',
                        
                        // Nombres para fallback si faltan IDs
                        color_nombre: tela.color_nombre || tela.color || '',
                        tela_nombre: tela.tela_nombre || tela.tela || ''
                    };
                    
                    // ✅ AGREGAR IDs PARA MERGE PATTERN
                    // Si tiene ID de relación, es tela existente → UPDATE
                    if (tela.id) {
                        obj.id = tela.id;  // ID de relación (prenda_pedido_colores_telas.id)
                    }
                    
                    // Agregar IDs del color y tela (para búsqueda en backend)
                    if (tela.color_id) {
                        obj.color_id = tela.color_id;
                    }
                    if (tela.tela_id) {
                        obj.tela_id = tela.tela_id;
                    }
                    
                    // Procesar imágenes
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        obj.imagenes = [];
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                // Imagen nueva (File object)
                                formData.append(`telas[${idx}][imagenes][${imgIdx}]`, img);
                            } else if (img.urlDesdeDB || img.url) {
                                // Imagen existente de BD - guardar para preservar
                                obj.imagenes.push({
                                    url: img.url || img.urlDesdeDB,
                                    nombre: img.nombre || ''
                                });
                            }
                        });
                    }
                    
                    return obj;
                });
                formData.append('colores_telas', JSON.stringify(telasArray));
                console.log('[modal-novedad-edicion] Telas enviadas (MERGE):', telasArray);
                
                // ✅ ENVIAR FOTOS DE TELAS CON ESTRUCTURA CORRECTA (MERGE PATTERN)
                const fotosTelaArray = [];
                let fotoTelaFileIndex = 0;
                
                telasParaEnviar.forEach((tela, telaIdx) => {
                    if (tela.imagenes && tela.imagenes.length > 0) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                // Subir imagen a FormData
                                formData.append(`fotos_tela[${fotoTelaFileIndex}]`, img);
                                
                                // Registrar metadatos en array para backend
                                fotosTelaArray.push({
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    id: img.id || null,  // ID de foto existente si está siendo actualizada
                                    orden: imgIdx + 1
                                });
                                fotoTelaFileIndex++;
                            } else if (img.id && (img.urlDesdeDB || img.url)) {
                                // Foto existente - preservar referencia
                                fotosTelaArray.push({
                                    id: img.id,
                                    color_id: tela.color_id || null,
                                    tela_id: tela.tela_id || null,
                                    ruta_original: img.url || img.urlDesdeDB,
                                    orden: imgIdx + 1
                                });
                            }
                        });
                    }
                });
                
                if (fotosTelaArray.length > 0) {
                    formData.append('fotosTelas', JSON.stringify(fotosTelaArray));
                    console.log('[modal-novedad-edicion] ✅ Fotos de telas enviadas (MERGE):', fotosTelaArray);
                }
            }

            // IMPORTANTE: Leer procesos ACTUALIZADOS (window.procesosSeleccionados)
            // NO del this.prendaData inicial que no incluye procesos nuevos agregados en el modal
            const procesosParaEnviar = window.procesosSeleccionados || this.prendaData.procesos || {};
            const procesosArray = this._transformarProcesosAArray(procesosParaEnviar);
            formData.append('procesos', JSON.stringify(procesosArray)); // Usar array transformado
            console.log('[modal-novedad-edicion] ✅ Procesos ACTUALIZADOS enviados:', procesosArray);
            formData.append('novedad', novedad);
            
            // Obtener prenda_id - puede venir en diferentes propiedades
            const prendaId = this.prendaData.prenda_pedido_id || this.prendaData.id;




            
            if (!prendaId || isNaN(prendaId)) {
                throw new Error('ID de prenda inválido o no disponible. Recibido: ' + prendaId);
            }
            
            const prendaIdInt = parseInt(prendaId);

            formData.append('prenda_id', prendaIdInt);
            
            // Separar imágenes nuevas (File objects) de imágenes existentes (DB)
            const imagenesNuevas = [];
            const imagenesDB = [];
            
            if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
                this.prendaData.imagenes.forEach((img, idx) => {
                    if (img instanceof File) {
                        imagenesNuevas.push(img);
                        formData.append(`imagenes[${imagenesNuevas.length - 1}]`, img);
                    } else if (img && img.urlDesdeDB) {
                        // Imagen existente de la BD - guardar URL para preservarla
                        imagenesDB.push({
                            previewUrl: img.previewUrl,
                            nombre: img.nombre
                        });
                    }
                });
            }
            
            // Enviar imágenes existentes como JSON para que backend las preserve
            if (imagenesDB.length > 0) {
                formData.append('imagenes_existentes', JSON.stringify(imagenesDB));
            }
            



            const response = await fetch(`/asesores/pedidos/${this.pedidoId}/actualizar-prenda`, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: formData
            });
            
            const resultado = await response.json();
            if (!response.ok || !resultado.success) throw new Error(resultado.message);
            


            
            // IMPORTANTE: Recargar datos completos del pedido para asegurar que telasAgregadas y datos relacionados se actualizan correctamente
            if (window.prendaEnEdicion) {
                const pedidoId = window.prendaEnEdicion.pedidoId;

                
                try {
                    const respDataEdicion = await fetch(`/asesores/pedidos-produccion/${pedidoId}/datos-edicion`);
                    const resultadoDataEdicion = await respDataEdicion.json();
                    
                    if (resultadoDataEdicion.success && resultadoDataEdicion.datos) {

                        window.datosEdicionPedido = resultadoDataEdicion.datos;
                        
                        // Actualizar en prendasEdicion también
                        if (window.prendasEdicion) {
                            window.prendasEdicion.prendas = resultadoDataEdicion.datos.prendas;
                            window.prendasEdicion.pedidoId = resultadoDataEdicion.datos.id || resultadoDataEdicion.datos.numero_pedido;
                        }
                    }
                } catch (e) {

                    // Si falla la recarga automática, al menos actualizar la prenda con los datos que vinieron
                    if (resultado.prenda && window.datosEdicionPedido && window.prendaEnEdicion) {
                        const prendasIndex = window.prendaEnEdicion.prendasIndex;
                        if (prendasIndex !== null && prendasIndex !== undefined) {
                            window.datosEdicionPedido.prendas[prendasIndex] = resultado.prenda;
                            if (window.prendasEdicion && window.prendasEdicion.prendas) {
                                window.prendasEdicion.prendas[prendasIndex] = resultado.prenda;
                            }
                        }
                    }
                }
            }
            
            this.mostrarExito();
        } catch (error) {

            this.mostrarError(error.message);
        }
    }

    mostrarCargando() {
        Swal.fire({
            title: '⏳ Actualizando...',
            html: '<p>Por favor espera</p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    mostrarExito() {
        Swal.fire({
            title: ' ¡Éxito!',
            html: '<p>Prenda actualizada correctamente</p>',
            icon: 'success',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        }).then((result) => {
            if (result.isConfirmed) {
                // NUEVO: Disparar evento para actualizar tabla en tiempo real
                const evento = new CustomEvent('prendaActualizada', {
                    detail: {
                        pedidoId: this.pedidoId,
                        prendaId: this.prendaData.prenda_pedido_id || this.prendaData.id,
                        timestamp: new Date()
                    }
                });
                window.dispatchEvent(evento);
                console.log('[modal-novedad-edicion] 📢 Evento disparado: prendaActualizada', evento.detail);
                
                // IMPORTANTE: Solo cerrar el modal de prenda, NO abrir otro modal
                // El usuario estaba editando dentro del modal de prenda y ya finalizó
                if (typeof window.cerrarModalPrendaNueva === 'function') {
                    window.cerrarModalPrendaNueva();
                }
            }
        });
    }

    mostrarError(mensaje) {
        Swal.fire({
            title: ' Error',
            html: `<p>${mensaje}</p>`,
            icon: 'error',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#ef4444',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => this.forzarZIndexMaximo()
        });
    }

    convertirTallasAlFormatoJson(tallas) {
        // Convierte array de tallas {genero, talla, cantidad} a JSON {GENERO: {talla: cantidad}}
        if (!Array.isArray(tallas)) return {};
        
        const resultado = {};
        tallas.forEach(tallaObj => {
            if (tallaObj.genero && tallaObj.talla && tallaObj.cantidad) {
                const genero = tallaObj.genero.toUpperCase();
                if (!resultado[genero]) {
                    resultado[genero] = {};
                }
                resultado[genero][tallaObj.talla] = tallaObj.cantidad;
            }
        });
        return resultado;
    }

    convertirVariantesAlFormatoBackend(variantes) {
        // Convierte variantes (objeto o array) al formato esperado por backend
        // Formato esperado: [ { tipo_manga_id, tipo_broche_boton_id, manga_obs, broche_boton_obs, tiene_bolsillos, bolsillos_obs } ]
        
        // Si ya es un array, validar que tenga los campos correctos
        if (Array.isArray(variantes)) {
            return variantes.map(v => ({
                tipo_manga_id: v.tipo_manga_id || null,
                tipo_broche_boton_id: v.tipo_broche_boton_id || null,
                manga_obs: v.manga_obs || v.obs_manga || v.manga || '',
                broche_boton_obs: v.broche_boton_obs || v.obs_broche || v.broche || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                bolsillos_obs: v.bolsillos_obs || v.obs_bolsillos || '',
                tiene_reflectivo: v.tiene_reflectivo || false,
                reflectivo_obs: v.reflectivo_obs || v.obs_reflectivo || ''
            }));
        }
        
        // Si es un objeto con propiedades de variantes, convertir a array
        if (variantes && typeof variantes === 'object') {
            // Crear un único objeto de variante con todas las propiedades
            const varianteObject = {
                tipo_manga_id: variantes.tipo_manga_id || null,
                tipo_broche_boton_id: variantes.tipo_broche_boton_id || null,
                manga_obs: variantes.obs_manga || variantes.manga || variantes.manga_obs || '',
                broche_boton_obs: variantes.obs_broche || variantes.broche || variantes.broche_boton_obs || '',
                tiene_bolsillos: variantes.tiene_bolsillos || false,
                bolsillos_obs: variantes.obs_bolsillos || variantes.bolsillos_obs || '',
                tiene_reflectivo: variantes.tiene_reflectivo || false,
                reflectivo_obs: variantes.obs_reflectivo || variantes.reflectivo_obs || ''
            };
            
            // Retornar como array con un único elemento
            return [varianteObject];
        }
        
        // Si está vacío, retornar array vacío
        return [];
    }

    /**
     * Obtener variantes actuales del formulario
     */
    async obtenerVariantesDelFormulario() {
        const variante = {};

        // Manga
        const mangaCheckbox = document.getElementById('aplica-manga');
        if (mangaCheckbox && mangaCheckbox.checked) {
            const mangaInput = document.getElementById('manga-input');
            const mangaObs = document.getElementById('manga-obs');
            
            // Procesar el input de manga (crea automáticamente si no existe)
            if (mangaInput && mangaInput.value && typeof window.procesarMangaInput === 'function') {
                await window.procesarMangaInput(mangaInput);
            }
            
            // Obtener el ID de la manga seleccionada
            let tipo_manga_id = null;
            if (mangaInput && mangaInput.value) {
                // Buscar el ID en el datalist basado en el nombre seleccionado
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    const option = Array.from(datalist.options).find(opt => opt.value === mangaInput.value);
                    if (option && option.dataset.id) {
                        tipo_manga_id = parseInt(option.dataset.id);
                    }
                }
            }
            
            variante.tipo_manga_id = tipo_manga_id;
            variante.manga_obs = mangaObs?.value || '';
        }

        // Broche/Botón
        const brocheCheckbox = document.getElementById('aplica-broche');
        if (brocheCheckbox && brocheCheckbox.checked) {
            const brocheInput = document.getElementById('broche-input');
            const brocheObs = document.getElementById('broche-obs');
            
            // Mapear valor del select a ID
            let tipo_broche_boton_id = null;
            if (brocheInput && brocheInput.value) {
                if (brocheInput.value === 'broche') {
                    tipo_broche_boton_id = 1;
                } else if (brocheInput.value === 'boton') {
                    tipo_broche_boton_id = 2;
                }
            }
            
            variante.tipo_broche_boton_id = tipo_broche_boton_id;
            variante.broche_boton_obs = brocheObs?.value || '';
        }

        // Bolsillos
        const bolsillosCheckbox = document.getElementById('aplica-bolsillos');
        if (bolsillosCheckbox && bolsillosCheckbox.checked) {
            const bolsillosObs = document.getElementById('bolsillos-obs');
            variante.tiene_bolsillos = true;
            variante.bolsillos_obs = bolsillosObs?.value || '';
        } else {
            variante.tiene_bolsillos = false;
            variante.bolsillos_obs = '';
        }

        // Reflectivo
        const reflectivoCheckbox = document.getElementById('checkbox-reflectivo');
        if (reflectivoCheckbox && reflectivoCheckbox.checked) {
            const reflectivoObs = document.getElementById('obs-reflectivo');
            variante.tiene_reflectivo = true;
            variante.reflectivo_obs = reflectivoObs?.value || '';
        } else {
            variante.tiene_reflectivo = false;
            variante.reflectivo_obs = '';
        }

        return variante;
    }

    /**
     * Transformar procesos de estructura de objeto a array
     * De: { 'reflectivo': { datos: {...} }, 'estampado': { datos: {...} } }
     * A:  [ { tipo_proceso_id: 1, ubicaciones: [...], ... }, { tipo_proceso_id: 2, ... } ]
     */
    _transformarProcesosAArray(procesosObj) {
        if (!procesosObj || typeof procesosObj !== 'object' || Array.isArray(procesosObj)) {
            return Array.isArray(procesosObj) ? procesosObj : [];
        }

        return Object.entries(procesosObj).map(([tipoProceso, procInfo]) => {
            const datosProc = procInfo?.datos || procInfo || {};
            return {
                id: datosProc.id || undefined,
                tipo_proceso_id: datosProc.tipo_proceso_id || undefined,
                tipo: datosProc.tipo || tipoProceso,
                nombre: datosProc.nombre || tipoProceso,
                ubicaciones: datosProc.ubicaciones || [],
                observaciones: datosProc.observaciones || '',
                estado: datosProc.estado || 'PENDIENTE'
            };
        }).filter(proc => proc.tipo_proceso_id); // Solo retornar procesos con tipo_proceso_id válido
    }
}

// Instanciar modal cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.modalNovedadEditacion = new ModalNovedadEdicion();
    });
} else {
    window.modalNovedadEditacion = new ModalNovedadEdicion();
}

