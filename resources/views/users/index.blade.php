@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/users-styles.css') }}">

    <div class="table-container">
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
                            <th class="table-header-cell" data-column="nombre">
                                Nombre
                                <i class="fas fa-filter filter-icon"></i>
                                <div class="filter-dropdown" id="filter-nombre"></div>
                            </th>
                            <th class="table-header-cell" data-column="email">
                                Email
                                <i class="fas fa-filter filter-icon"></i>
                                <div class="filter-dropdown" id="filter-email"></div>
                            </th>
                            <th class="table-header-cell" data-column="telefono">
                                Teléfono
                                <i class="fas fa-filter filter-icon"></i>
                                <div class="filter-dropdown" id="filter-telefono"></div>
                            </th>
                            <th class="table-header-cell" data-column="rol">
                                Rol
                                <i class="fas fa-filter filter-icon"></i>
                                <div class="filter-dropdown" id="filter-rol"></div>
                            </th>
                            <th class="table-header-cell" data-column="fecha">
                                Fecha Registro
                                <i class="fas fa-filter filter-icon"></i>
                                <div class="filter-dropdown" id="filter-fecha"></div>
                            </th>
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
                                <td class="table-cell">
                                    {{ $user->email }}
                                </td>
                                <td class="table-cell">
                                    {{ $user->telefono ?? '—' }}
                                </td>
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
                                <td class="table-cell">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                                <td class="table-cell">
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
                                <td colspan="6" class="no-results">
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
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create_avatar">Avatar</label>
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <div style="flex-shrink: 0;">
                                <div id="create_avatar_preview" style="width: 100px; height: 100px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <i class="fas fa-user" style="font-size: 40px; color: #999;"></i>
                                </div>
                            </div>
                            <div style="flex-grow: 1;">
                                <input type="file" id="create_avatar" name="avatar" accept="image/*" class="form-input" onchange="previewCreateAvatar(this)">
                                <small style="color: #666;">JPG, PNG o GIF. Máx 5MB. Se convertirá a WebP automáticamente.</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="create_name">Nombre</label>
                        <input type="text" id="create_name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="create_email">Email</label>
                        <input type="email" id="create_email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="create_telefono">Teléfono</label>
                        <input type="tel" id="create_telefono" name="telefono" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="create_password">Contraseña</label>
                        <input type="password" id="create_password" name="password" class="form-input" required minlength="8">
                        <small>Mínimo 8 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label for="create_roles">Roles</label>
                        <div class="roles-selector">
                            <div class="roles-search-wrapper">
                                <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
                                <input 
                                    type="text" 
                                    id="create_roles_search" 
                                    class="roles-search-input" 
                                    placeholder="Buscar roles..."
                                    autocomplete="off"
                                >
                            </div>
                            <div class="roles-list">
                                @foreach($roles as $role)
                                    <label class="role-item" data-role-name="{{ strtolower($role->name) }}">
                                        <input type="checkbox" name="roles_ids[]" value="{{ $role->id }}" class="role-checkbox" data-role-id="{{ $role->id }}">
                                        <span class="role-name">{{ $role->name }}</span>
                                        <span class="role-badge">{{ $role->id }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="roles-selected">
                                <div class="selected-label">Roles seleccionados:</div>
                                <div id="create_selected_roles" class="selected-roles-list"></div>
                            </div>
                        </div>
                        <small style="color: #666; display: block; margin-top: 8px;">Selecciona al menos un rol</small>
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
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_avatar">Avatar</label>
                        <div style="display: flex; gap: 15px; align-items: flex-start;">
                            <div style="flex-shrink: 0;">
                                <div id="edit_avatar_preview" style="width: 100px; height: 100px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <i class="fas fa-user" style="font-size: 40px; color: #999;"></i>
                                </div>
                            </div>
                            <div style="flex-grow: 1;">
                                <input type="file" id="edit_avatar" name="avatar" accept="image/*" class="form-input" onchange="previewEditAvatar(this)">
                                <small style="color: #666;">JPG, PNG o GIF. Máx 5MB. Se convertirá a WebP automáticamente.</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Nombre</label>
                        <input type="text" id="edit_name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_telefono">Teléfono</label>
                        <input type="tel" id="edit_telefono" name="telefono" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="edit_roles">Roles</label>
                        <div class="roles-selector">
                            <div class="roles-search-wrapper">
                                <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
                                <input 
                                    type="text" 
                                    id="edit_roles_search" 
                                    class="roles-search-input" 
                                    placeholder="Buscar roles..."
                                    autocomplete="off"
                                >
                            </div>
                            <div class="roles-list">
                                @foreach($roles as $role)
                                    <label class="role-item" data-role-name="{{ strtolower($role->name) }}">
                                        <input type="checkbox" name="roles_ids[]" value="{{ $role->id }}" class="role-checkbox" data-role-id="{{ $role->id }}">
                                        <span class="role-name">{{ $role->name }}</span>
                                        <span class="role-badge">{{ $role->id }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="roles-selected">
                                <div class="selected-label">Roles seleccionados:</div>
                                <div id="edit_selected_roles" class="selected-roles-list"></div>
                            </div>
                        </div>
                        <small style="color: #666; display: block; margin-top: 8px;">Selecciona al menos un rol</small>
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
