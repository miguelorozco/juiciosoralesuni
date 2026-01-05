# Guía de Migración: Sistema de Diálogos v2

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Proceso de Migración Paso a Paso](#proceso-de-migración-paso-a-paso)
3. [Checklist de Migración](#checklist-de-migración)
4. [Troubleshooting Común](#troubleshooting-común)
5. [Guía de Rollback](#guía-de-rollback)
6. [Cambios en la API](#cambios-en-la-api)
7. [Cambios en Formatos de Datos](#cambios-en-formatos-de-datos)
8. [Guía de Migración para Unity](#guía-de-migración-para-unity)

---

## Resumen Ejecutivo

### ¿Qué cambió?

El sistema de diálogos ha sido completamente reescrito para usar un nuevo esquema de base de datos (`_v2`) que:

- **Soporta posiciones directas** (`posicion_x`, `posicion_y`) en lugar de JSON
- **Maneja usuarios no registrados** en el flujo de diálogos
- **Incluye evaluación del profesor** para decisiones de estudiantes
- **Soporta grabación de audio MP3** para decisiones y sesiones completas
- **Está alineado con Pixel Crushers Dialogue System** para futura integración
- **Mejora el rendimiento** con índices optimizados y estructura más eficiente

### Estado Actual

- ✅ Nuevas tablas `_v2` creadas y migradas
- ✅ Modelos Eloquent v2 implementados
- ✅ Controladores refactorizados (marcados como `@deprecated` pero funcionales)
- ✅ Tests completos pasando
- ✅ Tablas antiguas eliminadas

### Compatibilidad

**Las rutas API se mantienen iguales** para compatibilidad temporal. Los controladores usan modelos v2 internamente pero mantienen la misma interfaz.

---

## Proceso de Migración Paso a Paso

### Pre-requisitos

1. **Backup completo de la base de datos**
   ```bash
   mysqldump -u miguel -p juiciosorales > backup_pre_migracion_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verificar que todas las migraciones estén aplicadas**
   ```bash
   php artisan migrate:status
   ```

3. **Verificar que los tests pasen**
   ```bash
   php artisan test --filter DialogosV2
   ```

### Paso 1: Verificar Estado Actual

```bash
# Verificar que las tablas v2 existen
php artisan tinker
>>> Schema::hasTable('dialogos_v2')
>>> Schema::hasTable('nodos_dialogo_v2')
>>> Schema::hasTable('respuestas_dialogo_v2')
>>> Schema::hasTable('sesiones_dialogos_v2')
>>> Schema::hasTable('decisiones_dialogo_v2')
```

### Paso 2: Migrar Datos (si aplica)

Si tienes datos en las tablas antiguas que necesitas migrar:

```bash
# Ejecutar script de migración de datos
php artisan migrate:dialogos-to-v2

# Validar migración
php artisan validate:dialogos-migration
```

### Paso 3: Verificar Integridad

```bash
# Ejecutar tests de migración
php artisan test --filter DialogosV2MigrationTest

# Ejecutar tests de funcionalidad
php artisan test --filter DialogosV2FuncionalidadTest
```

### Paso 4: Actualizar Aplicación (si es necesario)

Si tu aplicación usa directamente los modelos antiguos, actualiza las referencias:

**Antes:**
```php
use App\Models\Dialogo;
use App\Models\NodoDialogo;
```

**Después:**
```php
use App\Models\DialogoV2 as Dialogo;
use App\Models\NodoDialogoV2 as NodoDialogo;
```

### Paso 5: Eliminar Tablas Antiguas (Ya completado)

Las tablas antiguas ya fueron eliminadas en la migración `2026_01_05_120005_drop_old_dialogo_tables.php`.

### Paso 6: Verificar Funcionalidad

1. **Crear un diálogo de prueba**
   ```bash
   POST /api/dialogos
   {
     "nombre": "Test Diálogo v2",
     "descripcion": "Prueba del nuevo sistema",
     "estado": "borrador"
   }
   ```

2. **Crear un nodo**
   ```bash
   POST /api/dialogos/{id}/nodos
   {
     "titulo": "Nodo inicial",
     "contenido": "Contenido del nodo",
     "tipo": "inicio",
     "posicion_x": 0,
     "posicion_y": 0
   }
   ```

3. **Verificar que todo funciona correctamente**

---

## Checklist de Migración

### Pre-Migración

- [ ] Backup completo de la base de datos realizado
- [ ] Backup guardado en ubicación segura
- [ ] Documentación del sistema actual revisada
- [ ] Equipo notificado sobre la migración
- [ ] Ventana de mantenimiento programada (si aplica)

### Durante la Migración

- [ ] Migraciones v2 ejecutadas correctamente
- [ ] Script de migración de datos ejecutado (si aplica)
- [ ] Validación de datos migrados exitosa
- [ ] Tests de migración pasando
- [ ] Tests de funcionalidad pasando
- [ ] Verificación manual de datos críticos

### Post-Migración

- [ ] API endpoints funcionando correctamente
- [ ] Creación de diálogos funcionando
- [ ] Creación de nodos funcionando
- [ ] Flujo de diálogos funcionando
- [ ] Importación/exportación funcionando
- [ ] Integración con Unity funcionando (si aplica)
- [ ] Documentación actualizada
- [ ] Equipo notificado sobre completación

### Verificación de Funcionalidades Específicas

- [ ] Posiciones de nodos se guardan correctamente (`posicion_x`, `posicion_y`)
- [ ] Usuarios no registrados pueden participar en diálogos
- [ ] Evaluación del profesor funciona
- [ ] Grabación de audio funciona (si está habilitada)
- [ ] Historial de nodos se registra correctamente
- [ ] Variables de diálogo funcionan
- [ ] Condiciones y consecuencias funcionan

---

## Troubleshooting Común

### Error: "Table 'dialogos_v2' doesn't exist"

**Causa:** Las migraciones no se han ejecutado.

**Solución:**
```bash
php artisan migrate
```

### Error: "Column 'posicion_x' cannot be null"

**Causa:** Al crear un nodo, no se están proporcionando las posiciones.

**Solución:**
```php
// Asegúrate de incluir posicion_x y posicion_y
NodoDialogoV2::create([
    'dialogo_id' => $dialogo->id,
    'titulo' => 'Nodo',
    'contenido' => 'Contenido',
    'posicion_x' => 0,  // Requerido
    'posicion_y' => 0,  // Requerido
    'tipo' => 'inicio',
    'orden' => 1,
    'activo' => true,
]);
```

### Error: "Foreign key constraint fails"

**Causa:** Intentando crear relaciones con IDs que no existen.

**Solución:**
```php
// Verificar que el diálogo existe antes de crear nodos
$dialogo = DialogoV2::findOrFail($dialogoId);
```

### Error: "Method 'puedeSerEditadoPor' not found"

**Causa:** Usando el modelo antiguo en lugar del v2.

**Solución:**
```php
// Cambiar import
use App\Models\DialogoV2 as Dialogo;
```

### Error: "No se puede iniciar el diálogo - nodo inicial no encontrado"

**Causa:** El diálogo no tiene un nodo marcado como inicial.

**Solución:**
```php
// Marcar un nodo como inicial
$nodo->marcarComoInicial();

// O crear el diálogo con un nodo inicial
$nodoInicial = NodoDialogoV2::create([
    'dialogo_id' => $dialogo->id,
    'es_inicial' => true,
    // ... otros campos
]);
```

### Error: "Tests fallan con 'could not find driver'"

**Causa:** Extensión PDO MySQL no instalada.

**Solución:**
```bash
sudo apt install php8.3-pdo-mysql
php -m | grep pdo_mysql
```

### Error: "Field 'contenido' doesn't have a default value"

**Causa:** Creando nodos sin el campo `contenido` requerido.

**Solución:**
```php
// Siempre incluir contenido
NodoDialogoV2::create([
    'contenido' => 'Contenido del nodo', // Requerido
    // ... otros campos
]);
```

### Error: "Enum value 'agrupacion' not valid"

**Causa:** La migración que agrega el tipo 'agrupacion' no se ha ejecutado.

**Solución:**
```bash
php artisan migrate
# Verificar que la migración 2026_01_05_120007_update_tipo_enum_nodos_v2.php se ejecutó
```

---

## Guía de Rollback

### ⚠️ ADVERTENCIA

**El rollback completo NO es posible** porque las tablas antiguas ya fueron eliminadas. Sin embargo, puedes:

1. **Restaurar desde backup** (si tienes uno)
2. **Recrear las tablas antiguas** (no recomendado)
3. **Migrar datos de v2 a un formato compatible** (si es necesario)

### Opción 1: Restaurar desde Backup

```bash
# Restaurar base de datos completa
mysql -u miguel -p juiciosorales < backup_pre_migracion_YYYYMMDD_HHMMSS.sql

# Revertir migraciones v2
php artisan migrate:rollback --step=10
```

### Opción 2: Recrear Tablas Antiguas (NO RECOMENDADO)

Si absolutamente necesitas las tablas antiguas:

1. Crear migraciones para recrear las tablas antiguas
2. Migrar datos de v2 a formato antiguo
3. Actualizar código para usar modelos antiguos

**Nota:** Esto requiere trabajo significativo y no está soportado oficialmente.

### Opción 3: Mantener Ambos Sistemas Temporalmente

Si necesitas compatibilidad temporal:

1. **NO eliminar tablas v2**
2. Mantener ambos sistemas funcionando en paralelo
3. Migrar gradualmente funcionalidades

### Script de Rollback Parcial

Si solo necesitas revertir cambios específicos:

```bash
# Revertir migración de audio
php artisan migrate:rollback --path=database/migrations/2026_01_05_120009_add_audio_fields_to_decisiones_v2.php

# Revertir migración de evaluación
php artisan migrate:rollback --path=database/migrations/2026_01_05_120008_add_evaluacion_fields_to_decisiones_v2.php
```

---

## Cambios en la API

### Endpoints que NO Cambiaron

Todos los endpoints mantienen la misma URL y estructura de respuesta para compatibilidad:

#### Diálogos
- `GET /api/dialogos` - Listar diálogos
- `POST /api/dialogos` - Crear diálogo
- `GET /api/dialogos/{id}` - Obtener diálogo
- `PUT /api/dialogos/{id}` - Actualizar diálogo
- `DELETE /api/dialogos/{id}` - Eliminar diálogo
- `POST /api/dialogos/{id}/activar` - Activar diálogo
- `POST /api/dialogos/{id}/copiar` - Copiar diálogo
- `GET /api/dialogos/{id}/estructura` - Obtener estructura
- `POST /api/dialogos/{id}/posiciones` - Actualizar posiciones
- `GET /api/dialogos/{id}/export` - Exportar diálogo
- `POST /api/dialogos/import` - Importar diálogo

#### Nodos
- `POST /api/dialogos/{id}/nodos` - Crear nodo
- `PUT /api/nodos/{id}` - Actualizar nodo
- `DELETE /api/nodos/{id}` - Eliminar nodo
- `POST /api/nodos/{id}/marcar-inicial` - Marcar como inicial
- `GET /api/nodos/{id}/respuestas` - Obtener respuestas
- `POST /api/nodos/{id}/respuestas` - Agregar respuesta

#### Flujo de Diálogos
- `POST /api/sesiones/{id}/iniciar-dialogo` - Iniciar diálogo
- `GET /api/sesiones/{id}/dialogo-actual` - Estado actual
- `GET /api/sesiones/{id}/respuestas-disponibles/{usuario}` - Respuestas disponibles
- `POST /api/sesiones/{id}/procesar-decision` - Procesar decisión
- `POST /api/sesiones/{id}/avanzar-dialogo` - Avanzar diálogo
- `POST /api/sesiones/{id}/pausar-dialogo` - Pausar diálogo
- `POST /api/sesiones/{id}/finalizar-dialogo` - Finalizar diálogo
- `GET /api/sesiones/{id}/historial-decisiones` - Historial

### Cambios en Validaciones

#### Crear Nodo

**Antes:**
```json
{
  "rol_id": 1,
  "contenido": "Texto",
  "tipo": "inicio"
}
```

**Ahora (campos adicionales opcionales):**
```json
{
  "rol_id": 1,  // Ahora opcional
  "titulo": "Título del nodo",  // Requerido
  "contenido": "Texto",
  "tipo": "inicio",
  "posicion_x": 0,  // Nuevo - opcional (default: 0)
  "posicion_y": 0,  // Nuevo - opcional (default: 0)
  "conversant_id": 1,  // Nuevo - opcional (Pixel Crushers)
  "menu_text": "Texto del menú"  // Nuevo - opcional (Pixel Crushers)
}
```

#### Crear Respuesta

**Antes:**
```json
{
  "texto": "Sí",
  "nodo_siguiente_id": 2
}
```

**Ahora (campos adicionales):**
```json
{
  "texto": "Sí",
  "nodo_siguiente_id": 2,
  "requiere_usuario_registrado": false,  // Nuevo
  "es_opcion_por_defecto": true,  // Nuevo
  "requiere_rol": [1, 2]  // Nuevo - array de IDs de roles
}
```

### Nuevos Campos en Respuestas

#### Procesar Decisión

**Nuevo campo opcional:**
```json
{
  "usuario_id": 1,
  "respuesta_id": 1,
  "decision_texto": "Texto adicional",
  "tiempo_respuesta": 45,
  "audio_mp3": "path/to/audio.mp3"  // Nuevo - opcional
}
```

### Cambios en Respuestas de API

#### Obtener Diálogo

**Nuevo campo en respuesta:**
```json
{
  "id": 1,
  "nombre": "Diálogo",
  "version": "1.0.0",  // Nuevo
  "metadata_unity": {},  // Nuevo
  "nodos": [
    {
      "id": 1,
      "posicion_x": 100,  // Cambió de metadata.posicion.x
      "posicion_y": 200,  // Cambió de metadata.posicion.y
      "conversant_id": 1,  // Nuevo
      "menu_text": "Menú"  // Nuevo
    }
  ]
}
```

#### Estado del Diálogo

**Nuevo campo:**
```json
{
  "sesion_dialogo": {...},
  "nodo_actual": {...},
  "progreso": {
    "nodos_visitados": 5,
    "total_nodos": 10,
    "porcentaje": 50.0,
    "tiempo_transcurrido": 300
  },
  "historial_nodos": [...]  // Nuevo - array de nodos visitados
}
```

---

## Cambios en Formatos de Datos

### Estructura de Nodos

#### Antes (v1)
```json
{
  "id": 1,
  "dialogo_id": 1,
  "rol_id": 1,
  "contenido": "Texto",
  "metadata": {
    "posicion": {
      "x": 100,
      "y": 200
    }
  }
}
```

#### Ahora (v2)
```json
{
  "id": 1,
  "dialogo_id": 1,
  "rol_id": 1,
  "titulo": "Título",  // Nuevo - requerido
  "contenido": "Texto",
  "posicion_x": 100,  // Directo, no en metadata
  "posicion_y": 200,  // Directo, no en metadata
  "conversant_id": 1,  // Nuevo - Pixel Crushers
  "menu_text": "Menú",  // Nuevo - Pixel Crushers
  "tipo": "inicio",
  "es_inicial": true,
  "es_final": false,
  "orden": 1,
  "activo": true
}
```

### Estructura de Respuestas

#### Antes (v1)
```json
{
  "id": 1,
  "nodo_padre_id": 1,
  "nodo_siguiente_id": 2,
  "texto": "Sí",
  "puntuacion": 10
}
```

#### Ahora (v2)
```json
{
  "id": 1,
  "nodo_padre_id": 1,
  "nodo_siguiente_id": 2,
  "texto": "Sí",
  "descripcion": "Descripción",  // Nuevo
  "puntuacion": 10,
  "color": "#28a745",  // Nuevo
  "requiere_usuario_registrado": false,  // Nuevo
  "es_opcion_por_defecto": true,  // Nuevo
  "requiere_rol": [1, 2],  // Nuevo - array
  "condiciones": {},  // Mejorado
  "consecuencias": {},  // Mejorado
  "orden": 1,
  "activo": true
}
```

### Estructura de Sesiones de Diálogo

#### Antes (v1)
```json
{
  "id": 1,
  "sesion_id": 1,
  "dialogo_id": 1,
  "nodo_actual_id": 1,
  "estado": "en_curso",
  "variables": {},
  "configuracion": {}
}
```

#### Ahora (v2)
```json
{
  "id": 1,
  "sesion_id": 1,
  "dialogo_id": 1,
  "nodo_actual_id": 1,
  "estado": "en_curso",
  "variables": {},
  "configuracion": {
    "progreso": {  // Nuevo
      "nodos_visitados": 5,
      "total_nodos": 10,
      "porcentaje": 50.0,
      "tiempo_transcurrido": 300
    }
  },
  "historial_nodos": [  // Nuevo
    {
      "nodo_id": 1,
      "fecha": "2026-01-05T12:00:00Z",
      "usuario_id": 1,
      "rol_id": 1,
      "tiempo_en_nodo": 10,
      "respuesta_seleccionada_id": 1
    }
  ],
  "audio_mp3_completo": "path/to/audio.mp3",  // Nuevo
  "audio_duracion_completo": 300,  // Nuevo
  "audio_habilitado": true  // Nuevo
}
```

### Estructura de Decisiones

#### Antes (v1)
```json
{
  "id": 1,
  "sesion_dialogo_id": 1,
  "nodo_dialogo_id": 1,
  "respuesta_id": 1,
  "usuario_id": 1,
  "rol_id": 1,
  "texto_respuesta": "Sí",
  "puntuacion_obtenida": 10
}
```

#### Ahora (v2)
```json
{
  "id": 1,
  "sesion_dialogo_id": 1,
  "nodo_dialogo_id": 1,
  "respuesta_id": 1,
  "usuario_id": 1,
  "rol_id": 1,
  "texto_respuesta": "Sí",
  "puntuacion_obtenida": 10,
  "tiempo_respuesta": 45,
  "fue_opcion_por_defecto": false,
  "usuario_registrado": true,
  "metadata": {},
  "calificacion_profesor": 8,  // Nuevo - evaluación
  "notas_profesor": "Buen trabajo",  // Nuevo
  "estado_evaluacion": "evaluado",  // Nuevo
  "justificacion_estudiante": "...",  // Nuevo
  "retroalimentacion": "...",  // Nuevo
  "audio_mp3": "path/to/audio.mp3",  // Nuevo
  "audio_duracion": 5,  // Nuevo
  "audio_procesado": true  // Nuevo
}
```

---

## Guía de Migración para Unity

### Cambios Principales

1. **Posiciones de nodos**: Ahora vienen directamente en `posicion_x` y `posicion_y`
2. **Nuevos campos**: `conversant_id`, `menu_text` para alineación con Pixel Crushers
3. **Usuarios no registrados**: Soporte completo en el flujo
4. **Audio**: Nuevos campos para grabación MP3

### Actualizar Código Unity

#### Antes (v1)

```csharp
// Obtener posición del nodo
var posicion = nodo.metadata["posicion"];
float x = posicion["x"];
float y = posicion["y"];
```

#### Ahora (v2)

```csharp
// Obtener posición directamente
float x = nodo.posicion_x;
float y = nodo.posicion_y;
```

### Nuevos Campos Disponibles

```csharp
public class NodoDialogo {
    public int id;
    public string titulo;  // Nuevo - requerido
    public string contenido;
    public int posicion_x;  // Cambió de metadata
    public int posicion_y;  // Cambió de metadata
    public int? conversant_id;  // Nuevo - Pixel Crushers
    public string menu_text;  // Nuevo - Pixel Crushers
    public string tipo;
    public bool es_inicial;
    public bool es_final;
}
```

### Manejo de Usuarios No Registrados

```csharp
// Al obtener respuestas disponibles
var respuestas = await api.ObtenerRespuestasDisponibles(
    sesionId, 
    usuarioId, 
    usuarioRegistrado: false  // Nuevo parámetro
);

// Filtrar respuestas disponibles
var respuestasDisponibles = respuestas
    .Where(r => !r.requiere_usuario_registrado || r.es_opcion_por_defecto)
    .ToList();
```

### Grabación de Audio

```csharp
// Al procesar decisión con audio
var decision = await api.ProcesarDecision(new {
    usuario_id = usuarioId,
    respuesta_id = respuestaId,
    tiempo_respuesta = tiempo,
    audio_mp3 = rutaAudio  // Nuevo campo opcional
});
```

### Importación/Exportación

El formato JSON de importación/exportación ha cambiado ligeramente:

#### Antes
```json
{
  "nodos": [{
    "posicion": {"x": 100, "y": 200}
  }]
}
```

#### Ahora
```json
{
  "nodos": [{
    "posicion_x": 100,
    "posicion_y": 200
  }]
}
```

### Checklist para Unity

- [ ] Actualizar modelos C# para incluir nuevos campos
- [ ] Cambiar acceso a posiciones de nodos
- [ ] Implementar soporte para usuarios no registrados
- [ ] Agregar campos de Pixel Crushers (`conversant_id`, `menu_text`)
- [ ] Actualizar importación/exportación de diálogos
- [ ] Probar flujo completo de diálogos
- [ ] Verificar que audio funciona (si está habilitado)
- [ ] Actualizar documentación de Unity

### Ejemplo de Clase Actualizada

```csharp
[System.Serializable]
public class NodoDialogoV2 {
    public int id;
    public int dialogo_id;
    public int? rol_id;
    public int? conversant_id;  // Nuevo
    public string titulo;  // Nuevo - requerido
    public string contenido;
    public string menu_text;  // Nuevo
    public string instrucciones;
    public string tipo;
    public int posicion_x;  // Cambió
    public int posicion_y;  // Cambió
    public bool es_inicial;
    public bool es_final;
    public int orden;
    public bool activo;
    public List<RespuestaDialogoV2> respuestas;
}

[System.Serializable]
public class RespuestaDialogoV2 {
    public int id;
    public int nodo_padre_id;
    public int? nodo_siguiente_id;
    public string texto;
    public string descripcion;  // Nuevo
    public int puntuacion;
    public string color;  // Nuevo
    public bool requiere_usuario_registrado;  // Nuevo
    public bool es_opcion_por_defecto;  // Nuevo
    public int[] requiere_rol;  // Nuevo
    public int orden;
    public bool activo;
}
```

---

## Recursos Adicionales

- [Diseño de Base de Datos v2](./database-design-v2.md)
- [Formatos JSON v2](./database-design-v2-formatos-json.md)
- [Alineación Pixel Crushers](./pixel-crushers-alignment.md)
- [Evaluación del Profesor](./evaluacion-decisiones-profesor.md)
- [Grabación de Audio](./audio-grabacion-dialogos.md)
- [Guía de Integración Unity](./unity-integration-guide.md)

---

## Soporte

Si encuentras problemas durante la migración:

1. Revisa la sección de [Troubleshooting](#troubleshooting-común)
2. Verifica los logs de Laravel: `storage/logs/laravel.log`
3. Ejecuta los tests: `php artisan test --filter DialogosV2`
4. Consulta la documentación adicional en `/docs`

---

**Última actualización:** 2026-01-05  
**Versión del sistema:** v2.0.0
