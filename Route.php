<?php

/*
	Classe per la modellazione delle rotte
*/

namespace Pure\Routing;

class Route
{
	// metodo della richiesta 
	private $method = null;
	// azione della richiesta
	private $action = null;
	// array di middleware
	private $middlewares = array();
	// gestione della risposta alle richieste 
	private $callback = null;
	// salva i parametri dell'azione
	private $arguments = array();

	public function __construct($method, $action, $callback)
	{
		$this->method = $method;
		$this->action = '/' . trim($action, '/');
		$this->callback = $callback;
	}

	public function __destruct(){}

	public function middleware($class_name)
	{
		if(class_exists($class_name) && is_subclass_of(Middleware::class))
		{
			if(!array_key_exists($class_name, $this->middleware))
				array_push($this->middlewares, $class_name);
		}
		return $this;
	}

	// verifica se l'uri richiesta corrisponde alla seguente rotta
	public function match($request_uri, $request_method, $rules = array())
	{
		// pulisci gli argomenti
		$this->arguments = array();

		if($this->method != $request_method)
			return false;

		// se sono identici, ok
		if($this->action == $request_uri)
			return true;

		// se l'action contiene dei parametri ($params)
		// esegui un match basato anche sulle rules
		if (($strpos = strpos($this->action, '$')) !== false) {
			return $this->match_with_arguments($request_uri, $rules);
		}
		return false;
	}

	private function match_with_arguments($request_uri, $rules)
	{
		$request_uri = trim($request_uri, '/');
        $current_action = trim($this->action, '/');

        $uri_pieces = explode('/', $request_uri);
        $action_pieces = explode('/', $current_action);

        // Se il numero di elementi non combacia è inutile proseguire
        if(count($uri_pieces) != count($action_pieces))
            return false;

		for( $i = 0; $i < count($uri_pieces); $i++ )
		{
			$u = $uri_pieces[$i];
            $a = $action_pieces[$i];

            // Se l'elemento inizia per $, si tratta di un parametro
            // Verificare se è stata specificata una espressione regolare
            // della forma: $param:regular_expression
            if (0 === strpos($a, '$')) 
            {
                // se contiene ':''
                // eseguire il match usando le espressioni regolari
                if (($strpos = strpos($a, ':')) !== false)
                {
                    if($this->match_regex($u, $a, $rules) == false)
                        return false;
                    else {
                    	// salva il parametro trovato
                        $temp = explode(':', $a);
                        $this->arguments[ltrim($temp[0], '$')] = $u;
                    }
                }
                // altrimenti salva solo il parametro 
                else 
                {
                    $this->arguments[ltrim($a, '$')] = $u;
                }
            }
            // L'elemento non è un parametro
            // esegui un confronto diretto
            else 
            {
                if($u != $a)
                    return false;
            }
		}
		return true;
	}

	// Verifica il match con le espressioni regolari ( regular expression )
	// Per esempio, pattern => id:i, value => 1, i => '^\d+$'
    private function match_regex($value, $pattern, $rules = array())
    {
        $pieces = explode(':', $pattern);
        if(count($pieces) <= 1)
            return false;

        $regex = $pieces[1];

        if (array_key_exists($regex, $rules)) {
            $regex = $rules[$regex];
        }

        // ritorna vero se il valore
        // rispetta l'espressione regolare
        return preg_match( "/$regex/", $value );
    }

    // controlla che i middleware siano soddisfatti
    // ed in seguito chiama la callback
    public function call($namespaces = array())
    {
		// verifica che i middleware siano soddisfatti
		foreach($this->middlewares as $middleware_class)
		{
			$middleware = new $middleware_class;
			if(!$middleware->handle())
				return false;
		}
		// i middleware sono soddisfatti
		// chiama la callback
		return $this->call_internal($namespaces);
	}

	// esegui la callback
	private function call_internal($namespaces = array())
	{
		// Se si tratta di una funzione base
        if(is_callable($this->callback))
        {
            call_user_func_array($this->callback, $this->arguments);
            return true;
        }
        // Si tratta di un controllore
        else if(is_string($this->callback))
        {
        	$classname = $this->callback;
            $action = 'index';
        	// controlla se sono presenti namespace 
        	// del formato namespace:class_name
            if (($strpos = strpos($classname, ':')) !== false)
            {
            	$temp = explode(':', $classname);
            	dd($temp);
            	if(count($temp) > 0)
            	{
            		$alias = $temp[0];
            		if(array_key_exists($alias, $namespaces))
            		{
            			if(!empty($namespaces[$alias]))
            			{
            				$classname = str_replace("$alias:", $namespaces[$alias] . '\\', $classname);
            			}
            			else return false;
            		}
            	}
            	else return false;
            }

            // se la stringa contiene @, come nell'esempio: Foo@action1
            // estrai Foo come classname e action1 come method
            if (($strpos = strpos($classname, '@')) !== false)
            {
                $pieces = explode( '@', $classname );
                $classname = $pieces[0];
                $action = $pieces[1];
            }

            if(class_exists($classname))
            {
                // Instanzia il controllore
                $_obj = new $classname();
                // chiama il metodo richiesto
                if ( is_callable( array( $_obj, $action ) ) ){
                    call_user_func_array( array( $_obj, $action ), $this->arguments );
                    return true;
                }
                else return false;
            }
            else return false;
        }
        else return false;
	}
}

?>