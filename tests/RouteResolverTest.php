<?php
declare(strict_types=1);

namespace Slim\Tests;

use Slim\CallableResolver;
use Slim\Dispatcher;
use Slim\RouteCollector;
use Slim\RouteResolver;
use ReflectionClass;

/**
 * Class RouteResolverTest
 * @package Slim\Tests
 */
class RouteResolverTest extends TestCase
{
    /**
     * @var RouteResolver
     */
    private $routeResolver;

    public function setUp()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $this->routeResolver = new RouteResolver($routeCollector);
    }

    public function testCreateDispatcher()
    {
        $class = new ReflectionClass(RouteResolver::class);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);
        $this->assertInstanceOf(Dispatcher::class, $method->invoke($this->routeResolver));
    }

    public function testSetDispatcher()
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = \FastRoute\simpleDispatcher(function ($r) {
        }, ['dispatcher' => Dispatcher::class]);
        $this->routeResolver->setDispatcher($dispatcher);

        $class = new ReflectionClass(RouteResolver::class);
        $prop = $class->getProperty('dispatcher');
        $prop->setAccessible(true);

        $this->assertEquals($dispatcher, $prop->getValue($this->routeResolver));
    }

    /**
     * Calling createDispatcher as second time should give you back the same
     * dispatcher as when you called it the first time.
     */
    public function testCreateDispatcherReturnsSameDispatcherASecondTime()
    {
        $class = new ReflectionClass(RouteResolver::class);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->routeResolver);
        $dispatcher2 = $method->invoke($this->routeResolver);
        $this->assertSame($dispatcher2, $dispatcher);
    }
}
