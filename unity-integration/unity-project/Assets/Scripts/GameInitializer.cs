using UnityEngine;
using System.Collections;
using JuiciosSimulator.Config;
using JuiciosSimulator.API;
using JuiciosSimulator.Integration;
using JuiciosSimulator.UI;
using JuiciosSimulator.Dialogue;
using JuiciosSimulator.Utils;

namespace JuiciosSimulator
{
    /// <summary>
    /// Inicializador principal del juego
    /// </summary>
    public class GameInitializer : MonoBehaviour
    {
        [Header("Configuración")]
        public UnityConfig config;

        [Header("Referencias")]
        public LaravelAPI laravelAPI;
        public DialogoUI dialogoUI;
        public UnityLaravelIntegration integration;
        public DialogueManager dialogueManager;
        public SessionInfoUI sessionInfoUI;

        [Header("Configuración de Sesión")]
        public int sesionId = 0; // Se obtendrá automáticamente de la sesión activa
        public string testEmail = "ana.garcia@estudiante.com";
        public string testPassword = "Ana2024!";

        [Header("Datos de Sesión")]
        public SessionData currentSessionData;
        public DialogueData currentDialogueData;

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
            // Retrasar inicialización para evitar recursión durante la carga de Unity
            StartCoroutine(InitializeGameDelayed());
        }

        private System.Collections.IEnumerator InitializeGameDelayed()
        {
            // Esperar varios frames para que Unity termine de inicializar todos los scripts
            yield return null; // Frame 1
            yield return null; // Frame 2
            yield return null; // Frame 3
            
            InitializeGame();
        }

        private void InitializeGame()
        {
            // ESPERAR a que LaravelAPI esté completamente inicializado
            if (!LaravelAPI.IsInitialized)
            {
                Debug.LogWarning("[GameInitializer] LaravelAPI aún no está inicializado. Reintentando en el siguiente frame...");
                StartCoroutine(WaitForLaravelAPIAndInitialize());
                return;
            }

            Debug.Log("Inicializando Simulador de Juicios Orales...");

            // Configurar componentes (con delay para evitar recursión)
            StartCoroutine(SetupComponentsDelayed());

            // Suscribirse a eventos
            SubscribeToEvents();

            // Iniciar proceso de conexión
            StartConnectionProcess();
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
                Debug.LogError("[GameInitializer] Timeout esperando LaravelAPI. Inicializando de todas formas...");
            }

