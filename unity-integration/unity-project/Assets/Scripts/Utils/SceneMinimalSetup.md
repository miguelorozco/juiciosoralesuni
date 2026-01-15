# Guía: Crear Escena Mínima para Debug

## Objetivo
Crear una escena limpia con solo los componentes esenciales para identificar el problema de recursión.

## Pasos

### Opción A: Configuración Automática (Recomendado)

1. **Abrir Unity Editor**
2. **Ir al menú**: `Juicios Simulator > Setup Minimal Test Scene`
3. **Hacer clic en**: "Configurar Escena Mínima"
4. **Listo**: La escena se configurará automáticamente con los GameObjects necesarios

### Opción B: Configuración Manual

#### 1. Crear Nueva Escena
- File > New Scene > Basic (Built-in)
- Guardar como `MinimalTestScene`

#### 2. Agregar Solo Estos GameObjects (en orden):

##### GameObject: "LaravelAPI"
- Componente: `LaravelAPI`
- Configurar:
  - `baseURL`: http://localhost:8000/api
  - `enableDebugLogging`: true

##### GameObject: "InitializationDiagnostics"
- Componente: `InitializationDiagnostics`
- Configurar:
  - `enableDiagnostics`: true
  - `logToHTML`: true

### 3. NO Agregar (por ahora):
**IMPORTANTE**: Asegúrate de que NO haya otros scripts de inicialización en la escena.
- ❌ GameInitializer
- ❌ LaravelUnityEntryManager
- ❌ GestionRedJugador
- ❌ UnityLaravelIntegration
- ❌ DialogueManager
- ❌ DynamicSceneSetup
- ❌ Cualquier otro script de inicialización

### 4. Probar
- Build WebGL
- Verificar que NO hay error de recursión
- Revisar logs en ventana HTML

### 5. Agregar Scripts Uno por Uno
Agregar cada script de uno en uno y probar después de cada uno:

1. **GameInitializer** (si no hay error, continuar)
2. **LaravelUnityEntryManager** (si no hay error, continuar)
3. **DialogueManager** (si no hay error, continuar)
4. **GestionRedJugador** (si no hay error, continuar)
5. **UnityLaravelIntegration** (si no hay error, continuar)
6. **DynamicSceneSetup** (si no hay error, continuar)

### 6. Cuando Encuentres el Script Problemático
- Revisar ese script específico
- Verificar si tiene múltiples instancias
- Revisar sus suscripciones a eventos
- Revisar sus llamadas a FindObjectOfType

## Scripts de Diagnóstico

El script `InitializationDiagnostics` mostrará:
- Cuántas instancias de cada script hay
- Cuántas veces se llama cada método
- Alertas si hay recursión detectada

