/**
 * PedidosRecibosModule.js
 * Módulo principal para gestión de recibos dinámicos en pedidos
 * 
 * Integra: ModalManager, CloseButtonManager, NavigationManager, 
 *          GalleryManager, ReceiptRenderer, y utilidades
 */

import { ReceiptBuilder } from './utils/ReceiptBuilder.js';
import { ReceiptRenderer } from './components/ReceiptRenderer.js';
import { NavigationManager } from './components/NavigationManager.js';
import { GalleryManager } from './components/GalleryManager.js';
import { CloseButtonManager } from './components/CloseButtonManager.js';
import { ModalManager } from './components/ModalManager.js';
import { Formatters } from './utils/Formatters.js';

export class PedidosRecibosModule {
    constructor() {
        this.modalManager = new ModalManager();
        this.validaciones();
    }

    /**
     * Valida que existan los elementos necesarios en el DOM
     */
    validaciones() {
        const elementosCriticos = [
            'order-detail-modal-wrapper',
            'modal-overlay'
        ];

        const faltantes = elementosCriticos.filter(id => !document.getElementById(id));
        if (faltantes.length > 0) {

        }

        // Elementos opcionales - solo advertencia
        const elementosOpcionales = [
            'receipt-title',
            'cliente-value',
            'asesora-value'
        ];
        const opcionalesFaltantes = elementosOpcionales.filter(id => !document.getElementById(id));
        if (opcionalesFaltantes.length > 0) {

        }
    }

