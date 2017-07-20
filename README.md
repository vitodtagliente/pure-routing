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
        $router->get('/foo', 'foo');
        ```
        where **foo** can be a callable object (like a function), or it can be a controller:
        ```php
        $router->get('/foo', 'FooController@foo');
        ```        
        Note that, in the example, the controller must have filename: **FooController.php** and must be located at the path specified in:
        ```php
        Pure\Router\Route::path("path/to/controllers")
        ```
        About the callable string, it means that the callback can be the name of function like this:
        ```php
        function foo(){ ... }
        $router->get('/foo', 'foo');
        ```
        it means that the callback can be, also, the name of  static method
        ```php
        class Foo {
            public static function foo(){ ... }
        }
        $router->get('/foo', 'Foo::foo' );
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