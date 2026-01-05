# 🎯 Alineación con Pixel Crushers Dialogue System

**Objetivo**: Documentar cómo nuestro nuevo sistema de diálogos v2 se alinea con las características clave del Dialogue System de Pixel Crushers.

---

## 📊 Mapeo de Conceptos

### 1. Estructura de Datos

#### Pixel Crushers → Nuestro Sistema v2

| Pixel Crushers | Nuestro Sistema v2 | Notas |
|---------------|-------------------|-------|
| `DialogueDatabase` (ScriptableObject) | `dialogos_v2` (tabla) | Base de datos de diálogos |
| `Conversation` | `dialogos_v2` | Una conversación = un diálogo |
| `DialogueEntry` | `nodos_dialogo_v2` | Entrada de diálogo = nodo |
| `Link` | `respuestas_dialogo_v2` | Link = respuesta/opción |
| `Actor` | `roles_disponibles` + `nodos_dialogo_v2.rol_id` | Actor = Rol |
| `Conversant` | `nodos_dialogo_v2.rol_id` (conversant) | Quien escucha |
| `Variable` | `sesiones_dialogos_v2.variables` (JSON) | Variables de estado |
| `Field` (campos dinámicos) | `nodos_dialogo_v2.metadata` (JSON) | Campos adicionales |

---

## 🔑 Características Clave a Replicar

### 1. Sistema de Nodos (DialogueEntry)

#### Características de DialogueEntry:
- ✅ `id` - ID único
- ✅ `conversationID` - ID de conversación → `nodos_dialogo_v2.dialogo_id`
- ✅ `isRoot` - Nodo inicial → `nodos_dialogo_v2.es_inicial`
- ✅ `isGroup` - Nodo de agrupación → Podemos usar `tipo = 'agrupacion'`
- ✅ `ActorID` - Quien habla → `nodos_dialogo_v2.rol_id`
- ✅ `ConversantID` - Quien escucha → **FALTA** - Necesitamos agregar `conversant_id`
- ✅ `DialogueText` - Texto del diálogo → `nodos_dialogo_v2.contenido`
- ✅ `MenuText` - Texto del menú → Podemos usar `metadata` o agregar campo
- ✅ `canvasRect` - Posición en editor → `nodos_dialogo_v2.posicion_x`, `posicion_y`
- ✅ `outgoingLinks` - Enlaces salientes → `respuestas_dialogo_v2` (nodo_padre_id)
- ✅ `conditionsString` - Condiciones Lua → `nodos_dialogo_v2.condiciones` (JSON)
- ✅ `userScript` - Scripts Lua → `nodos_dialogo_v2.consecuencias` (JSON) o `metadata`
- ✅ `Sequence` - Comandos Sequencer → `nodos_dialogo_v2.metadata` (JSON)

#### ⚠️ Mejoras Necesarias en v2:

1. **Agregar `conversant_id`** a `nodos_dialogo_v2`:
   ```sql
   conversant_id BIGINT UNSIGNED NULL,
   FOREIGN KEY (conversant_id) REFERENCES roles_disponibles(id) ON DELETE SET NULL
   ```

2. **Agregar `menu_text`** a `nodos_dialogo_v2`:
   ```sql
   menu_text TEXT NULL, -- Texto para menú de respuestas
   ```

3. **Mejorar `metadata`** para incluir:
   - `sequence` - Comandos Sequencer
   - `userScript` - Scripts personalizados
   - `fields` - Campos dinámicos adicionales

### 2. Sistema de Links (Respuestas)

#### Características de Link:
- ✅ `originConversationID` - Conversación origen → `respuestas_dialogo_v2.nodo_padre_id`
- ✅ `originDialogueID` - Nodo origen → `respuestas_dialogo_v2.nodo_padre_id`
- ✅ `destinationConversationID` - Conversación destino → Podemos inferir desde `nodo_siguiente_id`
- ✅ `destinationDialogueID` - Nodo destino → `respuestas_dialogo_v2.nodo_siguiente_id`
- ✅ `isConnector` - Es conector → Podemos usar `metadata`
- ✅ `priority` - Prioridad → `respuestas_dialogo_v2.orden`

#### Características de Respuesta (Menu Text):
- ✅ `text` - Texto de la opción → `respuestas_dialogo_v2.texto`
- ✅ `conditionsString` - Condiciones → `respuestas_dialogo_v2.condiciones` (JSON)
- ✅ `userScript` - Scripts → `respuestas_dialogo_v2.consecuencias` (JSON)

### 3. Sistema de Variables

#### Pixel Crushers usa:
- Variables globales en `DialogueDatabase`
- Variables de conversación
- Variables de actor

#### Nuestro sistema v2:
- `sesiones_dialogos_v2.variables` (JSON) - Variables de sesión
- Podemos agregar variables globales en `dialogos_v2.configuracion` (JSON)

### 4. Sistema de Condiciones

#### Pixel Crushers:
- Usa Lua para condiciones: `conditionsString`
- Ejemplo: `"Variable[\"HasKey\"] == true"`

#### Nuestro sistema v2:
- `condiciones` (JSON) - Podemos almacenar condiciones en JSON
- Formato propuesto:
  ```json
  {
    "type": "lua",
    "expression": "Variable[\"HasKey\"] == true"
  }
  ```
- O formato simplificado:
  ```json
  {
    "variable": "HasKey",
    "operator": "==",
    "value": true
  }
  ```

### 5. Sistema de Sequencer

#### Pixel Crushers:
- `Sequence` field con comandos como: `"Camera(Closeup); Wait(2); Audio(Beep)"`

