# 📊 SCRIPT PARA ANALIZAR COTIZACIONES

## 📝 Descripción

Script Artisan que analiza las cotizaciones en la base de datos y proporciona estadísticas detalladas.

---

## 🚀 Cómo Usar

### Opción 1: Análisis General (Todas las cotizaciones)

```bash
php artisan analizar:cotizaciones
```

**Salida:**
- Total de cotizaciones en la BD
- Conteo por estado (es_borrador = 0 o 1)
- Conteo por tipo (P, B, PB)
- Conteo por estado (BORRADOR, ENVIADA, etc.)
- Últimas 10 cotizaciones

---

### Opción 2: Análisis por Usuario (Asesor)

```bash
php artisan analizar:cotizaciones --usuario_id=18
```

**Reemplaza `18` con el ID del usuario (asesor) que quieres analizar**

**Salida:**
- Estadísticas generales
- Cotizaciones del usuario específico
- Tabla detallada de todas sus cotizaciones

---

## 📊 Información que Proporciona

### 1. Estadísticas Generales
- Total de cotizaciones en la BD
- Total de cotizaciones del usuario (si se especifica)

### 2. Análisis por Estado
- Borradores (es_borrador = 1)
- Enviadas (es_borrador = 0)

### 3. Análisis por Tipo
- Prenda (P)
- Logo (B)
- Prenda/Logo (PB)

### 4. Análisis por Estado
- BORRADOR
- ENVIADA
- APROBADA
- etc.

### 5. Últimas 10 Cotizaciones
Tabla con:
- ID
- Asesor ID
- Número de cotización
- Tipo
- Cliente
- ¿Es borrador?
- Estado
- Fecha de creación

### 6. Análisis Detallado del Usuario (si se especifica)
Tabla completa de todas las cotizaciones del usuario

---

## 💡 Ejemplos

### Ver todas las cotizaciones
```bash
php artisan analizar:cotizaciones
```

### Ver cotizaciones del usuario 18
```bash
php artisan analizar:cotizaciones --usuario_id=18
```

### Ver cotizaciones del usuario 5
```bash
php artisan analizar:cotizaciones --usuario_id=5
```

---

## 📍 Ubicación del Script

`app/Console/Commands/AnalizarCotizaciones.php`

---

## ✅ Uso Recomendado

1. Ejecuta el script sin parámetros para ver estadísticas generales
2. Identifica el usuario_id que quieres analizar
3. Ejecuta con `--usuario_id=X` para ver sus cotizaciones
4. Analiza los datos para entender la estructura

---

**Creado:** 10 de Diciembre de 2025
**Estado:** ✅ LISTO PARA USAR
