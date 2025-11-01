# Mapeos Exactos para Google Apps Script

## 🔢 IDs de Operarios (FIJOS)

```javascript
const OPERARIOS = {
  'PAOLA': 3,
  'JULIAN': 4,
  'ADRIAN': 5
};
```

## ⏰ IDs de Horas

Basado en `HorasSeeder.php`:

```javascript
const HORAS = {
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
```

## 🔧 IDs de Máquinas

Basado en `MaquinasTelasSeeder.php`:

```javascript
const MAQUINAS = {
  'BANANA': 1,
  'VERTICAL': 2,
  'TIJERAS': 3
};
```

## 🧵 IDs de Telas

Basado en `MaquinasTelasSeeder.php`:

### Grupo 1 (IDs 1-31)
```javascript
const TELAS_GRUPO1 = {
  'NAFLIX': 1,
  'POLUX': 2,
  'POLO': 3,
  'SHELSY': 4,
  'HIDROTECH': 5,
  'ALFONSO': 6,
  'MADRIGAL': 7,
  'SPORTWEAR': 8,
  'NATIVA': 9,
  'SUDADERA': 10,
  'OXFORD VESTIR': 11,
  'PANTALON DE VESTIR': 12,
  'BRAGAS': 13,
  'CONJUNTO ANTIFLUIDO': 14,
  'BRAGAS DRILL': 15,
  'SPEED': 16,
  'PIQUE': 17,
  'IGNIFUGO': 18,
  'COFIAS': 19,
  'BOLSA QUIRURGICA': 20,
  'FORROS': 21,
  'TOP PLUX': 22,
  'NOVACRUM': 23,
  'CEDACRON': 24,
  'DACRON': 25,
  'ENTRETELA': 26,
  'NAUTICA': 27,
  'CHAQUETA ORION': 28,
  'MICRO TITAN': 29,
  'SPRAY RIB': 30,
  'DOBLE PUNTO': 31
};
```

### Grupo 2 (IDs 32-43)
```javascript
const TELAS_GRUPO2 = {
  'OXFORD': 32,
  'DRILL': 33,
  'GOLIAT': 34,
  'BOLSILLO': 35,
  'SANSON': 36,
  'PANTALON ORION': 37,
  'SEGAL WIKING': 38,
  'JEANS': 39,
  'SHAMBRAIN': 40,
  'NAPOLES': 41,
  'DACRUM': 42,
  'RETACEO DRILL': 43
};
```

## 📝 Código Completo para Copiar

Reemplaza las funciones de mapeo en tu script con estas versiones exactas:

```javascript
/**
 * Mapea el nombre del operario a su ID en la base de datos
 */
function mapearOperario(nombre) {
  if (!nombre) return null;
  
  const nombreUpper = nombre.toString().trim().toUpperCase();
  
  const mapeo = {
    'PAOLA': 3,
    'JULIAN': 4,
    'ADRIAN': 5
  };
  
  return mapeo[nombreUpper] || null;
}

/**
 * Mapea la hora a hora_id
 */
function mapearHora(hora) {
  if (!hora) return 1;
  
  const horaStr = hora.toString().trim();
  
  const mapeo = {
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
  
  return mapeo[horaStr] || 1;
}

/**
 * Mapea el nombre de la máquina a maquina_id
 */
function mapearMaquina(maquina) {
  if (!maquina) return 1;
  
  const maquinaStr = maquina.toString().trim().toUpperCase();
  
  const mapeo = {
    'BANANA': 1,
    'VERTICAL': 2,
    'TIJERAS': 3
  };
  
  return mapeo[maquinaStr] || 1;
}

/**
 * Mapea el nombre de la tela a tela_id
 */
function mapearTela(tela) {
  if (!tela) return 1;
  
  const telaStr = tela.toString().trim().toUpperCase();
  
  const mapeo = {
    // Grupo 1
    'NAFLIX': 1,
    'POLUX': 2,
    'POLO': 3,
    'SHELSY': 4,
    'HIDROTECH': 5,
    'ALFONSO': 6,
    'MADRIGAL': 7,
    'SPORTWEAR': 8,
    'NATIVA': 9,
    'SUDADERA': 10,
    'OXFORD VESTIR': 11,
    'PANTALON DE VESTIR': 12,
    'BRAGAS': 13,
    'CONJUNTO ANTIFLUIDO': 14,
    'BRAGAS DRILL': 15,
    'SPEED': 16,
    'PIQUE': 17,
    'IGNIFUGO': 18,
    'COFIAS': 19,
    'BOLSA QUIRURGICA': 20,
    'FORROS': 21,
    'TOP PLUX': 22,
    'NOVACRUM': 23,
    'CEDACRON': 24,
    'DACRON': 25,
    'ENTRETELA': 26,
    'NAUTICA': 27,
    'CHAQUETA ORION': 28,
    'MICRO TITAN': 29,
    'SPRAY RIB': 30,
    'DOBLE PUNTO': 31,
    // Grupo 2
    'OXFORD': 32,
    'DRILL': 33,
    'GOLIAT': 34,
    'BOLSILLO': 35,
    'SANSON': 36,
    'PANTALON ORION': 37,
    'SEGAL WIKING': 38,
    'JEANS': 39,
    'SHAMBRAIN': 40,
    'NAPOLES': 41,
    'DACRUM': 42,
    'RETACEO DRILL': 43
  };
  
  return mapeo[telaStr] || 1;
}
```

## ⚠️ Notas Importantes

1. **Sensibilidad a mayúsculas/minúsculas:** Todos los mapeos convierten a MAYÚSCULAS antes de comparar
2. **Valores por defecto:** Si no se encuentra el valor, se retorna 1 (primer registro)
3. **Validación:** El mapeo de operarios retorna `null` si no se encuentra, para detectar errores
4. **Orden de IDs:** Los IDs de telas son secuenciales según el orden en el seeder

## 🔍 Verificación en Base de Datos

Después de ejecutar los seeders, puedes verificar los IDs con estas consultas:

```sql
-- Ver operarios
SELECT id, name, email FROM users WHERE role_id = 3;

-- Ver horas
SELECT id, hora, rango FROM horas ORDER BY id;

-- Ver máquinas
SELECT id, nombre_maquina FROM maquinas ORDER BY id;

-- Ver telas
SELECT id, nombre_tela FROM telas ORDER BY id;

-- Ver tiempos de ciclo
SELECT tc.id, t.nombre_tela, m.nombre_maquina, tc.tiempo_ciclo
FROM tiempo_ciclos tc
JOIN telas t ON tc.tela_id = t.id
JOIN maquinas m ON tc.maquina_id = m.id
ORDER BY t.id, m.id;
```
