# Pure Router Component
=================

The Routing component maps an HTTP request to a set of configuration variables.

# HOW TO
=================

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