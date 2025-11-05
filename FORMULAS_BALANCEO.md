# Fórmulas de Balanceo - Métricas de Producción

## Fecha: 2025-11-04

## Métricas Básicas (Vista Simple)

### 1. **Tiempo Disponible en Horas**
```
T. Disponible Horas = Horas/turno × Turnos × Total operarios
```
**Ejemplo:**
```
7.5 × 1 × 10 = 75 horas
```

### 2. **Tiempo Disponible en Segundos**
```
T. Disponible Segundos = T. Disponible Horas × 3600
```
**Ejemplo:**
```
75 × 3600 = 270,000 segundos
```

### 3. **SAM Total**
```
SAM Total = Σ (SAM de cada operación)
```
**Ejemplo:**
```
4.8 + 4.8 + 4.8 + ... = 757.1 segundos
```

### 4. **Meta Teórica**
```
Meta Teórica = ROUND(T. Disponible Segundos / SAM Total)
```
**Ejemplo:**
```
ROUND(270,000 / 757.1) = 357 unidades
```

### 5. **Meta Real (90%)**
```
Meta Real = Meta Teórica × 0.90
```
**Ejemplo:**
```
357 × 0.90 = 321.30 unidades
```
**Nota:** Se muestra con 2 decimales para precisión.

---

## Métricas de Cuello de Botella (Vista Avanzada)

### 6. **Operario Cuello de Botella**
```
Operación con el mayor SAM
```
**Ejemplo:**
```
OP2 (operación "Botas x2" con SAM = 75.0)
```

### 7. **Tiempo Cuello de Botella**
```
SAM de la operación con mayor tiempo
```
**Ejemplo:**
```
75.0 segundos
```

### 8. **SAM Real**
```
SAM Real = Tiempo Cuello Botella × Total Operarios
```
**Ejemplo:**
```
75.0 × 10 = 750 segundos
```

### 9. **Meta Real (Cuello de Botella)**
```
Meta Real (CB) = FLOOR(T. Disponible Segundos / SAM Real)
```
**Ejemplo:**
```
FLOOR(270,000 / 750) = 360 unidades
```

### 10. **Meta Sugerida (85%)**
```
Meta Sugerida = FLOOR(Meta Real (CB) × 0.85)
```
**Ejemplo:**
```
FLOOR(360 × 0.85) = 306 unidades
```

---

## Implementación en el Sistema

### Modelo PHP (`app/Models/Balanceo.php`)

```php
public function calcularMetricas()
{
    // 1. SAM Total
    $this->sam_total = $this->operaciones()->sum('sam');

    // 2. Tiempo Disponible en Horas
    $this->tiempo_disponible_horas = $this->horas_por_turno * $this->turnos * $this->total_operarios;
    
    // 3. Tiempo Disponible en Segundos
    $this->tiempo_disponible_segundos = $this->tiempo_disponible_horas * 3600;

    // 4 y 5. Meta Teórica y Meta Real
    if ($this->sam_total > 0) {
        $this->meta_teorica = round($this->tiempo_disponible_segundos / $this->sam_total);
        $this->meta_real = $this->meta_teorica * 0.90; // Con decimales
    }

    // 6-10. Cuello de Botella
    $cuelloBotella = $this->operaciones()->orderBy('sam', 'desc')->first();
    if ($cuelloBotella) {
        $this->operario_cuello_botella = $cuelloBotella->operario;
        $this->tiempo_cuello_botella = $cuelloBotella->sam;
        $this->sam_real = $cuelloBotella->sam * $this->total_operarios;
        
        $metaRealCuelloBotella = floor($this->tiempo_disponible_segundos / $this->sam_real);
        $this->meta_sugerida_85 = floor($metaRealCuelloBotella * 0.85);
    }

    $this->save();
}
```

### Vista Blade (Frontend)

**Vista Simple:**
- Total de operarios (editable)
- Turnos de trabajo (editable)
- Horas/turno (editable)
- T. Disponible en Horas (calculado)
- T. Disponible en Segundos (calculado)
- **SAM** (destacado, 1 decimal)
- Meta teórica (entero)
- **Meta Real 90%** (destacado, 2 decimales)

**Vista Cuello de Botella:**
- Operario cuello de botella
- Tiempo cuello de botella (1 decimal)
- SAM Real (1 decimal)
- Meta Real (cuello de botella)
- **Meta sugerida 85%** (destacado)

---

## Tipos de Datos

| Campo | Tipo | Formato Display |
|-------|------|----------------|
| `total_operarios` | `integer` | Entero |
| `turnos` | `integer` | Entero |
| `horas_por_turno` | `double` | 1 decimal |
| `tiempo_disponible_horas` | `double` | 2 decimales |
| `tiempo_disponible_segundos` | `double` | Entero |
| `sam_total` | `double` | 1 decimal |
| `meta_teorica` | `integer` | Entero |
| `meta_real` | `double` | **2 decimales** |
| `operario_cuello_botella` | `string` | Texto |
| `tiempo_cuello_botella` | `double` | 1 decimal |
| `sam_real` | `double` | 1 decimal |
| `meta_sugerida_85` | `integer` | Entero |

---

## Interfaz de Usuario

### Botón Toggle
- **Icono:** `analytics` (Material Symbols)
- **Texto:** "Cuello de Botella" / "Vista Simple"
- **Tooltip:** Muestra descripción al pasar el mouse
- **Transición:** Suave entre vistas

### Colores
- **Destacado:** `#ff9d58` (naranja)
- **Fondo:** Transparente con opacidad
- **Bordes:** Sutil con color naranja

---

## Notas Importantes

1. ✅ **Meta Real** ahora usa `DOUBLE` para mostrar decimales (ej: 321.30)
2. ✅ **Vista Simple** muestra métricas básicas por defecto
3. ✅ **Vista Cuello de Botella** se activa con botón toggle
4. ✅ Todas las fórmulas coinciden exactamente con Excel
5. ✅ Los campos editables actualizan automáticamente todos los cálculos
