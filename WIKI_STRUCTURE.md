# 📚 Estructura de Wiki - Manual de Usuario

## Jerarquía General de Páginas

```
📖 Centro de Ayuda
├── 🏠 Inicio (Introducción General)
├── 👥 Mi Rol (selector dinámico)
│   ├── 👩‍💼 Asesora
│   ├── 🏭 Producción
│   ├── 📦 Despacho
│   └── ⚙️ Administrador
├── 🔧 Tareas Frecuentes (transversales)
├── ❓ Preguntas Frecuentes
└── 📞 Soporte y Contacto
```

## Estructura Detallada

### 1. **Página Principal: Inicio**
- Bienvenida breve (máx. 3 párrafos)
- 4 tarjetas con links a roles principales
- Acceso directo a "Tareas Frecuentes"
- Búsqueda visible
- Contacto de soporte

---

### 2. **Por Rol (4 secciones principales)**

Cada rol tiene su propia estructura:

```
[NOMBRE DEL ROL]
├── 📋 Descripción General
├── 🎯 Funcionalidades Principales
│   ├── Función 1
│   ├── Función 2
│   └── Función 3
├── 📚 Guías Paso a Paso
│   ├── Tarea 1
│   ├── Tarea 2
│   └── Tarea 3
├── ⚠️ Errores Comunes
└── 💡 Tips y Recomendaciones
```

---

### 3. **Sección Transversal: Tareas Frecuentes**
- Búsqueda de pedidos
- Cambiar contraseña
- Descargar reportes
- Ver historial
- Exportar datos

---

### 4. **FAQ y Soporte**
- Preguntas por rol
- Problemas comunes
- Contacto con soporte

---

## Convención de Nombres de Páginas

| Tipo de Página | Patrón | Ejemplo |
|---|---|---|
| **Introducción de rol** | [Rol] - Inicio | Asesora - Inicio |
| **Guía paso a paso** | [Rol] - Cómo [acción] | Producción - Cómo registrar cortes |
| **Referencia** | [Rol] - [Concepto] | Despacho - Estados de envío |
| **Solución de problemas** | [Rol] - Errores comunes | Administrador - Errores comunes |
| **Conceptos generales** | ¿Qué es [concepto]? | ¿Qué es un recibi? |

### Principios de Nombrado
- ✅ Verbos claros: "Cómo crear...", "Cómo editar..."
- ✅ Palabras clave al inicio (mejor para búsqueda)
- ✅ Evitar abreviaturas
- ✅ Preguntas naturales para FAQs

---

## Estructura de Rutas (para mencionar en URLs)

```
/wiki/inicio
/wiki/roles/asesora/inicio
/wiki/roles/asesora/como-crear-pedido
/wiki/roles/asesora/errores-comunes
/wiki/roles/produccion/inicio
/wiki/roles/produccion/como-registrar-corte
...
/wiki/tareas-frecuentes
/wiki/faq
```

---

## Resumen de Módulos y Páginas Estimadas

| Módulo | Páginas Estimadas | Tiempo de Lectura |
|---|---|---|
| **Asesora** | 8-10 | 15-20 min |
| **Producción** | 10-12 | 20-25 min |
| **Despacho** | 8-10 | 15-20 min |
| **Administrador** | 12-15 | 25-30 min |
| **Transversal** | 5-8 | 10-15 min |
| **Total** | 43-55 | 85-110 min |

---

## Recomendaciones de Implementación

### En Notion
1. Crear workspace separado para documentación
2. Usar base de datos con filtros por rol
3. Integrar tabla de contenidos automática
4. Usar plantillas para consistencia

### Formato
- Títulos jerárquicos claros (H1, H2, H3)
- Máximo 2 niveles de profundidad en navegación
- Breadcrumbs en cada página: "Inicio > Rol > Sección > Página"
