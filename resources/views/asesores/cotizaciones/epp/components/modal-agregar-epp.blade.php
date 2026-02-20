@php
    // Ensure globals used by the shared modal exist to avoid ReferenceError on pages where they are not defined.
@endphp

<script>
    window.fotosEPP = window.fotosEPP || [];
    window.eppService = window.eppService || null;
    window.eppEnEdicion = window.eppEnEdicion || null;
</script>

@include('asesores.pedidos.modals.modal-agregar-epp')
