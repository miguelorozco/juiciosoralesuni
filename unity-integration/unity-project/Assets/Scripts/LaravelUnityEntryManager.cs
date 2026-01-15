using System.Collections;
using UnityEngine;
using UnityEngine.SceneManagement;
using JuiciosSimulator.API;
using JuiciosSimulator.Scene;
using JuiciosSimulator.Dialogue;
using System.Linq;
using JuiciosSimulator.Utils;

namespace JuiciosSimulator.Integration
{
    /// <summary>
    /// Manager para el flujo de entrada desde Laravel a Unity
    /// </summary>
    public class LaravelUnityEntryManager : MonoBehaviour
    {
        [Header("Configuración")]
        public string targetSceneName = "SalaPrincipal";
        public float connectionTimeout = 30f;
        public bool autoStartSession = true;

        [Header("Referencias")]
        public LaravelAPI laravelAPI;
        public GameInitializer gameInitializer;
        public DynamicSceneSetup dynamicSceneSetup;

        [Header("Estado")]
        public bool isEntryProcessActive = false;
        public bool hasSessionData = false;
        public bool hasDialogueData = false;

        // Datos almacenados
        private SessionData currentSessionData;
        private DialogueData currentDialogueData;

        // Eventos
        public static event System.Action<SessionData> OnSessionLoaded;
        public static event System.Action<DialogueData> OnDialogueLoaded;
        public static event System.Action<string> OnEntryError;
        public static event System.Action OnEntryComplete;

