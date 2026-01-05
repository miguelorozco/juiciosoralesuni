# 🎤 Sistema de Grabación de Audio MP3 - Diálogos

**Objetivo**: Documentar el sistema de grabación y almacenamiento de archivos MP3 de los diálogos para retroalimentación posterior.

---

## 📊 Campos de Audio en Base de Datos

### Tabla: `decisiones_dialogo_v2`

Campos para audio de cada decisión individual:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `audio_mp3` | varchar(500) | Ruta al archivo MP3 de la decisión |
| `audio_duracion` | integer | Duración del audio en segundos |
| `audio_grabado_en` | timestamp | Fecha y hora de grabación |
| `audio_procesado` | boolean | Si el audio fue procesado/validado |

### Tabla: `sesiones_dialogos_v2`

Campos para audio completo de la sesión:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `audio_mp3_completo` | varchar(500) | Ruta al archivo MP3 completo de la sesión |
| `audio_duracion_completo` | integer | Duración total en segundos |
| `audio_grabado_en` | timestamp | Fecha y hora de inicio de grabación |
| `audio_procesado` | boolean | Si el audio fue procesado/validado |
| `audio_habilitado` | boolean | Si la grabación está habilitada |

---

## 📁 Almacenamiento de Archivos

### Estructura de Directorios

```
storage/app/
├── public/
│   └── audios/
│       ├── decisiones/
│       │   ├── {año}/
│       │   │   ├── {mes}/
│       │   │   │   ├── decision_{id}_{timestamp}.mp3
│       │   │   │   └── ...
│       │   │   └── ...
│       │   └── ...
│       └── sesiones/
│           ├── {año}/
│           │   ├── {mes}/
│           │   │   ├── sesion_{id}_{timestamp}.mp3
│           │   │   └── ...
│           │   └── ...
│           └── ...
└── ...
```

### Ejemplo de Rutas

**Decisión individual:**
```
storage/app/public/audios/decisiones/2025/01/decision_123_20250120_143022.mp3
```

**Sesión completa:**
```
storage/app/public/audios/sesiones/2025/01/sesion_45_20250120_140000.mp3
```

### URLs Públicas

Las rutas públicas serán accesibles vía:
```
https://dominio.com/storage/audios/decisiones/2025/01/decision_123_20250120_143022.mp3
https://dominio.com/storage/audios/sesiones/2025/01/sesion_45_20250120_140000.mp3
```

---

## 🔄 Flujo de Grabación

### 1. Habilitar Grabación en Sesión

```php
// El profesor habilita la grabación al iniciar la sesión
$sesionDialogo = SesionDialogoV2::find($id);
$sesionDialogo->update([
    'audio_habilitado' => true,
    'audio_grabado_en' => now(),
]);
```

### 2. Grabar Decisión Individual

```php
// Cuando un estudiante toma una decisión y se graba el audio
$audioFile = $request->file('audio'); // Archivo MP3 desde Unity/Cliente

// Validar y guardar
$path = $audioFile->storeAs(
    "public/audios/decisiones/" . now()->format('Y/m'),
    "decision_{$decision->id}_" . now()->format('Ymd_His') . ".mp3"
);

// Obtener duración del audio
$duracion = obtenerDuracionAudio($audioFile); // Función helper

// Actualizar decisión
$decision->update([
    'audio_mp3' => str_replace('public/', '', $path),
    'audio_duracion' => $duracion,
    'audio_grabado_en' => now(),
    'audio_procesado' => false, // Se procesará después
]);
```

### 3. Grabar Sesión Completa

```php
// Al finalizar la sesión, se guarda el audio completo
$audioCompleto = $request->file('audio_completo');

$path = $audioCompleto->storeAs(
    "public/audios/sesiones/" . now()->format('Y/m'),
    "sesion_{$sesionDialogo->id}_" . now()->format('Ymd_His') . ".mp3"
);

$duracion = obtenerDuracionAudio($audioCompleto);

$sesionDialogo->update([
    'audio_mp3_completo' => str_replace('public/', '', $path),
    'audio_duracion_completo' => $duracion,
    'audio_procesado' => false,
]);
```

