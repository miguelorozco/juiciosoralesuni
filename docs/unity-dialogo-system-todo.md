# 📋 TODO List - Sistema de Diálogos Propio para Unity

## 🎯 Objetivo General
Crear un sistema de diálogos propio para Unity que reemplace la dependencia de Pixel Crushers, integrado con el backend Laravel del simulador de juicios orales.

---

## 🔄 FASE 0.5: Migración y Reemplazo del Sistema Actual de Diálogos

### 0.5.1 Análisis del Sistema Actual
- [ ] **Auditoría completa del sistema actual**
  - [ ] Listar todas las tablas relacionadas con diálogos
  - [ ] Listar todos los modelos relacionados
  - [ ] Listar todos los controladores relacionados
  - [ ] Listar todas las rutas API relacionadas
  - [ ] Listar todos los seeders que usan diálogos
  - [ ] Identificar dependencias con otros módulos
  - [ ] Documentar estructura actual completa

- [ ] **Análisis de datos existentes**
  - [ ] Contar registros en cada tabla de diálogos
  - [ ] Identificar datos críticos a migrar
  - [ ] Crear script de backup de datos
  - [ ] Documentar formato de datos actual

### 0.5.2 Diseño del Nuevo Esquema de Base de Datos
- [ ] **Diseñar tabla `dialogos_v2` (nueva versión)**
  - [ ] Campos base: `id`, `nombre`, `descripcion`, `version`
  - [ ] `creado_por` (FK a users)
  - [ ] `plantilla_id` (FK a plantillas_sesiones, nullable)
  - [ ] `publico` (boolean)
  - [ ] `estado` (enum: borrador, activo, archivado)
  - [ ] `configuracion` (JSON) - Configuraciones específicas
  - [ ] `metadata_unity` (JSON) - Metadatos para Unity
  - [ ] `fecha_creacion`, `fecha_actualizacion`
  - [ ] Soft deletes
  - [ ] Índices optimizados

- [ ] **Diseñar tabla `nodos_dialogo_v2`**
  - [ ] `id` (PK)
  - [ ] `dialogo_id` (FK a dialogos_v2, cascade delete)
  - [ ] `rol_id` (FK a roles_disponibles, nullable, set null)
  - [ ] `titulo` (string 200)
  - [ ] `contenido` (text)
  - [ ] `instrucciones` (text, nullable)
  - [ ] `tipo` (enum: inicio, desarrollo, decision, final)
  - [ ] `posicion_x` (integer) - Posición X en grid (200px unidades)
  - [ ] `posicion_y` (integer) - Posición Y en grid (200px unidades)
  - [ ] `es_inicial` (boolean, default false)
  - [ ] `es_final` (boolean, default false)
  - [ ] `condiciones` (JSON, nullable) - Condiciones para mostrar nodo
  - [ ] `consecuencias` (JSON, nullable) - Consecuencias al llegar al nodo
  - [ ] `metadata` (JSON, nullable) - Metadatos adicionales
  - [ ] `orden` (integer, default 0) - Para ordenamiento
  - [ ] `activo` (boolean, default true)
  - [ ] `timestamps`
  - [ ] Índices: dialogo_id, rol_id, tipo, es_inicial, es_final, posicion

- [ ] **Diseñar tabla `respuestas_dialogo_v2`**
  - [ ] `id` (PK)
  - [ ] `nodo_padre_id` (FK a nodos_dialogo_v2, cascade delete)
  - [ ] `nodo_siguiente_id` (FK a nodos_dialogo_v2, nullable, set null)
  - [ ] `texto` (string 500) - Texto de la opción
  - [ ] `descripcion` (text, nullable)
  - [ ] `orden` (integer, default 0)
  - [ ] `puntuacion` (integer, default 0)
  - [ ] `color` (string 7, default '#007bff') - Color hex
  - [ ] `condiciones` (JSON, nullable) - Condiciones para mostrar respuesta
  - [ ] `consecuencias` (JSON, nullable) - Consecuencias de seleccionar
  - [ ] `requiere_usuario_registrado` (boolean, default false)
  - [ ] `es_opcion_por_defecto` (boolean, default false) - Para usuarios no registrados
  - [ ] `requiere_rol` (JSON, nullable) - Array de IDs de roles requeridos
  - [ ] `activo` (boolean, default true)
  - [ ] `timestamps`
  - [ ] Índices: nodo_padre_id, nodo_siguiente_id, activo, requiere_usuario_registrado

- [ ] **Diseñar tabla `sesiones_dialogos_v2`**
  - [ ] `id` (PK)
  - [ ] `sesion_id` (FK a sesiones_juicios, cascade delete)
  - [ ] `dialogo_id` (FK a dialogos_v2, cascade delete)
  - [ ] `nodo_actual_id` (FK a nodos_dialogo_v2, nullable, set null)
  - [ ] `estado` (enum: iniciado, en_curso, pausado, finalizado)
  - [ ] `fecha_inicio` (timestamp, nullable)
  - [ ] `fecha_fin` (timestamp, nullable)
  - [ ] `variables` (JSON, nullable) - Variables de estado del diálogo
  - [ ] `configuracion` (JSON, nullable) - Configuración específica
  - [ ] `historial_nodos` (JSON, nullable) - Historial de nodos visitados
  - [ ] `timestamps`
  - [ ] Índices: sesion_id, dialogo_id, estado, nodo_actual_id
  - [ ] Unique: sesion_id + dialogo_id

