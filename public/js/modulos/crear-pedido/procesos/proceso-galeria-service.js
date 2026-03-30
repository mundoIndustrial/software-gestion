/**
 * Servicio de galeria para imagenes de procesos.
 * Mantiene API global para compatibilidad.
 */
(function() {
    'use strict';

    function agregarStorageUrl(url) {
        if (!url || typeof url !== 'string') return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
        if (url.startsWith('blob:')) return url;
        if (url.startsWith('data:')) return url;
        return '/storage/' + url;
    }

    function resolverUrlImagenProceso(img) {
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        if (img?.previewUrl) {
            return img.previewUrl;
        }
        if (img?.dataURL) {
            return img.dataURL;
        }
        if (typeof img === 'string') {
            return agregarStorageUrl(img);
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            return (typeof url === 'string') ? agregarStorageUrl(url) : '';
        }
        return '';
    }

    const ProcesoGaleriaService = {
        abrir(tipoProceso) {
            console.log('[GALERIA] Abriendo galeria para proceso:', tipoProceso);

            const proceso = window.procesosSeleccionados?.[tipoProceso];
            if (!proceso?.datos?.imagenes || proceso.datos.imagenes.length === 0) {
                console.error('[GALERIA] No hay imagenes para mostrar en proceso:', tipoProceso);
                return;
            }

            const imagenes = proceso.datos.imagenes;
            const galeria = document.createElement('div');
            galeria.id = 'galeria-proceso-modal';
            galeria.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); z-index: 999999999; display: flex; flex-direction: column; align-items: center; justify-content: center;';

            const urlPrimeraImagen = resolverUrlImagenProceso(imagenes[0]);
            galeria.innerHTML = `
                <div style="position: absolute; top: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); padding: 1rem; display: flex; justify-content: space-between; align-items: center; z-index: 999999999;">
                    <div style="color: white; font-size: 1rem; font-weight: 600;">
                        <i class="fas fa-images" style="margin-right: 0.5rem;"></i>
                        Galeria - ${tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1)}
                    </div>
                    <div style="color: white; font-size: 0.9rem;"><span id="galeria-contador">1</span> / ${imagenes.length}</div>
                    <button onclick="cerrarGaleriaImagenesProceso()" style="background: #dc2626; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; cursor: pointer;">×</button>
                </div>
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 4rem 2rem 2rem 2rem; width: 100%;">
                    <img id="galeria-imagen-actual" src="${urlPrimeraImagen}" style="max-width: 85vw; max-height: 80vh; border-radius: 8px; object-fit: contain;">
                </div>
                ${imagenes.length > 1 ? `
                    <button onclick="navegarGaleriaImagenesProceso(-1)" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">‹</button>
                    <button onclick="navegarGaleriaImagenesProceso(1)" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 2rem; cursor: pointer;">›</button>
                    <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; padding: 0.75rem; background: rgba(0,0,0,0.6); border-radius: 8px;">
                        ${imagenes.map((img, idx) => {
                            const urlMiniatura = resolverUrlImagenProceso(img);
                            return `<img src="${urlMiniatura}" onclick="irAImagenProceso(${idx})" class="miniatura-galeria-proceso" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${idx === 0 ? '#0ea5e9' : 'transparent'}; opacity: ${idx === 0 ? '1' : '0.6'};">`;
                        }).join('')}
                    </div>
                ` : ''}
            `;

            galeria.dataset.indiceActual = '0';
            window.imagenesGaleriaProceso = imagenes;
            document.body.appendChild(galeria);
        },

        navegar(direccion) {
            const galeria = document.getElementById('galeria-proceso-modal');
            if (!galeria || !window.imagenesGaleriaProceso) return;

            let indice = parseInt(galeria.dataset.indiceActual, 10) + direccion;
            if (indice < 0) indice = window.imagenesGaleriaProceso.length - 1;
            if (indice >= window.imagenesGaleriaProceso.length) indice = 0;

            galeria.dataset.indiceActual = indice;

            const img = window.imagenesGaleriaProceso[indice];
            const imgElement = document.getElementById('galeria-imagen-actual');
            if (imgElement) {
                imgElement.src = resolverUrlImagenProceso(img);
            }

            const contador = document.getElementById('galeria-contador');
            if (contador) contador.textContent = indice + 1;

            document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
                m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
                m.style.opacity = i === indice ? '1' : '0.6';
            });
        },

        irAImagen(indice) {
            const galeria = document.getElementById('galeria-proceso-modal');
            if (!galeria || !window.imagenesGaleriaProceso) return;

            galeria.dataset.indiceActual = indice;

            const img = window.imagenesGaleriaProceso[indice];
            const imgElement = document.getElementById('galeria-imagen-actual');
            if (imgElement) {
                imgElement.src = resolverUrlImagenProceso(img);
            }

            const contador = document.getElementById('galeria-contador');
            if (contador) contador.textContent = indice + 1;

            document.querySelectorAll('.miniatura-galeria-proceso').forEach((m, i) => {
                m.style.border = i === indice ? '2px solid #0ea5e9' : '2px solid transparent';
                m.style.opacity = i === indice ? '1' : '0.6';
            });
        },

        cerrar() {
            const galeria = document.getElementById('galeria-proceso-modal');
            if (galeria) {
                galeria.remove();
            }
            window.imagenesGaleriaProceso = null;
        }
    };

    window.ProcesoGaleriaService = ProcesoGaleriaService;
    window.abrirGaleriaImagenesProceso = (tipo) => ProcesoGaleriaService.abrir(tipo);
    window.navegarGaleriaImagenesProceso = (direccion) => ProcesoGaleriaService.navegar(direccion);
    window.irAImagenProceso = (indice) => ProcesoGaleriaService.irAImagen(indice);
    window.cerrarGaleriaImagenesProceso = () => ProcesoGaleriaService.cerrar();
})();