### 4. Procesar Audio (Validación)

```php
// Proceso de validación y procesamiento del audio
function procesarAudio($decision) {
    $rutaCompleta = storage_path('app/public/' . $decision->audio_mp3);
    
    // Validar que el archivo existe
    if (!file_exists($rutaCompleta)) {
        return false;
    }
    
    // Validar formato MP3
    $mimeType = mime_content_type($rutaCompleta);
    if ($mimeType !== 'audio/mpeg') {
        return false;
    }
    
    // Validar tamaño (máximo 50MB por decisión, 500MB por sesión completa)
    $tamañoMaximo = 50 * 1024 * 1024; // 50MB
    if (filesize($rutaCompleta) > $tamañoMaximo) {
        return false;
    }
    
    // Obtener duración real del audio
    $duracion = obtenerDuracionAudioReal($rutaCompleta);
    
    // Actualizar como procesado
    $decision->update([
        'audio_duracion' => $duracion,
        'audio_procesado' => true,
    ]);
    
    return true;
}
```

---

## 🎯 Casos de Uso

### 1. Subir Audio de Decisión (API)

```php
// POST /api/decisiones/{id}/audio
public function subirAudio(Request $request, DecisionDialogoV2 $decision)
{
    $request->validate([
        'audio' => 'required|file|mimes:mp3|max:51200', // 50MB máximo
    ]);
    
    $audioFile = $request->file('audio');
    
    // Guardar archivo
    $path = $audioFile->storeAs(
        "public/audios/decisiones/" . now()->format('Y/m'),
        "decision_{$decision->id}_" . now()->format('Ymd_His') . ".mp3"
    );
    
    // Obtener duración
    $duracion = $this->obtenerDuracionAudio($audioFile);
    
    // Actualizar decisión
    $decision->update([
        'audio_mp3' => str_replace('public/', '', $path),
        'audio_duracion' => $duracion,
        'audio_grabado_en' => now(),
        'audio_procesado' => false,
    ]);
    
    // Procesar en background
    ProcesarAudioJob::dispatch($decision);
    
    return response()->json([
        'success' => true,
        'message' => 'Audio subido correctamente',
        'audio_url' => Storage::url($decision->audio_mp3),
        'duracion' => $duracion,
    ]);
}
```

### 2. Obtener Audio de Decisión

```php
// GET /api/decisiones/{id}/audio
public function obtenerAudio(DecisionDialogoV2 $decision)
{
    if (!$decision->audio_mp3) {
        return response()->json([
            'success' => false,
            'message' => 'No hay audio disponible para esta decisión'
        ], 404);
    }
    
    $rutaCompleta = storage_path('app/public/' . $decision->audio_mp3);
    
    if (!file_exists($rutaCompleta)) {
        return response()->json([
            'success' => false,
            'message' => 'Archivo de audio no encontrado'
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'audio_url' => Storage::url($decision->audio_mp3),
        'duracion' => $decision->audio_duracion,
        'grabado_en' => $decision->audio_grabado_en,
        'procesado' => $decision->audio_procesado,
    ]);
}
```

### 3. Listar Decisiones con Audio

```php
// GET /api/sesiones/{id}/decisiones-con-audio
public function decisionesConAudio(SesionDialogoV2 $sesionDialogo)
{
    $decisiones = DecisionDialogoV2::where('sesion_dialogo_id', $sesionDialogo->id)
        ->whereNotNull('audio_mp3')
        ->with(['usuario', 'rol', 'nodoDialogo'])
        ->orderBy('audio_grabado_en', 'desc')
        ->get()
        ->map(function ($decision) {
            return [
                'id' => $decision->id,
                'usuario' => $decision->usuario->name,
                'rol' => $decision->rol->nombre,
                'nodo' => $decision->nodoDialogo->titulo,
                'audio_url' => Storage::url($decision->audio_mp3),
                'duracion' => $decision->audio_duracion,
                'grabado_en' => $decision->audio_grabado_en,
                'procesado' => $decision->audio_procesado,
            ];
        });
    
    return response()->json([
        'success' => true,
        'decisiones' => $decisiones,
        'total' => $decisiones->count(),
    ]);
}
```

