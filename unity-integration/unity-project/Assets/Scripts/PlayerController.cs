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

    private Rigidbody rb;
    private bool isLocalPlayer = false;
    private GameObject currentAvatar;

    void Start()
    {
        // Configurar componentes
        rb = GetComponent<Rigidbody>();
        if (rb == null)
        {
            rb = gameObject.AddComponent<Rigidbody>();
        }

        rb.freezeRotation = true;
        rb.mass = 1f;
        rb.linearDamping = 5f;

        // Configurar como jugador local
        isLocalPlayer = photonView.IsMine;

        // Configurar cámara para jugador local
        if (isLocalPlayer)
        {
            SetupLocalPlayer();
        }
        else
        {
            SetupRemotePlayer();
        }

        // Crear avatar
        CreateAvatar();
    }

    void SetupLocalPlayer()
    {
        // Configurar cámara para seguir al jugador local
        Camera mainCamera = Camera.main;
        if (mainCamera != null)
        {
            var cameraFollow = mainCamera.GetComponent<CameraFollow>();
            if (cameraFollow == null)
            {
                cameraFollow = mainCamera.gameObject.AddComponent<CameraFollow>();
            }
            cameraFollow.target = transform;
        }

        // Habilitar input para jugador local
        var inputHandler = GetComponent<PlayerInputHandler>();
        if (inputHandler == null)
        {
            inputHandler = gameObject.AddComponent<PlayerInputHandler>();
        }
        inputHandler.playerController = this;
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
        }
    }

    public void Move(Vector3 direction)
    {
        if (!isLocalPlayer) return;

        // Mover el jugador
        Vector3 moveDirection = direction.normalized;
        rb.linearVelocity = new Vector3(moveDirection.x * moveSpeed, rb.linearVelocity.y, moveDirection.z * moveSpeed);

        // Rotar hacia la dirección de movimiento
        if (moveDirection != Vector3.zero)
        {
            Quaternion targetRotation = Quaternion.LookRotation(moveDirection);
            transform.rotation = Quaternion.Slerp(transform.rotation, targetRotation, rotationSpeed * Time.deltaTime);
        }
    }

    public bool IsLocalPlayer()
    {
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
