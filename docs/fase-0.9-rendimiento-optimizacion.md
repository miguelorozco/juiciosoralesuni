# FASE 0.9: Análisis de Rendimiento y Optimización - Pixel Crushers Dialogue System

## 📋 Índice

1. [Optimizaciones Implementadas](#optimizaciones-implementadas)
2. [Limitaciones y Problemas Conocidos](#limitaciones-y-problemas-conocidos)
3. [Recomendaciones para Nuestra Implementación](#recomendaciones-para-nuestra-implementación)

---

## Optimizaciones Implementadas

### Sistema de Cache

#### DialogueDatabase - Cache de Assets

**Ubicación**: `Scripts/MVC/Model/Data/DialogueDatabase.cs`

**Propósito**: Cache de búsquedas rápidas por nombre/título para evitar búsquedas lineales en listas.

**Implementación**:

```csharp
public class DialogueDatabase : ScriptableObject
{
    // Caches privados (lazy-loaded)
    private Dictionary<string, Actor> actorNameCache;
    private Dictionary<string, Item> itemNameCache;
    private Dictionary<string, Location> locationNameCache;
    private Dictionary<string, Variable> variableNameCache;
    private Dictionary<string, Conversation> conversationTitleCache;
    
    private void SetupCaches()
    {
        if (actorNameCache == null) actorNameCache = CreateCache<Actor>(actors);
        if (itemNameCache == null) itemNameCache = CreateCache<Item>(items);
        if (locationNameCache == null) locationNameCache = CreateCache<Location>(locations);
        if (variableNameCache == null) variableNameCache = CreateCache<Variable>(variables);
        if (conversationTitleCache == null) conversationTitleCache = CreateCache<Conversation>(conversations);
    }
    
    private Dictionary<string, T> CreateCache<T>(List<T> assets) where T : Asset
    {
        var useTitle = typeof(T) == typeof(Conversation);
        var cache = new Dictionary<string, T>();
        if (Application.isPlaying) // Solo en runtime, no en editor
        {
            for (int i = 0; i < assets.Count; i++)
            {
                var asset = assets[i];
                var key = useTitle ? (asset as Conversation).Title : asset.Name;
                if (!cache.ContainsKey(key)) cache.Add(key, asset);
            }
        }
        return cache;
    }
    
    public Actor GetActor(string actorName)
    {
        if (string.IsNullOrEmpty(actorName)) return null;
        SetupCaches(); // Lazy initialization
        return actorNameCache.ContainsKey(actorName) 
            ? actorNameCache[actorName] 
            : actors.Find(a => string.Equals(a.Name, actorName)); // Fallback
    }
    
    public void ResetCache()
    {
        actorNameCache = null;
        itemNameCache = null;
        locationNameCache = null;
        variableNameCache = null;
        conversationTitleCache = null;
    }
}
```

**Características**:
- ✅ **Lazy Loading**: Los caches se crean solo cuando se necesitan
- ✅ **Runtime Only**: Los caches solo se construyen en runtime, no en editor
- ✅ **Fallback**: Si el cache falla, usa búsqueda lineal como respaldo
- ✅ **Reset Manual**: Método `ResetCache()` para invalidar caches cuando cambian nombres

**Rendimiento**:
- **Sin cache**: O(n) - búsqueda lineal en lista
- **Con cache**: O(1) - búsqueda en Dictionary

#### UITools - Sprite Cache

**Ubicación**: `Scripts/UI/Utility/UITools.cs`

**Propósito**: Cache de conversión de Texture2D a Sprite para evitar recrear sprites cada vez.

**Implementación**:

```csharp
public static class UITools
{
    public static Dictionary<Texture2D, Sprite> spriteCache = new Dictionary<Texture2D, Sprite>();
    
    public static Sprite CreateSprite(Texture2D texture)
    {
        if (texture == null) return null;
        if (spriteCache.ContainsKey(texture))
        {
            var cachedSprite = spriteCache[texture];
            if (cachedSprite != null) return spriteCache[texture];
        }
        var sprite = Sprite.Create(texture, new Rect(0, 0, texture.width, texture.height), Vector2.zero);
        spriteCache[texture] = sprite;
        return sprite;
    }
    
    public static void ClearSpriteCache()
    {
        spriteCache.Clear();
    }
}
```

**Características**:
- ✅ **Reutilización**: Un Texture2D se convierte a Sprite solo una vez
- ✅ **Validación**: Verifica que el sprite cacheado no sea null antes de retornarlo
- ✅ **Limpieza**: Método para limpiar el cache cuando sea necesario

**Rendimiento**:
- **Sin cache**: Crea nuevo Sprite cada vez (costoso)
- **Con cache**: Reutiliza Sprite existente (muy rápido)

#### StandardUISubtitleControls - Panel Cache

**Ubicación**: `Scripts/UI/Standard/Dialogue/StandardUISubtitleControls.cs`

**Propósito**: Cache de paneles de UI por actor para evitar búsquedas repetidas.

**Implementación**:

```csharp
public class StandardUISubtitleControls : AbstractUISubtitleControls
{
    // Cache de paneles por actor
    private Dictionary<Transform, StandardUISubtitlePanel> m_actorPanelCache = new Dictionary<Transform, StandardUISubtitlePanel>();
    
    // Cache de DialogueActor components
    private Dictionary<Transform, DialogueActor> m_dialogueActorCache = new Dictionary<Transform, DialogueActor>();
    
    // Cache de paneles por actor ID
    private Dictionary<int, StandardUISubtitlePanel> m_actorIdOverridePanel = new Dictionary<int, StandardUISubtitlePanel>();
    
    public void ClearCache()
    {
        m_actorPanelCache.Clear();
        m_customPanels.Clear();
        m_actorIdOverridePanel.Clear();
        m_dialogueActorCache.Clear();
    }
}
```

**Características**:
- ✅ **Múltiples niveles**: Cache por Transform, por Actor ID, y por DialogueActor
- ✅ **Limpieza**: Método para limpiar todos los caches
- ✅ **Evita GetComponent**: Cachea componentes DialogueActor para evitar búsquedas costosas

#### SequencerTools - Registered Subjects Cache

**Ubicación**: `Scripts/MVC/Sequencer/SequencerTools.cs`

**Propósito**: Cache de GameObjects registrados para búsquedas rápidas en comandos de sequencer.

**Implementación**:

```csharp
public static class SequencerTools
{
    private static Dictionary<string, Transform> registeredSubjects = new Dictionary<string, Transform>();
    
    public static void RegisterSubject(Transform subject)
    {
        if (subject == null) return;
        registeredSubjects[subject.name] = subject;
        HookIntoSceneLoaded();
    }
    
    public static void UnregisterSubject(Transform subject)
    {
        if (subject == null || !registeredSubjects.ContainsKey(subject.name)) return;
        registeredSubjects.Remove(subject.name);
    }
    
    public static void CleanNullSubjects()
    {
        registeredSubjects.RemoveAll(x => x == null);
    }
    
    public static Transform GetSubject(string specifier, Transform speaker, Transform listener, Transform defaultSubject = null)
    {
        // ... lógica de búsqueda ...
        
        // Check registered subjects:
        if (registeredSubjects.TryGetValue(specifier, out t) && t != null) return t.gameObject;
        
        // Fallback a búsquedas costosas...
    }
}
```

**Características**:
- ✅ **Registro manual**: Los objetos se registran explícitamente
- ✅ **Limpieza automática**: Limpia referencias null cuando se carga una escena
- ✅ **Fallback**: Si no está registrado, usa búsquedas costosas como respaldo

**Rendimiento**:
- **Sin registro**: `GameObject.Find()` o `Resources.FindObjectsOfTypeAll()` (muy costoso)
- **Con registro**: Búsqueda en Dictionary (O(1))

### Preloading y Warm-up

#### PreloadActorPortraits

**Ubicación**: `Scripts/UI/Utility/PreloadActorPortraits.cs`

**Propósito**: Precarga retratos de actores al inicio para evitar "hiccups" cuando inician conversaciones.

**Implementación**:

```csharp
public class PreloadActorPortraits : MonoBehaviour
{
    [Tooltip("Preload for Unity UI.")]
    public bool supportUnityUI;
    
    [Tooltip("If preloading for Unity UI, collapse legacy textures to save memory.")]
    public bool collapseLegacyTextures;
    
    private void Start()
    {
        if (DialogueManager.instance == null || DialogueManager.masterDatabase == null) return;
        var actors = DialogueManager.masterDatabase.actors;
        if (actors == null) return;
        for (int i = 0; i < actors.Count; i++)
        {
            PreloadActor(actors[i]);
        }
    }
    
    public void PreloadActor(Actor actor)
    {
        if (actor == null) return;
        actor.portrait = PreloadTexture(actor.portrait);
        if (actor.alternatePortraits == null) return;
        for (int i = 0; i < actor.alternatePortraits.Count; i++)
        {
            actor.alternatePortraits[i] = PreloadTexture(actor.alternatePortraits[i]);
        }
    }
    
    public Texture2D PreloadTexture(Texture2D texture)
    {
        if (texture == null) return null;
        if (supportUnityUI)
        {
            var sprite = Sprite.Create(texture, new Rect(0, 0, texture.width, texture.height), Vector2.zero);
            if (collapseLegacyTextures)
            {
                texture = new Texture2D(2, 2); // Libera memoria de textura original
            }
            UITools.spriteCache.Add(texture, sprite); // Agrega al cache
        }
        legacyPortraits.Add(texture);
        return texture;
    }
}
```

**Características**:
- ✅ **Precarga temprana**: Carga todos los retratos al inicio
- ✅ **Optimización de memoria**: Opción para colapsar texturas legacy
- ✅ **Integración con cache**: Agrega sprites al cache de UITools

**Rendimiento**:
- **Sin preload**: Conversión Texture2D → Sprite durante conversación (causa stutter)
- **Con preload**: Conversión al inicio, reutilización durante conversación (fluido)

#### DialogueSystemController - Warm-up

**Ubicación**: `Scripts/Manager/DialogueSystemController.cs`

**Propósito**: Calentar el sistema de conversaciones al inicio para evitar overhead en primera conversación.

**Implementación**:

```csharp
public class DialogueSystemController : MonoBehaviour
{
    [Tooltip("Preload the dialogue database and dialogue UI at Start. Otherwise they're loaded at first use.")]
    public bool preloadResources = true;
    
    public enum WarmUpMode { On, Extra, Off }
    
    [Tooltip("Warm up conversation engine and dialogue UI at Start to avoid a small amount of overhead on first use. 'Extra' performs deeper warmup that takes 1.25s at startup.")]
    public WarmUpMode warmUpConversationController = WarmUpMode.On;
    
    [Tooltip("Use a copy of the dialogue database at runtime instead of the asset file directly.")]
    public bool instantiateDatabase = true;
}
```

**Características**:
- ✅ **Preload Resources**: Precarga database y UI al inicio
- ✅ **Warm-up Modes**: 
  - `On`: Warm-up básico
  - `Extra`: Warm-up profundo (1.25s al inicio)
  - `Off`: Sin warm-up
- ✅ **Instantiate Database**: Crea copia en runtime para no modificar asset original

**Rendimiento**:
- **Sin warm-up**: Primera conversación tiene overhead de inicialización
- **Con warm-up**: Overhead movido al inicio del juego

### Optimizaciones de Búsqueda

#### Evitar GameObject.Find()

**Problema**: `GameObject.Find()` es extremadamente costoso (O(n) donde n = todos los GameObjects en la escena).

**Solución**: Usar registro manual o cache.

**Ejemplo**:

```csharp
// ❌ MALO (costoso)
GameObject go = GameObject.Find("MyObject");

// ✅ BUENO (rápido)
Transform t = SequencerTools.GetSubject("MyObject", speaker, listener);
// O mejor aún:
SequencerTools.RegisterSubject(myTransform);
Transform t = SequencerTools.GetSubject("MyObject", speaker, listener);
```

#### Evitar GetComponent Repetido

**Problema**: `GetComponent()` puede ser costoso si se llama repetidamente.

**Solución**: Cachear componentes.

**Ejemplo**:

```csharp
// ❌ MALO (llamadas repetidas)
DialogueActor actor = transform.GetComponent<DialogueActor>();
// ... más código ...
DialogueActor actor2 = transform.GetComponent<DialogueActor>(); // Llamada repetida

// ✅ BUENO (cacheado)
private Dictionary<Transform, DialogueActor> m_dialogueActorCache = new Dictionary<Transform, DialogueActor>();

DialogueActor GetDialogueActor(Transform t)
{
    if (!m_dialogueActorCache.ContainsKey(t))
    {
        m_dialogueActorCache[t] = t.GetComponent<DialogueActor>();
    }
    return m_dialogueActorCache[t];
}
```

#### Evitar Resources.FindObjectsOfTypeAll()

**Problema**: `Resources.FindObjectsOfTypeAll()` es muy costoso (busca en todos los objetos del proyecto).

**Solución**: Usar solo como último recurso, preferir registro manual.

**Ejemplo en SequencerTools**:

```csharp
public static GameObject FindSpecifier(string specifier, bool onlyActiveInScene = false)
{
    // 1. Buscar en registered subjects (rápido)
    if (registeredSubjects.TryGetValue(specifier, out t) && t != null) return t.gameObject;
    
    // 2. Buscar en escena activa (moderado)
    var match = GameObject.Find(specifier);
    if (match != null) return match;
    
    // 3. Buscar en todos los objetos (costoso, último recurso)
    foreach (GameObject go in Resources.FindObjectsOfTypeAll(typeof(GameObject)) as GameObject[])
    {
        if (string.Compare(specifier, go.name, System.StringComparison.OrdinalIgnoreCase) == 0)
        {
            return go;
        }
    }
    return null;
}
```

### Optimizaciones de UI

#### Cache de Paneles por Actor

**Problema**: Buscar qué panel usar para cada actor en cada línea es costoso.

**Solución**: Cachear la relación actor → panel.

**Implementación**:

```csharp
private Dictionary<Transform, StandardUISubtitlePanel> m_actorPanelCache = new Dictionary<Transform, StandardUISubtitlePanel>();

StandardUISubtitlePanel GetPanelForActor(Transform actor)
{
    if (m_actorPanelCache.ContainsKey(actor))
    {
        return m_actorPanelCache[actor];
    }
    
    // Búsqueda costosa solo la primera vez
    var panel = FindPanelForActor(actor);
    m_actorPanelCache[actor] = panel;
    return panel;
}
```

#### Reutilización de Componentes UI

**Problema**: Crear/destruir componentes UI repetidamente es costoso.

**Solución**: Reutilizar componentes, solo activar/desactivar.

**Ejemplo**:

```csharp
// ❌ MALO (crear/destruir)
GameObject panel = Instantiate(panelPrefab);
// ... usar panel ...
Destroy(panel);

// ✅ BUENO (reutilizar)
GameObject panel = GetOrCreatePanel();
panel.SetActive(true);
// ... usar panel ...
panel.SetActive(false); // Reutilizar después
```

### Optimizaciones de Lua

#### Cache de Resultados de Lua

**Problema**: Evaluar condiciones Lua repetidamente es costoso.

**Solución**: El sistema no cachea resultados de Lua directamente, pero permite optimizaciones:

1. **Stop at First Valid**: `stopEvaluationAtFirstValid = true` - Deja de evaluar links después del primero válido
2. **Linear Group Mode**: `useLinearGroupMode = true` - No evalúa grupos hermanos si uno es válido
3. **Reevaluate Links**: `reevaluateLinksAfterSubtitle = false` - No re-evalúa links después de mostrar subtítulo (si no es necesario)

**Implementación**:

```csharp
public class DialogueSystemController : MonoBehaviour
{
    [Tooltip("Stop evaluating links at first valid NPC link unless parent uses RandomizeNextEntry().")]
    public bool stopEvaluationAtFirstValid = true;
    
    [Tooltip("If a group node's Conditions are true, don't evaluate sibling group nodes.")]
    public bool useLinearGroupMode = false;
    
    [Tooltip("Reevaluate links after showing subtitle in case subtitle Sequence or OnConversationLine changes link conditions.")]
    public bool reevaluateLinksAfterSubtitle = false;
}
```

---

## Limitaciones y Problemas Conocidos

### Problemas de Rendimiento

#### 1. GameObject.Find() en SequencerTools

**Problema**: Aunque hay cache, `FindSpecifier()` todavía usa `GameObject.Find()` como fallback.

**Impacto**: Puede causar stutter si se llama frecuentemente con objetos no registrados.

**Solución Recomendada**: Registrar todos los objetos que se usarán en sequencer commands.

#### 2. Resources.FindObjectsOfTypeAll()

**Problema**: Usado como último recurso en `FindSpecifier()`, es extremadamente costoso.

**Impacto**: Puede causar frame drops significativos.

**Solución Recomendada**: Evitar completamente, usar registro manual.

#### 3. GetComponentInChildren/InParent

**Problema**: Aunque se cachea en algunos lugares, todavía se usa directamente en otros.

**Impacto**: Puede ser costoso en jerarquías profundas.

**Solución Recomendada**: Cachear resultados de `GetComponent` calls.

#### 4. Lua Evaluation Overhead

**Problema**: Evaluar condiciones Lua en cada link puede ser costoso.

**Impacto**: Conversaciones con muchos links pueden ser lentas.

**Solución Recomendada**: 
- Usar `stopEvaluationAtFirstValid = true`
- Usar `useLinearGroupMode = true`
- Minimizar condiciones Lua complejas

#### 5. Sprite Creation

**Problema**: Aunque hay cache, crear sprites de texturas grandes puede ser costoso.

**Impacto**: Primera vez que se muestra un retrato puede causar stutter.

**Solución Recomendada**: Usar `PreloadActorPortraits` component.

### Limitaciones de Diseño

#### 1. Single-Player Only

**Problema**: El sistema está diseñado para single-player, no multi-usuario.

**Impacto**: No se puede usar directamente para sistemas multi-usuario como el nuestro.

**Solución**: Necesitamos arquitectura diferente (base de datos centralizada).

#### 2. ScriptableObject Storage

**Problema**: Los datos se almacenan en ScriptableObjects, no en base de datos.

**Impacto**: No hay sincronización entre múltiples clientes.

**Solución**: Usar base de datos (como nuestra `dialogos_v2`).

#### 3. Lua-Based Variables

**Problema**: Variables se almacenan en Lua, no en base de datos persistente.

**Impacto**: No hay historial de cambios, no se puede compartir entre usuarios.

**Solución**: Usar base de datos para variables (como `sesiones_dialogos_v2.variables`).

#### 4. No Built-in Multi-User Support

**Problema**: No hay soporte nativo para múltiples usuarios simultáneos.

**Impacto**: Cada usuario tendría su propia instancia de Lua, sin sincronización.

**Solución**: Arquitectura cliente-servidor (Unity → Laravel API).

### Problemas de Compatibilidad

#### 1. Unity Version Compatibility

**Problema**: Algunas optimizaciones dependen de versiones específicas de Unity.

**Ejemplo**: `RuntimeInitializeOnLoadMethod` solo disponible en Unity 2019.3+

**Solución**: Usar `#if UNITY_2019_3_OR_NEWER` para compatibilidad.

#### 2. IL2CPP Limitations

**Problema**: Algunas optimizaciones (como lambdas) no funcionan bien con IL2CPP.

**Solución**: Usar métodos explícitos en lugar de lambdas.

#### 3. WebGL Limitations

**Problema**: Algunas optimizaciones (como threading) no están disponibles en WebGL.

**Solución**: Usar alternativas compatibles con WebGL.

---

## Recomendaciones para Nuestra Implementación

### Cache en Backend (Laravel)

#### 1. Cache de Diálogos

```php
// En DialogoV2 model
public static function getCached($id)
{
    return Cache::remember("dialogo_v2_{$id}", 3600, function () use ($id) {
        return self::with(['nodos', 'nodos.respuestas'])->find($id);
    });
}
```

#### 2. Cache de Nodos

```php
// En NodoDialogoV2 model
public static function getCachedByDialogo($dialogoId)
{
    return Cache::remember("nodos_dialogo_v2_{$dialogoId}", 3600, function () use ($dialogoId) {
        return self::where('dialogo_id', $dialogoId)
            ->with('respuestas')
            ->get()
            ->keyBy('id');
    });
}
```

#### 3. Cache de Respuestas Disponibles

```php
// En RespuestaDialogoV2 model
public static function getCachedAvailable($nodoId, $usuarioId = null)
{
    $key = "respuestas_disponibles_{$nodoId}_" . ($usuarioId ?? 'anon');
    return Cache::remember($key, 300, function () use ($nodoId, $usuarioId) {
        return self::obtenerDisponibles($nodoId, $usuarioId);
    });
}
```

### Optimizaciones de Base de Datos

#### 1. Índices Estratégicos

```sql
-- Ya implementados en nuestras migraciones
CREATE INDEX idx_dialogo_id ON nodos_dialogo_v2(dialogo_id);
CREATE INDEX idx_nodo_origen ON respuestas_dialogo_v2(nodo_origen_id);
CREATE INDEX idx_sesion_dialogo ON decisiones_dialogo_v2(sesion_dialogo_id);
CREATE INDEX idx_usuario_id ON decisiones_dialogo_v2(usuario_id);
```

#### 2. Eager Loading

```php
// En controllers
$dialogo = DialogoV2::with([
    'nodos' => function($query) {
        $query->orderBy('posicion_y')->orderBy('posicion_x');
    },
    'nodos.respuestas' => function($query) {
        $query->orderBy('orden');
    }
])->find($id);
```

#### 3. Paginación

```php
// Para listados grandes
$dialogos = DialogoV2::paginate(20);
```

### Optimizaciones en Unity

#### 1. Cache de Respuestas API

```csharp
public class DialogueAPICache
{
    private static Dictionary<int, DialogoData> dialogosCache = new Dictionary<int, DialogoData>();
    private static Dictionary<string, List<RespuestaData>> respuestasCache = new Dictionary<string, List<RespuestaData>>();
    
    public static DialogoData GetDialogo(int id)
    {
        if (dialogosCache.ContainsKey(id))
        {
            return dialogosCache[id];
        }
        
        // Llamar API y cachear
        var dialogo = APIClient.GetDialogo(id);
        dialogosCache[id] = dialogo;
        return dialogo;
    }
}
```

#### 2. Preload de Diálogos

```csharp
public class DialoguePreloader : MonoBehaviour
{
    public List<int> dialogoIdsToPreload;
    
    private void Start()
    {
        StartCoroutine(PreloadDialogos());
    }
    
    private IEnumerator PreloadDialogos()
    {
        foreach (var id in dialogoIdsToPreload)
        {
            yield return StartCoroutine(APIClient.GetDialogoAsync(id));
        }
    }
}
```

#### 3. Pooling de UI Elements

```csharp
public class ResponseButtonPool
{
    private Queue<GameObject> pool = new Queue<GameObject>();
    private GameObject prefab;
    
    public GameObject Get()
    {
        if (pool.Count > 0)
        {
            return pool.Dequeue();
        }
        return Instantiate(prefab);
    }
    
    public void Return(GameObject obj)
    {
        obj.SetActive(false);
        pool.Enqueue(obj);
    }
}
```

### Optimizaciones de Red

#### 1. Batch Requests

```php
// API endpoint para obtener múltiples diálogos
Route::post('/api/dialogos/batch', function(Request $request) {
    $ids = $request->input('ids');
    return DialogoV2::whereIn('id', $ids)
        ->with(['nodos', 'nodos.respuestas'])
        ->get();
});
```

#### 2. Server-Sent Events (SSE)

```php
// Para actualizaciones en tiempo real sin polling
Route::get('/api/dialogos/{id}/stream', function($id) {
    return response()->stream(function() use ($id) {
        while (true) {
            $data = ['updated_at' => now()];
            echo "data: " . json_encode($data) . "\n\n";
            ob_flush();
            flush();
            sleep(1);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
});
```

#### 3. Compression

```php
// En middleware
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    if ($response instanceof JsonResponse) {
        $response->header('Content-Encoding', 'gzip');
    }
    
    return $response;
}
```

### Métricas y Profiling

#### 1. Logging de Performance

```php
// En middleware
public function handle($request, Closure $next)
{
    $start = microtime(true);
    $response = $next($request);
    $duration = microtime(true) - $start;
    
    if ($duration > 1.0) { // Más de 1 segundo
        Log::warning("Slow request: {$request->path()} took {$duration}s");
    }
    
    return $response;
}
```

#### 2. Query Logging

```php
// En AppServiceProvider
DB::listen(function($query) {
    if ($query->time > 100) { // Más de 100ms
        Log::warning("Slow query: {$query->sql} took {$query->time}ms");
    }
});
```

#### 3. Unity Profiler Integration

```csharp
public class DialogueProfiler
{
    public static void BeginSample(string name)
    {
        UnityEngine.Profiling.Profiler.BeginSample(name);
    }
    
    public static void EndSample()
    {
        UnityEngine.Profiling.Profiler.EndSample();
    }
}

// Uso
DialogueProfiler.BeginSample("Load Dialogo");
var dialogo = APIClient.GetDialogo(id);
DialogueProfiler.EndSample();
```

---

## Resumen de Optimizaciones

### Pixel Crushers - Optimizaciones

1. ✅ **Cache de Assets**: Dictionary para búsquedas rápidas
2. ✅ **Sprite Cache**: Reutilización de sprites
3. ✅ **Panel Cache**: Cache de paneles UI por actor
4. ✅ **Registered Subjects**: Cache de GameObjects para sequencer
5. ✅ **Preload Portraits**: Precarga de retratos
6. ✅ **Warm-up**: Calentamiento del sistema al inicio
7. ✅ **Stop at First Valid**: Optimización de evaluación de links
8. ⚠️ **GameObject.Find()**: Aún usado como fallback (costoso)
9. ⚠️ **Resources.FindObjectsOfTypeAll()**: Aún usado (muy costoso)

### Nuestra Implementación - Recomendaciones

1. ✅ **Cache en Laravel**: Redis/Memcached para diálogos y nodos
2. ✅ **Índices de BD**: Ya implementados en migraciones
3. ✅ **Eager Loading**: Reducir N+1 queries
4. ✅ **Cache en Unity**: Dictionary para respuestas API
5. ✅ **Preload**: Cargar diálogos necesarios al inicio
6. ✅ **Pooling**: Reutilizar elementos UI
7. ✅ **Batch Requests**: Reducir llamadas API
8. ✅ **SSE**: Actualizaciones en tiempo real sin polling
9. ✅ **Profiling**: Logging de performance

---

## Próximos Pasos

1. **FASE 0.10**: Documentación del Análisis Completo
2. **FASE 1.0**: Inicio de Implementación

---

**Última actualización:** 2026-01-05  
**Versión analizada:** Pixel Crushers Dialogue System 2.2.64
