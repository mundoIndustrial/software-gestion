# 📚 ÍNDICE DE DOCUMENTACIÓN - Técnicas Combinadas

Bienvenido. Esta es tu guía completa sobre el sistema de técnicas combinadas.

---

## 🚀 Comienza Aquí

### Para Entender Rápidamente (5 min)
👉 **[RESUMEN_TECNICAS_COMBINADAS_v2.md](RESUMEN_TECNICAS_COMBINADAS_v2.md)**
- Qué se cambió
- Por qué se cambió
- Resultado final

---

## 👤 Para Asesores Nuevos

### Manual de Usuario (15 min)
👉 **[GUIA_USUARIO_TECNICAS_COMBINADAS.md](GUIA_USUARIO_TECNICAS_COMBINADAS.md)**
- Cómo crear técnicas combinadas
- Paso a paso visual
- Ejemplos prácticos
- Tips y trucos

---

## 🧪 Para Testing/QA

### Guía de Testing (20 min)
👉 **[TESTING_TECNICAS_COMBINADAS.md](TESTING_TECNICAS_COMBINADAS.md)**
- Escenarios de prueba
- Paso a paso con capturas
- Checklist de validación
- Qué buscar en consola

---

## 🔧 Para Desarrolladores

### Detalles del Fix (10 min)
👉 **[FIX_GRUPO_COMBINADO.md](FIX_GRUPO_COMBINADO.md)**
- Problema identificado
- Solución técnica
- Por qué funciona
- Código antes/después

### Cambios Visuales (5 min)
👉 **[ACTUALIZACION_ESTILO_TNS.md](ACTUALIZACION_ESTILO_TNS.md)**
- Paleta de colores
- Cambios en cada componente
- Antes vs después
- Archivos modificados

### Arquitectura General (10 min)
👉 **[TECNICAS_COMBINADAS_RESUMEN.md](TECNICAS_COMBINADAS_RESUMEN.md)**
- Cómo funciona el sistema
- Base de datos
- API endpoints
- Flujo completo

---

## 📊 Mapa Rápido

```
┌─ Usuario quiere...
│
├─ "Entender qué cambió"
│  → RESUMEN_TECNICAS_COMBINADAS_v2.md
│
├─ "Usar el sistema"
│  → GUIA_USUARIO_TECNICAS_COMBINADAS.md
│
├─ "Probar que funcione"
│  → TESTING_TECNICAS_COMBINADAS.md
│
├─ "Entender el código"
│  → FIX_GRUPO_COMBINADO.md
│
├─ "Ver cambios visuales"
│  → ACTUALIZACION_ESTILO_TNS.md
│
└─ "Arquitectura completa"
   → TECNICAS_COMBINADAS_RESUMEN.md
```

---

## 🎯 Por Rol

### 📋 Gerente/Producto
1. RESUMEN_TECNICAS_COMBINADAS_v2.md
2. TESTING_TECNICAS_COMBINADAS.md

### 👨‍💼 Asesor
1. GUIA_USUARIO_TECNICAS_COMBINADAS.md
2. TESTING_TECNICAS_COMBINADAS.md (para aprender)

### 👨‍💻 Desarrollador
1. FIX_GRUPO_COMBINADO.md
2. ACTUALIZACION_ESTILO_TNS.md
3. TECNICAS_COMBINADAS_RESUMEN.md

### 🧪 QA/Tester
1. TESTING_TECNICAS_COMBINADAS.md
2. FIX_GRUPO_COMBINADO.md (para contexto)

---

## 🔍 Búsqueda Rápida

**¿Dónde está...?**

| Pregunta | Documento |
|----------|-----------|
| ¿Cómo crear técnicas combinadas? | GUIA_USUARIO_TECNICAS_COMBINADAS.md |
| ¿Qué cambió en el código? | FIX_GRUPO_COMBINADO.md |
| ¿Cómo se ve ahora? | ACTUALIZACION_ESTILO_TNS.md |
| ¿Cómo pruebo? | TESTING_TECNICAS_COMBINADAS.md |
| ¿Cómo funciona todo? | TECNICAS_COMBINADAS_RESUMEN.md |
| ¿Resumen ejecutivo? | RESUMEN_TECNICAS_COMBINADAS_v2.md |

---

## 📝 Información Clave

### Problema Original
Sistema no agrupaba técnicas combinadas con misma prenda pero ubicaciones diferentes.

### Solución
Generador de `grupo_combinado` en frontend que asigna ID único a todas las técnicas del bundle.

### Resultado
✅ Técnicas combinadas se agrupan correctamente en tabla
✅ Visual minimalista TNS (gris, no colores vivos)
✅ Funciona rápido (< 1ms)

### Archivos Modificados
- `public/js/logo-cotizacion-tecnicas.js` (guardarTecnicaCombinada + renderizarTecnicasAgregadas)
- `resources/views/cotizaciones/bordado/create.blade.php` (modal estilo)

---

## ✅ Checklist de Lectura

- [ ] Leí RESUMEN_TECNICAS_COMBINADAS_v2.md
- [ ] Entiendo el problema y la solución
- [ ] Leí la documentación relevante para mi rol
- [ ] Entiendo cómo probar
- [ ] Estoy listo para usar/probar/desplegar

---

## 🆘 Preguntas Frecuentes

### P: ¿Dónde está el grupo_combinado en la BD?
A: En tabla `logo_cotizacion_tecnica_prendas` (columna `grupo_combinado`)

### P: ¿Cómo sé si está funcionando?
A: Abre F12 → Console → Busca "Grupo combinado asignado: [número]"

### P: ¿Por qué gris en lugar de verde?
A: Estilo minimalista TNS (sin colores vivos)

### P: ¿Puedo cambiar los colores?
A: Sí, edita paleta en ACTUALIZACION_ESTILO_TNS.md

### P: ¿Funciona con 3+ técnicas?
A: Sí, agrupamiento funciona para cualquier número

---

## 🚀 Próximos Pasos

1. **Leer documentación relevante** (según tu rol)
2. **Testing en desarrollo** (http://servermi:8000/...)
3. **Verificar en F12** que grupo_combinado se genera
4. **Confirmar visual minimalista** en tabla
5. **Aprobar para producción**

---

## 📞 Soporte

Si necesitas más información:
1. Busca en documentos (Ctrl+F)
2. Revisa ejemplos prácticos
3. Abre consola del navegador (F12)
4. Contacta al desarrollador

---

**Última actualización:** 7 de enero de 2026
**Versión:** 2.0
**Estado:** ✅ COMPLETADO

