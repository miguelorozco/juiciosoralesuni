# 🔍 Auditoría del Sistema Actual de Diálogos

**Fecha de Auditoría**: Enero 2025  
**Rama**: `feature/nuevo-sistema-dialogos-v2`  
**Objetivo**: Documentar completamente el sistema actual antes de la migración a v2

---

## 📊 1. Tablas de Base de Datos

### 1.1 Tablas Principales del Sistema de Diálogos

#### `dialogos`
- **Migración**: `2025_01_15_000001_create_dialogos_table.php`
- **Campos principales**:
  - `id`, `nombre`, `descripcion`
  - `creado_por` (FK a users)
  - `plantilla_id` (FK a plantillas_sesiones, nullable)
  - `publico` (boolean)
  - `estado` (enum: borrador, activo, archivado)
  - `configuracion` (JSON)
  - Soft deletes habilitado

#### `nodos_dialogo`
- **Migración**: `2025_10_16_075333_create_nodos_dialogo_table.php`
- **Campos principales**:
  - `id`, `dialogo_id` (FK a dialogos)
  - `rol_id` (FK a roles_disponibles, nullable)
  - `titulo`, `contenido`, `instrucciones`
  - `orden` (integer)
  - `tipo` (enum: inicio, desarrollo, decision, final)
  - `condiciones` (JSON)
  - `metadata` (JSON) - **Contiene posiciones en formato JSON**
  - `es_inicial`, `es_final` (boolean)

#### `respuestas_dialogo`
- **Migración**: `2025_01_15_000003_create_respuestas_dialogo_table.php`
- **Campos principales**:
  - `id`
  - `nodo_padre_id` (FK a nodos_dialogo)
  - `nodo_siguiente_id` (FK a nodos_dialogo, nullable)
  - `texto`, `descripcion`
  - `orden` (integer)
  - `condiciones` (JSON)
  - `consecuencias` (JSON)
  - `puntuacion` (integer)
  - `color` (string 7)
  - `activo` (boolean)

#### `sesiones_dialogos`
- **Migración**: `2025_01_15_000004_create_sesiones_dialogos_table.php`
- **Campos principales**:
  - `id`
  - `sesion_id` (FK a sesiones_juicios)
  - `dialogo_id` (FK a dialogos)
  - `nodo_actual_id` (FK a nodos_dialogo, nullable)
  - `estado` (enum: iniciado, en_curso, pausado, finalizado, programada)
  - `fecha_inicio`, `fecha_fin` (timestamp, nullable)
  - `configuracion` (JSON)
  - `variables` (JSON)
  - Unique: `sesion_id` + `dialogo_id`

#### `decisiones_sesion`
- **Migración**: `2025_01_15_000005_create_decisiones_sesion_table.php`
- **Campos principales**:
  - `id`
  - `sesion_id` (FK a sesiones_juicios)
  - `usuario_id` (FK a users)
  - `rol_id` (FK a roles_disponibles)
  - `nodo_dialogo_id` (FK a nodos_dialogo)
  - `respuesta_id` (FK a respuestas_dialogo, nullable)
  - `decision_texto` (text, nullable)
  - `metadata` (JSON)
  - `tiempo_respuesta` (integer, nullable)
  - `fecha_decision` (timestamp)

### 1.2 Tablas del Sistema Panel Dialogo (Alternativo)

#### `panel_dialogo_escenarios`
- **Migración**: `2025_10_22_054105_create_panel_dialogo_system_tables.php`
- Sistema alternativo de diálogos

#### `panel_dialogo_dialogos`
- Sistema alternativo de diálogos

#### `panel_dialogo_flujos`
- Sistema alternativo de diálogos

#### `panel_dialogo_opciones`
- Sistema alternativo de diálogos

#### `panel_dialogo_conexiones`
- Sistema alternativo de diálogos

#### `panel_dialogo_roles`
- Sistema alternativo de roles

#### `panel_dialogo_asignaciones`
- Sistema alternativo de asignaciones

#### `panel_dialogo_sesiones`
- Sistema alternativo de sesiones

#### `panel_dialogo_decisiones`
- Sistema alternativo de decisiones

#### `roles_dialogo`
- **Migración**: `2025_10_22_042527_create_roles_dialogo_table.php`
- Roles específicos para diálogos

### 1.3 Migraciones Relacionadas

