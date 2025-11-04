# 📚 Guía Paso a Paso: Importación de Balanceos desde Excel

## 🎯 Objetivo
Importar todos los balanceos desde un archivo Excel a la base de datos de forma limpia y sin duplicados.

---

## 📋 Requisitos Previos

### 1. Archivo Excel Preparado
- ✅ Archivo ubicado en: `resources/clasico (1).xlsx`
- ✅ Cada hoja representa una prenda diferente
- ✅ Encabezados: LETRA, OPERACIÓN, SAM, MAQUINA, OPERARIO, OP, SECCIÓN

### 2. Dependencias Instaladas
```bash
composer require phpoffice/phpspreadsheet --ignore-platform-req=ext-gd
```

---

## 🚀 Proceso Completo de Importación

### **PASO 1: Limpiar la Base de Datos (Opcional)**

Si quieres empezar desde cero y eliminar todos los balanceos existentes:

```bash
php artisan balanceo:limpiar-todo
```

**Salida esperada:**
```
⚠️  ADVERTENCIA: Esta acción eliminará TODOS los datos

📊 Registros a eliminar:
   • Prendas: 153
   • Balanceos: 153
   • Operaciones: 3,245

¿Estás seguro de que quieres eliminar TODO? (yes/no) [no]:
```

**Escribe:** `yes` y presiona Enter

**Resultado:**
```
🗑️  Eliminando todos los registros...

1️⃣ Eliminando operaciones...
   ✅ 3,245 operaciones eliminadas
2️⃣ Eliminando balanceos...
   ✅ 153 balanceos eliminados
3️⃣ Eliminando prendas...
   ✅ 153 prendas eliminadas

✅ Todos los datos han sido eliminados exitosamente

💡 Ahora puedes importar desde cero con:
   php artisan balanceo:importar-excel archivo.xlsx
```

---

### **PASO 2: Probar la Importación (DRY-RUN)**

Antes de importar realmente, prueba que todo funcione correctamente:

```bash
php artisan balanceo:importar-excel "resources/clasico (1).xlsx" --dry-run
```

**¿Qué hace?**
- ✅ Lee el archivo Excel
- ✅ Detecta todas las hojas
- ✅ Valida los datos
- ✅ Muestra qué se importaría
- ❌ **NO guarda nada en la base de datos**

**Salida esperada (fragmento):**
```
📂 Leyendo archivo: resources/clasico (1).xlsx
📊 Hojas encontradas: 153

============================================================
📄 Procesando hoja: JEAN CABALLERO
👕 Prenda: JEAN CABALLERO
📝 Referencia: REF-JEAN-CABALLERO-690a21e933350
👥 Operarios: 10 | Turnos: 1 | Horas: 8.0

📋 Columnas detectadas:
   LETRA: Col 1
   OPERACIÓN: Col 2
   SAM: Col 5
   PRECEDENCIA: Col 3
   MAQUINA: Col 4
   OPERARIO: Col 6
   OP: Col 7
   SECCIÓN: Col 8

✅ Operaciones encontradas: 28
⏱️  SAM Total: 678.5

📝 Muestra de operaciones:
   A: Filetear aletilla - SAM: 4.3
   B: Filetear aletillon - SAM: 8.9
   C: Montar cierre a aletilla - SAM: 6.5
   ... y 25 más

⚠️  DRY-RUN: No se guardó en la base de datos
```

**Si todo se ve bien, continúa al siguiente paso.**

---

### **PASO 3: Importar Realmente**

Ahora sí, importa todos los balanceos a la base de datos:

```bash
php artisan balanceo:importar-excel "resources/clasico (1).xlsx"
```

**¿Qué hace?**
- ✅ Lee todas las hojas del Excel
- ✅ Crea una prenda por cada hoja
- ✅ Crea un balanceo para cada prenda
- ✅ Inserta todas las operaciones
- ✅ Calcula métricas automáticamente
- ✅ Previene duplicados

**Salida esperada (fragmento):**
```
📂 Leyendo archivo: resources/clasico (1).xlsx
📊 Hojas encontradas: 153

============================================================
📄 Procesando hoja: JEAN CABALLERO
👕 Prenda: JEAN CABALLERO
📝 Referencia: REF-JEAN-CABALLERO-690a21e933350
👥 Operarios: 10 | Turnos: 1 | Horas: 8.0

📋 Columnas detectadas:
   LETRA: Col 1
   OPERACIÓN: Col 2
   SAM: Col 5
   ...

✅ Operaciones encontradas: 28
⏱️  SAM Total: 678.5

💾 Prenda creada: ID 1
💾 Balanceo creado: ID 1
💾 Operaciones creadas: 28

📊 Métricas calculadas:
   SAM Total: 678.5
   Meta Teórica: 424
   Meta Real (90%): 381.60
   Meta Sugerida (85%): 360

✅ Balanceo importado exitosamente

============================================================
📄 Procesando hoja: JEAN DAMA
...
```

**Tiempo estimado:** 2-5 minutos para 153 hojas

**Al finalizar:**
```
✅ Importación completada exitosamente
```

---

### **PASO 4: Verificar la Importación**

Verifica que todo se importó correctamente:

```bash
php artisan tinker
```

Dentro de tinker, ejecuta:

```php
// Contar prendas
\App\Models\Prenda::count()
// Resultado esperado: 153

// Contar balanceos
\App\Models\Balanceo::count()
// Resultado esperado: 153

// Contar operaciones
\App\Models\OperacionBalanceo::count()
// Resultado esperado: ~3,000-4,000

// Ver algunos balanceos
\App\Models\Balanceo::with('prenda')->latest()->take(5)->get(['id', 'prenda_id', 'sam_total', 'meta_teorica', 'meta_real'])

// Salir de tinker
exit
```

