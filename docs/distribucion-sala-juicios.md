# Distribución de Avatares en la Sala de Juicios Orales

## 📊 Configuración Completada

Se han posicionado los 20 avatares en la escena Unity siguiendo las reglas de distribución de una sala de juicios orales real.

## 🎯 Mapa de Posiciones

```
                        FONDO DE LA SALA (Público)
        ┌────────────────────────────────────────────────────────┐
        │    Periodista   Publico1   Publico2   Observador      │  Z = -52
        │      (-4)         (-2)        (2)         (4)          │
        └────────────────────────────────────────────────────────┘

                            CUSTODIA
        ┌────────────────────────────────────────────────────────┐
        │              Policia1          Policia2                │  Z = -45
        │                (-2)              (2)                   │
        └────────────────────────────────────────────────────────┘

                         ESTRADO DEL JUEZ (Elevado Y=5.3)
        ┌────────────────────────────────────────────────────────┐
        │                                                        │
        │           Secretario (2.5)    Juez (0)               │  Z = -42
        │                                                        │
        └────────────────────────────────────────────────────────┘
                            (Elevado)

        ┌───────────────────┐    CENTRO    ┌──────────────────┐
        │   MESA DERECHA    │   (Podium)   │   MESA IZQUIERDA │  Z = -38
        │                   │     (0)      │                  │
        └───────────────────┘              └──────────────────┘

              MESAS DE LAS PARTES (Mirando al Juez)
        ┌────────────────────────────────────────────────────────┐
        │  FISCAL Y ACUSACIÓN              DEFENSA Y ACUSADO     │  Z = -34.5
        │                                                        │
        │  Fiscal    Abogado1  Acusador  Victima   |  Abogado2  Acusado  Defensa │
        │   (6)       (5)        (2)      (3)      |   (-7)      (-5)     (-6)   │
        │                                          |                               │
        │  [Rotación: 180° - Mirando al Juez]     |  [Rotación: 180°]            │
        └────────────────────────────────────────────────────────┘

                    ÁREA DE ESPERA (Lado Izquierdo)
        ┌─────────────┐
        │  Testigo1   │  Z = -30  [Rotación: 90° - Mirando al centro]
        │  Testigo2   │  Z = -32
        │  Perito1    │  Z = -34
        │  Perito2    │  Z = -36
        │  Psicologo  │  Z = -38
        │             │
        │  X = -10    │
        └─────────────┘

                        FRENTE DE LA SALA
```

## 📍 Coordenadas Exactas (CORREGIDAS según estructura real)

### **ESTRADO DEL JUEZ** (Elevado - Y = 4.16, Z = -43.5)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Juez** | (0.037, 4.16, -43.5) | 180° | Centro del estrado elevado, mirando hacia el público |
| **Secretario** | (2.5, 4.16, -43.5) | 180° | A la derecha del juez, registra las actuaciones |

---

### **MESA DEL FISCAL Y VÍCTIMA** (Mesas frontales, mirando al juez)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Fiscal** | (6, 3.1, -34) | 0° | Mesa derecha - Ministerio Público |
| **Abogado1** | (3, 3.1, -34) | 0° | Mesa centro-derecha - Auxiliar del fiscal |

---

### **MESA DE LA DEFENSA Y ACUSADO** (Mesas frontales, mirando al juez)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Defensa** | (-6, 3.1, -34) | 0° | Mesa izquierda - Abogado defensor |
| **Acusado** | (-3, 3.1, -34) | 0° | Mesa centro-izquierda - Junto a su defensor |

---

### **ESTRADO - SILLAS ELEVADAS** (Auxiliares del tribunal, Y = 3.55, Z = -42.336)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Víctima** | (5, 3.55, -42.336) | 180° | Estrado derecho - Mirando al frente |
| **Abogado2** | (7.5, 3.55, -42.336) | 180° | Estrado derecho extremo - Segundo abogado |
| **Acusador** | (-5, 3.55, -42.336) | 180° | Estrado izquierdo - Acusador particular |
| **Policía1** | (-7.5, 3.55, -42.336) | 180° | Estrado izquierdo extremo - Custodia |

---

### **ÁREA DE TESTIGOS/PERITOS** (Lateral derecho, X = 10 y 12)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Testigo1** | (10, 3.1, -32) | 270° | Primera fila lateral - Esperando turno |
| **Testigo2** | (10, 3.1, -34) | 270° | Primera fila lateral - Esperando turno |
| **Perito1** | (10, 3.1, -36) | 270° | Primera fila lateral - Esperando turno |
| **Perito2** | (12, 3.1, -33) | 270° | Segunda fila lateral - Esperando turno |
| **Psicólogo** | (12, 3.1, -35) | 270° | Segunda fila lateral - Esperando turno |

> **NOTA**: Los testigos y peritos están sentados en sillas laterales. Cuando declaren, se moverán al **Podium central (0, 3.1, -38)**.

---

### **CUSTODIA Y SEGURIDAD**
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Policía2** | (2.5, 3.1, -45) | 0° | Área de custodia - Derecha del podium |

---

