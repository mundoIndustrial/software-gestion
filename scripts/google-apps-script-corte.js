/**
 * Script de Google Apps Script para exportar datos de Excel a SQL
 * Tabla: registro_piso_corte
 * 
 * ESTRATEGIA: Crear registros automáticamente si no existen
 * - Operarios: Se crean con role_id = 3 (cortador)
 * - Máquinas: Se crean automáticamente
 * - Telas: Se crean automáticamente
 * - Tiempos de ciclo: Se crean automáticamente según seeder
 * - Horas: Se crean automáticamente
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
  const tamañoLote = 1000; // controlar consumo de memoria
  let totalProcesadas = 0;
  let errores = [];
  
  // Sets para rastrear qué ya hemos insertado
  const operariosInsertados = new Set();
  const maquinasInsertadas = new Set();
  const telasInsertadas = new Set();
  const horasInsertadas = new Set();
  const tiemposCicloInsertados = new Set();

  // Colecciones para agrupar INSERTs
  const operariosParaInsertar = new Map();
  const maquinasParaInsertar = new Map();
  const telasParaInsertar = new Map();
  const horasParaInsertar = new Map();
  const tiemposCicloParaInsertar = new Map();
  const registrosCorte = [];
  
  for (let i = 0; i < datos.length; i += tamañoLote) {
    const lote = datos.slice(i, i + tamañoLote);

    lote.forEach((fila, index) => {
      const [
        marcaTemporal,           // 0
        fecha,                   // 1
        ordenProduccion,         // 2
        hora,                    // 3
        operario,                // 4
        maquina,                 // 5
        porcionTiempo,           // 6
        cantidadProducida,       // 7
        tiempoCiclo,             // 8
        paradasProgramadas,      // 9
        tiempoParaProgramada,    // 10
        paradasNoProgramadas,    // 11
        tiempoParadaNoProgramada,// 12
        tipoExtendido,           // 13
        numeroCapas,             // 14
        tiempoExtendido,         // 15
        trazado,                 // 16
        tiempoTrazado,           // 17
        actividad,               // 18
        columna19,               // 19 (no usada)
        tela,                    // 20
        tiempoDisponible,        // 21
        meta,                    // 22
        eficiencia,              // 23
        novedad                  // 24
      ] = fila;

      // Validaciones básicas
      if (!fecha || !ordenProduccion) {
        errores.push(`Fila ${i + index + 2}: Fecha u orden de producción vacía`);
        return;
      }

      const operarioNombre = (operario || '').toString().trim().toUpperCase();
      const maquinaNombre = normalizarMaquina((maquina || '').toString().trim().toUpperCase());
      const telasArray = normalizarTela((tela || '').toString().trim());
      const horaRango = normalizarRangoHora((hora || '').toString().trim());

      // Agregar operario a la colección si no existe
      if (operarioNombre && !operariosInsertados.has(operarioNombre)) {
        const email = `${operarioNombre.toLowerCase()}@mundoindustrial.com`;
        operariosParaInsertar.set(operarioNombre, { nombre: operarioNombre, email: email });
        operariosInsertados.add(operarioNombre);
      }

      // Agregar máquina a la colección si no existe (solo si no es NULL/N.A)
      if (maquinaNombre && maquinaNombre !== 'NULL' && !maquinasInsertadas.has(maquinaNombre)) {
        maquinasParaInsertar.set(maquinaNombre, { nombre: maquinaNombre });
        maquinasInsertadas.add(maquinaNombre);
      }

      // Agregar cada tela a la colección si no existe
      telasArray.forEach(telaNombre => {
        if (telaNombre && !telasInsertadas.has(telaNombre)) {
          telasParaInsertar.set(telaNombre, { nombre: telaNombre });
          telasInsertadas.add(telaNombre);
        }
        
        // Agregar tiempo_ciclo a la colección si no existe (solo si hay máquina válida)
        if (maquinaNombre && telaNombre && maquinaNombre !== 'NULL') {
          const claveTiempoCiclo = `${telaNombre}|${maquinaNombre}`;
          if (!tiemposCicloInsertados.has(claveTiempoCiclo)) {
            const tiempoCiclo = obtenerTiempoCiclo(telaNombre, maquinaNombre);
            tiemposCicloParaInsertar.set(claveTiempoCiclo, { 
              tela: telaNombre, 
              maquina: maquinaNombre, 
              tiempo: tiempoCiclo 
            });
            tiemposCicloInsertados.add(claveTiempoCiclo);
          }
        }
      });

      // Agregar hora a la colección si no existe
      if (horaRango && !horasInsertadas.has(horaRango)) {
        const horaNum = extraerNumeroHora(horaRango);
        horasParaInsertar.set(horaRango, { numero: horaNum, rango: horaRango });
        horasInsertadas.add(horaRango);
      }

      // Agregar registros de corte a la colección
      // Si hay múltiples telas, crear un registro por cada tela
      // Si no hay telas, crear un registro con tela NULL
      const telasParaRegistro = telasArray.length > 0 ? telasArray : [null];
      
      telasParaRegistro.forEach(telaNombre => {
        registrosCorte.push({
          fecha: formatearFecha(fecha),
          ordenProduccion: escaparTexto(ordenProduccion),
          porcionTiempo: toDecimal(porcionTiempo),
          cantidad: toInt(cantidadProducida),
          tiempoCiclo: toDecimal(tiempoCiclo),
          paradasProgramadas: escaparTexto(paradasProgramadas),
          tiempoParaProgramada: toDecimal(tiempoParaProgramada),
          paradasNoProgramadas: escaparTexto(paradasNoProgramadas),
          tiempoParadaNoProgramada: toDecimal(tiempoParadaNoProgramada),
          tipoExtendido: escaparTexto(tipoExtendido),
          numeroCapas: toInt(numeroCapas),
          tiempoExtendido: toInt(tiempoExtendido),
          trazado: escaparTexto(trazado),
          tiempoTrazado: toDecimal(tiempoTrazado),
          actividad: escaparTexto(actividad),
          tiempoDisponible: toDecimal(tiempoDisponible),
          meta: toDecimal(meta),
          eficiencia: toDecimal(eficiencia),
          horaRango: escaparTexto(horaRango),
          operarioNombre: operarioNombre,
          maquinaNombre: maquinaNombre,
          telaNombre: telaNombre
        });
        
        totalProcesadas++;
      });
    });
  }

  // Generar SQL consolidado
  contenidoTotal = generarSQLConsolidado(
    operariosParaInsertar,
    maquinasParaInsertar,
    telasParaInsertar,
    horasParaInsertar,
    tiemposCicloParaInsertar,
    registrosCorte
  );

  // Crear el archivo de texto final
  const archivo = carpeta.createFile(nombreArchivo, contenidoTotal, MimeType.PLAIN_TEXT);

  let mensaje = `✅ Archivo SQL generado con éxito.
📄 Total registros procesados: ${totalProcesadas}
📁 Guardado en carpeta: ${carpeta.getName()}
🔗 Enlace: ${archivo.getUrl()}`;

  if (errores.length > 0) {
    mensaje += `\n\n⚠️ ERRORES ENCONTRADOS (${errores.length}):\n${errores.slice(0, 10).join('\n')}`;
    if (errores.length > 10) {
      mensaje += `\n... y ${errores.length - 10} más`;
    }
  }

  SpreadsheetApp.getUi().alert(mensaje);
}

// ===== FUNCIÓN PRINCIPAL PARA GENERAR SQL CONSOLIDADO =====

/**
 * Genera SQL consolidado con INSERTs masivos
 */
