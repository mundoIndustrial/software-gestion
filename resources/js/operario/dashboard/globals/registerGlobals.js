import { abrirDetallesRecibos } from '../navigation/detallesRecibos';
import {
    abrirModalNovedad,
    abrirModalNovedadDesdeElemento,
    cerrarModalNovedad,
    guardarNovedad,
    editarNovedad,
    eliminarNovedad,
    cancelarConfirmacion,
    confirmarEliminar,
} from '../novedades/novedades';
import {
    manejarPasarACostura,
} from '../costura/costura';
import {
    cerrarModalCostura,
    confirmarAsignacion,
    seleccionarOpcionAsignacion,
    volverAOpciones,
} from '../costura/modal-asignacion';
import { pasarAControlCalidad, verTallasControlCalidad } from '../controlCalidad/controlCalidad';
import { toggleMobileActions } from '../mobile/mobileActions';
import { completarCorte, deshacerCorte, completarCostura, deshacerCostura } from '../recibos/corteCostura';
import { completarReciboCorteSobremedida, deshacerReciboCorteSobremedida } from '../recibos/corteSobremedida';
import { mostrarExito, mostrarError, mostrarMensaje, cerrarModalMensaje } from '../ui/messages';
import { actualizarContadorTarjetas, incrementarContadorCompletados } from '../ui/counters';
import { asegurarBadgeCompletado } from '../ui/badges';
import { abrirEditarEncargados } from '../distribucion/distribucion';

// Importar el modal-asignacion.js para que se cargue
import '../costura/modal-asignacion';

export function registerDashboardGlobals() {
    // Navegación
    window.abrirDetallesRecibos = abrirDetallesRecibos;

    // Novedades
    window.abrirModalNovedad = abrirModalNovedad;
    window.abrirModalNovedadDesdeElemento = abrirModalNovedadDesdeElemento;
    window.cerrarModalNovedad = cerrarModalNovedad;
    window.guardarNovedad = guardarNovedad;
    window.editarNovedad = editarNovedad;
    window.eliminarNovedad = eliminarNovedad;
    window.cancelarConfirmacion = cancelarConfirmacion;
    window.confirmarEliminar = confirmarEliminar;

    // Costura / asignación
    window.manejarPasarACostura = manejarPasarACostura;
    window.cerrarModalCostura = cerrarModalCostura;
    window.confirmarAsignacion = confirmarAsignacion;
    window.seleccionarOpcionAsignacion = seleccionarOpcionAsignacion;
    window.volverAOpciones = volverAOpciones;
    window.abrirEditarEncargados = abrirEditarEncargados;

    // Control de Calidad
    window.pasarAControlCalidad = pasarAControlCalidad;
    window.verTallasControlCalidad = verTallasControlCalidad;

    // Mobile
    window.toggleMobileActions = toggleMobileActions;

    // Completar / Deshacer
    window.completarCorte = completarCorte;
    window.deshacerCorte = deshacerCorte;
    window.completarCostura = completarCostura;
    window.deshacerCostura = deshacerCostura;
    window.completarReciboCorteSobremedida = completarReciboCorteSobremedida;
    window.deshacerReciboCorteSobremedida = deshacerReciboCorteSobremedida;

    // Utilidades UI
    window.mostrarExito = mostrarExito;
    window.mostrarError = mostrarError;
    window.mostrarMensaje = mostrarMensaje;
    window.cerrarModalMensaje = cerrarModalMensaje;
    window.actualizarContadorTarjetas = actualizarContadorTarjetas;
    window.incrementarContadorCompletados = incrementarContadorCompletados;

    window.asegurarBadgeCompletado = asegurarBadgeCompletado;
}
