using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using Photon.Pun;
using Photon.Realtime;
using ExitGames.Client.Photon;
using JuiciosSimulator.API;
using JuiciosSimulator.Session;

namespace JuiciosSimulator.Network
{
    /// <summary>
    /// Integración mejorada con Photon PUN2 para sesiones de juicios orales
    /// Maneja la sincronización de roles, estado de audio y datos de sesión
    /// </summary>
    public class EnhancedPhotonIntegration : MonoBehaviourPunCallbacks
    {
        [Header("Configuration")]
        public string roomPrefix = "SesionJuicio_";
        public int maxPlayersPerRoom = 20;
        public bool autoConnect = true;
        public bool autoJoinRoom = true;

        [Header("Audio Settings")]
        public bool enableVoiceChat = true;
        public bool enableSpatialAudio = true;
        public float voiceRange = 50f;

        [Header("Debug")]
        public bool enableDebugLogs = true;
        public bool showNetworkStatus = true;

        // References
        private SessionManager sessionManager;
        private LaravelAPI laravelAPI;

        // State
        private bool isConnectedToPhoton = false;
        private bool isInRoom = false;
        private string currentRoomName = "";
        private Dictionary<int, PlayerData> playersData = new Dictionary<int, PlayerData>();

        // Events
        public static event System.Action<bool> OnPhotonConnectionChanged;
        public static event System.Action<bool> OnRoomConnectionChanged;
        public static event System.Action<PlayerData> OnPlayerJoined;
        public static event System.Action<PlayerData> OnPlayerLeft;
        public static event System.Action<PlayerData> OnPlayerDataUpdated;
        public static event System.Action<string> OnNetworkError;

        private void Start()
        {
            InitializeReferences();
            SubscribeToEvents();

            if (autoConnect)
            {
                ConnectToPhoton();
            }
        }

        private void OnDestroy()
        {
            UnsubscribeFromEvents();
        }

        #region Initialization

        private void InitializeReferences()
        {
            sessionManager = FindObjectOfType<SessionManager>();
            laravelAPI = LaravelAPI.Instance;
        }

        private void SubscribeToEvents()
        {
            SessionManager.OnSessionJoined += OnSessionJoined;
            SessionManager.OnSessionLeft += OnSessionLeft;
            SessionManager.OnRoleAssigned += OnRoleAssigned;
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
        }

        private void UnsubscribeFromEvents()
        {
            SessionManager.OnSessionJoined -= OnSessionJoined;
            SessionManager.OnSessionLeft -= OnSessionLeft;
            SessionManager.OnRoleAssigned -= OnRoleAssigned;
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
        }

        #endregion

        #region Photon Connection

        public void ConnectToPhoton()
        {
            if (isConnectedToPhoton)
            {
                Debug.Log("Already connected to Photon");
                return;
            }

            if (enableDebugLogs)
            {
                Debug.Log("Connecting to Photon...");
            }

            PhotonNetwork.ConnectUsingSettings();
        }

        public void DisconnectFromPhoton()
        {
            if (isInRoom)
            {
                PhotonNetwork.LeaveRoom();
            }

            if (isConnectedToPhoton)
            {
                PhotonNetwork.Disconnect();
            }
        }

        #endregion

        #region Room Management

        public void CreateOrJoinRoom(string roomName)
        {
            if (!isConnectedToPhoton)
            {
                Debug.LogError("Not connected to Photon");
                return;
            }

            currentRoomName = roomName;

            RoomOptions roomOptions = new RoomOptions
            {
                MaxPlayers = maxPlayersPerRoom,
                IsVisible = true,
                IsOpen = true,
                CustomRoomProperties = new ExitGames.Client.Photon.Hashtable
                {
                    { "session_id", sessionManager?.GetCurrentSession()?.id ?? 0 },
                    { "room_type", "juicio_oral" },
                    { "created_at", System.DateTime.UtcNow.ToString("yyyy-MM-ddTHH:mm:ssZ") }
                },
                CustomRoomPropertiesForLobby = new string[] { "session_id", "room_type" }
            };

            if (enableDebugLogs)
            {
                Debug.Log($"Creating/Joining room: {roomName}");
            }

            PhotonNetwork.JoinOrCreateRoom(roomName, roomOptions, TypedLobby.Default);
        }

