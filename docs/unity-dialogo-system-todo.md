# 📋 TODO List - Sistema de Diálogos Propio para Unity

## 🎯 Objetivo General
Crear un sistema de diálogos propio para Unity que reemplace la dependencia de Pixel Crushers, integrado con el backend Laravel del simulador de juicios orales.

---

## 🔄 FASE 0.5: Migración y Reemplazo del Sistema Actual de Diálogos

### 0.5.1 Análisis del Sistema Actual
- [x] **Auditoría completa del sistema actual**
  - [x] Listar todas las tablas relacionadas con diálogos
  - [x] Listar todos los modelos relacionados
  - [x] Listar todos los controladores relacionados
  - [x] Listar todas las rutas API relacionadas
  - [x] Listar todos los seeders que usan diálogos
  - [x] Identificar dependencias con otros módulos
  - [x] Documentar estructura actual completa
  - [x] Documento completo en `docs/auditoria-sistema-dialogos-actual.md`

- [x] **Análisis de datos existentes**
  - [x] Contar registros en cada tabla de diálogos
  - [x] Identificar datos críticos a migrar
  - [x] Crear script de backup de datos
  - [x] Documentar formato de datos actual
  - [x] Script `database/scripts/analizar-datos-dialogos.php` creado
  - [x] Script `database/scripts/backup-datos-dialogos.php` creado

### 0.5.2 Diseño del Nuevo Esquema de Base de Datos
- [x] **Diseñar tabla `dialogos_v2` (nueva versión)**
  - [x] Campos base: `id`, `nombre`, `descripcion`, `version`
  - [x] `creado_por` (FK a users)
  - [x] `plantilla_id` (FK a plantillas_sesiones, nullable)
  - [x] `publico` (boolean)
  - [x] `estado` (enum: borrador, activo, archivado)
  - [x] `configuracion` (JSON) - Configuraciones específicas
  - [x] `metadata_unity` (JSON) - Metadatos para Unity
  - [x] `fecha_creacion`, `fecha_actualizacion`
  - [x] Soft deletes
  - [x] Índices optimizados
  - [x] Documentación completa en `database-design-v2.md`

- [x] **Diseñar tabla `nodos_dialogo_v2`**
  - [x] `id` (PK)
  - [x] `dialogo_id` (FK a dialogos_v2, cascade delete)
  - [x] `rol_id` (FK a roles_disponibles, nullable, set null)
  - [x] `conversant_id` (FK a roles_disponibles, nullable, set null) - Pixel Crushers
  - [x] `titulo` (string 200)
  - [x] `contenido` (text)
  - [x] `menu_text` (text, nullable) - Pixel Crushers MenuText
  - [x] `instrucciones` (text, nullable)
  - [x] `tipo` (enum: inicio, desarrollo, decision, final, agrupacion) - Pixel Crushers
  - [x] `posicion_x` (integer) - Posición X en grid (200px unidades)
  - [x] `posicion_y` (integer) - Posición Y en grid (200px unidades)
  - [x] `es_inicial` (boolean, default false)
  - [x] `es_final` (boolean, default false)
  - [x] `condiciones` (JSON, nullable) - Condiciones para mostrar nodo
  - [x] `consecuencias` (JSON, nullable) - Consecuencias al llegar al nodo
  - [x] `metadata` (JSON, nullable) - Metadatos adicionales (Sequence, userScript)
  - [x] `orden` (integer, default 0) - Para ordenamiento
  - [x] `activo` (boolean, default true)
  - [x] `timestamps`
  - [x] Índices: dialogo_id, rol_id, conversant_id, tipo, es_inicial, es_final, posicion

