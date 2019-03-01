<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use ReflectionClass;
use Slim\CallableResolver;
use Slim\Dispatcher;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Route;
use Slim\RouteCollector;
use Slim\RouteResolver;
use Slim\RoutingResults;
use Slim\Tests\Mocks\InvocationStrategyTest;

class RouteCollectorTest extends TestCase
{
    /** @var RouteCollectorInterface */
    protected $routeCollector;

    public function setUp()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $this->routeCollector = new RouteCollector($responseFactory, $callableResolver);
    }

    public function testMap()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);

        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertAttributeContains($route, 'routes', $this->routeCollector);
    }

    public function testMapPrependsGroupPattern()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };

        $this->routeCollector->pushGroup('/prefix', function () {
        });
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $this->routeCollector->popGroup();

        $this->assertAttributeEquals('/prefix/hello/{first}/{last}', 'pattern', $route);
    }

    /**
     * Base path is ignored by relativePathFor()
     */
    public function testRelativePathFor()
    {
        $this->routeCollector->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->routeCollector->relativePathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithNoBasePath()
    {
        $this->routeCollector->setBasePath('');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->routeCollector->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithBasePath()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $this->routeCollector->setBasePath('/base/path');
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/base/path/hello/josh/lockhart',
            $this->routeCollector->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithOptionalParameters()
    {
        $methods = ['GET'];
        $pattern = '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]';
        $callable = function ($request, $response, $args) {
            return $response;
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/archive/2015',
            $this->routeCollector->pathFor('foo', ['year' => '2015'])
        );
        $this->assertEquals(
            '/archive/2015/07',
            $this->routeCollector->pathFor('foo', ['year' => '2015', 'month' => '07'])
        );
        $this->assertEquals(
            '/archive/2015/07/d/19',
            $this->routeCollector->pathFor('foo', ['year' => '2015', 'month' => '07', 'day' => '19'])
        );
    }

    public function testPathForWithQueryParameters()
    {
        $methods = ['GET'];
        $pattern = '/hello/{name}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s', $args['name']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh?a=b&c=d',
            $this->routeCollector->pathFor('foo', ['name' => 'josh'], ['a' => 'b', 'c' => 'd'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPathForWithMissingSegmentData()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->routeCollector->pathFor('foo', ['last' => 'lockhart']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPathForRouteNotExists()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->routeCollector->pathFor('bar', ['first' => 'josh', 'last' => 'lockhart']);
    }

    public function testGetRouteInvocationStrategy()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver, null, $invocationStrategy);

        $this->assertEquals($invocationStrategy, $routeCollector->getDefaultInvocationStrategy());
    }

    public function testGetCallableResolver()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);

        $this->assertEquals($callableResolver, $routeCollector->getCallableResolver());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveRoute()
    {
        $methods = ['GET'];
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello ignore me');
        };

        $this->routeCollector->setBasePath('/base/path');

        $route1 = $this->routeCollector->map($methods, '/foo', $callable);
        $route1->setName('foo');

        $route2 = $this->routeCollector->map($methods, '/bar', $callable);
        $route2->setName('bar');

        $route3 = $this->routeCollector->map($methods, '/fizz', $callable);
        $route3->setName('fizz');

        $route4 = $this->routeCollector->map($methods, '/buzz', $callable);
        $route4->setName('buzz');

        $routeToRemove = $this->routeCollector->getNamedRoute('fizz');

        $routeCountBefore = count($this->routeCollector->getRoutes());
        $this->routeCollector->removeNamedRoute($routeToRemove->getName());
        $routeCountAfter = count($this->routeCollector->getRoutes());

        // Assert number of routes is now less by 1
        $this->assertEquals(
            ($routeCountBefore - 1),
            $routeCountAfter
        );

        // Simple test that the correct route was removed
        $this->assertEquals(
            $this->routeCollector->getNamedRoute('foo')->getName(),
            'foo'
        );

        $this->assertEquals(
            $this->routeCollector->getNamedRoute('bar')->getName(),
            'bar'
        );

        $this->assertEquals(
            $this->routeCollector->getNamedRoute('buzz')->getName(),
            'buzz'
        );

        // Exception thrown here, route no longer exists
        $this->routeCollector->getNamedRoute($routeToRemove->getName());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRouteRemovalNotExists()
    {
        $this->routeCollector->setBasePath('/base/path');
        $this->routeCollector->removeNamedRoute('non-existing-route-name');
    }

    public function testPathForWithModifiedRoutePattern()
    {
        $this->routeCollector->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['voornaam'], $args['achternaam']);
        };

        /** @var Route $route */
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');
        $route->setPattern('/hallo/{voornaam:\w+}/{achternaam}');

        $this->assertEquals(
            '/hallo/josh/lockhart',
            $this->routeCollector->relativePathFor('foo', ['voornaam' => 'josh', 'achternaam' => 'lockhart'])
        );
    }

    /**
     * Test if cacheFile is not a writable directory
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Router cacheFile directory must be writable
     */
    public function testSettingInvalidCacheFileNotExisting()
    {
        $this->routeCollector->setCacheFile(
            dirname(__FILE__) . uniqid(microtime(true)) . '/' . uniqid(microtime(true))
        );
    }

    /**
     * Test cached routes file is created & that it holds our routes.
     */
    public function testRouteCacheFileCanBeDispatched()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $this->routeCollector->map($methods, $pattern, $callable)->setName('foo');

        $cacheFile = dirname(__FILE__) . '/' . uniqid(microtime(true));
        $this->routeCollector->setCacheFile($cacheFile);

        $routeResolver = new RouteResolver($this->routeCollector);
        $class = new ReflectionClass(RouteResolver::class);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($routeResolver);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertFileExists($cacheFile, 'cache file was not created');

        // instantiate a new routeCollector & load the cached routes file & see if
        // we can dispatch to the route we cached.
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector2 = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector2->setCacheFile($cacheFile);
        $routeResolver2 = new RouteResolver($routeCollector2);

        $class = new ReflectionClass($routeResolver2);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher2 = $method->invoke($routeResolver2);

        /** @var RoutingResults $result */
        $result = $dispatcher2->dispatch('GET', '/hello/josh/lockhart');
        $this->assertSame(Dispatcher::FOUND, $result->getRouteStatus());

        unlink($cacheFile);
    }

    /**
     * Test that the routeCollector urlFor will proxy into a pathFor method, and trigger
     * the user deprecated warning
     */
    public function testUrlForAliasesPathFor()
    {
        //create a temporary error handler, store the error str in this value
        $errorString = null;

        set_error_handler(function ($no, $str) use (&$errorString) {
            $errorString = $str;
        }, E_USER_DEPRECATED);

        //create the parameters we expect
        $name = 'foo';
        $data = ['name' => 'josh'];
        $queryParams = ['a' => 'b', 'c' => 'd'];

        $routeCollector = $this
            ->getMockBuilder(RouteCollector::class)
            ->setConstructorArgs([$this->getResponseFactory(), new CallableResolver()])
            ->setMethods(['pathFor'])
            ->getMock();
        $routeCollector->expects($this->once())->method('pathFor')->with($name, $data, $queryParams);

        /** @var RouteCollectorInterface $routeCollector */
        $routeCollector->urlFor($name, $data, $queryParams);

        //check that our error was triggered
        $this->assertEquals($errorString, 'urlFor() is deprecated. Use pathFor() instead.');

        restore_error_handler();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLookupRouteThrowsExceptionIfRouteNotFound()
    {
        $this->routeCollector->lookupRoute("thisIsMissing");
    }
}
