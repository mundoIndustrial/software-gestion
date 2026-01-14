/**
 * GESTOR DE FOTOS - Crear Pedido Editable
 * 
 * Centraliza toda la lógica de manejo de fotos (agregar, eliminar, renderizar)
 */

// ============================================================
// GESTOR GENÉRICO DE FOTOS
// ============================================================

class GestorFotos {
    /**
     * Constructor
     * @param {Array} fotosArray - Array donde se almacenan las fotos
     * @param {number} maxFotos - Máximo número de fotos permitidas
     * @param {string} tipo - Tipo de foto (logo, prenda, tela)
     */
    constructor(fotosArray, maxFotos, tipo = 'generico') {
        this.fotos = fotosArray || [];
        this.maxFotos = maxFotos;
        this.tipo = tipo;
    }

    /**
     * Verificar si se puede agregar una foto más
     * @returns {Object} {permitido: boolean, mensaje: string}
     */
    puedeAgregarFoto(cantidad = 1) {
        const disponible = this.maxFotos - this.fotos.length;
        
        if (disponible <= 0) {
            return {
                permitido: false,
                mensaje: `Ya has alcanzado el máximo de ${this.maxFotos} imágenes permitidas`
            };
        }
        
        if (cantidad > disponible) {
            return {
                permitido: false,
                mensaje: `Solo puedes agregar ${disponible} imagen${disponible !== 1 ? 's' : ''} más. Máximo ${this.maxFotos} en total.`
            };
        }
        
        return { permitido: true, mensaje: '' };
    }

    /**
     * Agregar fotos al array
     * @param {File[]} archivos - Array de archivos a agregar
     * @returns {Promise<number>} Cantidad de fotos agregadas
     */
    async agregarFotos(archivos) {
        const validacion = this.puedeAgregarFoto(archivos.length);
        if (!validacion.permitido) {
            throw new Error(validacion.mensaje);
        }

        let fotosAgregadas = 0;
        const promesas = Array.from(archivos)
            .filter(file => file.type.startsWith('image/'))
            .map(file => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.fotos.push({
                            file: file,
                            preview: e.target.result,
                            existing: false,
                            nombre: file.name
                        });
                        fotosAgregadas++;
                        resolve();
                    };
                    reader.readAsDataURL(file);
                });
            });

        await Promise.all(promesas);
        return fotosAgregadas;
    }

    /**
     * Eliminar foto por índice
     * @param {number} index - Índice de la foto a eliminar
     */
    eliminarFoto(index) {
        if (index >= 0 && index < this.fotos.length) {
            const fotoEliminada = this.fotos[index];
            this.fotos.splice(index, 1);
            return fotoEliminada;
        }
        return null;
    }

    /**
     * Obtener todas las fotos
     * @returns {Array} Array de fotos
     */
    obtenerFotos() {
        return this.fotos;
    }

    /**
     * Obtener cantidad de fotos
     * @returns {number} Cantidad de fotos
     */
    cantidadFotos() {
        return this.fotos.length;
    }

    /**
     * Limpiar todas las fotos
     */
    limpiar() {
        this.fotos = [];
    }

    /**
     * Obtener espacios disponibles para agregar fotos
     * @returns {number} Espacios disponibles
     */
    espaciosDisponibles() {
        return this.maxFotos - this.fotos.length;
    }
}

// ============================================================
// GESTOR DE FOTOS DEL LOGO (específico)
// ============================================================

class GestorFotosLogo extends GestorFotos {
    constructor(fotosArray = []) {
        super(fotosArray, CONFIG.MAX_FOTOS_LOGO, 'logo');
    }

    /**
     * Renderizar fotos en un contenedor
     * @param {string|Element} contenedor - ID o elemento del DOM
     */
    renderizar(contenedor) {
        const el = typeof contenedor === 'string' ? 
            document.getElementById(contenedor) : contenedor;
        
        if (!el) return;

        el.innerHTML = '';
        
        if (this.fotos.length === 0) {
            el.innerHTML = '<p style="grid-column: 1/-1; color: #9ca3af; text-align: center; padding: 2rem;">Sin imágenes</p>';
            return;
        }

        this.fotos.forEach((foto, idx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; display: inline-block; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s;';
            div.innerHTML = `
                <img src="${foto.preview}" 
                     alt="Imagen ${idx + 1}" 
                     style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s; display: block;" 
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onclick="abrirModalImagen('${foto.preview}', '${this.tipo} - Imagen ${idx + 1}')">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.2s;" 
                     class="overlay-foto"
                     onmouseover="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='1'; this.style.background='rgba(0,0,0,0.3)'" 
                     onmouseout="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='0'; this.style.background='rgba(0,0,0,0)'"></div>
                <button type="button" 
                        onclick="window.gestorFotosLogo?.eliminarFoto(${idx}); window.gestorFotosLogo?.renderizar('${typeof contenedor === 'string' ? contenedor : contenedor.id}')" 
                        style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; z-index: 10; padding: 0; line-height: 1;" 
                        class="btn-eliminar-foto">×</button>
            `;
            el.appendChild(div);
        });
    }

