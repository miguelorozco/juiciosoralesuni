using System.Collections;
using UnityEngine;
using Photon.Pun;
using Photon.Realtime;
using ExitGames.Client.Photon;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Integration
{
    /// <summary>
    /// Integración completa entre Unity, Photon PUN2, PeerJS y Laravel
    /// </summary>
    public class UnityLaravelIntegration : MonoBehaviourPunCallbacks
    {
        [Header("Configuración")]
        public int sesionId = 1;
        public string roomName = "SalaJuicio";
        public int maxPlayers = 10;
        
        [Header("Referencias")]
        public LaravelAPI laravelAPI;
        public DialogoUI dialogoUI;
        
        private bool isPhotonConnected = false;
        private bool isLaravelConnected = false;
        private string currentRoomId;
        
        // Eventos
        public static event System.Action<bool> OnIntegrationReady;
        public static event System.Action<string> OnIntegrationError;
        
        private void Start()
        {
            // Suscribirse a eventos de Laravel
            LaravelAPI.OnUserLoggedIn += OnLaravelUserLoggedIn;
            LaravelAPI.OnError += OnLaravelError;
            
            // Inicializar integración
            StartCoroutine(InitializeIntegration());
        }
        
        private void OnDestroy()
        {
            // Desuscribirse de eventos
            LaravelAPI.OnUserLoggedIn -= OnLaravelUserLoggedIn;
            LaravelAPI.OnError -= OnLaravelError;
        }
        
        #region Inicialización
        
        private IEnumerator InitializeIntegration()
        {
            Debug.Log("Iniciando integración Unity + Laravel + Photon...");
            
            // Paso 1: Conectar a Photon
            yield return StartCoroutine(ConnectToPhoton());
            
            // Paso 2: Esperar a que Laravel esté conectado
            yield return new WaitUntil(() => isLaravelConnected);
            
            // Paso 3: Crear o unirse a sala
            yield return StartCoroutine(SetupRoom());
            
            // Paso 4: Inicializar PeerJS (se hace desde JavaScript)
            InitializePeerJS();
            
            Debug.Log("Integración completada exitosamente");
            OnIntegrationReady?.Invoke(true);
        }
        
        private IEnumerator ConnectToPhoton()
        {
            if (!PhotonNetwork.IsConnected)
            {
                PhotonNetwork.ConnectUsingSettings();
                yield return new WaitUntil(() => PhotonNetwork.IsConnected);
            }
            
            isPhotonConnected = true;
            Debug.Log("Conectado a Photon");
        }
        
        private IEnumerator SetupRoom()
        {
            // Intentar unirse a sala existente
            if (PhotonNetwork.InLobby)
            {
                PhotonNetwork.JoinRoom(roomName);
                yield return new WaitUntil(() => PhotonNetwork.InRoom || PhotonNetwork.JoinRoomFailed);
                
                if (!PhotonNetwork.InRoom)
                {
                    // Crear nueva sala si no existe
                    RoomOptions roomOptions = new RoomOptions
                    {
                        MaxPlayers = maxPlayers,
                        IsOpen = true,
                        IsVisible = true
                    };
                    
                    PhotonNetwork.CreateRoom(roomName, roomOptions);
                    yield return new WaitUntil(() => PhotonNetwork.InRoom);
                }
            }
            
            currentRoomId = PhotonNetwork.CurrentRoom.Name;
            Debug.Log($"En sala: {currentRoomId}");
        }
        
        #endregion
        
        #region Callbacks de Photon
        
        public override void OnJoinedRoom()
        {
            Debug.Log($"Unido a sala: {PhotonNetwork.CurrentRoom.Name}");
            
            // Notificar a Laravel que estamos en la sala
            if (laravelAPI != null)
            {
                laravelAPI.JoinRoom(PhotonNetwork.CurrentRoom.Name);
            }
            
            // Inicializar PeerJS desde JavaScript
            #if UNITY_WEBGL && !UNITY_EDITOR
            string roomId = PhotonNetwork.CurrentRoom.Name;
            int actorId = PhotonNetwork.LocalPlayer.ActorNumber;
            Application.ExternalCall("initVoiceCall", roomId, actorId);
            #endif
        }
        
        public override void OnPlayerEnteredRoom(Player newPlayer)
        {
            Debug.Log($"Jugador entró: {newPlayer.NickName}");
            
            // Notificar a PeerJS sobre nuevo jugador
            #if UNITY_WEBGL && !UNITY_EDITOR
            Application.ExternalCall("onPlayerJoined", newPlayer.ActorNumber);
            #endif
        }
        
        public override void OnPlayerLeftRoom(Player otherPlayer)
        {
            Debug.Log($"Jugador salió: {otherPlayer.NickName}");
            
            // Notificar a PeerJS sobre jugador que salió
            #if UNITY_WEBGL && !UNITY_EDITOR
            Application.ExternalCall("onPlayerLeft", otherPlayer.ActorNumber);
            #endif
        }
        
        #endregion
        
        #region Callbacks de Laravel
        
        private void OnLaravelUserLoggedIn(UserData user)
        {
            isLaravelConnected = true;
            Debug.Log($"Laravel conectado: {user.name}");
            
            // Configurar sesión en Laravel
            if (laravelAPI != null)
            {
                laravelAPI.GetDialogoEstado(sesionId);
            }
        }
        
        private void OnLaravelError(string error)
        {
            Debug.LogError($"Error de Laravel: {error}");
            OnIntegrationError?.Invoke(error);
        }
        
        #endregion
        
        #region PeerJS Integration
        
        private void InitializePeerJS()
        {
            #if UNITY_WEBGL && !UNITY_EDITOR
            // PeerJS se inicializa desde JavaScript en el HTML template
            Debug.Log("PeerJS se inicializará desde JavaScript");
            #else
            Debug.Log("PeerJS solo funciona en WebGL builds");
            #endif
        }
        
        /// <summary>
        /// Llamado desde JavaScript cuando PeerJS está listo
        /// </summary>
        public void OnVoiceReady(string myPeerId)
        {
            Debug.Log($"PeerJS listo: {myPeerId}");
            
            // Compartir PeerID con otros jugadores vía Photon RPC
            photonView.RPC("RecibirPeerId", RpcTarget.Others, myPeerId);
        }
        
        [PunRPC]
        public void RecibirPeerId(string peerId)
        {
            Debug.Log($"Recibido PeerID: {peerId}");
            
            // Llamar a JavaScript para conectar con este peer
            #if UNITY_WEBGL && !UNITY_EDITOR
            Application.ExternalCall("callPeer", peerId);
            #endif
        }
        
        #endregion
        
        #region Sincronización de Datos
        
        /// <summary>
        /// Sincronizar posición del jugador con Laravel
        /// </summary>
        public void SyncPlayerPosition(Vector3 position, Vector3 rotation)
        {
            if (laravelAPI != null && !string.IsNullOrEmpty(currentRoomId))
            {
                // Enviar posición a Laravel para sincronización
                // Esto se implementaría en LaravelAPI.cs
                Debug.Log($"Sincronizando posición: {position}");
            }
        }
        
        /// <summary>
        /// Sincronizar estado de audio con Laravel
        /// </summary>
        public void SyncAudioState(bool microfonoActivo, bool audioEnabled, float volumen = 1.0f)
        {
            if (laravelAPI != null && !string.IsNullOrEmpty(currentRoomId))
            {
                // Enviar estado de audio a Laravel
                Debug.Log($"Sincronizando audio: mic={microfonoActivo}, audio={audioEnabled}, vol={volumen}");
            }
        }
        
        #endregion
        
        #region Métodos Públicos
        
        /// <summary>
        /// Obtener estado de la integración
        /// </summary>
        public bool IsIntegrationReady()
        {
            return isPhotonConnected && isLaravelConnected && PhotonNetwork.InRoom;
        }
        
        /// <summary>
        /// Obtener información de la sala actual
        /// </summary>
        public string GetCurrentRoomInfo()
        {
            if (PhotonNetwork.InRoom)
            {
                return $"Sala: {PhotonNetwork.CurrentRoom.Name}, Jugadores: {PhotonNetwork.CurrentRoom.PlayerCount}/{PhotonNetwork.CurrentRoom.MaxPlayers}";
            }
            return "No en sala";
        }
        
        /// <summary>
        /// Forzar reconexión
        /// </summary>
        public void Reconnect()
        {
            StartCoroutine(InitializeIntegration());
        }
        
        #endregion
        
        #region Debug
        
        private void OnGUI()
        {
            if (Application.isEditor || Debug.isDebugBuild)
            {
                GUILayout.BeginArea(new Rect(10, 10, 300, 200));
                GUILayout.Label("Unity + Laravel + Photon Integration");
                GUILayout.Label($"Photon: {(isPhotonConnected ? "Conectado" : "Desconectado")}");
                GUILayout.Label($"Laravel: {(isLaravelConnected ? "Conectado" : "Desconectado")}");
                GUILayout.Label($"Sala: {(PhotonNetwork.InRoom ? PhotonNetwork.CurrentRoom.Name : "No en sala")}");
                GUILayout.Label($"Jugadores: {(PhotonNetwork.InRoom ? PhotonNetwork.CurrentRoom.PlayerCount : 0)}");
                
                if (GUILayout.Button("Reconectar"))
                {
                    Reconnect();
                }
                
                GUILayout.EndArea();
            }
        }
        
        #endregion
    }
}
