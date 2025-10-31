# Seeder de Máquinas, Telas y Tiempos de Ciclo

## Descripción
Este seeder crea los registros para las 3 máquinas de corte (BANANA, VERTICAL, TIJERAS) junto con sus telas asociadas y tiempos de ciclo correspondientes.

## Estructura de Datos

### Grupo 1: Tiempos de ciclo altos
- **BANANA**: 97 segundos
- **VERTICAL**: 130 segundos
- **TIJERAS**: 97 segundos

**Telas asociadas:**
- NAFLIX, POLUX, POLO, SHELSY, HIDROTECH, ALFONSO, MADRIGAL, SPORTWEAR, NATIVA
- SUDADERA, OXFORD VESTIR, PANTALON DE VESTIR
- BRAGAS, CONJUNTO ANTIFLUIDO, BRAGAS DRILL
- SPEED, PIQUE, IGNIFUGO
- COFIAS, BOLSA QUIRURGICA, FORROS
- TOP PLUX, NOVACRUM, CEDACRON, DACRON, ENTRETELA
- NAUTICA, CHAQUETA ORION, MICRO TITAN, SPRAY RIB, DOBLE PUNTO

### Grupo 2: Tiempos de ciclo bajos
- **BANANA**: 45 segundos
- **VERTICAL**: 114 segundos
- **TIJERAS**: 45 segundos

**Telas asociadas:**
- OXFORD, DRILL, GOLIAT, BOLSILLO, SANSON
- PANTALON ORION, SEGAL WIKING
- JEANS, SHAMBRAIN, NAPOLES, DACRUM, RETACEO DRILL

## Cómo ejecutar el seeder

### Opción 1: Ejecutar solo este seeder
```bash
php artisan db:seed --class=MaquinasTelasSeeder
```

### Opción 2: Ejecutar todos los seeders
```bash
php artisan db:seed
```

### Opción 3: Refrescar la base de datos y ejecutar seeders
```bash
php artisan migrate:fresh --seed
```

## Registros creados
- **3 máquinas**: BANANA, VERTICAL, TIJERAS
- **43 telas**: 31 del grupo 1 + 12 del grupo 2
- **129 tiempos de ciclo**: 43 telas × 3 máquinas

## Notas importantes
- El seeder limpia las tablas existentes antes de insertar los nuevos datos
- Cada tela tiene un tiempo de ciclo definido para cada una de las 3 máquinas
- Los tiempos de ciclo están en segundos
