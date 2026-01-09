using UnityEngine;
using JuiciosSimulator.Config;
using JuiciosSimulator.API;
using JuiciosSimulator.Integration;
using JuiciosSimulator.UI;
using JuiciosSimulator.Dialogue;

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
            // Inicializar juego
            InitializeGame();
        }

        private void InitializeGame()
        {
            Debug.Log("Inicializando Simulador de Juicios Orales...");

            // Configurar componentes
            SetupComponents();

            // Suscribirse a eventos
            SubscribeToEvents();

            // Iniciar proceso de conexión
            StartConnectionProcess();
        }

        private void SetupComponents()
        {
            // Configurar LaravelAPI
            if (laravelAPI == null)
            {
                laravelAPI = FindObjectOfType<LaravelAPI>();
            }

            if (laravelAPI != null && config != null)
            {
                laravelAPI.baseURL = config.apiBaseURL;
                laravelAPI.unityVersion = config.unityVersion;
                laravelAPI.unityPlatform = config.unityPlatform;
            }

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

        private void SubscribeToEvents()
        {
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

        private void OnUserLoggedIn(UserData user)
        {
            Debug.Log($"✅ Usuario logueado: {user.name} (ID: {user.id})");

            // Obtener sesión activa del usuario
            if (laravelAPI != null)
            {
                Debug.Log("Obteniendo sesión activa del usuario...");
                laravelAPI.GetActiveSession();
            }
        }

        private void OnLaravelError(string error)
        {
            Debug.LogError($"❌ Error de Laravel: {error}");
        }

        private void OnActiveSessionReceived(SessionData sessionData)
        {
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

        private void OnDialogueDataReceived(DialogueData dialogueData)
        {
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
