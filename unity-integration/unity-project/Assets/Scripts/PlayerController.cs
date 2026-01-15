using UnityEngine;
using Photon.Pun;
using TMPro;
using UnityEngine.UI;

public class PlayerController : MonoBehaviourPunCallbacks
{
    [Header("Configuración del Jugador")]
    public string playerRole = "";
    public Color roleColor = Color.white;

    [Header("Componentes UI")]
    public TextMeshProUGUI roleLabel;
    public Canvas roleCanvas;

    [Header("Configuración de Movimiento")]
    public float moveSpeed = 5f;
    public float rotationSpeed = 100f;

    [Header("Configuración de Avatar")]
    public GameObject avatarPrefab;
    public Transform avatarSpawnPoint;

    private bool isLocalPlayer = false;
    private GameObject currentAvatar;
    private bool isInitialized = false; // Flag para prevenir múltiples inicializaciones

    void Awake()
    {
        Debug.Log($"[PlayerController] ⚡ Awake() ejecutado en GameObject: {gameObject.name}");
        Debug.Log($"[PlayerController] PhotonView existe: {photonView != null}");
        if (photonView != null)
        {
            Debug.Log($"[PlayerController] PhotonView.IsMine en Awake: {photonView.IsMine}");
        }
    }

    void Start()
    {
        // Prevenir múltiples inicializaciones
        if (isInitialized)
        {
            Debug.LogWarning($"[PlayerController] ⚠️ Start() ya fue ejecutado. Ignorando llamada duplicada en {gameObject.name}");
            return;
        }

        Debug.Log($"[PlayerController] 🚀 Start() ejecutado en GameObject: {gameObject.name}");

        // Configurar como jugador local
        isLocalPlayer = photonView != null && photonView.IsMine;
        Debug.Log($"[PlayerController] photonView.IsMine: {isLocalPlayer} | GameObject: {gameObject.name}");

        // Marcar como inicializado ANTES de configurar para evitar recursión
        isInitialized = true;

        // Configurar cámara para jugador local
        if (isLocalPlayer)
        {
            Debug.Log($"[PlayerController] Configurando como jugador LOCAL");
            SetupLocalPlayer();
        }
        else
        {
            Debug.Log($"[PlayerController] Configurando como jugador REMOTO");
            SetupRemotePlayer();
        }

        // Crear avatar
        CreateAvatar();
    }

    void SetupLocalPlayer()
    {
        // Prevenir múltiples ejecuciones
        if (!isLocalPlayer)
        {
            Debug.LogWarning($"[PlayerController] ⚠️ SetupLocalPlayer() llamado pero no es jugador local");
            return;
        }

        // Deshabilitar todos los AudioListeners excepto el del jugador local
        // Usar coroutine para evitar ejecutarse en el mismo frame que Start()
        StartCoroutine(DisableAudioListenersCoroutine());

        // Configurar cámara para seguir al jugador local
        // Nota: El target se asignará después de crear el avatar en CreateAvatar()
        Camera mainCamera = Camera.main;
        if (mainCamera != null)
        {
            var cameraFollow = mainCamera.GetComponent<CameraFollow>();
            if (cameraFollow == null)
            {
                cameraFollow = mainCamera.gameObject.AddComponent<CameraFollow>();
            }
            // Usar el transform del PlayerController como fallback temporal
            // Se actualizará cuando se cree el avatar
            cameraFollow.target = transform;
        }

        // Habilitar input para jugador local
        var inputHandler = GetComponent<PlayerInputHandler>();
        if (inputHandler == null)
        {
            inputHandler = gameObject.AddComponent<PlayerInputHandler>();
            Debug.Log($"[PlayerController] PlayerInputHandler agregado como componente");
        }
        else
        {
            Debug.Log($"[PlayerController] PlayerInputHandler ya existe");
        }
        inputHandler.playerController = this;
        Debug.Log($"[PlayerController] playerController asignado al inputHandler: {inputHandler != null}");
    }

    /// <summary>
    /// Coroutine para deshabilitar AudioListeners después de un frame
    /// Evita problemas de recursión si se ejecuta en el mismo frame que Start()
    /// </summary>
    private System.Collections.IEnumerator DisableAudioListenersCoroutine()
    {
        // Esperar un frame para que todos los Start() se ejecuten
        yield return null;
        
        DisableAllAudioListenersExceptLocal();
    }

    /// <summary>
    /// Deshabilita todos los AudioListeners en la escena excepto los del jugador local.
    /// Esto asegura que solo haya un AudioListener activo.
    /// </summary>
    private void DisableAllAudioListenersExceptLocal()
    {
        // Buscar todos los AudioListeners en la escena
        AudioListener[] allListeners = FindObjectsOfType<AudioListener>();
        
        Debug.Log($"[PlayerController] Encontrados {allListeners.Length} AudioListener(s) en la escena");

        foreach (AudioListener listener in allListeners)
        {
            // Si el AudioListener pertenece a este jugador o a sus hijos (cámara del avatar), mantenerlo activo
            if (listener.transform.IsChildOf(transform) || listener.transform == transform)
            {
                listener.enabled = true;
                Debug.Log($"[PlayerController] AudioListener habilitado en: {listener.gameObject.name}");
            }
            else
            {
                // Deshabilitar todos los demás AudioListeners (cámara principal de la escena, etc.)
                listener.enabled = false;
                Debug.Log($"[PlayerController] AudioListener deshabilitado en: {listener.gameObject.name}");
            }
        }
    }

