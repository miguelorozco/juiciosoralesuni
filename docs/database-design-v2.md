# 🗄️ Diseño de Base de Datos v2 - Sistema de Diálogos

## 📋 Objetivo
Diseño optimizado del nuevo esquema de base de datos para el sistema de diálogos v2, reemplazando completamente el sistema actual.

---

## 🔄 Cambios Principales vs Sistema Actual

### Mejoras Clave
1. **Posiciones directas**: `posicion_x` y `posicion_y` en lugar de JSON en metadata
2. **Soporte usuarios no registrados**: Campos específicos en respuestas
3. **Mejor tracking**: Tabla de decisiones mejorada
4. **Optimización**: Índices mejorados y estructura más eficiente
5. **Historial**: Campo para historial de nodos visitados
6. **Metadata Unity**: Campo específico para metadatos de Unity

---

## 📊 Esquema de Base de Datos

### Tabla: `dialogos_v2`

```sql
CREATE TABLE `dialogos_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT NULL,
  `creado_por` BIGINT UNSIGNED NOT NULL,
  `plantilla_id` BIGINT UNSIGNED NULL,
  `publico` BOOLEAN NOT NULL DEFAULT FALSE,
  `estado` ENUM('borrador', 'activo', 'archivado') NOT NULL DEFAULT 'borrador',
  `version` VARCHAR(20) NULL DEFAULT '1.0.0',
  `configuracion` JSON NULL,
  `metadata_unity` JSON NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_creado_por` (`creado_por`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_publico` (`publico`),
  INDEX `idx_plantilla` (`plantilla_id`),
  FOREIGN KEY (`creado_por`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas_sesiones`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Campos nuevos:**
- `version`: Versión del diálogo (para control de versiones)
- `metadata_unity`: Metadatos específicos para Unity (configuraciones, estilos, etc.)

---

### Tabla: `nodos_dialogo_v2`

```sql
CREATE TABLE `nodos_dialogo_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dialogo_id` BIGINT UNSIGNED NOT NULL,
  `rol_id` BIGINT UNSIGNED NULL,
  `titulo` VARCHAR(200) NOT NULL,
  `contenido` TEXT NOT NULL,
  `instrucciones` TEXT NULL,
  `tipo` ENUM('inicio', 'desarrollo', 'decision', 'final') NOT NULL DEFAULT 'desarrollo',
  `posicion_x` INTEGER NOT NULL DEFAULT 0,
  `posicion_y` INTEGER NOT NULL DEFAULT 0,
  `es_inicial` BOOLEAN NOT NULL DEFAULT FALSE,
  `es_final` BOOLEAN NOT NULL DEFAULT FALSE,
  `condiciones` JSON NULL,
  `consecuencias` JSON NULL,
  `metadata` JSON NULL,
  `orden` INTEGER NOT NULL DEFAULT 0,
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_dialogo_id` (`dialogo_id`),
  INDEX `idx_rol_id` (`rol_id`),
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_es_inicial` (`es_inicial`),
  INDEX `idx_es_final` (`es_final`),
  INDEX `idx_posicion` (`posicion_x`, `posicion_y`),
  INDEX `idx_dialogo_tipo` (`dialogo_id`, `tipo`),
  INDEX `idx_dialogo_inicial` (`dialogo_id`, `es_inicial`),
  INDEX `idx_dialogo_final` (`dialogo_id`, `es_final`),
  FOREIGN KEY (`dialogo_id`) REFERENCES `dialogos_v2`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rol_id`) REFERENCES `roles_disponibles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Cambios principales:**
- ✅ `posicion_x` y `posicion_y` como campos directos (en lugar de JSON)
- ✅ Índices optimizados para búsquedas por posición
- ✅ Campo `activo` para soft enable/disable

**Formato de condiciones (JSON):**
```json
{
  "variables": [
    {"variable": "puntuacion", "operador": ">=", "valor": 50},
    {"variable": "rol", "operador": "in", "valor": [1, 2, 3]}
  ],
  "requiere_usuario_registrado": false
}
```

**Formato de consecuencias (JSON):**
```json
{
  "variables": [
    {"tipo": "set", "variable": "puntuacion", "valor": 100},
    {"tipo": "increment", "variable": "decisiones_tomadas", "valor": 1}
  ],
  "eventos": ["dialogo_avanzado", "nodo_completado"]
}
```

---

### Tabla: `respuestas_dialogo_v2`

```sql
CREATE TABLE `respuestas_dialogo_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nodo_padre_id` BIGINT UNSIGNED NOT NULL,
  `nodo_siguiente_id` BIGINT UNSIGNED NULL,
  `texto` VARCHAR(500) NOT NULL,
  `descripcion` TEXT NULL,
  `orden` INTEGER NOT NULL DEFAULT 0,
  `puntuacion` INTEGER NOT NULL DEFAULT 0,
  `color` VARCHAR(7) NOT NULL DEFAULT '#007bff',
  `condiciones` JSON NULL,
  `consecuencias` JSON NULL,
  `requiere_usuario_registrado` BOOLEAN NOT NULL DEFAULT FALSE,
  `es_opcion_por_defecto` BOOLEAN NOT NULL DEFAULT FALSE,
  `requiere_rol` JSON NULL,
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_nodo_padre` (`nodo_padre_id`),
  INDEX `idx_nodo_siguiente` (`nodo_siguiente_id`),
  INDEX `idx_activo` (`activo`),
  INDEX `idx_requiere_registrado` (`requiere_usuario_registrado`),
  INDEX `idx_opcion_defecto` (`es_opcion_por_defecto`),
  INDEX `idx_nodo_padre_activo` (`nodo_padre_id`, `activo`),
  FOREIGN KEY (`nodo_padre_id`) REFERENCES `nodos_dialogo_v2`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`nodo_siguiente_id`) REFERENCES `nodos_dialogo_v2`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Campos nuevos:**
