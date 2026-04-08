export function asegurarBadgeCompletado(card, estaCompletado) {
    if (!card) return;

    const badgesExistentes = card.querySelectorAll('.badge-completado-costura');

    if (estaCompletado) {
        if (badgesExistentes.length > 0) {
            badgesExistentes.forEach((badge) => {
                badge.classList.add('is-on');
                badge.textContent = 'COMPLETADO';
            });
            return;
        }

        const contenedorNumero = card.querySelector('.orden-numero-section');
        const estadoBadge = contenedorNumero ? contenedorNumero.querySelector('.estado-badge') : null;
        if (!contenedorNumero || !estadoBadge) {
            return;
        }

        const badgeNuevo = document.createElement('span');
        badgeNuevo.className = 'badge-completado-costura is-on';
        badgeNuevo.textContent = 'COMPLETADO';

        estadoBadge.insertAdjacentElement('afterend', badgeNuevo);
    } else {
        badgesExistentes.forEach((badge) => {
            badge.remove();
        });
    }
}
