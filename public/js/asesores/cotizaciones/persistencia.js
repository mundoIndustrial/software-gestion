/**
 * SISTEMA DE COTIZACIONES - PERSISTENCIA DE DATOS
 * Responsabilidad: Guardar y recuperar datos en localStorage
 * Compatible con: WebSockets/Reverb (sin conflictos)
 * 
 * NOTA IMPORTANTE SOBRE ARCHIVOS:
 * - Los archivos (images, files) NO se pueden persistir en localStorage
 * - Por razones de seguridad, JavaScript no permite establecer archivos en inputs type="file"
 * - Solo se restauran datos de texto, números, selects, textareas, etc.
 * - Los archivos deben ser seleccionados nuevamente por el usuario
 */

const STORAGE_KEY_PREFIX = 'cotizacion_prenda_';
const STORAGE_SPECS_KEY = 'especificacionesSeleccionadas';
const STORAGE_PRODUCTOS_KEY = 'productosGuardados';
const STORAGE_TIMESTAMP = 'ultimaModificacion';
const STORAGE_VERSION = 'v1.0'; // Para invalidar datos si cambia la estructura

// ============ GUARDAR EN LOCALSTORAGE ============

function guardarDatosEnStorage() {
    try {
        const datos = {
            // Datos generales del formulario
            cliente: document.querySelector('[name="cliente"]')?.value || '',
            email: document.querySelector('[name="email"]')?.value || '',
            telefono: document.querySelector('[name="telefono"]')?.value || '',
            direccion: document.querySelector('[name="direccion"]')?.value || '',
            ciudad: document.querySelector('[name="ciudad"]')?.value || '',
            observaciones: document.querySelector('[name="observaciones"]')?.value || '',
            
            // Especificaciones guardadas
            especificaciones: window.especificacionesSeleccionadas || {},
            
            // Timestamp de última modificación y versión
            timestamp: new Date().toISOString(),
            version: STORAGE_VERSION
        };
        
        localStorage.setItem(STORAGE_KEY_PREFIX + 'datos_generales', JSON.stringify(datos));
        console.log('💾 Datos generales guardados en localStorage:', datos);
        
        // Guardar también especificaciones de forma independiente
        if (window.especificacionesSeleccionadas && Object.keys(window.especificacionesSeleccionadas).length > 0) {
            localStorage.setItem(STORAGE_SPECS_KEY, JSON.stringify(window.especificacionesSeleccionadas));
            console.log(' Especificaciones guardadas:', window.especificacionesSeleccionadas);
        }
        
    } catch (error) {
        console.error(' Error al guardar en localStorage:', error);
    }
}

function guardarProductosEnStorage() {
    try {
        const contenedor = document.getElementById('productosContainer');
        if (!contenedor) return;
        
        const productos = [];
        contenedor.querySelectorAll('.producto-card').forEach((card, idx) => {
            const producto = {
                indice: idx + 1,
                datos: {}
            };
            
            // Recopilar todos los inputs del producto
            card.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.name) {
                    producto.datos[input.name] = input.value;
                }
            });
            
            productos.push(producto);
        });
        
        localStorage.setItem(STORAGE_PRODUCTOS_KEY, JSON.stringify(productos));
        console.log('📦 Productos guardados en localStorage:', productos.length, 'productos');
        
    } catch (error) {
        console.error(' Error al guardar productos:', error);
    }
}

// ============ CARGAR DESDE LOCALSTORAGE ============

