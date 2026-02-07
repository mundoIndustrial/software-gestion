/**
 * PrendaTelasService - Gestión de telas, colores y referencias
 * 
 * Responsabilidades:
 * - Cargar telas desde API de cotización
 * - Transformar formato BD a frontend
 * - Enriquecer telas con referencias de variantes
 * - Procesar fotos de telas
 * - Mostrar preview de telas
 * - Gestionar ubicaciones reflectivas
 */
class PrendaTelasService {
    constructor(opciones = {}) {
        this.api = opciones.api;
        this.domAdapter = opciones.domAdapter;
        this.eventBus = opciones.eventBus;
    }

    /**
     * Cargar telas desde cotización (API)
     * Fetch → Procesa → Emite evento
     */
    async cargarTelasDesdeCotizacion(prenda) {
        if (!prenda.prenda_id || !prenda.cotizacion_id) {
            console.debug('[PrendaTelasService] prenda_id o cotizacion_id faltante');
            return;
        }

        console.log('[PrendaTelasService] 🧵 Cargando telas desde API:', {
            prendaId: prenda.prenda_id,
            cotizacionId: prenda.cotizacion_id
        });

        try {
            const datos = await this.api.cargarTelasDesdeCotizacion(
                prenda.cotizacion_id,
                prenda.prenda_id
            );

            const telas = datos.telas || datos.data?.telas || [];
            const variaciones = datos.variaciones || datos.data?.variaciones || [];
            const ubicaciones = datos.ubicaciones || datos.data?.ubicaciones || [];
            const descripcion = datos.descripcion || datos.data?.descripcion || '';

            console.log('[PrendaTelasService] Datos cargados:', {
                telas_count: telas.length,
                variaciones_count: variaciones.length,
                ubicaciones_count: ubicaciones.length
            });

            if (telas.length > 0) {
                const telasAgregadas = telas.map(tela => this.procesarTela(tela));
                prenda.telasAgregadas = telasAgregadas;

                this.eventBus?.emit(PrendaEventBus.EVENTOS.TELAS_DESDE_COTIZACION, {
                    cantidad: telasAgregadas.length,
                    telas: telasAgregadas
                });

                // Actualizar preview visual
                this.actualizarPreviewTelasCotizacion(telasAgregadas);
            }

            // Procesar ubicaciones
            if (ubicaciones.length > 0) {
                this.aplicarUbicacionesReflectivo(ubicaciones);
            }

            return {
                telas,
                variaciones,
                ubicaciones,
                descripcion
            };

        } catch (error) {
            console.error('[PrendaTelasService] Error cargando telas:', error);
            this.eventBus?.emit(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, {
                mensaje: error.message,
                contexto: 'cargarTelasDesdeCotizacion'
            });
        }
    }

    /**
     * Procesar una tela individual
     * @private
     */
    procesarTela(tela) {
        return {
            nombre_tela: tela.nombre_tela || tela.tela?.nombre || 'N/A',
            color: tela.color || 'N/A',
            referencia: tela.referencia || tela.tela?.referencia || 'N/A',
            fotos: this.procesarFotosTela(tela.fotos || []),
            origen: 'cotizacion',
            id_tela: tela.id_tela || tela.tela?.id
        };
    }

    /**
     * Procesar fotos de una tela
     * Convierte múltiples formatos a array de URLs
     * @private
     */
    procesarFotosTela(fotos) {
        if (!Array.isArray(fotos)) return [];

        return fotos
            .map(foto => {
                if (typeof foto === 'string') return foto;
                if (foto.url) return foto.url;
                if (foto.ruta) return foto.ruta;
                if (foto.ruta_webp) return foto.ruta_webp;
                return null;
            })
            .filter(url => !!url);
    }

