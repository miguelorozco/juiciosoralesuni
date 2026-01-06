# 📚 Documentación del Sistema de Diálogos

Bienvenido a la documentación completa del sistema de diálogos ramificados para simulacros de juicios orales.

## 📋 Índice de Documentación

### 🎯 [Formato JSON para Importación de Diálogos](./dialogo-json-format.md)
**Guía completa para crear archivos JSON de diálogos**

- ✅ Estructura general del JSON
- ✅ Definición de nodos y conexiones
- ✅ Sistema de posicionamiento en grid
- ✅ Gestión de roles automática
- ✅ Ejemplos completos y plantillas
- ✅ Validaciones y mejores prácticas
- ✅ Troubleshooting y optimizaciones

### 🛠️ [TODO List - Sistema de Diálogos Unity](./unity-dialogo-system-todo.md)
**Plan de desarrollo del sistema de diálogos propio para Unity**

- 📋 Arquitectura base y estructura de datos
- 🎨 Editor de diálogos visual
- 💾 Sistema de almacenamiento y persistencia
- 🎬 Sistema de reproducción de diálogos
- 🎭 Sistema de UI para diálogos
- 👥 Asignación de diálogos a personajes
- 🔄 Integración con Laravel
- 🧪 Testing y validación
- 📚 Documentación
- 🚀 Optimización y mejoras

### 🗄️ [Diseño de Base de Datos v2](./database-design-v2.md)
**Esquema completo del nuevo sistema de base de datos**

- 📊 Estructura de tablas optimizada
- 🔗 Relaciones entre tablas
- 📝 Campos y tipos de datos
- ✅ Validaciones y constraints
- 🚀 Índices para performance
- 📋 Guía de migración de datos

### 📊 [Diagrama de Relaciones v2](./database-design-v2-diagrama.md)
**Diagrama ER y relaciones detalladas del sistema v2**

- 🔗 Diagrama ER simplificado
- 📋 Relaciones detalladas entre tablas
- 🔄 Flujo de datos
- 🎯 Cardinalidades
- 📊 Índices y performance

### 📄 [Formatos JSON v2](./database-design-v2-formatos-json.md)
**Documentación detallada de formatos JSON**

- 📋 Formatos de todos los campos JSON
- ✅ Ejemplos de uso
- 🔍 Reglas de validación
- 💡 Mejores prácticas

### 🎯 [Alineación con Pixel Crushers](./pixel-crushers-alignment.md)
**Mapeo y alineación con Dialogue System de Pixel Crushers**

- 🔄 Mapeo de conceptos
- 🔑 Características clave replicadas
- ⚠️ Mejoras necesarias
- 📋 Checklist de alineación
- 🎯 Prioridades de implementación

### 🔍 [Auditoría del Sistema Actual](./auditoria-sistema-dialogos-actual.md)
**Análisis completo del sistema actual antes de migración**

- 📊 Tablas de base de datos
- 🏗️ Modelos Eloquent
- 🎮 Controladores
- 🛣️ Rutas API/Web
- 🌱 Seeders
- 🔗 Dependencias
- 📝 Problemas identificados
- 📋 Plan de acción

### 📝 [Evaluación de Decisiones por Profesor](./evaluacion-decisiones-profesor.md)
**Guía completa para evaluación de decisiones por profesores/instructores**

- 📊 Campos de evaluación
- 🔄 Flujo de evaluación
- 📋 Estados de evaluación
- 🎯 Casos de uso
- 📊 Vistas y consultas
- 🎓 Criterios de evaluación
- 📈 Métricas y reportes

### 🎤 [Sistema de Grabación de Audio MP3](./audio-grabacion-dialogos.md)
**Sistema completo de grabación y almacenamiento de audio MP3**

- 📊 Campos de audio en base de datos
- 📁 Almacenamiento de archivos
- 🔄 Flujo de grabación
- 🎯 Casos de uso y APIs
- 🔧 Helpers y utilidades
- 🔒 Permisos y seguridad
- 🎓 Uso para retroalimentación
- 🔄 Integración con Unity

### 🔄 [Guía de Migración Dialogos v2](./migracion-dialogos-v2.md)
**Guía completa para migrar al nuevo sistema de diálogos v2**

