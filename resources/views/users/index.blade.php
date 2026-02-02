@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/users-styles.css') }}">

    <div class="table-container">
        <div class="table-header">
            <h1 class="table-title">
                <i class="fas fa-users"></i>
                Gestión de Usuarios
            </h1>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="buscarUsuario" placeholder="Buscar por nombre o email..." class="search-input">
                </div>
            </div>

            <div class="table-actions">
                <button class="btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <table id="tablaUsuarios" class="modern-table">
                    <thead class="table-head">
                        <tr>
                            <th class="table-header-cell">Nombre</th>
                            <th class="table-header-cell">Email</th>
                            <th class="table-header-cell">Rol</th>
                            <th class="table-header-cell">Fecha Registro</th>
                            <th class="table-header-cell acciones-column">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuariosBody" class="table-body">
                        @forelse($users as $user)
                            <tr class="table-row" data-user-id="{{ $user->id }}">
                                <td class="table-cell">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <span>{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">{{ $user->email }}</td>
                                <td class="table-cell">
                                    @if($user->roles_ids && count($user->roles_ids) > 0)
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            @foreach($user->roles()->get() as $role)
                                                <span class="badge badge-{{ strtolower($role->name) }}">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="badge badge-default">Sin rol</span>
                                    @endif
                                </td>
                                <td class="table-cell">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="table-cell acciones-cell">
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit"
                                                onclick="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}')"
                                                title="Editar usuario">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-password" 
                                                onclick="openPasswordModal({{ $user->id }}, '{{ $user->name }}')"
                                                title="Cambiar contraseña">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        @if(auth()->user()->id !== $user->id)
                                            <button class="btn-action btn-delete" 
                                                    onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')"
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td colspan="5" class="no-results">
                                    <i class="fas fa-users"></i>
                                    <p>No hay usuarios registrados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h2>
                <button class="modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_name">Nombre</label>
                        <input type="text" id="create_name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="create_email">Email</label>
                        <input type="email" id="create_email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="create_password">Contraseña</label>
                        <input type="password" id="create_password" name="password" class="form-input" required minlength="8">
                        <small>Mínimo 8 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label>Roles</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            @foreach($roles as $role)
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="roles_ids[]" value="{{ $role->id }}" class="form-checkbox">
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <small style="color: #666;">Selecciona al menos un rol</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeCreateModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Editar Usuario</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Nombre</label>
                        <input type="text" id="edit_name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Roles</label>
                        <div id="edit_roles_container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            @foreach($roles as $role)
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="roles_ids[]" value="{{ $role->id }}" class="form-checkbox edit-role-checkbox">
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <small style="color: #666;">Selecciona al menos un rol</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Cambiar Contraseña</h2>
                <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            </div>
            <form id="passwordForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p>Usuario: <strong id="password_user_name"></strong></p>
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña</label>
                        <input type="password" id="new_password" name="password" class="form-input" required minlength="8">
                        <small>Mínimo 8 caracteres</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closePasswordModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar al usuario <strong id="delete_user_name"></strong>?</p>
                    <p class="warning-text">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                    <button type="submit" class="btn-danger">Eliminar Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/users.js') }}"></script>
@endsection
