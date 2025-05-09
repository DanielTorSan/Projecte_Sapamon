# SAPAMON

## 📝 Descripción
Sapamon es una aplicación web inspirada en Pokémon que permite a los usuarios crear equipos personalizados, luchar contra otros jugadores y gestionar amistades. La aplicación integra la famosa [PokeAPI](https://pokeapi.co/) para obtener datos oficiales de los Pokémon, movimientos, tipos y características.

## 🌟 Características Principales

### 🎮 Sistema de combate Pokémon
- Combates en tiempo real entre jugadores
- Sistema de turnos
- Efectos visuales para ataques y cambios de estado
- Historial de acciones durante la batalla
- Modo espectador para ver combates de otros usuarios

### 👥 Gestión de amistades
- Sistema de búsqueda de usuarios
- Envío y recepción de solicitudes de amistad
- Lista de amigos activos
- Historial de combates con amigos

### 💭 Sistema de chat
- Chat en tiempo real con amigos
- Notificaciones de mensajes nuevos
- Historial de conversaciones

### 🎨 Personalización de avatar
- Selección de avatar personalizado para tu perfil

### 🔐 Sistema de autenticación
- Registro y login de usuarios
- Recuperación de contraseña por correo electrónico
- Integración con Google Auth

## 🔧 APIs Integradas

### 📊 PokeAPI
La aplicación utiliza la [PokeAPI](https://pokeapi.co/) para obtener datos oficiales de Pokémon:

- **Sistema de caché**: Implementamos un sistema de caché en base de datos para evitar solicitudes repetidas a la API, mejorando la velocidad y respetando los límites de uso
- **Datos utilizados**:
  - Información básica de Pokémon (nombre, tipos, estadísticas)
  - Sprites oficiales (integración con el repositorio de sprites oficial)
  - Movimientos y sus características (poder, precisión, tipo, categoría)
  - Datos de tipos para calcular efectividad en combate

Ejemplo de uso de PokeAPI en nuestro código:
```php
// Obtener datos de un Pokémon específico
$pokeAPIService = new PokeAPIService($conexion);
$pokemonData = $pokeAPIService->getPokemon($pokemonId);

// Obtener sprite del Pokémon
$sprite = $pokemonData['sprites']['front_default'];

// Obtener tipos
$tipos = array_map(function($tipo) {
    return $tipo['type']['name'];
}, $pokemonData['types']);
```

### 🥊 APIs Propias
La aplicación cuenta con varias APIs internas para gestionar diferentes funcionalidades:

#### 🏆 API de Combates (batalla_api.php & combat_api.php)
- Gestión del estado de batallas en tiempo real
- Sistema de turnos
- Cálculo de daño según tipo de ataque y defensas
- Cambio de Pokémon durante el combate

#### 👨‍👩‍👧‍👦 API de Amigos (gestiona_amistat.php & friend_action.php)
- Gestión de solicitudes de amistad
- Búsqueda de usuarios
- Listado de amigos activos

#### 💬 API de Chat (chat_api.php)
- Envío y recepción de mensajes
- Notificaciones de mensajes no leídos
- Historial de conversaciones

#### 🛠️ API de Equipos (gestio_equips.php)
- Creación y gestión de equipos Pokémon
- Asignación de movimientos a Pokémon
- Validación de equipos para combate

## 🚀 Estado del Desarrollo

### ✅ Funcionalidades Implementadas
- Sistema de autenticación completo
- Gestión de equipos Pokémon
- Sistema de combate básico
- Gestión de amistades
- Chat entre usuarios
- Personalización de avatar

### 🔄 En Desarrollo
- Mejoras en el sistema de combate (efectos visuales avanzados)
- Implementación de objetos durante el combate
- Ranking de batallas
- Torneos con múltiples participantes
- Sistema de logros y recompensas

### 📋 Próximas Características
- Sistema de comercio de Pokémon entre usuarios
- Eventos temporales con Pokémon especiales
- Modo historia con entrenadores IA
- Personalización avanzada de perfil

## 🧰 Tecnologías Utilizadas
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Backend: PHP
- Base de datos: MySQL
- Autenticación: Sistema propio + Google Auth
- API externa: PokeAPI

## 📦 Estructura del Proyecto
- **Controlador/**: Archivos PHP que gestionan la lógica de negocio
  - **js/**: Scripts para el frontend
  - **funcions_combat/**: Funciones específicas para el sistema de combate
- **Model/**: Archivos PHP para interactuar con la base de datos
- **Vista/**: Archivos de vistas y assets
- **libs/**: Librerías externas (PHPMailer, etc.)
- **logs/**: Archivos de log para depuración

## 🔍 Características Especiales

### Sistema de Caché para PokeAPI
Para optimizar el rendimiento y respetar los límites de la API, implementamos un sistema de caché que almacena localmente las respuestas de la API:

```php
// Verificar si existe en caché antes de hacer petición
$cached_data = $this->getFromCache($cache_key);
if ($cached_data !== false) {
    return $cached_data;
}

// Si no está en caché, hacer petición y guardar resultado
$url = $this->base_url . $endpoint;
$response = $this->makeApiRequest($url);
if ($response !== false) {
    $this->saveToCache($cache_key, $response);
}
```

### Sistema de Combate en Tiempo Real
El sistema de combate actualiza el estado de la batalla periódicamente mediante llamadas AJAX:

```javascript
// Iniciar actualización periódica
function iniciarActualizacionPeriodica() {
    intervaloActualizacion = setInterval(() => {
        actualizarEstadoBatalla();
    }, 2000); // Actualizar cada 2 segundos
}
```

## 🤝 Contribución
¿Quieres contribuir al proyecto? ¡Genial! Puedes ayudar de varias formas:
- Reportando bugs
- Sugiriendo nuevas características
- Mejorando la documentación
- Contribuyendo con código

## 📄 Licencia
Este proyecto está bajo la Licencia MIT.

## 👥 Autores
- [Tu nombre y equipo]

---

© 2025 Sapamon. Todos los derechos reservados.
