/**
 * MÓDULO: Gestión de Fotos para Logo Pedido
 * 
 * Maneja la carga, vista previa y eliminación de fotos
 * para los logos en los pedidos de producción
 */

let logoFotosSeleccionadas = [];

/**
 * Abrir modal para agregar fotos al logo
 */
function abrirModalAgregarFotosLogo() {
    Swal.fire({
        title: 'Agregar Fotos del Logo',
        width: '500px',
        html: `
            <div style="text-align: left;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                        Selecciona fotos (máximo 5, formatos: JPG, PNG, WebP)
                    </label>
                    <input type="file" 
                        id="inputFotosLogoModal" 
                        accept="image/jpeg,image/png,image/webp"
                        multiple 
                        style="display: block; width: 100%; padding: 8px; border: 2px dashed #0066cc; border-radius: 4px; cursor: pointer;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                        Vista previa:
                    </label>
                    <div id="previewFotosLogoModal" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px;"></div>
                </div>
            </div>
        `,
        didOpen: (modal) => {
            const inputFotos = document.getElementById('inputFotosLogoModal');
            const previewDiv = document.getElementById('previewFotosLogoModal');
            let fotosTemporales = [];
            
            inputFotos.addEventListener('change', function(e) {
                fotosTemporales = [];
                previewDiv.innerHTML = '';
                
                if (this.files.length > 5) {
                    Swal.showValidationMessage(`Máximo 5 fotos. Seleccionaste ${this.files.length}`);
                    return;
                }
                
                Array.from(this.files).forEach((file, idx) => {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        fotosTemporales.push({
                            file: file,
                            preview: event.target.result,
                            nombre: file.name
                        });
                        
                        // Crear preview
                        const previewHTML = document.createElement('div');
                        previewHTML.style.cssText = 'position: relative; border-radius: 4px; overflow: hidden; background: #f0f0f0; aspect-ratio: 1;';
                        previewHTML.innerHTML = `
                            <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" 
                                onclick="this.parentElement.remove()"
                                style="position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center;">
                                ✕
                            </button>
                        `;
                        previewDiv.appendChild(previewHTML);
                    };
                    reader.readAsDataURL(file);
                });
            });
        },
        showCancelButton: true,
        confirmButtonText: 'Agregar Fotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const previews = document.querySelectorAll('#previewFotosLogoModal > div');
            if (previews.length === 0) {
                Swal.showValidationMessage('Selecciona al menos una foto');
                return false;
            }
            
            // Retornar previews
            return Array.from(previews).map((el, idx) => {
                const img = el.querySelector('img');
                return {
                    preview: img.src,
                    nombre: `foto-${idx}`
                };
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Agregar fotos al array global
            logoFotosSeleccionadas.push(...result.value.map(foto => ({
                ...foto,
                nuevo: true
            })));
            
            renderizarGaleriaFotosLogo();
        }
    });
}

/**
 * Renderizar galería de fotos del logo
 */
function renderizarGaleriaFotosLogo() {
    const galeria = document.getElementById('logo-fotos-galeria');
    if (!galeria) return;
    
    if (logoFotosSeleccionadas.length === 0) {
        galeria.innerHTML = '<p style="color: #9ca3af; font-size: 0.9rem; grid-column: 1 / -1;">No hay fotos aún</p>';
        return;
    }
    
    galeria.innerHTML = logoFotosSeleccionadas.map((foto, idx) => `
        <div style="position: relative; border-radius: 4px; overflow: hidden; background: #f0f0f0; aspect-ratio: 1; border: 1px solid #ddd;">
            <img src="${foto.preview || foto.url}" alt="Foto ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover;">
            <button type="button" 
                onclick="eliminarFotoLogo(${idx})"
                style="position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 12px; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                ✕
            </button>
        </div>
    `).join('');
}

/**
 * Eliminar foto del logo
 */
function eliminarFotoLogo(index) {
    logoFotosSeleccionadas.splice(index, 1);
    renderizarGaleriaFotosLogo();
}

// =========================================================
// INICIALIZAR
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar galería vacía si el contenedor existe
    const galeriaDiv = document.getElementById('logo-fotos-galeria');
    if (galeriaDiv) {
        renderizarGaleriaFotosLogo();
    }
});
