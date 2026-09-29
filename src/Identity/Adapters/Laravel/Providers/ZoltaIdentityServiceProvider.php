<?php

declare(strict_types=1);

namespace Zolta\Http\Identity\Laravel\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Zolta\Http\Identity\Laravel\Http\Middleware\IntrospectIdentity;

final class ZoltaIdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__.'/../config/identity-consumer.php';
        $talredConfigPath = __DIR__.'/../config/talred-identity-consumer.php';

        $this->mergeConfigFrom($configPath, 'identity-consumer');

        $configured = $this->mergeConfigRecursively(
            (array) config('zolta', []),
            $this->app['config']->has('talred')
                ? (array) config('talred', [])
                : [],
        );
        $canonicalIdentityConsumer = (array) ($configured['identity_consumer'] ?? []);
        $configured['identity_consumer'] = (array) require $configPath;
        $configured['identity_consumer'] = $this->mergeConfigRecursively(
            $configured['identity_consumer'],
            (array) config('identity-consumer', []),
        );
        $configured['identity_consumer'] = $this->mergeConfigRecursively(
            $configured['identity_consumer'],
            (array) config('talred-identity-consumer', (array) require $talredConfigPath),
        );
        $configured['identity_consumer'] = $this->mergeConfigRecursively(
            $configured['identity_consumer'],
            $canonicalIdentityConsumer,
        );

        // Talred is the public configuration surface; zolta remains a
        // mirrored technical compatibility surface for existing consumers.
        $this->app['config']->set('zolta', $configured);
        $this->app['config']->set('talred', $configured);
        $this->app['config']->set('identity-consumer', $configured['identity_consumer']);
        $this->app['config']->set('talred-identity-consumer', $configured['identity_consumer']);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('identity.introspect', IntrospectIdentity::class);
        $this->publishes([
            __DIR__.'/../config/identity-consumer.php' => config_path('identity-consumer.php'),
        ], 'identity-consumer-config');
        $this->publishes([
            __DIR__.'/../config/identity-consumer.php' => config_path('talred-identity-consumer.php'),
        ], 'talred-identity-consumer-config');
    }

    /**
     * @param  array<string,mixed>  $defaults
     * @param  array<string,mixed>  $configured
     * @return array<string,mixed>
     */
    private function mergeConfigRecursively(array $defaults, array $configured): array
    {
        $merged = $defaults;

        foreach ($configured as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeConfigRecursively($merged[$key], $value);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}
