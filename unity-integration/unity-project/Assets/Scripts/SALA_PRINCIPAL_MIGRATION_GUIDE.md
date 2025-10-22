# 🔄 **Guía de Migración: SalaPrincipal - De Selección Manual a Laravel Integration**

## 📋 **Resumen de la Migración**

Esta guía te ayudará a migrar la escena `SalaPrincipal` de Unity para que funcione con las sesiones de Laravel en lugar del canvas de selección manual de roles.

### 🎯 **Objetivo**
- ❌ **Eliminar**: Canvas de selección manual de roles
- ✅ **Agregar**: Integración automática con sesiones de Laravel
- ✅ **Mantener**: Funcionalidad de Photon PUN2 y chat de voz
- ✅ **Mejorar**: Experiencia de usuario con asignación automática de roles

---

## 🔧 **Scripts Creados para la Migración**

### **1. EnhancedNetworkManager.cs** ⭐ **PRINCIPAL**
- **Función**: Reemplaza `GestionRedJugador` con integración de Laravel
- **Características**:
  - Se conecta automáticamente a la sesión de Laravel
  - Obtiene el rol asignado desde el backend
  - Se conecta a Photon con el rol pre-asignado
  - Mantiene la funcionalidad de chat de voz

### **2. RoleInfoUI.cs** 🎨 **UI INFORMATIVA**
- **Función**: Reemplaza `RoleSelectionUI` con información del rol asignado
- **Características**:
  - Muestra información del rol asignado
  - Descripción y color del rol
  - Información de la sesión y participantes
  - Botón "Ready" para continuar

### **3. SalaPrincipalMigration.cs** 🔍 **HERRAMIENTA DE MIGRACIÓN**
- **Función**: Script de ayuda para verificar el estado de la migración
- **Características**:
  - Verifica componentes antiguos y nuevos
  - Genera reportes de migración
  - Instrucciones paso a paso

---

## 📝 **Pasos para la Migración**

### **Paso 1: Preparación**
1. **Hacer backup** de la escena `SalaPrincipal.unity`
2. **Abrir** la escena en Unity
3. **Agregar** el script `SalaPrincipalMigration` a cualquier GameObject para guía

### **Paso 2: Eliminar Componentes Antiguos**

#### **2.1 Eliminar RoleSelectionUI**
- Buscar el GameObject que tiene el componente `RoleSelectionUI`
- **Eliminar** el componente `RoleSelectionUI`
- **Eliminar** el GameObject si solo contiene este componente

#### **2.2 Eliminar GestionRedJugador**
- Buscar el GameObject que tiene el componente `GestionRedJugador`
- **Eliminar** el componente `GestionRedJugador`
- **Mantener** el GameObject (lo usaremos para el nuevo componente)

#### **2.3 Eliminar Canvas de Selección de Roles**
- Buscar el Canvas que contiene los botones de selección de roles
- **Eliminar** todo el Canvas y sus hijos
- **Mantener** otros Canvas que no sean de selección de roles

### **Paso 3: Agregar Componentes Nuevos**

#### **3.1 Agregar EnhancedNetworkManager**
- Seleccionar el GameObject principal de la escena
- **Agregar Componente** → `EnhancedNetworkManager`
- **Configurar** los siguientes campos:
  - `SessionManager`: Arrastrar el SessionManager de la escena
  - `Auto Connect To Session`: ✅ Activado
  - `Spawn Position`: `(-0.06, 4.8, -16.0)`
  - `Spawn Rotation`: `(0, 180, 0)`

#### **3.2 Configurar UI de Carga (Opcional)**
- Crear un Canvas para la UI de carga
- **Agregar**:
  - Panel de carga (`loadingPanel`)
  - Texto de estado (`loadingText`, `statusText`)
- **Asignar** estos elementos al `EnhancedNetworkManager`

#### **3.3 Agregar RoleInfoUI (Opcional)**
- Crear un Canvas para mostrar información del rol
- **Agregar Componente** → `RoleInfoUI`
- **Configurar** todos los campos de UI:
  - `Role Name Text`: TextMeshProUGUI para el nombre del rol
  - `Role Description Text`: TextMeshProUGUI para la descripción
  - `Session Info Text`: TextMeshProUGUI para información de sesión
  - `Ready Button`: Botón para continuar
  - `Leave Session Button`: Botón para abandonar sesión

