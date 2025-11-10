/**
 * Script de Google Apps Script para exportar datos de Excel a SQL
 * Tabla: registro_piso_corte
 * 
 * VERSIÓN CONCATENADA - Múltiples telas en un solo registro
 * 
 * CAMBIOS:
 * - Cuando hay múltiples telas (ej: "NAFLIX-POLO"), se concatenan en un solo nombre
 * - Crea UN SOLO registro con el nombre completo de la tela
 * - NO duplica cantidades
 */

function generarYGuardarSQLenDrive() {
  const hoja = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("CORTE");
  if (!hoja) {
    SpreadsheetApp.getUi().alert("❌ No se encontró la hoja 'CORTE'.");
    return;
  }

  const datos = hoja.getDataRange().getValues();
  if (datos.length < 2) {
    SpreadsheetApp.getUi().alert("⚠️ No hay datos suficientes en la hoja.");
    return;
  }

  datos.shift(); // quitar encabezado

  const fechaActual = Utilities.formatDate(new Date(), "GMT-5", "yyyyMMdd_HHmmss");
  const nombreArchivo = `registro_piso_corte_${fechaActual}.sql`;
  const carpeta = DriveApp.createFolder("SQL_EXPORTS_CORTE_" + fechaActual);

  let contenidoTotal = "";
  const tamañoLote = 1000;
  let totalProcesadas = 0;
  let errores = [];
  
  const operariosInsertados = new Set();
  const maquinasInsertadas = new Set();
  const telasInsertadas = new Set();
  const horasInsertadas = new Set();
  const tiemposCicloInsertados = new Set();

  let sqlInsertsBase = "-- ===== CREAR REGISTROS BASE SI NO EXISTEN =====\n\n";
  
  for (let i = 0; i < datos.length; i += tamañoLote) {
    const lote = datos.slice(i, i + tamañoLote);
    let contenidoLote = "";

    lote.forEach((fila, index) => {
      const [
        marcaTemporal, fecha, ordenProduccion, hora, operario, maquina,
        porcionTiempo, cantidadProducida, tiempoCiclo, paradasProgramadas,
        tiempoParaProgramada, paradasNoProgramadas, tiempoParadaNoProgramada,
        tipoExtendido, numeroCapas, tiempoExtendido, trazado, tiempoTrazado,
        actividad, columna19, tela, tiempoDisponible, meta, eficiencia, novedad
      ] = fila;

      if (!fecha || !ordenProduccion) {
        errores.push(`Fila ${i + index + 2}: Fecha u orden de producción vacía`);
        return;
      }

      const operarioNombre = (operario || '').toString().trim().toUpperCase();
      const maquinaNombre = normalizarMaquina((maquina || '').toString().trim().toUpperCase());
      const horaRango = normalizarRangoHora((hora || '').toString().trim());
      
      // *** CAMBIO PRINCIPAL: Concatenar múltiples telas en un solo nombre ***
      const telaNombreConcatenado = normalizarTelaConcatenada((tela || '').toString().trim());

      // Generar INSERT para operario
      if (operarioNombre && !operariosInsertados.has(operarioNombre)) {
        sqlInsertsBase += generarInsertOperario(operarioNombre);
        operariosInsertados.add(operarioNombre);
      }

      // Generar INSERT para máquina
      if (maquinaNombre && maquinaNombre !== 'NULL' && !maquinasInsertadas.has(maquinaNombre)) {
        sqlInsertsBase += generarInsertMaquina(maquinaNombre);
        maquinasInsertadas.add(maquinaNombre);
      }

      // Generar INSERT para la tela concatenada
      if (telaNombreConcatenado && !telasInsertadas.has(telaNombreConcatenado)) {
        sqlInsertsBase += generarInsertTela(telaNombreConcatenado);
        telasInsertadas.add(telaNombreConcatenado);
        
        // Generar INSERT para tiempo_ciclo usando la primera tela del conjunto
        if (maquinaNombre && maquinaNombre !== 'NULL') {
          const claveTiempoCiclo = `${telaNombreConcatenado}|${maquinaNombre}`;
          if (!tiemposCicloInsertados.has(claveTiempoCiclo)) {
            // Usar la primera tela para determinar el tiempo de ciclo
            const primeraTela = telaNombreConcatenado.split('-')[0];
            sqlInsertsBase += generarInsertTiempoCiclo(telaNombreConcatenado, maquinaNombre, primeraTela);
            tiemposCicloInsertados.add(claveTiempoCiclo);
          }
        }
      }

      // Generar INSERT para hora
      if (horaRango && !horasInsertadas.has(horaRango)) {
        sqlInsertsBase += generarInsertHora(horaRango);
        horasInsertadas.add(horaRango);
      }

      // *** Crear UN SOLO registro con la tela concatenada ***
      const maquinaIdQuery = maquinaNombre 
        ? `(SELECT id FROM maquinas WHERE nombre_maquina = '${maquinaNombre}' LIMIT 1)` 
        : 'NULL';
      
      const telaIdQuery = telaNombreConcatenado
        ? `(SELECT id FROM telas WHERE nombre_tela = '${telaNombreConcatenado}' LIMIT 1)` 
        : 'NULL';
      
      const insert = `
INSERT INTO registro_piso_corte 
(fecha, orden_produccion, porcion_tiempo, cantidad, tiempo_ciclo, paradas_programadas, tiempo_para_programada, paradas_no_programadas, tiempo_parada_no_programada, tipo_extendido, numero_capas, tiempo_extendido, trazado, tiempo_trazado, actividad, tiempo_disponible, meta, eficiencia, hora_id, operario_id, maquina_id, tela_id, created_at, updated_at)
SELECT 
  '${formatearFecha(fecha)}',
  '${escaparTexto(ordenProduccion)}',
  ${toDecimal(porcionTiempo)},
  ${toInt(cantidadProducida)},
  ${toDecimal(tiempoCiclo)},
  '${escaparTexto(paradasProgramadas)}',
  ${toDecimal(tiempoParaProgramada)},
  '${escaparTexto(paradasNoProgramadas)}',
  ${toDecimal(tiempoParadaNoProgramada)},
  '${escaparTexto(tipoExtendido)}',
  ${toInt(numeroCapas)},
  ${toInt(tiempoExtendido)},
  '${escaparTexto(trazado)}',
  ${toDecimal(tiempoTrazado)},
  '${escaparTexto(actividad)}',
  ${toDecimal(tiempoDisponible)},
  ${toDecimal(meta)},
  ${toDecimal(eficiencia)},
  (SELECT id FROM horas WHERE rango = '${escaparTexto(horaRango)}' LIMIT 1),
  (SELECT id FROM users WHERE UPPER(name) = '${operarioNombre}' LIMIT 1),
  ${maquinaIdQuery},
  ${telaIdQuery},
  NOW(), NOW();
`;

      contenidoLote += insert;
      totalProcesadas++;
    });

    contenidoTotal += contenidoLote;
  }

  contenidoTotal = sqlInsertsBase + "\n-- ===== INSERTAR REGISTROS DE CORTE =====\n\n" + contenidoTotal;

  const archivo = carpeta.createFile(nombreArchivo, contenidoTotal, MimeType.PLAIN_TEXT);

  let mensaje = `✅ Archivo SQL generado con éxito (VERSIÓN CONCATENADA).
📄 Total registros procesados: ${totalProcesadas}
📁 Guardado en carpeta: ${carpeta.getName()}
🔗 Enlace: ${archivo.getUrl()}

✅ NOTA: Múltiples telas se concatenan en un solo nombre (ej: NAFLIX-POLO)`;

  if (errores.length > 0) {
    mensaje += `\n\n⚠️ ERRORES ENCONTRADOS (${errores.length}):\n${errores.slice(0, 10).join('\n')}`;
    if (errores.length > 10) {
      mensaje += `\n... y ${errores.length - 10} más`;
    }
  }

  SpreadsheetApp.getUi().alert(mensaje);
}

