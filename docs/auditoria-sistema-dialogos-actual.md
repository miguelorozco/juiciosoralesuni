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
- **Índices**: `creado_por`, `estado`, `publico`

#### `nodos_dialogo`
- **Migración**: `2025_10_16_075333_create_nodos_dialogo_table.php`
- **Campos principales**:
  - `id`, `dialogo_id` (FK a dialogos)
  - `rol_id` (FK a roles_disponibles, nullable)
  - `titulo`, `contenido`, `instrucciones`
  - `orden` (integer)
  - `tipo` (enum: inicio, desarrollo, decision, final)
  - `condiciones` (JSON)
  - `metadata` (JSON) - **Contiene posiciones en formato JSON** ⚠️
  - `es_inicial`, `es_final` (boolean)
- **Problema crítico**: Las posiciones están en JSON, no en campos directos

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
- **Problema**: No hay soporte para usuarios no registrados

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
- **Problema**: No hay historial de nodos visitados

#### `decisiones_sesion`
- **Migración**: `2025_01_15_000005_create_decisiones_sesion_table.php`
- **Campos principales**:
  - `id`
  - `sesion_id` (FK a sesiones_juicios)
  - `usuario_id` (FK a users) ⚠️ **NO NULLABLE - No soporta usuarios no registrados**
  - `rol_id` (FK a roles_disponibles)
  - `nodo_dialogo_id` (FK a nodos_dialogo)
  - `respuesta_id` (FK a respuestas_dialogo, nullable)
  - `decision_texto` (text, nullable)
  - `metadata` (JSON)
  - `tiempo_respuesta` (integer, nullable)
  - `fecha_decision` (timestamp)
- **Problema crítico**: `usuario_id` no es nullable, no permite usuarios no registrados

### 1.2 Tablas del Sistema Panel Dialogo (Alternativo)

#### `panel_dialogo_escenarios`
- **Migración**: `2025_10_22_054105_create_panel_dialogo_system_tables.php`
- Sistema alternativo de diálogos
- **Estado**: Sistema paralelo, puede causar confusión

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
- **Fillable**: `nombre`, `descripcion`, `creado_por`, `plantilla_id`, `publico`, `estado`, `configuracion`
- **Casts**: `publico` → boolean, `configuracion` → array
- **Relaciones**:
  - `creador()` → User (belongsTo)
  - `plantilla()` → PlantillaSesion (belongsTo, nullable)
  - `nodos()` → NodoDialogo (hasMany, ordenado por `orden`)
  - `roles()` → RolDialogo (hasMany)
  - `rolesActivos()` → RolDialogo (hasMany, filtrado por activo)
- **Scopes**: 
  - `activos()` - Filtra por estado 'activo'
  - `publicos()` - Filtra por publico = true
  - `delUsuario($userId)` - Filtra por creado_por
  - `disponiblesParaUsuario($user)` - Públicos o del usuario
- **Accessors**:
  - `total_nodos` - Cuenta de nodos
  - `nodo_inicial` - Primer nodo con es_inicial = true
  - `nodos_finales` - Nodos con es_final = true
- **Métodos clave**:
  - `obtenerEstructuraGrafo()` - Retorna estructura completa del grafo con nodos y conexiones
  - `actualizarPosicionesNodos($posiciones)` - Actualiza posiciones desde array
  - `obtenerNodosPorPosicion($x, $y, $tolerancia)` - Busca nodos cerca de posición
  - `validarEstructuraGrafo()` - Valida que tenga nodo inicial, final y no huérfanos
  - `puedeSerEditadoPor($user)` - Verifica permisos de edición
  - `puedeSerUsadoPor($user)` - Verifica permisos de uso
  - `activar()` - Cambia estado a 'activo'
  - `archivar()` - Cambia estado a 'archivado'
  - `crearCopia($nuevoNombre, $usuarioId)` - Crea copia completa con nodos y respuestas

#### `NodoDialogo` (`app/Models/NodoDialogo.php`)
- **Tabla**: `nodos_dialogo`
- **Relaciones**:
  - `dialogo()` → Dialogo (belongsTo)
  - `rol()` → RolDisponible (belongsTo, nullable)
  - `respuestas()` → RespuestaDialogo (hasMany, nodo_padre_id)
  - `respuestasEntrantes()` → RespuestaDialogo (hasMany, nodo_siguiente_id)
  - `decisiones()` → DecisionSesion (hasMany)
- **Accessors**:
  - `posicion` - Extrae de metadata JSON: `['x' => int, 'y' => int]`
  - `x` - Acceso directo a coordenada X
  - `y` - Acceso directo a coordenada Y
