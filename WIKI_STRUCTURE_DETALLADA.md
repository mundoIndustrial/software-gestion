# 🏗️ Estructura Detallada de Wiki para Manual de Usuario

## 📚 ARQUITECTURA GENERAL

```
📖 Centro de Ayuda
│
├── 🏠 INICIO
│   ├── Bienvenida
│   ├── ¿Cuál es tu rol?
│   └── Acceso rápido
│
├── 👩‍💼 MÓDULO ASESOR
│   ├── Asesor - Inicio
│   ├── Dashboard & Notificaciones
│   ├── Cotizaciones
│   │   ├── Cómo crear cotización de prenda
│   │   ├── Cómo crear cotización de bordado/logo
│   │   ├── Cómo crear cotización de EPP
│   │   ├── Cómo enviar cotización a contador
│   │   ├── Cómo corregir cotización rechazada
│   │   ├── Cómo gestionar borradores
│   │   └── Estados de cotización
│   ├── Pedidos
│   │   ├── Cómo crear pedido
│   │   ├── Cómo crear pedido desde cotización
│   │   ├── Cómo editar pedido
│   │   ├── Cómo agregar prendas a pedido
│   │   ├── Cómo editar prenda en pedido
│   │   ├── Cómo agregar imágenes
│   │   ├── Cómo eliminar pedido/prenda
│   │   ├── Ver pendientes del día
│   │   └── Estados de pedido
│   ├── Recibos
│   │   ├── ¿Qué es un recibo?
│   │   ├── Cómo ver recibos
│   │   ├── Cómo descargar PDF
│   │   └── Cómo imprimir recibo
│   ├── Inventario
│   │   └── Cómo consultar telas disponibles
│   ├── Coordinar con Despacho
│   │   ├── Cómo dejar observaciones
│   │   ├── Cómo ver entregas
│   │   └── Cómo hacer seguimiento
│   ├── Perfil & Cuenta
│   │   ├── Cómo editar perfil
│   │   └── Cómo cambiar contraseña
│   ├── Errores Comunes
│   │   ├── No puedo crear pedido
│   │   ├── Cotización fue rechazada
│   │   ├── Imágenes no suben
│   │   └── Otros errores
│   └── Tips & Recomendaciones
│
├── 📦 MÓDULO DESPACHO
│   ├── Despacho - Inicio
│   ├── Dashboard & Notificaciones
│   ├── Listar Pedidos
│   │   ├── Cómo ver pedidos pendientes
│   │   ├── Cómo ver pedidos entregados
│   │   ├── Cómo usar filtros
│   │   └── ¿Qué significan los estados?
│   ├── Procesar Despacho
│   │   ├── Cómo recibir prendas de bodega
│   │   ├── Cómo registrar despacho
│   │   ├── Cómo marcar como entregado
│   │   ├── Cómo hacer entrega parcial
│   │   └── Cómo deshacer despacho
│   ├── Observaciones
│   │   ├── Cómo ver observaciones del asesor
│   │   ├── Cómo dejar observación
│   │   ├── Cómo actualizar observación
│   │   └── Cómo marcar como resuelta
│   ├── Notas de Bodega
│   │   ├── Cómo crear nota
│   │   ├── Cómo editar nota
│   │   └── Cómo eliminar nota
│   ├── Documentos
│   │   ├── Cómo descargar factura
│   │   ├── Cómo imprimir guía de envío
│   │   └── Cómo ver datos del cliente
│   ├── Errores Comunes
│   │   ├── No puedo marcar como entregado
│   │   ├── Faltaban prendas
│   │   └── Debo deshacer despacho
│   └── Tips & Recomendaciones
│
├── 🏭 MÓDULO BODEGA
│   ├── Bodega - Inicio
│   ├── Dashboard & Notificaciones
│   ├── Listar Pedidos
│   │   ├── Cómo ver todos los pedidos
│   │   ├── Cómo filtrar por estado
│   │   ├── Cómo buscar pedido
│   │   └── Entendiendo los estados
│   ├── Procesar Llegada de Prendas
│   │   ├── Cómo recibir prendas de costura
│   │   ├── Cómo registrar cantidad por talla
│   │   ├── Cómo reportar faltantes
│   │   └── Cómo reportar daños
│   ├── Estados & Flujo
│   │   ├── Pendiente Costura: qué esperar
│   │   ├── Pendiente EPP: qué esperar
│   │   ├── Cómo marcar como "revisado"
│   │   └── Cómo marcar como "listo para despacho"
│   ├── Fechas Importantes
│   │   ├── Cómo actualizar fecha de entrega a despacho
│   │   ├── Cómo registrar fecha de recepción
│   │   └── Por qué importan las fechas
│   ├── Notas por Talla
│   │   ├── Cómo agregar nota a talla
│   │   ├── Cómo editar nota
│   │   └── Cómo eliminar nota
│   ├── Ocultar Pedidos
│   │   ├── Cómo ocultar pedido
│   │   ├── Cómo ver pedidos ocultos
│   │   └── Cómo restaurar pedido
│   ├── Homologación de EPP
│   │   ├── ¿Qué es homologación?
│   │   ├── Cómo homologar EPP
│   │   └── Casos comunes
│   ├── Exportar Datos
│   │   ├── Cómo exportar EPP pendientes
│   │   ├── Cómo exportar entregados
│   │   └── Cómo usar los reportes
│   ├── Errores Comunes
│   │   ├── No puedo registrar cantidad
│   │   ├── Faltaron prendas de costura
│   │   └── Cómo recuperar un pedido oculto
│   └── Tips & Recomendaciones
│
├── ⚙️ MÓDULO ADMINISTRADOR
│   ├── Administrador - Inicio
│   ├── Dashboard & Estadísticas
│   ├── Gestión de Usuarios
│   │   ├── Cómo crear usuario nuevo
│   │   ├── Cómo asignar roles
│   │   ├── Cómo editar usuario
│   │   ├── Cómo desactivar usuario
│   │   ├── Cómo resetear contraseña
│   │   └── Roles disponibles
│   ├── Aprobación de Cotizaciones (Contador)
│   │   ├── Cómo revisar cotización
│   │   ├── Cómo aprobar cotización
│   │   ├── Cómo rechazar con observaciones
│   │   ├── Cómo solicitar correcciones
│   │   └── Estados en aprobación
│   ├── Gestionar Costos
│   │   ├── Cómo crear costo de prenda
│   │   ├── Cómo editar costo
│   │   ├── Cómo aplicar descuentos
│   │   ├── Rangos de cantidad
│   │   └── Historial de costos
│   ├── Notas de Tallas
│   │   ├── Cómo agregar nota a talla
│   │   ├── Cómo editar nota
│   │   └── Qué incluir en nota
│   ├── Configuración del Sistema
│   │   ├── Datos de empresa
│   │   ├── Roles y permisos
│   │   ├── Campos obligatorios
│   │   ├── Plantillas de email
│   │   └── Configuración de notificaciones
│   ├── Reportes & Analytics
│   │   ├── Cómo generar reporte de cotizaciones
│   │   ├── Cómo generar reporte de pedidos
│   │   ├── Cómo ver estadísticas
│   │   ├── Cómo exportar a Excel
│   │   └── Interpretando los datos
│   ├── Auditoria & Logs
│   │   ├── Cómo ver cambios realizados
│   │   ├── Cómo ver quién hizo qué
│   │   └── Historial de acciones
│   ├── Perfil & Cuenta
│   │   ├── Cómo editar perfil
│   │   └── Cómo cambiar contraseña
│   ├── Errores Comunes
│   │   ├── Usuario no puede acceder
│   │   ├── Cotización no se aprueba
│   │   └── Datos de costos
│   └── Tips & Recomendaciones
│
├── 🔧 FUNCIONALIDADES TRANSVERSALES
│   ├── Notificaciones
│   │   ├── Cómo funcionan las notificaciones
│   │   ├── Cómo marcar como leída
│   │   ├── Tipos de notificaciones
│   │   └── Configurar notificaciones
│   ├── Búsqueda & Filtros
│   │   ├── Cómo buscar pedido
│   │   ├── Cómo buscar cotización
│   │   ├── Cómo usar filtros
│   │   └── Filtros disponibles por rol
│   ├── PDFs & Reportes
│   │   ├── Cómo descargar PDF de cotización
│   │   ├── Cómo descargar PDF de pedido
│   │   ├── Cómo imprimir documento
│   │   └── Problemas al descargar
│   ├── Observaciones
│   │   ├── ¿Qué es una observación?
│   │   ├── Cómo dejar observación
│   │   ├── Cómo ver historial
│   │   └── Buenas prácticas
│   ├── Cambiar Contraseña
│   │   ├── Cómo cambiar contraseña
│   │   ├── Requisitos de contraseña
│   │   └── Si olvidas contraseña
│   └── Cerrar Sesión
│
├── 📖 CONCEPTOS & GLOSARIO
│   ├── Términos Clave
│   │   ├── ¿Qué es una cotización?
│   │   ├── ¿Qué es un pedido?
│   │   ├── ¿Qué es un recibo?
│   │   ├── ¿Qué es una observación?
│   │   ├── ¿Qué es un borrador?
│   │   ├── ¿Qué es una talla?
│   │   └── ¿Qué es EPP?
│   ├── Estados & Flujos
│   │   ├── Estados de cotización
│   │   ├── Estados de pedido
│   │   ├── Flujo: De cotización a pedido
│   │   ├── Flujo: De producción a despacho
│   │   └── Flujo completo del pedido
│   └── Códigos de Error
│       ├── Errores de validación
│       ├── Errores de permisos
│       ├── Errores de conexión
│       └── Dónde reportar errores
│
├── ❓ PREGUNTAS FRECUENTES
│   ├── FAQ - Asesores
│   ├── FAQ - Despacho
│   ├── FAQ - Bodega
│   ├── FAQ - Administradores
│   └── FAQ - General
│
└── 📞 SOPORTE Y CONTACTO
    ├── Cómo reportar un problema
    ├── Contacto de soporte
    ├── Horario de atención
    └── Seguimiento de tickets

```

