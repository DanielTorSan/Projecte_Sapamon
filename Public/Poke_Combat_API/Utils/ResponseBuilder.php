<?php
namespace Poke_Combat_API\Utils;

/**
 * Classe d'utilitat per a construir respostes estandarditzades de l'API
 */
class ResponseBuilder {
    /**
     * Construeix una resposta d'èxit
     * 
     * @param mixed $data Dades a retornar
     * @param string $missatge Missatge d'èxit (opcional)
     * @return array Resposta formatada
     */
    public static function success($data = null, $missatge = null) {
        $resposta = [
            'exit' => true
        ];
        
        if ($data !== null) {
            $resposta['data'] = $data;
        }
        
        if ($missatge !== null) {
            $resposta['missatge'] = $missatge;
        }
        
        return $resposta;
    }
    
    /**
     * Construeix una resposta d'error
     * 
     * @param string $missatge Missatge d'error
     * @param int $codi Codi d'error (opcional)
     * @param mixed $errors Detalls dels errors (opcional)
     * @return array Resposta formatada
     */
    public static function error($missatge, $codi = null, $errors = null) {
        $resposta = [
            'exit' => false,
            'missatge' => $missatge
        ];
        
        if ($codi !== null) {
            $resposta['codi'] = $codi;
        }
        
        if ($errors !== null) {
            $resposta['errors'] = $errors;
        }
        
        return $resposta;
    }
    
    /**
     * Imprimeix una resposta en format JSON i finalitza l'execució
     * 
     * @param array $resposta Resposta a enviar
     * @param int $httpStatus Codi d'estat HTTP (opcional)
     */
    public static function outputJSON($resposta, $httpStatus = 200) {
        // Establir capçaleres
        header('Content-Type: application/json');
        http_response_code($httpStatus);
        
        // Imprimir JSON i finalitzar
        echo json_encode($resposta);
        exit;
    }
    
    /**
     * Envia una resposta d'èxit en format JSON i finalitza l'execució
     * 
     * @param mixed $data Dades a retornar
     * @param string $missatge Missatge d'èxit (opcional)
     * @param int $httpStatus Codi d'estat HTTP (opcional)
     */
    public static function outputSuccess($data = null, $missatge = null, $httpStatus = 200) {
        self::outputJSON(
            self::success($data, $missatge),
            $httpStatus
        );
    }
    
    /**
     * Envia una resposta d'error en format JSON i finalitza l'execució
     * 
     * @param string $missatge Missatge d'error
     * @param int $codi Codi d'error (opcional)
     * @param mixed $errors Detalls dels errors (opcional)
     * @param int $httpStatus Codi d'estat HTTP (opcional)
     */
    public static function outputError($missatge, $codi = null, $errors = null, $httpStatus = 400) {
        self::outputJSON(
            self::error($missatge, $codi, $errors),
            $httpStatus
        );
    }
}