    /**
     * Abrir diálogo para agregar fotos
     */
    abrirDialogoAgregar() {
        const validacion = this.puedeAgregarFoto();
        if (!validacion.permitido) {
            mostrarError('Límite alcanzado', validacion.mensaje);
            return;
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;

        input.addEventListener('change', async (e) => {
            try {
                const cantidad = await this.agregarFotos(e.target.files);
                this.renderizar('galeria-fotos-logo');
                mostrarExito('Éxito', `Se agregaron ${cantidad} imagen${cantidad !== 1 ? 's' : ''} correctamente`);
            } catch (error) {
                mostrarError('Error', error.message);
            }
        });

        input.click();
    }
}

// ============================================================
// GESTOR DE FOTOS DE PRENDA
// ============================================================

class GestorFotosPrenda extends GestorFotos {
    constructor(fotosArray = []) {
        super(fotosArray, CONFIG.MAX_FOTOS_PRENDA, 'prenda');
    }

    /**
     * Renderizar galería de prenda
     * @param {string} prendaIndex - Índice de la prenda
     */
    renderizar(prendaIndex) {
        const galeriaContainer = document.querySelector(`[data-prenda-index="${prendaIndex}"] .prenda-fotos-section`);
        if (!galeriaContainer) return;

        if (this.fotos.length === 0) {
            galeriaContainer.innerHTML = '<p style="color: #9ca3af; text-align: center; padding: 1rem;">Sin fotos</p>';
            return;
        }

        let html = `
            <div style="position: relative; width: 100%; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.75rem 0.75rem 0.6rem 0.75rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">Galería de la prenda</div>
                    <button type="button"
                            onclick="window.gestorFotosPrenda.abrirDialogoAgregar(${prendaIndex})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);"
                            title="Agregar foto">
                        ＋
                    </button>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.65rem;">
                    ${this.fotos.map((foto, idx) => `
                        <div style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-height: 180px; overflow: hidden; border-radius: 8px; border: 1px solid #d1d5db; box-shadow: 0 2px 6px rgba(0,0,0,0.08); background: white;">
                            <img src="${foto.preview}" alt="Foto prenda"
                                 style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                 onclick="abrirGaleriaPrenda(${prendaIndex}, ${idx})">
                            <button type="button"
                                    onclick="window.gestorFotosPrenda.eliminarFoto(${idx}); window.gestorFotosPrenda.renderizar(${prendaIndex})"
                                    style="position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10;">×</button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        galeriaContainer.innerHTML = html;
    }

    /**
     * Abrir diálogo para agregar fotos a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     */
    abrirDialogoAgregar(prendaIndex) {
        const validacion = this.puedeAgregarFoto();
        if (!validacion.permitido) {
            mostrarError('Límite alcanzado', validacion.mensaje);
            return;
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;

        input.addEventListener('change', async (e) => {
            try {
                const cantidad = await this.agregarFotos(e.target.files);
                this.renderizar(prendaIndex);
                mostrarExito('Éxito', `Se agregaron ${cantidad} imagen${cantidad !== 1 ? 's' : ''} correctamente`);
            } catch (error) {
                mostrarError('Error', error.message);
            }
        });

        input.click();
    }
}

// ============================================================
// GESTOR DE FOTOS DE TELA
// ============================================================

class GestorFotosTela extends GestorFotos {
    constructor(fotosArray = []) {
        super(fotosArray, CONFIG.MAX_FOTOS_TELA, 'tela');
    }

    /**
     * Abrir diálogo para agregar fotos de tela
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     */
    abrirDialogoAgregar(prendaIndex, telaIndex) {
        const validacion = this.puedeAgregarFoto();
        if (!validacion.permitido) {
            mostrarError('Límite alcanzado', validacion.mensaje);
            return;
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;

        input.addEventListener('change', async (e) => {
            try {
                const cantidad = await this.agregarFotos(e.target.files);
                mostrarExito('Éxito', `Se agregaron ${cantidad} imagen${cantidad !== 1 ? 's' : ''} correctamente`);
            } catch (error) {
                mostrarError('Error', error.message);
            }
        });

        input.click();
    }
}

// ============================================================
// INSTANCIAS GLOBALES
// ============================================================

// Estas instancias serán usadas en lugar de arrays simples
window.gestorFotosLogo = null; // Se inicializa cuando sea necesario
window.gestorFotosPrenda = null;
window.gestorFotosTela = null;

// Exportar para uso en otros módulos (si usas ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        GestorFotos,
        GestorFotosLogo,
        GestorFotosPrenda,
        GestorFotosTela
    };
}
