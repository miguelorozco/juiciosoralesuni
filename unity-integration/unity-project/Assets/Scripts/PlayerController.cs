using UnityEngine;
using Photon.Pun;
using TMPro;
using UnityEngine.UI;
using System.Collections.Generic;

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
    [SerializeField] private GameObject currentAvatar; // Serializado para poder acceder desde RoleManager
    private bool isInitialized = false; // Flag para prevenir múltiples inicializaciones
    
    public GameObject GetCurrentAvatar() { return currentAvatar; }

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
        
        // DEBUG: Agregar script de diagnóstico para el jugador local
        if (isLocalPlayer)
        {
            var diagnostic = GetComponent<PlayerMovementDiagnostic>();
            if (diagnostic == null)
            {
                diagnostic = gameObject.AddComponent<PlayerMovementDiagnostic>();
                Debug.Log($"[PlayerController] 🔧 DEBUG: Script de diagnóstico agregado");
            }
        }

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
        
        // Si es el jugador local, habilitar componentes de movimiento después de crear el avatar
        if (isLocalPlayer)
        {
            StartCoroutine(EnableMovementComponentsDelayed());
        }
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
        // El color debe ser asignado desde la API de Laravel
        roleColor = RoleColorManager.GetColorForRole(role);

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
        roleColor = RoleColorManager.GetColorForRole(role);

        if (roleLabel != null)
        {
            roleLabel.text = role;
            roleLabel.color = roleColor;
        }
    }

    private Color GetRoleColor(string role)
    {
        // El color ahora se obtiene desde RoleColorManager
        return RoleColorManager.GetColorForRole(role);
    }

    private void CreateAvatar()
    {
        Debug.Log($"[PlayerController] 🔍 CreateAvatar() llamado en GameObject: {gameObject.name}");
        Debug.Log($"[PlayerController]   - avatarPrefab: {(avatarPrefab != null ? avatarPrefab.name : "NULL")}");
        Debug.Log($"[PlayerController]   - avatarSpawnPoint: {(avatarSpawnPoint != null ? avatarSpawnPoint.name : "NULL")}");
        Debug.Log($"[PlayerController]   - isLocalPlayer: {isLocalPlayer}");
        Debug.Log($"[PlayerController]   - playerRole: {playerRole}");
        
        if (avatarPrefab == null)
        {
            // Si no hay avatarPrefab, verificar si este GameObject ya tiene componentes de movimiento
            // (como SimplePlayer que ya tiene ThirdPersonController directamente)
            var existingThirdPersonController = GetComponent<StarterAssets.ThirdPersonController>();
            var existingCharacterController = GetComponent<CharacterController>();
            var existingStarterInputs = GetComponent<StarterAssets.StarterAssetsInputs>();
            
            if (existingThirdPersonController != null || existingCharacterController != null || existingStarterInputs != null)
            {
                Debug.Log($"[PlayerController] ✅ Este GameObject ({gameObject.name}) ya tiene componentes de movimiento. Usándolo directamente como avatar.");
                currentAvatar = gameObject; // Usar este GameObject como avatar
                
                // Configurar componentes para el jugador local
                if (isLocalPlayer)
                {
                    if (existingThirdPersonController != null)
                    {
                        existingThirdPersonController.enabled = true;
                        Debug.Log($"[PlayerController] ✅ ThirdPersonController habilitado en {gameObject.name}");
                    }
                    if (existingStarterInputs != null)
                    {
                        existingStarterInputs.enabled = true;
                        Debug.Log($"[PlayerController] ✅ StarterAssetsInputs habilitado en {gameObject.name}");
                    }
                    if (existingCharacterController != null)
                    {
                        existingCharacterController.enabled = true;
                        Debug.Log($"[PlayerController] ✅ CharacterController habilitado en {gameObject.name}");
                    }
                    
                    UpdateCameraTarget();
                }
                else
                {
                    // Deshabilitar componentes para jugador remoto
                    if (existingThirdPersonController != null) existingThirdPersonController.enabled = false;
                    if (existingStarterInputs != null) existingStarterInputs.enabled = false;
                }
                
                return; // Ya configurado, salir
            }
            
            Debug.LogError($"[PlayerController] ❌ CRÍTICO: avatarPrefab es NULL en {gameObject.name}!");
            Debug.LogError($"[PlayerController] ❌ CRÍTICO: NO se puede crear avatar. El movimiento NO funcionará!");
            Debug.LogError($"[PlayerController] ❌ CRÍTICO: Verificar que PlayerPrefabSetup haya asignado el avatarPrefab correctamente.");
            
            // Intentar buscar un prefab por defecto en Resources
            GameObject defaultPrefab = Resources.Load<GameObject>("PlayerAvatar");
            if (defaultPrefab != null)
            {
                Debug.LogWarning($"[PlayerController] ⚠️ Usando prefab por defecto desde Resources: {defaultPrefab.name}");
                avatarPrefab = defaultPrefab;
            }
            else
            {
                Debug.LogError($"[PlayerController] ❌ NO se encontró prefab por defecto en Resources/PlayerAvatar");
                return; // Salir sin crear avatar
            }
        }
        
        if (avatarPrefab != null)
        {
            Vector3 spawnPos = avatarSpawnPoint != null ? avatarSpawnPoint.position : transform.position;
            Debug.Log($"[PlayerController] ✅ Instanciando avatar en posición: {spawnPos}");
            currentAvatar = Instantiate(avatarPrefab, spawnPos, Quaternion.identity);
            currentAvatar.transform.SetParent(transform);
            Debug.Log($"[PlayerController] ✅ Avatar creado: {currentAvatar.name} (Parent: {currentAvatar.transform.parent?.name})");

            // Configurar el avatar con el rol
            var avatarController = currentAvatar.GetComponent<AvatarController>();
            if (avatarController != null)
            {
                avatarController.SetRole(playerRole, roleColor);
                // Aplicar color al avatar (ejemplo: cambiar color del material principal)
                var renderer = currentAvatar.GetComponentInChildren<Renderer>();
                if (renderer != null)
                {
                    renderer.material.color = roleColor;
                }
            }

            // Si es el jugador local, actualizar el target de la cámara al avatar
            if (isLocalPlayer)
            {
                UpdateCameraTarget();
            }

            // Network-safety: deshabilitar componentes de control/movimiento en avatares remotos
            // Buscar ThirdPersonController en los hijos y deshabilitarlo COMPLETAMENTE si no es el jugador local
            var thirdPersonControllers = currentAvatar.GetComponentsInChildren<StarterAssets.ThirdPersonController>(true);
            foreach (var tpc in thirdPersonControllers)
            {
                if (tpc == null) continue;
                if (isLocalPlayer)
                {
                    Debug.Log($"[PlayerController] ✅ Habilitando ThirdPersonController para jugador local en avatar: {currentAvatar.name}");
                    tpc.enabled = true;
                }
                else
                {
                    Debug.Log($"[PlayerController] ⚠️ DESHABILITANDO ThirdPersonController para jugador remoto en avatar: {currentAvatar.name}");
                    tpc.enabled = false;
                }
            }

            // También deshabilitar cualquier StarterAssetsInputs en el avatar para evitar que lea input local en avatares remotos
            var starterInputs = currentAvatar.GetComponentsInChildren<StarterAssets.StarterAssetsInputs>(true);
            foreach (var si in starterInputs)
            {
                if (si == null) continue;
                if (isLocalPlayer)
                {
                    Debug.Log($"[PlayerController] ✅ Habilitando StarterAssetsInputs para jugador local en avatar: {currentAvatar.name}");
                    si.enabled = true;
                }
                else
                {
                    Debug.Log($"[PlayerController] ⚠️ DESHABILITANDO StarterAssetsInputs para jugador remoto en avatar: {currentAvatar.name}");
                    si.enabled = false;
                }
            }
            
            // Si es el jugador local, habilitar todos los componentes de movimiento después de crear el avatar
            if (isLocalPlayer)
            {
                Debug.Log($"[PlayerController] Avatar creado para jugador local, habilitando componentes de movimiento...");
                StartCoroutine(EnableMovementComponentsDelayed());
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
        // 3. Buscar "CinemachineCameraTarget" (usado en SimplePlayer)
        if (targetTransform == null)
        {
            Transform cinemachineTarget = currentAvatar.transform.Find("CinemachineCameraTarget");
            if (cinemachineTarget != null)
            {
                targetTransform = cinemachineTarget;
                Debug.Log("CameraFollow: Usando CinemachineCameraTarget como target");
            }
        }
        // 4. Buscar "Head" (cabeza del avatar)
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
            
            // Si ahora es el jugador local, reconfigurar
            if (isLocalPlayer)
            {
                Debug.Log($"[PlayerController] Reconfigurando como jugador local después de cambio de ownership");
                SetupLocalPlayer();
            }
        }
        return isLocalPlayer;
    }

    /// <summary>
    /// Reconfigura el player después de que se le asigna un rol y se transfiere el ownership
    /// </summary>
    public void ReconfigureAfterRoleAssignment()
    {
        Debug.Log($"[PlayerController] Reconfigurando después de asignación de rol en {gameObject.name}");
        
        // Actualizar isLocalPlayer
        isLocalPlayer = photonView != null && photonView.IsMine;
        
        if (isLocalPlayer)
        {
            Debug.Log($"[PlayerController] ✅ Es jugador local, reconfigurando...");
            
            // Reconfigurar como jugador local
            SetupLocalPlayer();
            
            // Esperar un frame y luego habilitar componentes de movimiento
            StartCoroutine(EnableMovementComponentsDelayed());
        }
        else
        {
            Debug.LogWarning($"[PlayerController] ⚠️ No es jugador local. IsMine: {photonView?.IsMine}");
        }
    }

    /// <summary>
    /// Habilita los componentes de movimiento después de un pequeño delay
    /// DEBUG: Sin restricciones de posición - el jugador puede moverse libremente
    /// </summary>
    private System.Collections.IEnumerator EnableMovementComponentsDelayed()
    {
        // Esperar varios frames para que todo esté inicializado, especialmente StarterAssetsInputs
        yield return null;
        yield return null;
        yield return null; // Frame adicional para asegurar que StarterAssetsInputs esté disponible
        
        Debug.Log($"[PlayerController] 🔧 DEBUG: Habilitando componentes de movimiento SIN RESTRICCIONES...");
        
        // Habilitar PlayerInputHandler en este GameObject
        var inputHandler = GetComponent<PlayerInputHandler>();
        if (inputHandler == null)
        {
            inputHandler = gameObject.AddComponent<PlayerInputHandler>();
            Debug.Log($"[PlayerController] PlayerInputHandler agregado");
        }
        inputHandler.enabled = true;
        inputHandler.playerController = this;
        Debug.Log($"[PlayerController] PlayerInputHandler habilitado");
        
        // Habilitar componentes de movimiento en el avatar
        if (currentAvatar != null)
        {
            Debug.Log($"[PlayerController] Buscando componentes en avatar: {currentAvatar.name}");
            
            // Buscar ThirdPersonController
            var thirdPersonController = currentAvatar.GetComponentInChildren<StarterAssets.ThirdPersonController>(true);
            if (thirdPersonController != null)
            {
                thirdPersonController.enabled = true;
                Debug.Log($"[PlayerController] ✅ ThirdPersonController habilitado en avatar: {thirdPersonController.gameObject.name}");
                Debug.Log($"[PlayerController] 📊 ThirdPersonController.enabled: {thirdPersonController.enabled}");
                
                // DEBUG: Asegurar que MoveSpeed no sea 0
                if (thirdPersonController.MoveSpeed <= 0)
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ MoveSpeed es {thirdPersonController.MoveSpeed}, estableciendo a 2.0");
                    thirdPersonController.MoveSpeed = 2.0f;
                }
                Debug.Log($"[PlayerController] 🔧 DEBUG: MoveSpeed={thirdPersonController.MoveSpeed}, SprintSpeed={thirdPersonController.SprintSpeed}");
                
                // CRÍTICO: Verificar que StarterAssetsInputs esté en el mismo GameObject que ThirdPersonController
                var starterInputsOnController = thirdPersonController.GetComponent<StarterAssets.StarterAssetsInputs>();
                if (starterInputsOnController == null)
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ StarterAssetsInputs NO encontrado en el mismo GameObject que ThirdPersonController. Buscando en hijos...");
                    starterInputsOnController = thirdPersonController.GetComponentInChildren<StarterAssets.StarterAssetsInputs>(true);
                }
                
                if (starterInputsOnController != null)
                {
                    starterInputsOnController.enabled = true;
                    // Asegurar que el GameObject esté activo
                    if (!starterInputsOnController.gameObject.activeInHierarchy)
                    {
                        Debug.LogWarning($"[PlayerController] ⚠️ GameObject de StarterAssetsInputs está inactivo, activándolo...");
                        starterInputsOnController.gameObject.SetActive(true);
                    }
                    Debug.Log($"[PlayerController] ✅ StarterAssetsInputs encontrado en ThirdPersonController GameObject: {starterInputsOnController.gameObject.name}");
                    Debug.Log($"[PlayerController] 📊 StarterAssetsInputs.enabled: {starterInputsOnController.enabled}, move: {starterInputsOnController.move}");
                    
                    // CRÍTICO: Deshabilitar y re-habilitar ThirdPersonController para forzar que re-obtenga la referencia al StarterAssetsInputs
                    Debug.Log($"[PlayerController] 🔧 DEBUG: Reinicializando ThirdPersonController para forzar re-obtención de StarterAssetsInputs...");
                    thirdPersonController.enabled = false;
                    yield return null; // Esperar un frame
                    thirdPersonController.enabled = true;
                    Debug.Log($"[PlayerController] ✅ ThirdPersonController reinicializado");
                }
                else
                {
                    Debug.LogError($"[PlayerController] ❌ CRÍTICO: StarterAssetsInputs NO encontrado en ThirdPersonController GameObject. El movimiento NO funcionará!");
                }
                
                // Verificar CharacterController asociado
                var cc = thirdPersonController.GetComponent<CharacterController>();
                if (cc != null)
                {
                    cc.enabled = true;
                    // DEBUG: Asegurar que el CharacterController no tenga restricciones
                    Debug.Log($"[PlayerController] 📊 CharacterController encontrado: enabled={cc.enabled}, isGrounded={cc.isGrounded}");
                    Debug.Log($"[PlayerController] 🔧 DEBUG: CharacterController - center={cc.center}, radius={cc.radius}, height={cc.height}, slopeLimit={cc.slopeLimit}");
                    
                    // Verificar que el GameObject del CharacterController esté activo
                    if (!cc.gameObject.activeInHierarchy)
                    {
                        Debug.LogWarning($"[PlayerController] ⚠️ GameObject del CharacterController está inactivo, activándolo...");
                        cc.gameObject.SetActive(true);
                    }
                    
                    // CRÍTICO: Verificar si hay un Rigidbody que pueda estar interfiriendo
                    var rigidbody = cc.GetComponent<Rigidbody>();
                    if (rigidbody == null)
                    {
                        rigidbody = cc.GetComponentInParent<Rigidbody>();
                    }
                    if (rigidbody != null)
                    {
                        Debug.LogWarning($"[PlayerController] ⚠️ CRÍTICO: Rigidbody encontrado en el mismo GameObject o padre del CharacterController!");
                        Debug.LogWarning($"[PlayerController] ⚠️ Esto puede causar conflictos. Deshabilitando Rigidbody...");
                        rigidbody.isKinematic = true;
                        rigidbody.constraints = RigidbodyConstraints.FreezeAll;
                        Debug.LogWarning($"[PlayerController] ⚠️ Rigidbody configurado como Kinematic y FreezeAll para evitar conflictos");
                    }
                }
                else
                {
                    Debug.LogError($"[PlayerController] ❌ CRÍTICO: CharacterController NO encontrado en ThirdPersonController. El movimiento NO funcionará!");
                }
                
                // Verificar que el GameObject del ThirdPersonController esté activo
                if (!thirdPersonController.gameObject.activeInHierarchy)
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ GameObject del ThirdPersonController está inactivo, activándolo...");
                    thirdPersonController.gameObject.SetActive(true);
                }
                
                // Iniciar monitoreo del movimiento
                StartCoroutine(MonitorThirdPersonController(thirdPersonController));
            }
            else
            {
                Debug.LogWarning($"[PlayerController] ⚠️ ThirdPersonController no encontrado en avatar");
            }
            
            // Buscar StarterAssetsInputs
            var starterInputs = currentAvatar.GetComponentInChildren<StarterAssets.StarterAssetsInputs>(true);
            if (starterInputs != null)
            {
                starterInputs.enabled = true;
                Debug.Log($"[PlayerController] ✅ StarterAssetsInputs habilitado en avatar: {starterInputs.gameObject.name}");
                Debug.Log($"[PlayerController] 📊 StarterAssetsInputs.move inicial: {starterInputs.move}");
                Debug.Log($"[PlayerController] 📊 StarterAssetsInputs.enabled: {starterInputs.enabled}");
                
                // DEBUG: Verificar que el GameObject del StarterAssetsInputs esté activo
                if (!starterInputs.gameObject.activeInHierarchy)
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ GameObject de StarterAssetsInputs está inactivo, activándolo...");
                    starterInputs.gameObject.SetActive(true);
                }
                
                // Iniciar monitoreo del input
                StartCoroutine(MonitorStarterAssetsInputs(starterInputs));
            }
            else
            {
                Debug.LogWarning($"[PlayerController] ⚠️ StarterAssetsInputs no encontrado en avatar");
            }
            
            // Buscar CharacterController
            var characterController = currentAvatar.GetComponentInChildren<CharacterController>(true);
            if (characterController != null)
            {
                characterController.enabled = true;
                // DEBUG: Asegurar que no haya restricciones de movimiento
                characterController.slopeLimit = 90f; // Permitir subir cualquier pendiente
                characterController.stepOffset = 0.5f; // Permitir saltar más alto
                Debug.Log($"[PlayerController] ✅ CharacterController habilitado en avatar: {characterController.gameObject.name}");
                Debug.Log($"[PlayerController] 🔧 DEBUG: CharacterController configurado SIN RESTRICCIONES - slopeLimit={characterController.slopeLimit}, stepOffset={characterController.stepOffset}");
                Debug.Log($"[PlayerController] 📊 CharacterController: enabled={characterController.enabled}, isGrounded={characterController.isGrounded}");
            }
            else
            {
                Debug.LogWarning($"[PlayerController] ⚠️ CharacterController no encontrado en avatar");
            }
            
            // CRÍTICO: El movimiento está en el avatar, así que necesitamos sincronizar el transform del avatar
            // Buscar o crear PhotonTransformView en el avatar (donde está el CharacterController)
            var avatarPhotonTransformView = currentAvatar.GetComponent<Photon.Pun.PhotonTransformView>();
            if (avatarPhotonTransformView == null)
            {
                // Buscar en los hijos
                avatarPhotonTransformView = currentAvatar.GetComponentInChildren<Photon.Pun.PhotonTransformView>(true);
            }
            
            if (avatarPhotonTransformView == null)
            {
                // Crear PhotonTransformView en el avatar para sincronizar su transform
                avatarPhotonTransformView = currentAvatar.AddComponent<Photon.Pun.PhotonTransformView>();
                Debug.Log($"[PlayerController] PhotonTransformView agregado al avatar para sincronizar movimiento");
            }
            
            if (avatarPhotonTransformView != null)
            {
                // CRÍTICO: Verificar si el PhotonView del avatar es IsMine
                var avatarPhotonView = currentAvatar.GetComponent<PhotonView>();
                if (avatarPhotonView == null)
                {
                    avatarPhotonView = currentAvatar.GetComponentInChildren<PhotonView>(true);
                }
                
                // Si el avatar tiene su propio PhotonView y NO es IsMine, el PhotonTransformView moverá el transform
                // Esto podría estar bloqueando el movimiento local
                if (avatarPhotonView != null && !avatarPhotonView.IsMine)
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ CRÍTICO: El avatar tiene PhotonView pero NO es IsMine! Esto puede bloquear el movimiento.");
                    Debug.LogWarning($"[PlayerController] ⚠️ Deshabilitando PhotonTransformView del avatar para evitar interferencia...");
                    avatarPhotonTransformView.enabled = false;
                }
                else
                {
                    // El PhotonTransformView solo mueve el transform si !photonView.IsMine
                    // Si el PhotonView es IsMine, solo enviará datos, no moverá el transform
                    avatarPhotonTransformView.enabled = true;
                    Debug.Log($"[PlayerController] ✅ PhotonTransformView del avatar habilitado");
                    Debug.Log($"[PlayerController] 📊 PhotonTransformView: enabled={avatarPhotonTransformView.enabled}, GameObject: {avatarPhotonTransformView.gameObject.name}");
                    Debug.Log($"[PlayerController] 🔧 DEBUG: PhotonView.IsMine: {photonView?.IsMine} - PhotonTransformView NO moverá el transform localmente");
                }
                
                // Iniciar monitoreo de sincronización Photon
                StartCoroutine(MonitorPhotonTransformView(avatarPhotonTransformView));
            }
            
            // También verificar PhotonTransformView en el GameObject raíz (por si acaso)
            var rootPhotonTransformView = GetComponent<Photon.Pun.PhotonTransformView>();
            if (rootPhotonTransformView != null)
            {
                rootPhotonTransformView.enabled = true;
                Debug.Log($"[PlayerController] ✅ PhotonTransformView del GameObject raíz también habilitado");
                Debug.Log($"[PlayerController] 🔧 DEBUG: PhotonView.IsMine: {photonView?.IsMine} - NO moverá el transform localmente");
            }
            
            // Verificar que el PhotonView esté observando el PhotonTransformView del avatar
            if (photonView != null && avatarPhotonTransformView != null)
            {
                var observedComponents = photonView.ObservedComponents;
                bool hasAvatarTransformView = false;
                
                if (observedComponents != null)
                {
                    foreach (var comp in observedComponents)
                    {
                        if (comp == avatarPhotonTransformView)
                        {
                            hasAvatarTransformView = true;
                            break;
                        }
                    }
                }
                
                if (!hasAvatarTransformView)
                {
                    Debug.Log($"[PlayerController] Agregando PhotonTransformView del avatar a ObservedComponents del PhotonView");
                    
                    // ObservedComponents es una List<Component>
                    // Inicializar la lista si es null
                    if (photonView.ObservedComponents == null)
                    {
                        photonView.ObservedComponents = new List<Component>();
                    }
                    
                    // Agregar el PhotonTransformView del avatar si no está ya en la lista
                    if (!photonView.ObservedComponents.Contains(avatarPhotonTransformView))
                    {
                        photonView.ObservedComponents.Add(avatarPhotonTransformView);
                        Debug.Log($"[PlayerController] ✅ PhotonView ahora observa el PhotonTransformView del avatar");
                    }
                }
                else
                {
                    Debug.Log($"[PlayerController] ✅ PhotonView ya está observando el PhotonTransformView del avatar");
                }
            }
        }
        else
        {
            Debug.LogWarning($"[PlayerController] ⚠️ currentAvatar es null - no se pueden habilitar componentes de movimiento");
        }
    }

    public string GetRole()
    {
        return playerRole;
    }

    public Color GetRoleColor()
    {
        return roleColor;
    }

    /// <summary>
    /// Monitorea el estado de StarterAssetsInputs para debugging
    /// </summary>
    private System.Collections.IEnumerator MonitorStarterAssetsInputs(StarterAssets.StarterAssetsInputs starterInputs)
    {
        Vector2 lastMove = Vector2.zero;
        int frameCount = 0;
        
        Debug.Log($"[PlayerController] 🔍 MONITOREO StarterAssetsInputs iniciado | GameObject: {starterInputs.gameObject.name} | enabled: {starterInputs.enabled}");
        
        while (starterInputs != null && isLocalPlayer)
        {
            frameCount++;
            Vector2 currentMove = starterInputs.move;
            bool moveChanged = currentMove != lastMove;
            
            // Log cada 30 frames o cuando el input cambia
            if (frameCount % 30 == 0 || moveChanged)
            {
                if (currentMove.magnitude > 0.01f || moveChanged)
                {
                    Debug.Log($"[PlayerController] 📊 StarterAssetsInputs.move: {currentMove} (anterior: {lastMove}) | enabled: {starterInputs.enabled} | GameObject: {starterInputs.gameObject.name} | Transform: {starterInputs.transform.position}");
                }
                lastMove = currentMove;
            }
            
            yield return null;
        }
    }

    /// <summary>
    /// Monitorea el estado de ThirdPersonController para debugging
    /// </summary>
    private System.Collections.IEnumerator MonitorThirdPersonController(StarterAssets.ThirdPersonController thirdPersonController)
    {
        Vector3 lastPosition = Vector3.zero;
        int frameCount = 0;
        
        // Obtener referencia a StarterAssetsInputs del mismo GameObject
        StarterAssets.StarterAssetsInputs inputs = thirdPersonController.GetComponent<StarterAssets.StarterAssetsInputs>();
        if (inputs == null)
        {
            inputs = thirdPersonController.GetComponentInChildren<StarterAssets.StarterAssetsInputs>(true);
        }
        
        Debug.Log($"[PlayerController] 🔍 MONITOREO ThirdPersonController iniciado | GameObject: {thirdPersonController.gameObject.name} | StarterAssetsInputs: {(inputs != null ? inputs.gameObject.name : "NULL")}");
        
        while (thirdPersonController != null && isLocalPlayer && currentAvatar != null)
        {
            frameCount++;
            Vector3 currentPosition = currentAvatar.transform.position;
            bool positionChanged = Vector3.Distance(currentPosition, lastPosition) > 0.01f;
            
            // Log cada 30 frames o cuando la posición cambia o cuando hay input
            if (frameCount % 30 == 0 || positionChanged)
            {
                // Verificar StarterAssetsInputs
                if (inputs != null)
                {
                    Vector2 moveInput = inputs.move;
                    if (moveInput.magnitude > 0.01f || positionChanged)
                    {
                        Debug.Log($"[PlayerController] 🎮 ThirdPersonController: move={moveInput} | enabled={thirdPersonController.enabled} | pos={currentPosition} | posCambió={positionChanged} | GameObject: {thirdPersonController.gameObject.name}");
                    }
                }
                else
                {
                    Debug.LogWarning($"[PlayerController] ⚠️ StarterAssetsInputs NULL en monitoreo ThirdPersonController");
                }
                
                if (positionChanged)
                {
                    Debug.Log($"[PlayerController] 🏃 MOVIMIENTO DETECTADO: Posición avatar: {currentPosition} (anterior: {lastPosition}) | enabled: {thirdPersonController.enabled}");
                }
                lastPosition = currentPosition;
            }
            
            // Verificar CharacterController cada 30 frames
            var cc = thirdPersonController.GetComponent<CharacterController>();
            if (cc != null && frameCount % 30 == 0)
            {
                Debug.Log($"[PlayerController] 📊 CharacterController: enabled={cc.enabled}, isGrounded={cc.isGrounded}, velocity={cc.velocity}, center={cc.center}, radius={cc.radius}, height={cc.height}");
            }
            
            yield return null;
        }
    }

    /// <summary>
    /// Monitorea el estado de PhotonTransformView para debugging de sincronización
    /// </summary>
    private System.Collections.IEnumerator MonitorPhotonTransformView(Photon.Pun.PhotonTransformView photonTransformView)
    {
        Vector3 lastPosition = Vector3.zero;
        int frameCount = 0;
        
        while (photonTransformView != null && isLocalPlayer && currentAvatar != null)
        {
            frameCount++;
            Vector3 currentPosition = currentAvatar.transform.position;
            
            // Log cada 120 frames (aproximadamente cada 2 segundos) o cuando la posición cambia significativamente
            if (frameCount % 120 == 0 || Vector3.Distance(currentPosition, lastPosition) > 0.1f)
            {
                if (Vector3.Distance(currentPosition, lastPosition) > 0.01f)
                {
                    Debug.Log($"[PlayerController] 📡 PHOTON SYNC: Posición sincronizada: {currentPosition} | PhotonTransformView.enabled: {photonTransformView.enabled} | IsMine: {photonView?.IsMine}");
                }
                lastPosition = currentPosition;
            }
            
            yield return null;
        }
    }

    void OnDestroy()
    {
        if (currentAvatar != null)
        {
            Destroy(currentAvatar);
        }
    }
}