---

## 🎯 DETALLES POR SECCIÓN

### **PÁGINA DE INICIO: Bienvenida**

**Objetivo:** Usuario entiende rápidamente qué es el sistema y a dónde ir.

**Contenido:**
1. Título: "Bienvenido a [Nombre Sistema]"
2. Párrafo breve (2-3 líneas) sobre qué es el sistema
3. 4 tarjetas grandes con:
   - 👩‍💼 "Soy Asesor" → Link a Asesor - Inicio
   - 📦 "Soy de Despacho" → Link a Despacho - Inicio
   - 🏭 "Soy de Bodega" → Link a Bodega - Inicio
   - ⚙️ "Soy Administrador" → Link a Admin - Inicio
4. Buscador (con placeholder "Busca: Crear pedido, Cotización...")
5. Links rápidos abajo: "Contacto", "FAQ", "Glosario"

---

### **PÁGINA DE INICIO POR ROL**

**Ejemplo: Asesor - Inicio**

**Estructura:**
```
👩‍💼 Módulo Asesor

¿Cuál es tu función?
[Párrafo 1-2 líneas sobre qué hace un asesor]

Lo que puedes hacer:
✅ Crear y gestionar cotizaciones - Envía propuestas de precio
✅ Crear y gestionar pedidos - Ordenes de producción
✅ Ver recibos - Confirmaciones de entrega
✅ Consultar inventario - Telas disponibles

Navegación rápida
[4 tarjetas clicables]
• 📋 Crear Cotización → Link a "Cómo crear cotización de prenda"
• 📝 Crear Pedido → Link a "Cómo crear pedido"
• 👀 Ver Pendientes → Link a "Ver pendientes del día"
• 💬 Observaciones → Link a "Cómo dejar observaciones"

Necesitas ayuda rápida?
[Links a errores comunes]
• No puedo crear pedido
• Cotización fue rechazada
• Imágenes no suben
```

