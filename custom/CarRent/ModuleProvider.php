<?php
namespace Custom\CarRent;

use Custom\ModuleServiceProvider;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot()
    {
        // Register permissions
        PermissionHelper::add([
            'carrent_view',
        ]);
    }

    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        // Build URL manually to avoid route() issues during menu generation
        $adminPrefix = config('admin.admin_route_prefix', 'admin');
        $url = url($adminPrefix . '/module/carrent/reservations');

        return [
            'car_rent_reservations' => [
                "position" => 50,
                'url' => $url,
                'title' => __('Car Rent Reservations'),
                'icon' => 'icon ion-ios-car',
                // Remove permission check temporarily to ensure menu shows
                // 'permission' => 'carrent_view',
            ],
        ];
    }
}

