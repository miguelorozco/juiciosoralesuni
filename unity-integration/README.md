# 🎮 Integración de Proyecto Unity con Laravel

Esta carpeta contiene la integración completa entre tu proyecto Unity existente y el sistema Laravel de simulador de juicios.

## 📁 Estructura de Carpetas

```
unity-integration/
├── unity-project/          # 👈 AQUÍ COPIA TU PROYECTO UNITY COMPLETO
│   ├── Assets/            # Assets de Unity
│   ├── ProjectSettings/   # Configuración del proyecto
│   ├── UserSettings/      # Configuración de usuario
│   └── Packages/          # Paquetes de Unity
├── scripts/               # Scripts de integración Laravel-Unity
├── config/                # Archivos de configuración
└── docs/                  # Documentación específica
```

## 🚀 Pasos para Integrar tu Proyecto

### 1. Copiar tu Proyecto Unity

**Copia TODO el contenido de tu proyecto Unity aquí:**

```bash
# Desde tu proyecto Unity original, copia:
cp -r /ruta/a/tu/proyecto-unity/* /var/www/juicios_local/unity-integration/unity-project/
```

**O manualmente:**
- Copia la carpeta `Assets/` completa
- Copia la carpeta `ProjectSettings/` completa  
- Copia la carpeta `UserSettings/` completa
- Copia la carpeta `Packages/` completa
- Copia cualquier archivo `.csproj`, `.sln`, etc.

### 2. Archivos que Necesitas Modificar

Una vez que copies tu proyecto, necesitarás modificar estos archivos:

#### A. Scripts de Sala (si los tienes)
- `Assets/Scripts/RoomManager.cs` → Integrar con `scripts/RoomIntegration.cs`
- `Assets/Scripts/AudioManager.cs` → Integrar con `scripts/AudioIntegration.cs`

#### B. Scripts de Red/Networking
- `Assets/Scripts/NetworkManager.cs` → Integrar con `scripts/LaravelAPI.cs`
- `Assets/Scripts/PlayerController.cs` → Integrar con `scripts/PlayerIntegration.cs`

### 3. Scripts de Integración

Los scripts en la carpeta `scripts/` están diseñados para integrarse con tu proyecto existente:

- `LaravelAPI.cs` - Comunicación con Laravel
- `RoomIntegration.cs` - Gestión de salas
- `AudioIntegration.cs` - Audio compartido
- `PlayerIntegration.cs` - Control de jugadores

## 🔧 Configuración

### 1. Configuración de Unity

Crea un archivo `UnityConfig.asset` en tu proyecto:

```csharp
// Assets/Resources/UnityConfig.asset
API Base URL: http://localhost:8000/api
Sesión ID: 1
Usuario ID: 1
```

### 2. Configuración de Laravel

El sistema Laravel ya está configurado para manejar:
- Salas de Unity
- Audio compartido
- Sincronización de jugadores
- Eventos en tiempo real

## 📋 Checklist de Integración

- [ ] Copiar proyecto Unity completo
- [ ] Verificar que todos los scripts funcionen
- [ ] Integrar scripts de Laravel
- [ ] Configurar conexión a API
- [ ] Probar audio compartido
- [ ] Probar sincronización de salas
- [ ] Probar eventos en tiempo real

## 🆘 Soporte

Si tienes problemas con la integración:

1. Revisa los logs de Unity
2. Revisa los logs de Laravel: `storage/logs/laravel.log`
3. Verifica la conexión a la API
4. Consulta la documentación en `docs/`

---

**¡Tu proyecto Unity estará completamente integrado con Laravel! 🎉**

