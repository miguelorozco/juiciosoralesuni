# Documentación Unity – Juicios Simulator

Documentación del proyecto Unity y su integración con el backend Laravel.

## Contenido

| Documento | Descripción |
|-----------|-------------|
| [unity-api-client.md](unity-api-client.md) | Cliente C# para el API Laravel (puente Unity–Laravel): uso, configuración y endpoints. |
| [sistema-dialogos-laravel-unity.md](sistema-dialogos-laravel-unity.md) | Plan y flujo del sistema de diálogos: Laravel como fuente de verdad, bucle en Unity, modelos y dónde está cada pieza. |

## Estructura del código Unity relacionado con el API

- **`Assets/Scripts/`** – Cliente del API y configuración del puente:
  - `UnityBridgeConfig.cs` – URL base y token (configuración estática).
  - `UnityApiClient.cs` – MonoBehaviour que realiza las peticiones HTTP al API.
  - `UnityApiModels.cs` – DTOs para requests y responses (JSON).
  - `DialogoManager.cs` – Driver del bucle de diálogo: polling de estado, respuestas cuando es mi turno y envío de decisiones (sin UI; enlazar por eventos).

## Flujo de integración

1. **Configuración**: En editor o en WebGL, establecer `UnityBridgeConfig.BaseUrl` y opcionalmente `UnityBridgeConfig.Token` (en WebGL el token suele inyectarse desde la página).
2. **Cliente**: Añadir el componente `UnityApiClient` a un GameObject (por ejemplo uno persistente con `DontDestroyOnLoad`).
3. **Llamadas**: Desde cualquier script, usar `UnityApiClient.Instance` para login, sesiones, diálogos, rooms y LiveKit.

Ver [unity-api-client.md](unity-api-client.md) para detalles de cada endpoint y ejemplos de código.
