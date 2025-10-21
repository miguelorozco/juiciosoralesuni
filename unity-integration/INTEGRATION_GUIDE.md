# Guía de Integración Unity + Laravel

## 🎯 Resumen

Esta guía te ayudará a integrar completamente el sistema de Laravel con Unity WebGL, incluyendo Photon PUN2 para multiplayer y PeerJS para comunicación de voz.

## 📋 Prerrequisitos

### Laravel
- PHP 8.2+
- Laravel 12
- JWT Authentication (tymon/jwt-auth)
- Composer

### Unity
- Unity 2022.3.15f1 o superior
- Photon PUN2 (gratuito)
- WebGL Build Support

### Servicios Externos
- Photon Cloud (cuenta gratuita)
- Servidor PeerJS (opcional, se puede usar el público)

## 🚀 Pasos de Integración

### 1. Configurar Laravel

#### Instalar dependencias
```bash
cd /var/www/juicios_local
composer install
```

#### Configurar JWT
```bash
php artisan jwt:secret
```

#### Ejecutar migraciones
```bash
php artisan migrate
```

#### Crear usuario de prueba
```bash
php artisan tinker
>>> User::create(['name' => 'Alumno', 'apellido' => 'Test', 'email' => 'alumno@example.com', 'password' => bcrypt('password'), 'tipo' => 'alumno', 'activo' => true]);
```

### 2. Configurar Unity

#### Instalar Photon PUN2
1. Abrir Unity Hub
2. Crear nuevo proyecto 2D/3D
3. Ir a Window > Package Manager
4. Buscar "PUN2" e instalar
5. Configurar App ID en PhotonServerSettings

#### Configurar Scripts
1. Copiar todos los scripts de `/unity-integration/unity-project/Assets/Scripts/` a tu proyecto Unity
2. Crear un GameObject vacío y agregar `GameInitializer`
3. Configurar las referencias en el inspector

#### Configurar Build Settings
1. File > Build Settings
2. Seleccionar WebGL
3. Player Settings:
   - Company Name: Tu empresa
   - Product Name: Simulador de Juicios Orales
   - WebGL Template: Custom (usar el template incluido)

### 3. Configurar Photon PUN2

#### Obtener App ID
1. Ir a [Photon Engine](https://www.photonengine.com/)
2. Crear cuenta gratuita
3. Crear nueva aplicación
4. Copiar App ID

#### Configurar en Unity
1. Ir a Window > Photon Unity Networking > PUN Wizard
2. Pegar App ID
3. Configurar región (us)

### 4. Configurar PeerJS

#### Usar servidor público (recomendado para desarrollo)
No se requiere configuración adicional.

#### Usar servidor propio (recomendado para producción)
1. Instalar PeerJS Server
2. Configurar en `unity-config.json`

### 5. Configurar CORS en Laravel

El archivo `config/cors.php` ya está configurado para Unity WebGL.

### 6. Probar Integración

#### Desde Laravel
1. Iniciar servidor: `php artisan serve`
2. Ir a `http://localhost:8000/sesiones/1/activa`
3. Hacer clic en "Entrar a Sala Virtual 3D"

#### Desde Unity
1. Abrir proyecto Unity
2. Presionar Play
3. Verificar logs en Console

## 🔧 Configuración Avanzada

### Variables de Entorno

#### Laravel (.env)
```env
APP_URL=http://localhost:8000
JWT_SECRET=tu_jwt_secret
PHOTON_APP_ID=tu_photon_app_id
PEERJS_HOST=juiciosorales.site
PEERJS_PORT=443
```

#### Unity (unity-config.json)
```json
{
  "api": {
    "baseURL": "http://localhost:8000/api"
  },
  "photon": {
    "appId": "tu_photon_app_id"
  }
}
```

### Personalización

#### Cambiar URL de API
1. Editar `UnityConfig.cs`
2. O modificar `unity-config.json`

#### Cambiar configuración de audio
1. Editar `UnityConfig.cs`
2. Modificar valores en `GetAudioConfig()`

#### Cambiar configuración de Photon
1. Editar `UnityConfig.cs`
2. Modificar valores en `GetPhotonConfig()`

## 🐛 Solución de Problemas

### Error de CORS
- Verificar `config/cors.php`
- Asegurar que la URL de Unity esté en `allowed_origins`

### Error de JWT
- Verificar que el token se esté enviando correctamente
- Revisar logs de Laravel: `tail -f storage/logs/laravel.log`

### Error de Photon
- Verificar App ID
- Revisar conexión a internet
- Verificar región configurada

### Error de PeerJS
- Verificar que el micrófono esté habilitado
- Revisar consola del navegador
- Verificar configuración de STUN servers

### Unity no se conecta a Laravel
- Verificar que Laravel esté corriendo
- Revisar URL en `LaravelAPI.cs`
- Verificar logs de Unity Console

## 📊 Monitoreo

### Logs de Laravel
```bash
tail -f storage/logs/laravel.log
```

### Logs de Unity
- Abrir Developer Tools en el navegador
- Ir a Console
- Verificar mensajes de debug

### Métricas de Photon
- Ir a [Photon Dashboard](https://dashboard.photonengine.com/)
- Revisar métricas de conexión

## 🚀 Despliegue

### Desarrollo
```bash
# Laravel
php artisan serve

# Unity
# Abrir en Unity Editor y presionar Play
```

### Producción
```bash
# Build de Unity
./build-unity.sh production

# Desplegar Laravel
# Seguir guía de despliegue de Laravel
```

## 📚 Recursos Adicionales

- [Documentación de Photon PUN2](https://doc.photonengine.com/pun2)
- [Documentación de PeerJS](https://peerjs.com/docs/)
- [Documentación de Laravel](https://laravel.com/docs)
- [Documentación de Unity WebGL](https://docs.unity3d.com/Manual/webgl.html)

## 🤝 Soporte

Si encuentras problemas:

1. Revisar logs de Laravel y Unity
2. Verificar configuración de CORS
3. Verificar que todos los servicios estén corriendo
4. Revisar esta guía paso a paso

## 📝 Notas Importantes

- Unity WebGL solo funciona en navegadores modernos
- PeerJS requiere HTTPS en producción
- Photon PUN2 tiene límites en la versión gratuita
- JWT tokens expiran, implementar refresh automático