// ===== FUNCIONES AUXILIARES =====

function generarInsertOperario(nombre) {
  const email = `${nombre.toLowerCase().replace(/\s+/g, '')}@mundoindustrial.com`;
  const password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
  
  return `
-- Crear operario: ${nombre}
INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at)
SELECT '${nombre}', '${email}', '${password}', 
       (SELECT id FROM roles WHERE name = 'cortador' LIMIT 1),
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE UPPER(name) = '${nombre}');

`;
}

function generarInsertMaquina(nombre) {
  return `
-- Crear máquina: ${nombre}
INSERT IGNORE INTO maquinas (nombre_maquina, created_at, updated_at)
SELECT '${nombre}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM maquinas WHERE nombre_maquina = '${nombre}');

`;
}

function generarInsertTela(nombre) {
  return `
-- Crear tela: ${nombre}
INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)
SELECT '${nombre}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM telas WHERE nombre_tela = '${nombre}');

`;
}

function generarInsertHora(rango) {
  const horaNum = extraerNumeroHora(rango);
  const rangoNormalizado = normalizarRangoHora(rango);
  
  return `
-- Crear hora: ${rangoNormalizado}
INSERT IGNORE INTO horas (hora, rango, created_at, updated_at)
SELECT ${horaNum}, '${escaparTexto(rangoNormalizado)}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM horas WHERE rango = '${escaparTexto(rangoNormalizado)}');

`;
}

