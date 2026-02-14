{{-- 
    Componente Reutilizable: Galería Modal
    
    Props:
    - $id: ID único del modal (requerido)
    - $titulo: Título del modal (opcional)
    - $permitirEliminar: Si permite eliminar fotos (default: true)
    - $onEliminar: Callback al eliminar foto (opcional)
    
    Uso:
    <x-galeria-modal :id="'prenda'" />
    <x-galeria-modal :id="'proceso'" :permitirEliminar="false" />
--}}

@props([
    'id' => 'galeria',
    'titulo' => 'Galería',
    'permitirEliminar' => true,
    'onEliminar' => null
])

@php
    $idSafe = ucfirst(str_replace('-', '', $id));
@endphp

<div id="modal-{{ $id }}" class="modal-galeria-componente" style="display: none;">
    <!-- Fondo oscuro clickeable -->
    <div class="galeria-componente-overlay" onclick="cerrarGaleria{{ $idSafe }}()"></div>
    
    <!-- Contenedor de la galería -->
    <div class="galeria-componente-container">
        <!-- Botón cerrar -->
        <button class="btn-cerrar-galeria-componente" onclick="cerrarGaleria{{ $idSafe }}()">
            <span class="material-symbols-rounded">close</span>
        </button>
        
        <!-- Flecha anterior -->
        <button class="btn-nav-componente btn-anterior-componente" onclick="galeriaAnterior{{ $idSafe }}()" id="btn-anterior-{{ $id }}">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
        
        <!-- Imagen principal -->
        <div class="galeria-componente-imagen-container">
            <img id="galeria-{{ $id }}-imagen-principal" src="" alt="Foto" class="galeria-componente-imagen" />
            <div class="galeria-componente-info">
                <span id="galeria-{{ $id }}-contador">1/3</span>
            </div>
        </div>
        
        <!-- Flecha siguiente -->
        <button class="btn-nav-componente btn-siguiente-componente" onclick="galeriaSiguiente{{ $idSafe }}()" id="btn-siguiente-{{ $id }}">
            <span class="material-symbols-rounded">chevron_right</span>
        </button>
        
        @if($permitirEliminar)
        <!-- Botón Eliminar -->
        <button class="btn-eliminar-galeria-componente" onclick="eliminarFotoActual{{ $idSafe }}()" title="Eliminar esta foto">
            <span class="material-symbols-rounded">delete</span>
        </button>
        @endif
    </div>
    
    <!-- Miniaturas (thumbnails) en la parte inferior -->
    <div class="galeria-componente-thumbnails" id="galeria-{{ $id }}-thumbnails">
        <!-- Se llena dinámicamente -->
    </div>
</div>

