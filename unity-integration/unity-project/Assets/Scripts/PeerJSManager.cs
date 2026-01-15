using UnityEngine;
using Photon.Pun;
using Photon.Realtime;
using System.Collections;
using System.Collections.Generic;

/// <summary>
/// Gestor de PeerJS para comunicación de voz en tiempo real
/// Se puede agregar directamente a la escena de Unity
/// </summary>
[RequireComponent(typeof(PhotonView))]
public class PeerJSManager : MonoBehaviourPunCallbacks
{
    [Header("Configuración de PeerJS")]
    [Tooltip("Servidor PeerJS principal")]
    public string peerjsHost = "juiciosorales.site";
    
    [Tooltip("Puerto del servidor PeerJS")]
    public int peerjsPort = 443;
    
    [Tooltip("Usar conexión segura (HTTPS)")]
    public bool peerjsSecure = true;
    
    [Tooltip("Path del servidor PeerJS")]
    public string peerjsPath = "/peerjs";
    
    [Header("Configuración de Audio")]
    [Tooltip("Echo cancellation")]
    public bool echoCancellation = true;
    
    [Tooltip("Noise suppression")]
    public bool noiseSuppression = true;
    
    [Tooltip("Auto gain control")]
    public bool autoGainControl = true;
    
    [Tooltip("Sample rate del audio")]
    public int sampleRate = 44100;
    
    [Tooltip("Channel count (1 = mono, 2 = stereo)")]
    public int channelCount = 1;
    
    [Tooltip("Latencia del audio en segundos")]
    public float audioLatency = 0.01f;
    
    [Header("Configuración de Sala")]
    [Tooltip("ID de la sala (se obtiene automáticamente de Photon)")]
    public string roomId = "";
    
    [Tooltip("ID del actor (se obtiene automáticamente de Photon)")]
    public string actorId = "";
    
    [Header("Estado")]
    [SerializeField] private bool isInitialized = false;
    [SerializeField] private string myPeerId = "";
    [SerializeField] private List<string> connectedPeers = new List<string>();
    
    private PhotonView photonView;
    
    // Eventos
    public static event System.Action<string> OnPeerJSReady;
    public static event System.Action<string> OnPeerConnected;
    public static event System.Action<string> OnPeerDisconnected;
    public static event System.Action<string> OnPeerJSError;
    
    void Awake()
    {
        photonView = GetComponent<PhotonView>();
        
        // Si no hay PhotonView, agregarlo
        if (photonView == null)
        {
            photonView = gameObject.AddComponent<PhotonView>();
        }
    }
    
    void Start()
    {
        // Esperar a que Photon esté conectado antes de inicializar PeerJS
        if (PhotonNetwork.IsConnected)
        {
            InitializePeerJS();
        }
        else
        {
            // Suscribirse al evento de conexión
            PhotonNetwork.AddCallbackTarget(this);
        }
    }
    
    void OnDestroy()
    {
        // Limpiar conexiones PeerJS
        CleanupPeerJS();
        
        // Desuscribirse de eventos
        PhotonNetwork.RemoveCallbackTarget(this);
    }
    
    public override void OnConnectedToMaster()
    {
        if (!isInitialized)
        {
            InitializePeerJS();
        }
    }
    
    public override void OnJoinedRoom()
    {
        // Obtener información de la sala
        roomId = PhotonNetwork.CurrentRoom.Name;
        actorId = PhotonNetwork.LocalPlayer.ActorNumber.ToString();
        
        Debug.Log($"[PeerJSManager] Unido a sala: {roomId}, Actor ID: {actorId}");
        
        // Reinicializar PeerJS con la nueva información de sala
        if (isInitialized)
        {
            ReinitializePeerJS();
        }
        else
        {
            InitializePeerJS();
        }
    }
    
    /// <summary>
    /// Inicializa PeerJS desde Unity
    /// </summary>
    public void InitializePeerJS()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        if (isInitialized)
        {
            Debug.LogWarning("[PeerJSManager] PeerJS ya está inicializado");
            return;
        }
        
        // Obtener información de la sala si está disponible
        if (PhotonNetwork.InRoom)
        {
            roomId = PhotonNetwork.CurrentRoom.Name;
            actorId = PhotonNetwork.LocalPlayer.ActorNumber.ToString();
        }
        
        // Construir configuración de PeerJS
        string configJson = BuildPeerJSConfig();
        
        Debug.Log($"[PeerJSManager] Inicializando PeerJS con configuración: {configJson}");
        
        // Llamar a JavaScript para inicializar PeerJS
        Application.ExternalCall("initVoiceCallFromUnity", roomId, actorId, configJson);
        
