using Photon.Pun;
using Photon.Realtime;
using UnityEngine;
using System.Collections;
using ExitGames.Client.Photon;
using JuiciosSimulator.API;
using JuiciosSimulator;
using JuiciosSimulator.Dialogue;

public class GestionRedJugador : MonoBehaviourPunCallbacks
{
    [Header("Referencias del Sistema")]
    public LaravelAPI laravelAPI;
    public GameInitializer gameInitializer;

    [Header("Configuración de Sala")]
    public string sessionRoomName = "SalaPrincipal"; // Nombre fijo para la sesión OXXO

    private bool hasAssignedRole = false;
    private string assignedRole = "";

    void Start()
    {
        // Obtener referencias si no están asignadas
        if (laravelAPI == null)
            laravelAPI = FindObjectOfType<LaravelAPI>();
        if (gameInitializer == null)
            gameInitializer = FindObjectOfType<GameInitializer>();

        // Suscribirse a eventos de LaravelAPI para obtener el rol cuando esté disponible
        LaravelAPI.OnActiveSessionReceived += OnActiveSessionReceived;
        LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;

        ConnectToPhoton();
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

        Debug.Log($"Sesión activa recibida en GestionRedJugador: {sessionData.session.nombre ?? "Sin nombre"}");
        
        // Actualizar el rol asignado
        if (sessionData.role != null)
        {
            assignedRole = sessionData.role.nombre ?? "Observador";
            hasAssignedRole = true;
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
        }
    }

    void ConnectToPhoton()
    {
        if (!PhotonNetwork.IsConnected)
        {
            Debug.Log("Conectando a Photon...");
            PhotonNetwork.ConnectUsingSettings();
        }
    }

    public override void OnConnectedToMaster()
    {
        Debug.Log("Conectado al Master Server. Entrando al lobby...");
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
        Debug.Log($"Uniéndose a la sala de sesión: {sessionRoomName}");

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
        string roomName = "Sala_" + Random.Range(1000, 9999);
        PhotonNetwork.CreateRoom(roomName, roomOptions, TypedLobby.Default);

    }

    public override void OnJoinedRoom()
    {
        Debug.Log("Unido a una sala. Configurando jugador con rol asignado...");

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

        // Instanciar el jugador con el rol asignado
        Vector3 spawnPosition = GetSpawnPositionForRole(assignedRole);
        Quaternion spawnRotation = Quaternion.Euler(0, 180, 0);

        // Instanciar el prefab del jugador
        Debug.Log($"[GestionRedJugador] Instanciando prefab 'Player' en posición: {spawnPosition}");
        GameObject playerPrefab = PhotonNetwork.Instantiate("Player", spawnPosition, spawnRotation);

        // IMPORTANTE: Esperar un frame para que PhotonView se configure correctamente
        // PhotonNetwork.Instantiate puede no tener IsMine configurado inmediatamente
        if (playerPrefab != null)
        {
            StartCoroutine(ConfigurePlayerAfterInstantiate(playerPrefab, assignedRole));
        }
        else
        {
            Debug.LogError($"[GestionRedJugador] ❌ No se pudo instanciar el prefab 'Player'!");
        }

        // Inicializar el sistema de audio
        InitializeAudioSystem();

        // Notificar al sistema de diálogos que un jugador se unió
        NotifyPlayerJoined(assignedRole);
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
        
        Debug.Log($"[GestionRedJugador] PlayerController encontrado: {playerController != null}");
        Debug.Log($"[GestionRedJugador] PlayerInputHandler encontrado: {playerInputHandler != null}");
        Debug.Log($"[GestionRedJugador] PhotonView encontrado: {photonView != null}");
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


