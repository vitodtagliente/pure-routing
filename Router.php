<?php

namespace Pure\Router;

class Router {

    // Implementazione del pattern singleton
    private static $instance = null;
    public static function main(){
        if( self::$instance == null )
            new Router();
        return self::$instance;
    }

    // variabile locale in cui andrò a memorizzare tutte le rotte
    private $routes = [];
    private $home = '';
    // Array in cui è possibile andare a specificare degli alias
    // per le espressioni regolari
    public $rules = [
        'i'  => '^\d+$', // integer
        'a'  => '[0-9A-Za-z]++', // alphanumeric
        'c'  => '^[a-zA-Z]+$' // characters
    ];

    public function __construct(){
        // mantenimento del riferimento all'oggetto corrente
        if( self::$instance == null )
            self::$instance = this;
    }

    // Metodi per la definizione delle rotte

    public function get( $pattern, $callback )
    {
        $this->map( 'GET', $pattern, $callback );
    }
    public function post( $pattern, $callback )
    {
        $this->map( 'POST', $pattern, $callback );
    }
    public function put( $pattern, $callback )
    {
        $this->map( 'PUT', $pattern, $callback );
    }
    public function delete( $pattern, $callback )
    {
        $this->map( 'DELETE', $pattern, $callback );
    }

    // Questa funzione si occupa di andare a mappare correttamente
    // le informazioni fornite sulle rotte nella struttura dati locale
    private function map( $method, $pattern, $callback )
    {
        // In caso di errore in cui il metodo viene omesso
        // questo viene impostato di default a GET
        if( empty($method) )
            $method = 'GET';

        array_push( $this->routes, [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ] );
    }

    // Trova il match migliore per le rotte specificate
    public function dispatch(){
        // Ottieni l'url corrente
        $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        // Ottieni il metodo della richiesta utente
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        // Elimina la string a di query (?a=b) dalla Request Url
		if (($strpos = strpos($requestUrl, '?')) !== false) {
			$requestUrl = substr($requestUrl, 0, $strpos);
		}

        $match = null;
        $params = [];

        foreach( $this->routes as $route ){
            // se il metodo della richiesta coincide con quello della rotta iterata
            // prosegui, altrimenti continua con la prossima rotta
            if( strcasecmp($requestMethod, $route['method']) != 0 )
                continue;

            // Controllo sulla rotta 'home'
            if( $requestUrl == '/' && $route['pattern'] = '/' ){
                $match = $route;
                break;
            }
            else
            {
                // Se la definizione della rotta prevede la presenza di parametri
                // Esegui un match applicando le espressioni regolari, se specificate
                if (($strpos = strpos($route['pattern'], '$')) !== false) {

                    $result = $this->complexMatch( $requestUrl, $route['pattern'] );
                    if ( $result != false ){
                        $params = $result;
                        $match = $route;
                        break;
                    }

        		}
                // altrimenti, si tratta di verificare un confronto tra due stringhe 
                // che devono combaciare
                else {
                    if( rtrim( $requestUrl, '/' ) == rtrim( $route['pattern'], '/' ) ){
                        $match = $route;
                        break;
                    }
                }
            }

        }

        // Se il match ha dato risultati, eseguilo
        if( $match != null ){
            $route = new Route( $match['callback'], $params );
            return $route->call();
        }
        return false;
    }

    // Do a match finding params
    // returns false if the processing fails
    // returns an array of params if the processing its done
    private function complexMatch( $url, $pattern ){
        $url = trim( $url, '/' );
        $pattern = trim( $pattern, '/' );

        $url_pieces = explode( '/', $url );
        $pattern_pieces = explode( '/', $pattern );

        if( count($url_pieces) != count($pattern_pieces) )
            return false;

        $params = [];

        for( $i = 0; $i < count($url_pieces); $i++ ){
            $u = $url_pieces[$i];
            $p = $pattern_pieces[$i];

            // if the current pattern starts with $
            // it is a parameter and could contains a regular expression
            // into a form: $param:regular_expression
            if ( 0 === strpos($p, '$') ) {
                // if contains :
                // we have to match the specified regular expression
                if (($strpos = strpos($p, ':')) !== false){

                    if( $this->regMatch( $u, $p ) == false )
                        return false;
                    else {
                        $temp = explode( ':', $p );
                        $params[ltrim($temp[0], '$')] = $u;
                    }

                }
                else {
                    $params[ltrim($p, '$')] = $u;
                }
            }
            // No params are defined
            // check if strings are equals
            else {
                if( $u != $p )
                    return false;
            }
        }

        return $params;
    }

    // Check if the value matches the pattern ( regular expression )
    private function regMatch( $value, $pattern ){
        $pieces = explode( ':', $pattern );
        if( count( $pieces ) <= 1 )
            return false;

        $regex = $pieces[1];

        if (array_key_exists($regex, $this->rules)) {
            $regex = $this->rules[$regex];
        }

        return preg_match( "/$regex/", $value );
    }

    function __destruct(){

    }

}

?>
