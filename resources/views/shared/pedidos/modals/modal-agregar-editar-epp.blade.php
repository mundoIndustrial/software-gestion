@include('shared.pedidos.modals.partials.modal-agregar-editar-epp.html')
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
    @endpush
@endonce
<script src="{{ asset('js/modulos/pedidos/modal-agregar-editar-epp/core.js') }}"></script>
<script src="{{ asset('js/modulos/pedidos/modal-agregar-editar-epp/edicion.js') }}"></script>
<script src="{{ asset('js/modulos/pedidos/modal-agregar-editar-epp/prenda.js') }}"></script>
<script src="{{ asset('js/modulos/pedidos/modal-agregar-editar-epp/backdrop.js') }}"></script>
<script src="{{ asset('js/modulos/pedidos/modal-agregar-editar-epp/exports.js') }}"></script>
