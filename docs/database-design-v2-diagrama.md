# 📊 Diagrama de Relaciones - Base de Datos v2

## 🔗 Diagrama ER Simplificado

```
┌─────────────────────┐
│    dialogos_v2     │
│─────────────────────│
│ id (PK)             │
│ nombre              │
│ descripcion         │
│ creado_por (FK)     │──┐
│ plantilla_id (FK)   │  │
│ publico             │  │
│ estado              │  │
│ version             │  │
│ configuracion (JSON)│  │
│ metadata_unity (JSON)│ │
│ deleted_at          │  │
└─────────────────────┘  │
         │                │
         │ 1:N            │
         │                │
         ▼                │
┌─────────────────────┐   │
│ nodos_dialogo_v2    │   │
│─────────────────────│   │
│ id (PK)             │   │
│ dialogo_id (FK)     │───┘
│ rol_id (FK)         │──┐
│ conversant_id (FK)  │──┤
│ titulo              │  │
│ contenido           │  │
│ menu_text           │  │
│ instrucciones       │  │
│ tipo                │  │
│ posicion_x          │  │
│ posicion_y          │  │
│ es_inicial          │  │
│ es_final            │  │
│ condiciones (JSON)  │  │
│ consecuencias (JSON)│  │
│ metadata (JSON)     │  │
│ orden               │  │
│ activo              │  │
└─────────────────────┘  │
         │                │
         │ 1:N            │
         │                │
         ▼                │
┌─────────────────────┐   │
│respuestas_dialogo_v2│   │
│─────────────────────│   │
│ id (PK)             │   │
│ nodo_padre_id (FK)  │───┘
│ nodo_siguiente_id   │──┐
│ texto               │  │
│ descripcion         │  │
│ orden               │  │
│ puntuacion          │  │
│ color               │  │
│ condiciones (JSON)  │  │
│ consecuencias (JSON)│  │
│ requiere_usuario... │  │
│ es_opcion_por_def...│  │
│ requiere_rol (JSON) │  │
│ activo              │  │
└─────────────────────┘  │
                         │
                         │ N:1
                         │
                         ▼
┌─────────────────────┐
│  nodos_dialogo_v2   │
│  (nodo_siguiente)   │
└─────────────────────┘

┌─────────────────────┐
│ sesiones_juicios     │
│─────────────────────│
│ id (PK)             │
│ ...                 │
└─────────────────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────┐
│sesiones_dialogos_v2 │
│─────────────────────│
│ id (PK)             │
│ sesion_id (FK)      │──┐
│ dialogo_id (FK)     │──┤
│ nodo_actual_id (FK) │──┤
│ estado              │  │
│ fecha_inicio        │  │
│ fecha_fin           │  │
│ variables (JSON)    │  │
│ configuracion (JSON) │  │
│ historial_nodos(JSON)│  │
└─────────────────────┘  │
         │                │
         │ 1:N            │
         │                │
         ▼                │
┌─────────────────────┐   │
│decisiones_dialogo_v2│   │
│─────────────────────│   │
│ id (PK)             │   │
│ sesion_dialogo_id   │───┘
│ nodo_dialogo_id (FK)│──┐
│ respuesta_id (FK)   │──┤
│ usuario_id (FK)     │──┤
│ rol_id (FK)         │──┤
│ texto_respuesta     │  │
│ puntuacion_obtenida │  │
│ tiempo_respuesta    │  │
│ fue_opcion_por_def...│ │
│ usuario_registrado  │  │
│ metadata (JSON)      │  │
└─────────────────────┘  │
                         │
                         │ N:1 (nullable)
                         │
                         ▼
┌─────────────────────┐
│      users          │
│─────────────────────│
│ id (PK)             │
│ ...                 │
└─────────────────────┘

┌─────────────────────┐
│ roles_disponibles   │
│─────────────────────│
│ id (PK)             │
│ nombre              │
│ ...                 │
└─────────────────────┘
         ▲
         │
         │ N:1 (nullable)
         │
         │
┌─────────────────────┐
│ nodos_dialogo_v2    │
│ (rol_id, conversant)│
└─────────────────────┘
```

---

## 📋 Relaciones Detalladas

### 1. dialogos_v2

**Relaciones:**
- `creado_por` → `users.id` (N:1, RESTRICT)
- `plantilla_id` → `plantillas_sesiones.id` (N:1, nullable, SET NULL)
- `nodos_dialogo_v2` (1:N, CASCADE)
- `sesiones_dialogos_v2` (1:N, CASCADE)

### 2. nodos_dialogo_v2

