/**
 * GaleriaService - Servicio de galerías modales
 * 
 * Responsabilidad: Abrir y gestionar galerías modales de imágenes
 * Patrón: Strategy + Observer
 */

class GaleriaService {
    /**
     * Abrir galería de fotos de prenda
     * @param {Object} prenda - Prenda con imágenes
     * @param {number} prendaIndex - Índice de la prenda
     */
    static abrirGaleriaFotos(prenda, prendaIndex) {
        console.log('[GaleriaService] Abriendo galería de fotos:', prendaIndex);

        const imagenes = prenda.imagenes || prenda.fotos || [];
        const fotosUrls = ImageProcessor.procesarImagenes(imagenes);

        if (fotosUrls.length === 0) {
            Swal.fire({
                title: '📷 Sin fotos',
                html: '<p style="color: #666;">Esta prenda no tiene fotos cargadas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        this._mostrarGaleria(fotosUrls, prenda.nombre_producto, prendaIndex);
    }

    /**
     * Abrir galería de fotos de telas
     * @param {Object} prenda - Prenda con telas
     * @param {number} prendaIndex - Índice de la prenda
     */
    static abrirGaleriaTelas(prenda, prendaIndex) {
        console.log('[GaleriaService] Abriendo galería de telas:', prendaIndex);

        const telas = prenda.telasAgregadas || [];
        const telasConFotos = [];

        telas.forEach((tela, telaIdx) => {
            const fotos = tela.imagenes || [];
            fotos.forEach((foto, fotoIdx) => {
                telasConFotos.push({
                    telaIdx,
                    fotoIdx,
                    url: ImageProcessor.procesarImagen(foto),
                    tela: tela.tela || 'N/A',
                    color: tela.color || 'N/A'
                });
            });
        });

        if (telasConFotos.length === 0) {
            Swal.fire({
                title: ' Sin fotos de telas',
                html: '<p style="color: #666;">No hay fotos de telas configuradas</p>',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0ea5e9'
            });
            return;
        }

        this._mostrarGaleriaTelas(telasConFotos, prenda.nombre_producto, prendaIndex);
    }

    /**
     * Mostrar galería modal
     * @private
     */
    static _mostrarGaleria(fotosUrls, nombrePrenda, prendaIndex) {
        let indiceActual = 0;

        const generarContenido = (idx) => {
            const foto = fotosUrls[idx];
            return `
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                    <img 
                        src="${foto}" 
                        alt="Foto ${idx + 1}" 
                        style="max-width: 400px; max-height: 400px; object-fit: contain; border-radius: 8px; border: 2px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.1);"
                    />
                    <div style="text-align: center; color: #666; font-size: 0.9rem;">
                        Foto ${idx + 1} de ${fotosUrls.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: `📷 ${nombrePrenda}`,
            html: generarContenido(indiceActual),
            width: '600px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            didOpen: () => {
                const modal = Swal.getPopup();
                const btnAnterior = document.createElement('button');
                const btnSiguiente = document.createElement('button');

                btnAnterior.innerHTML = '❮ Anterior';
                btnSiguiente.innerHTML = 'Siguiente ❯';

                btnAnterior.style.cssText = 'margin: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;';
                btnSiguiente.style.cssText = 'margin: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;';

                btnAnterior.onclick = () => {
                    indiceActual = (indiceActual - 1 + fotosUrls.length) % fotosUrls.length;
                    Swal.update({ html: generarContenido(indiceActual) });
                };

                btnSiguiente.onclick = () => {
                    indiceActual = (indiceActual + 1) % fotosUrls.length;
                    Swal.update({ html: generarContenido(indiceActual) });
                };

                const footer = modal.querySelector('.swal2-footer') || document.createElement('div');
                footer.innerHTML = '';
                footer.appendChild(btnAnterior);
                footer.appendChild(btnSiguiente);
                
                if (!modal.querySelector('.swal2-footer')) {
                    modal.appendChild(footer);
                }
            }
        });
    }

    /**
     * Mostrar galería de telas
     * @private
     */
    static _mostrarGaleriaTelas(telasConFotos, nombrePrenda, prendaIndex) {
        let indiceActual = 0;

        const generarContenido = (idx) => {
            const item = telasConFotos[idx];
            return `
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                    <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; width: 100%; text-align: center;">
                        <p style="margin: 0; color: #0369a1; font-weight: 700;">
                            <i class="fas fa-palette" style="margin-right: 0.5rem;"></i>${item.tela} - ${item.color}
                        </p>
                    </div>
                    <img 
                        src="${item.url}" 
                        alt="Tela ${item.tela}" 
                        style="max-width: 400px; max-height: 400px; object-fit: contain; border-radius: 8px; border: 2px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.1);"
                    />
                    <div style="text-align: center; color: #666; font-size: 0.9rem;">
                        Foto ${idx + 1} de ${telasConFotos.length}
                    </div>
                </div>
            `;
        };

        Swal.fire({
            title: ` Telas - ${nombrePrenda}`,
            html: generarContenido(indiceActual),
            width: '600px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0ea5e9',
            didOpen: () => {
                const modal = Swal.getPopup();
                const btnAnterior = document.createElement('button');
                const btnSiguiente = document.createElement('button');

                btnAnterior.innerHTML = '❮ Anterior';
                btnSiguiente.innerHTML = 'Siguiente ❯';

                btnAnterior.style.cssText = 'margin: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;';
                btnSiguiente.style.cssText = 'margin: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;';

                btnAnterior.onclick = () => {
                    indiceActual = (indiceActual - 1 + telasConFotos.length) % telasConFotos.length;
                    Swal.update({ html: generarContenido(indiceActual) });
                };

                btnSiguiente.onclick = () => {
                    indiceActual = (indiceActual + 1) % telasConFotos.length;
                    Swal.update({ html: generarContenido(indiceActual) });
                };

                const footer = modal.querySelector('.swal2-footer') || document.createElement('div');
                footer.innerHTML = '';
                footer.appendChild(btnAnterior);
                footer.appendChild(btnSiguiente);
                
                if (!modal.querySelector('.swal2-footer')) {
                    modal.appendChild(footer);
                }
            }
        });
    }
}

window.GaleriaService = GaleriaService;
console.log('✓ [GALERIA-SERVICE] Cargado correctamente');
