# Plan de trabajo: GUI del sistema de diálogos (~2 h)

**Objetivo:** Tener en Unity un panel de diálogo que muestre el estado del diálogo, indique el turno, muestre las respuestas cuando sea tu turno y permita enviar la decisión (y opcionalmente iniciar el diálogo). Todo enlazado a `DialogoManager` y al API existente.

**Última actualización:** al completar cada ítem se marca el check en este documento.

**Nota:** Si el componente `DialogoUIController` no se añadió al Canvas por MCP, añádelo manualmente en el Inspector (Add Component → DialogoUIController). Las referencias se resuelven por nombre bajo el Canvas.

---

## Checklist general

- [x] Canvas + PanelDialogo con textos (título, contenido, rol, progreso, tiempo, mensaje turno).
- [x] Contenedor de respuestas + prefab BotonRespuesta.
- [x] `DialogoUIController.cs`: suscripción a los 4 eventos del `DialogoManager`.
- [x] Actualizar UI en OnEstadoActualizado (y ocultar/mensaje si no activo).
- [x] OnRespuestasDisponibles: MarcarInicioRespuesta, crear botones, EnviarDecision al clic.
- [x] OnError y OnDialogoFinalizado: mostrar mensaje y estado final.
- [x] Botón Iniciar diálogo → IniciarDialogo(callback).
- [x] sesionJuicioId y usuarioId configurados; RefrescarEstado() una vez al inicio.
- [ ] Prueba con Laravel: estado, turno, respuestas, enviar decisión, finalizado.

---

## Bloque 1 — Preparación y estructura (~25 min)

| Tiempo   | Tarea                      | Detalle |
|----------|----------------------------|--------|
| 0:00–0:10 | **Escena y Canvas**         | Abrir la escena donde se juega. Crear un Canvas (Screen Space – Overlay) y dentro un **Panel** "PanelDialogo" (imagen de fondo opcional). Asegurar que en la escena hay un GameObject con `UnityApiClient` y otro con `DialogoManager` (crear uno "GameManager" o "DialogoManager" si no existe). |
| 0:10–0:20 | **Textos del estado**      | Dentro del panel, crear textos (TextMeshPro): **TituloNodo**, **ContenidoNodo**, **RolHablando**, **Progreso**, **Tiempo**, **MensajeTurno** ("Tu turno" / "Esperando a…"). Colocarlos y nombrarlos bien para enlazarlos en el script. |
| 0:20–0:25 | **Contenedor y prefab**    | Crear un **ContenedorRespuestas** (Vertical Layout Group o similar). Crear un **prefab** "BotonRespuesta": Button + hijo TextMeshPro para el texto; guardar en `Assets/Prefabs/` o en una carpeta de UI. |

**Entregable:** escena con panel, textos y prefab de botón listos para enlazar.

---

## Bloque 2 — Script de UI y enlace con DialogoManager (~45 min)

| Tiempo    | Tarea                              | Detalle |
|-----------|------------------------------------|--------|
| 0:25–0:35 | **Crear script DialogoUIController** | En `Assets/Scripts/` crear `DialogoUIController.cs`. Campos públicos: referencias a los Text (título, contenido, rol, progreso, tiempo, mensaje turno), al `Transform` contenedor de respuestas, al prefab del botón, y opcional al `DialogoManager`. |
| 0:35–0:50 | **Suscripciones y estado**         | En `Start()`: suscribirse a `OnEstadoActualizado`, `OnRespuestasDisponibles`, `OnError`, `OnDialogoFinalizado`; en `OnDestroy()` desuscribirse. En **OnEstadoActualizado**: si no diálogo activo, ocultar panel o mostrar "No hay diálogo activo"; si está activo, asignar título, contenido, rol, progreso, tiempo; según `EsMiTurno` actualizar **MensajeTurno**. |
| 0:50–1:05 | **Respuestas y EnviarDecision**    | En **OnRespuestasDisponibles**: limpiar contenedor; `MarcarInicioRespuesta()`; por cada respuesta instanciar botón, en `onClick` → `EnviarDecision(respuestaId, "", 0)`. |
| 1:05–1:10 | **Errores y finalizado**           | En **OnError**: mostrar mensaje. En **OnDialogoFinalizado**: mostrar "Diálogo finalizado" y vaciar respuestas. |

**Entregable:** panel que reacciona al estado, muestra respuestas y envía la decisión.

---

## Bloque 3 — Iniciar diálogo y configuración (~25 min)

| Tiempo    | Tarea                         | Detalle |
|-----------|-------------------------------|--------|
| 1:10–1:20 | **Botón "Iniciar diálogo"**   | Añadir Button en el panel; en el script, `onClick` → `DialogoManager.IniciarDialogo(callback)` y mostrar ok/error. |
| 1:20–1:30 | **Sesión y usuario**          | Asegurar que `DialogoManager.sesionJuicioId` y `usuarioId` se rellenan (Inspector o flujo de entrada a sesión). |
| 1:30–1:35 | **Refresco inicial**          | En `DialogoUIController.Start()`, tras suscribirse, llamar `RefrescarEstado()`. |

**Entregable:** se puede iniciar el diálogo desde la GUI; sesión/usuario configurados.

---

## Bloque 4 — Pruebas y ajustes (~25 min)

| Tiempo    | Tarea                | Detalle |
|-----------|----------------------|--------|
| 1:35–1:50 | **Prueba con backend** | Laravel + sesión con diálogo; en Unity probar estado, turno, respuestas, EnviarDecision, finalizado. |
| 1:50–2:00 | **Ajustes UX**       | Fuentes, colores, feedback "Enviando…"; revisar consola. |

---

## Orden si te pasas de tiempo

1. **Prioridad máxima:** Bloque 2 (script + estado + respuestas + EnviarDecision).
2. Luego: Bloque 1 (estructura de panel y prefab).
3. Después: Bloque 3 (Iniciar diálogo e IDs).
4. Por último: Bloque 4 (pruebas y pulido).
