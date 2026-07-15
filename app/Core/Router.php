<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Custom router: method + path pattern matching, named routes, route
 * groups (prefix + middleware stack), and a middleware pipeline executed
 * before the controller action. No third-party routing library is used,
 * per the project's strict framework-free constraint.
 */
final class Router
{
    /**
     * Each route's `middleware` entries may be either a class-string
     * (autowired via the container) or a pre-instantiated middleware
     * object (used when a middleware needs constructor arguments, e.g.
     * RateLimitMiddleware('auth.login')).
     *
     * @var array<int, array{method:string, pattern:string, regex:string, action:mixed, middleware:array, name:?string}>
     */
    private array $routes = [];

    private array $groupPrefixStack = [];
    private array $groupMiddlewareStack = [];

    /** @var array<string, string> name => pattern */
    private array $namedRoutes = [];

    public function get(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $action, $middleware);
    }

    public function put(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $action, $middleware);
    }

    public function patch(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->addRoute('PATCH', $pattern, $action, $middleware);
    }

    public function delete(string $pattern, mixed $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $action, $middleware);
    }

    /**
     * Group routes under a shared prefix and/or middleware stack.
     * Example: $router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], fn($r) => ...);
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupPrefixStack[] = rtrim($attributes['prefix'] ?? '', '/');
        $this->groupMiddlewareStack[] = $attributes['middleware'] ?? [];

        $callback($this);

        array_pop($this->groupPrefixStack);
        array_pop($this->groupMiddlewareStack);
    }

    public function name(string $name): void
    {
        $lastIndex = array_key_last($this->routes);

        if ($lastIndex === null) {
            throw new RuntimeException('Cannot name a route before it is defined.');
        }

        $this->routes[$lastIndex]['name'] = $name;
        $this->namedRoutes[$name] = $this->routes[$lastIndex]['pattern'];
    }

    public function urlFor(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Route [{$name}] is not defined.");
        }

        $pattern = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $pattern = str_replace("{{$key}}", (string) $value, $pattern);
        }

        return $pattern;
    }

    private function addRoute(string $method, string $pattern, mixed $action, array $middleware): void
    {
        $fullPattern = implode('', $this->groupPrefixStack) . $pattern;
        $fullPattern = $fullPattern === '' ? '/' : $fullPattern;

        // Normalize exactly like Request::path() does ('/' . trim($path, '/')),
        // so a route built from nested group prefixes + a '/' pattern (e.g.
        // group('/administrator') > group('/payments') > get('/')) matches
        // the incoming request path the same way regardless of how many
        // prefix segments contributed the trailing slash.
        if ($fullPattern !== '/') {
            $fullPattern = '/' . trim($fullPattern, '/');
        }

        $groupMiddleware = empty($this->groupMiddlewareStack) ? [] : array_merge(...$this->groupMiddlewareStack);
        $mergedMiddleware = array_merge($groupMiddleware, $middleware);

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'regex' => $this->compilePattern($fullPattern),
            'action' => $action,
            'middleware' => $mergedMiddleware,
            'name' => null,
        ];
    }

    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#u';
    }

    /**
     * Dispatch the request through the matched route's middleware pipeline
     * and into the controller action. Returns a 404/405 Response when no
     * route matches.
     */
    public function dispatch(Request $request, Container $container): Response
    {
        $method = $request->method();
        $path = $request->path();

        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn ($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            return $this->runPipeline($route, $params, $request, $container);
        }

        return $pathMatched
            ? Response::apiError('Phương thức không được hỗ trợ.', [], 'METHOD_NOT_ALLOWED', 405)
            : Response::apiError('Không tìm thấy tài nguyên yêu cầu.', [], 'NOT_FOUND', 404);
    }

    private function runPipeline(array $route, array $params, Request $request, Container $container): Response
    {
        $destination = function (Request $request) use ($route, $params, $container): Response {
            return $this->callAction($route['action'], $params, $request, $container);
        };

        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (\Closure $next, string|Middleware\MiddlewareInterface $middlewareRef) use ($container) {
                return function (Request $request) use ($next, $middlewareRef, $container): Response {
                    $middleware = $middlewareRef instanceof Middleware\MiddlewareInterface
                        ? $middlewareRef
                        : $container->make($middlewareRef);

                    /** @var Middleware\MiddlewareInterface $middleware */
                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    private function callAction(mixed $action, array $params, Request $request, Container $container): Response
    {
        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;
            $controller = $container->make($controllerClass);

            return $controller->{$method}($request, $params);
        }

        if (is_callable($action)) {
            return $action($request, $params);
        }

        throw new RuntimeException('Invalid route action.');
    }
}