- [ ] **Diseñar tabla `decisiones_dialogo_v2` (mejorada)**
  - [ ] `id` (PK)
  - [ ] `sesion_dialogo_id` (FK a sesiones_dialogos_v2, cascade delete)
  - [ ] `nodo_dialogo_id` (FK a nodos_dialogo_v2, set null)
  - [ ] `respuesta_id` (FK a respuestas_dialogo_v2, nullable, set null)
  - [ ] `usuario_id` (FK a users, nullable) - NULL si usuario no registrado
  - [ ] `rol_id` (FK a roles_disponibles, nullable)
  - [ ] `texto_respuesta` (text, nullable) - Texto de la respuesta seleccionada
  - [ ] `puntuacion_obtenida` (integer, default 0)
  - [ ] `tiempo_respuesta` (integer, nullable) - Tiempo en segundos
  - [ ] `fue_opcion_por_defecto` (boolean, default false)
  - [ ] `usuario_registrado` (boolean, default false)
  - [ ] `metadata` (JSON, nullable) - Metadatos adicionales
  - [ ] `timestamps`
  - [ ] Índices: sesion_dialogo_id, usuario_id, nodo_dialogo_id, respuesta_id

### 0.5.3 Crear Nuevas Migraciones
- [ ] **Crear migración de eliminación de tablas antiguas**
  - [ ] `drop_sesiones_dialogos_table` (si existe)
  - [ ] `drop_respuestas_dialogo_table`
  - [ ] `drop_nodos_dialogo_table`
  - [ ] `drop_dialogos_table`
  - [ ] Verificar que no hay foreign keys dependientes

- [ ] **Crear migración `create_dialogos_v2_table`**
  - [ ] Implementar estructura completa
  - [ ] Agregar índices
  - [ ] Agregar foreign keys
  - [ ] Agregar soft deletes

- [ ] **Crear migración `create_nodos_dialogo_v2_table`**
  - [ ] Implementar estructura completa
  - [ ] Campos de posición directos (posicion_x, posicion_y)
  - [ ] Agregar índices optimizados
  - [ ] Agregar foreign keys con cascadas apropiadas

- [ ] **Crear migración `create_respuestas_dialogo_v2_table`**
  - [ ] Implementar estructura completa
  - [ ] Campos para usuarios no registrados
  - [ ] Campo para opción por defecto
  - [ ] Agregar índices
  - [ ] Agregar foreign keys

- [ ] **Crear migración `create_sesiones_dialogos_v2_table`**
  - [ ] Implementar estructura completa
  - [ ] Campo de historial de nodos
  - [ ] Agregar índices
  - [ ] Agregar unique constraint

- [ ] **Crear migración `create_decisiones_dialogo_v2_table`**
  - [ ] Implementar estructura completa
  - [ ] Campos para tracking de usuarios no registrados
  - [ ] Agregar índices
  - [ ] Agregar foreign keys

### 0.5.4 Scripts de Migración de Datos
- [ ] **Crear script de migración de datos**
  - [ ] Script para migrar `dialogos` → `dialogos_v2`
  - [ ] Script para migrar `nodos_dialogo` → `nodos_dialogo_v2`
    - [ ] Extraer posiciones de metadata JSON a campos directos
  - [ ] Script para migrar `respuestas_dialogo` → `respuestas_dialogo_v2`
  - [ ] Script para migrar `sesiones_dialogos` → `sesiones_dialogos_v2`
  - [ ] Script para migrar `decisiones_sesion` → `decisiones_dialogo_v2`
  - [ ] Validación de integridad de datos migrados

- [ ] **Crear script de rollback**
  - [ ] Script para revertir migración si es necesario
  - [ ] Restaurar datos desde backup

### 0.5.5 Actualizar Modelos Eloquent
- [ ] **Crear nuevo modelo `DialogoV2`**
  - [ ] Actualizar fillable
  - [ ] Actualizar relaciones
  - [ ] Actualizar scopes
  - [ ] Actualizar métodos de validación
  - [ ] Métodos para exportar a formato Unity

- [ ] **Crear nuevo modelo `NodoDialogoV2`**
  - [ ] Actualizar fillable
  - [ ] Accessors para posicion (x, y directos)
  - [ ] Métodos para actualizar posición
  - [ ] Actualizar relaciones
  - [ ] Métodos de validación

- [ ] **Crear nuevo modelo `RespuestaDialogoV2`**
  - [ ] Actualizar fillable
  - [ ] Métodos para filtrar por usuario registrado
  - [ ] Método para obtener opción por defecto
  - [ ] Actualizar relaciones
  - [ ] Métodos de evaluación de condiciones

- [ ] **Crear nuevo modelo `SesionDialogoV2`**
  - [ ] Actualizar fillable
  - [ ] Métodos para gestionar historial
  - [ ] Métodos para gestionar variables
  - [ ] Actualizar relaciones

- [ ] **Crear nuevo modelo `DecisionDialogoV2`**
  - [ ] Actualizar fillable
  - [ ] Métodos para tracking de usuarios no registrados
  - [ ] Actualizar relaciones
  - [ ] Métodos de estadísticas

### 0.5.6 Remover Código Antiguo
- [ ] **Eliminar modelos antiguos**
  - [ ] Eliminar `Dialogo.php` (después de migración)
  - [ ] Eliminar `NodoDialogo.php`
  - [ ] Eliminar `RespuestaDialogo.php`
  - [ ] Actualizar referencias en otros modelos

- [ ] **Eliminar controladores antiguos**
  - [ ] Eliminar o refactorizar `DialogoController.php`
  - [ ] Eliminar o refactorizar `NodoDialogoController.php`
  - [ ] Eliminar o refactorizar `DialogoFlujoController.php`
  - [ ] Eliminar o refactorizar `DialogoImportController.php`
  - [ ] Actualizar `UnityDialogoController.php`

- [ ] **Actualizar rutas API**
  - [ ] Actualizar rutas en `routes/api.php`
  - [ ] Mantener compatibilidad temporal si es necesario
  - [ ] Documentar cambios de endpoints

