# Pure Router Component

The Routing component maps an HTTP request to a set of configuration variables.

# HOW TO

1. Instantiate the router
    ```php
    $router = new Pure/Router/Router();
    ```
2. define the routes
    ```php    
    $router->get('/foo', $callback );
    ```
    
    $callback can be:
    * a function:
        ```php
        $router->get('/foo', function(){ ... });
        ```
    * a string:
        ```php
        function foo(){ ... }
        $router->get('/foo', 'foo');
        ```
        where **foo** is a function.
        The callback can be, also, the name of  static method
        ```php
        class Foo {
            public static function foo(){ ... }
        }
        $router->get('/foo', 'Foo::foo' );
        ```
        Besides, the callback can be a controller. 
        ```php
        $router->get('/foo', 'FooController@action');
        ```
        Remember that all the namespace closures must be defined, like in the example:
        ```php        
        $router->get('/foo', 'App\Controllers\FooController@action');

        $router->get('/foo', App\Controllers\FooController::class . '@action');
        ```
        Usually, controllers are located in the same path. In that case is possible to define a default namespace closure:
        ```php
        $router->prefix('App\Controllers\\');
        $router->get('/foo', 'FooController@action');
        ```
3. Defining routes with parameters
    - Parameters can be defined using **$**
        ```php
        $routes->get('/user/$username', function($username){} );
        ```     
    - Parameters can be associated with regular expression
        There are 3 types of default regular expression
        - i: integer
        - a: alphanumeric
        - c: characters
        In this example we define $id as an integer:
        ```php
        $router->get('/user/$id:i', function($id){ ... } );
        ```        
        How to define other regular expression. There are 2 ways:
        - using regular expression in path expression
            ```php
            $router->get('/user/$id:regular_expression', ... )
            ```
        - Defining ner router rules
            ```php
            $router->rules['key'] = 'regular_expression'
            ```
            so that you can use the new one in path definition:
            ```php
            $router->get('/foo/$param:key', ... );
            ```