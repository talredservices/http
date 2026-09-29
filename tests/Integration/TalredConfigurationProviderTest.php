<?php

declare(strict_types=1);

namespace Zolta\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Zolta\Http\Identity\Laravel\Providers\ZoltaIdentityServiceProvider;
use Zolta\Http\Service\Laravel\Providers\ZoltaHttpServiceProvider;

final class TalredConfigurationProviderTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('talred', [
            'http' => [
                'routes' => [
                    'exclude_paths' => ['app/Talred/Controllers'],
                ],
            ],
            'identity_consumer' => [
                'timeout_seconds' => 11,
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ZoltaHttpServiceProvider::class,
            ZoltaIdentityServiceProvider::class,
        ];
    }

    public function test_talred_configuration_is_resolved_and_mirrored_to_zolta_consumers(): void
    {
        (new ZoltaHttpServiceProvider($this->app))->register();
        (new ZoltaIdentityServiceProvider($this->app))->register();

        $this->assertSame(
            ['app/Talred/Controllers'],
            config('talred.http.routes.exclude_paths'),
        );
        $this->assertSame(
            config('talred.http.routes.exclude_paths'),
            config('zolta-http.routes.exclude_paths'),
        );
        $this->assertSame(11, config('talred.identity_consumer.timeout_seconds'));
        $this->assertSame(
            config('talred.identity_consumer.timeout_seconds'),
            config('identity-consumer.timeout_seconds'),
        );
    }
}
