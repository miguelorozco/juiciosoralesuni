<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>JuiciosSimulator - Sala Principal</title>
    <link rel="shortcut icon" href="{{ asset('unity-build/TemplateData/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('unity-build/TemplateData/style.css') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Simulador de Juicios Orales - Sala Principal">
    
    <!-- Meta tags para Unity WebGL -->
    <meta name="unity-webgl" content="true">
    <meta name="unity-platform" content="WebGL">
    
    <!-- Estilos personalizados -->
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #1a1a1a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        #unity-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        #unity-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        
        #unity-loading-bar {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: none;
        }
        
        #unity-logo {
            width: 154px;
            height: 130px;
            background: url('{{ asset('unity-build/TemplateData/logo.png') }}') no-repeat center / contain;
        }
        
        #unity-progress-bar-empty {
            width: 141px;
            height: 18px;
            margin-top: 10px;
            background: url('{{ asset('unity-build/TemplateData/progress-bar-empty-dark.png') }}') no-repeat center / contain;
        }
        
        #unity-progress-bar-full {
            width: 0%;
            height: 18px;
            background: url('{{ asset('unity-build/TemplateData/progress-bar-full-dark.png') }}') no-repeat center / contain;
        }
        
        #unity-footer {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            color: #888;
            font-size: 12px;
        }
        
        .loading-text {
            color: #fff;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ff6b6b;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div id="unity-container">
        <canvas id="unity-canvas" tabindex="-1"></canvas>
        
        <div id="unity-loading-bar">
            <div id="unity-logo"></div>
            <div id="unity-progress-bar-empty">
                <div id="unity-progress-bar-full"></div>
            </div>
            <div class="loading-text">Cargando JuiciosSimulator...</div>
        </div>
        
        <div id="unity-footer">
            <p>JuiciosSimulator v1.0.0 - Simulador de Juicios Orales</p>
        </div>
        
        <div class="error-message" id="error-message">
            <h3>Error al cargar el juego</h3>
            <p id="error-text">Por favor, recarga la página e intenta nuevamente.</p>
        </div>
    </div>

    <!-- Scripts de Unity -->
    <script src="{{ asset('unity-build/Build/JuiciosSimulator.loader.js') }}"></script>
    <script>
        // Configuración de Unity
        var container = document.querySelector("#unity-container");
        var canvas = document.querySelector("#unity-canvas");
        var loadingBar = document.querySelector("#unity-loading-bar");
        var progressBarFull = document.querySelector("#unity-progress-bar-full");
        var fullscreenButton = document.querySelector("#unity-fullscreen-button");
        var errorMessage = document.querySelector("#error-message");
        var errorText = document.querySelector("#error-text");

        // Mostrar loading
        loadingBar.style.display = "block";

        // Configuración del módulo Unity
        var buildUrl = "{{ asset('unity-build/Build') }}";
        var config = {
            dataUrl: buildUrl + "/JuiciosSimulator.data",
            frameworkUrl: buildUrl + "/JuiciosSimulator.framework.js",
            codeUrl: buildUrl + "/JuiciosSimulator.wasm",
            streamingAssetsUrl: "StreamingAssets",
            companyName: "JuiciosSimulator",
            productName: "JuiciosSimulator",
            productVersion: "1.0.0",
        };

        // Configurar canvas
        config.canvas = canvas;

        // Configurar eventos de progreso
        config.onProgress = function (progress) {
            progressBarFull.style.width = 100 * progress + "%";
        };

        // Configurar eventos de error
        config.onError = function (message) {
            console.error("Error de Unity:", message);
            loadingBar.style.display = "none";
            errorMessage.style.display = "block";
            errorText.textContent = message;
        };

        // Configurar eventos de éxito
        config.onSuccess = function () {
            console.log("Unity cargado exitosamente");
            loadingBar.style.display = "none";
            
            // Configurar comunicación con Laravel
            setupLaravelCommunication();
        };

        // Crear instancia de Unity
        var unityInstance = createUnityInstance(canvas, config, function (progress) {
            config.onProgress(progress);
        }).then(function (unityInstance) {
            config.onSuccess();
            return unityInstance;
        }).catch(function (message) {
            config.onError(message);
        });

        // Configurar comunicación con Laravel
        function setupLaravelCommunication() {
            // Función para enviar datos a Unity
            window.sendToUnity = function(data) {
                if (unityInstance) {
                    unityInstance.SendMessage('LaravelUnityEntryManager', 'ReceiveLaravelData', JSON.stringify(data));
                }
            };

            // Función para recibir datos de Unity
            window.receiveFromUnity = function(data) {
                console.log("Datos recibidos de Unity:", data);
                // Aquí puedes procesar los datos recibidos de Unity
            };

            // Configurar comunicación bidireccional
            if (unityInstance) {
                unityInstance.Module.onRuntimeInitialized = function() {
                    console.log("Runtime de Unity inicializado");
                    
                    // Enviar información de la sesión si está disponible
                    @if(isset($sessionData))
                        window.sendToUnity(@json($sessionData));
                    @endif
                };
            }
        }

        // Manejar errores de WebGL
        window.addEventListener('error', function(e) {
            console.error("Error global:", e);
            if (e.message.includes('WebGL') || e.message.includes('Unity')) {
                errorMessage.style.display = "block";
                errorText.textContent = "Error de WebGL: " + e.message;
                loadingBar.style.display = "none";
            }
        });

        // Manejar resize de ventana
        window.addEventListener('resize', function() {
            if (unityInstance) {
                unityInstance.Module.setCanvasSize(window.innerWidth, window.innerHeight);
            }
        });
    </script>
</body>
</html>
