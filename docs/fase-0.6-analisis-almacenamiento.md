# FASE 0.6: Análisis del Sistema de Almacenamiento - Pixel Crushers Dialogue System

## 📋 Índice

1. [Sistema de Almacenamiento de Pixel Crushers](#sistema-de-almacenamiento-de-pixel-crushers)
2. [Comparación con Nuestra Base de Datos v2](#comparación-con-nuestra-base-de-datos-v2)
3. [Mapeo de Conceptos](#mapeo-de-conceptos)
4. [Sistema de Recursos](#sistema-de-recursos)

---

## Sistema de Almacenamiento de Pixel Crushers

### DialogueDatabase - ScriptableObject

**Ubicación**: `Scripts/MVC/Model/Data/DialogueDatabase.cs`

**Tipo**: `ScriptableObject` (Unity Asset)

**Propósito**: Almacena todos los datos de diálogos como un asset de Unity que puede ser editado en el editor y cargado en runtime.

#### Estructura de Datos

```csharp
public class DialogueDatabase : ScriptableObject
{
    // Metadatos
    public string version;
    public string author;
    public string description;
    public string globalUserScript;
    
    // Configuración
    public EmphasisSetting[] emphasisSettings;
    public int baseID = 1;
    
    // Datos principales
    public List<Actor> actors = new List<Actor>();
    public List<Item> items = new List<Item>();
    public List<Location> locations = new List<Location>();
    public List<Variable> variables = new List<Variable>();
    public List<Conversation> conversations = new List<Conversation>();
    
    // Sincronización
    public SyncInfo syncInfo;
    public string templateJson = string.Empty;
    
    // Caches (runtime)
    private Dictionary<string, Actor> actorNameCache;
    private Dictionary<string, Item> itemNameCache;
    private Dictionary<string, Location> locationNameCache;
    private Dictionary<string, Variable> variableNameCache;
    private Dictionary<string, Conversation> conversationTitleCache;
}
```

#### Características

1. **ScriptableObject**: Asset de Unity que persiste entre sesiones
2. **Editable en Editor**: Se edita visualmente en el Dialogue Editor
3. **Serializable**: Unity serializa automáticamente todos los datos
4. **Caches en Runtime**: Diccionarios para búsquedas rápidas por nombre
5. **Múltiples Bases de Datos**: Soporte para bases de datos adicionales

#### Conversation - Estructura

```csharp
public class Conversation : Asset
{
    public int id;
    public string Title;
    public int ActorID;        // Quien habla
    public int ConversantID;   // Quien escucha
    public List<DialogueEntry> dialogueEntries;
    public List<Link> links;
    public List<Field> fields;
}
```

#### DialogueEntry - Estructura

```csharp
public class DialogueEntry : Asset
{
    public int id;
    public int conversationID;
    public int ActorID;
    public string Title;
    public string DialogueText;
    public string MenuText;    // Texto para menú de respuestas
    public bool isGroup;       // Si es un grupo (agrupación)
    public List<Link> outgoingLinks;
    public List<Link> incomingLinks;
    public List<Field> fields;
    public string ConditionsString;
    public string UserScript;
    public string Sequence;
}
```

### Sistema de Persistencia - PersistentDataManager

**Ubicación**: `Scripts/Save System/PersistentDataManager.cs`

**Propósito**: Gestiona el guardado y carga de datos del juego usando el entorno Lua.

#### Características

1. **Lua como Motor de Persistencia**: Todos los datos se guardan como código Lua
2. **Variables Dinámicas**: Variables del diálogo se almacenan en Lua
3. **SimStatus**: Estado de simulación de conversaciones (WasDisplayed, etc.)
4. **Quest States**: Estados de misiones/quests
5. **Actor Data**: Datos de actores (opcional)
6. **Relationship Data**: Relaciones entre actores (opcional)

#### Datos que se Guardan

```csharp
public static string GetSaveData()
{
    Record();  // Permite que objetos registren su estado
    StringBuilder sb = new StringBuilder();
    
    // Variables
    AppendVariableData(sb);
    
    // Items (Quests) - Solo State por defecto
    AppendItemData(sb);
    
    // Locations - Nada por defecto
    AppendLocationData(sb);
    
    // Actors - Todos los datos (si includeActorData = true)
    if (includeActorData) AppendActorData(sb);
    
    // Conversations - Solo SimStatus
    AppendConversationData(sb);
    
    // Relationships
    if (includeRelationshipAndStatusData) 
        AppendRelationshipAndStatusTables(sb);
    
    return sb.ToString();
}
```

#### Formato de Datos Guardados (Lua)

```lua
-- Variables
Variable["puntuacion"] = 100
Variable["decisiones_tomadas"] = 5

-- Quest States
Item["Mision1"].State = "active"
Item["Mision2"].State = "success"

-- Conversation SimStatus
Conversation[1].Dialog[1].SimStatus = "WasDisplayed"
Conversation[1].Dialog[2].SimStatus = "WasOffered"

-- Actor Data (si está habilitado)
Actor["Player"].Field["Health"] = 100
Actor["NPC1"].Field["MetPlayer"] = true
```

#### Configuración de Persistencia

```csharp
public static class PersistentDataManager
{
    // Qué incluir en el guardado
    public static bool includeActorData = true;
    public static bool includeAllItemData = false;
    public static bool includeLocationData = false;
    public static bool includeAllConversationFields = false;
    public static bool includeSimStatus = false;
    public static bool includeRelationshipAndStatusData = true;
    
    // Inicialización de nuevas variables
    public static bool initializeNewVariables = true;
}
```

### Sistema de Guardado - Save System Integration

**Ubicación**: `Scripts/Save System/DialogueSystemSaver.cs`

**Integración**: Pixel Crushers Common Library Save System

#### DialogueSystemSaver

```csharp
public class DialogueSystemSaver : Saver
{
    // Guardar datos raw (más rápido, más grande)
    public bool saveRawData = false;
    
    public override string RecordData()
    {
        if (saveRawData)
        {
            // Guardar como bytes raw
            var rawData = new RawData();
            rawData.bytes = PersistentDataManager.GetRawData();
            return SaveSystem.Serialize(rawData);
        }
        else
        {
            // Guardar como string Lua
            return PersistentDataManager.GetSaveData();
        }
    }
    
    public override void ApplyData(string data)
    {
        if (saveRawData)
        {
            var rawData = SaveSystem.Deserialize<RawData>(data);
            PersistentDataManager.ApplyRawData(rawData.bytes);
        }
        else
        {
            PersistentDataManager.ApplySaveData(data);
        }
    }
}
```

#### ConversationStateSaver

**Ubicación**: `Scripts/Save System/ConversationStateSaver.cs`

**Propósito**: Guarda el estado de la conversación actual para reanudarla después de cargar.

```csharp
public class ConversationStateSaver : Saver
{
    [Serializable]
    public class Data
    {
        public int conversationID;
        public int entryID;
        public string actorName;
        public string conversantName;
        public List<string> actorGOs;
        public List<SubtitlePanelNumber> actorGOPanels;
        public List<int> actorIDs;
        public List<SubtitlePanelNumber> actorIDPanels;
        public string accumulatedText;
    }
    
    public override string RecordData()
    {
        // Guardar estado actual de conversación
        var data = new Data();
        data.conversationID = DialogueManager.currentConversationID;
        data.entryID = DialogueManager.currentConversationState.subtitle.dialogueEntry.id;
        // ... más datos
        return SaveSystem.Serialize(data);
    }
}
```

### Sistema de Checkpoints

**No hay sistema de checkpoints nativo**, pero se puede implementar usando:

1. **Save System**: Guardar en puntos específicos
2. **ConversationStateSaver**: Reanudar conversaciones
3. **PersistentDataManager**: Guardar estado completo del juego

---

## Comparación con Nuestra Base de Datos v2

### Arquitectura de Almacenamiento

| Aspecto | Pixel Crushers | Nuestra Base de Datos v2 |
|---------|----------------|--------------------------|
| **Tipo de Almacenamiento** | ScriptableObject (Unity Asset) | Base de Datos MySQL (Laravel) |
| **Persistencia** | Archivo .asset en Unity | Tablas relacionales en MySQL |
| **Edición** | Dialogue Editor (Unity) | Editor web/API (Laravel) |
| **Runtime** | Carga desde Resources/AssetBundles | API REST desde servidor |
| **Variables** | Lua environment | JSON en `sesiones_dialogos_v2.variables` |
| **Estado de Conversación** | SimStatus en Lua | `sesiones_dialogos_v2` + `decisiones_dialogo_v2` |
| **Historial** | No nativo (se puede implementar) | `sesiones_dialogos_v2.historial_nodos` |
| **Multi-usuario** | No soportado | Sí (sesiones por usuario) |
| **Evaluación** | No nativo | `decisiones_dialogo_v2` con campos de evaluación |
| **Audio** | No nativo | `decisiones_dialogo_v2.audio_mp3` + `sesiones_dialogos_v2.audio_mp3_completo` |

### Mapeo de Estructuras

#### DialogueDatabase → dialogos_v2

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `DialogueDatabase.version` | `dialogos_v2.version` | Versión del diálogo |
| `DialogueDatabase.author` | `dialogos_v2.creado_por` | Creador (FK a users) |
| `DialogueDatabase.description` | `dialogos_v2.descripcion` | Descripción |
| `DialogueDatabase.conversations[]` | `dialogos_v2` (1 registro = 1 diálogo) | Estructura diferente |
| `DialogueDatabase.actors[]` | `roles_disponibles` (tabla externa) | Actores = Roles |
| `DialogueDatabase.variables[]` | `sesiones_dialogos_v2.variables` (JSON) | Variables por sesión |
| `DialogueDatabase.items[]` | No mapeado (no usamos quests) | - |
| `DialogueDatabase.locations[]` | No mapeado | - |

#### Conversation → dialogos_v2 + nodos_dialogo_v2

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `Conversation.id` | `dialogos_v2.id` | ID único |
| `Conversation.Title` | `dialogos_v2.nombre` | Nombre del diálogo |
| `Conversation.ActorID` | `nodos_dialogo_v2.rol_id` | Actor que habla (por nodo) |
| `Conversation.ConversantID` | `nodos_dialogo_v2.conversant_id` | Quien escucha (por nodo) |
| `Conversation.dialogueEntries[]` | `nodos_dialogo_v2[]` | Entradas = Nodos |
| `Conversation.links[]` | `respuestas_dialogo_v2[]` | Links = Respuestas |

#### DialogueEntry → nodos_dialogo_v2

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `DialogueEntry.id` | `nodos_dialogo_v2.id` | ID único |
| `DialogueEntry.conversationID` | `nodos_dialogo_v2.dialogo_id` | FK a diálogo |
| `DialogueEntry.ActorID` | `nodos_dialogo_v2.rol_id` | Actor que habla |
| `DialogueEntry.Title` | `nodos_dialogo_v2.titulo` | Título del nodo |
| `DialogueEntry.DialogueText` | `nodos_dialogo_v2.contenido` | Texto del diálogo |
| `DialogueEntry.MenuText` | `nodos_dialogo_v2.menu_text` | Texto para menú |
| `DialogueEntry.isGroup` | `nodos_dialogo_v2.tipo = 'agrupacion'` | Si es grupo |
| `DialogueEntry.ConditionsString` | `nodos_dialogo_v2.condiciones` (JSON) | Condiciones |
| `DialogueEntry.UserScript` | `nodos_dialogo_v2.consecuencias` (JSON) | Scripts/consecuencias |
| `DialogueEntry.Sequence` | `nodos_dialogo_v2.metadata` (JSON) | Secuencias |
| `DialogueEntry.outgoingLinks[]` | `respuestas_dialogo_v2[]` | Links salientes = Respuestas |
| Posición (en editor) | `nodos_dialogo_v2.posicion_x`, `posicion_y` | Posición directa |

#### Link → respuestas_dialogo_v2

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `Link.originConversationID` | `respuestas_dialogo_v2.nodo_padre_id` | Nodo origen |
| `Link.originDialogueID` | `respuestas_dialogo_v2.nodo_padre_id` | Mismo que arriba |
| `Link.destinationConversationID` | `respuestas_dialogo_v2.nodo_siguiente_id` | Nodo destino |
| `Link.destinationDialogueID` | `respuestas_dialogo_v2.nodo_siguiente_id` | Mismo que arriba |
| `Link.isConnector` | No mapeado | - |
| Texto de respuesta | `respuestas_dialogo_v2.texto` | Texto de la opción |
| Condiciones | `respuestas_dialogo_v2.condiciones` (JSON) | Condiciones |
| Consecuencias | `respuestas_dialogo_v2.consecuencias` (JSON) | Consecuencias |

#### SimStatus → sesiones_dialogos_v2 + decisiones_dialogo_v2

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `Conversation[#].Dialog[#].SimStatus` | `decisiones_dialogo_v2` | Estado por decisión |
| `"WasDisplayed"` | `decisiones_dialogo_v2.created_at` | Fue mostrado (timestamp) |
| `"WasOffered"` | `decisiones_dialogo_v2` (registro existe) | Fue ofrecido |
| `"WasSelected"` | `decisiones_dialogo_v2.respuesta_id` (no null) | Fue seleccionado |
| Historial de nodos | `sesiones_dialogos_v2.historial_nodos` (JSON) | Historial completo |

#### Variables → sesiones_dialogos_v2.variables

| Pixel Crushers | Nuestra BD v2 | Notas |
|----------------|---------------|-------|
| `Variable["nombre"]` | `sesiones_dialogos_v2.variables` (JSON) | Variables en JSON |
| Tipo (String, Number, Boolean) | JSON nativo | Tipos nativos de JSON |
| Valor inicial | `dialogos_v2.configuracion` (JSON) | Configuración inicial |
| Cambios en runtime | `sesiones_dialogos_v2.variables` (JSON) | Actualizado en sesión |

### Diferencias Clave

#### 1. Almacenamiento de Variables

**Pixel Crushers**:
- Variables en entorno Lua
- Acceso: `Variable["nombre"]`
- Persistencia: Código Lua en string

**Nuestra BD v2**:
- Variables en JSON en `sesiones_dialogos_v2.variables`
- Acceso: Parsear JSON
- Persistencia: Campo JSON en MySQL

**Ejemplo**:

```lua
-- Pixel Crushers (Lua)
Variable["puntuacion"] = 100
Variable["decisiones_tomadas"] = 5
```

```json
// Nuestra BD v2 (JSON)
{
  "puntuacion": 100,
  "decisiones_tomadas": 5
}
```

#### 2. Estado de Conversación

**Pixel Crushers**:
- SimStatus por entrada de diálogo
- Almacenado en Lua: `Conversation[#].Dialog[#].SimStatus`
- No hay historial nativo

**Nuestra BD v2**:
- Estado en `sesiones_dialogos_v2.estado`
- Historial en `sesiones_dialogos_v2.historial_nodos` (JSON)
- Decisiones individuales en `decisiones_dialogo_v2`

#### 3. Multi-usuario y Sesiones

**Pixel Crushers**:
- Diseñado para single-player
- No hay concepto de sesiones multi-usuario
- No hay tracking de decisiones por usuario

**Nuestra BD v2**:
- Diseñado para multi-usuario
- `sesiones_dialogos_v2` vinculado a `sesiones_juicios`
- `decisiones_dialogo_v2` con `usuario_id` y `rol_id`
- Soporte para usuarios no registrados

#### 4. Evaluación y Retroalimentación

**Pixel Crushers**:
- No tiene sistema de evaluación nativo
- No tiene campos para calificación de profesor

**Nuestra BD v2**:
- `decisiones_dialogo_v2.calificacion_profesor`
- `decisiones_dialogo_v2.notas_profesor`
- `decisiones_dialogo_v2.evaluado_por`
- `decisiones_dialogo_v2.fecha_evaluacion`
- `decisiones_dialogo_v2.estado_evaluacion`
- `decisiones_dialogo_v2.justificacion_estudiante`
- `decisiones_dialogo_v2.retroalimentacion`

#### 5. Audio

**Pixel Crushers**:
- No tiene sistema de audio nativo
- No guarda grabaciones de diálogos

**Nuestra BD v2**:
- `decisiones_dialogo_v2.audio_mp3` (por decisión)
- `decisiones_dialogo_v2.audio_duracion`
- `decisiones_dialogo_v2.audio_grabado_en`
- `sesiones_dialogos_v2.audio_mp3_completo` (sesión completa)
- `sesiones_dialogos_v2.audio_habilitado`

#### 6. Posicionamiento de Nodos

**Pixel Crushers**:
- Posición almacenada en metadata del DialogueEntry
- Formato interno (no estándar)

**Nuestra BD v2**:
- `nodos_dialogo_v2.posicion_x` (INTEGER)
- `nodos_dialogo_v2.posicion_y` (INTEGER)
- Campos directos, optimizados para búsquedas

---

## Mapeo de Conceptos

### Conceptos Equivalentes

| Concepto Pixel Crushers | Concepto Nuestra BD v2 | Diferencia |
|-------------------------|------------------------|------------|
| **DialogueDatabase** | `dialogos_v2` | 1 DB = 1 diálogo (no múltiples conversaciones) |
| **Conversation** | `dialogos_v2` | 1 conversación = 1 diálogo completo |
| **DialogueEntry** | `nodos_dialogo_v2` | Entrada = Nodo |
| **Link** | `respuestas_dialogo_v2` | Link = Respuesta |
| **Actor** | `roles_disponibles` | Actor = Rol |
| **Variable** | `sesiones_dialogos_v2.variables` (JSON) | Variables en JSON, no Lua |
| **SimStatus** | `decisiones_dialogo_v2` | Estado por decisión |
| **PersistentDataManager** | API REST + Base de Datos | Guardado en servidor |
| **ScriptableObject** | Tablas MySQL | Persistencia en BD |

### Conceptos Únicos de Nuestra BD v2

| Concepto | Descripción | Por qué es necesario |
|----------|-------------|----------------------|
| **sesiones_dialogos_v2** | Sesión de diálogo activa | Multi-usuario, tracking |
| **decisiones_dialogo_v2** | Decisión individual | Evaluación, audio, tracking |
| **historial_nodos** | Historial de nodos visitados | Análisis, replay |
| **evaluacion_profesor** | Campos de evaluación | Sistema educativo |
| **audio_mp3** | Grabaciones de audio | Retroalimentación |
| **usuario_registrado** | Flag de usuario registrado | Soporte usuarios anónimos |
| **es_opcion_por_defecto** | Opción por defecto | Flujo para usuarios no registrados |

---

## Sistema de Recursos

### Carga de DialogueDatabase en Pixel Crushers

#### Método 1: Resources

```csharp
// Cargar desde Resources
DialogueDatabase db = Resources.Load<DialogueDatabase>("DialogueDatabases/MainDatabase");
DialogueManager.masterDatabase = db;
```

**Ubicación**: `Assets/Resources/DialogueDatabases/`

**Ventajas**:
- Simple de usar
- No requiere configuración adicional

**Desventajas**:
- Todos los assets se incluyen en el build
- No se puede descargar dinámicamente

#### Método 2: AssetBundles

```csharp
// Registrar AssetBundle
AssetBundle bundle = AssetBundle.LoadFromFile("path/to/bundle");
DialogueSystemController.Instance.assetBundleManager.RegisterAssetBundle(bundle);

// Cargar desde bundle
DialogueDatabase db = bundle.LoadAsset<DialogueDatabase>("MainDatabase");
DialogueManager.masterDatabase = db;
```

**Ubicación**: AssetBundles externos

**Ventajas**:
- Descarga dinámica
- Actualizaciones sin recompilar
- Reducción de tamaño inicial

**Desventajas**:
- Requiere gestión de bundles
- Más complejo

#### Método 3: Asignación Directa

```csharp
// Asignar en Inspector
DialogueManager.masterDatabase = mainDatabaseAsset;
```

**Ubicación**: Inspector de Unity

**Ventajas**:
- Más simple
- No requiere código

**Desventajas**:
- Solo funciona en build
- No dinámico

### Carga de Diálogos en Nuestra BD v2

#### Método: API REST

```csharp
// Unity - Cargar diálogo desde API
public async Task<DialogoV2> LoadDialogo(int dialogoId)
{
    string url = $"{apiBaseUrl}/api/dialogos/{dialogoId}";
    HttpResponseMessage response = await httpClient.GetAsync(url);
    string json = await response.Content.ReadAsStringAsync();
    return JsonUtility.FromJson<DialogoV2>(json);
}

// Cargar estructura completa
public async Task<DialogoCompleto> LoadDialogoCompleto(int dialogoId)
{
    // Cargar diálogo
    var dialogo = await LoadDialogo(dialogoId);
    
    // Cargar nodos
    var nodos = await LoadNodos(dialogoId);
    
    // Cargar respuestas
    var respuestas = await LoadRespuestas(dialogoId);
    
    return new DialogoCompleto
    {
        dialogo = dialogo,
        nodos = nodos,
        respuestas = respuestas
    };
}
```

#### Estructura de Respuesta JSON

```json
{
  "id": 1,
  "nombre": "Diálogo de Ejemplo",
  "descripcion": "Descripción del diálogo",
  "version": "1.0.0",
  "configuracion": {
    "variables_iniciales": {
      "puntuacion": 0,
      "decisiones_tomadas": 0
    }
  },
  "metadata_unity": {
    "estilo": "moderno",
    "tema": "legal"
  },
  "nodos": [
    {
      "id": 1,
      "titulo": "Inicio",
      "contenido": "Bienvenido al diálogo",
      "tipo": "inicio",
      "posicion_x": 0,
      "posicion_y": 0,
      "es_inicial": true,
      "rol_id": 1,
      "conversant_id": 2,
      "menu_text": "Iniciar",
      "condiciones": {},
      "consecuencias": {}
    }
  ],
  "respuestas": [
    {
      "id": 1,
      "nodo_padre_id": 1,
      "nodo_siguiente_id": 2,
      "texto": "Continuar",
      "orden": 0,
      "puntuacion": 10,
      "condiciones": {},
      "consecuencias": {}
    }
  ]
}
```

### Comparación de Carga

| Aspecto | Pixel Crushers | Nuestra BD v2 |
|---------|----------------|---------------|
| **Fuente** | Resources/AssetBundles | API REST |
| **Formato** | ScriptableObject (binario) | JSON |
| **Tamaño** | Optimizado (binario) | Texto (más grande) |
| **Actualización** | Requiere rebuild/bundle | Instantánea (API) |
| **Multi-usuario** | No | Sí (por sesión) |
| **Caching** | Unity cache | Cliente HTTP cache |
| **Offline** | Sí (si está en bundle) | No (requiere conexión) |

---

## Resumen de Diferencias Arquitectónicas

### Pixel Crushers (Single-Player, Unity)

1. **ScriptableObject**: Asset de Unity, editable en editor
2. **Lua**: Motor de scripting y persistencia
3. **Resources/AssetBundles**: Carga de assets
4. **Save System**: Guardado en archivos locales
5. **SimStatus**: Estado de simulación en Lua
6. **Single-Player**: Diseñado para un jugador

### Nuestra BD v2 (Multi-User, Web + Unity)

1. **MySQL**: Base de datos relacional
2. **JSON**: Variables y metadata en JSON
3. **API REST**: Carga desde servidor
4. **Base de Datos**: Guardado en servidor
5. **Decisiones**: Tracking completo de decisiones
6. **Multi-User**: Diseñado para múltiples usuarios simultáneos
7. **Evaluación**: Sistema de evaluación integrado
8. **Audio**: Grabaciones de audio
9. **Historial**: Historial completo de nodos visitados

### Ventajas de Nuestra Arquitectura

1. ✅ **Multi-usuario**: Soporte nativo para múltiples usuarios
2. ✅ **Evaluación**: Sistema de evaluación integrado
3. ✅ **Audio**: Grabaciones de audio para retroalimentación
4. ✅ **Historial**: Tracking completo de decisiones
5. ✅ **Escalabilidad**: Base de datos escalable
6. ✅ **Actualización**: Actualizaciones sin recompilar
7. ✅ **Analytics**: Fácil análisis de datos
8. ✅ **Backup**: Backups automáticos de base de datos

### Ventajas de Pixel Crushers

1. ✅ **Editor Visual**: Editor integrado en Unity
2. ✅ **Performance**: Carga rápida desde assets
3. ✅ **Offline**: Funciona sin conexión
4. ✅ **Lua**: Motor de scripting potente
5. ✅ **SimStatus**: Sistema de estado de simulación
6. ✅ **Mature**: Sistema probado y estable

---

## Próximos Pasos (FASE 0.7)

1. **Análisis de Funcionalidades Avanzadas**
2. **Sistema de Quests (si aplica)**
3. **Sistema de Localización**

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64  
**Base de datos:** MySQL v2 (Laravel)