- **Métodos clave**:
  - `actualizarPosicion($x, $y)` - Actualiza posición en metadata JSON
  - `obtenerRespuestasDisponibles()` - Filtra respuestas por condiciones
  - `evaluarCondiciones()` - Evalúa condiciones del nodo
  - `marcarComoInicial()` - Marca como inicial (desmarca otros del diálogo)

#### `RespuestaDialogo` (`app/Models/RespuestaDialogo.php`)
- **Tabla**: `respuestas_dialogo`
- **Relaciones**:
  - `nodoPadre()` → NodoDialogo (belongsTo)
  - `nodoSiguiente()` → NodoDialogo (belongsTo, nullable)
  - `decisiones()` → DecisionSesion (hasMany)
- **Métodos clave**:
  - `aplicarConsecuencias()` - Aplica consecuencias a variables
  - `evaluarCondiciones()` - Evalúa condiciones de la respuesta
  - `obtenerEstadisticas()` - Estadísticas de selección

#### `SesionDialogo` (`app/Models/SesionDialogo.php`)
- **Tabla**: `sesiones_dialogos`
- **Relaciones**:
  - `sesion()` → SesionJuicio (belongsTo)
  - `dialogo()` → Dialogo (belongsTo)
  - `nodoActual()` → NodoDialogo (belongsTo, nullable)

#### `DecisionSesion` (`app/Models/DecisionSesion.php`)
- **Tabla**: `decisiones_sesion`
- **Relaciones**:
  - `sesion()` → SesionJuicio (belongsTo)
  - `usuario()` → User (belongsTo) ⚠️ **NO NULLABLE**
  - `rol()` → RolDisponible (belongsTo)
  - `nodoDialogo()` → NodoDialogo (belongsTo)
  - `respuesta()` → RespuestaDialogo (belongsTo, nullable)
- **Métodos clave**:
  - `calcularPuntuacion()` - Calcula puntuación con modificadores
  - `obtenerEstadisticas()` - Estadísticas de la decisión
  - `obtenerEstadisticasGenerales()` - Estadísticas generales (static)
  - `obtenerEstadisticasPorRol()` - Estadísticas por rol (static)
  - `obtenerEstadisticasPorUsuario()` - Estadísticas por usuario (static)

### 2.2 Modelos del Sistema Panel Dialogo

- `PanelDialogoEscenario` (`app/Models/PanelDialogoEscenario.php`)
- `PanelDialogoDialogo` (`app/Models/PanelDialogoDialogo.php`)
- `PanelDialogoFlujo` (`app/Models/PanelDialogoFlujo.php`)
- `PanelDialogoOpcion` (`app/Models/PanelDialogoOpcion.php`)
- `PanelDialogoConexion` (`app/Models/PanelDialogoConexion.php`)
- `PanelDialogoRol` (`app/Models/PanelDialogoRol.php`)
- `PanelDialogoAsignacion` (`app/Models/PanelDialogoAsignacion.php`)
- `PanelDialogoSesion` (`app/Models/PanelDialogoSesion.php`)
- `PanelDialogoDecision` (`app/Models/PanelDialogoDecision.php`)
- `RolDialogo` (`app/Models/RolDialogo.php`)

**⚠️ PROBLEMA**: Sistema dual causa confusión y duplicación de código.

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
  - `GET /api/dialogos` - Listar diálogos (index)
  - `POST /api/dialogos` - Crear diálogo (store) - Requiere: admin/instructor
  - `GET /api/dialogos/{dialogo}` - Mostrar diálogo (show)
  - `PUT /api/dialogos/{dialogo}` - Actualizar diálogo (update) - Requiere: admin/instructor
  - `DELETE /api/dialogos/{dialogo}` - Eliminar diálogo (destroy) - Requiere: admin/instructor
  - `POST /api/dialogos/{dialogo}/activar` - Activar diálogo (activar) - Requiere: admin/instructor
  - `POST /api/dialogos/{dialogo}/copiar` - Copiar diálogo (copiar)
  - `GET /api/dialogos/{dialogo}/estructura` - Obtener estructura (estructura)
  - `POST /api/dialogos/{dialogo}/posiciones` - Actualizar posiciones (actualizarPosiciones)
  - `GET /api/dialogos/{dialogo}/export` - Exportar a JSON (exportar)
- **Rutas Web**:
  - `/dialogos-legacy` - Vista legacy (indexWeb)
  - `/dialogos-legacy/{dialogo}` - Mostrar diálogo legacy (showWeb)

