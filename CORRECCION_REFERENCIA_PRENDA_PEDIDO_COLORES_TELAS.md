# CORRECCIÓN: Referencia de Telas - Usar prenda_pedido_colores_telas

## 📌 Problema Identificado

La referencia de tela estaba siendo buscada en la tabla `telas` (que contiene datos generales), cuando debería buscarse en la tabla **`prenda_pedido_colores_telas`** que contiene la referencia **específica del pedido**.

### Estructura de Datos

```sql
-- Tabla pivot: Relación entre prenda, color y tela EN UN PEDIDO ESPECÍFICO
prenda_pedido_colores_telas
├── id (PK)
├── prenda_pedido_id (FK)
├── color_id (FK)
├── tela_id (FK)
├── referencia ← ✅ LA REFERENCIA DEBE VENIR DE AQUÍ
├── created_at
└── updated_at

-- Tabla de telas generales: Datos globales (no específicos del pedido)
telas
├── id (PK)
├── nombre
├── referencia (es solo la referencia general, no del pedido)
└── ...
```

### Diferencia

- `telas.referencia` = Referencia general de la tela
- `prenda_pedido_colores_telas.referencia` = Referencia **específica asignada en ESTE PEDIDO**

## ✅ Solución Aplicada

### 1. [prenda-editor-modal.js](public/js/componentes/prenda-editor-modal.js) - Línea 177

**ANTES:**
```javascript
referencia: ct.tela?.referencia || ct.tela_referencia || '',
```

**DESPUÉS:**
```javascript
referencia: ct.referencia || ct.tela?.referencia || ct.tela_referencia || '',
```

**Cambio:** Ahora busca primero `ct.referencia` (viene directo de la tabla pivot).

---

### 2. [prenda-editor.js](public/js/modulos/crear-pedido/procesos/services/prenda-editor.js) - Línea 352

**ANTES:**
```javascript
referencia: ct.tela_referencia || '',
```

**DESPUÉS:**
```javascript
referencia: ct.referencia || ct.tela_referencia || '',
```

**Cambio:** Ahora busca primero `ct.referencia` (de la tabla pivot).

---

## 🧬 Flujo de Datos Correcto

```
Backend → ObtenerPedidoUseCase
  ↓
prenda.colores_telas = [
  {
    id: 101,
    color_id: 29,
    color_nombre: 'dsfdfs',
    tela_id: 3,
    tela_nombre: 'drill',
    referencia: 'ABC-123'  ← ✅ AQUÍ está la referencia del pedido
  }
]
  ↓
prenda-editor.js transforma
  ↓
window.telasAgregadas = [
  {
    nombre_tela: 'drill',
    color: 'dsfdfs',
    referencia: 'ABC-123'  ← ✅ Viene de prenda_pedido_colores_telas
  }
]
  ↓
gestion-telas.js renderiza
  ↓
Tabla muestra:
| TELA  | COLOR  | REFERENCIA | FOTO |
|-------|--------|------------|------|
| drill | dsfdfs | ABC-123    | [IMG]|
```

---

## 📊 Impacto

| Aspecto | Antes | Después |
|--------|-------|---------|
| Referencia mostrada | De tabla `telas` (genérica) | De `prenda_pedido_colores_telas` (específica del pedido) |
| Precisión | ❌ Podría ser incorrecta | ✅ Siempre correcta |
| Fallback | Ninguno (si tela no tiene ref) | Soportado (3 niveles) |
| Compatibilidad | N/A | ✅ Backward compatible |

---

## 🔍 Verificación

### En Console del Browser

Después de abrir un modal de edición, ejecutar:

```javascript
// Ver estructura de datos que llega del backend
console.log('telasAgregadas[0]:', window.telasAgregadas[0]);

// Debe mostrar:
{
  nombre_tela: "drill",
  color: "dsfdfs",
  referencia: "ABC-123"  ← Verificar que aquí esté correcto
}
```

### En Base de Datos

Verificar que la tabla contiene los datos:

```sql
SELECT id, prenda_pedido_id, referencia FROM prenda_pedido_colores_telas LIMIT 5;
```

Debe mostrar referencia específica para cada relación.

---

## 🧪 Casos de Uso

| Caso | Referencia en BD | Esperado en Modal | Resultado |
|------|---|---|---|
| Tela sin ref en pivot | NULL | Fallback a tela.referencia | ✅ Soportado |
| Tela con ref en pivot | "ABC-123" | Mostrar "ABC-123" | ✅ Prioridad 1 |
| Tela con ref en tabla telas | "XYZ-789" | Si no está en pivot → "XYZ-789" | ✅ Fallback |

---

## ✅ Orden de Búsqueda

```javascript
// Prioridad de búsqueda:
const referencia = 
    ct.referencia ||                  // 1️⃣ Primero: pivot table (específico del pedido)
    ct.tela?.referencia ||            // 2️⃣ Segundo: tabla telas (genérico)
    ct.tela_referencia ||             // 3️⃣ Tercero: fallback variante
    '';                               // 4️⃣ Cuarto: vacío si no hay nada
```

---

## 📝 Resumen de Cambios

| Archivo | Línea | Cambio |
|---------|-------|--------|
| prenda-editor-modal.js | 177 | Agregar `ct.referencia` como prioridad 1 |
| prenda-editor.js | 352 | Agregar `ct.referencia` como prioridad 1 |
| gestion-telas.js | 311 | Ya normaliza `telaData.referencia` ✅ |

---

**Fecha:** 27 ENE 2026  
**Estado:** ✅ Implementado  
**Probado:** Con estructura de datos reales de `prenda_pedido_colores_telas`
