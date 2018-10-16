<?php

namespace Pure\Routing;

class Router
{
	// singleton pattern
	private static $instance = null;

	public static function main(){
		if(!isset(self::$instance))
			self::$instance = new Router;
		return self::$instance;
	}

	public function __construct(){}

	public function __destruct(){}

	// qui vengono memorizzate le varie rotte
	private $routes = array();
	// espressioni regolari usate per
	// la validazione dei parametri di rotta
	private $rules = array(
		'i'  => '^\d+$', 			// integer
        'c'  => '^[a-zA-Z]+$',		// characters
        'a'  => '[0-9A-Za-z]++' 	// alphanumeric
	);
	// gestione degli alias per i namespace
	private $namespaces = array();

	// aggiungi una nuova regola
	public function rule($key, $regex){
		if(!array_key_exists($key, $this->rules))
			$this->rules[$key] = $regex;
	}

	// aggiungi un nuovo namespace
	public function namespace($key, $value){
		if(!array_key_exists($key, $this->namespaces))
			$this->namespaces[$key] = $value;
	}

	// mappature delle richieste
	private function map($method, $action, $callback){
		$route = new Route($method, $action, $callback);
		array_push($this->routes, $route);
		return $route;
	}

	public function get($action, $callback){ return $this->map('GET', $action, $callback); }
	public function post($action, $callback){ return $this->map('POST', $action, $callback); }
	public function put($action, $callback){ return $this->map('PUT', $action, $callback); }
	public function delete($action, $callback){ return $this->map('DELETE', $action, $callback); }

	// ritorna l'uri corrente
	public function uri(){
        $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
        if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    // ritorna l'uri ripulita dei parametri
	// delle richieste get (?param1=value1&param2=value2....)
    public function base_uri(){
    	$uri = $this->uri();
		if (($strpos = strpos($uri, '?')) !== false) 
			$uri = substr($uri, 0, $strpos);
		return $uri;
    }

    // ritorna il metodo corrent
    public function method(){
    	return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

	// elaborazione della richiesta dell'utente
	public function dispatch()
	{
		// prendi l'uri corrente priva di argomenti get
		$current_uri = $this->base_uri();
		// memorizza il metodo corrente
		$current_method = $this->method();

		foreach($this->routes as $route)
		{
			// confronta l'uri corrente con
			// la rotta, se corrispondono 
			// eseguila
			if($route->match($current_uri, $current_method, $this->rules))
			{
				return $route->call($this->namespaces);
			}
		}
		return false;
	}
}

?>