    /**
     * Cargar telas en modal
     * Transforma varios formatos BD a estructura frontend
     */
    cargarTelas(prenda) {
        console.log('[PrendaTelasService] Cargando telas en modal');

        let telasParaCargar = prenda.telasAgregadas || [];

        // TRANSFORMACIÓN 1: colores_telas (BD) → telasAgregadas (frontend)
        if (telasParaCargar.length === 0 && prenda.colores_telas?.length > 0) {
            console.log('[PrendaTelasService] Transformando colores_telas');
            telasParaCargar = this.transformarColoresTelas(prenda.colores_telas);
            prenda.telasAgregadas = telasParaCargar;
        }

        // TRANSFORMACIÓN 2: variantes (BD) →telasAgregadas (frontend)
        if (telasParaCargar.length === 0 && prenda.variantes?.length > 0) {
            console.log('[PrendaTelasService] Transformando desde variantes');
            telasParaCargar = this.transformarVariantesTelas(prenda.variantes);
            prenda.telasAgregadas = telasParaCargar;
        }

        if (telasParaCargar.length === 0) {
            console.log('[PrendaTelasService] No hay telas para cargar');
            return;
        }

        // ENRIQUECIMIENTO: Si referencias vacías, buscar en variantes
        const referenciasVacias = telasParaCargar.some(t => !t.referencia || t.referencia === '');
        if (referenciasVacias && prenda.variantes) {
            console.log('[PrendaTelasService] Enriqueciendo telas con referencias desde variantes');
            telasParaCargar = this.enriquecerTelasConVariantes(telasParaCargar, prenda.variantes);
        }

        // Limpiar inputs y storage
        this.limpiarInputsTela();
        this.limpiarStorageTelas();

        // Asignar a window (compatibilidad)
        window.telasAgregadas = telasParaCargar;

        // Actualizar tabla si existe función
        if (typeof window.actualizarTablaTelas === 'function') {
            window.actualizarTablaTelas();
        }

        this.eventBus?.emit(PrendaEventBus.EVENTOS.TELAS_CARGADAS, {
            cantidad: telasParaCargar.length,
            telas: telasParaCargar
        });

        console.log('[PrendaTelasService] ✓ Telas cargadas:', telasParaCargar.length);
    }

    /**
     * Transformar colores_telas (formato BD) a telasAgregadas (formato frontend)
     * @private
     */
    transformarColoresTelas(coloresTelas) {
        return coloresTelas.map((ct, idx) => ({
            nombre_tela: ct.nombre_tela || ct.tela?.nombre || `Tela ${idx + 1}`,
            color: ct.color || 'N/A',
            referencia: ct.referencia || ct.tela?.referencia || 'N/A',
            fotos: this.procesarFotosTela(ct.fotos || []),
            origen: 'bd',
            id_color_tela: ct.id
        }));
    }

    /**
     * Transformar variantes a telas
     * Algunos reportes pueden traer telas en variantes
     * @private
     */
    transformarVariantesTelas(variantes) {
        if (!Array.isArray(variantes)) return [];

        const telas = [];

        variantes.forEach((variante, idx) => {
            if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                variante.telas_multiples.forEach((tela, tidx) => {
                    telas.push({
                        nombre_tela: tela.nombre_tela || tela.tela || `Tela ${tidx + 1}`,
                        color: tela.color || 'N/A',
                        referencia: tela.referencia || tela.ref || 'N/A',
                        fotos: [],
                        origen: 'variantes'
                    });
                });
            }
        });

