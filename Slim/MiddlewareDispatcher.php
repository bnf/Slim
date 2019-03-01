<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Class MiddlewareDispatcher
 * @package Slim
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * Tip of the middleware call stack
     *
     * @var RequestHandlerInterface
     */
    protected $tip;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @param RequestHandlerInterface $kernel
     * @param ContainerInterface|null $container
     */
    public function __construct(
        RequestHandlerInterface $kernel,
        ContainerInterface $container = null
    ) {
        $this->seedMiddlewareStack($kernel);
        $this->container = $container;
    }

    /**
     * Invoke the middleware stack
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->tip->handle($request);
    }

    /**
     * Seed the middleware stack with the inner request handler
     *
     * @param RequestHandlerInterface $kernel
     */
    protected function seedMiddlewareStack(RequestHandlerInterface $kernel)
    {
        $this->tip = $kernel;
    }

    /**
     * Add a new middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param MiddlewareInterface|string|callable $middleware
     */
    public function add($middleware)
    {
        if ($middleware instanceof MiddlewareInterface) {
            $this->addMiddleware($middleware);
        } elseif (is_string($middleware)) {
            $this->addDeferred($middleware);
        } elseif (is_callable($middleware)) {
            $this->addCallable($middleware);
        } else {
            throw new RuntimeException(
                'Middleware must be an object/class name referencing an implementation of ' .
                'MiddlewareInterface or a callable with a matching signature.'
            );
        }
    }

    /**
     * Add a new middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $next = $this->tip;
        $this->tip = new class($middleware, $next) implements RequestHandlerInterface {
            private $middleware;
            private $next;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->next);
            }
        };
    }

    /**
     * Add a new middleware by class name
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param string $middleware
     */
    public function addDeferred(string $middleware)
    {
        $next = $this->tip;
        $this->tip = new class($middleware, $next, $this->container) implements RequestHandlerInterface {
            private $middleware;
            private $next;
            private $container;

            public function __construct(
                string $middleware,
                RequestHandlerInterface $next,
                ContainerInterface $container = null
            ) {
                $this->middleware = $middleware;
                $this->next = $next;
                $this->container = $container;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $resolved = $this->middleware;
                if ($this->container && $this->container->has($this->middleware)) {
                    $resolved = $this->container->get($this->middleware);
                    if ($resolved instanceof MiddlewareInterface) {
                        return $resolved->process($request, $this->next);
                    }
                }
                if (is_subclass_of($resolved, MiddlewareInterface::class)) {
                    return (new $resolved)->process($request, $this->next);
                }
                if (is_callable($resolved)) {
                    return ($resolved)($request, $this->next);
                }
                throw new RuntimeException(sprintf(
                    '%s is not resolvable',
                    $this->middleware
                ));
            }
        };
    }

    /**
     * Add a (non standard) callable middleware to the stack
     *
     * Middleware are organized as a stack. That means middleware
     * that have been added before will be executed after the newly
     * added one (last in, first out).
     *
     * @param callable $middleware
     */
    public function addCallable(callable $middleware)
    {
        $next = $this->tip;
        $this->tip = new class($middleware, $next) implements RequestHandlerInterface {
            private $middleware;
            private $next;

            public function __construct(callable $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->middleware)($request, $this->next);
            }
        };
    }
}