- ✅ `requiere_usuario_registrado`: Si la respuesta solo está disponible para usuarios registrados
- ✅ `es_opcion_por_defecto`: Si es la opción automática para usuarios no registrados
- ✅ `requiere_rol`: Array JSON de IDs de roles requeridos

**Formato de requiere_rol (JSON):**
```json
[1, 2, 3]  // Array de IDs de roles que pueden ver esta respuesta
```

**Lógica de filtrado:**
- Si `requiere_usuario_registrado = true` y usuario no está registrado → Ocultar
- Si `es_opcion_por_defecto = true` y usuario no está registrado → Mostrar como única opción
- Si `requiere_rol` tiene valores y usuario no tiene ese rol → Ocultar

---

### Tabla: `sesiones_dialogos_v2`

```sql
CREATE TABLE `sesiones_dialogos_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_id` BIGINT UNSIGNED NOT NULL,
  `dialogo_id` BIGINT UNSIGNED NOT NULL,
  `nodo_actual_id` BIGINT UNSIGNED NULL,
  `estado` ENUM('iniciado', 'en_curso', 'pausado', 'finalizado') NOT NULL DEFAULT 'iniciado',
  `fecha_inicio` TIMESTAMP NULL,
  `fecha_fin` TIMESTAMP NULL,
  `variables` JSON NULL,
  `configuracion` JSON NULL,
  `historial_nodos` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sesion_id` (`sesion_id`),
  INDEX `idx_dialogo_id` (`dialogo_id`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_nodo_actual` (`nodo_actual_id`),
  UNIQUE KEY `unique_sesion_dialogo` (`sesion_id`, `dialogo_id`),
  FOREIGN KEY (`sesion_id`) REFERENCES `sesiones_juicios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`dialogo_id`) REFERENCES `dialogos_v2`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`nodo_actual_id`) REFERENCES `nodos_dialogo_v2`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Campos nuevos:**
- ✅ `historial_nodos`: Array JSON con historial de nodos visitados

