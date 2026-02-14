/**
 * MODAL CLEANUP - Gestor de limpieza de modales y formularios
 * 
 * Centraliza toda la lógica de limpieza de inputs, storages y estados
 * para mantener los modales limpios entre usos
 * 
 * @module ModalCleanup
 */

class ModalCleanup {
    /**
     * Limpiar completo: inputs, checkboxes, storages y estados
     */
    static limpiarTodo() {
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes();
        this.limpiarProcesos();
        this.limpiarContenedores();
    }

    /**
     * Limpiar todos los inputs y textareas del modal de prenda
     * PROTECCIÓN: No limpiar si el usuario está escribiendo en un input
     */
    static limpiarFormulario() {
        // PROTECCIÓN: Verificar si hay un elemento con focus
        const elementoEnFoco = document.activeElement;
        const idsInputs = [
            'nueva-prenda-nombre',
            'nueva-prenda-descripcion',
            'nueva-prenda-origen-select',
            'manga-input',
            'manga-obs',
            'bolsillos-obs',
            'broche-input',
            'broche-obs',
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia',
            'nueva-prenda-foto-contador'
        ];

        // Si el usuario está escribiendo en algún input, no limpiar
        if (elementoEnFoco && idsInputs.includes(elementoEnFoco.id)) {

            return;
        }

        idsInputs.forEach(id => {
            const element = DOMUtils.getElement(id);
            if (element) {
                if (element.type === 'select-one') {
                    //  IMPORTANTE: NO resetear origen-select en modo edición
                    // Se cargará correctamente en llenarCamposBasicos()
                    if (id === 'nueva-prenda-origen-select' && window.prendaEditIndex !== null && window.prendaEditIndex !== undefined) {
                        console.log(' [limpiarFormulario]  SALTANDO LIMPIAR SELECT ORIGEN (MODO EDICIÓN) ', {
                            prendaEditIndex: window.prendaEditIndex,
                            selectId: id,
                            valorActual: element.value,
                            razon: 'El valor será establecido en llenarCamposBasicos()'
                        });
                        return; // No limpiar en modo edición
                    }
                    element.value = element.querySelector('option')?.value || '';
                } else {
                    //  IMPORTANTE: NO resetear inputs de telas en modo edición
                    // Se necesitan para permitir agregar nuevas telas durante la edición
                    if ((id === 'nueva-prenda-tela' || id === 'nueva-prenda-color' || id === 'nueva-prenda-referencia') 
                        && window.prendaEditIndex !== null && window.prendaEditIndex !== undefined) {
                        console.log('[limpiarFormulario]  SALTANDO LIMPIAR INPUT TELA (MODO EDICIÓN)', {
                            prendaEditIndex: window.prendaEditIndex,
                            inputId: id,
                            razon: 'Se permite agregar nuevas telas en edición'
                        });
                        return; // No limpiar inputs de telas en modo edición
                    }
                    element.value = '';
                }
            }
        });


    }

    /**
     * Limpiar todos los storages globales
     */
    static limpiarStorages() {
        //  CRÍTICO: Limpiar storage de imágenes de prenda PRIMERO
        // Esto vacía el array y revoca todas las URLs blob
        if (window.imagenesPrendaStorage) {
            if (typeof window.imagenesPrendaStorage.limpiar === 'function') {
                window.imagenesPrendaStorage.limpiar();
            } else if (window.imagenesPrendaStorage.images) {
                // Fallback: limpiar directamente si el método no existe
                window.imagenesPrendaStorage.images.forEach(img => {
                    if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                        URL.revokeObjectURL(img.previewUrl);
                    }
                });
                window.imagenesPrendaStorage.images = [];
            }
        }

        // Limpiar storage de imágenes de tela
        if (window.imagenesTelaStorage) {
            if (typeof window.imagenesTelaStorage.limpiar === 'function') {
                window.imagenesTelaStorage.limpiar();
            }
        }

