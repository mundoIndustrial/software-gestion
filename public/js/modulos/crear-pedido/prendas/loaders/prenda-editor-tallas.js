/**
 * Modulo de Tallas y Cantidades
 * Responsabilidad: cargar tarjetas de genero con inputs de tallas
 */

class PrendaEditorTallas {
    static TALLAS_DISPONIBLES = {
        DAMA: ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        CABALLERO: ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        UNISEX: ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        SOBREMEDIDA: []
    };

    static cargar(prenda) {
        const tallasNormalizadas = this._resolverTallas(prenda || {});
        const sobremedidaDetectada = this._resolverSobremedida(prenda || {}, tallasNormalizadas || {});

        const container = document.getElementById('tarjetas-generos-container');
        if (!container) {
            console.warn('[Tallas] No encontrado #tarjetas-generos-container');
            return;
        }

        container.innerHTML = '';
        console.log('[Tallas] Tarjetas limpias');

        if (!tallasNormalizadas) {
            return;
        }

        if (sobremedidaDetectada) {
            this._renderizarTarjetaSobremedida(sobremedidaDetectada.genero, sobremedidaDetectada.cantidad);
        }

        Object.entries(tallasNormalizadas).forEach(([genero, tallas]) => {
            const generoNormalizado = String(genero || '').toUpperCase();

            if (generoNormalizado === 'GENERICO') {
                console.log('[Tallas] Saltando GENERICO (se renderiza como UNISEX)');
                return;
            }

            if (!tallas || typeof tallas !== 'object' || Object.keys(tallas).length === 0) {
                console.log(`[Tallas] ${genero} sin datos`);
                return;
            }

            if (this._esSobremedidaGenero(generoNormalizado, tallas, sobremedidaDetectada)) {
                console.log(`[Tallas] ${generoNormalizado} renderizado como tarjeta especial de sobremedida`);
                return;
            }

            let tarjeta = container.querySelector(`[data-genero="${generoNormalizado}"]`);
            if (!tarjeta) {
                console.log(`[Tallas] Creando tarjeta de ${generoNormalizado}`);
                tarjeta = this._crearTarjeta(generoNormalizado, tallas);
                container.appendChild(tarjeta);
            }

            Object.entries(tallas).forEach(([talla, cantidad]) => {
                const input = tarjeta.querySelector(`input[data-talla="${talla}"]`);
                if (!input) return;

                input.value = cantidad || 0;

                const sincronizarInput = () => {
                    const nuevaCantidad = parseInt(input.value, 10) || 0;

                    if (!globalThis.tallasRelacionales) {
                        globalThis.tallasRelacionales = {};
                    }

                    if (!globalThis.tallasRelacionales[generoNormalizado]) {
                        globalThis.tallasRelacionales[generoNormalizado] = {};
                    }

                    if (nuevaCantidad > 0) {
                        globalThis.tallasRelacionales[generoNormalizado][talla] = nuevaCantidad;
                    } else {
                        delete globalThis.tallasRelacionales[generoNormalizado][talla];
                    }

                    console.log(`[Tallas] Actualizando ${generoNormalizado} - ${talla}: ${nuevaCantidad}`);
                    this._actualizarTotal();
                    this._sincronizarProcesosDesdeTallas('prenda-editor-tallas-cambio');
                };

                input.addEventListener('change', sincronizarInput);
                input.addEventListener('input', sincronizarInput);

                console.log(`[Tallas] ${generoNormalizado} - ${talla}: ${cantidad}`);
            });
        });

        this._actualizarTotal();

        globalThis.tallasRelacionales = JSON.parse(JSON.stringify(tallasNormalizadas));
        console.log('[Tallas] Tallas replicadas en globalThis.tallasRelacionales');
        this._sincronizarProcesosDesdeTallas('prenda-editor-tallas-inicial');
        console.log('[Tallas] Completado');
    }

