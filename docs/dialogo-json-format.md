# 📋 Formato JSON para Importación de Diálogos

Esta documentación describe el formato JSON requerido para importar diálogos completos al sistema de simulacros de juicios orales.

## 🎯 Estructura General

El archivo JSON debe contener tres secciones principales:

```json
{
  "dialogo": { ... },
  "nodos": [ ... ],
  "conexiones": [ ... ]
}
```

## 📝 Sección "dialogo"

Define los metadatos básicos del diálogo.

### Propiedades Requeridas

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `nombre` | string | Nombre del diálogo (máx. 200 caracteres) | "Simulación de Juicio Civil" |
| `descripcion` | string | Descripción detallada del diálogo | "Simulación completa de un juicio civil..." |
| `publico` | boolean | Si el diálogo es público o privado | `true` |

### Ejemplo

```json
{
  "dialogo": {
    "nombre": "Simulación de Juicio Civil",
    "descripcion": "Simulación completa de un juicio civil con múltiples testigos y evidencias",
    "publico": true
  }
}
```

## 🎭 Sección "nodos"

Array de objetos que define cada nodo del diálogo.

### Propiedades Requeridas

| Campo | Tipo | Descripción | Valores Válidos |
|-------|------|-------------|-----------------|
| `id` | string | Identificador único del nodo | Cualquier string único |
| `titulo` | string | Título del nodo (máx. 200 caracteres) | "Inicio del Juicio" |
| `contenido` | string | Contenido principal del nodo | "Bienvenidos al juicio..." |
| `rol_nombre` | string | Nombre del rol que ejecuta el nodo | "Juez", "Fiscal", "Defensor" |
| `tipo` | string | Tipo de nodo | `"inicio"`, `"desarrollo"`, `"decision"`, `"final"` |
| `es_inicial` | boolean | Si es el nodo de inicio del diálogo | `true` o `false` |
| `es_final` | boolean | Si es un nodo final del diálogo | `true` o `false` |
| `posicion` | object | Posición en el grid del editor | `{ "x": 0, "y": 0 }` |

### Propiedades Opcionales

| Campo | Tipo | Descripción | Valor por Defecto |
|-------|------|-------------|-------------------|
| `instrucciones` | string | Instrucciones adicionales | `null` |

### Ejemplo de Nodo

```json
{
  "id": "nodo_inicio",
  "titulo": "Inicio del Juicio",
  "contenido": "Bienvenidos a la audiencia del caso #2024-001. Procederemos con la lectura de cargos.",
  "instrucciones": "El juez debe leer con voz clara y pausada",
  "rol_nombre": "Juez",
  "tipo": "inicio",
  "es_inicial": true,
  "es_final": false,
  "posicion": {
    "x": 0,
    "y": 0
  }
}
```

## 🔗 Sección "conexiones"

Array de objetos que define las conexiones entre nodos.

### Propiedades Requeridas

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `desde` | string | ID del nodo origen | "nodo_inicio" |
| `hacia` | string | ID del nodo destino | "nodo_lectura_cargos" |
| `texto` | string | Texto de la respuesta/conexión | "Continuar con la lectura" |

### Propiedades Opcionales

| Campo | Tipo | Descripción | Valor por Defecto |
|-------|------|-------------|-------------------|
| `descripcion` | string | Descripción de la conexión | `null` |
| `color` | string | Color de la línea (hex) | `"#007bff"` |
| `puntuacion` | number | Puntuación de la respuesta | `0` |

### Ejemplo de Conexión

```json
{
  "desde": "nodo_inicio",
  "hacia": "nodo_lectura_cargos",
  "texto": "Proceder con la lectura de cargos",
  "descripcion": "El juez procede a leer los cargos imputados",
  "color": "#28a745",
  "puntuacion": 10
}
```

## 🎨 Tipos de Nodos

### 1. Nodo de Inicio (`"inicio"`)
- **Propósito**: Punto de entrada del diálogo
- **Características**: 
  - `es_inicial: true`
  - `es_final: false`
  - Debe haber exactamente uno por diálogo

### 2. Nodo de Desarrollo (`"desarrollo"`)
- **Propósito**: Contenido narrativo o informativo
- **Características**:
  - `es_inicial: false`
  - `es_final: false`
  - Puede tener múltiples conexiones salientes

### 3. Nodo de Decisión (`"decision"`)
- **Propósito**: Punto donde el usuario debe tomar una decisión
- **Características**:
  - `es_inicial: false`
  - `es_final: false`
  - Debe tener múltiples conexiones salientes

### 4. Nodo Final (`"final"`)
- **Propósito**: Punto de salida del diálogo
- **Características**:
  - `es_inicial: false`
  - `es_final: true`
  - No debe tener conexiones salientes

## 📐 Sistema de Posicionamiento y Grid

### Conceptos Básicos

El sistema de posicionamiento utiliza un **grid de celdas** para organizar los nodos de manera ordenada y evitar solapamientos. Cada nodo debe posicionarse en una celda específica del grid.

#### Características del Grid
- **Tamaño de celda**: 200px × 200px
- **Dimensiones**: 5 columnas × 50 filas (expandible)
- **Origen**: Esquina superior izquierda (0, 0)
- **Coordenadas**: Sistema cartesiano con `x` horizontal e `y` vertical

### Sistema de Coordenadas

#### Coordenadas Absolutas
Las coordenadas `x` e `y` representan la posición en píxeles desde el origen:

```json
"posicion": {
  "x": 0,    // 0px desde la izquierda
  "y": 0     // 0px desde arriba
}
```

#### Conversión a Celdas del Grid
El sistema convierte automáticamente las coordenadas a posiciones de celda:

```javascript
// Fórmula de conversión
columna = Math.floor(x / 200)
fila = Math.floor(y / 200)
```

### Ejemplos Visuales del Grid

#### Grid 5x5 (Vista Simplificada)
```
    0    1    2    3    4
0  [A]  [B]  [C]  [D]  [E]
1  [F]  [G]  [H]  [I]  [J]
2  [K]  [L]  [M]  [N]  [O]
3  [P]  [Q]  [R]  [S]  [T]
4  [U]  [V]  [W]  [X]  [Y]
```

#### Mapeo de Coordenadas a Celdas
| Coordenadas | Celda | Descripción |
|-------------|-------|-------------|
| `x: 0, y: 0` | (0,0) | Esquina superior izquierda |
| `x: 200, y: 0` | (1,0) | Segunda columna, primera fila |
| `x: 0, y: 200` | (0,1) | Primera columna, segunda fila |
| `x: 400, y: 200` | (2,1) | Tercera columna, segunda fila |

