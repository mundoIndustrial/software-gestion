/**
 * Gestor de Galerías de Imágenes
 * Maneja la visualización de imágenes en modales con navegación
 */

class ImageGalleryManager {
    constructor() {
        this.galerías = {};
        this.idGaleria = 0;
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window._extraerURLImagen = this.extraerURLImagen.bind(this);
        window._registrarGalería = this.registrarGalería.bind(this);
        window._abrirGaleriaImagenesDesdeID = this.abrirGaleriaImagenesDesdeID.bind(this);
        window._abrirGaleriaImagenes = this.abrirGaleriaImagenes.bind(this);
        
        // Mantener compatibilidad con variables globales existentes
        window._galeríasPreview = this.galerías;
        window._idGaleriaPreview = 0;
    }

    /**
     * Extrae URL de una imagen (puede ser string u objeto)
     */
    extraerURLImagen(img) {
        if (!img) {
            return '';
        }
        
        let url = '';
        
        if (typeof img === 'string') {
            url = img;
        } else if (typeof img === 'object') {
            if (img.ruta_webp) {
                url = img.ruta_webp;
            } else if (img.ruta_web) {
                url = img.ruta_web;
            } else if (img.ruta_original) {
                url = img.ruta_original;
            } else if (img.url) {
                url = img.url;
            } else if (img.ruta) {
                url = img.ruta;
            } else if (img.path) {
                url = img.path;
            } else if (img.src) {
                url = img.src;
            }
        }
        
        // Procesar la URL para evitar duplicación de /storage/
        if (url && typeof url === 'string') {
            if (url.startsWith('/storage/')) {
                return url;
            } else if (url.startsWith('storage/')) {
                url = '/' + url;
                return url;
            } else {
                url = '/storage/' + url;
                return url;
            }
        }
        
        return '';
    }

    /**
     * Registra una galería de imágenes y retorna un ID único
     */
    registrarGalería(imagenes, titulo) {
        if (!Array.isArray(imagenes) || imagenes.length === 0) return null;
        
        const id = this.idGaleria++;
        this.galerías[id] = { imagenes, titulo };
        
        // Sincronizar con variable global para compatibilidad
        window._idGaleriaPreview = this.idGaleria;
        
        return id;
    }

    /**
     * Abre una galería usando su ID registrado
     */
    abrirGaleriaImagenesDesdeID(galeriaId) {
        if (galeriaId === null || galeriaId === undefined || !this.galerías[galeriaId]) {
            return;
        }
        
        const { imagenes, titulo } = this.galerías[galeriaId];
        this.abrirGaleriaImagenes(imagenes, titulo);
    }

    /**
     * Abre una galería de imágenes en un modal
     */
    abrirGaleriaImagenes(imagenes, titulo = 'Galería') {
        console.log('[GALERIA-DEBUG] Abriendo galería:', { titulo, cantidadImagenes: imagenes?.length });
        
        if (!Array.isArray(imagenes) || imagenes.length === 0) {
            console.warn('[GALERIA-DEBUG] Array de imágenes vacío o inválido');
            return;
        }
        
        // Normalizar imagenes
        const imagenesNormalizadas = imagenes.map(img => {
            if (typeof img === 'string') {
                return this.extraerURLImagen(img);
            } else {
                return this.extraerURLImagen(img);
            }
        });
        
        console.log('[GALERIA-DEBUG] URLs normalizadas:', { cantidad: imagenesNormalizadas.length, primeraURL: imagenesNormalizadas[0] });
        
        // Agregar estilos si no existen
        this.agregarEstilosGaleria();
        
        // Crear modal
        const modalGaleria = this.crearModalGaleria(imagenesNormalizadas, titulo);
        
        // Agregar al DOM
        document.body.appendChild(modalGaleria);
        
        // Configurar eventos
        this.configurarEventosModal(modalGaleria, imagenesNormalizadas);
    }

