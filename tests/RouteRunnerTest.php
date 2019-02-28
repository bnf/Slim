<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\CallableResolver;
use Slim\MiddlewareDispatcher;
use Slim\RouteCollector;
use Slim\RouteResolver;
use Slim\RouteRunner;
use Slim\RoutingResults;

class RouteRunnerTest extends TestCase
{
    public function testRoutingIsPerformedIfRoutingResultsAreUnavailable()
    {
        $handler = (function (ServerRequestInterface $request, ResponseInterface $response) {
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $response;
        })->bindTo($this);

        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector->map(['GET'], '/hello/{name}', $handler);
        $routeResolver = new RouteResolver($routeCollector);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');
        $routeRunner = new RouteRunner($routeResolver);

        $middlewareDispatcher = new MiddlewareDispatcher($routeRunner);
        $middlewareDispatcher->handle($request);
    }
}
