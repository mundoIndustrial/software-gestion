/**
 * DOMPedidoModel.js
 * 
 * MODELO EDITABLE DEL PEDIDO (solo vive en memoria/DOM)
 * 
 * Características:
 * ✅ Contiene File objects completos
 * ✅ Contiene previews de imágenes (data URLs)
 * ✅ Reactive (cambios en tiempo real)
 * ✅ Nunca se serializa a JSON
 * 
 * ESTRUCTURA:
 * {
 *   cliente: "Acme Corp",
 *   asesora: "María",
 *   forma_de_pago: "Contado",
 *   prendas: [
 *     {
 *       uid: "uuid-1",
 *       nombre_prenda: "Camisa",
 *       cantidad_talla: { dama: { S: 10 } },
 *       telas: [
 *         {
 *           uid: "tela-uuid-1",
 *           tela_id: 64,
 *           color_id: 50,
 *           nombre: "Algodón",
 *           color: "Rojo",
 *           imagenes: [
 *             {
 *               uid: "img-uuid-1",
 *               file: File object,
 *               preview: "data:image/...",
 *               nombre_archivo: "tela_001.jpg"
 *             }
 *           ]
 *         }
 *       ],
 *       procesos: [
 *         {
 *           uid: "proceso-uuid-1",
 *           nombre: "bordado",
 *           ubicaciones: ["pecho", "espalda"],
 *           observaciones: "Bordado con hilo azul",
 *           imagenes: [...]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */

export class DOMPedidoModel {
    constructor() {
        this.cliente = '';
        this.asesora = '';
        this.forma_de_pago = 'Contado';
        this.prendas = [];
        this.epps = [];
    }

    /**
     * Agregar prenda con UID único
     */
    agregarPrenda(prenda) {
        if (!prenda.uid) {
            prenda.uid = this.generarUID();
        }
        if (!prenda.telas) prenda.telas = [];
        if (!prenda.procesos) prenda.procesos = [];
        this.prendas.push(prenda);
        return prenda;
    }

    /**
     * Agregar imagen a prenda
     */
    agregarImagenPrenda(prendaIdx, file) {
        const prenda = this.prendas[prendaIdx];
        if (!prenda) throw new Error(`Prenda ${prendaIdx} no existe`);
        if (!prenda.imagenes) prenda.imagenes = [];

        const imagenDom = {
            uid: this.generarUID(),
            file: file,
            preview: this.crearPreview(file),
            nombre_archivo: this.sanitizarNombre(file.name)
        };

        prenda.imagenes.push(imagenDom);
        return imagenDom;
    }

    /**
     * Agregar imagen a tela
     */
    agregarImagenTela(prendaIdx, telaIdx, file) {
        const prenda = this.prendas[prendaIdx];
        if (!prenda) throw new Error(`Prenda ${prendaIdx} no existe`);

        const tela = prenda.telas[telaIdx];
        if (!tela) throw new Error(`Tela ${telaIdx} no existe en prenda ${prendaIdx}`);
        if (!tela.imagenes) tela.imagenes = [];

        const imagenDom = {
            uid: this.generarUID(),
            file: file,
            preview: this.crearPreview(file),
            nombre_archivo: this.sanitizarNombre(file.name)
        };

        tela.imagenes.push(imagenDom);
        return imagenDom;
    }

    /**
     * Agregar imagen a proceso
     */
    agregarImagenProceso(prendaIdx, procesoIdx, file) {
        const prenda = this.prendas[prendaIdx];
        if (!prenda) throw new Error(`Prenda ${prendaIdx} no existe`);

        const proceso = prenda.procesos[procesoIdx];
        if (!proceso) throw new Error(`Proceso ${procesoIdx} no existe en prenda ${prendaIdx}`);
        if (!proceso.imagenes) proceso.imagenes = [];

        const imagenDom = {
            uid: this.generarUID(),
            file: file,
            preview: this.crearPreview(file),
            nombre_archivo: this.sanitizarNombre(file.name)
        };

        proceso.imagenes.push(imagenDom);
        return imagenDom;
    }

    /**
     * Eliminar imagen de prenda
     */
    eliminarImagenPrenda(prendaIdx, imagenIdx) {
        const prenda = this.prendas[prendaIdx];
        if (!prenda?.imagenes) return false;
        prenda.imagenes.splice(imagenIdx, 1);
        return true;
    }

    /**
     * Eliminar imagen de tela
     */
    eliminarImagenTela(prendaIdx, telaIdx, imagenIdx) {
        const tela = this.prendas[prendaIdx]?.telas[telaIdx];
        if (!tela?.imagenes) return false;
        tela.imagenes.splice(imagenIdx, 1);
        return true;
    }

    /**
     * Eliminar imagen de proceso
     */
    eliminarImagenProceso(prendaIdx, procesoIdx, imagenIdx) {
        const proceso = this.prendas[prendaIdx]?.procesos[procesoIdx];
        if (!proceso?.imagenes) return false;
        proceso.imagenes.splice(imagenIdx, 1);
        return true;
    }

    /**
     * Crear preview de imagen
     */
    crearPreview(file) {
        return new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.readAsDataURL(file);
        });
    }

    /**
     * Sanitizar nombre de archivo
     */
    sanitizarNombre(nombre) {
        return nombre
            .replace(/[^a-zA-Z0-9._-]/g, '_')
            .replace(/_{2,}/g, '_')
            .toLowerCase();
    }

    /**
     * Generar UID
     */
    generarUID() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }
}
