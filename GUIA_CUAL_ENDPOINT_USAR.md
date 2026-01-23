# 📋 GUÍA: CUÁL ENDPOINT USAR PARA PEDIDOS

## ¿CONFUNDIDO? AQUÍ ESTÁ LA RESPUESTA

### Pregunta: ¿Qué endpoint debo usar?

Responde estas preguntas:

**1. ¿Quién eres?**
- ☐ Un **Asesor** (usuario interno)  → Usa `/asesores/pedidos`
- ☐ Un **Sistema externo / API** → Usa `/api/pedidos`
- ☐ **No sé** → Pregunta abajo 👇

**2. ¿Qué datos tienes?**
- ☐ Productos con detalles complejos (prendas, logos, telas) → Usa `/asesores/pedidos`
- ☐ Solo datos básicos (cliente_id, descripcion, prendas_simples) → Usa `/api/pedidos`

**3. ¿Cuál es tu caso de uso?**
- ☐ Crear un **borrador** que voy a confirmar después → Usa `/asesores/pedidos`
- ☐ Crear un **pedido formal** directamente → Usa `/api/pedidos`

---

## 📊 TABLA DE DECISIÓN

| Si quieres... | Usa esta ruta | Ejemplo |
|---------------|--------------|---------|
| Crear borrador con productos/logos | `/asesores/pedidos` | Asesor creando propuesta |
| Crear pedido desde sistema externo | `/api/pedidos` | App móvil, webhook, integración |
| Listar pedidos de un cliente | `/api/pedidos/cliente/{id}` | Reportes, consultas |
| Confirmar pedido | `/api/pedidos/{id}/confirmar` | Cambiar estado a CONFIRMADO |
| Cancelar pedido | `/api/pedidos/{id}/cancelar` | Anular un pedido |

---

## 🔴 ADVERTENCIA IMPORTANTE

### ⚠️ NO mezcles ambos endpoints en la misma operación

**MALO ❌:**
```javascript
// NO HAGAS ESTO:
POST /asesores/pedidos           // Creas en tabla pedidos_produccion
POST /api/pedidos                // Creas en tabla pedidos (DISTINTA)
// Ahora tienes 2 pedidos en 2 tablas sin relación
```

**BUENO ✅:**
```javascript
// ELIGE UNO U OTRO:

// Opción A (Legacy - Asesores internos)
POST /asesores/pedidos    // Crea borrador
POST /asesores/pedidos/confirm   // Confirma

// Opción B (DDD - Sistemas externos)
POST /api/pedidos         // Crea pedido
PATCH /api/pedidos/{id}/confirmar  // Confirma
```

---

## 📚 DOCUMENTACIÓN COMPLETA

- **Para asesores internos**: Ver documentación de `/asesores` (legacy)
- **Para sistemas externos**: Ver [GUIA_API_PEDIDOS_DDD.md](GUIA_API_PEDIDOS_DDD.md)

---

## 🎯 RECOMENDACIÓN

**Si estás integrando un nuevo sistema en 2026:**
👉 **USA `/api/pedidos`** (DDD, moderno, bien documentado)

**Si eres asesor interno:**
👉 **USA `/asesores/pedidos`** (legacy, sigue funcionando)

---

## 🚀 PLAN FUTURO

En el futuro (cuando migre `/asesores/pedidos` a DDD):
```
/asesores/pedidos → Será redirigido a /api/pedidos
```

Pero por ahora, ambas funcionan independientemente.
