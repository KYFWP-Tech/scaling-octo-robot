<?php

namespace App\Providers;

use App\Enums\Role as AdminRole;
use App\Models\Admin;
use App\Models\Article;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Episode;
use App\Models\Permission;
use App\Models\Podcast;
use App\Models\Reader;
use App\Models\Reflection;
use App\Models\Role;
use App\Models\User;
use App\Models\Verification;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        if ($this->app->environment('local')) {
            Model::shouldBeStrict();
        }

        if ($this->app->environment('production') || $this->app->environment('development')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : $rule;
        });

        Relation::enforceMorphMap([
            'admin' => Admin::class,
            'article' => Article::class,
            'category' => Category::class,
            'episode' => Episode::class,
            'permission' => Permission::class,
            'podcast' => Podcast::class,
            'contributor' => Contributor::class,
            'reader' => Reader::class,
            'reflection' => Reflection::class,
            'role' => Role::class,
            'user' => User::class,
            'verification' => Verification::class,
        ]);

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });

        Gate::before(fn (User $user, string $ability) => $user->hasRole(AdminRole::SuperAdmin->value) ? true : null);

        /**
         * Get the authenticated user's profile and make it available to the request guard.
         *
         * @return Admin|Contributor|Reader|null
         */
        RequestGuard::macro('profile', function () {
            return (Auth::check()) ? Auth::user()->profile : null;
        });

        /**
         * Get the authenticated user's profile and make it available to the session guard.
         *
         * @return Admin|Contributor|Reader|null
         */
        SessionGuard::macro('profile', function () {
            return (Auth::check()) ? Auth::user()->profile : null;
        });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url').'/auth/reset-password?token='.$token.'&email='.$user->email;
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email', $request->ip());

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            $key = $request->session()->get('login.id', $request->ip());

            return Limit::perMinute(5)->by($key);
        });
    }
}
