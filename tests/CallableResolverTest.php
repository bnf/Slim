<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;
use Slim\CallableResolver;
use Slim\Tests\Mocks\CallableRequestHandlerTest;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvokableTest;
use Slim\Tests\Mocks\RequestHandlerTest;

class CallableResolverTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Pimple
     */
    private $pimple;

    public function setUp()
    {
        CallableTest::$CalledCount = 0;
        InvokableTest::$CalledCount = 0;
        RequestHandlerTest::$CalledCount = 0;
        CallableRequestHandlerTest::$CalledCount = 0;

        $this->pimple = new Pimple;
        $this->container = new Container($this->pimple);
    }

    public function testClosure()
    {
        $test = function () {
            // @todo  this should rather be `CallableResolverTest::assertNull($this)`, as the container in CallableResolver
            // is null
            CallableResolverTest::assertInstanceOf(CallableResolverTest::class, $this);
            static $called_count = 0;
            return $called_count++;
        };
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($test);
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testClosureBindsContainer()
    {
        $test = function () {
            TestCase::assertInstanceof(ContainerInterface::class, $this);
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve($test);
        $callable();
    }

    public function testClosureInContainerIsResolvedAndBindsContainer()
    {
        $this->pimple['a_closure'] = $this->pimple->protect(function () {
            TestCase::assertInstanceof(ContainerInterface::class, $this);
            static $called_count = 0;
            return ++$called_count;
        });
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('a_closure');
        $this->assertEquals("1", $callable());
    }

    public function testFunctionName()
    {
        // @codingStandardsIgnoreStart
        function testCallable()
        {
            static $called_count = 0;
            return $called_count++;
        };
        // @codingStandardsIgnoreEnd

        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__ . '\testCallable');
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testObjMethodArrayInContainer()
    {
        $obj = new CallableTest();
        $this->pimple['a_method_array'] = [$obj, 'toCall'];
        $obj = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('a_method_array');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallableContainer()
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($this->container, CallableTest::$CalledContainer);
    }

    public function testContainer()
    {
        $this->pimple['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('callable_service:toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer()
    {
        $this->pimple['an_invokable'] = function ($c) {
            return new InvokableTest();
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('an_invokable');
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\InvokableTest');
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClass()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class);
        $callable($request);
        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($obj);
        $callable($request);
        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testObjPsrRequestHandlerClassInContainer()
    {
        $this->pimple['a_requesthandler'] = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('a_requesthandler');
        $callable($request);
        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class . ':custom');
        $this->assertInternalType('array', $callable);
        $this->assertInstanceOf(RequestHandlerTest::class, $callable[0]);
        $this->assertEquals('custom', $callable[1]);
    }

    public function testResolutionToAPsrRequestHandlerClassWhichIsAlsoInvokable()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(CallableRequestHandlerTest::class);
        $callable($request);
        $this->assertEquals("1", CallableRequestHandlerTest::$CalledCount);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMethodNotFoundThrowException()
    {
        $this->pimple['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('callable_service:noFound');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMethodNotFoundThrowExceptionForInvokable()
    {
        $this->pimple['an_invokable'] = function ($c) {
            return new InvokableTest();
        };
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('an_invokable:noFound');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMethodNotFoundThrowExceptionForRequestHandler()
    {
        $this->pimple['a_requesthandler'] = new RequestHandlerTest();
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('a_requesthandler:noFound');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFunctionNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('noFound');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Callable Unknown does not exist
     */
    public function testClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('Unknown:notFound');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage is not resolvable
     */
    public function testCallableClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve(['Unknown', 'notFound']);
    }
}