- [x] **Diseñar tabla `respuestas_dialogo_v2`**
  - [x] `id` (PK)
  - [x] `nodo_padre_id` (FK a nodos_dialogo_v2, cascade delete)
  - [x] `nodo_siguiente_id` (FK a nodos_dialogo_v2, nullable, set null)
  - [x] `texto` (string 500) - Texto de la opción
  - [x] `descripcion` (text, nullable)
  - [x] `orden` (integer, default 0)
  - [x] `puntuacion` (integer, default 0)
  - [x] `color` (string 7, default '#007bff') - Color hex
  - [x] `condiciones` (JSON, nullable) - Condiciones para mostrar respuesta
  - [x] `consecuencias` (JSON, nullable) - Consecuencias de seleccionar
  - [x] `requiere_usuario_registrado` (boolean, default false)
  - [x] `es_opcion_por_defecto` (boolean, default false) - Para usuarios no registrados
  - [x] `requiere_rol` (JSON, nullable) - Array de IDs de roles requeridos
  - [x] `activo` (boolean, default true)
  - [x] `timestamps`
  - [x] Índices: nodo_padre_id, nodo_siguiente_id, activo, requiere_usuario_registrado

- [x] **Diseñar tabla `sesiones_dialogos_v2`**
  - [x] `id` (PK)
  - [x] `sesion_id` (FK a sesiones_juicios, cascade delete)
  - [x] `dialogo_id` (FK a dialogos_v2, cascade delete)
  - [x] `nodo_actual_id` (FK a nodos_dialogo_v2, nullable, set null)
  - [x] `estado` (enum: iniciado, en_curso, pausado, finalizado)
  - [x] `fecha_inicio` (timestamp, nullable)
  - [x] `fecha_fin` (timestamp, nullable)
  - [x] `variables` (JSON, nullable) - Variables de estado del diálogo
  - [x] `configuracion` (JSON, nullable) - Configuración específica
  - [x] `historial_nodos` (JSON, nullable) - Historial de nodos visitados
  - [x] `timestamps`
  - [x] Índices: sesion_id, dialogo_id, estado, nodo_actual_id
  - [x] Unique: sesion_id + dialogo_id

- [x] **Diseñar tabla `decisiones_dialogo_v2` (mejorada)**
  - [x] `id` (PK)
  - [x] `sesion_dialogo_id` (FK a sesiones_dialogos_v2, cascade delete)
  - [x] `nodo_dialogo_id` (FK a nodos_dialogo_v2, set null)
  - [x] `respuesta_id` (FK a respuestas_dialogo_v2, nullable, set null)
  - [x] `usuario_id` (FK a users, nullable) - NULL si usuario no registrado
  - [x] `rol_id` (FK a roles_disponibles, nullable)
  - [x] `texto_respuesta` (text, nullable) - Texto de la respuesta seleccionada
  - [x] `puntuacion_obtenida` (integer, default 0)
  - [x] `tiempo_respuesta` (integer, nullable) - Tiempo en segundos
  - [x] `fue_opcion_por_defecto` (boolean, default false)
  - [x] `usuario_registrado` (boolean, default false)
  - [x] `metadata` (JSON, nullable) - Metadatos adicionales
  - [x] `timestamps`
  - [x] Índices: sesion_dialogo_id, usuario_id, nodo_dialogo_id, respuesta_id

- [x] **Documentación adicional**
  - [x] Diagrama de relaciones (`database-design-v2-diagrama.md`)
  - [x] Formatos JSON detallados (`database-design-v2-formatos-json.md`)
  - [x] Alineación con Pixel Crushers (`pixel-crushers-alignment.md`)

### 0.5.3 Crear Nuevas Migraciones
- [x] **Crear migración de eliminación de tablas antiguas**
  - [x] `drop_sesiones_dialogos_table` (si existe)
  - [x] `drop_respuestas_dialogo_table`
  - [x] `drop_nodos_dialogo_table`
  - [x] `drop_dialogos_table`
  - [x] Verificar que no hay foreign keys dependientes