function generarInsertTiempoCiclo(telaNombreConcatenado, maquinaNombre, primeraTela) {
  // Usar la primera tela para determinar el tiempo de ciclo
  const tiempoCiclo = obtenerTiempoCiclo(primeraTela, maquinaNombre);
  
  return `
-- Crear tiempo_ciclo: ${telaNombreConcatenado} + ${maquinaNombre}
INSERT IGNORE INTO tiempo_ciclos (tela_id, maquina_id, tiempo_ciclo, created_at, updated_at)
SELECT 
  (SELECT id FROM telas WHERE nombre_tela = '${telaNombreConcatenado}' LIMIT 1),
  (SELECT id FROM maquinas WHERE nombre_maquina = '${maquinaNombre}' LIMIT 1),
  ${tiempoCiclo},
  NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM tiempo_ciclos 
  WHERE tela_id = (SELECT id FROM telas WHERE nombre_tela = '${telaNombreConcatenado}' LIMIT 1)
  AND maquina_id = (SELECT id FROM maquinas WHERE nombre_maquina = '${maquinaNombre}' LIMIT 1)
);

`;
}

function obtenerTiempoCiclo(telaNombre, maquinaNombre) {
  const telasGrupo1 = [
    'NAFLIX', 'POLUX', 'POLO', 'SHELSY', 'HIDROTECH', 'ALFONSO', 'MADRIGAL',
    'SPORTWEAR', 'NATIVA', 'SUDADERA', 'OXFORD VESTIR', 'PANTALON DE VESTIR',
    'BRAGAS', 'CONJUNTO ANTIFLUIDO', 'BRAGAS DRILL', 'SPEED', 'PIQUE',
    'IGNIFUGO', 'COFIAS', 'BOLSA QUIRURGICA', 'FORROS', 'TOP PLUX',
    'NOVACRUM', 'CEDACRON', 'DACRON', 'ENTRETELA', 'NAUTICA',
    'CHAQUETA ORION', 'MICRO TITAN', 'SPRAY RIB', 'DOBLE PUNTO'
  ];
  
  const telasGrupo2 = [
    'OXFORD', 'DRILL', 'GOLIAT', 'BOLSILLO', 'SANSON', 'PANTALON ORION',
    'SEGAL WIKING', 'JEANS', 'SHAMBRAIN', 'NAPOLES', 'DACRUM', 'RETACEO DRILL'
  ];
  
  const esGrupo1 = telasGrupo1.includes(telaNombre);
  const esGrupo2 = telasGrupo2.includes(telaNombre);
  
  if (esGrupo1) {
    if (maquinaNombre === 'BANANA') return 97;
    if (maquinaNombre === 'VERTICAL') return 130;
    if (maquinaNombre === 'TIJERAS') return 97;
  }
  
  if (esGrupo2) {
    if (maquinaNombre === 'BANANA') return 45;
    if (maquinaNombre === 'VERTICAL') return 114;
    if (maquinaNombre === 'TIJERAS') return 45;
  }
  
  return 97;
}