        //  CRÍTICO: Limpiar telas agregadas (variable principal donde se guardan las telas)
        if (window.telasAgregadas) {
            window.telasAgregadas.length = 0;
        }
        
        // Limpiar telas agregadas (AMBOS FLUJOS: CREACIÓN y EDICIÓN - SEPARADOS)
        if (window.telasCreacion) {
            window.telasCreacion.length = 0;
        }
        if (window.telasEdicion) {
            window.telasEdicion.length = 0;
        }

        // Limpiar tallas relacionales (modelo nuevo: {GENERO: {TALLA: CANTIDAD}})
        if (window.tallasRelacionales) {
            window.tallasRelacionales.DAMA = {};
            window.tallasRelacionales.CABALLERO = {};
            window.tallasRelacionales.UNISEX = {};
            window.tallasRelacionales.SOBREMEDIDA = {};
        }

        // Limpieza de variables
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = { dama: { tallas: [], tipo: null }, caballero: { tallas: [], tipo: null } };
        }
    }

    /**
     * Limpiar todos los checkboxes del modal
     * @param {boolean} preservarProcesos - Si true, no limpiar checkboxes de procesos
     */
    static limpiarCheckboxes(preservarProcesos = false) {
        const checkboxesBásicos = [
            'aplica-manga',
            'aplica-bolsillos',
            'aplica-broche',
            'checkbox-reflectivo'
        ];

        checkboxesBásicos.forEach(id => {
            DOMUtils.setChecked(id, false);
        });

        // Limpiar procesos solo si no se especifica preservarlos
        if (!preservarProcesos) {
            const checkboxesProcesos = [
                'checkbox-bordado',
                'checkbox-estampado',
                'checkbox-dtf',
                'checkbox-sublimado'
            ];

            checkboxesProcesos.forEach(id => {
                DOMUtils.setChecked(id, false);
            });


        }


    }

    /**
     * Limpiar procesos seleccionados
     * @param {boolean} preservar - Si true, no limpiar procesos
     */
    static limpiarProcesos(preservar = false) {
        if (!preservar && window.procesosSeleccionados) {
            Object.keys(window.procesosSeleccionados).forEach(key => {
                delete window.procesosSeleccionados[key];
            });

        }
        
        //  CRÍTICO: Limpiar tallas de procesos (que se muestran en el contador)
        if (!preservar) {
            if (window.tallasSeleccionadasProceso) {
                window.tallasSeleccionadasProceso = { dama: [], caballero: [] };
            }
            if (window.tallasCantidadesProceso) {
                window.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
            }
        }
    }

    /**
     * Limpiar contenedores visuales (tablas, galerías, etc)
     */
    static limpiarContenedores() {
        // Limpiar tabla de telas - PERO MANTENER LA FILA BASE CON INPUTS
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            const filas = tbodyTelas.querySelectorAll('tr');
            let filasEliminadas = 0;
            
            // Eliminar SOLO las filas agregadas (aquellas que no contienen los inputs principales)
            filas.forEach(fila => {
                const telaInput = fila.querySelector('#nueva-prenda-tela');
                // Si NO tiene el input #nueva-prenda-tela, es una fila agregada y se elimina
                if (!telaInput) {
                    fila.remove();
                    filasEliminadas++;
                }
            });
            

        }

        // Limpiar preview de tela
        const telaPreview = document.getElementById('nueva-prenda-tela-preview');
        if (telaPreview) {
            telaPreview.innerHTML = '';
            telaPreview.style.display = 'none';
        }

        //  CRÍTICO: Limpiar COMPLETAMENTE galería de fotos - STORAGE + DOM
        // Primero limpiar el servicio de imágenes (vacía array y revoca URLs)
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar?.();
        }
        
        // Luego limpiar el DOM del preview
        const fotosPreview = document.getElementById('nueva-prenda-foto-preview');
        if (fotosPreview) {
            fotosPreview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click o arrastra para agregar</div></div>';
            fotosPreview.style.cursor = 'pointer';
            // DragDrop se reconfigura en shown.bs.modal, NO aquí
        }
        
        // Limpiar contador de fotos
        const fotosContador = document.getElementById('nueva-prenda-foto-contador');
        if (fotosContador) {
            fotosContador.textContent = '';
        }
        
        // Limpiar botón de agregar fotos
        const fotosBtn = document.getElementById('nueva-prenda-foto-btn');
        if (fotosBtn) {
            fotosBtn.style.display = 'block';
        }
        
        // DragDrop tela se reconfigura en shown.bs.modal, NO aquí

        //  CRÍTICO: Limpiar TODAS las tarjetas de géneros (DAMA, CABALLERO, UNISEX)
        const tarjetasGenerosContainer = document.getElementById('tarjetas-generos-container');
        if (tarjetasGenerosContainer) {
            tarjetasGenerosContainer.innerHTML = '';
        }
        
        // Resetear botones de género a estado NO seleccionado
        ['dama', 'caballero', 'unisex'].forEach(genero => {
            const btnGenero = document.getElementById(`btn-genero-${genero}`);
            const checkMark = document.getElementById(`check-${genero}`);
            
            if (btnGenero) {
                btnGenero.dataset.selected = 'false';
                btnGenero.style.borderColor = '';
                btnGenero.style.background = '';
            }
            
            if (checkMark) {
                checkMark.style.display = 'none';
            }
        });

        // Limpiar contenedor de procesos - PERO SOLO SI NO ESTAMOS CARGANDO PROCESOS
        // En modo edición, los procesos se cargan DESPUÉS de limpiar, así que no deberíamos borrar aquí
        const contenedorTarjetas = document.getElementById('contenedor-tarjetas-procesos');
        if (contenedorTarjetas) {
            // CRÍTICO: Solo limpiar si es realmente necesario (cuando se crea NUEVA prenda)
            // No limpiar aquí porque causaria que los procesos cargados en edición se borren
            console.log('[ModalCleanup] Contenedor de tarjetas procesos encontrado - NO se limpia aquí (se cargará en PrendaEditor)');
            // contenedorTarjetas.innerHTML = ''; // COMENTADO: Esto borraba los procesos al abrir en edición
        }
        
        //  CRÍTICO: Limpiar resumen de tallas de procesos (el contador que muestra "Total: X unidades")
        const resumenTallas = document.getElementById('proceso-tallas-resumen');
        if (resumenTallas) {
            resumenTallas.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        }
        
        //  CRÍTICO: Resetear el contador general de prendas también
        const totalPrendas = document.getElementById('total-prendas');
        if (totalPrendas) {
            totalPrendas.textContent = '0';
        }


    }

    /**
     * Limpiar solo inputs específicos de tela
     */
    static limpiarTela() {
        const idsTelaInputs = [
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia'
        ];

        DOMUtils.clearValues(idsTelaInputs);
        
        const telaPreview = DOMUtils.getElement('nueva-prenda-tela-preview');
        if (telaPreview) {
            telaPreview.innerHTML = '';
        }


    }

    /**
     * Limpiar solo fotos
     */
    static limpiarFotos() {
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar?.();
        }

        const fotosPreview = DOMUtils.getElement('nueva-prenda-foto-preview');
        if (fotosPreview) {
            fotosPreview.innerHTML = FOTOS_PREVIEW_VACIO_HTML;
        }

        DOMUtils.clearValue('nueva-prenda-foto-input');
        DOMUtils.clearValue('nueva-prenda-foto-contador');


    }

    /**
     * Limpiar solo géneros y tallas
     */
    static limpiarGenerosYTallas() {
        // Limpiar estructura relacional
        if (window.tallasRelacionales) {
            window.tallasRelacionales.DAMA = {};
            window.tallasRelacionales.CABALLERO = {};
            window.tallasRelacionales.UNISEX = {};
        }

        // Limpiar variables
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = { dama: { tallas: [], tipo: null }, caballero: { tallas: [], tipo: null } };
        }

        const tarjetasGenerosContainer = DOMUtils.getElement('tarjetas-generos-container');
        if (tarjetasGenerosContainer) {
            tarjetasGenerosContainer.innerHTML = '';
        }

        // Resetear botones de género
        const btnDama = DOMUtils.getElement('btn-genero-dama');
        const btnCaballero = DOMUtils.getElement('btn-genero-caballero');
        if (btnDama) btnDama.classList.remove('active', 'bg-pink-100', 'border-pink-500');
        if (btnCaballero) btnCaballero.classList.remove('active', 'bg-blue-100', 'border-blue-500');

        const totalPrendas = DOMUtils.getElement('total-prendas');
        if (totalPrendas) {
            totalPrendas.textContent = '0';
        }


    }

    /**
     * Limpiar solo variaciones
     */
    static limpiarVariaciones() {
        const idsVariaciones = [
            'manga-input',
            'manga-obs',
            'bolsillos-obs',
            'broche-input',
            'broche-obs'
        ];

        DOMUtils.clearValues(idsVariaciones);

        const checkboxesVariaciones = [
            'aplica-manga',
            'aplica-bolsillos',
            'aplica-broche',
            'checkbox-reflectivo'
        ];

        DOMUtils.setCheckedAll(checkboxesVariaciones, false);


    }

    /**
     * Actualizar dinámicamente el título del modal
     * @param {string} modo - 'agregar' o 'editar'
     */
    static actualizarTituloModal(modo = 'agregar') {
        // Buscar el título en el modal
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) return;
        
        const modalTitle = modal.querySelector('.modal-title');
        if (!modalTitle) return;
        
        if (modo === 'editar') {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">edit</span>Edición de Prenda';
        } else {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">add_box</span>Agregar Prenda Nueva';
        }
    }

    /**
     * Preparar modal para creación de NUEVA prenda
     */
    static prepararParaNueva() {

        
        //  CRÍTICO: Resetear prendaEditIndex PRIMERO, antes de limpiar
        // Esto asegura que limpiarFormulario() vea que estamos en modo CREACIÓN (no edición)
        window.prendaEditIndex = null;
        
        //  CRÍTICO: Resetear COMPLETAMENTE tallasRelacionales ANTES de cualquier otra limpieza
        // Esto previene que datos viejos de prenda anterior aparezcan en las tarjetas
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = {};
        }
        window.tallasRelacionales.DAMA = {};
        window.tallasRelacionales.CABALLERO = {};
        window.tallasRelacionales.UNISEX = {};
        window.tallasRelacionales.SOBREMEDIDA = {};
        
        //  CRÍTICO: Limpiar imágenes de procesos
        window.imagenesProcesoActual = [null, null, null];
        
        //  CRÍTICO: Limpiar TELAS - arrays en memoria
        if (window.telasAgregadas) {
            window.telasAgregadas.length = 0;
        }
        if (window.telasCreacion) {
            window.telasCreacion.length = 0;
        }
        
        //  CRÍTICO: Limpiar tabla de telas completamente y recrear la fila de entrada
        // Esto asegura que los inputs SIEMPRE estén presentes
        const tbody = document.getElementById('tbody-telas');
        if (tbody) {
            // Limpiar todas las filas
            tbody.innerHTML = '';
            
            // Recrear SOLO la fila de entrada con todos los inputs
            // Esto garantiza que los inputs siempre existan sin importar cuántas veces se limpie
            tbody.innerHTML = `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.5rem; width: 20%;">
                        <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" list="opciones-telas" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                        <datalist id="opciones-telas"></datalist>
                    </td>
                    <td style="padding: 0.5rem; width: 20%;">
                        <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" list="opciones-colores" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                        <datalist id="opciones-colores"></datalist>
                    </td>
                    <td style="padding: 0.5rem; width: 20%;">
                        <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem; text-align: center; vertical-align: top; width: 20%;">
                        <div id="nueva-prenda-tela-drop-zone" class="tela-drop-zone" style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; width: 100%; transition: all 0.2s ease; border: 2px dashed transparent; border-radius: 6px; padding: 8px; cursor: pointer;">
                            <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.5rem 1rem; transition: all 0.2s ease; margin-bottom: 8px;" title="Agregar imagen (opcional) o arrastra una imagen aquí">
                                <span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">image</span>
                                <span style="font-size: 0.7rem;">Agregar imagen</span>
                            </button>
                            <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenTela(this)">
                            
                            <!-- Texto de ayuda -->
                            <div style="text-align: center; color: #6b7280; font-size: 0.7rem; margin-top: 4px;">
                                <div class="material-symbols-rounded" style="font-size: 1.2rem; opacity: 0.5;">cloud_upload</div>
                                <div>Arrastra una imagen aquí</div>
                            </div>
                        </div>
                        <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px;"></div>
                    </td>
                    <td style="padding: 0.5rem; text-align: center; width: 20%;">
                        <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                        </button>
                    </td>
                </tr>
            `;
        }
        
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes(false); // Limpiar TODO
        this.limpiarProcesos(false); // Limpiar TODO
        this.limpiarContenedores();
        
        //  CRÍTICO: Cargar opciones de telas y colores para las datalist
        // Esto restaura las sugerencias de autocomplete
        if (typeof window.cargarTelasDisponibles === 'function') {
            window.cargarTelasDisponibles();
        }
        if (typeof window.cargarColoresDisponibles === 'function') {
            window.cargarColoresDisponibles();
        }
        
        // Cambiar título del modal a "Agregar Prenda Nueva"
        this.actualizarTituloModal('agregar');

    }

    /**
     * Preparar modal para EDITAR prenda existente
     * @param {number} prendaIndex - Índice de la prenda a editar
     */
    static prepararParaEditar(prendaIndex) {

        
        //  IMPORTANTE: Establecer índice de edición PRIMERO, antes de limpiar
        // Esto permite que limpiarFormulario() sepa que estamos en modo edición
        window.prendaEditIndex = prendaIndex;
        
        //  CRÍTICO: Limpiar imágenes de procesos cuando abrimos para editar
        window.imagenesProcesoActual = [null, null, null];
        
        // NO limpiar storages en modo edición - se cargarán los datos de la prenda
        // Solo limpiar formulario e inputs
        this.limpiarFormulario();
        // NO llamar a limpiarStorages() - preservar telas e imágenes
        this.limpiarCheckboxes(true); // Preservar procesos
        // NO limpiar contenedores en modo edición - se cargarán los datos de la prenda
        // this.limpiarContenedores();
        
        //  CRÍTICO: Limpiar telasCreacion para que no interfiera con telasAgregadas
        // Cuando editamos una prenda que fue agregada desde creación, telasCreacion podría tener datos viejos
        if (window.telasCreacion) {
            window.telasCreacion.length = 0;
            console.log(' [prepararParaEditar] telasCreacion limpiado para modo edición');
        }
        
        //  CRÍTICO: Inicializar telasAgregadas si no existe (será llenado por cargarTelas)
        if (!window.telasAgregadas) {
            window.telasAgregadas = [];
            console.log(' [prepararParaEditar] telasAgregadas inicializado como array vacío');
        }
        
        //  Cargar opciones de telas y colores para las datalist
        if (typeof window.cargarTelasDisponibles === 'function') {
            window.cargarTelasDisponibles();
        }
        if (typeof window.cargarColoresDisponibles === 'function') {
            window.cargarColoresDisponibles();
        }
        
        // Cambiar título del modal a "Edición de Prenda"
        this.actualizarTituloModal('editar');

    }

    /**
     * Resetear datos de índice de edición
     */
    static resetearEdicion() {
        window.prendaEditIndex = null;

    }

    /**
     * Limpiar modal completamente (después de guardar)
     */
    static limpiarDespuésDeGuardar() {
        const inicioTiempo = performance.now();
        console.log(' [ModalCleanup.limpiarDespuésDeGuardar] INICIANDO limpieza...');
        
        try {
            // PASO 1: Limpiar formulario
            console.log('  → PASO 1: limpiarFormulario()...');
            const paso1 = performance.now();
            this.limpiarFormulario();
            console.log(`  ✓ PASO 1 completado en ${(performance.now() - paso1).toFixed(2)}ms`);
            
            // PASO 2: Resetear edición
            console.log('  → PASO 2: resetearEdicion()...');
            const paso2 = performance.now();
            this.resetearEdicion();
            console.log(`  ✓ PASO 2 completado en ${(performance.now() - paso2).toFixed(2)}ms`);
            
            // PASO 3: Resetear prendaEditIndex en gestionItemsUI
            console.log('  → PASO 3: Reseteando window.gestionItemsUI.prendaEditIndex...');
            const paso3 = performance.now();
            if (window.gestionItemsUI) {
                window.gestionItemsUI.prendaEditIndex = null;
            }
            if (window.gestionItemsUI?.prendaEditor) {
                window.gestionItemsUI.prendaEditor.prendaEditIndex = null;
            }
            console.log(`  ✓ PASO 3 completado en ${(performance.now() - paso3).toFixed(2)}ms`);
            
            // PASO 4: Ocultar modal
            console.log('  → PASO 4: Ocultando modal...');
            const paso4 = performance.now();
            const modal = DOMUtils.getElement('modal-agregar-prenda-nueva');
            if (modal) {
                modal.style.display = 'none';
            }
            console.log(`  ✓ PASO 4 completado en ${(performance.now() - paso4).toFixed(2)}ms`);
            
            const tiempoTotal = performance.now() - inicioTiempo;
            console.log(` [ModalCleanup.limpiarDespuésDeGuardar] COMPLETADO EN ${tiempoTotal.toFixed(2)}ms`);
            
            // PASO 5: Limpiar el resto de forma ASÍNCRONA (no bloqueante)
            console.log('  → PASO 5 (ASÍNCRONO): Programando limpiezas adicionales...');
            setTimeout(() => {
                try {
                    console.log('    → Limpiando storages...');
                    const pasoS1 = performance.now();
                    this.limpiarStorages();
                    console.log(`    ✓ Storages en ${(performance.now() - pasoS1).toFixed(2)}ms`);
                    
                    console.log('    → Limpiando checkboxes...');
                    const pasoS2 = performance.now();
                    this.limpiarCheckboxes();
                    console.log(`    ✓ Checkboxes en ${(performance.now() - pasoS2).toFixed(2)}ms`);
                    
                    console.log('    → Limpiando procesos...');
                    const pasoS3 = performance.now();
                    this.limpiarProcesos();
                    console.log(`    ✓ Procesos en ${(performance.now() - pasoS3).toFixed(2)}ms`);
                    
                    console.log('    → Limpiando contenedores...');
                    const pasoS4 = performance.now();
                    this.limpiarContenedores();
                    console.log(`    ✓ Contenedores en ${(performance.now() - pasoS4).toFixed(2)}ms`);
                    
                    console.log('  ✓ PASO 5 (ASÍNCRONO) completado');
                } catch (error) {
                    console.error('   Error en limpieza asíncrona:', error);
                }
            }, 10);

        } catch (error) {
            console.error(' [ModalCleanup.limpiarDespuésDeGuardar] Error:', error);
        }
    }
}

// Hacer disponible globalmente
window.ModalCleanup = ModalCleanup;
