<?php

namespace XooPress\Tests\Core;

use PHPUnit\Framework\TestCase;
use XooPress\Core\Container;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testInstance(): void
    {
        $this->container->instance('foo', 'bar');
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    public function testHasInstance(): void
    {
        $this->container->instance('foo', 'bar');
        $this->assertTrue($this->container->has('foo'));
        $this->assertFalse($this->container->has('nonexistent'));
    }

    public function testBindClosure(): void
    {
        $this->container->bind('greeter', function ($container) {
            return 'Hello, World!';
        });
        $this->assertEquals('Hello, World!', $this->container->get('greeter'));
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton('counter', function ($container) {
            static $count = 0;
            $count++;
            return $count;
        });

        $this->assertEquals(1, $this->container->get('counter'));
        $this->assertEquals(1, $this->container->get('counter')); // Same instance
    }

    public function testBindReturnsNewInstanceEachTime(): void
    {
        $this->container->bind('counter', function ($container) {
            static $count = 0;
            $count++;
            return $count;
        });

        $this->assertEquals(1, $this->container->get('counter'));
        $this->assertEquals(2, $this->container->get('counter')); // New instance
    }

    public function testBindWithStringClass(): void
    {
        $this->container->bind('stdClass', \stdClass::class);
        $instance = $this->container->get('stdClass');
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    public function testGetThrowsExceptionForUnbound(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No binding found for [nonexistent]');
        $this->container->get('nonexistent');
    }

    public function testHasClassReturnsTrue(): void
    {
        $this->assertTrue($this->container->has(\stdClass::class));
    }

    public function testGetResolvesClassAutomatically(): void
    {
        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    public function testSingletonWithStringClass(): void
    {
        $this->container->singleton('shared_std', \stdClass::class);
        $first = $this->container->get('shared_std');
        $second = $this->container->get('shared_std');
        $this->assertSame($first, $second);
    }

    public function testInstanceOverridesBinding(): void
    {
        $this->container->bind('key', fn() => 'from_binding');
        $this->container->instance('key', 'from_instance');
        $this->assertEquals('from_instance', $this->container->get('key'));
    }

    public function testContainerReceivesItselfAsParameter(): void
    {
        $this->container->bind('self_check', function ($container) {
            return $container;
        });
        $this->assertSame($this->container, $this->container->get('self_check'));
    }

    public function testBindWithNullConcreteUsesAbstract(): void
    {
        $this->container->bind(\stdClass::class);
        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }
}
