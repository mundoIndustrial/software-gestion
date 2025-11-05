/**
 * Script de Google Apps Script para exportar datos de Excel a SQL
 * Tabla: registro_piso_polo
 * 
 * CARACTERÍSTICAS:
 * - Genera UN SOLO INSERT con múltiples VALUES
 * - Preserva decimales completos (ej: 11.08896539)
 * - No convierte valores vacíos a 0, los mantiene como NULL
 * - Mapea correctamente las columnas del Excel a la base de datos
 */

function generarYGuardarSQLenDrive() {
  const SHEET_NAME = "REGISTRO";
  const TABLE_NAME = "registro_piso_polo";
  
  const hoja = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET_NAME);
  if (!hoja) {
    SpreadsheetApp.getUi().alert("❌ No se encontró la hoja '" + SHEET_NAME + "'.");
    return;
  }

  const datos = hoja.getDataRange().getValues();
  if (datos.length < 2) {
    SpreadsheetApp.getUi().alert("⚠️ No hay datos suficientes en la hoja.");
    return;
  }

  const encabezados = datos[0].map(h => h.toString().trim().toUpperCase());
  const filas = datos.slice(1);

  // Mapeo entre encabezado de hoja → columna SQL
  const mapaColumnas = {
    "FECHA": "fecha",
    "MODULO": "modulo",
    "ORDEN DE PRODUCCIÓN": "orden_produccion",
    "HORA": "hora",
    "TIEMPO DE CICLO": "tiempo_ciclo",
    "PORCIÓN DE TIEMPO": "porcion_tiempo",
    "CANTIDAD PRODUCIDA": "cantidad",
    "PARADAS PROGRAMADAS": "paradas_programadas",
    "PARADAS NO PROGRAMADAS": "paradas_no_programadas",
    "TIEMPO DE PARADA NO PROGRAMADA": "tiempo_parada_no_programada",
    "NÚMERO DE OPERARIOS": "numero_operarios",
    "TIEMPO PARA PROG": "tiempo_para_programada",
    "TIEMPO DISP": "tiempo_disponible",
    "META": "meta",
    "EFICIENCIA": "eficiencia"
  };

  const columnasSQL = Object.values(mapaColumnas);
  const fechaActual = Utilities.formatDate(new Date(), "GMT-5", "yyyyMMdd_HHmmss");
  const nombreArchivo = `${TABLE_NAME}_${fechaActual}.sql`;
  const carpeta = DriveApp.createFolder("SQL_EXPORTS_POLO_" + fechaActual);

  let valuesArray = [];
  let totalProcesadas = 0;
  let filasDescartadas = [];

  filas.forEach((fila, index) => {
    const numeroFila = index + 2; // +2 porque: +1 por el índice base 0, +1 por el encabezado
    const filaObj = {};

    // Mapear cada columna del Excel a su equivalente SQL
    for (const [encabezado, columnaSQL] of Object.entries(mapaColumnas)) {
      const indexCol = encabezados.indexOf(encabezado);
      if (indexCol !== -1) {
        filaObj[columnaSQL] = fila[indexCol];
      } else {
        filaObj[columnaSQL] = null;
      }
    }

    // Validación: verificar si la fila está completamente vacía
    const filaVacia = Object.values(filaObj).every(val => 
      val === null || val === undefined || val === '' || 
      (typeof val === 'string' && val.trim() === '')
    );
    
    if (filaVacia) {
      filasDescartadas.push(`Fila ${numeroFila}: Fila completamente vacía`);
      return;
    }

    // Validación: debe tener al menos fecha O orden de producción
    if ((!filaObj.fecha || filaObj.fecha === '') && 
        (!filaObj.orden_produccion || filaObj.orden_produccion === '')) {
      filasDescartadas.push(`Fila ${numeroFila}: Sin fecha ni orden de producción - Fecha: "${filaObj.fecha}", Orden: "${filaObj.orden_produccion}"`);
      return;
    }

    // Construir el VALUES para este registro
    const values = `(
  '${formatearFecha(filaObj.fecha)}',
  '${escaparTexto(filaObj.modulo)}',
  '${escaparTexto(filaObj.orden_produccion)}',
  '${escaparTexto(filaObj.hora)}',
  ${toDecimalOrNull(filaObj.tiempo_ciclo)},
  ${toDecimalOrNull(filaObj.porcion_tiempo)},
  ${toIntOrNull(filaObj.cantidad)},
  '${escaparTexto(filaObj.paradas_programadas)}',
  '${escaparTexto(filaObj.paradas_no_programadas)}',
  ${toDecimalOrNull(filaObj.tiempo_parada_no_programada)},
  ${toIntOrNull(filaObj.numero_operarios)},
  ${toDecimalOrNull(filaObj.tiempo_para_programada)},
  ${toDecimalOrNull(filaObj.tiempo_disponible)},
  ${toDecimalOrNull(filaObj.meta)},
  ${toDecimalOrNull(filaObj.eficiencia)},
  NOW(),
  NOW()
)`;

    valuesArray.push(values);
    totalProcesadas++;
  });

  // Generar UN SOLO INSERT con múltiples VALUES
  let contenidoSQL = "";
  
  if (valuesArray.length > 0) {
    contenidoSQL = `INSERT INTO ${TABLE_NAME}
(${columnasSQL.join(", ")}, created_at, updated_at)
VALUES
${valuesArray.join(",\n")};
`;
  }

  const archivo = carpeta.createFile(nombreArchivo, contenidoSQL, MimeType.PLAIN_TEXT);

  // Crear archivo de log si hay filas descartadas
  let mensajeDescartadas = '';
  if (filasDescartadas.length > 0) {
    const logContent = `REPORTE DE FILAS DESCARTADAS
Total de filas en Excel: ${filas.length}
Filas procesadas: ${totalProcesadas}
Filas descartadas: ${filasDescartadas.length}

DETALLE DE FILAS DESCARTADAS:
${filasDescartadas.join('\n')}
`;
    carpeta.createFile(`LOG_filas_descartadas_${fechaActual}.txt`, logContent, MimeType.PLAIN_TEXT);
    mensajeDescartadas = `\n\n⚠️ ATENCIÓN: ${filasDescartadas.length} filas fueron descartadas.\nRevisa el archivo LOG en la carpeta para más detalles.`;
  }

  SpreadsheetApp.getUi().alert(`✅ Archivo SQL generado con éxito.
📄 Total filas en Excel: ${filas.length}
✅ Registros procesados: ${totalProcesadas}
❌ Filas descartadas: ${filasDescartadas.length}
📁 Guardado en carpeta: ${carpeta.getName()}
🔗 Enlace: ${archivo.getUrl()}

✅ Se generó UN SOLO INSERT con ${totalProcesadas} registros${mensajeDescartadas}`);
}