- `2025_10_22_015149_add_foreign_keys_to_tables.php` - Foreign keys adicionales
- `2025_10_22_035908_add_tipo_to_sesiones_juicios_table.php` - Campo tipo en sesiones
- `2025_10_22_041408_add_programada_to_sesiones_dialogos_estado.php` - Estado programada
- `2025_10_22_042639_update_asignaciones_roles_table.php` - Actualización asignaciones
- `2025_10_22_042902_make_rol_id_nullable_in_asignaciones_roles.php` - Rol nullable

---

## 🏗️ 2. Modelos Eloquent

### 2.1 Modelos Principales

#### `Dialogo` (`app/Models/Dialogo.php`)
- **Tabla**: `dialogos`
- **Relaciones**:
  - `creador()` → User
  - `plantilla()` → PlantillaSesion
  - `nodos()` → NodoDialogo (hasMany)
  - `sesiones()` → SesionDialogo (hasMany)
- **Scopes**: `activos()`, `publicos()`, `delUsuario()`, `disponiblesParaUsuario()`
- **Métodos clave**:
  - `obtenerEstructuraGrafo()` - Obtiene estructura completa del grafo
  - `actualizarPosicionesNodos()` - Actualiza posiciones desde metadata JSON
  - `validarEstructuraGrafo()` - Valida estructura del diálogo
  - `crearCopia()` - Crea copia del diálogo con nodos y respuestas

#### `NodoDialogo` (`app/Models/NodoDialogo.php`)
- **Tabla**: `nodos_dialogo`
- **Relaciones**:
  - `dialogo()` → Dialogo
  - `rol()` → RolDisponible
  - `respuestas()` → RespuestaDialogo (hasMany, nodo_padre_id)
  - `respuestasEntrantes()` → RespuestaDialogo (hasMany, nodo_siguiente_id)
  - `decisiones()` → DecisionSesion (hasMany)
- **Accessors**:
  - `posicion` - Extrae de metadata JSON
  - `x`, `y` - Acceso directo a coordenadas
- **Métodos clave**:
  - `actualizarPosicion($x, $y)` - Actualiza posición en metadata
  - `obtenerRespuestasDisponibles()` - Filtra respuestas por condiciones
  - `evaluarCondiciones()` - Evalúa condiciones del nodo
  - `marcarComoInicial()` - Marca como inicial (desmarca otros)

#### `RespuestaDialogo` (`app/Models/RespuestaDialogo.php`)
- **Tabla**: `respuestas_dialogo`
- **Relaciones**:
  - `nodoPadre()` → NodoDialogo
  - `nodoSiguiente()` → NodoDialogo
  - `decisiones()` → DecisionSesion (hasMany)
- **Métodos clave**:
  - `aplicarConsecuencias()` - Aplica consecuencias a variables
  - `evaluarCondiciones()` - Evalúa condiciones de la respuesta
  - `obtenerEstadisticas()` - Estadísticas de selección

#### `SesionDialogo` (`app/Models/SesionDialogo.php`)
- **Tabla**: `sesiones_dialogos`
- **Relaciones**:
  - `sesion()` → SesionJuicio
  - `dialogo()` → Dialogo
  - `nodoActual()` → NodoDialogo

#### `DecisionSesion` (`app/Models/DecisionSesion.php`)
- **Tabla**: `decisiones_sesion`
- **Relaciones**:
  - `sesion()` → SesionJuicio
  - `usuario()` → User
  - `rol()` → RolDisponible
  - `nodoDialogo()` → NodoDialogo
  - `respuesta()` → RespuestaDialogo
- **Métodos clave**:
  - `calcularPuntuacion()` - Calcula puntuación con modificadores
  - `obtenerEstadisticas()` - Estadísticas de la decisión
  - `obtenerEstadisticasGenerales()` - Estadísticas generales (static)
  - `obtenerEstadisticasPorRol()` - Estadísticas por rol (static)
  - `obtenerEstadisticasPorUsuario()` - Estadísticas por usuario (static)

### 2.2 Modelos del Sistema Panel Dialogo

- `PanelDialogoEscenario`
- `PanelDialogoDialogo`
- `PanelDialogoFlujo`
- `PanelDialogoOpcion`
- `PanelDialogoConexion`
- `PanelDialogoRol`
- `PanelDialogoAsignacion`
- `PanelDialogoSesion`
- `PanelDialogoDecision`
- `RolDialogo`

### 2.3 Modelos Relacionados (Dependencias)

