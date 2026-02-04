/**
 * EJEMPLO PRÁCTICO: Crear un pedido completo con imágenes
 * 
 * Escenario: Usuario en Blade crea un pedido con:
 * - 1 prenda (Camisa)
 * - 2 imágenes de prenda
 * - 1 tela (Algodón rojo)
 * - 1 imagen de tela
 * - 1 proceso (Bordado)
 * - 1 imagen de proceso
 */

import { DOMPedidoModel } from './arquitectura-pedidos/DOMPedidoModel.js';
import { BackendPedidoModel } from './arquitectura-pedidos/BackendPedidoModel.js';
import { PedidoService } from './arquitectura-pedidos/PedidoService.js';

// ========================================
// 1️⃣ CONSTRUIR PEDIDO EN DOM (EDITABLE)
// ========================================

async function demoConstruirPedidoCompleto() {
    console.log('=== PASO 1: Construir modelo DOM ===');
    
    // Inicializar
    const pedidoDOM = new DOMPedidoModel();
    pedidoDOM.cliente = "Acme Corporation";
    pedidoDOM.asesora = "María García";
    pedidoDOM.forma_de_pago = "Crédito 30 días";

    // Agregar prenda
    const prenda = pedidoDOM.agregarPrenda({
        nombre_prenda: "Camisa Corporativa",
        cantidad_talla: {
            dama: { S: 10, M: 5, L: 3 },
            caballero: { M: 8, L: 6 }
        },
        variaciones: {
            tipo_manga: "Larga",
            tiene_bolsillos: true,
            tipo_broche: "Botones"
        },
        telas: [],
        procesos: [],
        imagenes: []
    });

    console.log(' Prenda agregada:', prenda.uid);

    // ========================================
    // 2️⃣ SIMULAR CARGA DE ARCHIVOS
    // ========================================
    
    console.log('\n=== PASO 2: Cargar imágenes ===');
    
    // Simular File objects (en la práctica vienen de <input type="file">)
    const archivosPrenda = [
        new File(["contenido1"], "camisa_frente.jpg", { type: "image/jpeg" }),
        new File(["contenido2"], "camisa_espalda.jpg", { type: "image/jpeg" })
    ];
    
    const archivoTela = new File(["contenido3"], "tela_algodon_rojo.jpg", { type: "image/jpeg" });
    const archivoProceso = new File(["contenido4"], "bordado_pecho.jpg", { type: "image/jpeg" });

    // Agregar imágenes de prenda
    for (const archivo of archivosPrenda) {
        await pedidoDOM.agregarImagenPrenda(0, archivo);
    }
    console.log(' Imágenes de prenda agregadas:', prenda.imagenes.length);

    // ========================================
    // 3️⃣ AGREGAR TELA
    // ========================================
    
    console.log('\n=== PASO 3: Agregar tela ===');
    
    const tela = {
        uid: "tela-uuid-001",
        tela_id: 64,         // ID de Tela en BD
        color_id: 50,        // ID de Color en BD
        nombre: "Algodón Premium",
        color: "Rojo Fuego",
        imagenes: []
    };
    
    prenda.telas.push(tela);
    
    // Agregar imagen a tela
    await pedidoDOM.agregarImagenTela(0, 0, archivoTela);
    console.log(' Tela agregada con imagen');

    // ========================================
    // 4️⃣ AGREGAR PROCESO
    // ========================================
    
    console.log('\n=== PASO 4: Agregar proceso ===');
    
    const proceso = {
        uid: "proceso-uuid-001",
        nombre: "bordado",
        ubicaciones: ["Pecho", "Espalda"],
        observaciones: "Bordado en hilo azul marino, tamaño grande",
        tallas: {
            dama: { S: 10, M: 5, L: 3 },
            caballero: { M: 8, L: 6 }
        },
        imagenes: []
    };
    
    prenda.procesos.push(proceso);
    
    // Agregar imagen a proceso
    await pedidoDOM.agregarImagenProceso(0, 0, archivoProceso);
    console.log(' Proceso agregado con imagen');

    // ========================================
    // 5️⃣ ESTADO ACTUAL DEL MODELO DOM
    // ========================================
    
    console.log('\n=== ESTADO ACTUAL DEL MODELO DOM ===');
    console.log('Cliente:', pedidoDOM.cliente);
    console.log('Prendas:', pedidoDOM.prendas.length);
    console.log('  - Prenda:', pedidoDOM.prendas[0].nombre_prenda);
    console.log('  - Imágenes de prenda:', pedidoDOM.prendas[0].imagenes.length);
    console.log('    - Contienen File objects:', pedidoDOM.prendas[0].imagenes[0].file instanceof File);
    console.log('    - Contienen preview:', !!pedidoDOM.prendas[0].imagenes[0].preview);
    console.log('  - Telas:', pedidoDOM.prendas[0].telas.length);
    console.log('    - Imágenes de tela:', pedidoDOM.prendas[0].telas[0].imagenes.length);
    console.log('  - Procesos:', pedidoDOM.prendas[0].procesos.length);
    console.log('    - Imágenes de proceso:', pedidoDOM.prendas[0].procesos[0].imagenes.length);

    // ========================================
    // 6️⃣ CONVERTIR A MODELO BACKEND
    // ========================================
    
    console.log('\n=== PASO 6: Convertir a modelo Backend ===');
    
    const pedidoBackend = BackendPedidoModel.fromDOMPedido(pedidoDOM);
    
    console.log(' Convertido a Backend (sin File objects)');
    console.log('JSON serializable:', JSON.stringify(pedidoBackend, null, 2).substring(0, 500) + '...');

    // ========================================
    // 7️⃣ VERIFICAR QUE ES SERIALIZABLE
    // ========================================
    
    console.log('\n=== PASO 7: Verificar serializabilidad ===');
    
    try {
        const json = JSON.stringify(pedidoBackend);
        console.log(' Backend model es 100% JSON serializable');
        console.log('Tamaño JSON:', (json.length / 1024).toFixed(2), 'KB');
        
        // Verificar que NO contiene File objects
        const contieneFILE = json.includes('[object File]') || json.includes('File');
        console.log(' NO contiene referencias a File objects:', !contieneFILE);
        
    } catch (error) {
        console.error('❌ Error al serializar:', error.message);
    }

    // ========================================
    // 8️⃣ MOSTRAR COMPARACIÓN
    // ========================================
    
    console.log('\n=== COMPARACIÓN: DOM vs Backend ===');
    
    console.log('DOM Model - Prenda[0].imagenes[0]:', {
        uid: pedidoDOM.prendas[0].imagenes[0].uid,
        file: pedidoDOM.prendas[0].imagenes[0].file instanceof File ? 'File object ' : 'NO FILE',
        preview: pedidoDOM.prendas[0].imagenes[0].preview ? 'data:image... ' : 'NO PREVIEW',
        nombre_archivo: pedidoDOM.prendas[0].imagenes[0].nombre_archivo
    });

    console.log('Backend Model - Prenda[0].imagenes[0]:', {
        uid: pedidoBackend.prendas[0].imagenes[0].uid,
        nombre_archivo: pedidoBackend.prendas[0].imagenes[0].nombre_archivo,
        note: 'Solo metadata, sin File object'
    });

    // ========================================
    // 9️⃣ ENVIAR AL BACKEND (REAL)
    // ========================================
    
    console.log('\n=== PASO 9: Enviar al backend ===');
    
    const service = new PedidoService('/asesores/pedidos-editable/crear');
    
    try {
        const resultado = await service.crearPedido({
            cliente: pedidoDOM.cliente,
            asesora: pedidoDOM.asesora,
            forma_de_pago: pedidoDOM.forma_de_pago,
            prendas: pedidoDOM.prendas,
            epps: []
        });
        
        console.log(' PEDIDO CREADO EXITOSAMENTE');
        console.log('ID:', resultado.pedido_id);
        console.log('Número:', resultado.numero_pedido);
        console.log('Cliente ID:', resultado.cliente_id);
        console.log('\n Imágenes guardadas en:', {
            prendas: `storage/pedidos/${resultado.pedido_id}/prendas/`,
            telas: `storage/pedidos/${resultado.pedido_id}/telas/`,
            procesos: `storage/pedidos/${resultado.pedido_id}/procesos/bordado/`
        });
        
    } catch (error) {
        console.error('❌ Error al crear pedido:', error.message);
    }
}

// ========================================
// EJECUTAR DEMO
// ========================================

// Llamar en consola o evento
// demoConstruirPedidoCompleto();

// O desde un botón en Blade
document.getElementById('btn-demo-pedido')?.addEventListener('click', demoConstruirPedidoCompleto);

export { demoConstruirPedidoCompleto };
