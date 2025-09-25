<?php

namespace Modules\Offers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    protected string $webNamespace   = 'Modules\\Offers\\Controllers';
    protected string $adminNamespace = 'Modules\\Offers\\Admin';

    public function boot(): void
    {
        parent::boot();

        $this->routes(function () {
            Route::middleware('web')
                ->namespace($this->webNamespace)
                ->group(__DIR__ . '/Routes/web.php');

            Route::middleware(['web','dashboard']) // or ['web','auth']
                ->namespace($this->adminNamespace)
                ->prefix(config('admin.admin_route_prefix', 'admin') . '/module/offers')
                ->group(__DIR__ . '/Routes/admin.php');
        });
    }
}