#### `SesionJuicio` (`app/Models/SesionJuicio.php`)
- **Relaciones con diálogos**:
  - `dialogos()` → SesionDialogo (hasMany)
  - `dialogoActivo()` - Obtiene diálogo activo de la sesión

#### `AsignacionRol` (`app/Models/AsignacionRol.php`)
- Relacionado con sesiones y roles de diálogos

#### `RolDisponible` (`app/Models/RolDisponible.php`)
- Roles disponibles para asignar en diálogos

---

## 🎮 3. Controladores

### 3.1 Controladores Principales

#### `DialogoController` (`app/Http/Controllers/DialogoController.php`)
- **Rutas API**:
  - `GET /api/dialogos` - Listar diálogos
  - `POST /api/dialogos` - Crear diálogo
  - `GET /api/dialogos/{id}` - Mostrar diálogo
  - `PUT /api/dialogos/{id}` - Actualizar diálogo
  - `DELETE /api/dialogos/{id}` - Eliminar diálogo
  - `POST /api/dialogos/{id}/activar` - Activar diálogo
  - `POST /api/dialogos/{id}/copiar` - Copiar diálogo
  - `GET /api/dialogos/{id}/estructura` - Obtener estructura
  - `POST /api/dialogos/{id}/posiciones` - Actualizar posiciones
- **Rutas Web**:
  - `/dialogos-legacy` - Vista legacy

#### `NodoDialogoController` (`app/Http/Controllers/NodoDialogoController.php`)
- **Rutas API**:
  - `POST /api/dialogos/{dialogo}/nodos` - Crear nodo
  - `PUT /api/nodos/{id}` - Actualizar nodo
  - `DELETE /api/nodos/{id}` - Eliminar nodo
  - `POST /api/nodos/{id}/marcar-inicial` - Marcar como inicial
  - `GET /api/nodos/{id}/respuestas` - Obtener respuestas
  - `POST /api/nodos/{id}/respuestas` - Agregar respuesta

#### `DialogoFlujoController` (`app/Http/Controllers/DialogoFlujoController.php`)
- **Rutas API**:
  - `POST /api/sesiones/{id}/iniciar-dialogo` - Iniciar diálogo en sesión
  - `GET /api/sesiones/{id}/dialogo-actual` - Estado actual
  - `GET /api/sesiones/{id}/respuestas-disponibles/{usuario}` - Respuestas disponibles
  - `POST /api/sesiones/{id}/procesar-decision` - Procesar decisión
  - `POST /api/sesiones/{id}/avanzar-dialogo` - Avanzar diálogo
  - `POST /api/sesiones/{id}/pausar-dialogo` - Pausar diálogo
  - `POST /api/sesiones/{id}/finalizar-dialogo` - Finalizar diálogo
  - `GET /api/sesiones/{id}/historial-decisiones` - Historial

#### `DialogoImportController` (`app/Http/Controllers/DialogoImportController.php`)
- **Rutas API**:
  - `POST /api/dialogos/import` - Importar desde JSON
  - `GET /api/dialogos/{id}/export` - Exportar a JSON

#### `UnityDialogoController` (`app/Http/Controllers/UnityDialogoController.php`)
- **Rutas API Unity**:
  - `GET /api/unity/sesion/{id}/dialogo-estado` - Estado del diálogo
  - `GET /api/unity/sesion/{id}/respuestas-usuario/{usuario}` - Respuestas del usuario
  - `POST /api/unity/sesion/{id}/enviar-decision` - Enviar decisión
  - `POST /api/unity/sesion/{id}/notificar-hablando` - Notificar habla
  - `GET /api/unity/sesion/{id}/movimientos-personajes` - Movimientos

#### `PanelDialogoController` (`app/Http/Controllers/PanelDialogoController.php`)
- **Rutas API**:
  - `GET /api/panel-dialogos` - Listar escenarios
  - `POST /api/panel-dialogos` - Crear escenario
  - Rutas para roles, flujos, diálogos, opciones, conexiones
- **Rutas Web**:
  - `/panel-dialogos` - Vista principal
  - `/panel-dialogos/create` - Crear escenario
  - `/panel-dialogos/{id}` - Mostrar escenario
  - `/panel-dialogos/{id}/editor` - Editor

---

## 🛣️ 4. Rutas

### 4.1 Rutas API (`routes/api.php`)

