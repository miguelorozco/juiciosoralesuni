using UnityEngine;
using Photon.Pun;

/// <summary>
/// Sistema de cámara en tercera persona que sigue al avatar del jugador.
/// Posición estándar de la industria: ~3 metros detrás, ~1.7 metros arriba del personaje.
/// </summary>
public class ThirdPersonCamera : MonoBehaviourPun
{
    [Header("Target")]
    [Tooltip("El transform del avatar a seguir (generalmente el cuerpo del personaje)")]
    public Transform target;

    [Header("Camera Settings")]
    [Tooltip("Distancia horizontal detrás del personaje (estándar: 3-4 metros)")]
    public float distance = 3.5f;
    
    [Tooltip("Altura de la cámara sobre el personaje (estándar: 1.5-2 metros)")]
    public float height = 1.7f;
    
    [Tooltip("Offset lateral (0 = centrado, positivo = derecha)")]
    public float lateralOffset = 0.5f;
    
    [Tooltip("Suavidad del seguimiento (mayor = más suave)")]
    public float smoothSpeed = 8f;
    
    [Tooltip("Punto de enfoque (altura relativa al target, ~1.6m para cabeza)")]
    public float lookAtHeight = 1.6f;

    [Header("Rotation")]
    [Tooltip("Si true, la cámara rota con el personaje. Si false, usa rotación libre.")]
    public bool followRotation = true;

    [Tooltip("Sensibilidad de rotación con el mouse (si followRotation = false)")]
    public float rotationSpeed = 100f;

    [Header("Collision")]
    [Tooltip("Detectar obstáculos entre cámara y personaje")]
    public bool detectCollisions = true;
    
    [Tooltip("Layer mask para detectar colisiones")]
    public LayerMask collisionLayers = -1;
    
    [Tooltip("Radio de la esfera para detección de colisiones")]
    public float collisionRadius = 0.3f;

    private Camera cam;
    private AudioListener audioListener;
    private Vector3 currentVelocity;
    private float currentDistance;
    private float horizontalAngle;
    private float verticalAngle;

    void Awake()
    {
        cam = GetComponent<Camera>();
        audioListener = GetComponent<AudioListener>();
        
        // CRÍTICO: Desactivar INMEDIATAMENTE todas las cámaras
        if (cam != null)
        {
            cam.enabled = false;
        }
        if (audioListener != null)
        {
            audioListener.enabled = false;
        }

        currentDistance = distance;
    }

    void Start()
    {
        // Solo activar para el jugador local
        if (!photonView.IsMine)
        {
            // Desactivar completamente el script para jugadores remotos
            enabled = false;
            return;
        }

        // Buscar el target automáticamente si no está asignado
        if (target == null)
        {
            target = transform.parent;
            if (target == null)
            {
                Debug.LogError("[ThirdPersonCamera] No se encontró target. La cámara debe ser hija del Player.");
                enabled = false;
                return;
            }
        }

        // Activar la cámara solo para el jugador local
        if (cam != null)
        {
            cam.enabled = true;
            cam.depth = 0;
        }
        if (audioListener != null)
        {
            audioListener.enabled = true;
        }

        // Desactivar Main Camera y otros AudioListeners
        DisableMainCamera();
        DisableOtherAudioListeners();

        // Inicializar ángulos de rotación
        Vector3 angles = target.eulerAngles;
        horizontalAngle = angles.y;
        verticalAngle = 15f; // Ángulo vertical inicial (mirando ligeramente hacia abajo)

        Debug.Log($"[ThirdPersonCamera] Cámara en tercera persona ACTIVADA para {target.name}");
    }

    void LateUpdate()
    {
        if (target == null || cam == null || !cam.enabled) return;

        UpdateCameraPosition();
    }

