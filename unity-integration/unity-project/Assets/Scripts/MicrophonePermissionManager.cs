using UnityEngine;
using System.Collections;

/// <summary>
/// Gestor de permisos de micrófono y captura de audio
/// Solicita permiso, captura audio del micrófono y calcula nivel de ruido en tiempo real
/// </summary>
public class MicrophonePermissionManager : MonoBehaviour
{
    public static MicrophonePermissionManager Instance { get; private set; }

    [Header("Audio Settings")]
    [SerializeField] private int sampleRate = 44100;
    [SerializeField] private int audioClipDuration = 10; // 10 segundos
    [SerializeField] private int analysisFrequency = 10; // Analizar cada X frames

    private AudioClip audioClip;
    private bool isRecording = false;
    private float currentAudioLevel = 0f;
    private int analysisCounter = 0;

    private DebugUIManager debugUI;

    private void Awake()
    {
        if (Instance != null && Instance != this)
        {
            Destroy(gameObject);
            return;
        }
        Instance = this;
        DontDestroyOnLoad(gameObject);
    }

    private void Start()
    {
        debugUI = DebugUIManager.Instance;
        if (!debugUI) debugUI = FindObjectOfType<DebugUIManager>();

        debugUI?.Log("info", "MICROPHONE", "MicrophonePermissionManager initialized");

        // Verificar si ya hay permisos
        CheckMicrophonePermission();
    }

    private void Update()
    {
        if (isRecording)
        {
            analysisCounter++;
            if (analysisCounter >= analysisFrequency)
            {
                analysisCounter = 0;
                UpdateAudioLevel();
            }
        }
    }

    /// <summary>
    /// Verifica si ya hay permisos de micrófono
    /// </summary>
    private void CheckMicrophonePermission()
    {
        #if UNITY_WEBGL && !UNITY_EDITOR
        debugUI?.Log("info", "MICROPHONE", "WebGL detected - permissions handled by browser");
        #else
        #if !UNITY_WEBGL
        if (Microphone.devices.Length > 0)
        {
            debugUI?.Log("info", "MICROPHONE", $"Microphone device found: {Microphone.devices[0]}");
        }
        else
        {
            debugUI?.Log("warning", "MICROPHONE", "No microphone devices detected");
        }
        #endif
        #endif
    }

    /// <summary>
    /// Solicita permiso de micrófono (puede ser llamado desde botón HTML)
    /// </summary>
    public void RequestMicrophonePermission()
    {
        debugUI?.Log("info", "MICROPHONE", "Requesting microphone permission...");

        #if UNITY_WEBGL && !UNITY_EDITOR
        // En WebGL, el permiso se solicita cuando se inicia la captura
        StartAudioCapture();
        #else
        // En otras plataformas, usar Permissions API
        StartCoroutine(RequestPermissionCoroutine());
        #endif
    }

    /// <summary>
    /// Corrutina para solicitar permisos en plataformas que lo requieren
    /// </summary>
    private IEnumerator RequestPermissionCoroutine()
    {
        #if !UNITY_WEBGL || UNITY_EDITOR
        if (Application.platform != RuntimePlatform.WebGLPlayer)
        {
            debugUI?.Log("info", "MICROPHONE", "Requesting microphone permission...");
            yield return Application.RequestUserAuthorization(UserAuthorization.Microphone);
            
            if (Application.HasUserAuthorization(UserAuthorization.Microphone))
            {
                debugUI?.UpdateMicrophoneStatus(true, "Granted");
                StartAudioCapture();
            }
            else
            {
                debugUI?.UpdateMicrophoneStatus(false, "User denied");
                debugUI?.Log("warning", "MICROPHONE", "Microphone permission denied by user");
            }
        }
        else
        #endif
        {
            // En WebGL, el navegador pide permisos automáticamente al acceder al micrófono
            debugUI?.Log("info", "MICROPHONE", "WebGL - Browser will request permission");
            debugUI?.UpdateMicrophoneStatus(true, "WebGL auto");
            StartAudioCapture();
        }

        yield return null;
    }

