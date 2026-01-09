using UnityEngine;

public class CameraFollow : MonoBehaviour
{
    [Header("Configuración de Cámara")]
    public Transform target;
    public Vector3 offset = new Vector3(0, 5, -10);
    public float followSpeed = 5f;
    public float rotationSpeed = 5f;

    [Header("Configuración de Rotación")]
    public bool followRotation = true;
    public float mouseSensitivity = 2f;
    public bool rotateAvatarWithMouse = true; // Si true, el avatar rota con el mouse horizontal

    [Header("Configuración de Ángulo")]
    public float minVerticalAngle = -30f;
    public float maxVerticalAngle = 60f;

    private Vector3 currentVelocity;
    private float mouseY = 0f; // Solo ángulo vertical (arriba/abajo)
    private Transform avatarTransform; // Referencia al transform del avatar para rotarlo

    void Start()
    {
        if (target == null)
        {
            Debug.LogWarning("CameraFollow: No hay target asignado");
        }
        
        // Inicializar el ángulo vertical basado en la posición inicial de la cámara
        if (target != null)
        {
            Vector3 direction = (transform.position - target.position).normalized;
            mouseY = Mathf.Asin(direction.y) * Mathf.Rad2Deg;
            mouseY = Mathf.Clamp(mouseY, minVerticalAngle, maxVerticalAngle);
        }
        else
        {
            // Ángulo inicial basado en el offset
            mouseY = Mathf.Atan2(offset.y, Mathf.Abs(offset.z)) * Mathf.Rad2Deg;
        }
    }

    void LateUpdate()
    {
        if (target == null) return;

        HandleMouseInput();
        FollowTarget();
    }

    void HandleMouseInput()
    {
        // Mouse horizontal: rota el avatar
        // Mouse vertical: controla el ángulo de la cámara (arriba/abajo)
        if (rotateAvatarWithMouse)
        {
            float mouseX = Input.GetAxis("Mouse X") * mouseSensitivity;
            
            // Obtener el transform del avatar
            if (avatarTransform == null)
            {
                var playerController = target.GetComponentInParent<PlayerController>();
                if (playerController != null)
                {
                    avatarTransform = playerController.transform;
                }
                else
                {
                    avatarTransform = target;
                }
            }

            // Rotar el avatar horizontalmente con el mouse
            if (avatarTransform != null)
            {
                avatarTransform.Rotate(0, mouseX, 0);
            }
        }

        // Controlar el ángulo vertical de la cámara
        mouseY -= Input.GetAxis("Mouse Y") * mouseSensitivity;
        mouseY = Mathf.Clamp(mouseY, minVerticalAngle, maxVerticalAngle);
    }

    void FollowTarget()
    {
        // Obtener el transform del avatar si no lo tenemos
        if (avatarTransform == null)
        {
            var playerController = target.GetComponentInParent<PlayerController>();
            if (playerController != null)
            {
                avatarTransform = playerController.transform;
            }
            else
            {
                avatarTransform = target;
            }
        }

        if (avatarTransform == null) return;

        // Calcular la posición de la cámara detrás del avatar
        // Usar la rotación del avatar para el eje horizontal
        Vector3 avatarForward = avatarTransform.forward;
        avatarForward.y = 0; // Mantener solo la dirección horizontal
        avatarForward.Normalize();

        // Calcular el offset rotado según la rotación del avatar y el ángulo vertical
        Quaternion horizontalRotation = Quaternion.LookRotation(avatarForward);
        Quaternion verticalRotation = Quaternion.Euler(mouseY, 0, 0);
        
        // Aplicar el offset relativo a la rotación del avatar
        Vector3 rotatedOffset = horizontalRotation * verticalRotation * new Vector3(0, offset.y, offset.z);
        
        // Calcular la posición deseada detrás del avatar
        Vector3 desiredPosition = target.position + rotatedOffset;
        
        // Mover la cámara suavemente hacia la posición deseada
        transform.position = Vector3.SmoothDamp(transform.position, desiredPosition, ref currentVelocity, 1f / followSpeed);

        // Rotar la cámara para mirar al target
        if (followRotation)
        {
            Vector3 lookDirection = target.position - transform.position;
            if (lookDirection != Vector3.zero)
            {
                Quaternion targetRotation = Quaternion.LookRotation(lookDirection);
                transform.rotation = Quaternion.Slerp(transform.rotation, targetRotation, rotationSpeed * Time.deltaTime);
            }
        }
    }

    public void SetTarget(Transform newTarget)
    {
        target = newTarget;
        avatarTransform = null; // Resetear para buscar el nuevo avatar
    }
}
