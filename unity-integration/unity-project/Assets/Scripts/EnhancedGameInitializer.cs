using UnityEngine;
using JuiciosSimulator.API;
using JuiciosSimulator.Core;
using JuiciosSimulator.Config;

namespace JuiciosSimulator
{
    /// <summary>
    /// Inicializador principal del juego - Versión mejorada
    /// Integra con el nuevo sistema de gestión de sesiones y diálogos ramificados
    /// </summary>
    public class EnhancedGameInitializer : MonoBehaviour
    {
        [Header("Configuración")]
        public UnityConfig config;

        [Header("Sistema Mejorado")]
        public EnhancedGameManager gameManager;

        [Header("Configuración de Testing")]
        public string testEmail = "alumno@example.com";
        public string testPassword = "password";
        public bool autoLogin = true;
        public bool autoJoinSession = false;
        public string testSessionCode = "";

        [Header("Debug")]
        public bool showDebugPanel = true;
        public bool showLegacyUI = false;

        // Referencias legacy para compatibilidad
        private LaravelAPI laravelAPI;
        private DialogoUI dialogoUI;
        private UnityLaravelIntegration integration;

        // Estado
        private bool isInitialized = false;

        // Eventos
        public static event System.Action<bool> OnGameInitialized;
        public static event System.Action<string> OnInitializationError;

        private void Awake()
        {
            // Aplicar configuración
            if (config != null)
            {
                config.ApplyConfig();
            }

            // Configurar logs
            if (config != null && !config.showDebugLogs)
            {
                Debug.unityLogger.logEnabled = false;
            }
        }

        private void Start()
        {
            InitializeGame();
        }

        private void InitializeGame()
        {
            Debug.Log("=== INICIALIZANDO JUEGO MEJORADO ===");

            // Aplicar configuración
            if (config != null)
            {
                config.ApplyConfig();
            }

            // Configurar componentes
            SetupComponents();

            // Suscribirse a eventos
            SubscribeToEvents();

            // Inicializar sistema mejorado
            if (gameManager != null)
            {
                StartCoroutine(WaitForGameManagerInitialization());
            }
            else
            {
                // Fallback al sistema legacy
                InitializeLegacySystem();
            }
        }

        private void SetupComponents()
        {
            // Buscar GameManager si no está asignado
            if (gameManager == null)
            {
                gameManager = FindObjectOfType<EnhancedGameManager>();
            }

            // Configurar referencias legacy para compatibilidad
            laravelAPI = FindObjectOfType<LaravelAPI>();
            dialogoUI = FindObjectOfType<DialogoUI>();
            integration = FindObjectOfType<UnityLaravelIntegration>();
        }

        private void SubscribeToEvents()
        {
            // Eventos del sistema mejorado
            if (gameManager != null)
            {
                EnhancedGameManager.OnInitializationComplete += OnSystemInitialized;
                EnhancedGameManager.OnSystemError += OnSystemError;
            }

            // Eventos legacy
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnError += OnLaravelError;
        }

        private System.Collections.IEnumerator WaitForGameManagerInitialization()
        {
            Debug.Log("Esperando inicialización del sistema mejorado...");

            while (!gameManager.IsInitialized())
            {
                yield return new WaitForSeconds(0.5f);
            }

            Debug.Log("Sistema mejorado inicializado");
            OnSystemInitialized(true);
        }

        private void OnSystemInitialized(bool success)
        {
            if (success)
            {
                isInitialized = true;
                OnGameInitialized?.Invoke(true);

                if (autoLogin)
                {
                    StartCoroutine(AutoLoginCoroutine());
                }
            }
            else
            {
                OnInitializationError?.Invoke("Error en la inicialización del sistema");
            }
        }

        private void OnSystemError(string error)
        {
            Debug.LogError($"Error del sistema: {error}");
            OnInitializationError?.Invoke(error);
        }

        private void InitializeLegacySystem()
        {
            Debug.Log("Inicializando sistema legacy...");

            // Configurar componentes legacy
            if (laravelAPI == null)
            {
                laravelAPI = new GameObject("LaravelAPI").AddComponent<LaravelAPI>();
            }

            if (dialogoUI == null)
            {
                dialogoUI = new GameObject("DialogoUI").AddComponent<DialogoUI>();
            }

            if (integration == null)
            {
                integration = new GameObject("UnityLaravelIntegration").AddComponent<UnityLaravelIntegration>();
            }

            // Iniciar proceso de conexión legacy
            StartConnectionProcess();
        }

