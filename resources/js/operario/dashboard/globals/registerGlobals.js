import { abrirDetallesRecibos } from '../navigation/detallesRecibos';
import {
    abrirModalNovedad,
    cerrarModalNovedad,
    guardarNovedad,
    editarNovedad,
    eliminarNovedad,
} from '../novedades/novedades';
import {
    manejarPasarACostura,
    cerrarModalCostura,
    confirmarPasarACostura,
    cargarUsuariosCostura,
} from '../costura/costura';
import { pasarAControlCalidad } from '../controlCalidad/controlCalidad';
import { toggleMobileActions } from '../mobile/mobileActions';
import { completarCorte, deshacerCorte, completarCostura, deshacerCostura } from '../recibos/corteCostura';
import { mostrarExito, mostrarError, mostrarMensaje, cerrarModalMensaje } from '../ui/messages';
import { actualizarContadorTarjetas } from '../ui/counters';
import { asegurarBadgeCompletado } from '../ui/badges';

export function registerDashboardGlobals() {
    // Navegación
    window.abrirDetallesRecibos = abrirDetallesRecibos;

    // Novedades
    window.abrirModalNovedad = abrirModalNovedad;
    window.cerrarModalNovedad = cerrarModalNovedad;
    window.guardarNovedad = guardarNovedad;
    window.editarNovedad = editarNovedad;
    window.eliminarNovedad = eliminarNovedad;

    // Costura / asignación
    window.manejarPasarACostura = manejarPasarACostura;
    window.cerrarModalCostura = cerrarModalCostura;
    window.confirmarPasarACostura = confirmarPasarACostura;
    window.cargarUsuariosCostura = cargarUsuariosCostura;

    // Control de Calidad
    window.pasarAControlCalidad = pasarAControlCalidad;

    // Mobile
    window.toggleMobileActions = toggleMobileActions;

    // Completar / Deshacer
    window.completarCorte = completarCorte;
    window.deshacerCorte = deshacerCorte;
    window.completarCostura = completarCostura;
    window.deshacerCostura = deshacerCostura;

    // Utilidades UI
    window.mostrarExito = mostrarExito;
    window.mostrarError = mostrarError;
    window.mostrarMensaje = mostrarMensaje;
    window.cerrarModalMensaje = cerrarModalMensaje;
    window.actualizarContadorTarjetas = actualizarContadorTarjetas;
    window.asegurarBadgeCompletado = asegurarBadgeCompletado;
}
