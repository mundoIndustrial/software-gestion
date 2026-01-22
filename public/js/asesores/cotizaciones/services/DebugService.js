/**
 * DebugService - Servicio para debugging de FormData
 * 
 * @module DebugService
 */
class DebugService {
    /**
     * Convierte FormData a objeto legible para debugging
     * @param {FormData} formData - Datos del formulario
     * @returns {Object} Objeto con los datos
     */
    static formDataToObject(formData) {
        const obj = {};
        for (let [key, value] of formData.entries()) {
            if (obj[key] !== undefined) {
                // Si la clave ya existe, convertir a array
                if (!Array.isArray(obj[key])) {
                    obj[key] = [obj[key]];
                }
                obj[key].push(value);
            } else {
                obj[key] = value;
            }
        }
        return obj;
    }

    /**
     * Loguea el FormData de forma legible
     * @param {FormData} formData - Datos a loguear
     */
    static logFormData(formData) {
        const obj = this.formDataToObject(formData);
        console.log('📤 FormData enviado:', obj);
        console.log(' JSON:', JSON.stringify(obj, null, 2));
        return obj;
    }

    /**
     * Valida que los campos obligatorios estén presentes
     * @param {FormData} formData - Datos a validar
     * @returns {Object} Resultado de validación
     */
    static validateFormData(formData) {
        const required = ['cliente', 'asesora', 'fecha', 'tipo_venta', '_token'];
        const missing = [];
        const obj = this.formDataToObject(formData);

        required.forEach(field => {
            if (!obj[field] || (typeof obj[field] === 'string' && !obj[field].trim())) {
                missing.push(field);
            }
        });

        return {
            valid: missing.length === 0,
            missing,
            data: obj
        };
    }
}
