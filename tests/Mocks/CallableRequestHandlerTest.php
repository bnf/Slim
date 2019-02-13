<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Mocks;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Tests\Providers\PSR7ObjectProvider;

/**
 * Mock object for Slim\Tests\CallableResolverTest
 */
class CallableRequestHandlerTest implements RequestHandlerInterface
{
    public static $CalledCount = 0;

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        static::$CalledCount++;

        $psr7ObjectProvider = new PSR7ObjectProvider();
        $responseFactory = $psr7ObjectProvider->getResponseFactory();

        $response = $responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/plain');
        $calledCount = static::$CalledCount;
        $response->getBody()->write("{$calledCount}");

        return $response;
    }

    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        $psr7ObjectProvider = new PSR7ObjectProvider();
        $responseFactory = $psr7ObjectProvider->getResponseFactory();

        $response = $responseFactory->createResponse();

        return $response;
    }

    public function custom(ServerRequestInterface $request) : ResponseInterface
    {
        return $responseFactory->createResponse();
    }
}
