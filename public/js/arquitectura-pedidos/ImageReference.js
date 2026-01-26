/**
 * ImageReference.js
 * 
 * Maneja referencias únicas a imágenes en el pedido
 * Separa COMPLETAMENTE el mundo del DOM (File + preview) del mundo del backend (JSON serializable)
 * 
 * ARQUITECTURA:
 * - DOM MODEL: { uid, file, preview, nombre_archivo }      (editable, con File object)
 * - BACKEND MODEL: { uid, nombre_archivo }                  (serializable en JSON)
 * - FormData: agrupa archivos por ruta: prendas.0.imagenes.0 = File
 */

export class ImageReference {
    constructor(uid, nombreArchivo) {
        this.uid = uid;                              // ID único: uuid-v4
        this.nombre_archivo = nombreArchivo;         // Nombre para almacenar
    }

    /**
     * Crear referencia a partir de File (SOLO en DOM)
     */
    static fromFile(file) {
        const uid = this.generarUID();
        const nombreArchivo = this.sanitizarNombreArchivo(file.name);
        return new ImageReference(uid, nombreArchivo);
    }

    /**
     * Crear referencia desde JSON (del backend o almacenamiento)
     */
    static fromJSON(json) {
        return new ImageReference(json.uid, json.nombre_archivo);
    }

    /**
     * Serializar para JSON (sin File object)
     */
    toJSON() {
        return {
            uid: this.uid,
            nombre_archivo: this.nombre_archivo
        };
    }

    /**
     * Generar UID único
     */
    static generarUID() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Sanitizar nombre de archivo
     */
    static sanitizarNombreArchivo(nombre) {
        return nombre
            .replace(/[^a-zA-Z0-9._-]/g, '_')
            .replace(/_{2,}/g, '_')
            .toLowerCase();
    }
}