function generarSQLConsolidado(operarios, maquinas, telas, horas, tiemposCiclo, registros) {
  let sql = "-- ===== SCRIPT DE IMPORTACIÓN DE DATOS DE CORTE =====\n";
  sql += `-- Generado: ${new Date().toLocaleString()}\n`;
  sql += `-- Total registros: ${registros.length}\n\n`;
  
  const password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
  
  // 1. OPERARIOS
  if (operarios.size > 0) {
    sql += "-- ===== CREAR OPERARIOS =====\n";
    const valoresOperarios = Array.from(operarios.values()).map(op => 
      `SELECT '${op.nombre}' as name, '${op.email}' as email, '${password}' as password, (SELECT id FROM roles WHERE name = 'cortador' LIMIT 1) as role_id, NOW() as created_at, NOW() as updated_at`
    ).join('\nUNION ALL\n');
    
    sql += `INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at)\n${valoresOperarios};\n\n`;
  }
  
  // 2. MÁQUINAS
  if (maquinas.size > 0) {
    sql += "-- ===== CREAR MÁQUINAS =====\n";
    const valoresMaquinas = Array.from(maquinas.values()).map(m => 
      `SELECT '${m.nombre}' as nombre_maquina, NOW() as created_at, NOW() as updated_at`
    ).join('\nUNION ALL\n');
    
    sql += `INSERT IGNORE INTO maquinas (nombre_maquina, created_at, updated_at)\n${valoresMaquinas};\n\n`;
  }
  
  // 3. TELAS
  if (telas.size > 0) {
    sql += "-- ===== CREAR TELAS =====\n";
    const valoresTelas = Array.from(telas.values()).map(t => 
      `SELECT '${t.nombre}' as nombre_tela, NOW() as created_at, NOW() as updated_at`
    ).join('\nUNION ALL\n');
    
    sql += `INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)\n${valoresTelas};\n\n`;
  }
  
  // 4. HORAS
  if (horas.size > 0) {
    sql += "-- ===== CREAR HORAS =====\n";
    const valoresHoras = Array.from(horas.values()).map(h => 
      `SELECT ${h.numero} as hora, '${h.rango}' as rango, NOW() as created_at, NOW() as updated_at`
    ).join('\nUNION ALL\n');
    
    sql += `INSERT IGNORE INTO horas (hora, rango, created_at, updated_at)\n${valoresHoras};\n\n`;
  }
  
  // 5. TIEMPOS DE CICLO
  if (tiemposCiclo.size > 0) {
    sql += "-- ===== CREAR TIEMPOS DE CICLO =====\n";
    const valoresTiemposCiclo = Array.from(tiemposCiclo.values()).map(tc => 
      `SELECT (SELECT id FROM telas WHERE nombre_tela = '${tc.tela}' LIMIT 1) as tela_id, (SELECT id FROM maquinas WHERE nombre_maquina = '${tc.maquina}' LIMIT 1) as maquina_id, ${tc.tiempo} as tiempo_ciclo, NOW() as created_at, NOW() as updated_at`
    ).join('\nUNION ALL\n');
    
    sql += `INSERT IGNORE INTO tiempo_ciclos (tela_id, maquina_id, tiempo_ciclo, created_at, updated_at)\n${valoresTiemposCiclo};\n\n`;
  }
  
  // 6. REGISTROS DE CORTE (en lotes de 500 para evitar queries muy grandes)
  if (registros.length > 0) {
    sql += "-- ===== INSERTAR REGISTROS DE CORTE =====\n";
    const tamañoLote = 500;
    
    for (let i = 0; i < registros.length; i += tamañoLote) {
      const lote = registros.slice(i, i + tamañoLote);
      
      const valoresRegistros = lote.map(r => {
        const maquinaId = r.maquinaNombre ? `(SELECT id FROM maquinas WHERE nombre_maquina = '${r.maquinaNombre}' LIMIT 1)` : 'NULL';
        const telaId = r.telaNombre ? `(SELECT id FROM telas WHERE nombre_tela = '${r.telaNombre}' LIMIT 1)` : 'NULL';
        
        return `SELECT '${r.fecha}', '${r.ordenProduccion}', ${r.porcionTiempo}, ${r.cantidad}, ${r.tiempoCiclo}, '${r.paradasProgramadas}', ${r.tiempoParaProgramada}, '${r.paradasNoProgramadas}', ${r.tiempoParadaNoProgramada}, '${r.tipoExtendido}', ${r.numeroCapas}, ${r.tiempoExtendido}, '${r.trazado}', ${r.tiempoTrazado}, '${r.actividad}', ${r.tiempoDisponible}, ${r.meta}, ${r.eficiencia}, (SELECT id FROM horas WHERE rango = '${r.horaRango}' LIMIT 1), (SELECT id FROM users WHERE UPPER(name) = '${r.operarioNombre}' LIMIT 1), ${maquinaId}, ${telaId}, NOW(), NOW()`;
      }).join('\nUNION ALL\n');
      
      sql += `INSERT INTO registro_piso_corte (fecha, orden_produccion, porcion_tiempo, cantidad, tiempo_ciclo, paradas_programadas, tiempo_para_programada, paradas_no_programadas, tiempo_parada_no_programada, tipo_extendido, numero_capas, tiempo_extendido, trazado, tiempo_trazado, actividad, tiempo_disponible, meta, eficiencia, hora_id, operario_id, maquina_id, tela_id, created_at, updated_at)\n${valoresRegistros};\n\n`;
    }
  }
  
  sql += "-- ===== FIN DEL SCRIPT =====\n";
  return sql;
}

