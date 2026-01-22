# 📌 PLAN DE ACCIÓN: Mantener Conformidad de Tallas en JavaScript

## 🎯 Objetivo
Asegurar que la lógica de tallas permanezca limpia y relacional en futuras modificaciones.

---

## ✅ Estado Actual (22 Enero 2026)

```
invoice-preview-live.js: ✅ CONFORME
Otros archivos:         ⚠️ VARIABLES AUXILIARES (aceptables)
Modelo de datos:        ✅ RELACIONAL EN TODOS LADOS
```

---

## 🔄 Checklist para Próximas Modificaciones

### Cuando modifiques `invoice-preview-live.js`

```
ANTES DE MODIFICAR:
☐ Revisar si afecta lectura de tallas
☐ Revisar si afecta cálculo de cantidades
☐ Revisar si afecta envío a API

AL MODIFICAR:
☐ Mantener estructura {GENERO: {TALLA: CANTIDAD}}
☐ No introducir variables globales legacy
☐ No crear fallbacks a cantidadesTallas
☐ No usar JSON.parse de cantidad_talla (leer directamente)

DESPUÉS DE MODIFICAR:
☐ Verificar sintaxis con herramienta de validación
☐ Probar preview en vivo con datos de prueba
☐ Verificar que cálculos sean correctos
☐ Revisar console.log para errores
☐ Ejecutar test unitarios si existen
```

### Cuando modifiques otros archivos JS

```
SI MODIFICA TALLAS:
☐ Verificar que cantidad_talla se envía como JSON
☐ Verificar que estructura es {GENERO: {TALLA: CANTIDAD}}
☐ Verificar que NO se envían tallas_dama/caballero separadas
☐ Revisar que invoice-preview-live.js no se ve afectado

SI MODIFICA PROCESOS:
☐ Verificar que procesos.tallas es estructura relacional
☐ Verificar que se guardan en BD correctamente
☐ Verificar que invoice-preview los lee correctamente

SI AÑADE NUEVAS VARIABLES:
☐ NO usar nombre \"cantidadesTallas\" (ya existe legacy)
☐ NO usar nombre \"_TALLAS_BACKUP_PERMANENTE\" (legacy)
☐ Preferir clases u objetos estructurados
☐ Documentar por qué es necesaria la variable
```

---

## 🚨 Señales de Alerta

Cuando veas estos patrones, ¡DETENTE y REVISA!

### 🔴 ROJO: Detener Inmediatamente

```javascript
// ❌ MALO - Variables globales sin inicializar
window.cantidadesTallas[key] = valor;  // Puede fallar

// ❌ MALO - Crear respaldos de tallas
window._TALLAS_NUEVO_BACKUP = {};

// ❌ MALO - Parsear cantidad_talla nuevamente
JSON.parse(prenda.cantidad_talla)  // Ya debería venir parseado

// ❌ MALO - Enviar tallas separadas
formData.append('tallas_dama', JSON.stringify(...));
formData.append('tallas_caballero', JSON.stringify(...));
```

### 🟡 AMARILLO: Revisar Contexto

```javascript
// ⚠️ REVISAR - ¿Por qué accedes a tallas aquí?
const tallas = window.cantidadesTallas;

// ⚠️ REVISAR - ¿Cuál es la estructura?
Object.entries(prenda.tallas).forEach(...);

// ⚠️ REVISAR - ¿De dónde viene cantidad_talla?
if (prenda.cantidad_talla) { ... }

// ⚠️ REVISAR - ¿Es necesaria esta función?
function extraerTallas(data) { ... }
```

### 🟢 VERDE: Patrones Correctos

```javascript
// ✅ BIEN - Lectura segura
const tallas = prenda.tallas || {};

// ✅ BIEN - Estructura relacional
{DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}}

// ✅ BIEN - Cálculo correcto
Object.values(tallas).reduce((sum, genero) => 
    sum + Object.values(genero).reduce((s, c) => s + c, 0), 0)

// ✅ BIEN - Envío relacional
JSON.stringify(prenda.tallas)
```

---

## 📋 Procedimiento para Auditorías Futuras

Si necesitas re-auditar en el futuro:

### Paso 1: Búsqueda Rápida
```bash
# En terminal: buscar referencias legacy
grep -r "cantidadesTallas\|cantidad_talla\|_TALLAS_BACKUP_PERMANENTE" public/js/

# O en VSCode:
# Ctrl+Shift+F → Buscar en workspace
```

### Paso 2: Análisis de Impacto
```javascript
// Para cada referencia encontrada:
1. ¿En qué archivo se encuentra?
2. ¿Es lectura o escritura?
3. ¿Afecta datos persistidos?
4. ¿Afecta invoice-preview?
```