### Ejemplos Prácticos de Posicionamiento

#### Ejemplo 1: Diálogo Lineal Simple
```json
{
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "posicion": { "x": 0, "y": 0 }      // Celda (0,0)
    },
    {
      "id": "desarrollo",
      "titulo": "Desarrollo", 
      "posicion": { "x": 200, "y": 0 }    // Celda (1,0)
    },
    {
      "id": "fin",
      "titulo": "Fin",
      "posicion": { "x": 400, "y": 0 }    // Celda (2,0)
    }
  ]
}
```

**Resultado Visual:**
```
[Inicio] → [Desarrollo] → [Fin]
(0,0)      (1,0)          (2,0)
```

#### Ejemplo 2: Diálogo con Ramificación
```json
{
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Decisión",
      "posicion": { "x": 200, "y": 0 }    // Celda (1,0)
    },
    {
      "id": "opcion_a",
      "titulo": "Opción A",
      "posicion": { "x": 0, "y": 200 }    // Celda (0,1)
    },
    {
      "id": "opcion_b", 
      "titulo": "Opción B",
      "posicion": { "x": 400, "y": 200 }  // Celda (2,1)
    }
  ]
}
```

**Resultado Visual:**
```
    [Opción A]    [Decisión]    [Opción B]
      (0,1)         (1,0)         (2,1)
         \            |            /
          \           |           /
           \          |          /
            \         |         /
             \        |        /
              \       |       /
               \      |      /
                \     |     /
                 \    |    /
                  \   |   /
                   \  |  /
                    \ | /
                     \|/
```

#### Ejemplo 3: Diálogo Complejo con Múltiples Niveles
```json
{
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "posicion": { "x": 0, "y": 0 }      // Celda (0,0)
    },
    {
      "id": "decision_1",
      "titulo": "Primera Decisión",
      "posicion": { "x": 200, "y": 0 }    // Celda (1,0)
    },
    {
      "id": "rama_a_1",
      "titulo": "Rama A - Paso 1",
      "posicion": { "x": 0, "y": 200 }    // Celda (0,1)
    },
    {
      "id": "rama_a_2",
      "titulo": "Rama A - Paso 2", 
      "posicion": { "x": 0, "y": 400 }    // Celda (0,2)
    },
    {
      "id": "rama_b_1",
      "titulo": "Rama B - Paso 1",
      "posicion": { "x": 400, "y": 200 }  // Celda (2,1)
    },
    {
      "id": "rama_b_2",
      "titulo": "Rama B - Paso 2",
      "posicion": { "x": 400, "y": 400 }  // Celda (2,2)
    },
    {
      "id": "convergencia",
      "titulo": "Punto de Convergencia",
      "posicion": { "x": 200, "y": 600 }  // Celda (1,3)
    }
  ]
}
```

**Resultado Visual:**
```
[Inicio] → [Primera Decisión]
(0,0)      (1,0)
             |
    +--------+--------+
    |                 |
    v                 v
[Rama A-1]         [Rama B-1]
(0,1)              (2,1)
    |                 |
    v                 v
[Rama A-2]         [Rama B-2]
(0,2)              (2,2)
    |                 |
    +--------+--------+
             |
             v
    [Convergencia]
         (1,3)
```

### Reglas de Posicionamiento

#### 1. Una Celda, Un Nodo
- **Regla**: Solo puede haber un nodo por celda
- **Validación**: El sistema previene automáticamente los solapamientos
- **Comportamiento**: Si intentas colocar un nodo en una celda ocupada, se busca la celda libre más cercana

#### 2. Coordenadas Válidas
- **Rango X**: 0 a (columnas × 200) - 200
- **Rango Y**: 0 a (filas × 200) - 200
- **Ejemplo**: Para un grid 5×50: x ∈ [0, 800], y ∈ [0, 9800]

#### 3. Alineación Automática
- **Snap automático**: Los nodos se "enganchan" automáticamente a las celdas
- **Precisión**: No necesitas calcular coordenadas exactas
- **Tolerancia**: El sistema encuentra la celda más cercana

### Estrategias de Organización

#### 1. Organización Horizontal (Recomendada)
```json
// Flujo de izquierda a derecha
"posicion": { "x": 0, "y": 0 }    // Inicio
"posicion": { "x": 200, "y": 0 }  // Desarrollo
"posicion": { "x": 400, "y": 0 }  // Decisión
"posicion": { "x": 600, "y": 0 }  // Final
```

#### 2. Organización Vertical
```json
// Flujo de arriba a abajo
"posicion": { "x": 0, "y": 0 }    // Inicio
"posicion": { "x": 0, "y": 200 }  // Desarrollo
"posicion": { "x": 0, "y": 400 }  // Decisión
"posicion": { "x": 0, "y": 600 }  // Final
```

#### 3. Organización en Árbol
```json
// Nodo central con ramas
"posicion": { "x": 200, "y": 0 }    // Nodo central
"posicion": { "x": 0, "y": 200 }    // Rama izquierda
"posicion": { "x": 400, "y": 200 }  // Rama derecha
"posicion": { "x": 200, "y": 400 }  // Convergencia
```

### Herramientas de Posicionamiento

#### 1. Calculadora de Coordenadas
```javascript
// Función para calcular coordenadas
function calcularPosicion(columna, fila) {
    return {
        x: columna * 200,
        y: fila * 200
    };
}

// Ejemplos
calcularPosicion(0, 0);  // { x: 0, y: 0 }
calcularPosicion(1, 0);  // { x: 200, y: 0 }
calcularPosicion(0, 1);  // { x: 0, y: 200 }
calcularPosicion(2, 3);  // { x: 400, y: 600 }
```

#### 2. Validador de Posiciones
```javascript
// Función para validar coordenadas
function validarPosicion(x, y, maxColumnas = 5, maxFilas = 50) {
    const columna = Math.floor(x / 200);
    const fila = Math.floor(y / 200);
    
    return {
        valida: columna >= 0 && columna < maxColumnas && 
                fila >= 0 && fila < maxFilas,
        columna: columna,
        fila: fila
    };
}
```

### Casos Especiales

#### 1. Diálogos Muy Grandes
Si necesitas más de 5 columnas o 50 filas:
- **Expandir grid**: El sistema se expande automáticamente
- **Scroll**: Usa scroll horizontal/vertical en el editor
- **Modularizar**: Considera dividir en múltiples diálogos

