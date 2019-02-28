<?php
declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\RoutingResults;

/**
 * Interface RouteResolverInterface
 * @package Slim\Interfaces
 */
interface RouteResolverInterface
{
    /**
     * @param string $uri Should be $request->getUri()->getPath()
     * @param string $method
     * @return RoutingResults
     */
    public function resolve(string $uri, string $method): RoutingResults;

    /**
     * @param string $identifier
     * @return RouteInterface
     */
    public function getRouteHandler(string $identifier): RouteInterface;
}