# 📝 Crear Diálogo de Ejemplo - Sistema v2

Esta guía te muestra cómo crear un diálogo de ejemplo para probar el sistema v2.

---

## 🎯 Opción 1: Usar el Seeder (Recomendado)

### Ejecutar el Seeder

```bash
# Ejecutar solo el seeder de ejemplo
php artisan db:seed --class=DialogoV2EjemploSeeder

# O ejecutar todos los seeders (incluye el de ejemplo)
php artisan db:seed
```

### ¿Qué crea el seeder?

El seeder `DialogoV2EjemploSeeder` crea un diálogo completo con:

- **1 Diálogo**: "Diálogo de Ejemplo - Juicio Penal Simple"
- **7 Nodos**:
  - 1 Nodo Inicio
  - 3 Nodos Desarrollo
  - 1 Nodo Decisión
  - 2 Nodos Final
- **6 Respuestas/Conexiones** entre nodos

### Estructura del Diálogo

```
[Inicio] 
   ↓
[Presentación Fiscal]
   ↓
[Decisión: Estrategia]
   ├─→ [Defensa Inocencia] → [Absolución]
   └─→ [Defensa Atenuantes] → [Condena]
```

### Acceder al Diálogo Creado

Después de ejecutar el seeder, verás en la consola:

```
✅ Diálogo de ejemplo creado exitosamente!
📝 ID del diálogo: 1
📝 Nombre: Diálogo de Ejemplo - Juicio Penal Simple
🔗 URL del editor: /dialogos-v2/1/editor
```

**Accede al editor:**
```
http://localhost:8000/dialogos-v2/1/editor
```

---

## 🎨 Opción 2: Crear Manualmente desde el Editor Web

### Paso 1: Acceder al Editor

1. Inicia sesión con una cuenta de administrador:
   - Email: `admin@juiciosorales.site`
   - Contraseña: `password`

2. Accede a la URL de creación:
   ```
   http://localhost:8000/dialogos-v2/create
   ```

### Paso 2: Crear el Diálogo

1. **En el panel izquierdo**, completa la información:
   - **Nombre**: "Mi Primer Diálogo"
   - **Descripción**: "Un diálogo de prueba"
   - **Estado**: Selecciona "borrador" o "activo"
   - Marca "Público" si quieres que sea visible para todos

2. **Haz clic en "Guardar"** en la barra superior

### Paso 3: Crear Nodos

1. **En el panel izquierdo**, haz clic en los botones para crear nodos:
   - **Inicio**: Crea el nodo inicial
   - **Desarrollo**: Crea nodos de desarrollo
   - **Decisión**: Crea nodos de decisión
   - **Final**: Crea nodos finales

2. **Arrastra los nodos** en el canvas para organizarlos

3. **Selecciona un nodo** y edita sus propiedades en el panel derecho:
   - **Título**: Nombre del nodo
   - **Contenido**: Texto que se mostrará
   - **Tipo**: Tipo de nodo
   - **Menu Text**: Texto para el menú
   - **Es Inicial**: Marca si es el nodo inicial
   - **Es Final**: Marca si es el nodo final

### Paso 4: Crear Conexiones

1. **Selecciona un nodo** en el canvas
2. **En el panel derecho**, en la sección "Respuestas":
   - Haz clic en **"Agregar Respuesta"**
   - Escribe el **texto de la respuesta**
   - Selecciona el **nodo destino** del dropdown
3. **Guarda el nodo** haciendo clic en "Guardar Nodo"

### Paso 5: Guardar y Validar

1. **Guarda el diálogo** completo con el botón "Guardar" en la barra superior
2. **Valida la estructura** con el botón "Validar"
3. Si hay errores, corrígelos antes de activar

---

## 📋 Estructura Mínima de un Diálogo

Un diálogo válido debe tener:

- ✅ **Al menos 1 nodo inicial** (marcado como "Es Inicial")
- ✅ **Al menos 1 nodo final** (marcado como "Es Final")
- ✅ **Todos los nodos de decisión deben tener al menos 1 respuesta**
- ✅ **Todas las respuestas deben apuntar a nodos existentes**

---

## 🧪 Ejemplo de Diálogo Simple

### Nodo 1: Inicio
- **Tipo**: Inicio
- **Título**: "Bienvenida"
- **Contenido**: "Bienvenido al simulador de juicios orales"
- **Es Inicial**: ✅

### Nodo 2: Desarrollo
- **Tipo**: Desarrollo
- **Título**: "Presentación del Caso"
- **Contenido**: "El juez presenta el caso a los participantes"
- **Es Inicial**: ❌

### Nodo 3: Decisión
- **Tipo**: Decisión
- **Título**: "Elegir Estrategia"
- **Contenido**: "¿Qué estrategia quieres seguir?"
- **Es Inicial**: ❌
- **Respuestas**:
  - "Estrategia A" → Nodo 4
  - "Estrategia B" → Nodo 5

### Nodo 4: Final
- **Tipo**: Final
- **Título**: "Final A"
- **Contenido**: "Has elegido la estrategia A"
- **Es Final**: ✅

### Nodo 5: Final
- **Tipo**: Final
- **Título**: "Final B"
- **Contenido**: "Has elegido la estrategia B"
- **Es Final**: ✅

---

## 🔍 Verificar Diálogos Creados

### Desde Tinker

```bash
php artisan tinker
```

```php
use App\Models\DialogoV2;

// Listar todos los diálogos
DialogoV2::all();

// Ver un diálogo específico con sus nodos
$dialogo = DialogoV2::with('nodos.respuestas')->find(1);
$dialogo->nodos;

// Contar nodos
$dialogo->nodos->count();

exit
```

### Desde MySQL

```sql
-- Ver todos los diálogos
SELECT id, nombre, estado, creado_por FROM dialogos_v2;

-- Ver nodos de un diálogo
SELECT id, titulo, tipo, es_inicial, es_final 
FROM nodos_dialogo_v2 
WHERE dialogo_id = 1;

-- Ver respuestas de un diálogo
SELECT r.id, r.texto, n.titulo as nodo_origen, n2.titulo as nodo_destino
FROM respuestas_dialogo_v2 r
JOIN nodos_dialogo_v2 n ON r.nodo_origen_id = n.id
LEFT JOIN nodos_dialogo_v2 n2 ON r.nodo_siguiente_id = n2.id
WHERE n.dialogo_id = 1;
```

---

## 🚀 Próximos Pasos

Una vez que tengas un diálogo creado:

1. **Probar el editor**: Edita nodos, mueve posiciones, crea conexiones
2. **Validar estructura**: Usa el botón "Validar" para verificar que todo está correcto
3. **Activar diálogo**: Cambia el estado a "activo" cuando esté listo
4. **Probar en sesión**: Crea una sesión y asigna el diálogo para probarlo en tiempo real

---

## 📚 Referencias

- **Guía de Instalación**: `docs/guia-instalacion-editor-dialogos-v2.md`
- **Cuentas Seed**: `docs/cuentas-seed-credenciales.md`
- **Rutas del Editor**: `/dialogos-v2/create` y `/dialogos-v2/{id}/editor`

---

**¡Listo! Ahora puedes crear y probar diálogos en el sistema v2.** 🎉
