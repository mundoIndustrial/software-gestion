/**
 * Gestión de Ítems - Capa de Presentación
 * 
 * Responsabilidades:
 * - Renderizar lista de ítems del pedido
 * - Manejar agregar/eliminar ítems desde UI
 * - Recolectar datos del formulario para envío
 * - Coordinar con el backend para crear pedido
 * - Mostrar notificaciones y vista previa
 */

class GestionItemsUI {
    constructor() {
        this.api = window.pedidosAPI;
        this.items = [];
        this.prendaEditIndex = null;  // ✅ NUEVO: Rastrear índice de prenda siendo editada
        this.inicializar();
    }

    inicializar() {
        this.attachEventListeners();
        this.cargarItems();
    }

    attachEventListeners() {
        // Agregar ítem desde cotización
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        // Agregar ítem nuevo
        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        // Vista previa
        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        // Formulario de creación
        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    async cargarItems() {
        try {
            const resultado = await this.api.obtenerItems();
            this.items = resultado.items;
            this.actualizarVistaItems();
        } catch (error) {
            console.error('Error al cargar ítems:', error);
        }
    }

    async agregarItem(itemData) {
        try {
            const resultado = await this.api.agregarItem(itemData);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem agregado correctamente', 'success');
                return true;
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
            return false;
        }
    }

    async eliminarItem(index) {
        if (!confirm('¿Eliminar este ítem?')) {
            return;
        }

        try {
            const resultado = await this.api.eliminarItem(index);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem eliminado', 'success');
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    actualizarVistaItems() {
        const container = document.getElementById('lista-items-pedido');

        if (!container) return;

        if (this.items.length === 0) {
            container.innerHTML = '';
            return;
        }

        if (mensajeSinItems) mensajeSinItems.style.display = 'none';

        // Renderizar todos los items y actualizar el DOM
        this.renderizarItems();
    }

    async renderizarItems() {
        const container = document.getElementById('lista-items-pedido');
        if (!container) return;

        container.innerHTML = '';

        for (let index = 0; index < this.items.length; index++) {
            const item = this.items[index];
            try {
                const html = await this.obtenerItemCardHTML(item, index);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                container.appendChild(tempDiv.firstElementChild);
            } catch (error) {
                console.error(`Error al renderizar item ${index}:`, error);
                console.warn('⚠️  No hay fallback disponible. Omitiendo item con error.');
                // No renderizar fallback - solo omitir el item
            }
        }

        // Actualizar interactividad
        if (window.updateItemCardInteractions) {
            window.updateItemCardInteractions();
        }
    }

    async obtenerItemCardHTML(item, index) {
        try {
            const response = await fetch('/api/pedidos-editable/render-item-card', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    item: item,
                    index: index,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success && data.html) {
                return data.html;
            } else {
                throw new Error(data.error || 'Error al renderizar componente');
            }
        } catch (error) {
            console.error('Error al obtener HTML del item-card:', error);
            throw error;
        }
    }

    abrirModalSeleccionPrendas() {
        // Delegar a modal-seleccion-prendas.js
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        console.log('🎯 [GestionItemsUI] abrirModalAgregarPrendaNueva() - abriendo modal');
        
        // ✅ NUEVO: Limpiar índice de edición cuando se abre para crear NUEVA
        // Solo limpiar si NO se está editando (si no viene de cargarItemEnModal)
        if (this.prendaEditIndex === undefined) {
            // No hacer nada, es apertura normal de nuevo modal
        } else if (this.prendaEditIndex === null) {
            // Ya está limpio
        }
        // Si tiene valor, se mantiene porque viene de cargarItemEnModal
        
        // Delegar a modal correspondiente
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            console.log('✅ [GestionItemsUI] Modal encontrado, abriendo...');
            modal.style.display = 'flex';
            
            // Asegurar que el formulario esté limpio
            const form = document.getElementById('form-prenda-nueva');
            if (form) {
                form.reset();
                console.log('🧹 [GestionItemsUI] Formulario limpiado');
            }
            
            // Limpiar storage de imágenes
            if (window.imagenesPrendaStorage) {
                window.imagenesPrendaStorage.limpiar();
                console.log('🧹 [GestionItemsUI] Storage de imágenes de prenda limpiado');
            }
            
            // IMPORTANTE: Limpiar storage de telas
            if (window.telasAgregadas) {
                window.telasAgregadas.length = 0;
                console.log('🧹 [GestionItemsUI] Telas agregadas limpiadas');
            }
            
            // IMPORTANTE: Limpiar variables globales de tallas y cantidades
            if (window.cantidadesTallas) {
                window.cantidadesTallas = {};
                console.log('🧹 [GestionItemsUI] Cantidades de tallas limpiadas');
            }
            
            if (window.tallasSeleccionadas) {
                window.tallasSeleccionadas = {
                    dama: { tallas: [], tipo: null },
                    caballero: { tallas: [], tipo: null }
                };
                console.log('🧹 [GestionItemsUI] Tallas seleccionadas limpias');
            }
            
            // ✅ NUEVO: Limpiar procesos seleccionados
            if (window.procesosSeleccionados) {
                window.procesosSeleccionados = {};
                console.log('🧹 [GestionItemsUI] Procesos seleccionados limpiados');
            }
            
            // ✅ NUEVO: Limpiar contenedor de tarjetas de procesos (pero NO ponerlo en display:none)
            const contenedorTarjetas = document.getElementById('contenedor-tarjetas-procesos');
            if (contenedorTarjetas) {
                contenedorTarjetas.innerHTML = '';
                // NO poner display:none aquí - dejar que renderizarTarjetasProcesos() controle el display
                console.log('🧹 [GestionItemsUI] Contenedor de tarjetas de procesos limpiado');
            }
            
            // Limpiar índice de edición si existe
            this.prendaEditIndex = null;
            console.log('🧹 [GestionItemsUI] Índice de edición limpiado');
            
            // IMPORTANTE: Limpiar checkboxes de variaciones
            const checkboxes = [
                'aplica-manga', 'aplica-bolsillos', 'aplica-broche',
                'checkbox-reflectivo', 'checkbox-bordado', 'checkbox-estampado',
                'checkbox-dtf', 'checkbox-sublimado'
            ];
            
            checkboxes.forEach(checkboxId => {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    checkbox.checked = false;
                }
            });
            console.log('🧹 [GestionItemsUI] Checkboxes de variaciones limpiados');
            
            // Limpiar campos de texto asociados a variaciones
            const campos = [
                'manga-input', 'manga-obs',
                'bolsillos-obs',
                'broche-input', 'broche-obs',
                'reflectivo-obs'
            ];
            
            campos.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = '';
                    field.disabled = true;
                    field.style.opacity = '0.5';
                }
            });
            console.log('🧹 [GestionItemsUI] Campos de variaciones limpios');
            
        } else {
            console.error('❌ [GestionItemsUI] Modal no encontrado');
        }
    }

    /**
     * Cargar datos de prenda en el modal para editar
     * @param {Object} prenda - Objeto de prenda a cargar
     * @param {number} prendaIndex - Índice de la prenda
     */
    cargarItemEnModal(prenda, prendaIndex) {
        console.log('📝 [GestionItemsUI] cargarItemEnModal() - cargando prenda para editar');
        console.log('   Prenda recibida:', prenda);
        console.log('   Índice:', prendaIndex);
        
        // Abrir el modal primero
        this.abrirModalAgregarPrendaNueva();
        
        if (!prenda) {
            console.warn('⚠️  Prenda no válida');
            return;
        }
        
        // Poblar formulario con datos de prenda
        const form = document.getElementById('form-prenda-nueva');
        if (!form) {
            console.error('❌ Formulario no encontrado');
            return;
        }
        
        // Llenar campos básicos
        const nombreField = document.getElementById('nueva-prenda-nombre');
        const descripcionField = document.getElementById('nueva-prenda-descripcion');
        const origenField = document.getElementById('nueva-prenda-origen-select');
        
        if (nombreField) nombreField.value = prenda.nombre_producto || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        if (origenField) origenField.value = prenda.origen || 'bodega';
        
        console.log('✅ Campos básicos cargados');
        
        // ========== CARGAR IMÁGENES ==========
        console.log('📸 Cargando imágenes...');
        if (prenda.imagenes && prenda.imagenes.length > 0 && window.imagenesPrendaStorage) {
            // Limpiar storage primero
            window.imagenesPrendaStorage.limpiar();
            
            // Agregar imágenes al storage
            prenda.imagenes.forEach(img => {
                if (img.file) {
                    window.imagenesPrendaStorage.agregarImagen(img.file);
                    console.log(`   ✅ Imagen cargada: ${img.nombre}`);
                }
            });
            
            console.log(`✅ ${prenda.imagenes.length} imagen(es) cargada(s)`);
            
            // Actualizar preview
            if (window.actualizarPreviewPrenda) {
                window.actualizarPreviewPrenda();
            }
        } else {
            console.log('📸 Sin imágenes para cargar');
        }
        
        // ========== CARGAR TELAS ==========
        console.log('🧵 Cargando telas...');
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0 && window.telasAgregadas) {
            // Limpiar telas existentes
            window.telasAgregadas.length = 0;
            
            // Agregar telas
            prenda.telasAgregadas.forEach(tela => {
                window.telasAgregadas.push({
                    color: tela.color || '',
                    tela: tela.tela || '',
                    referencia: tela.referencia || '',
                    imagenes: tela.imagenes || []
                });
            });
            
            console.log(`✅ ${prenda.telasAgregadas.length} tela(s) cargada(s)`);
            
            // Actualizar tabla de telas
            if (window.actualizarTablaTelas) {
                window.actualizarTablaTelas();
            }
        }
        
        // ========== CARGAR TALLAS Y CANTIDADES ==========
        console.log('📏 Cargando tallas y cantidades...');
        console.log('   prenda.tallas:', prenda.tallas);
        console.log('   prenda.cantidadesPorTalla:', prenda.cantidadesPorTalla);
        
        if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
            // Inicializar variables globales si no existen
            if (!window.cantidadesTallas) {
                window.cantidadesTallas = {};
            }
            if (!window.tallasSeleccionadas) {
                window.tallasSeleccionadas = {
                    dama: { tallas: [], tipo: null },
                    caballero: { tallas: [], tipo: null }
                };
            }
            
            // Procesar cada género de tallas
            prenda.tallas.forEach(tallaGenero => {
                const generoActual = tallaGenero.genero || 'dama';
                const listaTallas = tallaGenero.tallas || [];
                const tipoTalla = tallaGenero.tipo || 'letra';
                
                console.log(`   Procesando género: ${generoActual}`);
                console.log(`   Tallas: ${listaTallas.join(', ')}`);
                console.log(`   Tipo: ${tipoTalla}`);
                
                // PRIMERO: Cargar cantidades en window.cantidadesTallas ANTES de limpiar
                if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
                    listaTallas.forEach(talla => {
                        const tallaKey = `${generoActual}-${talla}`;
                        const cantidad = prenda.cantidadesPorTalla[tallaKey];
                        
                        // Solo asignar si existe el valor en prenda
                        if (cantidad !== undefined && cantidad !== null) {
                            window.cantidadesTallas[tallaKey] = cantidad;
                            console.log(`   ✅ Cantidad sincronizada ${tallaKey}: ${cantidad}`);
                        } else {
                            console.warn(`   ⚠️  No hay cantidad para: ${tallaKey}`);
                        }
                    });
                } else {
                    console.warn('   ⚠️  cantidadesPorTalla no encontrado o no es objeto');
                }
                
                // LUEGO: Sincronizar tallas seleccionadas
                window.tallasSeleccionadas[generoActual] = {
                    tallas: listaTallas,
                    tipo: tipoTalla
                };
                
                console.log(`   Género ${generoActual} sincronizado`);
            });
            
            console.log('   Estado final de cantidades:', window.cantidadesTallas);
            
            // FINALMENTE: Actualizar inputs si ya existen en el DOM
            Object.entries(window.cantidadesTallas).forEach(([tallaKey, cantidad]) => {
                let input = document.querySelector(`input[data-key="${tallaKey}"]`);
                
                if (input) {
                    input.value = cantidad;
                    console.log(`   ✅ Input actualizado para ${tallaKey}: ${cantidad}`);
                } else {
                    console.log(`   ℹ️  Input para ${tallaKey} aún no existe en el DOM`);
                }
            });
        } else {
            console.warn('   ⚠️  No hay tallas para cargar');
        }
        
        // ========== CARGAR VARIACIONES ==========
        console.log('🔧 Cargando variaciones...');
        console.log('   Verificando ubicación de variaciones:');
        console.log('   prenda.variantes:', prenda.variantes);
        console.log('   prenda.tipo_manga:', prenda.tipo_manga);
        
        // Determinar de dónde extraer las variaciones
        const variaciones = prenda.variantes || {};
        const tipoManga = variaciones.tipo_manga || prenda.tipo_manga || 'No aplica';
        const obsManga = variaciones.obs_manga || prenda.obs_manga || '';
        const tieneBolsillos = variaciones.tiene_bolsillos || prenda.tiene_bolsillos || false;
        const obsBolsillos = variaciones.obs_bolsillos || prenda.obs_bolsillos || '';
        const tipoBroche = variaciones.tipo_broche || prenda.tipo_broche || 'No aplica';
        const obsBroche = variaciones.obs_broche || prenda.obs_broche || '';
        const tieneReflectivo = variaciones.tiene_reflectivo || prenda.tiene_reflectivo || false;
        
        console.log('   Variaciones extraídas:');
        console.log('   - tipo_manga:', tipoManga);
        console.log('   - obs_manga:', obsManga);
        console.log('   - tiene_bolsillos:', tieneBolsillos);
        console.log('   - obs_bolsillos:', obsBolsillos);
        console.log('   - tipo_broche:', tipoBroche);
        console.log('   - obs_broche:', obsBroche);
        console.log('   - tiene_reflectivo:', tieneReflectivo);
        
        // Manga
        const aplicaMangaCheckbox = document.getElementById('aplica-manga');
        const mangaInput = document.getElementById('manga-input');
        const mangaObs = document.getElementById('manga-obs');
        
        if (aplicaMangaCheckbox) {
            aplicaMangaCheckbox.checked = tipoManga !== 'No aplica' && tipoManga !== '';
            if (mangaInput) mangaInput.value = tipoManga && tipoManga !== 'No aplica' ? tipoManga : '';
            if (mangaInput) mangaInput.disabled = !aplicaMangaCheckbox.checked;
            if (mangaInput) mangaInput.style.opacity = aplicaMangaCheckbox.checked ? '1' : '0.5';
            if (mangaObs) mangaObs.value = obsManga;
            if (mangaObs) mangaObs.disabled = !aplicaMangaCheckbox.checked;
            if (mangaObs) mangaObs.style.opacity = aplicaMangaCheckbox.checked ? '1' : '0.5';
            console.log('✅ Manga cargada');
        }
        
        // Bolsillos
        const aplicaBolsillosCheckbox = document.getElementById('aplica-bolsillos');
        const bolsillosObs = document.getElementById('bolsillos-obs');
        
        if (aplicaBolsillosCheckbox) {
            aplicaBolsillosCheckbox.checked = tieneBolsillos === true || tieneBolsillos === 'true';
            if (bolsillosObs) bolsillosObs.value = obsBolsillos;
            if (bolsillosObs) bolsillosObs.disabled = !aplicaBolsillosCheckbox.checked;
            if (bolsillosObs) bolsillosObs.style.opacity = aplicaBolsillosCheckbox.checked ? '1' : '0.5';
            console.log('✅ Bolsillos cargados');
        }
        
        // Broche
        const aplicaBrocheCheckbox = document.getElementById('aplica-broche');
        const brocheInput = document.getElementById('broche-input');
        const brocheObs = document.getElementById('broche-obs');
        
        if (aplicaBrocheCheckbox) {
            aplicaBrocheCheckbox.checked = tipoBroche !== 'No aplica' && tipoBroche !== '';
            if (brocheInput) brocheInput.value = tipoBroche && tipoBroche !== 'No aplica' ? tipoBroche : 'boton';
            if (brocheInput) brocheInput.disabled = !aplicaBrocheCheckbox.checked;
            if (brocheInput) brocheInput.style.opacity = aplicaBrocheCheckbox.checked ? '1' : '0.5';
            if (brocheObs) brocheObs.value = obsBroche;
            if (brocheObs) brocheObs.disabled = !aplicaBrocheCheckbox.checked;
            if (brocheObs) brocheObs.style.opacity = aplicaBrocheCheckbox.checked ? '1' : '0.5';
            console.log('✅ Broche cargado');
        }
        
        // Reflectivo
        const checkboxReflectivo = document.getElementById('checkbox-reflectivo');
        if (checkboxReflectivo) {
            checkboxReflectivo.checked = tieneReflectivo === true || tieneReflectivo === 'true';
            console.log('✅ Reflectivo cargado');
        }
        
        // Procesos adicionales
        const checkboxBordado = document.getElementById('checkbox-bordado');
        const checkboxEstampado = document.getElementById('checkbox-estampado');
        const checkboxDtf = document.getElementById('checkbox-dtf');
        const checkboxSublimado = document.getElementById('checkbox-sublimado');
        
        if (checkboxBordado) checkboxBordado.checked = variaciones.proceso_bordado === true || variaciones.proceso_bordado === 'true' || prenda.proceso_bordado === true || prenda.proceso_bordado === 'true';
        if (checkboxEstampado) checkboxEstampado.checked = variaciones.proceso_estampado === true || variaciones.proceso_estampado === 'true' || prenda.proceso_estampado === true || prenda.proceso_estampado === 'true';
        if (checkboxDtf) checkboxDtf.checked = variaciones.proceso_dtf === true || variaciones.proceso_dtf === 'true' || prenda.proceso_dtf === true || prenda.proceso_dtf === 'true';
        if (checkboxSublimado) checkboxSublimado.checked = variaciones.proceso_sublimado === true || variaciones.proceso_sublimado === 'true' || prenda.proceso_sublimado === true || prenda.proceso_sublimado === 'true';
        
        console.log('✅ Procesos booleanos cargados');
        
        // ========== CARGAR DATOS COMPLETOS DE PROCESOS ==========
        console.log('🔧 Cargando datos completos de procesos...');
        
        // ✅ NUEVO: Limpiar contenedor de tarjetas antes de cargar procesos
        const contenedorTarjetas = document.getElementById('contenedor-tarjetas-procesos');
        if (contenedorTarjetas) {
            contenedorTarjetas.innerHTML = '';
            // NO poner display:none aquí - dejar que renderizarTarjetasProcesos() controle el display
            console.log('🧹 Contenedor de tarjetas limpiado antes de cargar procesos');
        }
        
        if (prenda.procesos && typeof prenda.procesos === 'object' && Object.keys(prenda.procesos).length > 0) {
            // Reinicializar window.procesosSeleccionados
            window.procesosSeleccionados = {};
            
            // Cargar cada proceso con sus datos
            Object.entries(prenda.procesos).forEach(([tipoProceso, procesoData]) => {
                if (procesoData && procesoData.datos) {
                    console.log(`   📋 Cargando proceso: ${tipoProceso}`, procesoData);
                    
                    window.procesosSeleccionados[tipoProceso] = {
                        tipo: tipoProceso,
                        datos: procesoData.datos
                    };
                    
                    // Marcar el checkbox
                    const checkbox = document.getElementById(`checkbox-${tipoProceso}`);
                    if (checkbox) {
                        checkbox._ignorarOnclick = true; // Evitar trigger del handler
                        checkbox.checked = true;
                        checkbox._ignorarOnclick = false;
                        console.log(`   ✅ Checkbox ${tipoProceso} marcado`);
                    }
                }
            });
            
            console.log('✅ Procesos completos cargados:', window.procesosSeleccionados);
            
            // Renderizar tarjetas de procesos
            if (window.renderizarTarjetasProcesos) {
                window.renderizarTarjetasProcesos();
                console.log('🎨 Tarjetas de procesos renderizadas');
            }
        } else {
            console.log('ℹ️  Sin procesos completos para cargar');
        }
        
        // Guardar índice para actualización posterior
        this.prendaEditIndex = prendaIndex;
        
        // ✅ NUEVO: Cambiar texto del botón a "Guardar cambios" cuando está editando
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">save</span>Guardar cambios';
        }
        
        console.log('✅ [GestionItemsUI] Prenda cargada completamente en modal para editar');
        console.log('   Índice guardado para actualización:', prendaIndex);
    }

    agregarPrendaNueva() {
        // ✅ NUEVO: Verificar si está editando una prenda existente
        if (this.prendaEditIndex !== undefined && this.prendaEditIndex !== null) {
            console.log('✏️  [GestionItemsUI] EDITANDO prenda en lugar de crear nueva. Índice:', this.prendaEditIndex);
            this.actualizarPrendaExistente();
            return;
        }
        
        console.log('➕ [GestionItemsUI] agregarPrendaNueva() - procesando prenda nueva');
        
        // Debug: listar todos los inputs en el modal
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal && modal.style.display !== 'none') {
            const allInputs = modal.querySelectorAll('input[type="text"], input[type="checkbox"], select, textarea');
            console.log('🔍 [MODAL DEBUG] Inputs encontrados en modal:', allInputs.length);
            allInputs.forEach((input, idx) => {
                if (input.id) {
                    console.log(`  [${idx}] ID: ${input.id}, Type: ${input.type}, Value: "${input.value}", Disabled: ${input.disabled}`);
                }
            });
        }
        
        // Recopilar datos del formulario
        const nombrePrenda = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const origen = document.getElementById('nueva-prenda-origen-select')?.value;
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        
        // ✅ Determinar género basándose en el estado de tallas existente
        let genero = null;
        const tallasSeleccionadas = window.tallasSeleccionadas || {};
        
        // Verificar qué géneros tienen tallas seleccionadas
        const tienetallasDama = tallasSeleccionadas.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas.caballero?.tallas?.length > 0;
        
        if (tienetallasDama && !tieneTallasCaballero) {
            genero = 'dama';
        } else if (tieneTallasCaballero && !tienetallasDama) {
            genero = 'caballero';
        } else if (tienetallasDama && tieneTallasCaballero) {
            // Si hay tallas en ambos, es multi-género
            genero = 'unisex';
        }
        
        console.log('📋 [GestionItemsUI] Datos recopilados:', { nombrePrenda, origen, genero, tallasSeleccionadas });
        
        // Validación básica
        if (!nombrePrenda) {
            alert('Por favor ingresa el nombre de la prenda');
            document.getElementById('nueva-prenda-nombre')?.focus();
            return;
        }
        
        if (!genero) {
            alert('Por favor selecciona tallas para la prenda');
            return;
        }
        
        // Obtener imágenes del storage
        const imagenesPrenda = window.imagenesPrendaStorage?.obtenerImagenes() || [];
        console.log(`📸 [GestionItemsUI] Imágenes de prenda: ${imagenesPrenda.length}`);
        
        // ✅ CRÍTICO: Crear blob URLs AHORA, antes de que se limpie el storage
        const imagenesConUrls = imagenesPrenda.map(img => {
            let blobUrl = null;
            if (img.file instanceof File) {
                blobUrl = URL.createObjectURL(img.file);
                console.log(`   📸 Blob URL creado para imagen: ${blobUrl}`);
            }
            return {
                file: img.file,
                nombre: img.nombre,
                tamaño: img.tamaño,
                blobUrl: blobUrl // Guardar la URL blob creada
            };
        });
        
        // Obtener procesos configurados (reflectivo, bordado, estampado, etc.)
        let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
        console.log(`🎨 [GestionItemsUI] Procesos configurables (antes):`, procesosConfigurables);
        
        // ✅ FILTRAR: Solo incluir procesos que realmente tienen datos
        // Prevenir incluir procesos vacíos (datos: null)
        procesosConfigurables = Object.keys(procesosConfigurables).reduce((acc, tipoProceso) => {
            const proceso = procesosConfigurables[tipoProceso];
            // Incluir el proceso si tiene datos o si es un objeto válido
            if (proceso && (proceso.datos !== null || proceso.tipo)) {
                acc[tipoProceso] = proceso;
            }
            return acc;
        }, {});
        
        console.log(`🎨 [GestionItemsUI] Procesos configurables (después):`, procesosConfigurables);
        
        // ✅ Obtener tallas del estado de gestion-tallas.js
        const tallasPorGenero = [];
        if (tienetallasDama) {
            tallasPorGenero.push({
                genero: 'dama',
                tallas: tallasSeleccionadas.dama.tallas,
                tipo: tallasSeleccionadas.dama.tipo
            });
        }
        if (tieneTallasCaballero) {
            tallasPorGenero.push({
                genero: 'caballero',
                tallas: tallasSeleccionadas.caballero.tallas,
                tipo: tallasSeleccionadas.caballero.tipo
            });
        }
        console.log(`📏 [GestionItemsUI] Tallas por género:`, tallasPorGenero);
        
        // Obtener telas agregadas
        const telasAgregadas = window.telasAgregadas || [];
        console.log(`🧵 [GestionItemsUI] Telas agregadas: ${telasAgregadas.length}`);
        
        // ✅ CRÍTICO: Crear blob URLs para telas AHORA, antes de que se limpie el storage
        const telasConUrls = telasAgregadas.map(tela => ({
            ...tela,
            imagenes: (tela.imagenes || []).map(img => {
                let blobUrl = null;
                if (img.file instanceof File) {
                    blobUrl = URL.createObjectURL(img.file);
                    console.log(`   📸 Blob URL creado para imagen de tela: ${blobUrl}`);
                }
                return {
                    ...img,
                    blobUrl: blobUrl
                };
            })
        }));
        
        // Obtener variaciones configuradas del modal
        const variacionesConfiguradas = {
            tipo_manga: 'No aplica',
            obs_manga: '',
            tipo_broche: 'No aplica',
            obs_broche: '',
            tiene_bolsillos: false,
            obs_bolsillos: '',
            tiene_reflectivo: false,
            obs_reflectivo: ''
        };
        
        // 🔍 VALIDACIÓN EXHAUSTIVA: MANGA
        console.log('🔍 [VARIACIONES CAPTURA] ===== MANGA =====');
        const plicaManga = document.getElementById('aplica-manga');
        console.log('   - aplica-manga checkbox encontrado:', !!plicaManga);
        console.log('   - aplica-manga checkbox.checked:', plicaManga?.checked);
        
        if (plicaManga?.checked) {
            const mangaInput = document.getElementById('manga-input');
            const mangaObs = document.getElementById('manga-obs');
            const tipoMangaRaw = mangaInput?.value?.trim();
            const obsMangaRaw = mangaObs?.value?.trim();
            
            console.log('   - manga-input encontrado:', !!mangaInput);
            console.log('   - manga-input VALUE:', tipoMangaRaw);
            console.log('   - manga-obs encontrado:', !!mangaObs);
            console.log('   - manga-obs VALUE:', obsMangaRaw);
            
            variacionesConfiguradas.tipo_manga = tipoMangaRaw || 'No aplica';
            variacionesConfiguradas.obs_manga = obsMangaRaw || '';
            
            console.log('   ✅ MANGA CAPTURADA:', {
                tipo: variacionesConfiguradas.tipo_manga,
                obs: variacionesConfiguradas.obs_manga
            });
        } else {
            console.log('   ⚠️  Manga NO seleccionada (checkbox desmarcado)');
        }
        
        // 🔍 VALIDACIÓN EXHAUSTIVA: BOLSILLOS
        console.log('🔍 [VARIACIONES CAPTURA] ===== BOLSILLOS =====');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        console.log('   - aplica-bolsillos checkbox encontrado:', !!aplicaBolsillos);
        console.log('   - aplica-bolsillos checkbox.checked:', aplicaBolsillos?.checked);
        
        if (aplicaBolsillos?.checked) {
            variacionesConfiguradas.tiene_bolsillos = true;
            const bolsillosObs = document.getElementById('bolsillos-obs');
            const obsBolsillosRaw = bolsillosObs?.value?.trim();
            
            console.log('   - bolsillos-obs encontrado:', !!bolsillosObs);
            console.log('   - bolsillos-obs VALUE:', obsBolsillosRaw);
            
            variacionesConfiguradas.obs_bolsillos = obsBolsillosRaw || '';
            
            console.log('   ✅ BOLSILLOS CAPTURADOS:', {
                tiene: variacionesConfiguradas.tiene_bolsillos,
                obs: variacionesConfiguradas.obs_bolsillos
            });
        } else {
            console.log('   ⚠️  Bolsillos NO seleccionados (checkbox desmarcado)');
        }
        
        // 🔍 VALIDACIÓN EXHAUSTIVA: BROCHE/BOTÓN
        console.log('🔍 [VARIACIONES CAPTURA] ===== BROCHE/BOTÓN =====');
        const aplicaBroche = document.getElementById('aplica-broche');
        console.log('   - aplica-broche checkbox encontrado:', !!aplicaBroche);
        console.log('   - aplica-broche checkbox.checked:', aplicaBroche?.checked);
        
        if (aplicaBroche?.checked) {
            const brocheInput = document.getElementById('broche-input');
            const brocheObs = document.getElementById('broche-obs');
            const tipoBrocheRaw = brocheInput?.value?.trim();
            const obsBrocheRaw = brocheObs?.value?.trim();
            
            console.log('   - broche-input encontrado:', !!brocheInput);
            console.log('   - broche-input VALUE:', tipoBrocheRaw);
            console.log('   - broche-obs encontrado:', !!brocheObs);
            console.log('   - broche-obs VALUE:', obsBrocheRaw);
            
            variacionesConfiguradas.tipo_broche = tipoBrocheRaw || 'No aplica';
            variacionesConfiguradas.obs_broche = obsBrocheRaw || '';
            
            console.log('   ✅ BROCHE CAPTURADO:', {
                tipo: variacionesConfiguradas.tipo_broche,
                obs: variacionesConfiguradas.obs_broche
            });
        } else {
            console.log('   ⚠️  Broche NO seleccionado (checkbox desmarcado)');
        }
        
        // 🔍 VALIDACIÓN EXHAUSTIVA: REFLECTIVO
        console.log('🔍 [VARIACIONES CAPTURA] ===== REFLECTIVO =====');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');
        console.log('   - aplica-reflectivo checkbox encontrado:', !!aplicaReflectivo);
        console.log('   - aplica-reflectivo checkbox.checked:', aplicaReflectivo?.checked);
        
        if (aplicaReflectivo?.checked) {
            variacionesConfiguradas.tiene_reflectivo = true;
            const reflectivoObs = document.getElementById('reflectivo-obs');
            const obsReflectivoRaw = reflectivoObs?.value?.trim();
            
            console.log('   - reflectivo-obs encontrado:', !!reflectivoObs);
            console.log('   - reflectivo-obs VALUE:', obsReflectivoRaw);
            
            variacionesConfiguradas.obs_reflectivo = obsReflectivoRaw || '';
            
            console.log('   ✅ REFLECTIVO CAPTURADO:', {
                tiene: variacionesConfiguradas.tiene_reflectivo,
                obs: variacionesConfiguradas.obs_reflectivo
            });
        } else {
            console.log('   ⚠️  Reflectivo NO seleccionado (checkbox desmarcado)');
        }
        
        console.log(`🎨 [GestionItemsUI] ===== VARIACIONES CONFIGURADAS FINALES =====`);
        console.log(`🎨 [DETALLE VARIACIONES]:
            - tipo_manga: "${variacionesConfiguradas.tipo_manga}"
            - obs_manga: "${variacionesConfiguradas.obs_manga}"
            - tipo_broche: "${variacionesConfiguradas.tipo_broche}"
            - obs_broche: "${variacionesConfiguradas.obs_broche}"
            - tiene_bolsillos: ${variacionesConfiguradas.tiene_bolsillos}
            - obs_bolsillos: "${variacionesConfiguradas.obs_bolsillos}"
            - tiene_reflectivo: ${variacionesConfiguradas.tiene_reflectivo}
            - obs_reflectivo: "${variacionesConfiguradas.obs_reflectivo}"
        `);
        
        // Obtener cantidades por talla
        const cantidadesPorTalla = window.cantidadesTallas || {};
        console.log(`📊 [GestionItemsUI] Cantidades por talla:`, cantidadesPorTalla);
        
        // ✅ CONSTRUIR generosConTallas CORRECTAMENTE como objeto anidado
        // Estructura: { genero: { talla: cantidad, talla: cantidad } }
        const generosConTallas = {};
        
        tallasPorGenero.forEach(tallaData => {
            const generoKey = tallaData.genero; // 'dama' o 'caballero'
            generosConTallas[generoKey] = {};
            
            // Buscar cantidades para este género en cantidadesPorTalla
            // IMPORTANTE: cantidadesPorTalla tiene formato "genero-talla" (ej: "dama-S")
            if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                tallaData.tallas.forEach(talla => {
                    // Buscar con formato "genero-talla"
                    const key = `${generoKey}-${talla}`;
                    const cantidad = cantidadesPorTalla[key] || 0;
                    console.log(`      Buscando: ${key} = ${cantidad}`);
                    if (cantidad > 0) {
                        generosConTallas[generoKey][talla] = cantidad;
                        console.log(`      ✅ Agregando: ${generoKey}.${talla} = ${cantidad}`);
                    }
                });
            }
        });
        
        console.log(`✅ [GestionItemsUI] generosConTallas construido:`, generosConTallas);
        console.log(`   Estructura: { genero: { talla: cantidad } }`);
        console.log(`   Tipo de generosConTallas:`, typeof generosConTallas);
        console.log(`   Claves:`, Object.keys(generosConTallas));
        Object.keys(generosConTallas).forEach(genero => {
            console.log(`   ${genero}:`, generosConTallas[genero], `tipo:`, typeof generosConTallas[genero]);
        });
        
        // Crear objeto de prenda
        const prendaNueva = {
            nombre_producto: nombrePrenda,
            descripcion: descripcion || '',
            genero: genero,
            origen: origen || 'bodega',
            imagenes: imagenesConUrls, // ✅ Usar las imágenes con blob URLs ya creadas
            telas: [],
            telasAgregadas: telasConUrls, // ✅ Usar las telas con blob URLs ya creadas
            tallas: tallasPorGenero,
            variantes: variacionesConfiguradas,
            procesos: procesosConfigurables,
            cantidadesPorTalla: cantidadesPorTalla,
            generosConTallas: generosConTallas // ✅ INCLUIR ESTRUCTURADO CORRECTAMENTE
        };
        
        console.log('✅ [GestionItemsUI] Prenda nueva creada:', prendaNueva);
        
        // Inicializar gestor de prenda sin cotización si no existe
        if (!window.gestorPrendaSinCotizacion) {
            window.inicializarGestorPrendaSinCotizacion?.();
        }
        
        try {
            console.log('📌 [GestionItemsUI] ===== INICIANDO AGREGACIÓN DE PRENDA =====');
            console.log('📸 [ANTES DE AGREGAR] prendaNueva.imagenes:', prendaNueva.imagenes);
            console.log('📸 [ANTES DE AGREGAR] prendaNueva.imagenes?.length:', prendaNueva.imagenes?.length);
            if (prendaNueva.imagenes?.length > 0) {
                console.log('📸 [ANTES DE AGREGAR] Detalles de imagenes:', prendaNueva.imagenes.map((img, i) => ({
                    index: i,
                    tieneFile: !!img.file,
                    tieneBlobUrl: !!img.blobUrl,
                    blobUrl: img.blobUrl?.substring(0, 50),
                    nombre: img.nombre
                })));
            }
            
            // Agregar a gestor CON los datos creados
            if (window.gestorPrendaSinCotizacion?.agregarPrenda) {
                console.log('✅ [ANTES DE GUARDAR] generosConTallas en prendaNueva:', prendaNueva.generosConTallas);
                
                const indiceAgregado = window.gestorPrendaSinCotizacion.agregarPrenda(prendaNueva);
                console.log('✅ [GestionItemsUI] Prenda agregada al gestor (índice: ' + indiceAgregado + ')');
                console.log('   Total prendas:', window.gestorPrendaSinCotizacion.prendas.length);
                console.log('   Prendas activas:', window.gestorPrendaSinCotizacion.obtenerActivas().length);
                
                // ✅ AGREGAR A GESTOR CENTRALIZADO JSON
                if (window.gestorDatosPedidoJSON) {
                    console.log('💾 [GestionItemsUI] Agregando prenda al gestor centralizado JSON...');
                    
                    // 🔍 VERIFICAR VARIACIONES ANTES DE GUARDAR
                    console.log('🔍 [JSON GESTOR] Variantes extraídas de prendaNueva:', prendaNueva.variantes);
                    console.log('🔍 [JSON GESTOR] Verificación de valores:');
                    console.log('   - tipo_manga:', prendaNueva.variantes?.tipo_manga);
                    console.log('   - obs_manga:', prendaNueva.variantes?.obs_manga);
                    console.log('   - tipo_broche:', prendaNueva.variantes?.tipo_broche);
                    console.log('   - obs_broche:', prendaNueva.variantes?.obs_broche);
                    console.log('   - tiene_bolsillos:', prendaNueva.variantes?.tiene_bolsillos);
                    console.log('   - obs_bolsillos:', prendaNueva.variantes?.obs_bolsillos);
                    console.log('   - tiene_reflectivo:', prendaNueva.variantes?.tiene_reflectivo);
                    console.log('   - obs_reflectivo:', prendaNueva.variantes?.obs_reflectivo);
                    
                    window.gestorDatosPedidoJSON.agregarPrenda({
                        nombre: prendaNueva.nombre_producto,
                        descripcion: prendaNueva.descripcion,
                        origen: prendaNueva.origen,
                        genero: prendaNueva.genero,
                        imagenes: prendaNueva.imagenes || [],
                        telas: prendaNueva.telasAgregadas || [],
                        procesos: window.procesosSeleccionados || {}, // Agregar procesos seleccionados
                        variaciones: {
                            tipo_manga: prendaNueva.variantes?.tipo_manga ?? 'No aplica',
                            obs_manga: prendaNueva.variantes?.obs_manga ?? '',
                            tipo_broche: prendaNueva.variantes?.tipo_broche ?? 'No aplica',
                            obs_broche: prendaNueva.variantes?.obs_broche ?? '',
                            tiene_bolsillos: prendaNueva.variantes?.tiene_bolsillos ?? false,
                            obs_bolsillos: prendaNueva.variantes?.obs_bolsillos ?? '',
                            tiene_reflectivo: prendaNueva.variantes?.tiene_reflectivo ?? false,
                            obs_reflectivo: prendaNueva.variantes?.obs_reflectivo ?? ''
                        },
                        cantidades: prendaNueva.cantidadesPorTalla || {}
                    });
                    
                    console.log('✅ [GestionItemsUI] Prenda agregada al gestor JSON centralizado');
                    console.log('📊 Datos JSON actuales:', window.gestorDatosPedidoJSON.obtenerJSON());
                }
                
                // Verificación: obtener la prenda que se acaba de guardar
                const prendaGuardada = window.gestorPrendaSinCotizacion.obtenerPorIndice(indiceAgregado);
                console.log('📸 [VERIFICACIÓN GESTOR] Prenda guardada tiene imagenes:', prendaGuardada?.imagenes);
                console.log('📸 [VERIFICACIÓN GESTOR] imagenes?.length:', prendaGuardada?.imagenes?.length);
                console.log('📸 [VERIFICACIÓN GESTOR] generosConTallas guardado:', prendaGuardada?.generosConTallas);
            } else {
                console.error('❌ [GestionItemsUI] GestorPrendaSinCotizacion no disponible');
                return;
            }
            
            // ✅ CRÍTICO: Renderizar UI ANTES de cerrar modal y limpiar procesos
            console.log('🔍 [GestionItemsUI] Verificando función de renderizado...');
            console.log('   Tipo:', typeof window.renderizarPrendasTipoPrendaSinCotizacion);
            console.log('   Es función:', window.renderizarPrendasTipoPrendaSinCotizacion instanceof Function);
            
            // ✅ USAR NUEVO COMPONENTE DE TARJETA READONLY
            console.log('🎨 [GestionItemsUI] Inicializando renderizado de tarjetas readonly...');
            
            const container = document.getElementById('prendas-container-editable');
            if (!container) {
                console.error('❌ [GestionItemsUI] Container prendas-container-editable no encontrado');
                return;
            }
            
            const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
            
            if (prendas.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="text-align: center; padding: 2rem; color: #9ca3af;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        <p>No hay ítems agregados. Selecciona un tipo de pedido para agregar nuevos ítems.</p>
                    </div>
                `;
            } else {
                if (typeof window.generarTarjetaPrendaReadOnly !== 'function') {
                    console.error('❌ [GestionItemsUI] generarTarjetaPrendaReadOnly NO ESTÁ CARGADO');
                    console.error('   Verifica que prenda-card-readonly.js esté incluido en el HTML');
                    return;
                }
                
                let html = '';
                prendas.forEach((prenda, indice) => {
                    html += window.generarTarjetaPrendaReadOnly(prenda, indice);
                });
                container.innerHTML = html;
            }
            
            console.log('✅ [GestionItemsUI] UI renderizada correctamente con tarjetas readonly');
            console.log('📺 [GestionItemsUI] Sección de ítems actualizada con prendas');
            
            // Verificar renderizado
            setTimeout(() => {
                const tarjetas = container?.querySelectorAll('.prenda-card-readonly');
                console.log('📊 [GestionItemsUI] Verificación post-renderizado:');
                console.log('   Container existe:', !!container);
                console.log('   Tarjetas readonly en DOM:', tarjetas?.length || 0);
            }, 100);
            
            // ✅ AHORA SÍ: Cerrar modal Y limpiar procesos (DESPUÉS de renderizar)
            cerrarModalPrendaNueva();
            console.log('✅ [GestionItemsUI] Modal cerrado y procesos limpiados');
            console.log('📌 [GestionItemsUI] ===== AGREGACIÓN COMPLETADA =====\n');
            
            // Mostrar notificación
            this.mostrarNotificacion('Prenda agregada correctamente', 'success');
        } catch (error) {
            console.error('❌ [GestionItemsUI] Error al agregar prenda:', error);
            console.error('   Mensaje:', error.message);
            console.error('   Stack:', error.stack);
            this.mostrarNotificacion('Error al agregar prenda: ' + error.message, 'error');
        }
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            // Validación local del cliente
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.mostrarNotificacion('El cliente es requerido', 'error');
                clienteInput?.focus();
                return;
            }

            // Recolectar datos del formulario
            const pedidoData = this.recolectarDatosPedido();
            
            // 🔍 LOG CRÍTICO: Verificar estructura antes de enviar
            console.log('📋 [manejarSubmitFormulario] Datos del pedido recolectados:');
            console.log('   Cliente:', pedidoData.cliente);
            console.log('   Asesora:', pedidoData.asesora);
            console.log('   Forma de pago:', pedidoData.forma_de_pago);
            console.log('   Items totales:', pedidoData.items.length);
            
            // Verificar que cada ítem tenga los campos requeridos
            pedidoData.items.forEach((item, idx) => {
                console.log(`   ✓ Ítem ${idx}:`, {
                    tipo: item.tipo,
                    prenda: item.prenda,
                    origen: item.origen,
                    has_tallas: !!((item.tallas && item.tallas.length > 0) || (item.cantidad_talla && Object.keys(item.cantidad_talla).length > 0)),
                    // 'numero_pedido': 'NO REQUERIDO (generado en backend)', // ❌ COMENTADO
                    // 'pedido_produccion_id': 'SERÁ ASIGNADO EN BACKEND', // ✅ FK requerida
                });
            });

            // Validar que haya items
            if (!pedidoData.items || pedidoData.items.length === 0) {
                this.mostrarNotificacion('Debe agregar al menos un item al pedido', 'error');
                return;
            }

            // Validar pedido
            const validacion = await this.api.validarPedido(pedidoData);
            
            if (!validacion.valid) {
                const errores = validacion.errores.join('\n');
                alert('Errores en el pedido:\n' + errores);
                return;
            }

            console.log('✅ [manejarSubmitFormulario] Validación pasada, enviando pedido...');

            // Crear pedido
            const resultado = await this.api.crearPedido(pedidoData);

            if (resultado.success) {
                console.log('✅ [manejarSubmitFormulario] PEDIDO CREADO EXITOSAMENTE', {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido,
                });
                // Mostrar modal en lugar de notificación
                setTimeout(() => {
                    this.mostrarModalExito();
                }, 500);
            }
        } catch (error) {
            console.error('❌ [manejarSubmitFormulario] ERROR:', error);
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    recolectarDatosPedido() {
        const items = window.itemsPedido || [];
        
        // 🔍 LOG: Verificar items antes de procesar
        console.log('🔎 [recolectarDatosPedido] Items totales recibidos:', items.length);
        
        // Convertir items al formato esperado por el backend
        const itemsFormato = items.map((item, itemIndex) => {
            const baseItem = {
                tipo: item.tipo,
                prenda: item.prenda?.nombre || item.nombre || '',
                origen: item.origen || 'bodega',
                procesos: item.procesos || [],
                tallas: item.tallas || [],
                variaciones: item.variaciones || {},
            };
            
            // 🔍 LOG: Verificar pedido_produccion_id si existe
            if (item.pedido_produccion_id) {
                baseItem.pedido_produccion_id = item.pedido_produccion_id;
                console.log(`✅ [Item ${itemIndex}] Incluido pedido_produccion_id: ${item.pedido_produccion_id}`);
            }
            
            // Si tiene imagenes, incluirlas
            if (item.imagenes && item.imagenes.length > 0) {
                baseItem.imagenes = item.imagenes;
                console.log(`📸 [Item ${itemIndex}] Imágenes: ${item.imagenes.length}`);
            }
            
            // Si es cotizacion, incluir datos de cotizacion
            if (item.tipo === 'cotizacion') {
                baseItem.cotizacion_id = item.id;
                baseItem.numero_cotizacion = item.numero;
                baseItem.cliente = item.cliente;
            }
            
            return baseItem;
        });
        
        // ✅ AGREGAR PRENDAS SIN COTIZACIÓN (gestores)
        // Verificar si hay prendas sin cotización del tipo PRENDA
        if (window.gestorPrendaSinCotizacion && window.gestorPrendaSinCotizacion.obtenerActivas().length > 0) {
            console.log('🔄 Integrando prendas sin cotización (tipo PRENDA)...');
            const prendasSinCot = window.gestorPrendaSinCotizacion.obtenerActivas();
            
            prendasSinCot.forEach((prenda, prendaIndex) => {
                console.log(`🔍 Procesando prenda sin cotización ${prendaIndex}:`, prenda);
                // Construir cantidad_talla desde generosConTallas
                const cantidadTalla = {};
                
                if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
                    console.log(`📊 Usando generosConTallas:`, prenda.generosConTallas);
                    console.log(`📊 Claves de generosConTallas:`, Object.keys(prenda.generosConTallas));
                    
                    // Iterate over each gender's tallas
                    Object.keys(prenda.generosConTallas).forEach(genero => {
                        console.log(`   Procesando género: "${genero}"`);
                        const tallaDelGenero = prenda.generosConTallas[genero];
                        console.log(`   tallaDelGenero:`, tallaDelGenero);
                        console.log(`   Claves de tallaDelGenero:`, Object.keys(tallaDelGenero));
                        
                        Object.keys(tallaDelGenero).forEach(talla => {
                            const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                            console.log(`      Talla "${talla}": cantidad = ${cantidad}`);
                            if (cantidad > 0) {
                                // ✅ INCLUIR GÉNERO EN LA CLAVE (dama-S, dama-M, etc)
                                const key = `${genero}-${talla}`;
                                cantidadTalla[key] = cantidad;
                                console.log(`      ✅ Agregando: ${key} = ${cantidad}`);
                            }
                        });
                    });
                    console.log(`📊 cantidadTalla final:`, cantidadTalla);
                } else if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
                    console.log(`📊 Usando cantidadesPorTalla:`, prenda.cantidadesPorTalla);
                    // Fallback: usar cantidadesPorTalla si existe
                    // Obtener el primer género disponible para construir las claves
                    const generoFallback = prenda.genero && Array.isArray(prenda.genero) && prenda.genero.length > 0 
                        ? prenda.genero[0] 
                        : 'mixto';
                    
                    Object.keys(prenda.cantidadesPorTalla).forEach(talla => {
                        const cantidad = parseInt(prenda.cantidadesPorTalla[talla]) || 0;
                        if (cantidad > 0) {
                            // ✅ INCLUIR GÉNERO EN LA CLAVE
                            cantidadTalla[`${generoFallback}-${talla}`] = cantidad;
                        }
                    });
                }
                
                // ✅ CONSTRUIR VARIACIONES DESDE LA FUENTE CORRECTA (prenda.variantes)
                // prenda.variantes contiene: {tipo_manga, obs_manga, tipo_broche, obs_broche, tiene_bolsillos, obs_bolsillos, tiene_reflectivo, obs_reflectivo}
                
                console.log(`🔍 [VARIACIONES] Procesando prenda ${prendaIndex}:`, {
                    tieneVariantes: !!prenda.variantes,
                    varianteKeys: Object.keys(prenda.variantes || {}),
                    variantes: prenda.variantes
                });
                
                // 🔹 EXTRAER VALORES DIRECTOS DE prenda.variantes
                // Usar ?? para diferenciar entre undefined/null y false/0/''
                const tipoMangaRaw = prenda.variantes?.tipo_manga ?? 'No aplica';
                const obsMangaRaw = prenda.variantes?.obs_manga ?? '';
                const tieneBolsillosRaw = prenda.variantes?.tiene_bolsillos ?? false;
                const obsBolsillosRaw = prenda.variantes?.obs_bolsillos ?? '';
                const tipoBrocheRaw = prenda.variantes?.tipo_broche ?? 'No aplica';
                const obsBrocheRaw = prenda.variantes?.obs_broche ?? '';
                const tieneReflectivoRaw = prenda.variantes?.tiene_reflectivo ?? false;
                const obsReflectivoRaw = prenda.variantes?.obs_reflectivo ?? '';
                
                console.log(`✅ [VARIACIONES DEBUG] Valores extraídos de prenda.variantes:`, {
                    tipo_manga: tipoMangaRaw,
                    obs_manga: obsMangaRaw,
                    tipo_broche: tipoBrocheRaw,
                    obs_broche: obsBrocheRaw,
                    tiene_bolsillos: tieneBolsillosRaw,
                    obs_bolsillos: obsBolsillosRaw,
                    tiene_reflectivo: tieneReflectivoRaw,
                    obs_reflectivo: obsReflectivoRaw
                });
                
                // 🔹 VALIDAR: No permitir sobrescritura accidental con valores por defecto
                // Si el usuario NO seleccionó manga, tipo_manga será 'No aplica'
                // Si el usuario SÍ seleccionó manga, tipo_manga tendrá el valor real (ej: "ret", "corta", etc)
                const tipoManga = tipoMangaRaw === 'No aplica' ? 'No aplica' : (tipoMangaRaw || 'No aplica');
                const obsManga = obsMangaRaw?.trim?.() || '';
                const tieneBolsillos = tieneBolsillosRaw === true; // ✅ Validar que es exactamente true
                const obsBolsillos = obsBolsillosRaw?.trim?.() || '';
                const tipoBroche = tipoBrocheRaw === 'No aplica' ? 'No aplica' : (tipoBrocheRaw || 'No aplica');
                const obsBroche = obsBrocheRaw?.trim?.() || '';
                const tieneReflectivo = tieneReflectivoRaw === true; // ✅ Validar que es exactamente true
                const obsReflectivo = obsReflectivoRaw?.trim?.() || '';
                
                console.log(`✅ [VARIACIONES VALIDADAS] Prenda ${prendaIndex}:`, {
                    tipo_manga: tipoManga,
                    obs_manga: obsManga,
                    tipo_broche: tipoBroche,
                    obs_broche: obsBroche,
                    tiene_bolsillos: tieneBolsillos,
                    obs_bolsillos: obsBolsillos,
                    tiene_reflectivo: tieneReflectivo,
                    obs_reflectivo: obsReflectivo
                });
                
                // Construir objeto de variaciones para el backend
                const variaciones = {
                    manga: {
                        tipo: tipoManga,
                        observacion: obsManga
                    },
                    bolsillos: {
                        tiene: tieneBolsillos,
                        observacion: obsBolsillos
                    },
                    broche: {
                        tipo: tipoBroche,
                        observacion: obsBroche
                    },
                    reflectivo: {
                        tiene: tieneReflectivo,
                        observacion: obsReflectivo
                    }
                };
                
                console.log(`📤 [VARIACIONES JSON] Objeto final para backend:`, variaciones);
                
                // ✅ EXTRAER OBSERVACIONES para enviar al backend
                // El backend espera estos campos al nivel superior del objeto
                const obs_manga = obsManga;
                const obs_bolsillos = obsBolsillos;
                const obs_broche = obsBroche;
                const obs_reflectivo = obsReflectivo;
                
                // ✅ Convertir cantidad_talla a tallas array para validación backend
                const tallas = Object.keys(cantidadTalla);
                console.log(`✅ Prenda ${prendaIndex}: cantidadTalla =`, cantidadTalla, ', tallas =', tallas);

                // ✅ EXTRAER PROCESOS DEL NIVEL INTERNO
                // Los procesos se guardan como {tipo: {tipo, datos: {...}}}
                // Pero el backend espera {tipo: {tipo, ubicaciones, observaciones, tallas_dama, tallas_caballero}}
                // Entonces extraemos .datos si existe
                let procesosParaEnviar = {};
                if (prenda.procesos && typeof prenda.procesos === 'object') {
                    Object.entries(prenda.procesos).forEach(([tipoProceso, procesoData]) => {
                        // Si existe .datos, usar eso; si no, usar todo
                        if (procesoData?.datos) {
                            procesosParaEnviar[tipoProceso] = procesoData.datos;
                        } else {
                            procesosParaEnviar[tipoProceso] = procesoData;
                        }
                    });
                }
                console.log(`✅ Procesos preparados para envío:`, procesosParaEnviar);
                
                const itemSinCot = {
                    tipo: 'prenda_nueva',
                    prenda: prenda.nombre_producto || '',
                    descripcion: prenda.descripcion || '',
                    genero: prenda.genero || [],
                    cantidad_talla: cantidadTalla,
                    tallas: tallas, // ✅ REQUERIDO por validación backend
                    variaciones: variaciones,
                    // ✅ OBSERVACIONES AL NIVEL SUPERIOR
                    obs_manga: obs_manga,
                    obs_bolsillos: obs_bolsillos,
                    obs_broche: obs_broche,
                    obs_reflectivo: obs_reflectivo,
                    origen: prenda.origen || 'bodega', // ✅ USAR ORIGEN DEL GESTOR
                    de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1, // ✅ PASAR de_bodega
                    // ✅ CRÍTICO: INCLUIR IDs DE BASE DE DATOS y NOMBRES PARA CREAR
                    color: prenda.color || null,
                    tela: prenda.tela || null,
                    color_id: prenda.color_id || null,
                    tela_id: prenda.tela_id || null,
                    tipo_manga_id: prenda.tipo_manga_id || null,
                    tipo_broche_boton_id: prenda.tipo_broche_boton_id || null,
                    // ✅ INCLUIR NOMBRES PARA CREAR SI NO EXISTEN
                    manga: tipoManga !== 'No aplica' ? tipoManga : null,
                    broche: tipoBroche !== 'No aplica' ? tipoBroche : null,
                    // ✅ CRÍTICO: INCLUIR PROCESOS CON TALLAS Y DETALLES
                    procesos: procesosParaEnviar
                };
                
                // Agregar fotos si existen
                // Primero verificar en fotosNuevas (fotos recién agregadas)
                let fotosParaEnviar = [];
                if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
                    fotosParaEnviar = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
                    console.log(`📸 Fotos encontradas para prenda ${prendaIndex}:`, fotosParaEnviar.length);
                }
                // Si no hay en fotosNuevas, verificar en prenda.fotos
                else if (prenda.fotos && prenda.fotos.length > 0) {
                    fotosParaEnviar = prenda.fotos;
                    console.log(`📸 Fotos encontradas en prenda.fotos:`, fotosParaEnviar.length);
                }
                
                if (fotosParaEnviar.length > 0) {
                    itemSinCot.imagenes = fotosParaEnviar;
                }
                
                // Agregar telas si existen
                if (prenda.telas && prenda.telas.length > 0) {
                    itemSinCot.telas = prenda.telas;
                    // ✅ Usar la primera tela para color_id y tela_id
                    if (prenda.telas[0]) {
                        if (prenda.telas[0].color) {
                            itemSinCot.color = prenda.telas[0].color;
                        }
                        if (prenda.telas[0].nombre_tela) {
                            itemSinCot.tela = prenda.telas[0].nombre_tela;
                        }
                    }
                    console.log(`🧵 Telas encontradas:`, prenda.telas.length);
                }
                
                // Agregar fotos de telas si existen
                if (window.gestorPrendaSinCotizacion?.telasFotosNuevas?.[prendaIndex]) {
                    itemSinCot.telasFotos = window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex];
                    console.log(`📷 Fotos de telas encontradas:`, Object.keys(itemSinCot.telasFotos).length);
                }
                
                itemsFormato.push(itemSinCot);
                console.log('✅ Prenda sin cotización agregada:', itemSinCot);
            });
        }
        
        console.log('📦 Items para enviar:', itemsFormato);
        
        // 🔍 LOG CRÍTICO: Verificar que cada ítem tiene pedido_produccion_id si es necesario
        // ACTUALIZACIÓN [16/01/2026]: El backend ahora requiere pedido_produccion_id
        console.log('🔎 [recolectarDatosPedido] VERIFICACIÓN FINAL:');
        itemsFormato.forEach((item, idx) => {
            const tieneId = !!item.pedido_produccion_id;
            const tieneTallas = (item.tallas && item.tallas.length > 0) || (item.cantidad_talla && Object.keys(item.cantidad_talla).length > 0);
            const tienePrenda = !!item.prenda;
            console.log(`  ✓ Ítem ${idx}: prenda="${item.prenda}", tiene_id=${tieneId}, tiene_tallas=${tieneTallas}`);
            
            // 🔍 VERIFICACIÓN EXHAUSTIVA DE VARIACIONES
            if (item.variaciones) {
                console.log(`  🎨 [VARIACIONES ÍTEM ${idx}]:`, {
                    manga: {
                        tipo: item.variaciones.manga?.tipo,
                        observacion: item.variaciones.manga?.observacion
                    },
                    bolsillos: {
                        tiene: item.variaciones.bolsillos?.tiene,
                        observacion: item.variaciones.bolsillos?.observacion
                    },
                    broche: {
                        tipo: item.variaciones.broche?.tipo,
                        observacion: item.variaciones.broche?.observacion
                    },
                    reflectivo: {
                        tiene: item.variaciones.reflectivo?.tiene,
                        observacion: item.variaciones.reflectivo?.observacion
                    }
                });
            }
        });
        
        const pedidoFinal = {
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            items: itemsFormato,
            // 'numero_pedido': null, // ❌ COMENTADO [16/01/2026]: Se genera en el backend automáticamente
        };
        
        console.log('📤 Objeto pedido final a enviar:', pedidoFinal);
        
        // 🔍 LOG CRÍTICO: Confirmar que las variaciones NO son valores por defecto
        console.log('🔍 [VARIACIONES - ANTES DE ENVIAR] VALIDACIÓN EXHAUSTIVA:');
        let variacionesValidas = true;
        itemsFormato.forEach((item, idx) => {
            if (item.variaciones) {
                const manga = item.variaciones.manga;
                const bolsillos = item.variaciones.bolsillos;
                const broche = item.variaciones.broche;
                const reflectivo = item.variaciones.reflectivo;
                
                // Verificar que NO son todos valores por defecto
                const esDefaultManga = manga?.tipo === 'No aplica' && manga?.observacion === '';
                const esDefaultBolsillos = bolsillos?.tiene === false && bolsillos?.observacion === '';
                const esDefaultBroche = broche?.tipo === 'No aplica' && broche?.observacion === '';
                const esDefaultReflectivo = reflectivo?.tiene === false && reflectivo?.observacion === '';
                
                console.log(`  Ítem ${idx} (${item.prenda}):`);
                console.log(`    - Manga: tipo="${manga?.tipo}" (esDefault=${esDefaultManga})`);
                console.log(`    - Bolsillos: tiene=${bolsillos?.tiene} obs="${bolsillos?.observacion}" (esDefault=${esDefaultBolsillos})`);
                console.log(`    - Broche: tipo="${broche?.tipo}" (esDefault=${esDefaultBroche})`);
                console.log(`    - Reflectivo: tiene=${reflectivo?.tiene} obs="${reflectivo?.observacion}" (esDefault=${esDefaultReflectivo})`);
                
                if (esDefaultManga && esDefaultBolsillos && esDefaultBroche && esDefaultReflectivo) {
                    console.warn(`  ⚠️  ADVERTENCIA: Ítem ${idx} tiene TODAS las variaciones con valores por defecto`);
                    variacionesValidas = false;
                }
            }
        });
        
        if (variacionesValidas) {
            console.log('✅ [VARIACIONES] Validación exitosa: contienen valores del usuario, no defaults');
        } else {
            console.warn('⚠️  [VARIACIONES] Se detectaron ítems con valores por defecto - verifique si esto es intencional');
        }
        
        return pedidoFinal;
    }

    /**
     * Actualizar una prenda existente
     * Similar a agregarPrendaNueva() pero reemplaza los datos en lugar de agregar nuevos
     */
    actualizarPrendaExistente() {
        const prendaIndex = this.prendaEditIndex;
        console.log('📝 [GestionItemsUI] actualizarPrendaExistente() - actualizando prenda índice:', prendaIndex);
        
        // Recopilar datos del formulario (mismo código que agregarPrendaNueva)
        const nombrePrenda = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const origen = document.getElementById('nueva-prenda-origen-select')?.value;
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
        
        let genero = null;
        const tallasSeleccionadas = window.tallasSeleccionadas || {};
        
        const tienetallasDama = tallasSeleccionadas.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas.caballero?.tallas?.length > 0;
        
        if (tienetallasDama && !tieneTallasCaballero) {
            genero = 'dama';
        } else if (tieneTallasCaballero && !tienetallasDama) {
            genero = 'caballero';
        } else if (tienetallasDama && tieneTallasCaballero) {
            genero = 'unisex';
        }
        
        if (!nombrePrenda || !genero) {
            alert('Por favor completa los campos requeridos');
            return;
        }
        
        // Obtener imágenes, telas, variaciones, etc. (mismo proceso que agregarPrendaNueva)
        const imagenesPrenda = window.imagenesPrendaStorage?.obtenerImagenes() || [];
        const imagenesConUrls = imagenesPrenda.map(img => {
            let blobUrl = null;
            if (img.file instanceof File) {
                blobUrl = URL.createObjectURL(img.file);
            }
            return {
                file: img.file,
                nombre: img.nombre,
                tamaño: img.tamaño,
                blobUrl: blobUrl
            };
        });
        
        let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
        procesosConfigurables = Object.keys(procesosConfigurables).reduce((acc, tipoProceso) => {
            const proceso = procesosConfigurables[tipoProceso];
            if (proceso && (proceso.datos !== null || proceso.tipo)) {
                acc[tipoProceso] = proceso;
            }
            return acc;
        }, {});
        
        const tallasPorGenero = [];
        if (tienetallasDama) {
            tallasPorGenero.push({
                genero: 'dama',
                tallas: tallasSeleccionadas.dama.tallas,
                tipo: tallasSeleccionadas.dama.tipo
            });
        }
        if (tieneTallasCaballero) {
            tallasPorGenero.push({
                genero: 'caballero',
                tallas: tallasSeleccionadas.caballero.tallas,
                tipo: tallasSeleccionadas.caballero.tipo
            });
        }
        
        const telasAgregadas = window.telasAgregadas || [];
        const telasConUrls = telasAgregadas.map(tela => ({
            ...tela,
            imagenes: (tela.imagenes || []).map(img => {
                let blobUrl = null;
                if (img.file instanceof File) {
                    blobUrl = URL.createObjectURL(img.file);
                }
                return {
                    ...img,
                    blobUrl: blobUrl
                };
            })
        }));
        
        // Variaciones
        const variacionesConfiguradas = {
            tipo_manga: 'No aplica',
            obs_manga: '',
            tipo_broche: 'No aplica',
            obs_broche: '',
            tiene_bolsillos: false,
            obs_bolsillos: '',
            tiene_reflectivo: false,
            obs_reflectivo: ''
        };
        
        const plicaManga = document.getElementById('aplica-manga');
        if (plicaManga?.checked) {
            const mangaInput = document.getElementById('manga-input');
            const mangaObs = document.getElementById('manga-obs');
            variacionesConfiguradas.tipo_manga = mangaInput?.value?.trim() || 'No aplica';
            variacionesConfiguradas.obs_manga = mangaObs?.value?.trim() || '';
        }
        
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        if (aplicaBolsillos?.checked) {
            variacionesConfiguradas.tiene_bolsillos = true;
            const bolsillosObs = document.getElementById('bolsillos-obs');
            variacionesConfiguradas.obs_bolsillos = bolsillosObs?.value?.trim() || '';
        }
        
        const aplicaBroche = document.getElementById('aplica-broche');
        if (aplicaBroche?.checked) {
            const brocheInput = document.getElementById('broche-input');
            variacionesConfiguradas.tipo_broche = brocheInput?.value?.trim() || 'No aplica';
            const brocheObs = document.getElementById('broche-obs');
            variacionesConfiguradas.obs_broche = brocheObs?.value?.trim() || '';
        }
        
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');
        if (aplicaReflectivo?.checked) {
            variacionesConfiguradas.tiene_reflectivo = true;
            const reflectivoObs = document.getElementById('reflectivo-obs');
            variacionesConfiguradas.obs_reflectivo = reflectivoObs?.value?.trim() || '';
        }
        
        const cantidadesPorTalla = window.cantidadesTallas || {};
        
        // Crear objeto actualizado
        const prendaActualizada = {
            nombre_producto: nombrePrenda,
            descripcion: descripcion || '',
            genero: genero,
            origen: origen || 'bodega',
            imagenes: imagenesConUrls,
            telas: [],
            telasAgregadas: telasConUrls,
            tallas: tallasPorGenero,
            variantes: variacionesConfiguradas,
            procesos: procesosConfigurables,
            cantidadesPorTalla: cantidadesPorTalla
        };
        
        console.log('📝 [GestionItemsUI] Prenda actualizada:', prendaActualizada);
        
        try {
            // Actualizar en el gestor
            if (window.gestorPrendaSinCotizacion?.actualizarPrenda) {
                window.gestorPrendaSinCotizacion.actualizarPrenda(prendaIndex, prendaActualizada);
                console.log('✅ [GestionItemsUI] Prenda actualizada en el gestor (índice: ' + prendaIndex + ')');
            } else {
                console.error('❌ [GestionItemsUI] Método actualizarPrenda no disponible en gestor');
                return;
            }
            
            // Re-renderizar
            const container = document.getElementById('prendas-container-editable');
            if (!container) {
                console.error('❌ [GestionItemsUI] Container no encontrado');
                return;
            }
            
            const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
            let html = '';
            prendas.forEach((prenda, indice) => {
                html += window.generarTarjetaPrendaReadOnly(prenda, indice);
            });
            container.innerHTML = html;
            
            console.log('✅ [GestionItemsUI] UI re-renderizada después de actualización');
            
            // Limpiar índice de edición
            this.prendaEditIndex = null;
            
            // Cerrar modal y limpiar
            cerrarModalPrendaNueva();
            
            this.mostrarNotificacion('Prenda actualizada correctamente', 'success');
            console.log('📌 [GestionItemsUI] ===== ACTUALIZACIÓN COMPLETADA =====\n');
            
        } catch (error) {
            console.error('❌ [GestionItemsUI] Error al actualizar prenda:', error);
            this.mostrarNotificacion('Error al actualizar prenda: ' + error.message, 'error');
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        const clase = tipo === 'error' ? 'alert-danger' : tipo === 'success' ? 'alert-success' : 'alert-info';
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert ${clase}`;
        notificacion.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 6px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notificacion.textContent = mensaje;

        document.body.appendChild(notificacion);

        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }

    mostrarVistaPreviaFactura() {
        // Crear modal
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
        
        const contenedor = document.createElement('div');
        contenedor.style.cssText = 'background: white; border-radius: 12px; width: 90%; max-width: 1000px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
        
        // Header
        const header = document.createElement('div');
        header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 2rem; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;';
        
        const titulo = document.createElement('h2');
        titulo.textContent = '📋 Vista Previa del Pedido';
        titulo.style.cssText = 'margin: 0; font-size: 1.5rem;';
        header.appendChild(titulo);
        
        const btnCerrar = document.createElement('button');
        btnCerrar.innerHTML = '✕';
        btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; padding: 0.75rem 1.25rem; cursor: pointer; font-size: 1.5rem; font-weight: bold;';
        btnCerrar.onclick = () => modal.remove();
        header.appendChild(btnCerrar);
        
        contenedor.appendChild(header);
        
        // Contenido
        const contenido = document.createElement('div');
        contenido.style.cssText = 'padding: 2rem;';
        
        // Información del pedido
        const infoPedido = document.createElement('div');
        infoPedido.style.cssText = 'background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #0066cc;';
        
        const cliente = document.getElementById('cliente_editable')?.value || 'No especificado';
        const asesora = document.getElementById('asesora_editable')?.value || 'No especificado';
        const forma = document.getElementById('forma_de_pago_editable')?.value || 'No especificado';
        
        infoPedido.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Cliente</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${cliente}</p>
                </div>
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Asesora</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${asesora}</p>
                </div>
                <div>
                    <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Forma de Pago</p>
                    <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">${forma}</p>
                </div>
            </div>
        `;
        
        contenido.appendChild(infoPedido);
        
        // Ítems
        const tituloItems = document.createElement('h3');
        tituloItems.textContent = 'Ítems del Pedido';
        tituloItems.style.cssText = 'color: #1f2937; font-size: 1.25rem; margin: 0 0 1.5rem 0; padding-bottom: 0.75rem; border-bottom: 2px solid #0066cc;';
        contenido.appendChild(tituloItems);
        
        if (window.itemsPedido && window.itemsPedido.length > 0) {
            const itemsContainer = document.createElement('div');
            itemsContainer.style.cssText = 'display: grid; grid-template-columns: 1fr; gap: 1rem;';
            
            window.itemsPedido.forEach((item, idx) => {
                const itemDiv = document.createElement('div');
                itemDiv.style.cssText = 'background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;';
                
                let tallasTex = '';
                if (item.tallas && Array.isArray(item.tallas)) {
                    const tallasPorGenero = {};
                    item.tallas.forEach(t => {
                        const genero = t.genero || 'sin-genero';
                        if (!tallasPorGenero[genero]) tallasPorGenero[genero] = [];
                        tallasPorGenero[genero].push(`${t.talla}: ${t.cantidad}`);
                    });
                    const generoArray = [];
                    Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
                        if (genero !== 'sin-genero') {
                            generoArray.push(`<strong>${genero.toUpperCase()}:</strong> ${tallas.join(', ')}`);
                        } else {
                            generoArray.push(tallas.join(', '));
                        }
                    });
                    tallasTex = generoArray.join(' | ');
                }
                
                itemDiv.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 1.15rem;">${idx + 1}. ${item.prenda?.nombre || 'Prenda'}</h4>
                            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                <strong>Origen:</strong> ${item.origen === 'bodega' ? '🏭 BODEGA' : '🪡 CONFECCIÓN'}
                            </p>
                            ${item.procesos?.length > 0 ? `
                                <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                    <strong>Procesos:</strong> ${item.procesos.join(', ')}
                                </p>
                            ` : ''}
                            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                                <strong>Tallas:</strong> ${tallasTex}
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div style="background: #fef3c7; color: #92400e; padding: 0.75rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 1.1rem;">
                                📦 ${item.tallas?.reduce((sum, t) => sum + t.cantidad, 0) || 0} unidades
                            </div>
                        </div>
                    </div>
                `;
                
                itemsContainer.appendChild(itemDiv);
            });
            
            contenido.appendChild(itemsContainer);
        } else {
            const vacio = document.createElement('p');
            vacio.textContent = 'No hay ítems agregados';
            vacio.style.cssText = 'color: #6b7280; text-align: center; padding: 2rem;';
            contenido.appendChild(vacio);
        }
        
        // Botón de acción
        const footer = document.createElement('div');
        footer.style.cssText = 'padding: 2rem; display: flex; justify-content: space-between; gap: 1rem; border-top: 1px solid #e5e7eb;';
        
        const btnImpreso = document.createElement('button');
        btnImpreso.textContent = '🖨️ Imprimir';
        btnImpreso.style.cssText = 'background: #6366f1; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; font-size: 1rem;';
        btnImpreso.onclick = () => window.print();
        footer.appendChild(btnImpreso);
        
        const btnContinuar = document.createElement('button');
        btnContinuar.textContent = '✓ Continuar y Crear Pedido';
        btnContinuar.style.cssText = 'background: #10b981; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; font-size: 1rem;';
        btnContinuar.onclick = () => {
            modal.remove();
            document.getElementById('formCrearPedidoEditable')?.submit();
        };
        footer.appendChild(btnContinuar);
        
        contenedor.appendChild(contenido);
        contenedor.appendChild(footer);
        
        modal.appendChild(contenedor);
        document.body.appendChild(modal);
        
        // Cerrar al hacer click fuera
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };
    }

    mostrarModalExito() {
        console.log('📋 [mostrarModalExito] Iniciando...');
        
        // Obtener el modal del DOM
        const modal = document.getElementById('modalExitoPedido');
        
        if (!modal) {
            console.warn('⚠️ Modal no encontrado en el DOM, redirigiendo...');
            setTimeout(() => {
                window.location.href = '/asesores/pedidos';
            }, 1000);
            return;
        }

        console.log('✅ Modal encontrado en el DOM');

        // Usar Bootstrap's Modal API
        try {
            // Bootstrap 5+
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                console.log('📦 Usando Bootstrap 5+');
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } 
            // Bootstrap 4 con jQuery
            else if (typeof $ !== 'undefined' && $.fn.modal) {
                console.log('📦 Usando Bootstrap 4 + jQuery');
                $(modal).modal('show');
            } 
            // Fallback manual
            else {
                console.warn('⚠️ Bootstrap no disponible, mostrando modal manualmente...');
                modal.style.display = 'block';
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                
                // Agregar backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                
                // Permitir cerrar con escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                        modal.setAttribute('aria-hidden', 'true');
                        backdrop.remove();
                    }
                });
            }
            console.log('✅ Modal mostrado correctamente');
        } catch (error) {
            console.error('❌ Error al mostrar modal:', error);
            // Si hay error, redirigir directamente
            setTimeout(() => {
                window.location.href = '/asesores/pedidos';
            }, 1000);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionItemsUI = new GestionItemsUI();
});


