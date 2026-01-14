/**
 * TALLAS - Gestión de tallas en Prenda Sin Cotización
 * 
 * Funciones para:
 * - Agregar tallas
 * - Eliminar tallas
 */

/**
 * Agregar una talla a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.agregarTallaPrendaTipo = function(prendaIndex) {
    Swal.fire({
        title: 'Seleccionar Talla',
        html: `
            <select id="select-talla" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
                <option value="">-- Seleccionar Talla --</option>
                <option value="XS">XS</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
                <option value="XXXL">XXXL</option>
                <option value="2">2</option>
                <option value="4">4</option>
                <option value="6">6</option>
                <option value="8">8</option>
                <option value="10">10</option>
                <option value="12">12</option>
                <option value="14">14</option>
                <option value="16">16</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: (modal) => {
            document.getElementById('select-talla').focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const talla = document.getElementById('select-talla').value;
            if (talla) {
                window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
                window.renderizarPrendasTipoPrendaSinCotizacion();
                Swal.fire('Éxito', `Talla ${talla} agregada`, 'success');
            } else {
                Swal.fire('Error', 'Seleccione una talla', 'error');
            }
        }
    });
};

/**
 * Eliminar una talla de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a eliminar
 */
window.eliminarTallaPrendaTipo = function(prendaIndex, talla) {
    Swal.fire({
        title: '¿Eliminar Talla?',
        text: `¿Está seguro que desea eliminar la talla ${talla}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', `La talla ${talla} ha sido eliminada`, 'success');
        }
    });
};

console.log('✅ [TALLAS] Componente prenda-sin-cotizacion-tallas.js cargado');