        public void LeaveRoom()
        {
            if (isInRoom)
            {
                PhotonNetwork.LeaveRoom();
            }
        }

        #endregion

        #region Player Data Management

        public void UpdatePlayerData(PlayerData data)
        {
            if (!isInRoom) return;

            Hashtable customProperties = new Hashtable
            {
                { "user_id", data.userId },
                { "role_id", data.roleId },
                { "role_name", data.roleName },
                { "is_ready", data.isReady },
                { "is_speaking", data.isSpeaking },
                { "audio_enabled", data.audioEnabled },
                { "microphone_enabled", data.microphoneEnabled },
                { "volume", data.volume },
                { "position", data.position },
                { "rotation", data.rotation }
            };

            PhotonNetwork.LocalPlayer.SetCustomProperties(customProperties);

            if (enableDebugLogs)
            {
                Debug.Log($"Updated player data for role: {data.roleName}");
            }
        }

        public PlayerData GetPlayerData(int actorNumber)
        {
            if (playersData.ContainsKey(actorNumber))
            {
                return playersData[actorNumber];
            }
            return null;
        }

        public List<PlayerData> GetAllPlayersData()
        {
            return new List<PlayerData>(playersData.Values);
        }

        #endregion

        #region Audio Management

        public void SetAudioState(bool microphoneEnabled, bool audioEnabled, float volume = 1f)
        {
            if (!isInRoom) return;

            Hashtable audioProperties = new Hashtable
            {
                { "microphone_enabled", microphoneEnabled },
                { "audio_enabled", audioEnabled },
                { "volume", volume }
            };

            PhotonNetwork.LocalPlayer.SetCustomProperties(audioProperties);
        }

        public void SetSpeakingState(bool isSpeaking)
        {
            if (!isInRoom) return;

            Hashtable speakingProperties = new Hashtable
            {
                { "is_speaking", isSpeaking }
            };

            PhotonNetwork.LocalPlayer.SetCustomProperties(speakingProperties);
        }

        #endregion

        #region RPC Methods

        [PunRPC]
        public void SyncPlayerPosition(Vector3 position, Vector3 rotation)
        {
            // Sync player position with other players
            if (enableDebugLogs)
            {
                Debug.Log($"Player position synced: {position}");
            }
        }

        [PunRPC]
        public void SyncPlayerAction(string action, string data)
        {
            // Sync player actions (like making decisions)
            if (enableDebugLogs)
            {
                Debug.Log($"Player action synced: {action} - {data}");
            }
        }

        [PunRPC]
        public void SyncDialogState(string dialogState)
        {
            // Sync dialog state changes
            if (enableDebugLogs)
            {
                Debug.Log($"Dialog state synced: {dialogState}");
            }
        }

        #endregion

        #region Photon Callbacks

        public override void OnConnectedToMaster()
        {
            isConnectedToPhoton = true;
            OnPhotonConnectionChanged?.Invoke(true);

            if (enableDebugLogs)
            {
                Debug.Log("Connected to Photon Master Server");
            }

            if (autoJoinRoom && sessionManager != null && sessionManager.IsInSession())
            {
                var session = sessionManager.GetCurrentSession();
                if (session != null)
                {
                    CreateOrJoinRoom(roomPrefix + session.id);
                }
            }
        }

        public override void OnDisconnected(DisconnectCause cause)
        {
            isConnectedToPhoton = false;
            isInRoom = false;
            OnPhotonConnectionChanged?.Invoke(false);
            OnRoomConnectionChanged?.Invoke(false);

            if (enableDebugLogs)
            {
                Debug.Log($"Disconnected from Photon: {cause}");
            }

            OnNetworkError?.Invoke($"Disconnected: {cause}");
        }