        isInitialized = true;
#else
        Debug.LogWarning("[PeerJSManager] PeerJS solo funciona en builds WebGL");
#endif
    }
    
    /// <summary>
    /// Reinicializa PeerJS (útil cuando cambias de sala)
    /// </summary>
    public void ReinitializePeerJS()
    {
        CleanupPeerJS();
        
        // Esperar un frame antes de reinicializar
        StartCoroutine(DelayedReinitialize());
    }
    
    private IEnumerator DelayedReinitialize()
    {
        yield return new WaitForSeconds(0.5f);
        isInitialized = false;
        InitializePeerJS();
    }
    
    /// <summary>
    /// Construye la configuración JSON para PeerJS
    /// </summary>
    private string BuildPeerJSConfig()
    {
        // Construir objeto de configuración
        System.Text.StringBuilder sb = new System.Text.StringBuilder();
        sb.Append("{");
        sb.Append($"\"host\":\"{peerjsHost}\",");
        sb.Append($"\"port\":{peerjsPort},");
        sb.Append($"\"secure\":{peerjsSecure.ToString().ToLower()},");
        sb.Append($"\"path\":\"{peerjsPath}\",");
        sb.Append($"\"audio\":{{");
        sb.Append($"\"echoCancellation\":{echoCancellation.ToString().ToLower()},");
        sb.Append($"\"noiseSuppression\":{noiseSuppression.ToString().ToLower()},");
        sb.Append($"\"autoGainControl\":{autoGainControl.ToString().ToLower()},");
        sb.Append($"\"sampleRate\":{sampleRate},");
        sb.Append($"\"channelCount\":{channelCount},");
        sb.Append($"\"latency\":{audioLatency}");
        sb.Append("}");
        sb.Append("}");
        
        return sb.ToString();
    }
    
    /// <summary>
    /// Llamado desde JavaScript cuando PeerJS está listo
    /// </summary>
    public void OnPeerJSReadyCallback(string peerId)
    {
        myPeerId = peerId;
        isInitialized = true;
        
        Debug.Log($"[PeerJSManager] ✅ PeerJS listo con ID: {peerId}");
        
        // Notificar a otros jugadores vía Photon RPC
        if (photonView != null && photonView.IsMine)
        {
            photonView.RPC("ReceivePeerId", RpcTarget.Others, peerId);
        }
        
        // Disparar evento
        OnPeerJSReady?.Invoke(peerId);
    }
    
    /// <summary>
    /// RPC para recibir PeerID de otros jugadores
    /// </summary>
    [PunRPC]
    public void ReceivePeerId(string peerId, PhotonMessageInfo info)
    {
        Debug.Log($"[PeerJSManager] 📞 Recibido PeerID de {info.Sender.NickName}: {peerId}");
        
        // Conectar con este peer
        ConnectToPeer(peerId);
    }
    
    /// <summary>
    /// Conecta con un peer específico
    /// </summary>
    public void ConnectToPeer(string peerId)
    {
        if (string.IsNullOrEmpty(peerId) || peerId == myPeerId)
        {
            return;
        }
        
        if (connectedPeers.Contains(peerId))
        {
            Debug.Log($"[PeerJSManager] Ya conectado con peer: {peerId}");
            return;
        }
        
#if UNITY_WEBGL && !UNITY_EDITOR
        Debug.Log($"[PeerJSManager] 🔊 Conectando con peer: {peerId}");
        Application.ExternalCall("callPeer", peerId);
        connectedPeers.Add(peerId);
        
        OnPeerConnected?.Invoke(peerId);
#else
        Debug.LogWarning("[PeerJSManager] ConnectToPeer solo funciona en WebGL");
#endif
    }
    
    /// <summary>
    /// Desconecta de un peer específico
    /// </summary>
    public void DisconnectFromPeer(string peerId)
    {
        if (!connectedPeers.Contains(peerId))
        {
            return;
        }
        
#if UNITY_WEBGL && !UNITY_EDITOR
        Debug.Log($"[PeerJSManager] ❌ Desconectando de peer: {peerId}");
        Application.ExternalCall("cleanupPeer", peerId);
        connectedPeers.Remove(peerId);
        
        OnPeerDisconnected?.Invoke(peerId);
#else
        Debug.LogWarning("[PeerJSManager] DisconnectFromPeer solo funciona en WebGL");
#endif
    }
    
    /// <summary>
    /// Llamado desde JavaScript cuando un peer se conecta
    /// </summary>
    public void OnPeerConnectedCallback(string peerId)
    {
        if (!connectedPeers.Contains(peerId))
        {
            connectedPeers.Add(peerId);
            OnPeerConnected?.Invoke(peerId);
        }
    }
    
    /// <summary>
    /// Llamado desde JavaScript cuando un peer se desconecta
    /// </summary>
    public void OnPeerDisconnectedCallback(string peerId)
    {
        if (connectedPeers.Contains(peerId))
        {
            connectedPeers.Remove(peerId);
            OnPeerDisconnected?.Invoke(peerId);
        }
    }
    
    /// <summary>
    /// Llamado desde JavaScript cuando hay un error
    /// </summary>
    public void OnPeerJSErrorCallback(string error)
    {
        Debug.LogError($"[PeerJSManager] 🚨 Error de PeerJS: {error}");
        OnPeerJSError?.Invoke(error);
    }
    
    /// <summary>
    /// Limpia todas las conexiones PeerJS
    /// </summary>
    public void CleanupPeerJS()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        // Desconectar de todos los peers
        foreach (string peerId in connectedPeers)
        {
            Application.ExternalCall("cleanupPeer", peerId);
        }
        connectedPeers.Clear();
        
        // Cerrar conexión principal
        Application.ExternalCall("cleanupAllPeers");
        
        isInitialized = false;
        myPeerId = "";
        
        Debug.Log("[PeerJSManager] PeerJS limpiado");
#endif
    }
    
    /// <summary>
    /// Obtiene el estado actual de PeerJS
    /// </summary>
    public PeerJSStatus GetStatus()
    {
        return new PeerJSStatus
        {
            isInitialized = isInitialized,
            myPeerId = myPeerId,
            connectedPeersCount = connectedPeers.Count,
            connectedPeers = new List<string>(connectedPeers),
            roomId = roomId,
            actorId = actorId
        };
    }
    
    // Métodos públicos para control desde otros scripts
    public bool IsInitialized => isInitialized;
    public string MyPeerId => myPeerId;
    public int ConnectedPeersCount => connectedPeers.Count;
    public List<string> ConnectedPeers => new List<string>(connectedPeers);
}

/// <summary>
/// Estructura para el estado de PeerJS
/// </summary>
[System.Serializable]
public class PeerJSStatus
{
    public bool isInitialized;
    public string myPeerId;
    public int connectedPeersCount;
    public List<string> connectedPeers;
    public string roomId;
    public string actorId;
}

