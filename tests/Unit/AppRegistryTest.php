<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;

class AppRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        AppRegistry::flush();
    }

    public function test_registry_stores_and_retrieves_apps()
    {
        $appA = new Application(__DIR__);
        AppRegistry::register('plugin-a', $appA);

        $appB = new Application(__DIR__);
        AppRegistry::register('plugin-b', $appB);

        $this->assertSame($appA, AppRegistry::get('plugin-a'));
        $this->assertSame($appB, AppRegistry::get('plugin-b'));
    }

    public function test_registry_throws_on_missing_key()
    {
        $this->expectException(\RuntimeException::class);
        AppRegistry::get('missing');
    }

    public function test_registry_has_method()
    {
        $app = new Application(__DIR__);
        AppRegistry::register('exists', $app);

        $this->assertTrue(AppRegistry::has('exists'));
        $this->assertFalse(AppRegistry::has('missing'));
    }

    public function test_helper_resolves_correct_app_by_key()
    {
        $appA = new Application(__DIR__);
        $appA->bind('name', fn() => 'A');
        AppRegistry::register('plugin-a', $appA);

        $appB = new Application(__DIR__);
        $appB->bind('name', fn() => 'B');
        AppRegistry::register('plugin-b', $appB);

        $this->assertSame($appA, wpj_app(null, [], 'plugin-a'));
        $this->assertSame($appB, wpj_app(null, [], 'plugin-b'));

        $this->assertEquals('A', wpj_app('name', [], 'plugin-a'));
        $this->assertEquals('B', wpj_app('name', [], 'plugin-b'));
    }
}