#### 2. Nodos Muy Cercanos
Para evitar confusión visual:
- **Espaciado mínimo**: Deja al menos una celda entre nodos relacionados
- **Agrupación**: Usa colores o estilos para agrupar nodos
- **Documentación**: Incluye comentarios en el JSON

#### 3. Conexiones Largas
Para conexiones entre nodos distantes:
- **Líneas multipuntos**: El sistema crea líneas rectas automáticamente
- **Puntos intermedios**: Se calculan automáticamente
- **Legibilidad**: Las etiquetas se posicionan en el punto medio

### Mejores Prácticas

#### 1. Planificación
- **Dibuja primero**: Haz un boceto del flujo antes de crear el JSON
- **Identifica nodos**: Marca todos los nodos y sus conexiones
- **Asigna coordenadas**: Planifica las posiciones antes de escribir

#### 2. Organización
- **Flujo lógico**: Sigue el flujo natural de izquierda a derecha
- **Agrupación**: Agrupa nodos relacionados en la misma fila
- **Espaciado**: Deja espacio para futuras expansiones

#### 3. Mantenimiento
- **Nomenclatura**: Usa IDs descriptivos para nodos
- **Comentarios**: Incluye comentarios en el JSON para explicar secciones
- **Versionado**: Mantén versiones del JSON para cambios importantes

### Ejemplo Completo con Posicionamiento

```json
{
  "dialogo": {
    "nombre": "Ejemplo de Posicionamiento",
    "descripcion": "Demostración del sistema de coordenadas y grid"
  },
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "contenido": "Punto de entrada del diálogo",
      "rol_nombre": "Sistema",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }      // Celda (0,0) - Esquina superior izquierda
    },
    {
      "id": "presentacion",
      "titulo": "Presentación",
      "contenido": "Se presenta el caso a resolver",
      "rol_nombre": "Fiscal",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }    // Celda (1,0) - Segunda columna
    },
    {
      "id": "decision_principal",
      "titulo": "Decisión Principal",
      "contenido": "¿Cuál es la estrategia a seguir?",
      "rol_nombre": "Juez",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 400, "y": 0 }    // Celda (2,0) - Tercera columna
    },
    {
      "id": "estrategia_a",
      "titulo": "Estrategia A",
      "contenido": "Se sigue la estrategia conservadora",
      "rol_nombre": "Defensor",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 200 }  // Celda (1,1) - Segunda fila, segunda columna
    },
    {
      "id": "estrategia_b",
      "titulo": "Estrategia B",
      "contenido": "Se sigue la estrategia agresiva",
      "rol_nombre": "Defensor",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": 200 }  // Celda (3,1) - Segunda fila, cuarta columna
    },
    {
      "id": "convergencia",
      "titulo": "Punto de Convergencia",
      "contenido": "Ambas estrategias convergen aquí",
      "rol_nombre": "Sistema",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 400, "y": 400 }  // Celda (2,2) - Tercera fila, tercera columna
    },
    {
      "id": "final_exitoso",
      "titulo": "Final Exitoso",
      "contenido": "El caso se resuelve exitosamente",
      "rol_nombre": "Juez",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 200, "y": 600 }  // Celda (1,3) - Cuarta fila, segunda columna
    },
    {
      "id": "final_alternativo",
      "titulo": "Final Alternativo",
      "contenido": "El caso requiere más investigación",
      "rol_nombre": "Juez",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 600, "y": 600 }  // Celda (3,3) - Cuarta fila, cuarta columna
    }
  ],
  "conexiones": [
    {
      "desde": "inicio",
      "hacia": "presentacion",
      "texto": "Iniciar presentación",
      "color": "#007bff",
      "puntuacion": 0
    },
    {
      "desde": "presentacion",
      "hacia": "decision_principal",
      "texto": "Proceder a decisión",
      "color": "#28a745",
      "puntuacion": 5
    },
    {
      "desde": "decision_principal",
      "hacia": "estrategia_a",
      "texto": "Elegir estrategia conservadora",
      "color": "#ffc107",
      "puntuacion": 10
    },
    {
      "desde": "decision_principal",
      "hacia": "estrategia_b",
      "texto": "Elegir estrategia agresiva",
      "color": "#dc3545",
      "puntuacion": 15
    },
    {
      "desde": "estrategia_a",
      "hacia": "convergencia",
      "texto": "Continuar con estrategia A",
      "color": "#6f42c1",
      "puntuacion": 0
    },
    {
      "desde": "estrategia_b",
      "hacia": "convergencia",
      "texto": "Continuar con estrategia B",
      "color": "#6f42c1",
      "puntuacion": 0
    },
    {
      "desde": "convergencia",
      "hacia": "final_exitoso",
      "texto": "Caso resuelto",
      "color": "#28a745",
      "puntuacion": 20
    },
    {
      "desde": "convergencia",
      "hacia": "final_alternativo",
      "texto": "Requiere más investigación",
      "color": "#dc3545",
      "puntuacion": 5
    }
  ]
}
```

**Resultado Visual del Ejemplo:**
```
[Inicio] → [Presentación] → [Decisión Principal]
(0,0)      (1,0)            (2,0)
                              |
                    +---------+---------+
                    |                   |
                    v                   v
            [Estrategia A]         [Estrategia B]
                (1,1)                  (3,1)
                    |                   |
                    +---------+---------+
                              |
                              v
                    [Convergencia]
                         (2,2)
                              |
                    +---------+---------+
                    |                   |
                    v                   v
            [Final Exitoso]      [Final Alternativo]
                (1,3)                  (3,3)
```

Este sistema de coordenadas y grid te permite crear diálogos complejos y bien organizados de manera intuitiva y mantenible.

### Diagramas de Flujo Específicos por Tipo de Simulación

#### Simulación de Juicio Civil - Flujo Contractual
```
[Demanda] → [Lectura] → [Respuesta] → [Pruebas] → [Sentencia]
(0,0)      (1,0)       (2,0)        (3,0)      (4,0)
             |
             v
        [Mediación]
           (1,1)
             |
             v
        [Acuerdo]
           (1,2)
```

#### Simulación de Juicio Penal - Flujo Acusatorio
```
[Inicio] → [Cargos] → [Declaración] → [Pruebas] → [Veredicto]
(0,0)     (1,0)      (2,0)          (3,0)       (4,0)
           |           |
           v           v
      [Defensa]   [Testigos]
        (1,1)       (2,1)
           |           |
           v           v
      [Contrainterrogatorio]
           (1,2)
```

