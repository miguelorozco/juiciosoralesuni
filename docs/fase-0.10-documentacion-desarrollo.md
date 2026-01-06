# FASE 0.10: Documentación Técnica para Desarrollo - Sistema de Diálogos

## 📋 Índice

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Diagramas y Flujos](#diagramas-y-flujos)
3. [Comparativa con Pixel Crushers](#comparativa-con-pixel-crushers)
4. [Plan de Desarrollo](#plan-de-desarrollo)
5. [Funcionalidades Clave](#funcionalidades-clave)
6. [Mapa de Dependencias](#mapa-de-dependencias)

---

## Arquitectura del Sistema

### Arquitectura General

Nuestro sistema de diálogos está diseñado como una arquitectura **cliente-servidor** con las siguientes capas:

```
┌─────────────────────────────────────────────────────────────┐
│                    UNITY CLIENT (Frontend)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Dialogue    │  │  Dialogue   │  │  Dialogue    │     │
│  │   Editor     │  │   Player     │  │     UI      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│         │                 │                 │              │
│         └─────────────────┼─────────────────┘              │
│                           │                                 │
│                  ┌────────▼────────┐                        │
│                  │  API Client     │                        │
│                  │  (REST/SSE)     │                        │
│                  └────────┬────────┘                        │
└───────────────────────────┼─────────────────────────────────┘
                            │
                            │ HTTP/HTTPS
                            │
┌───────────────────────────▼─────────────────────────────────┐
│              LARAVEL BACKEND (API Server)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Controllers  │  │   Models     │  │  Services   │     │
│  │  (REST API)  │  │  (Eloquent)  │  │  (Business) │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│         │                 │                 │              │
│         └─────────────────┼─────────────────┘              │
│                           │                                 │
│                  ┌────────▼────────┐                        │
│                  │   MySQL DB      │                        │
│                  │  (dialogos_v2) │                        │
│                  └─────────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

### Componentes Principales

#### 1. Unity Client

**Responsabilidades**:
- Editor visual de diálogos
- Reproducción de diálogos en tiempo real
- UI para mostrar diálogos y respuestas
- Gestión de estado local (cache)
- Sincronización con servidor

**Componentes Clave**:
- `DialogueEditor`: Editor visual de diálogos
- `DialoguePlayer`: Motor de reproducción
- `DialogueUI`: Sistema de UI
- `APIClient`: Cliente REST/SSE
- `DialogueCache`: Cache local

#### 2. Laravel Backend

**Responsabilidades**:
- Almacenamiento persistente de diálogos
- Gestión de sesiones multi-usuario
- Evaluación de decisiones
- Grabación de audio
- API REST para Unity

**Componentes Clave**:
- `DialogoV2`: Modelo de diálogo
- `NodoDialogoV2`: Modelo de nodo
- `RespuestaDialogoV2`: Modelo de respuesta
- `SesionDialogoV2`: Modelo de sesión
- `DecisionDialogoV2`: Modelo de decisión
- `DialogoController`: API REST
- `DialogoFlujoController`: Flujo de diálogo

#### 3. Base de Datos

**Tablas Principales**:
- `dialogos_v2`: Diálogos
- `nodos_dialogo_v2`: Nodos de diálogo
- `respuestas_dialogo_v2`: Respuestas/opciones
- `sesiones_dialogos_v2`: Sesiones activas
- `decisiones_dialogo_v2`: Decisiones tomadas

---

## Diagramas y Flujos

### Flujo de Creación de Diálogo

```
┌─────────────┐
│   Unity     │
│   Editor    │
└──────┬──────┘
       │
       │ 1. Crear diálogo
       │    (nodos, respuestas)
       │
       ▼
┌─────────────────┐
│  Validar        │
│  Estructura     │
└──────┬──────────┘
       │
       │ 2. POST /api/dialogos
       │
       ▼
┌─────────────────┐
│  Laravel API    │
│  Controller     │
└──────┬──────────┘
       │
       │ 3. Validar datos
       │
       ▼
┌─────────────────┐
│  DialogoV2     │
│  Model         │
└──────┬──────────┘
       │
       │ 4. Guardar en BD
       │
       ▼
┌─────────────────┐
│   MySQL DB      │
│  dialogos_v2    │
└─────────────────┘
```

### Flujo de Reproducción de Diálogo

```
┌─────────────┐
│   Unity     │
│   Player    │
└──────┬──────┘
       │
       │ 1. Iniciar sesión
       │    POST /api/sesiones-dialogos
       │
       ▼
┌─────────────────┐
│  Laravel API    │
│  Controller     │
└──────┬──────────┘
       │
       │ 2. Crear sesión
       │    Obtener nodo inicial
       │
       ▼
┌─────────────────┐
│  SesionDialogoV2│
│  Model          │
└──────┬──────────┘
       │
       │ 3. GET /api/dialogos/{id}/nodo-inicial
       │
       ▼
┌─────────────────┐
│   Unity Player  │
│   Muestra nodo  │
└──────┬──────────┘
       │
       │ 4. Usuario selecciona respuesta
       │    POST /api/sesiones-dialogos/{id}/decision
       │
       ▼
┌─────────────────┐
│  Laravel API    │
│  Guarda decisión│
│  Obtiene nodo   │
│  siguiente      │
└──────┬──────────┘
       │
       │ 5. Retorna nodo siguiente
       │
       ▼
┌─────────────────┐
│   Unity Player  │
│   Muestra nodo  │
│   siguiente     │
└─────────────────┘
```

### Flujo de Evaluación por Profesor

```
┌─────────────┐
│  Profesor   │
│  (Web UI)   │
└──────┬──────┘
       │
       │ 1. Ver decisiones pendientes
       │    GET /api/decisiones/pendientes
       │
       ▼
┌─────────────────┐
│  Laravel API    │
│  Controller     │
└──────┬──────────┘
       │
       │ 2. Obtener decisiones
       │
       ▼
┌─────────────────┐
│ DecisionDialogoV2│
│  Model          │
└──────┬──────────┘
       │
       │ 3. Profesor evalúa
       │    PUT /api/decisiones/{id}/evaluar
       │
       ▼
┌─────────────────┐
│  Guardar        │
│  Evaluación     │
└─────────────────┘
```

### Diagrama de Clases (Unity)

```
┌─────────────────────┐
│   DialogueEditor    │
├─────────────────────┤
│ + CreateDialogue()   │
│ + EditNode()         │
│ + AddResponse()      │
│ + ValidateGraph()    │
│ + ExportToJSON()     │
└──────────┬──────────┘
           │
           │ uses
           ▼
┌─────────────────────┐
│   DialogueGraph     │
├─────────────────────┤
│ - nodes: List<Node> │
│ - edges: List<Edge> │
│ + AddNode()         │
│ + AddEdge()         │
│ + Validate()        │
└──────────┬──────────┘
           │
           │ contains
           ▼
┌─────────────────────┐
│       Node          │
├─────────────────────┤
│ + id: int           │
│ + tipo: string      │
│ + contenido: string │
│ + posicion_x: int   │
│ + posicion_y: int    │
│ + respuestas: List   │
└─────────────────────┘
```

### Diagrama de Clases (Laravel)

```
┌─────────────────────┐
│     DialogoV2       │
├─────────────────────┤
│ + id                │
│ + nombre            │
│ + descripcion       │
│ + estado            │
│ + nodos()           │
│ + sesiones()        │
│ + exportarParaUnity()│
└──────────┬──────────┘
           │
           │ has many
           ▼
┌─────────────────────┐
│   NodoDialogoV2     │
├─────────────────────┤
│ + id                │
│ + dialogo_id        │
│ + tipo              │
│ + contenido         │
│ + posicion_x        │
│ + posicion_y        │
│ + respuestas()      │
└──────────┬──────────┘
           │
           │ has many
           ▼
┌─────────────────────┐
│ RespuestaDialogoV2  │
├─────────────────────┤
│ + id                │
│ + nodo_origen_id    │
│ + nodo_destino_id   │
│ + texto             │
│ + orden             │
│ + condiciones       │
└─────────────────────┘
```

---

## Comparativa con Pixel Crushers

### Tabla Comparativa de Funcionalidades

| Funcionalidad | Pixel Crushers | Nuestro Sistema | Estado |
|--------------|----------------|----------------|--------|
| **Editor Visual** | ✅ Node Editor | ✅ Unity Editor Window | 🟡 Por implementar |
| **Sistema de Nodos** | ✅ DialogueEntry | ✅ NodoDialogoV2 | ✅ Implementado |
| **Sistema de Respuestas** | ✅ Links | ✅ RespuestaDialogoV2 | ✅ Implementado |
| **Condiciones** | ✅ Lua Scripting | ✅ JSON Conditions | 🟡 Por implementar |
| **Variables** | ✅ Lua Variables | ✅ JSON Variables | ✅ Implementado |
| **UI System** | ✅ IDialogueUI | ✅ Custom UI | 🟡 Por implementar |
| **Actores/Personajes** | ✅ DialogueActor | ✅ Asignación por rol | ✅ Implementado |
| **Multi-Usuario** | ❌ Single-player | ✅ Multi-user | ✅ Implementado |
| **Persistencia** | ✅ ScriptableObject | ✅ MySQL Database | ✅ Implementado |
| **Evaluación** | ❌ No | ✅ Sistema completo | ✅ Implementado |
| **Audio Recording** | ❌ No | ✅ MP3 Recording | ✅ Implementado |
| **Localización** | ✅ TextTable | 🟡 Opcional | ⚪ Futuro |
| **Quests** | ✅ QuestLog | ❌ No necesario | ⚪ No aplica |
| **Sequencer** | ✅ Sequencer Commands | 🟡 Opcional | ⚪ Futuro |
| **Import/Export** | ✅ Múltiples formatos | ✅ JSON | ✅ Implementado |

**Leyenda**:
- ✅ Implementado
- 🟡 Por implementar
- ❌ No disponible
- ⚪ No aplica / Futuro

### Qué Mantener Igual

1. **Estructura de Nodos y Respuestas**
   - Mantener el concepto de nodos conectados por respuestas
   - Mantener tipos de nodos (NPC, PC, Agrupación)

2. **Sistema de Condiciones**
   - Mantener evaluación de condiciones para mostrar/ocultar respuestas
   - Mantener lógica AND/OR para múltiples condiciones

3. **Sistema de Variables**
   - Mantener variables de sesión para tracking de estado
   - Mantener evaluación de variables en condiciones

4. **Flujo de Diálogo**
   - Mantener flujo básico: nodo → respuestas → nodo siguiente
   - Mantener concepto de nodo inicial

### Qué Mejorar

1. **Multi-Usuario**
   - ✅ **Ya mejorado**: Sistema multi-usuario nativo
   - ✅ **Ya mejorado**: Sesiones compartidas
   - ✅ **Ya mejorado**: Tracking de decisiones por usuario

2. **Persistencia**
   - ✅ **Ya mejorado**: Base de datos en lugar de ScriptableObject
   - ✅ **Ya mejorado**: Historial completo de decisiones
   - ✅ **Ya mejorado**: Evaluación y retroalimentación

3. **Rendimiento**
   - 🟡 **Por mejorar**: Cache en Laravel (Redis/Memcached)
   - 🟡 **Por mejorar**: Cache en Unity
   - 🟡 **Por mejorar**: Batch requests

4. **Editor**
   - 🟡 **Por mejorar**: Editor visual más intuitivo
   - 🟡 **Por mejorar**: Auto-arrange de nodos
   - 🟡 **Por mejorar**: Validación en tiempo real

### Qué Simplificar

1. **Sistema de Quests**
   - ❌ **No necesario**: Sistema educativo, no RPG
   - ✅ **Simplificado**: Tracking mediante `decisiones_dialogo_v2`

2. **Sistema de Localización**
   - ⚪ **Opcional**: No crítico para MVP
   - 🟡 **Futuro**: Implementar si es necesario

3. **Sequencer Commands**
   - ⚪ **Opcional**: No crítico para MVP
   - 🟡 **Futuro**: Implementar si es necesario

4. **Lua Scripting**
   - ✅ **Simplificado**: JSON conditions en lugar de Lua
   - ✅ **Ventaja**: Más fácil de validar y depurar

### Qué Agregar (Integración Laravel)

1. **Sistema de Evaluación**
   - ✅ **Agregado**: Campos de evaluación en `decisiones_dialogo_v2`
   - ✅ **Agregado**: Estados de evaluación (pendiente, evaluado, revisado)
   - ✅ **Agregado**: Calificaciones y notas del profesor

2. **Sistema de Audio**
   - ✅ **Agregado**: Grabación MP3 de decisiones
   - ✅ **Agregado**: Grabación MP3 de sesiones completas
   - ✅ **Agregado**: Campos de metadata de audio

3. **Sistema de Sesiones**
   - ✅ **Agregado**: Sesiones vinculadas a juicios
   - ✅ **Agregado**: Historial completo de nodos visitados
   - ✅ **Agregado**: Variables de sesión en JSON

4. **API REST**
   - ✅ **Agregado**: Endpoints REST para Unity
   - 🟡 **Por agregar**: Server-Sent Events (SSE) para tiempo real
   - 🟡 **Por agregar**: Webhooks para eventos

---

## Plan de Desarrollo

### Fase 1: Fundamentos (MVP) - PRIORITARIO

**Objetivo**: Sistema básico funcional para crear y reproducir diálogos.

#### 1.1 Backend (Laravel) - ✅ COMPLETADO

- [x] Migraciones de base de datos
- [x] Modelos Eloquent
- [x] Controllers básicos
- [x] Rutas API
- [x] Tests básicos

#### 1.2 Unity Editor - 🟡 EN PROGRESO

- [ ] Editor Window básico
- [ ] Crear/editar diálogos
- [ ] Crear/editar nodos
- [ ] Crear/editar respuestas
- [ ] Guardar en servidor
- [ ] Validación básica

#### 1.3 Unity Player - 🟡 POR IMPLEMENTAR

- [ ] Cargar diálogo desde API
- [ ] Mostrar nodo actual
- [ ] Mostrar respuestas disponibles
- [ ] Procesar selección de respuesta
- [ ] Avanzar al siguiente nodo
- [ ] UI básica

#### 1.4 Integración - 🟡 POR IMPLEMENTAR

- [ ] APIClient para Unity
- [ ] Autenticación JWT
- [ ] Manejo de errores
- [ ] Cache básico

### Fase 2: Funcionalidades Avanzadas

**Objetivo**: Mejorar editor y player con funcionalidades avanzadas.

#### 2.1 Editor Avanzado

- [ ] Auto-arrange de nodos
- [ ] Zoom y pan
- [ ] Multi-selección
- [ ] Validación en tiempo real
- [ ] Import/Export mejorado
- [ ] Templates de diálogos

#### 2.2 Player Avanzado

- [ ] Efectos visuales (typewriter, fade)
- [ ] Sistema de retratos
- [ ] Animaciones
- [ ] Sonidos y música
- [ ] Skip/auto-advance

#### 2.3 Sistema de Condiciones

- [ ] Editor visual de condiciones
- [ ] Evaluación de variables
- [ ] Operadores lógicos (AND/OR)
- [ ] Debug de condiciones

### Fase 3: Optimización y Performance

**Objetivo**: Optimizar rendimiento y escalabilidad.

#### 3.1 Cache

- [ ] Cache en Laravel (Redis)
- [ ] Cache en Unity
- [ ] Invalidación de cache
- [ ] Preload de diálogos

#### 3.2 Optimizaciones de Red

- [ ] Batch requests
- [ ] Compression
- [ ] Server-Sent Events (SSE)
- [ ] Webhooks

#### 3.3 Optimizaciones de BD

- [ ] Índices adicionales
- [ ] Query optimization
- [ ] Paginación
- [ ] Eager loading

### Fase 4: Funcionalidades Adicionales

**Objetivo**: Agregar funcionalidades opcionales.

#### 4.1 Localización

- [ ] Tabla de textos localizados
- [ ] Cambio de idioma en runtime
- [ ] Editor multi-idioma

#### 4.2 Sequencer

- [ ] Sistema de comandos básico
- [ ] Comandos personalizados
- [ ] Integración con Timeline

#### 4.3 Analytics

- [ ] Tracking de uso
- [ ] Métricas de rendimiento
- [ ] Reportes

---

## Funcionalidades Clave a Replicar

### 1. Sistema de Nodos y Respuestas

**Prioridad**: 🔴 CRÍTICA

**Descripción**: Sistema básico de nodos conectados por respuestas.

**Implementación**:
- ✅ Backend: Tablas `nodos_dialogo_v2` y `respuestas_dialogo_v2`
- 🟡 Unity: Editor y Player

**Referencia Pixel Crushers**:
- `DialogueEntry` → `NodoDialogoV2`
- `Link` → `RespuestaDialogoV2`

### 2. Sistema de Condiciones

**Prioridad**: 🟡 ALTA

**Descripción**: Condiciones para mostrar/ocultar respuestas.

**Implementación**:
- ✅ Backend: Campo `condiciones` (JSON)
- 🟡 Unity: Evaluación de condiciones

**Referencia Pixel Crushers**:
- Lua conditions → JSON conditions

### 3. Sistema de Variables

**Prioridad**: 🟡 ALTA

**Descripción**: Variables de sesión para tracking de estado.

**Implementación**:
- ✅ Backend: Campo `variables` (JSON) en `sesiones_dialogos_v2`
- 🟡 Unity: Get/Set variables

**Referencia Pixel Crushers**:
- Lua variables → JSON variables

### 4. Sistema de UI

**Prioridad**: 🟡 ALTA

**Descripción**: UI para mostrar diálogos y respuestas.

**Implementación**:
- 🟡 Unity: Custom UI system

**Referencia Pixel Crushers**:
- `IDialogueUI` → Custom UI

### 5. Sistema de Actores

**Prioridad**: 🟢 MEDIA

**Descripción**: Asignación de diálogos a personajes/roles.

**Implementación**:
- ✅ Backend: Campo `rol_id` en `nodos_dialogo_v2`
- 🟡 Unity: Asignación visual

**Referencia Pixel Crushers**:
- `DialogueActor` → Asignación por rol

### 6. Editor Visual

**Prioridad**: 🔴 CRÍTICA

**Descripción**: Editor visual para crear diálogos.

**Implementación**:
- 🟡 Unity: Editor Window con graph view

**Referencia Pixel Crushers**:
- Node Editor → Unity Editor Window

### 7. Import/Export

**Prioridad**: 🟢 MEDIA

**Descripción**: Importar/exportar diálogos en JSON.

**Implementación**:
- ✅ Backend: Métodos `exportarParaUnity()`
- 🟡 Unity: Import/Export

**Referencia Pixel Crushers**:
- Import/Export system → JSON format

---

## Mapa de Dependencias

### Dependencias Backend (Laravel)

```
Laravel Framework
├── Eloquent ORM
│   └── MySQL Driver
├── JWT Auth
│   └── tymon/jwt-auth
├── Validation
│   └── Illuminate/Validation
└── Cache
    └── Redis/Memcached (opcional)
```

### Dependencias Unity

```
Unity 6
├── Unity Editor
│   └── GraphView API (para editor)
├── Unity UI
│   └── uGUI
├── JSON.NET
│   └── Newtonsoft.Json
└── HTTP Client
    └── UnityWebRequest
```

### Dependencias entre Componentes

```
Unity Editor
    └──→ APIClient ──→ Laravel API ──→ MySQL DB

Unity Player
    └──→ APIClient ──→ Laravel API ──→ MySQL DB
                            │
                            └──→ SesionDialogoV2
                            └──→ DecisionDialogoV2
```

---

## Estrategia de Implementación

### Enfoque Incremental

1. **MVP Primero**: Funcionalidad básica funcionando
2. **Iterar**: Agregar funcionalidades una por una
3. **Optimizar**: Mejorar rendimiento después
4. **Extender**: Agregar funcionalidades avanzadas

### Orden de Implementación Recomendado

1. ✅ **Backend Base** (COMPLETADO)
   - Migraciones
   - Modelos
   - Controllers básicos

2. 🟡 **Unity Editor Básico** (EN PROGRESO)
   - Editor Window
   - Crear/editar diálogos
   - Guardar en servidor

3. 🟡 **Unity Player Básico** (POR IMPLEMENTAR)
   - Cargar diálogo
   - Mostrar nodo/respuestas
   - Procesar selección

4. 🟡 **Sistema de Condiciones** (POR IMPLEMENTAR)
   - Evaluación de condiciones
   - Variables de sesión

5. 🟡 **UI Avanzada** (POR IMPLEMENTAR)
   - Efectos visuales
   - Retratos
   - Animaciones

6. ⚪ **Optimizaciones** (FUTURO)
   - Cache
   - Batch requests
   - SSE

---

## Checklist de Desarrollo

### Backend (Laravel)

- [x] Migraciones de base de datos
- [x] Modelos Eloquent
- [x] Controllers básicos
- [x] Rutas API
- [x] Tests básicos
- [ ] Cache (Redis/Memcached)
- [ ] Server-Sent Events (SSE)
- [ ] Webhooks
- [ ] Batch requests
- [ ] Compression

### Unity Editor

- [ ] Editor Window básico
- [ ] Graph View para nodos
- [ ] Crear/editar diálogos
- [ ] Crear/editar nodos
- [ ] Crear/editar respuestas
- [ ] Validación
- [ ] Auto-arrange
- [ ] Zoom y pan
- [ ] Multi-selección
- [ ] Import/Export

### Unity Player

- [ ] APIClient
- [ ] Cargar diálogo
- [ ] Mostrar nodo
- [ ] Mostrar respuestas
- [ ] Procesar selección
- [ ] UI básica
- [ ] Efectos visuales
- [ ] Retratos
- [ ] Animaciones
- [ ] Sonidos

### Integración

- [ ] Autenticación JWT
- [ ] Manejo de errores
- [ ] Cache local
- [ ] Offline mode (opcional)
- [ ] Sincronización

---

## Próximos Pasos

1. **Completar Unity Editor Básico**
   - Editor Window
   - Graph View
   - Guardar en servidor

2. **Completar Unity Player Básico**
   - Cargar diálogo
   - Mostrar UI
   - Procesar decisiones

3. **Implementar Sistema de Condiciones**
   - Evaluación de condiciones
   - Variables de sesión

4. **Optimizar Rendimiento**
   - Cache
   - Batch requests
   - SSE

---

**Última actualización:** 2026-01-05  
**Versión:** 1.0.0