#### Nuestro sistema v2:
- Almacenar en `nodos_dialogo_v2.metadata.sequence` (JSON)
- Formato:
  ```json
  {
    "sequence": "Camera(Closeup); Wait(2); Audio(Beep)",
    "commands": [
      {"type": "Camera", "params": "Closeup"},
      {"type": "Wait", "params": "2"},
      {"type": "Audio", "params": "Beep"}
    ]
  }
  ```

### 6. Sistema de Actores (Roles)

#### Pixel Crushers:
- `Actor` con campos: `id`, `Name`, `IsPlayer`, `Portrait`, etc.

#### Nuestro sistema v2:
- Usamos `roles_disponibles` existente
- Asociación en `nodos_dialogo_v2.rol_id` (Actor)
- **FALTA**: `conversant_id` (quien escucha)

---

## 🔄 Cambios Necesarios en el Diseño v2

### 1. Actualizar `nodos_dialogo_v2`

```sql
ALTER TABLE nodos_dialogo_v2 ADD COLUMN conversant_id BIGINT UNSIGNED NULL;
ALTER TABLE nodos_dialogo_v2 ADD FOREIGN KEY (conversant_id) 
  REFERENCES roles_disponibles(id) ON DELETE SET NULL;

ALTER TABLE nodos_dialogo_v2 ADD COLUMN menu_text TEXT NULL;
-- Texto para mostrar en menú de respuestas (equivalente a MenuText)
```

### 2. Mejorar `metadata` en `nodos_dialogo_v2`

Estructura propuesta:
```json
{
  "sequence": "Camera(Closeup); Wait(2)",
  "userScript": "Variable[\"Score\"] = Variable[\"Score\"] + 10",
  "fields": {
    "custom_field_1": "value1",
    "custom_field_2": "value2"
  },
  "portrait": "path/to/portrait.png",
  "audio": "path/to/audio.wav"
}
```

### 3. Mejorar `condiciones` en `nodos_dialogo_v2` y `respuestas_dialogo_v2`

Estructura propuesta:
```json
{
  "type": "lua",
  "expression": "Variable[\"HasKey\"] == true AND Variable[\"Score\"] > 10",
  "fallback": "passthrough" // o "block"
}
```

O formato simplificado:
```json
{
  "conditions": [
    {"variable": "HasKey", "operator": "==", "value": true},
    {"variable": "Score", "operator": ">", "value": 10}
  ],
  "logic": "AND", // AND, OR
  "fallback": "passthrough"
}
```

### 4. Mejorar `consecuencias` en `respuestas_dialogo_v2`

Estructura propuesta:
```json
{
  "userScript": "Variable[\"Score\"] = Variable[\"Score\"] + 10",
  "variables": {
    "Score": {"operator": "+=", "value": 10},
    "HasKey": {"operator": "=", "value": true}
  },
  "events": [
    {"type": "OnResponseSelected", "action": "PlaySound", "params": "beep"}
  ]
}
```

---

## 📋 Checklist de Alineación

### Estructura de Datos
- [x] DialogueDatabase → dialogos_v2
- [x] Conversation → dialogos_v2
- [x] DialogueEntry → nodos_dialogo_v2
- [x] Link → respuestas_dialogo_v2
- [x] Actor → roles_disponibles + rol_id
- [ ] Conversant → **FALTA** conversant_id
- [x] Variables → sesiones_dialogos_v2.variables
- [x] Fields → metadata (JSON)

### Funcionalidades Core
- [x] Nodos iniciales (isRoot) → es_inicial
- [x] Nodos finales → es_final
- [x] Posiciones en editor → posicion_x, posicion_y
- [x] Condiciones → condiciones (JSON)
- [x] Consecuencias → consecuencias (JSON)
- [ ] Menu Text → **FALTA** menu_text
- [ ] Sequence → **FALTA** en metadata
- [ ] UserScript → **FALTA** en metadata
- [ ] Conversant → **FALTA** conversant_id

### Funcionalidades Avanzadas
- [ ] Sistema de Quests → **FUTURO**
- [ ] Sistema de Localización → **FUTURO**
- [ ] Sistema de Sequencer completo → **FUTURO**
- [ ] Sistema de Bark → **FUTURO**

---

## 🎯 Prioridades de Implementación

### Fase 1: Core (Crítico)
1. ✅ Estructura básica de nodos y respuestas
2. ✅ Posiciones en editor
3. ✅ Condiciones básicas
4. ⏳ Agregar `conversant_id` a nodos
5. ⏳ Agregar `menu_text` a nodos

### Fase 2: Funcionalidades Pixel Crushers (Importante)
1. ⏳ Sistema de Sequence en metadata
2. ⏳ Sistema de UserScript en metadata
3. ⏳ Mejorar estructura de condiciones (soporte Lua)
4. ⏳ Mejorar estructura de consecuencias

### Fase 3: Funcionalidades Avanzadas (Futuro)
1. Sistema de Quests
2. Sistema de Localización
3. Sistema de Sequencer completo
4. Sistema de Bark

---

## 📝 Notas de Diseño

### Diferencias Intencionales

1. **No usamos ScriptableObjects**: Usamos base de datos SQL para integración con Laravel
2. **No usamos Lua directamente**: Usamos JSON para condiciones, pero podemos evaluar con PHP
3. **Sistema de Roles**: Usamos roles existentes en lugar de crear sistema de Actors separado
4. **Integración Laravel**: Variables y estado se manejan en backend, no solo en Unity

### Ventajas de Nuestro Enfoque

1. **Persistencia**: Datos en BD, no solo en Unity
2. **Multiplataforma**: Accesible desde web y Unity
3. **Integración**: Directamente integrado con Laravel
4. **Escalabilidad**: Base de datos relacional vs ScriptableObjects

---

**Última actualización**: Enero 2025  
**Estado**: Análisis inicial completado  
**Próximo paso**: Actualizar migraciones con campos faltantes (conversant_id, menu_text)