#### Simulación de Entrevista - Flujo de Evaluación
```
[Saludo] → [Pregunta 1] → [Pregunta 2] → [Pregunta 3] → [Decisión]
(0,0)     (1,0)         (2,0)         (3,0)         (4,0)
           |             |             |
           v             v             v
      [Respuesta A] [Respuesta B] [Respuesta C]
        (1,1)         (2,1)         (3,1)
           |             |             |
           v             v             v
      [Evaluación] → [Puntuación] → [Resultado]
        (1,2)         (2,2)         (3,2)
```

### Patrones de Diseño Recomendados

#### 1. Patrón Lineal (Para procesos secuenciales)
```json
// Secuencia simple: A → B → C → D
"posicion": { "x": 0, "y": 0 }    // A
"posicion": { "x": 200, "y": 0 }  // B  
"posicion": { "x": 400, "y": 0 }  // C
"posicion": { "x": 600, "y": 0 }  // D
```

#### 2. Patrón de Decisión (Para puntos de elección)
```json
// Decisión con dos opciones
"posicion": { "x": 200, "y": 0 }    // Decisión central
"posicion": { "x": 0, "y": 200 }    // Opción A (izquierda)
"posicion": { "x": 400, "y": 200 }  // Opción B (derecha)
```

#### 3. Patrón de Convergencia (Para múltiples rutas)
```json
// Múltiples rutas que convergen
"posicion": { "x": 0, "y": 0 }    // Inicio
"posicion": { "x": 200, "y": 0 }  // Decisión
"posicion": { "x": 0, "y": 200 }  // Ruta A
"posicion": { "x": 400, "y": 200 } // Ruta B
"posicion": { "x": 200, "y": 400 } // Convergencia
```

#### 4. Patrón de Evaluación (Para sistemas de puntuación)
```json
// Evaluación con múltiples criterios
"posicion": { "x": 0, "y": 0 }    // Inicio
"posicion": { "x": 200, "y": 0 }  // Criterio 1
"posicion": { "x": 400, "y": 0 }  // Criterio 2
"posicion": { "x": 600, "y": 0 }  // Criterio 3
"posicion": { "x": 400, "y": 200 } // Evaluación final
```

### Herramientas de Planificación Visual

#### 1. Plantilla de Boceto
```
Usa esta plantilla para planificar tu diálogo:

    0    1    2    3    4
0  [ ]  [ ]  [ ]  [ ]  [ ]
1  [ ]  [ ]  [ ]  [ ]  [ ]
2  [ ]  [ ]  [ ]  [ ]  [ ]
3  [ ]  [ ]  [ ]  [ ]  [ ]
4  [ ]  [ ]  [ ]  [ ]  [ ]

Leyenda:
[ ] = Nodo vacío
[A] = Nodo de inicio
[D] = Nodo de decisión
[F] = Nodo final
```

#### 2. Calculadora de Espaciado
```javascript
// Función para calcular espaciado automático
function calcularEspaciado(numeroNodos, ancho = 5) {
    const posiciones = [];
    for (let i = 0; i < numeroNodos; i++) {
        const columna = i % ancho;
        const fila = Math.floor(i / ancho);
        posiciones.push({
            x: columna * 200,
            y: fila * 200
        });
    }
    return posiciones;
}

// Ejemplo: 8 nodos en grid 5x5
calcularEspaciado(8, 5);
// Resultado: 8 posiciones distribuidas automáticamente
```

#### 3. Validador de Flujo
```javascript
// Función para validar que el flujo sea lógico
function validarFlujo(nodos, conexiones) {
    const errores = [];
    
    // Verificar que hay un nodo inicial
    const nodosIniciales = nodos.filter(n => n.es_inicial);
    if (nodosIniciales.length !== 1) {
        errores.push("Debe haber exactamente un nodo inicial");
    }
    
    // Verificar que hay al menos un nodo final
    const nodosFinales = nodos.filter(n => n.es_final);
    if (nodosFinales.length === 0) {
        errores.push("Debe haber al menos un nodo final");
    }
    
    // Verificar que todas las conexiones son válidas
    conexiones.forEach(conexion => {
        const nodoOrigen = nodos.find(n => n.id === conexion.desde);
        const nodoDestino = nodos.find(n => n.id === conexion.hacia);
        
        if (!nodoOrigen) {
            errores.push(`Nodo origen no encontrado: ${conexion.desde}`);
        }
        if (!nodoDestino) {
            errores.push(`Nodo destino no encontrado: ${conexion.hacia}`);
        }
    });
    
    return errores;
}
```

### Casos de Uso Específicos por Coordenadas

#### Simulación de Juicio Civil - Coordenadas Específicas
```json
{
  "nodos": [
    {
      "id": "demanda",
      "titulo": "Presentación de Demanda",
      "posicion": { "x": 0, "y": 0 }      // Esquina superior izquierda
    },
    {
      "id": "notificacion",
      "titulo": "Notificación al Demandado",
      "posicion": { "x": 200, "y": 0 }    // Segunda columna
    },
    {
      "id": "contestacion",
      "titulo": "Contestación de Demanda",
      "posicion": { "x": 400, "y": 0 }    // Tercera columna
    },
    {
      "id": "audiencia_preliminar",
      "titulo": "Audiencia Preliminar",
      "posicion": { "x": 200, "y": 200 }  // Segunda fila, segunda columna
    },
    {
      "id": "pruebas",
      "titulo": "Período de Pruebas",
      "posicion": { "x": 400, "y": 200 }  // Segunda fila, tercera columna
    },
    {
      "id": "alegatos",
      "titulo": "Alegatos de Clausura",
      "posicion": { "x": 200, "y": 400 }  // Tercera fila, segunda columna
    },
    {
      "id": "sentencia",
      "titulo": "Sentencia",
      "posicion": { "x": 400, "y": 400 }  // Tercera fila, tercera columna
    }
  ]
}
```

