{{--
    Capa shared de modales de edicion para pedidos.
    Este archivo concentra el puente temporal hacia vistas de asesores
    para que modulos como supervisor no dependan directamente de ese namespace.
--}}

{{-- Modal Editar Pedido --}}
@include('shared.pedidos.components.modal-editar-pedido')

{{-- Componentes de modulos de edicion --}}
@include('shared.pedidos.components.modal-prendas-lista')
@include('shared.pedidos.components.modal-agregar-prenda')
@include('shared.pedidos.modals.modal-agregar-prenda-nueva')
@include('shared.pedidos.components.modal-editar-prenda')
@include('shared.pedidos.modals.modal-agregar-editar-epp')
@include('shared.pedidos.components.modal-editar-epp')
@include('shared.pedidos.modals.modal-seleccionar-tallas')
@include('shared.pedidos.modals.modal-selector-modo-proceso')
@include('shared.pedidos.modals.modal-proceso-por-tallas')
@include('shared.pedidos.modals.modal-proceso-generico')
@include('shared.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')
