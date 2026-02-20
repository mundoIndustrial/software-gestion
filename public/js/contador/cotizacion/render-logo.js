(function () {
    if (typeof window === 'undefined') return;

    function renderLogoSection(payload) {
        let htmlLogo = '';

        if (payload.logo_cotizacion) {
            const logo = payload.logo_cotizacion;

            // Normalizar arrays que pueden venir como string o null
            const parseArray = (value) => {
                if (!value) return [];
                if (Array.isArray(value)) return value;
                if (typeof value === 'string') {
                    try {
                        const parsed = JSON.parse(value);
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (e) {
                        return [];
                    }
                }
                return [];
            };

            const secciones = parseArray(logo.secciones);

            htmlLogo += '<div style="display: flex; flex-direction: column; gap: 1rem;">';

            // Secciones / ubicaciones
            if (secciones && secciones.length > 0) {
                htmlLogo += `
                    <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                            Ubicaciones del Logo
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                `;

                secciones.forEach((seccion, idx) => {
                    htmlLogo += `
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 1rem;">
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">
                                ${idx + 1}. ${seccion.nombre || seccion.titulo || 'Sección'}
                            </p>
                    `;

                    if (seccion.descripcion) {
                        htmlLogo += `
                            <p style="margin: 0; color: #333; font-size: 0.9rem; white-space: pre-line;">
                                ${seccion.descripcion}
                            </p>
                        `;
                    }

                    if (seccion.observaciones) {
                        htmlLogo += `
                            <p style="margin: 0; color: #666; font-size: 0.85rem;">
                                <strong>Observaciones:</strong> ${seccion.observaciones}
                            </p>
                        `;
                    }

                    htmlLogo += `</div>`;
                });

                htmlLogo += `</div></div>`;
            }

            // Fotos del logo
            if (logo.fotos && Array.isArray(logo.fotos) && logo.fotos.length > 0) {
                const galleryIdLogo = `logo-fotos-${logo.id || 'cotizacion'}`;
                htmlLogo += `
                    <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                            Imágenes del Logo
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;">
                `;

                logo.fotos.forEach((foto, idx) => {
                    htmlLogo += `
                        <div style="position: relative;">
                            <img src="${foto.url}"
                                 data-gallery="${galleryIdLogo}"
                                 data-index="${idx}"
                                 alt="Logo"
                                 style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;"
                                 onclick="abrirImagenGrande('${foto.url}', '${galleryIdLogo}', ${idx})">
                            <span style="position: absolute; top: 2px; right: 2px; background: #1e5ba8; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">${foto.orden}</span>
                        </div>
                    `;
                });

                htmlLogo += `
                        </div>
                    </div>
                `;
            }

            htmlLogo += '</div>';
        } else {
            htmlLogo += '<p style="color: #999; text-align: center; padding: 2rem;">No hay información de logo para mostrar</p>';
        }

        return htmlLogo;
    }

    window.CotizacionModalRenderLogo = {
        renderLogoSection,
    };
})();