- 📋 Resumen ejecutivo de cambios
- 🚀 Proceso de migración paso a paso
- ✅ Checklist completo de migración
- 🔧 Troubleshooting común
- ↩️ Guía de rollback
- 📡 Cambios en la API
- 📊 Cambios en formatos de datos
- 🎮 Guía de migración para Unity

### 🔍 [FASE 0.1: Análisis Pixel Crushers](./fase-0.1-analisis-pixel-crushers.md)
**Análisis profundo de la arquitectura y estructura del Dialogue System de Pixel Crushers**

- 📁 Estructura de carpetas del plugin
- 🏗️ Clases core del sistema (DialogueSystemController, DialogueDatabase, etc.)
- 📊 Modelo de datos completo (Conversation, DialogueEntry, Actor, etc.)
- 🎨 Patrones de diseño utilizados (Singleton, Observer, MVC, Strategy)
- 🔗 Dependencias entre módulos
- 📈 Diagramas de estructura y ER

### 🔄 [FASE 0.2: Análisis del Sistema de Diálogos](./fase-0.2-analisis-sistema-dialogos.md)
**Análisis del flujo de ejecución, nodos, conexiones y scripting del Dialogue System**

- 🚀 Flujo de ejecución de conversaciones (inicio, navegación, fin)
- 🔗 Sistema de nodos y conexiones (Links, tipos de nodos, grafo)
- 📝 Sistema de condiciones y scripting (Lua, variables, Sequencer)
- 🎯 Diagramas de flujo completos
- 💡 Ejemplos de código y uso

### ✏️ [FASE 0.3: Análisis del Editor](./fase-0.3-analisis-editor.md)
**Análisis del editor de diálogos y sistema de importación/exportación**

- 🎨 Editor de diálogos (Node Editor y Outline Editor)
- 📊 Sistema de visualización del grafo (zoom, pan, links)
- 🛠️ Herramientas de organización (auto-arrange, grupos, validación)
- 📥 Importación (Chat Mapper, Articy, Celtx, Yarn, JSON)
- 📤 Exportación (Chat Mapper, CSV, Screenplay, Voiceover, etc.)
- ✅ Validación de datos y estructura

### 🎨 [FASE 0.4: Análisis del Sistema de UI](./fase-0.4-analisis-sistema-ui.md)
**Análisis completo del sistema de UI y personalización**

- 🖼️ Componentes de UI (IDialogueUI, AbstractDialogueUI, StandardDialogueUI)
- 📝 Sistema de subtítulos (paneles, retratos, texto)
- 🎯 Sistema de menús y respuestas (botones, paneles, override)
- 🎭 Sistema de retratos/portraits (animados, nativos, override)
- ✨ Efectos visuales (typewriter, fade, color)
- 🎨 Sistema de personalización (prefabs, temas, localización)
- 🌍 Localización e internacionalización
- 🔤 Sistema de fuentes y textos (Unity UI Text, TextMesh Pro)

### 👥 [FASE 0.5: Análisis del Sistema de Actores y Personajes](./fase-0.5-analisis-actores-personajes.md)
**Análisis completo del sistema de actores e integración con personajes**

- 🎭 DialogueActor (componente principal de actores)
- 📋 CharacterInfo (información de personajes)
- 🖼️ Sistema de retratos/portraits (estáticos, animados, alternativos)
- 🎨 Override de UI por actor (paneles personalizados)
- 💬 Sistema de bark (comentarios breves)
- 🔗 Integración con personajes del juego
- ⚡ Sistema de triggers (DialogueSystemTrigger)
- 📍 Proximidad y detección (ProximitySelector)
- 🎮 Sistema de interacción (Usable)

### 💾 [FASE 0.6: Análisis del Sistema de Almacenamiento](./fase-0.6-analisis-almacenamiento.md)
**Análisis del sistema de almacenamiento y comparación con nuestra BD v2**

- 📦 DialogueDatabase (ScriptableObject)
- 💾 Sistema de persistencia (PersistentDataManager, Lua)
- 💿 Sistema de guardado (Save System Integration)
- 🔄 Comparación con nuestra base de datos v2
- 🗺️ Mapeo de estructuras (DialogueDatabase → dialogos_v2, etc.)
- 📊 Diferencias arquitectónicas (Single-Player vs Multi-User)
- 📥 Sistema de recursos (Resources, AssetBundles, API REST)
- ✅ Ventajas y desventajas de cada enfoque