        // Singleton
        public static LaravelUnityEntryManager Instance { get; private set; }

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
                DontDestroyOnLoad(gameObject);
            }
            else
            {
                Destroy(gameObject);
            }
        }

        private void Start()
        {
            // Retrasar inicialización para evitar recursión durante la carga de Unity
            StartCoroutine(InitializeEntryManagerDelayed());
        }

        private System.Collections.IEnumerator InitializeEntryManagerDelayed()
        {
            // Esperar varios frames para que Unity termine de inicializar todos los scripts
            yield return null; // Frame 1
            yield return null; // Frame 2
            yield return null; // Frame 3
            
            InitializeEntryManager();
        }

        private void InitializeEntryManager()
        {
            // ESPERAR a que LaravelAPI esté completamente inicializado
            if (!LaravelAPI.IsInitialized)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] LaravelAPI aún no está inicializado. Reintentando en el siguiente frame...");
                StartCoroutine(WaitForLaravelAPIAndInitialize());
                return;
            }

            DebugLogger.LogPhase("LaravelUnityEntryManager", "Inicializando Entry Manager");
            
            // Obtener referencias (usar Instance en lugar de FindObjectOfType cuando sea posible)
            laravelAPI = LaravelAPI.Instance;
            
            // Retrasar búsquedas de otros objetos para evitar recursión
            StartCoroutine(FindReferencesDelayed());

            DebugLogger.LogPhase("LaravelUnityEntryManager", "Inicialización completada");
            Debug.Log("LaravelUnityEntryManager inicializado");
        }

        private IEnumerator WaitForLaravelAPIAndInitialize()
        {
            // Esperar hasta que LaravelAPI esté inicializado (máximo 5 segundos)
            float timeout = 5f;
            float elapsed = 0f;
            
            while (!LaravelAPI.IsInitialized && elapsed < timeout)
            {
                yield return null;
                elapsed += Time.deltaTime;
            }

            if (!LaravelAPI.IsInitialized)
            {
                Debug.LogError("[LaravelUnityEntryManager] Timeout esperando LaravelAPI. Inicializando de todas formas...");
            }

            // Ahora inicializar
            laravelAPI = LaravelAPI.Instance;
            StartCoroutine(FindReferencesDelayed());
        }

        private IEnumerator FindReferencesDelayed()
        {
            // Esperar varios frames adicionales antes de buscar objetos
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();

            if (gameInitializer == null)
            {
                gameInitializer = FindObjectOfType<GameInitializer>();
            }
            if (dynamicSceneSetup == null)
            {
                dynamicSceneSetup = FindObjectOfType<DynamicSceneSetup>();
            }

            DebugLogger.LogInfo("LaravelUnityEntryManager", "Referencias obtenidas", new {
                hasLaravelAPI = laravelAPI != null,
                hasGameInitializer = gameInitializer != null,
                hasDynamicSceneSetup = dynamicSceneSetup != null
            });

            // Suscribirse a eventos
            SubscribeToEvents();
        }

        private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones

        private void SubscribeToEvents()
        {
            // Prevenir múltiples suscripciones
            if (isSubscribedToEvents)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] Ya está suscrito a eventos. Ignorando suscripción duplicada.");
                return;
            }

            isSubscribedToEvents = true;

            if (laravelAPI != null)
            {
                LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;
                LaravelAPI.OnDialogueDataReceived += OnDialogueDataReceived;
                LaravelAPI.OnError += OnLaravelError;
            }
        }

        #region Flujo de Entrada Principal

        /// <summary>
        /// Iniciar proceso de entrada desde Laravel
        /// </summary>
        public void StartEntryProcess(string authToken = null)
        {
            if (isEntryProcessActive)
            {
                DebugLogger.LogWarning("LaravelUnityEntryManager", "Proceso de entrada ya está activo");
                Debug.LogWarning("Proceso de entrada ya está activo");
                return;
            }

            DebugLogger.LogPhase("LaravelUnityEntryManager", "INICIANDO proceso de entrada", new { hasToken = !string.IsNullOrEmpty(authToken) });
            Debug.Log("Iniciando proceso de entrada desde Laravel...");
            isEntryProcessActive = true;

            // Configurar token de autenticación si se proporciona
            if (!string.IsNullOrEmpty(authToken) && laravelAPI != null)
            {
                laravelAPI.authToken = authToken;
            }

            // Iniciar secuencia de entrada
            StartCoroutine(EntryProcessCoroutine());
        }

        private IEnumerator EntryProcessCoroutine()
        {
            // Paso 1: Verificar conexión con Laravel
            yield return StartCoroutine(VerifyLaravelConnection());

            if (!laravelAPI.isConnected)
            {
                OnEntryError?.Invoke("No se pudo conectar con Laravel");
                yield break;
            }

            // Paso 2: Obtener sesión activa
            yield return StartCoroutine(LoadActiveSession());

            if (!hasSessionData)
            {
                OnEntryError?.Invoke("No se pudo cargar la sesión activa");
                yield break;
            }

            // Paso 3: Cargar datos del diálogo
            yield return StartCoroutine(LoadDialogueData());

            if (!hasDialogueData)
            {
                OnEntryError?.Invoke("No se pudo cargar los datos del diálogo");
                yield break;
            }

            // Paso 4: Configurar escena
            yield return StartCoroutine(ConfigureScene());

            // Paso 5: Iniciar sesión
            if (autoStartSession)
            {
                yield return StartCoroutine(StartSession());
            }

            // Proceso completado
            isEntryProcessActive = false;
            OnEntryComplete?.Invoke();

            Debug.Log("Proceso de entrada completado exitosamente");
        }

        #endregion

        #region Pasos del Proceso de Entrada

        private IEnumerator VerifyLaravelConnection()
        {
            Debug.Log("Verificando conexión con Laravel...");

            float timeout = Time.time + connectionTimeout;

            while (Time.time < timeout)
            {
                if (laravelAPI != null && laravelAPI.isConnected)
                {
                    Debug.Log("Conexión con Laravel verificada");
                    yield break;
                }

                yield return new WaitForSeconds(0.5f);
            }

            Debug.LogError("Timeout al verificar conexión con Laravel");
        }

        private bool isLoadingSession = false; // Flag para prevenir múltiples cargas simultáneas

        private IEnumerator LoadActiveSession()
        {
            // Prevenir múltiples cargas simultáneas
            if (isLoadingSession)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] Ya se está cargando la sesión activa. Ignorando llamada duplicada.");
                yield break;
            }

            isLoadingSession = true;

            try
            {
                Debug.Log("Cargando sesión activa...");

                if (laravelAPI != null)
                {
                    laravelAPI.GetActiveSession();

                    // Esperar a que se reciba la sesión con timeout y límite de iteraciones
                    float timeout = Time.time + connectionTimeout;
                    int maxIterations = Mathf.RoundToInt(connectionTimeout / 0.1f); // Prevenir loops infinitos
                    int iterations = 0;

                    while (Time.time < timeout && !hasSessionData && iterations < maxIterations)
                    {
                        iterations++;
                        yield return new WaitForSeconds(0.1f);
                    }

                    if (hasSessionData)
                    {
                        Debug.Log("Sesión activa cargada exitosamente");
                    }
                    else
                    {
                        Debug.LogError($"Timeout al cargar sesión activa después de {iterations} iteraciones");
                        OnEntryError?.Invoke("Timeout al cargar sesión activa");
                    }
                }
            }
            finally
            {
                isLoadingSession = false;
            }
        }

        private bool isLoadingDialogue = false; // Flag para prevenir múltiples cargas simultáneas

        private IEnumerator LoadDialogueData()
        {
            // Prevenir múltiples cargas simultáneas
            if (isLoadingDialogue)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] Ya se están cargando los datos del diálogo. Ignorando llamada duplicada.");
                yield break;
            }

            isLoadingDialogue = true;

            try
            {
                Debug.Log("Cargando datos del diálogo...");

                if (laravelAPI != null && hasSessionData)
                {
                    // Obtener ID de la sesión desde los datos cargados
                    var sessionId = laravelAPI.currentSessionData?.session.id ?? 0;

                    if (sessionId > 0)
                    {
                        laravelAPI.GetSessionDialogue(sessionId);

                        // Esperar a que se reciban los datos del diálogo con timeout y límite de iteraciones
                        float timeout = Time.time + connectionTimeout;
                        int maxIterations = Mathf.RoundToInt(connectionTimeout / 0.1f); // Prevenir loops infinitos
                        int iterations = 0;

                        while (Time.time < timeout && !hasDialogueData && iterations < maxIterations)
                        {
                            iterations++;
                            yield return new WaitForSeconds(0.1f);
                        }

                        if (hasDialogueData)
                        {
                            Debug.Log("Datos del diálogo cargados exitosamente");
                        }
                        else
                        {
                            Debug.LogError($"Timeout al cargar datos del diálogo después de {iterations} iteraciones");
                            OnEntryError?.Invoke("Timeout al cargar datos del diálogo");
                        }
                    }
                    else
                    {
                        Debug.LogError("ID de sesión inválido");
                        OnEntryError?.Invoke("ID de sesión inválido");
                    }
                }
            }
            finally
            {
                isLoadingDialogue = false;
            }
        }

        private bool isConfiguringScene = false; // Flag para prevenir múltiples configuraciones de escena

        private IEnumerator ConfigureScene()
        {
            // Prevenir múltiples configuraciones simultáneas
            if (isConfiguringScene)
            {
                Debug.LogWarning("[LaravelUnityEntryManager] ConfigureScene ya está en progreso. Ignorando llamada duplicada.");
                yield break;
            }

            isConfiguringScene = true;

            try
            {
                Debug.Log("Configurando escena...");

                // Obtener nombre de escena desde configuración de sesión si está disponible
                string sceneToLoad = targetSceneName;
                if (laravelAPI != null && laravelAPI.currentSessionData != null)
                {
                    var config = laravelAPI.currentSessionData.session?.configuracion;
                    if (config != null && config.ContainsKey("unity_scene"))
                    {
                        sceneToLoad = config["unity_scene"].ToString();
                        Debug.Log($"Escena obtenida de configuración de sesión: {sceneToLoad}");
                    }
                }

                // Cargar escena objetivo si no está cargada
                if (SceneManager.GetActiveScene().name != sceneToLoad)
                {
                    DebugLogger.LogPhase("LaravelUnityEntryManager", $"CARGANDO ESCENA: {sceneToLoad}", new { 
                        currentScene = SceneManager.GetActiveScene().name,
                        targetScene = sceneToLoad
                    });
                    Debug.Log($"Cargando escena: {sceneToLoad}");
                    
                    // Desuscribirse temporalmente de eventos antes de cargar escena para evitar múltiples suscripciones
                    if (isSubscribedToEvents && laravelAPI != null)
                    {
                        DebugLogger.LogPhase("LaravelUnityEntryManager", "Desuscribiéndose de eventos antes de cargar escena");
                        LaravelAPI.OnActiveSessionReceived -= OnActiveSessionReceived;
                        LaravelAPI.OnDialogueDataReceived -= OnDialogueDataReceived;
                        isSubscribedToEvents = false;
                    }

                    SceneManager.LoadScene(sceneToLoad);

                    // Esperar a que la escena se cargue completamente
                    DebugLogger.LogPhase("LaravelUnityEntryManager", "Esperando carga de escena...");
                    yield return new WaitUntil(() => SceneManager.GetActiveScene().name == sceneToLoad);
                    
                    DebugLogger.LogPhase("LaravelUnityEntryManager", $"Escena cargada: {SceneManager.GetActiveScene().name}");
                    
                    // Esperar varios frames para que todos los Start() se ejecuten
                    yield return new WaitForEndOfFrame();
                    yield return new WaitForEndOfFrame();

                    // Re-suscribirse a eventos después de cargar la escena
                    DebugLogger.LogPhase("LaravelUnityEntryManager", "Re-suscribiéndose a eventos después de cargar escena");
                    SubscribeToEvents();
                }
                else
                {
                    DebugLogger.LogPhase("LaravelUnityEntryManager", $"Escena ya está cargada: {SceneManager.GetActiveScene().name}");
                }

                // Reconfigurar referencias después de cargar la escena
                ReconfigureReferences();

                // Esperar un frame adicional para asegurar que todo esté inicializado
                yield return new WaitForEndOfFrame();

                Debug.Log("Escena configurada exitosamente");
            }
            finally
            {
                isConfiguringScene = false;
            }
        }

        private IEnumerator StartSession()
        {
            Debug.Log("Iniciando sesión...");

            // Configurar GameInitializer
            if (gameInitializer != null)
            {
                // Los datos ya están cargados, solo necesitamos configurar la UI
                gameInitializer.SetupDialogueUI();
            }

            // Iniciar secuencia de diálogos
            var dialogueManager = JuiciosSimulator.Dialogue.DialogueManager.Instance;
            if (dialogueManager != null)
            {
                dialogueManager.StartDialogueSequence();
            }

            Debug.Log("Sesión iniciada exitosamente");
            yield return null;
        }

        #endregion

        #region Event Handlers

        private bool hasProcessedSession = false; // Flag para prevenir procesamiento múltiple
        private bool hasProcessedDialogue = false; // Flag para prevenir procesamiento múltiple

        private void OnActiveSessionReceived(SessionData sessionData)
        {
            DebugLogger.LogEvent("LaravelUnityEntryManager.OnActiveSessionReceived", $"Sesión: {sessionData?.session?.nombre ?? "N/A"}", new {
                sessionId = sessionData?.session?.id,
                hasProcessedSession,
                hasSessionData
            });

            // Prevenir procesamiento múltiple de la misma sesión
            if (hasProcessedSession && hasSessionData && 
                currentSessionData != null && sessionData != null &&
                sessionData.session != null && currentSessionData.session != null &&
                sessionData.session.id == currentSessionData.session.id)
            {
                DebugLogger.LogWarning("LaravelUnityEntryManager", $"Sesión {sessionData.session.id} ya procesada. Ignorando evento duplicado.");
                Debug.LogWarning("[LaravelUnityEntryManager] Sesión ya procesada. Ignorando evento duplicado.");
                return;
            }

            hasProcessedSession = true;
            hasSessionData = true;
            currentSessionData = sessionData; // Almacenar la sesión recibida
            
            DebugLogger.LogEventInvocation("OnSessionLoaded", OnSessionLoaded?.GetInvocationList()?.Length ?? 0);
            OnSessionLoaded?.Invoke(sessionData);

            DebugLogger.LogPhase("LaravelUnityEntryManager", "Sesión procesada exitosamente", new { sessionName = sessionData.session.nombre });
            Debug.Log($"Sesión recibida: {sessionData.session.nombre}");
        }

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
            DebugLogger.LogEvent("LaravelUnityEntryManager.OnDialogueDataReceived", $"Diálogo: {dialogueData?.dialogue?.nombre ?? "N/A"}", new {
                dialogueId = dialogueData?.dialogue?.id,
                hasProcessedDialogue,
                hasDialogueData
            });

            // Prevenir procesamiento múltiple del mismo diálogo
            if (hasProcessedDialogue && hasDialogueData && 
                currentDialogueData != null && dialogueData != null &&
                dialogueData.dialogue != null && currentDialogueData.dialogue != null &&
                dialogueData.dialogue.id == currentDialogueData.dialogue.id)
            {
                DebugLogger.LogWarning("LaravelUnityEntryManager", $"Diálogo {dialogueData.dialogue.id} ya procesado. Ignorando evento duplicado.");
                Debug.LogWarning("[LaravelUnityEntryManager] Diálogo ya procesado. Ignorando evento duplicado.");
                return;
            }

            hasProcessedDialogue = true;
            hasDialogueData = true;
            currentDialogueData = dialogueData; // Almacenar el diálogo recibido
            
            DebugLogger.LogEventInvocation("OnDialogueLoaded", OnDialogueLoaded?.GetInvocationList()?.Length ?? 0);
            OnDialogueLoaded?.Invoke(dialogueData);

            DebugLogger.LogPhase("LaravelUnityEntryManager", "Diálogo procesado exitosamente", new { dialogueName = dialogueData.dialogue.nombre });
            Debug.Log($"Diálogo recibido: {dialogueData.dialogue.nombre}");
        }

        private void OnLaravelError(string error)
        {
            Debug.LogError($"Error de Laravel: {error}");
            OnEntryError?.Invoke(error);
        }

        #endregion

        #region Utilidades

        private void ReconfigureReferences()
        {
            // Reconfigurar referencias después de cargar la escena
            laravelAPI = LaravelAPI.Instance;
            gameInitializer = FindObjectOfType<GameInitializer>();
            dynamicSceneSetup = FindObjectOfType<DynamicSceneSetup>();

            Debug.Log("Referencias reconfiguradas");
            
            // NO llamar SetupScene() aquí - DynamicSceneSetup se configura automáticamente
            // cuando recibe los eventos OnDialogueDataReceived y OnActiveSessionReceived
            // Si forzamos la configuración aquí, puede causar recursión infinita
        }

        /// <summary>
        /// Obtener estado del proceso de entrada
        /// </summary>
        public string GetEntryStatus()
        {
            string status = "Estado del Proceso de Entrada:\n";
            status += $"Proceso activo: {isEntryProcessActive}\n";
            status += $"Sesión cargada: {hasSessionData}\n";
            status += $"Diálogo cargado: {hasDialogueData}\n";
            status += $"Laravel conectado: {(laravelAPI?.isConnected ?? false)}\n";
            status += $"Escena actual: {SceneManager.GetActiveScene().name}\n";

            return status;
        }

        #endregion

        #region Métodos Públicos

        /// <summary>
        /// Reiniciar proceso de entrada
        /// </summary>
        public void RestartEntryProcess()
        {
            Debug.Log("Reiniciando proceso de entrada...");

            // Resetear estado
            isEntryProcessActive = false;
            hasSessionData = false;
            hasDialogueData = false;

            // Reiniciar proceso
            StartEntryProcess();
        }

        /// <summary>
        /// Entrada rápida con token
        /// </summary>
        public void QuickEntry(string authToken)
        {
            Debug.Log("Iniciando entrada rápida...");
            StartEntryProcess(authToken);
        }

        #endregion

        private void OnDestroy()
        {
            // Desuscribirse de eventos
            if (laravelAPI != null)
            {
                LaravelAPI.OnActiveSessionReceived -= OnActiveSessionReceived;
                LaravelAPI.OnDialogueDataReceived -= OnDialogueDataReceived;
                LaravelAPI.OnError -= OnLaravelError;
            }
        }
    }
}