### Paso 3: Verificación de Estructura
```javascript
// Verificar que tallas siempre sean:
function validarTallas(obj) {
    // Debe ser: {GENERO_STRING: {TALLA_STRING: CANTIDAD_NUMBER}}
    if (typeof obj !== 'object') return false;
    
    for (let genero in obj) {
        if (typeof obj[genero] !== 'object') return false;
        for (let talla in obj[genero]) {
            if (typeof obj[genero][talla] !== 'number') return false;
        }
    }
    return true;
}
```

### Paso 4: Reporte
Si encuentras incumplimientos, documenta:
- Archivo y línea
- Código específico
- Impacto identificado
- Recomendación de acción

---

## 🎓 Formación del Equipo

### Para Nuevo Desarrollador
```
1. Leer: MODELO_DATOS_FIJO_REFERENCIA_RAPIDA.md
2. Leer: GUIA_REFACTORIZACION_TALLAS_JAVASCRIPT.md
3. Revisar: invoice-preview-live.js como ejemplo
4. Comprender: Flujo de datos (formulario → API → BD → preview)
5. Practicar: Hacer cambios pequeños primero
```

### Para Code Review
```
Checklist al revisar cambios de tallas:

☐ ¿Usa estructura {GENERO: {TALLA: CANTIDAD}}?
☐ ¿No introduce variables globales nuevas?
☐ ¿Mantiene compatibilidad con invoice-preview?
☐ ¿Envía datos correctamente a API?
☐ ¿Se valida la estructura antes de usar?
☐ ¿Se documenta el flujo de datos?
```

---

## 🔧 Herramientas Útiles

### Validador de Sintaxis JavaScript
```javascript
// Incluir en proyecto
function validarSintaxisJavaScript(codigo) {
    try {
        new Function(codigo);
        return { valido: true };
    } catch (error) {
        return { valido: false, error: error.message };
    }
}
```

### Verificador de Estructura de Tallas
```javascript
class ValidadorTallas {
    static validar(obj) {
        const errores = [];
        
        if (typeof obj !== 'object' || obj === null) {
            errores.push('No es un objeto');
            return { valido: false, errores };
        }
        
        for (let genero in obj) {
            if (typeof genero !== 'string') {
                errores.push(`Género no es string: ${genero}`);
            }
            
            if (typeof obj[genero] !== 'object') {
                errores.push(`Tallas de ${genero} no es objeto`);
            } else {
                for (let talla in obj[genero]) {
                    const cant = obj[genero][talla];
                    if (typeof cant !== 'number' || cant < 0) {
                        errores.push(
                            `Cantidad inválida en ${genero}-${talla}: ${cant}`
                        );
                    }
                }
            }
        }
        
        return {
            valido: errores.length === 0,
            errores
        };
    }
}

// Uso
const resultado = ValidadorTallas.validar({DAMA: {S: 10}});
console.log(resultado); // { valido: true, errores: [] }
```

---

## 📝 Documentación a Mantener Actualizada

Estos documentos deben actualizarse si hay cambios:

```
✅ AUDITORIA_COMPLETA_JAVASCRIPT_TALLAS.md
   → Actualizar si se encuentran nuevas referencias
   
✅ GUIA_REFACTORIZACION_TALLAS_JAVASCRIPT.md
   → Actualizar si se añaden nuevos patrones
   
✅ INFORME_VISUAL_AUDITORIA_JAVASCRIPT.md
   → Regenerar anualmente
   
✅ Este documento (PLAN_ACCION_TALLAS_JAVASCRIPT.md)
   → Actualizar con lecciones aprendidas
```

---

## 📅 Calendario de Revisiones

```
FRECUENCIA: Cada sprint de desarrollo
AUDITORÍA COMPLETA: Trimestral
ACTUALIZACIÓN DE DOCUMENTACIÓN: Anual

PRÓXIMAS REVISIONES:
├─ 29 Enero 2026     (weekly check)
├─ 05 Febrero 2026   (weekly check)
├─ 22 Febrero 2026   (mensual completo)
├─ 22 Abril 2026     (trimestral)
└─ 22 Enero 2027     (anual)
```

---

## ✅ Signoff

```
Documento: PLAN_ACCION_TALLAS_JAVASCRIPT.md
Fecha: 22 de Enero, 2026
Auditor: Sistema Automático
Revisor: [Pendiente]
Aprobado: [Pendiente]

PRÓXIMO CONTROL: 29 de Enero, 2026
```

---

## 🆘 Soporte y Escalación

Si encuentras problemas:

1. **Problema Menor** (log extraño)
   - Revisar este documento
   - Validar estructura con ValidadorTallas
   - Documentar en comentarios

2. **Problema Medio** (comportamiento incorrecto)
   - Ejecutar auditoría parcial
   - Revisar flujo de datos
   - Crear test unitario

3. **Problema Mayor** (datos perdidos)
   - Detener cambios
   - Ejecutar auditoría completa
   - Revisar con equipo senior

---

**Versión:** 1.0  
**Última actualización:** 22 Enero 2026  
**Próxima revisión:** 29 Enero 2026
