(function() {
    'use strict';

    function construirEppsProcesados(datos, formData) {
        return (datos.epps || []).map((e, eppIndex) => {
            const imagenesExistentes = [];

            if (Array.isArray(e.imagenes)) {
                e.imagenes.forEach((img, imgIndex) => {
                    if (!img) return;

                    if (img instanceof File || (img.file && img.file instanceof File)) {
                        const file = img instanceof File ? img : img.file;
                        const fieldName = `epps.${eppIndex}.imagenes.${imgIndex}`;
                        formData.append(fieldName, file);
                        return;
                    }

                    let imageUrl = null;
                    if (typeof img === 'string') imageUrl = img;
                    else if (img.url) imageUrl = img.url;
                    else if (img.preview) imageUrl = img.preview;
                    else if (img.ruta_webp) imageUrl = img.ruta_webp;
                    else if (img.ruta) imageUrl = img.ruta;

                    if (imageUrl) {
                        imagenesExistentes.push(imageUrl);
                    }
                });
            }

            return {
                epp_id: e.epp_id,
                cantidad: e.cantidad,
                observaciones: e.observaciones,
                imagenes: imagenesExistentes
            };
        });
    }

    function construirNuevasPrendasYExistentes(formData) {
        const prendasExistentesJson = [];
        const nuevasPrendasJson = [];

        if (!window.gestionItemsUI || !Array.isArray(window.gestionItemsUI.prendas)) {
            return { prendasExistentesJson, nuevasPrendasJson };
        }

        let nuevaPrendaIdx = 0; // Contador independiente para nuevas prendas (el backend itera desde 0)

        window.gestionItemsUI.prendas.forEach((p, prendaIdx) => {
            const esPrendaExistente = !!(p?.prenda_pedido_id || p?.id);
            if (esPrendaExistente) {
                const payloadPrendaExistente = typeof window.serializarPrendaExistenteParaBorrador === 'function'
                    ? window.serializarPrendaExistenteParaBorrador(p, prendaIdx, formData)
                    : null;

                if (payloadPrendaExistente) {
                    prendasExistentesJson.push(payloadPrendaExistente);
                }
                return;
            }

            const imagenesArr = Array.isArray(p.imagenes) ? p.imagenes : [];
            let imgFileIdx = 0;
            imagenesArr.forEach((img) => {
                const file = (img instanceof File) ? img : (img && img.file instanceof File ? img.file : null);
                if (file) {
                    // Bracket notation so PHP builds nested $_FILES['nuevas_prendas'][i]['imagenes'][j]
                    // which Laravel can find via dot-notation: hasFile('nuevas_prendas.i.imagenes.j')
                    formData.append(`nuevas_prendas[${nuevaPrendaIdx}][imagenes][${imgFileIdx}]`, file);
                    imgFileIdx++;
                }
            });

            const telasArr = Array.isArray(p.telasAgregadas) ? p.telasAgregadas : (Array.isArray(p.telas) ? p.telas : []);
            telasArr.forEach((tela, telaIdx) => {
                let telaImgFileIdx = 0;
                const imagenesTelaArr = Array.isArray(tela.imagenes) ? tela.imagenes : [];
                imagenesTelaArr.forEach((imgTela) => {
                    const file = (imgTela instanceof File) ? imgTela : (imgTela && imgTela.file instanceof File ? imgTela.file : null);
                    if (file) {
                        formData.append(`nuevas_prendas[${nuevaPrendaIdx}][telas][${telaIdx}][imagenes][${telaImgFileIdx}]`, file);
                        telaImgFileIdx++;
                    }
                });
            });

            nuevasPrendasJson.push({
                tipo: 'prenda',
                nombre_prenda: p.nombre_prenda || p.nombre_producto || '',
                nombre_producto: p.nombre_producto || p.nombre_prenda || '',
                descripcion: p.descripcion || '',
                de_bodega: p.de_bodega !== undefined ? p.de_bodega : 1,
                genero: p.genero || '',
                cantidad_talla: p.cantidad_talla || p.cantidades || {},
                telas: telasArr.map(t => ({
                    tela: t.nombre_tela || t.tela || '',
                    color: t.color || t.color_nombre || '',
                    referencia: t.referencia || ''
                })),
                procesos: (typeof p.procesos === 'object' && p.procesos) ? p.procesos : {},
                asignacionesColoresPorTalla: p.asignacionesColoresPorTalla || {}
            });
            nuevaPrendaIdx++;
        });

        return { prendasExistentesJson, nuevasPrendasJson };
    }

    function construirFormDataBorrador(datos, csrfToken) {
        const formData = new FormData();
        const eppsProcesados = construirEppsProcesados(datos, formData);
        const { prendasExistentesJson, nuevasPrendasJson } = construirNuevasPrendasYExistentes(formData);

        const pedidoLimpio = {
            cliente: datos.cliente || '',
            asesora: datos.asesora || '',
            forma_de_pago: datos.forma_de_pago || '',
            observaciones: datos.observaciones || '',
            orden_compra: datos.orden_compra || document.getElementById('orden_compra_editable')?.value?.trim() || '',
            numero_cotizacion: datos.numero_cotizacion,
            es_sin_cotizacion: datos.es_sin_cotizacion,
            tipo_cotizacion: datos.tipo_cotizacion || null,
            logo: datos.logo || null,
            reflectivo: datos.reflectivo || null,
            prendas: [],
            prendas_existentes: prendasExistentesJson,
            nuevas_prendas: nuevasPrendasJson,
            epps: eppsProcesados
        };

        formData.append('pedido', JSON.stringify(pedidoLimpio));
        formData.append('_token', csrfToken);

        return {
            formData,
            pedidoLimpio
        };
    }

    window.DraftPedidoBuilder = {
        construirFormDataBorrador
    };
})();