---

### **PLANTILLA: Guía Paso a Paso**

**Nombre:** "[Rol] - Cómo [ACCIÓN]"

**Estructura:**
```
## Cómo [ACCIÓN]

**¿Qué es lo que voy a lograr?**
Una frase clara. Máximo 2 líneas.

**Tiempo estimado:** 5-7 minutos

**Requisitos previos:**
- Tener pedido creado
- Tener rol de asesor
- [Otro requisito]

---

### PASOS

**Paso 1: [Nombre del paso]**
```
Descripción clara (máximo 2-3 frases).
```
📸 *Imagen: Mostrar dónde está el botón "Crear Pedido"*

**Paso 2: [Nombre del paso]**
```
Descripción clara.
```
📸 *Imagen: Form lleno con datos de ejemplo*

**Paso 3: [Nombre del paso]**
```
Descripción clara.
```
📸 *Imagen: Confirmación de éxito*

---

### CONFIRMACIÓN

¿Cómo sé que funcionó?
✅ Ves mensaje "Pedido creado exitosamente"
✅ El pedido aparece en tu lista de "Mis Pedidos"
✅ Recibes notificación

---

### ERRORES QUE PODRÍAS ENCONTRAR

| Error | Significa | Solución |
|---|---|---|
| "Campos requeridos" | Faltó completar algo | Marca con * están obligatorios |
| "Error de conexión" | Internet lento/cortado | Revisa conexión e intenta de nuevo |

---

### 💡 RECOMENDACIONES

- Tip 1: Llena los datos de cliente con cuidado
- Tip 2: Sube imágenes claras de la tela/diseño
- Tip 3: Revisa que cantidades sean correctas antes de guardar

---

### 📌 ACCIONES RELACIONADAS

- [Link] Cómo crear pedido desde cotización
- [Link] Cómo editar pedido
- [Link] Cómo agregar prendas a pedido

---

### ❓ Aún necesitas ayuda?

[Button] Ver FAQ de Asesores
[Button] Contactar Soporte
```

