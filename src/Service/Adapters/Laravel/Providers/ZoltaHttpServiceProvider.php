<?php

declare(strict_types=1);

namespace Zolta\Http\Service\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Zolta\Http\Authorization\AuthorizationMatrix;

class ZoltaHttpServiceProvider extends ServiceProvider
{
    /**
     * Register all HTTP service providers in order.
     */
    public function register(): void
    {
        $httpConfigPath = __DIR__.'/../config/zolta-http.php';
        $talredHttpConfigPath = __DIR__.'/../config/talred-http.php';
        $securityConfigPath = dirname(__DIR__, 4).'/Authorization/Adapters/Laravel/config/zolta-security.php';
        $talredSecurityConfigPath = dirname(__DIR__, 4).'/Authorization/Adapters/Laravel/config/talred-security.php';
        $identityConfigPath = __DIR__.'/../config/zolta_identity.php';
        $talredIdentityConfigPath = __DIR__.'/../config/talred_identity.php';

        $this->mergeConfigFrom($httpConfigPath, 'zolta-http');
        $this->mergeConfigFrom($securityConfigPath, 'zolta-security');
        $this->mergeConfigFrom($identityConfigPath, 'zolta_identity');

        $configured = $this->mergeConfigRecursively(
            (array) config('zolta', []),
            $this->app['config']->has('talred')
                ? (array) config('talred', [])
                : [],
        );
        $canonicalHttp = (array) ($configured['http'] ?? []);
        $canonicalSecurity = (array) ($configured['security'] ?? []);
        $canonicalIdentity = (array) ($configured['identity'] ?? []);

        // Merge order: package defaults -> zolta aliases -> talred aliases ->
        // canonical root keys. This keeps compatibility while making the
        // Talred surface the preferred override when both roots are present.
        $configured['http'] = $this->mergeConfigRecursively(
            (array) require $httpConfigPath,
            (array) config('zolta-http', []),
        );
        $configured['http'] = $this->mergeConfigRecursively(
            $configured['http'],
            (array) config('talred-http', (array) require $talredHttpConfigPath),
        );
        $configured['http'] = $this->mergeConfigRecursively(
            $configured['http'],
            $canonicalHttp,
        );

        $configured['security'] = $this->mergeConfigRecursively(
            (array) require $securityConfigPath,
            (array) config('zolta-security', []),
        );
        $configured['security'] = $this->mergeConfigRecursively(
            $configured['security'],
            (array) config('talred-security', (array) require $talredSecurityConfigPath),
        );
        $configured['security'] = $this->mergeConfigRecursively(
            $configured['security'],
            $canonicalSecurity,
        );

        $configured['identity'] = $this->mergeConfigRecursively(
            (array) require $identityConfigPath,
            (array) config('zolta_identity', []),
        );
        $configured['identity'] = $this->mergeConfigRecursively(
            $configured['identity'],
            (array) config('talred_identity', (array) require $talredIdentityConfigPath),
        );
        $configured['identity'] = $this->mergeConfigRecursively(
            $configured['identity'],
            $canonicalIdentity,
        );

        // Talred is the public configuration surface; zolta remains a
        // mirrored technical compatibility surface for existing consumers.
        $this->app['config']->set('zolta', $configured);
        $this->app['config']->set('talred', $configured);

        // Package aliases remain available under both names.
        $this->app['config']->set('zolta-http', $configured['http']);
        $this->app['config']->set('talred-http', $configured['http']);
        $this->app['config']->set('zolta-security', $configured['security']);
        $this->app['config']->set('talred-security', $configured['security']);
        $this->app['config']->set('zolta_identity', $configured['identity']);
        $this->app['config']->set('talred_identity', $configured['identity']);

        // Core bindings
        $this->app->register(LaravelBridgeServiceProvider::class);
        $this->app->register(HTTPServiceProvider::class);
        $this->app->register(ArraySerializationServiceProvider::class);
        $this->app->register(ExceptionServiceProvider::class);

        // Artisan commands
        $this->app->register(ZoltaCommandServiceProvider::class);
    }

    /**
     * Boot: configure authorization matrix and publish configs.
     */
    public function boot(): void
    {
        $securityConfig = config('zolta.security', config('zolta-security', []));
        if (is_array($securityConfig) && $securityConfig !== []) {
            AuthorizationMatrix::configure($securityConfig);
        }

        $this->publishes([
            __DIR__.'/../config/zolta-http.php' => config_path('zolta-http.php'),
        ], 'zolta-http-config');

        $this->publishes([
            dirname(__DIR__, 4).'/Authorization/Adapters/Laravel/config/zolta-security.php' => config_path('zolta-security.php'),
        ], 'zolta-security-config');

        $this->publishes([
            __DIR__.'/../config/zolta-http.php' => config_path('talred-http.php'),
        ], 'talred-http-config');

        $this->publishes([
            dirname(__DIR__, 4).'/Authorization/Adapters/Laravel/config/zolta-security.php' => config_path('talred-security.php'),
        ], 'talred-security-config');

        $this->publishes([
            __DIR__.'/../config/zolta_identity.php' => config_path('talred_identity.php'),
        ], 'talred-identity-config');
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
