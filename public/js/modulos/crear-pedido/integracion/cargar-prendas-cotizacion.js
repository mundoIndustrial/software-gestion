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
            
            // DEBUG: Ver procesos completos - DIAGNÓSTICO DETALLADO
            console.log('[CargadorPrendasCotizacion] 🔍 PROCESOS COMPLETOS DEL BACKEND:');
            console.log(data.procesos);
            
            // Análisis detallado de procesos
            console.log('[CargadorPrendasCotizacion] 📊 ANÁLISIS DE PROCESOS:');
            console.log('  - Tipo de data.procesos:', typeof data.procesos);
            console.log('  - ¿Es array?', Array.isArray(data.procesos));
            console.log('  - ¿Es objeto?', data.procesos && typeof data.procesos === 'object');
            console.log('  - Claves:', Object.keys(data.procesos || {}));
            console.log('  - Contenido completo JSON:', JSON.stringify(data.procesos, null, 2));
            
            // Ver estructura completa de data
            console.log('[CargadorPrendasCotizacion] 🔍 ESTRUCTURA COMPLETA DE DATA:');
            console.log('  - Keys principales:', Object.keys(data));
            console.log('  - ¿Tiene procesos?', 'procesos' in data);
            console.log('  - ¿Tiene prenda?', 'prenda' in data);
            console.log('  - ¿Tiene cotizacion_id?', 'cotizacion_id' in data);
            
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
        
        // PROcesar telas desde el backend (prioridad 1)
        let telasDesdeBackend = (prenda.telas || []).map((tela, idx) => {
            const teleImagen = tela.imagenes || [];
            console.log(`[transformarDatos] 🧵 Procesando tela ${idx} desde backend:`, {
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
        
        // PROcesar telas desde TODAS las variantes (solución directa y robusta)
        let telasDesdeVariantes = [];
        
        // Inicializar telasAgregadas vacío para esta prenda
        const telasAgregadasTemp = [];
        
        if (prenda.variantes && Array.isArray(prenda.variantes)) {
            console.log('[transformarDatos] 🔄 Recorriendo TODAS las variantes para extraer telas');
            console.log('[transformarDatos] � Total de variantes a procesar:', prenda.variantes.length);
            
            // Recorremos todas las variantes
            prenda.variantes.forEach((variante, varianteIndex) => {
                console.log(`[transformarDatos] 📦 [Variante ${varianteIndex}] Procesando variante:`, {
                    tipo_manga: variante.tipo_manga,
                    tiene_bolsillos: variante.tiene_bolsillos,
                    tiene_telas_multiples: !!(variante.telas_multiples),
                    telas_multiples_count: variante.telas_multiples?.length || 0
                });
                
                // Verificar si esta variante tiene telas_multiples
                if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                    console.log(`[transformarDatos] 🧵 [Variante ${varianteIndex}] Encontradas ${variante.telas_multiples.length} telas`);
                    
                    // Recorrer todas las telas de esta variante
                    variante.telas_multiples.forEach((tela, telaIndex) => {
                        console.log(`[transformarDatos] 🎯 [Tela ${telaIndex}] Extrayendo:`, {
                            tela: tela.tela,
                            color: tela.color,
                            referencia: tela.referencia,
                            descripcion: tela.descripcion,
                            imagenes_count: tela.imagenes?.length || 0,
                            todos_los_campos: Object.keys(tela)
                        });
                        
                        // Extraer y validar la referencia directamente
                        const referenciaExtraida = (tela.referencia !== undefined && tela.referencia !== null && tela.referencia !== '') 
                            ? String(tela.referencia).trim() 
                            : '';
                        
                        // Crear objeto de tela con todas las propiedades
                        const telaCompleta = {
                            id: tela.id || null,
                            nombre_tela: tela.tela || tela.nombre_tela || '',
                            color: tela.color || '',
                            referencia: referenciaExtraida, // <-- AQUÍ SE ASEGURA DE COPIAR LA REFERENCIA
                            descripcion: tela.descripcion || '',
                            grosor: tela.grosor || '',
                            composicion: tela.composicion || '',
                            imagenes: Array.isArray(tela.imagenes) ? tela.imagenes : [],
                            origen: 'variante_directa',
                            variante_index: varianteIndex,
                            tela_index: telaIndex
                        };
                        
                        // Agregar al array de telas
                        telasAgregadasTemp.push(telaCompleta);
                        
                        console.log(`[transformarDatos] ✅ [Tela ${telaIndex}] Agregada correctamente:`, {
                            nombre: telaCompleta.nombre_tela,
                            color: telaCompleta.color,
                            referencia: `"${telaCompleta.referencia}"`,
                            descripcion: telaCompleta.descripcion,
                            imagenes: telaCompleta.imagenes.length
                        });
                    });
                } else {
                    console.log(`[transformarDatos] ⚠️ [Variante ${varianteIndex}] No tiene telas_multiples válido`);
                }
            });
            
            // Asignar el resultado final
            telasDesdeVariantes = telasAgregadasTemp;
            
            console.log('[transformarDatos] 🎯 RESULTADO FINAL DE EXTRACIÓN DIRECTA:');
            console.log(`[transformarDatos] 📊 Total de telas extraídas: ${telasDesdeVariantes.length}`);
            
            telasDesdeVariantes.forEach((tela, idx) => {
                console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" | descripción: "${tela.descripcion}"`);
            });
            
        } else {
            console.log('[transformarDatos] ⚠️ La prenda no tiene variantes array');
        }
        
        // Combinar telas: prioridad a las del backend, pero enriquecer con datos de variantes
        let telasFormato = [...telasDesdeBackend];
        
        // Agregar telas desde variantes que no existan ya (basado en nombre_tela + color)
        telasDesdeVariantes.forEach(telaVariante => {
            const existe = telasFormato.some(telaBackend => 
                telaBackend.nombre_tela === telaVariante.nombre_tela && 
                telaBackend.color === telaVariante.color
            );
            
            if (!existe) {
                // No existe: agregar como nueva tela
                telasFormato.push(telaVariante);
                console.log('[transformarDatos] ➕ Agregada tela desde variantes:', telaVariante);
            } else {
                // Existe: enriquecer con referencia si la tela de backend no tiene
                const indiceExistente = telasFormato.findIndex(telaBackend => 
                    telaBackend.nombre_tela === telaVariante.nombre_tela && 
                    telaBackend.color === telaVariante.color
                );
                
                if (indiceExistente !== -1) {
                    const telaExistente = telasFormato[indiceExistente];
                    
                    // Si la tela de backend no tiene referencia pero la de variantes sí, usar la de variantes
                    if ((!telaExistente.referencia || telaExistente.referencia === '') && 
                        telaVariante.referencia && telaVariante.referencia !== '') {
                        telasFormato[indiceExistente].referencia = telaVariante.referencia;
                        telasFormato[indiceExistente].origen = 'backend_enriquecido_variantes';
                        console.log('[transformarDatos] 🔄 Tela enriquecida con referencia de variantes:', {
                            nombre: telaExistente.nombre_tela,
                            color: telaExistente.color,
                            referencia_anterior: `"${telaExistente.referencia}"`,
                            referencia_nueva: `"${telaVariante.referencia}"`
                        });
                    } else {
                        console.log('[transformarDatos] ℹ️ Tela ya existe con referencia, sin cambios:', telaVariante);
                    }
                }
            }
        });
        
        console.log('[transformarDatos] 📦 TELAS FINALES PROCESADAS:', telasFormato);

        // Estructura de tallas - SOLO OBTENER TALLAS DISPONIBLES (sin cantidades)
        // El usuario digitará las cantidades manualmente
        const tallasDisponibles = [];
        if (prenda.tallas_disponibles && Array.isArray(prenda.tallas_disponibles)) {
            tallasDisponibles.push(...prenda.tallas_disponibles);
        }
        
        console.log('[transformarDatos] TALLAS DISPONIBLES:', tallasDisponibles);
        
        // IMPORTANTE: Incluir tallas con cantidades para cotizaciones
        let tallasConCantidades = [];
        if (prenda.tallas && Array.isArray(prenda.tallas)) {
            tallasConCantidades = prenda.tallas;
            console.log('[transformarDatos] 📏 TALLAS CON CANTIDADES para cotización:', tallasConCantidades);
        }

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
            
            // TALLAS CON CANTIDADES - Para cotizaciones (pre-selección)
            tallas: tallasConCantidades,
            
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
            prenda_id: prenda.id,  // ID de la prenda
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

    // Manejar ambos casos:
    // 1. Objeto formateado: cotizacion.original.prendas
    // 2. Objeto original directo: cotizacion.prendas
    let prendas = [];
    
    if (cotizacion.original && cotizacion.original.prendas) {
        // Caso 1: Objeto formateado (tiene propiedad 'original')
        prendas = cotizacion.original.prendas;
    } else if (cotizacion.prendas) {
        // Caso 2: Objeto original directo (prendas está en el nivel superior)
        prendas = cotizacion.prendas;
    } else {
        alert('❌ Error: No hay prendas disponibles en esta cotización');
        return;
    }

    if (!prendas || !Array.isArray(prendas) || prendas.length === 0) {
        alert('❌ Error: No hay prendas disponibles en esta cotización');
        return;
    }

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
             Selecciona una Prenda
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
        const numeroPrenda = idx + 1;
        
        // Debug: Verificar estructura de datos de la prenda
        console.log('[DEBUG] Estructura de prenda:', prenda);
        console.log('[DEBUG] Todas las propiedades de prenda:', Object.keys(prenda));
        console.log('[DEBUG] prenda.telas:', prenda.telas);
        console.log('[DEBUG] prenda.tela:', prenda.tela);
        console.log('[DEBUG] prenda.color:', prenda.color);
        console.log('[DEBUG] prenda.telasAgregadas:', prenda.telasAgregadas);
        console.log('[DEBUG] prenda.variantes:', prenda.variantes);
        console.log('[DEBUG] prenda.colores_telas:', prenda.colores_telas);
        
        // Extraer información de tela y color
        let telaColor = '';
        if (prenda.telas && prenda.telas.length > 0) {
            const primeraTela = prenda.telas[0];
            console.log('[DEBUG] Primera tela encontrada:', primeraTela);
            const nombreTela = primeraTela.nombre_tela || primeraTela.tela || 'N/A';
            const color = primeraTela.color || 'N/A';
            telaColor = `${nombreTela} - ${color}`;
        } else if (prenda.tela || prenda.color) {
            console.log('[DEBUG] Usando tela/color directos');
            telaColor = `${prenda.tela || 'N/A'} - ${prenda.color || 'N/A'}`;
        } else if (prenda.variantes && prenda.variantes.length > 0) {
            console.log('[DEBUG] Revisando variantes para tela/color');
            const primeraVariante = prenda.variantes[0];
            console.log('[DEBUG] Primera variante:', primeraVariante);
            console.log('[DEBUG] Todas las propiedades de la variante:', Object.keys(primeraVariante));
            console.log('[DEBUG] Contenido de telas_multiples:', primeraVariante.telas_multiples);
            
            // Buscar en múltiples propiedades posibles
            let nombreTela = 'N/A';
            let color = primeraVariante.color || 'N/A';
            
            // Primero intentar desde telas_multiples
            if (primeraVariante.telas_multiples) {
                console.log('[DEBUG] Analizando telas_multiples:', primeraVariante.telas_multiples);
                try {
                    const telasMultiples = typeof primeraVariante.telas_multiples === 'string' 
                        ? JSON.parse(primeraVariante.telas_multiples) 
                        : primeraVariante.telas_multiples;
                    
                    console.log('[DEBUG] telas_multiples parseado:', telasMultiples);
                    
                    if (Array.isArray(telasMultiples) && telasMultiples.length > 0) {
                        const primeraTela = telasMultiples[0];
                        console.log('[DEBUG] Primera tela de telas_multiples:', primeraTela);
                        nombreTela = primeraTela.tela || primeraTela.nombre_tela || primeraTela.nombre || 'N/A';
                        color = primeraTela.color || color;
                    }
                } catch (e) {
                    console.error('[DEBUG] Error parseando telas_multiples:', e);
                }
            }
            
            // Si no se encontró en telas_multiples, buscar en otras propiedades
            if (nombreTela === 'N/A') {
                nombreTela = primeraVariante.tela || 
                              primeraVariante.nombre_tela || 
                              primeraVariante.tipo_tela ||
                              primeraVariante.tela_nombre ||
                              'N/A';
            }
            
            console.log('[DEBUG] nombreTela extraído:', nombreTela);
            console.log('[DEBUG] color extraído:', color);
            
            telaColor = `${nombreTela} - ${color}`;
        }
        
        console.log('[DEBUG] telaColor final:', telaColor);

        prendaItem.innerHTML = `
            <div>
                <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-weight: 700;">
                    Prenda ${numeroPrenda} - ${nombrePrenda}
                </h4>
                ${telaColor ? `
                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 500;">
                         ${telaColor}
                    </p>
                ` : ''}
            </div>
        `;

        prendaItem.addEventListener('click', async () => {
            console.log(`[abrirSelectorPrendasCotizacion] ✓ Prenda seleccionada: ${nombrePrenda}`);
            
            try {
                // Cerrar modal de selección
                modal.remove();

                // 💾 GUARDAR PRENDA ORIGINAL PARA REFERENCIAS
                // Guardar la prenda original del selector para poder acceder a telas_multiples más tarde
                window.prendaOriginalDesdeSelector = {
                    variantes: prenda.variantes,
                    id: prenda.id,
                    nombre_producto: prenda.nombre_producto,
                    cotizacion_id: cotizacion.id
                };
                console.log('[abrirSelectorPrendasCotizacion] 💾 Prenda original guardada para referencia:', {
                    tiene_variantes: !!prenda.variantes,
                    variantes_es_array: Array.isArray(prenda.variantes),
                    variantes_length: prenda.variantes?.length || 0
                });

                const prendaCompleta = await window.cargadorPrendasCotizacion.cargarPrendaCompletaDesdeCotizacion(
                    cotizacion.id,
                    prenda.id
                );

                // Cerrar modal de selección
                modal.remove();

                // Abrir el modal modal-agregar-prenda-nueva con la prenda PRECARGADA
                // Esto permite al usuario ver todos los campos llenos desde la cotización
                if (window.gestionItemsUI && window.gestionItemsUI.prendaEditor) {
                    // 🔴 ASIGNAR COTIZACIÓN AL PRENDAEDITOR (para origen automático)
                    // Usar el objeto original si existe, para tener acceso a tipo_cotizacion_id y tipo_cotizacion
                    const cotizacionParaPrendaEditor = cotizacion.original || cotizacion;
                    
                    window.gestionItemsUI.prendaEditor.cotizacionActual = cotizacionParaPrendaEditor;
                    
                    console.log('[abrirSelectorPrendasCotizacion] 🔗 Cotización asignada al PrendaEditor:', {
                        id: cotizacionParaPrendaEditor.id,
                        tipo_cotizacion_id: cotizacionParaPrendaEditor.tipo_cotizacion_id,
                        tipo_cotizacion_nombre: cotizacionParaPrendaEditor.tipo_cotizacion?.nombre,
                        numero: cotizacionParaPrendaEditor.numero_cotizacion || cotizacion.numero_cotizacion
                    });
                    
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