- [x] **Crear migración `create_dialogos_v2_table`**
  - [x] Implementar estructura completa
  - [x] Agregar índices
  - [x] Agregar foreign keys
  - [x] Agregar soft deletes

- [x] **Crear migración `create_nodos_dialogo_v2_table`**
  - [x] Implementar estructura completa
  - [x] Campos de posición directos (posicion_x, posicion_y)
  - [x] Agregar índices optimizados
  - [x] Agregar foreign keys con cascadas apropiadas

- [x] **Crear migración `create_respuestas_dialogo_v2_table`**
  - [x] Implementar estructura completa
  - [x] Campos para usuarios no registrados
  - [x] Campo para opción por defecto
  - [x] Agregar índices
  - [x] Agregar foreign keys

- [x] **Crear migración `create_sesiones_dialogos_v2_table`**
  - [x] Implementar estructura completa
  - [x] Campo de historial de nodos
  - [x] Agregar índices
  - [x] Agregar unique constraint

- [x] **Crear migración `create_decisiones_dialogo_v2_table`**
  - [x] Implementar estructura completa
  - [x] Campos para tracking de usuarios no registrados
  - [x] Agregar índices
  - [x] Agregar foreign keys

### 0.5.4 Scripts de Migración de Datos
- [x] **Crear script de migración de datos**
  - [x] Script para migrar `dialogos` → `dialogos_v2`
  - [x] Script para migrar `nodos_dialogo` → `nodos_dialogo_v2`
    - [x] Extraer posiciones de metadata JSON a campos directos
  - [x] Script para migrar `respuestas_dialogo` → `respuestas_dialogo_v2`
  - [x] Script para migrar `sesiones_dialogos` → `sesiones_dialogos_v2`
  - [x] Script para migrar `decisiones_sesion` → `decisiones_dialogo_v2`
  - [x] Validación de integridad de datos migrados
  - [x] Comando Artisan `dialogos:migrate-to-v2`
  - [x] Comando Artisan `dialogos:validate-migration`

- [x] **Crear script de validación**
  - [x] Script para validar migración
  - [x] Comparación de conteos entre v1 y v2
  - [x] Validación de integridad referencial
  - [x] Validación de nodos iniciales y finales

### 0.5.5 Actualizar Modelos Eloquent
- [x] **Crear nuevo modelo `DialogoV2`**
  - [x] Actualizar fillable
  - [x] Actualizar relaciones
  - [x] Actualizar scopes
  - [x] Actualizar métodos de validación
  - [x] Métodos para exportar a formato Unity

- [x] **Crear nuevo modelo `NodoDialogoV2`**
  - [x] Actualizar fillable
  - [x] Accessors para posicion (x, y directos)
  - [x] Métodos para actualizar posición
  - [x] Actualizar relaciones
  - [x] Métodos de validación

- [x] **Crear nuevo modelo `RespuestaDialogoV2`**
  - [x] Actualizar fillable
  - [x] Métodos para filtrar por usuario registrado
  - [x] Método para obtener opción por defecto
  - [x] Actualizar relaciones
  - [x] Métodos de evaluación de condiciones

- [x] **Crear nuevo modelo `SesionDialogoV2`**
  - [x] Actualizar fillable
  - [x] Métodos para gestionar historial
  - [x] Métodos para gestionar variables
  - [x] Actualizar relaciones
  - [x] Métodos para audio completo

- [x] **Crear nuevo modelo `DecisionDialogoV2`**
  - [x] Actualizar fillable
  - [x] Métodos para tracking de usuarios no registrados
  - [x] Actualizar relaciones
  - [x] Métodos de estadísticas
  - [x] Métodos para evaluación del profesor
  - [x] Métodos para audio MP3