### 🎯 [FASE 0.7 y 0.8: Funcionalidades Avanzadas e Integraciones](./fase-0.7-0.8-funcionalidades-avanzadas-integraciones.md)
**Análisis de funcionalidades avanzadas y sistema de extensiones**

- 🎮 Sistema de misiones (Quests) - QuestLog, QuestState, Quest Entries
- 🌍 Sistema de localización (Localization, TextTable)
- 📢 Sistema de eventos (DialogueSystemEvents, Unity Events, Messages)
- 🎬 Integraciones (Timeline, Cinemachine, Input System, TextMesh Pro)
- 🔧 Sistema de extensibilidad (Custom Commands, Custom UI, Custom Lua Functions)
- 🔌 Hooks y callbacks disponibles
- 📊 Comparación con nuestra implementación

### ⚡ [FASE 0.9: Análisis de Rendimiento y Optimización](./fase-0.9-rendimiento-optimizacion.md)
**Análisis de optimizaciones y limitaciones de rendimiento**

- 🚀 Optimizaciones implementadas (Cache, Preloading, Warm-up)
- 🔍 Optimizaciones de búsqueda (Evitar GameObject.Find, GetComponent)
- 🎨 Optimizaciones de UI (Cache de paneles, Reutilización)
- ⚙️ Optimizaciones de Lua (Stop at First Valid, Linear Group Mode)
- ⚠️ Limitaciones y problemas conocidos (Rendimiento, Diseño, Compatibilidad)
- 💡 Recomendaciones para nuestra implementación (Cache Laravel, Optimizaciones BD, Unity)
- 📈 Métricas y profiling

### 📚 [FASE 0.10: Documentación Técnica para Desarrollo](./fase-0.10-documentacion-desarrollo.md)
**Documentación técnica completa para el desarrollo del sistema**

- 🏗️ Arquitectura del sistema (Cliente-Servidor, Componentes)
- 📊 Diagramas y flujos (Creación, Reproducción, Evaluación)
- 🔄 Comparativa con Pixel Crushers (Tabla de funcionalidades)
- 📋 Plan de desarrollo (Fases, Prioridades, Checklist)
- 🎯 Funcionalidades clave a replicar
- 🗺️ Mapa de dependencias
- ✅ Estrategia de implementación incremental

### 🧪 [FASE 0.11: Prototipos y Pruebas](./fase-0.11-prototipos-pruebas.md)
**Prototipos de funcionalidades clave y pruebas comparativas**

- 🔬 Prototipo de estructura de datos básica (Backend ✅, Unity 🟡)
- ⚙️ Prototipo de sistema de ejecución simple (Backend ✅, Unity 🟡)
- 🎨 Prototipo de UI básica (Unity 🟡)
- 📊 Pruebas comparativas (Rendimiento, Facilidad de uso, Funcionalidades)
- ✅ Validación de conceptos (Estructura, Ejecución, Multi-Usuario, Evaluación)
- 📈 Resultados y conclusiones

### 🔧 [FASE 0.12: Herramientas de Análisis](./fase-0.12-herramientas-analisis.md)
**Scripts de análisis automatizado y base de conocimiento**

- 📊 Script para mapear estructura de clases (PHP)
- 🔗 Script para extraer dependencias (PHP + Graphviz)
- 💾 Script para analizar uso de memoria (PHP)
- 📝 Script para generar documentación automática (PHP)
- 📚 Base de conocimiento (Notas por componente, Decisiones de diseño)
- 🔗 Referencias y recursos útiles (Pixel Crushers, Laravel, Unity)

### 🚀 Características Principales

#### **Sistema de Grid Inteligente**
- Posicionamiento automático en celdas de 200x200px
- Prevención de solapamientos
- Navegación fluida por el editor

#### **Tipos de Nodos**
- **Inicio**: Punto de entrada único
- **Desarrollo**: Contenido narrativo
- **Decisión**: Puntos de elección múltiple
- **Final**: Puntos de salida

