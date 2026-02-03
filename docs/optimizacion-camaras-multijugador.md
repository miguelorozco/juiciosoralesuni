# Optimización de Cámaras - Sistema Multi-Jugador

## 🎯 Problema Resuelto

**Antes**: Unity intentaba renderizar desde las 20 cámaras simultáneamente, causando:
- Lag extremo
- Múltiples renders por frame
- Sobrecarga de CPU/GPU
- Conflictos de AudioListener

**Ahora**: Solo se activa UNA cámara a la vez (la del jugador local).

---

## 📝 Cambios Implementados

### 1. **ControlCamaraJugador.cs Mejorado**
**Ubicación**: `Assets/Scripts/ControlCamaraJugador.cs`

**Mejoras**:
- ✅ Desactivación INMEDIATA de todas las cámaras en `Awake()`
- ✅ Activación selectiva solo para `photonView.IsMine`
- ✅ Desactivación automática de `Main Camera`
- ✅ Gestión correcta de `AudioListener` (solo uno activo)
- ✅ Logs detallados para debugging

**Código Clave**:
```csharp
void Awake()
{
    // CRÍTICO: Desactivar INMEDIATAMENTE todas las cámaras
    if (camara != null) camara.enabled = false;
    if (audioListener != null) audioListener.enabled = false;
}

void Start()
{
    if (photonView.IsMine)
    {
        // Solo activar la cámara del jugador local
        camara.enabled = true;
        audioListener.enabled = true;
        DisableMainCamera();
        DisableOtherAudioListeners(audioListener);
    }
}
```

---

### 2. **RoleManager.cs (Nuevo)**
**Ubicación**: `Assets/Scripts/RoleManager.cs`

**Funcionalidades**:
- ✅ Gestiona asignación de roles de forma centralizada
- ✅ Controla qué cámara debe estar activa
- ✅ Sincroniza roles entre jugadores vía Photon RPC
- ✅ Desactiva TODAS las cámaras antes de activar una
- ✅ Lista de 20 roles disponibles

**Métodos Principales**:
```csharp
public void AssignRole(string roleName)
{
    // Asigna rol al jugador y activa su cámara
}

private void ActivateCameraForPlayer(GameObject player)
{
    // Desactiva todas las cámaras primero
    DeactivateAllCameras();
    // Activa solo la cámara del player especificado
}

public List<string> GetAvailableRoles()
{
    // Devuelve roles no asignados
}
```

---

## 🚀 Cómo Usar

### **Opción A: Asignación Manual (Desarrollo)**

1. Abrir la escena en Unity
2. Presionar **Play**
3. Abrir la consola y ver los roles disponibles
4. Usar el RoleManager para asignar rol:

```csharp
RoleManager.Instance.AssignRole("Juez");
```

### **Opción B: Sistema Existente (RoleSelectionUI)**

El proyecto ya tiene `RoleSelectionUI.cs` que:
- Muestra botones para cada rol
- Gestiona la selección del jugador
- Se integra con `GestionRedJugador`

Este sistema ya funciona y **se integra automáticamente** con el nuevo `ControlCamaraJugador`.

---

## 🔧 Configuración en Unity

### **Paso 1: Agregar RoleManager a la Escena**

1. Crear GameObject vacío: `GameObject > Create Empty`
2. Renombrar a **"RoleManager"**
3. Agregar componentes:
   - `RoleManager` (script)
   - `PhotonView` (para sincronización)
4. En PhotonView:
   - Marcar **"Reliable Delta Compressed"**
   - Observable: **"Unreliable On Change"**

### **Paso 2: Verificar Players**

Cada uno de los 20 Players debe tener:
- ✅ `PhotonView` (ya lo tienen)
- ✅ `ControlCamaraJugador` (ya lo tienen - ACTUALIZADO)
- ✅ `RedesJugador` (ya lo tienen)
- ✅ Una cámara hijo (ya la tienen)

**NO hacer cambios en los Players**, el script actualizado maneja todo automáticamente.

### **Paso 3: Configurar Main Camera**