#### `NodoDialogoController` (`app/Http/Controllers/NodoDialogoController.php`)
- **Rutas API**:
  - `POST /api/dialogos/{dialogo}/nodos` - Crear nodo (store) - Requiere: admin/instructor
  - `PUT /api/nodos/{id}` - Actualizar nodo (update) - Requiere: admin/instructor
  - `DELETE /api/nodos/{id}` - Eliminar nodo (destroy) - Requiere: admin/instructor
  - `POST /api/nodos/{id}/marcar-inicial` - Marcar como inicial (marcarComoInicial) - Requiere: admin/instructor
  - `GET /api/nodos/{id}/respuestas` - Obtener respuestas (obtenerRespuestas)
  - `POST /api/nodos/{id}/respuestas` - Agregar respuesta (agregarRespuesta) - Requiere: admin/instructor

#### `DialogoFlujoController` (`app/Http/Controllers/DialogoFlujoController.php`)
- **Rutas API**:
  - `POST /api/sesiones/{id}/iniciar-dialogo` - Iniciar diálogo en sesión (iniciarDialogo) - Requiere: admin/instructor
  - `GET /api/sesiones/{id}/dialogo-actual` - Estado actual (obtenerEstadoActual)
  - `GET /api/sesiones/{id}/respuestas-disponibles/{usuario}` - Respuestas disponibles (obtenerRespuestasDisponibles)
  - `POST /api/sesiones/{id}/procesar-decision` - Procesar decisión (procesarDecision)
  - `POST /api/sesiones/{id}/avanzar-dialogo` - Avanzar diálogo (avanzarDialogo) - Requiere: admin/instructor
  - `POST /api/sesiones/{id}/pausar-dialogo` - Pausar diálogo (pausarDialogo) - Requiere: admin/instructor
  - `POST /api/sesiones/{id}/finalizar-dialogo` - Finalizar diálogo (finalizarDialogo) - Requiere: admin/instructor
  - `GET /api/sesiones/{id}/historial-decisiones` - Historial (obtenerHistorialDecisiones)

#### `DialogoImportController` (`app/Http/Controllers/DialogoImportController.php`)
- **Rutas API**:
  - `POST /api/dialogos/import` - Importar desde JSON (importar) - Requiere: admin/instructor
  - `GET /api/dialogos/{id}/export` - Exportar a JSON (exportar)

#### `UnityDialogoController` (`app/Http/Controllers/UnityDialogoController.php`)
- **Rutas API Unity** (requieren `unity.auth`):
  - `GET /api/unity/sesion/{id}/dialogo-estado` - Estado del diálogo (obtenerEstadoDialogo)
  - `GET /api/unity/sesion/{id}/respuestas-usuario/{usuario}` - Respuestas del usuario (obtenerRespuestasUsuario)
  - `POST /api/unity/sesion/{id}/enviar-decision` - Enviar decisión (enviarDecision)
  - `POST /api/unity/sesion/{id}/notificar-hablando` - Notificar habla (notificarHablando)
  - `GET /api/unity/sesion/{id}/movimientos-personajes` - Movimientos (obtenerMovimientosPersonajes)

#### `PanelDialogoController` (`app/Http/Controllers/PanelDialogoController.php`)
- **Rutas API** (sistema alternativo):
  - `GET /api/panel-dialogos` - Listar escenarios (index)
  - `POST /api/panel-dialogos` - Crear escenario (store)
  - `GET /api/panel-dialogos/{escenario}` - Mostrar escenario (show)
  - `PUT /api/panel-dialogos/{escenario}` - Actualizar escenario (update)
  - `DELETE /api/panel-dialogos/{escenario}` - Eliminar escenario (destroy)
  - Rutas para roles, flujos, diálogos, opciones, conexiones
- **Rutas Web**:
  - `/panel-dialogos` - Vista principal (indexWeb)
  - `/panel-dialogos/create` - Crear escenario (create)
  - `/panel-dialogos/{id}` - Mostrar escenario (show)
  - `/panel-dialogos/{id}/editor` - Editor (editor)

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

#### `DialogoEjemploSeeder` (`database/seeders/DialogoEjemploSeeder.php`)
- Diálogo de ejemplo básico

#### `DialogoRoboOXXOSeeder` (`database/seeders/DialogoRoboOXXOSeeder.php`)
- Diálogo de robo a OXXO (versión simple)

#### `DialogoRoboOXXOCompletoSeeder` (`database/seeders/DialogoRoboOXXOCompletoSeeder.php`)
- Diálogo de robo a OXXO (versión completa, 1309 líneas)

#### `DialogoJuicioPenalSeeder` (`database/seeders/DialogoJuicioPenalSeeder.php`)
- Diálogo de juicio penal (424 líneas)

