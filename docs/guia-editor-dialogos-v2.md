# Guía del Editor de Diálogos v2

## 📋 Índice
1. [Conceptos Básicos](#conceptos-básicos)
2. [Flujo de Trabajo Recomendado](#flujo-de-trabajo-recomendado)
3. [Tipos de Nodos](#tipos-de-nodos)
4. [Cómo Crear un Diálogo Completo](#cómo-crear-un-diálogo-completo)
5. [Conectar Nodos](#conectar-nodos)
6. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 🎯 Conceptos Básicos

### ¿Qué es un Diálogo Ramificado?
Un diálogo ramificado es una conversación interactiva donde el usuario puede tomar decisiones que afectan el flujo de la conversación. Cada decisión lleva a diferentes caminos y resultados.

### Componentes del Editor

1. **Panel Izquierdo (Herramientas)**
   - Información del diálogo (nombre, descripción, estado)
   - Botones para crear nodos
   - Estadísticas del diálogo

2. **Panel Central (Canvas)**
   - Área visual donde se colocan y conectan los nodos
   - Los nodos se pueden arrastrar libremente
   - Las conexiones se muestran automáticamente

3. **Panel Derecho (Propiedades)**
   - Edita las propiedades del nodo seleccionado
   - Configura respuestas/opciones para nodos de decisión
   - Conecta nodos entre sí

---

## 🔄 Flujo de Trabajo Recomendado

### Paso 1: Crear el Diálogo
1. Ingresa un **nombre** para el diálogo
2. Opcionalmente, agrega una **descripción**
3. Haz clic en **"Guardar"** para crear el diálogo

### Paso 2: Crear el Nodo Inicio
1. Haz clic en el botón **"Inicio"** en el panel izquierdo
2. El nodo aparecerá en el canvas
3. Selecciona el nodo y completa sus propiedades:
   - **Título**: Nombre descriptivo del nodo
   - **Contenido**: El texto que se mostrará al usuario
4. Haz clic en **"Guardar Nodo"**

### Paso 3: Agregar Nodos de Desarrollo o Decisión

#### Para Nodos de Desarrollo:
1. Haz clic en **"Desarrollo"**
2. Completa título y contenido
3. Guarda el nodo
4. **Conecta** desde el nodo anterior:
   - Selecciona el nodo que debe llevar a este
   - Agrega una respuesta (si es necesario)
   - Selecciona este nodo como destino

#### Para Nodos de Decisión:
1. Haz clic en **"Decisión"**
2. Completa título y contenido
3. Guarda el nodo
4. **Agrega opciones**:
   - Haz clic en **"Agregar Opción"**
   - Escribe el texto de la opción (ej: "Aceptar", "Rechazar")
   - **Conecta a un nodo destino**:
     - Si el nodo ya existe: selecciónalo del dropdown
     - Si no existe: selecciona **"➕ Crear nuevo nodo..."** y elige el tipo
   - Repite para cada opción (mínimo 2)

### Paso 4: Crear Nodos Finales
1. Haz clic en **"Final"**
2. Completa título y contenido
3. Conecta desde los nodos que deben terminar el diálogo

### Paso 5: Validar y Activar
1. Haz clic en **"Validar"** para verificar que todo esté correcto
2. Si hay errores, corrígelos
3. Haz clic en **"Activar"** para poner el diálogo en uso

---

## 📦 Tipos de Nodos

### 🟢 Nodo Inicio
- **Propósito**: Punto de entrada del diálogo
- **Características**:
  - Solo debe haber **uno** por diálogo
  - Se marca automáticamente como inicial
  - No puede tener respuestas que lo conecten desde otros nodos

### 🔵 Nodo Desarrollo
- **Propósito**: Mostrar contenido y continuar al siguiente nodo
- **Características**:
  - Muestra texto al usuario
  - Generalmente tiene una sola conexión hacia adelante
  - Puede tener respuestas simples

### 🟡 Nodo Decisión
- **Propósito**: Permitir al usuario elegir entre múltiples opciones
- **Características**:
  - **Requiere al menos 2 opciones**
  - Cada opción conecta a un nodo diferente
  - El usuario ve todas las opciones y elige una
  - Visualmente se distingue con un icono ⚡

### 🔴 Nodo Final
- **Propósito**: Terminar el diálogo
- **Características**:
  - Puede haber múltiples nodos finales
  - Se marca automáticamente como final
  - No tiene conexiones salientes

---

## 🎨 Cómo Crear un Diálogo Completo

### Ejemplo: Diálogo Simple de Aceptación/Rechazo

```
1. Crear Nodo Inicio
   └─ Título: "Bienvenida"
   └─ Contenido: "Bienvenido al simulacro de juicio"

2. Crear Nodo Decisión
   └─ Título: "¿Acepta los términos?"
   └─ Contenido: "¿Desea continuar con el proceso?"
   └─ Opciones:
      ├─ "Sí, acepto" → Conectar a Nodo Desarrollo "Continuar"
      └─ "No, rechazo" → Conectar a Nodo Final "Rechazado"

3. Crear Nodo Desarrollo "Continuar"
   └─ Título: "Proceso Continuado"
   └─ Contenido: "El proceso continúa..."
   └─ Conectar a Nodo Final "Completado"

4. Crear Nodos Finales
   ├─ "Rechazado" (conectado desde "No, rechazo")
   └─ "Completado" (conectado desde "Continuar")
```

### Pasos Detallados:

1. **Crear el diálogo** con nombre "Ejemplo Aceptación"

2. **Crear Nodo Inicio**:
   - Clic en "Inicio"
   - Título: "Bienvenida"
   - Contenido: "Bienvenido..."
   - Guardar

3. **Crear Nodo Decisión**:
   - Clic en "Decisión"
   - Título: "¿Acepta los términos?"
   - Contenido: "¿Desea continuar?"
   - Guardar
   - **Agregar Opción 1**:
     - Texto: "Sí, acepto"
     - En "Conectar a nodo": Seleccionar "➕ Crear nuevo nodo..."
     - Elegir tipo "desarrollo"
     - El nuevo nodo se crea y conecta automáticamente
   - **Agregar Opción 2**:
     - Texto: "No, rechazo"
     - En "Conectar a nodo": Seleccionar "➕ Crear nuevo nodo..."
     - Elegir tipo "final"
     - El nuevo nodo se crea y conecta automáticamente

4. **Completar los nodos creados**:
   - Seleccionar el nodo de desarrollo creado
   - Completar título y contenido
   - Conectar a un nodo final (crear si no existe)
   - Guardar

5. **Validar y Activar**

---

## 🔗 Conectar Nodos

### Método 1: Desde el Panel de Propiedades (Recomendado)

1. Selecciona el nodo **origen** (el que tiene la decisión o respuesta)
2. En el panel derecho, ve a **"Respuestas"** o **"Opciones de Decisión"**
3. Agrega una respuesta/opción
4. En el campo **"Conectar a nodo"**:
   - Si el nodo destino **ya existe**: selecciónalo del dropdown
   - Si **no existe**: selecciona **"➕ Crear nuevo nodo..."** y elige el tipo
5. Guarda el nodo (esto guardará también las conexiones)

### Método 2: Crear Nodos Primero, Conectar Después

1. Crea todos los nodos que necesites usando los botones del panel izquierdo
2. Selecciona cada nodo y completa sus propiedades
3. Para conectar:
   - Selecciona el nodo origen
   - Agrega respuestas/opciones
   - Selecciona el nodo destino del dropdown
   - Guarda

### Visualización de Conexiones

- Las conexiones se muestran automáticamente como líneas entre nodos
- El color de la conexión corresponde al color de la respuesta
- Puedes arrastrar los nodos para reorganizar el layout
- Las conexiones se actualizan automáticamente

---

## ❓ Preguntas Frecuentes

### ¿Cómo sé qué nodos están conectados?
- Las conexiones se muestran como líneas en el canvas
- Al seleccionar un nodo, puedes ver sus respuestas en el panel derecho
- Cada respuesta muestra a qué nodo está conectada

### ¿Puedo crear un nodo desde el selector de destino?
**¡Sí!** Cuando estás configurando una respuesta/opción:
1. En el dropdown "Conectar a nodo", selecciona **"➕ Crear nuevo nodo..."**
2. Se te pedirá el tipo de nodo (desarrollo, decisión o final)
3. El nodo se crea automáticamente y se conecta

### ¿Qué pasa si creo un nodo de decisión sin opciones?
- El diálogo no funcionará correctamente
- La validación te avisará que faltan opciones
- **Siempre agrega al menos 2 opciones** a los nodos de decisión

### ¿Puedo tener múltiples nodos iniciales?
- No, solo debe haber **un nodo inicial** por diálogo
- Si marcas otro nodo como inicial, el anterior se desmarca automáticamente

### ¿Cómo cambio el orden de las opciones en un nodo de decisión?
- Las opciones se ordenan según el orden en que las agregas
- Puedes eliminar y volver a agregar para cambiar el orden
- El campo "orden" se ajusta automáticamente

### ¿Los nodos se guardan automáticamente?
- No, debes hacer clic en **"Guardar Nodo"** después de crear o modificar
- Las respuestas se guardan automáticamente cuando guardas el nodo

### ¿Cómo valido que mi diálogo está correcto?
- Haz clic en el botón **"Validar"** en la parte superior
- Se mostrará un modal con:
  - ✅ Errores que debes corregir
  - ⚠️ Advertencias (sugerencias de mejora)

---

## 💡 Consejos y Mejores Prácticas

1. **Planifica antes de crear**: Dibuja un esquema en papel de cómo quieres que fluya el diálogo

2. **Nombres descriptivos**: Usa títulos claros para los nodos (ej: "Pregunta sobre evidencia" en lugar de "Nodo 1")

3. **Organiza el canvas**: Arrastra los nodos para organizarlos visualmente de izquierda a derecha o de arriba hacia abajo

4. **Valida frecuentemente**: Usa el botón "Validar" mientras construyes para detectar errores temprano

5. **Guarda regularmente**: Guarda el diálogo y los nodos frecuentemente para no perder trabajo

6. **Revisa las conexiones**: Asegúrate de que todas las opciones de decisión tengan un destino válido

7. **Prueba el flujo**: Después de activar, prueba el diálogo desde el punto de vista del usuario

---

## 🎓 Ejemplo Completo Paso a Paso

### Diálogo: "Simulacro de Audiencia Inicial"

**Paso 1: Crear Diálogo**
- Nombre: "Audiencia Inicial - Ejemplo"
- Descripción: "Simulacro básico de audiencia inicial"
- Guardar

**Paso 2: Nodo Inicio**
- Crear → "Inicio"
- Título: "Inicio de Audiencia"
- Contenido: "La audiencia da inicio. El juez se presenta."
- Guardar Nodo

**Paso 3: Nodo Decisión**
- Crear → "Decisión"
- Título: "Presentación del Abogado"
- Contenido: "¿Cómo desea proceder el abogado defensor?"
- Guardar Nodo
- Agregar Opción 1:
  - Texto: "Solicitar aplazamiento"
  - Conectar a: ➕ Crear nuevo nodo... → "final"
  - (Se crea nodo final "Aplazamiento")
- Agregar Opción 2:
  - Texto: "Continuar con la audiencia"
  - Conectar a: ➕ Crear nuevo nodo... → "desarrollo"
  - (Se crea nodo desarrollo "Continuación")
- Guardar Nodo

**Paso 4: Completar Nodos Creados**
- Seleccionar nodo "Continuación"
- Título: "Continuación de Audiencia"
- Contenido: "La audiencia continúa..."
- Conectar a: ➕ Crear nuevo nodo... → "final"
- (Se crea nodo final "Audiencia Completada")
- Guardar Nodo

**Paso 5: Completar Nodos Finales**
- Seleccionar "Aplazamiento"
- Contenido: "La audiencia ha sido aplazada."
- Guardar Nodo
- Seleccionar "Audiencia Completada"
- Contenido: "La audiencia ha sido completada exitosamente."
- Guardar Nodo

**Paso 6: Validar y Activar**
- Clic en "Validar"
- Si todo está bien, clic en "Activar"

---

## 🆘 Solución de Problemas

### El nodo no se guarda
- Verifica que el diálogo tenga un nombre
- Asegúrate de que el diálogo esté guardado primero
- Revisa la consola del navegador para errores

### Las conexiones no se muestran
- Guarda el nodo después de agregar respuestas
- Recarga la página
- Verifica que ambos nodos (origen y destino) estén guardados

### No puedo crear un nodo desde el selector
- Asegúrate de que el diálogo esté guardado
- Verifica que tengas permisos de administrador/instructor
- Revisa la consola del navegador

### Las respuestas no se guardan
- Asegúrate de hacer clic en "Guardar Nodo" después de agregar respuestas
- Verifica que el texto de la respuesta no esté vacío
- Revisa que el nodo destino esté seleccionado

---

¡Feliz creación de diálogos! 🎉