### **ÁREA PÚBLICA** (Fondo de la sala, Z = -51 y -54)
| Rol | Posición (X, Y, Z) | Rotación | Descripción |
|-----|-------------------|----------|-------------|
| **Público1** | (2, 3.1, -51) | 0° | Primera fila público - Derecha |
| **Público2** | (0, 3.1, -51) | 0° | Primera fila público - Centro |
| **Periodista** | (-2, 3.1, -51) | 0° | Primera fila público - Izquierda (prensa) |
| **Observador** | (3, 3.1, -54) | 180° | Segunda fila público - Observador neutral |

---

## 🎭 Roles y Funciones

### **ROLES FIJOS** (No cambian de posición durante el juicio)

1. **Juez** - Autoridad máxima, dirige el juicio, toma decisiones
2. **Secretario** - Registra las actuaciones y administra documentos
3. **Fiscal** - Representa al Ministerio Público, acusa
4. **Defensa** - Defiende al acusado
5. **Acusado** - Persona acusada del delito, permanece junto a su defensor
6. **Víctima** - Víctima del delito, permanece en su mesa
7. **Acusador** - Acusador particular (si existe)
8. **Abogados adicionales** - Apoyan a fiscal o defensa
9. **Policías** - Custodian la sala y mantienen el orden
10. **Público** - Observan el juicio desde sus asientos

### **ROLES MÓVILES** (Cambian de posición temporalmente)

**Testigos y Peritos:**
- **Posición Base**: Área de espera lateral (X = -10, Z = -30 a -38)
- **Cuando Declaran**: Se mueven al Podium central (0, 3.1, -38)
- **Rotación al Declarar**: 180° (mirando al juez)
- **Después de Declarar**: Regresan a su posición de espera

**Incluye:**
- Testigo1, Testigo2
- Perito1, Perito2
- Psicólogo

---

## 🔄 Flujo de Movimiento

```
┌──────────────────────────────────────────────────────────────┐
│                  FLUJO DE DECLARACIÓN                         │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Testigo/Perito espera en área lateral (X = -10)        │
│                        ↓                                     │
│  2. Juez autoriza la declaración                            │
│                        ↓                                     │
│  3. Testigo/Perito se desplaza al Podium (0, 3.1, -38)    │
│                        ↓                                     │
│  4. Rota 180° para mirar al Juez                           │
│                        ↓                                     │
│  5. Declara ante el tribunal                                │
│                        ↓                                     │
│  6. Regresa a su posición de espera lateral                │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 📸 Screenshots Capturados

Se han generado 4 capturas de la escena desde diferentes ángulos:

1. **scene_overview_01.png** - Vista general inicial
2. **sala_juicios_configurada_01.png** - Vista configurada final
3. **Vista lateral** - Ángulo lateral (-15, 8, -35) con rotación (20°, 45°, 0°)
4. **Vista superior** - Cenital (0, 15, -30) con rotación (60°, 180°, 0°)
5. **Vista frontal** - Desde el público (0, 5, -50) con rotación (10°, 0°, 0°)

**Ubicación**: `Assets/Screenshots/`

---

## ✅ Verificación Completada

- [x] 20 avatares posicionados
- [x] Distribución según reglas de sala real
- [x] Rotaciones correctas (mirando al juez)
- [x] Áreas diferenciadas (estrado, mesas, espera, público)
- [x] Escena guardada en `Assets/Scenes/main.unity`
- [x] Screenshots capturados

---

## 🎮 Configuración en Unity

**Archivo de Escena**: `Assets/Scenes/main.unity`

**Jerarquía**:
```
- Main Camera
- Directional Light
- Players (contenedor)
  ├─ Player_Juez
  ├─ Player_Secretario
  ├─ Player_Fiscal
  ├─ Player_Abogado1
  ├─ Player_Defensa
  ├─ Player_Acusado
  ├─ Player_Abogado2
  ├─ Player_Victima
  ├─ Player_Acusador
  ├─ Player_Testigo1
  ├─ Player_Testigo2
  ├─ Player_Perito1
  ├─ Player_Perito2
  ├─ Player_Psicologo
  ├─ Player_Policia1
  ├─ Player_Policia2
  ├─ Player_Publico1
  ├─ Player_Publico2
  ├─ Player_Periodista
  └─ Player_Observador
- GameManagers
  ├─ DebugUIManager
  └─ PhotonNetworkManager
- Structure_03 (Sala de Juicios)
  ├─ Exterior
  └─ Interior
```

---

## 🔧 Próximos Pasos Recomendados

1. **Sistema de Movimiento de Testigos**:
   - Implementar script para mover testigos/peritos al podium cuando sea su turno
   - Implementar cola de turnos
   - Animación de desplazamiento

2. **Asignación Automática de Roles**:
   - Al conectarse, el jugador recibe su rol asignado
   - El sistema posiciona automáticamente al jugador en su lugar

3. **Restricciones de Movimiento**:
   - Los roles fijos no pueden salir de su área
   - Los testigos solo se mueven cuando el juez lo autoriza

4. **Cámaras Dinámicas**:
   - Cámara del juez (vista desde el estrado)
   - Cámara del testigo (cuando declara)
   - Cámara del público (vista general)

5. **Etiquetas de Rol**:
   - Mostrar el nombre del rol sobre cada avatar
   - Usar el componente `RoleLabelDisplay` existente

---

**Fecha de Configuración**: 2 de Febrero, 2026  
**Configurado por**: MCP Unity Tools  
**Estado**: ✅ Listo para Testing