        public override void OnJoinedRoom()
        {
            isInRoom = true;
            OnRoomConnectionChanged?.Invoke(true);

            if (enableDebugLogs)
            {
                Debug.Log($"Joined room: {PhotonNetwork.CurrentRoom.Name}");
            }

            // Initialize player data
            InitializePlayerData();

            // Update other players about our data
            UpdatePlayerDataFromSession();
        }

        public override void OnLeftRoom()
        {
            isInRoom = false;
            OnRoomConnectionChanged?.Invoke(false);
            playersData.Clear();

            if (enableDebugLogs)
            {
                Debug.Log("Left room");
            }
        }

        public override void OnPlayerEnteredRoom(Player newPlayer)
        {
            if (enableDebugLogs)
            {
                Debug.Log($"Player entered room: {newPlayer.NickName}");
            }

            // Initialize player data
            InitializePlayerDataForPlayer(newPlayer);
        }

        public override void OnPlayerLeftRoom(Player otherPlayer)
        {
            if (enableDebugLogs)
            {
                Debug.Log($"Player left room: {otherPlayer.NickName}");
            }

            // Remove player data
            if (playersData.ContainsKey(otherPlayer.ActorNumber))
            {
                var playerData = playersData[otherPlayer.ActorNumber];
                playersData.Remove(otherPlayer.ActorNumber);
                OnPlayerLeft?.Invoke(playerData);
            }
        }

        public override void OnPlayerPropertiesUpdate(Player targetPlayer, Hashtable changedProps)
        {
            if (enableDebugLogs)
            {
                Debug.Log($"Player properties updated: {targetPlayer.NickName}");
            }

            // Update player data
            UpdatePlayerDataFromProperties(targetPlayer, changedProps);
        }

        #endregion

        #region Player Data Initialization

        private void InitializePlayerData()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) return;

            var session = sessionManager.GetCurrentSession();
            var role = sessionManager.GetCurrentRoleAssignment();