#### Grupo `/api/dialogos`
- 9 rutas principales para CRUD de diálogos
- 2 rutas para import/export
- 1 ruta para nodos

#### Grupo `/api/nodos`
- 6 rutas para gestión de nodos y respuestas

#### Grupo `/api/sesiones/{id}/...`
- 7 rutas para flujo de diálogos en sesiones

#### Grupo `/api/unity/sesion/{id}/...`
- 5 rutas para integración Unity

#### Grupo `/api/panel-dialogos`
- Múltiples rutas para sistema Panel Dialogo alternativo

### 4.2 Rutas Web (`routes/web.php`)

- `/dialogos` → Redirige a `/panel-dialogos`
- `/dialogos-legacy` → Sistema legacy
- `/panel-dialogos` → Sistema nuevo
- `/dialogos/migration-info` → Información de migración

---

## 🌱 5. Seeders

### 5.1 Seeders de Diálogos

#### `DialogoEjemploSeeder`
- Diálogo de ejemplo básico

#### `DialogoRoboOXXOSeeder`
- Diálogo de robo a OXXO (versión simple)

#### `DialogoRoboOXXOCompletoSeeder`
- Diálogo de robo a OXXO (versión completa, 1309 líneas)

#### `DialogoJuicioPenalSeeder`
- Diálogo de juicio penal (424 líneas)

#### `PanelDialogoEscenarioSeeder`
- Seeders para sistema Panel Dialogo

#### `RolesDialogoSeeder`
- Seeders de roles de diálogos

---

## 🔗 6. Dependencias y Relaciones

### 6.1 Dependencias con Otros Módulos

#### Módulo de Sesiones (`SesionJuicio`)
- **Relación**: `hasMany` con `SesionDialogo`
- **Uso**: Cada sesión puede tener múltiples diálogos activos
- **Impacto**: Necesario mantener compatibilidad

#### Módulo de Usuarios (`User`)
- **Relación**: `belongsTo` en `Dialogo` (creado_por)
- **Relación**: `belongsTo` en `DecisionSesion` (usuario_id)
- **Impacto**: Necesario mantener referencias

#### Módulo de Roles (`RolDisponible`)
- **Relación**: `belongsTo` en `NodoDialogo` (rol_id)
- **Relación**: `belongsTo` en `DecisionSesion` (rol_id)
- **Impacto**: Necesario mantener compatibilidad

#### Módulo de Plantillas (`PlantillaSesion`)
- **Relación**: `belongsTo` en `Dialogo` (plantilla_id, nullable)
- **Impacto**: Baja, relación opcional

### 6.2 Foreign Keys Críticas

```sql
-- Dialogos
dialogos.creado_por → users.id
dialogos.plantilla_id → plantillas_sesiones.id (nullable)

-- Nodos
nodos_dialogo.dialogo_id → dialogos.id (CASCADE)
nodos_dialogo.rol_id → roles_disponibles.id (SET NULL)

-- Respuestas
respuestas_dialogo.nodo_padre_id → nodos_dialogo.id (CASCADE)
respuestas_dialogo.nodo_siguiente_id → nodos_dialogo.id (SET NULL)

-- Sesiones Diálogos
sesiones_dialogos.sesion_id → sesiones_juicios.id (CASCADE)
sesiones_dialogos.dialogo_id → dialogos.id (CASCADE)
sesiones_dialogos.nodo_actual_id → nodos_dialogo.id (SET NULL)

-- Decisiones
decisiones_sesion.sesion_id → sesiones_juicios.id
decisiones_sesion.usuario_id → users.id
decisiones_sesion.rol_id → roles_disponibles.id
decisiones_sesion.nodo_dialogo_id → nodos_dialogo.id
decisiones_sesion.respuesta_id → respuestas_dialogo.id (nullable)
```

---

## 📝 7. Problemas Identificados en el Sistema Actual

### 7.1 Problemas de Diseño

1. **Posiciones en JSON**: Las posiciones están en `metadata` JSON en lugar de campos directos
   - Dificulta consultas por posición
   - No hay índices en posiciones
   - Extracción requiere parsing JSON

2. **Falta soporte para usuarios no registrados**:
   - No hay campo `requiere_usuario_registrado` en respuestas
   - No hay campo `es_opcion_por_defecto`
   - No hay tracking de usuarios no registrados en decisiones

