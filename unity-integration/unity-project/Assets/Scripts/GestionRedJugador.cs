using Photon.Pun;
using Photon.Realtime;
using UnityEngine;
using System;
using System.Collections;
using ExitGames.Client.Photon;
using JuiciosSimulator.API;
using JuiciosSimulator;
using JuiciosSimulator.Dialogue;
using JuiciosSimulator.Utils;

public class GestionRedJugador : MonoBehaviourPunCallbacks
{
    [Header("Referencias del Sistema")]
    public LaravelAPI laravelAPI;
    public GameInitializer gameInitializer;

    [Header("Configuración de Sala")]
    public string sessionRoomName = "main"; // Nombre de la sala de sesión

    private bool isConnecting = false; // Flag para evitar múltiples conexiones
    private bool hasConnected = false; // Flag para evitar reconexiones
    private bool playerInstantiated = false; // Flag para evitar múltiples instanciaciones del Player
    private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones

    void Start()
    {
        // Retrasar inicialización para evitar recursión durante la carga de Unity
        StartCoroutine(InitializeDelayed());
    }

    private System.Collections.IEnumerator InitializeDelayed()
    {
        // ESPERAR a que LaravelAPI esté completamente inicializado
        float timeout = 5f;
        float elapsed = 0f;
        
        while (!LaravelAPI.IsInitialized && elapsed < timeout)
        {
            yield return null;
            elapsed += Time.deltaTime;
        }

        // Esperar varios frames adicionales para que Unity termine de inicializar todos los scripts
        yield return new WaitForEndOfFrame();
        yield return new WaitForEndOfFrame();
        yield return new WaitForEndOfFrame();
        
        // Obtener referencias si no están asignadas (usar Instance cuando sea posible)
        if (laravelAPI == null)
            laravelAPI = LaravelAPI.Instance;
        
        // Esperar otro frame antes de buscar más objetos
        yield return new WaitForEndOfFrame();
        
        if (gameInitializer == null)
            gameInitializer = FindObjectOfType<GameInitializer>();

        // Suscribirse a eventos de LaravelAPI (solo una vez)
        if (!isSubscribedToEvents)
        {
            LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;
            LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
            isSubscribedToEvents = true;
        }

        // Solo conectar si no hay otra instancia conectándose
        if (!isConnecting && !hasConnected && !PhotonNetwork.IsConnected)
        {
            ConnectToPhoton();
        }
    }

    void OnDestroy()
    {
        // Desuscribirse de eventos
        LaravelAPI.OnActiveSessionReceived -= OnActiveSessionReceived;
        LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
    }

    private void OnUserLoggedIn(UserData user)
    {
        Debug.Log($"Usuario logueado en GestionRedJugador: {user.name}");
        // La sesión se obtendrá automáticamente después del login
    }

    private int? lastProcessedSessionId = null; // ID de la última sesión procesada

    private void OnActiveSessionReceived(SessionData sessionData)
    {
        // Validaciones null críticas
        if (sessionData == null || sessionData.session == null)
        {
            Debug.LogError("❌ OnActiveSessionReceived: sessionData o session es null");
            return;
        }

        // Prevenir procesamiento múltiple de la misma sesión
        if (lastProcessedSessionId.HasValue && 
            lastProcessedSessionId.Value == sessionData.session.id)
        {
            Debug.LogWarning($"[GestionRedJugador] Sesión {sessionData.session.id} ya procesada. Ignorando evento duplicado.");
            return;
        }

        lastProcessedSessionId = sessionData.session.id;
        Debug.Log($"Sesión activa recibida: {sessionData.session.nombre ?? "Sin nombre"}");

        // Si estamos en el lobby pero no en sala, unirse a la sala
        if (PhotonNetwork.InLobby && !PhotonNetwork.InRoom)
        {
            JoinSessionRoom();
        }
    }

