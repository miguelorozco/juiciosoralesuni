@extends('layouts.app')

@section('title', 'Mi Perfil - Simulador de Juicios Orales')

@section('content')
<div x-data="perfilManager()" class="container-fluid py-4">
    <!-- Header Principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-person-circle me-2 text-primary"></i>
                        Mi Perfil
                    </h1>
                    <p class="text-muted mb-0">
                        Gestiona tu información personal y preferencias
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" @click="cancelarCambios()">
                        <i class="bi bi-x-lg me-2"></i>
                        Cancelar
                    </button>
                    <button class="btn btn-primary" @click="guardarPerfil()" :disabled="guardando">
                        <i class="bi bi-check-lg me-2"></i>
                        <span x-text="guardando ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Personal -->
        <div class="col-lg-8">
            <!-- Datos Básicos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-person me-2 text-primary"></i>
                        Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Nombre</label>
                            <input type="text" class="form-control" x-model="usuario.name" required>
                            <div class="invalid-feedback" x-show="errores.name" x-text="errores.name"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Apellido</label>
                            <input type="text" class="form-control" x-model="usuario.apellido" required>
                            <div class="invalid-feedback" x-show="errores.apellido" x-text="errores.apellido"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Correo Electrónico</label>
                            <input type="email" class="form-control" x-model="usuario.email" required>
                            <div class="invalid-feedback" x-show="errores.email" x-text="errores.email"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Tipo de Usuario</label>
                            <input type="text" class="form-control" :value="getTipoUsuario(usuario.tipo)" readonly>
                            <div class="form-text">El tipo de usuario no se puede cambiar</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Fecha de Registro</label>
                            <input type="text" class="form-control" :value="formatDate(usuario.created_at)" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Último Acceso</label>
                            <input type="text" class="form-control" :value="formatDate(usuario.ultimo_acceso)" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cambio de Contraseña -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-shield-lock me-2 text-warning"></i>
                        Cambio de Contraseña
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Contraseña Actual</label>
                            <div class="input-group">
                                <input :type="mostrarContraseñaActual ? 'text' : 'password'" 
                                       class="form-control" 
                                       x-model="cambioContraseña.contraseña_actual">
                                <button class="btn btn-outline-secondary" type="button" 
                                        @click="mostrarContraseñaActual = !mostrarContraseñaActual">
                                    <i class="bi" :class="mostrarContraseñaActual ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" x-show="errores.contraseña_actual" x-text="errores.contraseña_actual"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Nueva Contraseña</label>
                            <div class="input-group">
                                <input :type="mostrarNuevaContraseña ? 'text' : 'password'" 
                                       class="form-control" 
                                       x-model="cambioContraseña.nueva_contraseña">
                                <button class="btn btn-outline-secondary" type="button" 
                                        @click="mostrarNuevaContraseña = !mostrarNuevaContraseña">
                                    <i class="bi" :class="mostrarNuevaContraseña ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" x-show="errores.nueva_contraseña" x-text="errores.nueva_contraseña"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <input :type="mostrarConfirmarContraseña ? 'text' : 'password'" 
                                       class="form-control" 
                                       x-model="cambioContraseña.confirmar_contraseña">
                                <button class="btn btn-outline-secondary" type="button" 
                                        @click="mostrarConfirmarContraseña = !mostrarConfirmarContraseña">
                                    <i class="bi" :class="mostrarConfirmarContraseña ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" x-show="errores.confirmar_contraseña" x-text="errores.confirmar_contraseña"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <button class="btn btn-warning" @click="cambiarContraseña()" :disabled="!validarContraseñas()">
                                <i class="bi bi-key me-2"></i>
                                Cambiar Contraseña
                            </button>
                        </div>
                    </div>
                    
                    <!-- Indicador de Fortaleza de Contraseña -->
                    <div x-show="cambioContraseña.nueva_contraseña" class="mt-3">
                        <div class="text-muted small mb-2">Fortaleza de la contraseña:</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" 
                                 :class="getFortalezaContraseña().clase" 
                                 :style="`width: ${getFortalezaContraseña().porcentaje}%`"></div>
                        </div>
                        <div class="text-muted small mt-1" x-text="getFortalezaContraseña().texto"></div>
                    </div>
                </div>
            </div>

            <!-- Preferencias -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-gear me-2 text-info"></i>
                        Preferencias
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="preferencias.notificaciones_email">
                                <label class="form-check-label fw-medium">
                                    Notificaciones por Email
                                </label>
                                <div class="form-text">Recibir notificaciones por correo electrónico</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="preferencias.notificaciones_push">
                                <label class="form-check-label fw-medium">
                                    Notificaciones Push
                                </label>
                                <div class="form-text">Recibir notificaciones push en tiempo real</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="preferencias.modo_oscuro">
                                <label class="form-check-label fw-medium">
                                    Modo Oscuro
                                </label>
                                <div class="form-text">Usar tema oscuro en la interfaz</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" x-model="preferencias.auto_save">
                                <label class="form-check-label fw-medium">
                                    Auto-guardado
                                </label>
                                <div class="form-text">Guardar automáticamente los cambios</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Idioma</label>
                            <select class="form-select" x-model="preferencias.idioma">
                                <option value="es">Español</option>
                                <option value="en">English</option>
                                <option value="fr">Français</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Zona Horaria</label>
                            <select class="form-select" x-model="preferencias.zona_horaria">
                                <option value="America/Mexico_City">México (GMT-6)</option>
                                <option value="America/New_York">Nueva York (GMT-5)</option>
                                <option value="Europe/Madrid">Madrid (GMT+1)</option>
                                <option value="UTC">UTC (GMT+0)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Avatar y Estado -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px;">
                            <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold text-dark mb-1" x-text="usuario.name + ' ' + usuario.apellido"></h5>
                    <p class="text-muted mb-2" x-text="usuario.email"></p>
                    
                    <span class="badge" :class="getBadgeClass(usuario.tipo)" x-text="getTipoUsuario(usuario.tipo)"></span>
                    
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm" @click="cambiarAvatar()">
                            <i class="bi bi-camera me-1"></i>
                            Cambiar Avatar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas del Usuario -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2 text-success"></i>
                        Mis Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Sesiones Participadas</span>
                            <span class="fw-bold text-dark" x-text="estadisticas.sesiones_participadas || 0"></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Sesiones Creadas</span>
                            <span class="fw-bold text-dark" x-text="estadisticas.sesiones_creadas || 0"></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Puntuación Promedio</span>
                            <span class="fw-bold text-dark" x-text="estadisticas.puntuacion_promedio || '0'"></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Tiempo Total</span>
                            <span class="fw-bold text-dark" x-text="estadisticas.tiempo_total || '0h 0m'"></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Última Actividad</span>
                            <span class="fw-bold text-dark" x-text="formatDate(usuario.ultimo_acceso)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history me-2 text-info"></i>
                        Actividad Reciente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="space-y-3" style="max-height: 300px; overflow-y: auto;">
                        <template x-for="actividad in actividadReciente" :key="actividad.id">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-circle-fill text-info" style="font-size: 8px;"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="text-dark small" x-text="actividad.descripcion"></div>
                                    <div class="text-muted small" x-text="formatTime(actividad.fecha)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Acciones de Cuenta -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                        Acciones de Cuenta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning" @click="exportarDatos()">
                            <i class="bi bi-download me-2"></i>
                            Exportar Mis Datos
                        </button>
                        
                        <button class="btn btn-outline-info" @click="descargarCertificados()">
                            <i class="bi bi-award me-2"></i>
                            Mis Certificados
                        </button>
                        
                        <button class="btn btn-outline-danger" @click="eliminarCuenta()">
                            <i class="bi bi-trash me-2"></i>
                            Eliminar Cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function perfilManager() {
    return {
        usuario: {
            id: null,
            name: '',
            apellido: '',
            email: '',
            tipo: '',
            created_at: null,
            ultimo_acceso: null
        },
        cambioContraseña: {
            contraseña_actual: '',
            nueva_contraseña: '',
            confirmar_contraseña: ''
        },
        preferencias: {
            notificaciones_email: true,
            notificaciones_push: false,
            modo_oscuro: false,
            auto_save: true,
            idioma: 'es',
            zona_horaria: 'America/Mexico_City'
        },
        estadisticas: {},
        actividadReciente: [],
        errores: {},
        guardando: false,
        mostrarContraseñaActual: false,
        mostrarNuevaContraseña: false,
        mostrarConfirmarContraseña: false,
        
        init() {
            this.cargarPerfil();
            this.cargarEstadisticas();
            this.cargarActividadReciente();
        },
        
        async cargarPerfil() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/auth/me', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.usuario = data.user;
                }
            } catch (error) {
                console.error('Error cargando perfil:', error);
            }
        },
        
        async cargarEstadisticas() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/estadisticas/usuario', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.estadisticas = data.data;
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        },
        
        async cargarActividadReciente() {
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/estadisticas/actividad-usuario', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.actividadReciente = data.data;
                }
            } catch (error) {
                console.error('Error cargando actividad reciente:', error);
            }
        },
        
        async guardarPerfil() {
            this.guardando = true;
            this.errores = {};
            
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/auth/profile', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.usuario.name,
                        apellido: this.usuario.apellido,
                        email: this.usuario.email,
                        preferencias: this.preferencias
                    })
                });
                
                if (response.ok) {
                    this.showToast('Perfil actualizado exitosamente', 'success');
                } else {
                    const error = await response.json();
                    if (error.errors) {
                        this.errores = error.errors;
                    } else {
                        this.showToast(error.message || 'Error al actualizar el perfil', 'error');
                    }
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            } finally {
                this.guardando = false;
            }
        },
        
        async cambiarContraseña() {
            if (!this.validarContraseñas()) {
                this.showToast('Las contraseñas no coinciden', 'error');
                return;
            }
            
            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch('/api/auth/cambiar-contraseña', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.cambioContraseña)
                });
                
                if (response.ok) {
                    this.showToast('Contraseña cambiada exitosamente', 'success');
                    this.cambioContraseña = {
                        contraseña_actual: '',
                        nueva_contraseña: '',
                        confirmar_contraseña: ''
                    };
                } else {
                    const error = await response.json();
                    this.showToast(error.message || 'Error al cambiar la contraseña', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        validarContraseñas() {
            return this.cambioContraseña.nueva_contraseña && 
                   this.cambioContraseña.nueva_contraseña === this.cambioContraseña.confirmar_contraseña &&
                   this.cambioContraseña.nueva_contraseña.length >= 6;
        },
        
        getFortalezaContraseña() {
            const contraseña = this.cambioContraseña.nueva_contraseña || '';
            let fortaleza = 0;
            let texto = 'Muy débil';
            let clase = 'bg-danger';
            
            if (contraseña.length >= 6) fortaleza += 25;
            if (contraseña.length >= 8) fortaleza += 25;
            if (/[A-Z]/.test(contraseña)) fortaleza += 25;
            if (/[0-9]/.test(contraseña)) fortaleza += 25;
            
            if (fortaleza >= 75) {
                texto = 'Muy fuerte';
                clase = 'bg-success';
            } else if (fortaleza >= 50) {
                texto = 'Fuerte';
                clase = 'bg-warning';
            } else if (fortaleza >= 25) {
                texto = 'Débil';
                clase = 'bg-warning';
            }
            
            return { porcentaje: fortaleza, texto, clase };
        },
        
        cancelarCambios() {
            this.cargarPerfil();
            this.cambioContraseña = {
                contraseña_actual: '',
                nueva_contraseña: '',
                confirmar_contraseña: ''
            };
            this.errores = {};
        },
        
        cambiarAvatar() {
            // Implementar cambio de avatar
            this.showToast('Función de cambio de avatar en desarrollo', 'info');
        },
        
        exportarDatos() {
            const datos = {
                usuario: this.usuario,
                estadisticas: this.estadisticas,
                actividadReciente: this.actividadReciente,
                fecha: new Date().toISOString()
            };
            
            const blob = new Blob([JSON.stringify(datos, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `mis-datos-${new Date().toISOString().slice(0,10)}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        descargarCertificados() {
            this.showToast('Función de certificados en desarrollo', 'info');
        },
        
        eliminarCuenta() {
            if (confirm('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.')) {
                this.showToast('Función de eliminación de cuenta en desarrollo', 'warning');
            }
        },
        
        getTipoUsuario(tipo) {
            const tipos = {
                'admin': 'Administrador',
                'instructor': 'Instructor',
                'alumno': 'Alumno'
            };
            return tipos[tipo] || tipo;
        },
        
        getBadgeClass(tipo) {
            const clases = {
                'admin': 'bg-danger',
                'instructor': 'bg-warning',
                'alumno': 'bg-info'
            };
            return clases[tipo] || 'bg-secondary';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'Nunca';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES');
        },
        
        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES');
        },
        
        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            } text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    }
}
</script>

<style>
.card {
    transition: all 0.3s ease;
    border-radius: 12px;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

/* Animaciones suaves */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .h2 {
        font-size: 1.5rem;
    }
}
</style>
@endsection