- [ ] **Limpiar seeders**
  - [ ] Actualizar seeders que usan diálogos
  - [ ] Crear nuevos seeders para v2
  - [ ] Eliminar seeders antiguos

### 0.5.7 Testing de Migración
- [ ] **Tests de migración**
  - [ ] Test de creación de tablas
  - [ ] Test de migración de datos
  - [ ] Test de integridad referencial
  - [ ] Test de rollback

- [ ] **Tests de funcionalidad**
  - [ ] Test de creación de diálogo v2
  - [ ] Test de creación de nodos con posiciones
  - [ ] Test de respuestas con usuarios no registrados
  - [ ] Test de flujo completo

### 0.5.8 Documentación de Migración
- [ ] **Documentar proceso de migración**
  - [ ] Guía paso a paso
  - [ ] Checklist de migración
  - [ ] Troubleshooting común
  - [ ] Guía de rollback

- [ ] **Documentar cambios de API**
  - [ ] Lista de endpoints cambiados
  - [ ] Cambios en formato de datos
  - [ ] Guía de migración para clientes Unity

---

## 🔍 FASE 0: Análisis Profundo del Dialogue System de Pixel Crushers

### 0.1 Análisis de Arquitectura y Estructura
- [ ] **Estudiar estructura de carpetas del plugin**
  - [ ] Mapear organización de scripts principales
  - [ ] Identificar carpetas: Scripts, Wrappers, Prefabs, Resources
  - [ ] Documentar dependencias entre módulos
  - [ ] Crear diagrama de estructura de carpetas

- [ ] **Analizar clases core del sistema**
  - [ ] `DialogueSystemController` - Manager principal
  - [ ] `DialogueDatabase` - Estructura de datos
  - [ ] `DialogueSystemController` - Control de conversaciones
  - [ ] `DialogueUI` - Sistema de interfaz
  - [ ] `DialogueActor` - Sistema de actores/personajes
  - [ ] Documentar responsabilidades de cada clase
  - [ ] Identificar patrones de diseño utilizados (Singleton, Observer, etc.)

- [ ] **Analizar modelo de datos**
  - [ ] Estructura de `DialogueDatabase` (ScriptableObject)
  - [ ] Estructura de `Conversation` y `DialogueEntry`
  - [ ] Sistema de `Actor` y `Item`
  - [ ] Sistema de `Quest` (misiones)
  - [ ] Variables y condiciones (Lua)
  - [ ] Crear diagrama ER del modelo de datos

### 0.2 Análisis del Sistema de Diálogos
- [ ] **Estudiar flujo de ejecución de conversaciones**
  - [ ] Cómo se inicia una conversación
  - [ ] Cómo se navega entre diálogos
  - [ ] Sistema de respuestas y selección
  - [ ] Manejo de condiciones y consecuencias
  - [ ] Crear diagrama de flujo de ejecución

- [ ] **Analizar sistema de nodos y conexiones**
  - [ ] Cómo se representan los nodos internamente
  - [ ] Sistema de links entre diálogos
  - [ ] Tipos de diálogos (Player, NPC, etc.)
  - [ ] Sistema de menús y respuestas múltiples
  - [ ] Documentar estructura de grafo

- [ ] **Estudiar sistema de condiciones y scripting**
  - [ ] Integración con Lua
  - [ ] Variables del diálogo
  - [ ] Condiciones de entrada/salida
  - [ ] Scripts de secuencia (Sequencer)
  - [ ] Eventos y callbacks

### 0.3 Análisis del Editor
- [ ] **Estudiar editor de diálogos (si existe)**
  - [ ] Cómo se crean conversaciones
  - [ ] Interfaz de edición de nodos
  - [ ] Sistema de visualización del grafo
  - [ ] Herramientas de organización
  - [ ] Importación/exportación de datos

- [ ] **Analizar sistema de importación/exportación**
  - [ ] Formatos soportados (Chat Mapper, Articy, etc.)
  - [ ] Estructura de archivos exportados
  - [ ] Proceso de conversión de formatos
  - [ ] Validación de datos

### 0.4 Análisis del Sistema de UI
- [ ] **Estudiar componentes de UI**
  - [ ] `DialogueUI` base y variantes
  - [ ] `UnityUIDialogueUI` - Implementación Unity UI
  - [ ] `StandardDialogueUI` - Implementación Standard UI
  - [ ] Sistema de subtítulos y menús
  - [ ] Sistema de retratos/portraits
  - [ ] Efectos visuales (typewriter, fade, etc.)

- [ ] **Analizar sistema de personalización**
  - [ ] Cómo se personalizan los prefabs
  - [ ] Sistema de temas y estilos
  - [ ] Localización e internacionalización
  - [ ] Sistema de fuentes y textos

### 0.5 Análisis del Sistema de Actores y Personajes
- [ ] **Estudiar sistema de actores**
  - [ ] Clase `DialogueActor`
  - [ ] Asignación de actores a diálogos
  - [ ] Sistema de retratos/portraits
  - [ ] Override de UI por actor
  - [ ] Sistema de bark (comentarios breves)

- [ ] **Analizar integración con personajes del juego**
  - [ ] Cómo se asocian personajes con actores
  - [ ] Sistema de triggers
  - [ ] Proximidad y detección
  - [ ] Sistema de interacción

### 0.6 Análisis del Sistema de Almacenamiento
- [ ] **Estudiar persistencia de datos**
  - [ ] Uso de ScriptableObjects
  - [ ] Sistema de guardado/carga
  - [ ] Persistencia de variables
  - [ ] Sistema de checkpoints
  - [ ] Integración con Save System (si existe)