#### `PanelDialogoEscenarioSeeder` (`database/seeders/PanelDialogoEscenarioSeeder.php`)
- Seeders para sistema Panel Dialogo

#### `RolesDialogoSeeder` (`database/seeders/RolesDialogoSeeder.php`)
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
- **Relación**: `belongsTo` en `DecisionSesion` (usuario_id) ⚠️ **NO NULLABLE**
- **Impacto**: Necesario mantener referencias, pero v2 debe soportar NULL

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
decisiones_sesion.usuario_id → users.id ⚠️ **NO NULLABLE - PROBLEMA**
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
   - **Solución v2**: Campos `posicion_x` y `posicion_y` directos

2. **Falta soporte para usuarios no registrados**:
   - No hay campo `requiere_usuario_registrado` en respuestas
   - No hay campo `es_opcion_por_defecto`
   - No hay tracking de usuarios no registrados en decisiones
   - `usuario_id` en `decisiones_sesion` es NOT NULL
   - **Solución v2**: Campos específicos y `usuario_id` nullable

3. **Sistema dual**: Existen dos sistemas paralelos
   - Sistema principal (`Dialogo`, `NodoDialogo`, etc.)
   - Sistema Panel Dialogo (`PanelDialogo*`)
   - Confusión y duplicación de código
   - **Solución v2**: Unificar en un solo sistema

4. **Falta historial de nodos**: No hay tracking de nodos visitados en sesiones
   - **Solución v2**: Campo `historial_nodos` (JSON array)

5. **Metadata sin estructura**: Campos JSON sin validación estricta
   - **Solución v2**: Validación y estructura definida

6. **Falta versionado**: No hay control de versiones de diálogos
   - **Solución v2**: Campo `version` en `dialogos_v2`

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

### 8.1 Scripts de Análisis

**Scripts creados**:
- `database/scripts/analizar-datos-dialogos.php` - Análisis completo de datos
- `database/scripts/backup-datos-dialogos.php` - Backup de datos antes de migración

**Para ejecutar**:
```bash
# Análisis
php artisan tinker
require 'database/scripts/analizar-datos-dialogos.php';

# Backup
php artisan tinker
require 'database/scripts/backup-datos-dialogos.php';
```

### 8.2 Consultas SQL de Análisis

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

### 8.3 Datos Críticos a Migrar

1. **Todos los diálogos activos**
2. **Todos los nodos con sus posiciones** (extraer de metadata JSON)
3. **Todas las respuestas y conexiones**
4. **Sesiones de diálogos activas**
5. **Decisiones históricas** (para estadísticas)

---

## 🎯 9. Plan de Acción para Migración

### 9.1 Fase 1: Preparación ✅
1. ✅ Auditoría completa (este documento)
2. ✅ Crear script de backup de datos
3. ✅ Documentar formato de datos actual

### 9.2 Fase 2: Diseño ✅
1. ✅ Diseño de nuevo esquema (ver `database-design-v2.md`)
2. ✅ Crear nuevas migraciones
3. ⏳ Validar diseño con stakeholders

### 9.3 Fase 3: Implementación
1. ⏳ Crear tablas v2
2. ⏳ Crear modelos v2
3. ✅ Scripts de migración de datos
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
- [x] Backup completo de base de datos (script creado)
- [x] Backup de código actual (git)
- [x] Documentar todos los endpoints en uso
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

## 📄 11. Scripts de Migración Creados

### Scripts Disponibles

1. **`database/scripts/analizar-datos-dialogos.php`**
   - Analiza todos los datos del sistema actual
   - Cuenta registros por tabla
   - Identifica datos críticos

2. **`database/scripts/backup-datos-dialogos.php`**
   - Crea backup completo de todas las tablas relacionadas
   - Guarda en `storage/app/backups/dialogos_v1/`

3. **`database/scripts/migrar-datos-dialogos-v2.php`**
   - Migra todos los datos de v1 a v2
   - Extrae posiciones de metadata JSON
   - Valida integridad referencial

4. **`database/scripts/validar-migracion-dialogos.php`**
   - Valida que la migración se haya realizado correctamente
   - Compara conteos entre v1 y v2
   - Verifica integridad referencial

### Comandos Artisan

1. **`php artisan dialogos:migrate-to-v2`**
   - Ejecuta la migración de datos
   - Opciones: `--validate-only`, `--force`

2. **`php artisan dialogos:validate-migration`**
   - Valida la migración realizada

---

**Última actualización**: Enero 2025  
**Estado**: Auditoría completada ✅  
**Próximo paso**: Ejecutar análisis de datos y completar migraciones
