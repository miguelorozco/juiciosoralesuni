using Photon.Pun;
using UnityEngine;
using System.Collections.Generic;
using System.Linq;
using JuiciosSimulator.API;

/// <summary>
/// Gestiona la asignación de roles y la activación de cámaras.
/// Solo debe haber UNA cámara activa a la vez (la del jugador local).
/// </summary>
public class RoleManager : MonoBehaviourPunCallbacks
{
    public static RoleManager Instance { get; private set; }

    [Header("Roles Disponibles")]
    public List<string> availableRoles = new List<string>
    {
        "Juez", "Fiscal", "Defensa", "Testigo1", "Testigo2",
        "Policia1", "Policia2", "Psicologo", "Acusado", "Secretario",
        "Abogado1", "Abogado2", "Perito1", "Perito2", "Victima",
        "Acusador", "Periodista", "Publico1", "Publico2", "Observador"
    };

    private Dictionary<string, GameObject> roleToPlayerMap = new Dictionary<string, GameObject>();
    private string myAssignedRole = null;

    void Awake()
    {
        if (Instance != null && Instance != this)
        {
            Destroy(gameObject);
            return;
        }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    void Start()
    {
        // Esperar un frame para que todos los players se instancien
        Invoke(nameof(InitializeRoleSystem), 0.5f);
    }

    private void InitializeRoleSystem()
    {
        // Encontrar todos los players en la escena
        GameObject[] allPlayers = GameObject.FindGameObjectsWithTag("Player");
        
        Debug.Log($"[RoleManager] Encontrados {allPlayers.Length} players en la escena");

        // Mapear roles a GameObjects
        foreach (GameObject player in allPlayers)
        {
            string playerName = player.name;
            
            // Extraer el rol del nombre (ej: "Player_Juez" -> "Juez")
            if (playerName.StartsWith("Player_"))
            {
                string role = playerName.Replace("Player_", "");
                roleToPlayerMap[role] = player;
                
                // Desactivar TODAS las cámaras inicialmente
                Camera cam = player.GetComponentInChildren<Camera>();
                if (cam != null)
                {
                    cam.enabled = false;
                }
                
                AudioListener listener = player.GetComponentInChildren<AudioListener>();
                if (listener != null)
                {
                    listener.enabled = false;
                }
            }
        }

        // Si el jugador no tiene rol asignado, mostrar opciones
        if (string.IsNullOrEmpty(myAssignedRole))
        {
            Debug.Log("[RoleManager] No hay rol asignado. Los roles disponibles son: " + string.Join(", ", availableRoles));
        }
    }

    /// <summary>
    /// Asigna un rol al jugador local y activa su cámara.
    /// </summary>
    public void AssignRole(string roleName)
    {
        if (!availableRoles.Contains(roleName))
        {
            Debug.LogError($"[RoleManager] Rol '{roleName}' no existe en la lista de roles disponibles");
            return;
        }

        if (!roleToPlayerMap.ContainsKey(roleName))
        {
            Debug.LogError($"[RoleManager] No se encontró GameObject para el rol '{roleName}'");
            return;
        }

        GameObject targetPlayer = roleToPlayerMap[roleName];
        PhotonView pv = targetPlayer.GetComponent<PhotonView>();

        if (pv != null)
        {
            // Transferir ownership al jugador local
            if (!pv.IsMine)
            {
                pv.TransferOwnership(PhotonNetwork.LocalPlayer);
            }

            myAssignedRole = roleName;
            
            // Activar la cámara de este player
            ActivateCameraForPlayer(targetPlayer);
            
            // Sincronizar con otros jugadores
            photonView.RPC("RPC_NotifyRoleAssigned", RpcTarget.AllBuffered, roleName, PhotonNetwork.LocalPlayer.ActorNumber);
            
            Debug.Log($"[RoleManager] Rol '{roleName}' asignado al jugador local");
        }
        else
        {
            Debug.LogError($"[RoleManager] El GameObject '{targetPlayer.name}' no tiene PhotonView");
        }
    }

    /// <summary>
    /// Activa la cámara solo para el player especificado.
    /// </summary>
    private void ActivateCameraForPlayer(GameObject player)
    {
        // Primero, desactivar TODAS las cámaras
        DeactivateAllCameras();

        // Activar solo la cámara del player especificado
        Camera cam = player.GetComponentInChildren<Camera>();
        if (cam != null)
        {
            cam.enabled = true;
            cam.depth = 0;
            Debug.Log($"[RoleManager] Cámara ACTIVADA para: {player.name}");
        }

        AudioListener listener = player.GetComponentInChildren<AudioListener>();
        if (listener != null)
        {
            listener.enabled = true;
        }

        // Desactivar Main Camera si existe
        Camera mainCam = Camera.main;
        if (mainCam != null && mainCam != cam)
        {
            mainCam.enabled = false;
            AudioListener mainListener = mainCam.GetComponent<AudioListener>();
            if (mainListener != null) mainListener.enabled = false;
        }
    }

    /// <summary>
    /// Desactiva todas las cámaras en la escena.
    /// </summary>
    private void DeactivateAllCameras()
    {
        Camera[] allCameras = FindObjectsOfType<Camera>();
        foreach (Camera cam in allCameras)
        {
            cam.enabled = false;
        }

        AudioListener[] allListeners = FindObjectsOfType<AudioListener>();
        foreach (AudioListener listener in allListeners)
        {
            listener.enabled = false;
        }

        Debug.Log($"[RoleManager] Desactivadas {allCameras.Length} cámaras");
    }

    [PunRPC]
    private void RPC_NotifyRoleAssigned(string roleName, int actorNumber)
    {
        Debug.Log($"[RoleManager] Rol '{roleName}' asignado al jugador {actorNumber}");
        
        // Remover el rol de la lista de disponibles
        if (availableRoles.Contains(roleName))
        {
            availableRoles.Remove(roleName);
        }
    }

    /// <summary>
    /// Obtiene una lista de roles disponibles (no asignados).
    /// </summary>
    public List<string> GetAvailableRoles()
    {
        return new List<string>(availableRoles);
    }

    /// <summary>
    /// Verifica si un rol está disponible.
    /// </summary>
    public bool IsRoleAvailable(string roleName)
    {
        return availableRoles.Contains(roleName);
    }

    public override void OnPlayerLeftRoom(Photon.Realtime.Player otherPlayer)
    {
        // Cuando un jugador se desconecta, liberar su rol
        Debug.Log($"[RoleManager] Jugador {otherPlayer.NickName} ha salido de la sala");

        // Buscar el rol asignado a ese jugador
        string disconnectedRole = null;
        foreach (var kvp in roleToPlayerMap)
        {
            PhotonView pv = kvp.Value.GetComponent<PhotonView>();
            if (pv != null && pv.Owner != null && pv.Owner.ActorNumber == otherPlayer.ActorNumber)
            {
                disconnectedRole = kvp.Key;
                break;
            }
        }

        if (!string.IsNullOrEmpty(disconnectedRole))
        {
            // Liberar el rol
            if (!availableRoles.Contains(disconnectedRole))
            {
                availableRoles.Add(disconnectedRole);
                Debug.Log($"[RoleManager] Rol '{disconnectedRole}' liberado por desconexión de jugador {otherPlayer.NickName}");
            }
            // Opcional: notificar a LaravelAPI
            if (LaravelAPI.Instance != null)
            {
                LaravelAPI.Instance.ReleaseRoleToBackend(disconnectedRole, otherPlayer.ActorNumber);
            }
        }
    }
}
