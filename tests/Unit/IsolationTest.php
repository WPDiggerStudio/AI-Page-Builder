<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;
use stdClass;

class IsolationTest extends TestCase
{
    protected function setUp(): void
    {
        AppRegistry::flush();
    }

    public function test_applications_are_isolated()
    {
        $app1 = new Application(__DIR__);
        $app2 = new Application(__DIR__);

        $app1->bind('foo', fn() => 'bar');
        $app2->bind('foo', fn() => 'baz');

        $this->assertEquals('bar', $app1->make('foo'));
        $this->assertEquals('baz', $app2->make('foo'));
    }

    public function test_singletons_are_isolated()
    {
        $app1 = new Application(__DIR__);
        $app2 = new Application(__DIR__);

        $app1->singleton('shared', fn() => new stdClass());
        $app2->singleton('shared', fn() => new stdClass());

        $obj1 = $app1->make('shared');
        $obj2 = $app2->make('shared');

        $this->assertNotSame($obj1, $obj2);
    }

    public function test_instances_are_isolated()
    {
        $app1 = new Application(__DIR__);
        $app2 = new Application(__DIR__);

        $instance1 = new stdClass();
        $instance1->name = 'app1';
        $app1->instance('instance', $instance1);

        $instance2 = new stdClass();
        $instance2->name = 'app2';
        $app2->instance('instance', $instance2);

        $this->assertEquals('app1', $app1->make('instance')->name);
        $this->assertEquals('app2', $app2->make('instance')->name);
    }
}
