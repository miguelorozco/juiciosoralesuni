using UnityEngine;
using Photon.Pun;

/// <summary>
/// Maneja los efectos de sonido del jugador (footsteps, landing, etc.)
/// </summary>
[RequireComponent(typeof(AudioSource))]
public class PlayerAudioController : MonoBehaviourPun
{
    [Header("Audio Clips")]
    [Tooltip("Sonido de aterrizaje")]
    public AudioClip landingAudioClip;
    
    [Tooltip("Sonidos de pasos (se reproducen aleatoriamente)")]
    public AudioClip[] footstepAudioClips;

    [Header("Audio Settings")]
    [Tooltip("Volumen de los pasos (0-1)")]
    [Range(0f, 1f)]
    public float footstepVolume = 0.5f;
    
    [Tooltip("Volumen del aterrizaje (0-1)")]
    [Range(0f, 1f)]
    public float landingVolume = 0.8f;
    
    [Tooltip("Intervalo mínimo entre pasos (segundos)")]
    public float footstepInterval = 0.5f;

    [Header("Auto-Load from Resources")]
    [Tooltip("Cargar automáticamente los clips desde Resources/sounds")]
    public bool autoLoadFromResources = true;

    private AudioSource audioSource;
    private CharacterController characterController;
    private float footstepTimer;
    private bool wasGrounded = true;

    void Awake()
    {
        audioSource = GetComponent<AudioSource>();
        if (audioSource == null)
        {
            audioSource = gameObject.AddComponent<AudioSource>();
        }
        
        // Configurar AudioSource
        audioSource.spatialBlend = 1f; // 3D sound
        audioSource.minDistance = 1f;
        audioSource.maxDistance = 15f;
        audioSource.rolloffMode = AudioRolloffMode.Linear;
        audioSource.playOnAwake = false;

        characterController = GetComponent<CharacterController>();
    }

    void Start()
    {
        // Solo cargar y reproducir audio si es el jugador local o si está cerca
        if (autoLoadFromResources && (photonView == null || photonView.IsMine))
        {
            LoadAudioClipsFromResources();
        }
    }

    void Update()
    {
        if (photonView != null && !photonView.IsMine)
        {
            // Para jugadores remotos, solo reproducir si están cerca (optimización)
            return;
        }

        if (characterController == null) return;

        // Detectar aterrizaje
        bool isGrounded = characterController.isGrounded;
        if (isGrounded && !wasGrounded)
        {
            PlayLandingSound();
        }
        wasGrounded = isGrounded;

        // Reproducir footsteps mientras se mueve
        if (isGrounded && characterController.velocity.magnitude > 0.1f)
        {
            footstepTimer -= Time.deltaTime;
            if (footstepTimer <= 0f)
            {
                PlayFootstepSound();
                footstepTimer = footstepInterval;
            }
        }
    }

    /// <summary>
    /// Carga automáticamente los clips de audio desde Resources/sounds
    /// </summary>
    private void LoadAudioClipsFromResources()
    {
        try
        {
            // Cargar landing sound
            if (landingAudioClip == null)
            {
                landingAudioClip = Resources.Load<AudioClip>("sounds/Player_Land");
                if (landingAudioClip != null)
                {
                    Debug.Log($"[PlayerAudioController] ✅ Landing sound cargado: {landingAudioClip.name}");
                }
                else
                {
                    Debug.LogWarning("[PlayerAudioController] ⚠️ No se encontró 'sounds/Player_Land' en Resources");
                }
            }

            // Cargar footstep sounds
            if (footstepAudioClips == null || footstepAudioClips.Length == 0)
            {
                var clips = new System.Collections.Generic.List<AudioClip>();
                for (int i = 1; i <= 10; i++)
                {
                    string clipName = $"sounds/Player_Footstep_{i:00}";
                    AudioClip clip = Resources.Load<AudioClip>(clipName);
                    if (clip != null)
                    {
                        clips.Add(clip);
                    }
                }
                
                if (clips.Count > 0)
                {
                    footstepAudioClips = clips.ToArray();
                    Debug.Log($"[PlayerAudioController] ✅ {clips.Count} footstep sounds cargados");
                }
                else
                {
                    Debug.LogWarning("[PlayerAudioController] ⚠️ No se encontraron footstep sounds en Resources/sounds");
                }
            }
        }
        catch (System.Exception e)
        {
            Debug.LogError($"[PlayerAudioController] ❌ Error cargando audio clips: {e.Message}");
        }
    }

    /// <summary>
    /// Reproduce un sonido de paso aleatorio
    /// </summary>
    public void PlayFootstepSound()
    {
        if (footstepAudioClips == null || footstepAudioClips.Length == 0)
        {
            return;
        }

        if (audioSource == null)
        {
            Debug.LogWarning("[PlayerAudioController] AudioSource no disponible");
            return;
        }

        // Seleccionar clip aleatorio
        AudioClip clip = footstepAudioClips[UnityEngine.Random.Range(0, footstepAudioClips.Length)];
        audioSource.PlayOneShot(clip, footstepVolume);
    }

    /// <summary>
    /// Reproduce el sonido de aterrizaje
    /// </summary>
    public void PlayLandingSound()
    {
        if (landingAudioClip == null || audioSource == null)
        {
            return;
        }

        audioSource.PlayOneShot(landingAudioClip, landingVolume);
        Debug.Log("[PlayerAudioController] 🔊 Landing sound reproducido");
    }

    /// <summary>
    /// Reproduce un sonido personalizado
    /// </summary>
    public void PlaySound(AudioClip clip, float volume = 1f)
    {
        if (clip != null && audioSource != null)
        {
            audioSource.PlayOneShot(clip, volume);
        }
    }

    void OnDrawGizmosSelected()
    {
        // Visualizar rango de audio en el editor
        Gizmos.color = new Color(0f, 1f, 0f, 0.3f);
        Gizmos.DrawWireSphere(transform.position, 15f); // maxDistance
        
        Gizmos.color = new Color(1f, 1f, 0f, 0.5f);
        Gizmos.DrawWireSphere(transform.position, 1f); // minDistance
    }
}