**Formato de historial_nodos (JSON):**
```json
[
  {
    "nodo_id": 1,
    "fecha": "2025-01-20 10:00:00",
    "usuario_id": 5,
    "tiempo_en_nodo": 30
  },
  {
    "nodo_id": 2,
    "fecha": "2025-01-20 10:00:30",
    "usuario_id": 5,
    "tiempo_en_nodo": 45
  }
]
```

**Formato de variables (JSON):**
```json
{
  "puntuacion_total": 150,
  "decisiones_tomadas": 5,
  "tiempo_total": 300,
  "variables_personalizadas": {
    "testigo_llamado": true,
    "evidencia_presentada": false
  }
}
```

---

### Tabla: `decisiones_dialogo_v2`

```sql
CREATE TABLE `decisiones_dialogo_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_dialogo_id` BIGINT UNSIGNED NOT NULL,
  `nodo_dialogo_id` BIGINT UNSIGNED NULL,
  `respuesta_id` BIGINT UNSIGNED NULL,
  `usuario_id` BIGINT UNSIGNED NULL,
  `rol_id` BIGINT UNSIGNED NULL,
  `texto_respuesta` TEXT NULL,
  `puntuacion_obtenida` INTEGER NOT NULL DEFAULT 0,
  `tiempo_respuesta` INTEGER NULL,
  `fue_opcion_por_defecto` BOOLEAN NOT NULL DEFAULT FALSE,
  `usuario_registrado` BOOLEAN NOT NULL DEFAULT FALSE,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sesion_dialogo` (`sesion_dialogo_id`),
  INDEX `idx_usuario_id` (`usuario_id`),
  INDEX `idx_nodo_dialogo` (`nodo_dialogo_id`),
  INDEX `idx_respuesta` (`respuesta_id`),
  INDEX `idx_usuario_registrado` (`usuario_registrado`),
  INDEX `idx_fecha` (`created_at`),
  FOREIGN KEY (`sesion_dialogo_id`) REFERENCES `sesiones_dialogos_v2`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`nodo_dialogo_id`) REFERENCES `nodos_dialogo_v2`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`respuesta_id`) REFERENCES `respuestas_dialogo_v2`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`rol_id`) REFERENCES `roles_disponibles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Campos nuevos:**
- ✅ `fue_opcion_por_defecto`: Indica si se usó la opción automática
- ✅ `usuario_registrado`: Indica si el usuario estaba registrado al tomar la decisión
- ✅ `texto_respuesta`: Almacena el texto de la respuesta (por si se elimina la respuesta)
- ✅ `tiempo_respuesta`: Tiempo en segundos que tardó en responder

**Uso:**
- Si `usuario_id` es NULL → Usuario no registrado
- Si `fue_opcion_por_defecto = true` → Se ejecutó automáticamente
- `texto_respuesta` permite mantener historial incluso si se elimina la respuesta

---

## 🔗 Relaciones entre Tablas

```
dialogos_v2
  ├── nodos_dialogo_v2 (1:N)
  │     ├── respuestas_dialogo_v2 (1:N) [nodo_padre_id]
  │     └── respuestas_dialogo_v2 (1:N) [nodo_siguiente_id]
  │
  └── sesiones_dialogos_v2 (1:N)
        └── decisiones_dialogo_v2 (1:N)
              ├── nodos_dialogo_v2 (N:1)
              ├── respuestas_dialogo_v2 (N:1)
              └── users (N:1, nullable)
