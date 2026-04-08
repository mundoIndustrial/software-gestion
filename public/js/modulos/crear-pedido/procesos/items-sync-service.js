/**
 * Servicio de sincronizacion de items (carga/agregado) extraido desde GestionItemsUI.
 */
class ItemsSyncService {
    constructor(options = {}) {
        this.ui = options.ui || null;
    }

    async cargarItems() {
        try {
            if (!this.ui?._tieneServiciosBase?.()) {
                return;
            }

            const resultado = await this.ui.apiService.obtenerItems();
            this.ui.items = resultado.items;

            await this.ui?._actualizarRenderItemsOrdenados?.();
        } catch (error) {
            if (this.ui?.notificationService) {
                this.ui.notificationService.error('Error al cargar ítems');
            }
            throw error;
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this.ui?._tieneServiciosBase?.()) {
                return false;
            }

            const resultado = await this.ui.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.ui.items = resultado.items;
                await this.ui?._actualizarRenderItemsOrdenados?.();
                this.ui.notificationService.exito('Ítem agregado correctamente');
                return true;
            }
        } catch (error) {
            if (this.ui?.notificationService) {
                this.ui.notificationService.error('Error: ' + error.message);
            }
            return false;
        }

        return false;
    }
}