#### Simulación de Juicio Penal - Coordenadas Específicas
```json
{
  "nodos": [
    {
      "id": "inicio_juicio",
      "titulo": "Inicio del Juicio",
      "posicion": { "x": 0, "y": 0 }      // Inicio
    },
    {
      "id": "lectura_cargos",
      "titulo": "Lectura de Cargos",
      "posicion": { "x": 200, "y": 0 }    // Lectura
    },
    {
      "id": "declaracion_fiscal",
      "titulo": "Declaración del Fiscal",
      "posicion": { "x": 400, "y": 0 }    // Fiscal
    },
    {
      "id": "declaracion_defensa",
      "titulo": "Declaración de la Defensa",
      "posicion": { "x": 600, "y": 0 }    // Defensa
    },
    {
      "id": "testigos_fiscal",
      "titulo": "Testigos del Fiscal",
      "posicion": { "x": 200, "y": 200 }  // Testigos fiscal
    },
    {
      "id": "testigos_defensa",
      "titulo": "Testigos de la Defensa",
      "posicion": { "x": 600, "y": 200 }  // Testigos defensa
    },
    {
      "id": "alegatos_finales",
      "titulo": "Alegatos Finales",
      "posicion": { "x": 400, "y": 400 }  // Alegatos
    },
    {
      "id": "veredicto",
      "titulo": "Veredicto del Jurado",
      "posicion": { "x": 400, "y": 600 }  // Veredicto
    }
  ]
}
```

### Optimización de Espacio

#### 1. Uso Eficiente del Grid
- **Densidad óptima**: 60-80% de celdas ocupadas
- **Espaciado mínimo**: 1 celda entre nodos relacionados
- **Agrupación lógica**: Nodos relacionados en la misma fila

#### 2. Patrones de Reutilización
```json
// Patrón reutilizable para decisiones binarias
{
  "patron_decision": {
    "nodo_central": { "x": 200, "y": 0 },
    "opcion_izquierda": { "x": 0, "y": 200 },
    "opcion_derecha": { "x": 400, "y": 200 }
  }
}
```

#### 3. Escalabilidad
- **Módulos**: Dividir diálogos grandes en módulos
- **Referencias**: Reutilizar patrones comunes
- **Jerarquía**: Organizar por niveles de complejidad

## 🎭 Gestión de Roles

### Roles Automáticos
Si un rol no existe, se creará automáticamente con:
- **Nombre**: El especificado en `rol_nombre`
- **Descripción**: "Rol importado automáticamente"
- **Color**: `#007bff` (azul)
- **Icono**: `bi-person`
- **Estado**: Activo

### Roles Predefinidos Recomendados
- `"Juez"` - Preside la audiencia
- `"Fiscal"` - Representa la acusación
- `"Defensor"` - Representa la defensa
- `"Testigo"` - Persona que declara
- `"Sistema"` - Mensajes del sistema
- `"Usuario"` - Interacciones del usuario

## 📋 Ejemplos Completos

### Ejemplo 1: Juicio Civil - Caso de Contrato

```json
{
  "dialogo": {
    "nombre": "Juicio Civil - Caso de Contrato",
    "descripcion": "Simulación de un juicio civil por incumplimiento de contrato",
    "publico": true
  },
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio de la Audiencia",
      "contenido": "Bienvenidos a la audiencia del caso #2024-001. Procederemos con la lectura de la demanda.",
      "rol_nombre": "Juez",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    },
    {
      "id": "lectura_demanda",
      "titulo": "Lectura de la Demanda",
      "contenido": "Se procede a la lectura de la demanda presentada por el demandante contra el demandado por incumplimiento de contrato.",
      "rol_nombre": "Fiscal",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }
    },
    {
      "id": "decision_defensa",
      "titulo": "Respuesta de la Defensa",
      "contenido": "¿Cómo responde la defensa a la demanda?",
      "rol_nombre": "Defensor",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 400, "y": 0 }
    },
    {
      "id": "acepta_demanda",
      "titulo": "Acepta la Demanda",
      "contenido": "El demandado acepta los términos de la demanda y se procede a la sentencia.",
      "rol_nombre": "Defensor",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 600, "y": -100 }
    },
    {
      "id": "rechaza_demanda",
      "titulo": "Rechaza la Demanda",
      "contenido": "El demandado rechaza la demanda y se procede a la presentación de pruebas.",
      "rol_nombre": "Defensor",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": 100 }
    }
  ],
  "conexiones": [
    {
      "desde": "inicio",
      "hacia": "lectura_demanda",
      "texto": "Proceder con la lectura",
      "color": "#28a745",
      "puntuacion": 5
    },
    {
      "desde": "lectura_demanda",
      "hacia": "decision_defensa",
      "texto": "Solicitar respuesta de la defensa",
      "color": "#007bff",
      "puntuacion": 0
    },
    {
      "desde": "decision_defensa",
      "hacia": "acepta_demanda",
      "texto": "Aceptar la demanda",
      "color": "#28a745",
      "puntuacion": 10
    },
    {
      "desde": "decision_defensa",
      "hacia": "rechaza_demanda",
      "texto": "Rechazar la demanda",
      "color": "#dc3545",
      "puntuacion": 5
    }
  ]
}
```

### Ejemplo 2: Juicio Penal - Declaración de Testigos

