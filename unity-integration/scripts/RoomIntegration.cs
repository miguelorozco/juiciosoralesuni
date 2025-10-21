using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using System;
using Newtonsoft.Json;

namespace JuiciosSimulator.Room
{
    /// <summary>
    /// Integración de salas Unity con Laravel
    /// Este script se integra con tu RoomManager existente
    /// </summary>
    public class RoomIntegration : MonoBehaviour
    {
        [Header("Configuración de Sala")]
        public string roomId;
        public string roomName;
        public int maxPlayers = 10;
        public bool isHost = false;
        
        [Header("Referencias")]
        public RoomManager roomManager; // Tu RoomManager existente
        public AudioManager audioManager; // Tu AudioManager existente
        public PlayerController[] players; // Tus PlayerControllers existentes
        
        [Header("Estado de la Sala")]
        public bool isConnected = false;
        public bool isRoomActive = false;
        public int currentPlayerCount = 0;
        
        // Eventos
        public static event Action<string> OnRoomCreated;
        public static event Action<string> OnRoomJoined;
        public static event Action OnRoomLeft;
        public static event Action<PlayerData> OnPlayerJoined;
        public static event Action<int> OnPlayerLeft;
        public static event Action<RoomState> OnRoomStateUpdated;
        
        // Singleton
        public static RoomIntegration Instance { get; private set; }
        
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
            // Suscribirse a eventos de Laravel
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            LaravelAPI.OnError += OnError;
            
            // Suscribirse a eventos de tu RoomManager existente
            if (roomManager != null)
            {
                // Aquí te suscribes a los eventos de tu RoomManager
                // roomManager.OnPlayerJoined += OnPlayerJoinedRoom;
                // roomManager.OnPlayerLeft += OnPlayerLeftRoom;
            }
        }
        
        private void OnDestroy()
        {
            LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
            LaravelAPI.OnError -= OnError;
        }
        
        #region Integración con Laravel
        
        /// <summary>
        /// Crear sala en Laravel y sincronizar con Unity
        /// </summary>
        public void CreateRoom(string roomName, int maxPlayers = 10)
        {
            StartCoroutine(CreateRoomCoroutine(roomName, maxPlayers));
        }
        
