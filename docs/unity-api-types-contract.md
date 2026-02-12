# Contrato de tipos: API Laravel ↔ Unity (diálogos)

Este documento define los tipos de datos que **Laravel** debe enviar y **Unity** debe esperar en las respuestas JSON, para evitar errores de deserialización (ej. `Input string 'X' is not a valid integer`).

**Regla:** Laravel debe normalizar todos los valores antes de `response()->json()`. Unity debe usar los tipos indicados en C#.

---

## Respuesta genérica

| Campo    | Laravel (PHP/JSON) | Unity (C#) | Notas |
|----------|--------------------|------------|--------|
| `success` | `bool` | `bool` | Siempre presente. |
| `message` | `string` | `string` | Puede ser null en JSON → string en C#. |
| `data`    | `object` o `null` | `T` (genérico) | Según endpoint. |

---

## GET `/api/unity/sesion/{id}/dialogo-estado` → `APIResponse<DialogoEstado>`

### `data` cuando `success: true` (estado activo)

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `dialogo_activo` | `bool` | `bool` | |
| `estado` | `string` | `string` | Valores: `"iniciado"`, `"en_curso"`, `"pausado"`. |
| `dialogo_nombre` | `string` \| null | `string` | |
| `dialogo_id` | `int` | `int` | |
| `nodo_actual` | objeto (ver abajo) \| null | `NodoActual` | |
| `participantes` | `array` de objetos | `List<Participante>` | |
| `progreso` | `float` en [0, 1] o null | `float?` | **Normalizar** a float; null permitido. |
| `tiempo_transcurrido` | **`float`** (segundos) | **`float`** | **No enviar solo int:** puede ser decimal. Normalizar con `(float)` en Laravel. |
| `variables` | objeto `{}` (no array) | `Dictionary<string, object>` | **Normalizar:** si es array list → `{}`. |
| `audio_habilitado` | `bool` | (no en modelo actual) | Opcional. |
| `puede_actuar` | `bool` | `bool` | True si este usuario puede actuar (es su turno o es instructor). |

### `data` cuando `success: false` (sin diálogo activo)

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `dialogo_activo` | `false` | `bool` | |
| `estado` | `"sin_dialogo"` | `string` | |
| `dialogo_configurado_nombre` | `string` \| null | `string` | |
| `dialogo_configurado_id` | `int` | `int` | 0 si no hay. |

### `nodo_actual`

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `id` | `int` | `int` | |
| `titulo` | `string` \| null | `string` | |
| `contenido` | `string` \| null | `string` | |
| `rol_hablando` | objeto \| null | `RolHablando` | |
| `tipo` | `string` \| null | `string` | |
| `es_final` | `bool` | `bool` | |

### `rol_hablando` / roles en participantes

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `id` | `int` | `int` | |
| `nombre` | `string` | `string` | |
| `color` | `string` \| null | `string` | |
| `icono` | `string` \| null | `string` | |

### `participantes[]`

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `usuario_id` | `int` | `int` | |
| `nombre` | `string` | `string` | |
| `rol` | objeto | `RolHablando` | |
| `es_turno` | `bool` | `bool` | |

---

## GET respuestas-usuario → `APIResponse<RespuestasResponse>`

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `respuestas_disponibles` | `bool` | `bool` | |
| `respuestas` | `array` | `List<RespuestaUsuario>` | |
| `nodo_actual` | objeto | `NodoActual` | id, titulo, contenido, instrucciones. |
| `rol_usuario` | objeto | `RolHablando` | id, nombre, color. |

### `respuestas[]` (RespuestaUsuario)

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `id` | `int` | `int` | |
| `texto` | `string` \| null | `string` | |
| `descripcion` | `string` \| null | `string` | |
| `color` | `string` \| null | `string` | |
| `puntuacion` | `int` | `int` | |
| `tiene_consecuencias` | `bool` | `bool` | |
| `es_final` | `bool` | `bool` | En Laravel puede ser `es_respuesta_final`. |

---

## POST enviar-decision → `APIResponse<DecisionResponse>`

### `data`

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `decision_procesada` | `bool` | `bool` | |
| `decision_id` | `int` | `int` | |
| `puntuacion_obtenida` | `int` | `int` | |
| `nuevo_estado` | objeto \| null | `NuevoEstado` | |

### `nuevo_estado`

| Campo | Laravel | Unity | Notas |
|-------|--------|-------|--------|
| `nodo_actual` | objeto \| null | `NodoActual` | |
| `progreso` | `float` \| null | `float?` | 0..1. |
| `dialogo_finalizado` | `bool` | `bool` | |

---

## Reglas de normalización en Laravel

1. **Números que Unity usa como float:** enviar siempre como número JSON (no string).  
   - `tiempo_transcurrido`: `(float) $valor` para aceptar segundos con decimales.  
   - `progreso`: usar `normalizarProgresoParaUnity()` (devuelve `float` 0..1).

2. **IDs:** siempre `(int)`.

3. **Booleans:** siempre `(bool)`; no enviar `0`/`1` si Unity espera `bool`.

4. **Objetos vs arrays:**  
   - `variables`: objeto `{}`; si en PHP es array list → convertir a `(object) []`.  
   - Usar `normalizarVariablesParaUnity()`.

5. **Strings null:** permitido; en C# se deserializan como `null` o string vacío según el cliente.

---

## Referencia en Unity

Los modelos C# están en `Assets/Scripts/UnityApiModels.cs`. Deben coincidir con este contrato. Si se añade un campo nuevo en Laravel, actualizar aquí y en Unity a la vez.