    agregarEstilosGaleria() {
        if (!document.getElementById('galeria-styles')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'galeria-styles';
            styleSheet.textContent = `
                @keyframes galeriaFadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes galeriaZoomIn {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }
                
                #galeria-imagenes-modal {
                    animation: galeriaFadeIn 0.3s ease;
                }
                
                .galeria-contenedor {
                    animation: galeriaZoomIn 0.3s ease;
                }
                
                .galeria-imagen-wrapper {
                    position: relative;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(20,20,20,0.8) 100%);
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
                    flex-grow: 0;
                    width: 650px !important;
                    height: 450px !important;
                    max-width: none;
                    max-height: none;
                }
                
                .galeria-img {
                    width: 600px !important;
                    height: 400px !important;
                    object-fit: contain;
                    border-radius: 8px;
                    box-shadow: none;
                }
                
                .galeria-titulo {
                    color: white;
                    font-size: 13px;
                    font-weight: 700;
                    margin-bottom: 5px;
                    text-align: center;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
                    letter-spacing: 0.5px;
                    padding: 5px 20px;
                }
                
                .galeria-navegacion {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    margin-top: 0;
                    justify-content: center;
                    flex-wrap: wrap;
                    padding: 8px 20px;
                    background: rgba(0, 0, 0, 0.5);
                    width: 100%;
                }
                
                .galeria-btn {
                    padding: 8px 12px;
                    background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 12px;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }
                
                .galeria-btn:hover {
                    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4);
                }
                
                .galeria-btn:active {
                    transform: translateY(0);
                }
                
                .galeria-contador {
                    color: white;
                    font-size: 12px;
                    font-weight: 600;
                    background: rgba(255, 255, 255, 0.1);
                    padding: 6px 10px;
                    border-radius: 6px;
                    min-width: 70px;
                    text-align: center;
                    backdrop-filter: blur(4px);
                }
                
                .galeria-btn-cerrar {
                    position: fixed;
                    top: 15px;
                    right: 15px;
                    padding: 10px 14px;
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    z-index: 10004;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .galeria-btn-cerrar:hover {
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    transform: scale(1.05);
                }
                
                .galeria-thumbnails {
                    display: flex;
                    gap: 8px;
                    margin-top: 15px;
                    overflow-x: auto;
                    padding: 0 10px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                
                .galeria-thumbnail {
                    width: 60px;
                    height: 60px;
                    border-radius: 6px;
                    cursor: pointer;
                    border: 3px solid transparent;
                    opacity: 0.6;
                    transition: all 0.3s ease;
                    object-fit: cover;
                    flex-shrink: 0;
                }
                
                .galeria-thumbnail:hover {
                    opacity: 0.9;
                    transform: scale(1.05);
                }
                
                .galeria-thumbnail.activo {
                    border-color: #1e40af;
                    opacity: 1;
                    box-shadow: 0 0 10px rgba(30, 64, 175, 0.5);
                }
            `;
            document.head.appendChild(styleSheet);
        }
    }

