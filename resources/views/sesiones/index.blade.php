@extends('layouts.app')

@section('title', 'Gestión de Sesiones')

@section('content')
<div x-data="sesionesManager()" class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                Gestión de Sesiones
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Administra y participa en sesiones de juicios orales
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
            <button @click="mostrarModalCrear = true" 
                    class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Nueva Sesión
            </button>
            @endif
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                <select x-model="filtros.estado" @change="filtrarSesiones()" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos</option>
                    <option value="programada">Programada</option>
                    <option value="en_curso">En Curso</option>
                    <option value="finalizada">Finalizada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select x-model="filtros.tipo" @change="filtrarSesiones()" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos</option>
                    <option value="civil">Civil</option>
                    <option value="penal">Penal</option>
                    <option value="laboral">Laboral</option>
                    <option value="administrativo">Administrativo</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar</label>
                <input type="text" x-model="filtros.buscar" @input="filtrarSesiones()" 
                       placeholder="Nombre o descripción..."
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div class="flex items-end">
                <button @click="limpiarFiltros()" 
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de Sesiones -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flow-root">
                <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Sesión
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Participantes
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Instructor
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                    <template x-for="sesion in sesionesFiltradas" :key="sesion.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                                            <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="sesion.nombre"></div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="sesion.descripcion"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                      :class="{
                                                        'bg-green-100 text-green-800': sesion.estado === 'en_curso',
                                                        'bg-blue-100 text-blue-800': sesion.estado === 'programada',
                                                        'bg-gray-100 text-gray-800': sesion.estado === 'finalizada',
                                                        'bg-red-100 text-red-800': sesion.estado === 'cancelada'
                                                      }"
                                                      x-text="sesion.estado"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <div x-text="formatDate(sesion.fecha_inicio)"></div>
                                                <div class="text-xs" x-text="formatTime(sesion.fecha_inicio)"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span x-text="sesion.participantes_count || 0"></span> / <span x-text="sesion.max_participantes || '∞'"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span x-text="sesion.instructor?.name || 'Sin asignar'"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <button @click="verSesion(sesion)" 
                                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                        Ver
                                                    </button>
                                                    
                                                    @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
                                                    <button @click="editarSesion(sesion)" 
                                                            class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                                        Editar
                                                    </button>
                                                    
                                                    <button @click="eliminarSesion(sesion)" 
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                        Eliminar
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Sesión -->
    <div x-show="mostrarModalCrear" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="mostrarModalCrear = false"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="crearSesion()">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Nueva Sesión de Juicio
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                                        <input type="text" x-model="nuevaSesion.nombre" required
                                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                                        <textarea x-model="nuevaSesion.descripcion" rows="3"
                                                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"></textarea>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Inicio</label>
                                            <input type="datetime-local" x-model="nuevaSesion.fecha_inicio" required
                                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máx. Participantes</label>
                                            <input type="number" x-model="nuevaSesion.max_participantes" min="1" max="20"
                                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="creando"
                                :class="creando ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-700'"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <span x-text="creando ? 'Creando...' : 'Crear Sesión'"></span>
                        </button>
                        <button type="button" @click="mostrarModalCrear = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function sesionesManager() {
    return {
        sesiones: [],
        sesionesFiltradas: [],
        filtros: {
            estado: '',
            tipo: '',
            buscar: ''
        },
        mostrarModalCrear: false,
        creando: false,
        nuevaSesion: {
            nombre: '',
            descripcion: '',
            fecha_inicio: '',
            max_participantes: 10
        },
        
        init() {
            this.cargarSesiones();
        },
        
        async cargarSesiones() {
            try {
                const token = localStorage.getItem('token');
                const response = await fetch('/api/sesiones', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.sesiones = data.data.data;
                    this.sesionesFiltradas = [...this.sesiones];
                }
            } catch (error) {
                console.error('Error cargando sesiones:', error);
            }
        },
        
        filtrarSesiones() {
            this.sesionesFiltradas = this.sesiones.filter(sesion => {
                const cumpleEstado = !this.filtros.estado || sesion.estado === this.filtros.estado;
                const cumpleTipo = !this.filtros.tipo || sesion.tipo === this.filtros.tipo;
                const cumpleBusqueda = !this.filtros.buscar || 
                    sesion.nombre.toLowerCase().includes(this.filtros.buscar.toLowerCase()) ||
                    sesion.descripcion.toLowerCase().includes(this.filtros.buscar.toLowerCase());
                
                return cumpleEstado && cumpleTipo && cumpleBusqueda;
            });
        },
        
        limpiarFiltros() {
            this.filtros = { estado: '', tipo: '', buscar: '' };
            this.sesionesFiltradas = [...this.sesiones];
        },
        
        async crearSesion() {
            this.creando = true;
            
            try {
                const token = localStorage.getItem('token');
                const response = await fetch('/api/sesiones', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.nuevaSesion)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.sesiones.unshift(data.data);
                    this.sesionesFiltradas = [...this.sesiones];
                    this.mostrarModalCrear = false;
                    this.nuevaSesion = { nombre: '', descripcion: '', fecha_inicio: '', max_participantes: 10 };
                    this.showToast('Sesión creada exitosamente', 'success');
                } else {
                    const error = await response.json();
                    this.showToast(error.message || 'Error al crear la sesión', 'error');
                }
            } catch (error) {
                this.showToast('Error de conexión', 'error');
            } finally {
                this.creando = false;
            }
        },
        
        verSesion(sesion) {
            window.location.href = `/sesiones/${sesion.id}`;
        },
        
        editarSesion(sesion) {
            window.location.href = `/sesiones/${sesion.id}/edit`;
        },
        
        async eliminarSesion(sesion) {
            if (confirm('¿Estás seguro de que quieres eliminar esta sesión?')) {
                try {
                    const token = localStorage.getItem('token');
                    const response = await fetch(`/api/sesiones/${sesion.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        this.sesiones = this.sesiones.filter(s => s.id !== sesion.id);
                        this.sesionesFiltradas = [...this.sesiones];
                        this.showToast('Sesión eliminada exitosamente', 'success');
                    } else {
                        const error = await response.json();
                        this.showToast(error.message || 'Error al eliminar la sesión', 'error');
                    }
                } catch (error) {
                    this.showToast('Error de conexión', 'error');
                }
            }
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES');
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
