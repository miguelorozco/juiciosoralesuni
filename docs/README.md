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
