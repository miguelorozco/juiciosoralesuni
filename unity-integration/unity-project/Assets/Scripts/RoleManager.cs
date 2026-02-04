using Photon.Pun;
using Photon.Realtime;
using UnityEngine;
using UnityEngine.SceneManagement;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using ExitGames.Client.Photon;
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

        // DEBUG: OCULTAR TODOS LOS PLAYERS EXCEPTO Player_Juez
        Debug.Log("[RoleManager] 🔧 DEBUG: OCULTANDO todos los players excepto Player_Juez para simplificar debugging");

        // Mapear roles a GameObjects y DESACTIVAR todos excepto Player_Juez
        foreach (GameObject player in allPlayers)
        {
            string playerName = player.name;
            
            // Extraer el rol del nombre (ej: "Player_Juez" -> "Juez")
            if (playerName.StartsWith("Player_"))
            {
                string role = playerName.Replace("Player_", "");
                roleToPlayerMap[role] = player;
                
                // DEBUG: Desactivar TODOS los players excepto Player_Juez
                if (role != "Juez")
                {
                    Debug.Log($"[RoleManager] 🔧 DEBUG: DESACTIVANDO player: {playerName}");
                    player.SetActive(false); // Desactivar completamente el GameObject
                }
                else
                {
                    Debug.Log($"[RoleManager] ✅ DEBUG: MANTENIENDO ACTIVO player: {playerName}");
                    player.SetActive(true); // Asegurar que Player_Juez esté activo
                }
                
                // Desactivar TODAS las cámaras inicialmente (solo si el player está activo)
                if (player.activeSelf)
                {
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
        }

        // DEBUG: Deshabilitar asignación automática de roles - GestionRedJugador se encargará
        Debug.Log("[RoleManager] 🔧 DEBUG: Asignación automática deshabilitada - GestionRedJugador asignará el rol");
        
        /* COMENTADO PARA DEBUG
        // Verificar si estamos en la escena "main" y asignar rol automáticamente
        if (SceneManager.GetActiveScene().name == "main" && string.IsNullOrEmpty(myAssignedRole))
        {
            Debug.Log("[RoleManager] Escena 'main' detectada. Intentando asignar rol automáticamente...");
            
            // Esperar un poco para que Photon esté listo
            Invoke(nameof(AutoAssignAvailableRole), 1f);
        }
        else if (string.IsNullOrEmpty(myAssignedRole))
        {
            Debug.Log("[RoleManager] No hay rol asignado. Los roles disponibles son: " + string.Join(", ", availableRoles));
        }
        */
    }

    /// <summary>
    /// Obtiene los roles que están siendo usados en Photon (desde las propiedades de la sala)
    /// </summary>
    private string[] GetUsedRolesFromPhoton()
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
    /// Obtiene los roles disponibles verificando en Photon
    /// </summary>
    private List<string> GetAvailableRolesFromPhoton()
    {
        string[] usedRoles = GetUsedRolesFromPhoton();
        List<string> available = new List<string>(availableRoles);
        
        // Remover roles que ya están siendo usados
        foreach (string usedRole in usedRoles)
        {
            available.Remove(usedRole);
        }
        
        return available;
    }

    /// <summary>
    /// Asigna automáticamente un rol aleatorio disponible (verificando en Photon)
    /// </summary>
    private void AutoAssignAvailableRole()
    {
        if (!PhotonNetwork.IsConnected || PhotonNetwork.CurrentRoom == null)
        {
            Debug.LogWarning("[RoleManager] Photon no está conectado. No se puede asignar rol automáticamente.");
            return;
        }

        if (!string.IsNullOrEmpty(myAssignedRole))
        {
            Debug.Log($"[RoleManager] Ya hay un rol asignado: {myAssignedRole}");
            return;
        }

        // DEBUG: Hardcodear rol como "Juez"
        string roleToAssign = "Juez";
        Debug.Log($"[RoleManager] 🔧 DEBUG: Asignando rol hardcodeado como Juez (ignorando Photon)");

        AssignRole(roleToAssign);
        
        /* COMENTADO PARA DEBUG
        // Obtener roles disponibles verificando en Photon
        List<string> available = GetAvailableRolesFromPhoton();
        
        if (available.Count == 0)
        {
            Debug.LogWarning("[RoleManager] No hay roles disponibles. Todos están ocupados.");
            return;
        }

        // Asignar un rol aleatorio disponible
        string roleToAssign = available[Random.Range(0, available.Count)];
        
        Debug.Log($"[RoleManager] Asignando rol aleatorio disponible: '{roleToAssign}' de {available.Count} roles disponibles");

        AssignRole(roleToAssign);
        */
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

        // DEBUG: Ignorar verificación de Photon para permitir asignar Juez siempre
        Debug.Log($"[RoleManager] 🔧 DEBUG: Ignorando verificación de Photon para asignar rol '{roleName}'");
        
        /* COMENTADO PARA DEBUG
        // Verificar si el rol ya está siendo usado en Photon
        string[] usedRoles = GetUsedRolesFromPhoton();
        if (usedRoles.Contains(roleName))
        {
            Debug.LogWarning($"[RoleManager] El rol '{roleName}' ya está siendo usado por otro jugador. No se puede asignar.");
            return;
        }
        */

        GameObject targetPlayer = roleToPlayerMap[roleName];
        PhotonView pv = targetPlayer.GetComponent<PhotonView>();

        if (pv != null)
        {
            // Transferir ownership al jugador local
            if (!pv.IsMine)
            {
                pv.TransferOwnership(PhotonNetwork.LocalPlayer);
                Debug.Log($"[RoleManager] Ownership transferido al jugador local para {targetPlayer.name}");
            }

            myAssignedRole = roleName;
            
            // Esperar un frame para que Photon actualice IsMine antes de reconfigurar
            StartCoroutine(ReconfigurePlayerAfterOwnershipTransfer(targetPlayer, pv));
            
            // Activar la cámara de este player
            ActivateCameraForPlayer(targetPlayer);
            
            // Notificar a Photon que este rol está siendo usado (esto sincroniza con todos los jugadores)
            NotifyRoleAssignedToPhoton(roleName);
            
            Debug.Log($"[RoleManager] Rol '{roleName}' asignado al jugador local y notificado a Photon");
        }
        else
        {
            Debug.LogError($"[RoleManager] El GameObject '{targetPlayer.name}' no tiene PhotonView");
        }
    }

    /// <summary>
    /// Reconfigura el PlayerController después de transferir el ownership
    /// </summary>
    private IEnumerator ReconfigurePlayerAfterOwnershipTransfer(GameObject targetPlayer, PhotonView pv)
    {
        // Esperar varios frames para que Photon actualice IsMine y el avatar se cree
        yield return null;
        yield return null;
        yield return null;
        
        // Verificar que el ownership se transfirió correctamente
        if (pv.IsMine)
        {
            Debug.Log($"[RoleManager] Reconfigurando PlayerController para {targetPlayer.name}");
            
            // Obtener el PlayerController del targetPlayer y reconfigurarlo
            PlayerController playerController = targetPlayer.GetComponent<PlayerController>();
            if (playerController != null)
            {
                // Verificar que el avatar exista, si no, esperar un poco más
                int attempts = 0;
                while (attempts < 20) // Esperar hasta 20 frames (aproximadamente 0.3 segundos)
                {
                    GameObject currentAvatar = playerController.GetCurrentAvatar();
                    
                    if (currentAvatar != null)
                    {
                        Debug.Log($"[RoleManager] Avatar encontrado: {currentAvatar.name}");
                        break;
                    }
                    
                    attempts++;
                    yield return null;
                }
                
                // Usar el método público para reconfigurar
                playerController.ReconfigureAfterRoleAssignment();
                Debug.Log($"[RoleManager] PlayerController reconfigurado para {targetPlayer.name}");
            }
            else
            {
                Debug.LogWarning($"[RoleManager] No se encontró PlayerController en {targetPlayer.name}");
            }
        }
        else
        {
            Debug.LogError($"[RoleManager] El ownership no se transfirió correctamente para {targetPlayer.name}. IsMine: {pv.IsMine}");
        }
    }

    /// <summary>
    /// Notifica a Photon que un rol ha sido asignado (actualiza las propiedades de la sala)
    /// </summary>
    private void NotifyRoleAssignedToPhoton(string roleName)
    {
        if (PhotonNetwork.CurrentRoom == null)
        {
            Debug.LogWarning("[RoleManager] No hay sala de Photon activa. No se puede notificar asignación de rol.");
            return;
        }

        try
        {
            string[] usedRoles = GetUsedRolesFromPhoton();
            
            // Verificar que el rol no esté ya en la lista
            if (!usedRoles.Contains(roleName))
            {
                // Agregar el nuevo rol a la lista
                string[] newUsedRoles = new string[usedRoles.Length + 1];
                usedRoles.CopyTo(newUsedRoles, 0);
                newUsedRoles[newUsedRoles.Length - 1] = roleName;

                // Actualizar las propiedades de la sala
                ExitGames.Client.Photon.Hashtable roomProps = new ExitGames.Client.Photon.Hashtable { { "UsedRoles", newUsedRoles } };
                PhotonNetwork.CurrentRoom.SetCustomProperties(roomProps);
                
                Debug.Log($"[RoleManager] Rol '{roleName}' agregado a las propiedades de la sala de Photon");
            }
            else
            {
                Debug.LogWarning($"[RoleManager] El rol '{roleName}' ya estaba en las propiedades de la sala");
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"[RoleManager] Error notificando rol a Photon: {e.Message}");
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

    /// <summary>
    /// Callback cuando las propiedades de la sala cambian
    /// </summary>
    public override void OnRoomPropertiesUpdate(ExitGames.Client.Photon.Hashtable propertiesThatChanged)
    {
        if (propertiesThatChanged.ContainsKey("UsedRoles"))
        {
            string[] usedRoles = GetUsedRolesFromPhoton();
            Debug.Log($"[RoleManager] Roles usados actualizados en Photon: {string.Join(", ", usedRoles)}");
            
            // Actualizar la lista local de roles disponibles
            List<string> newAvailable = new List<string>(availableRoles);
            foreach (string usedRole in usedRoles)
            {
                newAvailable.Remove(usedRole);
            }
            
            // Si el rol del jugador local ya no está disponible localmente pero está en Photon, mantenerlo
            if (!string.IsNullOrEmpty(myAssignedRole) && usedRoles.Contains(myAssignedRole))
            {
                // El rol está correctamente asignado
            }
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