### 0.5.6 Remover Código Antiguo
- [x] **Eliminar modelos antiguos**
  - [x] Eliminar `Dialogo.php` (después de migración)
  - [x] Eliminar `NodoDialogo.php`
  - [x] Eliminar `RespuestaDialogo.php`
  - [x] Eliminar `SesionDialogo.php`
  - [x] Eliminar `DecisionSesion.php`
  - [x] Actualizar referencias en otros modelos (SesionJuicio, RolDialogo)
  - [x] Actualizar servicios (ProcesamientoAutomaticoService)

- [x] **Eliminar controladores antiguos**
  - [x] Refactorizar `DialogoController.php` para usar DialogoV2 (marcado como deprecated)
  - [x] Refactorizar `UnityDialogoController.php` para usar SesionDialogoV2 (marcado como deprecated)
  - [x] Actualizar `SesionController.php` para usar DialogoV2 y SesionDialogoV2
  - [x] Refactorizar `NodoDialogoController.php` para usar modelos v2 (marcado como deprecated)
  - [x] Refactorizar `DialogoFlujoController.php` para usar modelos v2 (marcado como deprecated)
  - [x] Refactorizar `DialogoImportController.php` para usar modelos v2 (marcado como deprecated)

- [x] **Actualizar rutas API**
  - [x] Actualizar rutas en `routes/api.php` con comentarios sobre v2
  - [x] Mantener compatibilidad temporal
  - [x] Documentar cambios de endpoints

- [x] **Limpiar seeders**
  - [x] Marcar seeders antiguos como deprecated
  - [x] Actualizar `DialogoJuicioPenalSeeder.php` para usar modelos v2
  - [x] Actualizar `RolesDialogoSeeder.php` para usar DialogoV2
  - [x] Actualizar `DialogoEjemploSeeder.php` como deprecated

### 0.5.7 Testing de Migración
- [x] **Tests de migración**
  - [x] Test de creación de tablas
  - [x] Test de estructura de columnas
  - [x] Test de integridad referencial (Foreign Keys)
  - [x] Test de índices
  - [x] Test de rollback
  - [x] Test de campos JSON

- [x] **Tests de funcionalidad**
  - [x] Test de creación de diálogo v2
  - [x] Test de creación de nodos con posiciones
  - [x] Test de actualización de posición
  - [x] Test de respuestas con usuarios no registrados
  - [x] Test de flujo completo de diálogo
  - [x] Test de evaluación del profesor
  - [x] Test de audio MP3 en decisiones
  - [x] Test de historial de nodos en sesión

### 0.5.8 Documentación de Migración
- [x] **Documentar proceso de migración**
  - [x] Guía paso a paso
  - [x] Checklist de migración
  - [x] Troubleshooting común
  - [x] Guía de rollback

- [x] **Documentar cambios de API**
  - [x] Lista de endpoints cambiados
  - [x] Cambios en formato de datos
  - [x] Guía de migración para clientes Unity

---

## 🔍 FASE 0: Análisis Profundo del Dialogue System de Pixel Crushers

### 0.1 Análisis de Arquitectura y Estructura
- [x] **Estudiar estructura de carpetas del plugin**
  - [x] Mapear organización de scripts principales
  - [x] Identificar carpetas: Scripts, Wrappers, Prefabs, Resources
  - [x] Documentar dependencias entre módulos
  - [x] Crear diagrama de estructura de carpetas

- [x] **Analizar clases core del sistema**
  - [x] `DialogueSystemController` - Manager principal
  - [x] `DialogueDatabase` - Estructura de datos
  - [x] `DialogueSystemController` - Control de conversaciones
  - [x] `DialogueUI` - Sistema de interfaz
  - [x] `DialogueActor` - Sistema de actores/personajes
  - [x] Documentar responsabilidades de cada clase
  - [x] Identificar patrones de diseño utilizados (Singleton, Observer, etc.)

- [x] **Analizar modelo de datos**
  - [x] Estructura de `DialogueDatabase` (ScriptableObject)
  - [x] Estructura de `Conversation` y `DialogueEntry`
  - [x] Sistema de `Actor` y `Item`
  - [x] Sistema de `Quest` (misiones)
  - [x] Variables y condiciones (Lua)
  - [x] Crear diagrama ER del modelo de datos

