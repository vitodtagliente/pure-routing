# Pure Routing Component

The Routing component maps an HTTP request to a set of configuration variables.

# HOW TO

1. Instantiate the router
    ```php
    $router = new Pure/Routing/Router();
    ```
2. define the routes
    ```php    
    $router->get('/foo', $callback);    // GET method
    $router->post('/foo', $callback);   // POST method
    $router->put('/foo', $callback);    // PUT method
    $router->delete('/foo', $callback); // DELETE method
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

        Besides, the callback can be a controller.

        ```php
        $router->get('/foo', 'FooController@action');
        ```
        Remember that all the namespace closures must be defined, like in the example:
        ```php        
        $router->get('/foo', 'App\Controllers\FooController@action');
        
        $router->get('/foo', 'App\Controllers\FooController::class . '@action');
        ```
        Is it possible to define namespace alias, like in this example:
        ```php
        $router->namespace('app', 'App\Controllers');
        $router->get('/foo', 'app:FooController@action');
        ```
3. Defining routes with parameters
    - Parameters can be defined using (**$**variable) syntax, like in php
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
        It is possible to add new regular expression:
        ```php
        $router->rule('k', 'regular_expression');
        ```



## How To define middlewares

Middlewares let to check a route before executing it. Let's take an example, try to protect the 'dashboard' navigation from users that are not logged in:

```php
use Pure\Routing\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(){
        // returns true if the user is logged in
        return MyAuthNamespace\Auth::check();
    }
}
```

```php
$router->get('/dashboard', $callback)->middleware(AuthMiddleware::class);   
```

If the handle function returns false, the route execution is stopped.