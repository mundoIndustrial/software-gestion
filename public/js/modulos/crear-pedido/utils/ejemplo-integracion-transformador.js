/**
 * EJEMPLO DE INTEGRACIÓN - Transformador de Telas desde Variantes
 * 
 * Este archivo muestra cómo integrar el transformador en el flujo
 * existente de carga de prendas desde cotización
 */

// Ejemplo 1: Integración en cargar-prendas-cotizacion.js
function integrarTransformadorEnCargarPrendas() {
    // En el método transformarDatos de CargadorPrendasCotizacion
    class CargadorPrendasCotizacion {
        transformarDatos(data, cotizacionId) {
            const prenda = data.prenda || {};
            const procesos = data.procesos || {};

            // ... código existente para procesos, fotos, etc.

            // NUEVO: Usar el transformador para telas desde variantes
            let telasFormato = [];
            
            if (prenda.telas && prenda.telas.length > 0) {
                // Si hay telas del backend, usarlas como base
                telasFormato = prenda.telas.map(tela => ({
                    id: tela.id,
                    nombre_tela: tela.nombre_tela || tela.tela?.nombre || tela.nombre || 'SIN NOMBRE',
                    color: tela.color || tela.color?.nombre || '',
                    referencia: tela.referencia || '',
                    composicion: tela.composicion || '',
                    imagenes: (tela.imagenes || []).map(img => ({
                        ruta: img.ruta || img,
                        ruta_webp: img.ruta_webp || null,
                        uid: `existing-tela-${tela.id}-${Math.random().toString(36).substr(2, 9)}`
                    }))
                }));
            }

            // NUEVO: Transformar variantes a telasAgregadas
            if (prenda.variantes && (prenda.variantes.telas_multiples || prenda.variantes.length > 0)) {
                console.log('[transformarDatos] 🔄 Usando transformador para variantes');
                
                // Usar el transformador
                const telasDesdeVariantes = window.transformarVariantesATelasAgregadas(prenda);
                
                // Combinar con telas del backend (si existen)
                telasDesdeVariantes.forEach(telaVariante => {
                    const existe = telasFormato.some(telaBackend => 
                        telaBackend.nombre_tela === telaVariante.nombre_tela && 
                        telaBackend.color === telaVariante.color
                    );
                    
                    if (!existe) {
                        telasFormato.push(telaVariante);
                        console.log('[transformarDatos] ➕ Agregada tela desde transformador:', telaVariante);
                    } else {
                        // Enriquecer si no tiene referencia
                        const indice = telasFormato.findIndex(t => 
                            t.nombre_tela === telaVariante.nombre_tela && 
                            t.color === telaVariante.color
                        );
                        if (indice !== -1 && !telasFormato[indice].referencia && telaVariante.referencia) {
                            telasFormato[indice].referencia = telaVariante.referencia;
                            console.log('[transformarDatos] 🔄 Enriquecida con referencia:', telaVariante.referencia);
                        }
                    }
                });
            }

            // Asignar telasAgregadas a la estructura final
            const prendaCompleta = {
                // ... otras propiedades
                telasAgregadas: telasFormato,
                telas: telasFormato, // Para compatibilidad
            };

            // Asignar a window para uso global
            window.telasAgregadas = [...telasFormato];

            return prendaCompleta;
        }
    }
}

// Ejemplo 2: Uso directo en prenda-editor.js
function integrarTransformadorEnPrendaEditor() {
    // En el método cargarTelas de PrendaEditor
    class PrendaEditor {
        cargarTelas(prenda) {
            console.log('[cargarTelas] 🔄 Usando transformador mejorado');

            // Si no hay telasAgregadas, usar el transformador
            if (!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) {
                if (prenda.variantes && (prenda.variantes.telas_multiples || prenda.variantes.length > 0)) {
                    console.log('[cargarTelas] 🔄 Transformando variantes con el nuevo transformador');
                    
                    // Usar el transformador
                    const telasTransformadas = window.transformarVariantesATelasAgregadas(prenda);
                    
                    // Asignar a la prenda
                    prenda.telasAgregadas = telasTransformadas;
                    
                    // Asignar a window
                    window.telasAgregadas = [...telasTransformadas];
                    
                    console.log('[cargarTelas] ✅ Transformación completada:', telasTransformadas.length);
                } else {
                    console.warn('[cargarTelas] ⚠️ No hay variantes con telas_multiples');
                    prenda.telasAgregadas = [];
                }
            } else {
                console.log('[cargarTelas] ℹ️ telasAgregadas ya existen');
            }

            // Continuar con el flujo normal...
            if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
                window.telasAgregadas = [...prenda.telasAgregadas];
                
                // Actualizar tabla
                if (window.actualizarTablaTelas) {
                    window.actualizarTablaTelas();
                }
            }
        }
    }
}

// Ejemplo 3: Uso standalone para testing
function ejemploUsoStandalone() {
    // Prenda de ejemplo con variantes
    const prendaEjemplo = {
        nombre_prenda: "Camisa Corporativa",
        cotizacion_id: 123,
        variantes: [
            {
                tipo_manga: "Larga",
                tipo_broche: "No aplica",
                tiene_bolsillos: true,
                telas_multiples: [
                    {
                        id: null,
                        tela: "Algodón Premium",
                        color: "Blanco",
                        referencia: "ALG-001",
                        descripcion: "Algodón de alta calidad, suave al tacto",
                        imagenes: [
                            "https://ejemplo.com/algodon1.jpg",
                            "https://ejemplo.com/algodon2.jpg"
                        ]
                    },
                    {
                        id: null,
                        tela: "Polyester",
                        color: "Azul Marino",
                        referencia: "POL-045",
                        descripcion: "Polyester resistente a arrugas",
                        imagenes: []
                    }
                ]
            },
            {
                tipo_manga: "Corta",
                tipo_broche: "Metálico",
                tiene_bolsillos: false,
                telas_multiples: [
                    {
                        id: null,
                        tela: "Lino",
                        color: "Beige",
                        referencia: "LIN-012",
                        descripcion: "Lino natural transpirable",
                        imagenes: [
                            "https://ejemplo.com/lino1.jpg"
                        ]
                    }
                ]
            }
        ]
    };

    console.log('🧪 [Ejemplo] Iniciando prueba de transformador');
    
    // Usar el flujo completo
    const prendaProcesada = window.procesarTelasDesdeVariantes(prendaEjemplo);
    
    console.log(' [Ejemplo] Resultado final:');
    console.log('Prenda procesada:', prendaProcesada);
    console.log('window.telasAgregadas:', window.telasAgregadas);
    
    return prendaProcesada;
}

// Exportar ejemplos para uso
if (typeof window !== 'undefined') {
    window.ejemploUsoStandalone = ejemploUsoStandalone;
    window.integrarTransformadorEnCargarPrendas = integrarTransformadorEnCargarPrendas;
    window.integrarTransformadorEnPrendaEditor = integrarTransformadorEnPrendaEditor;
}

// Para probar, ejecuta en consola:
// window.ejemploUsoStandalone();
