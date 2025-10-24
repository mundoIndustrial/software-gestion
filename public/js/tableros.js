function tablerosApp() {
    return {
        activeTab: 'produccion',
        showRecords: false,

        setActiveTab(tab) {
            this.activeTab = tab;
            this.showRecords = false; // Reset when changing tabs
        },

        toggleRecords() {
            this.showRecords = !this.showRecords;
        },

        openFormModal() {
            document.getElementById('activeSection').value = this.activeTab;
            const modalTitle = document.getElementById('modalTitle');
            if (this.activeTab === 'produccion') {
                modalTitle.textContent = 'Registro Control de Piso Producción';
            } else if (this.activeTab === 'polos') {
                modalTitle.textContent = 'Registro Control de Piso Polos';
            }
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'tableros-form' }));
        }
    }
}

// Función para actualizar la tabla de registros después de guardar
function actualizarRegistros() {
    // Recargar la página para mostrar los nuevos registros
    location.reload();
}
