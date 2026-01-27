class CargadorPrendasCotizacion {
    constructor() {
        this.prendasDisponiables = [];
        this.cotizacionActual = null;
    }
    async cargarPrendaCompletaDesdeCotizacion(cotizacionId, prendaId) {
        try {
            console.log('[CargadorPrendasCotizacion] 📦 Cargando prenda completa...');
            console.log('  - Cotización ID:', cotizacionId);
            console.log('  - Prenda ID:', prendaId);

            // Cargar datos COMPLETOS de la prenda desde el backend
            const response = await fetch(
                `/asesores/pedidos-produccion/obtener-prenda-completa/${cotizacionId}/${prendaId}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            console.log('[CargadorPrendasCotizacion] ✓ Datos cargados:', {
                nombre: data.prenda?.nombre_producto || data.prenda?.nombre,
                procesos: Object.keys(data.procesos || {}),
                telas_count: data.prenda?.telas?.length || 0,
                fotos_count: data.prenda?.fotos?.length || 0
            });
            
            // DEBUG: Ver procesos completos
            console.log('[CargadorPrendasCotizacion] 🔍 PROCESOS COMPLETOS DEL BACKEND:');
            console.log(data.procesos);
            
            console.log('[CargadorPrendasCotizacion] 🔍 TELAS RECIBIDAS DEL BACKEND:', data.prenda?.telas);

            // Transformar datos al formato esperado por GestionItemsUI
            return this.transformarDatos(data, cotizacionId);

        } catch (error) {
            console.error('[CargadorPrendasCotizacion] ❌ Error cargando prenda:', error);
            throw error;
        }
    }

    /**
     * Transformar datos de la API al formato esperado por el modal
     */
    transformarDatos(data, cotizacionId) {
        const prenda = data.prenda || {};
        const procesos = data.procesos || {};

        console.log('[CargadorPrendasCotizacion] 🔄 Transformando datos para prenda:', prenda.nombre_producto);

        // Preparar estructura de procesos con TODA la información
        const procesosCompletos = {};
        Object.entries(procesos).forEach(([tipoProceso, procesoData]) => {
            console.log(`  [Proceso] ${tipoProceso}:`, {
                ubicaciones: procesoData.ubicaciones,
                imagenes: procesoData.imagenes?.length || 0,
                observaciones: procesoData.observaciones,
                variaciones_prenda: !!procesoData.variaciones_prenda,
                talla_cantidad: !!procesoData.talla_cantidad
            });

            procesosCompletos[tipoProceso] = {
                tipo: procesoData.tipo || tipoProceso,
                slug: procesoData.slug || tipoProceso,  // Agregar slug si viene
                ubicaciones: procesoData.ubicaciones || [],
                observaciones: procesoData.observaciones || '',
                // NUEVO: Procesar variaciones de prenda
                variaciones_prenda: procesoData.variaciones_prenda || {},
                // NUEVO: Procesar talla cantidad desde técnicas de logo
                talla_cantidad: procesoData.talla_cantidad || {},
                imagenes: (procesoData.imagenes || []).map(img => ({
                    ruta: img.ruta || img,
                    ruta_webp: img.ruta_webp || null,
                    uid: `existing-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
                })),
                tallas: procesoData.tallas || {}
            };
        });

        // Preparar fotos de prenda
        const fotosFormato = (prenda.fotos || []).map((foto, idx) => ({
            ruta: foto.ruta || foto,
            ruta_webp: foto.ruta_webp || null,
            uid: `existing-foto-${Date.now()}-${idx}`
        }));
        
        console.log('[transformarDatos] 📸 FOTOS RECIBIDAS DEL BACKEND:', prenda.fotos);
        console.log('[transformarDatos] 📦 FOTOS PROCESADAS:', fotosFormato);

        // Preparar telas CON TODAS LAS REFERENCIAS
        console.log('[transformarDatos] 🧵 TELAS RECIBIDAS DEL BACKEND:', prenda.telas);
        console.log('[transformarDatos] 🧵 ESTRUCTURA completa de telas:', JSON.stringify(prenda.telas, null, 2));
        
        const telasFormato = (prenda.telas || []).map((tela, idx) => {
            const teleImagen = tela.imagenes || [];
            console.log(`[transformarDatos] 🧵 Procesando tela ${idx}:`, {
                id: tela.id,
                nombre_tela: tela.nombre_tela,
                color: tela.color,
                referencia: tela.referencia,
                imagenes_count: teleImagen.length
            });
            return {
                id: tela.id,
                nombre_tela: tela.nombre_tela || tela.tela?.nombre || tela.nombre || 'SIN NOMBRE',
                color: tela.color || tela.color?.nombre || '',
                grosor: tela.grosor || '',
                referencia: tela.referencia || '',
                composicion: tela.composicion || '',
                imagenes: teleImagen.map((img, idx) => ({
                    ruta: img.ruta || img,
                    ruta_webp: img.ruta_webp || null,
                    uid: `existing-tela-${tela.id}-${idx}`
                }))
            };
        });
        
        console.log('[transformarDatos] 📦 TELAS PROCESADAS:', telasFormato);

        // Estructura de tallas - SOLO OBTENER TALLAS DISPONIBLES (sin cantidades)
        // El usuario digitará las cantidades manualmente
        const tallasDisponibles = [];
        if (prenda.tallas_disponibles && Array.isArray(prenda.tallas_disponibles)) {
            tallasDisponibles.push(...prenda.tallas_disponibles);
        }
        
        console.log('[transformarDatos] 👕 TALLAS DISPONIBLES:', tallasDisponibles);

        // Estructura COMPLETA de prenda para el editor modal
        const prendaCompleta = {
            // Datos básicos
            nombre_prenda: prenda.nombre_producto || prenda.nombre || '',
            descripcion: prenda.descripcion || '',
            origen: prenda.prenda_bodega === 1 || prenda.prenda_bodega === true ? 'bodega' : 'confeccion',
            genero: prenda.genero || [],
            cantidad: prenda.cantidad || 0,
            
            // TELAS PRECARGADAS (con clave telasAgregadas para el modal)
            telasAgregadas: telasFormato,
            telas: telasFormato, // Para compatibilidad
            
            // FOTOS DE PRENDA PRECARGADAS
            imagenes: fotosFormato,
            fotos: fotosFormato, // Para compatibilidad
            
            // TALLAS DISPONIBLES - SOLO array de tallas, sin cantidades
            // Frontend debe mostrar checkboxes/inputs SIN valores pre-llenados
            tallas_disponibles: tallasDisponibles,
            cantidad_talla: {},  // Vacío - usuario digitará las cantidades
            
            // VARIACIONES/ESPECIFICACIONES - COMPLETAS desde prenda_variantes_cot
            variantes: prenda.variantes || {
                // Información básica
                tipo_prenda: '',
                es_jean_pantalon: false,
                tipo_jean_pantalon: '',
                
                // Manga - INCLUIR aplica_manga para checkear
                aplica_manga: prenda.variantes?.aplica_manga || false,
                tipo_manga: prenda.variantes?.tipo_manga || 'No aplica',
                tipo_manga_id: prenda.variantes?.tipo_manga_id || null,
                obs_manga: prenda.variantes?.obs_manga || '',
                
                // Bolsillos
                tiene_bolsillos: prenda.variantes?.tiene_bolsillos || false,
                obs_bolsillos: prenda.variantes?.obs_bolsillos || '',
                
                // Broche - INCLUIR aplica_broche para checkear
                aplica_broche: prenda.variantes?.aplica_broche || false,
                tipo_broche: prenda.variantes?.tipo_broche || 'No aplica',
                tipo_broche_id: prenda.variantes?.tipo_broche_id || null,
                obs_broche: prenda.variantes?.obs_broche || '',
                
                // Reflectivo
                tiene_reflectivo: prenda.variantes?.tiene_reflectivo || false,
                obs_reflectivo: prenda.variantes?.obs_reflectivo || '',
                
                // Descripción adicional
                descripcion_adicional: prenda.variantes?.descripcion_adicional || '',
                
                // Telas múltiples (JSON)
                telas_multiples: prenda.variantes?.telas_multiples || [],
                
                // Género
                genero_id: prenda.variantes?.genero_id || null,
                genero: prenda.variantes?.genero || '',
                color: prenda.variantes?.color || ''
            },
            
            // PROCESOS COMPLETOS
            procesos: procesosCompletos,
            
            // Metadata
            tipo: 'cotizacion',
            cotizacion_id: data.cotizacion_id || cotizacionId,
            prenda_id: prenda.id,
            numero_cotizacion: data.numero_cotizacion
        };

        console.log('[CargadorPrendasCotizacion] ✅ Prenda transformada:', {
            nombre: prendaCompleta.nombre_prenda,
            procesos_count: Object.keys(prendaCompleta.procesos).length,
            telas_count: prendaCompleta.telasAgregadas.length,
            fotos_count: prendaCompleta.imagenes.length,
            variantes: prendaCompleta.variantes
        });

        return prendaCompleta;
    }

    /**
     * Formatear tallas para el modal (convertir array a formato {GENERO: {talla: cantidad}})
     */
    formatearTallasParaModal(tallasCotizacion) {
        const resultado = {};

        if (!tallasCotizacion) {
            return resultado;
        }

        // Si es un array simple de objetos {talla, cantidad}
        if (Array.isArray(tallasCotizacion)) {
            tallasCotizacion.forEach(tc => {
                if (tc.talla && tc.cantidad) {
                    // Determinar género basado en la talla
                    const genero = this.determinarGeneroTalla(tc.talla);
                    
                    if (!resultado[genero]) {
                        resultado[genero] = {};
                    }
                    resultado[genero][tc.talla] = parseInt(tc.cantidad) || 0;
                }
            });
        } else if (typeof tallasCotizacion === 'object') {
            // Si ya es un objeto
            Object.entries(tallasCotizacion).forEach(([talla, cantidad]) => {
                const genero = this.determinarGeneroTalla(talla);
                if (!resultado[genero]) {
                    resultado[genero] = {};
                }
                resultado[genero][talla] = parseInt(cantidad) || 0;
            });
        }

        return resultado;
    }

    /**
     * Determinar si una talla es DAMA, CABALLERO o UNISEX
     */
    determinarGeneroTalla(talla) {
        if (!talla) return 'UNISEX';
        
        const tallaStr = talla.toString().toUpperCase();
        
        // Tallas de DAMA (números pares pequeños)
        if (['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'].includes(tallaStr)) {
            return 'DAMA';
        }
        
        // Tallas de CABALLERO (números pares grandes)
        if (['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'].includes(tallaStr)) {
            return 'CABALLERO';
        }
        
        // Tallas de UNISEX (letras)
        if (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'].includes(tallaStr)) {
            return 'UNISEX';
        }
        
        // Default
        return 'UNISEX';
    }

    /**
     * Agregar prenda completa a GestionItemsUI
     */
    agregarPrendaAGestion(prendaCompleta) {
        console.log('[CargadorPrendasCotizacion] 📋 Agregando prenda a GestionItemsUI');

        if (!window.gestionItemsUI) {
            console.error('❌ GestionItemsUI no disponible');
            return false;
        }

        try {
            // Agregar al array de prendas
            window.gestionItemsUI.agregarPrendaAlOrden(prendaCompleta);

            console.log('[CargadorPrendasCotizacion] ✓ Prenda agregada a GestionItemsUI');
            return true;
        } catch (error) {
            console.error('[CargadorPrendasCotizacion] Error agregando prenda:', error);
            return false;
        }
    }
}