    static _crearTarjeta(genero, tallasData = {}) {
        const tarjeta = document.createElement('div');
        tarjeta.setAttribute('data-genero', genero);
        tarjeta.style.cssText = 'background: white; border: 1px solid rgb(229, 231, 235); border-radius: 8px; padding: 1.5rem; margin-top: 1rem; box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px;';

        const icons = {
            DAMA: 'woman',
            CABALLERO: 'man',
            UNISEX: 'diversity_1',
            SOBREMEDIDA: 'straighten'
        };

        const header = document.createElement('div');
        header.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; justify-content: space-between;';

        const headerLeft = document.createElement('div');
        headerLeft.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
        headerLeft.innerHTML = `
            <span class="material-symbols-rounded" style="font-size: 1.5rem; color: #374151;">${icons[genero] || 'help'}</span>
            <h4 style="margin: 0; color: #1f2937; font-size: 1rem; font-weight: 600;">${genero}</h4>
        `;
        header.appendChild(headerLeft);

        const btnGroup = document.createElement('div');
        btnGroup.style.cssText = 'display: flex; gap: 0.25rem;';

        const btnEditar = document.createElement('button');
        btnEditar.type = 'button';
        btnEditar.title = 'Editar tallas';
        btnEditar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
        btnEditar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">edit</span>';
        btnEditar.onmouseover = () => {
            btnEditar.style.color = '#0066cc';
            btnEditar.style.background = '#f3f4f6';
        };
        btnEditar.onmouseout = () => {
            btnEditar.style.color = '#6b7280';
            btnEditar.style.background = 'transparent';
        };
        btnEditar.onclick = () => {
            console.log(`[PrendaEditorTallas] Editando tallas de ${genero}`);
            if (typeof abrirModalSeleccionarTallas === 'function') {
                abrirModalSeleccionarTallas(genero);
            }
        };
        btnGroup.appendChild(btnEditar);

        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.title = 'Eliminar tallas';
        btnEliminar.style.cssText = 'background: transparent; border: none; color: #6b7280; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border-radius: 6px;';
        btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>';
        btnEliminar.onmouseover = () => {
            btnEliminar.style.color = '#ef4444';
            btnEliminar.style.background = '#fee2e2';
        };
        btnEliminar.onmouseout = () => {
            btnEliminar.style.color = '#6b7280';
            btnEliminar.style.background = 'transparent';
        };
        btnEliminar.onclick = () => {
            console.log(`[PrendaEditorTallas] Eliminando tallas de ${genero}`);
            globalThis.tallasRelacionales[genero] = {};
            tarjeta.remove();

            const btnGenero = document.getElementById(`btn-genero-${genero}`);
            if (btnGenero) {
                btnGenero.dataset.selected = 'false';
                btnGenero.style.borderColor = '#d1d5db';
                btnGenero.style.background = 'white';
                btnGenero.style.color = '#1f2937';
            }

            this._actualizarTotal();
            this._sincronizarProcesosDesdeTallas('prenda-editor-tallas-cambio');
        };
        btnGroup.appendChild(btnEliminar);

        header.appendChild(btnGroup);

        const grid = document.createElement('div');
        grid.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;';

        Object.keys(tallasData).forEach((talla) => {
            const item = document.createElement('div');
            item.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem;';
            item.innerHTML = `
                <label style="font-size: 0.875rem; font-weight: 600; color: rgb(107, 114, 128); text-align: center;">${talla}</label>
                <input
                    type="number"
                    min="0"
                    data-talla="${talla}"
                    value="0"
                    style="padding: 0.5rem; border: 2px solid rgb(0, 102, 204); border-radius: 6px; text-align: center; font-weight: 600; font-size: 0.9rem;">
            `;
            grid.appendChild(item);
        });

        tarjeta.appendChild(header);
        tarjeta.appendChild(grid);

        return tarjeta;
    }

    static _actualizarTotal() {
        const totalSpan = document.getElementById('total-prendas');
        if (!totalSpan) return;

        let total = 0;
        const inputs = document.querySelectorAll('#tarjetas-generos-container input[type="number"]');
        inputs.forEach((input) => {
            const valor = parseInt(input.value, 10) || 0;
            if (valor > 0) {
                total += valor;
            }
        });

        const sobremedida = globalThis.tallasRelacionales?.SOBREMEDIDA;
        if (sobremedida && typeof sobremedida === 'object') {
            Object.values(sobremedida).forEach((cantidad) => {
                const valor = parseInt(cantidad, 10) || 0;
                if (valor > 0) {
                    total += valor;
                }
            });
        }

        totalSpan.textContent = total;
        console.log(`[Tallas] Total actualizado: ${total}`);
    }

    static _sincronizarProcesosDesdeTallas(origen = 'desconocido') {
        try {
            if (typeof globalThis.emitirCambioTallas === 'function') {
                globalThis.emitirCambioTallas(origen);
                return;
            }

            if (typeof globalThis.sincronizarTallasConTarjetasProcesos === 'function') {
                globalThis.sincronizarTallasConTarjetasProcesos(origen);
            }
        } catch (error) {
            console.error('[PrendaEditorTallas] Error sincronizando tallas con procesos:', error);
        }
    }

