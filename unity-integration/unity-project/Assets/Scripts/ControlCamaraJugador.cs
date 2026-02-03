using Photon.Pun;
using UnityEngine;

public class ControlCamaraJugador : MonoBehaviourPun
{
    [Header("Third Person Camera Settings")]
    [Tooltip("Distancia detrás del avatar (estándar industria: 3-4m)")]
    public float cameraDistance = 3.5f;
    
    [Tooltip("Altura sobre el avatar (estándar industria: 1.5-2m)")]
    public float cameraHeight = 1.7f;
    
    [Tooltip("Offset lateral (0 = centrado, positivo = derecha)")]
    public float cameraLateralOffset = 0.5f;

    private Camera camara;
    private AudioListener audioListener;
    private Transform cameraTransform;

    void Awake()
    {
        // Busca la cámara hija automáticamente
        camara = GetComponentInChildren<Camera>();
        audioListener = camara != null ? camara.GetComponent<AudioListener>() : null;

        // CRÍTICO: Desactivar TODAS las cámaras inicialmente
        // Solo se activará la del jugador local después de confirmar ownership
        if (camara != null)
        {
            camara.enabled = false;
        }
        if (audioListener != null)
        {
            audioListener.enabled = false;
        }
    }

    void Start()
    {
        // NO hacer nada aquí todavía, esperar a que se confirme el ownership
        // Esto evita destruir cámaras antes de saber quién es el jugador local
    }

    /// <summary>
    /// Llamado externamente después de confirmar el ownership
    /// </summary>
    public void InitializeCamera()
    {
        if (camara == null)
        {
            Debug.LogError($"[ControlCamaraJugador] No se encontró cámara en {gameObject.name}");
            return;
        }

        if (photonView != null && photonView.IsMine)
        {
            // Solo activar la cámara del jugador local
            camara.enabled = true;
            camara.depth = 0;
            
            if (audioListener != null)
            {
                audioListener.enabled = true;
            }

            // Configurar posición de tercera persona
            SetupThirdPersonCamera();

            // AHORA SÍ, destruir todas las demás cámaras
            DestroyOtherCameras();
            
            // Deshabilitar todos los demás AudioListeners
            DisableOtherAudioListeners(audioListener);
            
            Debug.Log($"[ControlCamaraJugador] Cámara en TERCERA PERSONA ACTIVADA para jugador local: {gameObject.name}");
        }
        else
        {
            // Para jugadores remotos: eliminar componente Camera si es necesario (no destruir el GameObject raíz)
            if (camara != null)
            {
                Debug.Log($"[ControlCamaraJugador] Eliminando componente Camera de jugador remoto: {gameObject.name}");
                Destroy(camara);
                camara = null;
            }
            if (audioListener != null)
            {
                Destroy(audioListener);
                audioListener = null;
            }
        }
    }

    void LateUpdate()
    {
        // Actualizar posición de cámara en tercera persona
        if (photonView.IsMine && camara != null && camara.enabled && cameraTransform != null)
        {
            UpdateThirdPersonCamera();
        }
    }

    /// <summary>
    /// Configura la cámara en posición de tercera persona detrás del avatar
    /// Posición estándar de la industria: 3.5m atrás, 1.7m arriba
    /// </summary>
    private void SetupThirdPersonCamera()
    {
        if (camara == null) return;

        cameraTransform = camara.transform;
        
        // Posición inicial de tercera persona
        Vector3 offset = new Vector3(cameraLateralOffset, cameraHeight, -cameraDistance);
        cameraTransform.localPosition = offset;
        
        // Mirar ligeramente hacia abajo hacia el personaje
        Vector3 lookAtPoint = transform.position + Vector3.up * 1.6f; // Mirar a la altura de la cabeza
        cameraTransform.LookAt(lookAtPoint);

        Debug.Log($"[ControlCamaraJugador] Cámara configurada en tercera persona: offset={offset}");
    }

    /// <summary>
    /// Actualiza la posición de la cámara en tercera persona cada frame
    /// </summary>
    private void UpdateThirdPersonCamera()
    {
        // Calcular posición deseada detrás del personaje
        Vector3 offset = new Vector3(cameraLateralOffset, cameraHeight, -cameraDistance);
        Vector3 desiredPosition = transform.position + transform.rotation * offset;
        
        // Suavizar movimiento
        cameraTransform.position = Vector3.Lerp(cameraTransform.position, desiredPosition, Time.deltaTime * 8f);
        
        // Mirar al personaje (altura de cabeza)
        Vector3 lookAtPoint = transform.position + Vector3.up * 1.6f;
        cameraTransform.LookAt(lookAtPoint);
    }

    /// <summary>
    /// Desactiva la Main Camera de la escena si existe
    /// </summary>
    private void DisableMainCamera()
    {
        Camera mainCam = Camera.main;
        if (mainCam != null && mainCam != camara)
        {
            mainCam.enabled = false;
            AudioListener mainListener = mainCam.GetComponent<AudioListener>();
            if (mainListener != null)
            {
                mainListener.enabled = false;
            }
            Debug.Log("[ControlCamaraJugador] Main Camera desactivada");
        }
    }

    /// <summary>
    /// DESTRUYE todas las demás cámaras en la escena EXCEPTO LA PROPIA
    /// </summary>
    private void DestroyOtherCameras()
    {
        Camera[] allCameras = FindObjectsOfType<Camera>(true); // Incluir inactivas

        int destroyed = 0;
        foreach (Camera cam in allCameras)
        {
            // NO destruir la cámara propia, solo las demás
            if (cam != null && cam != camara)
            {
                // Si la cámara está en el GameObject raíz (ej. la cámara está en el root del jugador),
                // eliminar solo el componente Camera para no destruir el GameObject entero.
                if (cam.transform != null && cam.transform.root == cam.transform)
                {
                    Debug.Log($"[ControlCamaraJugador] Eliminando componente Camera en root objeto: {cam.gameObject.name}");
                    Destroy(cam);
                }
                else
                {
                    Debug.Log($"[ControlCamaraJugador] DESTRUYENDO cámara GameObject: {cam.gameObject.name} (parent: {cam.transform.parent?.name})");
                    Destroy(cam.gameObject);
                }
                destroyed++;
            }
        }

        Debug.Log($"[ControlCamaraJugador] Total cámaras procesadas: {destroyed}. Cámara propia PRESERVADA: {camara?.gameObject.name}");
    }

    /// <summary>
    /// Deshabilita todos los AudioListeners excepto el proporcionado.
    /// </summary>
    private void DisableOtherAudioListeners(AudioListener keepActive)
    {
        AudioListener[] allListeners = FindObjectsOfType<AudioListener>();
        
        foreach (AudioListener listener in allListeners)
        {
            if (listener != keepActive && listener.enabled)
            {
                listener.enabled = false;
                Debug.Log($"[ControlCamaraJugador] AudioListener deshabilitado en: {listener.gameObject.name}");
            }
        }
    }
}