// ===== FUNCIONES PARA GENERAR INSERTs (YA NO SE USAN) =====

/**
 * Genera INSERT para operario si no existe
 */
function generarInsertOperario(nombre) {
  const email = `${nombre.toLowerCase()}@mundoindustrial.com`;
  const password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password genérico
  
  return `
-- Crear operario: ${nombre}
INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at)
SELECT '${nombre}', '${email}', '${password}', 
       (SELECT id FROM roles WHERE name = 'cortador' LIMIT 1),
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE UPPER(name) = '${nombre}');

`;
}

/**
 * Genera INSERT para máquina si no existe
 */
function generarInsertMaquina(nombre) {
  return `
-- Crear máquina: ${nombre}
INSERT IGNORE INTO maquinas (nombre_maquina, created_at, updated_at)
SELECT '${nombre}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM maquinas WHERE nombre_maquina = '${nombre}');

`;
}

/**
 * Genera INSERT para tela si no existe
 */
function generarInsertTela(nombre) {
  return `
-- Crear tela: ${nombre}
INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)
SELECT '${nombre}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM telas WHERE nombre_tela = '${nombre}');

`;
}

/**
 * Genera INSERT para hora si no existe
 */
function generarInsertHora(rango) {
  // Extraer el número de hora del rango
  const horaNum = extraerNumeroHora(rango);
  
  // Normalizar el rango (puede venir como "HORA 07" o "08:00am - 09:00am")
  const rangoNormalizado = normalizarRangoHora(rango);
  
  return `
-- Crear hora: ${rangoNormalizado}
INSERT IGNORE INTO horas (hora, rango, created_at, updated_at)
SELECT ${horaNum}, '${escaparTexto(rangoNormalizado)}', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM horas WHERE rango = '${escaparTexto(rangoNormalizado)}');

`;
}

