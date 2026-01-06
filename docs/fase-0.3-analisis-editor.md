# FASE 0.3: Análisis del Editor - Pixel Crushers Dialogue System

## 📋 Índice

1. [Editor de Diálogos](#editor-de-diálogos)
2. [Sistema de Importación/Exportación](#sistema-de-importaciónexportación)

---

## Editor de Diálogos

### Acceso al Editor

El editor de diálogos se accede desde el menú de Unity:

```
Tools → Pixel Crushers → Dialogue System → Dialogue Editor
```

**Código de acceso**:
```csharp
[MenuItem("Tools/Pixel Crushers/Dialogue System/Dialogue Editor", false, -1)]
public static DialogueEditorWindow OpenDialogueEditorWindow()
{
    var window = GetWindow<DialogueEditorWindow>("Dialogue");
    window.OnSelectionChange();
    return window;
}
```

### Estructura del Editor

El editor está dividido en múltiples archivos parciales (`partial class DialogueEditorWindow`):

#### Archivos Principales

| Archivo | Responsabilidad |
|---------|----------------|
| `DialogueEditorWindow.cs` | Clase base y acceso al editor |
| `DialogueEditorWindowMain.cs` | Ventana principal y tabs |
| `DialogueEditorWindowConversationSection.cs` | Sección de conversaciones |
| `DialogueEditorWindowDialogueTreeSection.cs` | Editor estilo outline |
| `DialogueEditorWindowConversationNodeEditor.cs` | Editor de nodos (grafo) |
| `DialogueEditorWindowFieldsSection.cs` | Campos de diálogo |
| `DialogueEditorWindowActorSection.cs` | Sección de actores |
| `DialogueEditorWindowItemSection.cs` | Sección de items |
| `DialogueEditorWindowVariableSection.cs` | Sección de variables |
| `DialogueEditorWindowLocalization.cs` | Localización |

### Creación de Conversaciones

#### Método Principal

```csharp
private void AddNewConversationToOutlineEditor()
{
    Conversation newConversation = template.CreateConversation(
        GetNextConversationID(), 
        "New Conversation"
    );
    database.conversations.Add(newConversation);
    OpenConversation(newConversation);
}
```

#### Proceso de Creación

1. **Obtener ID único**: `GetNextConversationID()`
2. **Crear conversación desde template**: `template.CreateConversation(id, title)`
3. **Agregar a base de datos**: `database.conversations.Add(newConversation)`
4. **Abrir conversación**: `OpenConversation(newConversation)`

#### Creación desde Template

El editor soporta crear conversaciones desde templates:

```csharp
// Templates disponibles
- Quest Conversation (built-in)
- From Template JSON (custom)
```

**Ejemplo**:
```csharp
private void CreateQuestConversationFromTemplate()
{
    // Crea una conversación con estructura predefinida para quests
    Conversation questConversation = CreateConversationFromQuestTemplate();
    database.conversations.Add(questConversation);
    OpenConversation(questConversation);
}
```

### Interfaz de Edición de Nodos

#### Dos Modos de Edición

1. **Node Editor (Editor de Nodos)**: Visualización tipo grafo
2. **Outline Editor (Editor Outline)**: Visualización tipo árbol

#### Modo Node Editor

**Características**:
- Visualización tipo grafo con nodos y conexiones
- Zoom y pan del canvas
- Arrastrar y soltar nodos
- Crear links arrastrando entre nodos
- Selección múltiple con lasso
- Agrupación de nodos

**Estructura**:
```csharp
private void DrawConversationSectionNodeStyle()
{
    DrawNodeEditorTopControls();  // Controles superiores
    DrawCanvas();                  // Canvas con nodos
    HandleEmptyCanvasEvents();    // Eventos del canvas
    HandleKeyEvents();            // Atajos de teclado
}
```

#### Modo Outline Editor

**Características**:
- Visualización tipo árbol jerárquico
- Foldouts para expandir/colapsar
- Edición inline de texto
- Navegación rápida

**Estructura**:
```csharp
private void DrawConversationSectionOutlineStyle()
{
    DrawConversations();  // Lista de conversaciones
    DrawDialogueTree();   // Árbol de diálogos
}
```

### Sistema de Visualización del Grafo

#### Canvas y Zoom

```csharp
[SerializeField]
private float _zoom = 1.0f;  // Nivel de zoom (0.1 - 2.0)
private Vector2 canvasScrollPosition;  // Posición de scroll
private Rect _zoomArea;  // Área de zoom
```

**Funcionalidades**:
- Zoom con rueda del mouse (0.1x - 2.0x)
- Pan arrastrando con botón medio
- Scroll con barras de desplazamiento
- Zoom bloqueado opcional

#### Dibujo de Nodos

```csharp
private void DrawNode(DialogueEntry entry, Rect rect)
{
    // Dibujar fondo del nodo
    DrawNodeBackground(rect, entry);
    
    // Dibujar contenido
    DrawNodeContent(entry, rect);
    
    // Dibujar iconos (Sequence, Conditions, Script, Event)
    DrawNodeIcons(entry, rect);
    
    // Dibujar handles de resize
    DrawResizeHandles(rect);
}
```

#### Dibujo de Links (Conexiones)

```csharp
private void DrawLink(Vector3 start, Vector3 end, Color color, bool wide)
{
    // Dibujar línea entre nodos
    // Color diferente según prioridad
    // Ancho diferente según tipo
}
```

**Colores de Links**:
- **Amarillo**: Links salientes (outgoing)
- **Marrón**: Links entrantes (incoming)
- **Ancho**: Depende de la prioridad

#### Posicionamiento de Nodos

Cada `DialogueEntry` tiene un `canvasRect`:

```csharp
public Rect canvasRect = new Rect(0, 0, CanvasRectWidth, CanvasRectHeight);
```

**Operaciones**:
- **Arrastrar**: Mover nodo manteniendo links
- **Snap to Grid**: Alinear a grid opcional
- **Auto-arrange**: Organización automática
- **Multi-select**: Seleccionar múltiples nodos

### Herramientas de Organización

#### Auto-arrange

Organiza automáticamente los nodos en el canvas:

```csharp
private void AutoArrangeNodes()
{
    // Organiza nodos en layout jerárquico
    // Respeta estructura de links
    // Evita superposiciones
}
```

#### Reordenamiento de IDs

Reordena los IDs de los diálogos en orden depth-first:

```csharp
public static void ReorderIDsInConversationDepthFirst(
    DialogueDatabase database, 
    Conversation conversation
)
{
    // Determina nuevo orden
    var newIDs = new Dictionary<int, int>();
    DetermineNewEntryID(database, conversation, root, newIDs, ref nextID);
    
    // Cambia IDs en todos los links
    ChangeEntryIDEverywhere(database, conversationID, oldID, newID);
    
    // Ordena entradas
    conversation.dialogueEntries.Sort((x, y) => x.id.CompareTo(y.id));
}
```

#### Agrupación de Nodos

Soporte para `EntryGroup` (grupos de nodos):

```csharp
public class EntryGroup
{
    public string title;
    public Rect rect;
    public List<DialogueEntry> entries;
}
```

**Características**:
- Agrupar nodos visualmente
- Colapsar/expandir grupos
- Mover grupos completos
- Organización jerárquica

#### Búsqueda y Filtrado

```csharp
private void DrawDialogueTreeSearchBar()
{
    // Barra de búsqueda
    searchText = EditorGUILayout.TextField("Search", searchText);
    
    // Filtrar nodos por texto
    FilterDialogueTree(searchText);
}
```

#### Validación de Base de Datos

El editor incluye validación:

```csharp
private void DrawDatabaseIssuesCheckSection()
{
    // Verifica:
    // - IDs duplicados
    // - Links rotos
    // - Referencias inválidas
    // - Campos requeridos faltantes
}
```

### Importación/Exportación de Datos

#### Exportación

El editor soporta múltiples formatos de exportación:

##### 1. Chat Mapper Export

```csharp
public class ChatMapperExporter
{
    public static void Export(DialogueDatabase database, string filename)
    {
        // Exporta a formato Chat Mapper XML
        // Incluye conversaciones, actores, items, variables
    }
}
```

**Formato**: XML compatible con Chat Mapper

##### 2. CSV Export

```csharp
public class CSVExporter
{
    public static void Export(DialogueDatabase database, string filename)
    {
        // Exporta a CSV para edición en Excel
        // Una fila por diálogo
    }
}
```

**Uso**: Edición masiva en Excel/Google Sheets

##### 3. Language Text Export

```csharp
public class LanguageTextExporter
{
    public static void Export(DialogueDatabase database, string language)
    {
        // Exporta texto de un idioma específico
        // Para traducción externa
    }
}
```

**Uso**: Envío a traductores

##### 4. Screenplay Export

```csharp
public class ScreenplayExporter
{
    public static void Export(DialogueDatabase database, string filename)
    {
        // Exporta en formato guion cinematográfico
    }
}
```

**Formato**: Formato estándar de guion

##### 5. Voiceover Script Export

```csharp
public class VoiceoverScriptExporter
{
    public static void Export(DialogueDatabase database, string filename)
    {
        // Exporta script para grabación de voz
        // Incluye información de timing
    }
}
```

**Uso**: Preparación para grabación de audio

##### 6. Proofreading Export

```csharp
public class ProofreadingExporter
{
    public static void Export(DialogueDatabase database, string filename)
    {
        // Exporta para corrección de texto
        // Formato legible para revisión
    }
}
```

**Uso**: Revisión y corrección de texto

#### Importación

El editor soporta múltiples formatos de importación:

##### 1. Chat Mapper Import

**Ubicación**: `Scripts/Editor/Tools/Importers/Chat Mapper/`

```csharp
public class ChatMapperConverter : EditorWindow
{
    [MenuItem("Tools/Pixel Crushers/Dialogue System/Import/Chat Mapper...")]
    public static void Init()
    {
        // Abre ventana de importación
    }
    
    public void Convert()
    {
        // Convierte proyecto Chat Mapper (.cmp o .xml)
        // a DialogueDatabase
    }
}
```

**Formatos soportados**:
- `.cmp` (Chat Mapper Project) - Requiere licencia comercial
- `.xml` (Chat Mapper Export XML) - Licencia indie

**Proceso**:
1. Seleccionar archivo Chat Mapper
2. Configurar opciones (portraits, encoding, etc.)
3. Convertir a DialogueDatabase
4. Guardar asset en Unity

##### 2. Articy Draft Import

**Ubicación**: `Scripts/Editor/Tools/Importers/Articy/`

```csharp
public class ArticyConverterWindow : AbstractConverterWindow
{
    // Soporta múltiples versiones de Articy:
    // - Articy 1.4
    // - Articy 2.2
    // - Articy 2.4
    // - Articy 3.1
    // - Articy 4.0
}
```

**Formatos soportados**:
- `.articy` (Articy Draft Project)
- `.xml` (Articy Export XML)

**Características**:
- Importa conversaciones, actores, items, variables
- Soporta múltiples esquemas de Articy
- Mapeo de campos personalizado
- Preserva estructura de diálogo

##### 3. Celtx Import

**Ubicación**: `Scripts/Editor/Tools/Importers/Celtx/`

```csharp
public class CeltxConverterWindow : AbstractConverterWindow
{
    public void Convert()
    {
        // Convierte proyecto Celtx
        // a DialogueDatabase
    }
}
```

**Formato**: `.celtx` (Celtx Project)

##### 4. Yarn Spinner Import

**Ubicación**: `Scripts/Editor/Tools/Importers/Yarn2/`

```csharp
public class Yarn2ImporterWindow : AbstractConverterWindow<YarnImporterPrefs>
{
    public void Import()
    {
        // Importa proyecto Yarn Spinner
        // a DialogueDatabase
    }
}
```

**Formato**: `.yarn` (Yarn Spinner Script)

**Características**:
- Parser ANTLR para Yarn
- Convierte nodos Yarn a DialogueEntry
- Preserva condiciones y scripts

##### 5. JSON Import

**Ubicación**: `Scripts/Editor/Tools/Importers/JSON/`

```csharp
public class JsonImportWindow : EditorWindow
{
    public void Import()
    {
        // Importa desde JSON personalizado
        // Formato flexible
    }
}
```

**Formato**: `.json` (JSON personalizado)

### Estructura de Archivos Exportados

#### Chat Mapper XML

```xml
<?xml version="1.0" encoding="utf-8"?>
<ChatMapperProject>
    <Actors>
        <Actor>
            <ID>1</ID>
            <Name>Player</Name>
            <Fields>...</Fields>
        </Actor>
    </Actors>
    <Conversations>
        <Conversation>
            <ID>1</ID>
            <Title>Main Conversation</Title>
            <DialogEntries>
                <DialogEntry>
                    <ID>0</ID>
                    <Fields>...</Fields>
                    <OutgoingLinks>
                        <Link>
                            <OriginConvoID>1</OriginConvoID>
                            <OriginDialogID>0</OriginDialogID>
                            <DestinationConvoID>1</DestinationConvoID>
                            <DestinationDialogID>1</DestinationDialogID>
                        </Link>
                    </OutgoingLinks>
                </DialogEntry>
            </DialogEntries>
        </Conversation>
    </Conversations>
</ChatMapperProject>
```

#### CSV Export

```csv
Conversation,Entry ID,Title,Dialogue Text,Menu Text,Actor,Conversant,Conditions,Script,Sequence
Main Conversation,0,START,Hello!,Hello!,Player,NPC,,,
Main Conversation,1,Response 1,Yes,I agree,Player,NPC,Variable["HasKey"]==true,,
```

### Proceso de Conversión de Formatos

#### Flujo General

```
[Formato Externo]
    ↓
[Parser/Reader]
    ↓
[Conversión a Estructura Interna]
    ↓
[Validación]
    ↓
[DialogueDatabase]
    ↓
[Guardar Asset Unity]
```

#### Ejemplo: Chat Mapper Import

```csharp
// 1. Leer archivo XML
ChatMapperProject chatMapperProject = ChatMapperProject.LoadFromFile(filename);

// 2. Convertir a DialogueDatabase
DialogueDatabase database = new DialogueDatabase();
foreach (var cmActor in chatMapperProject.Actors)
{
    Actor actor = ConvertActor(cmActor);
    database.actors.Add(actor);
}

foreach (var cmConversation in chatMapperProject.Conversations)
{
    Conversation conversation = ConvertConversation(cmConversation);
    database.conversations.Add(conversation);
}

// 3. Validar
ValidateDatabase(database);

// 4. Guardar
AssetDatabase.CreateAsset(database, outputPath);
```

### Validación de Datos

#### Validaciones Realizadas

1. **IDs Únicos**: Verificar que no haya IDs duplicados
2. **Links Válidos**: Verificar que todos los links apunten a entradas existentes
3. **Referencias**: Verificar que actores, items, variables referenciados existan
4. **Campos Requeridos**: Verificar campos obligatorios
5. **Estructura**: Verificar que haya un nodo raíz por conversación

#### Herramienta de Validación

```csharp
private void DrawDatabaseIssuesCheckSection()
{
    if (GUILayout.Button("Check Database Issues"))
    {
        List<string> issues = ValidateDatabase(database);
        if (issues.Count == 0)
        {
            EditorUtility.DisplayDialog("Validation", "No issues found!", "OK");
        }
        else
        {
            ShowIssuesWindow(issues);
        }
    }
}
```

### Características Avanzadas del Editor

#### 1. Undo/Redo

```csharp
private void RecordUndo(string operation)
{
    Undo.RecordObject(database, operation);
    EditorUtility.SetDirty(database);
}
```

#### 2. Templates

Sistema de templates para crear conversaciones predefinidas:

```csharp
public class ConversationTemplate
{
    public string name;
    public Conversation structure;
    
    public Conversation CreateFromTemplate(int id, string title)
    {
        // Crea conversación desde template
    }
}
```

#### 3. Localización

Soporte para múltiples idiomas:

```csharp
private void DrawLocalizationSection()
{
    // Seleccionar idioma
    // Editar texto localizado
    // Exportar/importar traducciones
}
```

#### 4. Campos Personalizados

Sistema de campos personalizados:

```csharp
public class CustomFieldType
{
    public string name;
    public FieldType type;
    public string defaultValue;
}
```

#### 5. Búsqueda Avanzada

```csharp
private void DrawSearchBar()
{
    // Búsqueda por:
    // - Texto de diálogo
    // - Título de conversación
    // - ID de entrada
    // - Actor
    // - Condiciones
    // - Scripts
}
```

### Atajos de Teclado

| Atajo | Acción |
|-------|--------|
| `Ctrl+N` | Nueva conversación |
| `Ctrl+D` | Duplicar conversación |
| `Delete` | Eliminar entrada seleccionada |
| `Ctrl+C` | Copiar entrada |
| `Ctrl+V` | Pegar entrada |
| `Ctrl+Z` | Undo |
| `Ctrl+Y` | Redo |
| `F` | Frame selección |
| `A` | Auto-arrange |
| `G` | Toggle grid |

### Resumen de Funcionalidades

| Funcionalidad | Descripción |
|---------------|-------------|
| **Crear Conversaciones** | Desde template o vacía |
| **Editar Nodos** | Modo grafo o outline |
| **Visualización Grafo** | Zoom, pan, links visuales |
| **Organización** | Auto-arrange, grupos, snap to grid |
| **Búsqueda** | Por texto, ID, actor, etc. |
| **Validación** | Verificar integridad de datos |
| **Importación** | Chat Mapper, Articy, Celtx, Yarn, JSON |
| **Exportación** | Chat Mapper, CSV, Screenplay, Voiceover, etc. |
| **Localización** | Múltiples idiomas |
| **Templates** | Conversaciones predefinidas |
| **Undo/Redo** | Sistema completo de deshacer |

---

## Resumen de Formatos Soportados

### Importación

| Formato | Extensión | Ubicación |
|---------|-----------|-----------|
| Chat Mapper | `.cmp`, `.xml` | `Tools/Importers/Chat Mapper/` |
| Articy Draft | `.articy`, `.xml` | `Tools/Importers/Articy/` |
| Celtx | `.celtx` | `Tools/Importers/Celtx/` |
| Yarn Spinner | `.yarn` | `Tools/Importers/Yarn2/` |
| JSON | `.json` | `Tools/Importers/JSON/` |

### Exportación

| Formato | Uso | Ubicación |
|---------|-----|-----------|
| Chat Mapper XML | Intercambio con Chat Mapper | `Export/ChatMapperExporter.cs` |
| CSV | Edición masiva | `Export/CSVExporter.cs` |
| Language Text | Traducción | `Export/LanguageTextExporter.cs` |
| Screenplay | Guion cinematográfico | `Export/ScreenplayExporter.cs` |
| Voiceover Script | Grabación de voz | `Export/VoiceoverScriptExporter.cs` |
| Proofreading | Corrección de texto | `Export/ProofreadingExporter.cs` |

---

## Próximos Pasos (FASE 0.4)

1. **Análisis del Sistema de UI**
2. **Análisis del sistema de personalización**

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
