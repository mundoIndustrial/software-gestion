/**
 * Real-Time Table Refresh System
 * Refresca automáticamente la tabla de pedidos cada X segundos
 */

class PedidosRealtimeRefresh {
    constructor(options = {}) {
        this.checkInterval = options.checkInterval || 1000; // Verificar cada 1 segundo
        this.autoStart = options.autoStart !== false; // Auto-iniciar por defecto
        this.isRunning = false;
        this.lastUpdateTime = null;
        this.lastChangeTime = null;
        this.intervaloId = null;
        this.pedidosAnterior = new Map(); // Almacenar estado anterior para detectar cambios
        
        // Elementos DOM
        this.tableContainer = document.querySelector('.table-scroll-container');
        this.tableHeader = document.querySelector('[style*="grid-template-columns"]');
        
        this.init();
    }

    init() {
        console.log('🔄 [PedidosRealtime] Sistema de detección de cambios inicializado');
        
        if (this.autoStart) {
            this.start();
        }
        
        // Crear indicador visual de refresco
        this.crearIndicadorRefresco();
        
        // Agregar controles
        this.agregarControles();
    }

    crearIndicadorRefresco() {
        const indicador = document.createElement('div');
        indicador.id = 'realtime-refresh-indicator';
        indicador.innerHTML = `
            <div style="
                position: fixed;
                top: 80px;
                right: 20px;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.85rem;
            ">
                <span class="refresh-status" style="color: #6b7280;">
                    ✅ Monitoreo activo
                </span>
                <span class="refresh-time" style="color: #9ca3af; font-size: 0.75rem;">
                    Esperando cambios...
                </span>
            </div>
        `;
        document.body.appendChild(indicador);
        this.indicador = indicador;
    }

    agregarControles() {
        const contenedorContrales = document.createElement('div');
        contenedorContrales.id = 'realtime-controls';
        contenedorContrales.innerHTML = `
            <div style="
                position: fixed;
                top: 140px;
                right: 20px;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                display: flex;
                gap: 4px;
            ">
                <button id="btn-refresco-manual" title="Refrescar ahora" style="
                    padding: 8px 12px;
                    background: #1e40af;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.8rem;
                    font-weight: 600;
                ">
                    🔄 Actualizar
                </button>
                <button id="btn-pausa-refresco" title="Pausa/Reanudar" style="
                    padding: 8px 12px;
                    background: #6b7280;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 0.8rem;
                    font-weight: 600;
                ">
                    ⏸️ Pausar
                </button>
            </div>
        `;
        document.body.appendChild(contenedorContrales);
        
        // Event listeners
        document.getElementById('btn-refresco-manual').addEventListener('click', () => this.verificarAhora());
        document.getElementById('btn-pausa-refresco').addEventListener('click', () => this.togglePausa());
    }

    start() {
        if (this.isRunning) {
            console.log('🔄 [PedidosRealtime] Ya está monitoreando');
            return;
        }
        
        console.log('🔄 [PedidosRealtime] ✅ Iniciando monitoreo de cambios');
        this.isRunning = true;
        this.actualizarIndicador();
        
        // Verificar cambios cada segundo
        this.intervaloId = setInterval(() => this.verificar(), this.checkInterval);
    }

    stop() {
        if (!this.isRunning) {
            console.log('🔄 [PedidosRealtime] Ya está pausado');
            return;
        }
        
        console.log('🔄 [PedidosRealtime] ⏸️ Pausado');
        this.isRunning = false;
        clearInterval(this.intervaloId);
        this.actualizarIndicador();
    }

    togglePausa() {
        if (this.isRunning) {
            this.stop();
        } else {
            this.start();
        }
    }

    verificarAhora() {
        console.log('🔄 [PedidosRealtime] Verificando cambios ahora...');
        this.verificar();
    }