La `Main Camera` de la escena:
- Puede quedarse en la escena (se desactivará automáticamente)
- O puede eliminarse directamente

**Recomendado**: Dejarla pero desactivarla manualmente antes de hacer Build.

---

## 📊 Flujo del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                    JUGADOR ENTRA A LA SALA                      │
└───────────────────────────────┬─────────────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  RoleSelectionUI      │
                    │  Muestra roles        │
                    │  disponibles          │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Jugador selecciona   │
                    │  un rol (ej: "Juez")  │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────────────────┐
                    │  RoleManager.AssignRole("Juez")   │
                    │  - Transfiere ownership del       │
                    │    GameObject Player_Juez         │
                    │  - Desactiva TODAS las cámaras    │
                    │  - Activa solo cámara del Juez    │
                    └───────────┬───────────────────────┘
                                │
                                ▼
                    ┌───────────────────────────────────┐
                    │  ControlCamaraJugador (Player_Juez)│
                    │  - Detecta photonView.IsMine      │
                    │  - Mantiene cámara activa         │
                    │  - Deshabilita AudioListeners     │
                    │    duplicados                     │
                    └───────────────────────────────────┘
```

---

## 🐛 Debugging

### **Verificar que solo hay 1 cámara activa:**

```csharp
// En la consola de Unity:
Camera[] allCameras = FindObjectsOfType<Camera>();
int activeCameras = 0;
foreach (Camera cam in allCameras)
{
    if (cam.enabled)
    {
        Debug.Log($"Cámara ACTIVA: {cam.gameObject.name}");
        activeCameras++;
    }
}
Debug.Log($"Total cámaras activas: {activeCameras}");
```

**Resultado esperado**: `Total cámaras activas: 1`

### **Verificar AudioListeners:**

```csharp
AudioListener[] listeners = FindObjectsOfType<AudioListener>();
int activeListeners = 0;
foreach (AudioListener listener in listeners)
{
    if (listener.enabled)
    {
        Debug.Log($"AudioListener ACTIVO: {listener.gameObject.name}");
        activeListeners++;
    }
}
Debug.Log($"Total AudioListeners activos: {activeListeners}");
```

**Resultado esperado**: `Total AudioListeners activos: 1`

---

## ⚡ Optimizaciones Adicionales (Futuro)

### **1. Culling de Players Remotos**

Desactivar renderizado de avatares que están muy lejos:

```csharp
void Update()
{
    if (!photonView.IsMine)
    {
        float distance = Vector3.Distance(transform.position, localPlayerPos);
        GetComponent<Renderer>().enabled = distance < 50f;
    }
}
```

### **2. LOD (Level of Detail)**

Reducir calidad de modelos remotos:
- Usar `LODGroup` en los avatares
- Configurar 3 niveles: Alto, Medio, Bajo

### **3. Occlusion Culling**

Activar en el proyecto:
- `Window > Rendering > Occlusion Culling`
- Configurar zonas de la sala
- Bake occlusion data

---

## ✅ Checklist de Verificación

Antes de hacer Build, verificar:

- [ ] Solo 1 cámara activa en escena
- [ ] Solo 1 AudioListener activo
- [ ] RoleManager agregado a la escena
- [ ] PhotonView configurado en RoleManager
- [ ] Todos los Players tienen ControlCamaraJugador actualizado
- [ ] Main Camera desactivada o eliminada
- [ ] Logs limpios (sin warnings de múltiples AudioListeners)
- [ ] FPS estables (60+ en Editor)

---

## 📞 Soporte

Si hay problemas después de estos cambios:

1. **Check Console Logs**: Buscar mensajes de `[ControlCamaraJugador]` y `[RoleManager]`
2. **Verificar Photon**: Asegurar que `photonView.IsMine` funciona correctamente
3. **Revisar Roles**: Confirmar que el rol fue asignado (`RoleManager.Instance.GetAvailableRoles()`)

---

**Última actualización**: 2 de Febrero, 2026  
**Estado**: ✅ Implementado y Listo para Testing
