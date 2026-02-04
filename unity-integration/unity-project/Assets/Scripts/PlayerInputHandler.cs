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
    private int frameCount = 0;

    void Awake()
    {
        Debug.Log($"[PlayerInputHandler] ⚡ Awake() ejecutado en GameObject: {gameObject.name}");
    }

    void Start()
    {
        // Log de inicialización
        Debug.Log($"[PlayerInputHandler] 🚀 Start() ejecutado en GameObject: {gameObject.name}");
        
        // Buscar StarterAssetsInputs en el avatar del PlayerController
        if (playerController != null)
        {
            GameObject avatar = playerController.GetCurrentAvatar();
            if (avatar != null)
            {
                // Buscar ThirdPersonController primero
                var thirdPersonController = avatar.GetComponentInChildren<StarterAssets.ThirdPersonController>(true);
                if (thirdPersonController != null)
                {
                    // StarterAssetsInputs debe estar en el mismo GameObject que ThirdPersonController
                    starterAssetsInputs = thirdPersonController.GetComponent<StarterAssetsInputs>();
                    if (starterAssetsInputs == null)
                    {
                        // Si no está en el mismo GameObject, buscar en los hijos
                        starterAssetsInputs = thirdPersonController.GetComponentInChildren<StarterAssetsInputs>(true);
                    }
                    
                    if (starterAssetsInputs != null)
                    {
                        Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en Start() en GameObject: {starterAssetsInputs.gameObject.name} (mismo que ThirdPersonController: {thirdPersonController.gameObject.name})");
                    }
                    else
                    {
                        Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en ThirdPersonController GameObject: {thirdPersonController.gameObject.name}");
                        // Fallback: buscar en todo el avatar
                        starterAssetsInputs = avatar.GetComponentInChildren<StarterAssetsInputs>(true);
                        if (starterAssetsInputs != null)
                        {
                            Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs encontrado en avatar (fallback), pero puede no ser el correcto: {avatar.name}");
                        }
                    }
                }
                else
                {
                    // Fallback: buscar en todo el avatar
                    starterAssetsInputs = avatar.GetComponentInChildren<StarterAssetsInputs>(true);
                    if (starterAssetsInputs != null)
                    {
                        Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en avatar (fallback): {avatar.name}");
                    }
                    else
                    {
                        Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en avatar: {avatar.name}");
                    }
                }
            }
            else
            {
                Debug.LogWarning($"[PlayerInputHandler] ⚠️ Avatar es NULL, buscando StarterAssetsInputs en GameObject principal...");
                // Fallback: buscar en el GameObject principal
                starterAssetsInputs = GetComponent<StarterAssetsInputs>();
                if (starterAssetsInputs == null)
                {
                    Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en {gameObject.name}");
                }
                else
                {
                    Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en GameObject principal");
                }
            }
            
            Debug.Log($"[PlayerInputHandler] playerController asignado: {playerController.gameObject.name}");
            Debug.Log($"[PlayerInputHandler] IsLocalPlayer: {playerController.IsLocalPlayer()}");
        }
        else
        {
            Debug.LogWarning($"[PlayerInputHandler] ⚠️ playerController es NULL en Start()");
            // Fallback: buscar en el GameObject principal
            starterAssetsInputs = GetComponent<StarterAssetsInputs>();
            if (starterAssetsInputs == null)
            {
                Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en {gameObject.name}");
            }
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
                Debug.Log($"[PlayerInputHandler] StarterAssetsInputs: {(starterAssetsInputs != null ? starterAssetsInputs.gameObject.name : "NULL")}");
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

        // Si StarterAssetsInputs es null, intentar encontrarlo de nuevo (por si el avatar se creó después)
        if (starterAssetsInputs == null)
        {
            GameObject avatar = playerController.GetCurrentAvatar();
            if (avatar != null)
            {
                // Buscar ThirdPersonController primero
                var thirdPersonController = avatar.GetComponentInChildren<StarterAssets.ThirdPersonController>(true);
                if (thirdPersonController != null)
                {
                    // StarterAssetsInputs debe estar en el mismo GameObject que ThirdPersonController
                    starterAssetsInputs = thirdPersonController.GetComponent<StarterAssetsInputs>();
                    if (starterAssetsInputs == null)
                    {
                        // Si no está en el mismo GameObject, buscar en los hijos
                        starterAssetsInputs = thirdPersonController.GetComponentInChildren<StarterAssetsInputs>(true);
                    }
                    
                    if (starterAssetsInputs != null)
                    {
                        Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en Update() en GameObject: {starterAssetsInputs.gameObject.name} (mismo que ThirdPersonController: {thirdPersonController.gameObject.name})");
                        Debug.Log($"[PlayerInputHandler] 🔍 VERIFICACIÓN: Avatar pertenece a PlayerController: {avatar.transform.parent?.name ?? "NO PARENT"} | PlayerController esperado: {playerController.gameObject.name}");
                    }
                    else
                    {
                        Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en ThirdPersonController GameObject: {thirdPersonController.gameObject.name}");
                    }
                }
                else
                {
                    // Fallback: buscar en todo el avatar
                    starterAssetsInputs = avatar.GetComponentInChildren<StarterAssetsInputs>(true);
                    if (starterAssetsInputs != null)
                    {
                        Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en Update() en avatar (fallback): {avatar.name}");
                        Debug.Log($"[PlayerInputHandler] 🔍 VERIFICACIÓN: Avatar pertenece a PlayerController: {avatar.transform.parent?.name ?? "NO PARENT"} | PlayerController esperado: {playerController.gameObject.name}");
                    }
                }
            }
            else
            {
                // DEBUG: Log detallado cuando el avatar es null
                if (frameCount % 120 == 0) // Cada 2 segundos aproximadamente
                {
                    Debug.LogWarning($"[PlayerInputHandler] ⚠️ Avatar es NULL para PlayerController: {playerController.gameObject.name}");
                    Debug.LogWarning($"[PlayerInputHandler] 🔍 Buscando todos los avatares en la escena...");
                    
                    // Buscar todos los ThirdPersonController en la escena
                    var allControllers = FindObjectsOfType<StarterAssets.ThirdPersonController>();
                    Debug.LogWarning($"[PlayerInputHandler] 📊 ThirdPersonController encontrados: {allControllers.Length}");
                    foreach (var controller in allControllers)
                    {
                        Debug.LogWarning($"[PlayerInputHandler]   - {controller.gameObject.name} (enabled: {controller.enabled}, position: {controller.transform.position})");
                        var parentPlayer = controller.GetComponentInParent<PlayerController>();
                        if (parentPlayer != null)
                        {
                            Debug.LogWarning($"[PlayerInputHandler]     Parent PlayerController: {parentPlayer.gameObject.name} (IsLocalPlayer: {parentPlayer.IsLocalPlayer()})");
                        }
                    }
                }
            }
        }
        
        // CRÍTICO: Verificar que el StarterAssetsInputs encontrado pertenezca al avatar del jugador local
        if (starterAssetsInputs != null && playerController != null)
        {
            GameObject avatar = playerController.GetCurrentAvatar();
            if (avatar != null)
            {
                // Verificar que el StarterAssetsInputs esté dentro del avatar del jugador local
                if (!starterAssetsInputs.transform.IsChildOf(avatar.transform) && starterAssetsInputs.transform != avatar.transform)
                {
                    Debug.LogError($"[PlayerInputHandler] ❌ CRÍTICO: StarterAssetsInputs encontrado NO pertenece al avatar del jugador local!");
                    Debug.LogError($"[PlayerInputHandler]   - StarterAssetsInputs GameObject: {starterAssetsInputs.gameObject.name}");
                    Debug.LogError($"[PlayerInputHandler]   - Avatar esperado: {avatar.name}");
                    Debug.LogError($"[PlayerInputHandler]   - PlayerController: {playerController.gameObject.name}");
                    
                    // Buscar el StarterAssetsInputs correcto en el avatar
                    var correctInputs = avatar.GetComponentInChildren<StarterAssetsInputs>(true);
                    if (correctInputs != null)
                    {
                        Debug.Log($"[PlayerInputHandler] ✅ Encontrado StarterAssetsInputs correcto: {correctInputs.gameObject.name}");
                        starterAssetsInputs = correctInputs;
                    }
                    else
                    {
                        Debug.LogError($"[PlayerInputHandler] ❌ NO se encontró StarterAssetsInputs en el avatar correcto!");
                        starterAssetsInputs = null;
                    }
                }
            }
        }

        // Resetear el warning flag si todo está bien
        hasLoggedWarning = false;
        frameCount++;
        HandleInput();
    }

    void HandleInput()
    {
        inputDirection = Vector3.zero;
        bool anyKeyPressed = false;

        // Movimiento hacia adelante/atrás (relativo al avatar)
        if (Input.GetKey(forwardKey))
        {
            inputDirection += Vector3.forward;
            anyKeyPressed = true;
        }
        if (Input.GetKey(backwardKey))
        {
            inputDirection += Vector3.back;
            anyKeyPressed = true;
        }

        // Movimiento hacia izquierda/derecha (relativo al avatar)
        if (Input.GetKey(leftKey))
        {
            inputDirection += Vector3.left;
            anyKeyPressed = true;
        }
        if (Input.GetKey(rightKey))
        {
            inputDirection += Vector3.right;
            anyKeyPressed = true;
        }

        // Log del input capturado (solo cuando hay input para evitar spam)
        if (anyKeyPressed)
        {
            Debug.Log($"[PlayerInputHandler] 📥 INPUT CAPTURADO: {inputDirection} (W:{Input.GetKey(forwardKey)}, S:{Input.GetKey(backwardKey)}, A:{Input.GetKey(leftKey)}, D:{Input.GetKey(rightKey)}) | StarterAssetsInputs: {(starterAssetsInputs != null ? starterAssetsInputs.gameObject.name : "NULL")} | enabled: {(starterAssetsInputs != null ? starterAssetsInputs.enabled.ToString() : "N/A")}");
        }

        // Actualizar StarterAssetsInputs con el input
        if (starterAssetsInputs != null)
        {
            // Convertir inputDirection (Vector3) a Vector2 para StarterAssetsInputs
            Vector2 moveInput = new Vector2(inputDirection.x, inputDirection.z);
            Vector2 previousMove = starterAssetsInputs.move;
            starterAssetsInputs.move = moveInput;
            
            // Log cuando el input cambia o cuando hay input activo
            if (moveInput.magnitude > 0.01f)
            {
                if (moveInput != previousMove)
                {
                    Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs.move ACTUALIZADO: {moveInput} (anterior: {previousMove}) | GameObject: {starterAssetsInputs.gameObject.name} | enabled: {starterAssetsInputs.enabled} | Transform: {starterAssetsInputs.transform.position}");
                }
                else if (frameCount % 30 == 0) // Log cada 30 frames si el input se mantiene
                {
                    Debug.Log($"[PlayerInputHandler] 📊 StarterAssetsInputs.move ACTIVO: {moveInput} | GameObject: {starterAssetsInputs.gameObject.name} | enabled: {starterAssetsInputs.enabled} | Transform: {starterAssetsInputs.transform.position}");
                }
            }
        }
        else
        {
            // Log de error más frecuente para debugging
            if (frameCount % 60 == 0) // Log cada 60 frames para no saturar
            {
                Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs es NULL, no se puede actualizar el input. Intentando buscar en avatar...");
                
                // Intentar encontrar StarterAssetsInputs de nuevo
                if (playerController != null)
                {
                    GameObject avatar = playerController.GetCurrentAvatar();
                    if (avatar != null)
                    {
                        starterAssetsInputs = avatar.GetComponentInChildren<StarterAssetsInputs>(true);
                        if (starterAssetsInputs != null)
                        {
                            Debug.Log($"[PlayerInputHandler] ✅ StarterAssetsInputs encontrado en HandleInput() en avatar: {avatar.name}");
                        }
                        else
                        {
                            Debug.LogWarning($"[PlayerInputHandler] ⚠️ StarterAssetsInputs NO encontrado en avatar: {avatar.name}. Buscando en hijos...");
                            // Buscar todos los StarterAssetsInputs en la escena para debugging
                            var allInputs = FindObjectsOfType<StarterAssetsInputs>();
                            Debug.LogWarning($"[PlayerInputHandler] 📊 StarterAssetsInputs encontrados en escena: {allInputs.Length}");
                            foreach (var input in allInputs)
                            {
                                Debug.LogWarning($"[PlayerInputHandler]   - {input.gameObject.name} (enabled: {input.enabled}, move: {input.move})");
                            }
                        }
                    }
                    else
                    {
                        Debug.LogWarning($"[PlayerInputHandler] ⚠️ Avatar es NULL en HandleInput()");
                    }
                }
            }
        }
    }
}
