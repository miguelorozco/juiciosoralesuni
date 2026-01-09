<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>JuiciosSimulator - Sala Principal</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <style>
        /* Estilos básicos para Unity WebGL */
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
            background: #007bff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        #unity-logo::before {
            content: "⚖️";
            font-size: 48px;
        }
        
        #unity-progress-bar-empty {
            width: 141px;
            height: 18px;
            margin-top: 10px;
            background: #333;
            border-radius: 9px;
        }
        
        #unity-progress-bar-full {
            width: 0%;
            height: 18px;
            background: #007bff;
            border-radius: 9px;
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
            background: #007bff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        #unity-logo::before {
            content: "⚖️";
            font-size: 48px;
        }
        
        #unity-progress-bar-empty {
            width: 141px;
            height: 18px;
            margin-top: 10px;
            background: #333;
            border-radius: 9px;
        }
        
        #unity-progress-bar-full {
            width: 0%;
            height: 18px;
            background: #007bff;
            border-radius: 9px;
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
            <button id="retry-button" onclick="retryUnityLoad()" style="margin-top: 15px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Reintentar
            </button>
        </div>
    </div>

    <!-- Scripts de Unity -->
    <script src="{{ route('unity.assets', 'Build/juicio.loader.js') }}"></script>
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
        // Usar ruta de Laravel para archivos con headers correctos
        var buildUrl = "{{ route('unity.assets', 'Build') }}";
        var streamingAssetsUrl = "{{ route('unity.assets', 'StreamingAssets') }}";
        var config = {
            dataUrl: buildUrl + "/juicio.data.br",
            frameworkUrl: buildUrl + "/juicio.framework.js.br",
            codeUrl: buildUrl + "/juicio.wasm.br",
            streamingAssetsUrl: streamingAssetsUrl,
            companyName: "JuiciosSimulator",
            productName: "JuiciosSimulator",
            productVersion: "1.0.0",
            // Configuraciones adicionales para prevenir errores
            matchWebGLToCanvasSize: true,
            devicePixelRatio: window.devicePixelRatio || 1,
            // Prevenir múltiples inicializaciones
            preserveDrawingBuffer: false,
            powerPreference: "default",
            // Configuraciones para prevenir errores de Blitter y Photon
            enableWebGLMemoryManager: true,
            enableWebGLMemoryManagerJS: true,
            // Deshabilitar características problemáticas temporalmente
            disableWebGLMemoryManager: false,
            // Configuración de timeout más largo
            startupTimeout: 60000
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
            isInitializing = false;
        };

        // Configurar eventos de éxito
        config.onSuccess = function () {
            console.log("Unity cargado exitosamente");
            loadingBar.style.display = "none";
            
            // Configurar comunicación con Laravel
            setupLaravelCommunication();
        };

        // Variable global para la instancia de Unity
        var unityInstance = null;
        var isInitializing = false;
        var errorHandlingConfig = null;

        // Cargar configuración de manejo de errores
        function loadErrorHandlingConfig() {
            fetch('{{ route('unity.assets', 'StreamingAssets/unity-error-handling.json') }}')
                .then(response => response.json())
                .then(config => {
                    errorHandlingConfig = config;
                    console.log("Configuración de manejo de errores cargada:", config);
                })
                .catch(error => {
                    console.warn("No se pudo cargar la configuración de errores, usando configuración por defecto");
                    errorHandlingConfig = {
                        errorHandling: {
                            suppressBlitterErrors: true,
                            suppressFormatExceptions: true,
                            suppressPhotonErrors: true,
                            suppressServerCertificateErrors: true,
                            logErrorsToConsole: true,
                            showErrorsToUser: false
                        }
                    };
                });
        }

        // Cargar configuración al inicio
        loadErrorHandlingConfig();

        // Función para limpiar recursos antes de reinicializar
        function cleanupUnityInstance() {
            if (unityInstance) {
                try {
                    if (unityInstance.Module && unityInstance.Module.destroy) {
                        unityInstance.Module.destroy();
                    }
                } catch (error) {
                    console.warn("Error al limpiar instancia de Unity:", error);
                }
                unityInstance = null;
            }
        }

        // Crear instancia de Unity
        function initializeUnity() {
            if (isInitializing) {
                console.warn("Unity ya se está inicializando");
                return;
            }
            
            isInitializing = true;
            cleanupUnityInstance();

            createUnityInstance(canvas, config, function (progress) {
                config.onProgress(progress);
            }).then(function (instance) {
                unityInstance = instance;
                isInitializing = false;
                config.onSuccess();
                setupLaravelCommunication();
            }).catch(function (message) {
                isInitializing = false;
                config.onError(message);
            });
        }

        // Función de reintento mejorada
        window.retryUnityLoad = function() {
            console.log("Reintentando carga de Unity...");
            errorMessage.style.display = "none";
            loadingBar.style.display = "block";
            progressBarFull.style.width = "0%";
            
            // Limpiar completamente antes de reintentar
            cleanupUnityInstance();
            
            // Esperar un poco antes de reinicializar
            setTimeout(function() {
                initializeUnity();
            }, 1000);
        };

        // Inicializar Unity
        initializeUnity();

        // Configurar comunicación con Laravel
        function setupLaravelCommunication() {
            // Función para enviar datos a Unity
            window.sendToUnity = function(data) {
                if (unityInstance && unityInstance.SendMessage) {
                    try {
                        unityInstance.SendMessage('LaravelUnityEntryManager', 'ReceiveLaravelData', JSON.stringify(data));
                    } catch (error) {
                        console.error("Error enviando datos a Unity:", error);
                    }
                }
            };

            // Función para recibir datos de Unity
            window.receiveFromUnity = function(data) {
                console.log("Datos recibidos de Unity:", data);
                // Aquí puedes procesar los datos recibidos de Unity
            };

            // Configurar comunicación bidireccional cuando la instancia esté lista
            if (unityInstance && unityInstance.Module) {
                unityInstance.Module.onRuntimeInitialized = function() {
                    console.log("Runtime de Unity inicializado");
                    
                    // Enviar información de la sesión si está disponible
                    @if(isset($sessionData))
                        setTimeout(function() {
                            window.sendToUnity(@json($sessionData));
                        }, 1000); // Esperar un poco para asegurar que Unity esté completamente listo
                    @endif
                };
            } else {
                console.warn("Unity instance no está disponible para configuración de comunicación");
            }
        }

        // Manejar errores de WebGL
        window.addEventListener('error', function(e) {
            console.error("Error global:", e);
            
            // Usar configuración de manejo de errores
            var config = errorHandlingConfig ? errorHandlingConfig.errorHandling : {
                suppressBlitterErrors: true,
                suppressFormatExceptions: true,
                suppressPhotonErrors: true,
                suppressServerCertificateErrors: true,
                logErrorsToConsole: true,
                showErrorsToUser: false
            };
            
            // Detectar errores específicos de Unity
            if (e.message.includes('Blitter is already initialized')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de Blitter detectado - Unity se está reinicializando");
                }
                if (!config.suppressBlitterErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de renderizado: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('FormatException') || e.message.includes('StringBuilder')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de formato detectado - posible problema de datos");
                }
                if (!config.suppressFormatExceptions && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de datos: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('PhotonNetwork') || e.message.includes('Photon')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de Photon detectado - problemas de red");
                }
                if (!config.suppressPhotonErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de red: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            if (e.message.includes('ServerCertificate')) {
                if (config.logErrorsToConsole) {
                    console.warn("Error de ServerCertificate detectado - script faltante");
                }
                if (!config.suppressServerCertificateErrors && config.showErrorsToUser) {
                    errorMessage.style.display = "block";
                    errorText.textContent = "Error de certificado: " + e.message;
                    loadingBar.style.display = "none";
                }
                return;
            }
            
            // Solo mostrar errores críticos al usuario
            if (e.message.includes('WebGL') || e.message.includes('Unity') || e.message.includes('unityInstance')) {
                errorMessage.style.display = "block";
                errorText.textContent = "Error de WebGL: " + e.message;
                loadingBar.style.display = "none";
            }
        });

        // Manejar errores no capturados
        window.addEventListener('unhandledrejection', function(e) {
            console.error("Promise rechazada:", e.reason);
            if (e.reason && e.reason.toString().includes('Unity')) {
                errorMessage.style.display = "block";
                errorText.textContent = "Error de Unity: " + e.reason.toString();
                loadingBar.style.display = "none";
            }
        });

        // Manejar resize de ventana
        window.addEventListener('resize', function() {
            if (unityInstance && unityInstance.Module && unityInstance.Module.setCanvasSize) {
                try {
                    unityInstance.Module.setCanvasSize(window.innerWidth, window.innerHeight);
                } catch (error) {
                    console.warn("Error al redimensionar canvas:", error);
                }
            }
        });
    </script>
</body>
</html>