### 0.2 Análisis del Sistema de Diálogos
- [x] **Estudiar flujo de ejecución de conversaciones**
  - [x] Cómo se inicia una conversación
  - [x] Cómo se navega entre diálogos
  - [x] Sistema de respuestas y selección
  - [x] Manejo de condiciones y consecuencias
  - [x] Crear diagrama de flujo de ejecución

- [x] **Analizar sistema de nodos y conexiones**
  - [x] Cómo se representan los nodos internamente
  - [x] Sistema de links entre diálogos
  - [x] Tipos de diálogos (Player, NPC, etc.)
  - [x] Sistema de menús y respuestas múltiples
  - [x] Documentar estructura de grafo

- [x] **Estudiar sistema de condiciones y scripting**
  - [x] Integración con Lua
  - [x] Variables del diálogo
  - [x] Condiciones de entrada/salida
  - [x] Scripts de secuencia (Sequencer)
  - [x] Eventos y callbacks

### 0.3 Análisis del Editor
- [x] **Estudiar editor de diálogos (si existe)**
  - [x] Cómo se crean conversaciones
  - [x] Interfaz de edición de nodos
  - [x] Sistema de visualización del grafo
  - [x] Herramientas de organización
  - [x] Importación/exportación de datos

- [x] **Analizar sistema de importación/exportación**
  - [x] Formatos soportados (Chat Mapper, Articy, etc.)
  - [x] Estructura de archivos exportados
  - [x] Proceso de conversión de formatos
  - [x] Validación de datos

### 0.4 Análisis del Sistema de UI
- [x] **Estudiar componentes de UI**
  - [x] `DialogueUI` base y variantes
  - [x] `UnityUIDialogueUI` - Implementación Unity UI
  - [x] `StandardDialogueUI` - Implementación Standard UI
  - [x] Sistema de subtítulos y menús
  - [x] Sistema de retratos/portraits
  - [x] Efectos visuales (typewriter, fade, etc.)

- [x] **Analizar sistema de personalización**
  - [x] Cómo se personalizan los prefabs
  - [x] Sistema de temas y estilos
  - [x] Localización e internacionalización
  - [x] Sistema de fuentes y textos

### 0.5 Análisis del Sistema de Actores y Personajes
- [x] **Estudiar sistema de actores**
  - [x] Clase `DialogueActor`
  - [x] Asignación de actores a diálogos
  - [x] Sistema de retratos/portraits
  - [x] Override de UI por actor
  - [x] Sistema de bark (comentarios breves)

- [x] **Analizar integración con personajes del juego**
  - [x] Cómo se asocian personajes con actores
  - [x] Sistema de triggers
  - [x] Proximidad y detección
  - [x] Sistema de interacción

### 0.6 Análisis del Sistema de Almacenamiento
- [x] **Estudiar persistencia de datos**
  - [x] Uso de ScriptableObjects
  - [x] Sistema de guardado/carga
  - [x] Persistencia de variables
  - [x] Sistema de checkpoints
  - [x] Integración con Save System (si existe)

- [x] **Analizar sistema de recursos**
  - [x] Cómo se cargan diálogos en runtime
  - [x] Sistema de Resources
  - [x] Addressables (si se usa)
  - [x] Carga dinámica de diálogos

- [x] **Comparar con nuestra base de datos v2**
  - [x] Mapeo de estructuras
  - [x] Diferencias arquitectónicas
  - [x] Ventajas y desventajas

### 0.7 Análisis de Funcionalidades Avanzadas
- [x] **Estudiar sistema de misiones (Quests)**
  - [x] Estructura de quests
  - [x] Estados de quests (unassigned, active, success, failure)
  - [x] Integración con diálogos
  - [x] Sistema de tracking