    void ConnectToPhoton()
    {
        // Prevenir múltiples conexiones simultáneas
        if (isConnecting || hasConnected || PhotonNetwork.IsConnected)
        {
            Debug.LogWarning("[GestionRedJugador] Ya hay una conexión en progreso o activa. Ignorando llamada.");
            return;
        }

        isConnecting = true;
        Debug.Log("[GestionRedJugador] Conectando a Photon...");
        PhotonNetwork.ConnectUsingSettings();
    }

    public override void OnConnectedToMaster()
    {
        if (hasConnected)
        {
            Debug.LogWarning("[GestionRedJugador] Ya conectado al Master Server. Ignorando callback duplicado.");
            return;
        }

        isConnecting = false;
        hasConnected = true;
        Debug.Log("[GestionRedJugador] Conectado al Master Server. Entrando al lobby...");
        PhotonNetwork.JoinLobby();
    }

    public override void OnJoinedLobby()
    {
        Debug.Log("Unido al Lobby. Uniéndose a la sala...");
        JoinSessionRoom();
    }

    public void JoinSessionRoom()
    {
        // Prevenir múltiples intentos de unirse a la sala
        if (PhotonNetwork.InRoom)
        {
            Debug.LogWarning($"[GestionRedJugador] Ya estamos en una sala: {PhotonNetwork.CurrentRoom.Name}. Ignorando llamada.");
            return;
        }

        if (!PhotonNetwork.InLobby)
        {
            Debug.LogWarning("[GestionRedJugador] No estamos en el lobby. Esperando a unirse al lobby primero.");
            return;
        }

        Debug.Log($"[GestionRedJugador] Uniéndose a la sala de sesión: {sessionRoomName}");

        // Intentar unirse a la sala existente primero
        PhotonNetwork.JoinRoom(sessionRoomName);
    }

    public override void OnJoinRoomFailed(short returnCode, string message)
    {
        Debug.Log($"No se pudo unir a la sala {sessionRoomName}. Creando nueva sala...");

        // Crear la sala si no existe
        RoomOptions roomOptions = new RoomOptions
        {
            MaxPlayers = 20,
            IsVisible = true,
            IsOpen = true
        };

        PhotonNetwork.CreateRoom(sessionRoomName, roomOptions, TypedLobby.Default);
    }

    public override void OnJoinedRoom()
    {
        DebugLogger.LogEvent("GestionRedJugador.OnJoinedRoom", $"Sala: {PhotonNetwork.CurrentRoom?.Name ?? "N/A"}", new {
            roomName = PhotonNetwork.CurrentRoom?.Name,
            playerCount = PhotonNetwork.CurrentRoom?.PlayerCount ?? 0,
            playerInstantiated
        });

        // Protección contra recursión
        if (!RecursionProtection.CanInitialize("GestionRedJugador.OnJoinedRoom"))
        {
            DebugLogger.LogError("GestionRedJugador", "⚠️ Recursión detectada en OnJoinedRoom(). Abortando.");
            Debug.LogError("[GestionRedJugador] ⚠️ Recursión detectada en OnJoinedRoom(). Abortando.");
            return;
        }

        RecursionProtection.BeginInitialization("GestionRedJugador.OnJoinedRoom");

        try
        {
            // Prevenir múltiples llamadas
            if (PhotonNetwork.CurrentRoom == null)
            {
                Debug.LogWarning("[GestionRedJugador] OnJoinedRoom llamado pero CurrentRoom es null. Ignorando.");
                return;
            }

            // Prevenir múltiples instanciaciones del Player
            if (playerInstantiated)
            {
                Debug.LogWarning("[GestionRedJugador] ⚠️ Player ya fue instanciado. Ignorando OnJoinedRoom() duplicado.");
                return;
            }

            Debug.Log($"[GestionRedJugador] Unido a una sala: {PhotonNetwork.CurrentRoom.Name}. Buscando Player...");

            // Buscar y reclamar un Player disponible
            GameObject player = FindAndClaimAnyAvailablePlayer();
            if (player != null)
            {
                Debug.Log($"[GestionRedJugador] Player encontrado: {player.name}");
                
                // Transferir ownership al jugador local si es necesario
                var photonView = player.GetComponent<PhotonView>();
                if (photonView != null && !photonView.IsMine)
                {
                    photonView.TransferOwnership(PhotonNetwork.LocalPlayer);
                    Debug.Log($"[GestionRedJugador] Ownership transferido para {player.name}");
                }

                playerInstantiated = true;
                StartCoroutine(ConfigurePlayerAfterInstantiate(player));
            }
            else
            {
                Debug.LogError("[GestionRedJugador] ❌ No se encontró ningún Player disponible en la escena.");
            }

            // Inicializar el sistema de audio
            InitializeAudioSystem();
        }
        finally
        {
            RecursionProtection.EndInitialization("GestionRedJugador.OnJoinedRoom");
        }
    }