<style>
    /* Estilos para la galería de componentes */
    .modal-galeria-componente {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.98);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10000000000 !important;
    }

    .galeria-componente-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .galeria-componente-container {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 90%;
        max-width: 800px;
        height: 70%;
        z-index: 999910;
    }

    .btn-cerrar-galeria-componente {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 999911;
    }

    .btn-cerrar-galeria-componente:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
    }

    .btn-eliminar-galeria-componente {
        position: absolute;
        top: 80px;
        right: 20px;
        background: rgba(239, 68, 68, 0.7);
        border: none;
        color: white;
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 999911;
    }

    .btn-eliminar-galeria-componente:hover {
        background: rgba(239, 68, 68, 1);
        transform: scale(1.1);
    }

    .galeria-componente-imagen-container {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        user-select: none;
    }

    .galeria-componente-imagen {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        cursor: grab;
    }

    .galeria-componente-imagen:active {
        cursor: grabbing;
    }

    .galeria-componente-info {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-nav-componente {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 2rem;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        z-index: 999910;
    }

    .btn-nav-componente:hover {
        background: rgba(255, 255, 255, 0.4);
        transform: translateY(-50%) scale(1.1);
    }

    .btn-anterior-componente {
        left: 20px;
    }

    .btn-siguiente-componente {
        right: 20px;
    }

    .galeria-componente-thumbnails {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 999910;
        padding: 10px;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 8px;
    }

    .galeria-componente-thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 4px;
        cursor: pointer;
        opacity: 0.6;
        border: 2px solid transparent;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .galeria-componente-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .galeria-componente-thumbnail.active {
        opacity: 1;
        border-color: white;
    }

    .galeria-componente-thumbnail:hover {
        opacity: 1;
    }
</style>

<script>
// Declarar la clase GaleriaComponente solo una vez (si no existe)
if (typeof window.GaleriaComponente === 'undefined') {
    window.GaleriaComponente = class {
        constructor(id) {
            this.id = id;
            this.fotos = [];
            this.fotoActual = 0;
            this.permitirEliminar = {{ $permitirEliminar ? 'true' : 'false' }};
            this.onEliminada = null;
        }

    abrirGaleria(fotos) {
        if (!fotos || fotos.length === 0) return;
        
        this.fotos = fotos;
        this.fotoActual = 0;
        
        const modal = document.getElementById('modal-' + this.id);
        if (modal) {
            modal.style.display = 'flex';
            this.mostrarFotoActual();
            this.renderizarThumbnails();
        }
    }

    cerrarGaleria() {
        const modal = document.getElementById('modal-' + this.id);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    mostrarFotoActual() {
        if (this.fotos.length === 0) return;
        
        const foto = this.fotos[this.fotoActual];
        const img = document.getElementById('galeria-' + this.id + '-imagen-principal');
        const contador = document.getElementById('galeria-' + this.id + '-contador');
        
        if (img) {
            img.src = foto;
            img.alt = `Foto ${this.fotoActual + 1} de ${this.fotos.length}`;
        }
        
        if (contador) {
            contador.textContent = `${this.fotoActual + 1}/${this.fotos.length}`;
        }

        // Actualizar botones de navegación
        const btnAnterior = document.getElementById('btn-anterior-' + this.id);
        const btnSiguiente = document.getElementById('btn-siguiente-' + this.id);

        if (btnAnterior) {
            btnAnterior.style.display = this.fotoActual > 0 ? 'flex' : 'none';
        }

        if (btnSiguiente) {
            btnSiguiente.style.display = this.fotoActual < this.fotos.length - 1 ? 'flex' : 'none';
        }

        // Actualizar thumbnails activos
        const thumbnails = document.querySelectorAll(`#galeria-${this.id}-thumbnails .galeria-componente-thumbnail`);
        thumbnails.forEach((thumb, i) => {
            if (i === this.fotoActual) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }

    renderizarThumbnails() {
        const container = document.getElementById('galeria-' + this.id + '-thumbnails');
        if (!container) return;
        
        // Limpiar thumbnails existentes
        const existentes = container.querySelectorAll('.galeria-componente-thumbnail');
        existentes.forEach(t => t.remove());
        
        // Crear nuevos thumbnails
        this.fotos.forEach((foto, i) => {
            const thumb = document.createElement('div');
            thumb.className = 'galeria-componente-thumbnail' + (i === this.fotoActual ? ' active' : '');
            thumb.onclick = () => this.irAFoto(i);
            
            const img = document.createElement('img');
            img.src = foto;
            img.alt = `Miniatura ${i + 1}`;
            
            thumb.appendChild(img);
            container.appendChild(thumb);
        });
    }

    irAFoto(index) {
        this.fotoActual = index;
        this.mostrarFotoActual();
    }

    fotoAnterior() {
        if (this.fotoActual > 0) {
            this.fotoActual--;
            this.mostrarFotoActual();
        }
    }

    fotoSiguiente() {
        if (this.fotoActual < this.fotos.length - 1) {
            this.fotoActual++;
            this.mostrarFotoActual();
        }
    }

    eliminarFotoActual() {
        if (this.fotos.length === 0) return;
        
        // Obtener el nombre/índice de la foto actual para eliminación del storage
        const indiceAEliminar = this.fotoActual;
        const fotoAEliminar = this.fotos[indiceAEliminar];
        
        // Eliminar foto del array
        this.fotos.splice(this.fotoActual, 1);
        
        // Eliminar del storage según el tipo de galería
        this._eliminarDelStorage(indiceAEliminar, fotoAEliminar);
        
        // Ejecutar callback si existe
        if (this.onEliminada) {
            this.onEliminada(indiceAEliminar, this.fotos);
        }
        
        // Si no hay más fotos, cerrar galería
        if (this.fotos.length === 0) {
            this.cerrarGaleria();
            return;
        }
        
        // Ajustar índice si es necesario
        if (this.fotoActual >= this.fotos.length) {
            this.fotoActual = this.fotos.length - 1;
        }
        
        // Refrescar la galería
        this.mostrarFotoActual();
        this.renderizarThumbnails();
    }

    /**
     * Eliminar foto del storage según el tipo de galería
     * @param {number} indice - Índice de la foto a eliminar
     * @param {string} src - URL/src de la foto a eliminar
     * @private
     */
    _eliminarDelStorage(indice, src) {
        try {
            console.log('[GaleriaComponente] Intentando eliminar foto del storage:', {
                id: this.id,
                indice: indice,
                src: src ? src.substring(0, 50) + '...' : 'N/A'
            });

            // Según el tipo de galería, usar el storage correspondiente
            if (this.id === 'prenda' && typeof window.imagenesPrendaStorage !== 'undefined') {
                // Buscar el índice real de la imagen en el storage
                const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
                let indiceReal = -1;
                
                // Primero: Intentar buscar por previewUrl o url exacto
                for (let i = 0; i < imagenes.length; i++) {
                    if (imagenes[i].previewUrl === src || imagenes[i].src === src || imagenes[i].url === src) {
                        indiceReal = i;
                        break;
                    }
                }
                
                // Segundo: Si no encontró, usar el indice directo (porque el orden en galería puede coincidir)
                if (indiceReal === -1 && indice < imagenes.length) {
                    indiceReal = indice;
                    console.log('[GaleriaComponente]  Imagen no encontrada por URL, usando índice directo:', indiceReal);
                }
                
                if (indiceReal !== -1) {
                    window.imagenesPrendaStorage.eliminarImagen(indiceReal);
                    console.log('[GaleriaComponente]  Imagen eliminada del storage de prendas (índice real:', indiceReal + ')');
                    
                    // Actualizar preview de prendas
                    if (typeof window.actualizarPreviewPrenda === 'function') {
                        window.actualizarPreviewPrenda();
                    }
                } else {
                    console.warn('[GaleriaComponente]  No se encontró la imagen en el storage de prendas');
                    console.warn('[GaleriaComponente]  Imágenes en storage:', imagenes);
                }
                
            } else if (this.id === 'tela' && typeof window.imagenesTelaStorage !== 'undefined') {
                // Buscar el índice real de la imagen en el storage
                const imagenes = window.imagenesTelaStorage.obtenerImagenes();
                let indiceReal = -1;
                
                // Primero: Intentar buscar por previewUrl o url exacto
                for (let i = 0; i < imagenes.length; i++) {
                    if (imagenes[i].previewUrl === src || imagenes[i].src === src || imagenes[i].url === src) {
                        indiceReal = i;
                        break;
                    }
                }
                
                // Segundo: Si no encontró, usar el indice directo
                if (indiceReal === -1 && indice < imagenes.length) {
                    indiceReal = indice;
                    console.log('[GaleriaComponente]  Imagen no encontrada por URL, usando índice directo:', indiceReal);
                }
                
                if (indiceReal !== -1) {
                    window.imagenesTelaStorage.eliminarImagen(indiceReal);
                    console.log('[GaleriaComponente]  Imagen eliminada del storage de telas (índice real:', indiceReal + ')');
                    
                    // Actualizar preview de telas
                    if (typeof window.actualizarPreviewTela === 'function') {
                        window.actualizarPreviewTela();
                    }
                } else {
                    console.warn('[GaleriaComponente]  No se encontró la imagen en el storage de telas');
                }
                
            } else if (this.id.includes('proceso') && typeof window.procesosImagenesStorage !== 'undefined') {
                // Extraer número de proceso si existe
                const match = this.id.match(/\d+/);
                const numProceso = match ? match[0] : '1';
                
                // El índice a eliminar es el mismo que en el array de fotos mostrado
                // Porque la galería mantiene sincronizado el orden
                const indiceAEliminar = indice;
                
                // Verificar que el índice sea válido
                const imagenes = window.procesosImagenesStorage.obtenerImagenes(numProceso);
                if (imagenes && indiceAEliminar >= 0 && indiceAEliminar < imagenes.length) {
                    window.procesosImagenesStorage.eliminarImagen(numProceso, indiceAEliminar);
                    console.log(`[GaleriaComponente]  Imagen eliminada del storage de proceso ${numProceso} (índice:`, indiceAEliminar + ')');
                    
                    // Limpiar el preview del proceso
                    if (typeof window.eliminarImagenProceso === 'function') {
                        window.eliminarImagenProceso(numProceso);
                    }
                } else {
                    console.warn(`[GaleriaComponente]  No se pudo eliminar la imagen del storage de proceso ${numProceso}`);
                }
                
            } else {
                console.warn('[GaleriaComponente]  Storage no encontrado para galería:', this.id);
            }
        } catch (error) {
            console.error('[GaleriaComponente]  Error eliminando del storage:', error);
        }
    }
    };
}

// Instancia global de la galería
window.galeria{{ $idSafe }} = new window.GaleriaComponente('{{ $id }}');

// Funciones globales para los botones
window.abrirGaleria{{ $idSafe }} = function(fotos) {
    window.galeria{{ $idSafe }}.abrirGaleria(fotos);
};

window.cerrarGaleria{{ $idSafe }} = function() {
    window.galeria{{ $idSafe }}.cerrarGaleria();
};

window.galeriaAnterior{{ $idSafe }} = function() {
    window.galeria{{ $idSafe }}.fotoAnterior();
};

window.galeriaSiguiente{{ $idSafe }} = function() {
    window.galeria{{ $idSafe }}.fotoSiguiente();
};

window.eliminarFotoActual{{ $idSafe }} = function() {
    // Usar SweetAlert para confirmación en lugar de confirm()
    Swal.fire({
        title: '¿Eliminar esta foto?',
        html: '<p style="margin: 15px 0; color: #666;">Se eliminará esta foto del registro.</p><p style="color: #ef4444; font-weight: 500;"><i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            // Inyectar estilos CSS para asegurar que SweetAlert esté SIEMPRE encima
            if (!document.getElementById('swal-z-index-override')) {
                const style = document.createElement('style');
                style.id = 'swal-z-index-override';
                style.innerHTML = `
                    .swal2-container {
                        z-index: 999999999999 !important;
                    }
                    .swal2-container.swal2-shown {
                        z-index: 999999999999 !important;
                    }
                    .swal2-popup {
                        z-index: 999999999999 !important;
                    }
                    .swal2-backdrop-show {
                        z-index: 999999999998 !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Eliminar la foto
            window.galeria{{ $idSafe }}.eliminarFotoActual();
            
            // Mostrar mensaje de éxito
            Swal.fire({
                title: 'Eliminado',
                text: 'Foto eliminada correctamente',
                icon: 'success',
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    // Inyectar estilos CSS para asegurar que SweetAlert esté SIEMPRE encima
                    if (!document.getElementById('swal-z-index-override')) {
                        const style = document.createElement('style');
                        style.id = 'swal-z-index-override';
                        style.innerHTML = `
                            .swal2-container {
                                z-index: 999999999999 !important;
                            }
                            .swal2-container.swal2-shown {
                                z-index: 999999999999 !important;
                            }
                            .swal2-popup {
                                z-index: 999999999999 !important;
                            }
                            .swal2-backdrop-show {
                                z-index: 999999999998 !important;
                            }
                        `;
                        document.head.appendChild(style);
                    }
                }
            });
        }
    });
};
</script>