function cargarDatosDesdeStorage() {
    try {
        const datosGuardados = localStorage.getItem(STORAGE_KEY_PREFIX + 'datos_generales');
        if (!datosGuardados) {
            console.log(' No hay datos guardados en localStorage');
            return false;
        }
        
        const datos = JSON.parse(datosGuardados);
        
        // Validar versión
        if (datos.version !== STORAGE_VERSION) {
            console.warn('⚠️ Versión de datos incompatible, ignorando');
            limpiarStorage();
            return false;
        }
        
        // Restaurar datos generales
        const campos = ['cliente', 'email', 'telefono', 'direccion', 'ciudad', 'observaciones'];
        campos.forEach(campo => {
            const input = document.querySelector(`[name="${campo}"]`);
            if (input && datos[campo]) {
                input.value = datos[campo];
                console.log(`✓ Restaurado: ${campo}`);
            }
        });
        
        // Restaurar especificaciones
        if (datos.especificaciones && Object.keys(datos.especificaciones).length > 0) {
            window.especificacionesSeleccionadas = datos.especificaciones;
            console.log('✓ Especificaciones restauradas');
        }
        
        const fecha = new Date(datos.timestamp).toLocaleString('es-CO');
        console.log(` Datos cargados desde localStorage (última modificación: ${fecha})`);
        
        return true;
        
    } catch (error) {
        console.error(' Error al cargar desde localStorage:', error);
        return false;
    }
}

function cargarProductosDesdeStorage() {
    try {
        const productosGuardados = localStorage.getItem(STORAGE_PRODUCTOS_KEY);
        if (!productosGuardados) {
            console.log(' No hay productos guardados en localStorage');
            return false;
        }
        
        const productos = JSON.parse(productosGuardados);
        const contenedor = document.getElementById('productosContainer');
        
        if (!contenedor) return false;
        
        // Limpiar productos existentes excepto el primero
        const productosExistentes = contenedor.querySelectorAll('.producto-card');
        for (let i = productosExistentes.length - 1; i > 0; i--) {
            productosExistentes[i].remove();
        }
        
        // Cargar productos
        productos.forEach((producto, idx) => {
            if (idx === 0) {
                // Restaurar datos en el primer producto
                Object.entries(producto.datos).forEach(([name, value]) => {
                    const input = contenedor.querySelector(`[name="${name}"]`);
                    // No restaurar inputs de tipo file (no se pueden establecer por seguridad)
                    if (input && input.type !== 'file') {
                        input.value = value;
                    }
                });
            } else {
                // Agregar nuevos productos
                agregarProductoPrenda();
                const ultimoProducto = contenedor.querySelector('.producto-card:last-child');
                
                // Restaurar datos en el nuevo producto
                Object.entries(producto.datos).forEach(([name, value]) => {
                    const input = ultimoProducto.querySelector(`[name="${name}"]`);
                    // No restaurar inputs de tipo file (no se pueden establecer por seguridad)
                    if (input && input.type !== 'file') {
                        input.value = value;
                    }
                });
            }
        });
        
        console.log(` ${productos.length} productos cargados desde localStorage`);
        return true;
        
    } catch (error) {
        console.error(' Error al cargar productos:', error);
        return false;
    }
}

// ============ LIMPIAR LOCALSTORAGE ============

function limpiarStorage() {
    try {
        // Limpiar localStorage
        localStorage.removeItem(STORAGE_KEY_PREFIX + 'datos_generales');
        localStorage.removeItem(STORAGE_SPECS_KEY);
        localStorage.removeItem(STORAGE_PRODUCTOS_KEY);
        
        // Limpiar variables globales
        window.especificacionesSeleccionadas = {};
        window.imagenesEnMemoria = { prenda: [], tela: [], logo: [], prendaConIndice: [], telaConIndice: [] };
        
        // Limpiar seccionesSeleccionadasFriendly si existe
        if (typeof seccionesSeleccionadasFriendly !== 'undefined') {
            window.seccionesSeleccionadasFriendly = [];
        }
        
        // Limpiar fotosSeleccionadas si existe
        if (typeof fotosSeleccionadas !== 'undefined') {
            window.fotosSeleccionadas = {};
        }
        
        console.log('🗑️ localStorage limpiado completamente');
        console.log('🗑️ Variables globales limpiadas');
    } catch (error) {
        console.error(' Error al limpiar localStorage:', error);
    }
}

// ============ LIMPIAR FORMULARIO COMPLETAMENTE ============

