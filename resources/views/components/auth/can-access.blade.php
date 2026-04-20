{{--
  Componente para validar acceso basado en roles

  Uso:
  <x-auth.can-access roles="bodeguero,admin">
    <a href="{{ route('bodega.index') }}">Gestión de Bodega</a>
  </x-auth.can-access>
--}}

@php
  $userRoles = auth()->user()?->roles->pluck('name')->toArray() ?? [];
  $requiredRoles = array_filter(array_map('trim', explode(',', $roles ?? '')));
  $hasAccess = !empty(array_intersect($userRoles, $requiredRoles));
@endphp

@if($hasAccess)
  {{ $slot }}
@endif
