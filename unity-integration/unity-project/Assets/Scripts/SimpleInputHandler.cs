using UnityEngine;
using StarterAssets;

/// <summary>
/// Handler simple de input para el player básico
/// </summary>
public class SimpleInputHandler : MonoBehaviour
{
    public GameObject playerCapsule;
    public StarterAssetsInputs starterAssetsInputs;
    
    private void Start()
    {
        Debug.Log("[SimpleInputHandler] 🚀 Iniciando handler de input simple...");
        
        if (starterAssetsInputs == null)
        {
            starterAssetsInputs = GetComponent<StarterAssetsInputs>();
        }
        
        if (starterAssetsInputs == null)
        {
            Debug.LogError("[SimpleInputHandler] ❌ StarterAssetsInputs no encontrado!");
        }
        else
        {
            Debug.Log("[SimpleInputHandler] ✅ StarterAssetsInputs encontrado");
        }
    }
    
    void Update()
    {
        if (starterAssetsInputs == null) return;
        
        // Capturar input del teclado
        Vector2 moveInput = Vector2.zero;
        
        if (Input.GetKey(KeyCode.W) || Input.GetKey(KeyCode.UpArrow))
        {
            moveInput.y += 1f;
        }
        if (Input.GetKey(KeyCode.S) || Input.GetKey(KeyCode.DownArrow))
        {
            moveInput.y -= 1f;
        }
        if (Input.GetKey(KeyCode.A) || Input.GetKey(KeyCode.LeftArrow))
        {
            moveInput.x -= 1f;
        }
        if (Input.GetKey(KeyCode.D) || Input.GetKey(KeyCode.RightArrow))
        {
            moveInput.x += 1f;
        }
        
        // Normalizar el vector de movimiento
        if (moveInput.magnitude > 1f)
        {
            moveInput = moveInput.normalized;
        }
        
        // Actualizar StarterAssetsInputs
        starterAssetsInputs.move = moveInput;
        starterAssetsInputs.sprint = Input.GetKey(KeyCode.LeftShift);
        starterAssetsInputs.jump = Input.GetKeyDown(KeyCode.Space);
        
        // Log cuando hay input
        if (moveInput.magnitude > 0.01f)
        {
            Debug.Log($"[SimpleInputHandler] 📥 INPUT: move={moveInput}, sprint={starterAssetsInputs.sprint}, jump={starterAssetsInputs.jump}");
        }
    }
}