    crearModalGaleria(imagenesNormalizadas, titulo) {
        const modalGaleria = document.createElement('div');
        modalGaleria.id = 'galeria-imagenes-modal';
        modalGaleria.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.98);
            z-index: 10002;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            overflow: hidden;
        `;
        
        let indiceActual = 0;
        
        const contenido = document.createElement('div');
        contenido.className = 'galeria-contenedor';
        contenido.style.cssText = `
            position: relative;
            max-width: 700px;
            width: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0;
        `;
        
        // Título
        const titulo_elem = document.createElement('div');
        titulo_elem.className = 'galeria-titulo';
        titulo_elem.textContent = titulo;
        
        // Imagen
        const imagenWrapper = document.createElement('div');
        imagenWrapper.className = 'galeria-imagen-wrapper';
        
        const imagen = document.createElement('img');
        imagen.className = 'galeria-img';
        imagen.src = imagenesNormalizadas[indiceActual];
        imagen.alt = titulo;
        
        imagen.onload = function() {
            console.log('[GALERIA-DEBUG] 📸 Imagen cargada:', {
                src: this.src,
                naturalWidth: this.naturalWidth,
                naturalHeight: this.naturalHeight
            });
        };
        
        imagen.onerror = function() {
            console.error('[GALERIA-DEBUG] Error cargando imagen:', this.src);
        };
        
        imagenWrapper.appendChild(imagen);
        
        // Navegación
        const navegacion = this.crearNavegacionGaleria(imagenesNormalizadas, indiceActual, imagen);
        
        // Thumbnails
        const thumbnails = this.crearThumbnails(imagenesNormalizadas, indiceActual, imagen);
        
        // Botón cerrar
        const btnCerrar = this.crearBotonCerrar(modalGaleria);
        
        // Ensamblar
        contenido.appendChild(btnCerrar);
        contenido.appendChild(imagenWrapper);
        contenido.appendChild(navegacion);
        if (imagenesNormalizadas.length > 1) {
            contenido.appendChild(thumbnails);
        }
        
        modalGaleria.appendChild(contenido);
        
        return modalGaleria;
    }

    crearNavegacionGaleria(imagenesNormalizadas, indiceActual, imagen) {
        const navegacion = document.createElement('div');
        navegacion.className = 'galeria-navegacion';
        
        const btnAnterior = document.createElement('button');
        btnAnterior.className = 'galeria-btn';
        btnAnterior.innerHTML = '<span>←</span> Anterior';
        btnAnterior.disabled = imagenesNormalizadas.length <= 1;
        btnAnterior.onclick = () => {
            indiceActual = (indiceActual - 1 + imagenesNormalizadas.length) % imagenesNormalizadas.length;
            imagen.src = imagenesNormalizadas[indiceActual];
            this.actualizarContador(navegacion.querySelector('.galeria-contador'), indiceActual + 1, imagenesNormalizadas.length);
            this.actualizarThumbnails(indiceActual);
        };
        
        const contador = document.createElement('span');
        contador.className = 'galeria-contador';
        contador.textContent = `${indiceActual + 1} / ${imagenesNormalizadas.length}`;
        
        const btnSiguiente = document.createElement('button');
        btnSiguiente.className = 'galeria-btn';
        btnSiguiente.innerHTML = 'Siguiente <span>→</span>';
        btnSiguiente.disabled = imagenesNormalizadas.length <= 1;
        btnSiguiente.onclick = () => {
            indiceActual = (indiceActual + 1) % imagenesNormalizadas.length;
            imagen.src = imagenesNormalizadas[indiceActual];
            this.actualizarContador(contador, indiceActual + 1, imagenesNormalizadas.length);
            this.actualizarThumbnails(indiceActual);
        };
        
        navegacion.appendChild(btnAnterior);
        navegacion.appendChild(contador);
        navegacion.appendChild(btnSiguiente);
        
        return navegacion;
    }

    crearThumbnails(imagenesNormalizadas, indiceActual, imagenPrincipal) {
        const thumbnails = document.createElement('div');
        thumbnails.className = 'galeria-thumbnails';
        
        imagenesNormalizadas.forEach((imgSrc, idx) => {
            const thumb = document.createElement('img');
            thumb.className = 'galeria-thumbnail';
            if (idx === 0) thumb.classList.add('activo');
            thumb.src = imgSrc;
            thumb.alt = `Imagen ${idx + 1}`;
            thumb.onclick = () => {
                indiceActual = idx;
                imagenPrincipal.src = imagenesNormalizadas[indiceActual];
                this.actualizarThumbnails(indiceActual);
                
                // Actualizar contador
                const contador = document.querySelector('.galeria-contador');
                if (contador) {
                    contador.textContent = `${indiceActual + 1} / ${imagenesNormalizadas.length}`;
                }
            };
            thumbnails.appendChild(thumb);
        });
        
        return thumbnails;
    }

    crearBotonCerrar(modalGaleria) {
        const btnCerrar = document.createElement('button');
        btnCerrar.className = 'galeria-btn-cerrar';
        btnCerrar.innerHTML = '<span>✕</span> Cerrar';
        btnCerrar.onclick = () => {
            modalGaleria.style.opacity = '0';
            setTimeout(() => modalGaleria.remove(), 300);
        };
        return btnCerrar;
    }

    actualizarContador(contador, actual, total) {
        if (contador) {
            contador.textContent = `${actual} / ${total}`;
        }
    }

    actualizarThumbnails(indiceActual) {
        document.querySelectorAll('.galeria-thumbnail').forEach((thumb, idx) => {
            if (idx === indiceActual) {
                thumb.classList.add('activo');
            } else {
                thumb.classList.remove('activo');
            }
        });
    }

    configurarEventosModal(modalGaleria, imagenesNormalizadas) {
        // Cerrar al hacer click en el fondo
        modalGaleria.addEventListener('click', (e) => {
            if (e.target === modalGaleria) {
                modalGaleria.style.opacity = '0';
                setTimeout(() => modalGaleria.remove(), 300);
            }
        });
        
        // Soporte para teclado
        const manejadorTeclado = (e) => {
            if (document.getElementById('galeria-imagenes-modal')) {
                const btnAnterior = document.querySelector('.galeria-btn');
                const btnSiguiente = document.querySelectorAll('.galeria-btn')[1];
                
                if (e.key === 'ArrowLeft' && btnAnterior) btnAnterior.click();
                if (e.key === 'ArrowRight' && btnSiguiente) btnSiguiente.click();
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', manejadorTeclado);
                    modalGaleria.style.opacity = '0';
                    setTimeout(() => modalGaleria.remove(), 300);
                }
            }
        };
        document.addEventListener('keydown', manejadorTeclado);
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.imageGalleryManager = new ImageGalleryManager();
});

// También permitir inicialización manual si DOMContentLoaded ya ocurrió
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.imageGalleryManager = new ImageGalleryManager();
    });
} else {
    window.imageGalleryManager = new ImageGalleryManager();
}
