using System.Collections;
using UnityEngine;
using UnityEngine.SceneManagement;
using JuiciosSimulator.API;
using JuiciosSimulator.Scene;
using JuiciosSimulator.Dialogue;

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
        public OXXOSceneSetup oxxoSetup;

        [Header("Estado")]
        public bool isEntryProcessActive = false;
        public bool hasSessionData = false;
        public bool hasDialogueData = false;

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
            InitializeEntryManager();
        }

        private void InitializeEntryManager()
        {
            // Obtener referencias
            laravelAPI = LaravelAPI.Instance;
            gameInitializer = FindObjectOfType<GameInitializer>();
            oxxoSetup = FindObjectOfType<OXXOSceneSetup>();

            // Suscribirse a eventos
            SubscribeToEvents();

            Debug.Log("LaravelUnityEntryManager inicializado");
        }

        private void SubscribeToEvents()
        {
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
                Debug.LogWarning("Proceso de entrada ya está activo");
                return;
            }

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

        private IEnumerator LoadActiveSession()
        {
            Debug.Log("Cargando sesión activa...");

            if (laravelAPI != null)
            {
                laravelAPI.GetActiveSession();

                // Esperar a que se reciba la sesión
                float timeout = Time.time + connectionTimeout;

                while (Time.time < timeout && !hasSessionData)
                {
                    yield return new WaitForSeconds(0.1f);
                }

                if (hasSessionData)
                {
                    Debug.Log("Sesión activa cargada exitosamente");
                }
                else
                {
                    Debug.LogError("Timeout al cargar sesión activa");
                }
            }
        }

        private IEnumerator LoadDialogueData()
        {
            Debug.Log("Cargando datos del diálogo...");

            if (laravelAPI != null && hasSessionData)
            {
                // Obtener ID de la sesión desde los datos cargados
                var sessionId = laravelAPI.currentSessionData?.session.id ?? 0;

                if (sessionId > 0)
                {
                    laravelAPI.GetSessionDialogue(sessionId);

                    // Esperar a que se reciban los datos del diálogo
                    float timeout = Time.time + connectionTimeout;

                    while (Time.time < timeout && !hasDialogueData)
                    {
                        yield return new WaitForSeconds(0.1f);
                    }

                    if (hasDialogueData)
                    {
                        Debug.Log("Datos del diálogo cargados exitosamente");
                    }
                    else
                    {
                        Debug.LogError("Timeout al cargar datos del diálogo");
                    }
                }
                else
                {
                    Debug.LogError("ID de sesión inválido");
                }
            }
        }

        private IEnumerator ConfigureScene()
        {
            Debug.Log("Configurando escena...");

            // Cargar escena objetivo si no está cargada
            if (SceneManager.GetActiveScene().name != targetSceneName)
            {
                Debug.Log($"Cargando escena: {targetSceneName}");
                SceneManager.LoadScene(targetSceneName);

                // Esperar a que la escena se cargue
                yield return new WaitUntil(() => SceneManager.GetActiveScene().name == targetSceneName);
            }

            // Reconfigurar referencias después de cargar la escena
            ReconfigureReferences();

            // Configurar escena OXXO
            if (oxxoSetup != null)
            {
                oxxoSetup.SetupOXXOScene();
            }

            Debug.Log("Escena configurada exitosamente");
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

        private void OnActiveSessionReceived(SessionData sessionData)
        {
            hasSessionData = true;
            OnSessionLoaded?.Invoke(sessionData);

            Debug.Log($"Sesión recibida: {sessionData.session.nombre}");
        }

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
            hasDialogueData = true;
            OnDialogueLoaded?.Invoke(dialogueData);

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
            oxxoSetup = FindObjectOfType<OXXOSceneSetup>();

            Debug.Log("Referencias reconfiguradas");
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