        private IEnumerator CreateRoomCoroutine(string roomName, int maxPlayers)
        {
            var roomData = new CreateRoomRequest
            {
                nombre = roomName,
                max_participantes = maxPlayers,
                configuracion = GetRoomConfiguration(),
                audio_config = GetAudioConfiguration()
            };
            
            string jsonData = JsonConvert.SerializeObject(roomData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{LaravelAPI.Instance.baseURL}/unity/rooms/create", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<RoomResponse>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        this.roomId = response.data.room_id;
                        this.roomName = response.data.nombre;
                        this.maxPlayers = response.data.max_participantes;
                        this.isHost = true;
                        this.isRoomActive = true;
                        
                        // Crear sala en Unity
                        CreateUnityRoom();
                        
                        OnRoomCreated?.Invoke(this.roomId);
                        Debug.Log($"Sala creada: {this.roomId}");
                    }
                    else
                    {
                        Debug.LogError($"Error al crear sala: {response.message}");
                    }
                }
                else
                {
                    Debug.LogError($"Error de conexión: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Unirse a una sala existente
        /// </summary>
        public void JoinRoom(string roomId)
        {
            StartCoroutine(JoinRoomCoroutine(roomId));
        }
        
        private IEnumerator JoinRoomCoroutine(string roomId)
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{LaravelAPI.Instance.baseURL}/unity/rooms/{roomId}/join"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<RoomResponse>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        this.roomId = response.data.room_id;
                        this.roomName = response.data.nombre;
                        this.maxPlayers = response.data.max_participantes;
                        this.isHost = false;
                        this.isRoomActive = true;
                        
                        // Unirse a sala en Unity
                        JoinUnityRoom();
                        
                        OnRoomJoined?.Invoke(this.roomId);
                        Debug.Log($"Unido a sala: {this.roomId}");
                    }
                    else
                    {
                        Debug.LogError($"Error al unirse a sala: {response.message}");
                    }
                }
                else
                {
                    Debug.LogError($"Error de conexión: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Salir de la sala
        /// </summary>
        public void LeaveRoom()
        {
            StartCoroutine(LeaveRoomCoroutine());
        }
        
        private IEnumerator LeaveRoomCoroutine()
        {
            if (string.IsNullOrEmpty(roomId)) yield break;
            
            using (UnityWebRequest request = new UnityWebRequest($"{LaravelAPI.Instance.baseURL}/unity/rooms/{roomId}/leave", "POST"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                // Salir de sala en Unity
                LeaveUnityRoom();
                
                this.roomId = "";
                this.isRoomActive = false;
                this.isHost = false;
                
                OnRoomLeft?.Invoke();
                Debug.Log("Salió de la sala");
            }
        }
        
        #endregion
        
        #region Integración con Unity RoomManager
        
        /// <summary>
        /// Crear sala en Unity (integra con tu RoomManager)
        /// </summary>
        private void CreateUnityRoom()
        {
            if (roomManager != null)
            {
                // Llamar a tu método de crear sala
                // roomManager.CreateRoom(roomId, roomName, maxPlayers);
                
                // O si tienes un método diferente:
                // roomManager.InitializeRoom(roomId);
            }
            
            // Configurar audio
            if (audioManager != null)
            {
                ConfigureAudioForRoom();
            }
        }
        
        /// <summary>
        /// Unirse a sala en Unity
        /// </summary>
        private void JoinUnityRoom()
        {
            if (roomManager != null)
            {
                // Llamar a tu método de unirse a sala
                // roomManager.JoinRoom(roomId);
            }
            
            // Configurar audio
            if (audioManager != null)
            {
                ConfigureAudioForRoom();
            }
        }
        
        /// <summary>
        /// Salir de sala en Unity
        /// </summary>
        private void LeaveUnityRoom()
        {
            if (roomManager != null)
            {
                // Llamar a tu método de salir de sala
                // roomManager.LeaveRoom();
            }
            
            // Limpiar audio
            if (audioManager != null)
            {
                CleanupAudio();
            }
        }
        
        #endregion
        
        #region Sincronización de Jugadores
        
        /// <summary>
        /// Sincronizar jugador con Laravel
        /// </summary>
        public void SyncPlayer(PlayerData playerData)
        {
            StartCoroutine(SyncPlayerCoroutine(playerData));
        }
        
        private IEnumerator SyncPlayerCoroutine(PlayerData playerData)
        {
            var syncData = new PlayerSyncRequest
            {
                usuario_id = playerData.usuarioId,
                posicion = new float[] { playerData.position.x, playerData.position.y, playerData.position.z },
                rotacion = new float[] { playerData.rotation.x, playerData.rotation.y, playerData.rotation.z },
                audio_enabled = playerData.audioEnabled,
                microfono_activo = playerData.microfonoActivo,
                metadata = playerData.metadata
            };
            
            string jsonData = JsonConvert.SerializeObject(syncData);
            
            using (UnityWebRequest request = new UnityWebRequest($"{LaravelAPI.Instance.baseURL}/unity/rooms/{roomId}/sync-player", "POST"))
            {
                byte[] bodyRaw = Encoding.UTF8.GetBytes(jsonData);
                request.uploadHandler = new UploadHandlerRaw(bodyRaw);
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Content-Type", "application/json");
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result != UnityWebRequest.Result.Success)
                {
                    Debug.LogError($"Error al sincronizar jugador: {request.error}");
                }
            }
        }
        
        /// <summary>
        /// Obtener estado actual de la sala
        /// </summary>
        public void GetRoomState()
        {
            StartCoroutine(GetRoomStateCoroutine());
        }
        
        private IEnumerator GetRoomStateCoroutine()
        {
            using (UnityWebRequest request = UnityWebRequest.Get($"{LaravelAPI.Instance.baseURL}/unity/rooms/{roomId}/state"))
            {
                request.downloadHandler = new DownloadHandlerBuffer();
                request.SetRequestHeader("Authorization", $"Bearer {LaravelAPI.Instance.authToken}");
                
                yield return request.SendWebRequest();
                
                if (request.result == UnityWebRequest.Result.Success)
                {
                    var response = JsonConvert.DeserializeObject<APIResponse<RoomState>>(request.downloadHandler.text);
                    
                    if (response.success)
                    {
                        UpdateRoomState(response.data);
                    }
                }
            }
        }
        
        #endregion
        
        #region Configuración de Audio
        
        /// <summary>
        /// Configurar audio para la sala
        /// </summary>
        private void ConfigureAudioForRoom()
        {
            if (audioManager != null)
            {
                // Configurar audio según la configuración de Laravel
                // audioManager.SetSpatialAudio(true);
                // audioManager.SetMaxDistance(10f);
                // audioManager.SetEchoCancellation(true);
            }
        }
        
        /// <summary>
        /// Limpiar configuración de audio
        /// </summary>
        private void CleanupAudio()
        {
            if (audioManager != null)
            {
                // Limpiar configuración de audio
                // audioManager.StopAllAudio();
            }
        }
        
        /// <summary>
        /// Obtener configuración de audio para Laravel
        /// </summary>
        private Dictionary<string, object> GetAudioConfiguration()
        {
            return new Dictionary<string, object>
            {
                {"spatial_audio", true},
                {"max_distance", 10.0f},
                {"echo_cancellation", true},
                {"noise_suppression", true},
                {"volume_multiplier", 1.0f}
            };
        }
        
        #endregion
        
        #region Configuración de Sala
        
        /// <summary>
        /// Obtener configuración de sala para Laravel
        /// </summary>
        private Dictionary<string, object> GetRoomConfiguration()
        {
            return new Dictionary<string, object>
            {
                {"unity_version", Application.unityVersion},
                {"platform", Application.platform.ToString()},
                {"scene_name", UnityEngine.SceneManagement.SceneManager.GetActiveScene().name},
                {"max_players", maxPlayers},
                {"room_type", "juicio_simulator"}
            };
        }
        
        #endregion
        
        #region Event Handlers
        
        private void OnUserLoggedIn(UserData user)
        {
            // Usuario logueado, listo para crear/unirse a salas
            Debug.Log($"Usuario logueado: {user.name}, listo para salas");
        }
        
        private void OnError(string error)
        {
            Debug.LogError($"Error en RoomIntegration: {error}");
        }
        
        private void UpdateRoomState(RoomState roomState)
        {
            currentPlayerCount = roomState.participantes_conectados;
            isRoomActive = roomState.estado == "activa";
            
            OnRoomStateUpdated?.Invoke(roomState);
        }
        
        #endregion
        
        #region Public Methods
        
        /// <summary>
        /// Verificar si la sala está activa
        /// </summary>
        public bool IsRoomActive()
        {
            return isRoomActive && !string.IsNullOrEmpty(roomId);
        }
        
        /// <summary>
        /// Obtener información de la sala
        /// </summary>
        public RoomInfo GetRoomInfo()
        {
            return new RoomInfo
            {
                roomId = this.roomId,
                roomName = this.roomName,
                maxPlayers = this.maxPlayers,
                currentPlayers = this.currentPlayerCount,
                isHost = this.isHost,
                isActive = this.isRoomActive
            };
        }
        
        #endregion
    }
    
    #region Clases de Datos
    
    [System.Serializable]
    public class CreateRoomRequest
    {
        public string nombre;
        public int max_participantes;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> audio_config;
    }
    
    [System.Serializable]
    public class RoomResponse
    {
        public string room_id;
        public string nombre;
        public int max_participantes;
        public int participantes_conectados;
        public string estado;
        public Dictionary<string, object> configuracion;
        public Dictionary<string, object> audio_config;
    }
    
    [System.Serializable]
    public class RoomState
    {
        public string room_id;
        public string nombre;
        public string estado;
        public int participantes_conectados;
        public int max_participantes;
        public List<PlayerData> participantes;
        public Dictionary<string, object> configuracion;
    }
    
    [System.Serializable]
    public class PlayerData
    {
        public int usuarioId;
        public string nombre;
        public Vector3 position;
        public Quaternion rotation;
        public bool audioEnabled;
        public bool microfonoActivo;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class PlayerSyncRequest
    {
        public int usuario_id;
        public float[] posicion;
        public float[] rotacion;
        public bool audio_enabled;
        public bool microfono_activo;
        public Dictionary<string, object> metadata;
    }
    
    [System.Serializable]
    public class RoomInfo
    {
        public string roomId;
        public string roomName;
        public int maxPlayers;
        public int currentPlayers;
        public bool isHost;
        public bool isActive;
    }
    
    #endregion
}

