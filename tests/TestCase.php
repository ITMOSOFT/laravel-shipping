<?php
namespace ItmoSoft\Shipping\Tests;
use Orchestra\Testbench\TestCase as Orchestra;
use ItmoSoft\Shipping\ShippingServiceProvider;
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array { return [ShippingServiceProvider::class]; }
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]);
        $app['config']->set('itmosoft-shipping.table_prefix', 'shipping_');
    }
    protected function setUp(): void { parent::setUp(); $this->loadMigrationsFrom(__DIR__.'/../database/migrations'); }
}
