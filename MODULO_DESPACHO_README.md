# 📦 MÓDULO DE DESPACHO - Quick Start

## ¿Qué es?

Sistema web completo para **controlar entregas parciales** de prendas y EPP desde pedidos de producción.

## Características

 Visualiza pedidos listos para despacho  
 Tabla interactiva con cálculo automático de pendientes  
 Despacho en 3 fases parciales  
 Separación clara entre prendas y EPP  
 Impresión profesional con firmas  
 Validaciones en tiempo real  

## 📍 Ubicaciones clave

| Archivo | Ubicación |
|---------|-----------|
| **Controlador** | `app/Http/Controllers/DespachoController.php` |
| **Rutas** | `routes/despacho.php` |
| **Vistas** | `resources/views/despacho/` |
| **Documentación** | `MODULO_DESPACHO_DOCUMENTACION.md` |
| **Modelos** | `app/Models/PedidoProduccion.php` (métodos helpers) |

##  Cómo acceder

```
URL: http://tuapp.local/despacho

1. Abre la lista de pedidos
2. Selecciona un pedido
3. Completa la tabla de despacho
4. Los parciales se calculan automáticamente
5. Guarda y/o imprime
```

## 🔧 Métodos helpers en PedidoProduccion

```php
$pedido = PedidoProduccion::find(1);

// Obtener todas las filas (prendas + EPP unificadas)
$filas = $pedido->getFilasDespacho();
// Retorna Collection con estructura unificada

// Obtener solo prendas
$prendas = $pedido->getPrendasParaDespacho();

// Obtener solo EPP
$epps = $pedido->getEppParaDespacho();
```

## 📊 Estructura de una fila de despacho

```php
[
    'tipo' => 'prenda|epp',
    'id' => 1,                  // ID del ítem
    'talla_id' => 1,            // Null para EPP
    'descripcion' => 'Polo XL',
    'cantidad_total' => 50,
    'talla' => 'XL|—',          // — para EPP
    'genero' => 'Hombre|null',
    'objeto_prenda' => $prenda,
    'objeto_talla' => $talla,   // Null para EPP
    'objeto_epp' => $epp,       // Null para prenda
]
```

## 🔗 Rutas disponibles

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/despacho` | Listar pedidos |
| GET | `/despacho/{id}` | Ver despacho |
| POST | `/despacho/{id}/guardar` | Guardar parciales |
| GET | `/despacho/{id}/print` | Imprimir |

## 💾 Guardar despacho (POST)

```json
{
  "fecha_hora": "2026-01-23T14:30",
  "cliente_empresa": "Empresa XYZ",
  "despachos": [
    {
      "tipo": "prenda",
      "id": 1,
      "parcial_1": 10,
      "parcial_2": 5,
      "parcial_3": 0
    },
    {
      "tipo": "epp",
      "id": 2,
      "parcial_1": 5,
      "parcial_2": 3,
      "parcial_3": 0
    }
  ]
}
```

##  Validaciones automáticas

- ❌ No permite números negativos
- ❌ No permite exceder cantidad total
-  Calcula pendientes en tiempo real
-  Previene despacho parcial inválido

##  Tabla de despacho

**Columnas:**
- Descripción
- Talla (— para EPP)
- P (Pendiente inicial)
- Parcial 1
- P (Pendiente 1)
- Parcial 2
- P (Pendiente 2)
- Parcial 3
- P (Pendiente 3)

**Cálculo:**
```
P1 = Cantidad Total - Parcial 1
P2 = P1 - Parcial 2
P3 = P2 - Parcial 3
```

## 🎨 Separación visual

| Tipo | Color | Ícono |
|------|-------|-------|
| Prendas | Azul | 👕 |
| EPP | Verde | 🛡️ |

## 🖨️ Impresión

- Click en botón "🖨️ Imprimir"
- Documento profesional con:
  - Info del pedido
  - Tabla separada: prendas vs EPP
  - Área de firmas
  - Notas importantes

## ⚙️ Tecnología

- **Backend:** Laravel 11 + Eloquent ORM
- **Frontend:** Blade + TailwindCSS + JavaScript vanilla
- **Base de datos:** Usa tablas existentes (sin crear nuevas)

## 📝 Ejemplo de uso en controlador

```php
class MiControlador extends Controller {
    public function generarReporte() {
        $pedido = PedidoProduccion::find(123);
        $filas = $pedido->getFilasDespacho();
        
        foreach ($filas as $fila) {
            if ($fila['tipo'] === 'prenda') {
                echo "Prenda: {$fila['descripcion']} - Talla: {$fila['talla']}";
            } else {
                echo "EPP: {$fila['descripcion']}";
            }
            echo " | Cantidad: {$fila['cantidad_total']}\n";
        }
    }
}
```

## 🔗 Integración con modelos existentes

**PedidoProduccion:**
- `$pedido->prendas()` → PrendaPedido
- `$pedido->epps()` → PedidoEpp

**PrendaPedido:**
- `$prenda->prendaPedidoTallas()` → PrendaPedidoTalla (alias)
- `$prenda->tallas()` → PrendaPedidoTalla (original)

**PedidoEpp:**
- `$epp->epp()` → Epp (catálogo)
- `$epp->imagenes()` → PedidoEppImagen

## 📚 Documentación completa

Ver: `MODULO_DESPACHO_DOCUMENTACION.md`

## 🐛 Logs y debugging

Errores se guardan en:
```
storage/logs/laravel.log
```

Búscar: "Despacho prenda" o "Despacho EPP"

## 🎓 Aprendizajes clave

1. **Relaciones Eloquent normalizadas:** Cada prenda con talla = una fila
2. **Estructura unificada:** Prendas y EPP en un mismo array
3. **Cálculos en cliente:** Para UX responsiva
4. **Validación dual:** Cliente + servidor
5. **Print-friendly:** CSS @media print para impresión

---

**Última actualización:** 23 de enero de 2026  
**Estado:**  Producción lista