function normalizarRangoHora(rango) {
  if (!rango) return '08:00am - 09:00am';
  
  const rangoStr = rango.toString().trim().toUpperCase();
  const match = rangoStr.match(/HORA\s*(\d+)/);
  
  if (match) {
    const horaNum = parseInt(match[1]);
    const mapeoRangos = {
      1: '08:00am - 09:00am', 2: '09:00am - 10:00am', 3: '10:00am - 11:00am',
      4: '11:00am - 12:00pm', 5: '12:00pm - 01:00pm', 6: '01:00pm - 02:00pm',
      7: '02:00pm - 03:00pm', 8: '03:00pm - 04:00pm', 9: '04:00pm - 05:00pm',
      10: '05:00pm - 06:00pm', 11: '06:00pm - 07:00pm', 12: '07:00pm - 08:00pm'
    };
    return mapeoRangos[horaNum] || '08:00am - 09:00am';
  }
  
  return rango;
}

function extraerNumeroHora(rango) {
  if (!rango) return 1;
  
  const rangoStr = rango.toString().trim().toUpperCase();
  const match = rangoStr.match(/HORA\s*(\d+)/);
  if (match) return parseInt(match[1]);
  
  const mapeoHoras = {
    '08:00am - 09:00am': 1, '09:00am - 10:00am': 2, '10:00am - 11:00am': 3,
    '11:00am - 12:00pm': 4, '12:00pm - 01:00pm': 5, '01:00pm - 02:00pm': 6,
    '02:00pm - 03:00pm': 7, '03:00pm - 04:00pm': 8, '04:00pm - 05:00pm': 9,
    '05:00pm - 06:00pm': 10, '06:00pm - 07:00pm': 11, '07:00pm - 08:00pm': 12
  };
  
  return mapeoHoras[rango] || 1;
}

function normalizarMaquina(nombre) {
  if (!nombre) return null;
  const nombreUpper = nombre.toString().trim().toUpperCase();
  if (['N.A', 'N/A', 'NA', 'N.A.', 'NINGUNA', 'NINGUNO'].includes(nombreUpper)) {
    return null;
  }
  return nombreUpper;
}

/**
 * *** FUNCIÓN CLAVE: Normaliza telas concatenándolas ***
 * Convierte "NAFLIX-POLO" a "NAFLIX-POLO" (mantiene el formato original)
 * Convierte "NAFLIX/POLO" a "NAFLIX-POLO" (unifica separadores)
 */
function normalizarTelaConcatenada(nombre) {
  if (!nombre) return null;
  
  let nombreStr = nombre.toString().trim().toUpperCase();
  
  // Si no tiene separadores, devolver tal cual
  if (!nombreStr.includes('-') && !nombreStr.includes('/')) {
    return aplicarVariaciones(nombreStr);
  }
  
  // Separar por guiones o barras
  let telas = nombreStr.split(/[-\/]/).map(t => t.trim()).filter(t => t.length > 0);
  
  // Aplicar variaciones a cada tela
  telas = telas.map(tela => aplicarVariaciones(tela));
  
  // Concatenar con guión
  return telas.join('-');
}

function aplicarVariaciones(tela) {
  const variaciones = {
    'SHAMBRAY': 'SHAMBRAIN',
    'SHAMBRE': 'SHAMBRAIN',
  };
  return variaciones[tela] || tela;
}

function toInt(valor) {
  const num = parseInt(limpiarNumero(valor));
  return isNaN(num) ? 0 : num;
}

function toDecimal(valor) {
  const num = parseFloat(limpiarNumero(valor));
  if (isNaN(num)) return 0;
  return parseFloat(num.toString().match(/^-?\d+(?:\.\d{0,9})?/)[0]);
}

function limpiarNumero(valor) {
  if (valor === null || valor === undefined) return '';
  let s = valor.toString().trim().replace(/,/g, '.');
  s = s.replace(/[^\d.\-]/g, '');
  return s;
}

function formatearFecha(fecha) {
  if (fecha instanceof Date) {
    return Utilities.formatDate(fecha, "GMT-5", "yyyy-MM-dd");
  }
  return fecha || '';
}

function escaparTexto(texto) {
  if (!texto) return '';
  return texto.toString().replace(/'/g, "\\'");
}
