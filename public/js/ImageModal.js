/**
 * Modal de Imágenes Genérico
 * Permite ver imágenes en un modal centrado con zoom y navegación
 */

class ImageModal {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente
        window.abrirModalImagen = this.abrirModal.bind(this);
        window.cerrarModalImagen = this.cerrar.bind(this);
        
        // Crear estructura del modal si no existe
        this.crearModal();
        
        // Evento para cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.cerrar();
            }
        });
        
        // Evento para cerrar al hacer clic fuera
        const modal = document.getElementById('imagen-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.cerrar();
                }
            });
        }
    }

    crearModal() {
        // Verificar si ya existe
        if (document.getElementById('imagen-modal')) {
            return;
        }

        const modal = document.createElement('div');
        modal.id = 'imagen-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 200000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        modal.innerHTML = `
            <div style="position: relative; width: 95%; height: 95%; max-width: 1200px; max-height: 90vh; background: white; border-radius: 12px; overflow: hidden; cursor: default; box-shadow: 0 20px 60px rgba(0,0,0,0.3); display: flex; flex-direction: column;" onclick="event.stopPropagation()">
                <!-- Header -->
                <div style="position: relative; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <div id="imagen-modal-title" style="font-size: 18px; font-weight: 600;">Imagen</div>
                        <div id="imagen-modal-subtitle" style="font-size: 14px; opacity: 0.9; margin-top: 2px;">Click fuera o presiona ESC para cerrar</div>
                    </div>
                    <button onclick="window.cerrarModalImagen()" style="background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        ✕
                    </button>
                </div>
                
                <!-- Contenido -->
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; background: #f8f9fa; position: relative; overflow: hidden;">
                    <img id="imagen-modal-img" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; cursor: zoom-in;" alt="Imagen ampliada">
                </div>
                
                <!-- Footer -->
                <div style="background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.6)); color: white; padding: 15px 20px; text-align: center;">
                    <div id="imagen-modal-info" style="font-size: 14px;">
                        Imagen 1 de 1
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    abrirModal(imgUrl, titulo = 'Imagen', imagenes = []) {
        const modal = document.getElementById('imagen-modal');
        const img = document.getElementById('imagen-modal-img');
        const titleEl = document.getElementById('imagen-modal-title');
        const infoEl = document.getElementById('imagen-modal-info');

        if (!modal || !img) {
            console.error('[ImageModal] Modal no encontrado');
            return;
        }

        // Establecer imagen
        img.src = imgUrl;
        img.alt = titulo;

        // Establecer título
        titleEl.textContent = titulo;

        // Mostrar información de imágenes si hay múltiples
        if (imagenes && imagenes.length > 1) {
            const currentIndex = imagenes.findIndex(img => 
                (typeof img === 'string' ? img : img.ruta_web || img.url) === imgUrl
            );
            infoEl.textContent = `Imagen ${currentIndex + 1} de ${imagenes.length}`;
        } else {
            infoEl.textContent = 'Imagen';
        }

        // Mostrar modal con animación
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.style.opacity = '1';
        }, 10);
    }

    cerrar() {
        const modal = document.getElementById('imagen-modal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new ImageModal();
});
