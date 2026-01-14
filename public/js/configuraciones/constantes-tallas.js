/**
 * Constantes de Tallas para Prendas
 * Archivo centralizado con todas las definiciones de tallas por género y tipo
 */

// Tallas de letra: XS hasta XXXL
const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

// Tallas numéricas para DAMA: 2 hasta 28 (números pares)
const TALLAS_NUMEROS_DAMA = [
    '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28'
];

// Tallas numéricas para CABALLERO: 30 hasta 56 (números pares)
const TALLAS_NUMEROS_CABALLERO = [
    '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56'
];

// Objeto centralizado para fácil acceso
const CONSTANTES_TALLAS = {
    LETRAS: TALLAS_LETRAS,
    NUMEROS_DAMA: TALLAS_NUMEROS_DAMA,
    NUMEROS_CABALLERO: TALLAS_NUMEROS_CABALLERO
};