#### **Sistema de Conexiones**
- Líneas multipuntos rectas
- Colores personalizables
- Puntuaciones por respuesta
- Validación automática

#### **Gestión de Roles**
- Creación automática de roles
- Colores y iconos personalizados
- Asignación por nodo

## 🎨 Ejemplos de Uso

### Simulacros de Juicios Civiles
```json
{
  "dialogo": {
    "nombre": "Juicio Civil - Contrato",
    "descripcion": "Simulación de incumplimiento de contrato"
  },
  "nodos": [...],
  "conexiones": [...]
}
```

### Simulacros de Juicios Penales
```json
{
  "dialogo": {
    "nombre": "Juicio Penal - Robo",
    "descripcion": "Simulación de juicio por robo agravado"
  },
  "nodos": [...],
  "conexiones": [...]
}
```

### Entrevistas y Evaluaciones
```json
{
  "dialogo": {
    "nombre": "Entrevista de Trabajo",
    "descripcion": "Simulación de entrevista laboral"
  },
  "nodos": [...],
  "conexiones": [...]
}
```

## 🔧 Herramientas Disponibles

### Interfaz Web
- **Editor Visual**: Creación y edición de diálogos
- **Importación JSON**: Carga masiva de diálogos
- **Vista previa**: Validación antes de importar
- **Plantillas**: Ejemplos descargables

### API REST
- **POST /api/dialogos/import**: Importar diálogo
- **GET /api/dialogos/{id}/export**: Exportar diálogo
- **Validación automática**: Verificación de estructura

### Plantillas Incluidas
1. **Diálogo Básico** (3 nodos)
2. **Diálogo con Decisión** (5 nodos)
3. **Juicio Civil Completo** (8+ nodos)
4. **Juicio Penal Completo** (10+ nodos)

## 📊 Métricas y Rendimiento

### Límites Recomendados
- **Nodos por diálogo**: Máximo 50
- **Conexiones por nodo**: Máximo 10
- **Tamaño de archivo**: Máximo 1MB
- **Tiempo de importación**: Menos de 30 segundos

### Optimizaciones
- **IDs cortos**: Usar identificadores concisos
- **Estructura simple**: Evitar anidaciones complejas
- **Validación previa**: Verificar antes de importar
- **División modular**: Separar diálogos grandes

## 🚀 Inicio Rápido

### 1. Crear tu Primer Diálogo
```bash
# Descargar plantilla básica
curl -O https://ejemplo.com/plantilla-basica.json

# Editar con tu editor preferido
code plantilla-basica.json

# Importar en el sistema
# Ir a /dialogos/import
```

### 2. Estructura Mínima
```json
{
  "dialogo": {
    "nombre": "Mi Diálogo",
    "descripcion": "Descripción del diálogo",
    "publico": false
  },
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "contenido": "Bienvenido",
      "rol_nombre": "Sistema",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    }
  ],
  "conexiones": []
}
```

### 3. Validación
- Usar la vista previa en la interfaz
- Verificar estructura con JSONLint
- Probar con diálogos pequeños primero

## 📞 Soporte y Ayuda

### Recursos de Ayuda
- **Documentación completa**: [dialogo-json-format.md](./dialogo-json-format.md)
- **Ejemplos interactivos**: Disponibles en la interfaz
- **Plantillas descargables**: En la sección de importación
- **Foro de usuarios**: Para preguntas y respuestas

### Contacto
- **Email**: soporte@simulador-juicios.com
- **Teléfono**: +1 (555) 123-4567
- **Horario**: Lunes a Viernes, 9:00 - 18:00

## 🔄 Actualizaciones

### Versión Actual: 1.0
- ✅ Sistema de grid implementado
- ✅ Importación JSON funcional
- ✅ Líneas multipuntos rectas
- ✅ Gestión automática de roles
- ✅ Validaciones completas

### Próximas Versiones
- 🔄 Editor visual mejorado
- 🔄 Exportación a otros formatos
- 🔄 Colaboración en tiempo real
- 🔄 Analytics de uso

---

**Desarrollado con ❤️ para la educación legal**  
**Sistema de Diálogos v2.0** | **Septiembre 2025**