- [ ] **Analizar sistema de recursos**
  - [ ] Cómo se cargan diálogos en runtime
  - [ ] Sistema de Resources
  - [ ] Addressables (si se usa)
  - [ ] Carga dinámica de diálogos

### 0.7 Análisis de Funcionalidades Avanzadas
- [ ] **Estudiar sistema de misiones (Quests)**
  - [ ] Estructura de quests
  - [ ] Estados de quests (unassigned, active, success, failure)
  - [ ] Integración con diálogos
  - [ ] Sistema de tracking

- [ ] **Analizar sistema de localización**
  - [ ] Text Tables
  - [ ] String Assets
  - [ ] Sistema de traducción
  - [ ] Cambio de idioma en runtime

- [ ] **Estudiar sistema de eventos**
  - [ ] Eventos del Dialogue System
  - [ ] Integración con Unity Events
  - [ ] Callbacks personalizados
  - [ ] Sistema de mensajería

### 0.8 Análisis de Integraciones y Extensiones
- [ ] **Estudiar integraciones con otros sistemas**
  - [ ] Timeline
  - [ ] Cinemachine
  - [ ] Input System
  - [ ] TextMesh Pro
  - [ ] Otros plugins de Pixel Crushers

- [ ] **Analizar sistema de extensibilidad**
  - [ ] Cómo crear custom UI
  - [ ] Cómo crear custom sequencer commands
  - [ ] Sistema de plugins
  - [ ] Hooks y callbacks disponibles

### 0.9 Análisis de Rendimiento y Optimización
- [ ] **Estudiar optimizaciones implementadas**
  - [ ] Pooling de objetos
  - [ ] Lazy loading
  - [ ] Cache de datos
  - [ ] Optimización de UI
  - [ ] Profiling y benchmarks

- [ ] **Analizar limitaciones y problemas conocidos**
  - [ ] Issues de rendimiento
  - [ ] Limitaciones de diseño
  - [ ] Problemas de compatibilidad
  - [ ] Áreas de mejora identificadas

### 0.10 Documentación del Análisis
- [ ] **Crear documentación técnica del análisis**
  - [ ] Documento de arquitectura del plugin
  - [ ] Diagramas de clases principales
  - [ ] Diagramas de flujo de datos
  - [ ] Mapa de dependencias
  - [ ] Lista de funcionalidades clave a replicar

- [ ] **Crear comparativa con nuestro sistema**
  - [ ] Tabla comparativa de funcionalidades
  - [ ] Identificar qué mantener igual
  - [ ] Identificar qué mejorar
  - [ ] Identificar qué simplificar
  - [ ] Identificar qué agregar (integración Laravel)

- [ ] **Crear plan de migración**
  - [ ] Funcionalidades prioritarias a implementar primero
  - [ ] Funcionalidades que podemos omitir inicialmente
  - [ ] Estrategia de reemplazo gradual
  - [ ] Compatibilidad con datos existentes (si aplica)

### 0.11 Crear Prototipos y Pruebas
- [ ] **Crear prototipos de funcionalidades clave**
  - [ ] Prototipo de estructura de datos básica
  - [ ] Prototipo de sistema de ejecución simple
  - [ ] Prototipo de UI básica
  - [ ] Validar conceptos antes de implementación completa

- [ ] **Realizar pruebas comparativas**
  - [ ] Comparar rendimiento con Pixel Crushers
  - [ ] Comparar facilidad de uso
  - [ ] Comparar funcionalidades
  - [ ] Identificar ventajas y desventajas

### 0.12 Herramientas de Análisis
- [ ] **Crear scripts de análisis automatizado**
  - [ ] Script para mapear estructura de clases
  - [ ] Script para extraer dependencias
  - [ ] Script para analizar uso de memoria
  - [ ] Script para generar documentación automática

- [ ] **Crear base de conocimiento**
  - [ ] Wiki o documentación interna
  - [ ] Notas de análisis por componente
  - [ ] Decisiones de diseño documentadas
  - [ ] Referencias y recursos útiles

---

## 📦 FASE 1: Arquitectura Base y Estructura de Datos

### 1.1 Modelos de Datos
- [ ] **Crear ScriptableObject `DialogoData`**
  - [ ] Propiedades: `id`, `nombre`, `descripcion`, `version`, `fechaCreacion`
  - [ ] Lista de `NodoDialogo`
  - [ ] Lista de `ConexionDialogo`
  - [ ] Métodos: `GetNodoInicial()`, `GetNodosFinales()`, `ValidarEstructura()`

- [ ] **Crear clase `NodoDialogo` (Serializable)**
  - [ ] Propiedades: `id`, `titulo`, `contenido`, `tipo` (Inicio/Desarrollo/Decision/Final)
  - [ ] `rolAsignado`, `posicion` (Vector2), `esInicial`, `esFinal`
  - [ ] `instrucciones`, `condiciones`, `consecuencias`
  - [ ] Lista de `RespuestaDialogo`

- [ ] **Crear clase `RespuestaDialogo` (Serializable)**
  - [ ] Propiedades: `id`, `texto`, `nodoDestinoId`, `puntuacion`
  - [ ] `color`, `condiciones`, `requiereUsuarioRegistrado`
  - [ ] `esOpcionPorDefecto` (para usuarios no registrados)

- [ ] **Crear clase `ConexionDialogo` (Serializable)**
  - [ ] Propiedades: `nodoOrigenId`, `nodoDestinoId`, `respuestaId`
  - [ ] `puntosIntermedios` (para líneas curvas)

- [ ] **Crear enum `TipoNodo`**
  - [ ] `Inicio`, `Desarrollo`, `Decision`, `Final`