            // Ahora inicializar
            StartCoroutine(SetupComponentsDelayed());
            SubscribeToEvents();
            StartConnectionProcess();
        }

        private IEnumerator SetupComponentsDelayed()
        {
            // Esperar varios frames adicionales antes de buscar objetos
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();
            yield return new WaitForEndOfFrame();

            // Configurar LaravelAPI (usar Instance en lugar de FindObjectOfType)
            if (laravelAPI == null)
            {
                laravelAPI = LaravelAPI.Instance;
            }

            if (laravelAPI != null && config != null)
            {
                laravelAPI.baseURL = config.apiBaseURL;
                laravelAPI.unityVersion = config.unityVersion;
                laravelAPI.unityPlatform = config.unityPlatform;
            }

            // Esperar otro frame antes de buscar más objetos
            yield return new WaitForEndOfFrame();

            // Configurar DialogoUI
            if (dialogoUI == null)
            {
                dialogoUI = FindObjectOfType<DialogoUI>();
            }

            if (dialogoUI != null)
            {
                dialogoUI.SetSesionId(sesionId);
            }

            // Configurar Integration
            if (integration == null)
            {
                integration = FindObjectOfType<UnityLaravelIntegration>();
            }

            if (integration != null)
            {
                integration.sesionId = sesionId;
            }

            // Configurar DialogueManager
            if (dialogueManager == null)
            {
                dialogueManager = FindObjectOfType<DialogueManager>();
            }

            // Configurar SessionInfoUI
            if (sessionInfoUI == null)
            {
                sessionInfoUI = FindObjectOfType<SessionInfoUI>();
            }
        }

        private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones

        private void SubscribeToEvents()
        {
            // Prevenir múltiples suscripciones
            if (isSubscribedToEvents)
            {
                Debug.LogWarning("[GameInitializer] Ya está suscrito a eventos. Ignorando suscripción duplicada.");
                return;
            }

            isSubscribedToEvents = true;

            // Eventos de integración
            if (integration != null)
            {
                UnityLaravelIntegration.OnIntegrationReady += OnIntegrationReady;
                UnityLaravelIntegration.OnIntegrationError += OnIntegrationError;
            }

            // Eventos de Laravel
            if (laravelAPI != null)
            {
                LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
                LaravelAPI.OnError += OnLaravelError;
                LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;
                LaravelAPI.OnDialogueDataReceived += OnDialogueDataReceived;
            }
        }

        private void StartConnectionProcess()
        {
            // Paso 1: Verificar configuración
            if (config != null && !config.ValidateConfig())
            {
                Debug.LogError("Configuración inválida. Por favor revisa UnityConfig.");
                return;
            }

            // Paso 2: Iniciar login automático (para testing)
            if (Application.isEditor || Debug.isDebugBuild)
            {
                StartCoroutine(AutoLogin());
            }
        }

        private System.Collections.IEnumerator AutoLogin()
        {
            // Esperar un frame para asegurar que todo esté inicializado
            yield return null;

            // Realizar login automático
            if (laravelAPI != null)
            {
                Debug.Log("Realizando login automático...");
                laravelAPI.Login(testEmail, testPassword);
            }
        }

        #region Event Handlers

        private void OnIntegrationReady(bool ready)
        {
            if (ready)
            {
                Debug.Log("✅ Integración completa lista!");
                Debug.Log($"Sesión ID: {sesionId}");
                Debug.Log($"Sala: {Photon.Pun.PhotonNetwork.CurrentRoom?.Name ?? "No en sala"}");
                Debug.Log($"Jugadores: {Photon.Pun.PhotonNetwork.CurrentRoom?.PlayerCount ?? 0}");
            }
        }

        private void OnIntegrationError(string error)
        {
            Debug.LogError($"❌ Error en integración: {error}");
        }

        private bool hasRequestedSession = false; // Flag para prevenir múltiples solicitudes

        private void OnUserLoggedIn(UserData user)
        {
            DebugLogger.LogEvent("OnUserLoggedIn", $"Usuario: {user.name} (ID: {user.id})", new { userId = user.id, userName = user.name });
            Debug.Log($"✅ Usuario logueado: {user.name} (ID: {user.id})");

            // Prevenir múltiples solicitudes de sesión
            if (hasRequestedSession)
            {
                DebugLogger.LogWarning("GameInitializer", "Ya se solicitó la sesión activa. Ignorando llamada duplicada.");
                Debug.LogWarning("[GameInitializer] Ya se solicitó la sesión activa. Ignorando llamada duplicada.");
                return;
            }

            // Obtener sesión activa del usuario (solo una vez)
            if (laravelAPI != null && !hasRequestedSession)
            {
                hasRequestedSession = true;
                DebugLogger.LogPhase("GameInitializer", "Solicitando sesión activa", new { userId = user.id });
                Debug.Log("Obteniendo sesión activa del usuario...");
                laravelAPI.GetActiveSession();
            }
        }

        private void OnLaravelError(string error)
        {
            Debug.LogError($"❌ Error de Laravel: {error}");
        }

        private bool hasReceivedSession = false; // Flag para prevenir procesamiento múltiple

        private void OnActiveSessionReceived(SessionData sessionData)
        {
            DebugLogger.LogEvent("OnActiveSessionReceived", $"Sesión: {sessionData?.session?.nombre ?? "N/A"} (ID: {sessionData?.session?.id ?? 0})", new {
                sessionId = sessionData?.session?.id,
                sessionName = sessionData?.session?.nombre,
                roleName = sessionData?.role?.nombre
            });

            // Prevenir procesamiento múltiple del mismo evento
            if (hasReceivedSession && currentSessionData != null && 
                currentSessionData.session != null && sessionData != null && 
                sessionData.session != null && 
                currentSessionData.session.id == sessionData.session.id)
            {
                DebugLogger.LogWarning("GameInitializer", $"Sesión {sessionData.session.id} ya recibida y procesada. Ignorando evento duplicado.");
                Debug.LogWarning("[GameInitializer] Sesión ya recibida y procesada. Ignorando evento duplicado.");
                return;
            }

            // Validaciones null críticas para evitar memory access out of bounds
            if (sessionData == null)
            {
                Debug.LogError("❌ OnActiveSessionReceived: sessionData es null");
                return;
            }

            if (sessionData.session == null)
            {
                Debug.LogError("❌ OnActiveSessionReceived: sessionData.session es null");
                return;
            }

            hasReceivedSession = true;
            currentSessionData = sessionData;
            sesionId = sessionData.session.id;

            Debug.Log($"✅ Sesión activa obtenida: {sessionData.session.nombre ?? "Sin nombre"}");
            
            // Validar role antes de acceder
            if (sessionData.role != null)
            {
                Debug.Log($"Rol asignado: {sessionData.role.nombre ?? "Sin nombre"}");
            }
            else
            {
                Debug.LogWarning("⚠️ Rol no asignado en sesión activa");
            }
            
            Debug.Log($"Estado: {sessionData.session.estado ?? "Desconocido"}");

            // Actualizar componentes con la nueva sesión
            UpdateComponentsWithSession();
        }

        private bool hasReceivedDialogue = false; // Flag para prevenir procesamiento múltiple

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
            DebugLogger.LogEvent("OnDialogueDataReceived", $"Diálogo: {dialogueData?.dialogue?.nombre ?? "N/A"} (ID: {dialogueData?.dialogue?.id ?? 0})", new {
                dialogueId = dialogueData?.dialogue?.id,
                dialogueName = dialogueData?.dialogue?.nombre,
                rolesCount = dialogueData?.dialogue?.roles?.Count ?? 0
            });

            // Prevenir procesamiento múltiple del mismo evento
            if (hasReceivedDialogue && currentDialogueData != null && 
                currentDialogueData.dialogue != null && dialogueData != null && 
                dialogueData.dialogue != null && 
                currentDialogueData.dialogue.id == dialogueData.dialogue.id)
            {
                DebugLogger.LogWarning("GameInitializer", $"Diálogo {dialogueData.dialogue.id} ya recibido y procesado. Ignorando evento duplicado.");
                Debug.LogWarning("[GameInitializer] Diálogo ya recibido y procesado. Ignorando evento duplicado.");
                return;
            }

            // Validaciones null críticas para evitar memory access out of bounds
            if (dialogueData == null)
            {
                Debug.LogError("❌ OnDialogueDataReceived: dialogueData es null");
                return;
            }

            if (dialogueData.dialogue == null)
            {
                Debug.LogError("❌ OnDialogueDataReceived: dialogueData.dialogue es null");
                return;
            }

            hasReceivedDialogue = true;
            currentDialogueData = dialogueData;

            Debug.Log($"✅ Diálogo cargado: {dialogueData.dialogue.nombre ?? "Sin nombre"}");
            
            // Validar roles antes de acceder
            if (dialogueData.dialogue.roles != null)
            {
                Debug.Log($"Roles disponibles: {dialogueData.dialogue.roles.Count}");
            }
            else
            {
                Debug.LogWarning("⚠️ Roles no disponibles en diálogo");
            }

            // Configurar UI con los datos del diálogo
            SetupDialogueUI();
        }

        private void UpdateComponentsWithSession()
        {
            // Actualizar DialogoUI
            if (dialogoUI != null)
            {
                dialogoUI.SetSesionId(sesionId);
            }

            // Actualizar Integration
            if (integration != null)
            {
                integration.sesionId = sesionId;
            }
        }

        public void SetupDialogueUI()
        {
            if (dialogueManager != null && currentDialogueData != null)
            {
                // Configurar el diálogo manager con los datos recibidos
                dialogueManager.SetupDialogueSystem(currentDialogueData);
            }
        }

        #endregion

        #region Métodos Públicos

        /// <summary>
        /// Reiniciar el juego
        /// </summary>
        public void RestartGame()
        {
            Debug.Log("Reiniciando juego...");

            // Desconectar de Photon
            if (Photon.Pun.PhotonNetwork.IsConnected)
            {
                Photon.Pun.PhotonNetwork.Disconnect();
            }

            // Reiniciar integración
            if (integration != null)
            {
                integration.Reconnect();
            }
        }

        /// <summary>
        /// Cambiar sesión
        /// </summary>
        public void ChangeSession(int newSesionId)
        {
            sesionId = newSesionId;

            if (dialogoUI != null)
            {
                dialogoUI.SetSesionId(sesionId);
            }

            if (integration != null)
            {
                integration.sesionId = sesionId;
            }

            Debug.Log($"Sesión cambiada a: {sesionId}");
        }

        /// <summary>
        /// Obtener estado del juego
        /// </summary>
        public string GetGameStatus()
        {
            string status = "Estado del Juego:\n";
            status += $"Sesión: {sesionId}\n";
            status += $"Laravel: {(laravelAPI?.isConnected ?? false ? "Conectado" : "Desconectado")}\n";
            status += $"Photon: {(Photon.Pun.PhotonNetwork.IsConnected ? "Conectado" : "Desconectado")}\n";
            status += $"Sala: {(Photon.Pun.PhotonNetwork.InRoom ? Photon.Pun.PhotonNetwork.CurrentRoom.Name : "No en sala")}\n";
            status += $"Jugadores: {(Photon.Pun.PhotonNetwork.InRoom ? Photon.Pun.PhotonNetwork.CurrentRoom.PlayerCount : 0)}\n";
            status += $"Integración: {(integration?.IsIntegrationReady() ?? false ? "Lista" : "No lista")}\n";
            status += $"Diálogos: {(dialogueManager != null ? "Activo" : "Inactivo")}\n";
            status += $"UI Sesión: {(sessionInfoUI != null ? "Activo" : "Inactivo")}\n";

            return status;
        }

        #endregion

        #region Debug UI

        private void OnGUI()
        {
            if (config != null && config.showDebugPanel)
            {
                GUILayout.BeginArea(new Rect(10, 10, 400, 300));
                GUILayout.Box("Simulador de Juicios Orales - Debug Panel");

                GUILayout.Label(GetGameStatus());

                GUILayout.Space(10);

                if (GUILayout.Button("Reiniciar Juego"))
                {
                    RestartGame();
                }

                if (GUILayout.Button("Actualizar Diálogo"))
                {
                    if (dialogoUI != null)
                    {
                        dialogoUI.RefreshDialogo();
                    }
                }

                if (GUILayout.Button("Cambiar a Sesión 2"))
                {
                    ChangeSession(2);
                }

                GUILayout.EndArea();
            }
        }

        #endregion

        private void OnDestroy()
        {
            // Desuscribirse de eventos
            if (integration != null)
            {
                UnityLaravelIntegration.OnIntegrationReady -= OnIntegrationReady;
                UnityLaravelIntegration.OnIntegrationError -= OnIntegrationError;
            }

            if (laravelAPI != null)
            {
                LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
                LaravelAPI.OnError -= OnLaravelError;
            }
        }
    }
}
