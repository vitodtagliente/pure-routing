<?php

namespace Pure\Router;

/*
    HOW TO:

    1. Instantiate the router
    $router = new Router();

    2. define the routes
    $router->get('/foo', $callback );

    $callback can be:
        - a function:
            $router->get('/foo', function(){ ... });
        - a string:
            $router->get('/foo', 'foo');
            where 'foo' if is callable, it will be called or
            you can call a controller like this:
            $router->get('/foo', 'FooController@foo');

            Note that, in the example, the controller must have filename: 'FooController.php'
            and must be located at the path specified in Pure\Router\Route::path()

            About the callable string, it means that the callback can be the name of function
                function foo(){ ... }
                $router->get('/foo', 'foo');

                it means that the callback can be, also, the name of  static function
                class Foo {
                    public static function foo(){ ... }
                }
                $router->get('/foo', 'Foo::foo' );


        - an Array
            With this type of parameter can be called controller form different paths
            $router->get('/foo', [
                'filename' => '/mypath/foo.php',
                'classname' => 'FooController',
                'action' => 'foo'
            ]);

    3. Defining routes with parameters

        - Parameters can be defined using $
            $routes->get('/user/$username', function($username){} );

        - Parameters can be associated with regular expression
            There are 3 types of default regular expression
            i: integer
            a: alphanumeric
            c: characters

            In this example we define $id as an integer:
            $router->get('/user/$id:i', function($id){ ... } );

        How to define other regular expression. There are 2 ways:
            - using regular expression in path expression
                $router->get('/user/$id:regular_expression', ... )

            - Defining ner router rules
                $router->rules['key'] = 'regular_expression'

                so that you can use the new one in path definition:
                $router->get('/foo/$param:key', ... );

*/

class Router {

    // we the routes will be stored
    private $routes = [];
    private $home = '';
    // Array which stores the rules ( regular expressions ) used to
    // match parameters in url
    public $rules = [
        'i'  => '^\d+$', // integer
        'a'  => '[0-9A-Za-z]++', // alphanumeric
        'c'  => '^[a-zA-Z]+$' // characters
    ];

    function __construct(){

    }

    function get( $pattern, $callback )
    {
        $this->map( 'GET', $pattern, $callback );
    }
    function post( $pattern, $callback )
    {
        $this->map( 'POST', $pattern, $callback );
    }
    function put( $pattern, $callback )
    {
        $this->map( 'PUT', $pattern, $callback );
    }
    function delete( $pattern, $callback )
    {
        $this->map( 'DELETE', $pattern, $callback );
    }

    private function map( $method, $pattern, $callback )
    {
        if( empty($method) )
            $method = 'GET';
        array_push( $this->routes, [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ] );
    }

    // Find the best route match
    function dispatch(){
        $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        // Strip query string (?a=b) from Request Url
		if (($strpos = strpos($requestUrl, '?')) !== false) {
			$requestUrl = substr($requestUrl, 0, $strpos);
		}

        $match = null;
        $params = [];

        foreach( $this->routes as $route ){
            // if method is the same continue processing the current route
            // else continue the search
            if( strcasecmp($requestMethod, $route['method']) != 0 )
                continue;

            if( $requestUrl == '/' && $route['pattern'] = '/' ){
                $match = $route;
                break;
            }
            else
            {
                // if the route pattern contains params
                // do a better search using regex
                if (($strpos = strpos($route['pattern'], '$')) !== false) {

                    $result = $this->complexMatch( $requestUrl, $route['pattern'] );
                    if ( $result != false ){
                        $params = $result;
                        $match = $route;
                        break;
                    }

        		}
                // else check if pattern and the url are equals
                else {
                    if( rtrim( $requestUrl, '/' ) == rtrim( $route['pattern'], '/' ) ){
                        $match = $route;
                        break;
                    }
                }
            }

        }

        // Execute the match
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
