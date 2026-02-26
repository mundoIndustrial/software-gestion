/**
 * 🎯 ORQUESTADOR - PrendaEditor (Simplificado)
 * 
 * Responsabilidad: Coordinar flujos de edición
 * - Abrición/cierre de modal
 * - Carga de datos guardados
 * - Delegación a loaders especializados
 */

//  Permitir redeclaración (se puede cargar múltiples veces desde diferentes Blades)
if (typeof window.PrendaEditor !== 'undefined') {
    delete window.PrendaEditor;
}

class PrendaEditor {
    constructor(options = {}) {
        this.modalId = options.modalId || 'modal-agregar-prenda-nueva';
        this.prendaEditIndex = null;
        this.cotizacionActual = options.cotizacionActual || null;
    }

    /**
     * 🔓 ABRE MODAL (NEW o EDIT)
     */
    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
        this.prendaEditIndex = esEdicion && prendaIndex !== null ? prendaIndex : null;
        if (cotizacionSeleccionada) this.cotizacionActual = cotizacionSeleccionada;

        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.abrir(this.modalId);
                PrendaModalManager.actualizarTitulo(esEdicion, this.modalId);
            } catch (error) {
                console.error('[🔓 abrirModal] Error:', error);
            }
        }
    }

    /**
     * 🔙 CIERRA MODAL
     */
    cerrarModal() {
        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.cerrar(this.modalId);
                PrendaModalManager.limpiar(this.modalId);
            } catch (error) {
                console.error('[🔙 cerrarModal] Error:', error);
            }
        }
    }

    /**
     *  CARGA PRENDA EN MODAL
     */
    async cargarPrendaEnModal(prenda, prendaIndex) {
        console.log(' [PrendaEditor] Cargando prenda:', prenda.nombre_prenda || prenda.nombre);

        try {
            // 1️⃣ Guardar en global
            window.prendaActual = prenda;
            this.prendaEditIndex = prendaIndex;

            // 2️⃣ Abrir modal
            this.abrirModal(true, prendaIndex);

            // 3️⃣ Esperar a que sea visible
            if (typeof PrendaModalManager !== 'undefined') {
                await PrendaModalManager.esperarVisible(this.modalId);
            } else {
                await new Promise(resolve => setTimeout(resolve, 150));
            }

            // 4️⃣ Obtener datos completos
            let prendaCompleta = prenda;
            if (typeof PrendaEditorService !== 'undefined') {
                prendaCompleta = await PrendaEditorService.obtenerConFallback(prenda);
            }

            // 5️⃣ Normalizar telas si es necesario
            prendaCompleta = this._normalizarTelas(prendaCompleta);

            // 6️⃣ Cargar datos en formulario
            this._cargarDatosEnFormulario(prendaCompleta);

            // 7️⃣ Cambiar botón
            if (typeof PrendaModalManager !== 'undefined') {
                PrendaModalManager.cambiarBotonAGuardarCambios(this.modalId);
            }

            console.log(' [PrendaEditor] Prenda cargada');
        } catch (error) {
            console.error(' [PrendaEditor]', error);
            if (typeof PrendaModalManager !== 'undefined') {
                PrendaModalManager.mostrarError(`Error: ${error.message}`);
            }
        }
    }

    /**
     * 📊 NORMALIZAR TELAS
     * @private
     */
    _normalizarTelas(prenda) {
        if (!prenda) return {};

        if (prenda.telasAgregadas) {
            if (Array.isArray(prenda.telasAgregadas)) {
                return prenda;
            }
            if (typeof prenda.telasAgregadas === 'object') {
                prenda.telasAgregadas = Object.values(prenda.telasAgregadas);
                return prenda;
            }
        }

        if (Array.isArray(prenda.telas)) {
            prenda.telasAgregadas = prenda.telas;
            return prenda;
        }

        prenda.telasAgregadas = [];
        return prenda;
    }

    /**
     * 🔄 CARGAR DATOS EN FORMULARIO
     * Llama a cada loader para cargar su parte
     * @private
     */
    _cargarDatosEnFormulario(prenda) {
        console.log('[🔄 Carga] Cargando datos en formulario...');

        // Basicos
        if (typeof PrendaEditorBasicos !== 'undefined') {
            PrendaEditorBasicos.cargar(prenda);
        }

        // Imágenes
        if (typeof PrendaEditorImagenes !== 'undefined') {
            PrendaEditorImagenes.cargar(prenda);
        }

        // Telas
        if (typeof PrendaEditorTelas !== 'undefined') {
            PrendaEditorTelas.cargar(prenda);
            // Replicar a global para edicion
            if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
                // 🔴 CRÍTICO: NO usar JSON.stringify/parse - DESTRUYE File objects y blob URLs
                // Hacer copia profunda que preserve todos los objetos
                window.telasCreacion = prenda.telasAgregadas.map(tela => ({
                    ...tela,
                    imagenes: tela.imagenes ? [...tela.imagenes] : []
                }));
                
                console.log('[prenda-editor] ✅ telasCreacion replicado con spread operator (SIN stringify/parse):', {
                    cantidad: window.telasCreacion.length,
                    primeraTela: window.telasCreacion[0]?.tela,
                    imagenesEnPrimera: window.telasCreacion[0]?.imagenes?.length || 0
                });
                
                // IMPORTANTE: Limpiar telasAgregadas para evitar conflicto en la colección de datos
                // (prenda-form-collector.js prioriza telasAgregadas sobre telasCreacion)
                window.telasAgregadas = [];
            }
        }

        // Variaciones
        if (typeof PrendaEditorVariaciones !== 'undefined') {
            PrendaEditorVariaciones.cargar(prenda);
        }

        // Tallas
        if (typeof PrendaEditorTallas !== 'undefined') {
            PrendaEditorTallas.cargar(prenda);
            PrendaEditorTallas.marcarGeneros(prenda);
            // Replicar a global para edicion
            if (prenda.cantidad_talla || prenda.tallasRelacionales) {
                const tallas = prenda.cantidad_talla || prenda.tallasRelacionales;
                window.tallasRelacionales = JSON.parse(JSON.stringify(tallas));
            }
        }

        // Colores
        if (typeof PrendaEditorColores !== 'undefined') {
            PrendaEditorColores.cargar(prenda);
        }

        // Procesos
        if (typeof PrendaEditorProcesos !== 'undefined') {
            PrendaEditorProcesos.cargar(prenda);
        }

        console.log(' [🔄 Carga] Datos cargados en formulario');
        
        // 🔴 NUEVO: Cargar sección de SOLO CANTIDAD si existe DIRECTAMENTE aquí
        const tieneGenerico = prenda.generosConTallas && 
                             Object.keys(prenda.generosConTallas).some(g => g.toUpperCase() === 'GENERICO');
        const tieneGenericoEnCantidadTalla = prenda.cantidad_talla && 
                                             prenda.cantidad_talla.GENERICO;
        
        if (tieneGenerico || tieneGenericoEnCantidadTalla) {
            console.log('[PrendaEditor] 📦 DETECTADA PRENDA CON SOLO CANTIDAD');
            
            // Limpiar tarjetas de géneros
            const containerGeneros = document.getElementById('tarjetas-generos-container');
            if (containerGeneros) {
                containerGeneros.innerHTML = '';
            }
            
            // Remover tarjeta GENERICO si existe
            const tarjetaGenerico = document.querySelector('[data-genero="GENERICO"]');
            if (tarjetaGenerico) {
                tarjetaGenerico.remove();
            }
            
            // Resetear botones de géneros
            const botonesGeneros = document.querySelectorAll('.btn-genero');
            botonesGeneros.forEach(btn => {
                btn.style.background = 'white';
                btn.style.borderColor = '#d1d5db';
                btn.style.color = '#374151';
                btn.setAttribute('data-selected', 'false');
                const check = btn.querySelector('.btn-genero-check');
                if (check) check.style.display = 'none';
            });
            
            // Obtener cantidad
            let cantidadValue = 0;
            if (prenda.cantidad_talla && prenda.cantidad_talla.GENERICO) {
                const genericoObj = prenda.cantidad_talla.GENERICO;
                cantidadValue = genericoObj.SIN_ESPECIFICAR || 
                               genericoObj['sin_especificar'] ||
                               Object.values(genericoObj)[0] || 0;
            } else if (prenda.generosConTallas && prenda.generosConTallas.GENERICO) {
                const genericoData = prenda.generosConTallas.GENERICO;
                if (genericoData && typeof genericoData === 'object') {
                    const tallasArray = genericoData.tallas || [];
                    if (tallasArray.length > 0) {
                        cantidadValue = tallasArray[0];
                    }
                }
            }
            
            if (cantidadValue > 0) {
                // Establecer cantidad en input
                const cantidadInput = document.getElementById('cantidad-solo');
                if (cantidadInput) {
                    cantidadInput.value = cantidadValue;
                }
                
                window.cantidadSoloSeleccionada = cantidadValue;
                
                // Mostrar sección
                const btnSoloCantidad = document.getElementById('btn-genero-solo-cantidad');
                const checkSoloCantidad = document.getElementById('check-solo-cantidad');
                const seccionSoloCantidad = document.getElementById('seccion-solo-cantidad');
                const tarjetaSoloCantidad = document.getElementById('tarjeta-solo-cantidad');
                const cantidadDisplay = document.getElementById('cantidad-solo-display');
                
                if (btnSoloCantidad) {
                    btnSoloCantidad.style.background = '#0066cc';
                    btnSoloCantidad.style.borderColor = '#0066cc';
                    btnSoloCantidad.style.color = 'white';
                    btnSoloCantidad.setAttribute('data-selected', 'true');
                }
                if (checkSoloCantidad) checkSoloCantidad.style.display = 'block';
                if (seccionSoloCantidad) seccionSoloCantidad.style.display = 'block';
                if (tarjetaSoloCantidad) {
                    tarjetaSoloCantidad.style.display = 'block';
                    if (cantidadDisplay) cantidadDisplay.textContent = cantidadValue;
                }
                
                console.log('[PrendaEditor] ✓ Sección SOLO CANTIDAD activada');
            }
        }
        
        // 🔴 CRÍTICO: Configurar drag & drop para prenda y procesos en modo edición
        this._configurarDragDropEnEdicion();
    }
    
    /**
     * 🔴 NUEVO: Configurar drag & drop en modo edición
     * @private
     */
    _configurarDragDropEnEdicion() {
        console.log('[PrendaEditor] 🔄 Configurando drag & drop en modo edición...');
        
        // Opción 1: Usar DragDropManager si está disponible (reconfiguración)
        if (typeof window.DragDropManager !== 'undefined') {
            console.log('[PrendaEditor] Usando DragDropManager para reconfiguración...');
            
            // Reconfigurar prendas
            if (typeof window.DragDropManager.reconfigurarPrendas === 'function') {
                window.DragDropManager.reconfigurarPrendas();
                console.log('[PrendaEditor] ✅ Drag & drop de prenda reconfigurado (DragDropManager)');
            }
            
            // Reconfigurar procesos
            if (typeof window.DragDropManager.reconfigurarProcesos === 'function') {
                window.DragDropManager.reconfigurarProcesos();
                console.log('[PrendaEditor] ✅ Drag & drop de procesos reconfigurado (DragDropManager)');
            }
        } else {
            // Opción 2: Fallback a funciones globales
            console.log('[PrendaEditor] DragDropManager no disponible, usando funciones globales...');
            
            if (typeof configurarDragDropPrenda === 'function') {
                configurarDragDropPrenda();
                console.log('[PrendaEditor] ✅ Drag & drop de prenda configurado');
            } else {
                console.warn('[PrendaEditor] ⚠️ configurarDragDropPrenda no disponible');
            }
            
            if (typeof configurarDragDropProcesos === 'function') {
                console.log('[PrendaEditor] 🔄 Llamando a configurarDragDropProcesos desde PrendaEditor');
                console.log('[PrendaEditor] 📊 Timestamp:', new Date().toISOString());
                console.log('[PrendaEditor] 🔍 Stack trace:', new Error().stack);
                configurarDragDropProcesos();
                console.log('[PrendaEditor] ✅ Drag & drop de procesos configurado');
            } else {
                console.warn('[PrendaEditor] ⚠️ configurarDragDropProcesos no disponible');
            }
        }
    }

    resetearEdicion() {
        this.prendaEditIndex = null;
        this.cerrarModal();
    }
}

// Asignar al window para que esté disponible globalmente
window.PrendaEditor = PrendaEditor;

// Exportar para módulos (si aplica)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditor;
}
