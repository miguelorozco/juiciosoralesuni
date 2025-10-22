using Photon.Pun;
using Photon.Realtime;
using UnityEngine;
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

        ConnectToPhoton();
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

        // Obtener el rol asignado desde la sesión activa
        GetAssignedRoleFromSession();
    }

    private void GetAssignedRoleFromSession()
    {
        if (gameInitializer != null && gameInitializer.currentSessionData != null)
        {
            // Usar el rol de la sesión activa
            assignedRole = gameInitializer.currentSessionData.role.nombre;
            hasAssignedRole = true;

            Debug.Log($"Rol asignado desde sesión: {assignedRole}");

            // Unirse directamente a la sala de la sesión
            JoinSessionRoom();
        }
        else
        {
            Debug.LogWarning("No hay sesión activa. Usando rol por defecto.");
            assignedRole = "Observador"; // Rol por defecto
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

        // Configurar el rol del jugador en Photon
        if (hasAssignedRole)
        {
            Hashtable playerProps = new Hashtable() { { "Rol", assignedRole } };
            PhotonNetwork.LocalPlayer.SetCustomProperties(playerProps);

            Debug.Log($"Jugador configurado con rol: {assignedRole}");
        }

        // Instanciar el jugador con el rol asignado
        Vector3 spawnPosition = GetSpawnPositionForRole(assignedRole);
        Quaternion spawnRotation = Quaternion.Euler(0, 180, 0);

        // Instanciar el prefab del jugador
        GameObject playerPrefab = PhotonNetwork.Instantiate("Player", spawnPosition, spawnRotation);

        // Configurar el jugador con su rol
        if (playerPrefab != null)
        {
            var playerController = playerPrefab.GetComponent<PlayerController>();
            if (playerController != null)
            {
                playerController.SetRole(assignedRole);
            }
        }

        // Inicializar el sistema de audio
        InitializeAudioSystem();

        // Notificar al sistema de diálogos que un jugador se unió
        NotifyPlayerJoined(assignedRole);
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