- [x] **Analizar sistema de localización**
  - [x] Text Tables
  - [x] String Assets
  - [x] Sistema de traducción
  - [x] Cambio de idioma en runtime

- [x] **Estudiar sistema de eventos**
  - [x] Eventos del Dialogue System
  - [x] Integración con Unity Events
  - [x] Callbacks personalizados
  - [x] Sistema de mensajería

### 0.8 Análisis de Integraciones y Extensiones
- [x] **Estudiar integraciones con otros sistemas**
  - [x] Timeline
  - [x] Cinemachine
  - [x] Input System
  - [x] TextMesh Pro
  - [x] Otros plugins de Pixel Crushers

- [x] **Analizar sistema de extensibilidad**
  - [x] Cómo crear custom UI
  - [x] Cómo crear custom sequencer commands
  - [x] Sistema de plugins
  - [x] Hooks y callbacks disponibles

### 0.9 Análisis de Rendimiento y Optimización
- [x] **Estudiar optimizaciones implementadas**
  - [x] Pooling de objetos
  - [x] Lazy loading
  - [x] Cache de datos
  - [x] Optimización de UI
  - [x] Profiling y benchmarks

- [x] **Analizar limitaciones y problemas conocidos**
  - [x] Issues de rendimiento
  - [x] Limitaciones de diseño
  - [x] Problemas de compatibilidad
  - [x] Áreas de mejora identificadas

### 0.10 Documentación del Análisis
- [x] **Crear documentación técnica del análisis**
  - [x] Documento de arquitectura del plugin
  - [x] Diagramas de clases principales
  - [x] Diagramas de flujo de datos
  - [x] Mapa de dependencias
  - [x] Lista de funcionalidades clave a replicar

- [x] **Crear comparativa con nuestro sistema**
  - [x] Tabla comparativa de funcionalidades
  - [x] Identificar qué mantener igual
  - [x] Identificar qué mejorar
  - [x] Identificar qué simplificar
  - [x] Identificar qué agregar (integración Laravel)

- [x] **Crear plan de migración**
  - [x] Funcionalidades prioritarias a implementar primero
  - [x] Funcionalidades que podemos omitir inicialmente
  - [x] Estrategia de reemplazo gradual
  - [x] Compatibilidad con datos existentes (si aplica)

### 0.11 Crear Prototipos y Pruebas
- [x] **Crear prototipos de funcionalidades clave**
  - [x] Prototipo de estructura de datos básica
  - [x] Prototipo de sistema de ejecución simple
  - [x] Prototipo de UI básica
  - [x] Validar conceptos antes de implementación completa

- [x] **Realizar pruebas comparativas**
  - [x] Comparar rendimiento con Pixel Crushers
  - [x] Comparar facilidad de uso
  - [x] Comparar funcionalidades
  - [x] Identificar ventajas y desventajas

### 0.12 Herramientas de Análisis
- [x] **Crear scripts de análisis automatizado**
  - [x] Script para mapear estructura de clases
  - [x] Script para extraer dependencias
  - [x] Script para analizar uso de memoria
  - [x] Script para generar documentación automática

- [x] **Crear base de conocimiento**
  - [x] Wiki o documentación interna
  - [x] Notas de análisis por componente
  - [x] Decisiones de diseño documentadas
  - [x] Referencias y recursos útiles

---

## 📦 FASE 1: Arquitectura Base y Estructura de Datos

### 1.1 Modelos de Datos
- [x] **Crear ScriptableObject `DialogoData`**
  - [x] Propiedades: `id`, `nombre`, `descripcion`, `version`, `fechaCreacion`
  - [x] Lista de `NodoDialogo`
  - [x] Lista de `ConexionDialogo`
  - [x] Métodos: `GetNodoInicial()`, `GetNodosFinales()`, `ValidarEstructura()`

