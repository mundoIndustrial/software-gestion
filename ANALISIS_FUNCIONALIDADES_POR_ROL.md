# 📊 Análisis de Funcionalidades por Rol

## Documento Base para Crear Manual de Usuario Type Wiki

---

## 1️⃣ ROL: ASESORA (👩‍💼 Asesor Comercial)

### Función Principal
Crear y gestionar cotizaciones, pedidos y seguimiento de producción. Punto de entrada principal para clientes y supervisor.

### Módulos Principales

#### **A) DASHBOARD**
**¿Qué ve?**
- Resumen de pedidos: cantidad total, por estado
- Pendientes del día
- Cotizaciones en proceso
- Notificaciones y alertas
- Gráficos de productividad

**Acciones clave:**
- Ver estadísticas generales
- Acceso rápido a pendientes
- Abrir notificaciones

---

#### **B) GESTIÓN DE COTIZACIONES**
**Tipos de cotizaciones:**
1. **Cotización de Prenda** (Ropa)
   - Crear nueva cotización
   - Agregar prendas con detalles
   - Subir imágenes de diseño/tela
   - Definir tallas y colores
   - Editar antes de enviar

2. **Cotización de Bordado/Logo**
   - Crear cotización de bordado
   - Subir archivo de logo
   - Definir posición y técnica
   - Prendas base para bordado

3. **Cotización de EPP** (Equipos Protección Personal)
   - Crear cotización de EPP
   - Agregar artículos
   - Cantidades por talla
   - Personalización

**Flujo:**
1. Crear cotización en borrador
2. Agregar prendas/detalles/imágenes
3. Guardar borrador (puede continuar después)
4. Enviar a contador para aprobación
5. Recibir aprobación/rechazos
6. Corregir si es necesario
7. Convertir a pedido

**Estados de cotización:**
- 🔲 BORRADOR (en edición)
- 📤 ENVIADA (esperando contador)
- ✅ APROBADA_CONTADOR (contador aprobó)
- 🔄 REQUIERE_CORRECCION (contador rechazó)
- 📋 APROBADA_CLIENTE (cliente aceptó)
- 📦 CONVERTIDA_PEDIDO (se convirtió en pedido)

---

#### **C) GESTIÓN DE PEDIDOS**
**Crear pedido:**
- Desde cotización aprobada
- Nuevo (sin cotización previa)
- Desde borrador

**Editar pedido:**
- Agregar prendas nuevas
- Modificar cantidades
- Cambiar colores/telas
- Agregar EPP
- Editar datos del cliente

**Información en pedido:**
- Datos del cliente (nombre, email, teléfono)
- Número de pedido (auto-generado)
- Prendas con detalles (modelo, talla, color, cantidad)
- Especificaciones de costura/bordado
- Observaciones especiales
- Fechas (creación, entrega esperada)

**Estados del pedido:**
- 📝 CREADO
- 🔍 EN_REVISION (supervisión)
- 🏭 EN_PRODUCCION (en taller)
- 📦 LISTA_PARA_DESPACHO (terminado)
- 🚚 EN_DESPACHO
- ✅ ENTREGADO
- ❌ ANULADO

**Acciones:**
- Listar todos pedidos
- Ver pedidos borradores (sin confirmar)
- Ver pedidos pendientes (requieren acción)
- Ver detalles completos
- Editar pedido
- Eliminar/anular pedido
- Descargar PDF

---

#### **D) PRENDAS EN PEDIDO**
**Detalles de prenda:**
- Tipo de prenda (tipo manga, tipo botón, etc.)
- Talla/Cantidad
- Color de tela
- Especificaciones de costura
- Detalles de bordado (si aplica)
- Procesos especiales (planchado, empaque, etc.)

**Imágenes asociadas:**
- Foto de prenda
- Muestra de tela
- Diseño/bordado
- Referencia de cliente

**Variantes:**
- Cambiar entre tallas
- Cambiar colores por talla
- Agregar variaciones de diseño

---

#### **E) RECIBOS DE PEDIDOS**
**Qué es un recibo:**
- Documento de confirmación de entrega a bodega/despacho
- Generado automáticamente cuando se marca un pedido

**Información en recibo:**
- Número de recibo
- Número de pedido
- Asesor responsable
- Detalles de prendas
- Cantidades
- Observaciones especiales
- Fecha de recepción

**Acciones:**
- Ver recibos
- Descargar PDF
- Imprimir
- Ver datos asociados

---