### 1.2 Sistema de Almacenamiento
- [ ] **Crear `DialogoStorageManager` (Singleton)**
  - [ ] Método `GuardarDialogo(DialogoData dialogo)` → ScriptableObject
  - [ ] Método `CargarDialogo(string dialogoId)` → DialogoData
  - [ ] Método `CargarDesdeJSON(string jsonPath)` → DialogoData
  - [ ] Método `ExportarAJSON(DialogoData dialogo)` → string JSON
  - [ ] Método `ImportarDesdeLaravel(int dialogoId)` → Coroutine/async
  - [ ] Método `SincronizarConLaravel(DialogoData dialogo)` → Coroutine/async
  - [ ] Cache local de diálogos cargados

- [ ] **Crear estructura de carpetas**
  - [ ] `Assets/DialogoSystem/Data/` → ScriptableObjects
  - [ ] `Assets/DialogoSystem/Data/JSON/` → Archivos JSON
  - [ ] `Assets/DialogoSystem/Data/Resources/` → Recursos runtime

- [ ] **Implementar serialización JSON**
  - [ ] Usar `JsonUtility` o `Newtonsoft.Json`
  - [ ] Convertir entre formato Laravel y formato Unity
  - [ ] Validar estructura JSON al importar

---

## 🎨 FASE 2: Editor de Diálogos (Editor Window)

### 2.1 Ventana Principal del Editor
- [ ] **Crear `DialogoEditorWindow` (EditorWindow)**
  - [ ] Menú: `Tools > Sistema de Diálogos > Editor`
  - [ ] Layout: Panel izquierdo (lista diálogos), Panel central (canvas), Panel derecho (propiedades)
  - [ ] Toolbar: Nuevo, Abrir, Guardar, Exportar, Importar, Sincronizar

- [ ] **Panel de Lista de Diálogos**
  - [ ] Lista scrollable de diálogos disponibles
  - [ ] Botones: Crear Nuevo, Duplicar, Eliminar
  - [ ] Búsqueda/filtro de diálogos
  - [ ] Indicador de diálogo modificado (sin guardar)

- [ ] **Canvas del Editor (Panel Central)**
  - [ ] Grid de fondo (200x200px por celda)
  - [ ] Zoom in/out (0.1x a 2.0x)
  - [ ] Pan con click medio o espacio + arrastre
  - [ ] Minimap en esquina
  - [ ] Ruler/guías opcionales

### 2.2 Sistema de Nodos en el Editor
- [ ] **Crear `NodoEditor` (Editor GUI)**
  - [ ] Renderizar nodo como rectángulo con estilo según tipo
  - [ ] Mostrar título, contenido truncado, rol asignado
  - [ ] Indicadores visuales: Inicial (verde), Final (rojo), Decisión (amarillo)
  - [ ] Drag & drop para mover nodos
  - [ ] Selección con click
  - [ ] Multi-selección con Ctrl/Cmd

- [ ] **Crear nodos desde el editor**
  - [ ] Click derecho en canvas → "Crear Nodo"
  - [ ] Menú contextual con tipos: Inicio, Desarrollo, Decisión, Final
  - [ ] Posicionamiento automático en grid más cercano
  - [ ] Validación: solo un nodo inicial, al menos un final

- [ ] **Editar propiedades de nodo**
  - [ ] Panel derecho muestra propiedades del nodo seleccionado
  - [ ] Campos: Título, Contenido (textarea), Tipo, Rol
  - [ ] Checkboxes: Es Inicial, Es Final
  - [ ] Campo de instrucciones (opcional)
  - [ ] Validación en tiempo real

- [ ] **Eliminar nodos**
  - [ ] Botón eliminar en panel de propiedades
  - [ ] Confirmación antes de eliminar
  - [ ] Eliminar conexiones asociadas automáticamente

### 2.3 Sistema de Conexiones en el Editor
- [ ] **Crear conexiones visualmente**
  - [ ] Click en nodo origen → arrastrar a nodo destino
  - [ ] Línea temporal mientras se arrastra
  - [ ] Validar que no sea auto-conexión
  - [ ] Crear `RespuestaDialogo` automáticamente

- [ ] **Renderizar conexiones**
  - [ ] Líneas rectas o con curvas Bezier
  - [ ] Color según respuesta o tipo
  - [ ] Flecha indicando dirección
  - [ ] Etiqueta con texto de respuesta (hover para ver completo)
  - [ ] Puntos de control para ajustar curva

- [ ] **Editar conexiones**
  - [ ] Click en conexión para seleccionar
  - [ ] Panel derecho muestra propiedades de respuesta
  - [ ] Campos: Texto, Puntuación, Color
  - [ ] Checkbox: "Requiere Usuario Registrado"
  - [ ] Checkbox: "Opción por Defecto" (para no registrados)
  - [ ] Eliminar conexión

- [ ] **Validación de conexiones**
  - [ ] Prevenir conexiones duplicadas
  - [ ] Validar que nodos destino existan
  - [ ] Advertencia si nodo queda huérfano

### 2.4 Funcionalidades Avanzadas del Editor
- [ ] **Sistema de Grid y Snap**
  - [ ] Snap automático a grid (200x200px)
  - [ ] Toggle para activar/desactivar snap
  - [ ] Ajustar tamaño de grid
  - [ ] Mostrar/ocultar grid

- [ ] **Herramientas de organización**
  - [ ] Alinear nodos (izquierda, centro, derecha, arriba, abajo)
  - [ ] Distribuir nodos uniformemente
  - [ ] Agrupar nodos seleccionados
  - [ ] Deshacer/Rehacer (Undo/Redo system)

- [ ] **Vista y navegación**
  - [ ] Zoom con rueda del mouse
  - [ ] Pan con click medio o espacio + arrastre
  - [ ] Centrar en nodo seleccionado (F)
  - [ ] Fit to screen (Ctrl/Cmd + 0)
  - [ ] Buscar nodo por ID o título

