# 📝 Guía de Evaluación de Decisiones por Profesor

**Objetivo**: Documentar cómo el profesor/instructor puede evaluar las decisiones tomadas por los estudiantes durante las sesiones de diálogos.

---

## 📊 Campos de Evaluación

La tabla `decisiones_dialogo_v2` incluye los siguientes campos para evaluación:

### Campos Principales

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `calificacion_profesor` | integer (0-100) | Calificación manual del profesor |
| `notas_profesor` | text | Comentarios y notas del profesor |
| `evaluado_por` | FK a users | ID del profesor que evaluó |
| `fecha_evaluacion` | timestamp | Fecha y hora de evaluación |
| `estado_evaluacion` | enum | Estado: pendiente, evaluado, revisado |
| `justificacion_estudiante` | text | Justificación del estudiante |
| `retroalimentacion` | text | Retroalimentación general |

---

## 🔄 Flujo de Evaluación

### 1. Registro de Decisión (Automático)

Cuando un estudiante toma una decisión, se crea automáticamente un registro:

```php
$decision = DecisionDialogoV2::create([
    'sesion_dialogo_id' => $sesionDialogo->id,
    'nodo_dialogo_id' => $nodoActual->id,
    'respuesta_id' => $respuesta->id,
    'usuario_id' => $usuario->id,
    'rol_id' => $rol->id,
    'texto_respuesta' => $respuesta->texto,
    'puntuacion_obtenida' => $respuesta->puntuacion,
    'tiempo_respuesta' => $tiempoRespuesta,
    'estado_evaluacion' => 'pendiente', // Por defecto
    'justificacion_estudiante' => $request->justificacion ?? null,
]);
```

### 2. Evaluación por el Profesor

El profesor puede evaluar la decisión:

```php
$decision->update([
    'calificacion_profesor' => 85,
    'notas_profesor' => 'Excelente uso de evidencia. Argumentación sólida.',
    'evaluado_por' => auth()->id(), // ID del profesor
    'fecha_evaluacion' => now(),
    'estado_evaluacion' => 'evaluado',
    'retroalimentacion' => 'Considera investigar más sobre precedentes similares.',
]);
```

### 3. Revisión por el Estudiante

El estudiante puede revisar la evaluación:

```php
$decision->update([
    'estado_evaluacion' => 'revisado',
]);
```

---

## 📋 Estados de Evaluación

### `pendiente`
- Estado inicial de todas las decisiones
- Aún no ha sido evaluada por el profesor
- El estudiante puede agregar justificación

### `evaluado`
- El profesor ya evaluó la decisión
- El estudiante puede ver la evaluación
- El estudiante puede marcar como revisado

### `revisado`
- El estudiante ya revisó la evaluación
- Indica que el estudiante tomó nota de la retroalimentación

---

## 🎯 Casos de Uso

### 1. Listar Decisiones Pendientes de Evaluación

```php
// Obtener todas las decisiones pendientes de una sesión
$decisionesPendientes = DecisionDialogoV2::where('sesion_dialogo_id', $sesionDialogo->id)
    ->where('estado_evaluacion', 'pendiente')
    ->with(['usuario', 'rol', 'nodoDialogo', 'respuesta'])
    ->orderBy('created_at', 'desc')
    ->get();
```

### 2. Evaluar Múltiples Decisiones

```php
// Evaluar todas las decisiones de un estudiante en una sesión
$decisiones = DecisionDialogoV2::where('sesion_dialogo_id', $sesionDialogo->id)
    ->where('usuario_id', $estudianteId)
    ->where('estado_evaluacion', 'pendiente')
    ->get();

foreach ($decisiones as $decision) {
    $decision->update([
        'calificacion_profesor' => calcularCalificacion($decision),
        'notas_profesor' => generarNotas($decision),
        'evaluado_por' => auth()->id(),
        'fecha_evaluacion' => now(),
        'estado_evaluacion' => 'evaluado',
    ]);
}
```

### 3. Obtener Estadísticas de Evaluación

```php
// Estadísticas por estudiante
$estadisticas = DecisionDialogoV2::where('sesion_dialogo_id', $sesionDialogo->id)
    ->where('usuario_id', $estudianteId)
    ->selectRaw('
        COUNT(*) as total_decisiones,
        SUM(CASE WHEN estado_evaluacion = "pendiente" THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado_evaluacion = "evaluado" THEN 1 ELSE 0 END) as evaluadas,
        SUM(CASE WHEN estado_evaluacion = "revisado" THEN 1 ELSE 0 END) as revisadas,
        AVG(calificacion_profesor) as promedio_calificacion,
        AVG(puntuacion_obtenida) as promedio_puntuacion
    ')
    ->first();
```

### 4. Reporte de Evaluación para Profesor

