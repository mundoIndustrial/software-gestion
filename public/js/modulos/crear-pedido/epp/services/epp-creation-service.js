/**
 * EppCreationService - Gestiona la creación de nuevos EPPs
 * Patrón: Service Layer
 * Responsabilidad: Lógica de creación de EPP, validación y actualización de UI
 */

class EppCreationService {
    constructor(apiService, notificationService) {
        this.apiService = apiService;
        this.notificationService = notificationService;
    }

    /**
     * Crear nuevo EPP
     */
    async crearEPP(datos) {
        try {


            // Validar campos obligatorios
            if (!datos.nombre?.trim()) {
                throw new Error('Por favor ingresa el nombre del EPP');
            }
            if (!datos.categoria?.trim()) {
                throw new Error('Por favor selecciona la categoría');
            }
            if (!datos.codigo?.trim()) {
                throw new Error('Por favor ingresa el código del EPP');
            }

            // Mostrar modal de cargando
            this.notificationService.mostrarCargando('Creando EPP', 'Por favor espera...');

            // Crear EPP via API
            const response = await fetch('/api/epp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    nombre: datos.nombre,
                    categoria: datos.categoria,
                    codigo: datos.codigo,
                    descripcion: datos.descripcion || ''
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Error al crear EPP');
            }

            const eppNuevo = await response.json();


            // Actualizar modal a éxito
            this.notificationService.actualizarAExito(
                'EPP creado exitosamente',
                'Ahora puedes agregar talla, cantidad e imágenes.'
            );

            return {
                id: eppNuevo.id || eppNuevo.data?.id,
                nombre: datos.nombre,
                codigo: datos.codigo,
                categoria: datos.categoria
            };

        } catch (error) {

            this.notificationService.actualizarAError('Error al crear EPP', error.message);
            throw error;
        }
    }

    /**
     * Actualizar UI después de crear EPP
     */
    actualizarUIPostCreacion(producto) {
        try {
            document.getElementById('nombreProductoEPP').textContent = producto.nombre;
            document.getElementById('categoriaProductoEPP').textContent = producto.categoria;
            document.getElementById('codigoProductoEPP').textContent = producto.codigo;
            document.getElementById('productoCardEPP').style.display = 'flex';

            // Habilitar campos de cantidad y observaciones
            this._habilitarCampos([
                'cantidadEPP',
                'observacionesEPP'
            ]);

            // Mostrar área de carga de imágenes
            document.getElementById('areaCargarImagenes').style.display = 'block';
            document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
            document.getElementById('listaImagenesSubidas').style.display = 'none';
            document.getElementById('contenedorImagenesSubidas').innerHTML = '';


        } catch (error) {

        }
    }

    /**
     * Habilitar campos
     */
    _habilitarCampos(ids) {
        ids.forEach(id => {
            const elem = document.getElementById(id);
            if (elem) {
                elem.disabled = false;
                elem.style.background = 'white';
                elem.style.color = '#1f2937';
                elem.style.cursor = 'text';
            }
        });
    }
}

// Exportar instancia global
window.eppCreationService = null; // Se inicializa en epp-init.js
