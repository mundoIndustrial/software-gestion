# Tarjeta de Prenda - Solo Lectura (Read-Only)

## Descripción General

Componente visual para mostrar prendas agregadas en el formulario de creación de pedidos. La tarjeta es **completamente de solo lectura** hasta que el usuario hace click en "Editar".

## Características

✅ **Foto con galería modal** - Click en foto → Modal con navegación entre fotos  
✅ **3 secciones expandibles** - Variaciones, Tallas, Procesos  
✅ **Menú contextual** - Botón de 3 puntos para Editar/Eliminar  
✅ **Solo lectura** - Sin inputs editables, sin cambios inline  
✅ **Responsivo** - Se adapta a dispositivos móviles  
✅ **Integrado** - Funciona con infraestructura existente  

## Estructura de Archivos

```
public/
├── js/
│   └── componentes/
│       ├── prenda-card-readonly.js          ← Lógica y funciones
│       └── prenda-card-readonly-guia.js     ← Documentación de uso
└── css/
    └── componentes/
        └── prenda-card-readonly.css         ← Estilos
```

## Instalación

### 1. Agregar al HTML (en el layout base)

```html
<!-- Después de SweetAlert2 -->
<link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
<script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
```

### 2. Container en el formulario

```html
<div id="prendas-container-editable" class="prendas-list">
    <!-- Las tarjetas se insertan aquí -->
</div>
```

## Uso

### Generar una tarjeta

```javascript
const prenda = {
    id: 1,
    nombre_producto: "Camisa",
    descripcion: "Camisa casual",
    fotos: ["/storage/foto1.jpg"],
    generosConTallas: {
        "dama": { "S": 20, "M": 30 }
    },
    variantes: {
        tela: "Algodón",
        color: "Azul",
        manga: "Larga"
    },
    procesos: {
        bordado: { tipo: "Logo" }
    }
};

// Renderizar
const html = generarTarjetaPrendaReadOnly(prenda, 0);
document.getElementById('prendas-container-editable').innerHTML += html;
```

### Con el gestor existente

```javascript
// Esto ya debería estar en el código
function actualizarVistaItems() {
    const container = document.getElementById('prendas-container-editable');
    const prendas = window.gestorPrendaSinCotizacion?.obtenerActivas() || [];
    
    if (prendas.length === 0) {
        container.innerHTML = '<div class="empty-state">No hay prendas agregadas</div>';
        return;
    }
    
    let html = '';
    prendas.forEach((prenda, indice) => {
        html += generarTarjetaPrendaReadOnly(prenda, indice);
    });
    
    container.innerHTML = html;
}

// Llamar después de agregar una prenda
window.gestorPrendaSinCotizacion.agregarPrenda(datosNuevaPreenda);
actualizarVistaItems();
```

## Estructura de Datos Esperada

```javascript
{
    id: number,
    nombre_producto: string,
    descripcion: string,
    origen: string,
    
    // Array de URLs de fotos
    fotos: [
        "/storage/prendas/foto1.jpg",
        "/storage/prendas/foto2.jpg"
    ],
    
    // Tallas agrupadas por género
    generosConTallas: {
        "dama": {
            "XS": 20,
            "S": 30
        },
        "caballero": {
            "M": 25,
            "L": 15
        }
    },
    
    // Propiedades de variación
    variantes: {
        tela: "Algodón 100%",
        color: "Azul",
        referencia: "CAM-001",
        manga: "Larga",              // Variación dinámica
        manga_obs: "Con puño...",    // Observación
        broche: "Botones",
        broche_obs: "...",
        bolsillos: "Sí",
        botones: "Sí",
        reflectivo: "No"
    },
    
    // Procesos configurados
    procesos: {
        bordado: {
            tipo: "Logo",
            datos: {
                ubicacion: "Pecho",
                tamaño: "5cm"
            }
        },
        estampado: {
            tipo: "Full Print",
            datos: { /* ... */ }
        }
    }
}
```

## Interacciones

### 1. Click en Foto
- Abre modal SweetAlert2 con galería
- Si hay múltiples fotos: muestra flechas para navegar
- Contador "Foto X de Y"
- Permite navegar entre fotos

### 2. Expandibles
- Click en "Variaciones" → Expande/contrae lista de variaciones
- Click en "Tallas" → Expande/contrae tallas por género
- Click en "Procesos" → Expande/contrae procesos aplicados
- Icono chevron rota para indicar estado

