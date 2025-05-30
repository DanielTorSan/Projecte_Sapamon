<?php
/**
 * CacheManager.php
 * 
 * Clase para gestionar el almacenamiento en caché de datos utilizando
 * la tabla 'memoria_cache' para optimizar el rendimiento de la aplicación.
 */

class CacheManager {
    private $connexio;
    
    /**
     * Constructor
     * 
     * @param mysqli $connexio Conexión a la base de datos
     */
    public function __construct($connexio) {
        $this->connexio = $connexio;
    }
    
    /**
     * Obtiene un valor de la caché por su clave
     * 
     * @param string $clau Clave del valor a obtener
     * @return mixed Valor almacenado o null si no existe o ha caducado
     */
    public function obtenir($clau) {
        try {
            // Escapar la clave para evitar inyecciones SQL
            $clauSegura = $this->connexio->real_escape_string($clau);
            
            // Consultar el valor en caché (eliminando automáticamente los elementos caducados)
            $sql = "SELECT valor_cache FROM memoria_cache 
                    WHERE clau_cache = ? 
                    AND caducitat > NOW()";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("s", $clauSegura);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            $valor = $result->fetch_assoc()['valor_cache'];
            
            // Actualizar la fecha de caducidad para mantener el valor "fresco"
            $this->actualitzarCaducitat($clauSegura);
            
            // Deserializar si es necesario
            return $this->deserialitzar($valor);
            
        } catch (Exception $e) {
            error_log("Error obteniendo valor de caché: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Almacena un valor en la caché con una clave específica
     * 
     * @param string $clau Clave para identificar el valor
     * @param mixed $valor Valor a almacenar (puede ser objeto, array, etc)
     * @param int $minuts Tiempo en minutos que el valor será válido (por defecto 30)
     * @return bool True si se almacenó correctamente, false en caso contrario
     */
    public function guardar($clau, $valor, $minuts = 30) {
        try {
            // Escapar la clave para evitar inyecciones SQL
            $clauSegura = $this->connexio->real_escape_string($clau);
            
            // Serializar valor si es necesario
            $valorSerialitzat = $this->serialitzar($valor);
            
            // Calcular fecha de caducidad
            $caducitat = date('Y-m-d H:i:s', time() + ($minuts * 60));
            
            // Eliminar clave existente si la hay
            $this->eliminar($clauSegura);
            
            // Insertar nuevo valor
            $sql = "INSERT INTO memoria_cache (clau_cache, valor_cache, caducitat) 
                    VALUES (?, ?, ?)";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("sss", $clauSegura, $valorSerialitzat, $caducitat);
            $result = $stmt->execute();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error guardando valor en caché: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un valor de la caché por su clave
     * 
     * @param string $clau Clave del valor a eliminar
     * @return bool True si se eliminó correctamente o no existía, false en caso contrario
     */
    public function eliminar($clau) {
        try {
            // Escapar la clave para evitar inyecciones SQL
            $clauSegura = $this->connexio->real_escape_string($clau);
            
            // Eliminar el valor
            $sql = "DELETE FROM memoria_cache WHERE clau_cache = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("s", $clauSegura);
            $stmt->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error eliminando valor de caché: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina todos los valores caducados de la caché
     * 
     * @return int Número de registros eliminados
     */
    public function netejaCaducats() {
        try {
            // Eliminar todos los valores caducados
            $sql = "DELETE FROM memoria_cache WHERE caducitat <= NOW()";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->execute();
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            error_log("Error limpiando caché: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Actualiza la fecha de caducidad de un elemento en caché
     * 
     * @param string $clau Clave del elemento
     * @param int $minuts Nuevos minutos de validez (por defecto 30)
     * @return bool True si se actualizó correctamente, false en caso contrario
     */
    public function actualitzarCaducitat($clau, $minuts = 30) {
        try {
            // Calcular nueva fecha de caducidad
            $caducitat = date('Y-m-d H:i:s', time() + ($minuts * 60));
            
            // Actualizar fecha
            $sql = "UPDATE memoria_cache SET caducitat = ? WHERE clau_cache = ?";
            
            $stmt = $this->connexio->prepare($sql);
            $stmt->bind_param("ss", $caducitat, $clau);
            $stmt->execute();
            
            return $stmt->affected_rows > 0;
            
        } catch (Exception $e) {
            error_log("Error actualizando caducidad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Serializa un valor para almacenarlo en la base de datos
     * 
     * @param mixed $valor Valor a serializar
     * @return string Valor serializado
     */
    private function serialitzar($valor) {
        // Si es un tipo simple, no necesitamos serializar
        if (is_string($valor) || is_numeric($valor) || is_bool($valor)) {
            return (string) $valor;
        }
        
        // Para arrays y objetos, serializamos
        return serialize($valor);
    }
    
    /**
     * Deserializa un valor almacenado en la base de datos
     * 
     * @param string $valor Valor serializado
     * @return mixed Valor deserializado
     */
    private function deserialitzar($valor) {
        // Intentar deserializar
        if (is_string($valor) && !is_numeric($valor)) {
            $unserializado = @unserialize($valor);
            
            // Si se deserializó correctamente, devolver el resultado
            if ($unserializado !== false || $valor === 'b:0;') {
                return $unserializado;
            }
        }
        
        // Si no es serializado, devolver tal cual
        return $valor;
    }
}