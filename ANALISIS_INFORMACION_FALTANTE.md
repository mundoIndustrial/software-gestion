# 📊 ANÁLISIS COMPLETO DE INFORMACIÓN EN COTIZACIONES vs PEDIDOS

## Información que muestra la vista de cotizaciones (144/ver)

### 1️⃣ INFORMACIÓN GENERAL DE LA COTIZACIÓN
- **Cliente** (nombre empresa)
- **Estado** (Borrador, Aceptada, Rechazada, Enviada a Contador)
- **Fecha de Envío**
- **Número de Cotización**
- **Asesora/Asesor**

### 2️⃣ PRENDAS (Tab Prendas)
Para cada prenda se muestra:

#### Datos básicos:
- ✅ Nombre del producto
- ✅ Descripción
- ✅ Género
- ✅ Tallas disponibles

#### Variantes (por cada prenda):
- ✅ **Color**
- ✅ **Tela**
- ✅ **Referencia de tela**
- ✅ **Tipo Manga** (si aplica) + observaciones
- ✅ **Bolsillos** (Si/No) + observaciones
- ✅ **Tipo Broche** (si aplica) + observaciones
- ✅ **Reflectivo** (Si/No) + observaciones
- ✅ **Telas múltiples** (array de tela + color + referencia)

#### Imágenes/Fotos:
- ✅ **Fotos de la Prenda** (múltiples, mostrando cantidad)
- ✅ **Fotos de Telas** (múltiples, mostrando cantidad)

### 3️⃣ LOGO/BORDADO (Tab Bordado)
- ✅ **Tipo Venta** del logo
- ✅ **Descripción** del logo/bordado
- ✅ **Fotos del Logo** (múltiples imágenes)
- ✅ **Técnicas disponibles** (Bordado, Impresión, etc.)
- ✅ **Observaciones técnicas**
- ✅ **Ubicaciones** en donde va el logo
- ✅ **Observaciones generales del logo**

### 4️⃣ ESPECIFICACIONES GENERALES
- 📦 **Disponibilidad** (Bodega, En tránsito, etc.) + observaciones
- 💳 **Forma de Pago** (Contado, Crédito, etc.) + observaciones
- 🏛️ **Régimen** (Común, Simplificado, etc.) + observaciones
- 📊 **Se ha vendido** (Si/No) + observaciones
- 💰 **Última Venta** (texto) + observaciones
- 🚚 **Flete de Envío** (valor) + observaciones

### 5️⃣ REFLECTIVO (Tab Reflectivo si aplica)
- ✅ Datos completos del reflectivo de la cotización

---

## 🔴 INFORMACIÓN QUE FALTA EN EL FORMULARIO DE PEDIDOS EDITABLE

El formulario actual SOLO muestra:
- ❌ Nombre de prenda
- ❌ Descripción de prenda
- ❌ Color
- ❌ Género
- ❌ Tallas con cantidades

### 🚨 INFORMACIÓN CRÍTICA QUE FALTA:

1. **LOGO/BORDADO** ❌
   - Fotos del logo
   - Técnicas (bordado, impresión, etc.)
   - Ubicaciones del logo en la prenda
   - Observaciones del logo
   - Descripción del bordado

2. **VARIANTES COMPLETAS** ❌
   - Tipo de Manga (Corta, Larga, etc.)
   - Observaciones de Manga
   - Tipo de Broche (Botones, Cremallera, etc.)
   - Observaciones de Broche
   - Bolsillos (Si/No) + observaciones
   - Reflectivo (Si/No) + observaciones
   - Telas múltiples (array completo)

3. **FOTOS DE TELAS** ❌
   - Las fotos de variaciones de tela no se muestran
   - Son críticas para ver las opciones de color/tela

4. **ESPECIFICACIONES DE LA COTIZACIÓN** ❌
   - Disponibilidad
   - Forma de pago (diferente a la del pedido)
   - Régimen
   - Se ha vendido
   - Última venta
   - Flete

5. **REFLECTIVO INFORMATION** ❌
   - Si la cotización tiene reflectivo, no se muestra

6. **OBSERVACIONES GENERALES** ❌
   - Observaciones de la cotización
   - Observaciones de ubicaciones del logo
   - Observaciones técnicas del logo

---

## 📋 COMPARATIVA: QUÉ HACE FALTA CARRGAR EN AJAX

### Actualmente se carga ✅:
```javascript
{
  id: 143,
  numero: "COT-00014",
  cliente: "MINCIVIL",
  asesora: "yus2",
  especificaciones: { ... },
  observaciones_generales: [],
  ubicaciones: [],
  prendas: [
    {
      id: 102,
      nombre_producto: "camisa drill",
      descripcion: "prueba de camisa drill",
      cantidad: 1,
      tallas: ["XS", "S", "M", ...],
      fotos: ["/storage/..."],
      variantes: { color, tipo_manga, ... },
      telas: [],                  // ← VACÍO
      telaFotos: [ {...} ]        // ← VACÍO (pero se trae las URLs)
    }
  ],
  logo: {
    id: 75,
    descripcion: "prueba de bordado",
    imagenes: [],
    fotos: [ {...} ]
  },
  reflectivo: null
}
```

### Debería incluir además 🔴:
```javascript
prendas: [
  {
    ...datos actuales,
    variantes: {
      ...datos actuales,
      tipo_manga: "Corta",          // ← FALTA
      obs_manga: "...",             // ← FALTA
      tipo_broche: "Botones",       // ← FALTA
      obs_broche: "...",            // ← FALTA
      tiene_bolsillos: true,        // ← FALTA (está en response pero no se usa)
      obs_bolsillos: "...",         // ← FALTA
      tiene_reflectivo: true,       // ← FALTA (está pero no se muestra)
      obs_reflectivo: "...",        // ← FALTA
      telas_multiples: [            // ← PRESENTE pero incompleto
        { tela: "drill", color: "Naranja", referencia: "..." }
      ]
    },
    manga_nombre: "Corta",          // ← FALTA (nombre legible)
    broche_nombre: "Botones",       // ← FALTA (nombre legible)
  }
],
logo: {
  ...datos actuales,
  tipo_venta: "M",                  // ← FALTA
  tecnicas: ["Bordado", "Impresión"], // ← FALTA
  observaciones_tecnicas: "...",    // ← FALTA
  ubicaciones: [ {...} ],           // ← FALTA (dónde va el logo)
  observaciones_generales: "...",   // ← FALTA
}
reflectivo: {
  // ← FALTA INFORMACIÓN COMPLETA DEL REFLECTIVO
}
```

---

## 🎯 RECOMENDACIONES

### NIVEL 1: Mínimo necesario para un pedido editable
1. ✅ Información de logo (fotos + descripción)
2. ✅ Información de técnicas de logo
3. ✅ Información de variantes completa (manga, broche, bolsillos, reflectivo)
4. ✅ Observaciones de prendas y variantes
5. ✅ Fotos de telas/colores

### NIVEL 2: Información contextual importante
6. ✅ Especificaciones de la cotización
7. ✅ Observaciones generales
8. ✅ Ubicaciones del logo

### NIVEL 3: Información de reflectivo (si aplica)
9. ✅ Datos completos de reflectivo