        return telas;
    }

    /**
     * Enriquecer telas con referencias desde variantes
     * Si una tela no tiene referencia, buscar en variantes
     * @private
     */
    enriquecerTelasConVariantes(telas, variantes) {
        if (!variantes) return telas;

        const varArray = Array.isArray(variantes) ? variantes : Object.values(variantes);

        return telas.map((tela, idx) => {
            // Si ya tiene referencia, retornar sin cambios
            if (tela.referencia && tela.referencia !== 'N/A' && tela.referencia !== '') {
                return tela;
            }

            // Buscar en variantes por nombre de tela
            const varianteBuscada = varArray.find(v =>
                v.telas_multiples?.some(t => 
                    (t.nombre_tela?.toLowerCase() || '').includes((tela.nombre_tela || '').toLowerCase())
                )
            );

            if (varianteBuscada?.telas_multiples?.length > 0) {
                const teleEncontrada = varianteBuscada.telas_multiples[idx];
                if (teleEncontrada?.referencia) {
                    console.log(`[PrendaTelasService] Tela ${idx} enriquecida: ${teleEncontrada.referencia}`);
                    tela.referencia = teleEncontrada.referencia;
                    tela.origen = 'variantes-enriquecida';
                }
            }

            return tela;
        });
    }

    /**
     * Actualizar preview visual de telas desde cotización
     * Renderiza HTML con información visual de telas
     * @private
     */
    actualizarPreviewTelasCotizacion(telas) {
        const contenedor = this.domAdapter?.obtenerContenedorTelas();
        if (!contenedor) return;

        contenedor.innerHTML = '';

        telas.forEach((tela, idx) => {
            const divTela = document.createElement('div');
            divTela.className = 'tela-cotizacion-item';
            divTela.style.marginBottom = '1rem';
            divTela.style.padding = '0.75rem';
            divTela.style.border = '1px solid #e5e7eb';
            divTela.style.borderRadius = '4px';

            let fotosHTML = '';
            if (tela.fotos && tela.fotos.length > 0) {
                fotosHTML = `
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                        ${tela.fotos.map((foto, fidx) => `
                            <img 
                                src="${foto}" 
                                alt="Foto tela ${fidx + 1}" 
                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                            />
                        `).join('')}
                    </div>
                `;
            }

            divTela.innerHTML = `
                <div style="font-weight: bold; margin-bottom: 0.5rem;">${tela.nombre_tela}</div>
                <div style="color: #666; font-size: 0.9rem;">
                    Color: ${tela.color} | Ref: ${tela.referencia}
                </div>
                ${fotosHTML}
            `;

            contenedor.appendChild(divTela);
        });

        console.log('[PrendaTelasService] Preview actualizado:', telas.length);
    }

    /**
     * Aplicar ubicaciones reflectivas al formulario
     */
    aplicarUbicacionesReflectivo(ubicaciones) {
        if (!ubicaciones || ubicaciones.length === 0) return;

        console.log('[PrendaTelasService] 📍 Aplicando ubicaciones:', ubicaciones.length);

        const contenedor = this.domAdapter?.obtenerContenedorUbicaciones();
        if (!contenedor) return;

        let html = '';
        ubicaciones.forEach((ubi) => {
            html += `
                <div style="margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <div><strong>📍 ${ubi.ubicacion || 'Ubicación'}</strong></div>
                    <div style="color: #666; font-size: 0.9rem;">${ubi.descripcion || ''}</div>
                </div>
            `;
        });

        contenedor.innerHTML = html;
        this.eventBus?.emit(PrendaEventBus.EVENTOS.PREVIEW_ACTUALIZADO, {
            tipo: 'ubicaciones',
            cantidad: ubicaciones.length
        });
    }

    /**
     * Limpiar inputs de tela
     * @private
     */
    limpiarInputsTela() {
        if (this.domAdapter) {
            this.domAdapter.limpiarInputsTela();
        }
    }

    /**
     * Limpiar storage de telas
     * @private
     */
    limpiarStorageTelas() {
        if (window.imagenesTelaStorage && typeof window.imagenesTelaStorage.limpiar === 'function') {
            window.imagenesTelaStorage.limpiar();
        }
    }

    /**
     * Validar tela antes de guardar
     */
    validarTela(tela) {
        const errores = [];

        if (!tela.nombre_tela || !tela.nombre_tela.trim()) {
            errores.push('El nombre de la tela es obligatorio');
        }

        if (!tela.color || !tela.color.trim()) {
            errores.push('El color es obligatorio');
        }

        if (!tela.referencia || !tela.referencia.trim()) {
            errores.push('La referencia es obligatoria');
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Obtener telas agregadas normalizadas
     */
    obtenerTelasNormalizadas(telasAgregadas) {
        return telasAgregadas.map(tela => ({
            nombre_tela: (tela.nombre_tela || '').trim(),
            color: (tela.color || '').trim(),
            referencia: (tela.referencia || '').trim(),
            fotos: Array.isArray(tela.fotos) ? tela.fotos : []
        }));
    }
}

window.PrendaTelasService = PrendaTelasService;