function limpiarFormularioCompleto() {
    try {
        console.log('🧹 Iniciando limpieza completa del formulario...');
        
        // 1. Limpiar localStorage
        if (typeof limpiarStorage === 'function') {
            limpiarStorage();
        }
        
        // 2. Buscar y limpiar ambos formularios
        let form = document.getElementById('formCrearPedidoFriendly');
        if (!form) {
            form = document.getElementById('cotizacionPrendaForm');
        }
        
        if (form) {
            // Limpiar todos los inputs, textareas, selects
            form.querySelectorAll('input, textarea, select').forEach(input => {
                if (input.type !== 'file') {
                    input.value = '';
                    input.checked = false;
                }
            });
            console.log('✓ Formulario HTML limpiado');
        }
        
        // 3. Limpiar contenedor de productos
        const productosContainer = document.getElementById('productosContainer');
        if (productosContainer) {
            // Obtener todos los productos excepto el primero
            const productos = productosContainer.querySelectorAll('.producto-card');
            for (let i = productos.length - 1; i > 0; i--) {
                productos[i].remove();
            }
            
            // Limpiar el primer producto completamente
            const primerProducto = productosContainer.querySelector('.producto-card');
            if (primerProducto) {
                // Limpiar todos los inputs del primer producto
                primerProducto.querySelectorAll('input, textarea, select').forEach(input => {
                    if (input.type !== 'file') {
                        input.value = '';
                        input.checked = false;
                    }
                });
                
                // Limpiar previsualizaciones de fotos
                primerProducto.querySelectorAll('.fotos-preview, .foto-tela-preview').forEach(preview => {
                    preview.innerHTML = '';
                });
            }
            console.log('✓ Contenedor de productos limpiado');
        }
        
        // 3.5. Limpiar memoria de fotos seleccionadas
        if (window.fotosSeleccionadas) {
            window.fotosSeleccionadas = {};
            console.log('✓ window.fotosSeleccionadas limpiado');
        }
        if (window.telasSeleccionadas) {
            window.telasSeleccionadas = {};
            console.log('✓ window.telasSeleccionadas limpiado');
        }
        if (window.fotosEliminadasServidor) {
            window.fotosEliminadasServidor = { prendas: [], telas: [] };
            console.log('✓ window.fotosEliminadasServidor limpiado');
        }
        
        // 4. Limpiar secciones de ubicación
        const seccionesContainer = document.getElementById('secciones_agregadas');
        if (seccionesContainer) {
            seccionesContainer.innerHTML = '';
            console.log('✓ Secciones de ubicación limpiadas');
        }
        
        // 5. Limpiar modal de especificaciones
        const modalEspecificaciones = document.getElementById('modalEspecificaciones');
        if (modalEspecificaciones) {
            // Limpiar todos los checkboxes del modal
            modalEspecificaciones.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            // Limpiar todos los inputs de texto del modal
            modalEspecificaciones.querySelectorAll('input[type="text"]').forEach(input => {
                input.value = '';
            });
            console.log('✓ Modal de especificaciones limpiado');
        }
        
        // 6. Resetear botón ENVIAR a rojo
        const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
        if (btnEnviar) {
            btnEnviar.style.background = '#ef4444';
            btnEnviar.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
            console.log('✓ Botón ENVIAR resetado a rojo');
        }
        
        // 7. Limpiar variables globales adicionales (SOLO si existen)
        if (typeof fotosSeleccionadas !== 'undefined') {
            window.fotosSeleccionadas = {};
        }
        if (typeof especificacionesSeleccionadas !== 'undefined') {
            window.especificacionesSeleccionadas = {};
        }
        if (typeof imagenesEnMemoria !== 'undefined') {
            window.imagenesEnMemoria = { prenda: [], tela: [], logo: [], prendaConIndice: [], telaConIndice: [] };
        }
        if (typeof seccionesSeleccionadasFriendly !== 'undefined') {
            window.seccionesSeleccionadasFriendly = [];
        }
        // NO limpiar tecnicasSeleccionadas, seccionesSeleccionadas, observacionesGenerales, imagenesSeleccionadas
        // porque se declaran DESPUÉS de que se carga este script
        
        console.log(' Limpieza completa del formulario finalizada');
        
    } catch (error) {
        console.error(' Error al limpiar formulario:', error);
    }
}

