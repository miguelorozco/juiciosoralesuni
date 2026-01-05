# 📄 Formatos JSON - Base de Datos v2

Documentación detallada de los formatos JSON utilizados en las tablas v2.

---

## 📋 Tabla de Contenidos

1. [dialogos_v2.configuracion](#1-dialogos_v2configuracion)
2. [dialogos_v2.metadata_unity](#2-dialogos_v2metadata_unity)
3. [nodos_dialogo_v2.condiciones](#3-nodos_dialogo_v2condiciones)
4. [nodos_dialogo_v2.consecuencias](#4-nodos_dialogo_v2consecuencias)
5. [nodos_dialogo_v2.metadata](#5-nodos_dialogo_v2metadata)
6. [respuestas_dialogo_v2.condiciones](#6-respuestas_dialogo_v2condiciones)
7. [respuestas_dialogo_v2.consecuencias](#7-respuestas_dialogo_v2consecuencias)
8. [respuestas_dialogo_v2.requiere_rol](#8-respuestas_dialogo_v2requiere_rol)
9. [sesiones_dialogos_v2.variables](#9-sesiones_dialogos_v2variables)
10. [sesiones_dialogos_v2.configuracion](#10-sesiones_dialogos_v2configuracion)
11. [sesiones_dialogos_v2.historial_nodos](#11-sesiones_dialogos_v2historial_nodos)
12. [decisiones_dialogo_v2.metadata](#12-decisiones_dialogo_v2metadata)

---

## 1. dialogos_v2.configuracion

Configuraciones específicas del diálogo.

```json
{
  "tiempo_limite": 3600,
  "puntuacion_maxima": 100,
  "permite_reintentos": false,
  "mostrar_puntuacion": true,
  "mostrar_tiempo": true,
  "auto_avanzar": false,
  "tiempo_auto_avance": 30,
  "variables_iniciales": {
    "puntuacion": 0,
    "decisiones_tomadas": 0
  },
  "reglas_personalizadas": {
    "penalizacion_tiempo": 1,
    "bonificacion_rapidez": 5
  }
}
```

**Campos:**
- `tiempo_limite` (integer, opcional): Tiempo máximo en segundos
- `puntuacion_maxima` (integer, opcional): Puntuación máxima posible
- `permite_reintentos` (boolean, opcional): Si permite reintentar
- `mostrar_puntuacion` (boolean, opcional): Si mostrar puntuación al usuario
- `mostrar_tiempo` (boolean, opcional): Si mostrar tiempo restante
- `auto_avanzar` (boolean, opcional): Si avanza automáticamente
- `tiempo_auto_avance` (integer, opcional): Segundos antes de auto-avanzar
- `variables_iniciales` (object, opcional): Variables iniciales
- `reglas_personalizadas` (object, opcional): Reglas específicas

---

## 2. dialogos_v2.metadata_unity

Metadatos específicos para Unity.

```json
{
  "prefab_dialogo": "Prefabs/DialogoJuicio",
  "audio_config": {
    "volumen": 0.8,
    "fade_in": 0.5,
    "fade_out": 0.5
  },
  "ui_config": {
    "tema": "juicio_civil",
    "fuente": "Arial",
    "tamaño_fuente": 14,
    "color_fondo": "#FFFFFF",
    "color_texto": "#000000"
  },
  "animaciones": {
    "entrada": "fade_in",
    "salida": "fade_out",
    "transicion": "slide"
  },
  "camara": {
    "posicion_inicial": {"x": 0, "y": 0, "z": -10},
    "angulo": 45
  }
}
```

**Campos:**
- `prefab_dialogo` (string, opcional): Ruta al prefab en Unity
- `audio_config` (object, opcional): Configuración de audio
- `ui_config` (object, opcional): Configuración de UI
- `animaciones` (object, opcional): Configuración de animaciones
- `camara` (object, opcional): Configuración de cámara

---

## 3. nodos_dialogo_v2.condiciones

Condiciones para mostrar el nodo (alineado con Pixel Crushers).

```json
{
  "type": "lua",
  "expression": "Variable[\"puntuacion\"] >= 50 AND Variable[\"rol\"] == \"juez\"",
  "fallback": "block"
}
```

**Formato simplificado (alternativo):**
```json
{
  "conditions": [
    {
      "variable": "puntuacion",
      "operator": ">=",
      "value": 50
    },
    {
      "variable": "rol",
      "operator": "==",
      "value": "juez"
    }
  ],
  "logic": "AND",
  "fallback": "block"
}
```

**Campos:**
- `type` (string, opcional): Tipo de condición ("lua" o "simple")
- `expression` (string, opcional): Expresión Lua (si type = "lua")
- `conditions` (array, opcional): Array de condiciones (si type = "simple")
- `logic` (string, opcional): "AND" o "OR" (solo para simple)
- `fallback` (string, opcional): "block" o "passthrough"

**Operadores soportados (simple):**
- `==`, `!=`, `>`, `<`, `>=`, `<=`
- `in`, `not_in` (para arrays)
- `exists`, `not_exists` (para verificar existencia)

---

## 4. nodos_dialogo_v2.consecuencias

Consecuencias al llegar al nodo (alineado con Pixel Crushers userScript).

```json
{
  "userScript": "Variable[\"puntuacion\"] = Variable[\"puntuacion\"] + 10",
  "variables": {
    "puntuacion": {
      "operator": "+=",
      "value": 10
    },
    "decisiones_tomadas": {
      "operator": "++",
      "value": 1
    }
  },
  "events": [
    {
      "type": "OnNodeEntered",
      "action": "PlaySound",
      "params": "beep.wav"
    }
  ]
}
```

**Campos:**
- `userScript` (string, opcional): Script Lua personalizado
- `variables` (object, opcional): Cambios de variables
- `events` (array, opcional): Eventos a disparar

**Operadores de variables:**
- `=`: Asignar
- `+=`: Sumar
- `-=`: Restar
- `++`: Incrementar
- `--`: Decrementar

---

## 5. nodos_dialogo_v2.metadata

Metadatos adicionales del nodo (alineado con Pixel Crushers).

```json
{
  "sequence": "Camera(Closeup); Wait(2); Audio(Beep)",
  "userScript": "Variable[\"Score\"] = Variable[\"Score\"] + 10",
  "fields": {
    "custom_field_1": "value1",
    "custom_field_2": "value2"
  },
  "portrait": "path/to/portrait.png",
  "audio": "path/to/audio.wav",
  "animation": "talk",
  "position_3d": {
    "x": 0,
    "y": 0,
    "z": 0
  }
}
```

**Campos:**
- `sequence` (string, opcional): Comandos Sequencer (Pixel Crushers)
- `userScript` (string, opcional): Script Lua adicional
- `fields` (object, opcional): Campos dinámicos personalizados
- `portrait` (string, opcional): Ruta al retrato del personaje
- `audio` (string, opcional): Ruta al archivo de audio
- `animation` (string, opcional): Nombre de animación
- `position_3d` (object, opcional): Posición 3D en Unity

---

## 6. respuestas_dialogo_v2.condiciones

Condiciones para mostrar la respuesta.

```json
{
  "type": "simple",
  "conditions": [
    {
      "variable": "usuario_registrado",
      "operator": "==",
      "value": true
    },
    {
      "variable": "rol_id",
      "operator": "in",
      "value": [1, 2, 3]
    }
  ],
  "logic": "AND"
}
```

**Mismo formato que nodos_dialogo_v2.condiciones**

---

## 7. respuestas_dialogo_v2.consecuencias

Consecuencias de seleccionar la respuesta.

```json
{
  "userScript": "Variable[\"puntuacion\"] = Variable[\"puntuacion\"] + 10",
  "variables": {
    "puntuacion": {
      "operator": "+=",
      "value": 10
    }
  },
  "events": [
    {
      "type": "OnResponseSelected",
      "action": "PlaySound",
      "params": "click.wav"
    }
  ]
}
```

**Mismo formato que nodos_dialogo_v2.consecuencias**

---

## 8. respuestas_dialogo_v2.requiere_rol

Array de IDs de roles requeridos.

```json
[1, 2, 3]
```

**Formato:** Array simple de enteros (IDs de roles_disponibles)

---

## 9. sesiones_dialogos_v2.variables

Variables de estado del diálogo en la sesión.

```json
{
  "puntuacion_total": 150,
  "decisiones_tomadas": 5,
  "tiempo_total": 300,
  "nodos_visitados": 10,
  "variables_personalizadas": {
    "testigo_llamado": true,
    "evidencia_presentada": false,
    "contador_interrupciones": 3
  },
  "estado_roles": {
    "1": "activo",
    "2": "esperando",
    "3": "completado"
  }
}
```

**Campos estándar:**
- `puntuacion_total` (integer): Puntuación acumulada
- `decisiones_tomadas` (integer): Número de decisiones
- `tiempo_total` (integer): Tiempo en segundos
- `nodos_visitados` (integer): Número de nodos visitados
- `variables_personalizadas` (object): Variables específicas del diálogo
- `estado_roles` (object): Estado de cada rol en el diálogo

---

## 10. sesiones_dialogos_v2.configuracion

Configuración específica de la sesión.

```json
{
  "modo": "normal",
  "dificultad": "media",
  "permite_pausa": true,
  "notificaciones": {
    "email": true,
    "push": false
  },
  "configuracion_unity": {
    "room_id": "room_123",
    "photon_enabled": true
  }
}
```

**Campos:**
- `modo` (string, opcional): Modo de ejecución
- `dificultad` (string, opcional): Nivel de dificultad
- `permite_pausa` (boolean, opcional): Si permite pausar
- `notificaciones` (object, opcional): Configuración de notificaciones
- `configuracion_unity` (object, opcional): Configuración específica de Unity

---

## 11. sesiones_dialogos_v2.historial_nodos

Historial de nodos visitados.

```json
[
  {
    "nodo_id": 1,
    "fecha": "2025-01-20T10:00:00Z",
    "usuario_id": 5,
    "rol_id": 2,
    "tiempo_en_nodo": 30,
    "respuesta_seleccionada_id": 10,
    "puntuacion_obtenida": 10
  },
  {
    "nodo_id": 2,
    "fecha": "2025-01-20T10:00:30Z",
    "usuario_id": 5,
    "rol_id": 2,
    "tiempo_en_nodo": 45,
    "respuesta_seleccionada_id": 11,
    "puntuacion_obtenida": 5
  }
]
```

**Campos por entrada:**
- `nodo_id` (integer): ID del nodo visitado
- `fecha` (string, ISO 8601): Fecha y hora de visita
- `usuario_id` (integer, nullable): ID del usuario (null si no registrado)
- `rol_id` (integer, nullable): ID del rol
- `tiempo_en_nodo` (integer): Tiempo en segundos
- `respuesta_seleccionada_id` (integer, nullable): ID de la respuesta seleccionada
- `puntuacion_obtenida` (integer): Puntuación obtenida

---

## 12. decisiones_dialogo_v2.metadata

Metadatos adicionales de la decisión.

```json
{
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "device_info": {
    "platform": "Windows",
    "browser": "Chrome"
  },
  "analytics": {
    "tiempo_pensamiento": 5,
    "cambios_seleccion": 2,
    "hover_time": 10
  },
  "evaluacion": {
    "criterios_usados": ["efectividad", "uso_evidencia", "respeto_procedimiento"],
    "puntos_fuertes": ["Buen uso de evidencia", "Argumentación clara"],
    "puntos_debilidad": ["Falta de creatividad"],
    "sugerencias_mejora": ["Investigar más sobre precedentes"]
  },
  "custom_data": {
    "notas": "Decisión importante",
    "tags": ["urgente", "critico"]
  }
}
```

**Campos:**
- `ip_address` (string, opcional): IP del usuario
- `user_agent` (string, opcional): User agent del navegador
- `device_info` (object, opcional): Información del dispositivo
- `analytics` (object, opcional): Datos analíticos
- `evaluacion` (object, opcional): Datos adicionales de evaluación
- `custom_data` (object, opcional): Datos personalizados

---

## 🔍 Validación de Formatos

### Reglas de Validación

1. **Todos los JSON deben ser válidos**
2. **Campos opcionales pueden ser null o no existir**
3. **Arrays deben contener elementos del tipo correcto**
4. **Objetos deben tener las claves esperadas**
5. **Valores deben ser del tipo correcto**

### Ejemplo de Validación PHP

```php
function validarCondiciones($json) {
    $data = json_decode($json, true);
    
    if ($data === null) {
        return false; // JSON inválido
    }
    
    if (isset($data['type'])) {
        if ($data['type'] === 'lua' && !isset($data['expression'])) {
            return false;
        }
        if ($data['type'] === 'simple' && !isset($data['conditions'])) {
            return false;
        }
    }
    
    return true;
}
```

---

**Última actualización**: Enero 2025  
**Versión**: 1.0.0