    /// <summary>
    /// Configura el jugador después de que se instancia, esperando a que PhotonView se configure correctamente
    /// </summary>
    private IEnumerator ConfigurePlayerAfterInstantiate(GameObject playerPrefab)
    {
        // Esperar un frame para que PhotonView.IsMine se configure correctamente
        yield return null;
        
        if (playerPrefab == null)
        {
            Debug.LogError($"[GestionRedJugador] ❌ Prefab es null después de esperar un frame!");
            yield break;
        }

        Debug.Log($"[GestionRedJugador] ✅ Prefab instanciado: {playerPrefab.name}");
        
        // Verificar componentes
        var playerController = playerPrefab.GetComponent<PlayerController>();
        var playerInputHandler = playerPrefab.GetComponent<PlayerInputHandler>();
        var photonView = playerPrefab.GetComponent<PhotonView>();
        var controlCamara = playerPrefab.GetComponent<ControlCamaraJugador>();
        
        Debug.Log($"[GestionRedJugador] PlayerController encontrado: {playerController != null}");
        Debug.Log($"[GestionRedJugador] PlayerInputHandler encontrado: {playerInputHandler != null}");
        Debug.Log($"[GestionRedJugador] PhotonView encontrado: {photonView != null}");
        Debug.Log($"[GestionRedJugador] ControlCamaraJugador encontrado: {controlCamara != null}");
        
        // Si es SimplePlayer y no tiene PhotonView, agregarlo
        if (playerPrefab.name == "SimplePlayer" && photonView == null)
        {
            Debug.LogWarning($"[GestionRedJugador] ⚠️ SimplePlayer no tiene PhotonView. Agregándolo...");
            photonView = playerPrefab.AddComponent<PhotonView>();
            if (PhotonNetwork.IsConnected)
            {
                PhotonNetwork.AllocateViewID(photonView);
            }
            Debug.Log($"[GestionRedJugador] ✅ PhotonView agregado a SimplePlayer con viewID: {photonView.ViewID}");
        }
        
        if (photonView != null)
        {
            Debug.Log($"[GestionRedJugador] PhotonView.IsMine (después de esperar): {photonView.IsMine}");
        }
        
        // Si no existe PlayerController, agregarlo programáticamente
        if (playerController == null)
        {
            Debug.LogWarning($"[GestionRedJugador] ⚠️ PlayerController NO encontrado, agregándolo programáticamente...");
            playerController = playerPrefab.AddComponent<PlayerController>();
            Debug.Log($"[GestionRedJugador] ✅ PlayerController agregado: {playerController != null}");
            
            // Esperar otro frame para que el componente se inicialice
            yield return null;
        }
        
        // Si no existe PlayerInputHandler, agregarlo programáticamente
        if (playerInputHandler == null)
        {
            Debug.LogWarning($"[GestionRedJugador] ⚠️ PlayerInputHandler NO encontrado, agregándolo programáticamente...");
            playerInputHandler = playerPrefab.AddComponent<PlayerInputHandler>();
            if (playerController != null)
            {
                playerInputHandler.playerController = playerController;
            }
            Debug.Log($"[GestionRedJugador] ✅ PlayerInputHandler agregado: {playerInputHandler != null}");
        }
        else if (playerController != null)
        {
            // Asegurar que PlayerInputHandler tenga la referencia correcta
            playerInputHandler.playerController = playerController;
        }
        
        if (playerController == null)
        {
            Debug.LogError($"[GestionRedJugador] ❌ No se pudo crear PlayerController!");
        }

        // CRÍTICO: Inicializar la cámara DESPUÉS de confirmar el ownership
        if (controlCamara != null)
        {
            Debug.Log($"[GestionRedJugador] Inicializando cámara para {playerPrefab.name}");
            controlCamara.InitializeCamera();
        }
        else
        {
            Debug.LogWarning($"[GestionRedJugador] ⚠️ No se encontró ControlCamaraJugador en {playerPrefab.name}");
        }
    }