**Salida esperada:**
```php
=> 153  // Prendas
=> 153  // Balanceos
=> 3245 // Operaciones

=> Illuminate\Database\Eloquent\Collection {#...
     all: [
       App\Models\Balanceo {#...
         id: 153,
         prenda_id: 153,
         sam_total: 678.5,
         meta_teorica: 424,
         meta_real: 381.6,
         prenda: App\Models\Prenda {#...
           id: 153,
           nombre: "JEAN CABALLERO",
         },
       },
       ...
     ],
   }
```

---

### **PASO 5: Ver en la Interfaz Web**

Abre tu navegador y ve a:

```
http://localhost:8000/balanceo
```

Deberías ver:
- ✅ Lista de todas las prendas importadas
- ✅ Cada prenda con su balanceo activo
- ✅ Métricas calculadas (SAM Total, Meta Teórica, Meta Real)
- ✅ Botón para ver detalles de cada balanceo

---

## 🔄 Si Algo Sale Mal

### Problema 1: Error durante la importación

**Solución:**
1. Revisa el mensaje de error
2. Limpia la base de datos:
   ```bash
   php artisan balanceo:limpiar-todo --force
   ```
3. Vuelve a intentar la importación

### Problema 2: Duplicados

**Solución:**
```bash
# Ver duplicados
php artisan balanceo:limpiar-duplicados --dry-run

# Eliminar duplicados
php artisan balanceo:limpiar-duplicados
```

### Problema 3: Métricas incorrectas

**Solución:**
```bash
# Recalcular todas las métricas
php artisan balanceo:recalcular

# O recalcular un balanceo específico
php artisan balanceo:recalcular 5
```

---

## 📊 Comandos Útiles

### Ver estado actual
```bash
# Entrar a tinker
php artisan tinker

# Contar registros
\App\Models\Prenda::count()
\App\Models\Balanceo::count()
\App\Models\OperacionBalanceo::count()

# Ver últimas prendas creadas
\App\Models\Prenda::latest()->take(10)->pluck('nombre')

# Salir
exit
```

### Limpiar y empezar de nuevo
```bash
# Eliminar todo (con confirmación)
php artisan balanceo:limpiar-todo

# Eliminar todo (sin confirmación)
php artisan balanceo:limpiar-todo --force

# Importar de nuevo
php artisan balanceo:importar-excel "resources/clasico (1).xlsx"
```

### Limpiar solo duplicados
```bash
# Ver duplicados
php artisan balanceo:limpiar-duplicados --dry-run

# Eliminar duplicados
php artisan balanceo:limpiar-duplicados
```

### Recalcular métricas
```bash
# Recalcular todos
php artisan balanceo:recalcular

# Recalcular uno específico
php artisan balanceo:recalcular 5
```

---

## ✅ Checklist de Importación

Marca cada paso a medida que lo completas:

- [ ] **1. Preparación**
  - [ ] Archivo Excel en `resources/clasico (1).xlsx`
  - [ ] Dependencias instaladas (`phpoffice/phpspreadsheet`)

- [ ] **2. Limpieza (Opcional)**
  - [ ] Ejecutar `php artisan balanceo:limpiar-todo`
  - [ ] Confirmar eliminación

- [ ] **3. Prueba**
  - [ ] Ejecutar con `--dry-run`
  - [ ] Verificar que detecta todas las hojas
  - [ ] Verificar que lee las operaciones correctamente

- [ ] **4. Importación**
  - [ ] Ejecutar sin `--dry-run`
  - [ ] Esperar a que termine (2-5 minutos)
  - [ ] Verificar mensaje "✅ Importación completada exitosamente"

- [ ] **5. Verificación**
  - [ ] Contar registros en tinker
  - [ ] Ver balanceos en la web (`/balanceo`)
  - [ ] Verificar métricas calculadas

- [ ] **6. Limpieza Final (si hay duplicados)**
  - [ ] Ejecutar `php artisan balanceo:limpiar-duplicados`

---

## 🎯 Resultado Final Esperado

Después de completar todos los pasos, deberías tener:

✅ **153 prendas únicas** en la base de datos
✅ **153 balanceos** con métricas calculadas
✅ **~3,000-4,000 operaciones** distribuidas en los balanceos
✅ **Sin duplicados**
✅ **Métricas correctas** (SAM Total, Meta Teórica, Meta Real, etc.)
✅ **Interfaz web funcionando** con todos los balanceos visibles

---

## 💡 Consejos Importantes

1. **Siempre usa `--dry-run` primero** para verificar que todo esté bien
2. **No interrumpas el proceso** de importación una vez iniciado
3. **Si algo falla**, limpia todo y vuelve a empezar
4. **Verifica los resultados** en tinker y en la web
5. **Haz backup** de tu base de datos antes de limpiar todo

---

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los mensajes de error
2. Verifica que el archivo Excel esté en la ubicación correcta
3. Asegúrate de que las dependencias estén instaladas
4. Limpia todo y vuelve a intentar
5. Revisa los logs de Laravel en `storage/logs/laravel.log`

---

## 🎉 ¡Listo!

Ahora tienes un sistema completo para importar balanceos desde Excel de forma masiva, limpia y sin duplicados.

**Siguiente paso:** Abre `http://localhost:8000/balanceo` y disfruta de tus balanceos importados! 🚀
