/**
 * TRANSFORMADOR DE TELAS DESDE VARIANTES
 * 
 * Convierte todas las variantes de una prenda con telas_multiples
 * en un array único telasAgregadas con todas las propiedades
 * 
 * @param {Object} prenda - Objeto de la prenda con variantes
 * @returns {Array} Array de telasAgregadas con estructura unificada
 */
function transformarVariantesATelasAgregadas(prenda) {
    console.log('🔄 [transformarVariantesATelasAgregadas] Iniciando transformación');
    console.log('📋 Prenda recibida:', {
        nombre: prenda.nombre_prenda || prenda.nombre,
        tiene_variantes: !!prenda.variantes,
        variantes_count: prenda.variantes?.length || 0,
        cotizacion_id: prenda.cotizacion_id
    });

    // Validar que la prenda tenga variantes
    if (!prenda || !prenda.variantes) {
        console.warn(' [transformarVariantesATelasAgregadas] La prenda no tiene variantes');
        return [];
    }

    // Asegurar que variantes sea un array
    const variantes = Array.isArray(prenda.variantes) ? prenda.variantes : [prenda.variantes];
    
    console.log(`🔍 [transformarVariantesATelasAgregadas] Procesando ${variantes.length} variantes`);

    // Array para acumular todas las telas
    const telasAgregadas = [];
    const telasUnicas = new Set(); // Para evitar duplicados basado en nombre_tela + color

    // Recorrer todas las variantes
    variantes.forEach((variante, varianteIndex) => {
        console.log(`📦 [Variante ${varianteIndex}] Procesando variante:`, {
            tipo_manga: variante.tipo_manga,
            tiene_bolsillos: variante.tiene_bolsillos,
            tiene_telas_multiples: !!(variante.telas_multiples),
            telas_multiples_count: variante.telas_multiples?.length || 0
        });

        // Validar que la variante tenga telas_multiples
        if (!variante.telas_multiples || !Array.isArray(variante.telas_multiples)) {
            console.log(` [Variante ${varianteIndex}] No tiene telas_multiples o no es array`);
            return; // Continue con siguiente variante
        }

        // Recorrer todas las telas de esta variante
        variante.telas_multiples.forEach((tela, telaIndex) => {
            console.log(`🧵 [Tela ${telaIndex}] Procesando tela:`, {
                tela: tela.tela,
                color: tela.color,
                referencia: tela.referencia,
                descripcion: tela.descripcion,
                imagenes_count: tela.imagenes?.length || 0
            });

            // Extraer y validar propiedades
            const nombre_tela = tela.tela || tela.nombre_tela || '';
            const color = tela.color || '';
            const referencia = tela.referencia || '';
            const descripcion = tela.descripcion || '';
            const imagenes = Array.isArray(tela.imagenes) ? tela.imagenes : [];

            // Crear clave única para evitar duplicados
            const claveUnica = `${nombre_tela}|${color}`;
            
            // Validar que tenga datos mínimos
            if (!nombre_tela || !color) {
                console.warn(` [Tela ${telaIndex}] Datos incompletos - requiere nombre_tela y color:`, {
                    nombre_tela,
                    color
                });
                return; // Saltar esta tela
            }

            // Verificar si ya existe para evitar duplicados
            if (telasUnicas.has(claveUnica)) {
                console.log(` [Tela ${telaIndex}] Tela ya existe, omitiendo: ${claveUnica}`);
                return; // Saltar duplicado
            }

            // Agregar al set de únicas
            telasUnicas.add(claveUnica);

            // Crear objeto de tela con estructura unificada
            const telaTransformada = {
                // ID si existe (puede venir de BD o ser null)
                id: tela.id || null,
                
                // Propiedades principales de la variante
                nombre_tela: nombre_tela,
                color: color,
                referencia: referencia, //  MUY IMPORTANTE
                descripcion: descripcion,
                
                // Propiedades adicionales (valores por defecto)
                grosor: tela.grosor || '',
                composicion: tela.composicion || '',
                
                // Imágenes (array, puede venir vacío)
                imagenes: imagenes,
                
                // Metadatos para debugging
                origen: 'variante_transformada',
                variante_index: varianteIndex,
                tela_index: telaIndex,
                cotizacion_id: prenda.cotizacion_id || null
            };

            // Agregar al array final
            telasAgregadas.push(telaTransformada);
            
            console.log(` [Tela ${telaIndex}] Tela agregada correctamente:`, {
                nombre: telaTransformada.nombre_tela,
                color: telaTransformada.color,
                referencia: `"${telaTransformada.referencia}"`,
                descripcion: telaTransformada.descripcion,
                imagenes: telaTransformada.imagenes.length
            });
        });
    });

    // LOG FINAL CON REFERENCIAS
    console.log(' [transformarVariantesATelasAgregadas] TRANSFORMACIÓN COMPLETADA');
    console.log(` Total de telas agregadas: ${telasAgregadas.length}`);
    console.log('📋 ARRAY FINAL telasAgregadas con referencias:');
    
    telasAgregadas.forEach((tela, index) => {
        console.log(`  [${index}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" | descripción: "${tela.descripcion}" | imágenes: ${tela.imagenes.length}`);
    });

    return telasAgregadas;
}

