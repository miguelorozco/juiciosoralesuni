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
    public string sessionRoomName = "SalaPrincipal"; // Nombre fijo para la sesión OXXO

    private bool hasAssignedRole = false;
    private string assignedRole = "";
    private bool isConnecting = false; // Flag para evitar múltiples conexiones
    private bool hasConnected = false; // Flag para evitar reconexiones
    private bool playerInstantiated = false; // Flag para evitar múltiples instanciaciones del Player

    private bool isSubscribedToEvents = false; // Flag para prevenir múltiples suscripciones
    private bool roleProvidedBySession = false; // Indica si el rol fue provisto por la sesión (vs fallback)

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

        // Suscribirse a eventos de LaravelAPI para obtener el rol cuando esté disponible (solo una vez)
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

    private int sessionReceivedCount = 0; // Contador para detectar múltiples llamadas
    private int? lastProcessedSessionId = null; // ID de la última sesión procesada

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

        // Prevenir procesamiento múltiple de la misma sesión
        if (lastProcessedSessionId.HasValue && 
            lastProcessedSessionId.Value == sessionData.session.id)
        {
            Debug.LogWarning($"[GestionRedJugador] Sesión {sessionData.session.id} ya procesada. Ignorando evento duplicado.");
            return;
        }

        sessionReceivedCount++;
        if (sessionReceivedCount > 1)
        {
            Debug.LogWarning($"[GestionRedJugador] OnActiveSessionReceived llamado {sessionReceivedCount} veces. Posible recursión.");
        }

        lastProcessedSessionId = sessionData.session.id;
        Debug.Log($"Sesión activa recibida en GestionRedJugador: {sessionData.session.nombre ?? "Sin nombre"}");
        
        // Actualizar el rol asignado
        if (sessionData.role != null)
        {
            assignedRole = sessionData.role.nombre ?? "Observador";
            hasAssignedRole = true;
            roleProvidedBySession = true;
            Debug.Log($"✅ Rol asignado actualizado: {assignedRole}");

            // Si ya estamos en una sala, actualizar el rol del jugador en Photon
            if (PhotonNetwork.InRoom)
            {
                ExitGames.Client.Photon.Hashtable playerProps = new ExitGames.Client.Photon.Hashtable() { { "Rol", assignedRole } };
                PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);
                Debug.Log($"✅ Rol actualizado en Photon: {assignedRole}");
            }
            // Si estamos en el lobby pero no en sala, unirse a la sala
            else if (PhotonNetwork.InLobby)
            {
                JoinSessionRoom();
            }
        }
        else
        {
            Debug.LogWarning("⚠️ Sesión activa recibida pero sin rol asignado - usando Observador por defecto");
            assignedRole = "Observador";
            hasAssignedRole = false;
            roleProvidedBySession = false;
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
        Debug.Log("Unido al Lobby. Obteniendo rol asignado desde Laravel...");

        // Intentar obtener el rol asignado desde la sesión activa
        GetAssignedRoleFromSession();
    }

    private void GetAssignedRoleFromSession()
    {
        // Primero intentar desde gameInitializer (puede estar disponible si ya se cargó)
        if (gameInitializer != null && gameInitializer.currentSessionData != null && gameInitializer.currentSessionData.role != null)
        {
            // Usar el rol de la sesión activa con validación null
            assignedRole = gameInitializer.currentSessionData.role.nombre ?? "Observador";
            hasAssignedRole = !string.IsNullOrEmpty(assignedRole);

            Debug.Log($"Rol asignado desde sesión (GameInitializer): {assignedRole}");

            // Unirse directamente a la sala de la sesión
            JoinSessionRoom();
        }
        // Si no está disponible, intentar desde LaravelAPI directamente
        else if (laravelAPI != null && laravelAPI.currentSessionData != null && laravelAPI.currentSessionData.role != null)
        {
            assignedRole = laravelAPI.currentSessionData.role.nombre ?? "Observador";
            hasAssignedRole = !string.IsNullOrEmpty(assignedRole);

            Debug.Log($"Rol asignado desde sesión (LaravelAPI): {assignedRole}");

            // Unirse directamente a la sala de la sesión
            JoinSessionRoom();
        }
        // Si aún no está disponible, esperar a que llegue el evento OnActiveSessionReceived
        else
        {
            Debug.LogWarning("No hay sesión activa disponible todavía. Esperando a que se cargue...");
            // El evento OnActiveSessionReceived se encargará de unirse a la sala cuando el rol esté disponible
            // Por ahora, usar rol por defecto temporalmente
            assignedRole = "Observador"; // Rol por defecto temporal
            hasAssignedRole = true;
            JoinSessionRoom();
        }
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

    public override void OnJoinRandomFailed(short returnCode, string message)
    {
        Debug.Log("No se pudo unir a una sala existente. Creando nueva sala...");
        RoomOptions roomOptions = new RoomOptions { MaxPlayers = 20 };
        string roomName = "Sala_" + UnityEngine.Random.Range(1000, 9999);
        PhotonNetwork.CreateRoom(roomName, roomOptions, TypedLobby.Default);

    }

    public override void OnJoinedRoom()
    {
        DebugLogger.LogEvent("GestionRedJugador.OnJoinedRoom", $"Sala: {PhotonNetwork.CurrentRoom?.Name ?? "N/A"}", new {
            roomName = PhotonNetwork.CurrentRoom?.Name,
            playerCount = PhotonNetwork.CurrentRoom?.PlayerCount ?? 0,
            playerInstantiated,
            assignedRole
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

            Debug.Log($"[GestionRedJugador] Unido a una sala: {PhotonNetwork.CurrentRoom.Name}. Configurando jugador con rol asignado...");

        // Asegurar que tenemos un rol asignado
        if (string.IsNullOrEmpty(assignedRole))
        {
            // Intentar obtener el rol nuevamente con validación null
            if (gameInitializer != null && gameInitializer.currentSessionData != null && gameInitializer.currentSessionData.role != null)
            {
                assignedRole = gameInitializer.currentSessionData.role.nombre ?? "Observador";
                hasAssignedRole = !string.IsNullOrEmpty(assignedRole);
                Debug.Log($"Rol obtenido desde GameInitializer: {assignedRole}");
            }
            else if (laravelAPI != null && laravelAPI.currentSessionData != null && laravelAPI.currentSessionData.role != null)
            {
                assignedRole = laravelAPI.currentSessionData.role.nombre ?? "Observador";
                hasAssignedRole = !string.IsNullOrEmpty(assignedRole);
                Debug.Log($"Rol obtenido desde LaravelAPI: {assignedRole}");
            }
            else
            {
                // Usar "Observador" como fallback pero no mostrar error
                assignedRole = "Observador";
                hasAssignedRole = true;
                Debug.LogWarning("No se pudo obtener el rol desde Laravel, usando 'Observador' por defecto temporalmente. El rol se actualizará cuando llegue la sesión activa.");
            }
        }

        // Configurar el rol del jugador en Photon
        if (hasAssignedRole && !string.IsNullOrEmpty(assignedRole))
        {
            ExitGames.Client.Photon.Hashtable playerProps = new ExitGames.Client.Photon.Hashtable() { { "Rol", assignedRole } };
            PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);

            Debug.Log($"✅ Jugador configurado con rol: {assignedRole}");
        }
        else
        {
            // Si aún no hay rol, usar Observador como último recurso
            assignedRole = "Observador";
            hasAssignedRole = true;
            ExitGames.Client.Photon.Hashtable playerProps = new ExitGames.Client.Photon.Hashtable() { { "Rol", assignedRole } };
            PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);
            Debug.LogWarning($"⚠️ No se pudo obtener el rol desde Laravel, usando 'Observador' por defecto. El rol se actualizará automáticamente cuando llegue la sesión activa.");
        }

        // Si no venimos con un role provisto por la sesión, intentar reclamar automáticamente
        if (!playerInstantiated && !roleProvidedBySession)
        {
            Debug.Log("[GestionRedJugador] No se proporcionó ROLE. Intentando reclamar un Player disponible automáticamente...");
            GameObject claimed = FindAndClaimAnyAvailablePlayer();
            if (claimed != null)
            {
                Debug.Log($"[GestionRedJugador] Player reclamado automáticamente: {claimed.name}");
                playerInstantiated = true;
                StartCoroutine(ConfigurePlayerAfterInstantiate(claimed, assignedRole));
            }
        }

        // Usar el Player existente en la escena para el rol asignado
        if (!playerInstantiated)
        {
            GameObject existingPlayer = FindExistingPlayerForRole(assignedRole);

            if (existingPlayer != null)
            {
                DebugLogger.LogPhase("GestionRedJugador", "Asignando Player existente", new { 
                    role = assignedRole,
                    playerName = existingPlayer.name,
                    roomName = PhotonNetwork.CurrentRoom?.Name
                });
                Debug.Log($"[GestionRedJugador] Usando Player existente: {existingPlayer.name} para rol {assignedRole}");

                // Transferir ownership al jugador local si es necesario
                var existingPhotonView = existingPlayer.GetComponent<PhotonView>();
                if (existingPhotonView != null && !existingPhotonView.IsMine)
                {
                    existingPhotonView.TransferOwnership(PhotonNetwork.LocalPlayer);
                    Debug.Log($"[GestionRedJugador] Ownership transferido para {existingPlayer.name}");
                }

                playerInstantiated = true; // Marcar como asignado ANTES de configurar
                StartCoroutine(ConfigurePlayerAfterInstantiate(existingPlayer, assignedRole));
            }
            else
            {
                Debug.LogError($"[GestionRedJugador] ❌ No se encontró Player existente para el rol '{assignedRole}'. No se instanciará uno nuevo.");
            }
        }
        else
        {
            Debug.LogWarning("[GestionRedJugador] ⚠️ Player ya fue asignado. Ignorando asignación duplicada.");
        }

        // Inicializar el sistema de audio
        InitializeAudioSystem();

        // Notificar al sistema de diálogos que un jugador se unió
        NotifyPlayerJoined(assignedRole);
        }
        finally
        {
            RecursionProtection.EndInitialization("GestionRedJugador.OnJoinedRoom");
        }
    }

    /// <summary>
    /// Configura el jugador después de que se instancia, esperando a que PhotonView se configure correctamente
    /// </summary>
    private IEnumerator ConfigurePlayerAfterInstantiate(GameObject playerPrefab, string role)
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
        
        if (playerController != null)
        {
            Debug.Log($"[GestionRedJugador] Configurando rol: {role}");
            playerController.SetRole(role);
        }
        else
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
    /// Busca un Player existente en la escena para el rol asignado (ej: Player_Juez).
    /// </summary>
    private GameObject FindExistingPlayerForRole(string role)
    {
        if (string.IsNullOrEmpty(role))
        {
            return null;
        }

        string expectedName = $"Player_{role}";
        GameObject byName = GameObject.Find(expectedName);
        if (byName != null)
        {
            return byName;
        }

        // Fallback: buscar por tag y comparación case-insensitive del rol
        GameObject[] players = GameObject.FindGameObjectsWithTag("Player");
        foreach (var player in players)
        {
            if (player == null) continue;
            string name = player.name;
            if (!name.StartsWith("Player_")) continue;
            string foundRole = name.Replace("Player_", "").Replace("(Clone)", "").Trim();
            if (string.Equals(foundRole, role, StringComparison.OrdinalIgnoreCase))
            {
                return player;
            }
        }

        return null;
    }

    /// <summary>
    /// Busca cualquier Player_* disponible (sin owner) y reclama su ownership para el jugador local.
    /// Devuelve el GameObject reclamado o null si no encontró ninguno.
    /// </summary>
    private GameObject FindAndClaimAnyAvailablePlayer()
    {
        // Buscar todas las PhotonViews en escena y filtrar por nombre Player_
        var allPVs = FindObjectsOfType<PhotonView>();
        foreach (var pv in allPVs)
        {
            if (pv == null) continue;
            var go = pv.gameObject;
            if (go == null) continue;
            if (!go.name.StartsWith("Player_")) continue;

            // Si ya es nuestro, devolverlo
            if (pv.IsMine)
            {
                assignedRole = go.name.Replace("Player_", "").Replace("(Clone)", "").Trim();
                hasAssignedRole = true;
                return go;
            }

            // Si no tiene owner (scene object) o su owner es 0, intentar transferir ownership
            if (pv.Owner == null || pv.Owner.ActorNumber == 0)
            {
                try
                {
                    pv.TransferOwnership(PhotonNetwork.LocalPlayer);
                    assignedRole = go.name.Replace("Player_", "").Replace("(Clone)", "").Trim();
                    hasAssignedRole = true;
                    Debug.Log($"[GestionRedJugador] Ownership transferido automáticamente para {go.name}");
                    return go;
                }
                catch (System.Exception e)
                {
                    Debug.LogWarning($"[GestionRedJugador] No se pudo transferir ownership para {go.name}: {e.Message}");
                }
            }
        }

        return null;
    }

    private Vector3 GetSpawnPositionForRole(string role)
    {
        // Posición de spawn por defecto (SpawnPoint_Default)
        Vector3 defaultSpawnPosition = new Vector3(0f, 4.40f, -15.27f);

        // Posiciones específicas para cada rol en la sala (coordenadas reales de Unity)
        switch (role.ToLower())
        {
            case "juez":
                return new Vector3(0.0240f, 4.9600f, -21.97f); // SpawnPoint_Juez
            case "fiscal":
                return new Vector3(2.544f, 4.05f, -15.77f); // SpawnPoint_Fiscal
            case "defensor":
                return new Vector3(-1.48f, 4.05f, -15.77f); // SpawnPoint_Defensor
            case "testigo":
                return new Vector3(3.561f, 4.05f, -15.77f); // SpawnPoint_Testigo
            case "acusado":
                return new Vector3(-2.393f, 4.0500f, -15.77f); // SpawnPoint_Acusado
            default:
                // Usar la posición de SpawnPoint_Default como fallback
                Debug.LogWarning($"No se encontró posición específica para el rol '{role}', usando posición por defecto: {defaultSpawnPosition}");
                return defaultSpawnPosition;
        }
    }

    private void InitializeAudioSystem()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        string roomId = PhotonNetwork.CurrentRoom.Name;
        int actorId = PhotonNetwork.LocalPlayer.ActorNumber;
        Application.ExternalCall("initVoiceCall", roomId, actorId);
#endif
    }

    private void NotifyPlayerJoined(string role)
    {
        // Notificar al sistema de diálogos que un jugador se unió
        var dialogueManager = FindObjectOfType<DialogueManager>();
        if (dialogueManager != null)
        {
            dialogueManager.OnPlayerJoined(role);
        }
    }

    // Obtener los roles usados en la sala
    string[] GetUsedRoles()
    {
        if (PhotonNetwork.CurrentRoom == null) return new string[0];

        object used;
        if (PhotonNetwork.CurrentRoom.CustomProperties.TryGetValue("UsedRoles", out used))
        {
            return (string[])used;
        }
        return new string[0];
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


