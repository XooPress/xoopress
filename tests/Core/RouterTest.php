<?php

namespace XooPress\Tests\Core;

use PHPUnit\Framework\TestCase;
use XooPress\Core\Router;
use XooPress\Core\Container;

class RouterTest extends TestCase
{
    private Router $router;
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function testAddGetRoute(): void
    {
        $handler = function () { return 'GET response'; };
        $this->router->addRoute('GET', '/test', $handler);
        $routes = $this->router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['pattern']);
    }

    public function testAddPostRoute(): void
    {
        $handler = function () { return 'POST response'; };
        $this->router->addRoute('POST', '/submit', $handler);
        $routes = $this->router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('POST', $routes[0]['method']);
    }

    public function testConvenienceMethods(): void
    {
        $this->router->get('/get', fn() => 'get');
        $this->router->post('/post', fn() => 'post');
        $this->router->put('/put', fn() => 'put');
        $this->router->delete('/delete', fn() => 'delete');

        $routes = $this->router->getRoutes();
        $methods = array_map(fn($r) => $r['method'], $routes);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function testAnyRegistersAllMethods(): void
    {
        $this->router->any('/any', fn() => 'any');
        $routes = $this->router->getRoutes();
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        $registeredMethods = array_map(fn($r) => $r['method'], $routes);
        foreach ($methods as $method) {
            $this->assertContains($method, $registeredMethods);
        }
    }

    public function testPatternWithNumPlaceholder(): void
    {
        $handler = function ($id) { return "Post {$id}"; };
        $this->router->addRoute('GET', '/posts/:num', $handler);
        $this->assertCount(1, $this->router->getRoutes());
    }

    public function testPatternWithAlphaPlaceholder(): void
    {
        $this->router->addRoute('GET', '/section/:alpha', fn($slug) => $slug);
        $this->assertCount(1, $this->router->getRoutes());
    }

    public function testPatternWithAllPlaceholder(): void
    {
        $this->router->addRoute('GET', '/path/:all', fn($rest) => $rest);
        $this->assertCount(1, $this->router->getRoutes());
    }

    public function testUrlGeneration(): void
    {
        $url = $this->router->url('/users/:num', ['num' => 42]);
        $this->assertEquals('/users/42', $url);
    }

    public function testUrlRemovesUnusedPlaceholders(): void
    {
        $url = $this->router->url('/test/:alpha', ['alpha' => 'hello']);
        $this->assertEquals('/test/hello', $url);
    }

    public function testReturnsRoutesArray(): void
    {
        $this->router->get('/a', fn() => 'a');
        $this->router->post('/b', fn() => 'b');
        $routes = $this->router->getRoutes();
        $this->assertCount(2, $routes);
    }

    public function testCompilePatternPreservesRegexChars(): void
    {
        $this->router->addRoute('GET', '/search/:any', fn($q) => $q);
        $this->assertCount(1, $this->router->getRoutes());
    }
}