### 4. Obtener Audio Completo de Sesión

```php
// GET /api/sesiones/{id}/audio-completo
public function audioCompleto(SesionDialogoV2 $sesionDialogo)
{
    if (!$sesionDialogo->audio_mp3_completo) {
        return response()->json([
            'success' => false,
            'message' => 'No hay audio completo disponible para esta sesión'
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'audio_url' => Storage::url($sesionDialogo->audio_mp3_completo),
        'duracion' => $sesionDialogo->audio_duracion_completo,
        'grabado_en' => $sesionDialogo->audio_grabado_en,
        'procesado' => $sesionDialogo->audio_procesado,
    ]);
}
```

---

## 🔧 Helpers y Utilidades

### Obtener Duración del Audio

```php
use getID3;

function obtenerDuracionAudio($archivo) {
    $getID3 = new \getID3;
    $info = $getID3->analyze($archivo->getRealPath());
    
    return isset($info['playtime_seconds']) 
        ? (int) $info['playtime_seconds'] 
        : null;
}
```

### Validar Formato MP3

```php
function validarMP3($archivo) {
    $mimeType = $archivo->getMimeType();
    $extension = $archivo->getClientOriginalExtension();
    
    return $mimeType === 'audio/mpeg' && $extension === 'mp3';
}
```

### Comprimir Audio (Opcional)

```php
use FFMpeg;

function comprimirAudio($rutaOrigen, $rutaDestino) {
    $ffmpeg = FFMpeg\FFMpeg::create();
    $audio = $ffmpeg->open($rutaOrigen);
    
    $format = new FFMpeg\Format\Audio\Mp3();
    $format->setAudioCodec('libmp3lame')
           ->setAudioKiloBitrate(128); // 128 kbps
    
    $audio->save($format, $rutaDestino);
}
```

---

## 📋 Configuración de Storage

### config/filesystems.php

```php
'disks' => [
    // ...
    'audios' => [
        'driver' => 'local',
        'root' => storage_path('app/public/audios'),
        'url' => env('APP_URL') . '/storage/audios',
        'visibility' => 'public',
    ],
],
```

### .env

```env
# Configuración de audio
AUDIO_MAX_SIZE_DECISION=51200  # 50MB en KB
AUDIO_MAX_SIZE_SESION=512000   # 500MB en KB
AUDIO_BITRATE=128              # kbps
AUDIO_SAMPLE_RATE=44100        # Hz
```

---

## 🔒 Permisos y Seguridad

### Validación de Permisos

```php
// Solo el profesor puede habilitar/deshabilitar grabación
if (!auth()->user()->esProfesor()) {
    abort(403, 'Solo los profesores pueden habilitar grabaciones');
}

// Solo el estudiante dueño puede subir su audio
if ($decision->usuario_id !== auth()->id()) {
    abort(403, 'Solo puedes subir audio de tus propias decisiones');
}

// Solo el profesor puede acceder a audios para evaluación
if (!auth()->user()->esProfesor() && $decision->estado_evaluacion === 'pendiente') {
    abort(403, 'El audio no está disponible hasta que sea evaluado');
}
```

### Política de Retención