    /**
     * FUNCIÓN PRINCIPAL: Abre un recibo específico en el modal
     * 
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoRecibo - Tipo de recibo (STRING)
     * @param {number} prendaIndex - Índice de la prenda (opcional)
     */
    async abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex = null) {
        // VALIDACIÓN: Bloquear COSTURA-BODEGA en supervisor-pedidos y registros
        const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
        const esRegistros = window.location.pathname.includes('/registros');
        if ((esSupervisorPedidos || esRegistros) && tipoRecibo === 'costura-bodega') {
            console.warn('🚫 [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA - BLOQUEADO');
            return;
        }
        
        // Validaciones
        if (typeof tipoRecibo !== 'string') {

            alert('Error: tipo de recibo debe ser texto');
            return;
        }

        if (typeof prendaId !== 'number') {

            alert('Error: ID de prenda debe ser número');
            return;
        }

        try {
            // Resetear cualquier galería previa para evitar que quede pegada entre recibos
            GalleryManager.resetGaleria(this.modalManager);
            
            // Limpiar estado del modal para evitar caché entre recibos
            this.modalManager.limpiarEstado();

            // Actualizar estado con los nuevos datos
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar el endpoint según el contexto
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                // Contexto de registros
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                // Contexto de insumos - usar endpoint de registros para evitar filtro de de_bodega
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                // Contexto público (accesible para cualquier usuario autenticado)
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log(' [PedidosRecibosModule] Endpoint seleccionado:', endpoint);
            console.log(' [PedidosRecibosModule] Parámetros recibidos:', {
                pedidoId,
                prendaId,
                tipoRecibo,
                prendaIndex
            });

            // Obtener datos del servidor
            const response = await fetch(endpoint);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            let datos = await response.json();            
            // Manejar respuesta envuelta en { success: true, data: {...} }
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            // Normalización: el backend puede enviar `proceso.tallas` como ARRAY de detalles
            // (con ubicaciones/observaciones por talla). El renderer/Formatters espera
            // `proceso.tallas_detalle` para pintar "UBICACIONES POR TALLA".
            if (datos && Array.isArray(datos.prendas)) {
                datos.prendas.forEach((prenda) => {
                    if (!prenda || !Array.isArray(prenda.procesos)) return;
                    prenda.procesos.forEach((proceso) => {
                        if (!proceso) return;

                        // Preferir ubicaciones_array (si ya viene decodificado del backend)
                        if (!proceso.ubicaciones && Array.isArray(proceso.ubicaciones_array)) {
                            proceso.ubicaciones = proceso.ubicaciones_array;
                        }

                        // Caso: `tallas` viene como array -> es el detalle por talla
                        if (Array.isArray(proceso.tallas)) {
                            // Alias para que Formatters lo tome
                            if (!Array.isArray(proceso.tallas_detalle) || proceso.tallas_detalle.length === 0) {
                                proceso.tallas_detalle = proceso.tallas;
                            }
                        }

                        // Caso: modo_tallas=general y viene observaciones_por_talla como objeto
                        // -> convertir a tallas_detalle para que Formatters pinte "OBSERVACIONES POR TALLA"
                        const modo = String(proceso.modo_tallas || '').toLowerCase();
                        if (modo === 'general' && proceso.observaciones_por_talla && typeof proceso.observaciones_por_talla === 'object') {
                            const normalizarObs = (raw) => {
                                const s = String(raw ?? '').trim();
                                if (!s) return '';
                                return s;
                            };

                            const out = [];
                            const mapGenero = {
                                dama: 'DAMA',
                                caballero: 'CABALLERO',
                                unisex: 'UNISEX'
                            };

                            Object.keys(mapGenero).forEach((k) => {
                                const grupo = proceso.observaciones_por_talla[k];
                                if (!grupo || typeof grupo !== 'object') return;
                                Object.keys(grupo).forEach((tallaKey) => {
                                    const obs = normalizarObs(grupo[tallaKey]);
                                    if (!obs) return;
                                    out.push({
                                        genero: mapGenero[k],
                                        talla: tallaKey,
                                        cantidad: 1,
                                        observaciones: obs
                                    });
                                });
                            });

                            if (out.length > 0) {
                                proceso.tallas_detalle = out;
                            }
                        }
                    });
                });
            }
            
            console.group('[PedidosRecibosModule.abrirRecibo] 📥 DATOS RECIBIDOS DEL ENDPOINT');
            console.log('Endpoint:', endpoint);
            console.log('Cliente:', datos.cliente);
            console.log('Asesor:', datos.asesor);
            console.log('Forma de pago:', datos.forma_de_pago);
            console.log('Número pedido:', datos.numero_pedido);
            console.log('Total prendas:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
            console.log('IDs de prendas disponibles:', datos.prendas ? datos.prendas.map(p => p.id) : 'NONE');
            console.log('Buscando prenda_id:', prendaId);
            console.groupEnd();
                        
            this.modalManager.setState({ datosCompletos: datos });

            // Validar que existan prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            console.log(`[PedidosRecibosModule] 🔍 Buscando prenda con ID ${prendaId} entre ${datos.prendas.length} prendas`);
            console.log(`[PedidosRecibosModule] 📋 IDs disponibles:`, datos.prendas.map(p => ({ id: p.id, nombre: p.nombre || p.nombre_prenda })));
            
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            
            if (!prendaData) {
                console.error(`[PedidosRecibosModule] ❌ Prenda ${prendaId} no encontrada`);
                console.error(`[PedidosRecibosModule] 🔍 Búsqueda realizada con:`, {
                    buscarPor: 'id',
                    valorBuscado: prendaId,
                    tipoDeComparacion: '== (flexible)',
                    prendasDisponibles: datos.prendas.map(p => ({
                        id: p.id,
                        tipoId: typeof p.id,
                        nombre: p.nombre || p.nombre_prenda
                    }))
                });
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }
            
            console.log(`[PedidosRecibosModule] ✅ Prenda encontrada:`, {
                id: prendaData.id,
                nombre: prendaData.nombre || prendaData.nombre_prenda,
                tieneRecibos: !!prendaData.recibos,
                totalRecibos: prendaData.recibos ? prendaData.recibos.length : 0
            });

            // Debug: Verificar si los recibos están llegando desde el backend
            console.log(' [PedidosRecibosModule] Prenda encontrada:', {
                prendaId,
                prendaData: prendaData,
                recibos: prendaData.recibos,
                tieneRecibos: !!prendaData.recibos
            });



            // Construir lista de recibos
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            let reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);

            // Fallback: anexos de COSTURA pueden venir como "COSTURA" pero el recibo base
            // en prendas de bodega se construye como "costura-bodega".
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega' || tipoLower === 'costura anexo' || tipoLower === 'costura-anexo' || tipoLower === 'costura_anexo' || tipoLower === 'costura anexo 1' || tipoLower === 'costura anexo 2' || tipoLower === 'costura anexo 3' || tipoLower === 'costura anexo 4' || tipoLower === 'costura anexo 5' || tipoLower === 'costura anexo 6' || tipoLower === 'costura anexo 7' || tipoLower === 'costura anexo 8' || tipoLower === 'costura anexo 9' || tipoLower === 'costura anexo 10' || tipoLower === 'costura anexo 11' || tipoLower === 'costura anexo 12' || tipoLower === 'costura anexo 13' || tipoLower === 'costura anexo 14' || tipoLower === 'costura anexo 15' || tipoLower === 'costura anexo 16' || tipoLower === 'costura anexo 17' || tipoLower === 'costura anexo 18' || tipoLower === 'costura anexo 19' || tipoLower === 'costura anexo 20' || tipoLower === 'costura anexo 21' || tipoLower === 'costura anexo 22' || tipoLower === 'costura anexo 23' || tipoLower === 'costura anexo 24' || tipoLower === 'costura anexo 25' || tipoLower === 'costura anexo 26' || tipoLower === 'costura anexo 27' || tipoLower === 'costura anexo 28' || tipoLower === 'costura anexo 29' || tipoLower === 'costura anexo 30' || tipoLower === 'costura anexo 31' || tipoLower === 'costura anexo 32' || tipoLower === 'costura anexo 33' || tipoLower === 'costura anexo 34' || tipoLower === 'costura anexo 35' || tipoLower === 'costura anexo 36' || tipoLower === 'costura anexo 37' || tipoLower === 'costura anexo 38' || tipoLower === 'costura anexo 39' || tipoLower === 'costura anexo 40' || tipoLower === 'costura anexo 41' || tipoLower === 'costura anexo 42' || tipoLower === 'costura anexo 43' || tipoLower === 'costura anexo 44' || tipoLower === 'costura anexo 45' || tipoLower === 'costura anexo 46' || tipoLower === 'costura anexo 47' || tipoLower === 'costura anexo 48' || tipoLower === 'costura anexo 49' || tipoLower === 'costura anexo 50' || tipoLower === 'costura anexo 51' || tipoLower === 'costura anexo 52' || tipoLower === 'costura anexo 53' || tipoLower === 'costura anexo 54' || tipoLower === 'costura anexo 55' || tipoLower === 'costura anexo 56' || tipoLower === 'costura anexo 57' || tipoLower === 'costura anexo 58' || tipoLower === 'costura anexo 59' || tipoLower === 'costura anexo 60' || tipoLower === 'costura anexo 61' || tipoLower === 'costura anexo 62' || tipoLower === 'costura anexo 63' || tipoLower === 'costura anexo 64' || tipoLower === 'costura anexo 65' || tipoLower === 'costura anexo 66' || tipoLower === 'costura anexo 67' || tipoLower === 'costura anexo 68' || tipoLower === 'costura anexo 69' || tipoLower === 'costura anexo 70' || tipoLower === 'costura anexo 71' || tipoLower === 'costura anexo 72' || tipoLower === 'costura anexo 73' || tipoLower === 'costura anexo 74' || tipoLower === 'costura anexo 75' || tipoLower === 'costura anexo 76' || tipoLower === 'costura anexo 77' || tipoLower === 'costura anexo 78' || tipoLower === 'costura anexo 79' || tipoLower === 'costura anexo 80' || tipoLower === 'costura anexo 81' || tipoLower === 'costura anexo 82' || tipoLower === 'costura anexo 83' || tipoLower === 'costura anexo 84' || tipoLower === 'costura anexo 85' || tipoLower === 'costura anexo 86' || tipoLower === 'costura anexo 87' || tipoLower === 'costura anexo 88' || tipoLower === 'costura anexo 89' || tipoLower === 'costura anexo 90' || tipoLower === 'costura anexo 91' || tipoLower === 'costura anexo 92' || tipoLower === 'costura anexo 93' || tipoLower === 'costura anexo 94' || tipoLower === 'costura anexo 95' || tipoLower === 'costura anexo 96' || tipoLower === 'costura anexo 97' || tipoLower === 'costura anexo 98' || tipoLower === 'costura anexo 99' || tipoLower === 'costura anexo 100' || tipoLower === 'costura anexo 101' || tipoLower === 'costura anexo 102' || tipoLower === 'costura anexo 103' || tipoLower === 'costura anexo 104' || tipoLower === 'costura anexo 105' || tipoLower === 'costura anexo 106' || tipoLower === 'costura anexo 107' || tipoLower === 'costura anexo 108' || tipoLower === 'costura anexo 109' || tipoLower === 'costura anexo 110' || tipoLower === 'costura anexo 111' || tipoLower === 'costura anexo 112' || tipoLower === 'costura anexo 113' || tipoLower === 'costura anexo 114' || tipoLower === 'costura anexo 115' || tipoLower === 'costura anexo 116' || tipoLower === 'costura anexo 117' || tipoLower === 'costura anexo 118' || tipoLower === 'costura anexo 119' || tipoLower === 'costura anexo 120' || tipoLower === 'costura anexo 121' || tipoLower === 'costura anexo 122' || tipoLower === 'costura anexo 123' || tipoLower === 'costura anexo 124' || tipoLower === 'costura anexo 125' || tipoLower === 'costura anexo 126' || tipoLower === 'costura anexo 127' || tipoLower === 'costura anexo 128' || tipoLower === 'costura anexo 129' || tipoLower === 'costura anexo 130' || tipoLower === 'costura anexo 131' || tipoLower === 'costura anexo 132' || tipoLower === 'costura anexo 133' || tipoLower === 'costura anexo 134' || tipoLower === 'costura anexo 135' || tipoLower === 'costura anexo 136' || tipoLower === 'costura anexo 137' || tipoLower === 'costura anexo 138' || tipoLower === 'costura anexo 139' || tipoLower === 'costura anexo 140' || tipoLower === 'costura anexo 141' || tipoLower === 'costura anexo 142' || tipoLower === 'costura anexo 143' || tipoLower === 'costura anexo 144' || tipoLower === 'costura anexo 145' || tipoLower === 'costura anexo 146' || tipoLower === 'costura anexo 147' || tipoLower === 'costura anexo 148' || tipoLower === 'costura anexo 149' || tipoLower === 'costura anexo 150' || tipoLower === 'costura anexo 151' || tipoLower === 'costura anexo 152' || tipoLower === 'costura anexo 153' || tipoLower === 'costura anexo 154' || tipoLower === 'costura anexo 155' || tipoLower === 'costura anexo 156' || tipoLower === 'costura anexo 157' || tipoLower === 'costura anexo 158' || tipoLower === 'costura anexo 159' || tipoLower === 'costura anexo 160' || tipoLower === 'costura anexo 161' || tipoLower === 'costura anexo 162' || tipoLower === 'costura anexo 163' || tipoLower === 'costura anexo 164' || tipoLower === 'costura anexo 165' || tipoLower === 'costura anexo 166' || tipoLower === 'costura anexo 167' || tipoLower === 'costura anexo 168' || tipoLower === 'costura anexo 169' || tipoLower === 'costura anexo 170' || tipoLower === 'costura anexo 171' || tipoLower === 'costura anexo 172' || tipoLower === 'costura anexo 173' || tipoLower === 'costura anexo 174' || tipoLower === 'costura anexo 175' || tipoLower === 'costura anexo 176' || tipoLower === 'costura anexo 177' || tipoLower === 'costura anexo 178' || tipoLower === 'costura anexo 179' || tipoLower === 'costura anexo 180' || tipoLower === 'costura anexo 181' || tipoLower === 'costura anexo 182' || tipoLower === 'costura anexo 183' || tipoLower === 'costura anexo 184' || tipoLower === 'costura anexo 185' || tipoLower === 'costura anexo 186' || tipoLower === 'costura anexo 187' || tipoLower === 'costura anexo 188' || tipoLower === 'costura anexo 189' || tipoLower === 'costura anexo 190' || tipoLower === 'costura anexo 191' || tipoLower === 'costura anexo 192' || tipoLower === 'costura anexo 193' || tipoLower === 'costura anexo 194' || tipoLower === 'costura anexo 195' || tipoLower === 'costura anexo 196' || tipoLower === 'costura anexo 197' || tipoLower === 'costura anexo 198' || tipoLower === 'costura anexo 199' || tipoLower === 'costura anexo 200' || tipoLower === 'costura anexo 201' || tipoLower === 'costura anexo 202' || tipoLower === 'costura anexo 203' || tipoLower === 'costura anexo 204' || tipoLower === 'costura anexo 205' || tipoLower === 'costura anexo 206' || tipoLower === 'costura anexo 207' || tipoLower === 'costura anexo 208' || tipoLower === 'costura anexo 209' || tipoLower === 'costura anexo 210' || tipoLower === 'costura anexo 211' || tipoLower === 'costura anexo 212' || tipoLower === 'costura anexo 213' || tipoLower === 'costura anexo 214' || tipoLower === 'costura anexo 215' || tipoLower === 'costura anexo 216' || tipoLower === 'costura anexo 217' || tipoLower === 'costura anexo 218' || tipoLower === 'costura anexo 219' || tipoLower === 'costura anexo 220' || tipoLower === 'costura anexo 221' || tipoLower === 'costura anexo 222' || tipoLower === 'costura anexo 223' || tipoLower === 'costura anexo 224' || tipoLower === 'costura anexo 225' || tipoLower === 'costura anexo 226' || tipoLower === 'costura anexo 227' || tipoLower === 'costura anexo 228' || tipoLower === 'costura anexo 229' || tipoLower === 'costura anexo 230' || tipoLower === 'costura anexo 231' || tipoLower === 'costura anexo 232' || tipoLower === 'costura anexo 233' || tipoLower === 'costura anexo 234' || tipoLower === 'costura anexo 235' || tipoLower === 'costura anexo 236' || tipoLower === 'costura anexo 237' || tipoLower === 'costura anexo 238' || tipoLower === 'costura anexo 239' || tipoLower === 'costura anexo 240' || tipoLower === 'costura anexo 241' || tipoLower === 'costura anexo 242' || tipoLower === 'costura anexo 243' || tipoLower === 'costura anexo 244' || tipoLower === 'costura anexo 245' || tipoLower === 'costura anexo 246' || tipoLower === 'costura anexo 247' || tipoLower === 'costura anexo 248' || tipoLower === 'costura anexo 249' || tipoLower === 'costura anexo 250' || tipoLower === 'costura anexo 251' || tipoLower === 'costura anexo 252' || tipoLower === 'costura anexo 253' || tipoLower === 'costura anexo 254' || tipoLower === 'costura anexo 255' || tipoLower === 'costura anexo 256' || tipoLower === 'costura anexo 257' || tipoLower === 'costura anexo 258' || tipoLower === 'costura anexo 259' || tipoLower === 'costura anexo 260' || tipoLower === 'costura anexo 261' || tipoLower === 'costura anexo 262' || tipoLower === 'costura anexo 263' || tipoLower === 'costura anexo 264' || tipoLower === 'costura anexo 265' || tipoLower === 'costura anexo 266' || tipoLower === 'costura anexo 267' || tipoLower === 'costura anexo 268' || tipoLower === 'costura anexo 269' || tipoLower === 'costura anexo 270' || tipoLower === 'costura anexo 271' || tipoLower === 'costura anexo 272' || tipoLower === 'costura anexo 273' || tipoLower === 'costura anexo 274' || tipoLower === 'costura anexo 275' || tipoLower === 'costura anexo 276' || tipoLower === 'costura anexo 277' || tipoLower === 'costura anexo 278' || tipoLower === 'costura anexo 279' || tipoLower === 'costura anexo 280' || tipoLower === 'costura anexo 281' || tipoLower === 'costura anexo 282' || tipoLower === 'costura anexo 283' || tipoLower === 'costura anexo 284' || tipoLower === 'costura anexo 285' || tipoLower === 'costura anexo 286' || tipoLower === 'costura anexo 287' || tipoLower === 'costura anexo 288' || tipoLower === 'costura anexo 289' || tipoLower === 'costura anexo 290' || tipoLower === 'costura anexo 291' || tipoLower === 'costura anexo 292' || tipoLower === 'costura anexo 293' || tipoLower === 'costura anexo 294' || tipoLower === 'costura anexo 295' || tipoLower === 'costura anexo 296' || tipoLower === 'costura anexo 297' || tipoLower === 'costura anexo 298' || tipoLower === 'costura anexo 299' || tipoLower === 'costura anexo 300' || tipoLower === 'costura anexo 301' || tipoLower === 'costura anexo 302' || tipoLower === 'costura anexo 303' || tipoLower === 'costura anexo 304' || tipoLower === 'costura anexo 305' || tipoLower === 'costura anexo 306' || tipoLower === 'costura anexo 307' || tipoLower === 'costura anexo 308' || tipoLower === 'costura anexo 309' || tipoLower === 'costura anexo 310' || tipoLower === 'costura anexo 311' || tipoLower === 'costura anexo 312' || tipoLower === 'costura anexo 313' || tipoLower === 'costura anexo 314' || tipoLower === 'costura anexo 315' || tipoLower === 'costura anexo 316' || tipoLower === 'costura anexo 317' || tipoLower === 'costura anexo 318' || tipoLower === 'costura anexo 319' || tipoLower === 'costura anexo 320' || tipoLower === 'costura anexo 321' || tipoLower === 'costura anexo 322' || tipoLower === 'costura anexo 323' || tipoLower === 'costura anexo 324' || tipoLower === 'costura anexo 325' || tipoLower === 'costura anexo 326' || tipoLower === 'costura anexo 327' || tipoLower === 'costura anexo 328' || tipoLower === 'costura anexo 329' || tipoLower === 'costura anexo 330' || tipoLower === 'costura anexo 331' || tipoLower === 'costura anexo 332' || tipoLower === 'costura anexo 333' || tipoLower === 'costura anexo 334' || tipoLower === 'costura anexo 335' || tipoLower === 'costura anexo 336' || tipoLower === 'costura anexo 337' || tipoLower === 'costura anexo 338' || tipoLower === 'costura anexo 339' || tipoLower === 'costura anexo 340' || tipoLower === 'costura anexo 341' || tipoLower === 'costura anexo 342' || tipoLower === 'costura anexo 343' || tipoLower === 'costura anexo 344' || tipoLower === 'costura anexo 345' || tipoLower === 'costura anexo 346' || tipoLower === 'costura anexo 347' || tipoLower === 'costura anexo 348' || tipoLower === 'costura anexo 349' || tipoLower === 'costura anexo 350' || tipoLower === 'costura anexo 351' || tipoLower === 'costura anexo 352' || tipoLower === 'costura anexo 353' || tipoLower === 'costura anexo 354' || tipoLower === 'costura anexo 355' || tipoLower === 'costura anexo 356' || tipoLower === 'costura anexo 357' || tipoLower === 'costura anexo 358' || tipoLower === 'costura anexo 359' || tipoLower === 'costura anexo 360' || tipoLower === 'costura anexo 361' || tipoLower === 'costura anexo 362' || tipoLower === 'costura anexo 363' || tipoLower === 'costura anexo 364' || tipoLower === 'costura anexo 365' || tipoLower === 'costura anexo 366' || tipoLower === 'costura anexo 367' || tipoLower === 'costura anexo 368' || tipoLower === 'costura anexo 369' || tipoLower === 'costura anexo 370' || tipoLower === 'costura anexo 371' || tipoLower === 'costura anexo 372' || tipoLower === 'costura anexo 373' || tipoLower === 'costura anexo 374' || tipoLower === 'costura anexo 375' || tipoLower === 'costura anexo 376' || tipoLower === 'costura anexo 377' || tipoLower === 'costura anexo 378' || tipoLower === 'costura anexo 379' || tipoLower === 'costura anexo 380' || tipoLower === 'costura anexo 381' || tipoLower === 'costura anexo 382' || tipoLower === 'costura anexo 383' || tipoLower === 'costura anexo 384' || tipoLower === 'costura anexo 385' || tipoLower === 'costura anexo 386' || tipoLower === 'costura anexo 387' || tipoLower === 'costura anexo 388' || tipoLower === 'costura anexo 389' || tipoLower === 'costura anexo 390' || tipoLower === 'costura anexo 391' || tipoLower === 'costura anexo 392' || tipoLower === 'costura anexo 393' || tipoLower === 'costura anexo 394' || tipoLower === 'costura anexo 395' || tipoLower === 'costura anexo 396' || tipoLower === 'costura anexo 397' || tipoLower === 'costura anexo 398' || tipoLower === 'costura anexo 399' || tipoLower === 'costura anexo 400' || tipoLower === 'costura anexo 401' || tipoLower === 'costura anexo 402' || tipoLower === 'costura anexo 403' || tipoLower === 'costura anexo 404' || tipoLower === 'costura anexo 405' || tipoLower === 'costura anexo 406' || tipoLower === 'costura anexo 407' || tipoLower === 'costura anexo 408' || tipoLower === 'costura anexo 409' || tipoLower === 'costura anexo 410' || tipoLower === 'costura anexo 411' || tipoLower === 'costura anexo 412' || tipoLower === 'costura anexo 413' || tipoLower === 'costura anexo 414' || tipoLower === 'costura anexo 415' || tipoLower === 'costura anexo 416' || tipoLower === 'costura anexo 417' || tipoLower === 'costura anexo 418' || tipoLower === 'costura anexo 419' || tipoLower === 'costura anexo 420' || tipoLower === 'costura anexo 421' || tipoLower === 'costura anexo 422' || tipoLower === 'costura anexo 423' || tipoLower === 'costura anexo 424' || tipoLower === 'costura anexo 425' || tipoLower === 'costura anexo 426' || tipoLower === 'costura anexo 427' || tipoLower === 'costura anexo 428' || tipoLower === 'costura anexo 429' || tipoLower === 'costura anexo 430' || tipoLower === 'costura anexo 431' || tipoLower === 'costura anexo 432' || tipoLower === 'costura anexo 433' || tipoLower === 'costura anexo 434' || tipoLower === 'costura anexo 435' || tipoLower === 'costura anexo 436' || tipoLower === 'costura anexo 437' || tipoLower === 'costura anexo 438' || tipoLower === 'costura anexo 439' || tipoLower === 'costura anexo 440' || tipoLower === 'costura anexo 441' || tipoLower === 'costura anexo 442' || tipoLower === 'costura anexo 443' || tipoLower === 'costura anexo 444' || tipoLower === 'costura anexo 445' || tipoLower === 'costura anexo 446' || tipoLower === 'costura anexo 447' || tipoLower === 'costura anexo 448' || tipoLower === 'costura anexo 449' || tipoLower === 'costura anexo 450' || tipoLower === 'costura anexo 451' || tipoLower === 'costura anexo 452' || tipoLower === 'costura anexo 453' || tipoLower === 'costura anexo 454' || tipoLower === 'costura anexo 455' || tipoLower === 'costura anexo 456' || tipoLower === 'costura anexo 457' || tipoLower === 'costura anexo 458' || tipoLower === 'costura anexo 459' || tipoLower === 'costura anexo 460' || tipoLower === 'costura anexo 461' || tipoLower === 'costura anexo 462' || tipoLower === 'costura anexo 463' || tipoLower === 'costura anexo 464' || tipoLower === 'costura anexo 465' || tipoLower === 'costura anexo 466' || tipoLower === 'costura anexo 467' || tipoLower === 'costura anexo 468' || tipoLower === 'costura anexo 469' || tipoLower === 'costura anexo 470' || tipoLower === 'costura anexo 471' || tipoLower === 'costura anexo 472' || tipoLower === 'costura anexo 473' || tipoLower === 'costura anexo 474' || tipoLower === 'costura anexo 475' || tipoLower === 'costura anexo 476' || tipoLower === 'costura anexo 477' || tipoLower === 'costura anexo 478' || tipoLower === 'costura anexo 479' || tipoLower === 'costura anexo 480' || tipoLower === 'costura anexo 481' || tipoLower === 'costura anexo 482' || tipoLower === 'costura anexo 483' || tipoLower === 'costura anexo 484' || tipoLower === 'costura anexo 485' || tipoLower === 'costura anexo 486' || tipoLower === 'costura anexo 487' || tipoLower === 'costura anexo 488' || tipoLower === 'costura anexo 489' || tipoLower === 'costura anexo 490' || tipoLower === 'costura anexo 491' || tipoLower === 'costura anexo 492' || tipoLower === 'costura anexo 493' || tipoLower === 'costura anexo 494' || tipoLower === 'costura anexo 495' || tipoLower === 'costura anexo 496' || tipoLower === 'costura anexo 497' || tipoLower === 'costura anexo 498' || tipoLower === 'costura anexo 499' || tipoLower === 'costura anexo 500' || tipoLower === 'costura anexo 501' || tipoLower === 'costura anexo 502' || tipoLower === 'costura anexo 503' || tipoLower === 'costura anexo 504' || tipoLower === 'costura anexo 505' || tipoLower === 'costura anexo 506' || tipoLower === 'costura anexo 507' || tipoLower === 'costura anexo 508' || tipoLower === 'costura anexo 509' || tipoLower === 'costura anexo 510' || tipoLower === 'costura anexo 511' || tipoLower === 'costura anexo 512' || tipoLower === 'costura anexo 513' || tipoLower === 'costura anexo 514' || tipoLower === 'costura anexo 515' || tipoLower === 'costura anexo 516' || tipoLower === 'costura anexo 517' || tipoLower === 'costura anexo 518' || tipoLower === 'costura anexo 519' || tipoLower === 'costura anexo 520' || tipoLower === 'costura anexo 521' || tipoLower === 'costura anexo 522' || tipoLower === 'costura anexo 523' || tipoLower === 'costura anexo 524' || tipoLower === 'costura anexo 525' || tipoLower === 'costura anexo 526' || tipoLower === 'costura anexo 527' || tipoLower === 'costura anexo 528' || tipoLower === 'costura anexo 529' || tipoLower === 'costura anexo 530' || tipoLower === 'costura anexo 531' || tipoLower === 'costura anexo 532' || tipoLower === 'costura anexo 533' || tipoLower === 'costura anexo 534' || tipoLower === 'costura anexo 535' || tipoLower === 'costura anexo 536' || tipoLower === 'costura anexo 537' || tipoLower === 'costura anexo 538' || tipoLower === 'costura anexo 539' || tipoLower === 'costura anexo 540' || tipoLower === 'costura anexo 541' || tipoLower === 'costura anexo 542' || tipoLower === 'costura anexo 543' || tipoLower === 'costura anexo 544' || tipoLower === 'costura anexo 545' || tipoLower === 'costura anexo 546' || tipoLower === 'costura anexo 547' || tipoLower === 'costura anexo 548' || tipoLower === 'costura anexo 549' || tipoLower === 'costura anexo 550' || tipoLower === 'costura anexo 551' || tipoLower === 'costura anexo 552' || tipoLower === 'costura anexo 553' || tipoLower === 'costura anexo 554' || tipoLower === 'costura anexo 555' || tipoLower === 'costura anexo 556' || tipoLower === 'costura anexo 557' || tipoLower === 'costura anexo 558' || tipoLower === 'costura anexo 559' || tipoLower === 'costura anexo 560' || tipoLower === 'costura anexo 561' || tipoLower === 'costura anexo 562' || tipoLower === 'costura anexo 563' || tipoLower === 'costura anexo 564' || tipoLower === 'costura anexo 565' || tipoLower === 'costura anexo 566' || tipoLower === 'costura anexo 567' || tipoLower === 'costura anexo 568' || tipoLower === 'costura anexo 569' || tipoLower === 'costura anexo 570' || tipoLower === 'costura anexo 571' || tipoLower === 'costura anexo 572' || tipoLower === 'costura anexo 573' || tipoLower === 'costura anexo 574' || tipoLower === 'costura anexo 575' || tipoLower === 'costura anexo 576' || tipoLower === 'costura anexo 577' || tipoLower === 'costura anexo 578' || tipoLower === 'costura anexo 579' || tipoLower === 'costura anexo 580' || tipoLower === 'costura anexo 581' || tipoLower === 'costura anexo 582' || tipoLower === 'costura anexo 583' || tipoLower === 'costura anexo 584' || tipoLower === 'costura anexo 585' || tipoLower === 'costura anexo 586' || tipoLower === 'costura anexo 587' || tipoLower === 'costura anexo 588' || tipoLower === 'costura anexo 589' || tipoLower === 'costura anexo 590' || tipoLower === 'costura anexo 591' || tipoLower === 'costura anexo 592' || tipoLower === 'costura anexo 593' || tipoLower === 'costura anexo 594' || tipoLower === 'costura anexo 595' || tipoLower === 'costura anexo 596' || tipoLower === 'costura anexo 597' || tipoLower === 'costura anexo 598' || tipoLower === 'costura anexo 599' || tipoLower === 'costura anexo 600' || tipoLower === 'costura anexo 601' || tipoLower === 'costura anexo 602' || tipoLower === 'costura anexo 603' || tipoLower === 'costura anexo 604' || tipoLower === 'costura anexo 605' || tipoLower === 'costura anexo 606' || tipoLower === 'costura anexo 607' || tipoLower === 'costura anexo 608' || tipoLower === 'costura anexo 609' || tipoLower === 'costura anexo 610' || tipoLower === 'costura anexo 611' || tipoLower === 'costura anexo 612' || tipoLower === 'costura anexo 613' || tipoLower === 'costura anexo 614' || tipoLower === 'costura anexo 615' || tipoLower === 'costura anexo 616' || tipoLower === 'costura anexo 617' || tipoLower === 'costura anexo 618' || tipoLower === 'costura anexo 619' || tipoLower === 'costura anexo 620' || tipoLower === 'costura anexo 621' || tipoLower === 'costura anexo 622' || tipoLower === 'costura anexo 623' || tipoLower === 'costura anexo 624' || tipoLower === 'costura anexo 625' || tipoLower === 'costura anexo 626' || tipoLower === 'costura anexo 627' || tipoLower === 'costura anexo 628' || tipoLower === 'costura anexo 629' || tipoLower === 'costura anexo 630' || tipoLower === 'costura anexo 631' || tipoLower === 'costura anexo 632' || tipoLower === 'costura anexo 633' || tipoLower === 'costura anexo 634' || tipoLower === 'costura anexo 635' || tipoLower === 'costura anexo 636' || tipoLower === 'costura anexo 637' || tipoLower === 'costura anexo 638' || tipoLower === 'costura anexo 639' || tipoLower === 'costura anexo 640' || tipoLower === 'costura anexo 641' || tipoLower === 'costura anexo 642' || tipoLower === 'costura anexo 643' || tipoLower === 'costura anexo 644' || tipoLower === 'costura anexo 645' || tipoLower === 'costura anexo 646' || tipoLower === 'costura anexo 647' || tipoLower === 'costura anexo 648' || tipoLower === 'costura anexo 649' || tipoLower === 'costura anexo 650' || tipoLower === 'costura anexo 651' || tipoLower === 'costura anexo 652' || tipoLower === 'costura anexo 653' || tipoLower === 'costura anexo 654' || tipoLower === 'costura anexo 655' || tipoLower === 'costura anexo 656' || tipoLower === 'costura anexo 657' || tipoLower === 'costura anexo 658' || tipoLower === 'costura anexo 659' || tipoLower === 'costura anexo 660' || tipoLower === 'costura anexo 661' || tipoLower === 'costura anexo 662' || tipoLower === 'costura anexo 663' || tipoLower === 'costura anexo 664' || tipoLower === 'costura anexo 665' || tipoLower === 'costura anexo 666' || tipoLower === 'costura anexo 667' || tipoLower === 'costura anexo 668' || tipoLower === 'costura anexo 669' || tipoLower === 'costura anexo 670' || tipoLower === 'costura anexo 671' || tipoLower === 'costura anexo 672' || tipoLower === 'costura anexo 673' || tipoLower === 'costura anexo 674' || tipoLower === 'costura anexo 675' || tipoLower === 'costura anexo 676' || tipoLower === 'costura anexo 677' || tipoLower === 'costura anexo 678' || tipoLower === 'costura anexo 679' || tipoLower === 'costura anexo 680' || tipoLower === 'costura anexo 681' || tipoLower === 'costura anexo 682' || tipoLower === 'costura anexo 683' || tipoLower === 'costura anexo 684' || tipoLower === 'costura anexo 685' || tipoLower === 'costura anexo 686' || tipoLower === 'costura anexo 687' || tipoLower === 'costura anexo 688' || tipoLower === 'costura anexo 689' || tipoLower === 'costura anexo 690' || tipoLower === 'costura anexo 691' || tipoLower === 'costura anexo 692' || tipoLower === 'costura anexo 693' || tipoLower === 'costura anexo 694' || tipoLower === 'costura anexo 695' || tipoLower === 'costura anexo 696' || tipoLower === 'costura anexo 697' || tipoLower === 'costura anexo 698' || tipoLower === 'costura anexo 699' || tipoLower === 'costura anexo 700' || tipoLower === 'costura anexo 701' || tipoLower === 'costura anexo 702' || tipoLower === 'costura anexo 703' || tipoLower === 'costura anexo 704' || tipoLower === 'costura anexo 705' || tipoLower === 'costura anexo 706' || tipoLower === 'costura anexo 707' || tipoLower === 'costura anexo 708' || tipoLower === 'costura anexo 709' || tipoLower === 'costura anexo 710' || tipoLower === 'costura anexo 711' || tipoLower === 'costura anexo 712' || tipoLower === 'costura anexo 713' || tipoLower === 'costura anexo 714' || tipoLower === 'costura anexo 715' || tipoLower === 'costura anexo 716' || tipoLower === 'costura anexo 717' || tipoLower === 'costura anexo 718' || tipoLower === 'costura anexo 719' || tipoLower === 'costura anexo 720' || tipoLower === 'costura anexo 721' || tipoLower === 'costura anexo 722' || tipoLower === 'costura anexo 723' || tipoLower === 'costura anexo 724' || tipoLower === 'costura anexo 725' || tipoLower === 'costura anexo 726' || tipoLower === 'costura anexo 727' || tipoLower === 'costura anexo 728' || tipoLower === 'costura anexo 729' || tipoLower === 'costura anexo 730' || tipoLower === 'costura anexo 731' || tipoLower === 'costura anexo 732' || tipoLower === 'costura anexo 733' || tipoLower === 'costura anexo 734' || tipoLower === 'costura anexo 735' || tipoLower === 'costura anexo 736' || tipoLower === 'costura anexo 737' || tipoLower === 'costura anexo 738' || tipoLower === 'costura anexo 739' || tipoLower === 'costura anexo 740' || tipoLower === 'costura anexo 741' || tipoLower === 'costura anexo 742' || tipoLower === 'costura anexo 743' || tipoLower === 'costura anexo 744' || tipoLower === 'costura anexo 745' || tipoLower === 'costura anexo 746' || tipoLower === 'costura anexo 747' || tipoLower === 'costura anexo 748' || tipoLower === 'costura anexo 749' || tipoLower === 'costura anexo 750' || tipoLower === 'costura anexo 751' || tipoLower === 'costura anexo 752' || tipoLower === 'costura anexo 753' || tipoLower === 'costura anexo 754' || tipoLower === 'costura anexo 755' || tipoLower === 'costura anexo 756' || tipoLower === 'costura anexo 757' || tipoLower === 'costura anexo 758' || tipoLower === 'costura anexo 759' || tipoLower === 'costura anexo 760' || tipoLower === 'costura anexo 761' || tipoLower === 'costura anexo 762' || tipoLower === 'costura anexo 763' || tipoLower === 'costura anexo 764' || tipoLower === 'costura anexo 765' || tipoLower === 'costura anexo 766' || tipoLower === 'costura anexo 767' || tipoLower === 'costura anexo 768' || tipoLower === 'costura anexo 769' || tipoLower === 'costura anexo 770' || tipoLower === 'costura anexo 771' || tipoLower === 'costura anexo 772' || tipoLower === 'costura anexo 773' || tipoLower === 'costura anexo 774' || tipoLower === 'costura anexo 775' || tipoLower === 'costura anexo 776' || tipoLower === 'costura anexo 777' || tipoLower === 'costura anexo 778' || tipoLower === 'costura anexo 779' || tipoLower === 'costura anexo 780' || tipoLower === 'costura anexo 781' || tipoLower === 'costura anexo 782' || tipoLower === 'costura anexo 783' || tipoLower === 'costura anexo 784' || tipoLower === 'costura anexo 785' || tipoLower === 'costura anexo 786' || tipoLower === 'costura anexo 787' || tipoLower === 'costura anexo 788' || tipoLower === 'costura anexo 789' || tipoLower === 'costura anexo 790' || tipoLower === 'costura anexo 791' || tipoLower === 'costura anexo 792' || tipoLower === 'costura anexo 793' || tipoLower === 'costura anexo 794' || tipoLower === 'costura anexo 795' || tipoLower === 'costura anexo 796' || tipoLower === 'costura anexo 797' || tipoLower === 'costura anexo 798' || tipoLower === 'costura anexo 799' || tipoLower === 'costura anexo 800' || tipoLower === 'costura anexo 801' || tipoLower === 'costura anexo 802' || tipoLower === 'costura anexo 803' || tipoLower === 'costura anexo 804' || tipoLower === 'costura anexo 805' || tipoLower === 'costura anexo 806' || tipoLower === 'costura anexo 807' || tipoLower === 'costura anexo 808' || tipoLower === 'costura anexo 809' || tipoLower === 'costura anexo 810' || tipoLower === 'costura anexo 811' || tipoLower === 'costura anexo 812' || tipoLower === 'costura anexo 813' || tipoLower === 'costura anexo 814' || tipoLower === 'costura anexo 815' || tipoLower === 'costura anexo 816' || tipoLower === 'costura anexo 817' || tipoLower === 'costura anexo 818' || tipoLower === 'costura anexo 819' || tipoLower === 'costura anexo 820' || tipoLower === 'costura anexo 821' || tipoLower === 'costura anexo 822' || tipoLower === 'costura anexo 823' || tipoLower === 'costura anexo 824' || tipoLower === 'costura anexo 825' || tipoLower === 'costura anexo 826' || tipoLower === 'costura anexo 827' || tipoLower === 'costura anexo 828' || tipoLower === 'costura anexo 829' || tipoLower === 'costura anexo 830' || tipoLower === 'costura anexo 831' || tipoLower === 'costura anexo 832' || tipoLower === 'costura anexo 833' || tipoLower === 'costura anexo 834' || tipoLower === 'costura anexo 835' || tipoLower === 'costura anexo 836' || tipoLower === 'costura anexo 837' || tipoLower === 'costura anexo 838' || tipoLower === 'costura anexo 839' || tipoLower === 'costura anexo 840' || tipoLower === 'costura anexo 841' || tipoLower === 'costura anexo 842' || tipoLower === 'costura anexo 843' || tipoLower === 'costura anexo 844' || tipoLower === 'costura anexo 845' || tipoLower === 'costura anexo 846' || tipoLower === 'costura anexo 847' || tipoLower === 'costura anexo 848' || tipoLower === 'costura anexo 849' || tipoLower === 'costura anexo 850' || tipoLower === 'costura anexo 851' || tipoLower === 'costura anexo 852' || tipoLower === 'costura anexo 853' || tipoLower === 'costura anexo 854' || tipoLower === 'costura anexo 855' || tipoLower === 'costura anexo 856' || tipoLower === 'costura anexo 857' || tipoLower === 'costura anexo 858' || tipoLower === 'costura anexo 859' || tipoLower === 'costura anexo 860' || tipoLower === 'costura anexo 861' || tipoLower === 'costura anexo 862' || tipoLower === 'costura anexo 863' || tipoLower === 'costura anexo 864' || tipoLower === 'costura anexo 865' || tipoLower === 'costura anexo 866' || tipoLower === 'costura anexo 867' || tipoLower === 'costura anexo 868' || tipoLower === 'costura anexo 869' || tipoLower === 'costura anexo 870' || tipoLower === 'costura anexo 871' || tipoLower === 'costura anexo 872' || tipoLower === 'costura anexo 873' || tipoLower === 'costura anexo 874' || tipoLower === 'costura anexo 875' || tipoLower === 'costura anexo 876' || tipoLower === 'costura anexo 877' || tipoLower === 'costura anexo 878' || tipoLower === 'costura anexo 879' || tipoLower === 'costura anexo 880' || tipoLower === 'costura anexo 881' || tipoLower === 'costura anexo 882' || tipoLower === 'costura anexo 883' || tipoLower === 'costura anexo 884' || tipoLower === 'costura anexo 885' || tipoLower === 'costura anexo 886' || tipoLower === 'costura anexo 887' || tipoLower === 'costura anexo 888' || tipoLower === 'costura anexo 889' || tipoLower === 'costura anexo 890' || tipoLower === 'costura anexo 891' || tipoLower === 'costura anexo 892' || tipoLower === 'costura anexo 893' || tipoLower === 'costura anexo 894' || tipoLower === 'costura anexo 895' || tipoLower === 'costura anexo 896' || tipoLower === 'costura anexo 897' || tipoLower === 'costura anexo 898' || tipoLower === 'costura anexo 899' || tipoLower === 'costura anexo 900' || tipoLower === 'costura anexo 901' || tipoLower === 'costura anexo 902' || tipoLower === 'costura anexo 903' || tipoLower === 'costura anexo 904' || tipoLower === 'costura anexo 905' || tipoLower === 'costura anexo 906' || tipoLower === 'costura anexo 907' || tipoLower === 'costura anexo 908' || tipoLower === 'costura anexo 909' || tipoLower === 'costura anexo 910' || tipoLower === 'costura anexo 911' || tipoLower === 'costura anexo 912' || tipoLower === 'costura anexo 913' || tipoLower === 'costura anexo 914' || tipoLower === 'costura anexo 915' || tipoLower === 'costura anexo 916' || tipoLower === 'costura anexo 917' || tipoLower === 'costura anexo 918' || tipoLower === 'costura anexo 919' || tipoLower === 'costura anexo 920' || tipoLower === 'costura anexo 921' || tipoLower === 'costura anexo 922' || tipoLower === 'costura anexo 923' || tipoLower === 'costura anexo 924' || tipoLower === 'costura anexo 925' || tipoLower === 'costura anexo 926' || tipoLower === 'costura anexo 927' || tipoLower === 'costura anexo 928' || tipoLower === 'costura anexo 929' || tipoLower === 'costura anexo 930' || tipoLower === 'costura anexo 931' || tipoLower === 'costura anexo 932' || tipoLower === 'costura anexo 933' || tipoLower === 'costura anexo 934' || tipoLower === 'costura anexo 935' || tipoLower === 'costura anexo 936' || tipoLower === 'costura anexo 937' || tipoLower === 'costura anexo 938' || tipoLower === 'costura anexo 939' || tipoLower === 'costura anexo 940' || tipoLower === 'costura anexo 941' || tipoLower === 'costura anexo 942' || tipoLower === 'costura anexo 943' || tipoLower === 'costura anexo 944' || tipoLower === 'costura anexo 945' || tipoLower === 'costura anexo 946' || tipoLower === 'costura anexo 947' || tipoLower === 'costura anexo 948' || tipoLower === 'costura anexo 949' || tipoLower === 'costura anexo 950' || tipoLower === 'costura anexo 951' || tipoLower === 'costura anexo 952' || tipoLower === 'costura anexo 953' || tipoLower === 'costura anexo 954' || tipoLower === 'costura anexo 955' || tipoLower === 'costura anexo 956' || tipoLower === 'costura anexo 957' || tipoLower === 'costura anexo 958' || tipoLower === 'costura anexo 959' || tipoLower === 'costura anexo 960' || tipoLower === 'costura anexo 961' || tipoLower === 'costura anexo 962' || tipoLower === 'costura anexo 963' || tipoLower === 'costura anexo 964' || tipoLower === 'costura anexo 965' || tipoLower === 'costura anexo 966' || tipoLower === 'costura anexo 967' || tipoLower === 'costura anexo 968' || tipoLower === 'costura anexo 969' || tipoLower === 'costura anexo 970' || tipoLower === 'costura anexo 971' || tipoLower === 'costura anexo 972' || tipoLower === 'costura anexo 973' || tipoLower === 'costura anexo 974' || tipoLower === 'costura anexo 975' || tipoLower === 'costura anexo 976' || tipoLower === 'costura anexo 977' || tipoLower === 'costura anexo 978' || tipoLower === 'costura anexo 979' || tipoLower === 'costura anexo 980' || tipoLower === 'costura anexo 981' || tipoLower === 'costura anexo 982' || tipoLower === 'costura anexo 983' || tipoLower === 'costura anexo 984' || tipoLower === 'costura anexo 985' || tipoLower === 'costura anexo 986' || tipoLower === 'costura anexo 987' || tipoLower === 'costura anexo 988' || tipoLower === 'costura anexo 989' || tipoLower === 'costura anexo 990' || tipoLower === 'costura anexo 991' || tipoLower === 'costura anexo 992' || tipoLower === 'costura anexo 993' || tipoLower === 'costura anexo 994' || tipoLower === 'costura anexo 995' || tipoLower === 'costura anexo 996' || tipoLower === 'costura anexo 997' || tipoLower === 'costura anexo 998' || tipoLower === 'costura anexo 999' || tipoLower === 'costura anexo 1000';
                if (esCostura) {
                    const candidatos = ['costura-bodega', 'costura'];
                    for (const candidato of candidatos) {
                        const idx = ReceiptBuilder.encontrarReceibo(recibos, candidato);
                        if (idx !== -1) {
                            console.warn('[PedidosRecibosModule.abrirReciboParcial] Fallback tipoRecibo:', {
                                solicitado: tipoRecibo,
                                usando: candidato
                            });
                            reciboIndice = idx;
                            tipoRecibo = candidato;
                            break;
                        }
                    }
                }
            }

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Renderizar
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {

            alert('Error al cargar el recibo: ' + err.message);
            this.modalManager.cerrarModal();
        }
    }

    /**
     * Abre un recibo parcial (anexo) reutilizando la misma pipeline de renderizado.
     * Sigue el mismo flujo que abrirRecibo() pero inyecta las tallas del parcial
     * en el objeto recibo ANTES de renderizar.
     *
     * @param {number} pedidoId    - ID del pedido de producción
     * @param {number} prendaId    - ID de la prenda
     * @param {string} tipoRecibo  - Tipo del proceso base (ej: "BORDADO")
     * @param {number} parcialId   - ID del recibo parcial (pedidos_parciales)
     * @param {string} nombreAnexo - Nombre para mostrar (ej: "BORDADO ANEXO 2")
     */
    async abrirReciboParcial(pedidoId, prendaId, tipoRecibo, parcialId, nombreAnexo) {
        // Validaciones
        if (typeof tipoRecibo !== 'string') {
            alert('Error: tipo de recibo debe ser texto');
            return;
        }
        if (typeof prendaId !== 'number') {
            alert('Error: ID de prenda debe ser número');
            return;
        }
        if (!parcialId) {
            alert('Error: ID de recibo parcial requerido');
            return;
        }

        try {
            // Resetear estado
            GalleryManager.resetGaleria(this.modalManager);
            this.modalManager.limpiarEstado();
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex: null
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar endpoint de datos del pedido (misma lógica que abrirRecibo)
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] Cargando datos en paralelo:', {
                endpoint,
                parcialEndpoint: `/api/recibos-parciales/${parcialId}`
            });

            // Fetch en paralelo: datos del pedido + datos del parcial
            const [pedidoResponse, parcialResponse] = await Promise.all([
                fetch(endpoint),
                fetch(`/api/recibos-parciales/${parcialId}`)
            ]);

            if (!pedidoResponse.ok) throw new Error(`HTTP ${pedidoResponse.status} al cargar pedido`);
            if (!parcialResponse.ok) throw new Error(`HTTP ${parcialResponse.status} al cargar parcial`);

            let datos = await pedidoResponse.json();
            const parcialResult = await parcialResponse.json();

            if (!parcialResult.success) {
                throw new Error(parcialResult.message || 'Error al cargar recibo parcial');
            }

            // Desempaquetar datos del pedido
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            // Consecutivo real del ANEXO (viene del recibo parcial)
            const parcialData = parcialResult.data?.parcial || parcialResult.data || null;
            const consecutivoAnexo = parcialData?.consecutivo_actual ?? parcialData?.numero_recibo ?? null;

            // Para anexos: la fecha del recibo debe venir de pedidos_parciales.created_at
            // El renderer usa datosPedido.fecha para pintar los cuadros de fecha.
            if (parcialData && parcialData.created_at) {
                // Normalizar a solo fecha para evitar desfases por zona horaria (ej: 23:00 -> día siguiente)
                const createdAtStr = String(parcialData.created_at);
                // Soportar formatos: "YYYY-MM-DD HH:MM:SS" o ISO "YYYY-MM-DDTHH:MM:SS..."
                const soloFecha = createdAtStr.includes('T')
                    ? createdAtStr.split('T')[0]
                    : createdAtStr.substring(0, 10);

                datos.fecha = soloFecha;
            }

            this.modalManager.setState({ datosCompletos: datos });

            // Validar prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            if (!prendaData) {
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }

            // Construir recibos normalmente
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            let reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);

            // Fallback: anexos de COSTURA se guardan como "COSTURA" pero el recibo base
            // en prendas de bodega se construye como "costura-bodega".
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega';
                if (esCostura) {
                    const candidatos = ['costura-bodega', 'costura'];
                    for (const candidato of candidatos) {
                        const idx = ReceiptBuilder.encontrarReceibo(recibos, candidato);
                        if (idx !== -1) {
                            console.warn('[PedidosRecibosModule.abrirReciboParcial] Fallback tipoRecibo:', {
                                solicitado: tipoRecibo,
                                usando: candidato
                            });
                            reciboIndice = idx;
                            tipoRecibo = candidato;
                            break;
                        }
                    }
                }
            }

            // Caso especial: en /recibos-costura ReceiptBuilder puede excluir el recibo base
            // (ej: costura-bodega cuando prenda.de_bodega == 1). Para anexos de costura
            // necesitamos un recibo objetivo para inyectar tallas y renderizar.
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega';
                const esVistaRecibosCostura = window.location.pathname.includes('/recibos-costura');
                if (esCostura && esVistaRecibosCostura) {
                    const tipoSintetico = prendaData?.de_bodega == 1 ? 'costura-bodega' : 'costura';
                    console.warn('[PedidosRecibosModule.abrirReciboParcial] Recibo base no encontrado; creando recibo sintético para renderizar anexo', {
                        solicitado: tipoRecibo,
                        tipo_sintetico: tipoSintetico,
                        de_bodega: prendaData?.de_bodega
                    });

                    recibos.unshift({
                        tipo: tipoSintetico,
                        tipo_proceso: tipoSintetico === 'costura-bodega' ? 'Bodega' : 'Costura',
                        nombre_proceso: tipoSintetico === 'costura-bodega' ? 'Bodega' : 'Costura',
                        estado: 'Pendiente',
                        es_base: true,
                        ubicaciones: [],
                        observaciones: '',
                        imagenes: Array.isArray(prendaData?.imagenes) ? prendaData.imagenes : [],
                        tallas: prendaData?.tallas || {}
                    });

                    reciboIndice = 0;
                    tipoRecibo = tipoSintetico;
                }
            }

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            // === INYECCIÓN DE TALLAS DEL PARCIAL (antes de renderizar) ===
            const recibo = recibos[reciboIndice];
            const tallasFormato = parcialResult.data.tallas_formato;
            const tallasFormatoColores = parcialResult.data.tallas_formato_colores;
            const tallasArrayParcial = parcialResult.data.tallas;
            const tallasDetalleParcial = parcialResult.data.tallas_detalle;

            console.log('[PedidosRecibosModule.abrirReciboParcial] Inyectando tallas del parcial:', {
                tallasOriginales: recibo.tallas,
                tallasDelParcial: tallasFormato,
                nombreAnexo
            });

            // Sobrescribir tallas del recibo con las del parcial
            recibo.tallas = (tallasFormatoColores && Object.keys(tallasFormatoColores).length > 0)
                ? tallasFormatoColores
                : tallasFormato;

            // Si el parcial trae colores por talla, inyectarlos para que Formatters
            // renderice agrupado por color.
            if (Array.isArray(tallasArrayParcial) && tallasArrayParcial.length > 0) {
                recibo.talla_colores = tallasArrayParcial;
            } else {
                delete recibo.talla_colores;
            }

            // Marcar como parcial para que el renderer sepa limpiar consecutivo
            recibo._esParcial = true;
            recibo._nombreAnexo = nombreAnexo || tipoRecibo;
            // Asegurar consecutivo del anexo como fuente de verdad para el renderer
            if (consecutivoAnexo) {
                recibo.numero_recibo = consecutivoAnexo;
            }

            // Inyectar detalles por talla (observaciones/ubicaciones) del anexo
            // para que Formatters pinte OBSERVACIONES/UBICACIONES POR TALLA solo de las tallas anexadas.
            if (Array.isArray(tallasDetalleParcial) && tallasDetalleParcial.length > 0) {
                recibo.tallas_detalle = tallasDetalleParcial;
            } else {
                delete recibo.tallas_detalle;
            }

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Temporalmente limpiar talla_colores de prendaData para que el renderer
            // use los colores del parcial (si existen) y no los de la prenda base.
            const tallaColoresOriginal = prendaData.talla_colores;
            if (recibo.talla_colores && Array.isArray(recibo.talla_colores) && recibo.talla_colores.length > 0) {
                prendaData.talla_colores = recibo.talla_colores;
            }

            // Renderizar con la pipeline normal (tallas ya están inyectadas)
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Restaurar talla_colores para no mutar el estado permanentemente
            prendaData.talla_colores = tallaColoresOriginal;

            // Post-renderizado: ajustar título y consecutivo para el anexo
            const titleEl = document.querySelector('.receipt-title');
            if (titleEl) {
                const tipoReciboLower = String(tipoRecibo || '').toLowerCase();
                const tituloTipo = (tipoReciboLower === 'costura-bodega') ? 'COSTURA' : String(tipoRecibo || '').toUpperCase();
                titleEl.textContent = 'RECIBO DE ' + tituloTipo;
            }

            const pedidoNumberEl = document.querySelector('#order-pedido') || document.querySelector('.pedido-number');
            if (pedidoNumberEl) {
                pedidoNumberEl.textContent = consecutivoAnexo ? ('#' + consecutivoAnexo) : '#-';
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] ✓ Renderizado completo con tallas del parcial');

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {
            console.error('[PedidosRecibosModule.abrirReciboParcial] Error:', err);
            alert('Error al cargar el recibo parcial: ' + err.message);
            this.modalManager.cerrarModal();
        }
    }

    /**
     * Renderiza el recibo y configura componentes
     */
    _renderizarRecibo(prendaData, reciboIndice, tipoProceso, datosPedido, recibos) {
        // Mantener prendaData y recibos actuales en el estado para impresión/navegación.
        // (printReceiptModal lee prendaData desde el estado)
        this.modalManager.setState({
            prendaData,
            procesosActuales: recibos,
            procesoActualIndice: reciboIndice,
            tipoProceso
        });

        // Crear botón X
        CloseButtonManager.crearBotonCierre(this.modalManager);

        // Renderizar contenido
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            reciboIndice,
            tipoProceso,
            datosPedido,
            recibos
        );

        // Configurar navegación
        NavigationManager.configurarFlechas(
            this.modalManager,
            prendaData,
            (prendaData, indice, tipo) => this._onProcesoCambiado(prendaData, indice, tipo, datosPedido, recibos)
        );
    }

    /**
     * Callback cuando cambia de proceso vía navegación
     */
    _onProcesoCambiado(prendaData, nuevoIndice, tipoRecibo, datosPedido, recibos) {
        // Renderizar nuevo recibo
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            nuevoIndice,
            tipoRecibo,
            datosPedido,
            recibos
        );

        // Actualizar flechas
        NavigationManager.actualizarVisibilidad(this.modalManager);

        // Cerrar galería si estaba abierta
        const galeria = document.getElementById('galeria-modal-costura');
        if (galeria && galeria.style.display !== 'none') {
            GalleryManager.cerrarGaleria();
        }
    }

    /**
     * Abre la galería de imágenes
     */
    async abrirGaleria() {
        const mostroGaleria = await GalleryManager.abrirGaleria(this.modalManager);
        if (mostroGaleria) {
            GalleryManager.actualizarBotonesEstilo(true);
        } else {
            // Usar galería original solo si existe y evitando recursión
            if (window.toggleGaleria && window.originalToggleGaleria) {
                return window.originalToggleGaleria.call(this);
            }
        }
    }

    /**
     * Cierra la galería
     */
    cerrarGaleria() {
        GalleryManager.cerrarGaleria();
    }

    /**
     * Cierra el recibo/modal
     */
    cerrarRecibo() {
        CloseButtonManager.forzarCierre(this.modalManager);
    }

    /**
     * Obtiene el estado actual del modal
     */
    getEstado() {
        return this.modalManager.getState();
    }
}

