# 🚀 GUÍA: Cómo Agregar Ubicaciones de Reflectivo en PASO 4

## ❌ EL PROBLEMA IDENTIFICADO

Los logs de Laravel muestran:
```
"ubicaciones_data_raw":"[]"
"ubicaciones_array":[]
"ubicaciones_count":0
```

**Las ubicaciones están VACÍAS porque NO SE ESTÁN AGREGANDO en el formulario.**

---

## ✅ SOLUCIÓN: Sigue EXACTAMENTE estos pasos:

### **PASO 1: Llena PASO 2 (Prendas)**
1. Agrega una prenda (ejemplo: CAMISA DRILL)
2. Selecciona tallas (XS, S)
3. Configura variantes (manga, broche, bolsillos, etc.)
4. Click en **SIGUIENTE** ➜ PASO 3

### **PASO 2: Completa PASO 3 (Logo Técnicas)**
1. Agrega técnicas de logo si necesitas (opcional)
2. Agrega ubicaciones de logo (opcional)
3. Click en **SIGUIENTE** ➜ PASO 4

### **PASO 3: ⭐ CRUCIAL - Agrega Ubicaciones de REFLECTIVO en PASO 4**

**En la sección "Ubicación":**

1. **Campo de Sección:**
   ```
   [ _____ o Selecciona: PECHO, ESPALDA, MANGA, CUELLO, COSTADO, MÚLTIPLE ]
   ```
   - **Escribe una opción:** PECHO, ESPALDA, MANGA, etc.
   - O **selecciona de la lista desplegable**

2. **Click en botón AZUL "+":**
   ```
   [ UBICACIÓN ]                                    [ + ]
   ```
   - Esto abre un MODAL

3. **En el Modal:**
   - Te aparece un cuadro de diálogo con:
     ```
     PECHO (o la sección que escribiste)
     
     [Descripción]
     [ Escribe aquí: Ej: "Lado izquierdo, Centro, Ambos lados..." ]
     
     [ × ] [ + ]
     ```
   - **Escribe una DESCRIPCIÓN** (obligatorio)
   - Click en botón AZUL "+" para guardar

4. **Repite si necesitas más ubicaciones:**
   - Click en "+" nuevamente
   - Selecciona/escribe nueva sección
   - Escribe descripción
   - Guarda

5. **Verás las ubicaciones listadas abajo:**
   ```
   PECHO
   Descripción: Lado izquierdo
   [ × ]
   
   ESPALDA
   Descripción: Centro
   [ × ]
   ```

### **PASO 4: Guarda/Envía la Cotización**
- Click en **REVISAR** (botón inferior derecho)
- Verifica todo en PASO 5 (Resumen)
- Click en **GUARDAR** o **ENVIAR**

---

## 🔍 VERIFICACIÓN: Cómo Saber que Funcionó

### **En la Consola del Navegador (F12 > Console):**
Busca estos logs:

```
✅ Ubicación agregada correctamente
{
  ubicacion: "PECHO"
  descripcion: "Lado izquierdo"
  total_ubicaciones: 1
}
```

### **En la Base de Datos:**
Tabla `prenda_cot_reflectivo`:
```
ubicaciones: [{"ubicacion":"PECHO","descripcion":"Lado izquierdo"}]
```

---

##  🆘 COMMON ISSUES

| Problema | Solución |
|----------|----------|
| **"Por favor selecciona o escribe una SECCIÓN"** | Asegúrate de escribir/seleccionar algo en el campo "Selecciona o escribe la sección" |
| **"Por favor escribe una descripción"** | El modal pide descripción - escribe algo en el textarea |
| **Ubicaciones no aparecen abajo** | Recarga la página si no ves la lista actualizada |
| **Ubicaciones siguen siendo `[]` en BD** | Verifica que ANTES de hacer click en "REVISAR" veas las ubicaciones listadas |

---

## 📋 CHECKLIST ANTES DE ENVIAR

- [ ] PASO 2: Prenda agregada con tallas ✅
- [ ] PASO 3: Logo techniques (opcional) ✅
- [ ] PASO 4: Ubicación reflectivo AGREGADA ✅
  - [ ] Campo sección completado
  - [ ] Descripción completada  
  - [ ] Ubicación visible en la lista
- [ ] Consola del navegador: SIN ERRORES ✅
- [ ] Click en REVISAR → PASO 5 ✅
- [ ] Click en GUARDAR/ENVIAR ✅

---

## 💡 TIPS

1. **Abre la Consola (F12)** antes de agregar ubicaciones para ver los logs en tiempo real
2. **Múltiples ubicaciones:** Puedes agregar varias ubicaciones (PECHO, ESPALDA, etc.)
3. **Editar:** Si cometes error, haz click en "×" para eliminar y vuelve a agregar
4. **Descripción importante:** La descripción es lo que especifica CÓMO se coloca el reflectivo

---

**Última actualización:** 2026-01-20
