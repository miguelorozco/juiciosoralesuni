using UnityEngine;

public class PlayerInputHandler : MonoBehaviour
{
    [Header("Referencias")]
    public PlayerController playerController;

    [Header("Configuración de Input")]
    public KeyCode forwardKey = KeyCode.W;
    public KeyCode backwardKey = KeyCode.S;
    public KeyCode leftKey = KeyCode.A;
    public KeyCode rightKey = KeyCode.D;

    private Vector3 inputDirection;

    void Update()
    {
        if (playerController == null || !playerController.IsLocalPlayer()) return;

        HandleInput();
    }

    void HandleInput()
    {
        inputDirection = Vector3.zero;

        // Movimiento hacia adelante/atrás
        if (Input.GetKey(forwardKey))
        {
            inputDirection += Vector3.forward;
        }
        if (Input.GetKey(backwardKey))
        {
            inputDirection += Vector3.back;
        }

        // Movimiento hacia izquierda/derecha
        if (Input.GetKey(leftKey))
        {
            inputDirection += Vector3.left;
        }
        if (Input.GetKey(rightKey))
        {
            inputDirection += Vector3.right;
        }

        // Aplicar movimiento
        if (inputDirection != Vector3.zero)
        {
            playerController.Move(inputDirection);
        }
    }
}
