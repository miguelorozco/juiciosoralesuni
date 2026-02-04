using UnityEngine;
using Photon.Pun;
using StarterAssets;
using System.Linq;

/// <summary>
/// Script de diagnóstico para verificar por qué el jugador no se puede mover
/// </summary>
public class PlayerMovementDiagnostic : MonoBehaviourPunCallbacks
{
    private PlayerController playerController;
    private GameObject currentAvatar;
    private StarterAssets.ThirdPersonController thirdPersonController;
    private StarterAssets.StarterAssetsInputs starterAssetsInputs;
    private CharacterController characterController;
    private Photon.Pun.PhotonTransformView photonTransformView;
    
    private float diagnosticInterval = 2f;
    private float lastDiagnosticTime = 0f;
    
    void Start()
    {
        playerController = GetComponent<PlayerController>();
        if (playerController == null)
        {
            Debug.LogError("[PlayerMovementDiagnostic] PlayerController no encontrado!");
            enabled = false;
            return;
        }
        
        Debug.Log("[PlayerMovementDiagnostic] 🔍 Iniciando diagnóstico de movimiento...");
    }
    
    void Update()
    {
        if (Time.time - lastDiagnosticTime < diagnosticInterval) return;
        lastDiagnosticTime = Time.time;
        
        if (photonView == null || !photonView.IsMine)
        {
            return; // Solo diagnosticar para el jugador local
        }
        
        PerformDiagnostic();
    }
    
