/**
 * GaleriaService - Centraliza toda la lógica de galerías de imágenes
 * 
 * Elimina 290 líneas de código procedural de pedidos.js
 * Proporciona interfaz simple para mostrar/gestionar galerías
 * 
 * Uso:
 * - Galeria.toggleFactura(modalWrapperId, btnFacturaId, btnGaleriaId)
 * - Galeria.toggleGaleria(modalWrapperId, pedidoElementId, btnFacturaId, btnGaleriaId)
 * - Galeria.mostrarImagenGrande(index)
 * - Galeria.cerrarImagenGrande()
 * - Galeria.cambiarImagen(direccion)
 */

window.Galeria = {
    // Variables de estado
    allImages: [],
    currentImageIndex: 0,
    
    /**
     * Muestra la factura de costura (oculta galería)
     */
    toggleFactura: function(modalWrapperId, btnFacturaId, btnGaleriaId) {

        
        const modalWrapper = document.getElementById(modalWrapperId);
        if (!modalWrapper) {

            return;
        }
        
        // Mostrar factura
        const card = modalWrapper.querySelector('.order-detail-card');
        if (card) card.style.display = 'block';
        
        const galeria = document.getElementById('galeria-modal-costura');
        if (galeria) galeria.style.display = 'none';
        
        // Restaurar estilos del contenedor
        const container = modalWrapper.querySelector('.order-detail-modal-container');
        if (container) {
            container.style.padding = '1.5cm';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
            container.style.height = 'auto';
            container.style.width = '100%';
        }
        
        modalWrapper.style.maxWidth = '672px';
        modalWrapper.style.width = '90%';
        modalWrapper.style.height = 'auto';
        
        this._actualizarBotones(btnFacturaId, btnGaleriaId, true);

    },
    
    /**
     * Alterna a vista de galería de costura
     */
    toggleGaleria: function(modalWrapperId, pedidoElementId, btnFacturaId, btnGaleriaId) {

        
        const modalWrapper = document.getElementById(modalWrapperId);
        if (!modalWrapper) {

            return;
        }
        
        // Ocultar factura
        const card = modalWrapper.querySelector('.order-detail-card');
        if (card) card.style.display = 'none';
        
        // Obtener o crear contenedor de galería
        let galeria = document.getElementById('galeria-modal-costura');
        const container = modalWrapper.querySelector('.order-detail-modal-container');
        
        if (!container) {

            return;
        }
        
        if (!galeria) {
            galeria = document.createElement('div');
            galeria.id = 'galeria-modal-costura';
            galeria.style.cssText = 'width: 100%; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 400px; max-height: 600px; overflow-y: auto;';
            container.appendChild(galeria);
        }
        
        // Ajustar estilos del contenedor
        container.style.padding = '0';
        container.style.alignItems = 'stretch';
        container.style.justifyContent = 'flex-start';
        container.style.height = 'auto';
        container.style.width = '100%';
        
        galeria.style.display = 'flex';
        
        // Extraer número de pedido
        const pedidoElement = document.getElementById(pedidoElementId);
        if (!pedidoElement) {

            galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
            return;
        }
        
        const pedidoMatch = pedidoElement.textContent.match(/\d+/);
        const pedido = pedidoMatch ? pedidoMatch[0] : null;
        
        if (!pedido) {

            galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
            return;
        }
        
        // Cargar imágenes
        this._cargarImagenes(galeria, pedido);
        this._actualizarBotones(btnFacturaId, btnGaleriaId, false);

    },
    
    /**
     * Carga las imágenes de costura desde el servidor
     * @private
     */
    _cargarImagenes: function(container, pedido) {
        const pedidoLimpio = pedido.replace('#', '');
        const url = `/registros/${pedidoLimpio}/images`;
        

        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this._construirGaleria(container, data);
            })
            .catch(error => {

                container.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error al cargar las fotos. Intenta nuevamente.</p>';
            });
    },
    
    /**
     * Construye el HTML de la galería
     * @private
     */
    _construirGaleria: function(container, data) {
        this.allImages = [];
        let html = this._crearHeader();
        html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
        
        let totalFotos = 0;
        
        if (data.prendas && data.prendas.length > 0) {
            data.prendas.forEach((prenda, prendaIdx) => {
                if (prenda.imagenes && prenda.imagenes.length > 0) {
                    const imagenesPrend = prenda.imagenes.filter(img => img.type === 'prenda');
                    const imagenesTela = prenda.imagenes.filter(img => img.type === 'tela');
                    
                    // Fotos de prenda
                    if (imagenesPrend.length > 0) {
                        const fotosAMostrar = imagenesPrend.slice(0, 4);
                        totalFotos += fotosAMostrar.length;
                        html += this._crearSeccionFotos('PRENDA', prendaIdx + 1, fotosAMostrar, '#2563eb');
                    }
                    
                    // Fotos de tela
                    if (imagenesTela.length > 0) {
                        const fotosAMostrar = imagenesTela.slice(0, 4);
                        totalFotos += fotosAMostrar.length;
                        html += this._crearSeccionFotos('TELA', prendaIdx + 1, fotosAMostrar, '#1d4ed8');
                    }
                }
            });
        }
        
        html += '</div>';
        
        if (totalFotos === 0) {
            html = '<p style="text-align: center; color: #999; padding: 2rem;">No hay fotos de costura disponibles para este pedido</p>';
        }
        
        container.innerHTML = html;

    },
    
    /**
     * Crea el header de la galería
     * @private
     */
    _crearHeader: function() {
        return `<div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">
            <h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERÍA DE COSTURA</h2>
        </div>`;
    },
    
    /**
     * Crea una sección de fotos (prenda o tela)
     * @private
     */
    _crearSeccionFotos: function(tipo, numero, imagenes, color) {
        let html = `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
            <div style="border-left: 4px solid ${color}; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                <h3 style="font-size: 0.65rem; font-weight: 700; color: ${color}; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                    ${tipo} ${numero}
                </h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
        
        imagenes.forEach(image => {
            const imageIndex = this.allImages.length;
            this.allImages.push(image.url);
            
            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                onmouseover="this.style.borderColor='#2563eb'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.2)';"
                onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                onclick="Galeria.mostrarImagenGrande(${imageIndex})">
                <img src="${image.url}" alt="Foto ${tipo.toLowerCase()}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>`;
        });
        
        html += '</div></div>';
        return html;
    },
    
    /**
     * Muestra una imagen en grande
     */
    mostrarImagenGrande: function(index) {

        this.currentImageIndex = index;
        
        if (!this.allImages || this.allImages.length === 0) {

            return;
        }
        
        let modal = document.getElementById('modal-imagen-grande-costura');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modal-imagen-grande-costura';
            document.body.appendChild(modal);
        }
        
        const img = this.allImages[index];
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10001; padding: 20px;';
        
        modal.innerHTML = `
            <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                <img src="${img}" alt="Foto grande" style="max-width: 90vw; max-height: 90vh; object-fit: contain;">
                
                <button onclick="Galeria.cerrarImagenGrande()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: black; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    ✕
                </button>
                
                <button onclick="Galeria.cambiarImagen(-1)" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; color: black; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    ‹
                </button>
                
                <button onclick="Galeria.cambiarImagen(1)" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; color: black; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    ›
                </button>
                
                <div style="position: absolute; bottom: 20px; background: rgba(0,0,0,0.7); color: white; padding: 8px 16px; border-radius: 4px; font-size: 14px;">
                    ${index + 1} / ${this.allImages.length}
                </div>
            </div>
        `;
    },
    
    /**
     * Cierra la imagen grande
     */
    cerrarImagenGrande: function() {
        const modal = document.getElementById('modal-imagen-grande-costura');
        if (modal) {
            modal.style.display = 'none';
        }
    },
    
    /**
     * Cambia entre imágenes
     */
    cambiarImagen: function(direccion) {
        this.currentImageIndex += direccion;
        
        if (this.currentImageIndex < 0) {
            this.currentImageIndex = this.allImages.length - 1;
        } else if (this.currentImageIndex >= this.allImages.length) {
            this.currentImageIndex = 0;
        }
        
        this.mostrarImagenGrande(this.currentImageIndex);
    },
    
    /**
     * Actualiza estilos de botones
     * @private
     */
    _actualizarBotones: function(btnFacturaId, btnGaleriaId, esFacura) {
        const btnFactura = document.getElementById(btnFacturaId);
        const btnGaleria = document.getElementById(btnGaleriaId);
        
        if (esFacura) {
            if (btnFactura) {
                btnFactura.style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
                btnFactura.style.border = 'none';
                btnFactura.style.color = 'white';
            }
            if (btnGaleria) {
                btnGaleria.style.background = 'white';
                btnGaleria.style.border = '2px solid #ddd';
                btnGaleria.style.color = '#333';
            }
        } else {
            if (btnFactura) {
                btnFactura.style.background = 'white';
                btnFactura.style.border = '2px solid #ddd';
                btnFactura.style.color = '#333';
            }
            if (btnGaleria) {
                btnGaleria.style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
                btnGaleria.style.border = 'none';
                btnGaleria.style.color = 'white';
            }
        }
    }
};

/**
 * Función global para mostrar imagen en modal fullscreen
 * Usada por prenda-card-service.js para mostrar imágenes de procesos
 */
window.mostrarImagenProcesoGrande = function(srcImagen) {
    // Crear contenedor modal si no existe
    let modalExistente = document.getElementById('modal-imagen-proceso-grande');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'modal-imagen-proceso-grande';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
    `;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    const img = document.createElement('img');
    img.src = srcImagen;
    img.style.cssText = `
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 8px;
    `;
    
    // Botón cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '✕';
    btnCerrar.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: white;
        border: none;
        color: #000;
        font-size: 28px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        z-index: 1000000;
    `;
    btnCerrar.onclick = () => modal.remove();
    
    contenido.appendChild(img);
    contenido.appendChild(btnCerrar);
    modal.appendChild(contenido);
    
    // Cerrar al clickear fuera de la imagen
    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    };
    
    // Cerrar con tecla ESC
    const handleKeyPress = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleKeyPress);
        }
    };
    document.addEventListener('keydown', handleKeyPress);
    
    document.body.appendChild(modal);
};