/**
 * Función de conveniencia para asignar telasAgregadas a la prenda y al window
 * 
 * @param {Object} prenda - Objeto de la prenda a modificar
 * @param {Array} telasAgregadas - Array de telas procesadas
 * @returns {Object} Prenda modificada con telasAgregadas asignadas
 */
function asignarTelasAgregadas(prenda, telasAgregadas) {
    // Asignar a la prenda
    prenda.telasAgregadas = [...telasAgregadas];
    
    // Asignar al window para uso global
    window.telasAgregadas = [...telasAgregadas];
    
    console.log('🔗 [asignarTelasAgregadas] Telas asignadas:');
    console.log('  - prenda.telasAgregadas:', prenda.telasAgregadas.length);
    console.log('  - window.telasAgregadas:', window.telasAgregadas.length);
    
    return prenda;
}

/**
 * Flujo completo de transformación y asignación
 * 
 * @param {Object} prenda - Objeto de la prenda con variantes
 * @returns {Object} Prenda modificada con telasAgregadas
 */
function procesarTelasDesdeVariantes(prenda) {
    console.log('🚀 [procesarTelasDesdeVariantes] Iniciando flujo completo');
    
    // 1. Transformar variantes a telasAgregadas
    const telasAgregadas = transformarVariantesATelasAgregadas(prenda);
    
    // 2. Asignar a la prenda y al window
    const prendaModificada = asignarTelasAgregadas(prenda, telasAgregadas);
    
    // 3. Actualizar tabla de telas si existe la función
    if (typeof window.actualizarTablaTelas === 'function') {
        console.log('🔄 [procesarTelasDesdeVariantes] Actualizando tabla de telas');
        window.actualizarTablaTelas();
    }
    
    console.log(' [procesarTelasDesdeVariantes] Flujo completado exitosamente');
    return prendaModificada;
}

// Exportar funciones para uso global
if (typeof window !== 'undefined') {
    window.transformarVariantesATelasAgregadas = transformarVariantesATelasAgregadas;
    window.asignarTelasAgregadas = asignarTelasAgregadas;
    window.procesarTelasDesdeVariantes = procesarTelasDesdeVariantes;
}

// Ejemplo de uso:
/*
// Suponiendo que tienes una prenda con variantes:
const prenda = {
    nombre_prenda: "Camisa Corporativa",
    cotizacion_id: 123,
    variantes: [
        {
            tipo_manga: "Larga",
            telas_multiples: [
                {
                    tela: "Algodón Premium",
                    color: "Blanco",
                    referencia: "ALG-001",
                    descripcion: "Algodón de alta calidad",
                    imagenes: ["url1.jpg", "url2.jpg"]
                },
                {
                    tela: "Polyester",
                    color: "Azul",
                    referencia: "POL-045",
                    descripcion: "Polyester resistente",
                    imagenes: []
                }
            ]
        },
        {
            tipo_manga: "Corta",
            telas_multiples: [
                {
                    tela: "Lino",
                    color: "Beige",
                    referencia: "LIN-012",
                    descripcion: "Lino natural",
                    imagenes: ["url3.jpg"]
                }
            ]
        }
    ]
};

// Usar el flujo completo:
const prendaProcesada = procesarTelasDesdeVariantes(prenda);

// Resultado:
// prendaProcesada.telasAgregadas = [
//   { id: null, nombre_tela: "Algodón Premium", color: "Blanco", referencia: "ALG-001", descripcion: "...", imagenes: [...] },
//   { id: null, nombre_tela: "Polyester", color: "Azul", referencia: "POL-045", descripcion: "...", imagenes: [...] },
//   { id: null, nombre_tela: "Lino", color: "Beige", referencia: "LIN-012", descripcion: "...", imagenes: [...] }
// ]
// 
// window.telasAgregadas tendrá el mismo array
*/
