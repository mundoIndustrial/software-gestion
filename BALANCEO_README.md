# Sistema de Balanceo de Líneas de Producción

## 📋 Descripción

Sistema completo para gestionar el balanceo de operaciones en procesos de confección textil. Permite crear prendas, definir operaciones con sus tiempos estándar (SAM), asignar operarios y calcular automáticamente métricas de producción.

## 🎯 Características Principales

### 1. Gestión de Prendas
- Crear y administrar diferentes tipos de prendas (camisa, pantalón, polo, chaqueta, vestido, etc.)
- Subir imagen de referencia de cada prenda
- Asignar referencia única a cada prenda
- Descripción detallada de la prenda

### 2. Balanceo de Operaciones
Cada balanceo incluye:
- **Tabla de operaciones** con los siguientes campos:
  - **Letra**: Identificador único (A, B, C, etc.)
  - **Operación**: Descripción detallada de la tarea
  - **Precedencia**: Operación que debe completarse antes
  - **Máquina**: Tipo de máquina (FL, PL, 2 AG 1/4, CRR, PRET, PRES)
  - **SAM**: Tiempo estándar en segundos
  - **Operario**: Nombre del operario asignado
  - **OP**: Código del grupo de operaciones (op1, op2, etc.)
  - **Sección**: Parte de la prenda (DEL=Delantero, TRAS=Trasero, ENS=Ensamble)
  - **Operario A**: Reasignación del operario

### 3. Cálculos Automáticos
El sistema calcula automáticamente:
- **SAM Total**: Suma de todos los tiempos SAM
- **Tiempo Disponible**: Total de horas y segundos disponibles
- **Meta Teórica**: Producción máxima posible
- **Meta Real**: Basada en el cuello de botella
- **Meta Sugerida al 85%**: Meta realista considerando eficiencia
- **Cuello de Botella**: Operación más lenta que limita la producción
- **Tiempo Cuello de Botella**: SAM de la operación más lenta

### 4. Parámetros de Producción
- Total de operarios
- Número de turnos
- Horas por turno

## 🚀 Cómo Usar

### Paso 1: Acceder al Módulo
1. Haz clic en el botón **"Balanceo"** (con ícono de reloj) en el sidebar
2. Verás el listado de todas las prendas registradas

### Paso 2: Crear una Nueva Prenda
1. Haz clic en **"Nueva Prenda"**
2. Completa el formulario:
   - Nombre de la prenda
   - Referencia (opcional)
   - Tipo de prenda
   - Descripción
   - Imagen (opcional)
3. Haz clic en **"Crear Prenda"**

### Paso 3: Configurar el Balanceo
1. Haz clic en la tarjeta de la prenda
2. Si no existe balanceo, haz clic en **"Crear Balanceo"**
3. Configura los parámetros de producción:
   - Total de operarios
   - Turnos
   - Horas por turno

### Paso 4: Agregar Operaciones
1. Haz clic en **"Nueva Operación"**
2. Completa el formulario con los datos de la operación:
   - Letra identificadora
   - Descripción de la operación
   - SAM (tiempo en segundos)
   - Máquina a utilizar
   - Operario asignado
   - Sección de la prenda
   - Otros campos opcionales
3. Haz clic en **"Guardar"**

### Paso 5: Ver Métricas
Las métricas se calculan automáticamente cada vez que:
- Agregas una nueva operación
- Editas una operación existente
- Eliminas una operación
- Cambias los parámetros de producción

## 📊 Interpretación de Métricas

### SAM Total
Suma de todos los tiempos de las operaciones. Indica el tiempo total necesario para completar una prenda.

### Meta Teórica
Cantidad máxima de prendas que se podrían producir si todas las operaciones se ejecutaran al mismo tiempo sin cuellos de botella.

**Fórmula**: `Tiempo Disponible Total (segundos) / SAM Total`

### Meta Real
Cantidad realista de prendas basada en la operación más lenta (cuello de botella).

**Fórmula**: `Tiempo Disponible Total (segundos) / SAM del Cuello de Botella`

### Meta Sugerida (85%)
Meta conservadora considerando una eficiencia del 85%, que es más realista en producción.

**Fórmula**: `Meta Real × 0.85`

### Cuello de Botella
La operación con el mayor tiempo SAM. Esta operación limita la velocidad de toda la línea de producción.

## 🎨 Tipos de Máquinas Comunes

- **FL**: Fileteadora
- **PL**: Plana
- **2 AG 1/4**: Dos agujas con separación de 1/4"
- **CRR**: Cerradora
- **PRET**: Pretinadora
- **PRES**: Presilladora

## 📝 Secciones de la Prenda

- **DEL**: Delantero
- **TRAS**: Trasero
- **ENS**: Ensamble
- **OTRO**: Otras secciones

## 💡 Consejos de Uso

1. **Orden de Operaciones**: Asigna un orden lógico a las operaciones según el flujo de producción
2. **Precedencias**: Usa el campo precedencia para indicar dependencias entre operaciones
3. **Grupos de Operarios**: Usa el campo "OP" para agrupar operaciones que realiza el mismo equipo
4. **Actualización de Parámetros**: Ajusta los parámetros de producción según la disponibilidad real de operarios
5. **Optimización**: Identifica el cuello de botella y considera asignar más operarios a esa operación

## 🗄️ Estructura de Base de Datos

### Tabla: prendas
- id, nombre, descripcion, imagen, referencia, tipo, activo

### Tabla: balanceos
- id, prenda_id, version, total_operarios, turnos, horas_por_turno, métricas calculadas

### Tabla: operaciones_balanceo
- id, balanceo_id, letra, operacion, precedencia, maquina, sam, operario, op, seccion, operario_a, orden

## 🔄 Datos de Ejemplo

El sistema incluye datos de ejemplo:
- **Camisa Polo Básica**: 12 operaciones completas
- **Pantalón Jean Clásico**: 10 operaciones completas

Para cargar los datos de ejemplo:
```bash
php artisan db:seed --class=BalanceoSeeder
```

## 🛠️ Funcionalidades Técnicas

- **Cálculo Automático**: Las métricas se recalculan automáticamente con cada cambio
- **Validación de Datos**: Todos los campos tienen validación en el backend
- **Interfaz Reactiva**: Usa Alpine.js para actualización en tiempo real
- **Edición en Línea**: Edita operaciones directamente desde la tabla
- **Eliminación Segura**: Confirmación antes de eliminar operaciones
- **Versionamiento**: Cada balanceo tiene una versión para control de cambios

## 📱 Responsive Design

El sistema es completamente responsive y funciona en:
- Escritorio
- Tablets
- Móviles

## 🎯 Próximas Mejoras Sugeridas

1. Exportar balanceo a Excel/PDF
2. Gráficos de distribución de carga por operario
3. Comparación entre diferentes versiones de balanceo
4. Simulador de escenarios (¿qué pasa si agrego un operario?)
5. Historial de cambios en el balanceo
6. Plantillas de operaciones reutilizables
7. Importación masiva de operaciones desde Excel

---

**Desarrollado para Mundo Industrial**
Sistema de Gestión de Producción Textil