    /// <summary>
    /// Inicia la captura de audio del micrófono
    /// </summary>
    public void StartAudioCapture()
    {
        if (isRecording)
        {
            debugUI?.Log("warning", "MICROPHONE", "Already recording");
            return;
        }

        #if UNITY_WEBGL && !UNITY_EDITOR
        // En WebGL, el audio se captura vía JavaScript/getUserMedia
        debugUI?.Log("info", "MICROPHONE", "WebGL - Audio capture handled by JavaScript");
        isRecording = true;
        debugUI?.SetIsRecording(true);
        #else
        #if !UNITY_WEBGL
        try
        {
            if (Microphone.devices.Length == 0)
            {
                debugUI?.Log("error", "MICROPHONE", "No microphone devices available");
                return;
            }

            string deviceName = Microphone.devices[0];
            debugUI?.Log("info", "MICROPHONE", $"Starting capture from device: {deviceName}");

            // Crear clip de audio para captura
            audioClip = Microphone.Start(deviceName, true, audioClipDuration, sampleRate);
            
            // Esperar un poco a que el micrófono inicie
            StartCoroutine(WaitForMicrophoneStart());
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "MICROPHONE", $"Error starting audio capture: {e.Message}");
        }
        #endif
        #endif
    }

    /// <summary>
    /// Espera a que el micrófono esté completamente inicializado
    /// </summary>
    private IEnumerator WaitForMicrophoneStart()
    {
        #if !UNITY_WEBGL
        yield return new WaitUntil(() => Microphone.GetPosition(Microphone.devices[0]) > 0);
        
        isRecording = true;
        debugUI?.SetIsRecording(true);
        debugUI?.UpdateMicrophoneStatus(true, "Recording");
        debugUI?.Log("info", "MICROPHONE", "Audio capture started successfully");
        #else
        yield return null;
        #endif
    }

    /// <summary>
    /// Detiene la captura de audio
    /// </summary>
    public void StopAudioCapture()
    {
        if (!isRecording)
        {
            debugUI?.Log("warning", "MICROPHONE", "Not currently recording");
            return;
        }

        #if !UNITY_WEBGL
        try
        {
            if (Microphone.devices.Length > 0)
            {
                Microphone.End(Microphone.devices[0]);
            }

            if (audioClip != null)
            {
                Destroy(audioClip);
                audioClip = null;
            }

            isRecording = false;
            currentAudioLevel = 0f;
            debugUI?.SetIsRecording(false);
            debugUI?.UpdateAudioLevel(0f);
            debugUI?.Log("info", "MICROPHONE", "Audio capture stopped");
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "MICROPHONE", $"Error stopping audio capture: {e.Message}");
        }
        #else
        isRecording = false;
        debugUI?.SetIsRecording(false);
        debugUI?.Log("info", "MICROPHONE", "Audio capture stopped (WebGL)");
        #endif
    }

    /// <summary>
    /// Calcula el nivel de audio actual (0-1)
    /// </summary>
    private void UpdateAudioLevel()
    {
        if (audioClip == null || !isRecording) return;

        #if !UNITY_WEBGL
        try
        {
            int devicePosition = Microphone.GetPosition(Microphone.devices[0]);
            
            if (devicePosition <= 0) return;

            // Obtener muestras de audio
            int sampleCount = Mathf.Min(1024, audioClip.samples); // Tomar últimas 1024 muestras
            float[] samples = new float[sampleCount];
            audioClip.GetData(samples, Mathf.Max(0, devicePosition - sampleCount));

            // Calcular RMS (Root Mean Square) del audio
            float sum = 0f;
            foreach (float sample in samples)
            {
                sum += sample * sample;
            }

            currentAudioLevel = Mathf.Sqrt(sum / sampleCount);
            currentAudioLevel = Mathf.Clamp01(currentAudioLevel);

            // Enviar al HTML
            debugUI?.UpdateAudioLevel(currentAudioLevel);
        }
        catch (System.Exception e)
        {
            debugUI?.Log("warning", "MICROPHONE", $"Error analyzing audio: {e.Message}");
        }
        #endif
    }

    /// <summary>
    /// Obtiene el nivel de audio actual
    /// </summary>
    public float GetCurrentAudioLevel()
    {
        return currentAudioLevel;
    }

    /// <summary>
    /// Obtiene el clip de audio actual
    /// </summary>
    public AudioClip GetAudioClip()
    {
        return audioClip;
    }

    /// <summary>
    /// Verifica si está grabando
    /// </summary>
    public bool IsRecording()
    {
        return isRecording;
    }
}