- [x] **Crear clase `NodoDialogo` (Serializable)**
  - [x] Propiedades: `id`, `titulo`, `contenido`, `tipo` (Inicio/Desarrollo/Decision/Final)
  - [x] `rolAsignado`, `posicion` (Vector2), `esInicial`, `esFinal`
  - [x] `instrucciones`, `condiciones`, `consecuencias`
  - [x] Lista de `RespuestaDialogo`

- [x] **Crear clase `RespuestaDialogo` (Serializable)**
  - [x] Propiedades: `id`, `texto`, `nodoDestinoId`, `puntuacion`
  - [x] `color`, `condiciones`, `requiereUsuarioRegistrado`
  - [x] `esOpcionPorDefecto` (para usuarios no registrados)

- [x] **Crear clase `ConexionDialogo` (Serializable)**
  - [x] Propiedades: `nodoOrigenId`, `nodoDestinoId`, `respuestaId`
  - [x] `puntosIntermedios` (para líneas curvas)

- [x] **Crear enum `TipoNodo`**
  - [x] `Inicio`, `Desarrollo`, `Decision`, `Final`, `Agrupacion`

### 1.2 Sistema de Almacenamiento
- [x] **Crear `DialogoStorageManager` (Singleton)**
  - [x] Método `GuardarDialogo(DialogoData dialogo)` → ScriptableObject
  - [x] Método `CargarDialogo(string dialogoId)` → DialogoData
  - [x] Método `CargarDesdeJSON(string jsonPath)` → DialogoData
  - [x] Método `ExportarAJSON(DialogoData dialogo)` → string JSON
  - [x] Método `ImportarDesdeLaravel(int dialogoId)` → Coroutine/async
  - [x] Método `SincronizarConLaravel(DialogoData dialogo)` → Coroutine/async
  - [x] Cache local de diálogos cargados

- [x] **Crear estructura de carpetas**
  - [x] `Assets/DialogoSystem/Data/` → ScriptableObjects
  - [x] `Assets/DialogoSystem/Data/JSON/` → Archivos JSON
  - [x] `Assets/DialogoSystem/Data/Resources/` → Recursos runtime

- [x] **Implementar serialización JSON**
  - [x] Usar `JsonUtility` o `Newtonsoft.Json`
  - [x] Convertir entre formato Laravel y formato Unity
  - [x] Validar estructura JSON al importar

---

## 🎨 FASE 2: Editor de Diálogos (Editor Window)

### 2.1 Ventana Principal del Editor
- [x] **Crear `DialogoEditorWindow` (EditorWindow)**
  - [x] Menú: `Tools > Sistema de Diálogos > Editor`
  - [x] Layout: Panel izquierdo (lista diálogos), Panel central (canvas), Panel derecho (propiedades)
  - [x] Toolbar: Nuevo, Abrir, Guardar, Exportar, Importar, Sincronizar

- [x] **Panel de Lista de Diálogos**
  - [x] Lista scrollable de diálogos disponibles
  - [x] Botones: Crear Nuevo, Refrescar
  - [x] Búsqueda/filtro de diálogos
  - [x] Indicador de diálogo modificado (sin guardar)

- [x] **Canvas del Editor (Panel Central)**
  - [x] Grid de fondo (200x200px por celda)
  - [x] Zoom in/out (0.1x a 2.0x)
  - [x] Pan con click medio o espacio + arrastre
  - [ ] Minimap en esquina (pendiente)
  - [ ] Ruler/guías opcionales (pendiente)

### 2.2 Sistema de Nodos en el Editor
- [x] **Crear `NodoEditor` (Editor GUI)**
  - [x] Renderizar nodo como rectángulo con estilo según tipo
  - [x] Mostrar título, contenido truncado, rol asignado
  - [x] Indicadores visuales: Inicial (verde), Final (rojo), Decisión (amarillo)
  - [x] Drag & drop para mover nodos
  - [x] Selección con click
  - [x] Multi-selección con Ctrl/Cmd

