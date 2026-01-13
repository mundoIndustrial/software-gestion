# Plan de Continuación de Refactorización

## 📊 Estado Actual
- **Archivo:** `crear-pedido-editable.js`
- **Líneas actuales:** 3,305
- **Líneas extraídas:** 4,100 (en 10 módulos)
- **Reducción lograda:** 30%
- **Objetivo:** Reducir a ~1,500 líneas (55% adicional)

---

## 🎯 Funciones Grandes Identificadas para Refactorizar

### 1. **renderizarPrendasEditables** (~850 líneas)
**Ubicación:** Líneas 527-1377  
**Responsabilidad:** Renderizar prendas en modo editable  
**Acción:** Ya existe `PrendaComponent.renderizarPrendas()` - Migrar completamente

### 2. **Funciones de Logo** (~600 líneas)
**Ubicación:** Líneas 1398-2000  
**Funciones:**
- `renderizarFotosLogo()`
- `abrirModalAgregarFotosLogo()`
- `eliminarFotoLogo()`
- `agregarTecnicaLogo()`
- `renderizarTecnicasLogo()`
- `agregarSeccionLogo()`
- `renderizarSeccionesLogo()`

**Acción:** Crear `LogoComponent.js`

### 3. **Funciones de Fotos** (~300 líneas)
**Ubicación:** Líneas 1583-1878  
**Funciones:**
- `abrirModalAgregarFotosPrenda()`
- `abrirModalAgregarFotosTela()`

**Acción:** Ya existe `ImageService` - Consolidar aquí

### 4. **Funciones de Telas** (~150 líneas)
**Ubicación:** Líneas 1881-1926  
**Funciones:**
- `agregarFilaTela()`
- `eliminarFilaTela()`

**Acción:** Crear `TelaComponent.js`

### 5. **Funciones de Reflectivo** (~200 líneas)
**Ubicación:** Dispersas en el archivo  
**Acción:** Crear `ReflectivoComponent.js`

---

## 📋 Plan de Ejecución (Fase 3)

### **Paso 1: Crear LogoComponent** (Prioridad: ALTA)
**Líneas a extraer:** ~600  
**Archivo nuevo:** `public/js/components/logo-component.js`

**Métodos:**
```javascript
class LogoComponent {
    // Fotos
    renderizarFotos()
    abrirModalAgregarFotos()
    eliminarFoto(index)
    
    // Técnicas
    agregarTecnica()
    renderizarTecnicas()
    eliminarTecnica(index)
    
    // Secciones/Ubicaciones
    agregarSeccion()
    editarSeccion(index)
    renderizarSecciones()
    eliminarSeccion(index)
    
    // Observaciones
    agregarObservacion()
    renderizarObservaciones()
}
```

### **Paso 2: Crear TelaComponent** (Prioridad: MEDIA)
**Líneas a extraer:** ~150  
**Archivo nuevo:** `public/js/components/tela-component.js`

**Métodos:**
```javascript
class TelaComponent {
    agregarFila(prendaIndex)
    eliminarFila(prendaIndex, telaIndex)
    renderizarTelas(prendaIndex)
    abrirModalAgregarFotos(prendaIndex, telaIndex)
}
```

### **Paso 3: Consolidar Funciones de Fotos en ImageService** (Prioridad: MEDIA)
**Líneas a mover:** ~300  
**Archivo existente:** `public/js/services/image-service.js`

**Métodos a agregar:**
```javascript
// Ya existe uploadPrendaImage, uploadTelaImage
// Agregar:
abrirModalAgregarFotosPrenda(prendaIndex)
abrirModalAgregarFotosTela(prendaIndex, telaIndex)
```

### **Paso 4: Crear ReflectivoComponent** (Prioridad: BAJA)
**Líneas a extraer:** ~200  
**Archivo nuevo:** `public/js/components/reflectivo-component.js`

---

## 📊 Reducción Esperada

| Fase | Componente | Líneas | Acumulado |
|------|-----------|--------|-----------|
| Actual | - | 3,305 | - |
| Paso 1 | LogoComponent | -600 | 2,705 |
| Paso 2 | TelaComponent | -150 | 2,555 |
| Paso 3 | ImageService | -300 | 2,255 |
| Paso 4 | ReflectivoComponent | -200 | 2,055 |
| **Meta Final** | - | **~1,500** | **-1,805** |

---

## 🎯 Beneficios Esperados

1. **Modularidad:** Cada componente con responsabilidad única
2. **Mantenibilidad:** Código más fácil de encontrar y modificar
3. **Reutilización:** Componentes reutilizables en otros contextos
4. **Testing:** Más fácil de testear cada módulo
5. **Reducción:** 55% adicional de código en archivo principal

---

## 🚀 Próxima Acción Recomendada

**Empezar con Paso 1: Crear LogoComponent**
- Mayor impacto (600 líneas)
- Funcionalidad bien definida
- No afecta otras partes del sistema

---

**Fecha:** 12 de enero de 2026  
**Estado:** Listo para ejecutar