    void SetupRemotePlayer()
    {
        // Deshabilitar input para jugadores remotos
        var inputHandler = GetComponent<PlayerInputHandler>();
        if (inputHandler != null)
        {
            inputHandler.enabled = false;
        }
    }

    public void SetRole(string role)
    {
        playerRole = role;
        roleColor = GetRoleColor(role);

        // Actualizar UI del rol
        if (roleLabel != null)
        {
            roleLabel.text = role;
            roleLabel.color = roleColor;
        }

        // Notificar cambio de rol por RPC
        photonView.RPC("UpdateRole", RpcTarget.All, role);
    }

    [PunRPC]
    void UpdateRole(string role)
    {
        playerRole = role;
        roleColor = GetRoleColor(role);

        if (roleLabel != null)
        {
            roleLabel.text = role;
            roleLabel.color = roleColor;
        }
    }

    private Color GetRoleColor(string role)
    {
        switch (role.ToLower())
        {
            case "juez":
                return Color.red;
            case "fiscal":
                return Color.blue;
            case "defensor":
                return Color.green;
            case "testigo":
                return Color.yellow;
            case "acusado":
                return Color.magenta;
            default:
                return Color.white;
        }
    }

    private void CreateAvatar()
    {
        if (avatarPrefab != null)
        {
            Vector3 spawnPos = avatarSpawnPoint != null ? avatarSpawnPoint.position : transform.position;
            currentAvatar = Instantiate(avatarPrefab, spawnPos, Quaternion.identity);
            currentAvatar.transform.SetParent(transform);

            // Configurar el avatar con el rol
            var avatarController = currentAvatar.GetComponent<AvatarController>();
            if (avatarController != null)
            {
                avatarController.SetRole(playerRole, roleColor);
            }

            // Si es el jugador local, actualizar el target de la cámara al avatar
            if (isLocalPlayer)
            {
                UpdateCameraTarget();
            }
        }
    }

    /// <summary>
    /// Actualiza el target de la cámara para seguir el avatar.
    /// Busca un punto de referencia específico (Head, CameraTarget, PlayerCameraRoot) o usa el transform del avatar.
    /// </summary>
    private void UpdateCameraTarget()
    {
        if (currentAvatar == null) return;

        Camera mainCamera = Camera.main;
        if (mainCamera == null) return;

        var cameraFollow = mainCamera.GetComponent<CameraFollow>();
        if (cameraFollow == null) return;

        // Buscar un punto de referencia específico en el avatar
        Transform targetTransform = null;

        // 1. Buscar "PlayerCameraRoot" (usado en el prefab Player de Photon)
        Transform cameraRoot = currentAvatar.transform.Find("PlayerCameraRoot");
        if (cameraRoot != null)
        {
            targetTransform = cameraRoot;
            Debug.Log("CameraFollow: Usando PlayerCameraRoot como target");
        }
        // 2. Buscar "CameraTarget" (punto de referencia genérico)
        else
        {
            cameraRoot = currentAvatar.transform.Find("CameraTarget");
            if (cameraRoot != null)
            {
                targetTransform = cameraRoot;
                Debug.Log("CameraFollow: Usando CameraTarget como target");
            }
        }
        // 3. Buscar "Head" (cabeza del avatar)
        if (targetTransform == null)
        {
            Transform head = currentAvatar.transform.Find("Head");
            if (head != null)
            {
                targetTransform = head;
                Debug.Log("CameraFollow: Usando Head como target");
            }
        }
        // 4. Si no se encuentra ningún punto específico, usar el transform del avatar
        if (targetTransform == null)
        {
            targetTransform = currentAvatar.transform;
            Debug.Log("CameraFollow: Usando transform del avatar como target");
        }

        // Asignar el target a la cámara
        cameraFollow.SetTarget(targetTransform);
        Debug.Log($"CameraFollow: Target asignado a {targetTransform.name}");
    }


    public bool IsLocalPlayer()
    {
        // Verificar también photonView.IsMine por si acaso cambió
        bool isMine = photonView != null && photonView.IsMine;
        if (isLocalPlayer != isMine)
        {
            Debug.LogWarning($"[PlayerController] ⚠️ isLocalPlayer ({isLocalPlayer}) != photonView.IsMine ({isMine})");
            isLocalPlayer = isMine;
        }
        return isLocalPlayer;
    }

    public string GetRole()
    {
        return playerRole;
    }

    public Color GetRoleColor()
    {
        return roleColor;
    }

    void OnDestroy()
    {
        if (currentAvatar != null)
        {
            Destroy(currentAvatar);
        }
    }
}
