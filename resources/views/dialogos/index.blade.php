@extends('layouts.app')

@section('title', 'Diálogos - Simulador de Juicios Orales')

@section('content')
<div class="container-fluid py-4" x-data="dialogosIndex()" x-init="init()">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        Diálogos Ramificados
                    </h1>
                    <p class="text-muted mb-0">Gestiona los diálogos para simulacros de juicios</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" @click="filtrar()">
                        <i class="bi bi-funnel me-2"></i>
                        Filtros
                    </button>
                    <a href="/dialogos/import" class="btn btn-outline-success">
                        <i class="bi bi-upload me-2"></i>
                        Importar
                    </a>
                    <a href="/dialogos/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nuevo Diálogo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4" x-show="mostrarFiltros" x-transition>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Buscar</label>
                            <input type="text" class="form-control" x-model="filtros.buscar" 
                                   placeholder="Nombre o descripción...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Estado</label>
                            <select class="form-select" x-model="filtros.estado">
                                <option value="">Todos</option>
                                <option value="borrador">Borrador</option>
                                <option value="activo">Activo</option>
                                <option value="archivado">Archivado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Visibilidad</label>
                            <select class="form-select" x-model="filtros.publico">
                                <option value="">Todos</option>
                                <option value="true">Públicos</option>
                                <option value="false">Privados</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" @click="aplicarFiltros()">
                                <i class="bi bi-search me-1"></i>
                                Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Diálogos -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <!-- Loading -->
                    <div x-show="cargando" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted mt-2">Cargando diálogos...</p>
                    </div>

                    <!-- Lista -->
                    <div x-show="!cargando" class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Diálogo</th>
                                    <th class="border-0">Creador</th>
                                    <th class="border-0">Estado</th>
                                    <th class="border-0">Nodos</th>
                                    <th class="border-0">Última Modificación</th>
                                    <th class="border-0 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="dialogo in dialogos" :key="dialogo.id">
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-3">
                                                    <i class="bi bi-diagram-3 text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark" x-text="dialogo.nombre"></div>
                                                    <small class="text-muted" x-text="dialogo.descripcion || 'Sin descripción'"></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-2">
                                                    <i class="bi bi-person text-secondary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-medium text-dark" x-text="dialogo.creador?.name || 'Desconocido'"></div>
                                                    <small class="text-muted" x-text="dialogo.creador?.email"></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" 
                                                  :class="{
                                                      'bg-warning': dialogo.estado === 'borrador',
                                                      'bg-success': dialogo.estado === 'activo',
                                                      'bg-secondary': dialogo.estado === 'archivado'
                                                  }">
                                                <i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i>
                                                <span x-text="dialogo.estado.charAt(0).toUpperCase() + dialogo.estado.slice(1)"></span>
                                            </span>
                                            <div x-show="dialogo.publico" class="mt-1">
                                                <small class="text-info">
                                                    <i class="bi bi-globe me-1"></i>
                                                    Público
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-info bg-opacity-10 rounded-3 p-2 me-2">
                                                    <i class="bi bi-node-plus text-info"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark" x-text="dialogo.total_nodos || 0"></div>
                                                    <small class="text-muted">nodos</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <i class="bi bi-clock me-1"></i>
                                                <span x-text="formatearFecha(dialogo.updated_at)"></span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" :href="`/dialogos/${dialogo.id}`">
                                                            <i class="bi bi-eye me-2"></i>
                                                            Ver
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" :href="`/dialogos/${dialogo.id}/edit`">
                                                            <i class="bi bi-pencil me-2"></i>
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" @click="copiarDialogo(dialogo.id)">
                                                            <i class="bi bi-copy me-2"></i>
                                                            Copiar
                                                        </button>
                                                    </li>
                                                    <li x-show="dialogo.estado === 'borrador'">
                                                        <button class="dropdown-item text-success" @click="activarDialogo(dialogo.id)">
                                                            <i class="bi bi-play-circle me-2"></i>
                                                            Activar
                                                        </button>
                                                    </li>
                                                    <li x-show="dialogo.estado === 'activo'">
                                                        <button class="dropdown-item text-warning" @click="archivarDialogo(dialogo.id)">
                                                            <i class="bi bi-archive me-2"></i>
                                                            Archivar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" @click="eliminarDialogo(dialogo.id)">
                                                            <i class="bi bi-trash me-2"></i>
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sin resultados -->
                    <div x-show="!cargando && dialogos.length === 0" class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-diagram-3 text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h6 class="text-muted mb-2">No hay diálogos</h6>
                        <p class="text-muted small mb-4">Crea tu primer diálogo para comenzar</p>
                        <a href="/dialogos/create" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Crear Primer Diálogo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <div class="row mt-4" x-show="paginacion && paginacion.last_page > 1">
        <div class="col-12">
            <nav aria-label="Paginación de diálogos">
                <ul class="pagination justify-content-center">
                    <li class="page-item" :class="{ disabled: paginacion.current_page === 1 }">
                        <button class="page-link" @click="cambiarPagina(paginacion.current_page - 1)" 
                                :disabled="paginacion.current_page === 1">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                    </li>
                    
                    <template x-for="pagina in paginasVisibles" :key="pagina">
                        <li class="page-item" :class="{ active: pagina === paginacion.current_page }">
                            <button class="page-link" @click="cambiarPagina(pagina)" x-text="pagina"></button>
                        </li>
                    </template>
                    
                    <li class="page-item" :class="{ disabled: paginacion.current_page === paginacion.last_page }">
                        <button class="page-link" @click="cambiarPagina(paginacion.current_page + 1)" 
                                :disabled="paginacion.current_page === paginacion.last_page">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
