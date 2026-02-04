using UnityEngine;
using StarterAssets;

/// <summary>
/// Script simple para crear un player básico desde cero con movimiento funcional
/// </summary>
public class SimplePlayerSetup : MonoBehaviour
{
    [Header("Configuración")]
    public float moveSpeed = 5f;
    public float jumpHeight = 1.2f;
    public float gravity = -15f;
    
    private GameObject playerCapsule;
    private CharacterController characterController;
    private StarterAssetsInputs inputs;
    private ThirdPersonController thirdPersonController;
    private Camera playerCamera;
    
    void Start()
    {
        Debug.Log("[SimplePlayerSetup] 🚀 Iniciando creación de player simple...");
        CreateSimplePlayer();
    }
    
    void CreateSimplePlayer()
    {
        // 1. Crear la cápsula
        Debug.Log("[SimplePlayerSetup] Paso 1: Creando cápsula...");
        playerCapsule = GameObject.CreatePrimitive(PrimitiveType.Capsule);
        playerCapsule.name = "SimplePlayer";
        playerCapsule.transform.position = new Vector3(0, 1, 0);
        
        // Buscar el GameObject "Players" y colocar la cápsula debajo
        GameObject playersParent = GameObject.Find("Players");
        if (playersParent != null)
        {
            playerCapsule.transform.SetParent(playersParent.transform);
            Debug.Log("[SimplePlayerSetup] ✅ Cápsula colocada debajo de 'Players'");
        }
        else
        {
            Debug.LogWarning("[SimplePlayerSetup] ⚠️ No se encontró GameObject 'Players', colocando en raíz");
        }
        
        // 2. Agregar CharacterController
        Debug.Log("[SimplePlayerSetup] Paso 2: Agregando CharacterController...");
        characterController = playerCapsule.AddComponent<CharacterController>();
        characterController.height = 2f;
        characterController.radius = 0.5f;
        characterController.center = new Vector3(0, 1, 0);
        characterController.enabled = true;
        Debug.Log("[SimplePlayerSetup] ✅ CharacterController agregado y habilitado");
        
        // 3. Agregar StarterAssetsInputs
        Debug.Log("[SimplePlayerSetup] Paso 3: Agregando StarterAssetsInputs...");
        inputs = playerCapsule.AddComponent<StarterAssetsInputs>();
        inputs.enabled = true;
        Debug.Log("[SimplePlayerSetup] ✅ StarterAssetsInputs agregado y habilitado");
        
        // 4. Agregar ThirdPersonController
        Debug.Log("[SimplePlayerSetup] Paso 4: Agregando ThirdPersonController...");
        thirdPersonController = playerCapsule.AddComponent<ThirdPersonController>();
        thirdPersonController.MoveSpeed = moveSpeed;
        thirdPersonController.SprintSpeed = moveSpeed * 2f;
        thirdPersonController.JumpHeight = jumpHeight;
        thirdPersonController.Gravity = gravity;
        thirdPersonController.enabled = true;
        
        // Crear el GameObject para CinemachineCameraTarget (requerido por ThirdPersonController)
        GameObject cameraTarget = new GameObject("CinemachineCameraTarget");
        cameraTarget.transform.SetParent(playerCapsule.transform);
        cameraTarget.transform.localPosition = new Vector3(0, 1.86f, 0);
        thirdPersonController.CinemachineCameraTarget = cameraTarget;
        
        Debug.Log("[SimplePlayerSetup] ✅ ThirdPersonController agregado y habilitado");
        
        // 5. Configurar cámara
        Debug.Log("[SimplePlayerSetup] Paso 5: Configurando cámara...");
        SetupCamera();
        
        // 6. Configurar input handler simple
        Debug.Log("[SimplePlayerSetup] Paso 6: Configurando input handler...");
        SetupInputHandler();
        
        Debug.Log("[SimplePlayerSetup] ✅✅✅ PLAYER SIMPLE CREADO EXITOSAMENTE ✅✅✅");
        Debug.Log($"[SimplePlayerSetup] Posición: {playerCapsule.transform.position}");
        Debug.Log($"[SimplePlayerSetup] CharacterController.enabled: {characterController.enabled}");
        Debug.Log($"[SimplePlayerSetup] StarterAssetsInputs.enabled: {inputs.enabled}");
        Debug.Log($"[SimplePlayerSetup] ThirdPersonController.enabled: {thirdPersonController.enabled}");
    }
    
    void SetupCamera()
    {
        // Buscar la cámara principal o crear una nueva
        Camera mainCam = Camera.main;
        if (mainCam == null)
        {
            Debug.LogWarning("[SimplePlayerSetup] ⚠️ No hay cámara principal, creando una nueva...");
            GameObject cameraObj = new GameObject("Main Camera");
            playerCamera = cameraObj.AddComponent<Camera>();
            cameraObj.tag = "MainCamera";
            cameraObj.AddComponent<AudioListener>();
        }
        else
        {
            playerCamera = mainCam;
            Debug.Log("[SimplePlayerSetup] ✅ Usando cámara principal existente");
        }
        
        // Posicionar la cámara detrás del player
        if (playerCamera != null && playerCapsule != null)
        {
            playerCamera.transform.position = playerCapsule.transform.position + new Vector3(0, 2, -5);
            playerCamera.transform.LookAt(playerCapsule.transform.position + Vector3.up * 1.5f);
            Debug.Log("[SimplePlayerSetup] ✅ Cámara posicionada");
        }
    }
    
    void SetupInputHandler()
    {
        // Agregar un script simple para manejar el input
        var inputHandler = playerCapsule.AddComponent<SimpleInputHandler>();
        inputHandler.playerCapsule = playerCapsule;
        inputHandler.starterAssetsInputs = inputs;
        Debug.Log("[SimplePlayerSetup] ✅ Input handler agregado");
    }
    
    void Update()
    {
        // Verificar que todo esté funcionando
        if (Time.frameCount % 120 == 0 && playerCapsule != null)
        {
            Debug.Log($"[SimplePlayerSetup] 📊 Estado del player:");
            Debug.Log($"[SimplePlayerSetup]   - Posición: {playerCapsule.transform.position}");
            Debug.Log($"[SimplePlayerSetup]   - CharacterController.enabled: {(characterController != null ? characterController.enabled.ToString() : "NULL")}");
            Debug.Log($"[SimplePlayerSetup]   - StarterAssetsInputs.enabled: {(inputs != null ? inputs.enabled.ToString() : "NULL")}");
            if (inputs != null)
            {
                Debug.Log($"[SimplePlayerSetup]   - Input.move: {inputs.move}");
            }
            Debug.Log($"[SimplePlayerSetup]   - ThirdPersonController.enabled: {(thirdPersonController != null ? thirdPersonController.enabled.ToString() : "NULL")}");
        }
    }
}