/**
 * Genera INSERT para tiempo_ciclo si no existe
 * Basado en el seeder MaquinasTelasSeeder
 */
function generarInsertTiempoCiclo(telaNombre, maquinaNombre) {
  // Determinar el tiempo de ciclo según el grupo de tela y máquina
  const tiempoCiclo = obtenerTiempoCiclo(telaNombre, maquinaNombre);
  
  return `
-- Crear tiempo_ciclo: ${telaNombre} + ${maquinaNombre}
INSERT IGNORE INTO tiempo_ciclos (tela_id, maquina_id, tiempo_ciclo, created_at, updated_at)
SELECT 
  (SELECT id FROM telas WHERE nombre_tela = '${telaNombre}' LIMIT 1),
  (SELECT id FROM maquinas WHERE nombre_maquina = '${maquinaNombre}' LIMIT 1),
  ${tiempoCiclo},
  NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM tiempo_ciclos 
  WHERE tela_id = (SELECT id FROM telas WHERE nombre_tela = '${telaNombre}' LIMIT 1)
  AND maquina_id = (SELECT id FROM maquinas WHERE nombre_maquina = '${maquinaNombre}' LIMIT 1)
);

`;
}

/**
 * Obtiene el tiempo de ciclo según la tela y máquina
 * Basado en MaquinasTelasSeeder.php
 */
function obtenerTiempoCiclo(telaNombre, maquinaNombre) {
  // Grupo 1: BANANA = 97, VERTICAL = 130, TIJERAS = 97
  const telasGrupo1 = [
    'NAFLIX', 'POLUX', 'POLO', 'SHELSY', 'HIDROTECH', 'ALFONSO', 'MADRIGAL',
    'SPORTWEAR', 'NATIVA', 'SUDADERA', 'OXFORD VESTIR', 'PANTALON DE VESTIR',
    'BRAGAS', 'CONJUNTO ANTIFLUIDO', 'BRAGAS DRILL', 'SPEED', 'PIQUE',
    'IGNIFUGO', 'COFIAS', 'BOLSA QUIRURGICA', 'FORROS', 'TOP PLUX',
    'NOVACRUM', 'CEDACRON', 'DACRON', 'ENTRETELA', 'NAUTICA',
    'CHAQUETA ORION', 'MICRO TITAN', 'SPRAY RIB', 'DOBLE PUNTO'
  ];
  
  // Grupo 2: BANANA = 45, VERTICAL = 114, TIJERAS = 45
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
  
  // Valor por defecto si no está en ningún grupo
  return 97;
}

