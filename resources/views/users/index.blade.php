@extends('layouts.app')

@section('content')
  <h1 class="page-title">Gestión de Usuarios</h1>

  @if(session('status'))
    <div class="card" style="background:#d1fae5;color:#065f46;margin-bottom:15px;">
      {{ session('status') }}
    </div>
  @endif

  <div class="card">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:var(--color-bg-secondary);">
          <th style="padding:10px;text-align:left;">ID</th>
          <th style="padding:10px;text-align:left;">Nombre</th>
          <th style="padding:10px;text-align:left;">Email</th>
          <th style="padding:10px;text-align:left;">Rol</th>
          <th style="padding:10px;text-align:left;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr style="border-top:1px solid var(--color-border-hr);">
            <td style="padding:10px;">{{ $user->id }}</td>
            <td style="padding:10px;">{{ $user->name }}</td>
            <td style="padding:10px;">{{ $user->email }}</td>
            <td style="padding:10px;">{{ $user->role }}</td>
            <td style="padding:10px;">
              @if(auth()->user()->id !== $user->id)
                <form action="{{ route('users.updateRole', $user) }}" method="POST" style="display:inline;">
                  @csrf
                <select name="role" onchange="this.form.submit()" class="btn-role">
                  <option value="admin" {{ $user->role==='admin' ? 'selected' : '' }}>Admin</option>
                  <option value="operador" {{ $user->role==='operador' ? 'selected' : '' }}>Operador</option>
                </select>

                </form>
              @else
                <em>Tu rol</em>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