    /// <summary>
    /// Busca cualquier Player disponible en la escena (prioridad: SimplePlayer)
    /// </summary>
    private GameObject FindAndClaimAnyAvailablePlayer()
    {
        Debug.Log("[GestionRedJugador] Buscando Player disponible...");
        
        // PRIORIDAD 1: Buscar SimplePlayer
        GameObject simplePlayer = GameObject.Find("SimplePlayer");
        if (simplePlayer != null)
        {
            Debug.Log("[GestionRedJugador] ✅ Encontrado SimplePlayer");
            
            // Verificar que el player esté activo
            if (!simplePlayer.activeSelf)
            {
                Debug.LogWarning("[GestionRedJugador] ⚠️ SimplePlayer está DESACTIVADO. Activándolo...");
                simplePlayer.SetActive(true);
            }
            
            // Asegurar que tiene PhotonView
            var pv = simplePlayer.GetComponent<PhotonView>();
            if (pv == null)
            {
                Debug.LogWarning("[GestionRedJugador] ⚠️ SimplePlayer no tiene PhotonView. Agregándolo...");
                pv = simplePlayer.AddComponent<PhotonView>();
                if (PhotonNetwork.IsConnected)
                {
                    PhotonNetwork.AllocateViewID(pv);
                }
            }
            
            return simplePlayer;
        }
        
        // PRIORIDAD 2: Buscar cualquier Player con tag "Player"
        GameObject[] players = GameObject.FindGameObjectsWithTag("Player");
        foreach (var player in players)
        {
            if (player == null || !player.activeSelf) continue;
            
            // Asegurar que tiene PhotonView
            var pv = player.GetComponent<PhotonView>();
            if (pv == null)
            {
                pv = player.AddComponent<PhotonView>();
                if (PhotonNetwork.IsConnected)
                {
                    PhotonNetwork.AllocateViewID(pv);
                }
            }
            
            Debug.Log($"[GestionRedJugador] ✅ Encontrado Player: {player.name}");
            return player;
        }
        
        Debug.LogError("[GestionRedJugador] ❌ No se encontró ningún Player disponible en la escena!");
        return null;
    }

    private void InitializeAudioSystem()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        string roomId = PhotonNetwork.CurrentRoom.Name;
        int actorId = PhotonNetwork.LocalPlayer.ActorNumber;
        Application.ExternalCall("initVoiceCall", roomId, actorId);
#endif
    }


    // Unity -> JavaScript (Enviar PeerID)
    public void OnVoiceReady(string myPeerId)
    {
        Debug.Log("Mi PeerJS ID es: " + myPeerId);

        // Puedes compartirlo con los demás vía RPC o guardar localmente
        photonView.RPC("RecibirPeerId", RpcTarget.Others, myPeerId);
    }

    [PunRPC]
    public void RecibirPeerId(string peerId)
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        Application.ExternalCall("callPeer", peerId);
#endif
    }
}


