# 📊 Guía de Importación de Balanceos - Actualizada

## Fecha: 2025-11-04

---

## ✨ Cambios Importantes

### **Nombre de la Prenda = Nombre de la Hoja**

Ahora el sistema usa el **nombre de la hoja de Excel** como nombre de la prenda automáticamente.

**Ejemplo:**
- Hoja: `JEANS CABALLERO` → Prenda: `JEANS CABALLERO`
- Hoja: `JEAN CERÁMICA ITALIA` → Prenda: `JEAN CERÁMICA ITALIA`
- Hoja: `JEANS DAMA` → Prenda: `JEANS DAMA`

---

## 📋 Encabezados Exactos

El sistema busca estos encabezados **exactos** (en mayúsculas):

| Columna | Requerido | Descripción |
|---------|-----------|-------------|
| **LETRA** | No* | Letra de la operación (A, B, C...) |
| **OPERACIÓN** | Sí** | Descripción de la operación |
| **PRECEDENCIA** | No | Operaciones previas (ej: A-D, O-L) |
| **MAQUINA** | No | Tipo de máquina (FL, PL, 2A, etc.) |
| **SAM** | **Sí** | Tiempo en segundos |
| **OPERARIO** | No | Nombre del operario |
| **OP** | No | Código del operario (op1, op2, etc.) |
| **SECCIÓN** | No | Sección (DEL, TRAS, ENS, OTRO) |

*Si no hay columna LETRA, se genera automáticamente (A, B, C...)
**Si no hay columna OPERACIÓN, se detecta automáticamente

---

## 📁 Estructura del Excel

### Archivo con Múltiples Hojas

```
📄 balanceos.xlsx
  ├─ 📋 JEANS CABALLERO
  ├─ 📋 JEAN CERÁMICA ITALIA
  └─ 📋 JEANS DAMA
```

Cada hoja se importa como una prenda diferente.

### Contenido de Cada Hoja

```
LETRA | OPERACIÓN                    | PRECEDENCIA | MAQUINA   | SAM  | OPERARIO | OP   | SECCIÓN
------|------------------------------|-------------|-----------|------|----------|------|--------
A     | Filetear vista x2            | N/A         | FL        | 4.8  |          | op1  | DEL
B     | Filetear aletillas           | N/A         | FL        | 4.8  |          | op1  | DEL
C     | Filetear aletillones         | N/A         | FL        | 4.8  |          | op1  | DEL
D     | Embonar relojeras            | N/A         | 2 AG 1/4  | 14.0 |          | op2  | DEL
E     | Montar relojera a vista      | A-D         | 2 AG 1/4  | 13.3 |          | op2  | DEL
...
```

**Notas:**
- Puedes tener solo las columnas **OPERACIÓN** y **SAM** (mínimo)
- Los valores `N/A` se limpian automáticamente
- Las columnas vacías se ignoran

---

## 🚀 Uso del Comando

### 1. Instalar Dependencia (Solo una vez)

```bash
composer require maatwebsite/excel
```

### 2. Importar Excel

```bash
# Modo DRY-RUN (probar sin guardar)
php artisan balanceo:importar-excel balanceos.xlsx --dry-run

# Importar realmente
php artisan balanceo:importar-excel balanceos.xlsx
```

---

## 📊 Ejemplo de Salida

```
📂 Leyendo archivo: balanceos.xlsx
📊 Hojas encontradas: 3

============================================================
📄 Procesando hoja: JEANS CABALLERO
👕 Prenda: JEANS CABALLERO
📝 Referencia: REF-JEANS-CABALLERO-20251104154530
👥 Operarios: 10 | Turnos: 1 | Horas: 8.0

📋 Columnas detectadas:
   LETRA: Col 1
   OPERACIÓN: Col 2
   SAM: Col 5
   PRECEDENCIA: Col 3
   MAQUINA: Col 4
   OPERARIO: Col 6
   OP: Col 7
   SECCIÓN: Col 8

✅ Operaciones encontradas: 25
⏱️  SAM Total: 684.2

📝 Muestra de operaciones:
   A: Filetear vista x2 - SAM: 4.8
   B: Filetear aletillas - SAM: 4.8
   C: Filetear aletillones - SAM: 4.8
   ... y 22 más

💾 Prenda guardada: ID 5
💾 Balanceo creado: ID 8
💾 Operaciones creadas: 25

📊 Métricas calculadas:
   SAM Total: 684.2
   Meta Teórica: 421
   Meta Real (90%): 378.90
   Meta Sugerida (85%): 357

✅ Balanceo importado exitosamente

============================================================
📄 Procesando hoja: JEAN CERÁMICA ITALIA
...
```