// ============ AUTO-GUARDADO ============

function configurarAutoGuardado() {
    // Auto-guardar cada 5 segundos
    setInterval(() => {
        guardarDatosEnStorage();
        guardarProductosEnStorage();
    }, 5000);
    
    console.log('⏱️ Auto-guardado configurado (cada 5 segundos)');
}

// ============ INICIALIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    //  DESACTIVADO: No cargar datos del localStorage
    // Esto evita que se carguen datos de cotizaciones anteriores
    console.log('📝 localStorage DESACTIVADO - Formulario limpio');
    
    // ⚠️ NO limpiar si estamos en la página de bordado
    // La página de bordado declara sus propias variables globales DESPUÉS de que se carga este script
    if (window.location.pathname.includes('/cotizaciones-bordado/') || window.location.pathname.includes('/cotizaciones/bordado/')) {
        console.log('⚠️ Página de bordado detectada - No se ejecuta limpiarFormularioCompleto()');
    } else if (!window.location.search.includes('editar=')) {
        limpiarFormularioCompleto();
    } else {
        console.log('⚠️ Modo edición detectado - No se limpian variables globales');
    }
    
    //  DESACTIVADO: Auto-guardado desactivado
    // configurarAutoGuardado();
    
    //  DESACTIVADO: No guardar antes de cerrar
    // window.addEventListener('beforeunload', function() {
    //     guardarDatosEnStorage();
    //     guardarProductosEnStorage();
    // });
    
    // Actualizar especificaciones cuando se guarden desde el modal
    const originalGuardarEspecificaciones = window.guardarEspecificaciones;
    if (originalGuardarEspecificaciones) {
        window.guardarEspecificaciones = function() {
            originalGuardarEspecificaciones();
            //  DESACTIVADO: No guardar en localStorage
            // guardarDatosEnStorage();
            console.log('✓ Especificaciones guardadas');
        };
    }
});

// ============ MOSTRAR ESTADO ============

function mostrarEstorageSummary() {
    const datosGenerales = localStorage.getItem(STORAGE_KEY_PREFIX + 'datos_generales');
    const productos = localStorage.getItem(STORAGE_PRODUCTOS_KEY);
    const specs = localStorage.getItem(STORAGE_SPECS_KEY);
    
    let summary = '📊 Estado de localStorage:\n';
    summary += datosGenerales ? '✓ Datos generales guardados\n' : '✗ Sin datos generales\n';
    
    if (productos) {
        try {
            const datosProductos = JSON.parse(productos);
            summary += `✓ ${datosProductos.cantidad || 0} productos guardados\n`;
        } catch (e) {
            summary += '✗ Error al leer productos\n';
        }
    } else {
        summary += '✗ Sin productos\n';
    }
    
    summary += specs ? `✓ Especificaciones guardadas\n` : '✗ Sin especificaciones\n';
    summary += `🌐 WebSockets: ${window.Echo ? 'Disponible ✓' : 'No disponible'}\n`;
    
    console.log(summary);
    return summary;
}

// ============ NOTAS SOBRE WEBSOCKETS ============
/*
 * localStorage y WebSockets son completamente independientes y sin conflictos:
 * 
 * - localStorage: Persistencia local en el navegador (datos que quedan si recarga)
 * - WebSockets: Comunicación en tiempo real con el servidor (Reverb/Echo)
 * 
 * Usos complementarios:
 * 1. localStorage para guardar borradores localmente
 * 2. WebSockets para notificar cambios a otros usuarios en tiempo real
 * 
 * Si quieres sincronizar datos entre usuarios vía WebSocket:
 * - Los datos se guardan en localStorage primero
 * - Se notifica a través de WebSocket a otros usuarios
 * - Cada usuario recibe la notificación y actualiza su localStorage
 * 
 * No hay conflicto de rendimiento ni de datos. Son complementarios.
 */