---

### **PLANTILLA: Referencia/Concepto**

**Nombre:** "¿Qué es [CONCEPTO]?"

**Estructura:**
```
## ¿Qué es una Cotización?

**Definición simple:**
Una propuesta de precio que envías al cliente antes de producir.

**¿Cuándo la necesitas?**
- Siempre que un cliente quiera pedir algo nuevo
- Para dar precios antes de comprometerse

**¿Cuál es la diferencia con un Pedido?**
| Cotización | Pedido |
|---|---|
| Propuesta (puede rechazarse) | Orden confirmada |
| El cliente decide | Ya está aprobado |
| Puede modificarse | Entra a producción |

**Ejemplo real:**
1. Cliente quiere 100 camisetas azules
2. Asesor crea cotización con precio
3. Contador revisa y aprueba precio
4. Cliente ve la propuesta
5. Cliente dice "sí" o "no"
6. Si dice "sí", se convierte en pedido

**Estados que pasa:**
🔲 BORRADOR → 📤 ENVIADA → ✅ APROBADA → 📦 CONVERTIDA_PEDIDO

**Acciones relacionadas:**
- [Link] Cómo crear cotización
- [Link] Cómo enviar a contador
- [Link] Cómo convertir a pedido
```

---

### **PLANTILLA: Errores Comunes**

**Nombre:** "[Rol] - Errores Comunes"

**Estructura:**
```
## Errores Comunes - Asesores

### ❌ Error: "No puedo crear pedido"

**¿Por qué ocurre?**
Generalmente hay un campo requerido sin llenar.

**¿Cómo lo resuelvo?**
1. Revisa todos los campos marcados con *
2. Asegúrate de haber seleccionado cliente
3. Verifica que haya al menos una prenda
4. Intenta nuevamente

**¿Dónde veo el error?**
Arriba de la pantalla, en color rojo, dice exactamente qué falta.

**Alternativa:** [Link] Crear pedido desde cotización aprobada

---

### ❌ Error: "Cotización fue rechazada"

**¿Por qué ocurre?**
El contador encontró algo que revisar (precio, cantidad, etc.).

**¿Qué debo hacer?**
1. Abre la cotización (está en "Mis Cotizaciones")
2. Lee la observación del contador
3. Haz los cambios que pide
4. Envía nuevamente
5. Espera aprobación

**Consejo:** Revisa bien antes de enviar la primera vez.

---

### ❌ Error: "Las imágenes no suben"

**¿Por qué ocurre?**
- Archivo muy grande (máximo 10MB)
- Formato no permitido (solo JPG, PNG)
- Conexión lenta

**¿Cómo lo resuelvo?**
1. Reduce tamaño de imagen (usa online tool)
2. Verifica que sea JPG o PNG
3. Intenta con conexión más rápida
4. Recarga la página y prueba de nuevo

**Si nada funciona:** [Link] Reportar problema a soporte
```

