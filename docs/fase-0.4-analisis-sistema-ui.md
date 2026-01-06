# FASE 0.4: Análisis del Sistema de UI - Pixel Crushers Dialogue System

## 📋 Índice

1. [Componentes de UI](#componentes-de-ui)
2. [Sistema de Personalización](#sistema-de-personalización)

---

## Componentes de UI

### Arquitectura del Sistema de UI

El sistema de UI está organizado en capas:

```
IDialogueUI (Interfaz)
    ↓
AbstractDialogueUI (Clase base abstracta)
    ↓
CanvasDialogueUI (Base para Canvas)
    ├── UnityUIDialogueUI (Unity UI - Deprecated)
    ├── StandardDialogueUI (Standard UI - Recomendado)
    └── UIToolkitDialogueUI (UI Toolkit)
```

### IDialogueUI - Interfaz Base

**Ubicación**: `Scripts/MVC/View/Dialogue/IDialogueUI.cs`

**Métodos Requeridos**:

```csharp
public interface IDialogueUI
{
    // Eventos
    event EventHandler<SelectedResponseEventArgs> SelectedResponseHandler;
    
    // Conversación
    void Open();
    void Close();
    
    // Subtítulos
    void ShowSubtitle(Subtitle subtitle);
    void HideSubtitle(Subtitle subtitle);
    
    // Respuestas
    void ShowResponses(Subtitle subtitle, Response[] responses, float timeout);
    void HideResponses();
    
    // QTE
    void ShowQTEIndicator(int index);
    void HideQTEIndicator(int index);
    
    // Alertas
    void ShowAlert(string message, float duration);
    void HideAlert();
}
```

**Responsabilidades**:
- Define el contrato que todas las implementaciones de UI deben cumplir
- Permite intercambiar diferentes sistemas de UI sin cambiar el código del diálogo
- Patrón Strategy: diferentes implementaciones, misma interfaz

### AbstractDialogueUI - Clase Base Abstracta

**Ubicación**: `Scripts/UI/Abstract/Dialogue/AbstractDialogueUI.cs`

**Propiedades Abstractas**:

```csharp
public abstract class AbstractDialogueUI : MonoBehaviour, IDialogueUI
{
    public abstract AbstractUIRoot uiRootControls { get; }
    public abstract AbstractDialogueUIControls dialogueControls { get; }
    public abstract AbstractUIQTEControls qteControls { get; }
    public abstract AbstractUIAlertControls alertControls { get; }
}
```

**Funcionalidad Común**:
- Manejo de estado `isOpen`
- Coordinación entre controles
- Gestión de alertas
- Eventos de conversación

**Métodos Virtuales**:

```csharp
public virtual void Open()
{
    dialogueControls.ShowPanel();
    uiRootControls.Show();
    isOpen = true;
}

public virtual void Close()
{
    dialogueControls.Hide();
    if (!AreNonDialogueControlsVisible) uiRootControls.Hide();
    isOpen = false;
}

public virtual void ShowSubtitle(Subtitle subtitle)
{
    SetSubtitle(subtitle, true);
}

public virtual void HideSubtitle(Subtitle subtitle)
{
    SetSubtitle(subtitle, false);
}
```

### UnityUIDialogueUI - Implementación Unity UI

**Ubicación**: `Scripts/UI/Unity UI/Dialogue/UnityUIDialogueUI.cs`

**Estado**: ⚠️ **Deprecated** - Usar `StandardDialogueUI` en su lugar

**Características**:
- Basado en Unity UI (Canvas)
- Controles directos de UI.Text, UI.Button, etc.
- Más simple pero menos flexible

**Estructura**:

```csharp
public class UnityUIDialogueUI : CanvasDialogueUI
{
    public UnityUIRoot unityUIRoot;
    public UnityUIDialogueControls dialogue;
    public UnityEngine.UI.Graphic[] qteIndicators;
    public UnityUIAlertControls alert;
    
    public bool autoFocus = false;
    public bool allowStealFocus = false;
    public bool findActorOverrides = true;
}
```

**Controles**:
- `UnityUISubtitleControls` - Subtítulos NPC/PC
- `UnityUIResponseMenuControls` - Menú de respuestas
- `UnityUIAlertControls` - Alertas

### StandardDialogueUI - Implementación Standard UI (Recomendado)

**Ubicación**: `Scripts/UI/Standard/Dialogue/StandardDialogueUI.cs`

**Estado**: ✅ **Recomendado** - Sistema moderno y flexible

**Características**:
- Sistema de paneles modular
- Múltiples paneles de subtítulo
- Múltiples paneles de menú
- Animaciones integradas
- Sistema de efectos visuales
- Soporte para temas

**Estructura**:

```csharp
public class StandardDialogueUI : CanvasDialogueUI, IStandardDialogueUI
{
    public StandardUIAlertControls alertUIElements;
    public StandardUIDialogueControls conversationUIElements;
    public StandardUIQTEControls QTEIndicatorElements;
    
    public bool addEventSystemIfNeeded = true;
    public bool verifyPanelAssignments = true;
}
```

**Ventajas sobre UnityUIDialogueUI**:
- ✅ Sistema de paneles más flexible
- ✅ Múltiples paneles por actor
- ✅ Animaciones integradas
- ✅ Efectos visuales avanzados
- ✅ Mejor soporte para personalización
- ✅ Sistema de temas predefinidos

### Sistema de Subtítulos

#### AbstractUISubtitleControls

**Ubicación**: `Scripts/UI/Abstract/Dialogue/AbstractUISubtitleControls.cs`

**Métodos**:

```csharp
public abstract class AbstractUISubtitleControls : AbstractUIControls
{
    public abstract bool hasText { get; }
    public abstract void SetSubtitle(Subtitle subtitle);
    public abstract void ClearSubtitle();
    public virtual void ShowSubtitle(Subtitle subtitle);
    public virtual void SetActorPortraitSprite(string actorName, Sprite sprite);
}
```

#### StandardUISubtitlePanel

**Ubicación**: `Scripts/UI/Standard/Dialogue/StandardUISubtitlePanel.cs`

**Características**:

```csharp
public class StandardUISubtitlePanel : UIPanel
{
    // Componentes
    public RectTransform panel;
    public UnityEngine.UI.Image portraitImage;  // Retrato del actor
    public UITextField portraitName;           // Nombre del actor
    public UITextField subtitleText;           // Texto del subtítulo
    public UnityEngine.UI.Button continueButton; // Botón continuar
    
    // Opciones
    public bool addSpeakerName = false;        // Agregar nombre del hablante
    public string addSpeakerNameFormat = "{0}: {1}";
    public bool accumulateText = false;        // Acumular texto
    public int maxLines = 100;                // Máximo de líneas acumuladas
    public bool delayTypewriterUntilOpen = false; // Esperar animación antes de typewriter
    public bool onlyShowNPCPortraits = false; // Solo mostrar retratos NPC
    public bool useAnimatedPortraits = false; // Retratos animados
    public bool usePortraitNativeSize = false; // Tamaño nativo del retrato
    public bool waitForOpen = false;          // Esperar a que panel esté abierto
    public bool waitForClose = false;         // Esperar a que otros paneles cierren
    public bool clearTextOnClose = true;      // Limpiar texto al cerrar
    public bool clearTextOnConversationStart = false; // Limpiar al iniciar conversación
    
    // Eventos
    public UnityEvent onFocus = new UnityEvent();
    public UnityEvent onUnfocus = new UnityEvent();
}
```

**Funcionalidades**:
- Múltiples paneles de subtítulo (uno por actor)
- Sistema de focus (panel activo)
- Acumulación de texto
- Retratos animados
- Sincronización con animaciones

#### Proceso de Mostrar Subtítulo

```csharp
public virtual void ShowSubtitle(Subtitle subtitle)
{
    currentSubtitle = subtitle;
    
    // 1. Configurar retrato
    SetPortrait(subtitle);
    
    // 2. Configurar nombre
    SetPortraitName(subtitle);
    
    // 3. Configurar texto
    SetSubtitleText(subtitle);
    
    // 4. Mostrar panel con animación
    if (waitForOpen)
    {
        StartCoroutine(ShowSubtitleAfterOpen(subtitle));
    }
    else
    {
        ShowSubtitleNow(subtitle);
    }
}
```

### Sistema de Menús y Respuestas

#### AbstractUIResponseMenuControls

**Ubicación**: `Scripts/UI/Abstract/Dialogue/AbstractUIResponseMenuControls.cs`

**Métodos**:

```csharp
public abstract class AbstractUIResponseMenuControls : AbstractUIControls
{
    public ResponseButtonAlignment buttonAlignment = ResponseButtonAlignment.ToFirst;
    public bool showUnusedButtons = false;
    
    public abstract AbstractUISubtitleControls subtitleReminderControls { get; }
    protected abstract void ClearResponseButtons();
    protected abstract void SetResponseButtons(Response[] responses, Transform target);
    public abstract void StartTimer(float timeout);
    
    public virtual void ShowResponses(Subtitle subtitle, Response[] responses, Transform target);
    public virtual void SetPCPortrait(Sprite sprite, string portraitName);
}
```

#### StandardUIResponseMenuControls

**Ubicación**: `Scripts/UI/Standard/Dialogue/StandardUIResponseMenuControls.cs`

**Características**:

```csharp
public class StandardUIResponseMenuControls : AbstractUIResponseMenuControls
{
    protected List<StandardUIMenuPanel> m_builtinPanels;
    protected StandardUIMenuPanel m_defaultPanel;
    protected Dictionary<Transform, StandardUIMenuPanel> m_actorPanelCache;
    
    public virtual bool allowDialogueActorCustomPanels { get; set; } = true;
    
    public StandardUIMenuPanel GetPanel(Subtitle lastSubtitle, Response[] responses)
    {
        // 1. Verificar override forzado
        if (m_forcedOverridePanel != null) return m_forcedOverridePanel;
        
        // 2. Verificar override por actor
        var playerTransform = GetPlayerTransform(lastSubtitle, responses);
        if (m_actorPanelCache.ContainsKey(playerTransform))
            return m_actorPanelCache[playerTransform];
        
        // 3. Verificar DialogueActor component
        var dialogueActor = DialogueActor.GetDialogueActorComponent(playerTransform);
        var panel = GetDialogueActorPanel(dialogueActor);
        
        // 4. Usar panel por defecto
        return panel ?? m_defaultPanel;
    }
}
```

**Funcionalidades**:
- Múltiples paneles de menú
- Override por actor
- Cache de paneles por actor
- Panel por defecto
- Soporte para DialogueActor custom panels

#### StandardUIResponseButton

**Ubicación**: `Scripts/UI/Standard/Dialogue/StandardUIResponseButton.cs`

**Características**:
- Botón individual de respuesta
- Manejo de eventos onClick
- Estados visuales (normal, hover, disabled)
- Soporte para texto formateado
- Integración con typewriter effect

### Sistema de Retratos/Portraits

#### Configuración de Retratos

**En StandardUISubtitlePanel**:

```csharp
[Tooltip("(Optional) Image for actor's portrait.")]
public UnityEngine.UI.Image portraitImage;

[Tooltip("(Optional) Text element for actor's name.")]
public UITextField portraitName;

[Tooltip("Check Dialogue Actors for portrait animator controllers.")]
public bool useAnimatedPortraits = false;

[Tooltip("Set Portrait Image to actor portrait's native size.")]
public bool usePortraitNativeSize = false;

[Tooltip("If a player actor uses this panel, don't show player portrait.")]
public bool onlyShowNPCPortraits = false;
```

#### Proceso de Configuración de Retrato

```csharp
protected virtual void SetPortrait(Subtitle subtitle)
{
    if (portraitImage == null) return;
    
    // 1. Obtener sprite del actor
    Sprite portraitSprite = subtitle.speakerInfo.portrait;
    
    // 2. Verificar si es NPC o PC
    if (onlyShowNPCPortraits && subtitle.speakerInfo.isPlayer)
    {
        // Mantener retrato NPC anterior
        return;
    }
    
    // 3. Aplicar retrato
    if (portraitSprite != null)
    {
        portraitImage.sprite = portraitSprite;
        
        // 4. Tamaño nativo si está habilitado
        if (usePortraitNativeSize)
        {
            portraitImage.SetNativeSize();
        }
        
        // 5. Animación si está habilitada
        if (useAnimatedPortraits && animator != null)
        {
            animator.SetTrigger("Portrait");
        }
    }
    else
    {
        portraitImage.sprite = null;
    }
}
```

#### Retratos Animados

Soporte para retratos con Animator Controller:

```csharp
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

**Uso**:
- Asignar Animator Controller al `portraitImage`
- Configurar `useAnimatedPortraits = true`
- El sistema activará triggers automáticamente

### Efectos Visuales

#### Typewriter Effect

**Ubicación**: `Scripts/UI/Utility/UnityUITypewriterEffect.cs`

**Características**:

```csharp
public class UnityUITypewriterEffect : AbstractTypewriterEffect
{
    public float charactersPerSecond = 50f;
    public AudioClip audioClip;              // Audio por carácter
    public AudioClip[] alternateAudioClips;  // Audio alternativo
    public AudioSource audioSource;
    public bool pauseOnPunctuation = false;
    public float pauseDuration = 0.1f;
    public bool pauseOnNewLine = false;
    public float newLinePauseDuration = 0.5f;
    
    // Auto-scroll
    public AutoScrollSettings autoScrollSettings;
    
    // Eventos
    public UnityEvent onBegin = new UnityEvent();
    public UnityEvent onCharacter = new UnityEvent();
    public UnityEvent onEnd = new UnityEvent();
}
```

**Funcionalidades**:
- Velocidad configurable (caracteres por segundo)
- Audio por carácter
- Pausas en puntuación
- Pausas en nueva línea
- Soporte para rich text (bold, italic, color)
- Auto-scroll para texto largo
- Eventos (onBegin, onCharacter, onEnd)

**Proceso**:

```csharp
protected IEnumerator PlayTypewriter()
{
    onBegin.Invoke();
    
    // Parsear texto en tokens
    List<Token> tokens = ParseText(original);
    
    foreach (Token token in tokens)
    {
        switch (token.tokenType)
        {
            case TokenType.Character:
                // Agregar carácter
                current.Append(token.character);
                control.text = BuildText();
                
                // Audio
                if (audioSource != null && audioClip != null)
                    audioSource.PlayOneShot(audioClip);
                
                // Pausa
                yield return new WaitForSeconds(1f / charactersPerSecond);
                break;
                
            case TokenType.Pause:
                // Pausa en puntuación
                yield return new WaitForSeconds(token.duration);
                break;
                
            case TokenType.BoldOpen:
            case TokenType.ColorOpen:
                // Aplicar formato
                break;
        }
    }
    
    onEnd.Invoke();
}
```

#### Fade Effect

**Ubicación**: `Scripts/UI/Standard/Effects/`

**Características**:
- Fade in/out de paneles
- Transiciones suaves
- Configuración de duración
- Soporte para múltiples elementos

#### Color Text Effect

**Ubicación**: `Scripts/UI/Standard/Effects/StandardUIColorText.cs`

**Funcionalidad**:
- Cambio de color de texto
- Útil para hover en botones
- Restauración de color original

```csharp
public class StandardUIColorText : MonoBehaviour
{
    public Color color;
    public UITextField text;
    
    public void ApplyColor()
    {
        originalColor = text.color;
        text.color = color;
    }
    
    public void UndoColor()
    {
        text.color = originalColor;
    }
}
```

### Sistema de Paneles (UIPanel)

#### UIPanel - Clase Base

**Ubicación**: `Scripts/UI/Utility/UIPanel.cs`

**Estados**:

```csharp
public enum PanelState
{
    Closed,      // Cerrado
    Opening,     // Abriendo
    Open,        // Abierto
    Closing      // Cerrando
}
```

**Características**:

```csharp
public class UIPanel : MonoBehaviour
{
    public PanelState panelState { get; protected set; }
    public bool waitForShowAnimation { get; protected set; }
    public bool waitForHideAnimation { get; protected set; }
    
    // Animaciones
    public Animator animator;
    public string showAnimationTrigger = "Show";
    public string hideAnimationTrigger = "Hide";
    
    // Métodos
    public virtual void Show();
    public virtual void Hide();
    public virtual void HideImmediate();
    public virtual void Open();
    public virtual void Close();
}
```

**Proceso de Apertura**:

```csharp
public virtual void Show()
{
    if (panelState == PanelState.Open) return;
    
    gameObject.SetActive(true);
    panelState = PanelState.Opening;
    
    if (animator != null && !string.IsNullOrEmpty(showAnimationTrigger))
    {
        animator.SetTrigger(showAnimationTrigger);
        if (waitForShowAnimation)
        {
            StartCoroutine(WaitForShowAnimation());
        }
    }
    else
    {
        panelState = PanelState.Open;
    }
}
```

### Prefabs Predefinidos

El sistema incluye múltiples prefabs listos para usar:

#### Templates (Básicos)

- **Basic**: UI básica y simple
- **Bubble**: Estilo burbujas de chat
- **Focus**: Panel con focus visual
- **JRPG**: Estilo JRPG japonés
- **Letterbox**: Estilo cinematográfico con letterbox
- **VN**: Visual Novel style
- **WRPG**: Western RPG style

#### Pro (Temas Avanzados)

- **Circle**: Menú circular
- **Computer**: Estilo computadora/terminal
- **Mobile**: Optimizado para móviles
- **Nuke**: Tema nuclear/apocalíptico
- **Old School**: Estilo retro
- **Runic**: Tema rúnico/fantástico
- **Sci-fi**: Ciencia ficción
- **Scrolling**: Texto con scroll
- **SMS**: Estilo mensajes de texto
- **Wheel**: Menú tipo rueda

---

## Sistema de Personalización

### Personalización de Prefabs

#### Estructura de Prefabs

Los prefabs están organizados jerárquicamente:

```
Standard Dialogue UI (Root)
├── Main Panel
│   ├── NPC Subtitle Panel
│   │   ├── Portrait Image
│   │   ├── Portrait Name
│   │   └── Subtitle Text
│   ├── PC Subtitle Panel
│   │   └── ...
│   └── Response Menu Panel
│       ├── Response Button 1
│       ├── Response Button 2
│       └── ...
└── Alert Panel
    └── Alert Text
```

#### Modificación de Prefabs

**Pasos**:
1. Duplicar prefab existente
2. Modificar elementos visuales (imágenes, colores, fuentes)
3. Ajustar animaciones
4. Configurar paneles
5. Guardar como nuevo prefab

**Componentes Modificables**:
- Imágenes de fondo
- Colores de texto
- Fuentes
- Tamaños y posiciones
- Animaciones
- Efectos visuales

### Sistema de Temas y Estilos

#### Temas Predefinidos

Cada tema incluye:
- **Estilo visual**: Colores, fuentes, imágenes
- **Animaciones**: Transiciones, efectos
- **Layout**: Posicionamiento de elementos
- **Componentes**: Paneles, botones, retratos

#### Creación de Temas Personalizados

**Estructura**:

```csharp
// 1. Crear nuevo prefab basado en template
Standard Dialogue UI (Custom Theme)
    ├── Main Panel (con tema personalizado)
    │   ├── Background Image (tema)
    │   ├── NPC Subtitle Panel (estilo tema)
    │   └── Response Menu Panel (estilo tema)
    └── Alert Panel (estilo tema)
```

**Componentes del Tema**:
- **Color Scheme**: Colores principales y secundarios
- **Typography**: Fuentes y tamaños
- **Sprites**: Imágenes de fondo, bordes, iconos
- **Animations**: Animaciones de transición
- **Layout**: Posicionamiento y espaciado

### Localización e Internacionalización

#### Sistema de Localización

**Ubicación**: `Scripts/UI/Utility/`

**Componentes**:

1. **UILocalizationManager**
   - Gestiona idiomas
   - Cambio de idioma en tiempo de ejecución
   - Actualización de textos

2. **LocalizeUI**
   - Componente para localizar elementos UI
   - Asignación de keys de localización
   - Actualización automática

**Uso**:

```csharp
// En DialogueSystemController
public void SetLanguage(string language)
{
    Localization.language = language;
    UILocalizationManager.currentLanguage = language;
    
    // Actualizar textos activos
    if (updateActiveConversationTextWhenLanguageChanges)
    {
        UpdateLocalizationOnActiveConversations();
    }
}
```

#### Formato de Texto Localizado

En `DialogueEntry`:

```csharp
// Campo por defecto
public string DialogueText;  // "Hello"

// Campo localizado
public string currentLocalizedDialogueText;  // "Hola" (si language = "es")
```

**Campos Localizables**:
- `Dialogue Text` → `"ES"`, `"FR"`, etc.
- `Menu Text` → `"Menu Text ES"`, `"Menu Text FR"`, etc.
- `Sequence` → `"Sequence ES"`, `"Sequence FR"`, etc.

#### Text Table

Sistema de tablas de texto para localización:

```csharp
public class TextTable
{
    public Dictionary<string, Dictionary<string, string>> languages;
    
    // Ejemplo:
    // languages["es"]["greeting"] = "Hola"
    // languages["en"]["greeting"] = "Hello"
}
```

### Sistema de Fuentes y Textos

#### UITextField

**Ubicación**: `Scripts/UI/Utility/UITextField.cs`

**Características**:

```csharp
[System.Serializable]
public class UITextField
{
    public GameObject gameObject;
    public UnityEngine.UI.Text uiText;           // Unity UI Text
#if TMP_PRESENT
    public TMPro.TextMeshProUGUI textMeshProUGUI; // TextMesh Pro
#endif
    
    public string text
    {
        get
        {
            if (uiText != null) return uiText.text;
#if TMP_PRESENT
            if (textMeshProUGUI != null) return textMeshProUGUI.text;
#endif
            return string.Empty;
        }
        set
        {
            if (uiText != null) uiText.text = value;
#if TMP_PRESENT
            if (textMeshProUGUI != null) textMeshProUGUI.text = value;
#endif
        }
    }
    
    public Color color
    {
        get
        {
            if (uiText != null) return uiText.color;
#if TMP_PRESENT
            if (textMeshProUGUI != null) return textMeshProUGUI.color;
#endif
            return Color.white;
        }
        set
        {
            if (uiText != null) uiText.color = value;
#if TMP_PRESENT
            if (textMeshProUGUI != null) textMeshProUGUI.color = value;
#endif
        }
    }
}
```

**Ventajas**:
- Soporte para Unity UI Text y TextMesh Pro
- Interfaz unificada
- Cambio automático según disponibilidad

#### TextMesh Pro Support

Soporte opcional para TextMesh Pro:

```csharp
#if TMP_PRESENT
    // Código para TextMesh Pro
    public TMPro.TextMeshProUGUI textMeshProUGUI;
#endif
```

**Activación**:
- Definir scripting symbol `TMP_PRESENT`
- Importar TextMesh Pro package
- El sistema detecta automáticamente

#### Rich Text Support

Soporte para rich text tags:

```csharp
// Bold
<b>Texto en negrita</b>

// Italic
<i>Texto en cursiva</i>

// Color
<color=#FF0000>Texto rojo</color>

// Size
<size=20>Texto grande</size>

// Emphasis (del sistema)
[em1]Texto con énfasis 1[/em1]
[em2]Texto con énfasis 2[/em2]
```

### Override por Actor

#### OverrideUnityUIDialogueControls

**Ubicación**: `Scripts/MVC/Actor/Override/OverrideDialogueUI.cs`

**Funcionalidad**:
- Permite que un actor tenga UI personalizada
- Override de paneles de subtítulo
- Override de paneles de menú

```csharp
public class OverrideUnityUIDialogueControls : MonoBehaviour
{
    public UnityUISubtitleControls npcSubtitle;
    public UnityUISubtitleControls pcSubtitle;
    public UnityUIResponseMenuControls responseMenu;
}
```

#### DialogueActor Settings

**En DialogueActor**:

```csharp
public class StandardDialogueUISettings
{
    public SubtitlePanelNumber subtitlePanelNumber = SubtitlePanelNumber.Default;
    public StandardUISubtitlePanel customSubtitlePanel = null;
    public Vector3 customSubtitlePanelOffset = Vector3.zero;
    
    public MenuPanelNumber menuPanelNumber = MenuPanelNumber.Default;
    public StandardUIMenuPanel customMenuPanel = null;
    public Vector3 customMenuPanelOffset = Vector3.zero;
}
```

**Uso**:
- Asignar panel personalizado por actor
- Offset para posicionamiento
- Múltiples paneles por actor

### Sistema de Animaciones

#### UIAnimatorMonitor

**Ubicación**: `Scripts/UI/Utility/UIAnimatorMonitor.cs`

**Funcionalidad**:
- Monitorea estados de animación
- Detecta cuando animaciones terminan
- Coordina transiciones

```csharp
public class UIAnimatorMonitor : MonoBehaviour
{
    public Animator animator;
    public string showState = "Show";
    public string hideState = "Hide";
    
    public bool IsInState(string stateName);
    public bool IsTransitioning();
    public void SetTrigger(string triggerName);
}
```

#### Animaciones de Panel

**Estados**:
- `Show`: Animación de apertura
- `Hide`: Animación de cierre
- `Focus`: Animación de focus
- `Unfocus`: Animación de unfocus

**Configuración**:

```csharp
// En StandardUISubtitlePanel
public string focusAnimationTrigger = "Focus";
public string unfocusAnimationTrigger = "Unfocus";

// En UIPanel
public string showAnimationTrigger = "Show";
public string hideAnimationTrigger = "Hide";
```

### Resumen de Componentes UI

| Componente | Ubicación | Propósito |
|------------|-----------|-----------|
| `IDialogueUI` | MVC/View/Dialogue/ | Interfaz base |
| `AbstractDialogueUI` | UI/Abstract/Dialogue/ | Clase base abstracta |
| `StandardDialogueUI` | UI/Standard/Dialogue/ | Implementación recomendada |
| `StandardUISubtitlePanel` | UI/Standard/Dialogue/ | Panel de subtítulo |
| `StandardUIResponseMenuControls` | UI/Standard/Dialogue/ | Menú de respuestas |
| `StandardUIResponseButton` | UI/Standard/Dialogue/ | Botón de respuesta |
| `UnityUITypewriterEffect` | UI/Utility/ | Efecto typewriter |
| `UIPanel` | UI/Utility/ | Panel base con animaciones |
| `UITextField` | UI/Utility/ | Campo de texto unificado |
| `UIAnimatorMonitor` | UI/Utility/ | Monitor de animaciones |

### Resumen de Personalización

| Aspecto | Descripción | Ubicación |
|---------|-------------|-----------|
| **Prefabs** | Templates y temas predefinidos | `Prefabs/Standard UI Prefabs/` |
| **Temas** | Estilos visuales completos | Templates y Pro folders |
| **Localización** | Sistema multi-idioma | `UILocalizationManager`, `LocalizeUI` |
| **Fuentes** | Unity UI Text y TextMesh Pro | `UITextField` |
| **Override por Actor** | UI personalizada por actor | `DialogueActor.standardDialogueUISettings` |
| **Animaciones** | Sistema de animaciones integrado | `UIAnimatorMonitor`, `UIPanel` |
| **Efectos** | Typewriter, fade, color | `UI/Standard/Effects/` |

---

## Próximos Pasos (FASE 0.5)

1. **Análisis del Sistema de Actores y Personajes**
2. **Análisis del Sistema de Almacenamiento**

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