// Instancia global singleton
window.pedidosRecibosModule = new PedidosRecibosModule();

/**
 * FUNCIÓN GLOBAL compatibilidad con código antiguo
 * Mantiene la API antigua mientras usa el nuevo módulo
 */
window.openOrderDetailModalWithProcess = async function(pedidoId, prendaId, tipoRecibo, prendaIndex = null) {
    return window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);
};

 if (typeof window.printReceiptModal !== 'function') {
     window.printReceiptModal = function() {
         const wrapper = document.getElementById('order-detail-modal-wrapper');
         if (!wrapper) {
             window.print();
             return;
         }

        // Nuevo flujo: imprimir usando el diseño/paginación del ejemplo "sistema-de-recibos-mundo-industrial".
        // Se genera un HTML limpio para impresión (A4), sin depender del layout actual del modal.
        try {
            const estado = window.pedidosRecibosModule && typeof window.pedidosRecibosModule.getEstado === 'function'
                ? window.pedidosRecibosModule.getEstado()
                : null;

            const datosPedido = estado && estado.datosCompletos ? estado.datosCompletos : {};
            const prendaData = estado && estado.prendaData ? estado.prendaData : null;
            const recibos = estado && Array.isArray(estado.procesosActuales) ? estado.procesosActuales : [];
            const reciboActual = (recibos && typeof estado?.procesoActualIndice === 'number') ? recibos[estado.procesoActualIndice] : null;
            const tipoProceso = estado && estado.tipoProceso ? estado.tipoProceso : (reciboActual?.tipo || reciboActual?.tipo_proceso || '');

            // Fallback DOM: si por algún motivo el estado no trae datos (se ve en COSTURA en algunas vistas),
            // leer lo que ya está pintado en el modal.
            const getDomText = (sel) => {
                try {
                    const el = wrapper.querySelector(sel);
                    if (!el) return '';
                    return String(el.textContent || '').trim();
                } catch (_) {
                    return '';
                }
            };

            const getDomHtmlText = (sel) => {
                try {
                    const el = wrapper.querySelector(sel);
                    if (!el) return '';
                    return String(el.innerText || el.textContent || '').trim();
                } catch (_) {
                    return '';
                }
            };

            const esc = (v) => {
                const s = String(v ?? '');
                return s
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const normalizarLista = (raw) => {
                if (!raw) return [];
                if (Array.isArray(raw)) return raw.map(x => String(x)).filter(Boolean);
                if (typeof raw === 'string') {
                    // intentar JSON primero
                    const s = raw.trim();
                    if ((s.startsWith('[') && s.endsWith(']')) || (s.startsWith('{') && s.endsWith('}'))) {
                        try {
                            const parsed = JSON.parse(s);
                            if (Array.isArray(parsed)) return parsed.map(x => String(x)).filter(Boolean);
                        } catch (_) {}
                    }
                    return s.split(/\r?\n|\s*\|\s*|\s*,\s*/g).map(x => x.trim()).filter(Boolean);
                }
                return [];
            };

            const buildTallasResumen = (tallas, tallaColores = null) => {
                const normalizarGenero = (g) => String(g || '').trim().toUpperCase();
                const normalizarTalla = (t) => String(t || '').trim().toUpperCase();
                const normalizarColor = (c) => {
                    const s = String(c || '').trim().toUpperCase();
                    return s || 'SIN COLOR';
                };

                const formatGeneroGroup = (mapGeneroATallas) => {
                    // mapGeneroATallas: { CABALLERO: Map(talla->cantidad), DAMA: ... }
                    const partes = [];
                    Object.keys(mapGeneroATallas).forEach((gen) => {
                        const m = mapGeneroATallas[gen];
                        if (!m) return;
                        const items = [];
                        for (const [tallaKey, cant] of m.entries()) {
                            const n = Number(cant || 0);
                            if (!tallaKey || !n) continue;
                            items.push(`${tallaKey}: ${n}`);
                        }
                        if (items.length > 0) {
                            partes.push(`${gen}: ${items.join(', ')}`);
                        }
                    });
                    return partes.join(' | ');
                };

                // === 1) Prioridad: tallas por color (array estilo backend: [{genero,talla,color_nombre,cantidad}, ...]) ===
                const coloresArr = Array.isArray(tallaColores) ? tallaColores : null;
                if (coloresArr && coloresArr.length > 0) {
                    // Agrupar por color -> género -> talla
                    const byColor = new Map();
                    coloresArr.forEach((row) => {
                        const genero = normalizarGenero(row?.genero);
                        const talla = normalizarTalla(row?.talla);
                        const color = normalizarColor(row?.color_nombre || row?.color);
                        const cantidad = Number(row?.cantidad || 0);
                        if (!genero || !talla || !cantidad) return;

                        if (!byColor.has(color)) byColor.set(color, new Map());
                        const byGenero = byColor.get(color);
                        if (!byGenero.has(genero)) byGenero.set(genero, new Map());
                        const byTalla = byGenero.get(genero);
                        byTalla.set(talla, (Number(byTalla.get(talla) || 0) + cantidad));
                    });

                    const partesColor = [];
                    for (const [color, byGenero] of byColor.entries()) {
                        const mapGeneroATallas = {};
                        for (const [gen, byTalla] of byGenero.entries()) {
                            mapGeneroATallas[gen] = byTalla;
                        }
                        const strGenero = formatGeneroGroup(mapGeneroATallas);
                        if (strGenero) {
                            partesColor.push(`${color}: ${strGenero}`);
                        }
                    }
                    return partesColor.join(' | ');
                }

                // === 2) Array simple: [{genero,talla,cantidad}] (agrupar por género sin repetir) ===
                if (Array.isArray(tallas)) {
                    const mapGeneroATallas = {};
                    tallas.forEach((t) => {
                        const genero = normalizarGenero(t?.genero);
                        const talla = normalizarTalla(t?.talla);
                        const cantidad = Number(t?.cantidad || 0);
                        if (!genero || !talla || !cantidad) return;
                        if (!mapGeneroATallas[genero]) mapGeneroATallas[genero] = new Map();
                        const m = mapGeneroATallas[genero];
                        m.set(talla, (Number(m.get(talla) || 0) + cantidad));
                    });
                    return formatGeneroGroup(mapGeneroATallas);
                }

                // === 3) Objeto: {GENERO: {talla: cantidad}} o {GENERO: {talla: [{color,cantidad}]}} ===
                if (tallas && typeof tallas === 'object') {
                    // Si detectamos arrays con colores, agrupar por color igual
                    let tieneColores = false;
                    for (const genKey of Object.keys(tallas)) {
                        const v = tallas[genKey];
                        if (!v || typeof v !== 'object') continue;
                        for (const tallaKey of Object.keys(v)) {
                            const cell = v[tallaKey];
                            if (Array.isArray(cell) && cell.length > 0 && cell[0] && (cell[0].color || cell[0].color_nombre)) {
                                tieneColores = true;
                                break;
                            }
                        }
                        if (tieneColores) break;
                    }

                    if (tieneColores) {
                        const byColor = new Map();
                        Object.keys(tallas).forEach((genKey) => {
                            const genero = normalizarGenero(genKey);
                            const v = tallas[genKey];
                            if (!v || typeof v !== 'object') return;
                            Object.keys(v).forEach((tallaKey) => {
                                const talla = normalizarTalla(tallaKey);
                                const cell = v[tallaKey];
                                if (!Array.isArray(cell)) return;
                                cell.forEach((item) => {
                                    const color = normalizarColor(item?.color || item?.color_nombre);
                                    const cantidad = Number(item?.cantidad || 0);
                                    if (!cantidad) return;
                                    if (!byColor.has(color)) byColor.set(color, new Map());
                                    const byGenero = byColor.get(color);
                                    if (!byGenero.has(genero)) byGenero.set(genero, new Map());
                                    const byTalla = byGenero.get(genero);
                                    byTalla.set(talla, (Number(byTalla.get(talla) || 0) + cantidad));
                                });
                            });
                        });

                        const partesColor = [];
                        for (const [color, byGenero] of byColor.entries()) {
                            const mapGeneroATallas = {};
                            for (const [gen, byTalla] of byGenero.entries()) {
                                mapGeneroATallas[gen] = byTalla;
                            }
                            const strGenero = formatGeneroGroup(mapGeneroATallas);
                            if (strGenero) {
                                partesColor.push(`${color}: ${strGenero}`);
                            }
                        }
                        return partesColor.join(' | ');
                    }

                    const mapGeneroATallas = {};
                    Object.keys(tallas).forEach((generoKey) => {
                        const genero = normalizarGenero(generoKey);
                        const tallasGenero = tallas[generoKey];
                        if (!tallasGenero || typeof tallasGenero !== 'object') return;
                        if (!mapGeneroATallas[genero]) mapGeneroATallas[genero] = new Map();
                        const m = mapGeneroATallas[genero];
                        Object.keys(tallasGenero).forEach((tallaKey) => {
                            const cant = Number(tallasGenero[tallaKey] || 0);
                            if (!cant) return;
                            const talla = normalizarTalla(tallaKey);
                            m.set(talla, (Number(m.get(talla) || 0) + cant));
                        });
                    });
                    return formatGeneroGroup(mapGeneroATallas);
                }

                return '';
            };

            const buildObservacionesPorTalla = () => {
                // Preferir `tallas_detalle` si existe en el recibo/proceso.
                const detalle = reciboActual?.tallas_detalle || reciboActual?.tallasDetalle || null;
                if (!Array.isArray(detalle) || detalle.length === 0) return [];

                return detalle.map((d) => {
                    const talla = String(d?.talla || d?.nombre_talla || '').trim();
                    const genero = String(d?.genero || '').trim();

                    // Observaciones/ubicaciones: soportar múltiples formatos
                    let obs = [];
                    if (Array.isArray(d?.observaciones)) obs = d.observaciones;
                    else if (typeof d?.observaciones === 'string') obs = normalizarLista(d.observaciones);
                    else if (Array.isArray(d?.ubicaciones)) obs = d.ubicaciones;
                    else if (typeof d?.ubicaciones === 'string') obs = normalizarLista(d.ubicaciones);

                    obs = obs.map(x => String(x).trim()).filter(Boolean);
                    return {
                        genero: genero ? genero.toUpperCase() : '',
                        talla: talla ? talla.toUpperCase() : '-',
                        observaciones: obs
                    };
                }).filter(x => x.observaciones && x.observaciones.length > 0);
            };

            const fechaDOM = (() => {
                const d = getDomText('.day-box');
                const m = getDomText('.month-box');
                const y = getDomText('.year-box');
                if (d && m && y) return `${d}/${m}/${y}`;
                return '';
            })();
            const fecha = String(datosPedido.fecha || fechaDOM || '').trim();
            const asesor = String(datosPedido.asesor || datosPedido.asesora || getDomText('#asesora-value') || '').trim();
            const formaPago = String(datosPedido.forma_de_pago || getDomText('#forma-pago-value') || '').trim();
            const cliente = String(datosPedido.cliente || getDomText('#cliente-value') || '').trim();

            const descripcionDOM = getDomHtmlText('#descripcion-text');

            // Intentar extraer nombre/color/tallas desde el texto del modal si prendaData no está.
            const extraerDesdeDescripcion = (raw) => {
                const out = { prendaNombre: '', prendaColor: '', tallas: '' };
                const s = String(raw || '').replace(/\r/g, '').trim();
                if (!s) return out;
                const mPrenda = s.match(/PR\s*ENDA\s*\d+\s*:\s*([^\n]+)/i) || s.match(/PRENDA\s*\d+\s*:\s*([^\n]+)/i);
                if (mPrenda && mPrenda[1]) out.prendaNombre = String(mPrenda[1]).trim();
                const mTallas = s.match(/TALLAS\s*:\s*([^\n]+)/i);
                if (mTallas && mTallas[1]) out.tallas = String(mTallas[1]).trim();
                // Color: algunas vistas lo imprimen como "COLOR:" o como segunda línea fuerte.
                const mColor = s.match(/COLOR\s*:\s*([^\n]+)/i);
                if (mColor && mColor[1]) out.prendaColor = String(mColor[1]).trim();
                return out;
            };

            const extra = extraerDesdeDescripcion(descripcionDOM);

            const prendaNombre = String(prendaData?.nombre || prendaData?.nombre_prenda || extra.prendaNombre || '').trim();
            const prendaColor = String(prendaData?.color || extra.prendaColor || '').trim();

            // COSTURA y algunos recibos base no traen `ubicaciones` en el objeto recibo.
            // Para COSTURA: usar solo la descripción limpia de la prenda, sin repetir campos ya mostrados
            const ubicaciones = (() => {
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    // Para COSTURA: solo la descripción principal de la prenda
                    const desc = prendaData?.descripcion || '';
                    return desc ? [desc] : [];
                }
                
                // Para otros tipos: mantener lógica original
                const raw = (
                    reciboActual?.ubicaciones ||
                    reciboActual?.ubicaciones_array ||
                    reciboActual?.observaciones ||
                    prendaData?.descripcion
                );

                // Si no hay nada en data/estado, usar lo que se ve en el modal como bloque único.
                if ((!raw || (Array.isArray(raw) && raw.length === 0)) && descripcionDOM) {
                    return [descripcionDOM];
                }

                return normalizarLista(raw);
            })();

            const tallasResumen = (() => {
                const str = buildTallasResumen(
                    reciboActual?.tallas || prendaData?.tallas || null,
                    reciboActual?.talla_colores || prendaData?.talla_colores || null
                );
                if (str) return str;
                
                // Fallback para COSTURA: intentar desde variantes
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                    if (Array.isArray(variantes) && variantes.length > 0) {
                        const tallasMap = new Map();
                        variantes.forEach(v => {
                            const talla = v?.talla || '';
                            const genero = v?.genero || '';
                            const cantidad = Number(v?.cantidad || 0);
                            if (talla && cantidad > 0) {
                                const key = genero ? `${genero.toUpperCase()}:${talla.toUpperCase()}` : talla.toUpperCase();
                                tallasMap.set(key, (tallasMap.get(key) || 0) + cantidad);
                            }
                        });
                        
                        if (tallasMap.size > 0) {
                            const generosMap = new Map();
                            tallasMap.forEach((cantidad, key) => {
                                const [genero, talla] = key.includes(':') ? key.split(':') : ['', key];
                                if (!generosMap.has(genero)) generosMap.set(genero, new Map());
                                generosMap.get(genero).set(talla, cantidad);
                            });
                            
                            const partes = [];
                            generosMap.forEach((tallasGen, genero) => {
                                const tallasStr = Array.from(tallasGen.entries())
                                    .map(([t, c]) => `${t}-${c}`)
                                    .join(' ');
                                partes.push(genero ? `${genero}: ${tallasStr}` : tallasStr);
                            });
                            return partes.join(' | ');
                        }
                    }
                }
                
                return String(extra.tallas || '').trim();
            })();
            const observacionesPorTalla = buildObservacionesPorTalla();

            const receiptTitleEl = wrapper.querySelector('#receipt-title');
            const receiptTitle = receiptTitleEl ? receiptTitleEl.textContent.trim() : '';
            const titulo = receiptTitle || ('RECIBO DE ' + String(tipoProceso || '').toUpperCase());

            // Consecutivo real del recibo actual (fuente de verdad para impresión)
            // 1) Preferir el dato estructurado del estado (reciboActual.numero_recibo)
            // 2) Fallback: leer lo que ya está pintado en el modal (#order-pedido)
            const numeroReciboActual = reciboActual?.numero_recibo ?? reciboActual?.numeroRecibo ?? null;
            const numeroReciboDesdeDOM = (() => {
                try {
                    const el = document.querySelector('#order-pedido') || document.querySelector('.pedido-number');
                    const raw = el ? String(el.textContent || '').trim() : '';
                    if (!raw) return '';
                    // raw puede ser "#7" o "7"; normalizar a solo número
                    return raw.startsWith('#') ? raw.slice(1).trim() : raw;
                } catch (_) {
                    return '';
                }
            })();

            // Número a mostrar en impresión: si no existe, NO inventar un consecutivo.
            const numeroReciboFinal = (numeroReciboActual !== null && numeroReciboActual !== undefined && String(numeroReciboActual).trim() !== '')
                ? String(numeroReciboActual).trim()
                : String(numeroReciboDesdeDOM || '').trim();
            const numeroParaImpresion = numeroReciboFinal ? ('#' + numeroReciboFinal) : '';

            // Paginación (misma lógica del ejemplo): estimación por altura total de 4 columnas.
            // Ajuste: agrupar por género sin repetirlo en cada talla.
            const pages = [];
            let currentBlocks = []; // [{type:'genero', genero} | {type:'talla', genero, talla, observaciones}]
            let currentHeightMm = 0;

            // Altura dinámica de la sección de observaciones por talla:
            // - Arranca con una altura por defecto más parecida a la vista.
            // - Crece gradualmente si el contenido lo requiere, hasta el máximo permitido en hoja.
            const MIN_AVAILABLE_HEIGHT_MM = 75;
            const MAX_AVAILABLE_HEIGHT_MM = 110;
            const GEN_HEADER_HEIGHT_MM = 4;
            const TALLA_TITLE_HEIGHT_MM = 6;
            const LINE_HEIGHT_MM = 3.2;

            const estimarAlturaTotalNecesariaMm = (items) => {
                if (!Array.isArray(items) || items.length === 0) return 0;
                const gruposPorGenero = new Map();
                items.forEach((t) => {
                    const key = String(t?.genero || '').toUpperCase();
                    if (!gruposPorGenero.has(key)) gruposPorGenero.set(key, []);
                    gruposPorGenero.get(key).push(t);
                });

                let total = 0;
                for (const [generoKey, tallasGrupo] of gruposPorGenero.entries()) {
                    if (generoKey) total += GEN_HEADER_HEIGHT_MM;
                    (tallasGrupo || []).forEach((tg) => {
                        const obsLen = Array.isArray(tg?.observaciones) ? tg.observaciones.length : 0;
                        total += TALLA_TITLE_HEIGHT_MM + (obsLen * LINE_HEIGHT_MM);
                    });
                }
                return total;
            };

            const totalNecesarioMm = estimarAlturaTotalNecesariaMm(observacionesPorTalla);
            const cabeEnUnaHojaConMax = totalNecesarioMm <= (MAX_AVAILABLE_HEIGHT_MM * 4);
            const availableHeightMm = cabeEnUnaHojaConMax
                ? Math.max(MIN_AVAILABLE_HEIGHT_MM, Math.min(MAX_AVAILABLE_HEIGHT_MM, Math.ceil(totalNecesarioMm / 4)))
                : MAX_AVAILABLE_HEIGHT_MM;

            const AVAILABLE_HEIGHT_MM = availableHeightMm;
            const TOTAL_COLUMN_CAPACITY_MM = AVAILABLE_HEIGHT_MM * 4;

            const pushPage = () => {
                if (!currentBlocks.length) return;
                pages.push({ blocks: currentBlocks, num: pages.length + 1 });
                currentBlocks = [];
                currentHeightMm = 0;
            };

            const addGeneroHeaderIfNeeded = (genero) => {
                if (!genero) return;
                const last = currentBlocks.length ? currentBlocks[currentBlocks.length - 1] : null;
                const yaEsta = last && last.type === 'genero' && last.genero === genero;
                if (yaEsta) return;

                // Si no cabe el header, cortar página.
                if (currentHeightMm + GEN_HEADER_HEIGHT_MM > TOTAL_COLUMN_CAPACITY_MM && currentBlocks.length > 0) {
                    pushPage();
                }

                currentBlocks.push({ type: 'genero', genero });
                currentHeightMm += GEN_HEADER_HEIGHT_MM;
            };

            // Agrupar por género manteniendo orden
            const grupos = new Map();
            observacionesPorTalla.forEach((t) => {
                const key = t.genero || '';
                if (!grupos.has(key)) grupos.set(key, []);
                grupos.get(key).push(t);
            });

            for (const [generoKey, tallasGrupo] of grupos.entries()) {
                const generoUpper = generoKey ? String(generoKey).toUpperCase() : '';

                // Encabezado de género (una vez por grupo, y se repetirá solo si hay salto de página)
                addGeneroHeaderIfNeeded(generoUpper);

                tallasGrupo.forEach((tallaItem) => {
                    let remainingObs = Array.isArray(tallaItem.observaciones) ? [...tallaItem.observaciones] : [];
                    let isFirstPart = true;

                    while (remainingObs.length > 0) {
                        // Si estamos iniciando un nuevo “bloque” en una página vacía o recién se cortó,
                        // y el grupo tiene género, repetir el header para contexto.
                        if (currentBlocks.length === 0) {
                            addGeneroHeaderIfNeeded(generoUpper);
                        } else {
                            // Si el último block es de otra cosa y el género se perdió por page-break,
                            // lo reinsertamos cuando corresponde.
                            const last = currentBlocks[currentBlocks.length - 1];
                            const generoPresente = last && ((last.type === 'genero' && last.genero === generoUpper) || (last.type === 'talla' && last.genero === generoUpper));
                            if (!generoPresente) {
                                addGeneroHeaderIfNeeded(generoUpper);
                            }
                        }

                        const titleH = isFirstPart ? TALLA_TITLE_HEIGHT_MM : 0;
                        const availableSpace = TOTAL_COLUMN_CAPACITY_MM - currentHeightMm - titleH;
                        const maxObsThatFit = Math.floor(availableSpace / LINE_HEIGHT_MM);

                        if (maxObsThatFit <= 0 && currentBlocks.length > 0) {
                            pushPage();
                            continue;
                        }

                        const take = remainingObs.splice(0, Math.max(0, maxObsThatFit));
                        if (take.length > 0) {
                            currentBlocks.push({
                                type: 'talla',
                                genero: generoUpper,
                                talla: isFirstPart ? tallaItem.talla : (tallaItem.talla + ' (cont.)'),
                                observaciones: take
                            });
                            currentHeightMm += titleH + (take.length * LINE_HEIGHT_MM);
                        }

                        isFirstPart = false;

                        if (remainingObs.length > 0) {
                            pushPage();
                        }
                    }
                });
            }

            pushPage();

            const totalPages = (pages && Array.isArray(pages) && pages.length > 0) ? pages.length : 1;

            const renderHeader = (pageNum) => {
                const pageLabel = totalPages > 1 ? (`PÁGINA ${pageNum}`) : '';

                // Mostrar el número real del recibo (ej: #7). Si no existe, no mostrar nada.
                const reciboLabel = numeroParaImpresion ? numeroParaImpresion : '';

                // Para COSTURA: renderizar estructura específica con campos separados
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                    const primeraVariante = variantes.length > 0 ? variantes[0] : {};
                    
                    // Extraer datos específicos de COSTURA
                    const manga = primeraVariante?.manga || prendaData?.manga || '';
                    const obsManga = primeraVariante?.manga_obs || primeraVariante?.obs_manga || prendaData?.obs_manga || '';
                    const broche = primeraVariante?.broche || prendaData?.broche || '';
                    const obsBroche = primeraVariante?.broche_obs || primeraVariante?.obs_broche || prendaData?.obs_broche || '';
                    const tieneBolsillos = primeraVariante?.tiene_bolsillos || primeraVariante?.bolsillos || prendaData?.tiene_bolsillos || false;
                    const obsBolsillos = primeraVariante?.bolsillos_obs || primeraVariante?.obs_bolsillos || prendaData?.obs_bolsillos || '';
                    const tieneReflectivo = primeraVariante?.tiene_reflectivo || prendaData?.tiene_reflectivo || false;
                    const obsReflectivo = primeraVariante?.obs_reflectivo || prendaData?.obs_reflectivo || '';
                    
                    return `
                        <img src="/images/logo.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
                        <div id="order-date" class="order-date">
                          <div class="fec-label">FECHA</div>
                          <div class="date-boxes">
                            <div class="date-box day-box">${esc((fechaDOM || '').split('/')[0] || '')}</div>
                            <div class="date-box month-box">${esc((fechaDOM || '').split('/')[1] || '')}</div>
                            <div class="date-box year-box">${esc((fechaDOM || '').split('/')[2] || '')}</div>
                          </div>
                        </div>

                        <div class="header-right">
                          ${pageLabel ? `<div class="page-label">${esc(pageLabel)}</div>` : ''}
                          <div class="receipt-title-print">${esc(titulo).toUpperCase()}</div>
                          ${reciboLabel ? `<div class="recibo-number-print">${esc(reciboLabel)}</div>` : ''}
                          <div class="cliente-print"><span class="label">CLIENTE:</span> <span class="value">${esc(cliente || '-')}</span></div>
                        </div>

                        <div class="meta">
                          <div style="grid-column: 1 / 3;"><span class="label">ASESOR:</span> <span class="value">${esc(asesor || '-')}</span></div>
                          <div style="grid-column: 1 / 3;"><span class="label">FORMA DE PAGO:</span> <span class="value">${esc(formaPago || '-')}</span></div>
                        </div>

                        <div class="prenda-info">
                          <div class="prenda-name">${esc(prendaNombre || '-').toUpperCase()}</div>
                        </div>
                        
                        <div class="costura-section">
                          ${prendaColor ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">COLOR:</span>
                              <span class="value">${esc(prendaColor)}</span>
                            </div>
                          </div>
                          ` : ''}
                          
                          ${prendaData?.tela ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">TELA:</span>
                              <span class="value">${esc(prendaData.tela)}</span>
                            </div>
                          </div>
                          ` : ''}
                          
                          ${manga ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">TIPO MANGA:</span>
                              <span class="value">${esc(manga)}</span>
                            </div>
                            ${obsManga ? `<div class="costura-obs">${esc(obsManga)}</div>` : ''}
                          </div>
                          ` : ''}
                          
                          ${broche ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">BROCHE/BOTÓN:</span>
                              <span class="value">${esc(broche)}</span>
                            </div>
                            ${obsBroche ? `<div class="costura-obs">${esc(obsBroche)}</div>` : ''}
                          </div>
                          ` : ''}
                          
                          ${tieneBolsillos ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">TIENE BOLSILLOS:</span>
                              <span class="value">SÍ</span>
                            </div>
                            ${obsBolsillos ? `<div class="costura-obs">${esc(obsBolsillos)}</div>` : ''}
                          </div>
                          ` : ''}
                          
                          ${tieneReflectivo ? `
                          <div class="costura-row">
                            <div class="costura-field">
                              <span class="label">TIENE REFLECTIVO:</span>
                              <span class="value">SÍ</span>
                            </div>
                            ${obsReflectivo ? `<div class="costura-obs">${esc(obsReflectivo)}</div>` : ''}
                          </div>
                          ` : ''}
                        </div>
                        
                        ${tallasResumen ? `
                        <div class="section">
                          <h4>TALLAS:</h4>
                          <div class="tallas-resumen">${esc(tallasResumen)}</div>
                        </div>
                        ` : ''}
                        
                        ${observacionesPorTalla.length > 0 ? `
                        <div class="section observations-section">
                          <h4>OBSERVACIONES POR TALLA:</h4>
                          <div class="tallas-columns">
                            ${(function() {
                                const grupos = new Map();
                                observacionesPorTalla.forEach((t) => {
                                    const key = t.genero || '';
                                    if (!grupos.has(key)) grupos.set(key, []);
                                    grupos.get(key).push(t);
                                });
                                
                                let html = '';
                                for (const [generoKey, tallasGrupo] of grupos.entries()) {
                                    const generoUpper = generoKey ? generoKey.toUpperCase() : '';
                                    if (generoUpper) {
                                        html += `<div class="genero-header">${esc(generoUpper)}</div>`;
                                    }
                                    tallasGrupo.forEach((tallaItem) => {
                                        const lis = (tallaItem.observaciones || []).map(obs => `<li>${esc(obs).toUpperCase()}</li>`).join('');
                                        html += `
                                            <div class="talla-item">
                                              <div class="talla-title">${esc(tallaItem.talla).toUpperCase()}</div>
                                              <ul class="observaciones-list">${lis}</ul>
                                            </div>
                                        `;
                                    });
                                }
                                return html;
                            })()}
                          </div>
                        </div>
                        ` : ''}
                        
                        <div class="section">
                          <h4>DESCRIPCIÓN:</h4>
                          <div class="descripcion-list">${(ubicaciones && ubicaciones.length > 0 && ubicaciones[0]) ? ubicaciones.map(u => `<div>${esc(u).toUpperCase()}</div>`).join('') : '<div>-</div>'}</div>
                        </div>
                    `;
                }
                
                // Para otros tipos de recibo: mantener estructura original
                const ubicHtml = (ubicaciones && ubicaciones.length > 0)
                    ? ubicaciones.map(u => `<div>${esc(u).toUpperCase()}</div>`).join('')
                    : '<div>-</div>';

                return `
                    <img src="/images/logo.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
                    <div id="order-date" class="order-date">
                      <div class="fec-label">FECHA</div>
                      <div class="date-boxes">
                        <div class="date-box day-box">${esc((fechaDOM || '').split('/')[0] || '')}</div>
                        <div class="date-box month-box">${esc((fechaDOM || '').split('/')[1] || '')}</div>
                        <div class="date-box year-box">${esc((fechaDOM || '').split('/')[2] || '')}</div>
                      </div>
                    </div>

                    <div class="header-right">
                      ${pageLabel ? `<div class="page-label">${esc(pageLabel)}</div>` : ''}
                      <div class="receipt-title-print">${esc(titulo).toUpperCase()}</div>
                      ${reciboLabel ? `<div class="recibo-number-print">${esc(reciboLabel)}</div>` : ''}
                      <div class="cliente-print"><span class="label">CLIENTE:</span> <span class="value">${esc(cliente || '-')}</span></div>
                    </div>

                    <div class="meta">
                      <div style="grid-column: 1 / 3;"><span class="label">ASESOR:</span> <span class="value">${esc(asesor || '-')}</span></div>
                      <div style="grid-column: 1 / 3;"><span class="label">FORMA DE PAGO:</span> <span class="value">${esc(formaPago || '-')}</span></div>
                    </div>

                    <div class="prenda-info">
                      <div class="prenda-name">${esc(prendaNombre || '-').toUpperCase()}</div>
                      <div class="prenda-color">${esc(prendaColor || '-').toUpperCase()}</div>
                    </div>
                    <div class="section">
                      <h4>UBICACIONES:</h4>
                      <div class="ubicaciones-list">${ubicHtml}</div>
                    </div>
                `;
            };

            const renderTallaItem = (t) => {
                const lis = (t.observaciones || []).map(obs => `<li>${esc(obs).toUpperCase()}</li>`).join('');
                return `
                    <div class="talla-item">
                      <div class="talla-title">${esc(t.talla).toUpperCase()}</div>
                      <ul class="observaciones-list">${lis}</ul>
                    </div>
                `;
            };

            const renderGeneroHeader = (genero) => {
                if (!genero) return '';
                return `
                    <div class="genero-header">${esc(genero).toUpperCase()}</div>
                `;
            };

            const renderTallasSection = (blocks) => {
                const itemsHtml = (Array.isArray(blocks) ? blocks : []).map((b) => {
                    if (b && b.type === 'genero') return renderGeneroHeader(b.genero);
                    if (b && b.type === 'talla') return renderTallaItem(b);
                    return '';
                }).join('');

                return `
                    <div class="section observations-section" style="margin-top: 2px;">
                      <h4 style="margin-bottom: 2px; font-size: 11px;">OBSERVACIONES POR TALLA</h4>
                      <div class="tallas-columns">${itemsHtml}</div>
                    </div>
                `;
            };

            const renderFooter = () => {
                return `
                    <div class="separator-line"></div>
                    <div class="footer">
                      <div>ENCARGADO DE ORDEN:<br><span style="font-weight:700">-</span></div>
                      <div>PRENDAS ENTREGADAS:<br><span style="font-weight:700">0/0</span></div>
                    </div>
                `;
            };

            const pagesHtml = (pages.length > 0 ? pages : [{ blocks: [], num: 1 }]).map((p) => {
                return `
                    <div class="page">
                      <div class="receipt-card">
                        ${renderHeader(p.num)}
                        ${renderTallasSection(p.blocks)}
                        ${renderFooter()}
                      </div>
                    </div>
                `;
            }).join('');

            const css = `
                :root { --page-width: 180mm; --page-height: 297mm; --page-padding: 5mm; --brand-font: Inter, ui-sans-serif, system-ui, sans-serif; }
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                body { font-family: var(--brand-font); color: #111; }
                @media print {
                  @page { size: A4 portrait; margin: 0; }
                  body { background: #fff; margin: 0; padding: 0; }
                  .no-print { display: none !important; }
                  .page { width: var(--page-width); height: var(--page-height); padding: var(--page-padding); page-break-after: always; position: relative; overflow: hidden; }
                  .receipt-card { height: 100%; border: 4px solid #111; border-radius: 20px; padding: 30px 30px 60px 30px; box-shadow: none; }
                  /* Altura fija para que el contenido no sobrepase el separator/footer y fluya por columnas */
                  .tallas-columns {
                    height: ${AVAILABLE_HEIGHT_MM}mm;
                    overflow: hidden;
                    column-count: 4;
                    -webkit-column-count: 4;
                    -moz-column-count: 4;
                    column-fill: auto;
                    column-gap: 3px;
                    -webkit-column-gap: 3px;
                    -moz-column-gap: 3px;
                  }
                }
                .page { width: var(--page-width); min-height: var(--page-height); padding: var(--page-padding); box-sizing: border-box; position: relative; overflow: hidden; margin: 0 auto; }
                .receipt-card { width: 100%; border: 4px solid #111; border-radius: 20px; padding: 30px 30px 70px 30px; position: relative; }
                .order-logo { display: block; margin: -70px auto 20px auto; width: 200px; height: auto; }
                .order-date { position: absolute; top: 110px; left: 20px; background: #000; border-radius: 10px; padding: 8px; color: #fff; text-align: center; width: 180px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
                .fec-label { font-weight: 900; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; }
                .date-boxes { display: flex; justify-content: space-between; gap: 6px; }
                .date-box { background: #fff; color: #000; border-radius: 6px; padding: 8px 0; width: 52px; font-weight: 900; font-size: 14px; }
                .header-right { position: absolute; top: 110px; right: 30px; text-align: right; width: 55%; }
                .page-label { font-weight: 900; font-size: 11px; opacity: 0.85; line-height: 1; margin: 0 0 2px 0; text-align: right; }
                .receipt-title-print { font-weight: 900; text-transform: uppercase; text-align: right; font-size: 16px; line-height: 1.1; margin: 0; }
                .recibo-number-print { color: #d32f2f; font-weight: 900; font-size: 24px; line-height: 1; margin-top: 2px; text-align: right; }
                .cliente-print { font-weight: 900; font-size: 12px; margin-top: 6px; text-align: right; }
                .meta { display:grid; grid-template-columns: 1fr 1fr; gap: 5px 20px; font-size: 12px; font-weight: 700; margin: 0 0 15px 0; padding-top: 35px; }
                .meta .label { opacity: 0.7; }
                .prenda-info { margin-bottom: 15px; }
                .prenda-name { font-weight: 900; font-size: 16px; text-transform: uppercase; }
                .prenda-color { font-weight: 700; font-size: 14px; text-transform: uppercase; }
                .section { margin-bottom: 15px; font-size: 12px; }
                .section h4 { margin: 0 0 5px 0; font-weight: 900; text-transform: uppercase; font-size: 12px; }
                .tallas-resumen { color:#d32f2f; font-weight: 900; }
                
                /* Estilos específicos para COSTURA */
                .costura-section { margin-bottom: 15px; font-size: 11px; }
                .costura-row { margin-bottom: 6px; }
                .costura-field { display: flex; gap: 4px; margin-bottom: 2px; }
                .costura-field .label { font-weight: 900; min-width: 140px; }
                .costura-field .value { font-weight: 700; }
                .costura-obs { margin-left: 144px; font-weight: 500; font-style: italic; color: #333; }
                .descripcion-list { font-size: 11px; line-height: 1.3; }
                .observations-section { margin-bottom: 15px; }
                .observations-section h4 { margin: 0 0 5px 0; font-weight: 900; text-transform: uppercase; font-size: 12px; }
                /* Vista previa en popup: mantener el mismo flujo por columnas que en impresión */
                .tallas-columns {
                  height: ${AVAILABLE_HEIGHT_MM}mm;
                  overflow: hidden;
                  column-count: 4;
                  -webkit-column-count: 4;
                  -moz-column-count: 4;
                  column-fill: auto;
                  column-gap: 4px;
                  -webkit-column-gap: 4px;
                  -moz-column-gap: 4px;
                }
                .genero-header { break-inside: avoid; page-break-inside: avoid; margin: 0 0 4px 0; font-size: 11px; font-weight: 900; text-transform: uppercase; }
                /* Permitir cortes dentro de una talla para que las columnas queden llenas (sin huecos) */
                .talla-item { break-inside: auto; page-break-inside: auto; margin-bottom: 5px; font-size: 10px; padding-right: 1px; }
                .talla-title { font-weight: 900; text-decoration: underline; margin-bottom: 1px; font-size: 10.5px; }
                .observaciones-list { list-style:none; padding: 0; margin: 0; break-inside: auto; page-break-inside: auto; }
                .observaciones-list li { margin-bottom: 1px; line-height: 1.1; break-inside: auto; page-break-inside: auto; }
                .observaciones-list li::before { content: "• "; margin-right: 1px; }
                .separator-line { position: absolute; bottom: 60px; left: 0; right: 0; height: 1mm; background: #111; }
                .footer { position:absolute; bottom: 0; left:0; right:0; display:grid; grid-template-columns: 1fr 1fr; font-size: 11px; font-weight: 800; }
                .footer > div { padding: 10px 15px; border-right: 1mm solid #111; min-height: 50px; }
                .footer > div:last-child { border-right: none; }
                body.singlepage .receipt-card {
                  height: auto !important;
                  min-height: 0 !important;
                  padding: 20px 20px 70px 20px;
                  display: block;
                }
                /* Singlepage: NO forzar el recibo a ocupar toda la hoja; solo usar la altura necesaria */
                body.singlepage .page {
                  height: auto !important;
                  min-height: 0 !important;
                  overflow: visible !important;
                }
                body.singlepage .tallas-columns { height: ${AVAILABLE_HEIGHT_MM}mm !important; overflow: hidden !important; }
                body.singlepage .separator-line { position: absolute !important; bottom: 60px !important; left: 0 !important; right: 0 !important; }
                body.singlepage .footer { position: absolute !important; bottom: 0 !important; left: 0 !important; right: 0 !important; }
            `;

            const bodyClass = totalPages > 1 ? 'multipage' : 'singlepage';

            const html = `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Impresión Recibo</title>
  <style>${css}</style>
</head>
<body class="${bodyClass}">
  ${pagesHtml}
  <script>
    window.addEventListener('load', () => { setTimeout(() => window.print(), 50); });
  </script>
</body>
</html>`;

            const w = window.open('', '_blank');
            if (!w) {
                window.print();
                return;
            }
            w.document.open();
            w.document.write(html);
            w.document.close();
            return;
        } catch (e) {
            console.warn('[printReceiptModal] Error en impresión nueva, usando fallback window.print():', e);
            window.print();
        }
     };
 }

/**
 * FUNCIÓN GLOBAL para abrir recibo parcial (anexo)
 * Usa la pipeline de renderizado completa, inyectando tallas del parcial
 */
window.openOrderDetailModalWithParcial = async function(parcialId, prendaId, tipoString, pedidoIdOverride = null, nombreAnexoOverride = null) {
    const pedidoId = pedidoIdOverride || window.selectorRecibosState?.pedidoId;
    const nombreAnexo = nombreAnexoOverride || window.selectorRecibosState?.nombreProcesoAnexo || tipoString;

    if (!pedidoId) {
        console.error('[openOrderDetailModalWithParcial] pedidoId no disponible en selectorRecibosState');
        alert('Error: No se pudo determinar el pedido');
        return;
    }

    return window.pedidosRecibosModule.abrirReciboParcial(
        pedidoId, prendaId, tipoString, parcialId, nombreAnexo
    );
};

/**
 * FUNCIÓN GLOBAL para cerrar recibos
 */
window.cerrarModalRecibos = function() {
    window.pedidosRecibosModule.cerrarRecibo();
};

/**
 * FUNCIÓN GLOBAL para abrir imagen en grande desde la galería
 * Disponible en todas las vistas que usen PedidosRecibosModule
 */
if (typeof window.abrirModalImagenProcesoGrande !== 'function') {
    window.abrirModalImagenProcesoGrande = function(indice, fotos) {
        GalleryManager.abrirModalImagenProcesoGrande(indice, fotos);
    };
}

// Compatibilidad: algunos templates aún llaman onclick="toggleFactura()"
// Siempre sobreescribir para que funcione en todas las vistas (supervisor-pedidos, recibos-costura, insumos, etc.)
// Guarda la versión anterior como fallback para cuando NO hay estado activo de recibo
const _originalToggleFactura = window.toggleFactura;

window.toggleFactura = function() {
    console.log('[toggleFactura-PRM] Toggle entre recibo y galería');
    
    // Verificar si PedidosRecibosModule tiene estado activo
    const estado = window.pedidosRecibosModule ? window.pedidosRecibosModule.getEstado() : null;
    const tieneEstadoActivo = estado && estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId);
    
    if (!tieneEstadoActivo) {
        // Sin estado activo → usar la implementación original si existe
        console.log('[toggleFactura-PRM] Sin estado activo, delegando a implementación original');
        if (_originalToggleFactura) return _originalToggleFactura.call(this);
        return;
    }
    
    const galeria = document.getElementById('galeria-modal-costura');
    const estaEnGaleria = galeria && (galeria.style.display === 'flex' || galeria.style.display === 'block');
    
    const btnFactura = document.getElementById('btn-factura');
    const btnGaleria = document.getElementById('btn-galeria');
    
    if (estaEnGaleria) {
        // Estamos en galería → cerrar galería y mostrar recibo
        console.log('[toggleFactura-PRM] Cerrando galería, mostrando recibo');
        GalleryManager.cerrarGaleria();
        // Mostrar btn-factura (con icono de galería para indicar que se puede ir a galería)
        if (btnFactura) {
            btnFactura.style.display = 'block';
            btnFactura.style.visibility = 'visible';
            btnFactura.style.zIndex = '10';
            const icon = btnFactura.querySelector('i');
            if (icon) { icon.className = 'fas fa-images'; btnFactura.title = 'Ver galería'; }
        }
        // Ocultar btn-galeria
        if (btnGaleria) {
            btnGaleria.style.display = 'none';
            btnGaleria.style.visibility = 'hidden';
            btnGaleria.style.zIndex = '-1';
        }
    } else {
        // Estamos en recibo → abrir galería
        console.log('[toggleFactura-PRM] Abriendo galería');
        if (window.toggleGaleria) {
            window.toggleGaleria();
        }
        // Ocultar btn-factura
        if (btnFactura) {
            btnFactura.style.display = 'none';
            btnFactura.style.visibility = 'hidden';
            btnFactura.style.zIndex = '-1';
        }
        // Mostrar btn-galeria (con icono de recibo para indicar que se puede volver)
        if (btnGaleria) {
            btnGaleria.style.display = 'block';
            btnGaleria.style.visibility = 'visible';
            btnGaleria.style.zIndex = '10';
            const icon = btnGaleria.querySelector('i');
            if (icon) { icon.className = 'fas fa-receipt'; btnGaleria.title = 'Ver recibos'; }
        }
    }
};

// Interceptar toggleGaleria original
const originalToggleGaleria = window.toggleGaleria;
window.originalToggleGaleria = originalToggleGaleria; // Guardar referencia para evitar recursión

window.toggleGaleria = async function() {
    // Evitar recursión infinita
    if (window.toggleGaleria._calling) {
        console.warn('[toggleGaleria]  Evitando recursión infinita');
        return;
    }
    
    window.toggleGaleria._calling = true;
    
    try {
        // Si hay estado de recibos dinámicos, usar la nueva galería
        const estado = window.pedidosRecibosModule.getEstado();
        if (estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId)) {
            return window.pedidosRecibosModule.abrirGaleria();
        }
        
        // Si no, usar la galería original
        if (originalToggleGaleria) {
            return originalToggleGaleria.call(this);
        }
    } finally {
        window.toggleGaleria._calling = false;
    }
};

