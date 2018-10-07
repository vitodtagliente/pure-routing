<?php

namespace Pure\Routing;

class Router {

    // Implementazione del pattern singleton
    private static $instance = null;
    public static function main(){
        if( self::$instance == null )
            new Router();
        return self::$instance;
    }

    // Prefisso per l'identificazione dei namespace dei controllori
    private $prefix = '';
    public function namespace($value){
        if( !empty($value) )
            $this->prefix = $value;
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
            self::$instance = $this;
    }

    // Metodi per la definizione delle rotte

    public function get( $pattern, $callback, $middleware = null )
    {
        $this->map( 'GET', $pattern, $callback, $middleware );
    }
    public function post( $pattern, $callback, $middleware = null )
    {
        $this->map( 'POST', $pattern, $callback, $middleware );
    }
    public function put( $pattern, $callback, $middleware = null )
    {
        $this->map( 'PUT', $pattern, $callback, $middleware );
    }
    public function delete( $pattern, $callback, $middleware = null )
    {
        $this->map( 'DELETE', $pattern, $callback, $middleware );
    }

    // Questa funzione si occupa di andare a mappare correttamente
    // le informazioni fornite sulle rotte nella struttura dati locale
    private function map( $method, $pattern, $callback, $middleware = null )
    {
        // In caso di errore in cui il metodo viene omesso
        // questo viene impostato di default a GET
        if( empty($method) )
            $method = 'GET';

        array_push( $this->routes, [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'middleware' => $middleware
        ] );
    }

    public function uri(){
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    }

    // Trova il match migliore per le rotte specificate
    public function dispatch(){
        // Ottieni l'url corrente
        $requestUrl = $this->uri();
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
            // controlla se sono stati definiti middleware
            if( isset($match['middleware']) )
            {
                // the middleware can be a function
                // o a specified object of Middleware class
                if( is_callable( $match['middleware'] ) )
                {   
                    if(call_user_func($match['middleware']))
                        return $this->call($match['callback'], $params);
                }
                else 
                {
                    if(class_exists($match['middleware']))
                    {
                        $middleware = new $match['middleware'];
                        if($middleware && is_a($middleware, '\Pure\Routing\Middleware'))
                        {
                            if(call_user_func(array($middleware, 'handle')))
                                return $this->call($match['callback'], $params);
                        }
                    }
                }
            }
            else return $this->call($match['callback'], $params);
        }
        return false;
    }

    // Esegui la richiesta
    private function call( $callback, $params ){
        // Se si tratta du una funzione
        if( is_callable( $callback ) ){
            call_user_func_array( $callback, $params );
            return true;
        }
        // Si tratta di un controllore
        else if( is_string( $callback ) ){

            $classname = $callback;
            $action = 'index';
            // Se non contiene namespace
            // aggiungi quello definito di default
            if (($strpos = strpos($classname, '\\')) !== true){
                $classname = $this->prefix.$classname;
            }
            // se la stringa contiene @, come nell'esempio: Foo@action1
            // estrai Foo come classname e action1 come method
            if (($strpos = strpos($classname, '@')) !== false){
                $pieces = explode( '@', $classname );
                $classname = $pieces[0];
                $action = $pieces[1];
            }

            if( class_exists( $classname ) ){
                // Instanzia il controllore
                $_obj = new $classname();
                // chiama il metodo richiesto
                if ( is_callable( array( $_obj, $action ) ) ){
                    call_user_func_array( array( $_obj, $action ), $params );
                    return true;
                }
                else return false;
            }
            else return false;

        }
        else return false;
    }

    // Verifica se l'url e il pattern specificato corrispondono
    // in caso di fallimento, ritorna false
    // in caso di successo, ritorna un array di parametri
    private function complexMatch( $url, $pattern ){
        $url = trim( $url, '/' );
        $pattern = trim( $pattern, '/' );

        $url_pieces = explode( '/', $url );
        $pattern_pieces = explode( '/', $pattern );

        // Se il numero di elementi non combacia è inutile proseguire
        if( count($url_pieces) != count($pattern_pieces) )
            return false;

        $params = [];

        for( $i = 0; $i < count($url_pieces); $i++ ){
            $u = $url_pieces[$i];
            $p = $pattern_pieces[$i];

            // Se l'elemento inizia per $, si tratta di un parametro
            // Verificare se è stata specificata una espressione regolare
            // della forma: $param:regular_expression
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
            // L'elemento non è un parametro
            // esegui un confronto diretto
            else {
                if( $u != $p )
                    return false;
            }
        }

        // ritorna la lista dei parametri
        // definiti della rotta
        return $params;
    }

    // Verifica il match con le espressioni regolari ( regular expression )
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