#### **F) INVENTARIO DE TELAS**
**Acceso:**
- Consultar telas disponibles
- Ver colores disponibles por tela
- Ver stock (cantidad disponible)

**Información:**
- Nombre de tela
- Código
- Colores disponibles
- Cantidad en stock
- Precio unitario

---

#### **G) NOTIFICACIONES**
**Tipos:**
- Cotización rechazada por contador
- Cotización aprobada
- Pedido listo para producción
- Problemas en producción
- Pedido listo para despacho
- Entregas de despacho
- Observaciones de bodega/despacho

**Acciones:**
- Ver todas las notificaciones
- Marcar como leída
- Marcar todas como leídas
- Eliminar notificación

---

#### **H) OBSERVACIONES Y ENTREGAS DE DESPACHO**
**Observaciones:**
- Dejar notas para equipo de despacho
- Ver observaciones recibidas del despacho
- Marcar como leída

**Entregas:**
- Ver estado de entregas (parcial/completo)
- Marcar entrega como recibida
- Seguimiento de envíos

---

#### **I) PERFIL DE ASESOR**
**Información:**
- Nombre completo
- Email
- Teléfono
- Empresa/Sucursal
- Datos bancarios (opcional)

**Acciones:**
- Editar perfil
- Cambiar contraseña
- Ver comisiones (si aplica)

---

#### **J) BUSCADOR Y FILTROS**
**Búsqueda por:**
- Número de pedido
- Número de cotización
- Nombre de cliente
- Email de cliente
- Rango de fechas
- Estado de pedido/cotización
- Asesor responsable

---

## 2️⃣ ROL: DESPACHO (📦 Operario de Despacho)

### Función Principal
Recibir prendas de producción, empacar y preparar para envío. Coordina entregas a clientes.

### Módulos Principales

#### **A) DASHBOARD**
**¿Qué ve?**
- Pedidos listos para despacho
- Despachos pendientes
- Despachos entregados
- Cantidad de prendas por despachar
- Observaciones pendientes

**Acciones:**
- Ver estado general
- Acceso a filtros y búsqueda

---

#### **B) LISTA DE PEDIDOS**
**Vistas disponibles:**

1. **Pendientes Unificados**
   - Todos los pedidos que NO se han despachado
   - Muestra prendas de costura + EPP
   - Cantidad faltante por despachar

2. **Entregados**
   - Pedidos ya despachados
   - Historial de entregas
   - Fecha de entrega

3. **Pendientes de Costura**
   - Prendas que aún están en producción
   - No listas para despacho

4. **Pendientes de EPP**
   - EPP pendiente de envío
   - Cantidad faltante

---

#### **C) DETALLES DE PEDIDO**
**Al abrir un pedido:**
- Cliente (nombre, email, teléfono)
- Número de pedido
- Prendas con cantidad total por talla
- Estado de cada prenda
- Observaciones de producción
- Observaciones de asesor

**Información visible:**
- Cantidad recibida de cada prenda
- Cantidad faltante
- Detalles especiales (bordado, personalización)

---

#### **D) GUARDAR DESPACHO**
**Proceso:**
1. Recibir prendas de producción
2. Verificar cantidad y calidad
3. Registrar cantidad recibida por talla
4. Guardar despacho en sistema

**Datos a ingresar:**
- Cantidad de prendas por talla
- Observaciones (daños, faltantes, etc.)
- Persona que realiza despacho

---

#### **E) MARCAR COMO ENTREGADO**
**Opciones:**

1. **Entregar Todo**
   - Marca todas las prendas como entregadas
   - Valida que cantidad = total esperada

2. **Entregar Parcial**
   - Ingresa cantidad a entregar por talla
   - Permite entregas en múltiples fechas

3. **Deshacer Entregado**
   - Revierte un despacho
   - Vuelve a estado "pendiente"

---

#### **F) OBSERVACIONES DE DESPACHO**
**¿Qué son?**
- Notas sobre problemas/especificaciones en despacho
- Comunicación entre equipos

**Crear observación:**
- Seleccionar tipo (problema, especificación, etc.)
- Escribir descripción
- Asignar a persona/área

**Ver observaciones:**
- De asesor (que dejó el asesor)
- Propias (que dejó despacho)
- Historial completo

**Acciones:**
- Agregar observación
- Marcar como leída
- Actualizar observación
- Eliminar observación

---

#### **G) NOTAS DE BODEGA**
**¿Para qué sirven?**
- Anotaciones sobre problemas/hallazgos
- Instrucciones especiales

**Crear nota:**
- Escribir contenido
- Guardar

