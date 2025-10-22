# 📚 Referencia de API - Unity Simulador de Juicios Orales

## 📋 Tabla de Contenidos

1. [LaravelAPI.cs](#laravelapics)
2. [UnityLaravelIntegration.cs](#unitylaravelintegrationcs)
3. [DialogoUI.cs](#dialogouics)
4. [UnityConfig.cs](#unityconfigcs)
5. [GameInitializer.cs](#gameinitializercs)
6. [Scripts de Red](#scripts-de-red)
7. [Scripts de UI](#scripts-de-ui)

---

## LaravelAPI.cs

### Descripción
Cliente HTTP principal para comunicación con la API de Laravel. Implementa patrón Singleton y maneja autenticación JWT, comunicación RESTful y eventos en tiempo real.

### Namespace
```csharp
namespace JuiciosSimulator.API
```

### Propiedades Principales
```csharp
public string baseURL = "http://localhost:8000/api";
public string unityVersion = "2022.3.15f1";
public string unityPlatform = "WebGL";
public string deviceId = "UNITY_DEVICE_001";
public string authToken = "";
public UserData currentUser;
public int currentSesionId = 0;
public bool isConnected = false;
```

### Eventos
```csharp
public static event Action<bool> OnConnectionStatusChanged;
public static event Action<UserData> OnUserLoggedIn;
public static event Action<string> OnError;
public static event Action<DialogoEstado> OnDialogoUpdated;
public static event Action<List<RespuestaUsuario>> OnRespuestasReceived;
```

### Métodos de Autenticación

#### Login
```csharp
public void Login(string email, string password)
```
**Descripción**: Autentica al usuario con Laravel
**Parámetros**:
- `email`: Email del usuario
- `password`: Contraseña del usuario

**Ejemplo**:
```csharp
LaravelAPI.Instance.Login("alumno@example.com", "password");
```

#### CheckServerStatus
```csharp
private IEnumerator CheckServerStatus()
```
**Descripción**: Verifica el estado del servidor Laravel
**Retorna**: IEnumerator para corrutina

### Métodos de Diálogos

#### GetDialogoEstado
```csharp
public void GetDialogoEstado(int sesionId)
```
**Descripción**: Obtiene el estado actual del diálogo
**Parámetros**:
- `sesionId`: ID de la sesión

**Ejemplo**:
```csharp
LaravelAPI.Instance.GetDialogoEstado(1);
```

#### GetRespuestasUsuario
```csharp
public void GetRespuestasUsuario(int sesionId, int usuarioId)
```
**Descripción**: Obtiene las respuestas disponibles para un usuario
**Parámetros**:
- `sesionId`: ID de la sesión
- `usuarioId`: ID del usuario

#### EnviarDecision
```csharp
public void EnviarDecision(int sesionId, int usuarioId, int respuestaId, string decisionTexto, int tiempoRespuesta)
```
**Descripción**: Envía una decisión del usuario
**Parámetros**:
- `sesionId`: ID de la sesión
- `usuarioId`: ID del usuario
- `respuestaId`: ID de la respuesta seleccionada
- `decisionTexto`: Texto adicional de la decisión
- `tiempoRespuesta`: Tiempo en segundos para responder

### Métodos de Tiempo Real

#### StartRealtimeEvents
```csharp
public void StartRealtimeEvents(int sesionId)
```
**Descripción**: Inicia la escucha de eventos en tiempo real
**Parámetros**:
- `sesionId`: ID de la sesión

### Clases de Datos

#### UserData
```csharp
[Serializable]
public class UserData
{
    public int id;
    public string name;
    public string apellido;
    public string email;
    public string tipo;
    public bool activo;
    public Dictionary<string, object> configuracion;
}
```

#### DialogoEstado
```csharp
[Serializable]
public class DialogoEstado
{
    public bool dialogo_activo;
    public string estado;
    public NodoActual nodo_actual;
    public List<Participante> participantes;
    public float progreso;
    public int tiempo_transcurrido;
    public Dictionary<string, object> variables;
}
```

#### RespuestaUsuario
```csharp
[Serializable]
public class RespuestaUsuario
{
    public int id;
    public string texto;
    public int nodo_dialogo_id;
    public int orden;
}
```

---

## UnityLaravelIntegration.cs

### Descripción
Integración completa entre Unity, Photon PUN2 y Laravel. Maneja la conexión a Photon, sincronización con Laravel y integración con PeerJS para audio.

### Namespace
```csharp
namespace JuiciosSimulator.Integration
```

### Propiedades Principales
```csharp
public int sesionId = 1;
public string roomName = "SalaJuicio";
public int maxPlayers = 10;
public LaravelAPI laravelAPI;
public DialogoUI dialogoUI;
private bool isPhotonConnected = false;
private bool isLaravelConnected = false;
private string currentRoomId;
```

### Eventos
```csharp
public static event System.Action<bool> OnIntegrationReady;
public static event System.Action<string> OnIntegrationError;
```

### Métodos Principales

#### InitializeIntegration
```csharp
private IEnumerator InitializeIntegration()
```
**Descripción**: Inicializa la integración completa
**Retorna**: IEnumerator para corrutina

#### ConnectToPhoton
```csharp
private IEnumerator ConnectToPhoton()
```
**Descripción**: Conecta a Photon PUN2
**Retorna**: IEnumerator para corrutina

#### SetupRoom
```csharp
private IEnumerator SetupRoom()
```
**Descripción**: Crea o se une a una sala
**Retorna**: IEnumerator para corrutina

#### SyncPlayerPosition
```csharp
public void SyncPlayerPosition(Vector3 position, Vector3 rotation)
```
**Descripción**: Sincroniza la posición del jugador con Laravel
**Parámetros**:
- `position`: Posición del jugador
- `rotation`: Rotación del jugador

#### SyncAudioState
```csharp
public void SyncAudioState(bool microfonoActivo, bool audioEnabled, float volumen = 1.0f)
```
**Descripción**: Sincroniza el estado de audio con Laravel
**Parámetros**:
- `microfonoActivo`: Si el micrófono está activo
- `audioEnabled`: Si el audio está habilitado
- `volumen`: Nivel de volumen (0-1)

### Callbacks de Photon

#### OnJoinedRoom
```csharp
public override void OnJoinedRoom()
```
**Descripción**: Llamado cuando el jugador se une a una sala

#### OnPlayerEnteredRoom
```csharp
public override void OnPlayerEnteredRoom(Player newPlayer)
```
**Descripción**: Llamado cuando un nuevo jugador entra a la sala

#### OnPlayerLeftRoom
```csharp
public override void OnPlayerLeftRoom(Player otherPlayer)
```
**Descripción**: Llamado cuando un jugador sale de la sala

---

## DialogoUI.cs

### Descripción
Controlador de UI para el sistema de diálogos. Maneja la visualización de diálogos, selección de respuestas y feedback del usuario.

### Namespace
```csharp
namespace JuiciosSimulator.UI
```

### Propiedades Principales
```csharp
public GameObject loginPanel;
public GameObject dialogoPanel;
public TMP_InputField emailInput;
public TMP_InputField passwordInput;
public Button loginButton;
public TextMeshProUGUI dialogoTitleText;
public TextMeshProUGUI dialogoContentText;
public TextMeshProUGUI rolHablandoText;
public Transform respuestasContainer;
public GameObject respuestaButtonPrefab;
public int sesionId = 1;
public int usuarioId = 1;
```

### Métodos Principales

#### OnLoginClicked
```csharp
private void OnLoginClicked()
```
**Descripción**: Maneja el clic en el botón de login

#### OnUserLoggedIn
```csharp
private void OnUserLoggedIn(UserData user)
```
**Descripción**: Llamado cuando el usuario se loguea exitosamente
**Parámetros**:
- `user`: Datos del usuario logueado

#### OnDialogoUpdated
```csharp
private void OnDialogoUpdated(DialogoEstado estado)
```
**Descripción**: Actualiza la UI cuando cambia el estado del diálogo
**Parámetros**:
- `estado`: Estado actual del diálogo

#### OnRespuestasReceived
```csharp
private void OnRespuestasReceived(List<RespuestaUsuario> respuestas)
```
**Descripción**: Muestra las respuestas disponibles al usuario
**Parámetros**:
- `respuestas`: Lista de respuestas disponibles

#### OnEnviarDecisionClicked
```csharp
public void OnEnviarDecisionClicked()
```
**Descripción**: Envía la decisión seleccionada por el usuario

#### SetSesionId
```csharp
public void SetSesionId(int id)
```
**Descripción**: Establece el ID de la sesión
**Parámetros**:
- `id`: ID de la sesión

#### RefreshDialogo
```csharp
public void RefreshDialogo()
```
**Descripción**: Actualiza manualmente el estado del diálogo

---

## UnityConfig.cs

### Descripción
ScriptableObject para configuración centralizada del proyecto. Contiene todas las configuraciones necesarias para API, Photon, PeerJS y audio.

### Namespace
```csharp
namespace JuiciosSimulator.Config
```

### Propiedades de Configuración

#### API Configuration
```csharp
public string apiBaseURL = "http://localhost:8000/api";
public string unityVersion = "2022.3.15f1";
public string unityPlatform = "WebGL";
```

#### Photon Configuration
```csharp
public string photonAppId = "YOUR_PHOTON_APP_ID";
public string photonRegion = "us";
```

#### PeerJS Configuration
```csharp
public string peerjsHost = "juiciosorales.site";
public int peerjsPort = 443;
public bool peerjsSecure = true;
```

#### Audio Configuration
```csharp
public bool echoCancellation = true;
public bool noiseSuppression = true;
public bool autoGainControl = true;
public int sampleRate = 44100;
public int channelCount = 1;
public float audioLatency = 0.01f;
```

#### Sala Configuration
```csharp
public int maxPlayersPerRoom = 20;
public float connectionTimeout = 30f;
```

#### Debug Configuration
```csharp
public bool showDebugLogs = true;
public bool showDebugPanel = true;
public LogLevel logLevel = LogLevel.Info;
```

### Métodos Principales

#### GetAudioConfig
```csharp
public object GetAudioConfig()
```
**Descripción**: Obtiene la configuración de audio para PeerJS
**Retorna**: Objeto con configuración de audio

#### GetPeerJSConfig
```csharp
public object GetPeerJSConfig()
```
**Descripción**: Obtiene la configuración de PeerJS
**Retorna**: Objeto con configuración de PeerJS

#### GetPhotonConfig
```csharp
public object GetPhotonConfig()
```
**Descripción**: Obtiene la configuración de Photon
**Retorna**: Objeto con configuración de Photon

#### ValidateConfig
```csharp
public bool ValidateConfig()
```
**Descripción**: Valida la configuración actual
**Retorna**: true si la configuración es válida

#### ApplyConfig
```csharp
public void ApplyConfig()
```
**Descripción**: Aplica la configuración a los componentes

#### ResetToDefault
```csharp
[ContextMenu("Reset to Default")]
public void ResetToDefault()
```
**Descripción**: Resetea la configuración a valores por defecto

---

## GameInitializer.cs

### Descripción
Inicializador principal del juego. Maneja la inicialización de todos los componentes y la configuración inicial.

### Namespace
```csharp
namespace JuiciosSimulator
```

### Propiedades Principales
```csharp
public UnityConfig config;
public LaravelAPI laravelAPI;
public DialogoUI dialogoUI;
public UnityLaravelIntegration integration;
public int sesionId = 1;
public string testEmail = "alumno@example.com";
public string testPassword = "password";
```

### Métodos Principales

#### InitializeGame
```csharp
private void InitializeGame()
```
**Descripción**: Inicializa el juego completo

#### SetupComponents
```csharp
private void SetupComponents()
```
**Descripción**: Configura todos los componentes

#### SubscribeToEvents
```csharp
private void SubscribeToEvents()
```
**Descripción**: Se suscribe a todos los eventos necesarios

#### StartConnectionProcess
```csharp
private void StartConnectionProcess()
```
**Descripción**: Inicia el proceso de conexión

#### RestartGame
```csharp
public void RestartGame()
```
**Descripción**: Reinicia el juego completo

#### ChangeSession
```csharp
public void ChangeSession(int newSesionId)
```
**Descripción**: Cambia la sesión actual
**Parámetros**:
- `newSesionId`: ID de la nueva sesión

#### GetGameStatus
```csharp
public string GetGameStatus()
```
**Descripción**: Obtiene el estado actual del juego
**Retorna**: String con el estado del juego

---

## Scripts de Red

### GestionRedJugador.cs

#### Descripción
Gestión de conexión Photon y selección de roles

#### Métodos Principales
```csharp
void ConnectToPhoton()                    // Conecta a Photon
public void JoinRoom()                    // Se une a una sala
public override void OnConnectedToMaster() // Callback de conexión
public override void OnJoinedLobby()      // Callback de lobby
public override void OnJoinedRoom()       // Callback de sala
public void OnVoiceReady(string myPeerId) // Callback de PeerJS
```

### ControlCamaraJugador.cs

#### Descripción
Control de cámara específico por jugador

#### Funcionalidad
- Habilita/deshabilita cámara según el jugador
- Gestiona AudioListener por jugador
- Integración con Photon

### RedesJugador.cs

#### Descripción
Deshabilita scripts en jugadores remotos

#### Propiedades
```csharp
public MonoBehaviour[] codigosQueIgnorar; // Scripts a deshabilitar
```

---

## Scripts de UI

### RoleSelectionUI.cs

#### Descripción
UI para selección de roles

#### Métodos Principales
```csharp
public void InitializeUI()                // Inicializa la UI
void GenerateRoleButtons()               // Genera botones de roles
void OnRoleSelected(string selectedRole)  // Maneja selección de rol
string[] GetUsedRoles()                  // Obtiene roles usados
```

#### Roles Disponibles
```csharp
public static readonly string[] Roles = new string[]
{
    "Juez", "Fiscal", "Defensa", "Testigo1", "Testigo2",
    "Policía1", "Policía2", "Psicólogo", "Acusado", "Secretario",
    "Abogado1", "Abogado2", "Perito1", "Perito2", "Víctima",
    "Acusador", "Periodista", "Público1", "Público2", "Observador"
};
```

### RoleLabelDisplay.cs

#### Descripción
Visualización del rol del jugador

#### Métodos Principales
```csharp
void UpdateRoleLabel()                   // Actualiza el label del rol
public override void OnPlayerPropertiesUpdate() // Callback de propiedades
```

---

## 🔧 Patrones de Diseño Utilizados

### Singleton Pattern
- `LaravelAPI.cs`: Acceso global a la API
- `GameInitializer.cs`: Inicialización centralizada

### Observer Pattern
- Sistema de eventos en `LaravelAPI.cs`
- Callbacks de Photon en `UnityLaravelIntegration.cs`

### ScriptableObject Pattern
- `UnityConfig.cs`: Configuración persistente

### Component Pattern
- Scripts modulares y reutilizables
- Separación clara de responsabilidades

---

## 📊 Métricas del Código

- **Total de Scripts**: 10 scripts C#
- **Líneas de Código**: ~2,500 líneas
- **Namespaces**: 4 namespaces organizados
- **Patrones de Diseño**: 4 patrones implementados
- **Integraciones**: 3 integraciones principales

---

**¡Documentación de API completa! 📚**
