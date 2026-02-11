/**
 * Servicio de Captura de Datos del Formulario
 * Extrae datos del DOM para generar la vista previa de la factura
 */

class FormDataCaptureService {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.capturarDatosFormulario = this.capturarDatosFormulario.bind(this);
        window.capturarPrendas = this.capturarPrendas.bind(this);
        window.capturarProcesos = this.capturarProcesos.bind(this);
        window.capturarEPP = this.capturarEPP.bind(this);
    }

    /**
     * Captura los datos del formulario de creación de pedido
     */
    capturarDatosFormulario() {
        // Información básica
        const cliente = document.getElementById('cliente_editable')?.value || 'Cliente Nuevo';
        const asesora = document.getElementById('asesora_editable')?.value || 'Sin asignar';
        const formaPago = document.getElementById('forma_de_pago_editable')?.value || 'No especificada';
        
        if (!cliente || cliente.trim() === '') {
            return null;
        }
        
        // Capturar prendas/ítems
        const prendas = this.capturarPrendas();
        
        // Capturar procesos seleccionados
        const procesos = this.capturarProcesos();
        
        // Capturar EPP seleccionado
        const epp = this.capturarEPP();
        
        // Fecha actual
        const fechaHoy = new Date();
        
        const datos = {
            cliente: cliente.trim(),
            asesora: asesora.trim(),
            forma_de_pago: formaPago.trim(),
            fecha_creacion: fechaHoy.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            }),
            prendas: prendas,
            procesos: procesos,
            epp: epp,
            numero_pedido_temporal: Math.floor(Math.random() * 90000) + 10000
        };
        
        return datos;
    }

    /**
     * Captura las prendas del formulario usando el GestorPrendaSinCotizacion
     */
    capturarPrendas() {
        const prendas = [];
        
        // Verificar si el gestor existe, con múltiples intentos
        let gestor = window.GestorPrendaSinCotizacion || window.gestorPrendaSinCotizacion;
        
        if (!gestor && window.parent && window.parent.GestorPrendaSinCotizacion) {
            gestor = window.parent.GestorPrendaSinCotizacion;
        }
        
        if (!gestor && window.parent && window.parent.gestorPrendaSinCotizacion) {
            gestor = window.parent.gestorPrendaSinCotizacion;
        }
        
        if (!gestor) {
            // Intentar obtener del elemento data si existe
            const formElement = document.querySelector('[data-gestor-prendas]');
            if (formElement && formElement.__gestorPrendas) {
                gestor = formElement.__gestorPrendas;
            }
        }
        
        if (!gestor) {
            return prendas;
        }
        
        try {
            // Obtener todas las prendas del gestor
            const prendasDelGestor = gestor.obtenerActivas ? gestor.obtenerActivas() : 
                                     (gestor.prendas ? Object.values(gestor.prendas) : []);
            
            prendasDelGestor.forEach((prenda, index) => {
                // Extraer procesos con todos sus detalles
                const procesos = this.extraerProcesosDePrenda(prenda);
                
                // Extraer datos de variantes
                const variantes = prenda.variantes || {};
                const tipoManga = variantes.tipo_manga || prenda.tipo_manga || '';
                const obsManga = variantes.obs_manga || prenda.obs_manga || '';
                const tipoBroche = variantes.tipo_broche || prenda.tipo_broche || '';
                const obsBroche = variantes.obs_broche || prenda.obs_broche || '';
                const tieneBolsillos = variantes.tiene_bolsillos !== undefined ? variantes.tiene_bolsillos : (prenda.tiene_bolsillos || false);
                const obsBolsillos = variantes.obs_bolsillos || prenda.obs_bolsillos || '';
                const tienereflectivo = variantes.tiene_reflectivo !== undefined ? variantes.tiene_reflectivo : (prenda.tiene_reflectivo || false);
                
                // Calcular cantidad total desde tallas
                let cantidadTotal = 0;
                
                // Detectar imagen de prenda
                const imagenCapturada = this.extraerImagenPrenda(prenda);
                
                // Detectar imagen de tela
                const imagenTelaCapturada = this.extraerImagenTela(prenda);
                
                // Detectar tela, color y referencia
                const { telaCapturada, colorCapturado, refCapturada } = this.extraerDatosTela(prenda, variantes);
                
                // Extraer tallas
                const tallasReconstruidas = this.extraerTallas(prenda);
                
                // Calcular cantidad total
                cantidadTotal = this.calcularCantidadTotal(tallasReconstruidas);
                
                // Capturar colores asignados por talla
                const asignacionesCapturadas = this.capturarAsignacionesColores(prenda);
                
                // Extraer imágenes de prenda y tela
                const imagenesPrenda = this.extraerImagenesPrenda(prenda, imagenCapturada);
                const imagenesTela = this.extraerImagenesTela(prenda, imagenTelaCapturada);
                
                prendas.push({
                    numero: index + 1,
                    nombre: prenda.nombre_producto || prenda.nombre || `Prenda ${index + 1}`,
                    descripcion: prenda.descripcion || '',
                    ref: refCapturada,
                    imagen: imagenCapturada,
                    imagenes: imagenesPrenda,
                    imagen_tela: imagenTelaCapturada,
                    imagenes_tela: imagenesTela,
                    manga: tipoManga && tipoManga !== 'No aplica' ? tipoManga : '',
                    obs_manga: obsManga,
                    broche: tipoBroche && tipoBroche !== 'No aplica' ? tipoBroche : '',
                    obs_broche: obsBroche,
                    color: colorCapturado,
                    tela: telaCapturada,
                    talla_referencia: prenda.talla_referencia || '',
                    variantes: prenda.variantes || {},
                    origen: prenda.origen || 'Confección',
                    de_bodega: prenda.de_bodega || 0,
                    tallas: tallasReconstruidas,
                    cantidad: cantidadTotal,
                    tiene_bolsillos: tieneBolsillos,
                    obs_bolsillos: obsBolsillos,
                    tiene_reflectivo: tienereflectivo,
                    procesos: procesos,
                    asignacionesColoresPorTalla: asignacionesCapturadas
                });
            });
        } catch (error) {
            console.error('[FormDataCaptureService] Error capturando prendas:', error);
        }
        
        return prendas;
    }

    extraerProcesosDePrenda(prenda) {
        const procesos = [];
        
        if (prenda.procesos && typeof prenda.procesos === 'object') {
            Object.entries(prenda.procesos).forEach(([key, proc]) => {
                // Obtener los datos del proceso
                const procDatos = proc?.datos || proc;
                const procTipo = proc?.tipo || procDatos?.tipo;
                
                if (procTipo && procDatos) {
                    // Extraer tallas por género
                    const tallasProceso = this.extraerTallasProceso(procDatos);
                    
                    // Extraer ubicaciones
                    const ubicaciones = this.extraerUbicaciones(procDatos);
                    
                    // Extraer imágenes y mapear a URLs
                    const imagenes = this.mapearImagenesAURLs(procDatos.imagenes || []);
                    
                    const procObj = {
                        tipo: procTipo,
                        ubicaciones: ubicaciones,
                        observaciones: procDatos.observaciones || '',
                        imagenes: imagenes,
                        tallas: tallasProceso
                    };
                    procesos.push(procObj);
                }
            });
        }
        
        return procesos;
    }

    extraerTallasProceso(procDatos) {
        const tallasProceso = {};
        
        if (procDatos.tallas && typeof procDatos.tallas === 'object' && !Array.isArray(procDatos.tallas)) {
            Object.entries(procDatos.tallas).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0) {
                    tallasProceso[genero] = tallasObj;
                }
            });
        }
        
        return tallasProceso;
    }

    extraerUbicaciones(procDatos) {
        let ubicaciones = procDatos.ubicaciones || [];
        
        if (typeof ubicaciones === 'string') {
            ubicaciones = [ubicaciones];
        } else if (!Array.isArray(ubicaciones)) {
            ubicaciones = [];
        }
        
        return ubicaciones;
    }

    mapearImagenesAURLs(imagenes) {
        let imagenesMapeadas = imagenes || [];
        
        if (typeof imagenesMapeadas === 'string') {
            imagenesMapeadas = [imagenesMapeadas];
        } else if (!Array.isArray(imagenesMapeadas)) {
            imagenesMapeadas = [];
        }
        
        return imagenesMapeadas.map(img => {
            if (img instanceof File) {
                return URL.createObjectURL(img);
            } else if (img?.file instanceof File) {
                return URL.createObjectURL(img.file);
            } else if (img?.blobUrl) {
                return img.blobUrl;
            } else if (img?.ruta_webp) {
                return '/storage/' + img.ruta_webp;
            } else if (img?.ruta_original) {
                return '/storage/' + img.ruta_original;
            } else if (typeof img === 'string') {
                return img.startsWith('/storage/') ? img : '/storage/' + img;
            } else if (img?.url) {
                return img.url;
            } else if (img?.ruta) {
                return img.ruta;
            } else if (img?.path) {
                return img.path;
            } else if (img?.src) {
                return img.src;
            } else {
                return '';
            }
        }).filter(url => url);
    }

    extraerImagenPrenda(prenda) {
        if (prenda.imagen) {
            return typeof prenda.imagen === 'string' ? prenda.imagen : (prenda.imagen?.url || prenda.imagen?.ruta || prenda.imagen?.src || '');
        } else if (prenda.imagen_prenda) {
            return typeof prenda.imagen_prenda === 'string' ? prenda.imagen_prenda : (prenda.imagen_prenda?.url || prenda.imagen_prenda?.ruta || prenda.imagen_prenda?.src || '');
        } else if (prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            const firstFoto = prenda.fotos[0];
            return typeof firstFoto === 'string' ? firstFoto : (firstFoto?.blobUrl || firstFoto?.src || firstFoto?.url || '');
        } else if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            const firstImagen = prenda.imagenes[0];
            
            if (firstImagen?.file instanceof File) {
                return URL.createObjectURL(firstImagen.file);
            } else {
                return firstImagen?.previewUrl || firstImagen?.blobUrl || firstImagen?.src || firstImagen?.url || firstImagen?.ruta || firstImagen?.path || firstImagen?.data || (typeof firstImagen === 'string' ? firstImagen : '');
            }
        }
        
        return '';
    }

    extraerImagenTela(prenda) {
        if (prenda.imagen_tela) {
            return typeof prenda.imagen_tela === 'string' ? prenda.imagen_tela : (prenda.imagen_tela?.url || prenda.imagen_tela?.ruta || prenda.imagen_tela?.src || '');
        } else if (prenda.muestra_tela) {
            return typeof prenda.muestra_tela === 'string' ? prenda.muestra_tela : (prenda.muestra_tela?.url || prenda.muestra_tela?.ruta || prenda.muestra_tela?.src || '');
        } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela) && prenda.imagenes_tela.length > 0) {
            const firstTela = prenda.imagenes_tela[0];
            return typeof firstTela === 'string' ? firstTela : (firstTela?.url || firstTela?.ruta || firstTela?.blobUrl || firstTela?.src || firstTela?.path || firstTela?.data || '');
        } else if (prenda.telaFotos && Array.isArray(prenda.telaFotos) && prenda.telaFotos.length > 0) {
            const firstTelaFoto = prenda.telaFotos[0];
            return typeof firstTelaFoto === 'string' ? firstTelaFoto : (firstTelaFoto?.url || firstTelaFoto?.ruta || firstTelaFoto?.blobUrl || firstTelaFoto?.src || firstTelaFoto?.path || firstTelaFoto?.data || '');
        } else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0 && 
                   prenda.telasAgregadas[0].imagenes && Array.isArray(prenda.telasAgregadas[0].imagenes)) {
            const firstTelaAg = prenda.telasAgregadas[0].imagenes[0];
            
            if (firstTelaAg instanceof File) {
                return URL.createObjectURL(firstTelaAg);
            } else {
                return typeof firstTelaAg === 'string' ? firstTelaAg : (firstTelaAg?.blobUrl || firstTelaAg?.previewUrl || firstTelaAg?.src || firstTelaAg?.url || firstTelaAg?.data || '');
            }
        }
        
        return '';
    }

    extraerDatosTela(prenda, variantes) {
        let telaCapturada = prenda.tela || variantes.tela || '';
        let colorCapturado = prenda.color || variantes.color || '';
        let refCapturada = prenda.ref || '';
        
        if (!refCapturada || !telaCapturada || !colorCapturado) {
            if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
                const primeTela = prenda.telasAgregadas[0];
                if (!telaCapturada) telaCapturada = primeTela.tela || '';
                if (!colorCapturado) colorCapturado = primeTela.color || '';
                if (!refCapturada) refCapturada = primeTela.referencia || '';
            }
        }
        
        return { telaCapturada, colorCapturado, refCapturada };
    }

    extraerTallas(prenda) {
        let tallasReconstruidas = {};
        
        if (prenda.tallas && typeof prenda.tallas === 'object' && !Array.isArray(prenda.tallas) && Object.keys(prenda.tallas).length > 0) {
            Object.entries(prenda.tallas).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object' && !Array.isArray(tallasObj) && Object.keys(tallasObj).length > 0) {
                    tallasReconstruidas[genero] = tallasObj;
                }
            });
        }
        
        return tallasReconstruidas;
    }

    calcularCantidadTotal(tallasReconstruidas) {
        return Object.values(tallasReconstruidas).reduce((sum, generoTallas) => {
            if (typeof generoTallas === 'object' && !Array.isArray(generoTallas)) {
                return sum + Object.values(generoTallas).reduce((s, cant) => s + (parseInt(cant) || 0), 0);
            }
            return sum;
        }, 0);
    }

    capturarAsignacionesColores(prenda) {
        let asignacionesCapturadas = {};
        
        if (prenda.asignacionesColoresPorTalla && typeof prenda.asignacionesColoresPorTalla === 'object') {
            asignacionesCapturadas = { ...prenda.asignacionesColoresPorTalla };
        }
        
        console.log(`[CAPTURA-PRENDAS] Prenda "${prenda.nombre}":`, {
            tieneAsignaciones: !!asignacionesCapturadas,
            asignacionesKeys: Object.keys(asignacionesCapturadas),
            asignacionesCount: Object.keys(asignacionesCapturadas).length,
            asignacionesCompleta: asignacionesCapturadas
        });
        
        return asignacionesCapturadas;
    }

    extraerImagenesPrenda(prenda, imagenCapturada) {
        if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
            return prenda.imagenes.map(img => {
                if (img instanceof File) {
                    return URL.createObjectURL(img);
                }
                const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;
                return rutaFinal;
            });
        } else if (imagenCapturada) {
            return [imagenCapturada];
        }
        
        return [];
    }

    extraerImagenesTela(prenda, imagenTelaCapturada) {
        let imagenesTelaArr = [];
        
        if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            imagenesTelaArr = prenda.imagenes_tela.map(img => {
                if (img instanceof File) {
                    return URL.createObjectURL(img);
                }
                const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;
                return rutaFinal;
            });
        } else if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            imagenesTelaArr = prenda.telasAgregadas
                .filter(t => t.imagenes && t.imagenes.length > 0)
                .flatMap(t => t.imagenes.map(img => {
                    if (img instanceof File) {
                        return URL.createObjectURL(img);
                    }
                    const rutaFinal = img.blobUrl || img.previewUrl || img.src || img.url || img.ruta || img;
                    return rutaFinal;
                }));
        } else if (imagenTelaCapturada) {
            imagenesTelaArr = [imagenTelaCapturada];
        }
        
        return imagenesTelaArr;
    }

    /**
     * Captura los procesos seleccionados globales
     */
    capturarProcesos() {
        const procesos = [];
        
        // Buscar procesos globales en el formulario
        const procesosCheckboxes = document.querySelectorAll('input[type="checkbox"][name*="proceso_general"], input[type="checkbox"][name*="process_general"], input[type="checkbox"][name*="procesos"]');
        
        procesosCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const label = document.querySelector(`label[for="${checkbox.id}"]`)?.textContent || checkbox.value;
                procesos.push(label.trim());
            }
        });
        
        return procesos;
    }

    /**
     * Captura el EPP seleccionado del DOM
     */
    capturarEPP() {
        const epp = [];
        
        // Obtener todos los items EPP del DOM
        const itemsEPP = document.querySelectorAll('.item-epp[data-item-tipo="epp"]');
        
        itemsEPP.forEach(item => {
            const id = item.dataset.itemId;
            const categoria = item.querySelector('[style*="color: #0066cc"]')?.textContent || '';
            const nombre = item.querySelector('h4')?.textContent || '';
            
            // Extraer información de la etiqueta p
            const infoTexto = item.querySelector('p[style*="color: #6b7280"]')?.textContent || '';
            
            // Extraer imágenes
            const imagenes = [];
            const imagenesDiv = item.querySelector('[style*="grid-template-columns"]');
            if (imagenesDiv) {
                const imgs = imagenesDiv.querySelectorAll('img');
                imgs.forEach(img => {
                    if (img.src) imagenes.push(img.src);
                });
            }
            
            epp.push({
                id: id,
                nombre: nombre,
                categoria: categoria,
                info: infoTexto,
                imagenes: imagenes
            });
        });
        
        return epp;
    }
}

// Inicializar el servicio cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.formDataCaptureService = new FormDataCaptureService();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.formDataCaptureService = new FormDataCaptureService();
    });
} else {
    window.formDataCaptureService = new FormDataCaptureService();
}