**Gestionar notas:**
- Ver todas las notas del pedido
- Actualizar nota
- Eliminar nota

---

#### **H) FACTURA Y DOCUMENTOS**
**Acceso:**
- Descargar factura del pedido
- PDF de datos del cliente
- Datos de envío

---

#### **I) NOTIFICACIONES**
**Recibe notificaciones de:**
- Nuevos pedidos listos para despacho
- Cambios en pedidos
- Observaciones nuevas
- Mensajes del asesor

**Acciones:**
- Ver notificaciones
- Marcar como leída
- Marcar todas como leídas

---

#### **J) IMPRESIÓN**
**Documentos a imprimir:**
- Etiqueta de despacho
- Factura
- Guía de envío
- Especificaciones del cliente

---

## 3️⃣ ROL: PRODUCCIÓN/BODEGA (🏭 Operario de Bodega)

### Función Principal
Recibir prendas de costura, organizar por talla, registrar cantidades. Gestionar inventario y coordinar entregas a despacho.

### Módulos Principales

#### **A) DASHBOARD**
**¿Qué ve?**
- Pedidos por procesar
- Cantidad de prendas recibidas vs. esperadas
- Pendientes de costura
- Pendientes de EPP
- Notificaciones de cambios

---

#### **B) LISTADO DE PEDIDOS**
**Vistas principales:**

1. **Todos los Pedidos**
   - Listado completo
   - Estado de cada uno
   - Cantidad recibida/esperada

2. **Pendiente Costura**
   - Prendas que aún no llegan del taller de costura
   - Cantidad esperada vs. recibida

3. **Pendiente EPP**
   - EPP sin recibir
   - Cantidades
   - Proveedores

4. **Entregados**
   - Pedidos que ya pasaron a despacho
   - Fecha de entrega
   - Historial

5. **Anulados**
   - Pedidos cancelados
   - Razón de anulación

6. **Ocultos**
   - Pedidos marcados como ocultos
   - Opción de restaurar

---

#### **C) DETALLES DE PEDIDO**
**Información completa:**
- Número de pedido
- Cliente
- Prendas esperadas (por talla/color)
- Cantidad recibida
- Cantidad faltante
- Observaciones de producción
- Observaciones de asesor
- Especificaciones especiales

**Por cada talla:**
- Cantidad esperada
- Cantidad recibida
- Diferencia
- Campo para registrar entrega

---

#### **D) REGISTRAR RECEPCIÓN POR TALLA**
**Proceso:**
1. Recibir prendas del taller/proveedor
2. Verificar cantidad y calidad
3. Registrar cantidad por talla
4. Guardar en sistema

**Datos a ingresar:**
- Número de prendas por talla
- Observaciones (daños, devoluciones, etc.)
- Fecha de recepción

---

#### **E) MARCAR VISTO / REVISADO**
**Acciones:**

1. **Marcar Visto**
   - Indica que viste el pedido
   - Solo para rol EPP-Bodega

2. **Revisar Pedido**
   - Valida que todas las prendas estén completas
   - Generalmente antes de pasar a despacho

3. **Ocultar Pedido**
   - Esconde pedido de lista principal
   - Acceso desde "Pedidos Ocultos"

4. **Deshacer Ocultar**
   - Restaura pedido a lista visible

---

#### **F) ACTUALIZAR FECHAS**
**Fechas importantes:**

1. **Fecha de Entrega a Despacho**
   - Cuándo se entregó el pedido al área de despacho
   - Marca transición de bodega → despacho

2. **Fecha de Revisión**
   - Cuándo se revisó última vez

---

#### **G) ACTUALIZAR OBSERVACIONES**
**Crear observación:**
- Describir problema/evento
- Asignar a responsable
- Guardar

**Ver observaciones:**
- Historial de cambios
- Quién dejó cada nota

---

#### **H) NOTAS POR PEDIDO Y TALLA**
**¿Qué son?**
- Anotaciones sobre específico de la talla
- Instrucciones de manejo
- Problemas identificados

**Crear nota:**
- Seleccionar talla
- Escribir nota
- Guardar

**Gestionar notas:**
- Ver notas por talla
- Actualizar
- Eliminar

---

#### **I) HOMOLOGACIÓN DE EPP**
**¿Qué es?**
- Proceso de transformar EPP pendiente en entregado
- Aplicar cambios/sustituciones

**Datos necesarios:**
- EPP actual
- EPP a sustituir (si aplica)
- Razón de cambio
- Cantidad

---