- [ ] **Importar/Exportar**
  - [ ] Importar desde JSON (formato Laravel)
  - [ ] Exportar a JSON (formato Laravel)
  - [ ] Validar estructura antes de importar
  - [ ] Mostrar errores de validación

- [ ] **Sincronización con Laravel**
  - [ ] Botón "Sincronizar con Laravel"
  - [ ] Listar diálogos disponibles en backend
  - [ ] Descargar diálogo desde Laravel
  - [ ] Subir diálogo a Laravel
  - [ ] Resolver conflictos (local vs remoto)

---

## 💾 FASE 3: Sistema de Almacenamiento y Persistencia

### 3.1 Almacenamiento Local
- [ ] **ScriptableObjects para diálogos**
  - [ ] Crear asset por diálogo
  - [ ] Guardar en `Assets/DialogoSystem/Data/`
  - [ ] Nomenclatura: `Dialogo_[ID]_[Nombre].asset`

- [ ] **Sistema de versionado**
  - [ ] Campo `version` en DialogoData
  - [ ] Historial de versiones
  - [ ] Comparar versiones

- [ ] **Backup automático**
  - [ ] Crear backup antes de guardar
  - [ ] Mantener últimos N backups
  - [ ] Restaurar desde backup

### 3.2 Integración con Laravel
- [ ] **Cliente HTTP para Laravel**
  - [ ] Usar `UnityWebRequest` o `UnityNetworking`
  - [ ] Endpoints: GET `/api/dialogos/{id}`, POST `/api/dialogos`, PUT `/api/dialogos/{id}`
  - [ ] Autenticación JWT
  - [ ] Manejo de errores y timeouts

- [ ] **Sincronización bidireccional**
  - [ ] Descargar diálogo desde Laravel
  - [ ] Subir diálogo a Laravel
  - [ ] Detectar cambios locales vs remotos
  - [ ] Resolver conflictos

- [ ] **Cache local**
  - [ ] Guardar diálogos descargados localmente
  - [ ] Invalidar cache cuando hay actualizaciones
  - [ ] Modo offline (usar cache si no hay conexión)

### 3.3 Formato de Datos
- [ ] **Conversión Laravel ↔ Unity**
  - [ ] Mapear estructura JSON de Laravel a Unity
  - [ ] Convertir posiciones (grid Laravel → Unity)
  - [ ] Mapear roles y tipos
  - [ ] Validar compatibilidad de versiones

---

## 🎬 FASE 4: Sistema de Reproducción de Diálogos

### 4.1 Manager Principal
- [ ] **Crear `DialogoManager` (Singleton, MonoBehaviour)**
  - [ ] Referencia a `DialogoData` actual
  - [ ] Estado: Idle, Cargando, Reproduciendo, Pausado, Finalizado
  - [ ] Nodo actual del diálogo
  - [ ] Historial de decisiones
  - [ ] Métodos: `IniciarDialogo()`, `Avanzar()`, `Pausar()`, `Finalizar()`

- [ ] **Sistema de eventos**
  - [ ] Evento: `OnDialogoIniciado`
  - [ ] Evento: `OnNodoCambiado(NodoDialogo nodo)`
  - [ ] Evento: `OnRespuestaSeleccionada(RespuestaDialogo respuesta)`
  - [ ] Evento: `OnDialogoFinalizado`
  - [ ] Evento: `OnError(string mensaje)`

### 4.2 Motor de Ejecución
- [ ] **Lógica de flujo del diálogo**
  - [ ] Encontrar nodo inicial
  - [ ] Cargar nodo actual
  - [ ] Evaluar condiciones del nodo
  - [ ] Mostrar respuestas disponibles
  - [ ] Procesar selección de respuesta
  - [ ] Avanzar al siguiente nodo
  - [ ] Detectar nodo final

- [ ] **Sistema de condiciones**
  - [ ] Evaluar condiciones antes de mostrar nodo
  - [ ] Evaluar condiciones de respuestas
  - [ ] Variables del diálogo (Lua o sistema propio)
  - [ ] Integración con sistema de juego

- [ ] **Sistema de consecuencias**
  - [ ] Ejecutar consecuencias al seleccionar respuesta
  - [ ] Modificar variables
  - [ ] Disparar eventos del juego
  - [ ] Actualizar puntuación

### 4.3 Manejo de Usuarios
- [ ] **Detección de usuario registrado**
  - [ ] Verificar si hay sesión activa en Laravel
  - [ ] Obtener información del usuario
  - [ ] Almacenar estado: `usuarioRegistrado`, `usuarioId`, `rolId`

- [ ] **Lógica para usuarios no registrados**
  - [ ] Filtrar respuestas que requieren usuario registrado
  - [ ] Si no hay respuestas disponibles, usar "opción por defecto"
  - [ ] Marcar respuestas con `esOpcionPorDefecto = true`
  - [ ] Ejecutar automáticamente opción por defecto si es necesario

- [ ] **Sistema de roles**
  - [ ] Obtener rol del usuario desde Laravel
  - [ ] Filtrar nodos/respuestas por rol
  - [ ] Mostrar solo contenido permitido para el rol

---

## 🎭 FASE 5: Sistema de UI para Diálogos

### 5.1 Componentes de UI Base
- [ ] **Crear `DialogoUI` (MonoBehaviour)**
  - [ ] Referencias a elementos UI (Text, Buttons, Panels)
  - [ ] Métodos: `MostrarNodo()`, `MostrarRespuestas()`, `OcultarDialogo()`
  - [ ] Animaciones de entrada/salida