### **Paso 4: Configurar SessionManager**
- **Verificar** que `SessionManager` esté presente en la escena
- **Configurar** si es necesario:
  - API base URL
  - Configuración de autenticación
  - Configuración de sesiones

### **Paso 5: Verificar la Migración**
1. **Ejecutar** `SalaPrincipalMigration.CheckMigrationStatus()`
2. **Revisar** el reporte en la consola
3. **Corregir** cualquier problema identificado

---

## 🎮 **Flujo de Usuario Después de la Migración**

### **Antes (Selección Manual)**
1. Usuario abre Unity
2. Ve canvas de selección de roles
3. Selecciona un rol manualmente
4. Hace clic en "Iniciar"
5. Se conecta a Photon
6. Entra a la sala

### **Después (Integración Laravel)**
1. Usuario abre Unity
2. Ve pantalla de carga "Conectando a la sesión..."
3. Sistema obtiene rol automáticamente de Laravel
4. Ve información del rol asignado
5. Hace clic en "Ready"
6. Se conecta a Photon con el rol pre-asignado
7. Entra a la sala

---

## 🔍 **Verificación Post-Migración**

### **Checklist de Verificación**
- [ ] ❌ `RoleSelectionUI` eliminado
- [ ] ❌ `GestionRedJugador` eliminado
- [ ] ❌ Canvas de selección de roles eliminado
- [ ] ✅ `EnhancedNetworkManager` agregado y configurado
- [ ] ✅ `SessionManager` presente y configurado
- [ ] ✅ `RoleInfoUI` agregado (opcional)
- [ ] ✅ UI de carga configurada
- [ ] ✅ Spawn position configurada correctamente

### **Pruebas Funcionales**
- [ ] ✅ La escena se carga sin errores
- [ ] ✅ Se conecta a Laravel automáticamente
- [ ] ✅ Obtiene el rol asignado
- [ ] ✅ Se conecta a Photon correctamente
- [ ] ✅ El jugador se instancia en la posición correcta
- [ ] ✅ El chat de voz funciona
- [ ] ✅ El rol se muestra correctamente en Photon

---

## 🚨 **Problemas Comunes y Soluciones**

### **Error: SessionManager no encontrado**
- **Solución**: Agregar `SessionManager` a la escena
- **Verificar**: Que esté configurado correctamente

### **Error: No se asigna rol**
- **Solución**: Verificar que la sesión de Laravel esté activa
- **Verificar**: Que el usuario esté asignado a un rol en la sesión

### **Error: No se conecta a Photon**
- **Solución**: Verificar configuración de Photon
- **Verificar**: Que `PhotonNetwork.ConnectUsingSettings()` funcione

### **Error: Jugador no se instancia**
- **Solución**: Verificar que el prefab "Player" exista
- **Verificar**: Que la posición de spawn sea correcta

---

## 📊 **Beneficios de la Migración**

### **Para el Usuario**
- ✅ **Experiencia más fluida**: No necesita seleccionar rol manualmente
- ✅ **Menos confusión**: El rol viene pre-asignado por el instructor
- ✅ **Acceso más rápido**: Entra directamente a la sala

### **Para el Instructor**
- ✅ **Control total**: Asigna roles desde Laravel
- ✅ **Gestión centralizada**: Todos los roles en un lugar
- ✅ **Flexibilidad**: Puede cambiar roles sin reiniciar Unity

### **Para el Sistema**
- ✅ **Integración completa**: Unity y Laravel trabajan juntos
- ✅ **Menos errores**: No hay conflictos de roles
- ✅ **Escalabilidad**: Fácil agregar más funcionalidades

---

## 🎯 **Próximos Pasos Después de la Migración**

1. **Probar** la migración en un entorno de desarrollo
2. **Ajustar** la UI según las necesidades
3. **Optimizar** el flujo de conexión
4. **Documentar** cualquier cambio específico
5. **Entrenar** a los usuarios en el nuevo flujo

---

**💡 Tip**: Usa `SalaPrincipalMigration.CheckMigrationStatus()` para verificar que todo esté correcto antes de probar la escena.