#### **J) EXPORTAR DATOS**
**Opciones:**
- Exportar EPP pendientes a Excel
- Generar reporte de entregados
- Generar reporte de pendientes

**Información en reporte:**
- Número de pedido
- Cliente
- Prendas
- Cantidades
- Fechas

---

#### **K) NOTIFICACIONES**
**Recibe notificaciones de:**
- Nuevos pedidos para procesar
- Cambios en cantidades
- Mensajes del asesor
- Nuevas observaciones

**Acciones:**
- Ver notificaciones
- Marcar leída/todas leídas
- Toggle para ver noticias vs. pedidos

---

#### **L) FACTURAS Y DATOS**
**Acceso:**
- Datos del cliente (nombre, dirección, email, teléfono)
- Datos de factura
- Información para comprobante

---

## 4️⃣ ROL: ADMINISTRADOR (⚙️ Admin/Contador)

### Función Principal
Gestionar configuración del sistema, revisar cotizaciones, aprobar costos, crear usuarios, generar reportes.

### Módulos Principales

#### **A) USUARIO Y SEGURIDAD**
**Gestión de usuarios:**
- Crear usuario nuevo
- Editar datos de usuario
- Asignar roles
- Cambiar permisos
- Activar/desactivar usuario
- Resetear contraseña

**Roles disponibles:**
- Asesor
- Bodeguero
- Despacho
- Contador
- Supervisor
- Admin

---

#### **B) MÓDULO CONTADOR (Aprobación de Cotizaciones)**
**Dashboard:**
- Cotizaciones por revisar
- Cotizaciones aprobadas
- Todas las cotizaciones

**Procesos:**

1. **Revisar Cotización**
   - Ver detalles de prendas
   - Ver costos propuestos
   - Ver imágenes de diseño

2. **Aprobar/Rechazar**
   - Aprobar cotización
   - Rechazar con observaciones
   - Solicitar correcciones

3. **Estados que maneja:**
   - ENVIADA (del asesor) → APROBADA_CONTADOR o REQUIERE_CORRECCION
   - APROBADA_CONTADOR → disponible para convertir en pedido

---

#### **C) GESTIONAR COSTOS DE PRENDAS**
**Detalles:**
- Crear costo para prenda
- Editar costo
- Ver historial de costos
- Aplicar descuentos
- Exportar costos

**Información:**
- Prenda
- Tipo (costura, bordado, EPP)
- Costo unitario
- Rango de cantidad (1-10, 11-50, 50+)
- Descuentos aplicables

---

#### **D) NOTAS DE TALLAS**
**¿Qué son?**
- Anotaciones sobre especificaciones de cada talla
- Instrucciones de costura por talla

**Crear nota:**
- Prenda
- Talla
- Contenido
- Guardar

---

#### **E) TEXTO PERSONALIZADO DE TALLAS**
**¿Qué es?**
- Texto adicional a mostrar en producción
- Instrucciones especiales por talla

---

#### **F) PERFIL Y CONFIGURACIÓN**
**Datos del admin:**
- Nombre
- Email
- Teléfono
- Empresa

**Acciones:**
- Editar perfil
- Cambiar contraseña

---

#### **G) CONFIGURACIÓN DEL SISTEMA**
**Posibles configuraciones:**
- Empresa (nombre, datos, logo)
- Email de notificaciones
- Configuración de roles y permisos
- Plantillas de PDF
- Campos obligatorios
- Flujos de aprobación

---

#### **H) REPORTES Y ANALYTICS**
**Reportes disponibles:**
- Cotizaciones creadas (por período, asesor, estado)
- Pedidos creados (por período, estado)
- Entregados vs. pendientes
- Producción por área
- Ingresos (si integración financiera)
- Despachos completados

**Datos que se exportan:**
- PDF
- Excel
- Visualización gráfica

---

#### **I) SISTEMA DE PERMISOS**
**¿Qué se puede controlar?**
- Acceso a módulos
- Acceso a datos (solo propios vs. todos)
- Permisos de lectura/escritura
- Roles especiales (supervisor_pedidos, supervisor_asesores, etc.)

---

#### **J) NOTIFICACIONES DEL SISTEMA**
**Configurar:**
- Alertas críticas
- Notificaciones de error
- Logs de cambios
- Auditoría de acciones

---

## 📋 TABLA COMPARATIVA DE ACCIONES POR ROL

