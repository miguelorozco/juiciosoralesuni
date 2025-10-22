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

    private Vector3 currentVelocity;
    private float mouseX = 0f;
    private float mouseY = 0f;

    void Start()
    {
        if (target == null)
        {
            Debug.LogWarning("CameraFollow: No hay target asignado");
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
        if (Input.GetMouseButton(1)) // Botón derecho del mouse
        {
            mouseX += Input.GetAxis("Mouse X") * mouseSensitivity;
            mouseY -= Input.GetAxis("Mouse Y") * mouseSensitivity;
            mouseY = Mathf.Clamp(mouseY, -80f, 80f);
        }
    }

    void FollowTarget()
    {
        // Calcular posición objetivo
        Vector3 targetPosition = target.position + offset;

        // Rotar el offset basado en el input del mouse
        Quaternion rotation = Quaternion.Euler(mouseY, mouseX, 0);
        targetPosition = target.position + rotation * offset;

        // Mover la cámara suavemente
        transform.position = Vector3.SmoothDamp(transform.position, targetPosition, ref currentVelocity, 1f / followSpeed);

        // Rotar la cámara para mirar al target
        if (followRotation)
        {
            Vector3 lookDirection = target.position - transform.position;
            Quaternion targetRotation = Quaternion.LookRotation(lookDirection);
            transform.rotation = Quaternion.Slerp(transform.rotation, targetRotation, rotationSpeed * Time.deltaTime);
        }
    }

    public void SetTarget(Transform newTarget)
    {
        target = newTarget;
    }
}