```json
{
  "dialogo": {
    "nombre": "Juicio Penal - Declaración de Testigos",
    "descripcion": "Simulación de un juicio penal con declaración de múltiples testigos",
    "publico": true
  },
  "nodos": [
    {
      "id": "inicio_juicio",
      "titulo": "Inicio del Juicio Penal",
      "contenido": "Se inicia la audiencia del caso penal #2024-PEN-001. Se procederá con la declaración de testigos.",
      "instrucciones": "El juez debe mantener el orden y la solemnidad del acto",
      "rol_nombre": "Juez",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    },
    {
      "id": "presentacion_cargos",
      "titulo": "Presentación de Cargos",
      "contenido": "El fiscal presenta los cargos contra el acusado por el delito de robo agravado.",
      "rol_nombre": "Fiscal",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }
    },
    {
      "id": "decision_testigo",
      "titulo": "Selección de Testigo",
      "contenido": "¿Qué testigo desea interrogar primero?",
      "rol_nombre": "Sistema",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 400, "y": 0 }
    },
    {
      "id": "testigo_victima",
      "titulo": "Declaración de la Víctima",
      "contenido": "La víctima declara sobre los hechos ocurridos el día del robo.",
      "instrucciones": "El testigo debe responder con claridad y precisión",
      "rol_nombre": "Testigo",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": -100 }
    },
    {
      "id": "testigo_policia",
      "titulo": "Declaración del Policía",
      "contenido": "El oficial de policía declara sobre la investigación y arresto del sospechoso.",
      "rol_nombre": "Testigo",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": 100 }
    },
    {
      "id": "decision_final",
      "titulo": "Decisión Final",
      "contenido": "¿Cuál es la decisión del jurado?",
      "rol_nombre": "Sistema",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 800, "y": 0 }
    },
    {
      "id": "culpable",
      "titulo": "Veredicto: Culpable",
      "contenido": "El jurado declara al acusado culpable. Se procede a la sentencia.",
      "rol_nombre": "Juez",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 1000, "y": -100 }
    },
    {
      "id": "inocente",
      "titulo": "Veredicto: Inocente",
      "contenido": "El jurado declara al acusado inocente. Se ordena su liberación inmediata.",
      "rol_nombre": "Juez",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 1000, "y": 100 }
    }
  ],
  "conexiones": [
    {
      "desde": "inicio_juicio",
      "hacia": "presentacion_cargos",
      "texto": "Proceder con la presentación",
      "color": "#007bff",
      "puntuacion": 5
    },
    {
      "desde": "presentacion_cargos",
      "hacia": "decision_testigo",
      "texto": "Iniciar declaraciones",
      "color": "#28a745",
      "puntuacion": 0
    },
    {
      "desde": "decision_testigo",
      "hacia": "testigo_victima",
      "texto": "Interrogar a la víctima",
      "color": "#dc3545",
      "puntuacion": 10
    },
    {
      "desde": "decision_testigo",
      "hacia": "testigo_policia",
      "texto": "Interrogar al policía",
      "color": "#ffc107",
      "puntuacion": 8
    },
    {
      "desde": "testigo_victima",
      "hacia": "decision_final",
      "texto": "Continuar con la decisión",
      "color": "#6f42c1",
      "puntuacion": 0
    },
    {
      "desde": "testigo_policia",
      "hacia": "decision_final",
      "texto": "Continuar con la decisión",
      "color": "#6f42c1",
      "puntuacion": 0
    },
    {
      "desde": "decision_final",
      "hacia": "culpable",
      "texto": "Declarar culpable",
      "color": "#dc3545",
      "puntuacion": 15
    },
    {
      "desde": "decision_final",
      "hacia": "inocente",
      "texto": "Declarar inocente",
      "color": "#28a745",
      "puntuacion": 20
    }
  ]
}
```

### Ejemplo 3: Diálogo Simple - Entrevista

```json
{
  "dialogo": {
    "nombre": "Entrevista de Trabajo",
    "descripcion": "Simulación de una entrevista de trabajo con múltiples preguntas",
    "publico": false
  },
  "nodos": [
    {
      "id": "saludo",
      "titulo": "Saludo Inicial",
      "contenido": "Bienvenido a nuestra empresa. Gracias por venir a la entrevista.",
      "rol_nombre": "Entrevistador",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    },
    {
      "id": "pregunta_experiencia",
      "titulo": "Pregunta sobre Experiencia",
      "contenido": "Cuénteme sobre su experiencia laboral anterior.",
      "rol_nombre": "Entrevistador",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }
    },
    {
      "id": "respuesta_experiencia",
      "titulo": "Respuesta del Candidato",
      "contenido": "¿Cómo responde el candidato sobre su experiencia?",
      "rol_nombre": "Candidato",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 400, "y": 0 }
    },
    {
      "id": "respuesta_excelente",
      "titulo": "Respuesta Excelente",
      "contenido": "El candidato proporciona una respuesta detallada y convincente sobre su experiencia.",
      "rol_nombre": "Candidato",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": -100 }
    },
    {
      "id": "respuesta_basica",
      "titulo": "Respuesta Básica",
      "contenido": "El candidato da una respuesta básica sin muchos detalles.",
      "rol_nombre": "Candidato",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 600, "y": 100 }
    },
    {
      "id": "final_contratado",
      "titulo": "Contratado",
      "contenido": "Excelente. Nos pondremos en contacto contigo para ofrecerte el puesto.",
      "rol_nombre": "Entrevistador",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 800, "y": -100 }
    },
    {
      "id": "final_rechazado",
      "titulo": "No Contratado",
      "contenido": "Gracias por tu tiempo. Consideraremos tu candidatura.",
      "rol_nombre": "Entrevistador",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 800, "y": 100 }
    }
  ],
  "conexiones": [
    {
      "desde": "saludo",
      "hacia": "pregunta_experiencia",
      "texto": "Iniciar entrevista",
      "color": "#007bff",
      "puntuacion": 0
    },
    {
      "desde": "pregunta_experiencia",
      "hacia": "respuesta_experiencia",
      "texto": "Esperar respuesta",
      "color": "#6c757d",
      "puntuacion": 0
    },
    {
      "desde": "respuesta_experiencia",
      "hacia": "respuesta_excelente",
      "texto": "Dar respuesta detallada",
      "color": "#28a745",
      "puntuacion": 15
    },
    {
      "desde": "respuesta_experiencia",
      "hacia": "respuesta_basica",
      "texto": "Dar respuesta básica",
      "color": "#ffc107",
      "puntuacion": 5
    },
    {
      "desde": "respuesta_excelente",
      "hacia": "final_contratado",
      "texto": "Continuar proceso",
      "color": "#28a745",
      "puntuacion": 10
    },
    {
      "desde": "respuesta_basica",
      "hacia": "final_rechazado",
      "texto": "Terminar entrevista",
      "color": "#dc3545",
      "puntuacion": 0
    }
  ]
}
```

## ✅ Validaciones del Sistema

### Validaciones de Diálogo
- ✅ Nombre es requerido y único
- ✅ Descripción es requerida
- ✅ Al menos un nodo debe existir

### Validaciones de Nodos
- ✅ ID único en todo el diálogo
- ✅ Título no vacío
- ✅ Contenido no vacío
- ✅ Rol debe existir o ser creable
- ✅ Tipo debe ser válido
- ✅ Exactamente un nodo inicial
- ✅ Al menos un nodo final

### Validaciones de Conexiones
- ✅ Nodos origen y destino deben existir
- ✅ No se permiten auto-conexiones
- ✅ No se permiten conexiones duplicadas

## 🚀 Mejores Prácticas

### 1. Nomenclatura
- **IDs de nodos**: Usar nombres descriptivos (`nodo_inicio`, `nodo_decision_1`)
- **Títulos**: Ser conciso pero descriptivo
- **Roles**: Usar nombres consistentes

### 2. Estructura
- **Flujo lógico**: Asegurar que el flujo tenga sentido
- **Nodos finales**: Cada rama debe terminar en un nodo final
- **Posicionamiento**: Organizar nodos de izquierda a derecha

### 3. Contenido
- **Claridad**: Usar lenguaje claro y profesional
- **Instrucciones**: Incluir instrucciones para roles complejos
- **Consistencia**: Mantener tono y estilo consistente

