/**
 * Gestor de Estados de Carga
 * Maneja spinners y estados de carga para la interfaz
 */

class LoadingManager {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.mostrarCargando = this.mostrarCargando.bind(this);
        window.ocultarCargando = this.ocultarCargando.bind(this);
    }

    /**
     * Muestra un spinner de carga
     */
    mostrarCargando(mensaje = 'Cargando...') {
        // Ocultar cualquier spinner existente
        this.ocultarCargando();
        
        const spinner = document.createElement('div');
        spinner.id = 'factura-spinner';
        spinner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            flex-direction: column;
            gap: 20px;
        `;
        
        // Spinner animado
        const spinnerInner = document.createElement('div');
        spinnerInner.style.cssText = `
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        `;
        
        // Mensaje
        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        `;
        
        spinner.appendChild(spinnerInner);
        spinner.appendChild(texto);
        
        // Agregar estilos de animación si no existen
        this.agregarEstilosAnimacion();
        
        document.body.appendChild(spinner);
        
        console.log('[LoadingManager] Spinner de carga mostrado:', mensaje);
    }

    /**
     * Oculta el spinner de carga
     */
    ocultarCargando() {
        const spinner = document.getElementById('factura-spinner');
        if (spinner) {
            spinner.remove();
            console.log('[LoadingManager] Spinner de carga oculto');
        }
    }

    /**
     * Agrega los estilos de animación necesarios
     */
    agregarEstilosAnimacion() {
        if (!document.getElementById('loading-styles')) {
            const style = document.createElement('style');
            style.id = 'loading-styles';
            style.textContent = `
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Muestra un indicador de carga en un contenedor específico
     */
    mostrarCargandoEnContenedor(contenedor, mensaje = 'Cargando...') {
        if (typeof contenedor === 'string') {
            contenedor = document.getElementById(contenedor);
        }
        
        if (!contenedor) {
            console.warn('[LoadingManager] Contenedor no encontrado');
            return;
        }
        
        // Guardar contenido original
        const contenidoOriginal = contenedor.innerHTML;
        contenedor.dataset.contenidoOriginal = contenidoOriginal;
        
        // Crear indicador de carga
        const indicador = document.createElement('div');
        indicador.className = 'loading-indicator';
        indicador.style.cssText = `
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            color: #666;
        `;
        
        const spinner = document.createElement('div');
        spinner.style.cssText = `
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        `;
        
        const texto = document.createElement('div');
        texto.textContent = mensaje;
        texto.style.cssText = `
            font-size: 14px;
            font-weight: 500;
        `;
        
        indicador.appendChild(spinner);
        indicador.appendChild(texto);
        
        // Limpiar contenedor y agregar indicador
        contenedor.innerHTML = '';
        contenedor.appendChild(indicador);
        
        console.log('[LoadingManager] Carga mostrada en contenedor:', contenedor.id || contenedor.className);
    }

    /**
     * Oculta el indicador de carga y restaura el contenido original
     */
    ocultarCargandoEnContenedor(contenedor) {
        if (typeof contenedor === 'string') {
            contenedor = document.getElementById(contenedor);
        }
        
        if (!contenedor) {
            console.warn('[LoadingManager] Contenedor no encontrado para ocultar carga');
            return;
        }
        
        // Restaurar contenido original si existe
        if (contenedor.dataset.contenidoOriginal) {
            contenedor.innerHTML = contenedor.dataset.contenidoOriginal;
            delete contenedor.dataset.contenidoOriginal;
        } else {
            contenedor.innerHTML = '';
        }
        
        console.log('[LoadingManager] Carga ocultada en contenedor:', contenedor.id || contenedor.className);
    }

    /**
     * Muestra una barra de progreso
     */
    mostrarBarraProgreso(contenedor, progreso = 0, mensaje = '') {
        if (typeof contenedor === 'string') {
            contenedor = document.getElementById(contenedor);
        }
        
        if (!contenedor) {
            console.warn('[LoadingManager] Contenedor no encontrado para barra de progreso');
            return;
        }
        
        const barra = document.createElement('div');
        barra.style.cssText = `
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        `;
        
        const relleno = document.createElement('div');
        relleno.style.cssText = `
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            width: ${progreso}%;
            transition: width 0.3s ease;
        `;
        
        barra.appendChild(relleno);
        
        if (mensaje) {
            const texto = document.createElement('div');
            texto.textContent = mensaje;
            texto.style.cssText = `
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            `;
            contenedor.appendChild(texto);
        }
        
        contenedor.appendChild(barra);
        
        return { barra, relleno };
    }

    /**
     * Actualiza una barra de progreso existente
     */
    actualizarBarraProgreso(barraProgreso, progreso, mensaje = '') {
        if (!barraProgreso || !barraProgreso.relleno) {
            console.warn('[LoadingManager] Barra de progreso no válida');
            return;
        }
        
        barraProgreso.relleno.style.width = `${progreso}%`;
        
        if (mensaje && barraProgreso.contenedorMensaje) {
            barraProgreso.contenedorMensaje.textContent = mensaje;
        }
    }

    /**
     * Muestra un estado de carga con puntos animados
     */
    mostrarCargandoConPuntos(contenedor, mensajeBase = 'Cargando') {
        if (typeof contenedor === 'string') {
            contenedor = document.getElementById(contenedor);
        }
        
        if (!contenedor) {
            console.warn('[LoadingManager] Contenedor no encontrado para carga con puntos');
            return;
        }
        
        let puntos = 0;
        const elemento = document.createElement('div');
        elemento.style.cssText = `
            color: #666;
            font-size: 14px;
            font-weight: 500;
        `;
        
        const actualizarPuntos = () => {
            puntos = (puntos + 1) % 4;
            elemento.textContent = mensajeBase + '.'.repeat(puntos);
        };
        
        contenedor.appendChild(elemento);
        const intervalo = setInterval(actualizarPuntos, 500);
        
        // Devolver función para detener la animación
        return () => {
            clearInterval(intervalo);
            if (elemento.parentNode) {
                elemento.remove();
            }
        };
    }

    /**
     * Verifica si hay un spinner activo
     */
    estaCargando() {
        return !!document.getElementById('factura-spinner');
    }

    /**
     * Muestra un overlay de carga para toda la página
     */
    mostrarOverlayCarga(mensaje = 'Procesando...') {
        this.mostrarCargando(mensaje);
    }

    /**
     * Oculta el overlay de carga
     */
    ocultarOverlayCarga() {
        this.ocultarCargando();
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.loadingManager = new LoadingManager();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.loadingManager = new LoadingManager();
    });
} else {
    window.loadingManager = new LoadingManager();
}