3. **Sistema dual**: Existen dos sistemas paralelos
   - Sistema principal (`Dialogo`, `NodoDialogo`, etc.)
   - Sistema Panel Dialogo (`PanelDialogo*`)
   - Confusión y duplicación de código

4. **Falta historial de nodos**: No hay tracking de nodos visitados en sesiones

5. **Metadata sin estructura**: Campos JSON sin validación estricta

### 7.2 Problemas de Performance

1. **Falta de índices**: No hay índices en posiciones (porque están en JSON)
2. **Consultas N+1**: Posibles en relaciones complejas
3. **Sin cache**: No hay sistema de cache para diálogos cargados

### 7.3 Problemas de Mantenibilidad

1. **Código duplicado**: Dos sistemas de diálogos
2. **Validaciones dispersas**: Validaciones en múltiples lugares
3. **Falta de versionado**: No hay control de versiones de diálogos

---

## 📊 8. Análisis de Datos Existentes

### 8.1 Scripts de Análisis Necesarios

```sql
-- Contar registros por tabla
SELECT 'dialogos' as tabla, COUNT(*) as total FROM dialogos
UNION ALL
SELECT 'nodos_dialogo', COUNT(*) FROM nodos_dialogo
UNION ALL
SELECT 'respuestas_dialogo', COUNT(*) FROM respuestas_dialogo
UNION ALL
SELECT 'sesiones_dialogos', COUNT(*) FROM sesiones_dialogos
UNION ALL
SELECT 'decisiones_sesion', COUNT(*) FROM decisiones_sesion;

-- Diálogos con más nodos
SELECT d.id, d.nombre, COUNT(n.id) as total_nodos
FROM dialogos d
LEFT JOIN nodos_dialogo n ON n.dialogo_id = d.id
GROUP BY d.id, d.nombre
ORDER BY total_nodos DESC;

-- Nodos con posiciones definidas
SELECT COUNT(*) as nodos_con_posicion
FROM nodos_dialogo
WHERE metadata IS NOT NULL 
  AND JSON_EXTRACT(metadata, '$.posicion') IS NOT NULL;

-- Respuestas sin nodo siguiente (finales)
SELECT COUNT(*) as respuestas_finales
FROM respuestas_dialogo
WHERE nodo_siguiente_id IS NULL;
```

### 8.2 Datos Críticos a Migrar

1. **Todos los diálogos activos**
2. **Todos los nodos con sus posiciones** (extraer de metadata)
3. **Todas las respuestas y conexiones**
4. **Sesiones de diálogos activas**
5. **Decisiones históricas** (para estadísticas)

---

## 🎯 9. Plan de Acción para Migración

### 9.1 Fase 1: Preparación
1. ✅ Auditoría completa (este documento)
2. ⏳ Crear script de backup de datos
3. ⏳ Documentar formato de datos actual

### 9.2 Fase 2: Diseño
1. ✅ Diseño de nuevo esquema (ver `database-design-v2.md`)
2. ⏳ Crear nuevas migraciones
3. ⏳ Validar diseño con stakeholders

### 9.3 Fase 3: Implementación
1. ⏳ Crear tablas v2
2. ⏳ Crear modelos v2
3. ⏳ Scripts de migración de datos
4. ⏳ Tests de migración

### 9.4 Fase 4: Transición
1. ⏳ Migrar datos
2. ⏳ Actualizar controladores
3. ⏳ Actualizar rutas
4. ⏳ Tests de funcionalidad

### 9.5 Fase 5: Limpieza
1. ⏳ Eliminar código antiguo
2. ⏳ Eliminar tablas antiguas
3. ⏳ Documentación final

---

## 📋 10. Checklist de Migración

### Pre-Migración
- [ ] Backup completo de base de datos
- [ ] Backup de código actual
- [ ] Documentar todos los endpoints en uso
- [ ] Identificar datos de producción críticos
- [ ] Plan de rollback preparado

### Durante Migración
- [ ] Crear tablas v2
- [ ] Migrar datos
- [ ] Validar integridad referencial
- [ ] Tests de funcionalidad
- [ ] Verificar performance

### Post-Migración
- [ ] Actualizar documentación
- [ ] Notificar a usuarios
- [ ] Monitorear errores
- [ ] Optimizar queries
- [ ] Eliminar código antiguo

---

**Última actualización**: Enero 2025  
**Estado**: Auditoría completada  
**Próximo paso**: Crear script de backup y análisis de datos