---

## 📸 RECOMENDACIONES DE IMÁGENES

### **Dónde agregar imágenes:**
- ✅ Cada paso en guías "Cómo..."
- ✅ Mostrando dónde hacer click
- ✅ Mostrando resultado esperado
- ✅ En errores comunes
- ❌ Menos en conceptos/definiciones

### **Qué mostrar en cada imagen:**
1. **Paso 1** → Pantalla inicial, dónde está el botón
2. **Paso 2** → Form llenado, con datos de ejemplo reales
3. **Paso 3** → Resultado (mensaje de éxito, pantalla final)

### **Estilo de imagen:**
- Captura de pantalla real del sistema (no mockups)
- Usa círculo/flecha para señalar dónde clickear
- Alta claridad (mínimo 1200px ancho)
- Incluye una pequeña descripción bajo cada imagen

### **Nomenclatura de archivos:**
```
[rol]-[accion]-paso-[numero].png
Ejemplo: asesor-crear-pedido-paso-1.png
```

---

## 🔤 ESTÁNDARES DE ESCRITURA

### **Longitud de contenido:**
- Titulo (H1): 5-7 palabras
- Párrafo: máximo 3 frases cortas
- Sección: máximo 5 minutos de lectura

### **Tipografía:**
```markdown
# Título Principal (página)
## Sección
### Subsección
#### Detalles/Tips
```

### **Elementos visuales:**
```
✅ Éxito
❌ Error / No hacer
⚠️ Atención / Advertencia
💡 Consejo / Tip
📸 Imagen
🔗 Link interno
📌 Importante
❓ Pregunta
→ Flujo / Siguiente paso
```

### **Listas:**
- Máximo 5 elementos por lista
- Si necesitas más, divide en dos listas

### **Tablas:**
- Máximo 4 columnas
- Máximo 6 filas

---

## 🔍 NAVEGACIÓN Y BÚSQUEDA

### **Breadcrumbs (ruta):**
Mostrar en cada página:
```
Inicio > Módulo Asesor > Cotizaciones > Cómo crear cotización
```

### **Links relacionados:**
Al final de cada página:
```
📌 Acciones relacionadas:
- [Cómo editar cotización]
- [Cómo enviar a contador]
- [Errores en cotización]
```

### **Buscador:**
- Debe buscar por título, contenido y palabras clave
- Sugerencias mientras escribes
- Ejemplo: "crear pedido" sugiere 5 páginas relacionadas

---

## 📊 PRIORIZACIÓN: QUÉ DOCUMENTAR PRIMERO

**Fase 1 (Crítico):** 10 páginas
1. ✅ Inicio - Bienvenida
2. ✅ Asesor - Inicio
3. ✅ Cómo crear cotización de prenda
4. ✅ Cómo crear pedido
5. ✅ Cómo enviar cotización a contador
6. ✅ Despacho - Inicio
7. ✅ Cómo marcar despacho como entregado
8. ✅ Bodega - Inicio
9. ✅ Cómo registrar recepción por talla
10. ✅ Estados (Cotizaciones, Pedidos, Despacho)

**Fase 2 (Importante):** 20 páginas
- Editar pedido/cotización
- Agregar prendas
- Observaciones
- Notificaciones
- Filtros y búsqueda
- Perfil y cuenta
- FAQ por rol
- Errores comunes

**Fase 3 (Complementario):** 15 páginas
- Admin/Contador
- Configuración
- Reportes
- Glosario completo
- Tips avanzados

---

## ✅ CHECKLIST: Antes de Publicar Página

- [ ] Título claro (5-7 palabras)
- [ ] Objetivo está claro en primeras 2 líneas
- [ ] Pasos numerados y en orden lógico
- [ ] Cada paso tiene máximo 3 frases
- [ ] Imágenes acompañan pasos clave
- [ ] Sección "Errores" está presente
- [ ] Recomendaciones/tips están incluidos
- [ ] Links a páginas relacionadas funcionan
- [ ] Sin jerga técnica sin explicar
- [ ] Botones/menús en NEGRITA
- [ ] Campos requeridos marcados con *
- [ ] Tiempo estimado es realista
- [ ] Alguien no técnico la entiende
- [ ] Revisada ortografía y gramática
