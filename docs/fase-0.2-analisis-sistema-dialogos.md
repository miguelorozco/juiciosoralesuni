# FASE 0.2: Análisis del Sistema de Diálogos - Pixel Crushers Dialogue System

## 📋 Índice

1. [Flujo de Ejecución de Conversaciones](#flujo-de-ejecución-de-conversaciones)
2. [Sistema de Nodos y Conexiones](#sistema-de-nodos-y-conexiones)
3. [Sistema de Condiciones y Scripting](#sistema-de-condiciones-y-scripting)

---

## Flujo de Ejecución de Conversaciones

### Inicio de una Conversación

#### 1. Llamada Inicial

```csharp
// Desde DialogueSystemController
DialogueManager.StartConversation(title, actor, conversant);
```

**Proceso**:
1. `DialogueSystemController.StartConversation()` crea un `ConversationModel`
2. Crea un `ConversationController` (MVC Controller)
3. Crea un `ConversationView` (MVC View)
4. Inicializa el modelo con el primer estado (`firstState`)
5. Llama a `GotoState(firstState)` para comenzar

#### 2. Creación del Modelo

```csharp
// ConversationModel constructor
public ConversationModel(
    DialogueDatabase database, 
    string title, 
    Transform actor, 
    Transform conversant,
    bool allowLuaExceptions,
    IsDialogueEntryValidDelegate isDialogueEntryValid,
    int initialDialogueEntryID = -1
)
```

**Pasos**:
- Obtiene la `Conversation` de la base de datos
- Encuentra el nodo inicial (root entry o `initialDialogueEntryID`)
- Crea `CharacterInfo` para actor y conversante
- Ejecuta el script Lua del nodo inicial (si existe)
- Crea el `firstState` con respuestas evaluadas

#### 3. Primer Estado

El `firstState` contiene:
- `subtitle`: El subtítulo del nodo inicial
- `npcResponses[]`: Respuestas de NPC disponibles
- `pcResponses[]`: Respuestas del jugador disponibles

### Navegación entre Diálogos

#### Método `GotoState()`

```csharp
public void GotoState(ConversationState state)
{
    this.m_state = state;
    DialogueManager.instance.currentConversationState = state;
    
    if (state != null)
    {
        // Verificar cambio de conversación
        var newConversationID = m_model.GetConversationID(state);
        if (newConversationID != m_currentConversationID)
        {
            // Cambio de conversación (link cross-conversation)
            m_model.InformParticipants(DialogueSystemMessages.OnLinkedConversationStart, true);
        }
        
        // Mostrar subtítulo o menú
        if (state.isGroup)
        {
            m_view.ShowLastNPCSubtitle();
        }
        else
        {
            bool isPCResponseMenuNext, isPCAutoResponseNext;
            AnalyzePCResponses(state, out isPCResponseMenuNext, out isPCAutoResponseNext);
            m_view.StartSubtitle(state.subtitle, isPCResponseMenuNext, isPCAutoResponseNext);
        }
    }
    else
    {
        Close(); // Fin de conversación
    }
}
```

### Flujo de Navegación

```
[Inicio Conversación]
    ↓
[GotoState(firstState)]
    ↓
[Mostrar Subtítulo]
    ↓
[OnFinishedSubtitle] (cuando subtítulo termina)
    ↓
    ├─→ [Tiene NPC Response?]
    │       ↓ SÍ
    │   [GotoState(nextNPCState)]
    │       ↓
    │   [Mostrar Subtítulo NPC]
    │       ↓
    │   [Repetir ciclo]
    │
    ├─→ [Tiene PC Responses?]
    │       ↓ SÍ
    │   ├─→ [Auto-response?]
    │   │       ↓ SÍ
    │   │   [GotoState(autoResponseState)]
    │   │
    │   └─→ [Mostrar Menú]
    │           ↓
    │       [Usuario selecciona]
    │           ↓
    │       [OnSelectedResponse]
    │           ↓
    │       [GotoState(selectedState)]
    │
    └─→ [No hay respuestas]
            ↓
        [Close()] - Fin de conversación
```

### Sistema de Respuestas y Selección

#### Tipos de Respuestas

1. **NPC Responses** (`npcResponses[]`)
   - Diálogos de NPC que no requieren selección del jugador
   - Se muestran automáticamente después del subtítulo actual
   - Pueden tener múltiples opciones (se elige la primera válida o aleatoria)

2. **PC Responses** (`pcResponses[]`)
   - Respuestas del jugador que requieren selección
   - Se muestran en un menú
   - El jugador debe elegir una opción

#### Auto-Response

Un PC response puede ser "auto" si:
- Tiene el tag `[auto]` en el texto
- Es la única respuesta disponible
- No tiene `[f]` (force menu)

```csharp
public bool hasPCAutoResponse
{
    get
    {
        if (pcResponses == null || pcResponses.Length == 0) return false;
        // Verificar si tiene [auto] tag o es única respuesta
        return hasForceAutoResponse || (pcResponses.Length == 1);
    }
}
```

#### Manejo de Selección

```csharp
public void OnSelectedResponse(object sender, SelectedResponseEventArgs e)
{
    DialogueManager.instance.activeConversation = activeConversationRecord;
    GotoState(m_model.GetState(e.DestinationEntry));
}
```

### Manejo de Condiciones y Consecuencias

#### Evaluación de Condiciones

Las condiciones se evalúan en `EvaluateLinks()`:

```csharp
bool isValid = Lua.IsTrue(destinationEntry.conditionsString, ...) &&
    ((isDialogueEntryValid == null) || isDialogueEntryValid(destinationEntry));
```

**Proceso**:
1. Evalúa `conditionsString` usando Lua
2. Llama al delegate `isDialogueEntryValid` (si existe)
3. Si ambas son válidas, el link se agrega a las respuestas

#### Ejecución de Consecuencias

Cuando se ejecuta un `DialogueEntry`:

```csharp
private void ExecuteEntry(DialogueEntry entry, CharacterInfo actorInfo)
{
    // 1. Ejecutar script Lua del usuario
    if (!string.IsNullOrEmpty(entry.userScript))
    {
        Lua.Run(entry.userScript, DialogueDebug.logInfo, m_allowLuaExceptions);
    }
    
    // 2. Disparar evento OnExecute
    entry.onExecute.Invoke();
    
    // 3. Ejecutar secuencia (Sequencer)
    if (!string.IsNullOrEmpty(entry.Sequence))
    {
        // El Sequencer ejecuta comandos como animaciones, audio, etc.
    }
}
```

---

## Sistema de Nodos y Conexiones

### Representación Interna de Nodos

#### DialogueEntry como Nodo

Cada `DialogueEntry` representa un nodo en el grafo:

```csharp
public class DialogueEntry
{
    public int id;                              // ID único del nodo
    public int conversationID;                  // ID de conversación padre
    public bool isRoot;                         // Es nodo raíz
    public bool isGroup;                        // Es nodo grupo (organizador)
    
    // Contenido
    public string currentDialogueText;          // Texto del diálogo
    public string currentMenuText;              // Texto del menú
    public int ActorID;                         // ID del actor que habla
    public int ConversantID;                    // ID del conversante
    
    // Conexiones
    public List<Link> outgoingLinks;            // Enlaces salientes
    public List<Link> incomingLinks;            // Enlaces entrantes
    
    // Condiciones y scripts
    public string conditionsString;             // Condiciones Lua
    public string userScript;                   // Script Lua del usuario
    public string Sequence;                    // Secuencia de comandos
    
    // Posición en editor
    public Rect canvasRect;                     // Posición en canvas del editor
}
```

### Tipos de Nodos

#### 1. Nodo Raíz (Root)
- `isRoot = true`
- Punto de entrada de la conversación
- Solo puede haber uno por conversación

#### 2. Nodo Grupo (Group)
- `isGroup = true`
- Nodo organizador vacío
- No muestra texto, solo agrupa hijos
- Útil para organización y condiciones grupales

#### 3. Nodo NPC
- `ActorID` apunta a un actor que NO es jugador (`IsPlayer = false`)
- Se muestra automáticamente como subtítulo
- No requiere selección del jugador

#### 4. Nodo Player (PC)
- `ActorID` apunta al jugador (`IsPlayer = true`)
- Se muestra como opción en menú de respuestas
- Requiere selección del jugador

### Sistema de Links

#### Estructura de Link

```csharp
public class Link
{
    public int originConversationID;            // Conversación origen
    public int originDialogueID;                // Nodo origen
    public int destinationConversationID;       // Conversación destino
    public int destinationDialogueID;          // Nodo destino
    public bool isConnector;                    // Es conector cross-conversation
    public ConditionPriority priority;          // Prioridad del link
}
```

#### Características de Links

1. **Bidireccionales**: Un nodo puede tener múltiples links salientes y entrantes
2. **Cross-Conversation**: Los links pueden conectar diferentes conversaciones
3. **Prioridad**: Los links se evalúan por prioridad (High → Normal → Low)
4. **Condiciones**: Cada link puede tener condiciones en el nodo destino

### Evaluación de Links

#### Proceso de Evaluación

```csharp
private void EvaluateLinks(
    DialogueEntry entry, 
    List<Response> npcResponses, 
    List<Response> pcResponses,
    int depth,
    List<DialogueEntry> visited
)
{
    // 1. Prevenir loops infinitos
    if (depth > MaxEvaluateLinksDepth) return;
    if (visited.Contains(entry)) return;
    visited.Add(entry);
    
    // 2. Evaluar por prioridad (High → Normal → Low)
    for (int i = (int)ConditionPriority.High; i >= 0; i--)
    {
        EvaluateLinksAtPriority((ConditionPriority)i, entry, npcResponses, pcResponses, ...);
        if ((npcResponses.Count > 0) || (pcResponses.Count > 0)) return;
    }
}
```

#### Evaluación por Prioridad

```csharp
private void EvaluateLinksAtPriority(
    ConditionPriority priority, 
    DialogueEntry entry, 
    List<Response> npcResponses,
    List<Response> pcResponses,
    ...
)
{
    foreach (var link in entry.outgoingLinks)
    {
        if (link.priority == priority)
        {
            DialogueEntry destinationEntry = GetDialogueEntry(link);
            
            // Evaluar condiciones
            bool isValid = Lua.IsTrue(destinationEntry.conditionsString, ...);
            
            if (isValid)
            {
                CharacterType characterType = GetCharacterType(destinationEntry.ActorID);
                
                if (destinationEntry.isGroup)
                {
                    // Evaluar hijos del grupo
                    EvaluateLinksAtPriority(priority, destinationEntry, ...);
                }
                else if (characterType == CharacterType.NPC)
                {
                    // Agregar respuesta NPC
                    npcResponses.Add(new Response(...));
                }
                else
                {
                    // Agregar respuesta PC
                    pcResponses.Add(new Response(...));
                }
            }
        }
    }
}
```

### Sistema de Menús y Respuestas Múltiples

#### Estructura de Response

```csharp
public class Response
{
    public FormattedText formattedText;        // Texto formateado
    public DialogueEntry destinationEntry;      // Nodo destino
    public bool enabled;                        // Está habilitado
}
```

#### Formato de Texto

El `FormattedText` puede contener:
- **Tags de formato**: `[em1]`, `[em2]`, etc. (énfasis)
- **Tags especiales**: 
  - `[f]` - Force menu (forzar menú)
  - `[auto]` - Auto-response (selección automática)
  - `[lua]...[/lua]` - Evaluación Lua en tiempo real

#### Menú de Respuestas

```csharp
public void StartResponses(Subtitle subtitle, Response[] responses)
{
    // Mostrar menú con opciones
    m_view.StartResponses(subtitle, responses);
}
```

**Comportamiento**:
- Si hay 1 respuesta y no tiene `[f]`: Auto-response
- Si hay 1 respuesta con `[f]`: Mostrar menú con 1 opción
- Si hay múltiples respuestas: Mostrar menú con todas

### Estructura de Grafo

#### Representación Visual

```
Conversation
    │
    ├─── Entry (Root) [id: 0]
    │    │
    │    ├─── Link → Entry (NPC) [id: 1]
    │    │    │
    │    │    ├─── Link → Entry (PC) [id: 2]
    │    │    │    │
    │    │    │    ├─── Link → Entry (NPC) [id: 3]
    │    │    │    │
    │    │    │    └─── Link → Entry (NPC) [id: 4]
    │    │    │
    │    │    └─── Link → Entry (PC) [id: 5]
    │    │
    │    └─── Link → Entry (Group) [id: 6]
    │         │
    │         ├─── Link → Entry (NPC) [id: 7]
    │         │
    │         └─── Link → Entry (NPC) [id: 8]
```

#### Características del Grafo

1. **Grafo Dirigido**: Los links tienen dirección (origen → destino)
2. **Puede tener ciclos**: Los links pueden volver a nodos anteriores
3. **Cross-Conversation**: Los links pueden conectar diferentes conversaciones
4. **Múltiples caminos**: Un nodo puede tener múltiples links salientes

---

## Sistema de Condiciones y Scripting

### Integración con Lua

#### Motor Lua

El sistema usa un motor Lua integrado para:
- Evaluación de condiciones
- Ejecución de scripts
- Manipulación de variables
- Acceso a datos del diálogo

#### Ubicación de Archivos Lua

```
Scripts/Lua/
├── Lua Interpreter/          # Intérprete Lua
│   ├── LuaInterpreter.cs
│   └── LuaValue/
│       ├── LuaValue.cs
│       ├── LuaTable.cs
│       ├── LuaFunction.cs
│       └── ...
└── DialogueLua.cs            # Wrapper para diálogos
```

### Variables del Diálogo

#### Tipos de Variables

```csharp
public enum FieldType
{
    Boolean,    // true/false
    Number,     // Números
    Text,       // Texto
    Actor,      // Referencia a actor
    Item,       // Referencia a item
    Location    // Referencia a ubicación
}
```

#### Acceso a Variables en Lua

```lua
-- Variables globales
Variable["HasKey"] = true
Variable["PlayerLevel"] = 10
Variable["PlayerName"] = "John"

-- Variables de actor
Actor["Player"]["Score"] = 100

-- Variables de item
Item["Sword"]["Durability"] = 50

-- Variables de conversación
Conversation[1].Dialog[5].SimStatus = "WasDisplayed"
```

#### Operaciones con Variables

```lua
-- Lectura
local hasKey = Variable["HasKey"]
local level = Variable["PlayerLevel"]

-- Escritura
Variable["HasKey"] = true
Variable["PlayerLevel"] = Variable["PlayerLevel"] + 1

-- Condiciones
if Variable["HasKey"] == true then
    -- ...
end

if Variable["PlayerLevel"] >= 10 then
    -- ...
end
```

### Condiciones de Entrada/Salida

#### Condiciones en DialogueEntry

Cada `DialogueEntry` puede tener un `conditionsString`:

```csharp
public string conditionsString;  // Ejemplo: "Variable['HasKey'] == true"
```

#### Evaluación de Condiciones

```csharp
bool isValid = Lua.IsTrue(
    destinationEntry.conditionsString, 
    DialogueDebug.logInfo, 
    m_allowLuaExceptions
);
```

**Ejemplos de Condiciones**:

```lua
-- Condición simple
Variable["HasKey"] == true

-- Condición múltiple
Variable["HasKey"] == true and Variable["PlayerLevel"] >= 10

-- Condición con actor
Actor["Player"]["Score"] > 100

-- Condición con item
Item["Sword"]["Durability"] > 0

-- Condición con conversación
Conversation[1].Dialog[5].SimStatus ~= "WasDisplayed"
```

#### False Condition Action

Cuando una condición es falsa, el sistema puede:

1. **Block** (bloquear): No evaluar más links en esta prioridad
2. **Passthrough** (pasar): Ignorar este link y evaluar sus hijos

```csharp
public string falseConditionAction;  // "Block" o "Passthrough"
```

### Scripts de Secuencia (Sequencer)

#### ¿Qué es el Sequencer?

El Sequencer ejecuta comandos durante el diálogo:
- Animaciones
- Audio
- Movimiento de cámara
- Activación de objetos
- Efectos visuales

#### Estructura de Secuencia

```csharp
public string Sequence;  // Ejemplo: "Camera(Closeup); Audio(Hello); Animation(Talk)"
```

#### Comandos Comunes

```
Camera(Closeup)              # Cambiar ángulo de cámara
Audio(Hello)                 # Reproducir audio
Animation(Talk)              # Reproducir animación
MoveTo(Speaker, Listener)    # Mover objeto
Delay(2)                     # Esperar 2 segundos
Fade(in, 1)                  # Fade in/out
SetActive(MyObject, true)    # Activar/desactivar objeto
```

#### Ejecución de Secuencia

```csharp
// En Sequencer.cs
public void PlaySequence(string sequence, Transform speaker, Transform listener)
{
    // Parsear comandos
    // Ejecutar cada comando como coroutine
    // Esperar a que termine antes de continuar
}
```

### Scripts del Usuario (User Script)

#### UserScript en DialogueEntry

```csharp
public string userScript;  // Script Lua personalizado
```

**Ejecución**:
```csharp
if (!string.IsNullOrEmpty(entry.userScript))
{
    Lua.Run(entry.userScript, DialogueDebug.logInfo, m_allowLuaExceptions);
}
```

#### Ejemplos de User Script

```lua
-- Cambiar variable
Variable["HasKey"] = true

-- Incrementar contador
Variable["TalkCount"] = Variable["TalkCount"] + 1

-- Llamar función personalizada
MyCustomFunction()

-- Cambiar estado de item
Item["Sword"]["Durability"] = Item["Sword"]["Durability"] - 10
```

### Eventos y Callbacks

#### Eventos del Sistema

```csharp
// En DialogueSystemController
public static event SubtitleDelegate OnConversationLine;
public static event SubtitleDelegate OnConversationLineEnd;
public static event TransformDelegate OnConversationStart;
public static event TransformDelegate OnConversationEnd;
```

#### Callbacks Disponibles

1. **OnConversationStart**: Cuando inicia una conversación
2. **OnConversationEnd**: Cuando termina una conversación
3. **OnConversationLine**: Cuando se muestra una línea
4. **OnConversationLineEnd**: Cuando termina una línea
5. **OnSelectedResponse**: Cuando el jugador selecciona una respuesta

#### Uso de Eventos

```csharp
// Suscribirse a eventos
DialogueManager.instance.OnConversationStart += OnMyConversationStart;
DialogueManager.instance.OnConversationEnd += OnMyConversationEnd;

// En el handler
void OnMyConversationStart(Transform actor)
{
    Debug.Log("Conversación iniciada con: " + actor.name);
}
```

### Diagrama de Flujo de Ejecución Completo

```
[StartConversation]
    ↓
[Crear ConversationModel]
    ↓
[Obtener Conversation de Database]
    ↓
[Encontrar Entry Raíz]
    ↓
[ExecuteEntry(raíz)]
    ├─→ [Ejecutar userScript (Lua)]
    ├─→ [Disparar onExecute event]
    └─→ [Ejecutar Sequence (Sequencer)]
    ↓
[EvaluateLinks(raíz)]
    ├─→ [Por cada link saliente]
    │   ├─→ [Evaluar condiciones (Lua)]
    │   ├─→ [Verificar isDialogueEntryValid]
    │   ├─→ [Si es válido]
    │   │   ├─→ [Es Group?]
    │   │   │   └─→ [EvaluateLinks(grupo)]
    │   │   ├─→ [Es NPC?]
    │   │   │   └─→ [Agregar a npcResponses]
    │   │   └─→ [Es PC?]
    │   │       └─→ [Agregar a pcResponses]
    │   └─→ [Si no es válido]
    │       ├─→ [falseConditionAction == "Passthrough"?]
    │       │   └─→ [EvaluateLinks(destino)]
    │       └─→ [Bloquear link]
    ↓
[Crear ConversationState]
    ├─→ subtitle
    ├─→ npcResponses[]
    └─→ pcResponses[]
    ↓
[GotoState(firstState)]
    ↓
[Mostrar Subtítulo]
    ↓
[OnFinishedSubtitle]
    ├─→ [Tiene NPC Response?]
    │   └─→ [GotoState(nextNPCState)]
    ├─→ [Tiene PC Responses?]
    │   ├─→ [Auto-response?]
    │   │   └─→ [GotoState(autoState)]
    │   └─→ [Mostrar Menú]
    │       └─→ [OnSelectedResponse]
    │           └─→ [GotoState(selectedState)]
    └─→ [No hay respuestas]
        └─→ [Close() - Fin]
```

---

## Resumen de Conceptos Clave

### Flujo de Ejecución

| Paso | Descripción | Clase Responsable |
|------|-------------|-------------------|
| Inicio | `StartConversation()` | `DialogueSystemController` |
| Crear Modelo | `ConversationModel()` | `ConversationModel` |
| Evaluar Links | `EvaluateLinks()` | `ConversationModel` |
| Crear Estado | `GetState()` | `ConversationModel` |
| Navegar | `GotoState()` | `ConversationController` |
| Mostrar UI | `StartSubtitle()` / `StartResponses()` | `ConversationView` |
| Selección | `OnSelectedResponse()` | `ConversationController` |
| Fin | `Close()` | `ConversationController` |

### Tipos de Nodos

| Tipo | Característica | Uso |
|------|----------------|-----|
| Root | `isRoot = true` | Punto de entrada |
| Group | `isGroup = true` | Organización y condiciones grupales |
| NPC | `IsPlayer = false` | Diálogo automático de NPC |
| PC | `IsPlayer = true` | Respuesta del jugador |

### Sistema de Links

| Característica | Descripción |
|----------------|-------------|
| Prioridad | High → Normal → Low |
| Condiciones | Evaluadas con Lua |
| Cross-Conversation | Pueden conectar diferentes conversaciones |
| Passthrough | Pueden pasar condiciones falsas a hijos |

### Sistema de Scripting

| Componente | Propósito | Ejemplo |
|------------|-----------|---------|
| `conditionsString` | Condiciones de entrada | `"Variable['HasKey'] == true"` |
| `userScript` | Script Lua del usuario | `"Variable['Count'] = Variable['Count'] + 1"` |
| `Sequence` | Comandos del Sequencer | `"Camera(Closeup); Audio(Hello)"` |
| Variables Lua | Estado del diálogo | `Variable["HasKey"]`, `Actor["Player"]["Score"]` |

---

## Próximos Pasos (FASE 0.3)

1. **Análisis del Editor** de diálogos
2. **Análisis del sistema de importación/exportación**

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
