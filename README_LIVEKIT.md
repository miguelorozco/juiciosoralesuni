# Juicios Orales UNI - LiveKit Edition

Este proyecto es un fork de **juiciosorales** que utiliza **LiveKit (SFU)** y **coturn (TURN/STUN)** en lugar de PeerJS para la comunicación en tiempo real.

## 🆕 Diferencias con el Proyecto Original

### Tecnología de Comunicación

| Aspecto | juiciosorales (Original) | juiciosoralesuni (Este proyecto) |
|---------|--------------------------|----------------------------------|
| **Arquitectura** | P2P (Peer-to-Peer) | SFU (Selective Forwarding Unit) |
| **Biblioteca** | PeerJS | LiveKit |
| **TURN/STUN** | Servidor PeerJS integrado | coturn dedicado |
| **Escalabilidad** | Limitada (P2P) | Alta (SFU) |
| **Latencia** | Baja en conexiones directas | Consistente y predecible |
| **NAT Traversal** | Básico | Robusto con coturn |

## 📋 Requisitos Previos

### Software Base
- PHP 8.2+
- Composer
- Node.js 18+ y npm
- Apache 2.4+
- SQLite o MySQL

### Software Adicional (LiveKit)
- **LiveKit Server** - [Instalación](https://docs.livekit.io/home/self-hosting/local/)
  ```bash
  curl -sSL https://get.livekit.io | bash
  ```

- **coturn** - TURN/STUN server
  ```bash
  sudo apt-get install coturn  # Ubuntu/Debian
  ```

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
cd ~/Documents/github
git clone git@github.com:miguelorozco/juiciosoralesuni.git
cd juiciosoralesuni
```

### 2. Instalar Dependencias de Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Configurar Variables de Entorno

Edita `.env` y configura:

```env
# LiveKit Configuration
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
LIVEKIT_HOST=ws://localhost:7880
LIVEKIT_HTTP_URL=http://localhost:7880

# coturn (TURN/STUN) Configuration
COTURN_HOST=localhost
COTURN_PORT=3478
COTURN_USERNAME=usuario_turn
COTURN_PASSWORD=password_turn
COTURN_REALM=juiciosoralesuni
```

### 4. Configurar Base de Datos

```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

### 5. Instalar Dependencias de Node.js

```bash
npm install
npm run build
```

## 🎮 Uso

### Iniciar Servicios

#### 1. Iniciar LiveKit + coturn

```bash
./start-livekit.sh
```

Este script:
- Verifica que LiveKit y coturn estén instalados
- Crea configuraciones necesarias
- Inicia ambos servicios en background
- Muestra el estado de los servicios

#### 2. Iniciar Laravel

```bash
php artisan serve
```

O usar el script de desarrollo:

```bash
composer dev
```

### Detener Servicios

```bash
./stop-livekit.sh
```

## 🔄 Cambiar entre Proyectos

Para facilitar el desarrollo en ambos proyectos, usa el script de switch:

```bash
# Desde ~/Documents/github
./switch-project.sh juiciosoralesuni  # Cambiar a este proyecto (LiveKit)
./switch-project.sh juiciosorales     # Cambiar al proyecto original (PeerJS)
```

El script automáticamente:
- Cambia la configuración de Apache
- Reinicia Apache
- Muestra información del proyecto activo
- Indica qué servicios necesitas iniciar

## 📚 Arquitectura LiveKit

### Flujo de Conexión

1. **Cliente solicita token**: Unity/Web solicita un token JWT a Laravel
2. **Laravel genera token**: Usando LiveKit SDK con credenciales configuradas
3. **Cliente se conecta**: Usa el token para conectarse al servidor LiveKit
4. **SFU maneja streams**: LiveKit distribuye audio/video entre participantes
5. **coturn ayuda NAT**: Facilita conexiones cuando P2P no es posible

### Endpoints API

#### Obtener Token para Sala

```http
POST /api/livekit/token
Authorization: Bearer {JWT_TOKEN}

{
  "room_name": "juicio-room-123",
  "participant_name": "Juan Pérez",
  "participant_identity": "user_123"
}
```

**Respuesta:**

```json
{
  "token": "eyJhbGc...",
  "url": "ws://localhost:7880",
  "room_name": "juicio-room-123",
  "participant_identity": "user_123",
  "coturn": {
    "urls": [
      "stun:localhost:3478",
      "turn:localhost:3478"
    ],
    "username": "usuario_turn",
    "credential": "password_turn"
  }
}
```

## 🔧 Configuración

### LiveKit (`livekit.yaml`)

Configuración generada automáticamente por `start-livekit.sh`:

```yaml
port: 7880
bind_addresses:
  - 0.0.0.0

rtc:
  port_range_start: 50000
  port_range_end: 60000

keys:
  devkey: secret

room:
  auto_create: true
  empty_timeout: 600
  max_participants: 50
```

### coturn (`coturn.conf`)

Configuración para el servidor TURN/STUN:

```conf
listening-port=3478
realm=juiciosoralesuni
verbose
fingerprint
lt-cred-mech
```

## 🎯 Integración con Unity

### Cliente LiveKit en Unity

Deberás agregar el SDK de LiveKit para Unity:

```csharp
using LiveKit;

public class LiveKitManager : MonoBehaviour
{
    private Room room;
    
    async void ConnectToRoom(string token, string url)
    {
        room = new Room();
        
        await room.Connect(url, token);
        
        // Publicar audio/video
        await room.LocalParticipant.PublishAudioTrack();
        await room.LocalParticipant.PublishVideoTrack();
    }
}
```

## 📊 Ventajas de LiveKit sobre PeerJS

### ✅ Ventajas

1. **Escalabilidad**: SFU puede manejar más participantes eficientemente
2. **Calidad Consistente**: Control centralizado de calidad de streams
3. **NAT Traversal Robusto**: coturn maneja casos complejos de firewall
4. **Monitoreo**: Mejor observabilidad del estado de las conexiones
5. **Grabación**: Soporte nativo para grabar sesiones
6. **Simulcast**: Envío de múltiples calidades simultáneamente

### ⚠️ Consideraciones

1. **Infraestructura**: Requiere servidor LiveKit y coturn
2. **Complejidad**: Más componentes que mantener
3. **Latencia**: Ligeramente mayor que P2P directo en redes ideales
4. **Costos**: Servidor SFU consume más recursos que signaling P2P

## 🐛 Troubleshooting

### LiveKit no inicia

```bash
# Ver logs
tail -f livekit.log

# Verificar puerto disponible
sudo netstat -tlnp | grep 7880
```

### coturn no inicia

```bash
# Ver logs
tail -f coturn.log
sudo tail -f /var/log/coturn/coturn.log

# Verificar puerto disponible
sudo netstat -tlnp | grep 3478
```

### Error al generar tokens

Verifica que las credenciales en `.env` coincidan con `livekit.yaml`:

```bash
# En .env
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret

# En livekit.yaml
keys:
  devkey: secret
```

## 📖 Documentación Adicional

- [LiveKit Documentation](https://docs.livekit.io/)
- [coturn Documentation](https://github.com/coturn/coturn/wiki)
- [LiveKit Unity SDK](https://github.com/livekit/client-sdk-unity)

## 🤝 Contribución

Este proyecto es un fork experimental. Para contribuir al proyecto original, visita [juiciosorales](https://github.com/miguelorozco/juiciosorales).

## 📝 Licencia

MIT - Ver proyecto original para detalles completos.

---

**Nota**: Este proyecto está en desarrollo activo. La integración completa con Unity está en progreso.