```php
// Reporte completo de evaluación de una sesión
$reporte = DecisionDialogoV2::where('sesion_dialogo_id', $sesionDialogo->id)
    ->with(['usuario', 'rol', 'nodoDialogo', 'respuesta', 'evaluador'])
    ->get()
    ->groupBy('usuario_id')
    ->map(function ($decisiones) {
        return [
            'usuario' => $decisiones->first()->usuario,
            'total_decisiones' => $decisiones->count(),
            'pendientes' => $decisiones->where('estado_evaluacion', 'pendiente')->count(),
            'evaluadas' => $decisiones->where('estado_evaluacion', 'evaluado')->count(),
            'revisadas' => $decisiones->where('estado_evaluacion', 'revisado')->count(),
            'promedio_calificacion' => $decisiones->avg('calificacion_profesor'),
            'promedio_puntuacion' => $decisiones->avg('puntuacion_obtenida'),
            'decisiones' => $decisiones,
        ];
    });
```

---

## 📊 Vistas y Consultas Útiles

### Decisiones por Estudiante

```sql
SELECT 
    u.name as estudiante,
    r.nombre as rol,
    COUNT(d.id) as total_decisiones,
    SUM(CASE WHEN d.estado_evaluacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN d.estado_evaluacion = 'evaluado' THEN 1 ELSE 0 END) as evaluadas,
    AVG(d.calificacion_profesor) as promedio_calificacion
FROM decisiones_dialogo_v2 d
JOIN users u ON d.usuario_id = u.id
JOIN roles_disponibles r ON d.rol_id = r.id
WHERE d.sesion_dialogo_id = ?
GROUP BY u.id, u.name, r.id, r.nombre
ORDER BY promedio_calificacion DESC;
```

### Decisiones Pendientes de Evaluación

```sql
SELECT 
    d.id,
    u.name as estudiante,
    r.nombre as rol,
    nd.titulo as nodo,
    rd.texto as respuesta,
    d.puntuacion_obtenida,
    d.tiempo_respuesta,
    d.created_at as fecha_decision
FROM decisiones_dialogo_v2 d
JOIN users u ON d.usuario_id = u.id
JOIN roles_disponibles r ON d.rol_id = r.id
JOIN nodos_dialogo_v2 nd ON d.nodo_dialogo_id = nd.id
JOIN respuestas_dialogo_v2 rd ON d.respuesta_id = rd.id
WHERE d.sesion_dialogo_id = ?
  AND d.estado_evaluacion = 'pendiente'
ORDER BY d.created_at DESC;
```

---

## 🎓 Criterios de Evaluación Sugeridos

### 1. Efectividad de la Decisión (0-40 puntos)
- ¿La decisión fue apropiada para el contexto?
- ¿Se consideraron todas las opciones?
- ¿La decisión avanzó el diálogo de manera positiva?

### 2. Uso de Evidencia (0-30 puntos)
- ¿Se utilizó evidencia relevante?
- ¿La evidencia fue presentada correctamente?
- ¿Se cuestionó evidencia débil cuando fue necesario?

### 3. Respeto al Procedimiento (0-20 puntos)
- ¿Se siguió el procedimiento legal?
- ¿Se respetaron los tiempos?
- ¿Se mantuvo la formalidad apropiada?

### 4. Creatividad y Persuasión (0-10 puntos)
- ¿La argumentación fue creativa?
- ¿Fue persuasiva?
- ¿Se utilizaron técnicas de argumentación efectivas?

---

## 📝 Ejemplo de Evaluación Completa

```php
// Evaluar una decisión con criterios detallados
$decision->update([
    'calificacion_profesor' => 85,
    'notas_profesor' => json_encode([
        'efectividad' => 35,
        'uso_evidencia' => 28,
        'respeto_procedimiento' => 18,
        'creatividad' => 4,
        'comentarios' => [
            'Excelente uso de precedentes',
            'Buena argumentación, pero podría ser más persuasiva',
            'Respetó todos los procedimientos'
        ]
    ]),
    'evaluado_por' => auth()->id(),
    'fecha_evaluacion' => now(),
    'estado_evaluacion' => 'evaluado',
    'retroalimentacion' => 'Tu decisión fue sólida. Considera investigar más sobre técnicas de persuasión y cómo presentar evidencia de manera más impactante.',
]);
```

---

## 🔔 Notificaciones

### Cuando el Profesor Evalúa

```php
// Notificar al estudiante cuando su decisión es evaluada
Notification::send($estudiante, new DecisionEvaluada($decision));
```

### Cuando el Estudiante Revisa

```php
// Notificar al profesor cuando el estudiante revisa la evaluación
Notification::send($profesor, new DecisionRevisada($decision));
```

---

## 📈 Métricas y Reportes

### Dashboard del Profesor

- Total de decisiones pendientes
- Decisiones evaluadas hoy
- Promedio de calificaciones
- Estudiantes con más decisiones pendientes
- Tiempo promedio de evaluación

### Dashboard del Estudiante

- Mis decisiones pendientes de evaluación
- Decisiones evaluadas recientemente
- Mi promedio de calificaciones
- Retroalimentación recibida
- Progreso en la sesión

---

## ✅ Validaciones

### Al Evaluar

```php
$request->validate([
    'calificacion_profesor' => 'required|integer|min:0|max:100',
    'notas_profesor' => 'nullable|string|max:2000',
    'retroalimentacion' => 'nullable|string|max:2000',
]);
```

### Al Agregar Justificación

```php
$request->validate([
    'justificacion_estudiante' => 'nullable|string|max:2000',
]);
```

---

**Última actualización**: Enero 2025  
**Versión**: 1.0.0
