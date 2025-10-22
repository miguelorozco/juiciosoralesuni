# 🛠️ Guía de Desarrollo - Unity Simulador de Juicios Orales

## 📋 Tabla de Contenidos

1. [Configuración del Entorno](#configuración-del-entorno)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Flujo de Desarrollo](#flujo-de-desarrollo)
4. [Convenciones de Código](#convenciones-de-código)
5. [Testing](#testing)
6. [Debugging](#debugging)
7. [Build y Deploy](#build-y-deploy)
8. [Mejores Prácticas](#mejores-prácticas)

---

## ⚙️ Configuración del Entorno

### Requisitos del Sistema

#### Unity
- **Versión**: Unity 6000.2.8f1 o superior
- **Módulos Requeridos**:
  - WebGL Build Support
  - Universal Render Pipeline (URP)
  - Input System
  - Cinemachine
  - Post-processing

#### Herramientas Adicionales
- **Visual Studio Code** o **Visual Studio** (recomendado)
- **Git** para control de versiones
- **Node.js** (para herramientas de desarrollo)

### Instalación Paso a Paso

#### 1. Instalar Unity Hub
```bash
# Descargar desde: https://unity3d.com/get-unity/download
# Instalar Unity Hub
# Instalar Unity 6000.2.8f1
```

#### 2. Configurar el Proyecto
```bash
# Clonar el repositorio
git clone [repository-url]
cd unity-integration/unity-project

# Abrir en Unity Hub
# Unity Hub > Open > Seleccionar carpeta del proyecto
```

#### 3. Instalar Paquetes Requeridos
En Unity:
1. **Window > Package Manager**
2. Instalar los siguientes paquetes:
   - Universal RP
   - Input System
   - Cinemachine
   - Post-processing
   - TextMeshPro

#### 4. Configurar Photon PUN2
1. **Window > Photon Unity Networking > PUN Wizard**
2. Ingresar App ID: `2ec23c58-5cc4-419d-8214-13abad14a02f`
3. Configurar región: `us`

#### 5. Configurar Laravel
```bash
# En el directorio raíz del proyecto Laravel
cd /path/to/laravel/project
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 📁 Estructura del Proyecto

### Organización de Carpetas

```
Assets/
├── Scripts/                    # Scripts C# del proyecto
│   ├── Core/                  # Scripts principales
│   │   ├── GameInitializer.cs
│   │   ├── LaravelAPI.cs
│   │   └── UnityConfig.cs
│   ├── UI/                    # Scripts de interfaz
│   │   ├── DialogoUI.cs
│   │   ├── RoleSelectionUI.cs
│   │   └── RoleLabelDisplay.cs
│   ├── Integration/           # Scripts de integración
│   │   └── UnityLaravelIntegration.cs
│   └── Network/               # Scripts de red
│       ├── GestionRedJugador.cs
│       ├── ControlCamaraJugador.cs
│       └── RedesJugador.cs
├── Scenes/                    # Escenas del proyecto
│   ├── Sala.unity
│   └── SalaPrincipal.unity
├── Prefabs/                   # Prefabs reutilizables
├── Materials/                 # Materiales
├── Textures/                  # Texturas
├── Audio/                     # Archivos de audio
├── Resources/                 # Recursos cargados dinámicamente
└── StreamingAssets/          # Archivos de configuración
    └── unity-config.json
```

### Convenciones de Nomenclatura

#### Archivos y Carpetas
- **PascalCase** para scripts: `GameInitializer.cs`
- **snake_case** para configuraciones: `unity_config.json`
- **PascalCase** para carpetas: `Scripts/`, `UI/`

#### Clases y Métodos
```csharp
// Clases: PascalCase
public class GameInitializer : MonoBehaviour

// Métodos públicos: PascalCase
public void InitializeGame()

// Métodos privados: camelCase
private void setupComponents()

// Variables: camelCase
private bool isConnected = false;

// Constantes: UPPER_CASE
public const int MAX_PLAYERS = 20;
```

#### Eventos
```csharp
// Eventos: On + Acción + Sujeto
public static event Action<bool> OnConnectionStatusChanged;
public static event Action<UserData> OnUserLoggedIn;
```

---

## 🔄 Flujo de Desarrollo

### 1. Configuración Inicial

#### Crear Nueva Rama
```bash
git checkout -b feature/nueva-funcionalidad
```

#### Configurar Unity
1. Abrir proyecto en Unity
2. Verificar que todos los paquetes estén instalados
3. Configurar `UnityConfig.cs` con valores de desarrollo

#### Configurar Laravel
```bash
# En terminal separado
cd /path/to/laravel/project
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Desarrollo de Funcionalidades

#### Crear Nuevo Script
```csharp
using UnityEngine;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Features
{
    /// <summary>
    /// Descripción de la funcionalidad
    /// </summary>
    public class NewFeature : MonoBehaviour
    {
        [Header("Configuración")]
        public string configValue = "default";
        
        [Header("Referencias")]
        public LaravelAPI laravelAPI;
        
        private void Start()
        {
            // Inicialización
        }
        
        private void OnDestroy()
        {
            // Limpieza
        }
    }
}
```

#### Integrar con Laravel
```csharp
// Suscribirse a eventos
LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
LaravelAPI.OnError += OnError;

// Hacer llamadas a API
LaravelAPI.Instance.GetDialogoEstado(sesionId);

// Manejar respuestas
private void OnUserLoggedIn(UserData user)
{
    Debug.Log($"Usuario logueado: {user.name}");
}
```

#### Integrar con Photon
```csharp
using Photon.Pun;

public class NewFeature : MonoBehaviourPun
{
    // Usar RPC para sincronización
    [PunRPC]
    public void SyncData(string data)
    {
        // Procesar datos sincronizados
    }
    
    // Enviar datos a otros jugadores
    photonView.RPC("SyncData", RpcTarget.All, data);
}
```

### 3. Testing

#### Testing en Editor
```csharp
// Usar GameInitializer con auto-login
// Verificar logs en Console
// Usar Debug Panel para testing
```

#### Testing WebGL
1. **File > Build Settings**
2. Seleccionar **WebGL**
3. **Build** en carpeta `builds/webgl/`
4. Probar en navegador

#### Testing Multiplayer
1. Abrir múltiples instancias del build
2. Probar sincronización entre jugadores
3. Verificar audio entre jugadores

### 4. Commit y Push

```bash
# Agregar cambios
git add .

# Commit con mensaje descriptivo
git commit -m "feat: agregar nueva funcionalidad de diálogos"

# Push a la rama
git push origin feature/nueva-funcionalidad
```

---

## 📝 Convenciones de Código

### Estructura de Scripts

#### Header del Script
```csharp
using UnityEngine;
using JuiciosSimulator.API;

namespace JuiciosSimulator.Features
{
    /// <summary>
    /// Descripción detallada de la funcionalidad
    /// </summary>
    public class NewFeature : MonoBehaviour
    {
        // Contenido del script
    }
}
```

#### Organización de Propiedades
```csharp
[Header("Configuración")]
public string configValue = "default";
public int maxValue = 100;

[Header("Referencias")]
public LaravelAPI laravelAPI;
public DialogoUI dialogoUI;

[Header("Estado")]
private bool isInitialized = false;
private int currentValue = 0;
```

#### Organización de Métodos
```csharp
#region Unity Lifecycle
private void Start() { }
private void Update() { }
private void OnDestroy() { }
#endregion

#region Public Methods
public void PublicMethod() { }
#endregion

#region Private Methods
private void PrivateMethod() { }
#endregion

#region Event Handlers
private void OnEvent() { }
#endregion
```

### Manejo de Eventos

#### Suscripción a Eventos
```csharp
private void Start()
{
    // Suscribirse a eventos
    LaravelAPI.OnUserLoggedIn += OnUserLoggedIn;
    LaravelAPI.OnError += OnError;
}

private void OnDestroy()
{
    // Desuscribirse de eventos
    LaravelAPI.OnUserLoggedIn -= OnUserLoggedIn;
    LaravelAPI.OnError -= OnError;
}
```

#### Definición de Eventos
```csharp
// Eventos estáticos para comunicación global
public static event Action<bool> OnConnectionStatusChanged;
public static event Action<UserData> OnUserLoggedIn;

// Eventos de instancia para comunicación local
public event Action<string> OnLocalEvent;
```

### Manejo de Errores

#### Try-Catch para Operaciones Críticas
```csharp
try
{
    // Operación crítica
    LaravelAPI.Instance.GetDialogoEstado(sesionId);
}
catch (Exception e)
{
    Debug.LogError($"Error al obtener estado del diálogo: {e.Message}");
    OnError?.Invoke(e.Message);
}
```

#### Validación de Datos
```csharp
public void ProcessData(string data)
{
    if (string.IsNullOrEmpty(data))
    {
        Debug.LogWarning("Data is null or empty");
        return;
    }
    
    // Procesar datos
}
```

### Logging

#### Niveles de Log
```csharp
// Información general
Debug.Log("Usuario conectado exitosamente");

// Advertencias
Debug.LogWarning("Conexión lenta detectada");

// Errores
Debug.LogError($"Error de conexión: {error}");

// Debug detallado (solo en desarrollo)
if (Debug.isDebugBuild)
{
    Debug.Log($"Debug info: {detailedInfo}");
}
```

---

## 🧪 Testing

### Testing Unitario

#### Crear Test Script
```csharp
using NUnit.Framework;
using UnityEngine;
using JuiciosSimulator.API;

public class LaravelAPITests
{
    [Test]
    public void TestLogin_ValidCredentials_ReturnsSuccess()
    {
        // Arrange
        var api = new LaravelAPI();
        
        // Act
        api.Login("test@example.com", "password");
        
        // Assert
        Assert.IsTrue(api.isConnected);
    }
}
```

#### Ejecutar Tests
1. **Window > General > Test Runner**
2. Seleccionar tests a ejecutar
3. **Run All** o **Run Selected**

### Testing de Integración

#### Test de Conexión Laravel
```csharp
[Test]
public void TestLaravelConnection()
{
    var api = new LaravelAPI();
    api.baseURL = "http://localhost:8000/api";
    
    // Verificar conexión
    Assert.DoesNotThrow(() => api.CheckServerStatus());
}
```

#### Test de Photon
```csharp
[Test]
public void TestPhotonConnection()
{
    // Verificar que Photon esté configurado
    Assert.IsNotNull(PhotonNetwork.PhotonServerSettings);
    Assert.IsNotEmpty(PhotonNetwork.PhotonServerSettings.AppSettings.AppIdRealtime);
}
```

### Testing Manual

#### Checklist de Testing
- [ ] Login funciona correctamente
- [ ] Diálogos se muestran en tiempo real
- [ ] Respuestas se envían correctamente
- [ ] Audio funciona entre jugadores
- [ ] Sincronización de jugadores funciona
- [ ] UI responde correctamente
- [ ] Manejo de errores funciona
- [ ] Performance es aceptable

---

## 🐛 Debugging

### Herramientas de Debug

#### Console de Unity
```csharp
// Logs básicos
Debug.Log("Información general");
Debug.LogWarning("Advertencia");
Debug.LogError("Error crítico");

// Logs condicionales
Debug.LogFormat("Usuario {0} conectado", userName);
Debug.LogAssertion(condition, "Condición falló");
```

#### Debug Panel
```csharp
private void OnGUI()
{
    if (showDebugPanel)
    {
        GUILayout.BeginArea(new Rect(10, 10, 400, 300));
        GUILayout.Label("Debug Information");
        GUILayout.Label($"Estado: {GetStatus()}");
        
        if (GUILayout.Button("Test Button"))
        {
            TestFunction();
        }
        
        GUILayout.EndArea();
    }
}
```

#### Breakpoints
```csharp
// En Visual Studio
// Colocar breakpoint en línea específica
// Ejecutar en modo Debug
// Inspeccionar variables
```

### Debugging de Red

#### Logs de Photon
```csharp
// Habilitar logs detallados de Photon
PhotonNetwork.LogLevel = PunLogLevel.Full;

// Verificar estado de conexión
Debug.Log($"Photon Connected: {PhotonNetwork.IsConnected}");
Debug.Log($"In Room: {PhotonNetwork.InRoom}");
Debug.Log($"Room Name: {PhotonNetwork.CurrentRoom?.Name}");
```

#### Logs de Laravel
```bash
# En terminal
tail -f storage/logs/laravel.log

# Filtrar logs específicos
grep "Unity" storage/logs/laravel.log
```

### Debugging de Audio

#### Verificar PeerJS
```javascript
// En consola del navegador
console.log("PeerJS Status:", peer.connected);
console.log("Current Connections:", Object.keys(peer.connections));
```

#### Verificar Micrófono
```csharp
// Verificar permisos de micrófono
if (Application.HasUserAuthorization(UserAuthorization.Microphone))
{
    Debug.Log("Micrófono autorizado");
}
else
{
    Debug.LogWarning("Micrófono no autorizado");
}
```

---

## 🚀 Build y Deploy

### Build para Desarrollo

#### WebGL Build
1. **File > Build Settings**
2. Seleccionar **WebGL**
3. **Player Settings**:
   - Company Name: Tu empresa
   - Product Name: Simulador de Juicios Orales
   - WebGL Template: Custom
4. **Build** en carpeta `builds/webgl/`

#### Standalone Build
1. **File > Build Settings**
2. Seleccionar **Windows/Mac/Linux**
3. **Build** en carpeta `builds/standalone/`

### Build para Producción

#### Configuración de Producción
```csharp
// En UnityConfig.cs
public string apiBaseURL = "https://juiciosorales.site/api";
public bool debugMode = false;
public bool showDebugLogs = false;
```

#### Optimizaciones
1. **File > Build Settings > Player Settings**
2. **Publishing Settings**:
   - Compression Format: Gzip
   - Data Caching: Enabled
3. **XR Settings**: Deshabilitar si no se usa
4. **Graphics**: Usar URP optimizado

### Deploy

#### Deploy Local
```bash
# Usar script de deploy
./deploy-unity-local.sh builds/webgl/
```

#### Deploy a Servidor
```bash
# Usar script de deploy
./deploy-unity.sh builds/webgl/
```

---

## 💡 Mejores Prácticas

### Performance

#### Optimización de UI
```csharp
// Usar object pooling para botones
private Queue<GameObject> buttonPool = new Queue<GameObject>();

private GameObject GetButton()
{
    if (buttonPool.Count > 0)
    {
        return buttonPool.Dequeue();
    }
    return Instantiate(buttonPrefab);
}

private void ReturnButton(GameObject button)
{
    button.SetActive(false);
    buttonPool.Enqueue(button);
}
```

#### Optimización de Red
```csharp
// Limitar frecuencia de sincronización
private float lastSyncTime = 0f;
private float syncInterval = 0.1f; // 10 veces por segundo

private void Update()
{
    if (Time.time - lastSyncTime > syncInterval)
    {
        SyncData();
        lastSyncTime = Time.time;
    }
}
```

### Seguridad

#### Validación de Datos
```csharp
public void ProcessUserInput(string input)
{
    // Validar entrada
    if (string.IsNullOrEmpty(input) || input.Length > 1000)
    {
        Debug.LogWarning("Input inválido");
        return;
    }
    
    // Sanitizar entrada
    string sanitized = input.Trim().Replace("<", "&lt;").Replace(">", "&gt;");
    
    // Procesar
    ProcessData(sanitized);
}
```

#### Manejo de Tokens
```csharp
// Verificar token antes de usar
if (string.IsNullOrEmpty(authToken))
{
    Debug.LogWarning("Token no disponible");
    return;
}

// Usar token en headers
request.SetRequestHeader("Authorization", $"Bearer {authToken}");
```

### Mantenibilidad

#### Documentación de Código
```csharp
/// <summary>
/// Procesa una decisión del usuario y la envía al servidor
/// </summary>
/// <param name="sesionId">ID de la sesión actual</param>
/// <param name="usuarioId">ID del usuario</param>
/// <param name="respuestaId">ID de la respuesta seleccionada</param>
/// <param name="texto">Texto adicional de la decisión</param>
/// <returns>True si la decisión se envió exitosamente</returns>
public bool ProcesarDecision(int sesionId, int usuarioId, int respuestaId, string texto)
{
    // Implementación
}
```

#### Configuración Centralizada
```csharp
// Usar ScriptableObject para configuración
[CreateAssetMenu(fileName = "GameConfig", menuName = "Game/Config")]
public class GameConfig : ScriptableObject
{
    public string apiUrl;
    public int maxPlayers;
    public float timeout;
}
```

---

## 📚 Recursos Adicionales

### Documentación Oficial
- [Unity Documentation](https://docs.unity3d.com/)
- [Photon PUN2 Documentation](https://doc.photonengine.com/pun2)
- [Laravel Documentation](https://laravel.com/docs)

### Herramientas de Desarrollo
- [Unity Profiler](https://docs.unity3d.com/Manual/Profiler.html)
- [Unity Test Runner](https://docs.unity3d.com/Manual/testing-editortestsrunner.html)
- [Visual Studio Code Unity Extension](https://code.visualstudio.com/docs/other/unity)

### Comunidad
- [Unity Forum](https://forum.unity.com/)
- [Photon Community](https://forum.photonengine.com/)
- [Laravel Community](https://laracasts.com/)

---

**¡Guía de desarrollo completa! 🛠️**
