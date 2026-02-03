using UnityEngine;
using System.Collections;
using System.Collections.Generic;

/// <summary>
/// Bridge entre Unity y PeerJS para comunicación P2P de audio
/// Se comunica con el código JavaScript en index.html para manejar conexiones PeerJS
/// </summary>
public class PeerJSBridge : MonoBehaviour
{
    public static PeerJSBridge Instance { get; private set; }

    [Header("PeerJS Configuration")]
    [SerializeField] private string peerHost = "localhost";
    [SerializeField] private int peerPort = 9000;
    [SerializeField] private bool useSecure = false;
    [SerializeField] private string peerPath = "/peerjs";

    private string peerId;
    private string roomId;
    private Dictionary<string, bool> connectedPeers = new Dictionary<string, bool>();
    private DebugUIManager debugUI;
    private MicrophonePermissionManager micManager;

    private bool isInitialized = false;
    private float reconnectAttempts = 0;
    private const float MAX_RECONNECT_ATTEMPTS = 5;
    private const float RECONNECT_DELAY = 2f;

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

        micManager = MicrophonePermissionManager.Instance;
        if (!micManager) micManager = FindObjectOfType<MicrophonePermissionManager>();

        debugUI?.Log("info", "PEERJS", "PeerJSBridge initialized");
    }

    /// <summary>
    /// Inicializa la conexión PeerJS desde Unity
    /// Llamado desde GameInitializer cuando tenemos el room ID y actor ID
    /// </summary>
    public void Initialize(string roomId, int actorId)
    {
        this.roomId = roomId;
        this.peerId = $"{roomId}_{actorId}";

        debugUI?.LogPhase("PEERJS_INIT", $"Initializing with room={roomId}, actorId={actorId}");

        if (Application.platform == RuntimePlatform.WebGLPlayer)
        {
            InitializeWebGL();
        }
        else
        {
            debugUI?.Log("warning", "PEERJS", "PeerJS bridge only works in WebGL");
        }
    }

    /// <summary>
    /// Inicialización en WebGL - llama a JavaScript en index.html
    /// </summary>
    private void InitializeWebGL()
    {
        try
        {
            // Construir configuración JSON
            string config = BuildPeerJSConfig();
            
            debugUI?.Log("info", "PEERJS", $"Calling JavaScript: initVoiceCallFromUnity('{roomId}', {int.Parse(peerId.Split('_')[1])}, config)");

            // Llamar a la función JavaScript en index.html
            Application.ExternalEval($@"
                if (window.initVoiceCallFromUnity) {{
                    window.initVoiceCallFromUnity('{roomId}', {int.Parse(peerId.Split('_')[1])}, '{config}');
                }} else {{
                    console.error('initVoiceCallFromUnity not found in window');
                }}
            ");

            isInitialized = true;
            debugUI?.LogPhase("PEERJS_INIT", "WebGL initialization started");
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "PEERJS", $"Error initializing WebGL: {e.Message}");
        }
    }

    /// <summary>
    /// Construye la configuración JSON para PeerJS
    /// </summary>
    private string BuildPeerJSConfig()
    {
        var config = new
        {
            host = peerHost,
            port = peerPort,
            secure = useSecure,
            path = peerPath,
            audio = new
            {
                echoCancellation = true,
                noiseSuppression = true,
                autoGainControl = true,
                sampleRate = 44100,
                channelCount = 1,
                latency = 0.01f
            }
        };

        return JsonUtility.ToJson(config);
    }

    /// <summary>
    /// Callback desde JavaScript cuando PeerJS está listo
    /// Llamado por: window.unityInstance.SendMessage('PeerJSBridge', 'OnPeerJSReady', peerId)
    /// </summary>
    public void OnPeerJSReady(string id)
    {
        debugUI?.Log("info", "PEERJS", $"PeerJS ready with ID: {id}");
        debugUI?.UpdatePeerJSStatus(true);
        debugUI?.LogPhase("PEERJS_CONNECT", "Connected to PeerJS server");
        reconnectAttempts = 0;
    }

    /// <summary>
    /// Callback desde JavaScript cuando hay un error en PeerJS
    /// </summary>
    public void OnPeerJSError(string errorMessage)
    {
        debugUI?.Log("error", "PEERJS", $"PeerJS error: {errorMessage}");
        debugUI?.UpdatePeerJSStatus(false);
        
        // Intentar reconectar
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS)
        {
            reconnectAttempts++;
            debugUI?.Log("warning", "PEERJS", $"Reconnect attempt {reconnectAttempts}/{MAX_RECONNECT_ATTEMPTS}");
            StartCoroutine(ReconnectPeerJS());
        }
        else
        {
            debugUI?.Log("error", "PEERJS", "Max reconnection attempts reached");
        }
    }

    /// <summary>
    /// Callback desde JavaScript cuando se conecta a otro peer
    /// </summary>
    public void OnPeerConnected(string peerId)
    {
        if (!connectedPeers.ContainsKey(peerId))
        {
            connectedPeers[peerId] = true;
            debugUI?.Log("info", "PEERJS", $"Connected to peer: {peerId}");
            debugUI?.LogEvent("PEER_CONNECTED", $"Connection established with {peerId}");
        }
    }

    /// <summary>
    /// Callback desde JavaScript cuando se desconecta de otro peer
    /// </summary>
    public void OnPeerDisconnected(string peerId)
    {
        if (connectedPeers.ContainsKey(peerId))
        {
            connectedPeers.Remove(peerId);
            debugUI?.Log("info", "PEERJS", $"Disconnected from peer: {peerId}");
            debugUI?.LogEvent("PEER_DISCONNECTED", $"Disconnected from {peerId}");
        }
    }

    /// <summary>
    /// Conecta a un peer específico
    /// </summary>
    public void ConnectToPeer(string targetPeerId)
    {
        if (!isInitialized)
        {
            debugUI?.Log("warning", "PEERJS", "PeerJS not initialized yet");
            return;
        }

        debugUI?.Log("info", "PEERJS", $"Connecting to peer: {targetPeerId}");

        try
        {
            Application.ExternalEval($@"
                if (window.callPeerFromUnity) {{
                    window.callPeerFromUnity('{targetPeerId}');
                }} else {{
                    console.error('callPeerFromUnity not found');
                }}
            ");
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "PEERJS", $"Error connecting to peer: {e.Message}");
        }
    }

    /// <summary>
    /// Desconecta de un peer específico
    /// </summary>
    public void DisconnectFromPeer(string targetPeerId)
    {
        if (!isInitialized) return;

        debugUI?.Log("info", "PEERJS", $"Disconnecting from peer: {targetPeerId}");

        try
        {
            Application.ExternalEval($@"
                if (window.cleanupPeer) {{
                    window.cleanupPeer('{targetPeerId}');
                }} else {{
                    console.error('cleanupPeer not found');
                }}
            ");

            OnPeerDisconnected(targetPeerId);
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "PEERJS", $"Error disconnecting from peer: {e.Message}");
        }
    }

    /// <summary>
    /// Obtiene la lista de peers conectados
    /// </summary>
    public List<string> GetConnectedPeers()
    {
        return new List<string>(connectedPeers.Keys);
    }

    /// <summary>
    /// Obtiene el número de peers conectados
    /// </summary>
    public int GetConnectedPeersCount()
    {
        return connectedPeers.Count;
    }

    /// <summary>
    /// Cierra todas las conexiones PeerJS
    /// </summary>
    public void Close()
    {
        debugUI?.Log("info", "PEERJS", "Closing all PeerJS connections");

        try
        {
            Application.ExternalEval(@"
                if (window.cleanupAllPeers) {
                    window.cleanupAllPeers();
                } else {
                    console.error('cleanupAllPeers not found');
                }
            ");
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "PEERJS", $"Error closing PeerJS: {e.Message}");
        }

        connectedPeers.Clear();
        isInitialized = false;
    }

    /// <summary>
    /// Reconecta a PeerJS después de un error
    /// </summary>
    private IEnumerator ReconnectPeerJS()
    {
        yield return new WaitForSeconds(RECONNECT_DELAY);
        debugUI?.Log("info", "PEERJS", "Attempting to reconnect...");
        Initialize(roomId, int.Parse(peerId.Split('_')[1]));
    }

    /// <summary>
    /// Usa servidor PeerJS local para testing
    /// Llamado desde botón en HTML
    /// </summary>
    public void UseLocalPeerServer()
    {
        debugUI?.Log("info", "PEERJS", "Switching to local PeerJS server");
        peerHost = "localhost";
        peerPort = 9000;
        useSecure = false;

        try
        {
            Application.ExternalEval("if (window.connectToLocalPeerServer) window.connectToLocalPeerServer();");
        }
        catch (System.Exception e)
        {
            debugUI?.Log("error", "PEERJS", $"Error switching to local server: {e.Message}");
        }
    }

    /// <summary>
    /// Obtiene el estado de inicialización
    /// </summary>
    public bool IsInitialized()
    {
        return isInitialized;
    }

    /// <summary>
    /// Obtiene el peer ID
    /// </summary>
    public string GetPeerId()
    {
        return peerId;
    }

    /// <summary>
    /// Obtiene el room ID
    /// </summary>
    public string GetRoomId()
    {
        return roomId;
    }
}