### 4. Conexiones
- **Colores**: Usar colores que representen el tipo de acción
- **Puntuaciones**: Asignar puntuaciones significativas
- **Textos**: Usar textos descriptivos para las conexiones

## 🔧 Herramientas de Desarrollo

### Plantillas Disponibles
1. **Diálogo Básico**: Estructura simple con inicio y fin
2. **Diálogo Complejo**: Múltiples ramificaciones y decisiones
3. **Simulación de Juicio**: Estructura específica para juicios

### Plantillas Descargables

#### Plantilla Básica (3 nodos)
```json
{
  "dialogo": {
    "nombre": "Diálogo Básico",
    "descripcion": "Estructura simple con inicio, desarrollo y fin",
    "publico": false
  },
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "contenido": "Bienvenido al diálogo",
      "rol_nombre": "Sistema",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    },
    {
      "id": "desarrollo",
      "titulo": "Desarrollo",
      "contenido": "Contenido del diálogo",
      "rol_nombre": "Usuario",
      "tipo": "desarrollo",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }
    },
    {
      "id": "fin",
      "titulo": "Fin",
      "contenido": "Fin del diálogo",
      "rol_nombre": "Sistema",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 400, "y": 0 }
    }
  ],
  "conexiones": [
    {
      "desde": "inicio",
      "hacia": "desarrollo",
      "texto": "Continuar",
      "color": "#007bff",
      "puntuacion": 0
    },
    {
      "desde": "desarrollo",
      "hacia": "fin",
      "texto": "Finalizar",
      "color": "#28a745",
      "puntuacion": 10
    }
  ]
}
```

#### Plantilla de Decisión (5 nodos)
```json
{
  "dialogo": {
    "nombre": "Diálogo con Decisión",
    "descripcion": "Estructura con punto de decisión y múltiples finales",
    "publico": false
  },
  "nodos": [
    {
      "id": "inicio",
      "titulo": "Inicio",
      "contenido": "Bienvenido. Debe tomar una decisión importante.",
      "rol_nombre": "Sistema",
      "tipo": "inicio",
      "es_inicial": true,
      "es_final": false,
      "posicion": { "x": 0, "y": 0 }
    },
    {
      "id": "decision",
      "titulo": "Punto de Decisión",
      "contenido": "¿Qué opción prefiere?",
      "rol_nombre": "Usuario",
      "tipo": "decision",
      "es_inicial": false,
      "es_final": false,
      "posicion": { "x": 200, "y": 0 }
    },
    {
      "id": "opcion_a",
      "titulo": "Opción A",
      "contenido": "Ha elegido la opción A. Esta es una buena decisión.",
      "rol_nombre": "Sistema",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 400, "y": -100 }
    },
    {
      "id": "opcion_b",
      "titulo": "Opción B",
      "contenido": "Ha elegido la opción B. Esta es una decisión arriesgada.",
      "rol_nombre": "Sistema",
      "tipo": "final",
      "es_inicial": false,
      "es_final": true,
      "posicion": { "x": 400, "y": 100 }
    }
  ],
  "conexiones": [
    {
      "desde": "inicio",
      "hacia": "decision",
      "texto": "Proceder",
      "color": "#007bff",
      "puntuacion": 0
    },
    {
      "desde": "decision",
      "hacia": "opcion_a",
      "texto": "Elegir Opción A",
      "color": "#28a745",
      "puntuacion": 15
    },
    {
      "desde": "decision",
      "hacia": "opcion_b",
      "texto": "Elegir Opción B",
      "color": "#ffc107",
      "puntuacion": 5
    }
  ]
}
```

### Validación Online
- Usar la vista previa en la interfaz de importación
- Verificar estructura antes de importar
- Probar con diálogos pequeños primero

### Herramientas Recomendadas

#### Editores de JSON
- **Visual Studio Code** - Con extensión JSON
- **Sublime Text** - Con plugin JSONLint
- **Atom** - Con paquete language-json
- **Online**: jsoneditoronline.org

#### Validadores
- **JSONLint** - Validación de sintaxis
- **JSON Schema Validator** - Validación de estructura
- **Herramienta integrada** - Vista previa en la interfaz

#### Generadores de Plantillas
- **Herramienta de importación** - Plantillas descargables
- **Scripts personalizados** - Para generar JSONs masivos
- **Templates de código** - Para integración con otros sistemas

## 🎯 Casos de Uso Específicos

### Simulacros de Juicios Civiles
- **Contratos**: Incumplimiento, interpretación, rescisión
- **Daños y Perjuicios**: Responsabilidad civil, indemnizaciones
- **Familia**: Divorcios, custodia, pensión alimenticia
- **Laboral**: Despidos, acoso, discriminación

### Simulacros de Juicios Penales
- **Delitos Menores**: Robo, fraude, lesiones
- **Delitos Graves**: Homicidio, secuestro, narcotráfico
- **Procedimientos**: Audiencias de vinculación, juicios orales
- **Recursos**: Apelaciones, amparos

### Entrevistas y Evaluaciones
- **Recursos Humanos**: Entrevistas de trabajo, evaluaciones
- **Educativas**: Exámenes orales, presentaciones
- **Médicas**: Consultas, diagnósticos, tratamientos
- **Psicológicas**: Evaluaciones, terapias

## 🔧 Troubleshooting

### Errores Comunes

#### Error: "The string did not match the expected pattern"
**Causa**: JSON mal formado con cadenas incompletas o caracteres especiales sin escapar
**Solución**: 
- Verificar que todas las cadenas estén cerradas con comillas dobles
- Escapar comillas internas con `\"`
- Usar herramientas de validación JSON online

**Ejemplo de error:**
```json
❌ Incorrecto:
"descripcion": "Simulación completa de un juicio oral penal por robo a comercio, con salidas alternas, testigos (vícti

✅ Correcto:
"descripcion": "Simulación completa de un juicio oral penal por robo a comercio, con salidas alternas, testigos (víctima, policía, testigo presencial) y diferentes desenlaces según las decisiones del usuario."
```

#### Error: "Unexpected end of JSON input"
**Causa**: JSON incompleto, faltan llaves, corchetes o comillas de cierre
**Solución**: 
- Verificar que todas las llaves `{}` y corchetes `[]` estén cerrados
- Contar las comillas para asegurar que estén balanceadas
- Usar un editor con resaltado de sintaxis

