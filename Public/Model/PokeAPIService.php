<?php
/**
 * Servicio para interactuar con la PokeAPI
 * Este servicio implementa un sistema de caché para evitar hacer peticiones repetidas a la API
 */
class PokeAPIService {
    private $base_url;
    private $connection;
    private $cache_duration; // Duración de la caché en segundos
    
    /**
     * Constructor del servicio PokeAPI
     * @param object $connection Conexión a la base de datos
     * @param int $cache_duration Duración de la caché en segundos (por defecto 24 horas)
     */
    public function __construct($connection, $cache_duration = 86400) {
        $this->base_url = 'https://pokeapi.co/api/v2/';
        $this->connection = $connection;
        $this->cache_duration = $cache_duration;
    }
    
    /**
     * Realiza una petición a la PokeAPI con sistema de caché
     * @param string $endpoint Endpoint de la API a consultar
     * @return mixed Datos obtenidos de la API en formato objeto/array
     */
    public function fetch($endpoint) {
        // Eliminar la barra inicial si existe
        $endpoint = ltrim($endpoint, '/');
        
        // Crear clave de caché a partir de la URL
        $cache_key = md5($this->base_url . $endpoint);
        
        // Intentar obtener de la caché
        $cached_data = $this->getFromCache($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Si no está en caché, hacer la petición a la API
        $url = $this->base_url . $endpoint;
        $response = $this->makeApiRequest($url);
        
        // Si la respuesta es válida, guardarla en caché
        if ($response !== false) {
            $this->saveToCache($cache_key, $response);
        }
        
        return $response;
    }
    
    /**
     * Realiza una petición HTTP a la API
     * @param string $url URL completa para la petición
     * @return mixed Datos obtenidos o false en caso de error
     */
    private function makeApiRequest($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: SapamonApp/1.0 (contact@example.com)',
                    'Accept: application/json'
                ]
            ]
        ];
        
        $context = stream_context_create($options);
        
        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $status_line = $http_response_header[0] ?? '';
                preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
                $status = $match[1] ?? '000';
                
                if ($status == '403') {
                    error_log('PokeAPI: Acceso prohibido. Verifica tu User-Agent');
                } elseif ($status == '429') {
                    error_log('PokeAPI: Demasiadas solicitudes. Implementa rate limiting');
                }
                
                $error = error_get_last();
                error_log('Error en PokeAPIService::makeApiRequest: ' . ($error['message'] ?? 'Desconocido') . ' - HTTP Status: ' . $status);
                return false;
            }
            
            return json_decode($response, true);
        } catch (Exception $e) {
            error_log('Error en PokeAPIService::makeApiRequest: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene datos de la caché
     * @param string $key Clave de la caché
     * @return mixed Datos almacenados o false si no existe o ha caducado
     */
    private function getFromCache($key) {
        $sql = "SELECT valor_cache, caducitat FROM memoria_cache WHERE clau_cache = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $row = $result->fetch_assoc();
        $expiry_date = new DateTime($row['caducitat']);
        $now = new DateTime();
        
        // Comprobar si ha caducado
        if ($expiry_date < $now) {
            // Eliminar la entrada caducada
            $this->deleteFromCache($key);
            return false;
        }
        
        return json_decode($row['valor_cache'], true);
    }
    
    /**
     * Guarda datos en la caché
     * @param string $key Clave de la caché
     * @param mixed $data Datos a guardar
     * @return bool Éxito de la operación
     */
    private function saveToCache($key, $data) {
        // Calcular la fecha de caducidad
        $expiry_date = new DateTime();
        $expiry_date->modify('+' . $this->cache_duration . ' seconds');
        $expiry_formatted = $expiry_date->format('Y-m-d H:i:s');
        
        // Serializar los datos a JSON
        $json_data = json_encode($data);
        
        // Guardar en la base de datos
        $sql = "INSERT INTO memoria_cache (clau_cache, valor_cache, caducitat) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                valor_cache = VALUES(valor_cache), 
                caducitat = VALUES(caducitat)";
                
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('sss', $key, $json_data, $expiry_formatted);
        
        return $stmt->execute();
    }
    
    /**
     * Elimina una entrada de la caché
     * @param string $key Clave de la caché
     * @return bool Éxito de la operación
     */
    private function deleteFromCache($key) {
        $sql = "DELETE FROM memoria_cache WHERE clau_cache = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('s', $key);
        
        return $stmt->execute();
    }
    
    /**
     * Limpia entradas caducadas de la caché
     * @return int Número de entradas eliminadas
     */
    public function cleanExpiredCache() {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        
        $sql = "DELETE FROM memoria_cache WHERE caducitat < ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('s', $now);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Obtiene información de un Pokémon por su ID o nombre
     * @param int|string $id_or_name ID o nombre del Pokémon
     * @return array|false Datos del Pokémon o false si hay error
     */
    public function getPokemon($id_or_name) {
        return $this->fetch('pokemon/' . strtolower(trim($id_or_name)));
    }
    
    /**
     * Obtiene una lista paginada de Pokémon
     * @param int $limit Límite de resultados (por defecto 20)
     * @param int $offset Desplazamiento para la paginación
     * @return array|false Lista de Pokémon o false si hay error
     */
    public function getPokemonList($limit = 20, $offset = 0) {
        return $this->fetch("pokemon?limit=$limit&offset=$offset");
    }
    
    /**
     * Obtiene información de un movimiento por su ID o nombre
     * @param int|string $id_or_name ID o nombre del movimiento
     * @return array|false Datos del movimiento o false si hay error
     */
    public function getMove($id_or_name) {
        return $this->fetch('move/' . strtolower(trim($id_or_name)));
    }
    
    /**
     * Obtiene los movimientos que puede aprender un Pokémon
     * @param int|string $pokemon_id_or_name ID o nombre del Pokémon
     * @return array Lista de movimientos disponibles para el Pokémon
     */
    public function getPokemonMoves($pokemon_id_or_name) {
        $pokemon_data = $this->getPokemon($pokemon_id_or_name);
        if ($pokemon_data === false) {
            return [];
        }
        
        return $pokemon_data['moves'] ?? [];
    }
    
    /**
     * Busca Pokémon por nombre (parcial)
     * @param string $name Nombre o parte del nombre a buscar
     * @param int $limit Límite de resultados
     * @return array Lista de Pokémon que coinciden con la búsqueda
     */
    public function searchPokemonByName($name, $limit = 20) {
        $name = strtolower(trim($name));
        $results = [];
        
        // Obtener una lista más grande para poder filtrar
        $pokemon_list = $this->getPokemonList(100, 0);
        if ($pokemon_list === false || !isset($pokemon_list['results'])) {
            return $results;
        }
        
        // Filtrar por nombre
        $count = 0;
        foreach ($pokemon_list['results'] as $pokemon) {
            if (strpos($pokemon['name'], $name) !== false) {
                $results[] = $pokemon;
                $count++;
                
                if ($count >= $limit) {
                    break;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Obtiene información detallada de un tipo de Pokémon
     * @param int|string $id_or_name ID o nombre del tipo
     * @return array|false Datos del tipo o false si hay error
     */
    public function getType($id_or_name) {
        return $this->fetch('type/' . strtolower(trim($id_or_name)));
    }
    
    /**
     * Obtiene el color de fondo para un tipo de Pokémon
     * @param string $type_name Nombre del tipo
     * @return string Código de color hexadecimal
     */
    public function getTypeColor($type_name) {
        $colors = [
            'normal' => '#A8A878',
            'fire' => '#F08030',
            'water' => '#6890F0',
            'grass' => '#78C850',
            'electric' => '#F8D030',
            'ice' => '#98D8D8',
            'fighting' => '#C03028',
            'poison' => '#A040A0',
            'ground' => '#E0C068',
            'flying' => '#A890F0',
            'psychic' => '#F85888',
            'bug' => '#A8B820',
            'rock' => '#B8A038',
            'ghost' => '#705898',
            'dragon' => '#7038F8',
            'dark' => '#705848',
            'steel' => '#B8B8D0',
            'fairy' => '#EE99AC'
        ];
        
        $type_name = strtolower($type_name);
        return isset($colors[$type_name]) ? $colors[$type_name] : '#A8A8A8';
    }
    
    /**
     * Obtiene un sprite oficial para un Pokémon
     * @param int|string|array $pokemon_id_or_name ID o nombre del Pokémon, o array de datos del Pokémon
     * @param string $type Tipo de sprite (default, female, shiny, etc.)
     * @return string|null URL del sprite o null si no se encuentra
     */
    public function getPokemonSprite($pokemon_id_or_name, $type = 'default') {
        $pokemon_data = null;
        
        // Si ya tenemos los datos del Pokémon como array, usarlos directamente
        if (is_array($pokemon_id_or_name) && isset($pokemon_id_or_name['sprites'])) {
            $pokemon_data = $pokemon_id_or_name;
        } else {
            // Si es un ID o nombre, obtener los datos
            $pokemon_data = $this->getPokemon($pokemon_id_or_name);
        }
        
        if ($pokemon_data === false || !isset($pokemon_data['sprites'])) {
            return null;
        }
        
        $sprites = $pokemon_data['sprites'];
        
        // Intentar obtener el sprite frontal por defecto
        if ($type === 'default' && isset($sprites['front_default'])) {
            return $sprites['front_default'];
        }
        
        // Intentar obtener el sprite frontal shiny
        if ($type === 'shiny' && isset($sprites['front_shiny'])) {
            return $sprites['front_shiny'];
        }
        
        // Intentar obtener el sprite frontal femenino
        if ($type === 'female' && isset($sprites['front_female'])) {
            return $sprites['front_female'];
        }
        
        // Si no se encuentra el tipo pedido, usar el sprite por defecto
        return $sprites['front_default'] ?? null;
    }
}
?>