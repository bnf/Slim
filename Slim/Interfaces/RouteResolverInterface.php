<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

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
    public function getRoute(string $identifier): RouteInterface;
}
