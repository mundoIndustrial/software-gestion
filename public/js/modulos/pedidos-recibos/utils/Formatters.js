/**
 * Formatters.js
 * Formatea descripciones de prendas y datos para los recibos
 */

export class Formatters {
    /**
     * Construir descripción de COSTURA dinámicamente
     * Formato especializado para recibos de costura
     */
    static construirDescripcionCostura(prenda) {

        
        const lineas = [];

        // 1. Nombre de la prenda
        if (prenda.nombre) {
            const numeroPrenda = prenda.numero || 1;
            lineas.push(`<strong style="font-size: 13.4px;">PRENDA ${numeroPrenda}: ${prenda.nombre.toUpperCase()}</strong>`);
        }

        // 2. Línea técnica
        const partes = [];
        if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        
        // Manga desde variantes
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            if (primerVariante.manga) {
                let mangaTexto = primerVariante.manga.toUpperCase();
                if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                    mangaTexto += ` (${primerVariante.manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
            }
        }
        
        if (partes.length > 0) {
            lineas.push(partes.join(' | '));
        }

        // 3. Descripción base
        if (prenda.descripcion && prenda.descripcion.trim()) {
            lineas.push(prenda.descripcion.toUpperCase());
        }

        // 4. Detalles técnicos
        const detalles = [];
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            
            if (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim()) {
                detalles.push(`• <strong>BOLSILLOS:</strong> ${primerVariante.bolsillos_obs.toUpperCase()}`);
            }
            
            if (primerVariante.broche_obs && primerVariante.broche_obs.trim()) {
                let etiqueta = 'BROCHE/BOTÓN';
                if (primerVariante.broche) {
                    etiqueta = primerVariante.broche.toUpperCase();
                }
                detalles.push(`• <strong>${etiqueta}:</strong> ${primerVariante.broche_obs.toUpperCase()}`);
            }
        }
        
        if (detalles.length > 0) {
            detalles.forEach((detalle) => {
                lineas.push(detalle);
            });
        }

        // 5. Tallas
        if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            lineas.push('');
            lineas.push('<strong>TALLAS</strong>');
            this._agregarTallasFormato(lineas, prenda.tallas, prenda.genero);
        }

        return lineas.join('<br>') || '<em>Sin información</em>';
    }

    /**
     * Construir descripción de PROCESO (bordado, estampado, dtf, etc.)
     * Formato específico para procesos productivos
     */
    static construirDescripcionProceso(prenda, proceso) {

        
        const lineas = [];

        // 1. Nombre de la prenda
        if (prenda.nombre) {
            lineas.push(`<strong style="font-size: 13.4px;">${prenda.nombre.toUpperCase()}</strong>`);
        }

        // 2. Línea técnica
        const partes = [];
        if (prenda.tela) partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        if (prenda.color) partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        if (prenda.ref) partes.push(`<strong>REF:</strong> ${prenda.ref.toUpperCase()}`);
        
        // Manga desde variantes
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            if (primerVariante.manga) {
                let mangaTexto = primerVariante.manga.toUpperCase();
                if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                    mangaTexto += ` (${primerVariante.manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
            }
        }
        
        if (partes.length > 0) {
            lineas.push(partes.join(' | '));
        }

        // 3. Ubicaciones
        if (proceso.ubicaciones && Array.isArray(proceso.ubicaciones) && proceso.ubicaciones.length > 0) {
            lineas.push('');
            lineas.push('<strong>UBICACIONES:</strong>');
            proceso.ubicaciones.forEach((ubicacion) => {
                lineas.push(`• ${ubicacion.toUpperCase()}`);
            });
        }

        // 4. Observaciones
        if (proceso.observaciones && proceso.observaciones.trim()) {
            lineas.push('');
            lineas.push('<strong>OBSERVACIONES:</strong>');
            lineas.push(proceso.observaciones.toUpperCase());
        }

        // 5. Tallas
        if (prenda.tallas && Object.keys(prenda.tallas).length > 0) {
            lineas.push('');
            lineas.push('<strong>TALLAS</strong>');
            this._agregarTallasFormato(lineas, prenda.tallas, prenda.genero);
        }

        return lineas.join('<br>') || '<em>Sin información</em>';
    }

    /**
     * Agregar tallas al formato de forma reutilizable
     */
    static _agregarTallasFormato(lineas, tallas, generoDefault = 'dama') {
        const tallasDama = {};
        const tallasCalballero = {};
        
        // Procesar tallas - pueden venir ANIDADAS: {"dama": {"L": 30, "S": 20}}
        Object.entries(tallas).forEach(([key, value]) => {
            if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                const genero = key.toLowerCase();
                Object.entries(value).forEach(([talla, cantidad]) => {
                    if (genero === 'dama') {
                        tallasDama[talla] = cantidad;
                    } else if (genero === 'caballero') {
                        tallasCalballero[talla] = cantidad;
                    }
                });
            } 
            else if (typeof value === 'number' || typeof value === 'string') {
                if (key.includes('-')) {
                    const [genero, talla] = key.split('-');
                    if (genero.toLowerCase() === 'dama') {
                        tallasDama[talla] = value;
                    } else if (genero.toLowerCase() === 'caballero') {
                        tallasCalballero[talla] = value;
                    }
                } else {
                    const genero = generoDefault || 'dama';
                    if (genero.toLowerCase() === 'dama') {
                        tallasDama[key] = value;
                    } else if (genero.toLowerCase() === 'caballero') {
                        tallasCalballero[key] = value;
                    }
                }
            }
        });
        
        // Renderizar DAMA
        if (Object.keys(tallasDama).length > 0) {
            const tallasStr = Object.entries(tallasDama)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            lineas.push(`DAMA: ${tallasStr}`);
        }
        
        // Renderizar CABALLERO
        if (Object.keys(tallasCalballero).length > 0) {
            const tallasStr = Object.entries(tallasCalballero)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            lineas.push(`CABALLERO: ${tallasStr}`);
        }
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    static parsearFecha(fechaStr) {
        if (!fechaStr) return new Date();
        
        let fecha = null;
        
        // Formato d/m/Y (ej: "19/01/2026")
        if (fechaStr.includes('/')) {
            const [day, month, year] = fechaStr.split('/');
            fecha = new Date(year, parseInt(month) - 1, day);
        }
        // Formato Y-m-d (ej: "2026-01-19")
        else if (fechaStr.includes('-')) {
            fecha = new Date(fechaStr + 'T00:00:00');
        }
        // Otros formatos
        else {
            fecha = new Date(fechaStr);
        }
        
        // Si la fecha es inválida, usar fecha actual
        if (isNaN(fecha.getTime())) {

            fecha = new Date();
        }
        
        return fecha;
    }

    /**
     * Formatea fecha a objeto {day, month, year}
     */
    static formatearFecha(fecha) {
        return {
            day: String(fecha.getDate()).padStart(2, '0'),
            month: String(fecha.getMonth() + 1).padStart(2, '0'),
            year: fecha.getFullYear()
        };
    }
}