| Acción | Asesora | Despacho | Bodega | Admin |
|---|---|---|---|---|
| **Ver Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **Crear Cotización** | ✅ | ❌ | ❌ | ❌ |
| **Crear Pedido** | ✅ | ❌ | ❌ | ❌ |
| **Editar Pedido** | ✅ | ❌ | ❌ | ✅ |
| **Ver Pedidos** | ✅ | ✅ | ✅ | ✅ |
| **Registrar Recepción (Bodega)** | ❌ | ❌ | ✅ | ✅ |
| **Marcar Despacho** | ❌ | ✅ | ❌ | ✅ |
| **Aprobar Cotización** | ❌ | ❌ | ❌ | ✅ |
| **Crear Usuario** | ❌ | ❌ | ❌ | ✅ |
| **Ver Observaciones** | ✅ | ✅ | ✅ | ✅ |
| **Dejar Observaciones** | ✅ | ✅ | ✅ | ✅ |
| **Exportar Datos** | ✅ | ✅ | ✅ | ✅ |

---

## 🔄 FLUJOS PRINCIPALES DEL SISTEMA

### **Flujo: De Cotización a Pedido**
```
1. Asesor crea cotización
2. Asesor agrega prendas/detalles
3. Asesor envía a contador
4. Contador revisa y aprueba
5. Asesor convierte cotización a pedido
6. Pedido entra en producción
```

### **Flujo: De Producción a Despacho**
```
1. Costura fabrica prendas
2. Bodega recibe prendas
3. Bodega registra cantidad por talla
4. Bodega marca como "Listo para Despacho"
5. Despacho recibe y verifica
6. Despacho registra despacho
7. Despacho marca como "Entregado"
```

### **Flujo: De Cotización a Rechazo**
```
1. Asesor crea cotización
2. Asesor envía a contador
3. Contador revisa y rechaza
4. Asesor recibe notificación
5. Asesor corrige cotización
6. Asesor envía nuevamente
```

---

## 🎯 FUNCIONALIDADES TRANSVERSALES

### Disponibles para TODOS:
- ✅ Ver notificaciones
- ✅ Cambiar contraseña
- ✅ Editar perfil
- ✅ Ver PDFs
- ✅ Imprimir documentos
- ✅ Buscar/filtrar
- ✅ Dejar observaciones
- ✅ Descargar reportes

### Por Rol:
- **Asesor**: Crear/editar cotizaciones y pedidos
- **Bodega**: Registrar recepciones
- **Despacho**: Marcar entregas
- **Admin**: Aprobar y configurar

---

## 📝 CONCEPTOS CLAVE A EXPLICAR

1. **Cotización vs Pedido**
   - Cotización: Propuesta de precio (puede rechazarse)
   - Pedido: Orden de producción (confirmada)

2. **Borrador**
   - Guardado temporal sin completar
   - Puede editarse después
   - No se procesa en producción

3. **Estado**
   - Cada documento tiene un estado
   - Define qué acciones son posibles

4. **Observación**
   - Nota comunicativa entre equipos
   - Visible para todos
   - Requiere respuesta/acción

5. **Recibo**
   - Comprobante de entrega a siguiente área
   - Auto-generado
   - Importante para auditoría

6. **Talla**
   - Tamaño de la prenda (XS, S, M, L, XL, etc.)
   - Se especifica cantidad por talla
   - Bodega registra recepción por talla

7. **EPP**
   - Equipos de protección personal
   - Artículos adicionales (gorras, cinturones, etc.)
   - Flujo separado

---

## 📊 ESTIMACIÓN: PÁGINAS DE DOCUMENTACIÓN NECESARIAS

| Módulo | Páginas Estimadas | Temas |
|---|---|---|
| **Asesora** | 12 | Cotizaciones, Pedidos, Recibos, Inventario, Perfil |
| **Despacho** | 8 | Listar, Detalles, Marcar Entregado, Observaciones |
| **Bodega** | 10 | Listar, Recibir, Estados, Notas, Fechas |
| **Admin** | 10 | Cotizaciones, Costos, Usuarios, Configuración |
| **Transversal** | 5 | Notificaciones, Búsqueda, PDFs, Reportes |
| **Conceptos** | 6 | Glosario, Flujos, Estados, Errores comunes |
| **Total** | ~51 | - |

---

## 🚀 Recomendación para la Wiki

1. **Crear estructura por rol** → Usuario elige su rol al inicio
2. **Cada rol tiene sidebar** con sus funciones principales
3. **Páginas de "Cómo..."** para acciones comunes
4. **Tabla de estados** como referencia rápida
5. **Sección de errores comunes** por rol
6. **Glosario global** de términos
7. **FAQ** agrupado por rol
