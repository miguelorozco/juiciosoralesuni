@extends('layouts.app')

@section('title', 'Sesión Activa')

@section('content')
<div x-data="sesionActiva()" class="space-y-6">
    <!-- Header de la Sesión -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                    <span x-text="sesion.nombre"></span>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="sesion.descripcion"></p>
                
                <div class="mt-2 flex items-center space-x-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="{
                            'bg-green-100 text-green-800': sesion.estado === 'en_curso',
                            'bg-blue-100 text-blue-800': sesion.estado === 'programada',
                            'bg-gray-100 text-gray-800': sesion.estado === 'finalizada'
                          }"
                          x-text="sesion.estado"></span>
                    
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="sesion.participantes_count || 0"></span> / <span x-text="sesion.max_participantes || '∞'"></span> participantes
                    </span>
                    
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(sesion.fecha_inicio)"></span>
                </div>
            </div>
            
            <div class="mt-4 flex md:mt-0 md:ml-4">
                @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                <button @click="iniciarDialogo()" x-show="!dialogoActivo"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 10a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Iniciar Diálogo
                </button>
                
                <button @click="pausarDialogo()" x-show="dialogoActivo && dialogoActivo.estado === 'en_curso'"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pausar
                </button>
                
                <button @click="finalizarDialogo()" x-show="dialogoActivo"
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Finalizar
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Área del Diálogo -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <!-- Estado del Diálogo -->
                    <div x-show="dialogoActivo" class="mb-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Diálogo Activo: <span x-text="dialogoActivo?.dialogo?.nombre"></span>
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                    'bg-green-100 text-green-800': dialogoActivo?.estado === 'en_curso',
                                    'bg-yellow-100 text-yellow-800': dialogoActivo?.estado === 'pausado',
                                    'bg-red-100 text-red-800': dialogoActivo?.estado === 'finalizado'
                                  }"
                                  x-text="dialogoActivo?.estado"></span>
                        </div>
                    </div>
                    
                    <!-- Nodo Actual -->
                    <div x-show="nodoActual" class="mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                     :class="getRolColor(nodoActual?.rol_id)">
                                    <span class="text-sm font-medium text-white" x-text="getRolInicial(nodoActual?.rol_id)"></span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white" x-text="getRolNombre(nodoActual?.rol_id)"></h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Habla ahora</p>
                                </div>
                            </div>
                            
                            <div class="text-gray-900 dark:text-white" x-text="nodoActual?.texto"></div>
                        </div>
                    </div>
                    
                    <!-- Respuestas Disponibles -->
                    <div x-show="respuestasDisponibles && respuestasDisponibles.length > 0" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Opciones de Respuesta:</h4>
                        <div class="space-y-2">
                            <template x-for="respuesta in respuestasDisponibles" :key="respuesta.id">
                                <button @click="enviarRespuesta(respuesta)" 
                                        class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="text-sm text-gray-900 dark:text-white" x-text="respuesta.texto"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Sin diálogo activo -->
                    <div x-show="!dialogoActivo" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay diálogo activo</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selecciona un diálogo para comenzar la sesión.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Participantes -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Participantes
                    </h3>
                    <div class="mt-5">
                        <div class="space-y-3">
                            <template x-for="participante in participantes" :key="participante.id">
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400" 
                                              x-text="participante.usuario?.name?.charAt(0) || '?'"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white" 
                                           x-text="participante.usuario?.name || 'Usuario'"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" 
                                           x-text="participante.rol?.nombre || 'Sin rol'"></p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                'bg-green-100 text-green-800': participante.confirmado,
                                                'bg-yellow-100 text-yellow-800': !participante.confirmado
                                              }"
                                              x-text="participante.confirmado ? 'Activo' : 'Pendiente'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Decisiones -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Historial de Decisiones
                    </h3>
                    <div class="mt-5">
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <template x-for="decision in historialDecisiones" :key="decision.id">
                                <div class="border-l-4 border-indigo-400 pl-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white" 
                                           x-text="decision.usuario?.name || 'Usuario'"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" 
                                           x-text="formatTime(decision.fecha_decision)"></p>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400" 
                                       x-text="decision.texto_decision"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de la Sesión -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Estadísticas
                    </h3>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Tiempo transcurrido</span>
                                <span class="text-gray-900 dark:text-white font-medium" x-text="tiempoTranscurrido"></span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Decisiones tomadas</span>
                                <span class="text-gray-900 dark:text-white font-medium" x-text="historialDecisiones.length"></span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Participantes activos</span>
                                <span class="text-gray-900 dark:text-white font-medium" x-text="participantes.filter(p => p.confirmado).length"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Seleccionar Diálogo -->
    <div x-show="mostrarModalDialogos" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="mostrarModalDialogos = false"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        Seleccionar Diálogo
                    </h3>
                    
                    <div class="space-y-3">
                        <template x-for="dialogo in dialogosDisponibles" :key="dialogo.id">
                            <button @click="seleccionarDialogo(dialogo)" 
                                    class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="dialogo.nombre"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="dialogo.descripcion"></div>
                            </button>
                        </template>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="mostrarModalDialogos = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function sesionActiva() {
    return {
        sesion: {},
        dialogoActivo: null,
        nodoActual: null,
        respuestasDisponibles: [],
        participantes: [],
        historialDecisiones: [],
        dialogosDisponibles: [],
        mostrarModalDialogos: false,
        tiempoTranscurrido: '00:00:00',
        intervaloTiempo: null,
        
        init() {
            this.cargarSesion();
            this.cargarDialogosDisponibles();
            this.iniciarIntervaloTiempo();
        },
        
        async cargarSesion() {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.sesion = data.data;
                    this.participantes = data.data.asignaciones || [];
                    
                    // Cargar diálogo activo si existe
                    await this.cargarDialogoActivo();
                }
            } catch (error) {
                console.error('Error cargando sesión:', error);
            }
        },
        
        async cargarDialogoActivo() {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/dialogo-actual`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.dialogoActivo = data.data;
                    this.nodoActual = data.data.nodo_actual;
                    
                    // Cargar respuestas disponibles para el usuario actual
                    await this.cargarRespuestasDisponibles();
                    
                    // Cargar historial de decisiones
                    await this.cargarHistorialDecisiones();
                }
            } catch (error) {
                console.error('Error cargando diálogo activo:', error);
            }
        },
        
        async cargarRespuestasDisponibles() {
            if (!this.nodoActual) return;
            
            const sesionId = window.location.pathname.split('/').pop();
            const usuarioId = JSON.parse(localStorage.getItem('user')).id;
            
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/respuestas-disponibles/${usuarioId}`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.respuestasDisponibles = data.data;
                }
            } catch (error) {
                console.error('Error cargando respuestas disponibles:', error);
            }
        },
        
        async cargarHistorialDecisiones() {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/historial-decisiones`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.historialDecisiones = data.data;
                }
            } catch (error) {
                console.error('Error cargando historial de decisiones:', error);
            }
        },
        
        async cargarDialogosDisponibles() {
            try {
                const token = localStorage.getItem('token');
                const response = await fetch('/api/dialogos?activo=true', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.dialogosDisponibles = data.data.data;
                }
            } catch (error) {
                console.error('Error cargando diálogos disponibles:', error);
            }
        },
        
        async iniciarDialogo() {
            this.mostrarModalDialogos = true;
        },
        
        async seleccionarDialogo(dialogo) {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/iniciar-dialogo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ dialogo_id: dialogo.id })
                });
                
                if (response.ok) {
                    this.mostrarModalDialogos = false;
                    await this.cargarDialogoActivo();
                    this.showToast('Diálogo iniciado exitosamente', 'success');
                } else {
                    const error = await response.json();
                    this.showToast(error.message || 'Error al iniciar el diálogo', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        async enviarRespuesta(respuesta) {
            const sesionId = window.location.pathname.split('/').pop();
            const usuarioId = JSON.parse(localStorage.getItem('user')).id;
            
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/procesar-decision`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        respuesta_id: respuesta.id,
                        usuario_id: usuarioId
                    })
                });
                
                if (response.ok) {
                    await this.cargarDialogoActivo();
                    await this.cargarHistorialDecisiones();
                    this.showToast('Respuesta enviada exitosamente', 'success');
                } else {
                    const error = await response.json();
                    this.showToast(error.message || 'Error al enviar la respuesta', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            }
        },
        
        async pausarDialogo() {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/pausar-dialogo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.cargarDialogoActivo();
                    this.showToast('Diálogo pausado', 'success');
                }
            } catch (error) {
                this.showToast('Error al pausar el diálogo', 'error');
            }
        },
        
        async finalizarDialogo() {
            const sesionId = window.location.pathname.split('/').pop();
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`/api/sesiones/${sesionId}/finalizar-dialogo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    await this.cargarDialogoActivo();
                    this.showToast('Diálogo finalizado', 'success');
                }
            } catch (error) {
                this.showToast('Error al finalizar el diálogo', 'error');
            }
        },
        
        iniciarIntervaloTiempo() {
            this.intervaloTiempo = setInterval(() => {
                if (this.sesion.fecha_inicio) {
                    const inicio = new Date(this.sesion.fecha_inicio);
                    const ahora = new Date();
                    const diff = ahora - inicio;
                    
                    const horas = Math.floor(diff / 3600000);
                    const minutos = Math.floor((diff % 3600000) / 60000);
                    const segundos = Math.floor((diff % 60000) / 1000);
                    
                    this.tiempoTranscurrido = `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
                }
            }, 1000);
        },
        
        getRolColor(rolId) {
            const colors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'];
            return colors[rolId % colors.length];
        },
        
        getRolInicial(rolId) {
            const rol = this.participantes.find(p => p.rol_id === rolId)?.rol;
            return rol ? rol.nombre.charAt(0).toUpperCase() : '?';
        },
        
        getRolNombre(rolId) {
            const rol = this.participantes.find(p => p.rol_id === rolId)?.rol;
            return rol ? rol.nombre : 'Sin rol';
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        },
        
        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
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
@endsection