    static marcarGeneros(prenda) {
        const tallasData = this._resolverTallas(prenda || {});
        if (!tallasData) return;

        ['dama', 'caballero', 'sobremedida'].forEach((genero) => {
            const btn = document.getElementById(`btn-genero-${genero}`);
            if (btn) {
                btn.setAttribute('data-selected', 'false');
                btn.style.background = 'white';
                btn.style.borderColor = '#d1d5db';
            }
        });

        Object.entries(tallasData).forEach(([genero, tallas]) => {
            const tieneDatos = Object.values(tallas || {}).some((val) => parseInt(val, 10) > 0);
            if (!tieneDatos) return;

            const generoLower = genero.toLowerCase();
            const btn = document.getElementById(`btn-genero-${generoLower}`);
            if (btn) {
                btn.setAttribute('data-selected', 'true');
                btn.style.background = '#dbeafe';
                btn.style.borderColor = '#0369a1';
                console.log(`[Tallas] Genero ${genero} marcado`);
            }
        });
    }

    static _resolverTallas(prenda) {
        const fuentes = [
            prenda.tallasRelacionales,
            prenda.cantidad_talla,
            prenda.generosConTallas,
            prenda.tallas
        ];

        for (const fuente of fuentes) {
            const normalizada = this._normalizarFuenteTallas(fuente);
            if (normalizada) return normalizada;
        }

        return this._construirDesdeVariantes(prenda.variantes);
    }

    static _normalizarFuenteTallas(fuente) {
        if (!fuente || typeof fuente !== 'object') return null;
        if (Array.isArray(fuente)) return null;

        const resultado = {};
        Object.entries(fuente).forEach(([generoRaw, tallasRaw]) => {
            if (!tallasRaw || typeof tallasRaw !== 'object' || Array.isArray(tallasRaw)) return;

            const genero = String(generoRaw).toUpperCase().trim();
            const esSobremedida = Boolean(tallasRaw._es_sobremedida);
            resultado[genero] = {};

            Object.entries(tallasRaw).forEach(([tallaRaw, cantidadRaw]) => {
                if (String(tallaRaw).startsWith('_')) return;

                const cantidad = parseInt(cantidadRaw, 10) || 0;
                if (cantidad <= 0) return;

                const tallaNormalizada = this._normalizarClaveTalla(tallaRaw, esSobremedida);
                if (!tallaNormalizada) return;

                resultado[genero][tallaNormalizada] = cantidad;
            });

            if (Object.keys(resultado[genero]).length === 0) {
                delete resultado[genero];
            }
        });

        return Object.keys(resultado).length > 0 ? resultado : null;
    }

    static _construirDesdeVariantes(variantes) {
        if (!Array.isArray(variantes) || variantes.length === 0) return null;

        const resultado = {};
        variantes.forEach((v) => {
            const genero = String(v?.genero || 'DAMA').toUpperCase().trim();
            const talla = this._normalizarClaveTalla(v?.talla, Boolean(v?.es_sobremedida));
            const cantidad = parseInt(v?.cantidad, 10) || 0;

            if (!talla || cantidad <= 0) return;
            if (!resultado[genero]) resultado[genero] = {};

            resultado[genero][talla] = (resultado[genero][talla] || 0) + cantidad;
        });

        return Object.keys(resultado).length > 0 ? resultado : null;
    }

    static _normalizarClaveTalla(tallaRaw, esSobremedida = false) {
        const tallaTexto = tallaRaw == null ? '' : String(tallaRaw).trim();
        const tallaUpper = tallaTexto.toUpperCase();

        if (esSobremedida && (!tallaTexto || tallaUpper === 'NULL' || tallaUpper === 'SOBREMEDIDA')) {
            return 'SOBREMEDIDA';
        }

        if (!tallaTexto || tallaUpper === 'NULL' || tallaUpper === 'UNDEFINED') {
            return null;
        }

        return tallaTexto;
    }

    static _resolverSobremedida(prenda, tallasNormalizadas) {
        const variantes = Array.isArray(prenda?.variantes) ? prenda.variantes : [];
        const varianteSobremedida = variantes.find((variante) => Boolean(variante?.es_sobremedida) && (parseInt(variante?.cantidad, 10) || 0) > 0);
        if (varianteSobremedida) {
            return {
                genero: String(varianteSobremedida.genero || 'UNISEX').toUpperCase().trim(),
                cantidad: parseInt(varianteSobremedida.cantidad, 10) || 0
            };
        }

        if (tallasNormalizadas?.SOBREMEDIDA && typeof tallasNormalizadas.SOBREMEDIDA === 'object') {
            const [generoSobremedida, cantidadSobremedida] = Object.entries(tallasNormalizadas.SOBREMEDIDA).find(([, cantidad]) => (parseInt(cantidad, 10) || 0) > 0) || [];
            if (generoSobremedida) {
                return {
                    genero: String(generoSobremedida).toUpperCase().trim(),
                    cantidad: parseInt(cantidadSobremedida, 10) || 0
                };
            }
        }

        for (const [genero, tallas] of Object.entries(tallasNormalizadas || {})) {
            if (!tallas || typeof tallas !== 'object') continue;
            if (!Object.prototype.hasOwnProperty.call(tallas, 'SOBREMEDIDA')) continue;

            const cantidad = parseInt(tallas.SOBREMEDIDA, 10) || 0;
            if (cantidad > 0) {
                return { genero, cantidad };
            }
        }

        return null;
    }

