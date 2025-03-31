<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

namespace TP\Utils\Chi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Router implements a PSR-7 compatible HTTP router.
 *
 * This implementation below is a based on the original work by
 * Peter Kieltyka in https://github.com/go-chi/chi (MIT licensed).
 * It has been modified to port to PHP.
 */
class Router implements RequestHandlerInterface
{
    // The radix trie router
    private Node $tree;

    // The middleware stack
    private array $middlewares = [];

    // Custom route not found handler
    private ?\Closure $notFoundHandler = null;

    // Custom method not allowed handler
    private ?\Closure $methodNotAllowedHandler = null;

    // Controls behavior of middleware chain generation for inline groups
    private bool $inline = false;

    /**
     * Creates a new Router instance.
     */
    public function __construct()
    {
        $this->tree = new Node();
    }

    /**
     * Handles the request and returns a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middlewares)) {
            return $this->routeHTTP($request);
        }

        // Apply middleware chain
        $handler = $this->middlewares[0];
        for ($i = 1; $i < count($this->middlewares); ++$i) {
            $handler = $this->middlewares[$i]($handler);
        }

        return $handler($request);
    }

    /**
     * Add middleware to the router.
     *
     * @param callable[] $middlewares
     */
    public function use(callable ...$middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        return $this;
    }

    /**
     * Any adds a route with any HTTP method.
     */
    public function any(string $pattern, callable $handler): self
    {
        $parts = explode(' ', $pattern, 2);
        if (2 === count($parts)) {
            $this->method($parts[0], $parts[1], $handler);

            return $this;
        }

        $this->addRoute(MethodType::$ALL, $pattern, $handler);

        return $this;
    }

    /**
     * Method adds a route for a specific HTTP method.
     */
    public function method(string $method, string $pattern, callable $handler): self
    {
        $methodUpper = strtoupper($method);
        if (!isset(MethodType::$methodMap[$methodUpper])) {
            throw new \RuntimeException("Chi: '{$method}' HTTP method is not supported.");
        }

        $this->addRoute(MethodType::$methodMap[$methodUpper], $pattern, $handler);

        return $this;
    }

    /**
     * GET method route.
     */
    public function get(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::GET, $pattern, $handler);

        return $this;
    }

    /**
     * POST method route.
     */
    public function post(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::POST, $pattern, $handler);

        return $this;
    }

    /**
     * PUT method route.
     */
    public function put(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::PUT, $pattern, $handler);

        return $this;
    }

    /**
     * DELETE method route.
     */
    public function delete(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::DELETE, $pattern, $handler);

        return $this;
    }

    /**
     * PATCH method route.
     */
    public function patch(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::PATCH, $pattern, $handler);

        return $this;
    }

    /**
     * HEAD method route.
     */
    public function head(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::HEAD, $pattern, $handler);

        return $this;
    }

    /**
     * OPTIONS method route.
     */
    public function options(string $pattern, callable $handler): self
    {
        $this->addRoute(MethodType::OPTIONS, $pattern, $handler);

        return $this;
    }

    /**
     * Sets a custom handler for not found routes.
     */
    public function notFound(callable $handler): self
    {
        $this->notFoundHandler = $handler;

        return $this;
    }

    /**
     * Sets a custom handler for method not allowed responses.
     */
    public function methodNotAllowed(callable $handler): self
    {
        $this->methodNotAllowedHandler = $handler;

        return $this;
    }

    /**
     * With creates a new router with the added middleware.
     *
     * @param callable[] $middlewares
     */
    public function with(callable ...$middlewares): self
    {
        $router = new self();
        $router->inline = true;
        $router->tree = $this->tree;
        $router->middlewares = array_merge($this->middlewares, $middlewares);
        $router->notFoundHandler = $this->notFoundHandler;
        $router->methodNotAllowedHandler = $this->methodNotAllowedHandler;

        return $router;
    }

    /**
     * Returns routes defined on this router.
     */
    public function routes(): array
    {
        return $this->tree->routes();
    }

    /**
     * Returns middleware stack.
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Core HTTP routing method.
     */
    private function routeHTTP(ServerRequestInterface $request): ResponseInterface
    {
        // Get route path from request
        $routePath = $request->getUri()->getPath();
        if ('' === $routePath) {
            $routePath = '/';
        }

        // Check if method is supported
        $method = MethodType::$methodMap[$request->getMethod()] ?? null;

        if (null === $method) {
            return $this->getMethodNotAllowedHandler()($request);
        }

        // Find the route
        $routeContext = new Context();
        $handler = $this->tree->findRoute($routeContext, $method, $routePath)['handler'] ?? null;

        if (null !== $handler) {
            // Add route parameters to the request
            $request = $request->withAttribute('routeParams', [
                'keys' => $routeContext->routeParams->keys,
                'values' => $routeContext->routeParams->values,
            ]);

            // Add route pattern to the request
            $request = $request->withAttribute('routePattern', $routeContext->routePattern);

            return $handler($request);
        }

        if ($routeContext->methodNotAllowed) {
            return $this->getMethodNotAllowedHandler($routeContext->methodsAllowed)($request);
        }

        return $this->getNotFoundHandler()($request);
    }

    /**
     * Add a route to the router.
     */
    private function addRoute(int $method, string $pattern, callable $handler): void
    {
        if ('' === $pattern || '/' !== $pattern[0]) {
            throw new \RuntimeException("Chi: routing pattern must begin with '/' in '{$pattern}'");
        }

        // Apply middleware chain if this is an inline router
        if ($this->inline) {
            $finalHandler = function (ServerRequestInterface $request) use ($handler) {
                return $handler($request);
            };

            // Apply middleware stack
            for ($i = count($this->middlewares) - 1; $i >= 0; --$i) {
                $finalHandler = $this->middlewares[$i]($finalHandler);
            }

            $handler = $finalHandler;
        }

        // Add the endpoint to the tree and return the node
        $this->tree->insertRoute($method, $pattern, $handler);
    }

    /**
     * Get the not found handler.
     */
    private function getNotFoundHandler(): callable
    {
        if (null !== $this->notFoundHandler) {
            return $this->notFoundHandler;
        }

        return function (ServerRequestInterface $request): ResponseInterface {
            return Util::response(404, [], 'Not Found');
        };
    }

    /**
     * Get the method not allowed handler.
     */
    private function getMethodNotAllowedHandler(array $methodsAllowed = []): callable
    {
        if (null !== $this->methodNotAllowedHandler) {
            return $this->methodNotAllowedHandler;
        }

        return function (ServerRequestInterface $request) use ($methodsAllowed): ResponseInterface {
            $methods = [];
            foreach ($methodsAllowed as $m) {
                $methods[] = MethodType::$reverseMethodMap[$m];
            }

            return Util::response(405, ['Allow' => implode(', ', $methods)], 'Method Not Allowed');
        };
    }

    /**
     * Check if a given pattern exists in the route tree.
     */
    public function findPattern(string $pattern): bool
    {
        return $this->tree->findPattern($pattern);
    }

    /**
     * Helper to extract route parameters from request.
     */
    public static function getRouteParams(ServerRequestInterface $request): array
    {
        $params = $request->getAttribute('routeParams', ['keys' => [], 'values' => []]);
        $routeParams = [];
        foreach ($params['keys'] as $i => $key) {
            $routeParams[$key] = $params['values'][$i];
        }

        return $routeParams;
    }
}