// === FUNCIONES AUXILIARES ===

/**
 * Formatea una fecha al formato SQL (YYYY-MM-DD)
 */
function formatearFecha(fecha) {
  if (fecha instanceof Date) {
    return Utilities.formatDate(fecha, "GMT-5", "yyyy-MM-dd");
  }
  
  const s = fecha ? fecha.toString().trim() : "";
  
  // Si ya está en formato YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    return s;
  }
  
  // Si está en formato DD/MM/YYYY o D/M/YYYY
  const matchDMY = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
  if (matchDMY) {
    const dia = matchDMY[1].padStart(2, '0');
    const mes = matchDMY[2].padStart(2, '0');
    const anio = matchDMY[3];
    return `${anio}-${mes}-${dia}`;
  }
  
  return s || "";
}

/**
 * Limpia un número eliminando caracteres no numéricos
 * Convierte comas a puntos para decimales
 */
function limpiarNumero(valor) {
  if (valor === null || valor === undefined || valor === '') return null;
  
  let s = valor.toString().trim();
  
  // Si es un string vacío o "N/A" o similar, retornar null
  if (s === '' || s.toUpperCase() === 'N/A' || s.toUpperCase() === 'NA') {
    return null;
  }
  
  // Reemplazar comas por puntos
  s = s.replace(/,/g, '.');
  
  // Eliminar todo excepto dígitos, puntos y signo negativo
  s = s.replace(/[^\d.\-]/g, '');
  
  return s === '' ? null : s;
}

/**
 * Convierte a entero o retorna NULL si está vacío
 */
function toIntOrNull(valor) {
  const limpio = limpiarNumero(valor);
  if (limpio === null) return 'NULL';
  
  const num = parseInt(limpio);
  return isNaN(num) ? 'NULL' : num;
}

/**
 * Convierte a decimal preservando todos los decimales o retorna NULL si está vacío
 * Preserva hasta 9 decimales como máximo
 */
function toDecimalOrNull(valor) {
  const limpio = limpiarNumero(valor);
  if (limpio === null) return 'NULL';
  
  const num = parseFloat(limpio);
  if (isNaN(num)) return 'NULL';
  
  // Preservar hasta 9 decimales
  const match = num.toString().match(/^-?\d+(?:\.\d{0,9})?/);
  return match ? parseFloat(match[0]) : 'NULL';
}

/**
 * Escapa comillas simples en texto para SQL
 */
function escaparTexto(texto) {
  if (!texto || texto === null || texto === undefined) return '';
  return texto.toString().replace(/'/g, "\\'");
}