/**
 * Normaliza el rango de hora
 * Convierte "HORA 07" a "02:00pm - 03:00pm" según el mapeo del seeder
 */
function normalizarRangoHora(rango) {
  if (!rango) return '08:00am - 09:00am';
  
  const rangoStr = rango.toString().trim().toUpperCase();
  
  // Si viene como "HORA 01", "HORA 02", etc.
  const match = rangoStr.match(/HORA\s*(\d+)/);
  if (match) {
    const horaNum = parseInt(match[1]);
    const mapeoRangos = {
      1: '08:00am - 09:00am',
      2: '09:00am - 10:00am',
      3: '10:00am - 11:00am',
      4: '11:00am - 12:00pm',
      5: '12:00pm - 01:00pm',
      6: '01:00pm - 02:00pm',
      7: '02:00pm - 03:00pm',
      8: '03:00pm - 04:00pm',
      9: '04:00pm - 05:00pm',
      10: '05:00pm - 06:00pm',
      11: '06:00pm - 07:00pm',
      12: '07:00pm - 08:00pm'
    };
    return mapeoRangos[horaNum] || '08:00am - 09:00am';
  }
  
  // Si ya viene como rango, devolverlo tal cual
  return rango;
}

/**
 * Extrae el número de hora de un rango
 */
function extraerNumeroHora(rango) {
  if (!rango) return 1;
  
  const rangoStr = rango.toString().trim().toUpperCase();
  
  // Si viene como "HORA 01", "HORA 02", etc.
  const match = rangoStr.match(/HORA\s*(\d+)/);
  if (match) {
    return parseInt(match[1]);
  }
  
  // Mapeo de rangos conocidos
  const mapeoHoras = {
    '08:00am - 09:00am': 1,
    '09:00am - 10:00am': 2,
    '10:00am - 11:00am': 3,
    '11:00am - 12:00pm': 4,
    '12:00pm - 01:00pm': 5,
    '01:00pm - 02:00pm': 6,
    '02:00pm - 03:00pm': 7,
    '03:00pm - 04:00pm': 8,
    '04:00pm - 05:00pm': 9,
    '05:00pm - 06:00pm': 10,
    '06:00pm - 07:00pm': 11,
    '07:00pm - 08:00pm': 12
  };
  
  return mapeoHoras[rango] || 1;
}


// ===== FUNCIONES DE NORMALIZACIÓN =====

/**
 * Normaliza el nombre de la máquina
 * Maneja casos como "N.A", "N/A", "NA" como NULL
 */
function normalizarMaquina(nombre) {
  if (!nombre) return null;
  
  const nombreUpper = nombre.toString().trim().toUpperCase();
  
  // Si es N.A, N/A, NA, o similar, retornar null
  if (['N.A', 'N/A', 'NA', 'N.A.', 'NINGUNA', 'NINGUNO'].includes(nombreUpper)) {
    return null;
  }
  
  return nombreUpper;
}

/**
 * Normaliza el nombre de la tela
 * Convierte a MAYÚSCULAS y maneja variaciones
 * Retorna un array de telas si hay múltiples separadas por - o /
 */
function normalizarTela(nombre) {
  if (!nombre) return [];
  
  let nombreStr = nombre.toString().trim().toUpperCase();
  
  // Separar por guiones o barras si hay múltiples telas
  let telas = nombreStr.split(/[-\/]/).map(t => t.trim()).filter(t => t.length > 0);
  
  // Mapeo de variaciones comunes
  const variaciones = {
    'SHAMBRAY': 'SHAMBRAIN',
    'SHAMBRE': 'SHAMBRAIN',
    // Agregar más variaciones si es necesario
  };
  
  // Normalizar cada tela
  telas = telas.map(tela => variaciones[tela] || tela);
  
  return telas;
}

/**
 * Obtiene el nombre principal de tela (primera si hay múltiples)
 */
function obtenerTelaPrincipal(telas) {
  if (!telas || telas.length === 0) return null;
  return telas[0];
}

// ===== FUNCIONES AUXILIARES =====

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