```

---

## 📝 Notas de Migración

### Datos a Migrar

1. **dialogos → dialogos_v2**
   - Todos los campos se mapean directamente
   - Agregar `version = '1.0.0'` por defecto
   - `metadata_unity = NULL` inicialmente

2. **nodos_dialogo → nodos_dialogo_v2**
   - Extraer `posicion` de `metadata` JSON a `posicion_x` y `posicion_y`
   - Si no existe posición, usar (0, 0)
   - Mantener todos los demás campos

3. **respuestas_dialogo → respuestas_dialogo_v2**
   - Mapear directamente
   - `requiere_usuario_registrado = false` por defecto
   - `es_opcion_por_defecto = false` por defecto
   - `requiere_rol = NULL` por defecto

4. **sesiones_dialogos → sesiones_dialogos_v2**
   - Mapear directamente
   - `historial_nodos = []` inicialmente

5. **decisiones_sesion → decisiones_dialogo_v2**
   - Mapear campos existentes
   - `fue_opcion_por_defecto = false` por defecto
   - `usuario_registrado = (usuario_id IS NOT NULL)`
   - `texto_respuesta` desde relación con respuesta

---

## ✅ Validaciones y Constraints

### Validaciones de Negocio

1. **Dialogo**
   - Debe tener exactamente un nodo inicial
   - Debe tener al menos un nodo final
   - No puede tener nodos huérfanos (excepto el inicial)

2. **Nodo**
   - `posicion_x` y `posicion_y` deben ser >= 0
   - Solo un nodo por diálogo puede tener `es_inicial = true`
   - Nodos finales no deben tener respuestas salientes

3. **Respuesta**
   - `nodo_padre_id` y `nodo_siguiente_id` deben pertenecer al mismo diálogo
   - Solo una respuesta por nodo puede tener `es_opcion_por_defecto = true`
   - Si `requiere_usuario_registrado = true`, no puede ser `es_opcion_por_defecto = true`

4. **Sesión Dialogo**
   - `nodo_actual_id` debe pertenecer al `dialogo_id` asignado
   - Solo puede haber una sesión activa por sesión de juicio

---

## 🚀 Índices Recomendados para Performance

### Índices Críticos
- `dialogos_v2`: `creado_por`, `estado`, `publico`
- `nodos_dialogo_v2`: `dialogo_id`, `tipo`, `es_inicial`, `es_final`, `(posicion_x, posicion_y)`
- `respuestas_dialogo_v2`: `nodo_padre_id`, `activo`, `requiere_usuario_registrado`
- `sesiones_dialogos_v2`: `sesion_id`, `estado`, `nodo_actual_id`
- `decisiones_dialogo_v2`: `sesion_dialogo_id`, `usuario_id`, `created_at`

---

## 📊 Ejemplo de Uso

### Crear un diálogo completo

```php
// 1. Crear diálogo
$dialogo = DialogoV2::create([
    'nombre' => 'Juicio Civil - Contrato',
    'descripcion' => 'Simulación de incumplimiento de contrato',
    'creado_por' => auth()->id(),
    'publico' => true,
    'estado' => 'activo'
]);

// 2. Crear nodo inicial
$nodoInicial = NodoDialogoV2::create([
    'dialogo_id' => $dialogo->id,
    'titulo' => 'Inicio del Juicio',
    'contenido' => 'Bienvenidos a la audiencia...',
    'tipo' => 'inicio',
    'posicion_x' => 0,
    'posicion_y' => 0,
    'es_inicial' => true
]);

// 3. Crear nodo de decisión
$nodoDecision = NodoDialogoV2::create([
    'dialogo_id' => $dialogo->id,
    'titulo' => 'Respuesta de la Defensa',
    'contenido' => '¿Cómo responde la defensa?',
    'tipo' => 'decision',
    'posicion_x' => 200,
    'posicion_y' => 0
]);

// 4. Crear respuestas
$respuesta1 = RespuestaDialogoV2::create([
    'nodo_padre_id' => $nodoDecision->id,
    'nodo_siguiente_id' => $nodoFinal->id,
    'texto' => 'Aceptar la demanda',
    'puntuacion' => 10,
    'requiere_usuario_registrado' => false,
    'es_opcion_por_defecto' => true  // Para usuarios no registrados
]);

$respuesta2 = RespuestaDialogoV2::create([
    'nodo_padre_id' => $nodoDecision->id,
    'nodo_siguiente_id' => $nodoPruebas->id,
    'texto' => 'Rechazar la demanda',
    'puntuacion' => 5,
    'requiere_usuario_registrado' => true  // Solo usuarios registrados
]);
```

---

**Última actualización**: Enero 2025  
**Versión del esquema**: 2.0.0  
**Estado**: Diseño finalizado, pendiente implementación