#### Error: "Unexpected token"
**Causa**: Token inesperado, generalmente comas mal colocadas o caracteres inválidos
**Solución**:
- Verificar que las comas estén solo entre elementos, no al final
- Asegurar que no haya caracteres especiales sin escapar
- Revisar la sintaxis de arrays y objetos

#### Error: "ID de nodo duplicado"
**Causa**: Dos nodos tienen el mismo `id`
**Solución**: Asegurar que cada nodo tenga un ID único

#### Error: "Nodo inicial no encontrado"
**Causa**: No hay ningún nodo con `es_inicial: true`
**Solución**: Definir exactamente un nodo inicial

#### Error: "Conexión a nodo inexistente"
**Causa**: Una conexión referencia un nodo que no existe
**Solución**: Verificar que todos los IDs en `desde` y `hacia` existan

#### Error: "Rol no encontrado"
**Causa**: El `rol_nombre` no existe y no se puede crear
**Solución**: Usar roles existentes o verificar permisos

### Herramientas de Diagnóstico

#### 1. Validador JSON Online
```bash
# Herramientas recomendadas:
- jsonlint.com
- jsonformatter.org
- jsonformatter.curiousconcept.com
```

#### 2. Validación en Consola del Navegador
```javascript
// Pegar tu JSON en la consola del navegador
try {
    const jsonData = JSON.parse(tu_json_aqui);
    console.log('✅ JSON válido:', jsonData);
} catch (error) {
    console.error('❌ Error JSON:', error.message);
}
```

#### 3. Verificación de Estructura
```javascript
// Función para verificar estructura específica
function verificarEstructuraDialogo(jsonData) {
    const errores = [];
    
    // Verificar secciones principales
    if (!jsonData.dialogo) errores.push('Falta sección "dialogo"');
    if (!jsonData.nodos) errores.push('Falta sección "nodos"');
    if (!jsonData.conexiones) errores.push('Falta sección "conexiones"');
    
    // Verificar nodos
    if (jsonData.nodos) {
        jsonData.nodos.forEach((nodo, i) => {
            if (!nodo.id) errores.push(`Nodo ${i+1}: Falta "id"`);
            if (!nodo.contenido) errores.push(`Nodo ${i+1}: Falta "contenido"`);
            if (!nodo.tipo) errores.push(`Nodo ${i+1}: Falta "tipo"`);
            if (!nodo.posicion) errores.push(`Nodo ${i+1}: Falta "posicion"`);
        });
    }
    
    return errores;
}
```

### Casos Específicos de Error

#### 1. Cadenas Multilínea
❌ **Problema:**
```json
"descripcion": "Esta es una descripción muy larga
que se extiende por múltiples líneas
sin cerrar las comillas correctamente"
```

✅ **Solución:**
```json
"descripcion": "Esta es una descripción muy larga que se extiende por múltiples líneas pero está correctamente cerrada en una sola línea"
```

#### 2. Caracteres Especiales
❌ **Problema:**
```json
"contenido": "El juez dice: "Procedamos con la audiencia""
```

✅ **Solución:**
```json
"contenido": "El juez dice: \"Procedamos con la audiencia\""
```

#### 3. Comas Trailing
❌ **Problema:**
```json
{
  "nodos": [
    {
      "id": "nodo1",
      "contenido": "Contenido",
    },
  ]
}
```

✅ **Solución:**
```json
{
  "nodos": [
    {
      "id": "nodo1",
      "contenido": "Contenido"
    }
  ]
}
```

#### 4. Tipos de Datos Incorrectos
❌ **Problema:**
```json
{
  "posicion": {
    "x": "0",    // String en lugar de número
    "y": "0"     // String en lugar de número
  }
}
```

✅ **Solución:**
```json
{
  "posicion": {
    "x": 0,      // Número
    "y": 0       // Número
  }
}
```

### Validaciones Recomendadas

#### Antes de Importar
1. **Validar JSON**: Usar un validador online
2. **Revisar IDs**: Verificar que sean únicos
3. **Comprobar flujo**: Asegurar que cada rama termine en un nodo final
4. **Verificar posiciones**: Coordenadas dentro del grid

#### Después de Importar
1. **Revisar estructura**: Verificar nodos y conexiones
2. **Probar flujo**: Ejecutar el diálogo completo
3. **Ajustar posiciones**: Mover nodos si es necesario
4. **Verificar roles**: Confirmar que los roles se crearon correctamente

## 📊 Estadísticas y Métricas

### Métricas de Diálogo
- **Número de nodos**: Total de nodos en el diálogo
- **Número de conexiones**: Total de conexiones entre nodos
- **Complejidad**: Número de decisiones y ramificaciones
- **Longitud promedio**: Número promedio de nodos por ruta

### Métricas de Rendimiento
- **Tiempo de importación**: Duración del proceso de importación
- **Tamaño del archivo**: Peso del archivo JSON
- **Memoria utilizada**: Recursos consumidos durante la importación

## 🚀 Optimizaciones

### Para Diálogos Grandes
- **Dividir en módulos**: Separar diálogos complejos en partes
- **Usar referencias**: Reutilizar nodos comunes
- **Optimizar posiciones**: Usar coordenadas eficientes
- **Minimizar conexiones**: Reducir conexiones redundantes

### Para Rendimiento
- **Archivos pequeños**: Mantener JSONs bajo 1MB
- **Estructura simple**: Evitar anidaciones complejas
- **IDs cortos**: Usar identificadores concisos
- **Validación previa**: Verificar antes de importar

## 📚 Recursos Adicionales

### Documentación Relacionada
- **Manual del Usuario**: Guía de uso del sistema
- **API Reference**: Documentación de endpoints
- **Guía de Roles**: Gestión de roles y permisos
- **Tutoriales**: Videos y guías paso a paso

### Comunidad
- **Foro de Usuarios**: Preguntas y respuestas
- **GitHub**: Código fuente y issues
- **Discord**: Chat en tiempo real
- **Email**: Soporte técnico directo

## 📞 Soporte

Para dudas o problemas con el formato JSON:
- Revisar esta documentación
- Usar las plantillas de ejemplo
- Consultar la sección de troubleshooting
- Contactar al administrador del sistema

### Información de Contacto
- **Email**: soporte@simulador-juicios.com
- **Teléfono**: +52 (33) 1587 2645
- **Horario**: Lunes a Viernes, 9:00 - 18:00
- **Respuesta**: Máximo 24 horas

---

**Versión**: 1.0  
**Última actualización**: Octubre 2025  
**Compatibilidad**: Sistema de Diálogos v2.0+  
**Autor**: Miguel Orozco
**Licencia**: MIT