function dialogosIndex() {
    return {
        dialogos: [],
        paginacion: null,
        cargando: false,
        mostrarFiltros: false,
        filtros: {
            buscar: '',
            estado: '',
            publico: ''
        },

        get paginasVisibles() {
            if (!this.paginacion) return [];
            
            const current = this.paginacion.current_page;
            const last = this.paginacion.last_page;
            const pages = [];
            
            // Mostrar páginas alrededor de la actual
            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            
            return pages;
        },

        async init() {
            await this.cargarDialogos();
        },

        async cargarDialogos() {
            this.cargando = true;
            try {
                const params = new URLSearchParams();
                if (this.filtros.buscar) params.append('buscar', this.filtros.buscar);
                if (this.filtros.estado) params.append('estado', this.filtros.estado);
                if (this.filtros.publico) params.append('publico', this.filtros.publico);
                
                const response = await fetch(`/api/dialogos?${params.toString()}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.dialogos = data.data.data;
                    this.paginacion = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total
                    };
                } else {
                    this.mostrarMensaje('Error al cargar diálogos: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            } finally {
                this.cargando = false;
            }
        },

        async cambiarPagina(pagina) {
            if (pagina < 1 || pagina > this.paginacion.last_page) return;
            
            this.cargando = true;
            try {
                const params = new URLSearchParams();
                params.append('page', pagina);
                if (this.filtros.buscar) params.append('buscar', this.filtros.buscar);
                if (this.filtros.estado) params.append('estado', this.filtros.estado);
                if (this.filtros.publico) params.append('publico', this.filtros.publico);
                
                const response = await fetch(`/api/dialogos?${params.toString()}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.dialogos = data.data.data;
                    this.paginacion.current_page = data.data.current_page;
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            } finally {
                this.cargando = false;
            }
        },

        async activarDialogo(dialogoId) {
            try {
                const response = await fetch(`/api/dialogos/${dialogoId}/activar`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.mostrarMensaje('Diálogo activado exitosamente', 'success');
                    await this.cargarDialogos();
                } else {
                    this.mostrarMensaje('Error al activar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            }
        },

        async archivarDialogo(dialogoId) {
            if (!confirm('¿Estás seguro de que quieres archivar este diálogo?')) return;
            
            try {
                const response = await fetch(`/api/dialogos/${dialogoId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify({ estado: 'archivado' })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.mostrarMensaje('Diálogo archivado exitosamente', 'success');
                    await this.cargarDialogos();
                } else {
                    this.mostrarMensaje('Error al archivar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            }
        },

        async copiarDialogo(dialogoId) {
            const nombre = prompt('Nombre para la copia:');
            if (!nombre) return;
            
            try {
                const response = await fetch(`/api/dialogos/${dialogoId}/copiar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify({ nombre })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.mostrarMensaje('Diálogo copiado exitosamente', 'success');
                    await this.cargarDialogos();
                } else {
                    this.mostrarMensaje('Error al copiar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            }
        },

        async eliminarDialogo(dialogoId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este diálogo? Esta acción no se puede deshacer.')) return;
            
            try {
                const response = await fetch(`/api/dialogos/${dialogoId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.mostrarMensaje('Diálogo eliminado exitosamente', 'success');
                    await this.cargarDialogos();
                } else {
                    this.mostrarMensaje('Error al eliminar: ' + data.message, 'error');
                }
            } catch (error) {
                this.mostrarMensaje('Error de conexión', 'error');
            }
        },

        filtrar() {
            this.mostrarFiltros = !this.mostrarFiltros;
        },

        async aplicarFiltros() {
            await this.cargarDialogos();
        },

        formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        mostrarMensaje(mensaje, tipo) {
            // Implementar sistema de notificaciones
            console.log(`${tipo.toUpperCase()}: ${mensaje}`);
        }
    }
}
</script>
@endsection
