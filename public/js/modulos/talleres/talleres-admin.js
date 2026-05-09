/**
 * Lógica para la gestión de talleres (Administración)
 */
document.addEventListener('DOMContentLoaded', function() {
    initTalleresSearch();
});

function initTalleresSearch() {
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.taller-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name && name.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
}