    async verificar() {
        try {
            // Obtener parámetros de URL actuales
            const urlParams = new URLSearchParams(window.location.search);
            const tipo = urlParams.get('tipo') || '';
            const estado = urlParams.get('estado') || '';
            const search = urlParams.get('search') || '';
            
            // Construir URL de API
            let url = '/asesores/pedidos/api/listar';
            const params = new URLSearchParams();
            if (tipo) params.append('tipo', tipo);
            if (estado) params.append('estado', estado);
            if (search) params.append('search', search);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                // Detectar cambios comparando con datos anteriores
                const haysCambios = this.detectarCambios(result.data);
                
                if (haysCambios) {
                    console.log('🔄 [PedidosRealtime] ✅ Cambios detectados, actualizando tabla...');
                    this.actualizarTabla(result.data);
                    this.lastChangeTime = new Date();
                    this.actualizarIndicadorCambio();
                }
            }
            
        } catch (error) {
            console.error('❌ [PedidosRealtime] Error al verificar:', error);
        }
    }

    detectarCambios(pedidosNuevos) {
        // Si es la primera vez, guardar y no actualizar
        if (this.pedidosAnterior.size === 0) {
            this.guardarEstadoPedidos(pedidosNuevos);
            return false;
        }
        
        // Verificar si hay cambios
        let haysCambios = false;
        
        // Verificar nuevos pedidos
        if (pedidosNuevos.length !== this.pedidosAnterior.size) {
            console.log('📊 [PedidosRealtime] Cantidad de pedidos cambió:', this.pedidosAnterior.size, '->', pedidosNuevos.length);
            haysCambios = true;
        }
        
        // Verificar cambios en pedidos existentes
        for (const pedido of pedidosNuevos) {
            const anterior = this.pedidosAnterior.get(pedido.id);
            
            if (!anterior) {
                console.log('➕ [PedidosRealtime] Nuevo pedido:', pedido.id);
                haysCambios = true;
                continue;
            }
            
            // Comparar campos importantes
            if (anterior.estado !== pedido.estado) {
                console.log('🔄 [PedidosRealtime] Estado cambió (Pedido #' + pedido.id + '):', anterior.estado, '->', pedido.estado);
                haysCambios = true;
            }
            
            if (anterior.novedades !== pedido.novedades) {
                console.log('📝 [PedidosRealtime] Novedades cambió (Pedido #' + pedido.id + ')');
                haysCambios = true;
            }
        }
        
        // Guardar estado actual para próxima comparación
        this.guardarEstadoPedidos(pedidosNuevos);
        
        return haysCambios;
    }

    guardarEstadoPedidos(pedidos) {
        this.pedidosAnterior.clear();
        for (const pedido of pedidos) {
            this.pedidosAnterior.set(pedido.id, {
                estado: pedido.estado,
                novedades: pedido.novedades,
                forma_pago: pedido.forma_pago,
                fecha_estimada: pedido.fecha_estimada,
            });
        }
    }

    actualizarTabla(pedidos) {
        // Obtener contenedor de filas
        const tablasContainer = document.querySelector('.table-scroll-container');
        if (!tablasContainer) {
            console.error('❌ [PedidosRealtime] No se encontró el contenedor de la tabla');
            return;
        }
        
        // Obtener filas actuales
        const filasActuales = tablasContainer.querySelectorAll('[data-pedido-id]');
        const pedidosActuales = new Map(
            Array.from(filasActuales).map(fila => [
                parseInt(fila.dataset.pedidoId),
                fila
            ])
        );
        
        // Crear mapa de pedidos nuevos
        const pedidosNuevos = new Map(
            pedidos.map(p => [p.id, p])
        );
        
        // Actualizar filas existentes
        for (const [id, fila] of pedidosActuales) {
            if (pedidosNuevos.has(id)) {
                this.actualizarFila(fila, pedidosNuevos.get(id));
            } else {
                // Eliminar filas que ya no existen (con animación)
                fila.style.opacity = '0.5';
                fila.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => fila.remove(), 300);
            }
        }
        
        // Agregar nuevas filas
        const filasPadre = tablasContainer.querySelector('[style*="grid-template-columns"]')?.parentElement;
        for (const [id, pedido] of pedidosNuevos) {
            if (!pedidosActuales.has(id)) {
                console.log('➕ [PedidosRealtime] Nuevo pedido agregado:', id);
                // Las nuevas filas se agregan con una animación
                this.agregarFilaNueva(pedido);
            }
        }
    }

    actualizarFila(fila, pedido) {
        // Buscar celdas por posición
        const celdas = fila.querySelectorAll('[style*="display: flex"]');
        if (celdas.length >= 8) {
            // Estado (índice 1)
            const celdaEstado = celdas[1];
            const estadoActual = celdaEstado.textContent.trim();
            if (estadoActual !== pedido.estado) {
                celdaEstado.textContent = pedido.estado;
                fila.style.background = '#fef3c7'; // Resaltar cambio
                setTimeout(() => {
                    fila.style.background = '';
                    fila.style.transition = 'background-color 0.5s ease-out';
                }, 2000);
            }
            
            // Novedades (índice 5)
            if (celdas.length > 5) {
                const celdaNovedades = celdas[5];
                if (pedido.novedades) {
                    const conteo = (pedido.novedades.match(/\n/g) || []).length + 1;
                    celdaNovedades.textContent = conteo > 0 ? `${conteo} novedades` : 'Sin novedades';
                } else {
                    celdaNovedades.textContent = 'Sin novedades';
                }
            }
        }
    }

    agregarFilaNueva(pedido) {
        // Aquí se agregría lógica para crear una nueva fila con animación
        // Por ahora, se deja para recargar la página si hay nuevos pedidos
        console.log('📝 Nueva fila:', pedido);
    }

    actualizarIndicador() {
        const status = document.querySelector('.refresh-status');
        if (!status) return;
        
        if (this.isRunning) {
            status.innerHTML = '✅ Monitoreo activo';
            status.style.color = '#10b981';
            document.getElementById('btn-pausa-refresco').textContent = '⏸️ Pausar';
            document.getElementById('btn-pausa-refresco').style.background = '#6b7280';
        } else {
            status.innerHTML = '⏸️ Pausado';
            status.style.color = '#6b7280';
            document.getElementById('btn-pausa-refresco').textContent = '▶️ Reanudar';
            document.getElementById('btn-pausa-refresco').style.background = '#10b981';
        }
    }

    actualizarIndicadorCambio() {
        const status = document.querySelector('.refresh-status');
        const timeEl = document.querySelector('.refresh-time');
        
        if (status) {
            status.innerHTML = '✨ Cambio detectado';
            status.style.color = '#f59e0b';
            
            setTimeout(() => {
                if (this.isRunning) {
                    status.innerHTML = '✅ Monitoreo activo';
                    status.style.color = '#10b981';
                }
            }, 2000);
        }
        
        if (timeEl) {
            timeEl.textContent = 'Cambio hace unos segundos';
        }
    }

    actualizarTimestamp() {
        const timeEl = document.querySelector('.refresh-time');
        if (!timeEl || !this.lastChangeTime) {
            if (timeEl && !this.isRunning) {
                timeEl.textContent = 'Monitoreo pausado';
            }
            return;
        }
        
        const ahora = new Date();
        const diferencia = Math.floor((ahora - this.lastChangeTime) / 1000);
        
        let texto = 'Esperando cambios...';
        if (diferencia < 60) {
            texto = 'Cambio hace ' + diferencia + 's';
        } else if (diferencia < 3600) {
            const minutos = Math.floor(diferencia / 60);
            texto = 'Cambio hace ' + minutos + 'm';
        }
        
        timeEl.textContent = texto;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
        checkInterval: 1000, // Verificar cada 1 segundo, pero solo actualizar si hay cambios
        autoStart: true
    });
    
    // Actualizar timestamp cada segundo
    setInterval(() => {
        window.pedidosRealtimeRefresh?.actualizarTimestamp();
    }, 1000);
});
