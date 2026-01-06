# FASE 0.1: Análisis de Arquitectura y Estructura - Pixel Crushers Dialogue System

## 📋 Índice

1. [Estructura de Carpetas del Plugin](#estructura-de-carpetas-del-plugin)
2. [Clases Core del Sistema](#clases-core-del-sistema)
3. [Modelo de Datos](#modelo-de-datos)
4. [Patrones de Diseño](#patrones-de-diseño)
5. [Dependencias entre Módulos](#dependencias-entre-módulos)

---

## Estructura de Carpetas del Plugin

### Organización Principal

```
Dialogue System/
├── Scripts/                    # Código fuente principal (713 archivos .cs)
│   ├── Manager/               # Controladores principales
│   ├── MVC/                   # Arquitectura Model-View-Controller
│   │   ├── Model/            # Modelo de datos
│   │   │   ├── Data/         # DialogueDatabase, Conversation, DialogueEntry
│   │   │   └── ...
│   │   ├── View/             # Sistema de UI
│   │   │   ├── Dialogue/     # IDialogueUI, AbstractDialogueUI
│   │   │   └── Bark/         # Sistema de bark (comentarios breves)
│   │   ├── Controller/       # Controladores de conversación
│   │   ├── Actor/            # DialogueActor
│   │   └── Sequencer/        # Sistema de secuencias
│   ├── UI/                    # Implementaciones de UI
│   │   ├── Abstract/          # Clases abstractas base
│   │   ├── Unity UI/         # Implementación Unity UI
│   │   └── Standard UI/      # Implementación Standard UI
│   ├── Editor/               # Herramientas de editor
│   ├── Importers/            # Importadores de formatos externos
│   ├── Lua/                  # Integración con Lua
│   ├── Quests/               # Sistema de misiones
│   ├── Triggers/             # Triggers y eventos
│   ├── Save System/          # Sistema de guardado
│   ├── Options/              # Opciones y configuración
│   └── Utility/              # Utilidades
├── Wrappers/                  # Wrappers para compatibilidad (147 archivos .cs)
├── Prefabs/                   # Prefabs listos para usar
│   ├── Dialogue Manager.prefab
│   ├── Standard UI Prefabs/
│   └── UIToolkit UI Prefabs/
├── Resources/                 # Recursos cargados dinámicamente
├── Demo/                      # Escenas y ejemplos de demostración
├── Templates/                 # Plantillas para importación
│   ├── Articy Draft Template Project/
│   ├── Chat Mapper Template Project/
│   └── Scripts/
└── Third Party Support/       # Soporte para plugins de terceros
```

### Carpetas Clave

#### 1. **Scripts/Manager/**
- **Propósito**: Controladores principales del sistema
- **Archivos principales**:
  - `DialogueSystemController.cs` - Manager principal del sistema
  - Contiene la lógica central de coordinación

#### 2. **Scripts/MVC/**
- **Propósito**: Arquitectura Model-View-Controller
- **Subcarpetas**:
  - **Model/Data/**: Estructuras de datos (`DialogueDatabase`, `Conversation`, `DialogueEntry`)
  - **View/**: Interfaces y clases abstractas de UI
  - **Controller/**: Controladores de conversación
  - **Actor/**: Sistema de actores/personajes
  - **Sequencer/**: Sistema de secuencias y comandos

#### 3. **Scripts/UI/**
- **Propósito**: Implementaciones concretas de UI
- **Subcarpetas**:
  - **Abstract/**: Clases base abstractas
  - **Unity UI/**: Implementación para Unity UI (Canvas)
  - **Standard UI/**: Implementación para Standard UI

#### 4. **Scripts/Editor/**
- **Propósito**: Herramientas de editor de Unity
- **Contenido**: Editores personalizados, ventanas, inspectores

#### 5. **Scripts/Importers/**
- **Propósito**: Importadores de formatos externos
- **Formatos soportados**: Chat Mapper, Articy Draft, Celtx, etc.

#### 6. **Wrappers/**
- **Propósito**: Wrappers para compatibilidad entre código fuente y DLLs
- **Uso**: Permite cambiar entre código fuente y DLLs compiladas fácilmente

---

## Clases Core del Sistema

### 1. DialogueSystemController

**Ubicación**: `Scripts/Manager/DialogueSystemController.cs`

**Responsabilidades**:
- Manager principal del sistema de diálogos
- Coordina todos los componentes (Database, UI, Sequencer, Controller)
- Gestiona el ciclo de vida de las conversaciones
- Maneja eventos y callbacks del sistema
- Singleton pattern (accesible globalmente)

**Propiedades Clave**:
```csharp
public DialogueDatabase initialDatabase;      // Base de datos inicial
public DisplaySettings displaySettings;       // Configuración de visualización
public bool allowSimultaneousConversations;   // Permitir múltiples conversaciones
public bool instantiateDatabase;              // Usar copia en runtime
```

**Métodos Principales**:
- `StartConversation()` - Iniciar una conversación
- `StopConversation()` - Detener conversación actual
- `SendMessage()` - Enviar mensajes al sistema
- Eventos: `OnConversationStart`, `OnConversationEnd`, etc.

**Patrón de Diseño**: Singleton, Observer (eventos)

---

### 2. DialogueDatabase

**Ubicación**: `Scripts/MVC/Model/Data/DialogueDatabase.cs`

**Tipo**: `ScriptableObject` (asset de Unity)

**Responsabilidades**:
- Contenedor principal de todos los datos de diálogo
- Almacena actores, conversaciones, items, variables, locations
- Serialización y persistencia de datos
- Base para importación/exportación

**Estructura de Datos**:
```csharp
public class DialogueDatabase : ScriptableObject
{
    public string version;
    public string author;
    public string description;
    public string globalUserScript;           // Script Lua global
    
    public List<Actor> actors;                // Actores/personajes
    public List<Item> items;                  // Items/objetos
    public List<Location> locations;          // Ubicaciones
    public List<Variable> variables;          // Variables Lua
    public List<Conversation> conversations; // Conversaciones
}
```

**Características**:
- ScriptableObject permite edición en Unity Editor
- Soporte para múltiples bases de datos (Extra Databases)
- Sincronización entre bases de datos
- Importación desde formatos externos

---

### 3. Conversation

**Ubicación**: `Scripts/MVC/Model/Data/Conversation.cs`

**Responsabilidades**:
- Representa una conversación completa
- Contiene múltiples `DialogueEntry` (nodos de diálogo)
- Define el flujo de la conversación
- Gestiona links entre entradas

**Estructura**:
```csharp
public class Conversation
{
    public int id;                           // ID único
    public string Title;                     // Título de la conversación
    public string Description;              // Descripción
    public int ActorID;                      // ID del actor principal
    public int ConversantID;                 // ID del conversante
    public List<DialogueEntry> dialogueEntries; // Entradas de diálogo
    public bool OverrideDisplaySettings;     // Override de configuración
}
```

**Características**:
- Grafo de diálogo (nodos y conexiones)
- Soporte para múltiples actores
- Condiciones y scripts por conversación

---

### 4. DialogueEntry

**Ubicación**: `Scripts/MVC/Model/Data/DialogueEntry.cs`

**Responsabilidades**:
- Representa un nodo individual de diálogo
- Contiene texto, condiciones, consecuencias
- Gestiona links a otros nodos
- Soporta scripts Lua y secuencias

**Estructura**:
```csharp
public class DialogueEntry
{
    public int id;                           // ID único
    public int conversationID;               // ID de conversación padre
    public bool isRoot;                      // Es nodo raíz
    public bool isGroup;                     // Es nodo grupo
    public string Title;                     // Título
    public string currentDialogueText;       // Texto del diálogo
    public string currentMenuText;           // Texto del menú
    public int ActorID;                      // ID del actor que habla
    public int ConversantID;                 // ID del conversante
    
    // Condiciones y consecuencias
    public string ConditionsString;          // Condiciones Lua
    public string UserScript;                // Script Lua del usuario
    public string Sequence;                  // Secuencia de comandos
    
    // Links
    public List<Link> outgoingLinks;         // Enlaces salientes
    public List<Link> incomingLinks;         // Enlaces entrantes
}
```

**Tipos de Entradas**:
- **NPC**: Diálogo de NPC (no requiere respuesta del jugador)
- **Player**: Respuesta del jugador (requiere selección)
- **Group**: Nodo agrupador (para organización)

---

### 5. DialogueUI

**Ubicación**: `Scripts/MVC/View/Dialogue/IDialogueUI.cs` (interfaz)

**Responsabilidades**:
- Interfaz para sistemas de UI
- Define métodos para mostrar diálogos y menús
- Maneja subtítulos y respuestas del jugador

**Interfaz Principal**:
```csharp
public interface IDialogueUI
{
    void ShowSubtitle(Subtitle subtitle);
    void HideSubtitle(Subtitle subtitle);
    void ShowResponses(Subtitle subtitle, Response[] responses, float timeout);
    void HideResponses();
    void ShowMessage(string message, float duration);
    void OnConversationStart(Transform actor);
    void OnConversationEnd(Transform actor);
}
```

**Implementaciones**:
- `AbstractDialogueUI` - Clase base abstracta
- `UnityUIDialogueUI` - Implementación Unity UI
- `StandardDialogueUI` - Implementación Standard UI
- `CanvasDialogueUI` - Implementación Canvas

**Patrón de Diseño**: Strategy (diferentes implementaciones de UI)

---

### 6. DialogueActor

**Ubicación**: `Scripts/MVC/Actor/DialogueActor.cs`

**Responsabilidades**:
- Componente para asociar GameObjects con actores
- Override de nombre de actor
- Configuración de retratos/portraits
- Configuración de UI específica por actor
- Sistema de bark (comentarios breves)

**Propiedades Clave**:
```csharp
public class DialogueActor : MonoBehaviour
{
    public string actor;                      // Nombre del actor
    public string persistentDataName;        // Nombre para datos persistentes
    public Texture2D portrait;               // Retrato (Texture)
    public Sprite spritePortrait;            // Retrato (Sprite)
    public GameObject cameraAngles;          // Ángulos de cámara personalizados
    public AudioSource audioSource;          // Audio source para comandos
    public BarkUISettings barkUISettings;    // Configuración de bark
    public StandardDialogueUISettings standardDialogueUISettings; // UI específica
}
```

**Características**:
- Override de nombre de actor en conversaciones
- Retratos personalizados por GameObject
- UI personalizada por actor
- Sistema de bark para comentarios breves

---

## Modelo de Datos

### Diagrama ER Simplificado

```
DialogueDatabase (ScriptableObject)
    ├── Actor[]                    # Actores/personajes
    │   ├── id
    │   ├── Name
    │   ├── Portrait
    │   └── ...
    │
    ├── Conversation[]             # Conversaciones
    │   ├── id
    │   ├── Title
    │   ├── ActorID
    │   ├── ConversantID
    │   └── DialogueEntry[]        # Entradas de diálogo
    │       ├── id
    │       ├── Title
    │       ├── currentDialogueText
    │       ├── currentMenuText
    │       ├── ActorID
    │       ├── ConversantID
    │       ├── ConditionsString   # Condiciones Lua
    │       ├── UserScript         # Script Lua
    │       ├── Sequence           # Secuencia de comandos
    │       └── Link[]             # Enlaces a otros nodos
    │           ├── originConversationID
    │           ├── originDialogueID
    │           ├── destinationConversationID
    │           └── destinationDialogueID
    │
    ├── Item[]                     # Items/objetos
    │   ├── id
    │   ├── Name
    │   └── ...
    │
    ├── Variable[]                 # Variables Lua
    │   ├── id
    │   ├── Name
    │   ├── Type (Boolean, Float, String, etc.)
    │   └── InitialValue
    │
    └── Location[]                 # Ubicaciones
        ├── id
        ├── Name
        └── ...
```

### Estructura de Actor

```csharp
public class Actor
{
    public int id;
    public string Name;
    public string[] Pictures;      // Retratos
    public string Description;
    public bool IsPlayer;          // Es el jugador
    public string[] Fields;        // Campos personalizados
}
```

### Estructura de Link

```csharp
public class Link
{
    public int originConversationID;
    public int originDialogueID;
    public int destinationConversationID;
    public int destinationDialogueID;
    public bool isConnector;        // Es conector (no evalua condiciones)
    public int priority;            // Prioridad del link
}
```

### Sistema de Variables (Lua)

**Tipos de Variables**:
- `Boolean` - Valores true/false
- `Float` - Números decimales
- `String` - Texto
- `FieldType` - Tipos personalizados

**Uso en Condiciones**:
```lua
-- Ejemplo de condición Lua
Variable["HasKey"] == true
Variable["PlayerLevel"] >= 10
```

### Sistema de Quest (Misiones)

**Ubicación**: `Scripts/Quests/`

**Estructura**:
- Los `Item` pueden representar misiones
- Sistema de estados de misiones
- Integración con QuestLog

---

## Patrones de Diseño

### 1. Singleton
- **DialogueSystemController**: Manager único del sistema
- Acceso global: `DialogueManager.Instance`

### 2. Observer
- Sistema de eventos extenso
- Callbacks para eventos de conversación
- Delegates para personalización

### 3. Strategy
- Diferentes implementaciones de UI
- Interfaz `IDialogueUI` con múltiples implementaciones

### 4. MVC (Model-View-Controller)
- **Model**: `DialogueDatabase`, `Conversation`, `DialogueEntry`
- **View**: `IDialogueUI`, implementaciones de UI
- **Controller**: `ConversationController`, `DialogueSystemController`

### 5. Factory
- Creación de comandos de secuencia
- Generación de UI components

### 6. Command
- Sistema de Sequencer con comandos
- Cada comando es una clase separada

---

## Dependencias entre Módulos

### Dependencias Principales

```
DialogueSystemController
    ├── DialogueDatabase (Model)
    ├── IDialogueUI (View)
    ├── ConversationController (Controller)
    ├── Sequencer (Sistema de secuencias)
    └── Lua (Motor de scripting)

DialogueDatabase
    ├── Conversation[]
    │   └── DialogueEntry[]
    │       └── Link[]
    ├── Actor[]
    ├── Item[]
    ├── Variable[]
    └── Location[]

ConversationController
    ├── DialogueDatabase
    ├── IDialogueUI
    └── Sequencer

IDialogueUI (Interfaz)
    ├── AbstractDialogueUI (Base)
    │   ├── UnityUIDialogueUI
    │   ├── StandardDialogueUI
    │   └── CanvasDialogueUI
    └── BarkDialogueUI

DialogueActor
    ├── DialogueDatabase (para obtener datos del actor)
    └── IDialogueUI (para override de UI)
```

### Módulos Externos

- **Pixel Crushers Common**: Biblioteca común compartida
  - Message System
  - Save System
  - UI utilities
  - Text utilities

- **Lua**: Motor de scripting integrado
  - Evaluación de condiciones
  - Variables del diálogo
  - Scripts personalizados

---

## Diagrama de Estructura de Carpetas

```
Dialogue System/
│
├── Scripts/ (713 archivos .cs)
│   ├── Manager/              → DialogueSystemController
│   ├── MVC/
│   │   ├── Model/Data/       → DialogueDatabase, Conversation, DialogueEntry
│   │   ├── View/             → IDialogueUI, AbstractDialogueUI
│   │   ├── Controller/       → ConversationController
│   │   ├── Actor/            → DialogueActor
│   │   └── Sequencer/        → Sequencer, SequencerCommand
│   ├── UI/                   → Implementaciones de UI
│   ├── Editor/               → Herramientas de editor
│   ├── Importers/            → Importadores externos
│   ├── Lua/                  → Integración Lua
│   ├── Quests/               → Sistema de misiones
│   ├── Triggers/             → Triggers y eventos
│   └── Save System/          → Sistema de guardado
│
├── Wrappers/ (147 archivos .cs)
│   └── Wrappers para compatibilidad código fuente/DLL
│
├── Prefabs/
│   ├── Dialogue Manager.prefab
│   ├── Standard UI Prefabs/
│   └── UIToolkit UI Prefabs/
│
├── Resources/
│   └── Prefabs cargados dinámicamente
│
├── Demo/
│   ├── Scenes/
│   ├── Prefabs/
│   └── Data/
│
└── Templates/
    └── Plantillas para importación
```

---

## Resumen de Clases Core

| Clase | Ubicación | Responsabilidad Principal |
|-------|-----------|---------------------------|
| `DialogueSystemController` | Manager/ | Manager principal, coordina todo el sistema |
| `DialogueDatabase` | MVC/Model/Data/ | Contenedor de datos (ScriptableObject) |
| `Conversation` | MVC/Model/Data/ | Representa una conversación completa |
| `DialogueEntry` | MVC/Model/Data/ | Nodo individual de diálogo |
| `IDialogueUI` | MVC/View/Dialogue/ | Interfaz para sistemas de UI |
| `DialogueActor` | MVC/Actor/ | Componente para asociar GameObjects con actores |
| `ConversationController` | MVC/Controller/ | Controla el flujo de conversaciones |
| `Sequencer` | MVC/Sequencer/ | Ejecuta secuencias de comandos |

---

## Próximos Pasos (FASE 0.2)

1. **Análisis del flujo de ejecución** de conversaciones
2. **Análisis del sistema de nodos y conexiones**
3. **Análisis del sistema de condiciones y scripting** (Lua)

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