- [ ] **Panel de Diálogo Principal**
  - [ ] Panel contenedor
  - [ ] Área de texto del contenido
  - [ ] Área de título/nombre del personaje
  - [ ] Imagen/retrato del personaje (opcional)
  - [ ] Botón "Continuar" para nodos sin decisiones

- [ ] **Panel de Respuestas**
  - [ ] Lista de botones de respuestas
  - [ ] Scroll si hay muchas respuestas
  - [ ] Estilo visual según tipo de respuesta
  - [ ] Indicador de puntuación (opcional)
  - [ ] Deshabilitar respuestas no disponibles

- [ ] **UI para usuarios no registrados**
  - [ ] Mensaje indicando que se usa opción automática
  - [ ] Mostrar opción por defecto seleccionada
  - [ ] Botón "Registrarse" (opcional)

### 5.2 Integración con Unity UI
- [ ] **Sistema de prefabs**
  - [ ] Prefab base de `DialogoUI`
  - [ ] Prefab de botón de respuesta
  - [ ] Prefab de panel de diálogo
  - [ ] Variantes de estilo (moderno, clásico, minimalista)

- [ ] **Sistema de temas**
  - [ ] Tema claro/oscuro
  - [ ] Colores personalizables por rol
  - [ ] Fuentes y tamaños configurables

- [ ] **Animaciones y efectos**
  - [ ] Fade in/out del panel
  - [ ] Typewriter effect para texto
  - [ ] Animación de botones al aparecer
  - [ ] Efectos de sonido (opcional)

---

## 👥 FASE 6: Asignación de Diálogos a Personajes

### 6.1 Sistema de Personajes
- [ ] **Crear `PersonajeDialogo` (MonoBehaviour)**
  - [ ] Referencia a `DialogoData`
  - [ ] `personajeId`, `rolId`, `nombrePersonaje`
  - [ ] Método `IniciarDialogo()`
  - [ ] Método `AsignarDialogo(DialogoData dialogo)`

- [ ] **Detección de personajes en escena**
  - [ ] Buscar todos los `PersonajeDialogo` en escena
  - [ ] Listar personajes disponibles
  - [ ] Mostrar diálogos asignados

### 6.2 Asignación de Diálogos
- [ ] **Desde el Editor de Diálogos**
  - [ ] Dropdown/selector de personaje en propiedades del nodo
  - [ ] Asignar diálogo completo a personaje
  - [ ] Validar que personaje existe en escena

- [ ] **Desde código/Inspector**
  - [ ] Campo en `PersonajeDialogo` para asignar `DialogoData`
  - [ ] Botón "Cargar desde Laravel" en Inspector
  - [ ] Validación de compatibilidad de roles

- [ ] **Asignación dinámica**
  - [ ] Asignar diálogo según condiciones
  - [ ] Cambiar diálogo en tiempo de ejecución
  - [ ] Múltiples diálogos por personaje (sistema de prioridades)

### 6.3 Sistema de Interacción
- [ ] **Trigger de diálogo**
  - [ ] `DialogoTrigger` component
  - [ ] Tipos: OnClick, OnEnter, OnProximity, Manual
  - [ ] Distancia de activación
  - [ ] Cooldown entre activaciones

- [ ] **Sistema de proximidad**
  - [ ] Detectar cuando jugador se acerca a personaje
  - [ ] Mostrar indicador visual (exclamación, etc.)
  - [ ] Activar diálogo automáticamente o con input

---

## 🔄 FASE 7: Integración con Laravel

### 7.1 Autenticación y Sesión
- [ ] **Sistema de autenticación**
  - [ ] Login desde Unity
  - [ ] Almacenar token JWT
  - [ ] Refresh token automático
  - [ ] Logout

- [ ] **Gestión de sesión**
  - [ ] Obtener información de sesión activa
  - [ ] Verificar si usuario está registrado
  - [ ] Obtener rol del usuario
  - [ ] Sincronizar estado con Laravel

### 7.2 Comunicación con API
- [ ] **Endpoints necesarios**
  - [ ] `GET /api/unity/sesion/{id}/dialogo-estado` → Estado actual
  - [ ] `GET /api/unity/sesion/{id}/respuestas-usuario/{user}` → Respuestas disponibles
  - [ ] `POST /api/unity/sesion/{id}/enviar-decision` → Enviar decisión
  - [ ] `GET /api/dialogos/{id}` → Obtener diálogo completo
  - [ ] `POST /api/dialogos` → Crear/actualizar diálogo

- [ ] **Cliente HTTP Unity**
  - [ ] Wrapper para `UnityWebRequest`
  - [ ] Manejo de headers (JWT, Content-Type)
  - [ ] Manejo de errores (401, 404, 500, etc.)
  - [ ] Retry logic para requests fallidos
  - [ ] Timeout configurable

### 7.3 Sincronización en Tiempo Real
- [ ] **Server-Sent Events (SSE)**
  - [ ] Conectar a endpoint SSE de Laravel
  - [ ] Escuchar eventos: `dialogo_actualizado`, `decision_procesada`
  - [ ] Actualizar UI cuando hay cambios remotos
  - [ ] Manejar desconexiones y reconexiones

- [ ] **Broadcast de decisiones**
  - [ ] Enviar decisión a Laravel
  - [ ] Esperar confirmación
  - [ ] Actualizar estado local
  - [ ] Sincronizar con otros clientes

---

## 🧪 FASE 8: Testing y Validación

### 8.1 Testing del Editor
- [ ] **Tests unitarios del editor**
  - [ ] Crear/editar/eliminar nodos
  - [ ] Crear/editar/eliminar conexiones
  - [ ] Validación de estructura
  - [ ] Importar/exportar JSON

