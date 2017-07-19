<?php

/*

    This class is used internally by the router
    to define a route call

*/

namespace Pure\Router;

class Route {

    // a route is defined by a callback and params
    // callback can be : function, string or array data

    private $callback = null;
    private $params = [];

    // default path where to include controllers
    private static $path = null;

    function __construct( $callback, $params ){
        $this->callback = $callback;
        $this->params = $params;

        if( $this->params == null )
            $this->params = [];
    }

    // execute the callback
    public function call(){
        if( is_array( $this->callback ) ){
            return $this->processData( $this->callback, $this->params );
        }
        else if( is_callable( $this->callback ) ){
            call_user_func_array( $this->callback, $this->params );
            return true;
        }
        else if( is_string( $this->callback ) ){
            return $this->processString( $this->callback, $this->params );
        }
        else return false;
    }

    /*
        if callback is array, it must have fields:
        - filename: the filename that has to be include
        - classname: the name of the class that's to be instantiated
        - action: the method which will be called
    */
    private function processData( $data, $params ){
        $filename = $data['filename'];
        $classname = $data['classname'];
        $action = $data['action'];

        return $this->callController( $filename, $classname, $action, $params );
    }

    private function processString( $data, $params ){
        $classname = $data;
        $action = 'index';
        // if string contains @, like in the example: Foo@action1
        // extract Foo like classname, action1 like method
        if (($strpos = strpos($data, '@')) !== false){
            $pieces = explode( '@', $data );
            $classname = $pieces[0];
            $action = $pieces[1];
        }

        // if string starts with //
        // include the file starting from root directory
        if( strpos($classname, '//') === 0 ){
            $filename = ltrim($classname, '/') . '.php';
        }
        // else include the file starting from
        // the default controllers'path
        else
            $filename = self::path() . '/' . $classname . '.php';

        // if string contains /, like user/User, use the last piece 'User' as classname
        if( ($strpos = strpos($classname, '/')) !== false ){
            $pieces = explode( '/', $classname );
            $classname = $pieces[ count($pieces) - 1 ];
        }

        return $this->callController( $filename, $classname, $action, $params );
    }

    private function callController( $filename, $classname, $action, $params ){
        // include file if exists
        if( isset( $filename ) && file_exists( $filename ) ){
            include_once $filename;
        }
        else return false;

        $_obj = null;
        if( class_exists( $classname ) ){
            $_obj = new $classname();
        }
        else return false;

        if( isset( $action ) && $_obj != null ){

            if ( is_callable( array( $_obj, $action ) ) )
                call_user_func_array( array( $_obj, $action ), $params );
            else return false;

        }
        return true;
    }

    /*
        set or return the default path where controllers will be looked at
    */
    public static function path($path = null){
        if(isset($path))
            self::$path = $path;
        else return self::$path;
    }

    function __destruct(){

    }

}

?>