```php
// Eliminar audios antiguos (opcional)
function limpiarAudiosAntiguos($dias = 365) {
    $fechaLimite = now()->subDays($dias);
    
    // Decisiones
    $decisiones = DecisionDialogoV2::where('audio_grabado_en', '<', $fechaLimite)
        ->whereNotNull('audio_mp3')
        ->get();
    
    foreach ($decisiones as $decision) {
        Storage::delete('public/' . $decision->audio_mp3);
        $decision->update(['audio_mp3' => null]);
    }
    
    // Sesiones
    $sesiones = SesionDialogoV2::where('audio_grabado_en', '<', $fechaLimite)
        ->whereNotNull('audio_mp3_completo')
        ->get();
    
    foreach ($sesiones as $sesion) {
        Storage::delete('public/' . $sesion->audio_mp3_completo);
        $sesion->update(['audio_mp3_completo' => null]);
    }
}
```

---

## 📊 Consultas Útiles

### Decisiones con Audio Pendiente de Procesar

```sql
SELECT 
    d.id,
    u.name as estudiante,
    r.nombre as rol,
    d.audio_mp3,
    d.audio_duracion,
    d.audio_grabado_en
FROM decisiones_dialogo_v2 d
JOIN users u ON d.usuario_id = u.id
JOIN roles_disponibles r ON d.rol_id = r.id
WHERE d.audio_procesado = false
  AND d.audio_mp3 IS NOT NULL
ORDER BY d.audio_grabado_en DESC;
```

### Estadísticas de Audio

```sql
SELECT 
    COUNT(*) as total_decisiones,
    SUM(CASE WHEN audio_mp3 IS NOT NULL THEN 1 ELSE 0 END) as con_audio,
    SUM(CASE WHEN audio_procesado = true THEN 1 ELSE 0 END) as procesados,
    AVG(audio_duracion) as duracion_promedio,
    SUM(audio_duracion) as duracion_total_segundos
FROM decisiones_dialogo_v2
WHERE sesion_dialogo_id = ?;
```

---

## 🎓 Uso para Retroalimentación

### Vista del Profesor

El profesor puede:
1. Escuchar cada decisión individualmente
2. Escuchar la sesión completa
3. Comparar decisiones de diferentes estudiantes
4. Agregar notas específicas basadas en el audio
5. Compartir audio con el estudiante para retroalimentación

### Vista del Estudiante

El estudiante puede:
1. Escuchar sus propias decisiones después de ser evaluadas
2. Escuchar la sesión completa si el profesor lo permite
3. Usar el audio para autoevaluación
4. Compartir con otros para estudio

---

## 🔄 Integración con Unity

### Enviar Audio desde Unity

```csharp
// Unity C# - Enviar audio de decisión
public IEnumerator SubirAudioDecision(int decisionId, string audioPath)
{
    byte[] audioBytes = File.ReadAllBytes(audioPath);
    string base64Audio = Convert.ToBase64String(audioBytes);
    
    WWWForm form = new WWWForm();
    form.AddField("decision_id", decisionId);
    form.AddBinaryData("audio", audioBytes, "decision.mp3", "audio/mpeg");
    
    using (UnityWebRequest www = UnityWebRequest.Post(
        $"{apiUrl}/api/decisiones/{decisionId}/audio", 
        form))
    {
        www.SetRequestHeader("Authorization", $"Bearer {token}");
        yield return www.SendWebRequest();
        
        if (www.result == UnityWebRequest.Result.Success)
        {
            Debug.Log("Audio subido correctamente");
        }
    }
}
```

---

## ✅ Validaciones

### Al Subir Audio

```php
$request->validate([
    'audio' => [
        'required',
        'file',
        'mimes:mp3',
        'max:' . config('app.audio_max_size_decision', 51200), // 50MB
    ],
]);
```

### Validar Duración

```php
// Duración máxima: 10 minutos por decisión
if ($duracion > 600) {
    throw new \Exception('El audio no puede durar más de 10 minutos');
}
```

---

**Última actualización**: Enero 2025  
**Versión**: 1.0.0  
**Estado**: Diseño completado, pendiente implementación
