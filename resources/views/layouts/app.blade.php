<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Simulador de Juicios Orales')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Estilos específicos por vista -->
    @stack('styles')
    
    <!-- Scripts del head (se cargan antes de Alpine.js) -->
    @stack('head-scripts')
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Script de autenticación global -->
    <script>
        // Sistema de autenticación global
        (function() {
            console.log('🔐 Sistema de autenticación inicializado');
            
            // Incluir token en peticiones same-origin (incluye /api/) para que estadísticas y demás funcionen
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                const token = localStorage.getItem('auth_token');
                if (token && typeof url === 'string' && url.startsWith('/')) {
                    if (!options.headers) {
                        options.headers = {};
                    }
                    options.headers['Authorization'] = 'Bearer ' + token;
                    options.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
                return originalFetch(url, options);
            };
            
            // No interceptar clics en enlaces: navegación siempre normal (full page) para que sesión y menú funcionen bien.
            
            // Verificar autenticación al cargar la página (solo cuando se usa token JWT)
            // En /estadisticas no se verifica token para evitar cualquier redirección o efecto secundario.
            window.addEventListener('load', function() {
                var path = window.location.pathname || '';
                if (path === '/estadisticas') {
                    console.log('[ESTADISTICAS] Layout: evento load disparado (sin verificación de token)');
                    fetch('/api/estadisticas/debug-log?event=layout_load_estadisticas', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).catch(function() {});
                    return;
                }
                const token = localStorage.getItem('auth_token');
                if (window.location.pathname.includes('/login')) {
                    return;
                }
                if (token) {
                    // Verificar que el token siga siendo válido
                    console.log('🔍 Verificando autenticación con token...');
                    fetch('/api/auth/me', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    }).then(response => {
                        if (response.ok) {
                            console.log('✅ Token válido, usuario autenticado');
                            fetch(window.location.href, {
                                method: 'GET',
                                headers: {
                                    'Authorization': `Bearer ${token}`,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                                }
                            }).then(response => {
                                if (response.ok) {
                                    console.log('✅ Usuario re-autenticado en Laravel');
                                } else {
                                    console.log('⚠️ No se pudo re-autenticar, pero continuando...');
                                }
                            }).catch(() => {
                                console.log('⚠️ Error re-autenticando, pero continuando...');
                            });
                        } else {
                            // Token inválido o caducado: solo limpiar; no redirigir.
                            console.log('⚠️ Token inválido o caducado, se elimina. La sesión web sigue activa.');
                            if (path === '/estadisticas') {
                                fetch('/api/estadisticas/debug-log?event=token_verificacion_fallo_no_redirect', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).catch(function() {});
                            }
                            localStorage.removeItem('auth_token');
                        }
                    }).catch(() => {
                        console.log('⚠️ Error verificando token, se elimina. La sesión web sigue activa.');
                        if (path === '/estadisticas') {
                            fetch('/api/estadisticas/debug-log?event=token_verificacion_error_red_no_redirect', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).catch(function() {});
                        }
                        localStorage.removeItem('auth_token');
                    });
                }
                // Sin token: no redirigir; el servidor ya validó la sesión al servir la página
            });
            
            console.log('✅ Interceptor de autenticación configurado');
        })();
    </script>
    
    <!-- Chart.js para estadísticas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light" x-data="{ sidebarOpen: false }">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
        <div class="container-fluid">
            <!-- Logo y título -->
            <a class="navbar-brand d-flex align-items-center" href="/dashboard">
                <div class="bg-primary rounded-3 p-2 me-3">
                    <i class="bi bi-shield-check text-white fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark mb-0">Juicios Orales</div>
                    <small class="text-muted">Simulador</small>
                </div>
            </a>

            <!-- Botón móvil -->
            <button class="btn btn-outline-secondary d-lg-none" type="button" @click="sidebarOpen = !sidebarOpen">
                <i class="bi bi-list"></i>
            </button>

            <!-- Navbar items -->
            <div class="navbar-nav ms-auto d-none d-lg-flex">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="bi bi-person text-primary"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold text-dark mb-0">{{ auth()->user()->name ?? 'Usuario' }}</div>
                            <small class="text-muted">{{ ucfirst(auth()->user()->tipo ?? 'usuario') }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Perfil</a></li>
                        {{-- <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Configuración</a></li> --}}
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="/logout" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar móvil -->
    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" :class="{ 'show': sidebarOpen }" 
         x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold">Menú</h5>
            <button type="button" class="btn-close" @click="sidebarOpen = false"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('layouts.sidebar')
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar desktop -->
            <div class="col-lg-2 d-none d-lg-block p-0">
                <div class="bg-white shadow-sm h-100 min-vh-100">
                    @include('layouts.sidebar')
                </div>
            </div>

            <!-- Contenido -->
            <div class="col-lg-10">
                <main class="py-4">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Sección de scripts específica por vista --}}
    @yield('scripts')

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .navbar-brand {
            text-decoration: none;
        }
        
        .navbar-brand:hover {
            text-decoration: none;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 12px;
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.25rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .offcanvas {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .min-vh-100 {
            min-height: 100vh;
        }
    </style>
</body>
</html>