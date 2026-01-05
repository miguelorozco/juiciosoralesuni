# 📋 Plan de Eliminación de Código Antiguo - FASE 0.5.6

**Fecha**: Enero 2025  
**Objetivo**: Eliminar modelos, controladores y código antiguo del sistema de diálogos v1

---

## 🔍 Archivos Identificados que Usan Modelos Antiguos

### 1. Modelos a Eliminar
- ✅ `app/Models/Dialogo.php`
- ✅ `app/Models/NodoDialogo.php`
- ✅ `app/Models/RespuestaDialogo.php`
- ✅ `app/Models/SesionDialogo.php`
- ✅ `app/Models/DecisionSesion.php`

### 2. Controladores a Eliminar/Refactorizar
- ⚠️ `app/Http/Controllers/DialogoController.php` - **Usa Dialogo**
- ⚠️ `app/Http/Controllers/NodoDialogoController.php` - **Usa NodoDialogo, RespuestaDialogo**
- ⚠️ `app/Http/Controllers/DialogoFlujoController.php` - **Usa modelos antiguos**
- ⚠️ `app/Http/Controllers/DialogoImportController.php` - **Usa modelos antiguos**
- ⚠️ `app/Http/Controllers/UnityDialogoController.php` - **Usa SesionDialogo**
- ⚠️ `app/Http/Controllers/SesionController.php` - **Usa Dialogo, SesionDialogo**

### 3. Seeders a Actualizar
- ⚠️ `database/seeders/DialogoJuicioPenalSeeder.php` - **Usa Dialogo, NodoDialogo, RespuestaDialogo**
- ⚠️ `database/seeders/RolesDialogoSeeder.php` - **Usa Dialogo**
- ⚠️ `database/seeders/DialogoEjemploSeeder.php` - **Usa modelos antiguos**

### 4. Otros Archivos
- ⚠️ `routes/web.php` - **Usa Dialogo en vista**
- ⚠️ `app/Services/ProcesamientoAutomaticoService.php` - **Usa DecisionSesion**
- ⚠️ `app/Models/SesionJuicio.php` - **Relación con SesionDialogo**
- ⚠️ `app/Models/RolDialogo.php` - **Relación con Dialogo**
- ⚠️ `app/Models/AsignacionRol.php` - **Relación con RolDialogo**

### 5. Scripts de Análisis
- ⚠️ `database/scripts/analizar-datos-dialogos.php` - **Usa todos los modelos antiguos**

---

## 📝 Plan de Acción

### Paso 1: Actualizar Relaciones en Modelos Relacionados
1. Actualizar `SesionJuicio` para usar `SesionDialogoV2`
2. Actualizar `RolDialogo` para usar `DialogoV2` (si es necesario mantener)
3. Actualizar `AsignacionRol` si es necesario

### Paso 2: Actualizar Controladores
1. **DialogoController**: Refactorizar para usar `DialogoV2` o marcar como deprecated
2. **UnityDialogoController**: Refactorizar para usar `SesionDialogoV2`
3. **SesionController**: Actualizar referencias a `DialogoV2` y `SesionDialogoV2`
4. **NodoDialogoController**: Refactorizar o eliminar
5. **DialogoFlujoController**: Refactorizar o eliminar
6. **DialogoImportController**: Refactorizar o eliminar

### Paso 3: Actualizar Seeders
1. Actualizar seeders para usar modelos v2
2. O marcar como deprecated si no se usarán

### Paso 4: Actualizar Rutas
1. Actualizar `routes/api.php` para usar nuevos controladores
2. Actualizar `routes/web.php` para usar `DialogoV2`

### Paso 5: Actualizar Servicios
1. Actualizar `ProcesamientoAutomaticoService` para usar `DecisionDialogoV2`

### Paso 6: Eliminar Modelos Antiguos
1. Eliminar `app/Models/Dialogo.php`
2. Eliminar `app/Models/NodoDialogo.php`
3. Eliminar `app/Models/RespuestaDialogo.php`
4. Eliminar `app/Models/SesionDialogo.php`
5. Eliminar `app/Models/DecisionSesion.php`

### Paso 7: Eliminar Controladores Antiguos (si no se refactorizan)
1. Eliminar controladores que no se puedan refactorizar

### Paso 8: Actualizar Scripts
1. Actualizar `analizar-datos-dialogos.php` para usar modelos v2 o eliminarlo

---

## ⚠️ Consideraciones

1. **Compatibilidad temporal**: Algunos controladores pueden necesitar mantenerse temporalmente para compatibilidad con Unity
2. **Rutas API**: Las rutas antiguas pueden necesitar mantenerse con redirección a nuevas
3. **Seeders**: Los seeders antiguos pueden mantenerse para referencia histórica
4. **Scripts de análisis**: Pueden mantenerse para análisis de datos antiguos

---

## ✅ Checklist

- [x] Actualizar relaciones en modelos relacionados
  - [x] SesionJuicio: Actualizado para usar SesionDialogoV2
  - [x] RolDialogo: Actualizado para usar DialogoV2
- [ ] Refactorizar/eliminar controladores
- [ ] Actualizar seeders
- [ ] Actualizar rutas
- [x] Actualizar servicios
  - [x] ProcesamientoAutomaticoService: Actualizado para usar modelos v2
- [x] Eliminar modelos antiguos
  - [x] Dialogo.php eliminado
  - [x] NodoDialogo.php eliminado
  - [x] RespuestaDialogo.php eliminado
  - [x] SesionDialogo.php eliminado
  - [x] DecisionSesion.php eliminado
- [ ] Eliminar controladores antiguos (si aplica)
- [ ] Actualizar scripts
- [ ] Verificar que no haya referencias rotas
- [ ] Actualizar documentación