// Instancia global
window.cargadorPrendasCotizacion = new CargadorPrendasCotizacion();

/**
 * Abrir modal para seleccionar prenda de cotización
 * Usar el mismo modal-agregar-prenda-nueva que en crear sin cotización
 */
window.abrirSelectorPrendasCotizacion = function(cotizacion) {
    console.log('[abrirSelectorPrendasCotizacion] 📦 Abriendo selector de prendas');
    console.log('  Cotización:', cotizacion);

    if (!cotizacion || !cotizacion.original || !cotizacion.original.prendas) {
        alert('❌ Error: No hay prendas disponibles en esta cotización');
        return;
    }

    const prendas = cotizacion.original.prendas;
    console.log(`  Prendas disponibles: ${prendas.length}`);

    // Crear modal dinámico para seleccionar prenda
    const modal = document.createElement('div');
    modal.id = 'modal-seleccionar-prenda-cotizacion';
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    const container = document.createElement('div');
    container.className = 'modal-container modal-lg';
    container.style.cssText = `
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        max-height: 80vh;
        overflow-y: auto;
    `;

    // Header
    const header = document.createElement('div');
    header.className = 'modal-header modal-header-gradient';
    header.style.cssText = `
        padding: 1.5rem;
        background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
        color: white;
        border-radius: 12px 12px 0 0;
    `;
    header.innerHTML = `
        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">
            🎨 Selecciona una Prenda
        </h3>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; opacity: 0.9;">
            Cotización: ${cotizacion.numero_cotizacion} - ${cotizacion.cliente}
        </p>
    `;

    // Body
    const body = document.createElement('div');
    body.className = 'modal-body';
    body.style.cssText = `padding: 1.5rem;`;

    const listaPrendas = document.createElement('div');
    listaPrendas.style.cssText = `display: flex; flex-direction: column; gap: 1rem;`;

    prendas.forEach((prenda, idx) => {
        const prendaItem = document.createElement('div');
        prendaItem.style.cssText = `
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        `;
        prendaItem.onmouseover = () => {
            prendaItem.style.borderColor = '#0066cc';
            prendaItem.style.backgroundColor = '#f0f9ff';
        };
        prendaItem.onmouseout = () => {
            prendaItem.style.borderColor = '#e5e7eb';
            prendaItem.style.backgroundColor = 'white';
        };

        const nombrePrenda = prenda.nombre_producto || prenda.nombre || 'Prenda sin nombre';
        const cantidad = prenda.talla_cantidad && Array.isArray(prenda.talla_cantidad)
            ? prenda.talla_cantidad.reduce((sum, tc) => sum + (tc.cantidad || 0), 0)
            : 0;

        prendaItem.innerHTML = `
            <div>
                <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-weight: 700;">
                    ${nombrePrenda}
                </h4>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem;">
                        📦 ${cantidad} unidades
                    </span>
                    ${prenda.telas && prenda.telas.length > 0 ? `
                        <span style="padding: 0.25rem 0.75rem; background: #f0fdf4; color: #15803d; border-radius: 12px; font-size: 0.875rem;">
                            🎨 ${prenda.telas.length} tela(s)
                        </span>
                    ` : ''}
                    ${prenda.fotos && prenda.fotos.length > 0 ? `
                        <span style="padding: 0.25rem 0.75rem; background: #fef3c7; color: #92400e; border-radius: 12px; font-size: 0.875rem;">
                            📷 ${prenda.fotos.length} foto(s)
                        </span>
                    ` : ''}
                </div>
            </div>
        `;

        prendaItem.addEventListener('click', async () => {
            console.log(`[abrirSelectorPrendasCotizacion] ✓ Prenda seleccionada: ${nombrePrenda}`);
            
            try {
                // Cerrar modal de selección
                modal.remove();

                const prendaCompleta = await window.cargadorPrendasCotizacion.cargarPrendaCompletaDesdeCotizacion(
                    cotizacion.id,
                    prenda.id
                );

                // Cerrar modal de selección
                modal.remove();

                // Abrir el modal modal-agregar-prenda-nueva con la prenda PRECARGADA
                // Esto permite al usuario ver todos los campos llenos desde la cotización
                if (window.gestionItemsUI && window.gestionItemsUI.prendaEditor) {
                    // Cargar la prenda en el modal (NO como edición de existente, sino como NUEVA)
                    // Pero con todos los datos precargados
                    window.gestionItemsUI.prendaEditor.cargarPrendaEnModal(prendaCompleta, null);
                    console.log('[abrirSelectorPrendasCotizacion] ✓ Prenda cargada en modal para edición');
                    
                    // NUEVO: Cargar procesos automáticamente desde la prenda
                    console.log('[abrirSelectorPrendasCotizacion] 🔧 Cargando procesos desde la cotización...');
                    if (prendaCompleta.procesos && Object.keys(prendaCompleta.procesos).length > 0) {
                        window.gestionItemsUI.prendaEditor.cargarProcesos(prendaCompleta);
                        console.log('[abrirSelectorPrendasCotizacion] ✓ Procesos cargados:', Object.keys(prendaCompleta.procesos));
                    } else {
                        console.log('[abrirSelectorPrendasCotizacion] ℹ️ No hay procesos definidos para esta prenda');
                    }
                } else {
                    console.error('[abrirSelectorPrendasCotizacion] ❌ PrendaEditor no disponible');
                    alert('❌ Error: No se pudo abrir el editor de prendas');
                }

                // Notificar éxito
                if (window.gestionItemsUI?.notificationService) {
                    window.gestionItemsUI.notificationService.exito(
                        `Prenda "${nombrePrenda}" cargada desde cotización`
                    );
                }

            } catch (error) {
                console.error('[abrirSelectorPrendasCotizacion] Error:', error);
                alert('❌ Error al cargar la prenda: ' + error.message);
            }
        });

        listaPrendas.appendChild(prendaItem);
    });

    body.appendChild(listaPrendas);

    // Footer
    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    footer.style.cssText = `
        padding: 1.5rem;
        border-top: 1px solid #e5e7eb;
        text-align: right;
    `;
    footer.innerHTML = `
        <button style="
            padding: 0.75rem 1.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        " onclick="document.getElementById('modal-seleccionar-prenda-cotizacion').remove();">
            ✕ Cerrar
        </button>
    `;

    container.appendChild(header);
    container.appendChild(body);
    container.appendChild(footer);
    modal.appendChild(container);
    document.body.appendChild(modal);

    console.log('[abrirSelectorPrendasCotizacion] ✅ Modal abierto');
};
