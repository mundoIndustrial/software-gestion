// SCRIPT DE DEBUG - Ejecutar en consola del navegador

// Este script verifica exactamente qué se está enviando al backend
// Cópialo en la consola (F12) cuando estés guardando una cotización

console.log("=".repeat(80));
console.log("🔍 DEBUG - VERIFICANDO QUÉ SE ENVÍA AL BACKEND");
console.log("=".repeat(80));

// Verificar si el input hidden existe
const allHiddenInputs = document.querySelectorAll('.genero-id-hidden');
console.log("✅ Inputs hidden genero-id encontrados:", allHiddenInputs.length);

allHiddenInputs.forEach((input, idx) => {
    const productoCard = input.closest('.producto-card');
    const nombreProducto = productoCard?.querySelector('input[name*="nombre_producto"]')?.value;
    console.log(`  [${idx}] Prenda: ${nombreProducto}, genero_id.value: "${input.value}"`);
});

// Verificar qué trae recopilarDatos()
console.log("\n🔍 Revisando datos que se recopilan:");
const datos = recopilarDatos();
if (datos && datos.productos && datos.productos[0]) {
    const prod = datos.productos[0];
    console.log("Producto 0 variantes:", prod.variantes);
    console.log("  - genero_id:", prod.variantes.genero_id);
    console.log("  - tipo_manga_id:", prod.variantes.tipo_manga_id);
    console.log("  - Todas las keys:", Object.keys(prod.variantes));
} else {
    console.error("❌ No se pudo obtener datos o productos");
}

console.log("\n" + "=".repeat(80));
