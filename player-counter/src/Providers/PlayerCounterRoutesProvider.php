<?php

namespace Boy132\PlayerCounter\Providers;

use App\Models\Server;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Webmozart\Assert\Assert;

class PlayerCounterRoutesProvider extends RouteServiceProvider
{
    const QUERY_THROTTLE_KEY = 'api.client:server-resource:query';

    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware(['api', 'client-api', 'throttle:api.client'])
                ->prefix('/api/client')
                ->scopeBindings()
                ->group(plugin_path('player-counter', 'routes/api-client.php'));
        });

        RateLimiter::for(self::QUERY_THROTTLE_KEY, function (Request $request) {
            Assert::isInstanceOf($server = $request->route()->parameter('server'), Server::class);

            $limit = config('player-counter.query_rate_limit');

            if ($limit === 0) {
                return Limit::none();
            }

            return Limit::perMinute(max(1, $limit))->by($server->uuid);
        });
    }
}