**Relaciones:**
- `dialogo_id` → `dialogos_v2.id` (N:1, CASCADE)
- `rol_id` → `roles_disponibles.id` (N:1, nullable, SET NULL) - Actor
- `conversant_id` → `roles_disponibles.id` (N:1, nullable, SET NULL) - Conversant
- `respuestas_dialogo_v2` como padre (1:N, CASCADE) - `nodo_padre_id`
- `respuestas_dialogo_v2` como siguiente (1:N, nullable) - `nodo_siguiente_id`
- `sesiones_dialogos_v2.nodo_actual_id` (1:N, nullable, SET NULL)
- `decisiones_dialogo_v2` (1:N, nullable, SET NULL)

### 3. respuestas_dialogo_v2

**Relaciones:**
- `nodo_padre_id` → `nodos_dialogo_v2.id` (N:1, CASCADE)
- `nodo_siguiente_id` → `nodos_dialogo_v2.id` (N:1, nullable, SET NULL)
- `decisiones_dialogo_v2` (1:N, nullable, SET NULL)

### 4. sesiones_dialogos_v2

**Relaciones:**
- `sesion_id` → `sesiones_juicios.id` (N:1, CASCADE)
- `dialogo_id` → `dialogos_v2.id` (N:1, CASCADE)
- `nodo_actual_id` → `nodos_dialogo_v2.id` (N:1, nullable, SET NULL)
- `decisiones_dialogo_v2` (1:N, CASCADE)
- **Unique**: `(sesion_id, dialogo_id)`

### 5. decisiones_dialogo_v2

**Relaciones:**
- `sesion_dialogo_id` → `sesiones_dialogos_v2.id` (N:1, CASCADE)
- `nodo_dialogo_id` → `nodos_dialogo_v2.id` (N:1, nullable, SET NULL)
- `respuesta_id` → `respuestas_dialogo_v2.id` (N:1, nullable, SET NULL)
- `usuario_id` → `users.id` (N:1, nullable, SET NULL)
- `rol_id` → `roles_disponibles.id` (N:1, nullable, SET NULL)

---

## 🔄 Flujo de Datos

### Flujo de Creación de Diálogo

```
1. Crear dialogos_v2
   ↓
2. Crear nodos_dialogo_v2 (con dialogo_id)
   ↓
3. Crear respuestas_dialogo_v2 (con nodo_padre_id y nodo_siguiente_id)
```

### Flujo de Ejecución de Diálogo

```
1. Crear sesiones_dialogos_v2 (con sesion_id y dialogo_id)
   ↓
2. Establecer nodo_actual_id = nodo inicial
   ↓
3. Usuario selecciona respuesta
   ↓
4. Crear decisiones_dialogo_v2
   ↓
5. Actualizar nodo_actual_id al siguiente nodo
   ↓
6. Agregar nodo al historial_nodos
```

---

## 🎯 Cardinalidades

| Tabla Origen | Relación | Tabla Destino | Cardinalidad | Tipo |
|-------------|----------|---------------|--------------|------|
| dialogos_v2 | tiene | nodos_dialogo_v2 | 1:N | CASCADE |
| nodos_dialogo_v2 | pertenece_a | dialogos_v2 | N:1 | CASCADE |
| nodos_dialogo_v2 | tiene_respuestas | respuestas_dialogo_v2 | 1:N | CASCADE (padre) |
| respuestas_dialogo_v2 | conecta_a | nodos_dialogo_v2 | N:1 | SET NULL (siguiente) |
| sesiones_juicios | tiene | sesiones_dialogos_v2 | 1:N | CASCADE |
| sesiones_dialogos_v2 | tiene | decisiones_dialogo_v2 | 1:N | CASCADE |
| nodos_dialogo_v2 | tiene_actor | roles_disponibles | N:1 | SET NULL |
| nodos_dialogo_v2 | tiene_conversant | roles_disponibles | N:1 | SET NULL |
| decisiones_dialogo_v2 | tiene_usuario | users | N:1 | SET NULL (nullable) |

---

## 📊 Índices y Performance

### Índices Clave

1. **dialogos_v2**
   - `idx_creado_por`: Búsquedas por creador
   - `idx_estado`: Filtrado por estado
   - `idx_publico`: Filtrado de públicos

2. **nodos_dialogo_v2**
   - `idx_dialogo_id`: Nodos por diálogo
   - `idx_posicion`: Búsquedas por posición
   - `idx_dialogo_inicial`: Nodo inicial por diálogo
   - `idx_dialogo_final`: Nodos finales por diálogo

3. **respuestas_dialogo_v2**
   - `idx_nodo_padre_activo`: Respuestas disponibles por nodo
   - `idx_requiere_registrado`: Filtrado por tipo de usuario

4. **sesiones_dialogos_v2**
   - `unique_sesion_dialogo`: Una sesión por diálogo
   - `idx_estado`: Sesiones activas

5. **decisiones_dialogo_v2**
   - `idx_sesion_dialogo`: Decisiones por sesión
   - `idx_usuario_id`: Decisiones por usuario
   - `idx_fecha`: Ordenamiento temporal

---

**Última actualización**: Enero 2025  
**Versión**: 1.0.0