            if (session != null && role != null)
            {
                PlayerData playerData = new PlayerData
                {
                    actorNumber = PhotonNetwork.LocalPlayer.ActorNumber,
                    userId = role.usuario_id,
                    roleId = role.rol_id,
                    roleName = role.rol.nombre,
                    isReady = false,
                    isSpeaking = false,
                    audioEnabled = true,
                    microphoneEnabled = false,
                    volume = 1f,
                    position = Vector3.zero,
                    rotation = Vector3.zero
                };

                playersData[PhotonNetwork.LocalPlayer.ActorNumber] = playerData;
                UpdatePlayerData(playerData);
            }
        }

        private void InitializePlayerDataForPlayer(Player player)
        {
            PlayerData playerData = new PlayerData
            {
                actorNumber = player.ActorNumber,
                userId = 0,
                roleId = 0,
                roleName = "Unknown",
                isReady = false,
                isSpeaking = false,
                audioEnabled = true,
                microphoneEnabled = false,
                volume = 1f,
                position = Vector3.zero,
                rotation = Vector3.zero
            };

            playersData[player.ActorNumber] = playerData;
            OnPlayerJoined?.Invoke(playerData);
        }

        private void UpdatePlayerDataFromProperties(Player player, Hashtable properties)
        {
            if (!playersData.ContainsKey(player.ActorNumber)) return;

            var playerData = playersData[player.ActorNumber];

            if (properties.ContainsKey("user_id"))
                playerData.userId = (int)properties["user_id"];
            if (properties.ContainsKey("role_id"))
                playerData.roleId = (int)properties["role_id"];
            if (properties.ContainsKey("role_name"))
                playerData.roleName = (string)properties["role_name"];
            if (properties.ContainsKey("is_ready"))
                playerData.isReady = (bool)properties["is_ready"];
            if (properties.ContainsKey("is_speaking"))
                playerData.isSpeaking = (bool)properties["is_speaking"];
            if (properties.ContainsKey("audio_enabled"))
                playerData.audioEnabled = (bool)properties["audio_enabled"];
            if (properties.ContainsKey("microphone_enabled"))
                playerData.microphoneEnabled = (bool)properties["microphone_enabled"];
            if (properties.ContainsKey("volume"))
                playerData.volume = (float)properties["volume"];
            if (properties.ContainsKey("position"))
                playerData.position = (Vector3)properties["position"];
            if (properties.ContainsKey("rotation"))
                playerData.rotation = (Vector3)properties["rotation"];

            playersData[player.ActorNumber] = playerData;
            OnPlayerDataUpdated?.Invoke(playerData);
        }

        #endregion

        #region Event Handlers

        private void OnSessionJoined(SesionData session)
        {
            if (autoJoinRoom && isConnectedToPhoton)
            {
                CreateOrJoinRoom(roomPrefix + session.id);
            }
        }

        private void OnSessionLeft()
        {
            if (isInRoom)
            {
                LeaveRoom();
            }
        }

        private void OnRoleAssigned(AsignacionRolData role)
        {
            if (isInRoom)
            {
                UpdatePlayerDataFromSession();
            }
        }

        private void OnUserLoggedIn(UserData user)
        {
            if (enableDebugLogs)
            {
                Debug.Log($"User logged in: {user.name}");
            }
        }

        #endregion

        #region Helper Methods

        private void UpdatePlayerDataFromSession()
        {
            if (sessionManager == null || !sessionManager.IsInSession()) return;

            var session = sessionManager.GetCurrentSession();
            var role = sessionManager.GetCurrentRoleAssignment();

            if (session != null && role != null)
            {
                PlayerData playerData = new PlayerData
                {
                    actorNumber = PhotonNetwork.LocalPlayer.ActorNumber,
                    userId = role.usuario_id,
                    roleId = role.rol_id,
                    roleName = role.rol.nombre,
                    isReady = true,
                    isSpeaking = false,
                    audioEnabled = true,
                    microphoneEnabled = false,
                    volume = 1f,
                    position = Vector3.zero,
                    rotation = Vector3.zero
                };

                playersData[PhotonNetwork.LocalPlayer.ActorNumber] = playerData;
                UpdatePlayerData(playerData);
            }
        }

        #endregion

        #region Public Methods

        public bool IsConnectedToPhoton()
        {
            return isConnectedToPhoton;
        }

        public bool IsInRoom()
        {
            return isInRoom;
        }

        public string GetCurrentRoomName()
        {
            return currentRoomName;
        }

        public int GetPlayerCount()
        {
            return playersData.Count;
        }

        public void SendPlayerAction(string action, string data)
        {
            if (isInRoom)
            {
                photonView.RPC("SyncPlayerAction", RpcTarget.Others, action, data);
            }
        }

        public void SendDialogState(string dialogState)
        {
            if (isInRoom)
            {
                photonView.RPC("SyncDialogState", RpcTarget.Others, dialogState);
            }
        }

        #endregion

        #region Debug

        private void OnGUI()
        {
            if (!showNetworkStatus) return;

            GUILayout.BeginArea(new Rect(10, 200, 300, 200));
            GUILayout.Label("=== Photon Network Status ===");
            GUILayout.Label($"Connected: {isConnectedToPhoton}");
            GUILayout.Label($"In Room: {isInRoom}");
            GUILayout.Label($"Room Name: {currentRoomName}");
            GUILayout.Label($"Players: {playersData.Count}");

            if (GUILayout.Button("Connect to Photon"))
            {
                ConnectToPhoton();
            }

            if (GUILayout.Button("Disconnect from Photon"))
            {
                DisconnectFromPhoton();
            }

            GUILayout.EndArea();
        }

        #endregion
    }

    #region Data Classes

    [System.Serializable]
    public class PlayerData
    {
        public int actorNumber;
        public int userId;
        public int roleId;
        public string roleName;
        public bool isReady;
        public bool isSpeaking;
        public bool audioEnabled;
        public bool microphoneEnabled;
        public float volume;
        public Vector3 position;
        public Vector3 rotation;
    }

    #endregion
}
