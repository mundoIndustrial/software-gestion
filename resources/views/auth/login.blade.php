<x-guest-layout>
    <!-- Estado de la sesion -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Mensaje de error (sesion expirada, etc.) -->
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-medium text-red-800">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Boton Google OAuth -->
    <div class="mb-6">
        <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium text-gray-700">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#4285F4" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span>Iniciar sesion con Google</span>
        </a>
    </div>

    <!-- Divisor -->
    <div class="flex items-center mb-6">
        <div class="flex-1 border-t border-gray-300"></div>
        <span class="px-3 text-sm text-gray-500 font-medium">O</span>
        <div class="flex-1 border-t border-gray-300"></div>
    </div>

    <form id="api-login-form" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Correo electronico -->
        <div>
            <x-input-label for="email" :value="__('Correo electronico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p id="api-email-error" class="mt-2 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Contrasena -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contrasena')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p id="api-password-error" class="mt-2 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember" checked>
                <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme por 30 dias') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button id="api-login-submit" class="ms-3">
                {{ __('Iniciar sesion') }}
            </x-primary-button>
        </div>

        <p id="api-login-error" class="mt-4 text-sm text-red-600 hidden"></p>
    </form>

    <script>
        (() => {
            const form = document.getElementById('api-login-form');
            if (!form) return;

            const submitButton = document.getElementById('api-login-submit');
            const generalError = document.getElementById('api-login-error');
            const emailError = document.getElementById('api-email-error');
            const passwordError = document.getElementById('api-password-error');

            const showError = (el, message) => {
                if (!el) return;
                el.textContent = message;
                el.classList.remove('hidden');
            };

            const clearErrors = () => {
                [generalError, emailError, passwordError].forEach((el) => {
                    if (!el) return;
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearErrors();

                let shouldHideOverlay = true;
                if (typeof window.showGuestLoadingOverlay === 'function') {
                    window.showGuestLoadingOverlay('Iniciando sesión...');
                }

                if (submitButton) {
                    submitButton.disabled = true;
                }

                const email = document.getElementById('email')?.value ?? '';
                const password = document.getElementById('password')?.value ?? '';
                const remember = document.getElementById('remember_me')?.checked ?? false;
                let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                try {
                    const csrfResponse = await fetch('/api/v1/auth/csrf', {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const csrfPayload = await csrfResponse.json().catch(() => null);
                    const freshCsrfToken = csrfPayload?.data?.csrf_token;
                    if (freshCsrfToken) {
                        csrfToken = freshCsrfToken;
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (csrfMeta) {
                            csrfMeta.setAttribute('content', freshCsrfToken);
                        }
                    }

                    const response = await fetch('/api/v1/auth/login', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ email, password, remember }),
                    });

                    const data = await response.json().catch(() => null);

                    if (response.ok && data?.success) {
                        const redirectTo = data?.data?.redirect_to || '/dashboard';
                        shouldHideOverlay = false;
                        window.location.assign(redirectTo);
                        return;
                    }

                    if (response.status === 422 && data?.errors) {
                        if (Array.isArray(data.errors.email) && data.errors.email.length > 0) {
                            showError(emailError, data.errors.email[0]);
                        }
                        if (Array.isArray(data.errors.password) && data.errors.password.length > 0) {
                            showError(passwordError, data.errors.password[0]);
                        }
                        return;
                    }

                    showError(generalError, data?.message || 'No se pudo iniciar sesion. Intenta nuevamente.');
                } catch (error) {
                    shouldHideOverlay = false;
                    form.submit();
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    if (shouldHideOverlay && typeof window.hideGuestLoadingOverlay === 'function') {
                        window.hideGuestLoadingOverlay();
                    }
                }
            });
        })();
    </script>
</x-guest-layout>
