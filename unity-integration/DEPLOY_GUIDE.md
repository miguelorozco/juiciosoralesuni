# 🚀 Guía de Deploy de Unity

Esta guía te explica cómo hacer deploy del build de Unity al servidor usando FTP.

## 📋 Prerrequisitos

### En tu máquina local:
- ✅ Unity 2022.3.15f1 o superior
- ✅ Proyecto Unity configurado
- ✅ `lftp` instalado

### Instalar lftp:
```bash
# Ubuntu/Debian
sudo apt install lftp

# macOS
brew install lftp

# Windows (con Chocolatey)
choco install lftp
```

## 🔧 Configuración

### 1. Actualizar configuración de Unity
```bash
# Desde el directorio unity-integration/
./update-unity-config.sh
```

Este script actualiza:
- ✅ URL del servidor: `https://juiciosorales.site/api`
- ✅ Environment: `production`
- ✅ Debug mode: `false`

### 2. Configurar Photon App ID
Edita el archivo `unity-project/Assets/StreamingAssets/unity-config.json`:
```json
{
  "laravelApiBaseUrl": "https://juiciosorales.site/api",
  "photonAppId": "2ec23c58-5cc4-419d-8214-13abad14a02f",
  "environment": "production"
}
```

## 🎮 Flujo de trabajo

### 1. Desarrollo en Unity
1. Abrir Unity Hub
2. Abrir proyecto: `unity-integration/unity-project/`
3. Hacer cambios en el código
4. Probar en el editor

### 2. Build de Unity
1. En Unity: **File > Build Settings**
2. Seleccionar **WebGL**
3. Hacer clic en **Build**
4. Seleccionar carpeta: `builds/webgl/`

### 3. Deploy al servidor
```bash
# Desde tu máquina local
cd unity-integration/
./deploy-unity-local.sh builds/webgl/
```

## 📁 Estructura de archivos

```
unity-integration/
├── unity-project/              # Código fuente Unity
├── builds/                     # Builds compilados (ignorados por git)
│   └── webgl/                  # Build WebGL
├── deploy-unity-local.sh       # Script de deploy (local)
├── update-unity-config.sh      # Script de configuración
└── DEPLOY_GUIDE.md            # Esta guía
```

## 🛠️ Scripts disponibles

### `deploy-unity-local.sh`
Script principal para hacer deploy desde tu máquina local.

**Uso:**
```bash
./deploy-unity-local.sh [carpeta-build]
```

**Ejemplos:**
```bash
# Usar carpeta por defecto (builds/webgl/)
./deploy-unity-local.sh

# Especificar carpeta
./deploy-unity-local.sh builds/webgl/

# Usar ruta absoluta
./deploy-unity-local.sh /path/to/unity/build/
```

### `update-unity-config.sh`
Actualiza la configuración de Unity para producción.

**Uso:**
```bash
./update-unity-config.sh
```

## 🔍 Verificación

### 1. Verificar archivos subidos
```bash
# Conectar al servidor via FTP
lftp ftp://187.218.232.139
user simulador
pass soporte25$
cd /var/www/juicios_local/unity-integration/builds/
ls -la
```

### 2. Verificar en navegador
```
https://juiciosorales.site/unity-integration/builds/
```

## 🚨 Solución de problemas

### Error: "lftp no está instalado"
```bash
# Ubuntu/Debian
sudo apt install lftp

# macOS
brew install lftp
```

### Error: "Carpeta de build no existe"
- Verifica que hayas hecho el build de Unity
- Verifica la ruta de la carpeta
- Usa ruta absoluta si es necesario

### Error: "Error durante el deploy"
- Verifica las credenciales FTP
- Verifica la conectividad al servidor
- Verifica que el servidor FTP esté funcionando

### Error: "Carpeta de build está vacía"
- Verifica que el build de Unity se completó correctamente
- Verifica que hay archivos en la carpeta de build

## 📞 Soporte

Si tienes problemas:
1. Verifica los logs del script
2. Verifica la conectividad FTP
3. Verifica la configuración de Unity
4. Contacta al administrador del servidor

## 🎯 Próximos pasos

1. ✅ Configurar Photon App ID
2. ✅ Hacer build de Unity
3. ✅ Deploy al servidor
4. ✅ Probar en navegador
5. ✅ Configurar servidor web para archivos estáticos