### 3. Menú Contextual (3 puntos)
```
┌─ Editar   → Abre modal para editar esta prenda
└─ Eliminar → Pide confirmación y elimina
```

**Editar:**
```javascript
window.gestionItemsUI.cargarItemEnModal(prenda, prendaIndex);
// Abre el modal con datos de la prenda para editarla
```

**Eliminar:**
```javascript
window.gestorPrendaSinCotizacion.eliminarPrenda(prendaIndex);
window.renderizarPrendasTipoPrendaSinCotizacion?.();
// Elimina la prenda y re-renderiza
```

## Estilos Personalizables

### Variables principales en CSS

```css
/* Colores */
--color-primary: #3b82f6;      /* Azul */
--color-success: #10b981;      /* Verde para variaciones */
--color-warning: #f59e0b;      /* Naranja para procesos */
--color-danger: #dc2626;       /* Rojo para eliminar */

/* Fondos */
--bg-card: white;
--bg-light: #f9fafb;
--bg-lighter: #f3f4f6;

/* Bordes */
--border-color: #e5e7eb;
```

### Clases principales

```css
.prenda-card-readonly              /* Contenedor principal */
.prenda-card-header                /* Encabezado con título y menú */
.prenda-card-body                  /* Contenido + foto */
.prenda-info-section               /* Información izquierda */
.prenda-foto-section               /* Foto derecha */
.seccion-expandible                /* Sección expandible */
.variacion-item                    /* Item de variación */
.talla-badge                       /* Badge de talla */
.proceso-item                      /* Item de proceso */
```

## Comportamiento Responsivo

| Dispositivo | Layout |
|-------------|--------|
| Desktop (768px+) | Foto a la derecha, información a la izquierda (grid 2 columnas) |
| Mobile (<768px) | Foto arriba, información abajo (grid 1 columna) |

## Dependencias

**Librerías externas:**
- SweetAlert2 (para modales)
- FontAwesome (para iconos)

**Módulos locales esperados:**
- `window.gestorPrendaSinCotizacion` - Gestor de prendas
- `window.gestionItemsUI` - Interfaz de gestión
- `window.renderizarPrendasTipoPrendaSinCotizacion()` - Función de renderizado

## Notas Importantes

⚠️ **NO editar inline** - La tarjeta es de solo lectura. Los datos se editan abriendo el modal de "Editar".

⚠️ **Sincronización de datos** - Los cambios en el modal se reflejan en la tarjeta al cerrar el modal (en la siguiente renderización).

⚠️ **Eliminación** - La eliminación es irreversible. Se pide confirmación antes de ejecutar.

## Testeo Manual

### En consola del navegador:

```javascript
// 1. Crear objeto de prenda de ejemplo
const prenda = {
    nombre_producto: "Camisa Test",
    descripcion: "Descripción de prueba",
    fotos: ["/storage/prendas/test.jpg"],
    generosConTallas: {
        dama: { S: 10, M: 20 }
    },
    variantes: {
        tela: "Algodón",
        color: "Rojo",
        manga: "Corta"
    },
    procesos: {
        bordado: { tipo: "Logo" }
    }
};

// 2. Generar HTML
const html = generarTarjetaPrendaReadOnly(prenda, 0);

// 3. Insertarlo en la página
document.getElementById('prendas-container-editable').innerHTML = html;

// 4. Probar interacciones:
// - Click en foto
// - Click en expandibles
// - Click en 3 puntos
```

## Debugging

Habilitar logs en consola:

```javascript
// En prenda-card-readonly.js, descomentar:
console.log('✅ Tarjeta generada:', prenda.nombre_producto);
```

Verificar estructura de datos:

```javascript
// Ver qué datos tiene una prenda
console.log(window.gestorPrendaSinCotizacion.obtenerPorIndice(0));
```

## Próximos Pasos (Opcionales)

- [ ] Agregar animaciones al expandir/contraer
- [ ] Agregar efecto de arrastrar para reordenar prendas
- [ ] Agregar vista previa de miniaturas de fotos
- [ ] Agregar búsqueda/filtrado de prendas
- [ ] Exportar lista de prendas como PDF

---

**Versión:** 1.0  
**Última actualización:** Enero 2026  
**Estado:** ✅ Producción
