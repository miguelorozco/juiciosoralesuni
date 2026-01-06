# FASE 0.5: Análisis del Sistema de Actores y Personajes - Pixel Crushers Dialogue System

## 📋 Índice

1. [Sistema de Actores](#sistema-de-actores)
2. [Integración con Personajes del Juego](#integración-con-personajes-del-juego)

---

## Sistema de Actores

### DialogueActor - Componente Principal

**Ubicación**: `Scripts/MVC/Actor/DialogueActor.cs`

**Propósito**: Componente que permite asociar un GameObject de Unity con un actor del diálogo, permitiendo personalización por actor.

#### Propiedades Principales

```csharp
public class DialogueActor : MonoBehaviour
{
    // Nombre del actor
    [ActorPopup(true)]
    public string actor;  // Nombre del actor en la base de datos
    
    // Nombre para datos persistentes
    public string persistentDataName;
    
    // Retratos
    public Texture2D portrait;        // Retrato (Texture2D)
    public Sprite spritePortrait;     // Retrato (Sprite)
    
    // Cámaras
    public GameObject cameraAngles;   // Ángulos de cámara personalizados
    
    // Audio
    public AudioSource audioSource;   // Audio Source para secuencias
    
    // Configuración de Bark UI
    public BarkUISettings barkUISettings;
    
    // Configuración de Standard Dialogue UI
    public StandardDialogueUISettings standardDialogueUISettings;
}
```

#### Configuración de Bark UI

```csharp
[Serializable]
public class BarkUISettings
{
    [Tooltip("If a prefab, Dialogue Actor will instantiate it at runtime.")]
    public AbstractBarkUI barkUI;
    
    [Tooltip("If instantiating bark UI prefab, offset it this far from Dialogue Actor's origin.")]
    public Vector3 barkUIOffset = new Vector3(0, 2, 0);
}
```

**Funcionalidad**:
- Permite asignar un prefab de Bark UI por actor
- Si es prefab, se instancia automáticamente en runtime
- Offset configurable para posicionamiento

#### Configuración de Standard Dialogue UI

```csharp
[Serializable]
public class StandardDialogueUISettings
{
    // Panel de subtítulo
    public SubtitlePanelNumber subtitlePanelNumber = SubtitlePanelNumber.Default;
    public StandardUISubtitlePanel customSubtitlePanel = null;
    public Vector3 customSubtitlePanelOffset = new Vector3(0, 0, 0);
    
    // Panel de menú
    public MenuPanelNumber menuPanelNumber = MenuPanelNumber.Default;
    public StandardUIMenuPanel customMenuPanel = null;
    public Vector3 customMenuPanelOffset = new Vector3(0, 0, 0);
    public UseMenuPanelFor useMenuPanelFor = UseMenuPanelFor.OnlyMe;
    
    // Retrato animado
    public RuntimeAnimatorController portraitAnimatorController;
    
    // Color de subtítulo
    public bool setSubtitleColor = false;
    public bool applyColorToPrependedName = false;
    public string prependActorNameSeparator = ": ";
    public string prependActorNameFormat = "{0}{1}";
    public Color subtitleColor = Color.white;
}
```

**Opciones de Panel**:
- `Default`: Usa el panel por defecto del Dialogue UI
- `Panel1`, `Panel2`, etc.: Usa paneles específicos
- `Custom`: Usa un panel personalizado

**UseMenuPanelFor**:
- `OnlyMe`: Solo usa este panel cuando este actor es el respondent
- `MeAndResponsesToMe`: Usa este panel cuando este actor es el respondent o el personaje al que se está respondiendo

#### Métodos Principales

```csharp
// Obtener nombre del actor
public virtual string GetActorName()
{
    var actorName = string.IsNullOrEmpty(actor) ? name : actor;
    var result = CharacterInfo.GetLocalizedDisplayNameInDatabase(...);
    // Parsear tags [lua], [var], [em]
    if (actorName.Contains("[lua") || actorName.Contains("[var") || actorName.Contains("[em"))
    {
        return FormattedText.Parse(actorName, ...).text;
    }
    return actorName;
}

// Obtener retrato
public virtual Sprite GetPortraitSprite()
{
    return UITools.GetSprite(portrait, spritePortrait);
}

// Ajustar color de subtítulo
public virtual string AdjustSubtitleColor(Subtitle subtitle)
{
    if (!standardDialogueUISettings.setSubtitleColor) return text;
    
    if (standardDialogueUISettings.applyColorToPrependedName)
    {
        // Aplicar color solo al nombre
        var coloredName = UITools.WrapTextInColor(
            subtitle.speakerInfo.Name + standardDialogueUISettings.prependActorNameSeparator,
            standardDialogueUISettings.subtitleColor
        );
        return string.Format(standardDialogueUISettings.prependActorNameFormat, coloredName, text);
    }
    else
    {
        // Aplicar color a todo el texto
        return UITools.WrapTextInColor(text, standardDialogueUISettings.subtitleColor);
    }
}
```

#### Registro de Actores

```csharp
protected virtual void OnEnable()
{
    if (string.IsNullOrEmpty(actor)) return;
    StartCoroutine(RegisterAtEndOfFrame());
}

protected IEnumerator RegisterAtEndOfFrame()
{
    yield return new WaitForEndOfFrame();
    CharacterInfo.RegisterActorTransform(actor, transform);
}

protected virtual void OnDisable()
{
    if (string.IsNullOrEmpty(actor)) return;
    var registeredTransform = CharacterInfo.GetRegisteredActorTransform(actor);
    if (transform == registeredTransform)
    {
        CharacterInfo.UnregisterActorTransform(actor, transform);
        // Limpiar cache de conversaciones activas
    }
}
```

**Proceso**:
1. Al habilitarse, registra el Transform del actor
2. Espera al final del frame para evitar problemas de inicialización
3. Al deshabilitarse, desregistra el Transform
4. Limpia el cache de conversaciones activas

#### Métodos Estáticos

```csharp
// Buscar DialogueActor en un Transform
public static DialogueActor GetDialogueActorComponent(Transform t)
{
    if (t == null) return null;
    return t.GetComponent<DialogueActor>() 
        ?? t.GetComponentInChildren<DialogueActor>() 
        ?? t.GetComponentInParent<DialogueActor>();
}

// Obtener nombre del actor desde Transform
public static string GetActorName(Transform t)
{
    if (t == null) return string.Empty;
    var dialogueActor = GetDialogueActorComponent(t);
    return (dialogueActor != null && dialogueActor.isActiveAndEnabled) 
        ? dialogueActor.GetName()
        : CharacterInfo.GetLocalizedDisplayNameInDatabase(t.name);
}
```

### CharacterInfo - Información de Personajes

**Ubicación**: `Scripts/MVC/Model/Logic/Shared/CharacterInfo.cs`

**Propósito**: Clase que contiene información sobre un participante de la conversación.

#### Propiedades

```csharp
public class CharacterInfo
{
    public int id;                    // ID del actor
    public string nameInDatabase;     // Nombre en la base de datos
    public CharacterType characterType; // Tipo (PC o NPC)
    public Transform transform;       // Transform del GameObject
    public Sprite portrait;           // Retrato
    public string Name { get; set; }  // Nombre para mostrar
    
    public bool isPlayer { get { return characterType == CharacterType.PC; } }
    public bool isNPC { get { return characterType == CharacterType.NPC; } }
}
```

#### Registro de Transforms

```csharp
// Diccionario estático de actores registrados
private static Dictionary<string, Transform> actorTransforms = new Dictionary<string, Transform>();

public static void RegisterActorTransform(string actorName, Transform transform)
{
    if (string.IsNullOrEmpty(actorName)) return;
    actorTransforms[actorName] = transform;
}

public static Transform GetRegisteredActorTransform(string actorName)
{
    if (string.IsNullOrEmpty(actorName)) return null;
    return actorTransforms.ContainsKey(actorName) ? actorTransforms[actorName] : null;
}

public static void UnregisterActorTransform(string actorName, Transform transform)
{
    if (string.IsNullOrEmpty(actorName)) return;
    var registeredTransform = GetRegisteredActorTransform(actorName);
    if (transform == registeredTransform)
    {
        actorTransforms.Remove(actorName);
    }
}
```

**Uso**:
- Permite buscar el Transform de un actor por nombre
- Útil para secuencias y comandos que necesitan referenciar actores
- Se actualiza automáticamente cuando DialogueActor se habilita/deshabilita

#### Métodos de Campos

```csharp
// Obtener campo del actor
public Field GetField(string title)
{
    var actor = DialogueManager.masterDatabase.GetActor(id);
    return (actor != null) ? actor.fields.Find(field => field.title == title) : null;
}

// Obtener texto de campo
public string GetFieldText(string title)
{
    var field = GetField(title);
    return (field != null) ? field.value : string.Empty;
}

// Obtener bool de campo
public bool GetFieldBool(string title)
{
    var field = GetField(title);
    return (field != null) ? string.Equals(field.value, "true", ...) : false;
}

// Obtener int de campo
public int GetFieldInt(string title)
{
    var field = GetField(title);
    return (field != null) ? SafeConvert.ToInt(field.value) : 0;
}
```

### Sistema de Retratos/Portraits

#### Retratos en DialogueActor

**Tipos de Retratos**:
- `Texture2D portrait`: Retrato como textura (legacy)
- `Sprite spritePortrait`: Retrato como sprite (recomendado)

**Obtención de Retrato**:

```csharp
public virtual Sprite GetPortraitSprite()
{
    return UITools.GetSprite(portrait, spritePortrait);
}
```

**Prioridad**:
1. `spritePortrait` (si está asignado)
2. `portrait` convertido a Sprite (si está asignado)
3. Retrato del actor en la base de datos
4. `null` si no hay retrato

#### Retratos Animados

**Configuración**:

```csharp
public RuntimeAnimatorController portraitAnimatorController;
```

**Uso**:
- Asignar Animator Controller al `portraitImage` en el panel de subtítulo
- El sistema activará triggers automáticamente
- Útil para expresiones faciales animadas

**En StandardUISubtitlePanel**:

```csharp
[Tooltip("Check Dialogue Actors for portrait animator controllers.")]
public bool useAnimatedPortraits = false;

private Animator m_portraitAnimator = null;
protected virtual Animator animator 
{ 
    get 
    { 
        if (m_portraitAnimator == null && portraitImage != null)
            m_portraitAnimator = portraitImage.GetComponent<Animator>();
        return m_portraitAnimator;
    }
}
```

#### Retratos Alternativos

**En CharacterInfo**:

```csharp
public Sprite GetPicOverride(int picNum)
{
    if (picNum < 2) return portrait;
    int alternatePortraitIndex = picNum - 2;
    Actor actor = DialogueManager.masterDatabase.GetActor(id);
    return ((actor != null) && (alternatePortraitIndex < actor.alternatePortraits.Count))
        ? UITools.CreateSprite(actor.alternatePortraits[alternatePortraitIndex])
        : portrait;
}
```

**Uso en Diálogo**:
- Usar tag `[pic=2]` en el texto del diálogo para usar retrato alternativo 1
- Usar tag `[pic=3]` para retrato alternativo 2
- etc.

### Override de UI por Actor

#### Paneles de Subtítulo Personalizados

**Configuración**:

```csharp
public SubtitlePanelNumber subtitlePanelNumber = SubtitlePanelNumber.Default;
public StandardUISubtitlePanel customSubtitlePanel = null;
public Vector3 customSubtitlePanelOffset = new Vector3(0, 0, 0);
```

**Opciones**:
- `Default`: Usa el panel por defecto
- `Panel1`, `Panel2`, etc.: Usa paneles específicos
- `Custom`: Usa un panel personalizado (prefab o instancia)

**Instanciación de Prefabs**:

```csharp
protected virtual void SetupDialoguePanels()
{
    if (standardDialogueUISettings.subtitlePanelNumber == SubtitlePanelNumber.Custom &&
        standardDialogueUISettings.customSubtitlePanel != null &&
        Tools.IsPrefab(standardDialogueUISettings.customSubtitlePanel.gameObject))
    {
        // Instanciar prefab
        var go = Instantiate(standardDialogueUISettings.customSubtitlePanel.gameObject, 
            transform.position, transform.rotation) as GameObject;
        go.transform.SetParent(transform);
        go.transform.localPosition = standardDialogueUISettings.customSubtitlePanelOffset;
        go.transform.localRotation = Quaternion.identity;
        standardDialogueUISettings.customSubtitlePanel = go.GetComponent<StandardUISubtitlePanel>();
    }
}
```

#### Paneles de Menú Personalizados

**Configuración**:

```csharp
public MenuPanelNumber menuPanelNumber = MenuPanelNumber.Default;
public StandardUIMenuPanel customMenuPanel = null;
public Vector3 customMenuPanelOffset = new Vector3(0, 0, 0);
public UseMenuPanelFor useMenuPanelFor = UseMenuPanelFor.OnlyMe;
```

**UseMenuPanelFor**:
- `OnlyMe`: Solo cuando este actor es el respondent
- `MeAndResponsesToMe`: Cuando este actor es el respondent o el personaje al que se está respondiendo

### Sistema de Bark (Comentarios Breves)

#### IBarkUI - Interfaz Base

**Ubicación**: `Scripts/UI/Abstract/Bark/AbstractBarkUI.cs`

```csharp
public interface IBarkUI
{
    bool isPlaying { get; }
    void Bark(Subtitle subtitle);
    void Hide();
}
```

#### AbstractBarkUI - Clase Base

```csharp
public abstract class AbstractBarkUI : MonoBehaviour, IBarkUI
{
    public abstract bool isPlaying { get; }
    public abstract void Bark(Subtitle subtitle);
    public abstract void Hide();
}
```

**Implementaciones**:
- `StandardBarkUI`: Implementación para Standard UI
- `UnityUIBarkUI`: Implementación para Unity UI (legacy)

#### BarkController - Controlador de Barks

**Ubicación**: `Scripts/MVC/Controller/BarkController.cs`

**Funcionalidad**:
- Gestiona la reproducción de barks
- Maneja prioridades de barks
- Controla el orden de reproducción

**Métodos Principales**:

```csharp
public static class BarkController
{
    // Hacer que un personaje haga bark
    public static Sequencer Bark(string conversation, Transform speaker, Transform listener, 
        int entryID = -1, string entryTitle = null, string barkText = null, 
        string sequence = null, BarkSubtitleSetting subtitleSetting = BarkSubtitleSetting.SameAsDialogueManager,
        int priority = 0, bool skipIfNoValidEntry = false)
    {
        // 1. Verificar prioridad
        if (priority < GetSpeakerCurrentBarkPriority(speaker)) return null;
        
        // 2. Obtener entrada de diálogo
        DialogueEntry entry = GetBarkEntry(conversation, entryID, entryTitle, barkText);
        
        // 3. Crear subtítulo
        Subtitle subtitle = CreateBarkSubtitle(entry, speaker, listener, barkText);
        
        // 4. Mostrar bark
        ShowBark(subtitle, subtitleSetting);
        
        // 5. Ejecutar secuencia
        if (!string.IsNullOrEmpty(sequence))
        {
            LastSequencer = Sequencer.PlaySequence(sequence, speaker, listener);
        }
        
        return LastSequencer;
    }
}
```

**BarkOrder**:
- `Random`: Reproducir barks en orden aleatorio
- `Sequential`: Reproducir en orden secuencial
- `FirstValid`: Detener después de encontrar la primera entrada válida

**BarkSubtitleSetting**:
- `SameAsDialogueManager`: Usar la misma configuración que el Dialogue Manager
- `Show`: Siempre mostrar usando el Bark UI del personaje
- `Hide`: Nunca mostrar

#### BarkHistory - Historial de Barks

```csharp
public class BarkHistory
{
    public BarkOrder order;
    public int index = 0;
    public List<int> entries = null;
    
    public int GetNextIndex(int numEntries)
    {
        if (order == BarkOrder.Random)
        {
            // Mezclar lista y evitar repeticiones secuenciales
            if (entries == null || entries.Count != numEntries)
            {
                entries = new List<int>();
                for (int i = 0; i < numEntries; i++) entries.Add(i);
                entries.Shuffle();
            }
            return entries[index++];
        }
        else
        {
            // Secuencial
            int result = (index % numEntries);
            index = ((index + 1) % numEntries);
            return result;
        }
    }
}
```

---

## Integración con Personajes del Juego

### Asociación de Personajes con Actores

#### Método 1: DialogueActor Component

**Pasos**:
1. Agregar componente `DialogueActor` al GameObject del personaje
2. Asignar el nombre del actor en el campo `actor`
3. Configurar opciones personalizadas (retratos, paneles, etc.)

**Ejemplo**:

```csharp
// En el GameObject del personaje
DialogueActor dialogueActor = gameObject.AddComponent<DialogueActor>();
dialogueActor.actor = "Player";  // Nombre del actor en la base de datos
dialogueActor.spritePortrait = playerPortraitSprite;
dialogueActor.standardDialogueUISettings.subtitlePanelNumber = SubtitlePanelNumber.Panel1;
```

#### Método 2: Nombre del GameObject

**Si no hay DialogueActor**:
- El sistema usa el nombre del GameObject
- Busca un actor con ese nombre en la base de datos
- Si no encuentra, usa el nombre del GameObject como fallback

**Ejemplo**:
- GameObject llamado "Player" → busca actor "Player" en la base de datos
- Si no existe, usa "Player" como nombre

#### Método 3: Registro Manual

```csharp
// Registrar manualmente
CharacterInfo.RegisterActorTransform("Player", playerTransform);

// Obtener transform registrado
Transform playerTransform = CharacterInfo.GetRegisteredActorTransform("Player");
```

### Sistema de Triggers

#### DialogueSystemTrigger - Trigger Principal

**Ubicación**: `Scripts/Triggers/Triggers/DialogueSystemTrigger.cs`

**Propósito**: Trigger general que puede ejecutar múltiples funciones del Dialogue System.

#### Eventos de Trigger

```csharp
public enum DialogueSystemTriggerEvent
{
    OnUse,              // Cuando se usa el objeto
    OnTriggerEnter,     // Cuando entra un collider
    OnTriggerExit,      // Cuando sale un collider
    OnStart,            // Al iniciar el GameObject
    OnEnable,           // Al habilitarse el componente
    OnDisable,          // Al deshabilitarse el componente
    OnDestroy,          // Al destruirse el GameObject
    OnSaveDataApplied,  // Cuando se aplican datos guardados
    OnBarkEnd,          // Al terminar un bark
    OnConversationStart,// Al iniciar una conversación
    OnConversationEnd,  // Al terminar una conversación
    OnSequenceEnd,      // Al terminar una secuencia
    // ... más eventos
}
```

#### Acciones Disponibles

```csharp
public class DialogueSystemTrigger : MonoBehaviour
{
    // Conversación
    [ConversationPopup(false, true)]
    public string conversation = string.Empty;
    public Transform conversationConversant = null;
    public bool skipIfNoValidEntry = false;
    
    // Bark
    public BarkSource barkSource = BarkSource.None;
    [ConversationPopup(false, true)]
    public string barkConversation = string.Empty;
    public int barkEntryID = -1;
    public string barkEntryTitle;
    public string barkText = string.Empty;
    public string barkTextSequence = string.Empty;
    
    // Alert
    public string alertMessage;
    public float alertDuration = 0;
    
    // Sequence
    [TextArea(1, 10)]
    public string sequence = string.Empty;
    public Transform sequenceSpeaker;
    public Transform sequenceListener;
    
    // Lua
    public string luaCode = string.Empty;
    
    // Quest
    public bool setQuestState = true;
    [QuestPopup]
    public string questName;
    [QuestState]
    public QuestState questState;
    
    // Send Message
    public SendMessageAction[] sendMessages = new SendMessageAction[0];
}
```

#### Condiciones

```csharp
public Condition condition;

// La condición se evalúa antes de ejecutar el trigger
// Si la condición no se cumple, el trigger no se ejecuta
```

**Ejemplo de Uso**:

```csharp
// En un GameObject con DialogueSystemTrigger
DialogueSystemTrigger trigger = GetComponent<DialogueSystemTrigger>();
trigger.trigger = DialogueSystemTriggerEvent.OnUse;
trigger.conversation = "Greeting";
trigger.condition = new Condition("Variable[\"HasMet\"] == true");
```

### Proximidad y Detección

#### ProximitySelector - Selector por Proximidad

**Ubicación**: `Scripts/Triggers/Interaction/ProximitySelector.cs`

**Propósito**: Selector basado en proximidad que permite al jugador moverse cerca y usar objetos.

#### Características

```csharp
public class ProximitySelector : MonoBehaviour
{
    // GUI
    public bool useDefaultGUI = true;
    public GUISkin guiSkin;
    public string guiStyleName = "label";
    public TextAnchor alignment = TextAnchor.UpperCenter;
    public Color color = Color.yellow;
    
    // Reticle
    public Reticle reticle;
    
    // Input
    public KeyCode useKey = KeyCode.Space;
    public string useButton = "Fire2";
    public bool enableTouch = false;
    public ScaledRect touchArea;
    
    // Mensaje
    public string defaultUseMessage = "(spacebar to interact)";
    
    // Opciones
    public bool broadcastToChildren = true;
    public Transform actorTransform = null;
}
```

#### Funcionamiento

1. **Detección**: Detecta objetos con componente `Usable` dentro del rango
2. **Visualización**: Muestra reticle y mensaje de uso
3. **Input**: Espera input del jugador (tecla o botón)
4. **Ejecución**: Envía mensaje `OnUse` al objeto usable

**Proceso**:

```csharp
void Update()
{
    // 1. Buscar objetos usables en rango
    Usable usable = FindUsableInRange();
    
    // 2. Actualizar selección
    if (usable != currentUsable)
    {
        DeselectCurrentUsable();
        SelectUsable(usable);
    }
    
    // 3. Verificar input
    if (Input.GetKeyDown(useKey) || Input.GetButtonDown(useButton))
    {
        UseCurrentUsable();
    }
}

void UseCurrentUsable()
{
    if (currentUsable != null && IsInRange(currentUsable))
    {
        // Enviar mensaje OnUse
        if (broadcastToChildren)
        {
            currentUsable.BroadcastMessage("OnUse", actorTransform, SendMessageOptions.DontRequireReceiver);
        }
        else
        {
            currentUsable.SendMessage("OnUse", actorTransform, SendMessageOptions.DontRequireReceiver);
        }
    }
}
```

#### Usable - Componente de Objeto Usable

**Ubicación**: `Scripts/Triggers/Interaction/Usable.cs`

**Propósito**: Marca un GameObject como usable y proporciona información sobre cómo usarlo.

```csharp
public class Usable : MonoBehaviour
{
    // Nombre y mensaje
    public string overrideName;
    public string overrideUseMessage;
    
    // Distancia máxima
    public float maxUseDistance = 5f;
    
    // Eventos
    public UsableEvents events;
    
    public virtual string GetName()
    {
        if (string.IsNullOrEmpty(overrideName))
        {
            return DialogueActor.GetActorName(transform);
        }
        else if (overrideName.Contains("[lua") || overrideName.Contains("[var"))
        {
            return DialogueManager.GetLocalizedText(FormattedText.Parse(overrideName, ...).text);
        }
        else
        {
            return DialogueManager.GetLocalizedText(overrideName);
        }
    }
    
    public virtual void OnSelectUsable()
    {
        if (events != null && events.onSelect != null) events.onSelect.Invoke();
    }
    
    public virtual void OnDeselectUsable()
    {
        if (events != null && events.onDeselect != null) events.onDeselect.Invoke();
    }
    
    public virtual void OnUseUsable()
    {
        if (events != null && events.onUse != null) events.onUse.Invoke();
    }
}
```

**Eventos**:

```csharp
[Serializable]
public class UsableEvents
{
    public UnityEvent onSelect = new UnityEvent();
    public UnityEvent onDeselect = new UnityEvent();
    public UnityEvent onUse = new UnityEvent();
}
```

### Sistema de Interacción

#### Flujo de Interacción Completo

```
1. Jugador se acerca a un objeto
   ↓
2. ProximitySelector detecta el objeto Usable
   ↓
3. ProximitySelector muestra reticle y mensaje
   ↓
4. Jugador presiona tecla/botón de uso
   ↓
5. ProximitySelector envía mensaje OnUse al Usable
   ↓
6. Usable ejecuta eventos onUse
   ↓
7. DialogueSystemTrigger (si está presente) ejecuta acción
   ↓
8. Se inicia conversación/bark/sequence/etc.
```

#### Ejemplo de Configuración Completa

**GameObject con Usable y DialogueSystemTrigger**:

```csharp
// 1. Agregar Usable
Usable usable = gameObject.AddComponent<Usable>();
usable.overrideName = "Merchant";
usable.overrideUseMessage = "Press E to talk";
usable.maxUseDistance = 3f;

// 2. Agregar DialogueSystemTrigger
DialogueSystemTrigger trigger = gameObject.AddComponent<DialogueSystemTrigger>();
trigger.trigger = DialogueSystemTriggerEvent.OnUse;
trigger.conversation = "MerchantGreeting";

// 3. Agregar Collider (Trigger)
Collider collider = gameObject.AddComponent<SphereCollider>();
collider.isTrigger = true;
collider.radius = 3f;

// 4. Agregar DialogueActor (opcional)
DialogueActor actor = gameObject.AddComponent<DialogueActor>();
actor.actor = "Merchant";
actor.spritePortrait = merchantPortrait;
```

#### RangeTrigger - Trigger por Rango

**Ubicación**: `Scripts/Triggers/RangeTrigger.cs`

**Propósito**: Trigger que se activa cuando un objeto entra en un rango específico.

**Características**:
- Rango configurable
- Detección automática
- Múltiples eventos soportados

### Resumen de Componentes

| Componente | Ubicación | Propósito |
|------------|-----------|-----------|
| `DialogueActor` | MVC/Actor/ | Asociar GameObject con actor |
| `CharacterInfo` | MVC/Model/Logic/Shared/ | Información de personaje |
| `DialogueSystemTrigger` | Triggers/Triggers/ | Trigger general del sistema |
| `ProximitySelector` | Triggers/Interaction/ | Selector por proximidad |
| `Usable` | Triggers/Interaction/ | Marcar objeto como usable |
| `BarkController` | MVC/Controller/ | Controlador de barks |
| `AbstractBarkUI` | UI/Abstract/Bark/ | Interfaz base para Bark UI |

### Resumen de Funcionalidades

| Funcionalidad | Descripción | Componente |
|---------------|-------------|-------------|
| **Asignación de Actores** | Asociar GameObject con actor | `DialogueActor` |
| **Retratos** | Retratos estáticos y animados | `DialogueActor.portrait`, `spritePortrait` |
| **Override de UI** | Paneles personalizados por actor | `DialogueActor.standardDialogueUISettings` |
| **Bark System** | Comentarios breves de personajes | `BarkController`, `IBarkUI` |
| **Triggers** | Sistema de eventos y triggers | `DialogueSystemTrigger` |
| **Proximidad** | Detección y selección por proximidad | `ProximitySelector` |
| **Interacción** | Sistema de objetos usables | `Usable` |
| **Registro** | Registro de transforms de actores | `CharacterInfo.RegisterActorTransform` |

---

## Próximos Pasos (FASE 0.6)

1. **Análisis del Sistema de Almacenamiento**
2. **Análisis de Funcionalidades Avanzadas**

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
