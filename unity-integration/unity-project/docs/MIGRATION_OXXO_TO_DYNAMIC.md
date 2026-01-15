# Migración de OXXOSceneSetup a DynamicSceneSetup

## Resumen

Se ha refactorizado el sistema de configuración de escenas para que sea **completamente genérico y basado en datos de la base de datos**, eliminando la dependencia de código hardcodeado para casos específicos como OXXO.

## Cambios Principales

### 1. Nuevo Script: `DynamicSceneSetup.cs`

Reemplaza `OXXOSceneSetup.cs` con un sistema que:

- **Lee datos de la base de datos**: Usa `DialogueData` y `SessionData` de Laravel
- **Crea personajes dinámicamente**: Basado en los roles definidos en `DialogueData.dialogue.roles`
- **Configura colores e iconos**: Desde los datos del diálogo (color, icono)
- **Funciona con cualquier diálogo**: No está limitado a un caso específico

### 2. Actualización de `LaravelUnityEntryManager.cs`

- Reemplazada referencia `OXXOSceneSetup` por `DynamicSceneSetup`
- Obtiene nombre de escena desde `SessionData.session.configuracion["unity_scene"]` si está disponible
- Configura la escena automáticamente cuando los datos están disponibles

## Estructura de Datos

### DialogueData (desde Laravel)

```csharp
DialogueData
├── dialogue
│   ├── id
│   ├── nombre
│   ├── descripcion
│   └── roles[]          // Lista de roles del diálogo
│       ├── id
│       ├── nombre
│       ├── descripcion
│       ├── color        // Color en formato string (hex o nombre)
│       ├── icono        // Nombre del icono
│       └── requerido
└── session_info
```

### SessionData (desde Laravel)

```csharp
SessionData
├── session
│   ├── id
│   ├── nombre
│   ├── estado
│   └── configuracion   // Diccionario con configuración adicional
│       └── unity_scene // Nombre de la escena Unity (opcional)
└── role                // Rol asignado al usuario actual
```

## Configuración en Unity

### 1. Agregar DynamicSceneSetup a la Escena

1. Crear un GameObject vacío en la escena
2. Agregar el componente `DynamicSceneSetup`
3. Configurar:
   - **Generic Character Prefab**: Prefab genérico para personajes
   - **Character Positions**: Lista de posiciones de spawn (opcional, se generan automáticamente si no se definen)
   - **Role Prefab Mappings**: Mapeos opcionales de prefabs específicos por rol
   - **Dialogue Spawn Point**: Punto donde aparecerán los diálogos

### 2. Configuración de Sesión en Laravel

Para especificar qué escena Unity cargar, agregar en la configuración de la sesión:

```php
$sesion->configuracion = [
    'unity_scene' => 'SalaPrincipal'  // Nombre de la escena Unity
];
```

## Flujo de Inicialización

1. **LaravelUnityEntryManager** carga los datos de sesión y diálogo
2. **DynamicSceneSetup** se suscribe a eventos de `LaravelAPI`
3. Cuando `DialogueData` está disponible:
   - Lee los roles del diálogo
   - Crea personajes dinámicamente para cada rol
   - Asigna colores e iconos desde los datos
   - Configura el sistema de diálogos
4. La escena se configura completamente basada en datos de la BD

## Ventajas del Nuevo Sistema

✅ **Genérico**: Funciona con cualquier diálogo/sesión  
✅ **Basado en datos**: No requiere cambios de código para nuevos casos  
✅ **Escalable**: Fácil agregar nuevos diálogos desde Laravel  
✅ **Mantenible**: Un solo script para todas las escenas  
✅ **Flexible**: Permite prefabs específicos por rol si es necesario  

## Migración desde OXXOSceneSetup

### Pasos para Migrar

1. **Eliminar OXXOSceneSetup** de la escena
2. **Agregar DynamicSceneSetup** a la escena
3. **Configurar prefabs y posiciones** en el inspector
4. **Asegurar que los datos de Laravel** incluyan:
   - Roles con colores e iconos
   - Configuración de escena (opcional)

### Datos Requeridos en Laravel

Asegurar que el endpoint `/api/unity/session/{id}/dialogue` retorne:

```json
{
  "dialogue": {
    "roles": [
      {
        "id": 1,
        "nombre": "Juez",
        "color": "#CC3333",
        "icono": "gavel"
      },
      {
        "id": 2,
        "nombre": "Fiscal",
        "color": "#3333CC",
        "icono": "briefcase"
      }
      // ... más roles
    ]
  }
}
```

## Notas Importantes

- **Posiciones de personajes**: Si no se definen, se generan automáticamente en círculo
- **Prefabs**: Si no hay prefab específico para un rol, se usa el genérico
- **Colores**: Se pueden usar formatos hex (#RRGGBB) o nombres (rojo, azul, etc.)
- **Iconos**: Son strings que se pueden usar en UI (Bootstrap Icons, Font Awesome, etc.)

## Próximos Pasos

- [ ] Eliminar `OXXOSceneSetup.cs` del proyecto (después de verificar que todo funciona)
- [ ] Actualizar documentación de API para incluir campos requeridos
- [ ] Agregar validación en Laravel para asegurar que los datos estén completos
- [ ] Crear prefabs genéricos de personajes si no existen

