(function() {
    'use strict';

    class DraftPedidoAnalytics {
        constructor() {
            this.metrics = {
                imagenesProcesadas: 0,
                imagenesComprimidas: 0,
                tamanoOriginalTotal: 0,
                tamanoComprimidoTotal: 0,
                intentosFallidos: 0,
                intentosExitosos: 0,
                intentosReintentos: 0,
                tiempoPromedioProcesamiento: 0,
                tiemposIndividuales: []
            };
            this.isEnabled = true;
        }

        registrarImagenProcesada(tamanoOriginal, tamanoComprimido, fueComprimida = false) {
            if (!this.isEnabled) return;

            this.metrics.imagenesProcesadas += 1;

            if (fueComprimida) {
                this.metrics.imagenesComprimidas += 1;
            }

            this.metrics.tamanoOriginalTotal += tamanoOriginal || 0;
            this.metrics.tamanoComprimidoTotal += tamanoComprimido || 0;
        }

        registrarIntentoEnvio(exitoso, conReintento = false) {
            if (!this.isEnabled) return;

            if (exitoso) {
                this.metrics.intentosExitosos += 1;
                if (conReintento) {
                    this.metrics.intentosReintentos += 1;
                }
            } else {
                this.metrics.intentosFallidos += 1;
            }
        }

        registrarTiempo(ms) {
            if (!this.isEnabled) return;

            this.metrics.tiemposIndividuales.push(ms);

            // Mantener solo los últimos 100 registros para no consumir memoria
            if (this.metrics.tiemposIndividuales.length > 100) {
                this.metrics.tiemposIndividuales.shift();
            }

            // Calcular promedio
            const suma = this.metrics.tiemposIndividuales.reduce((a, b) => a + b, 0);
            this.metrics.tiempoPromedioProcesamiento = suma / this.metrics.tiemposIndividuales.length;
        }

        generarReporte() {
            const tasaCompresion = this.metrics.imagenesProcesadas > 0
                ? ((this.metrics.imagenesComprimidas / this.metrics.imagenesProcesadas) * 100).toFixed(2)
                : 0;

            const ratioCompresion = this.metrics.tamanoOriginalTotal > 0
                ? (this.metrics.tamanoComprimidoTotal / this.metrics.tamanoOriginalTotal * 100).toFixed(2)
                : 0;

            const tasaExito = (this.metrics.intentosExitosos + this.metrics.intentosFallidos) > 0
                ? ((this.metrics.intentosExitosos / (this.metrics.intentosExitosos + this.metrics.intentosFallidos)) * 100).toFixed(2)
                : 0;

            return {
                procesamiento: {
                    imagenesProcesadas: this.metrics.imagenesProcesadas,
                    imagenesComprimidas: this.metrics.imagenesComprimidas,
                    tasaCompresionPorcentaje: Number.parseFloat(tasaCompresion),
                    tamanoAhorradoMB: ((this.metrics.tamanoOriginalTotal - this.metrics.tamanoComprimidoTotal) / (1024 * 1024)).toFixed(2),
                    ratioCompresionPorcentaje: Number.parseFloat(ratioCompresion)
                },
                red: {
                    intentosExitosos: this.metrics.intentosExitosos,
                    intentosFallidos: this.metrics.intentosFallidos,
                    intentosConReintento: this.metrics.intentosReintentos,
                    tasaExitoPorcentaje: Number.parseFloat(tasaExito)
                },
                rendimiento: {
                    tiempoPromedioProcesamiento: Math.round(this.metrics.tiempoPromedioProcesamiento) + 'ms',
                    registrosTiempos: this.metrics.tiemposIndividuales.length
                }
            };
        }

        loguearReporte() {
            const reporte = this.generarReporte();
            console.info('[DraftPedidoAnalytics] Reporte de Efectividad:', {
                timestamp: new Date().toISOString(),
                ...reporte
            });
        }

        limpiar() {
            this.metrics = {
                imagenesProcesadas: 0,
                imagenesComprimidas: 0,
                tamanoOriginalTotal: 0,
                tamanoComprimidoTotal: 0,
                intentosFallidos: 0,
                intentosExitosos: 0,
                intentosReintentos: 0,
                tiempoPromedioProcesamiento: 0,
                tiemposIndividuales: []
            };
        }

        desabilitar() {
            this.isEnabled = false;
        }

        habilitar() {
            this.isEnabled = true;
        }
    }

    globalThis.DraftPedidoAnalytics = new DraftPedidoAnalytics();
})();