        private void StartConnectionProcess()
        {
            if (autoLogin)
            {
                StartCoroutine(AutoLoginCoroutine());
            }
        }

        private System.Collections.IEnumerator AutoLoginCoroutine()
        {
            // Esperar a que LaravelAPI esté listo
            while (laravelAPI == null)
            {
                yield return new WaitForSeconds(0.1f);
            }

            // Intentar login automático
            yield return new WaitForSeconds(1f);
            laravelAPI.Login(testEmail, testPassword);
        }

        private void OnUserLoggedIn(UserData user)
        {
            Debug.Log($"Usuario logueado: {user.name}");

            // Si estamos usando el sistema mejorado, unirse a sesión automáticamente
            if (gameManager != null && autoJoinSession && !string.IsNullOrEmpty(testSessionCode))
            {
                gameManager.JoinSession(testSessionCode);
            }
            else if (dialogoUI != null)
            {
                // Sistema legacy
                dialogoUI.SetSesionId(1);
            }
        }

        private void OnLaravelError(string error)
        {
            Debug.LogError($"Error de Laravel: {error}");
            OnInitializationError?.Invoke(error);
        }

        public void RestartGame()
        {
            Debug.Log("Reiniciando juego...");

            // Limpiar estado
            if (gameManager != null)
            {
                gameManager.ShutdownSystem();
            }

            if (laravelAPI != null)
            {
                laravelAPI.Logout();
            }

            // Reiniciar
            isInitialized = false;
            InitializeGame();
        }

        public void JoinSession(string sessionCode)
        {
            if (gameManager != null)
            {
                gameManager.JoinSession(sessionCode);
            }
            else
            {
                Debug.LogWarning("Sistema mejorado no disponible, usando sistema legacy");
            }
        }

        public void LeaveSession()
        {
            if (gameManager != null)
            {
                gameManager.LeaveSession();
            }
        }

        public string GetGameStatus()
        {
            string status = "=== ESTADO DEL JUEGO ===\n";

            if (gameManager != null)
            {
                status += $"Sistema: Mejorado\n";
                status += $"Estado: {gameManager.GetCurrentState()}\n";
                status += $"Inicializado: {gameManager.IsInitialized()}\n";
                status += $"En Sesión: {gameManager.IsInSession()}\n";
            }
            else
            {
                status += $"Sistema: Legacy\n";
                status += $"Laravel Conectado: {laravelAPI?.isConnected ?? false}\n";
                status += $"Usuario: {laravelAPI?.currentUser?.name ?? "No logueado"}\n";
            }

            return status;
        }

        private void OnGUI()
        {
            if (!showDebugPanel) return;

            GUILayout.BeginArea(new Rect(10, 10, 400, 300));
            GUILayout.Label("=== ENHANCED GAME INITIALIZER DEBUG ===");
            GUILayout.Label(GetGameStatus());

            GUILayout.Space(10);

            if (GUILayout.Button("Reiniciar Juego"))
            {
                RestartGame();
            }

            if (GUILayout.Button("Refresh System"))
            {
                if (gameManager != null)
                {
                    gameManager.RefreshSystem();
                }
            }

            GUILayout.Space(10);

            // UI para testing de sesiones
            GUILayout.Label("=== TESTING ===");
            if (GUILayout.Button("Auto Login"))
            {
                StartCoroutine(AutoLoginCoroutine());
            }

            if (GUILayout.Button("Join Test Session"))
            {
                if (!string.IsNullOrEmpty(testSessionCode))
                {
                    JoinSession(testSessionCode);
                }
            }

            GUILayout.Space(10);

            // Mostrar UI legacy si está habilitada
            if (showLegacyUI && dialogoUI != null)
            {
                GUILayout.Label("=== LEGACY UI ===");
                if (GUILayout.Button("Show Legacy Dialog UI"))
                {
                    dialogoUI.gameObject.SetActive(true);
                }
            }

            GUILayout.EndArea();
        }

        private void OnDestroy()
        {
            // Desuscribirse de eventos
            if (gameManager != null)
            {
                EnhancedGameManager.OnInitializationComplete -= OnSystemInitialized;
                EnhancedGameManager.OnSystemError -= OnSystemError;
            }

            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnError -= OnLaravelError;
        }
    }
}
