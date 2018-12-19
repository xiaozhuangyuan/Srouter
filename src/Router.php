<?php

namespace Xiaozhuangyuan\Srouter;

class Router
{

    public static $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    );

    private $currentGroupPrefix = '';

    private $currentGroupMiddleware = [];

    private $routes = [];

    /**
     * @param string $prefix
     * @param Closure $callback
     * @param array $middleware
     */
    public function group(string $prefix, \Closure $callback, array $middleware = [], array $opts = [])
    {
        // backups
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupMiddleware = $this->currentGroupMiddleware;

        //merge
        if (!empty($prefix)) {
            $this->currentGroupPrefix = $previousGroupPrefix . '/' . \trim($prefix, '/');
        } else {
            $this->currentGroupPrefix = $previousGroupPrefix;
        }
        $this->currentGroupMiddleware = array_merge($previousGroupMiddleware, $middleware);

        $callback($this);

        // reverts
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupMiddleware = $previousGroupMiddleware;
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function get(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['get'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function post(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['post'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function put(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['put'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function delete(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['delete'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function options(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['options'], $path, $middleware, $handler, $binds, $opts);
    }

    /**\
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function head(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['head'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function any(string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute(['any'], $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param array $methods
     * @param string $path
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    public function map(array $methods, string $path, $handler, array $middleware = [], array $binds = [], array $opts = [])
    {
        $this->addRoute($methods, $path, $middleware, $handler, $binds, $opts);
    }

    /**
     * @param array $methods
     * @param string $route
     * @param $handler
     * @param array $binds
     * @param array $opts
     */
    private function addRoute(array $methods, string $path, array $middleware = [], $handler, array $binds = [], array $opts = [])
    {
        $route = $this->currentGroupPrefix . $path;
        if (!isset($this->routes[$route])) {
            $this->routes[$route] = [
                'method' => array_map('strtoupper', $methods),
                'middleware' => array_merge($this->currentGroupMiddleware, $middleware),
                'handler' => $handler
            ];
        } else {
            //methods is not intersect
            if (empty(array_intersect(array_map('strtoupper', $methods)))) {
                $hasIntersect = false;
                if (isset($this->routes[$route]['other'])) {
                    foreach ($this->routes[$route]['other'] as $key => $routeInfo) {
                        //methods intersect
                        if (!empty(array_intersect(array_map('strtoupper', $methods), $routeInfo['method']))) {
                            $hasIntersect = true;
                            break;
                        }
                    }
                }
                if (!$hasIntersect) {
                    $this->routes[$route]['other'][] = [
                        'method' => array_map('strtoupper', $methods),
                        'middleware' => array_merge($this->currentGroupMiddleware, $middleware),
                        'handler' => $handler
                    ];
                }
            }
        }
    }

    public function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        $searches = array_keys(static::$patterns);
        $replaces = array_values(static::$patterns);

        $hitRoute = [];

        // Check if route is defined without regex
        if (isset($this->routes[$uri])) {
            // Using an ANY option to match both GET and POST requests
            if (in_array($method, $this->routes[$uri]['method']) || in_array('ANY', $this->routes[$uri]['method'])) {

                $hitRoute = [
                    'middleware' => $this->routes[$uri]['middleware'],
                    'handler' => $this->routes[$uri]['handler'],
                    'matched' => []
                ];

            } elseif (isset($this->routes[$uri]['other'])) {
                foreach ($this->routes[$uri]['other'] as $other) {
                    if (in_array($method, $other['method']) || in_array('ANY', $other['method'])) {
                        $hitRoute = [
                            'middleware' => $other['middleware'],
                            'handler' => $other['handler'],
                            'matched' => []
                        ];
                        break;
                    }
                }
            }

        } else {
            // Check if defined with regex
            foreach ($this->routes as $route => $routeInfo) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                if (preg_match('#^' . $route . '$#', $uri, $matched)) {

                    // Remove $matched[0] as [1] is the first parameter.
                    array_shift($matched);

                    if (in_array($method, $routeInfo['method']) || in_array('ANY', $routeInfo['method'])) {

                        $hitRoute = [
                            'middleware' => $routeInfo['middleware'],
                            'handler' => $routeInfo['handler'],
                            'matched' => $matched
                        ];

                    } elseif (isset($routeInfo['other'])) {
                        foreach ($routeInfo['other'] as $other) {
                            if (in_array($method, $other['method']) || in_array('ANY', $other['method'])) {

                                $hitRoute = [
                                    'middleware' => $other['middleware'],
                                    'handler' => $other['handler'],
                                    'matched' => $matched
                                ];

                                break 2;
                            }
                        }
                    }

                }
            }
        }

        if (!empty($hitRoute)) {
            if (!empty($hitRoute['middleware'])) {

                foreach ($hitRoute['middleware'] as $middleware) {

                    $class = 'app\\middleware\\' . $middleware;

                    $controller = new $class();

                    if (!method_exists($controller, 'handle')) {

                        echo "middleware controller and action not found";

                    } else {
                        //check middleware
                        if (!call_user_func_array(array($controller, 'handle'), [])) return;
                    }
                }
            }

            // If route is not an object
            if (!is_object($hitRoute['handler'])) {

                // Grab all parts based on a / separator
                $parts = explode('/', $hitRoute['handler']);

                // Collect the last index of the array
                $last = end($parts);

                // Grab the controller name and method call
                $segments = explode('@', $last);

                // Instanitate controller
                $controller = new $segments[0]();

                if (!method_exists($controller, $segments[1])) {
                    echo "controller and action not found";
                } else {
                    call_user_func_array(array($controller, $segments[1]), $hitRoute['matched']);
                }

                return;

            } else {
                // Call closure
                call_user_func_array($hitRoute['handler'], $hitRoute['matched']);

                return;

            }
        } else {
            // Run the error callback if the route was not found
            call_user_func(function () {
                header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                echo '404';
            });
        }

    }
}