- [x] **Crear nodos desde el editor**
  - [x] Click derecho en canvas → "Crear Nodo"
  - [x] Menú contextual con tipos: Inicio, Desarrollo, Decisión, Final
  - [x] Posicionamiento automático en grid más cercano
  - [x] Validación: solo un nodo inicial, al menos un final

- [x] **Editar propiedades de nodo**
  - [x] Panel derecho muestra propiedades del nodo seleccionado
  - [x] Campos: Título, Contenido (textarea), Tipo, Rol
  - [x] Checkboxes: Es Inicial, Es Final
  - [x] Campo de instrucciones (opcional)
  - [x] Validación en tiempo real

- [x] **Eliminar nodos**
  - [x] Botón eliminar en panel de propiedades
  - [x] Confirmación antes de eliminar
  - [x] Eliminar conexiones asociadas automáticamente

### 2.3 Sistema de Conexiones en el Editor
- [x] **Crear conexiones visualmente**
  - [x] Click derecho en nodo → "Crear Conexión" → arrastrar a nodo destino
  - [x] Línea temporal mientras se arrastra
  - [x] Validar que no sea auto-conexión
  - [x] Crear `RespuestaDialogo` automáticamente

- [x] **Renderizar conexiones**
  - [x] Líneas rectas con flecha indicando dirección
  - [x] Color según respuesta o tipo
  - [ ] Curvas Bezier (pendiente - usar líneas rectas por ahora)
  - [ ] Etiqueta con texto de respuesta (pendiente)
  - [ ] Puntos de control para ajustar curva (pendiente)

- [x] **Editar conexiones**
  - [x] Selección de respuesta desde propiedades del nodo
  - [x] Panel derecho muestra propiedades de respuesta
  - [x] Campos: Texto, Puntuación, Color
  - [x] Checkbox: "Requiere Usuario Registrado"
  - [x] Checkbox: "Opción por Defecto" (para no registrados)
  - [x] Eliminar conexión

- [x] **Validación de conexiones**
  - [x] Validar que nodos destino existan
  - [ ] Prevenir conexiones duplicadas (pendiente)
  - [ ] Advertencia si nodo queda huérfano (pendiente)

### 2.4 Funcionalidades Avanzadas del Editor
- [x] **Sistema de Grid y Snap**
  - [x] Snap automático a grid (200x200px)
  - [x] Toggle para activar/desactivar snap
  - [ ] Ajustar tamaño de grid (pendiente)
  - [x] Mostrar/ocultar grid

- [ ] **Herramientas de organización**
  - [ ] Alinear nodos (izquierda, centro, derecha, arriba, abajo) (pendiente)
  - [ ] Distribuir nodos uniformemente (pendiente)
  - [ ] Agrupar nodos seleccionados (pendiente)
  - [ ] Deshacer/Rehacer (Undo/Redo system) (pendiente)

- [x] **Vista y navegación**
  - [x] Zoom con rueda del mouse
  - [x] Pan con click medio o espacio + arrastre
  - [ ] Centrar en nodo seleccionado (F) (pendiente)
  - [ ] Fit to screen (Ctrl/Cmd + 0) (pendiente)
  - [ ] Buscar nodo por ID o título (pendiente)

- [x] **Importar/Exportar**
  - [x] Importar desde JSON (formato Laravel)
  - [x] Exportar a JSON (formato Laravel)
  - [x] Validar estructura antes de importar
  - [x] Mostrar errores de validación

- [x] **Sincronización con Laravel**
  - [x] Botón "Sincronizar con Laravel" (placeholder)
  - [ ] Listar diálogos disponibles en backend (pendiente)
  - [ ] Descargar diálogo desde Laravel (pendiente)
  - [ ] Subir diálogo a Laravel (pendiente)
  - [ ] Resolver conflictos (local vs remoto) (pendiente)

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