- [ ] **Tests de integración**
  - [ ] Guardar y cargar diálogo
  - [ ] Sincronizar con Laravel
  - [ ] Convertir formato Laravel ↔ Unity

### 8.2 Testing del Sistema de Reproducción
- [ ] **Tests de flujo**
  - [ ] Reproducir diálogo completo
  - [ ] Probar todos los tipos de nodos
  - [ ] Validar condiciones y consecuencias
  - [ ] Probar con usuario registrado/no registrado

- [ ] **Tests de UI**
  - [ ] Mostrar/ocultar diálogo
  - [ ] Interacción con botones
  - [ ] Animaciones
  - [ ] Responsive en diferentes resoluciones

### 8.3 Testing de Integración Laravel
- [ ] **Tests de API**
  - [ ] Autenticación
  - [ ] Obtener diálogos
  - [ ] Enviar decisiones
  - [ ] Sincronización

- [ ] **Tests de escenarios**
  - [ ] Usuario registrado con rol
  - [ ] Usuario no registrado
  - [ ] Múltiples usuarios simultáneos
  - [ ] Modo offline

---

## 📚 FASE 9: Documentación

### 9.1 Documentación Técnica
- [ ] **Guía de arquitectura**
  - [ ] Diagrama de clases
  - [ ] Flujo de datos
  - [ ] Integración con Laravel

- [ ] **API Reference**
  - [ ] Documentar todas las clases públicas
  - [ ] Ejemplos de código
  - [ ] Mejores prácticas

### 9.2 Documentación de Usuario
- [ ] **Guía del editor**
  - [ ] Cómo crear un diálogo
  - [ ] Cómo crear nodos y conexiones
  - [ ] Cómo importar/exportar
  - [ ] Sincronización con Laravel

- [ ] **Guía de integración**
  - [ ] Cómo asignar diálogos a personajes
  - [ ] Cómo personalizar UI
  - [ ] Cómo manejar usuarios no registrados

### 9.3 Ejemplos y Tutoriales
- [ ] **Ejemplos de código**
  - [ ] Diálogo simple
  - [ ] Diálogo con decisiones
  - [ ] Diálogo con condiciones
  - [ ] Integración completa

- [ ] **Tutoriales paso a paso**
  - [ ] Crear tu primer diálogo
  - [ ] Integrar con personaje
  - [ ] Conectar con Laravel

---

## 🚀 FASE 10: Optimización y Mejoras

### 10.1 Optimización de Rendimiento
- [ ] **Optimización del editor**
  - [ ] Culling de nodos fuera de vista
  - [ ] Pooling de elementos UI
  - [ ] Lazy loading de diálogos grandes

- [ ] **Optimización de runtime**
  - [ ] Cache de diálogos cargados
  - [ ] Preload de diálogos próximos
  - [ ] Optimizar búsqueda de nodos

### 10.2 Mejoras de UX
- [ ] **Mejoras del editor**
  - [ ] Atajos de teclado
  - [ ] Tooltips informativos
  - [ ] Validación en tiempo real
  - [ ] Autoguardado

- [ ] **Mejoras de UI**
  - [ ] Transiciones suaves
  - [ ] Feedback visual mejorado
  - [ ] Accesibilidad (lectores de pantalla)

### 10.3 Funcionalidades Adicionales
- [ ] **Editor avanzado**
  - [ ] Templates de diálogos
  - [ ] Buscar y reemplazar
  - [ ] Estadísticas del diálogo
  - [ ] Validación avanzada

- [ ] **Sistema de diálogos**
  - [ ] Variables globales
  - [ ] Sistema de misiones integrado
  - [ ] Sistema de logros
  - [ ] Analytics de decisiones

---

## 📝 Notas Importantes

### Prioridades
1. **Crítica**: Fase 0.5 (Migración y Reemplazo del Sistema Actual) - **DEBE completarse PRIMERO**
2. **Crítica**: Fase 0 (Análisis Profundo de Pixel Crushers) - **DEBE completarse antes de comenzar desarrollo**
3. **Alta**: Fases 1, 2, 3, 4 (Base, Editor, Almacenamiento, Reproducción)
4. **Media**: Fases 5, 6, 7 (UI, Personajes, Laravel)
5. **Baja**: Fases 8, 9, 10 (Testing, Documentación, Optimización)

### Dependencias
- Unity 6.0.3.2f1 o superior
- Universal Render Pipeline (URP)
- Unity Input System (opcional pero recomendado)
- Backend Laravel funcionando con endpoints Unity

### Consideraciones
- Mantener compatibilidad con formato JSON de Laravel
- Sistema debe funcionar offline (con cache)
- UI debe ser responsive y accesible
- Editor debe ser intuitivo para usuarios no técnicos

---

## ✅ Checklist de Entrega

### Pre-requisitos
- [ ] **FASE 0.5 completada**: Migración del sistema actual completada
- [ ] Nuevas tablas v2 creadas y funcionando
- [ ] Datos migrados exitosamente
- [ ] Código antiguo removido
- [ ] **FASE 0 completada**: Análisis profundo de Pixel Crushers
- [ ] Documentación técnica del análisis creada
- [ ] Plan de migración definido
- [ ] Prototipos validados

### Funcionalidades Core
- [ ] Editor de diálogos funcional
- [ ] Sistema de almacenamiento (local + Laravel)
- [ ] Sistema de reproducción completo
- [ ] Asignación a personajes
- [ ] Manejo de usuarios no registrados
- [ ] Integración con Laravel

### Calidad y Documentación
- [ ] Documentación completa
- [ ] Tests básicos pasando
- [ ] Ejemplos funcionales
- [ ] Guías de usuario creadas

---

**Última actualización**: Enero 2025  
**Versión del sistema**: 1.0.0  
**Estado**: En desarrollo
