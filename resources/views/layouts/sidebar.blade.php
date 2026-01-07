<nav class="nav flex-column p-3">
    <!-- Dashboard -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/dashboard">
        <i class="bi bi-house-door me-3 fs-5"></i>
        <span class="fw-medium">Dashboard</span>
    </a>

    <!-- Sesiones -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('sesiones.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/sesiones">
        <i class="bi bi-calendar-event me-3 fs-5"></i>
        <span class="fw-medium">Sesiones</span>
    </a>

           @if(auth()->user()->tipo === 'admin' || auth()->user()->tipo === 'instructor')
           <!-- Diálogos v2 -->
           <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('dialogos-v2.*') ? 'bg-primary text-white' : 'text-dark' }}" 
              href="{{ route('dialogos-v2.index') }}">
               <i class="bi bi-diagram-3 me-3 fs-5"></i>
               <span class="fw-medium">Diálogos</span>
           </a>

    <!-- Roles -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('roles.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/roles">
        <i class="bi bi-people me-3 fs-5"></i>
        <span class="fw-medium">Roles</span>
    </a>
    @endif

    <!-- Estadísticas -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('estadisticas.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/estadisticas">
        <i class="bi bi-graph-up me-3 fs-5"></i>
        <span class="fw-medium">Estadísticas</span>
    </a>

    <!-- Separador -->
    <hr class="my-3">

    <!-- Configuración -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('configuracion.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/configuracion">
        <i class="bi bi-gear me-3 fs-5"></i>
        <span class="fw-medium">Configuración</span>
    </a>

    <!-- Perfil -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('profile.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/profile">
        <i class="bi bi-person me-3 fs-5"></i>
        <span class="fw-medium">Perfil</span>
    </a>

    @if(auth()->user()->tipo === 'admin')
    <!-- Administración -->
    <a class="nav-link d-flex align-items-center py-3 px-3 rounded-3 mb-2 {{ request()->routeIs('admin.*') ? 'bg-primary text-white' : 'text-dark' }}" 
       href="/admin">
        <i class="bi bi-shield-check me-3 fs-5"></i>
        <span class="fw-medium">Administración</span>
    </a>
    @endif

    <!-- Separador -->
    <hr class="my-3">

    <!-- Cerrar Sesión -->
    <form method="POST" action="/logout" class="d-inline w-100">
        @csrf
        <button type="submit" class="nav-link d-flex align-items-center py-3 px-3 rounded-3 w-100 border-0 bg-transparent text-danger">
            <i class="bi bi-box-arrow-right me-3 fs-5"></i>
            <span class="fw-medium">Cerrar Sesión</span>
        </button>
    </form>
</nav>

<style>
.nav-link {
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
}

.nav-link:hover {
    background-color: #f8f9fa !important;
    color: #0d6efd !important;
    transform: translateX(4px);
}

.nav-link.bg-primary:hover {
    background-color: #0b5ed7 !important;
    color: white !important;
}

.nav-link.bg-primary {
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
}

hr {
    border-color: #e9ecef;
    margin: 1rem 0;
}

/* Responsive */
@media (max-width: 991.98px) {
    .nav {
        padding: 1rem;
    }
    
    .nav-link {
        margin-bottom: 0.5rem;
    }
}
</style>