    static _esSobremedidaGenero(genero, tallas, sobremedidaDetectada) {
        if (!sobremedidaDetectada) return false;
        if (String(genero).toUpperCase() === 'SOBREMEDIDA') return true;
        if (String(genero).toUpperCase() !== String(sobremedidaDetectada.genero).toUpperCase()) return false;

        const claves = Object.keys(tallas || {});
        return claves.length === 1 && String(claves[0]).toUpperCase() === 'SOBREMEDIDA';
    }

    static _renderizarTarjetaSobremedida(genero, cantidad) {
        if (typeof globalThis.crearTarjetaSobremedida === 'function') {
            globalThis.crearTarjetaSobremedida(genero, cantidad);
            return;
        }

        const btnSobremedida = document.getElementById('btn-genero-sobremedida');
        const checkMark = document.getElementById('check-sobremedida');
        if (btnSobremedida) {
            btnSobremedida.dataset.selected = 'true';
            btnSobremedida.style.borderColor = '#0066cc';
            btnSobremedida.style.background = '#f0f9ff';
        }
        if (checkMark) {
            checkMark.style.display = 'block';
        }

        const container = document.getElementById('tarjetas-generos-container');
        if (!container) return;

        const tarjetaAnterior = container.querySelector('[data-sobremedida="true"]');
        if (tarjetaAnterior) {
            tarjetaAnterior.remove();
        }

        const tarjeta = document.createElement('div');
        tarjeta.dataset.sobremedida = 'true';
        tarjeta.style.cssText = 'background: white; border: 2px solid rgb(0, 102, 204); border-radius: 8px; padding: 1rem; margin-top: 1rem; box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 3px;';

        tarjeta.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.25rem; color: #0066cc;">straighten</span>
                    <div>
                        <h4 style="margin: 0; color: #1f2937; font-size: 0.9rem; font-weight: 600;">SOBREMEDIDA</h4>
                        <p style="margin: 0; color: #6b7280; font-size: 0.75rem;">${genero}</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <button type="button" title="Eliminar sobremedida" data-action="eliminar-sobremedida" style="background: transparent; border: none; color: rgb(107, 114, 128); cursor: pointer; padding: 0.35rem; display: flex; align-items: center; justify-content: center; transition: 0.2s; border-radius: 4px; font-size: 1rem;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                    </button>
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: rgb(240, 249, 255); border-radius: 6px; padding: 0.75rem; border: 1px solid rgb(191, 219, 254);">
                <span class="material-symbols-rounded" style="font-size: 1.25rem; color: #0066cc;">shopping_bag</span>
                <div style="text-align: center;">
                    <p style="margin: 0; color: #6b7280; font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">Cantidad</p>
                    <p style="margin: 0; color: #0066cc; font-size: 1.5rem; font-weight: 700;">${cantidad}</p>
                </div>
            </div>
        `;

        const btnEliminar = tarjeta.querySelector('[data-action="eliminar-sobremedida"]');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => {
                if (globalThis.tallasRelacionales?.SOBREMEDIDA) {
                    delete globalThis.tallasRelacionales.SOBREMEDIDA;
                }
                tarjeta.remove();
                if (btnSobremedida) {
                    btnSobremedida.dataset.selected = 'false';
                    btnSobremedida.style.borderColor = '#d1d5db';
                    btnSobremedida.style.background = 'white';
                }
                if (checkMark) {
                    checkMark.style.display = 'none';
                }
                this._actualizarTotal();
                this._sincronizarProcesosDesdeTallas('prenda-editor-tallas-cambio');
            });
        }

        container.appendChild(tarjeta);
    }

    static limpiar() {
        const container = document.getElementById('tarjetas-generos-container');
        if (container) {
            container.innerHTML = '';
        }

        const totalSpan = document.getElementById('total-prendas');
        if (totalSpan) {
            totalSpan.textContent = '0';
        }
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorTallas;
}
