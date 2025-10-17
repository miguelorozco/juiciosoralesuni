<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Simulador de Juicios Orales</title>

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
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="text-center fade-in-up">
                    <!-- Logo principal -->
                    <div class="logo-container d-inline-block mb-4">
                        <i class="bi bi-balance logo-icon"></i>
                    </div>
                    
                    <!-- Título principal -->
                    <h1 class="display-4 fw-bold text-white mb-3">
                        Simulador de Juicios Orales
                    </h1>
                    
                    <!-- Subtítulo -->
                    <p class="lead text-white-50 mb-5">
                        Plataforma educativa para el aprendizaje del sistema judicial mexicano
                    </p>
                    
                    <!-- Características principales -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-white bg-opacity-10 backdrop-blur">
                                <div class="card-body text-center">
                                    <i class="bi bi-people-fill text-white mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title text-white">Múltiples Roles</h5>
                                    <p class="card-text text-white-50">
                                        Participa como juez, fiscal, defensor, testigo o parte
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-white bg-opacity-10 backdrop-blur">
                                <div class="card-body text-center">
                                    <i class="bi bi-diagram-3-fill text-white mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title text-white">Diálogos Ramificados</h5>
                                    <p class="card-text text-white-50">
                                        Sistema de diálogos interactivos con múltiples caminos
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-white bg-opacity-10 backdrop-blur">
                                <div class="card-body text-center">
                                    <i class="bi bi-graph-up text-white mb-3" style="font-size: 2rem;"></i>
                                    <h5 class="card-title text-white">Evaluación</h5>
                                    <p class="card-text text-white-50">
                                        Seguimiento detallado del progreso y decisiones
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón de acceso -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/login" class="btn btn-primary btn-lg px-5 py-3 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Acceder al Sistema
                        </a>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="mt-5">
                        <small class="text-white-50">
                            <i class="bi bi-shield-check me-1"></i>
                            Sistema seguro y confiable para instituciones educativas
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>