---

## ✅ Validaciones Automáticas

1. ✅ **Nombre de hoja** → Nombre de prenda
2. ✅ **Referencia única** → Se genera automáticamente
3. ✅ **Encabezados** → Detecta LETRA y SAM mínimo
4. ✅ **Valores N/A** → Se limpian automáticamente
5. ✅ **SAM numérico** → Valida y limpia formato
6. ✅ **Letras automáticas** → Si no hay columna LETRA
7. ✅ **Métricas** → Se calculan automáticamente

---

## 🎯 Casos de Uso

### Caso 1: Excel con Todas las Columnas

```
LETRA | OPERACIÓN | PRECEDENCIA | MAQUINA | SAM | OPERARIO | OP | SECCIÓN
A     | Op 1      | N/A         | FL      | 4.8 | LEONARDO | op1| DEL
```

✅ Se importan todos los datos

### Caso 2: Excel Solo con Operación y SAM

```
OPERACIÓN | SAM
Op 1      | 4.8
Op 2      | 8.9
```

✅ Se genera LETRA automáticamente (A, B, C...)
✅ Otros campos quedan vacíos

### Caso 3: Múltiples Hojas

```
📄 balanceos.xlsx
  ├─ JEAN 1
  ├─ JEAN 2
  └─ JEAN 3
```

✅ Se crean 3 prendas diferentes
✅ Cada una con su balanceo

---

## 🛠️ Solución de Problemas

### Error: "No se encontraron encabezados"

**Causa:** No hay columnas LETRA y SAM

**Solución:** Asegúrate de tener al menos:
- Una columna llamada **SAM** (mayúsculas)
- Una columna con operaciones

### Error: "No se encontró la columna SAM"

**Causa:** La columna no se llama exactamente "SAM"

**Solución:** Renombra la columna a **SAM** (todo en mayúsculas)

### Valores N/A aparecen en la BD

**Causa:** Versión anterior del código

**Solución:** Actualiza el código, ahora se limpian automáticamente

---

## 📝 Mejores Prácticas

1. **Nombra las hojas claramente** - El nombre será el de la prenda
2. **Usa encabezados en mayúsculas** - LETRA, SAM, OPERACIÓN, etc.
3. **Prueba con --dry-run primero** - Verifica antes de importar
4. **Revisa el SAM Total** - Debe coincidir con tu Excel
5. **Una hoja = Una prenda** - No mezcles prendas en la misma hoja

---

## 🎉 Ventajas del Sistema Actualizado

1. ✅ **Más simple** - Solo nombre de hoja + encabezados
2. ✅ **Más rápido** - No busca datos en filas
3. ✅ **Más flexible** - Acepta hojas con pocas columnas
4. ✅ **Más robusto** - Limpia valores N/A automáticamente
5. ✅ **Más claro** - Nombre de hoja = Nombre de prenda

---

## 📞 Comandos Útiles

```bash
# Importar con DRY-RUN
php artisan balanceo:importar-excel archivo.xlsx --dry-run

# Importar realmente
php artisan balanceo:importar-excel archivo.xlsx

# Recalcular métricas después
php artisan balanceo:recalcular

# Recalcular un balanceo específico
php artisan balanceo:recalcular 5
```

---

## ✨ Próximos Pasos

1. ✅ Prepara tu Excel con hojas nombradas
2. ✅ Asegúrate de tener encabezados LETRA y SAM
3. ✅ Ejecuta con --dry-run para probar
4. ✅ Importa sin --dry-run
5. ✅ Verifica en la interfaz web

¡Listo para importar! 🚀
