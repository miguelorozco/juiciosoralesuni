using UnityEngine;
using StarterAssets;

public class PlayerInputHandler : MonoBehaviour
{
    [Header("Referencias")]
    public PlayerController playerController;

    [Header("Configuración de Input")]
    public KeyCode forwardKey = KeyCode.W;
    public KeyCode backwardKey = KeyCode.S;
    public KeyCode leftKey = KeyCode.A;
    public KeyCode rightKey = KeyCode.D;

    private StarterAssetsInputs starterAssetsInputs;
    private Vector3 inputDirection;
    private bool hasLoggedInitialization = false;
    private bool hasLoggedWarning = false;

    void Awake()
    {
        Debug.Log($"[PlayerInputHandler] ⚡ Awake() ejecutado en GameObject: {gameObject.name}");
    }

    void Start()
    {
        // Log de inicialización
        Debug.Log($"[PlayerInputHandler] 🚀 Start() ejecutado en GameObject: {gameObject.name}");
        
        // Obtener StarterAssetsInputs
        starterAssetsInputs = GetComponent<StarterAssetsInputs>();
        if (starterAssetsInputs == null)
        {
            Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en {gameObject.name}");
        }
        else
        {
            Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado");
        }
        
        if (playerController == null)
        {
            Debug.LogWarning($"[PlayerInputHandler] ⚠️ playerController es NULL en Start()");
        }
        else
        {
            Debug.Log($"[PlayerInputHandler] playerController asignado: {playerController.gameObject.name}");
            Debug.Log($"[PlayerInputHandler] IsLocalPlayer: {playerController.IsLocalPlayer()}");
        }
    }

    void Update()
    {
        // Log de inicialización una sola vez
        if (!hasLoggedInitialization)
        {
            hasLoggedInitialization = true;
            Debug.Log($"[PlayerInputHandler] Update() ejecutándose. playerController: {(playerController != null ? playerController.gameObject.name : "NULL")}");
            if (playerController != null)
            {
                Debug.Log($"[PlayerInputHandler] IsLocalPlayer(): {playerController.IsLocalPlayer()}");
            }
        }

        if (playerController == null)
        {
            // Solo loguear una vez para evitar spam
            if (!hasLoggedWarning)
            {
                hasLoggedWarning = true;
                Debug.LogWarning($"[PlayerInputHandler] ⚠️ playerController es NULL en Update()");
            }
            return;
        }

        if (!playerController.IsLocalPlayer())
        {
            // Solo loguear una vez para evitar spam
            if (!hasLoggedWarning)
            {
                hasLoggedWarning = true;
                Debug.Log($"[PlayerInputHandler] No es jugador local, ignorando input");
            }
            return;
        }

        // Resetear el warning flag si todo está bien
        hasLoggedWarning = false;
        HandleInput();
    }

    void HandleInput()
    {
        inputDirection = Vector3.zero;

        // Movimiento hacia adelante/atrás (relativo al avatar)
        if (Input.GetKey(forwardKey))
        {
            inputDirection += Vector3.forward;
        }
        if (Input.GetKey(backwardKey))
        {
            inputDirection += Vector3.back;
        }

        // Movimiento hacia izquierda/derecha (relativo al avatar)
        if (Input.GetKey(leftKey))
        {
            inputDirection += Vector3.left;
        }
        if (Input.GetKey(rightKey))
        {
            inputDirection += Vector3.right;
        }

        // Actualizar StarterAssetsInputs con el input
        if (starterAssetsInputs != null)
        {
            // Convertir inputDirection (Vector3) a Vector2 para StarterAssetsInputs
            Vector2 moveInput = new Vector2(inputDirection.x, inputDirection.z);
            starterAssetsInputs.move = moveInput;
        }
        else
        {
            Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs es NULL, no se puede actualizar el input");
        }
    }
}
