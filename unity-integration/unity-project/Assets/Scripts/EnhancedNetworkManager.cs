using UnityEngine;
using UnityEngine.UI;
using Photon.Pun;
using Photon.Realtime;
using ExitGames.Client.Photon;
using TMPro;
using JuiciosSimulator.Session;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Integration
{
    /// <summary>
    /// Gestor de red mejorado que integra con Laravel para obtener roles de sesión
    /// Reemplaza la selección manual de roles con asignación automática desde Laravel
    /// </summary>
    public class EnhancedNetworkManager : MonoBehaviourPunCallbacks
    {
        [Header("Session Integration")]
        public SessionManager sessionManager;
        public bool autoConnectToSession = true;

        [Header("UI References")]
        public GameObject loadingPanel;
        public TextMeshProUGUI loadingText;
        public TextMeshProUGUI statusText;

        [Header("Player Spawn")]
        public Vector3 spawnPosition = new Vector3(-0.06f, 4.8f, -16.0f);
        public Quaternion spawnRotation = Quaternion.Euler(0, 180, 0);

        [Header("Debug")]
        public bool showDebugLogs = true;

        private string assignedRole;
        private bool roleAssigned = false;
        private bool photonConnected = false;

        void Start()
        {
            InitializeNetworkManager();
        }

        /// <summary>
        /// Inicializa el gestor de red con integración de Laravel
        /// </summary>
        private void InitializeNetworkManager()
        {
            try
            {
                ShowLoadingPanel("Inicializando sistema...");

                // Verificar que SessionManager esté disponible
                if (sessionManager == null)
                {
                    sessionManager = FindObjectOfType<SessionManager>();
                }

                if (sessionManager == null)
                {
                    Debug.LogError("EnhancedNetworkManager: SessionManager no encontrado");
                    ShowError("Error: SessionManager no encontrado");
                    return;
                }

                // Suscribirse a eventos del SessionManager
                SubscribeToSessionEvents();

                // Iniciar proceso de conexión
                if (autoConnectToSession)
                {
                    StartCoroutine(ConnectToSessionAndPhoton());
                }

                if (showDebugLogs)
                {
                    Debug.Log("EnhancedNetworkManager: Inicializado correctamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en inicialización: {e.Message}");
                ShowError($"Error de inicialización: {e.Message}");
            }
        }

        /// <summary>
        /// Se suscribe a los eventos del SessionManager
        /// </summary>
        private void SubscribeToSessionEvents()
        {
            if (sessionManager != null)
            {
                // TODO: Implementar eventos del SessionManager
                // SessionManager.OnSessionJoined += OnSessionJoined;
                // SessionManager.OnRoleAssigned += OnRoleAssigned;
                // SessionManager.OnSessionError += OnSessionError;
            }
        }

        /// <summary>
        /// Se desuscribe de los eventos del SessionManager
        /// </summary>
        private void UnsubscribeFromSessionEvents()
        {
            if (sessionManager != null)
            {
                // TODO: Implementar eventos del SessionManager
                // SessionManager.OnSessionJoined -= OnSessionJoined;
                // SessionManager.OnRoleAssigned -= OnRoleAssigned;
                // SessionManager.OnSessionError -= OnSessionError;
            }
        }

        /// <summary>
        /// Conecta a la sesión de Laravel y luego a Photon
        /// </summary>
        private System.Collections.IEnumerator ConnectToSessionAndPhoton()
        {
            try
            {
                ShowLoadingPanel("Conectando a la sesión...");

                // TODO: Implementar métodos del SessionManager
                // Esperar a que SessionManager esté listo
                // yield return new WaitUntil(() => sessionManager != null && sessionManager.IsInitialized);

                // TODO: Implementar métodos del SessionManager
                // Intentar unirse a la sesión automáticamente
                // if (sessionManager.HasActiveSession())
                // {
                //     ShowLoadingPanel("Uniéndose a sesión activa...");
                //     yield return StartCoroutine(sessionManager.JoinActiveSession());
                // }
                // else
                // {
                //     ShowError("No hay sesión activa disponible");
                //     yield break;
                // }

                // Conectar a Photon una vez que tengamos el rol
                ShowLoadingPanel("Conectando a Photon...");
                ConnectToPhoton();
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en conexión: {e.Message}");
                ShowError($"Error de conexión: {e.Message}");
            }

            // Esperar a que se asigne el rol (fuera del try-catch)
            yield return new WaitUntil(() => roleAssigned);
        }

        /// <summary>
        /// Conecta a Photon PUN2
        /// </summary>
        private void ConnectToPhoton()
        {
            try
            {
                if (!PhotonNetwork.IsConnected)
                {
                    if (showDebugLogs)
                    {
                        Debug.Log("EnhancedNetworkManager: Conectando a Photon...");
                    }
                    PhotonNetwork.ConnectUsingSettings();
                }
                else
                {
                    OnConnectedToMaster();
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error conectando a Photon: {e.Message}");
                ShowError($"Error conectando a Photon: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se conecta al Master Server de Photon
        /// </summary>
        public override void OnConnectedToMaster()
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log("EnhancedNetworkManager: Conectado al Master Server");
                }

                ShowLoadingPanel("Entrando al lobby de Photon...");
                PhotonNetwork.JoinLobby();
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en OnConnectedToMaster: {e.Message}");
                ShowError($"Error en conexión: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se une al lobby de Photon
        /// </summary>
        public override void OnJoinedLobby()
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log("EnhancedNetworkManager: Unido al Lobby de Photon");
                }

                ShowLoadingPanel("Buscando sala de la sesión...");

                // Intentar unirse a la sala de la sesión
                string sessionRoomName = GetSessionRoomName();
                if (!string.IsNullOrEmpty(sessionRoomName))
                {
                    PhotonNetwork.JoinRoom(sessionRoomName);
                }
                else
                {
                    // Si no hay sala específica, crear una nueva
                    CreateSessionRoom();
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en OnJoinedLobby: {e.Message}");
                ShowError($"Error en lobby: {e.Message}");
            }
        }

        /// <summary>
        /// Obtiene el nombre de la sala basado en la sesión actual
        /// </summary>
        private string GetSessionRoomName()
        {
            // TODO: Implementar métodos del SessionManager
            // if (sessionManager != null && sessionManager.HasActiveSession())
            // {
            //     var session = sessionManager.GetCurrentSession();
            //     if (session != null)
            //     {
            //         return $"Session_{session.id}";
            //     }
            // }
            return null;
        }

        /// <summary>
        /// Crea una nueva sala para la sesión
        /// </summary>
        private void CreateSessionRoom()
        {
            try
            {
                string roomName = GetSessionRoomName() ?? $"Session_{System.DateTime.Now.Ticks}";

                RoomOptions roomOptions = new RoomOptions
                {
                    MaxPlayers = 20,
                    IsVisible = true,
                    IsOpen = true
                };

                // Agregar propiedades de la sesión a la sala
                Hashtable roomProps = new Hashtable
                {
                    { "SessionId", sessionManager?.GetCurrentSession()?.id ?? 0 },
                    { "SessionName", sessionManager?.GetCurrentSession()?.nombre ?? "Sesión" },
                    { "AssignedRoles", new string[0] }
                };

                roomOptions.CustomRoomProperties = roomProps;
                roomOptions.CustomRoomPropertiesForLobby = new string[] { "SessionId", "SessionName" };

                PhotonNetwork.CreateRoom(roomName, roomOptions, TypedLobby.Default);

                if (showDebugLogs)
                {
                    Debug.Log($"EnhancedNetworkManager: Creando sala '{roomName}' para la sesión");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error creando sala: {e.Message}");
                ShowError($"Error creando sala: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se une a una sala
        /// </summary>
        public override void OnJoinedRoom()
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log($"EnhancedNetworkManager: Unido a la sala '{PhotonNetwork.CurrentRoom.Name}'");
                }

                // Configurar el rol del jugador
                ConfigurePlayerRole();

                // Instanciar el jugador
                SpawnPlayer();

                // Configurar chat de voz si está disponible
                SetupVoiceChat();

                HideLoadingPanel();

                if (showDebugLogs)
                {
                    Debug.Log("EnhancedNetworkManager: Jugador instanciado correctamente");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en OnJoinedRoom: {e.Message}");
                ShowError($"Error uniéndose a la sala: {e.Message}");
            }
        }

        /// <summary>
        /// Configura el rol del jugador en Photon
        /// </summary>
        private void ConfigurePlayerRole()
        {
            try
            {
                if (!string.IsNullOrEmpty(assignedRole))
                {
                    // Establecer el rol en las propiedades del jugador
                    Hashtable playerProps = new Hashtable { { "Rol", assignedRole } };
                    PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);

                    // Actualizar las propiedades de la sala con el rol usado
                    UpdateRoomUsedRoles();

                    if (showDebugLogs)
                    {
                        Debug.Log($"EnhancedNetworkManager: Rol '{assignedRole}' asignado al jugador");
                    }
                }
                else
                {
                    Debug.LogWarning("EnhancedNetworkManager: No hay rol asignado para el jugador");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error configurando rol: {e.Message}");
            }
        }

        /// <summary>
        /// Actualiza los roles usados en la sala
        /// </summary>
        private void UpdateRoomUsedRoles()
        {
            try
            {
                if (PhotonNetwork.CurrentRoom != null && !string.IsNullOrEmpty(assignedRole))
                {
                    string[] usedRoles = GetUsedRoles();
                    var newUsed = new string[usedRoles.Length + 1];
                    usedRoles.CopyTo(newUsed, 0);
                    newUsed[newUsed.Length - 1] = assignedRole;

                    Hashtable roomProps = new Hashtable { { "UsedRoles", newUsed } };
                    PhotonNetwork.CurrentRoom.SetCustomProperties(roomProps);
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error actualizando roles de sala: {e.Message}");
            }
        }

        /// <summary>
        /// Obtiene los roles usados en la sala actual
        /// </summary>
        private string[] GetUsedRoles()
        {
            if (PhotonNetwork.CurrentRoom != null)
            {
                object used;
                if (PhotonNetwork.CurrentRoom.CustomProperties.TryGetValue("UsedRoles", out used))
                {
                    return (string[])used;
                }
            }
            return new string[0];
        }

        /// <summary>
        /// Instancia el jugador en la sala
        /// </summary>
        private void SpawnPlayer()
        {
            try
            {
                PhotonNetwork.Instantiate("Player", spawnPosition, spawnRotation);

                if (showDebugLogs)
                {
                    Debug.Log("EnhancedNetworkManager: Jugador instanciado");
                }
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error instanciando jugador: {e.Message}");
            }
        }

        /// <summary>
        /// Configura el chat de voz
        /// </summary>
        private void SetupVoiceChat()
        {
            try
            {
#if UNITY_WEBGL && !UNITY_EDITOR
                string roomId = PhotonNetwork.CurrentRoom.Name;
                int actorId = PhotonNetwork.LocalPlayer.ActorNumber;
                
                // Llamar a la función JavaScript para inicializar el chat de voz
                Application.ExternalCall("initVoiceCall", roomId, actorId);
                
                if (showDebugLogs)
                {
                    Debug.Log($"EnhancedNetworkManager: Chat de voz inicializado - Room: {roomId}, Actor: {actorId}");
                }
#endif
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error configurando chat de voz: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se une a una sesión
        /// </summary>
        private void OnSessionJoined(SessionData session)
        {
            try
            {
                if (showDebugLogs)
                {
                    Debug.Log($"EnhancedNetworkManager: Sesión unida - {session.session.nombre}");
                }

                ShowLoadingPanel("Esperando asignación de rol...");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en OnSessionJoined: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando se asigna un rol
        /// </summary>
        private void OnRoleAssigned(string role)
        {
            try
            {
                assignedRole = role;
                roleAssigned = true;

                if (showDebugLogs)
                {
                    Debug.Log($"EnhancedNetworkManager: Rol asignado - {role}");
                }

                ShowLoadingPanel($"Rol asignado: {role}");
            }
            catch (System.Exception e)
            {
                Debug.LogError($"EnhancedNetworkManager: Error en OnRoleAssigned: {e.Message}");
            }
        }

        /// <summary>
        /// Callback cuando hay un error en la sesión
        /// </summary>
        private void OnSessionError(string error)
        {
            Debug.LogError($"EnhancedNetworkManager: Error de sesión - {error}");
            ShowError($"Error de sesión: {error}");
        }

        /// <summary>
        /// Muestra el panel de carga
        /// </summary>
        private void ShowLoadingPanel(string message)
        {
            if (loadingPanel != null)
            {
                loadingPanel.SetActive(true);
            }

            if (loadingText != null)
            {
                loadingText.text = message;
            }

            if (statusText != null)
            {
                statusText.text = message;
            }
        }

        /// <summary>
        /// Oculta el panel de carga
        /// </summary>
        private void HideLoadingPanel()
        {
            if (loadingPanel != null)
            {
                loadingPanel.SetActive(false);
            }
        }

        /// <summary>
        /// Muestra un error
        /// </summary>
        private void ShowError(string error)
        {
            Debug.LogError($"EnhancedNetworkManager: {error}");

            if (statusText != null)
            {
                statusText.text = $"Error: {error}";
            }
        }

        /// <summary>
        /// Método público para reconectar manualmente
        /// </summary>
        [ContextMenu("Reconectar")]
        public void Reconnect()
        {
            roleAssigned = false;
            assignedRole = null;
            StartCoroutine(ConnectToSessionAndPhoton());
        }

        /// <summary>
        /// Método público para verificar el estado
        /// </summary>
        [ContextMenu("Verificar Estado")]
        public void CheckStatus()
        {
            Debug.Log($"EnhancedNetworkManager: Estado - Rol: {assignedRole}, Photon: {PhotonNetwork.IsConnected}, Sala: {PhotonNetwork.InRoom}");
        }

        /// <summary>
        /// Limpia recursos al destruir el objeto
        /// </summary>
        private void OnDestroy()
        {
            UnsubscribeFromSessionEvents();
        }
    }
}
