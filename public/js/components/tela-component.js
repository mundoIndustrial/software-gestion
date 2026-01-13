/**
 * TelaComponent - Gestión de Telas en Prendas
 * Maneja agregar, eliminar y gestionar fotos de telas
 */

class TelaComponent {
    constructor() {
        // Usar window.telasFotosNuevas directamente para sincronización
        if (!window.telasFotosNuevas) window.telasFotosNuevas = {};
    }

    agregarFila(prendaIndex) {
        if (!window.prendasCargadas || !window.prendasCargadas[prendaIndex]) return;
        const prenda = window.prendasCargadas[prendaIndex];
        if (!prenda.variantes) prenda.variantes = {};
        if (!Array.isArray(prenda.variantes.telas_multiples)) prenda.variantes.telas_multiples = [];
        if (!Array.isArray(prenda.telas)) prenda.telas = [];
        prenda.variantes.telas_multiples.push({ nombre_tela: '', tela: '', color: '', referencia: '' });
        prenda.telas.push({ id: null, nombre_tela: '', color: '', referencia: '' });
        if (!window.telasFotosNuevas[prendaIndex]) window.telasFotosNuevas[prendaIndex] = {};
        if (typeof window.renderizarPrendas === 'function') window.renderizarPrendas();
    }

    async eliminarFila(prendaIndex, telaIndex) {
        const result = await Swal.fire({
            title: '¿Eliminar tela?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444'
        });
        if (!result.isConfirmed) return;
        if (!window.prendasCargadas || !window.prendasCargadas[prendaIndex]) return;
        const prenda = window.prendasCargadas[prendaIndex];
        if (Array.isArray(prenda.variantes?.telas_multiples)) prenda.variantes.telas_multiples.splice(telaIndex, 1);
        if (Array.isArray(prenda.telas)) prenda.telas.splice(telaIndex, 1);
        if (window.telasFotosNuevas && window.telasFotosNuevas[prendaIndex]) delete window.telasFotosNuevas[prendaIndex][telaIndex];
        if (typeof window.renderizarPrendas === 'function') window.renderizarPrendas();
        Swal.fire({ icon: 'success', title: 'Tela eliminada', timer: 1500, showConfirmButton: false });
    }

    abrirModalAgregarFotos(prendaIndex, telaIndex) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        input.addEventListener('change', (e) => this.manejarArchivos(e.target.files, prendaIndex, telaIndex));
        input.click();
    }

    async manejarArchivos(files, prendaIndex, telaIndex) {
        if (!files || files.length === 0) return;
        const esModoSinCot = window.gestorPrendaSinCotizacion && document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value === 'nuevo';
        let telaId = window.prendasCargadas?.[prendaIndex]?.telas?.[telaIndex]?.id || null;
        try {
            Swal.fire({ title: 'Subiendo imágenes...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const uploaded = [];
            for (const file of files) {
                if (!file.type.startsWith('image/')) continue;
                const img = await window.ImageService.uploadTelaImage(file, prendaIndex, telaIndex, telaId);
                uploaded.push(img);
            }
            if (uploaded.length === 0) throw new Error('No se pudo subir ninguna imagen');
            if (esModoSinCot) {
                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas) window.gestorPrendaSinCotizacion.telasFotosNuevas = {};
                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex] = {};
                if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex]) window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
                uploaded.forEach(img => window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex].push({ url: img.url, ruta_webp: img.ruta_webp, ruta_original: img.ruta_original, thumbnail: img.thumbnail, tela_id: img.tela_id, isNew: true }));
                
                // Re-renderizar sección de telas para mostrar las fotos
                const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                if (prenda && typeof window.renderizarTelasPrendaTipo === 'function') {
                    const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
                    if (container) {
                        const telasSection = container.querySelector('[data-section="telas"]');
                        if (telasSection) {
                            telasSection.innerHTML = window.renderizarTelasPrendaTipo(prenda, prendaIndex);
                            console.log('✅ Sección de telas re-renderizada con fotos nuevas');
                        }
                    }
                }
            } else {
                if (!window.telasFotosNuevas) window.telasFotosNuevas = {};
                if (!window.telasFotosNuevas[prendaIndex]) window.telasFotosNuevas[prendaIndex] = {};
                if (!window.telasFotosNuevas[prendaIndex][telaIndex]) window.telasFotosNuevas[prendaIndex][telaIndex] = [];
                uploaded.forEach(img => window.telasFotosNuevas[prendaIndex][telaIndex].push({ url: img.url, ruta_webp: img.ruta_webp, ruta_original: img.ruta_original, thumbnail: img.thumbnail, tela_id: img.tela_id, isNew: true }));
                if (typeof window.renderizarPrendas === 'function') window.renderizarPrendas();
            }
            Swal.close();
            window.ImageService.showSuccess('de tela subidas correctamente', uploaded.length);
        } catch (error) {
            window.ImageService.showError(error.message || 'Error al subir las imágenes');
        }
    }
}

window.TelaComponent = new TelaComponent();
