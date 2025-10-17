<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Iniciar Sesión' }} - Simulador de Juicios Orales</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="loginApp()">
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4 p-md-5">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="bi bi-shield-lock text-white fs-4"></i>
                                </div>
                                <h4 class="fw-bold text-dark mb-2">{{ $title ?? 'Iniciar Sesión' }}</h4>
                                <p class="text-muted small mb-0">{{ $subtitle ?? 'Accede a tu cuenta' }}</p>
                            </div>

                            <!-- Tabs -->
                            <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button 
                                        class="nav-link" 
                                        :class="activeTab === 'login' ? 'active' : ''"
                                        @click="activeTab = 'login'"
                                        type="button">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>
                                        Login
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation" x-show="registrationEnabled">
                                    <button 
                                        class="nav-link" 
                                        :class="activeTab === 'register' ? 'active' : ''"
                                        @click="activeTab = 'register'"
                                        type="button">
                                        <i class="bi bi-person-plus me-1"></i>
                                        Registro
                                    </button>
                                </li>
                            </ul>

                            <!-- Mensaje cuando el registro está deshabilitado -->
                            <div x-show="!registrationEnabled" class="alert alert-warning alert-sm mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <small>El registro de nuevos usuarios no está habilitado.</small>
                            </div>

                            <!-- Login Form -->
                            <div x-show="activeTab === 'login'" x-transition:enter="transition ease-out duration-200" 
                                 x-transition:enter-start="opacity-0 transform scale-95" 
                                 x-transition:enter-end="opacity-100 transform scale-100">
                                <form @submit.prevent="login()" @submit.stop>
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-envelope text-muted"></i>
                                            </span>
                                            <input 
                                                type="email" 
                                                class="form-control border-start-0" 
                                                id="email"
                                                x-model="loginForm.email"
                                                placeholder="tu@email.com"
                                                required>
                                        </div>
                                        <div x-show="loginErrors.email && loginErrors.email.length > 0" class="text-danger small mt-1">
                                            <span x-text="loginErrors.email && loginErrors.email[0] ? loginErrors.email[0] : ''"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-lock text-muted"></i>
                                            </span>
                                            <input 
                                                type="password" 
                                                class="form-control border-start-0" 
                                                id="password"
                                                x-model="loginForm.password"
                                                placeholder="Tu contraseña"
                                                required>
                                        </div>
                                        <div x-show="loginErrors.password && loginErrors.password.length > 0" class="text-danger small mt-1">
                                            <span x-text="loginErrors.password && loginErrors.password[0] ? loginErrors.password[0] : ''"></span>
                                        </div>
                                    </div>

                                    <div x-show="loginErrors.general && loginErrors.general.length > 0" class="alert alert-danger alert-sm mb-3">
                                        <span x-text="loginErrors.general && loginErrors.general[0] ? loginErrors.general[0] : ''"></span>
                                    </div>

                                    <button 
                                        type="submit" 
                                        class="btn btn-primary w-100 py-2 fw-semibold"
                                        :disabled="loginLoading">
                                        <template x-if="!loginLoading">
                                            <span>
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                Iniciar Sesión
                                            </span>
                                        </template>
                                        <template x-if="loginLoading">
                                            <span class="d-flex align-items-center justify-content-center">
                                                <span class="spinner-border spinner-border-sm me-2"></span>
                                                Iniciando...
                                            </span>
                                        </template>
                                    </button>
                                </form>
                            </div>

                            <!-- Register Form -->
                            <div x-show="activeTab === 'register'" x-transition:enter="transition ease-out duration-200" 
                                 x-transition:enter-start="opacity-0 transform scale-95" 
                                 x-transition:enter-end="opacity-100 transform scale-100">
                                <form @submit.prevent="register()" @submit.stop>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label fw-semibold">Nombre</label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="name"
                                                x-model="registerForm.name"
                                                placeholder="Tu nombre"
                                                required>
                                            <div x-show="registerErrors.name && registerErrors.name.length > 0" class="text-danger small mt-1">
                                                <span x-text="registerErrors.name && registerErrors.name[0] ? registerErrors.name[0] : ''"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido" class="form-label fw-semibold">Apellido</label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="apellido"
                                                x-model="registerForm.apellido"
                                                placeholder="Tu apellido"
                                                required>
                                            <div x-show="registerErrors.apellido && registerErrors.apellido.length > 0" class="text-danger small mt-1">
                                                <span x-text="registerErrors.apellido && registerErrors.apellido[0] ? registerErrors.apellido[0] : ''"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="register_email" class="form-label fw-semibold">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-envelope text-muted"></i>
                                            </span>
                                            <input 
                                                type="email" 
                                                class="form-control border-start-0" 
                                                id="register_email"
                                                x-model="registerForm.email"
                                                placeholder="tu@email.com"
                                                required>
                                        </div>
                                        <div x-show="registerErrors.email && registerErrors.email.length > 0" class="text-danger small mt-1">
                                            <span x-text="registerErrors.email && registerErrors.email[0] ? registerErrors.email[0] : ''"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="register_password" class="form-label fw-semibold">Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-lock text-muted"></i>
                                            </span>
                                            <input 
                                                type="password" 
                                                class="form-control border-start-0" 
                                                id="register_password"
                                                x-model="registerForm.password"
                                                placeholder="Mínimo 8 caracteres"
                                                required>
                                        </div>
                                        <div x-show="registerErrors.password && registerErrors.password.length > 0" class="text-danger small mt-1">
                                            <span x-text="registerErrors.password && registerErrors.password[0] ? registerErrors.password[0] : ''"></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tipo" class="form-label fw-semibold">Tipo de usuario</label>
                                        <select class="form-select" id="tipo" x-model="registerForm.tipo" required>
                                            <option value="alumno">Alumno</option>
                                            <option value="instructor">Instructor</option>
                                            <option value="admin">Administrador</option>
                                        </select>
                                        <div x-show="registerErrors.tipo && registerErrors.tipo.length > 0" class="text-danger small mt-1">
                                            <span x-text="registerErrors.tipo && registerErrors.tipo[0] ? registerErrors.tipo[0] : ''"></span>
                                        </div>
                                    </div>

                                    <div x-show="registerErrors.general && registerErrors.general.length > 0" class="alert alert-danger alert-sm mb-3">
                                        <span x-text="registerErrors.general && registerErrors.general[0] ? registerErrors.general[0] : ''"></span>
                                    </div>

                                    <div x-show="registerErrors.registro && registerErrors.registro.length > 0" class="alert alert-warning alert-sm mb-3">
                                        <span x-text="registerErrors.registro && registerErrors.registro[0] ? registerErrors.registro[0] : ''"></span>
                                    </div>

                                    <button 
                                        type="submit" 
                                        class="btn btn-primary w-100 py-2 fw-semibold"
                                        :disabled="registerLoading || !registrationEnabled">
                                        <template x-if="!registerLoading">
                                            <span>
                                                <i class="bi bi-person-plus me-2"></i>
                                                Crear Cuenta
                                            </span>
                                        </template>
                                        <template x-if="registerLoading">
                                            <span class="d-flex align-items-center justify-content-center">
                                                <span class="spinner-border spinner-border-sm me-2"></span>
                                                Creando...
                                            </span>
                                        </template>
                                    </button>
                                </form>
                            </div>

                            <!-- Footer -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Sistema seguro y confiable
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Alpine.js Data -->
    <script>
        function loginApp() {
            return {
                activeTab: 'login',
                registrationEnabled: true,
                showPassword: false,
                loginLoading: false,
                registerLoading: false,
                loginErrors: {
                    email: [],
                    password: [],
                    general: []
                },
                registerErrors: {
                    name: [],
                    apellido: [],
                    email: [],
                    password: [],
                    password_confirmation: [],
                    tipo: [],
                    general: [],
                    registro: []
                },
                loginForm: {
                    email: '',
                    password: ''
                },
                registerForm: {
                    name: '',
                    apellido: '',
                    email: '',
                    password: '',
                    tipo: 'alumno'
                },
                
                async init() {
                    try {
                        const response = await fetch('/api/auth/registration-status');
                        const data = await response.json();
                        this.registrationEnabled = data.data.registration_enabled;
                    } catch (error) {
                        console.error('Error checking registration status:', error);
                    }
                },
                
                async login(event) {
                    console.log('=== INICIO LOGIN JAVASCRIPT ===');
                    
                    // Prevenir cualquier comportamiento por defecto
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    console.log('Formulario de login:', this.loginForm);
                    
                    this.loginLoading = true;
                    this.loginErrors = {
                        email: [],
                        password: [],
                        general: []
                    };
                    
                    try {
                        console.log('Enviando petición POST a /login...');
                        
                        const response = await fetch('/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            },
                            body: JSON.stringify(this.loginForm)
                        });

                        console.log('Respuesta recibida:', response.status, response.statusText);

                        if (!response.ok) {
                            console.error('Error HTTP:', response.status, response.statusText);
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();
                        console.log('Datos de respuesta:', data);

                        if (data.success) {
                            console.log('✅ Login exitoso, guardando token y redirigiendo...');
                            
                            // Guardar token en localStorage
                            localStorage.setItem('auth_token', data.token);
                            console.log('Token guardado en localStorage');
                            
                            // Hacer petición AJAX al dashboard con el token
                            await this.loadDashboardWithToken(data.token);
                        } else {
                            console.log('❌ Login fallido:', data.message);
                            // Manejar diferentes tipos de errores
                            if (data.errors) {
                                console.log('Errores de validación:', data.errors);
                                this.loginErrors = data.errors;
                            } else if (data.message) {
                                console.log('Mensaje de error:', data.message);
                                this.loginErrors = { general: [data.message] };
                            } else {
                                console.log('Error desconocido');
                                this.loginErrors = { general: ['Error desconocido. Inténtalo de nuevo.'] };
                            }
                        }
                    } catch (error) {
                        console.error('❌ Error en login:', error);
                        this.loginErrors = { 
                            general: [`Error de conexión: ${error.message}`] 
                        };
                    } finally {
                        this.loginLoading = false;
                        console.log('=== FIN LOGIN JAVASCRIPT ===');
                    }
                    
                    return false; // Prevenir recarga de página
                },
                
                async loadDashboardWithToken(token) {
                    try {
                        console.log('Cargando dashboard con token...');
                        
                        const response = await fetch('/dashboard', {
                            method: 'GET',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'text/html',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        if (response.ok) {
                            console.log('Dashboard cargado exitosamente');
                            const html = await response.text();
                            document.open();
                            document.write(html);
                            document.close();
                            history.pushState(null, '', '/dashboard');
                        } else {
                            console.error('Error cargando dashboard:', response.status);
                            window.location.href = '/dashboard';
                        }
                    } catch (error) {
                        console.error('Error cargando dashboard:', error);
                        window.location.href = '/dashboard';
                    }
                },
                
                async register(event) {
                    // Prevenir cualquier comportamiento por defecto
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    if (!this.registrationEnabled) {
                        alert('El registro de nuevos usuarios no está habilitado');
                        return;
                    }

                    this.registerLoading = true;
                    this.registerErrors = {
                        name: [],
                        apellido: [],
                        email: [],
                        password: [],
                        password_confirmation: [],
                        tipo: [],
                        general: [],
                        registro: []
                    };
                    
                    try {
                        const response = await fetch('/api/auth/register', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                ...this.registerForm,
                                password_confirmation: this.registerForm.password
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Usuario registrado exitosamente');
                            this.activeTab = 'login';
                            this.registerForm = { name: '', apellido: '', email: '', password: '', tipo: 'alumno' };
                        } else {
                            // Manejar diferentes tipos de errores
                            if (data.errors) {
                                this.registerErrors = data.errors;
                            } else if (data.message) {
                                this.registerErrors = { general: [data.message] };
                            } else {
                                this.registerErrors = { general: ['Error desconocido. Inténtalo de nuevo.'] };
                            }
                        }
                    } catch (error) {
                        console.error('Error en registro:', error);
                        this.registerErrors = { general: ['Error de conexión. Inténtalo de nuevo.'] };
                    } finally {
                        this.registerLoading = false;
                    }
                    
                    return false; // Prevenir recarga de página
                }
            }
        }
    </script>
</body>
</html>