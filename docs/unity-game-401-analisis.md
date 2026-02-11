# Análisis: 401 en unity-game (iniciar-dialogo / dialogo-estado)

## Resumen del flujo

Se usa **un solo servidor PHP** en `http://localhost` (sin puerto en la URL).

1. **Página**: `http://localhost/unity-game?token=...&session=1`.
2. **API**: debe ser el **mismo origen**: `http://localhost/api/...`. Unity tenía por defecto `http://localhost:8000/api`; se ha corregido para usar la URL base que envía la página (mismo origen).
3. **Token**: La página envía a Unity `token` y `baseUrl` (p. ej. `http://localhost/api`). Unity configura `UnityBridgeConfig` con ambos.
4. **Error 401**: Si la API se llamaba a otro puerto u origen, el token podía no coincidir con el backend que validaba la sesión. Con mismo origen, las peticiones van al mismo Laravel.

## Posibles causas del 401

### 1. Dos orígenes (puerto 80 vs 8000)

- Página: `http://localhost` (puerto 80).
- API: `http://localhost:8000`.

Eso es **cross-origin**. CORS está configurado (incluye `http://localhost` y `Authorization`), pero si el proceso que sirve la **API en el puerto 8000** no es el mismo que tiene el middleware actualizado, seguirás recibiendo 401.

**Qué comprobar**: El proceso que atiende en **8000** debe ser el que tiene el código con `UnityAuthMiddleware` que acepta el token `unity_entry`. Si usas `php artisan serve`, ese proceso debe haberse reiniciado después de los cambios.

### 2. Proceso en 8000 sin reiniciar

Si el código del middleware se actualizó pero el PHP que escucha en 8000 no se reinició (o es otro binario/otra carpeta), seguirá usando la lógica antigua (solo JWT) y devolverá 401.

**Qué hacer**: Reiniciar el servidor que atiende el puerto 8000 (por ejemplo, parar y volver a ejecutar `php artisan serve`).

### 3. Token no llega en el POST (CORS / preflight)

En teoría CORS ya permite `Authorization` y el origen. Si en tu entorno hay un proxy o una configuración que elimina cabeceras, el POST podría llegar sin `Authorization` y el middleware respondería 401 por "token requerido".

**Qué hacer**: Usar temporalmente **mismo origen**: servir también la página desde 8000 y abrir  
`http://localhost:8000/unity-game?token=...&session=1`.  
Así todas las peticiones son a `localhost:8000` y se descarta un fallo raro de CORS/cabeceras.

## Comprobaciones recomendadas

### A) Mismo origen (recomendado para descartar CORS)

1. Arranca solo Laravel en 8000:
   ```bash
   php artisan serve
   ```
2. Abre en el navegador:
   ```
   http://localhost:8000/unity-game?token=eyJ1c2VyX2lkIjozLCJzZXNzaW9uX2lkIjoxLCJleHBpcmVzX2F0IjoxNzcwNzczMTAzLCJ0eXBlIjoidW5pdHlfZW50cnkifQ==&session=1
   ```
3. Inicia sesión si la ruta `/unity-game` lo requiere y prueba de nuevo "Iniciar diálogo".

Si así deja de dar 401, el problema está en el uso de dos orígenes (80 vs 8000) o en cómo se envían las cabeceras desde 80 a 8000.

### B) Logs de Laravel

En la máquina donde corre el proceso del puerto 8000:

```bash
tail -f storage/logs/laravel.log
```

Al pulsar "Iniciar diálogo" debería aparecer:

- Si el token **no** es aceptado:  
  `Unity auth: JWT y unity_entry fallaron` (con `path` y `token_preview`).
- Si el token **sí** es aceptado como `unity_entry`:  
  `Unity auth: autenticado con token unity_entry` (con `user_id` y `session_id`).

Si nunca ves "autenticado con token unity_entry", el middleware está rechazando el token (formato, caducidad o usuario no encontrado). Si lo ves y aun así hay 401, el 401 vendría de otro middleware o de la ruta, no del `unity.auth`.

Para que aparezcan las líneas de debug, en `.env` debe estar `LOG_LEVEL=debug` (o al menos `info`; por defecto en desarrollo suele ser `debug`).

### C) Ruta de diagnóstico

Se ha añadido una ruta de diagnóstico que usa el mismo middleware. Con el mismo token en cabecera:

```bash
curl -s -X POST "http://localhost:8000/api/unity/sesion/1/iniciar-dialogo" \
  -H "Authorization: Bearer eyJ1c2VyX2lkIjozLCJzZXNzaW9uX2lkIjoxLCJleHBpcmVzX2F0IjoxNzcwNzczMTAzLCJ0eXBlIjoidW5pdHlfZW50cnkifQ==" \
  -H "Content-Type: application/json"
```

- Si responde **200** (o un JSON de éxito/inicio de diálogo): el middleware acepta el token; el fallo estaría en el cliente (Unity/navegador).
- Si responde **401**: el mismo proceso que atiende este `curl` está rechazando el token; revisar logs y reinicio del proceso en 8000.

## Otros mensajes de tus logs

- **CreateRoom failed (Photon)**  
  Es independiente del 401: el cliente intenta crear sala antes de estar listo en el lobby. No afecta a la autenticación con Laravel.

- **AudioContext not allowed to start**  
  Política del navegador: el audio debe iniciarse tras una interacción del usuario. No está relacionado con el 401.

- **Shader / WebGL**  
  Avisos de compatibilidad de GPU, no relacionados con la API.

## Resumen de acciones

1. Reiniciar el proceso PHP que escucha en **8000**.
2. Probar con **mismo origen**: abrir `http://localhost:8000/unity-game?token=...&session=1` y pulsar "Iniciar diálogo".
3. Revisar `storage/logs/laravel.log` al reproducir el 401.
4. Probar el `curl` anterior contra `http://localhost:8000` para confirmar si el token es aceptado en ese servidor.
