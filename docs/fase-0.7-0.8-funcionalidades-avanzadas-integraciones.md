# FASE 0.7 y 0.8: Funcionalidades Avanzadas e Integraciones - Pixel Crushers Dialogue System

## 📋 Índice

1. [FASE 0.7: Funcionalidades Avanzadas](#fase-07-funcionalidades-avanzadas)
2. [FASE 0.8: Integraciones y Extensiones](#fase-08-integraciones-y-extensiones)

---

## FASE 0.7: Funcionalidades Avanzadas

### Sistema de Misiones (Quests)

#### QuestLog - Clase Principal

**Ubicación**: `Scripts/Quests/QuestLog.cs`

**Propósito**: Clase estática que gestiona un registro de misiones usando la tabla Lua `Item[]`.

#### Estructura de Quests

**En DialogueDatabase**:
- Los quests se almacenan en `Item[]` donde `Is_Item = false`
- Alias: `Quest[]` (equivalente a `Item[]`)

**Campos Requeridos**:
- `Name`: Nombre de la quest
- `State`: Estado de la quest (unassigned, active, success, failure, done, abandoned)
- `Description`: Descripción cuando está activa
- `Success Description`: Descripción cuando se completa exitosamente
- `Failure Description`: Descripción cuando falla
- `Display Name`: (Opcional) Nombre para mostrar en UIs

#### QuestState - Enum de Estados

**Ubicación**: `Scripts/Quests/QuestState.cs`

```csharp
[System.Flags]
public enum QuestState
{
    Unassigned = 0x1,      // No asignada
    Active = 0x2,          // Activa (asignada pero no completada)
    Success = 0x4,         // Completada exitosamente
    Failure = 0x8,         // Completada en fallo
    Abandoned = 0x10,       // Abandonada
    Grantable = 0x20,       // Disponible para otorgar (no usado por DS)
    ReturnToNPC = 0x40     // Esperando retornar a NPC (no usado por DS)
}
```

**Nota**: `done` es equivalente a `success` y ambos corresponden a `QuestState.Success`.

#### Métodos Principales de QuestLog

```csharp
public static class QuestLog
{
    // Agregar quest
    public static void AddQuest(string questName, string description, 
        string successDescription, string failureDescription, QuestState state);
    public static void AddQuest(string questName, string description, QuestState state);
    public static void AddQuest(string questName, string description);
    
    // Eliminar quest
    public static void DeleteQuest(string questName);
    
    // Obtener estado
    public static QuestState GetQuestState(string questName);
    public static string CurrentQuestState(string questName);
    
    // Establecer estado
    public static void SetQuestState(string questName, QuestState state);
    public static void SetQuestState(string questName, string state);
    
    // Métodos de conveniencia
    public static void StartQuest(string questName);
    public static void CompleteQuest(string questName);
    public static void FailQuest(string questName);
    public static void AbandonQuest(string questName);
    
    // Verificaciones
    public static bool IsQuestUnassigned(string questName);
    public static bool IsQuestActive(string questName);
    public static bool IsQuestSuccessful(string questName);
    public static bool IsQuestFailed(string questName);
    public static bool IsQuestAbandoned(string questName);
    public static bool IsQuestDone(string questName);
    public static bool IsQuestInStateMask(string questName, QuestState stateMask);
    
    // Quest Entries (sub-tareas)
    public static QuestState GetQuestEntryState(string questName, int entryNumber);
    public static void SetQuestEntryState(string questName, int entryNumber, QuestState state);
    public static void SetQuestEntryState(string questName, int entryNumber, string state);
    public static bool IsQuestEntryInStateMask(string questName, int entryNumber, QuestState stateMask);
    
    // Tracking
    public static void SetQuestTracking(string questName, bool track);
    public static bool IsQuestTrackingEnabled(string questName);
    
    // Obtener todas las quests
    public static string[] GetAllQuests();
    public static string[] GetAllQuests(QuestState stateMask);
}
```

#### Ejemplo de Uso

```csharp
// Agregar quest
QuestLog.AddQuest("Kill 5 Rats", 
    "The baker asked me to bring 5 rat corpses to make a pie.",
    "I brought the baker 5 dead rats, and we ate a delicious pie!",
    "I freed the Pied Piper from jail. He took all the rats. No pie for me....",
    QuestState.Unassigned);

// Iniciar quest
QuestLog.StartQuest("Kill 5 Rats");

// Completar quest
QuestLog.CompleteQuest("Kill 5 Rats");

// Verificar estado
if (QuestLog.IsQuestActive("Kill 5 Rats"))
{
    Debug.Log("Quest is active!");
}
```

#### Integración con Diálogos

**En Lua (dentro de diálogos)**:

```lua
-- Establecer estado de quest
SetQuestState("Kill 5 Rats", "active")

-- Verificar estado en condiciones
if CurrentQuestState("Kill 5 Rats") == "active" then
    -- Mostrar esta opción
end

-- Establecer estado de entrada de quest
SetQuestEntryState("Kill 5 Rats", 1, "success")
```

**En C# (desde código)**:

```csharp
// En un DialogueSystemTrigger o script personalizado
if (QuestLog.IsQuestActive("Kill 5 Rats"))
{
    DialogueManager.StartConversation("RatKillerConversation");
}
```

#### Sistema de Tracking

**Tracking**: Permite marcar quests para seguimiento especial (mostrar en UI, etc.)

```csharp
// Habilitar tracking
QuestLog.SetQuestTracking("Kill 5 Rats", true);

// Verificar si está siendo trackeada
bool isTracking = QuestLog.IsQuestTrackingEnabled("Kill 5 Rats");

// Obtener todas las quests trackeadas
string[] trackedQuests = QuestLog.GetAllQuests(QuestState.Active);
```

#### Quest Entries (Sub-tareas)

**Propósito**: Permite dividir una quest en múltiples sub-tareas.

**Ejemplo**:
- Quest: "Kill 5 Rats"
  - Entry 1: "Kill Rat 1" (success)
  - Entry 2: "Kill Rat 2" (success)
  - Entry 3: "Kill Rat 3" (active)
  - Entry 4: "Kill Rat 4" (unassigned)
  - Entry 5: "Kill Rat 5" (unassigned)

```csharp
// Establecer estado de entrada
QuestLog.SetQuestEntryState("Kill 5 Rats", 1, QuestState.Success);

// Verificar estado de entrada
QuestState entryState = QuestLog.GetQuestEntryState("Kill 5 Rats", 1);
```

#### QuestLogWindow - UI de Quest Log

**Ubicación**: `Scripts/Quests/QuestLogWindow.cs`

**Propósito**: Proporciona una ventana de UI para mostrar las quests activas.

**Características**:
- Muestra quests activas
- Filtrado por estado
- Actualización automática
- Integración con Unity UI

#### Eventos de Quest

**En DialogueSystemEvents**:

```csharp
public class QuestEvents
{
    public StringEvent onQuestStateChange = new StringEvent();
    public StringEvent onQuestTrackingEnabled = new StringEvent();
    public StringEvent onQuestTrackingDisabled = new StringEvent();
    public UnityEvent onUpdateQuestTracker = new UnityEvent();
}
```

**Mensajes Broadcast**:
- `OnQuestStateChange`: Cuando cambia el estado de una quest
- `OnQuestEntryStateChange`: Cuando cambia el estado de una entrada
- `OnQuestTrackingEnabled`: Cuando se habilita tracking
- `OnQuestTrackingDisabled`: Cuando se deshabilita tracking
- `UpdateTracker`: Cuando se actualiza el tracker

### Sistema de Localización

#### Localization - Clase Principal

**Ubicación**: `Scripts/Manager/Localization.cs`

**Propósito**: Clase estática que gestiona la localización de textos.

#### Propiedades

```csharp
public static class Localization
{
    // Idioma actual
    public static string language { get; set; }
    
    // Si es idioma por defecto
    public static bool isDefaultLanguage { get; }
    
    // Usar texto por defecto si no está definido
    public static bool useDefaultIfUndefined { get; set; }
}
```

#### Conversión de SystemLanguage

```csharp
public static string GetLanguage(SystemLanguage systemLanguage)
{
    switch (systemLanguage)
    {
        case SystemLanguage.Spanish: return "es";
        case SystemLanguage.English: return "en";
        case SystemLanguage.French: return "fr";
        // ... más idiomas
        default: return null;
    }
}
```

**Idiomas Soportados**:
- `af` (Afrikaans)
- `ar` (Arabic)
- `es` (Spanish)
- `en` (English)
- `fr` (French)
- `de` (German)
- `it` (Italian)
- `pt` (Portuguese)
- `ru` (Russian)
- `zh` (Chinese)
- `ja` (Japanese)
- `ko` (Korean)
- Y muchos más...

#### TextTable - Sistema de Tablas de Texto

**Ubicación**: `PixelCrushers.Common/Scripts/TextTable.cs` (Common Library)

**Propósito**: ScriptableObject que contiene tablas de texto localizado.

**Estructura**:

```csharp
public class TextTable : ScriptableObject
{
    public List<string> languages = new List<string>();
    public List<TextField> fields = new List<TextField>();
    
    public string GetText(string fieldName, int languageID = -1);
    public string GetText(string fieldName, string language);
    public int GetLanguageID(string language);
}
```

**Ejemplo de Uso**:

```csharp
// Obtener texto localizado
string text = textTable.GetText("greeting", "es");  // "Hola"
string text = textTable.GetText("greeting", "en");  // "Hello"

// Usar idioma actual
Localization.language = "es";
string text = textTable.GetText("greeting");  // "Hola"
```

#### LocalizedTextTable - (Deprecated)

**Ubicación**: `Scripts/Utility/LocalizedTextTable.cs`

**Estado**: ⚠️ **Deprecated** - Usar `TextTable` de Common Library

**Estructura**:

```csharp
public class LocalizedTextTable : ScriptableObject
{
    public List<string> languages = new List<string>();
    public List<LocalizedTextField> fields = new List<LocalizedTextField>();
    
    public string GetText(string fieldName);
    public bool ContainsField(string fieldName);
}
```

#### Uso en Diálogos

**En DialogueEntry**:
- Campo `Dialogue Text`: Texto por defecto
- Campos `"ES"`, `"FR"`, etc.: Textos localizados

**Ejemplo**:
- `Dialogue Text`: "Hello"
- `"ES"`: "Hola"
- `"FR"`: "Bonjour"

**Acceso en Lua**:

```lua
-- Obtener texto localizado
local text = GetLocalizedDialogueText(1)  -- Entry ID 1
```

**Acceso en C#**:

```csharp
// Obtener texto localizado de un actor
string text = DialogueLua.GetLocalizedActorField("Player", "Name");

// Obtener texto localizado de una conversación
string text = DialogueLua.GetLocalizedConversationField("Greeting", "Title");
```

#### Cambio de Idioma en Runtime

```csharp
// Cambiar idioma
Localization.language = "es";

// El sistema actualiza automáticamente:
// - Textos de diálogos activos
// - Textos de UI
// - Textos de quests
// - Textos de actores
```

**Configuración en DialogueManager**:

```csharp
// En Display Settings > Localization Settings
public class LocalizationSettings
{
    public TextTable textTable;
    public bool useSystemLanguage = true;
    public string defaultLanguage = "en";
    public bool useDefaultIfUndefined = true;
}
```

### Sistema de Eventos

#### DialogueSystemEvents - Componente de Eventos

**Ubicación**: `Scripts/Manager/DialogueSystemEvents.cs`

**Propósito**: Componente que permite suscribirse a eventos del Dialogue System usando Unity Events.

#### Eventos Disponibles

**ConversationEvents**:

```csharp
public class ConversationEvents
{
    public TransformEvent onConversationStart = new TransformEvent();
    public TransformEvent onConversationEnd = new TransformEvent();
    public TransformEvent onConversationCancelled = new TransformEvent();
    public SubtitleEvent onConversationLineEarly = new SubtitleEvent();
    public SubtitleEvent onConversationLine = new SubtitleEvent();
    public SubtitleEvent onConversationLineEnd = new SubtitleEvent();
    public SubtitleEvent onConversationLineCancelled = new SubtitleEvent();
    public ResponsesEvent onConversationResponseMenu = new ResponsesEvent();
    public UnityEvent onConversationResponseMenuTimeout = new UnityEvent();
    public TransformEvent onLinkedConversationStart = new TransformEvent();
}
```

**BarkEvents**:

```csharp
public class BarkEvents
{
    public TransformEvent onBarkStart = new TransformEvent();
    public TransformEvent onBarkEnd = new TransformEvent();
    public SubtitleEvent onBarkLine = new SubtitleEvent();
}
```

**SequenceEvents**:

```csharp
public class SequenceEvents
{
    public TransformEvent onSequenceStart = new TransformEvent();
    public TransformEvent onSequenceEnd = new TransformEvent();
}
```

**QuestEvents**:

```csharp
public class QuestEvents
{
    public StringEvent onQuestStateChange = new StringEvent();
    public StringEvent onQuestTrackingEnabled = new StringEvent();
    public StringEvent onQuestTrackingDisabled = new StringEvent();
    public UnityEvent onUpdateQuestTracker = new UnityEvent();
}
```

**PauseEvents**:

```csharp
public class PauseEvents
{
    public UnityEvent onDialogueSystemPause = new UnityEvent();
    public UnityEvent onDialogueSystemUnpause = new UnityEvent();
}
```

#### Uso en Inspector

1. Agregar componente `DialogueSystemEvents` al DialogueManager
2. Asignar métodos en Inspector usando Unity Events
3. Los eventos se invocan automáticamente

**Ejemplo**:

```csharp
// En un script personalizado
public class MyDialogueHandler : MonoBehaviour
{
    void OnEnable()
    {
        var events = DialogueManager.instance.GetComponent<DialogueSystemEvents>();
        if (events != null)
        {
            events.conversationEvents.onConversationStart.AddListener(OnConversationStart);
            events.conversationEvents.onConversationEnd.AddListener(OnConversationEnd);
        }
    }
    
    void OnConversationStart(Transform actor)
    {
        Debug.Log($"Conversation started with {actor.name}");
    }
    
    void OnConversationEnd(Transform actor)
    {
        Debug.Log($"Conversation ended with {actor.name}");
    }
}
```

#### DialogueSystemMessages - Mensajes Broadcast

**Ubicación**: `Scripts/Manager/DialogueSystemMessages.cs`

**Propósito**: Constantes de nombres de mensajes para usar con `SendMessage`/`BroadcastMessage`.

**Mensajes Disponibles**:

```csharp
public class DialogueSystemMessages
{
    // Conversación
    public const string OnConversationStart = "OnConversationStart";
    public const string OnConversationEnd = "OnConversationEnd";
    public const string OnConversationCancelled = "OnConversationCancelled";
    public const string OnConversationLineEarly = "OnConversationLineEarly";
    public const string OnConversationLine = "OnConversationLine";
    public const string OnConversationLineEnd = "OnConversationLineEnd";
    public const string OnConversationLineCancelled = "OnConversationLineCancelled";
    public const string OnConversationResponseMenu = "OnConversationResponseMenu";
    public const string OnConversationTimeout = "OnConversationTimeout";
    public const string OnLinkedConversationStart = "OnLinkedConversationStart";
    
    // Bark
    public const string OnBarkStart = "OnBarkStart";
    public const string OnBarkEnd = "OnBarkEnd";
    public const string OnBarkLine = "OnBarkLine";
    
    // Sequence
    public const string OnSequenceStart = "OnSequenceStart";
    public const string OnSequenceEnd = "OnSequenceEnd";
    public const string OnSequencerMessage = "OnSequencerMessage";
    
    // Quest
    public const string OnQuestStateChange = "OnQuestStateChange";
    public const string OnQuestEntryStateChange = "OnQuestEntryStateChange";
    public const string OnQuestTrackingEnabled = "OnQuestTrackingEnabled";
    public const string OnQuestTrackingDisabled = "OnQuestTrackingDisabled";
    public const string UpdateTracker = "UpdateTracker";
    
    // Sistema
    public const string OnDialogueSystemPause = "OnDialogueSystemPause";
    public const string OnDialogueSystemUnpause = "OnDialogueSystemUnpause";
    public const string OnShowAlert = "OnShowAlert";
}
```

**Uso**:

```csharp
// En cualquier MonoBehaviour
public void OnConversationStart(Transform actor)
{
    Debug.Log($"Conversation started with {actor.name}");
}

// El sistema invoca automáticamente usando SendMessage/BroadcastMessage
```

#### Integración con Unity Events

**Ventajas**:
- Configuración visual en Inspector
- No requiere código
- Fácil de usar para diseñadores

**Desventajas**:
- Menos flexible que código
- Más difícil de debuggear

---

## FASE 0.8: Integraciones y Extensiones

### Integraciones con Otros Sistemas

#### Timeline Integration

**Ubicación**: `Scripts/Options/Timeline/Sequencer/SequencerCommandTimeline.cs`

**Propósito**: Permite controlar Unity Timeline desde secuencias de diálogo.

**Uso**:

```
Timeline(PlayableDirectorName, [play|stop|pause|resume])
```

**Ejemplo**:

```
Timeline(MyDirector, play)
```

#### Cinemachine Integration

**Ubicación**: `Scripts/Options/Cinemachine/Sequencer Commands/`

**Comandos Disponibles**:
- `SequencerCommandCinemachineZoom.cs`: Control de zoom
- `SequencerCommandCinemachineTarget.cs`: Cambiar target
- `SequencerCommandCinemachinePriority.cs`: Cambiar prioridad

**Uso**:

```
CinemachineZoom(Camera1, 5, 2)
CinemachineTarget(Camera1, Speaker)
CinemachinePriority(Camera1, 10)
```

#### Input System Integration

**Soporte**: Unity Input System (nuevo) y Input Manager (legacy)

**Características**:
- Detección automática de sistema de input
- Soporte para gamepad, teclado, touch
- Configuración en DialogueManager

#### TextMesh Pro Integration

**Soporte**: TextMesh Pro (opcional)

**Características**:
- Soporte para `TextMeshProUGUI` en UI
- `UITextField` detecta automáticamente TextMesh Pro
- Rich text tags de TextMesh Pro

**Activación**:
- Definir scripting symbol `TMP_PRESENT`
- Importar TextMesh Pro package
- El sistema detecta automáticamente

#### Otros Plugins de Pixel Crushers

**Common Library**:
- Message System
- Save System
- UI utilities
- Text utilities

**Quest Machine** (plugin separado):
- Sistema de quests avanzado
- Integración con Dialogue System

**Love/Hate** (plugin separado):
- Sistema de relaciones
- Integración con Dialogue System

### Sistema de Extensibilidad

#### Custom Sequencer Commands

**Base**: `SequencerCommand`

**Ubicación**: `Scripts/MVC/Sequencer/Commands/SequencerCommand.cs`

**Crear Comando Personalizado**:

```csharp
using PixelCrushers.DialogueSystem;

[SequencerCommand("MyCustomCommand")]
public class SequencerCommandMyCustom : SequencerCommand
{
    public void Start()
    {
        // Obtener parámetros
        string param1 = GetParameter(0);  // Primer parámetro
        string param2 = GetParameter(1);  // Segundo parámetro
        
        // Ejecutar lógica
        DoSomething(param1, param2);
        
        // Indicar que terminó
        Stop();
    }
    
    void DoSomething(string param1, string param2)
    {
        // Tu lógica aquí
        Debug.Log($"MyCustomCommand: {param1}, {param2}");
    }
}
```

**Uso en Secuencia**:

```
MyCustomCommand(param1, param2)
```

**Atributos**:

```csharp
[SequencerCommand("CommandName", "Category/Subcategory")]
public class SequencerCommandMyCustom : SequencerCommand
{
    // ...
}
```

**Comandos Built-in** (ejemplos):

- `Animation`: Reproducir animación
- `Audio`: Reproducir audio
- `Camera`: Cambiar cámara
- `Delay`: Esperar tiempo
- `Fade`: Fade in/out
- `MoveTo`: Mover objeto
- `LookAt`: Mirar objeto
- `LoadLevel`: Cargar nivel
- `TextInput`: Input de texto
- `Voice`: Reproducir voz
- Y muchos más...

#### Custom UI

**Interfaz Base**: `IDialogueUI`

**Crear UI Personalizada**:

```csharp
using PixelCrushers.DialogueSystem;

public class MyCustomDialogueUI : MonoBehaviour, IDialogueUI
{
    public event EventHandler<SelectedResponseEventArgs> SelectedResponseHandler;
    
    public void Open()
    {
        // Mostrar UI
    }
    
    public void Close()
    {
        // Ocultar UI
    }
    
    public void ShowSubtitle(Subtitle subtitle)
    {
        // Mostrar subtítulo
    }
    
    public void HideSubtitle(Subtitle subtitle)
    {
        // Ocultar subtítulo
    }
    
    public void ShowResponses(Subtitle subtitle, Response[] responses, float timeout)
    {
        // Mostrar respuestas
    }
    
    public void HideResponses()
    {
        // Ocultar respuestas
    }
    
    // ... más métodos requeridos
}
```

**O usar AbstractDialogueUI**:

```csharp
public class MyCustomDialogueUI : AbstractDialogueUI
{
    public override AbstractUIRoot uiRootControls { get; }
    public override AbstractDialogueUIControls dialogueControls { get; }
    public override AbstractUIQTEControls qteControls { get; }
    public override AbstractUIAlertControls alertControls { get; }
    
    // Implementar métodos abstractos
}
```

#### Custom Bark UI

**Interfaz Base**: `IBarkUI`

**Crear Bark UI Personalizada**:

```csharp
using PixelCrushers.DialogueSystem;

public class MyCustomBarkUI : MonoBehaviour, IBarkUI
{
    public bool isPlaying { get; private set; }
    
    public void Bark(Subtitle subtitle)
    {
        // Mostrar bark
        isPlaying = true;
    }
    
    public void Hide()
    {
        // Ocultar bark
        isPlaying = false;
    }
}
```

#### Sistema de Plugins

**No hay sistema de plugins formal**, pero se puede extender mediante:

1. **Custom Sequencer Commands**: Agregar comandos personalizados
2. **Custom UI**: Crear implementaciones de UI
3. **Custom Lua Functions**: Agregar funciones Lua personalizadas
4. **Hooks y Callbacks**: Usar eventos y mensajes

#### Custom Lua Functions

**Registrar Función Lua Personalizada**:

```csharp
using PixelCrushers.DialogueSystem;

public class MyCustomLuaFunctions : MonoBehaviour
{
    void OnEnable()
    {
        Lua.RegisterFunction("MyCustomFunction", this, 
            SymbolExtensions.GetMethodInfo(() => MyCustomFunction(string.Empty)));
    }
    
    void OnDisable()
    {
        Lua.UnregisterFunction("MyCustomFunction");
    }
    
    public void MyCustomFunction(string param)
    {
        Debug.Log($"MyCustomFunction called with: {param}");
    }
}
```

**Uso en Lua**:

```lua
MyCustomFunction("test")
```

#### Hooks y Callbacks Disponibles

**Delegates**:

```csharp
// QuestLog
public static StringToQuestStateDelegate StringToState;
public static QuestStateToStringDelegate StateToString;
public static CurrentQuestStateDelegate CurrentQuestStateOverride;
public static SetQuestStateDelegate SetQuestStateOverride;

// DialogueManager
public static System.Action<Transform> onConversationStart;
public static System.Action<Transform> onConversationEnd;
```

**Mensajes**:

- Todos los mensajes en `DialogueSystemMessages`
- Usar `SendMessage` o `BroadcastMessage`

**Eventos**:

- `DialogueSystemEvents` component
- Unity Events en Inspector

---

## Comparación con Nuestra Implementación

### Sistema de Quests

| Aspecto | Pixel Crushers | Nuestra BD v2 |
|---------|----------------|---------------|
| **Almacenamiento** | Lua `Item[]` table | No implementado (no necesario) |
| **Estados** | Unassigned, Active, Success, Failure, Abandoned | N/A |
| **Tracking** | Sistema de tracking integrado | N/A |
| **Quest Entries** | Sub-tareas de quests | N/A |
| **Integración** | Integrado con diálogos | N/A |

**Nota**: Nuestro sistema no necesita quests porque:
- Es un sistema educativo (no un juego RPG)
- Las "misiones" son las sesiones de juicio
- El tracking se hace mediante `decisiones_dialogo_v2`

### Sistema de Localización

| Aspecto | Pixel Crushers | Nuestra BD v2 |
|---------|----------------|---------------|
| **Almacenamiento** | TextTable (ScriptableObject) | Base de datos (tabla `textos_localizados` o similar) |
| **Idiomas** | Lista de idiomas en TextTable | Tabla de idiomas en BD |
| **Cambio Runtime** | `Localization.language = "es"` | API REST para cambiar idioma |
| **Formato** | ScriptableObject | JSON o campos separados |
| **Multi-idioma** | Sí | Sí (si se implementa) |

**Recomendación para Nuestra BD v2**:

```sql
CREATE TABLE `textos_localizados` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tabla` VARCHAR(50) NOT NULL,  -- 'dialogos', 'nodos', 'respuestas'
  `registro_id` BIGINT UNSIGNED NOT NULL,
  `campo` VARCHAR(50) NOT NULL,  -- 'nombre', 'contenido', 'texto'
  `idioma` VARCHAR(10) NOT NULL,  -- 'es', 'en', 'fr'
  `texto` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_localizacion` (`tabla`, `registro_id`, `campo`, `idioma`),
  INDEX `idx_tabla_registro` (`tabla`, `registro_id`),
  INDEX `idx_idioma` (`idioma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sistema de Eventos

| Aspecto | Pixel Crushers | Nuestra BD v2 |
|---------|----------------|---------------|
| **Eventos** | Unity Events + SendMessage | Webhooks + Server-Sent Events (SSE) |
| **Configuración** | Inspector de Unity | Configuración en servidor |
| **Multi-usuario** | No (single-player) | Sí (broadcast a múltiples clientes) |
| **Persistencia** | No | Sí (registro en BD) |

**Recomendación para Nuestra BD v2**:

```sql
CREATE TABLE `eventos_dialogo` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_dialogo_id` BIGINT UNSIGNED NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,  -- 'conversation_start', 'conversation_end', etc.
  `datos` JSON NULL,
  `usuario_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sesion_dialogo` (`sesion_dialogo_id`),
  INDEX `idx_tipo` (`tipo`),
  FOREIGN KEY (`sesion_dialogo_id`) REFERENCES `sesiones_dialogos_v2`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sistema de Extensiones

| Aspecto | Pixel Crushers | Nuestra BD v2 |
|---------|----------------|---------------|
| **Custom Commands** | Sequencer Commands | API REST endpoints personalizados |
| **Custom UI** | Implementar `IDialogueUI` | Frontend personalizado (React, Vue, etc.) |
| **Custom Functions** | Funciones Lua | Funciones en backend (Laravel) |
| **Plugins** | Scripts de Unity | Paquetes Composer (PHP) |

---

## Resumen de Funcionalidades

### Pixel Crushers - Funcionalidades Avanzadas

1. ✅ **Sistema de Quests**: Completo, integrado con diálogos
2. ✅ **Sistema de Localización**: TextTable, cambio en runtime
3. ✅ **Sistema de Eventos**: Unity Events + SendMessage
4. ✅ **Integraciones**: Timeline, Cinemachine, Input System, TextMesh Pro
5. ✅ **Extensibilidad**: Custom commands, custom UI, custom Lua functions

### Nuestra BD v2 - Funcionalidades Únicas

1. ✅ **Sistema de Evaluación**: Campos de evaluación de profesor
2. ✅ **Sistema de Audio**: Grabaciones MP3
3. ✅ **Sistema de Historial**: Tracking completo de decisiones
4. ✅ **Multi-usuario**: Soporte nativo para múltiples usuarios
5. ✅ **Sistema de Sesiones**: Sesiones vinculadas a juicios
6. ✅ **Usuarios No Registrados**: Flujo para usuarios anónimos

### Funcionalidades a Implementar (Opcional)

1. **Localización**: Tabla de textos localizados
2. **Eventos**: Sistema de eventos y webhooks
3. **Extensiones**: API para extensiones personalizadas

---

## Próximos Pasos

1. **FASE 0.9**: Análisis de Rendimiento y Optimización
2. **FASE 1.0**: Inicio de Implementación

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
