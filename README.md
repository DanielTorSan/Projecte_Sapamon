# SAPAMON

## 📝 Descripció
Sapamon és una aplicació web inspirada en Pokémon que permet als usuaris crear equips personalitzats, lluitar contra altres jugadors i gestionar amistats. L'aplicació integra la famosa [PokeAPI](https://pokeapi.co/) per obtenir dades oficials dels Pokémon, moviments, tipus i característiques.

## 🌟 Característiques Principals

### 🎮 Sistema de combat Pokémon
- Combats en temps real entre jugadors
- Sistema de torns
- Efectes visuals per atacs i canvis d'estat
- Historial d'accions durant la batalla
- Mode espectador per veure combats d'altres usuaris

### 👥 Gestió d'amistats
- Sistema de cerca d'usuaris
- Enviament i recepció de sol·licituds d'amistat
- Llista d'amics actius
- Historial de combats amb amics

### 💭 Sistema de xat
- Xat en temps real amb amics
- Notificacions de missatges nous
- Historial de converses

### 🎨 Personalització d'avatar
- Selecció d'avatar personalitzat pel teu perfil

### 🔐 Sistema d'autenticació
- Registre i login d'usuaris
- Recuperació de contrasenya per correu electrònic
- Integració amb Google Auth

## 🔧 APIs Integrades

### 📊 PokeAPI
L'aplicació utilitza la [PokeAPI](https://pokeapi.co/) per obtenir dades oficials de Pokémon:

- **Sistema de caché**: Implementem un sistema de caché a la base de dades per evitar sol·licituds repetides a l'API, millorant la velocitat i respectant els límits d'ús
- **Dades utilitzades**:
  - Informació bàsica de Pokémon (nom, tipus, estadístiques)
  - Sprites oficials (integració amb el repositori d'sprites oficial)
  - Moviments i les seves característiques (poder, precisió, tipus, categoria)
  - Dades de tipus per calcular efectivitat en combat

Exemple d'ús de PokeAPI en el nostre codi:
```php
// Obtenir dades d'un Pokémon específic
$pokeAPIService = new PokeAPIService($connexio);
$pokemonData = $pokeAPIService->getPokemon($pokemonId);

// Obtenir sprite del Pokémon
$sprite = $pokemonData['sprites']['front_default'];

// Obtenir tipus
$tipus = array_map(function($tipus) {
    return $tipus['type']['name'];
}, $pokemonData['types']);
```

### 🥊 APIs Pròpies
L'aplicació compta amb diverses APIs internes per gestionar diferents funcionalitats:

#### 🏆 API de Combats (batalla_api.php & combat_api.php)
- Gestió de l'estat de batalles en temps real
- Sistema de torns
- Càlcul de dany segons tipus d'atac i defenses
- Canvi de Pokémon durant el combat

#### 👨‍👩‍👧‍👦 API d'Amics (gestiona_amistat.php & friend_action.php)
- Gestió de sol·licituds d'amistat
- Cerca d'usuaris
- Llistat d'amics actius

#### 💬 API de Xat (chat_api.php)
- Enviament i recepció de missatges
- Notificacions de missatges no llegits
- Historial de converses

#### 🛠️ API d'Equips (gestio_equips.php)
- Creació i gestió d'equips Pokémon
- Assignació de moviments a Pokémon
- Validació d'equips per combat

## 🚀 Estat del Desenvolupament

### ✅ Funcionalitats Implementades
- Sistema d'autenticació complet
- Gestió d'equips Pokémon
- Sistema de combat bàsic
- Gestió d'amistats
- Xat entre usuaris
- Personalització d'avatar

### 🔄 En Desenvolupament
- Millores en el sistema de combat (efectes visuals avançats)
- Implementació d'objectes durant el combat
- Rànquing de batalles
- Tornejos amb múltiples participants
- Sistema d'assoliments i recompenses

### 📋 Properes Característiques
- Sistema de comerç de Pokémon entre usuaris
- Esdeveniments temporals amb Pokémon especials
- Mode història amb entrenadors IA
- Personalització avançada de perfil

## 🧰 Tecnologies Utilitzades
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Backend: PHP
- Base de dades: MySQL
- Autenticació: Sistema propi + Google Auth
- API externa: PokeAPI

## 📦 Estructura del Projecte
- **Controlador/**: Arxius PHP que gestionen la lògica de negoci
  - **js/**: Scripts pel frontend
  - **funcions_combat/**: Funcions específiques pel sistema de combat
- **Model/**: Arxius PHP per interactuar amb la base de dades
- **Vista/**: Arxius de vistes i assets
- **libs/**: Llibreries externes (PHPMailer, etc.)
- **logs/**: Arxius de log per depuració

## 🔍 Característiques Especials

### Sistema de Caché per PokeAPI
Per optimitzar el rendiment i respectar els límits de l'API, implementem un sistema de caché que emmagatzema localment les respostes de l'API:

```php
// Verificar si existeix a la caché abans de fer petició
$cached_data = $this->getFromCache($cache_key);
if ($cached_data !== false) {
    return $cached_data;
}

// Si no està a la caché, fer petició i guardar resultat
$url = $this->base_url . $endpoint;
$response = $this->makeApiRequest($url);
if ($response !== false) {
    $this->saveToCache($cache_key, $response);
}
```

### Sistema de Combat en Temps Real
El sistema de combat actualitza l'estat de la batalla periòdicament mitjançant crides AJAX:

```javascript
// Iniciar actualització periòdica
function iniciarActualitzacioPeriodica() {
    intervaloActualizacion = setInterval(() => {
        actualitzarEstatBatalla();
    }, 2000); // Actualitzar cada 2 segons
}
```

## 🤝 Contribució
Vols contribuir al projecte? Genial! Pots ajudar de diverses formes:
- Reportant errors
- Suggerint noves característiques
- Millorant la documentació
- Contribuint amb codi

## 📄 Llicència
Aquest projecte està sota la Llicència MIT.

## 👥 Autors
- [El teu nom i equip]

---

© 2025 Sapamon. Tots els drets reservats.