    void PerformDiagnostic()
    {
        Debug.Log("═══════════════════════════════════════════════════════════");
        Debug.Log("[PlayerMovementDiagnostic] 🔍 DIAGNÓSTICO COMPLETO DE MOVIMIENTO");
        Debug.Log("═══════════════════════════════════════════════════════════");
        
        // 0. Listar TODOS los players en la escena
        var allPlayers = FindObjectsOfType<PlayerController>();
        Debug.Log($"[PlayerMovementDiagnostic] 📊 TOTAL PLAYERS EN ESCENA: {allPlayers.Length}");
        foreach (var player in allPlayers)
        {
            var avatar = player.GetCurrentAvatar();
            var photonView = player.GetComponent<PhotonView>();
            Debug.Log($"[PlayerMovementDiagnostic]   - {player.gameObject.name}: Role={player.GetRole()}, IsLocalPlayer={player.IsLocalPlayer()}, IsMine={photonView?.IsMine}, Avatar={(avatar != null ? avatar.name : "NULL")}, Position={player.transform.position}");
        }
        
        // 1. Verificar PlayerController
        if (playerController != null)
        {
            Debug.Log($"[PlayerMovementDiagnostic] ✅ PlayerController encontrado: {playerController.gameObject.name}");
            Debug.Log($"[PlayerMovementDiagnostic]   - IsLocalPlayer: {playerController.IsLocalPlayer()}");
            Debug.Log($"[PlayerMovementDiagnostic]   - PlayerRole: {playerController.GetRole()}");
            Debug.Log($"[PlayerMovementDiagnostic]   - Position: {playerController.transform.position}");
            
            var photonView = playerController.GetComponent<PhotonView>();
            Debug.Log($"[PlayerMovementDiagnostic]   - PhotonView.IsMine: {photonView?.IsMine}");
            
            currentAvatar = playerController.GetCurrentAvatar();
            if (currentAvatar != null)
            {
                Debug.Log($"[PlayerMovementDiagnostic] ✅ Avatar encontrado: {currentAvatar.name}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Avatar activo: {currentAvatar.activeInHierarchy}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Avatar posición: {currentAvatar.transform.position}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Avatar parent: {currentAvatar.transform.parent?.name ?? "NO PARENT"}");
            }
            else
            {
                Debug.LogError("[PlayerMovementDiagnostic] ❌ Avatar es NULL!");
                Debug.LogError("[PlayerMovementDiagnostic] 🔍 Buscando todos los avatares en la escena...");
                var allAvatars = FindObjectsOfType<GameObject>().Where(go => go.name.Contains("Player_") || go.name.Contains("Avatar"));
                Debug.LogError($"[PlayerMovementDiagnostic] 📊 Avatares encontrados: {allAvatars.Count()}");
                foreach (var av in allAvatars)
                {
                    Debug.LogError($"[PlayerMovementDiagnostic]   - {av.name} (position: {av.transform.position}, parent: {av.transform.parent?.name ?? "NO PARENT"})");
                }
            }
        }
        else
        {
            Debug.LogError("[PlayerMovementDiagnostic] ❌ PlayerController es NULL!");
        }
        
        // 2. Verificar ThirdPersonController
        if (currentAvatar != null)
        {
            thirdPersonController = currentAvatar.GetComponentInChildren<StarterAssets.ThirdPersonController>(true);
            if (thirdPersonController != null)
            {
                Debug.Log($"[PlayerMovementDiagnostic] ✅ ThirdPersonController encontrado: {thirdPersonController.gameObject.name}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Enabled: {thirdPersonController.enabled}");
                Debug.Log($"[PlayerMovementDiagnostic]   - GameObject activo: {thirdPersonController.gameObject.activeInHierarchy}");
                Debug.Log($"[PlayerMovementDiagnostic]   - MoveSpeed: {thirdPersonController.MoveSpeed}");
                Debug.Log($"[PlayerMovementDiagnostic]   - SprintSpeed: {thirdPersonController.SprintSpeed}");
            }
            else
            {
                Debug.LogError("[PlayerMovementDiagnostic] ❌ ThirdPersonController NO encontrado!");
            }
        }
        
        // 3. Verificar StarterAssetsInputs
        if (thirdPersonController != null)
        {
            starterAssetsInputs = thirdPersonController.GetComponent<StarterAssets.StarterAssetsInputs>();
            if (starterAssetsInputs == null)
            {
                starterAssetsInputs = thirdPersonController.GetComponentInChildren<StarterAssets.StarterAssetsInputs>(true);
            }
            
            if (starterAssetsInputs != null)
            {
                Debug.Log($"[PlayerMovementDiagnostic] ✅ StarterAssetsInputs encontrado: {starterAssetsInputs.gameObject.name}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Enabled: {starterAssetsInputs.enabled}");
                Debug.Log($"[PlayerMovementDiagnostic]   - GameObject activo: {starterAssetsInputs.gameObject.activeInHierarchy}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Move input: {starterAssetsInputs.move}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Jump: {starterAssetsInputs.jump}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Sprint: {starterAssetsInputs.sprint}");
            }
            else
            {
                Debug.LogError("[PlayerMovementDiagnostic] ❌ StarterAssetsInputs NO encontrado!");
            }
        }
        
        // 4. Verificar CharacterController
        if (thirdPersonController != null)
        {
            characterController = thirdPersonController.GetComponent<CharacterController>();
            if (characterController != null)
            {
                Debug.Log($"[PlayerMovementDiagnostic] ✅ CharacterController encontrado: {characterController.gameObject.name}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Enabled: {characterController.enabled}");
                Debug.Log($"[PlayerMovementDiagnostic]   - GameObject activo: {characterController.gameObject.activeInHierarchy}");
                Debug.Log($"[PlayerMovementDiagnostic]   - IsGrounded: {characterController.isGrounded}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Velocity: {characterController.velocity}");
                Debug.Log($"[PlayerMovementDiagnostic]   - SlopeLimit: {characterController.slopeLimit}");
                Debug.Log($"[PlayerMovementDiagnostic]   - StepOffset: {characterController.stepOffset}");
            }
            else
            {
                Debug.LogError("[PlayerMovementDiagnostic] ❌ CharacterController NO encontrado!");
            }
        }
        
        // 5. Verificar PhotonTransformView
        if (currentAvatar != null)
        {
            photonTransformView = currentAvatar.GetComponent<Photon.Pun.PhotonTransformView>();
            if (photonTransformView == null)
            {
                photonTransformView = currentAvatar.GetComponentInChildren<Photon.Pun.PhotonTransformView>(true);
            }
            
            if (photonTransformView != null)
            {
                Debug.Log($"[PlayerMovementDiagnostic] ✅ PhotonTransformView encontrado: {photonTransformView.gameObject.name}");
                Debug.Log($"[PlayerMovementDiagnostic]   - Enabled: {photonTransformView.enabled}");
                Debug.Log($"[PlayerMovementDiagnostic]   - PhotonView.IsMine: {photonView?.IsMine}");
                
                // Usar reflexión para obtener m_SynchronizePosition
                var synchronizePositionField = typeof(Photon.Pun.PhotonTransformView).GetField("m_SynchronizePosition", 
                    System.Reflection.BindingFlags.NonPublic | System.Reflection.BindingFlags.Instance);
                if (synchronizePositionField != null)
                {
                    bool syncPos = (bool)synchronizePositionField.GetValue(photonTransformView);
                    Debug.Log($"[PlayerMovementDiagnostic]   - SynchronizePosition: {syncPos}");
                }
            }
        }
        
        // 6. Verificar PlayerInputHandler
        var inputHandler = GetComponent<PlayerInputHandler>();
        if (inputHandler != null)
        {
            Debug.Log($"[PlayerMovementDiagnostic] ✅ PlayerInputHandler encontrado: {inputHandler.gameObject.name}");
            Debug.Log($"[PlayerMovementDiagnostic]   - Enabled: {inputHandler.enabled}");
        }
        else
        {
            Debug.LogWarning("[PlayerMovementDiagnostic] ⚠️ PlayerInputHandler NO encontrado!");
        }
        
        // 7. Verificar si hay scripts que puedan estar fijando la posición
        if (currentAvatar != null)
        {
            var allMonoBehaviours = currentAvatar.GetComponentsInChildren<MonoBehaviour>(true);
            foreach (var mb in allMonoBehaviours)
            {
                if (mb == null) continue;
                string typeName = mb.GetType().Name;
                if (typeName.Contains("Position") || typeName.Contains("Lock") || typeName.Contains("Freeze") || 
                    typeName.Contains("Constraint") || typeName.Contains("Restrict"))
                {
                    Debug.LogWarning($"[PlayerMovementDiagnostic] ⚠️ Script sospechoso encontrado: {typeName} en {mb.gameObject.name} (enabled: {mb.enabled})");
                }
            }
        }
        
        // 8. Verificar Rigidbody (si existe)
        if (currentAvatar != null)
        {
            var rigidbody = currentAvatar.GetComponent<Rigidbody>();
            if (rigidbody != null)
            {
                Debug.LogWarning($"[PlayerMovementDiagnostic] ⚠️ Rigidbody encontrado en avatar: {rigidbody.gameObject.name}");
                Debug.LogWarning($"[PlayerMovementDiagnostic]   - IsKinematic: {rigidbody.isKinematic}");
                Debug.LogWarning($"[PlayerMovementDiagnostic]   - Constraints: {rigidbody.constraints}");
                Debug.LogWarning($"[PlayerMovementDiagnostic]   - FreezePosition: {rigidbody.constraints & RigidbodyConstraints.FreezePosition}");
            }
        }
        
        Debug.Log("═══════════════════════════════════════════════════════════");
    }
}
