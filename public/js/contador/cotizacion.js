// ===== FUNCIONES PARA MODAL DE COTIZACIÓN =====

/**
 * Abre el modal de detalle de cotización
 * @param {number} cotizacionId - ID de la cotización
 */
function openCotizacionModal(cotizacionId) {
    console.log('🔄 Cargando cotización:', cotizacionId);

    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);

            // Actualizar header del modal con información de la cotización
            if (data.cotizacion) {
                const cot = data.cotizacion;
                document.getElementById('modalHeaderNumber').textContent = cot.numero_cotizacion || 'N/A';
                document.getElementById('modalHeaderDate').textContent = cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A';
                document.getElementById('modalHeaderClient').textContent = cot.nombre_cliente || 'N/A';
                document.getElementById('modalHeaderAdvisor').textContent = cot.asesora_nombre || 'N/A';
            }

            // Construir HTML del modal sin el encabezado (que ya está en el layout)
            let html = '';
            
            // Determinar si se necesitan tabs (cuando hay tanto prendas como logo)
            const tieneTabsNecesarios = data.tiene_prendas && data.tiene_logo;
            
            if (tieneTabsNecesarios) {
                // Crear estructura de tabs
                html += `
                    <div class="cotizacion-tabs-container">
                        <div class="cotizacion-tabs-header">
                            <button class="cotizacion-tab-button active" data-tab="prendas">
                                <span class="material-symbols-rounded" style="font-size: 20px; margin-right: 0.5rem;">checkroom</span>
                                PRENDAS
                            </button>
                            <button class="cotizacion-tab-button" data-tab="logo">
                                <span class="material-symbols-rounded" style="font-size: 20px; margin-right: 0.5rem;">image</span>
                                LOGO
                            </button>
                        </div>
                        <div class="cotizacion-tabs-content">
                            <div id="tab-prendas" class="cotizacion-tab-content active">
                                <!-- Contenido de prendas se insertará aquí -->
                            </div>
                            <div id="tab-logo" class="cotizacion-tab-content">
                                <!-- Contenido de logo se insertará aquí -->
                            </div>
                        </div>
                    </div>
                `;
            }

            // Construir contenido de prendas
            let htmlPrendas = '';

            // Contenedor de prendas
            htmlPrendas += '<div class="prendas-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';

            if (data.prendas_cotizaciones && data.prendas_cotizaciones.length > 0) {
                data.prendas_cotizaciones.forEach((prenda, index) => {
                    console.log('Renderizando prenda:', prenda);

                    // Construir atributos principales
                    let atributosLinea = [];

                    // Obtener color de variantes o telas
                    let color = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].color) {
                        color = prenda.variantes[0].color;
                    }

                    // Obtener tela de telas
                    let telaInfo = '';
                    if (prenda.telas && prenda.telas.length > 0) {
                        const tela = prenda.telas[0];
                        telaInfo = tela.nombre_tela || '';
                        if (tela.referencia) {
                            telaInfo += ` REF:${tela.referencia}`;
                        }
                    }

                    // Obtener manga de variantes
                    let manga = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].tipo_manga) {
                        manga = prenda.variantes[0].tipo_manga;
                    }

                    // Obtener manga de variantes
                    let manguaInfo = '';
                    if (prenda.variantes && prenda.variantes.length > 0) {
                        const variante = prenda.variantes[0];
                        if (variante.manga && variante.manga.nombre) {
                            manguaInfo = variante.manga.nombre;
                        }
                    }

                    if (color) atributosLinea.push(`Color: ${color}`);
                    if (telaInfo) atributosLinea.push(`Tela: ${telaInfo}`);
                    if (manguaInfo) atributosLinea.push(`Manga: ${manguaInfo}`);

                    // Construir HTML de la prenda
                    htmlPrendas += `
                        <div class="prenda-card" style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                ${prenda.nombre_prenda || 'Sin nombre'}
                            </h3>
                            <p style="margin: 0 0 0.75rem 0; color: #666; font-size: 0.9rem; font-weight: 500;">
                                ${atributosLinea.join(' | ') || ''}
                            </p>
                            <div style="margin: 0 0 1rem 0; color: #333; font-size: 0.85rem; line-height: 1.6;">
                                <span style="color: #1e5ba8; font-weight: 700;">DESCRIPCION:</span> ${(prenda.descripcion_formateada || prenda.descripcion || '-').replace(/\n/g, '<br>')}
                            </div>
                    `;

                    // Mostrar tallas si existen
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        const tallasTexto = prenda.tallas.map(t => t.talla).join(', ');
                        const textoPersonalizado = prenda.texto_personalizado_tallas ? ` ${prenda.texto_personalizado_tallas}` : '';
                        const textoCompleto = tallasTexto + textoPersonalizado;
                        
                        htmlPrendas += `
                            <div style="margin: 0 0 0.5rem 0;">
                                <span style="color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">Tallas: </span>
                                <span 
                                    id="tallas-prenda-${prenda.id}" 
                                    ondblclick="editarTallasPersonalizado(this, ${prenda.id}, '${tallasTexto}', '${prenda.texto_personalizado_tallas || ''}')"
                                    style="color: #ef4444; font-weight: 700; font-size: 0.9rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.2s; display: inline-block;"
                                    onmouseover="this.style.backgroundColor='#fee2e2'"
                                    onmouseout="this.style.backgroundColor='transparent'"
                                    title="Doble click para editar"
                                >${textoCompleto}</span>
                            </div>
                        `;
                    }

                    // Mostrar fotos de la prenda si existen
                    if (prenda.fotos && prenda.fotos.length > 0) {
                        htmlPrendas += `
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">
                                IMAGENES:
                            </p>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        `;
                        prenda.fotos.forEach((foto, idx) => {
                            htmlPrendas += `
                                <img src="${foto}" 
                                     data-gallery="prenda-fotos-${prenda.id}" 
                                     data-index="${idx}"
                                     alt="Foto prenda" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" 
                                     onclick="abrirImagenGrande('${foto}', 'prenda-fotos-${prenda.id}', ${idx})">
                            `;
                        });
                        htmlPrendas += `</div>`;
                    }

                    // Mostrar fotos de telas si existen
                    if (prenda.tela_fotos && prenda.tela_fotos.length > 0) {
                        htmlPrendas += `
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">
                                TELAS:
                            </p>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        `;
                        prenda.tela_fotos.forEach((foto, idx) => {
                            if (foto) {
                                htmlPrendas += `
                                    <img src="${foto}" 
                                         data-gallery="tela-fotos-${prenda.id}" 
                                         data-index="${idx}"
                                         alt="Foto tela" 
                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" 
                                         onclick="abrirImagenGrande('${foto}', 'tela-fotos-${prenda.id}', ${idx})">
                                `;
                            }
                        });
                        htmlPrendas += `</div>`;
                    }

                    htmlPrendas += `</div>`;
                });
            } else {
                htmlPrendas += '<p style="color: #999; text-align: center; padding: 2rem;">No hay prendas para mostrar</p>';
            }

            htmlPrendas += '</div>';

            // Agregar tabla de Especificaciones Generales
            if (data.cotizacion && data.cotizacion.especificaciones && Object.keys(data.cotizacion.especificaciones).length > 0) {
                const especificacionesMap = {
                    'disponibilidad': 'DISPONIBILIDAD',
                    'forma_pago': 'FORMA DE PAGO',
                    'regimen': 'RÉGIMEN',
                    'se_ha_vendido': 'SE HA VENDIDO',
                    'ultima_venta': 'ÚLTIMA VENTA',
                    'flete': 'FLETE DE ENVÍO'
                };

                htmlPrendas += `
                    <div style="margin-top: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">Especificaciones Generales</h3>
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #1e5ba8;">
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Especificación</th>
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Opciones Seleccionadas</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                for (const [clave, nombreCategoria] of Object.entries(especificacionesMap)) {
                    const valores = data.cotizacion.especificaciones[clave] || [];
                    let valoresText = '-';

                    if (Array.isArray(valores) && valores.length > 0) {
                        valoresText = valores.map(v => {
                            if (typeof v === 'object') {
                                return Object.values(v).join(', ');
                            }
                            return String(v);
                        }).join(', ');
                    } else if (typeof valores === 'string' && valores.trim() !== '') {
                        valoresText = valores;
                    }

                    htmlPrendas += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 0.75rem 1rem; color: #333; font-weight: 600; font-size: 0.85rem;">${nombreCategoria}</td>
                                    <td style="padding: 0.75rem 1rem; color: #666; font-size: 0.85rem;">${valoresText}</td>
                                </tr>
                    `;
                }

                htmlPrendas += `
                            </tbody>
                        </table>
                    </div>
                `;
            }


            // Construir contenido de logo
            let htmlLogo = '';
            if (data.logo_cotizacion) {
                const logo = data.logo_cotizacion;
                // Normalizar arrays que pueden venir como string o null
                const parseArray = (value) => {
                    if (!value) return [];
                    if (Array.isArray(value)) return value;
                    try {
                        const parsed = JSON.parse(value);
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (e) {
                        return [];
                    }
                };

                const tecnicas = parseArray(logo.tecnicas);
                const seccionesLogo = parseArray(logo.secciones || logo.ubicaciones);
                
                htmlLogo += '<div class="logo-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';
                
                // Descripción del logo
                if (logo.descripcion) {
                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                Descripción
                            </h3>
                            <p style="margin: 0; color: #333; font-size: 0.9rem; line-height: 1.6;">
                                ${logo.descripcion}
                            </p>
                        </div>
                    `;
                }
                
                // Técnicas utilizadas
                if (tecnicas.length > 0) {
                    const renderTecnica = (tecnica) => {
                        if (typeof tecnica === 'string') return tecnica;
                        if (typeof tecnica === 'object' && tecnica !== null) {
                            return tecnica.valor || tecnica.nombre || tecnica.tecnica || tecnica.tipo || Object.values(tecnica).join(' ');
                        }
                        return String(tecnica);
                    };

                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                                Técnicas
                            </h3>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                ${tecnicas.map(tecnica => `<span style="background: #1e5ba8; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">${renderTecnica(tecnica)}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }
                
                // Ubicaciones/Secciones
                if (seccionesLogo.length > 0) {
                    const renderOpcion = (opcion) => {
                        if (typeof opcion === 'string') return opcion;
                        if (typeof opcion === 'object' && opcion !== null) {
                            return opcion.nombre || opcion.valor || opcion.opcion || opcion.ubicacion || Object.values(opcion).join(' ');
                        }
                        return String(opcion);
                    };
                    const extraerTallas = (seccion) => {
                        if (!seccion) return [];
                        if (Array.isArray(seccion.tallas)) return seccion.tallas;
                        if (typeof seccion.tallas === 'string' && seccion.tallas.trim() !== '') {
                            // Intentar parsear JSON; si falla, usar split por comas
                            try {
                                const parsed = JSON.parse(seccion.tallas);
                                if (Array.isArray(parsed)) return parsed;
                            } catch (e) {
                                return seccion.tallas.split(',').map(t => t.trim()).filter(Boolean);
                            }
                        }
                        if (typeof seccion.tallas === 'object' && seccion.tallas !== null) return [seccion.tallas];
                        if (seccion.talla) return [seccion.talla];
                        if (seccion.tallas_texto) return [seccion.tallas_texto];
                        return [];
                    };

                    htmlLogo += `
                        <div style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 0.95rem; font-weight: 700; text-transform: uppercase;">
                                Secciones Prenda
                            </h3>
                    `;
                    
                    seccionesLogo.forEach((seccion, idx) => {
                        htmlLogo += `
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd;">
                                <p style="margin: 0 0 0.5rem 0; color: #333; font-weight: 700; font-size: 0.9rem;">
                                     ${seccion.ubicacion || seccion.seccion || 'Sin ubicación'}
                                </p>
                        `;
                        
                        if (seccion.opciones && Array.isArray(seccion.opciones) && seccion.opciones.length > 0) {
                            htmlLogo += `
                                <p style="margin: 0 0 0.25rem 0; color: #666; font-size: 0.85rem;">
                                    <strong>UBICACIONES:</strong> ${seccion.opciones.map(renderOpcion).join(', ')}
                                </p>
                            `;
                        }
                        
                        const tallasArray = extraerTallas(seccion);
                        if (tallasArray.length > 0) {
                            const tallasStr = tallasArray.map(t => {
                                if (typeof t === 'string') return t;
                                if (typeof t === 'object' && t !== null) return t.talla || t.valor || t.nombre || '';
                                return String(t);
                            }).filter(Boolean).join(', ');
                            htmlLogo += `
                                <p style="margin: 0 0 0.25rem 0; color: #666; font-size: 0.85rem;">
                                    <strong>Tallas:</strong> ${tallasStr}
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
                    
                    htmlLogo += `</div>`;
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

            // Insertar contenido en el modal
            if (tieneTabsNecesarios) {
                // Insertar HTML en tabs
                document.getElementById('modalBody').innerHTML = html;
                document.getElementById('tab-prendas').innerHTML = htmlPrendas;
                document.getElementById('tab-logo').innerHTML = htmlLogo;
                
                // Agregar event listeners a los tabs
                setTimeout(() => {
                    const tabButtons = document.querySelectorAll('.cotizacion-tab-button');
                    tabButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const tabName = this.getAttribute('data-tab');
                            
                            // Remover clase active de todos los botones
                            tabButtons.forEach(btn => btn.classList.remove('active'));
                            
                            // Agregar clase active al botón clickeado
                            this.classList.add('active');
                            
                            // Ocultar todos los contenidos
                            document.querySelectorAll('.cotizacion-tab-content').forEach(content => {
                                content.classList.remove('active');
                                content.style.display = 'none';
                            });
                            
                            // Mostrar el contenido del tab
                            const tabContent = document.getElementById(`tab-${tabName}`);
                            if (tabContent) {
                                tabContent.classList.add('active');
                                tabContent.style.display = 'block';
                            }
                        });
                    });
                }, 100);
            } else {
                // Sin tabs, insertar contenido normal
                if (data.tiene_prendas) {
                    html += htmlPrendas;
                } else if (data.tiene_logo) {
                    html += htmlLogo;
                }
                document.getElementById('modalBody').innerHTML = html;
            }

            document.getElementById('cotizacionModal').style.display = 'flex';

            console.log(' Modal abierto correctamente con', data.prendas_cotizaciones ? data.prendas_cotizaciones.length : 0, 'prendas y logo:', data.tiene_logo);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la cotización: ' + error.message);
        });
}

/**
 * Cierra el modal de cotización
 */
function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal de cotización (alias)
 */
function cerrarModalCotizacion() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal al hacer clic fuera del contenido
 */
document.addEventListener('click', function (event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

/**
 * Cierra el modal al presionar ESC
 */
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cotizacionModal');
        if (modal && modal.style.display === 'flex') {
            closeCotizacionModal();
        }
    }
});

/**
 * Elimina una cotización con confirmación
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function eliminarCotizacion(cotizacionId, cliente) {
    // Mostrar confirmación con SweetAlert
    Swal.fire({
        title: '¿Eliminar cotización completamente?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ¿Estás seguro de que deseas eliminar la cotización del cliente <strong>${cliente}</strong>?
                </p>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #92400e; font-weight: 600;">
                         Se eliminarán PERMANENTEMENTE:
                    </p>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.85rem; color: #92400e;">
                        <li><strong>Base de datos:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Registro de cotización</li>
                                <li>Todas las prendas relacionadas</li>
                                <li>Información de LOGO</li>
                                <li>Pedidos de producción asociados</li>
                                <li>Historial de cambios</li>
                            </ul>
                        </li>
                        <li style="margin-top: 0.5rem;"><strong>Servidor:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Carpeta: <code style="background: #fff3cd; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                <li>Todas las imágenes de prendas</li>
                                <li>Todas las imágenes de telas</li>
                                <li>Todas las imágenes de LOGO</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #ef4444; font-weight: 600;">
                     Esta acción NO se puede deshacer. Se eliminarán todos los datos y archivos.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar TODO',
        cancelButtonText: 'Cancelar',
        width: '550px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: `
                    <div style="text-align: left; color: #666;">
                        <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Por favor espera mientras se elimina:</p>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                            <li>Registros de la base de datos</li>
                            <li>Carpeta de imágenes del servidor</li>
                            <li>Todos los archivos relacionados</li>
                        </ul>
                    </div>
                `,
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Proceder con la eliminación
            fetch(`/contador/cotizacion/${cotizacionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '✓ Eliminado Completamente',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-weight: 600;"> Se eliminaron:</p>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                                    <li>Cotización de la base de datos</li>
                                    <li>Todas las prendas relacionadas</li>
                                    <li>Información de LOGO</li>
                                    <li>Pedidos de producción</li>
                                    <li>Historial de cambios</li>
                                    <li>Carpeta <code style="background: #f0f0f0; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                    <li>Todas las imágenes almacenadas</li>
                                </ul>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8'
                        }).then(() => {
                            // Recargar la página
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Aprueba la cotización directamente desde la tabla (sin abrir modal)
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} estadoActual - Estado actual de la cotización (opcional)
 */
function aprobarCotizacionEnLinea(cotizacionId, estadoActual = null) {
    // Determinar el mensaje y la ruta según el estado
    let mensaje = '¿Estás seguro de que deseas aprobar esta cotización?';
    let infoAdicional = 'La cotización será enviada al área de Aprobación de Cotizaciones';
    let ruta = `/cotizaciones/${cotizacionId}/aprobar-contador`;
    
    // Si el estado es APROBADA_POR_APROBADOR, usar la ruta para aprobar para pedido
    if (estadoActual === 'APROBADA_POR_APROBADOR') {
        infoAdicional = 'La cotización cambiará a estado APROBADO PARA PEDIDO';
        ruta = `/cotizaciones/${cotizacionId}/aprobar-para-pedido`;
    }
    
    // Mostrar confirmación
    Swal.fire({
        title: '¿Aprobar cotización?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ${mensaje}
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         ${infoAdicional}
                    </p>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        width: '450px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Aprobando cotización...',
                html: 'Por favor espera mientras se procesa la aprobación',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación
            fetch(ruta, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar todas las filas en la tabla de Pendientes
                        const filas = document.querySelectorAll('#pedidos-section tbody tr');

                        filas.forEach(fila => {
                            // Buscar si esta fila contiene el botón de aprobar para esta cotización
                            const boton = fila.querySelector(`button[onclick*="aprobarCotizacionEnLinea(${cotizacionId})"]`);

                            if (boton) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#pedidos-section tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 2rem; color: #999;">No hay cotizaciones pendientes</td></tr>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Cotización Aprobada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada correctamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha enviado notificación al área de Aprobación de Cotizaciones
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Enviado a Aprobador
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo aprobar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al aprobar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Abre una imagen en grande en un modal
 * @param {string} imagenUrl - URL de la imagen
 */
let galeriaActual = [];
let indiceActualGaleria = 0;
let galeriaIdActual = null;

function abrirImagenGrande(imagenUrl, galleryId = null, index = 0) {
    // Preparar galería si viene un grupo
    if (galleryId) {
        galeriaIdActual = galleryId;
        const imgs = document.querySelectorAll(`img[data-gallery="${galleryId}"]`);
        galeriaActual = Array.from(imgs).map(img => img.getAttribute('src'));
        indiceActualGaleria = Number(index) || 0;
    } else {
        galeriaIdActual = null;
        galeriaActual = [imagenUrl];
        indiceActualGaleria = 0;
    }

    // Crear modal dinámicamente si no existe
    let modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) {
        modalImagen = document.createElement('div');
        modalImagen.id = 'modalImagenGrande';
        modalImagen.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        modalImagen.innerHTML = `
            <div style="position: relative; width: 90vw; height: 90vh; max-width: 1200px; max-height: 800px; display: flex; align-items: center; justify-content: center;">
                <button id="cerrarImagenGrandeBtn" aria-label="Cerrar" style="position: absolute; top: -50px; right: 0; background: #fff; border: none; font-size: 1.4rem; cursor: pointer; color: #111; z-index: 10001; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
                    ✕
                </button>
                <button id="imagenAnteriorBtn" aria-label="Anterior" style="position: absolute; left: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">◀</button>
                <img id="imagenGrandeContent" src="" alt="Imagen ampliada" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <button id="imagenSiguienteBtn" aria-label="Siguiente" style="position: absolute; right: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">▶</button>
            </div>
        `;
        document.body.appendChild(modalImagen);

        // Eventos de botones
        modalImagen.querySelector('#cerrarImagenGrandeBtn').addEventListener('click', cerrarImagenGrande);
        modalImagen.querySelector('#imagenAnteriorBtn').addEventListener('click', mostrarAnteriorImagen);
        modalImagen.querySelector('#imagenSiguienteBtn').addEventListener('click', mostrarSiguienteImagen);
    }

    actualizarImagenGrande();
    modalImagen.style.display = 'flex';
}

function actualizarImagenGrande() {
    const modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) return;

    const img = modalImagen.querySelector('#imagenGrandeContent');
    img.src = galeriaActual[indiceActualGaleria] || '';

    const btnPrev = modalImagen.querySelector('#imagenAnteriorBtn');
    const btnNext = modalImagen.querySelector('#imagenSiguienteBtn');

    if (galeriaActual.length > 1) {
        btnPrev.style.display = 'flex';
        btnNext.style.display = 'flex';
    } else {
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
    }
}

function mostrarAnteriorImagen() {
    if (!galeriaActual.length) return;
    indiceActualGaleria = (indiceActualGaleria - 1 + galeriaActual.length) % galeriaActual.length;
    actualizarImagenGrande();
}

function mostrarSiguienteImagen() {
    if (!galeriaActual.length) return;
    indiceActualGaleria = (indiceActualGaleria + 1) % galeriaActual.length;
    actualizarImagenGrande();
}

/**
 * Cierra el modal de imagen grande
 */
function cerrarImagenGrande() {
    const modal = document.getElementById('modalImagenGrande');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función para aprobar cotización al aprobador (desde vista aprobadas)
function aprobarAlAprobador(cotizacionId) {
    // Mostrar confirmación
    Swal.fire({
        title: '¿Enviar al Asesor?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    Esta es la aprobación final del proceso. La cotización será enviada de vuelta al asesor para que pueda proceder con la venta.
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         Una vez aprobada, la cotización estará lista para presentarse al cliente
                    </p>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                    <strong>¿Estás seguro de que deseas proceder?</strong>
                </p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, Enviar al Asesor',
        cancelButtonText: 'Cancelar',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando al Asesor...',
                html: 'Por favor espera mientras se procesa',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación al aprobador
            fetch(`/cotizaciones/${cotizacionId}/aprobar-aprobador`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar la fila en la tabla de Aprobadas
                        const filas = document.querySelectorAll('#aprobadas-section .table-row');

                        filas.forEach(fila => {
                            const rowId = fila.getAttribute('data-cotizacion-id');
                            if (rowId == cotizacionId) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#aprobadas-section .table-body');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<div style="padding: 40px; text-align: center; color: #9ca3af;"><p>No hay cotizaciones aprobadas</p></div>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Aprobación Completada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada exitosamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha notificado al asesor
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Lista para hacer pedido
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo enviar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al procesar la solicitud',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

// Cerrar modal de imagen al hacer clic fuera
document.addEventListener('click', function (event) {
    const modal = document.getElementById('modalImagenGrande');
    if (modal && event.target === modal) {
        cerrarImagenGrande();
    }
});

// Cerrar modal de imagen al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarImagenGrande();
    }
});
