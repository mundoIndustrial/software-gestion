# ESPECIFICACIÓN: Almacenamiento Relacional de Tallas

## CONCEPTO CRÍTICO: DOS TIPOS DE TALLAS DIFERENTES

```
┌─ PEDIDO
│  └─ PRENDA_PEDIDO (ej: Camiseta)
│     └─ PRENDA_PEDIDO_TALLAS (LO QUE PIDIÓ EL CLIENTE)
│        ├─ DAMA XS: 5 unidades
│        ├─ DAMA S:  5 unidades
│        └─ DAMA M:  5 unidades
│
└─ PROCESO_PRENDA_DETALLE (ej: Bordado en Camiseta)
   └─ PEDIDOS_PROCESOS_PRENDA_TALLAS (LO QUE SE VA A PROCESAR)
      ├─ DAMA XS: 5 unidades (puede ser diferente al pedido)
      ├─ DAMA S:  4 unidades (puede ser parcial)
      └─ DAMA M:  5 unidades
```

**IMPORTANTE**: No son la misma tabla. Son **tablas relacionales separadas** porque:
- `prenda_pedido_tallas` = Qué pidió el cliente
- `pedidos_procesos_prenda_tallas` = Qué cantidad de cada talla se procesa en este paso

Ejemplo real:
- Cliente pide: Camiseta en tallas DAMA XS(5), S(5), M(5) = 15 total
- Bordado: Solo procesa DAMA XS(5), S(4), M(5) = 14 (1 S quedó pendiente de aprobación)
- Estampado: Procesa DAMA XS(5), S(5), M(5) = 15 (ya todas aprobadas)

---

## Objetivo
Garantizar que las tallas se almacenan de forma relacional (1 registro por talla+genero+cantidad) en TODAS las tablas, NO como JSON.

## Tablas Afectadas

### 1. prenda_pedido_tallas
**Contexto**: Tallas en prendas dentro de un pedido

```sql
Table: prenda_pedido_tallas
Columns:
  - id (bigint, UN, AI, PK)
  - prenda_pedido_id (bigint, UN, FK -> prenda_pedido)
  - genero (enum: DAMA, CABALLERO, UNISEX)
  - talla (varchar(50)) 
  - cantidad (int, UN)
  - created_at (timestamp)
  - updated_at (timestamp)
```

**Ejemplo de datos**:
```
id | prenda_pedido_id | genero     | talla | cantidad | created_at | updated_at
1  | 10               | DAMA       | XS    | 5        | ...        | ...
2  | 10               | DAMA       | S     | 5        | ...        | ...
3  | 10               | DAMA       | M     | 5        | ...        | ...
4  | 11               | CABALLERO  | 30    | 3        | ...        | ...
5  | 11               | CABALLERO  | 32    | 4        | ...        | ...
6  | 11               | CABALLERO  | 34    | 3        | ...        | ...
```

**Mapeo del Agregado PrendaPedido**:
```php
[
  'DAMA' => [
    'XS' => 5,
    'S' => 5,
    'M' => 5
  ],
  'CABALLERO' => [
    '30' => 3,
    '32' => 4,
    '34' => 3
  ]
]
```

---

### 2. pedidos_procesos_prenda_tallas
**Contexto**: Tallas en procesos (ej: bordado, estampado) para una prenda

```sql
Table: pedidos_procesos_prenda_tallas
Columns:
  - id (bigint, UN, AI, PK)
  - proceso_prenda_detalle_id (bigint, UN, FK -> procesos_prenda_detalles)
  - genero (enum: DAMA, CABALLERO, UNISEX)
  - talla (varchar(50))
  - cantidad (int, UN)
  - created_at (timestamp)
  - updated_at (timestamp)
```

**Ejemplo de datos**:
```
id | proceso_prenda_detalle_id | genero     | talla | cantidad | created_at | updated_at
1  | 5                         | DAMA       | XS    | 5        | ...        | ...
2  | 5                         | DAMA       | S     | 5        | ...        | ...
3  | 5                         | DAMA       | M     | 5        | ...        | ...
4  | 6                         | CABALLERO  | 30    | 3        | ...        | ...
5  | 6                         | CABALLERO  | 32    | 4        | ...        | ...
6  | 6                         | CABALLERO  | 34    | 3        | ...        | ...
```

**Mapeo del Entity ProcesoPrendaDetalle**:
```php
[
  'DAMA' => [
    'XS' => 5,
    'S' => 5,
    'M' => 5
  ],
  'CABALLERO' => [
    '30' => 3,
    '32' => 4,
    '34' => 3
  ]
]
```

---

## Implementación: Guardar Tallas (Patrón Estándar)

Todas las tablas de tallas deben seguir este patrón en el Repository:

```php
private function guardarTallas(int $recordId, array $tallas, string $tableName, string $foreignKeyColumn): void
{
    // Limpiar registros anteriores
    DB::table($tableName)->where($foreignKeyColumn, $recordId)->delete();

    // Insertar un registro por talla
    foreach ($tallas as $genero => $tallasPorGenero) {
        foreach ($tallasPorGenero as $talla => $cantidad) {
            DB::table($tableName)->insert([
                $foreignKeyColumn => $recordId,
                'genero' => $genero,
                'talla' => $talla,
                'cantidad' => $cantidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

## Implementación: Reconstruir Tallas (Patrón Estándar)

```php
private function reconstruirTallas(int $recordId, string $tableName, string $foreignKeyColumn): array
{
    $registros = DB::table($tableName)
        ->where($foreignKeyColumn, $recordId)
        ->get();

    $tallas = [];
    foreach ($registros as $registro) {
        if (!isset($tallas[$registro->genero])) {
            $tallas[$registro->genero] = [];
        }
        $tallas[$registro->genero][$registro->talla] = $registro->cantidad;
    }

    return $tallas;
}
```

---

## Checklist de Implementación

- [x] PedidoRepositoryImpl: Guardar tallas en `prenda_pedido_tallas`
- [x] PedidoRepositoryImpl: Reconstruir tallas desde `prenda_pedido_tallas`
- [ ] ProcesoPrendaDetalleRepositoryImpl: Guardar tallas en `pedidos_procesos_prenda_tallas`
- [ ] ProcesoPrendaDetalleRepositoryImpl: Reconstruir tallas desde `pedidos_procesos_prenda_tallas`

---

## NO Hacer (Antipatrones)

❌ **NO guardar tallas como JSON en la prenda_pedido**:
```php
// INCORRECTO
'tallas' => json_encode($prenda->tallas())  // ← NO hacer esto
```

❌ **NO usar columnas separadas por género**:
```php
// INCORRECTO
'tallasDama' => json_encode($dama),          // ← NO hacer esto
'tallasCalabrero' => json_encode($caballero) // ← NO hacer esto
```

 **SÍ usar tabla relacional normalizada**:
```php
// CORRECTO: Un registro por talla
DB::table('prenda_pedido_tallas')->insert([
    'prenda_pedido_id' => 10,
    'genero' => 'DAMA',
    'talla' => 'XS',
    'cantidad' => 5,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

---

## Validación

Después de guardar, la base de datos debe verse así:

```sql
-- Verificar prenda_pedido_tallas
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 10;
-- Resultado: 3 registros (XS, S, M)

-- Verificar pedidos_procesos_prenda_tallas
SELECT * FROM pedidos_procesos_prenda_tallas WHERE proceso_prenda_detalle_id = 5;
-- Resultado: 3 registros (XS, S, M)
```

**No** debe haber registros con tallas como JSON o nulas.
