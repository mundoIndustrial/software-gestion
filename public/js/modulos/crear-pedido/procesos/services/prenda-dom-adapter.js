/**
 * PrendaDOMAdapter - Adaptador de acceso al DOM
 * 
 * Propósito: Encapsular e inyectar todas las operaciones del DOM
 * Ventajas: Testeable, desacoplado, fácil cambiar selectores
 */
class PrendaDOMAdapter {
    constructor(selectorModal = '#modal-agregar-prenda-nueva') {
        this.selectorModal = selectorModal;
        this.cache = {};
        this.observers = {};
    }

    /**
     * Obtener elemento con cache
     * @private
     */
    obtenerElemento(id, selector = '#') {
        const key = `${selector}${id}`;
        if (!this.cache[key]) {
            const elemento = document.querySelector(`${selector}${id}`);
            if (elemento) {
                this.cache[key] = elemento;
            }
        }
        return this.cache[key] || null;
    }

    /**
     * Invalidar cache de un elemento
     * @private
     */
    invalidarCache(id, selector = '#') {
        const key = `${selector}${id}`;
        delete this.cache[key];
    }

    /**
     * Invalidar todo el cache
     */
    limpiarCache() {
        this.cache = {};
    }

    // ====== MODAL ======
    abrirModal() {
        const modal = this.obtenerElemento('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    cerrarModal() {
        const modal = this.obtenerElemento('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    estaModalAbierto() {
        const modal = this.obtenerElemento('modal-agregar-prenda-nueva');
        return modal && modal.offsetParent !== null;
    }

    // ====== CAMPOS BÁSICOS ======
    establecerNombrePrenda(valor) {
        const input = this.obtenerElemento('nueva-prenda-nombre');
        if (input) input.value = valor;
    }

    obtenerNombrePrenda() {
        const input = this.obtenerElemento('nueva-prenda-nombre');
        return input ? input.value : '';
    }

    establecerDescripcion(valor) {
        const input = this.obtenerElemento('nueva-prenda-descripcion');
        if (input) input.value = valor;
    }

    obtenerDescripcion() {
        const input = this.obtenerElemento('nueva-prenda-descripcion');
        return input ? input.value : '';
    }

    establecerOrigen(valor) {
        const select = this.obtenerElemento('nueva-prenda-origen-select');
        if (!select) return false;

        // Intentar asignación directa primero
        select.value = valor;
        if (select.value === valor) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        }

        // Si no funcionó, buscar por opción
        for (let opt of select.options) {
            if (this.normalizarTexto(opt.value) === this.normalizarTexto(valor)) {
                select.value = opt.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            }
        }

        return false;
    }

    obtenerOrigen() {
        const select = this.obtenerElemento('nueva-prenda-origen-select');
        return select ? select.value : '';
    }

    obtenerOpcionesOrigen() {
        const select = this.obtenerElemento('nueva-prenda-origen-select');
        if (!select) return [];
        return Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    }

    // ====== TELAS ======
    establecerTela(valor) {
        const input = this.obtenerElemento('nueva-prenda-tela');
        if (input) input.value = valor;
    }

    obtenerTela() {
        const input = this.obtenerElemento('nueva-prenda-tela');
        return input ? input.value : '';
    }

    establecerColor(valor) {
        const input = this.obtenerElemento('nueva-prenda-color');
        if (input) input.value = valor;
    }

    obtenerColor() {
        const input = this.obtenerElemento('nueva-prenda-color');
        return input ? input.value : '';
    }

    establecerReferencia(valor) {
        const input = this.obtenerElemento('nueva-prenda-referencia');
        if (input) input.value = valor;
    }

    obtenerReferencia() {
        const input = this.obtenerElemento('nueva-prenda-referencia');
        return input ? input.value : '';
    }

    limpiarInputsTela() {
        this.establecerTela('');
        this.establecerColor('');
        this.establecerReferencia('');
    }

    // ====== VARIACIONES ======
    marcarVariacion(nombreVariacion, marcado = true) {
        const checkbox = this.obtenerElemento(`aplica-${nombreVariacion.toLowerCase()}`);
        if (checkbox) {
            checkbox.checked = marcado;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    estaVariacionMarcada(nombreVariacion) {
        const checkbox = this.obtenerElemento(`aplica-${nombreVariacion.toLowerCase()}`);
        return checkbox ? checkbox.checked : false;
    }

    establecerVariacionInput(nombreVariacion, valor) {
        const input = this.obtenerElemento(`${nombreVariacion.toLowerCase()}-input`);
        if (input) {
            input.value = valor;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    obtenerVariacionInput(nombreVariacion) {
        const input = this.obtenerElemento(`${nombreVariacion.toLowerCase()}-input`);
        return input ? input.value : '';
    }

    establecerVariacionObs(nombreVariacion, valor) {
        const textarea = this.obtenerElemento(`${nombreVariacion.toLowerCase()}-obs`);
        if (textarea) {
            textarea.value = valor;
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    obtenerVariacionObs(nombreVariacion) {
        const textarea = this.obtenerElemento(`${nombreVariacion.toLowerCase()}-obs`);
        return textarea ? textarea.value : '';
    }

    // ====== GÉNEROS/TALLAS ======
    marcarGenero(genero, marcado = true) {
        const checkbox = document.querySelector(`input[value="${genero.toLowerCase()}"]`);
        if (checkbox) {
            checkbox.checked = marcado;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    estaGeneroMarcado(genero) {
        const checkbox = document.querySelector(`input[value="${genero.toLowerCase()}"]`);
        return checkbox ? checkbox.checked : false;
    }

    establecerCantidadTalla(genero, talla, cantidad) {
        const input = document.querySelector(
            `input[data-genero="${genero}"][data-talla="${talla}"]`
        );
        if (input) {
            input.value = cantidad;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    obtenerCantidadTalla(genero, talla) {
        const input = document.querySelector(
            `input[data-genero="${genero}"][data-talla="${talla}"]`
        );
        return input ? parseInt(input.value) || 0 : 0;
    }

    // ====== PROCESOS ======
    marcarProceso(tipoProceso, marcado = true) {
        const checkbox = this.obtenerElemento(`checkbox-${tipoProceso}`);
        if (checkbox) {
            checkbox.checked = marcado;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    estaProcesomarcado(tipoProceso) {
        const checkbox = this.obtenerElemento(`checkbox-${tipoProceso}`);
        return checkbox ? checkbox.checked : false;
    }

    // ====== IMÁGENES ======
    establecerPreviewImagen(url) {
        const preview = this.obtenerElemento('nueva-prenda-foto-preview');
        if (preview) {
            preview.style.backgroundImage = `url('${url}')`;
            preview.style.cursor = 'pointer';
        }
    }

    establecerContadorImagenes(cantidad) {
        const contador = this.obtenerElemento('nueva-prenda-foto-contador');
        if (contador && cantidad > 1) {
            contador.textContent = cantidad;
        }
    }

    limpiarPreviewImagen() {
        const preview = this.obtenerElemento('nueva-prenda-foto-preview');
        if (preview) {
            preview.style.backgroundImage = 'none';
            preview.style.backgroundColor = '';
        }
    }

    // ====== TABLAS ======
    actualizarTablaTelas(telasHTML) {
        const tabla = document.querySelector('table[data-role="telas-prenda"]');
        if (tabla) {
            tabla.innerHTML = telasHTML;
        }
    }

    obtenerContenedorTelas() {
        return document.querySelector('[data-role="telas-cotizacion"]') ||
               document.getElementById('contenedor-telas-cotizacion');
    }

    obtenerContenedorUbicaciones() {
        return document.getElementById('ubicaciones-reflectivo') ||
               document.querySelector('[data-role="ubicaciones-reflectivo"]');
    }

    // ====== BOTÓN GUARDAR ======
    establecerBotoGuardar(texto, datos = {}) {
        const btn = this.obtenerElemento('btn-guardar-prenda');
        if (btn) {
            btn.innerHTML = texto;
            Object.entries(datos).forEach(([key, value]) => {
                btn.setAttribute(`data-${key}`, value);
            });
        }
    }

    obtenerBotónGuardar() {
        return this.obtenerElemento('btn-guardar-prenda');
    }

    // ====== UTILIDADES ======
    normalizarTexto(texto) {
        return texto
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    /**
     * Observar cambios en un elemento
     */
    observarCambios(selectores, callback) {
        if (typeof selectores === 'string') {
            selectores = [selectores];
        }

        selectores.forEach(selector => {
            const elemento = document.querySelector(selector);
            if (elemento) {
                elemento.addEventListener('change', callback);
                this.observers[selector] = {
                    elemento,
                    callback
                };
            }
        });
    }

    /**
     * Dejar de observar cambios
     */
    dejarDeObservar(selectores) {
        if (typeof selectores === 'string') {
            selectores = [selectores];
        }

        selectores.forEach(selector => {
            const obs = this.observers[selector];
            if (obs) {
                obs.elemento.removeEventListener('change', obs.callback);
                delete this.observers[selector];
            }
        });
    }

    /**
     * Mostrar/ocultar elemento
     */
    mostrar(selector, visible = true) {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.style.display = visible ? 'block' : 'none';
        }
    }

    /**
     * Agregar clase a elemento
     */
    agregarClase(selector, clase) {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.classList.add(clase);
        }
    }

    /**
     * Remover clase de elemento
     */
    removerClase(selector, clase) {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.classList.remove(clase);
        }
    }
}

window.PrendaDOMAdapter = PrendaDOMAdapter;
