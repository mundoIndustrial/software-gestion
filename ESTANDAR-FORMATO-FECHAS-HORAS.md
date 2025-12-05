# 📅 ESTÁNDAR DE FORMATO DE FECHAS Y HORAS

## 🎯 Objetivo
Mantener consistencia en todos los formatos de fecha y hora del proyecto usando:
- **Formato de Fecha:** `d/m/Y` (día/mes/año)
- **Formato de Hora:** `h:i A` (12h con AM/PM, hora estándar)

## ✅ FORMATO ESTANDARIZADO

### Combinación Completa (Fecha + Hora)
```php
->format('d/m/Y h:i A')
```
**Ejemplo:** `04/12/2025 05:56 PM`

### Con Segundos
```php
->format('d/m/Y h:i:s A')
```
**Ejemplo:** `04/12/2025 05:56:32 PM`

### Solo Hora
```php
->format('h:i A')
```
**Ejemplo:** `05:56 PM`

### Solo Fecha
```php
->format('d/m/Y')
```
**Ejemplo:** `04/12/2025`

## ❌ FORMATOS NO PERMITIDOS

- ❌ `H:i` (hora militar 24h)
- ❌ `Y-m-d` (año-mes-día)
- ❌ `m/d/Y` (mes/día/año)
- ❌ `d-m-Y` (día-mes-año con guiones)

### Ejemplos Incorrectos:
- ❌ `17:56` (sin AM/PM)
- ❌ `2025-12-04 17:56:32` (formato ISO sin traducción)
- ❌ `04-12-2025` (guiones en lugar de barras)

## 📝 ARCHIVOS CON FORMATOS DE FECHA/HORA

### ✅ Archivos Correctamente Formateados

| Archivo | Formato | Ejemplo |
|---------|---------|---------|
| `supervisor-pedidos/pdf.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |
| `vistas/control-calidad.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |
| `asesores/pedidos/index.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |
| `contador/index.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |
| `cotizaciones/index.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |
| `inventario-telas/index.blade.php` | `d/m/Y h:i A` | 04/12/2025 05:56 PM |

## 🔄 USANDO EN BLADE TEMPLATES

### Opción 1: Carbon directo (Recomendado)
```blade
{{ $pedido->fecha_de_creacion_de_orden->format('d/m/Y h:i A') }}
```

### Opción 2: Carbon::parse (si es string)
```blade
{{ \Carbon\Carbon::parse($pedido->fecha_anulacion)->format('d/m/Y h:i A') }}
```

### Opción 3: Ternario con valor por defecto
```blade
{{ $pedido->fecha_anulacion ? \Carbon\Carbon::parse($pedido->fecha_anulacion)->format('d/m/Y h:i A') : '-' }}
```

## 🔄 USANDO EN LARAVEL/PHP

```php
// Mostrar fecha actual
echo now()->format('d/m/Y h:i A');  // 04/12/2025 05:56 PM

// Guardar en BD (siempre usar timestamp completo)
'fecha_creacion' => now()  // 2025-12-04 17:56:32

// Mostrar fecha guardada
echo $modelo->fecha_creacion->format('d/m/Y h:i A');  // 04/12/2025 05:56 PM
```

## 📊 CONVERSIÓN DE HORAS

| Militar | Estándar | Descripción |
|---------|----------|------------|
| 00:00 | 12:00 AM | Medianoche |
| 06:00 | 06:00 AM | Mañana |
| 12:00 | 12:00 PM | Mediodía |
| 18:00 | 06:00 PM | Tarde |
| 23:59 | 11:59 PM | Casi medianoche |

## ✨ CARACTERÍSTICA DE CARBON

### Localización (es_ES para español)
```php
// Si necesitas en español (mes en palabras)
echo $fecha->locale('es')->format('D, d \d\e F \d\e Y');
// Resultado: Viernes, 04 de Diciembre de 2025

// Pero para la interfaz usar siempre: d/m/Y h:i A
echo $fecha->format('d/m/Y h:i A');  // 04/12/2025 05:56 PM
```

## ✅ CHECKLIST PARA NUEVOS DESARROLLOS

- [ ] ¿Usa `d/m/Y h:i A` para fecha + hora?
- [ ] ¿Usa `d/m/Y` para solo fecha?
- [ ] ¿Usa `h:i A` para solo hora?
- [ ] ¿NO usa `H:i` (hora militar)?
- [ ] ¿Guarda timestamps completos en BD?
- [ ] ¿Prueba con 24h diferentes (AM y PM)?

## 📅 FECHA DE ESTANDARIZACIÓN
**5 de Diciembre de 2025**

## 🔗 REFERENCIAS
- [Carbon Documentation - Formatting](https://carbon.nesbot.com/docs/#formatting)
- [PHP Date Formats](https://www.php.net/manual/en/datetime.format.php)

## 📝 NOTA
Este estándar es **OBLIGATORIO** para todos los nuevos desarrollos en el proyecto.
Si encuentras código que no cumple este estándar, actualízalo a `d/m/Y h:i A`.
