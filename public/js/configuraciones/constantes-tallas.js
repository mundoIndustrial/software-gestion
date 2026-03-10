/**
 * Constantes de Tallas para Prendas
 * Archivo centralizado con todas las definiciones de tallas por género y tipo
 */

// Tallas de letra: XS hasta XXXXL
const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];

// Tallas numéricas para DAMA
const TALLAS_NUMEROS_DAMA = [
    '6', '8', '10', '12', '14', '16', '18', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'
];

// Tallas numéricas para CABALLERO
const TALLAS_NUMEROS_CABALLERO = [
    '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'
];

// Objeto centralizado para fácil acceso
const CONSTANTES_TALLAS = {
    LETRAS: TALLAS_LETRAS,
    NUMEROS_DAMA: TALLAS_NUMEROS_DAMA,
    NUMEROS_CABALLERO: TALLAS_NUMEROS_CABALLERO
};

// Asignar a window para disponibilidad global (especialmente en carga dinámica)
window.constantes_tallas = CONSTANTES_TALLAS;