    private void UpdateCameraPosition()
    {
        // Calcular rotación
        if (followRotation)
        {
            // Seguir la rotación del personaje
            horizontalAngle = target.eulerAngles.y;
        }
        else
        {
            // Rotación libre con el mouse (opcional)
            if (Input.GetMouseButton(1)) // Click derecho para rotar
            {
                horizontalAngle += Input.GetAxis("Mouse X") * rotationSpeed * Time.deltaTime;
                verticalAngle -= Input.GetAxis("Mouse Y") * rotationSpeed * Time.deltaTime;
                verticalAngle = Mathf.Clamp(verticalAngle, -20f, 60f);
            }
        }

        // Punto de enfoque (look at point)
        Vector3 lookAtPoint = target.position + Vector3.up * lookAtHeight;

        // Calcular posición deseada de la cámara
        Quaternion rotation = Quaternion.Euler(verticalAngle, horizontalAngle, 0);
        Vector3 offset = new Vector3(lateralOffset, height, -distance);
        Vector3 desiredPosition = target.position + rotation * offset;

        // Detección de colisiones (acercar cámara si hay obstáculos)
        float targetDistance = distance;
        if (detectCollisions)
        {
            Vector3 directionToCamera = desiredPosition - lookAtPoint;
            RaycastHit hit;
            if (Physics.SphereCast(lookAtPoint, collisionRadius, directionToCamera.normalized, 
                out hit, distance, collisionLayers))
            {
                targetDistance = Mathf.Clamp(hit.distance - collisionRadius, 0.5f, distance);
            }
        }

        // Suavizar la distancia
        currentDistance = Mathf.Lerp(currentDistance, targetDistance, Time.deltaTime * smoothSpeed * 2f);

        // Recalcular posición con distancia ajustada
        offset = new Vector3(lateralOffset, height, -currentDistance);
        desiredPosition = target.position + rotation * offset;

        // Interpolar suavemente a la posición deseada
        transform.position = Vector3.SmoothDamp(transform.position, desiredPosition, 
            ref currentVelocity, 1f / smoothSpeed);

        // Mirar al punto de enfoque
        transform.LookAt(lookAtPoint);
    }

    private void DisableMainCamera()
    {
        Camera mainCam = Camera.main;
        if (mainCam != null && mainCam != cam)
        {
            mainCam.enabled = false;
            AudioListener mainListener = mainCam.GetComponent<AudioListener>();
            if (mainListener != null)
            {
                mainListener.enabled = false;
            }
            Debug.Log("[ThirdPersonCamera] Main Camera desactivada");
        }
    }

    private void DisableOtherAudioListeners()
    {
        AudioListener[] allListeners = FindObjectsOfType<AudioListener>();
        foreach (AudioListener listener in allListeners)
        {
            if (listener != audioListener && listener.enabled)
            {
                listener.enabled = false;
            }
        }
    }

    // Gizmos para visualizar la configuración en el editor
    void OnDrawGizmosSelected()
    {
        if (target == null) return;

        Vector3 lookAtPoint = target.position + Vector3.up * lookAtHeight;
        Quaternion rotation = Quaternion.Euler(verticalAngle, horizontalAngle, 0);
        Vector3 offset = new Vector3(lateralOffset, height, -distance);
        Vector3 cameraPosition = target.position + rotation * offset;

        // Dibujar posición de la cámara
        Gizmos.color = Color.cyan;
        Gizmos.DrawWireSphere(cameraPosition, 0.3f);

        // Dibujar línea de visión
        Gizmos.color = Color.yellow;
        Gizmos.DrawLine(cameraPosition, lookAtPoint);

        // Dibujar punto de enfoque
        Gizmos.color = Color.green;
        Gizmos.DrawWireSphere(lookAtPoint, 0.2f);

        // Dibujar radio de detección de colisiones
        if (detectCollisions)
        {
            Gizmos.color = new Color(1f, 0f, 0f, 0.3f);
            Gizmos.DrawLine(lookAtPoint, cameraPosition);
        }
    }
}
