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

use Psr\Container\ContainerInterface;

/**
 * Resolves a callable.
 *
 * @package Slim
 * @since 3.0.0
 */
interface CallableResolverInterface
{
    /**
     * Resolve $toResolve into a callable
     *
     * @param mixed $toResolve
     * @return callable
     */
    public function resolve($toResolve): callable;

    /**
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface;
}
