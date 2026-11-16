<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginAuthController;
use App\Http\Middleware\NoCacheMiddleware;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // With cached routes, Laravel 9 trims the trailing slash from the
        // request URI. When installed below a path such as /hris/public/, this
        // loses the base path and the cached matcher rejects GET /.
        // A dynamic route lets the normal Laravel matcher handle that request.
        $this->booted(function () {
            $this->app->booted(function () {
                if ($this->app->routesAreCached()) {
                    Route::middleware(['web', 'maintenance.login', NoCacheMiddleware::class])
                        ->get('/', [LoginAuthController::class, 'getLogin']);
                }
            });
        });

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Slow down password and code guessing per account (or per browser for codes).
        // The per-address cap is loose on purpose: a whole campus can share one IP.
        $tooMany = fn () => redirect()->route('getLogin')
            ->with('error', 'Too many sign-in attempts. Please wait a minute and try again.');
        RateLimiter::for('login', function (Request $request) use ($tooMany) {
            $username = strtolower(trim((string) $request->input('username')));
            return [
                Limit::perMinute(5)->by('login:'.$username.'|'.$request->ip())->response($tooMany),
                Limit::perMinute(300)->by('login-ip:'.$request->ip())->response($tooMany),
            ];
        });

        RateLimiter::for('verify-code', function (Request $request) use ($tooMany) {
            return [
                Limit::perMinute(5)->by('verify:'.$request->session()->getId())->response($tooMany),
                Limit::perMinute(300)->by('verify-ip:'.$request->ip())->response($tooMany),
            ];
        });
    }
}
