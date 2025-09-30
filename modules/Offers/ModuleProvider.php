<?php

namespace Modules\Offers;

use Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Blade;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{
    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'offers');

        Blade::componentNamespace('Modules\\Offers\\Views\\Components', 'offers');

        if (class_exists(PermissionHelper::class)) {
            PermissionHelper::add(['offers_view','offers_manage']);
        }
    }

    public static function getAdminMenu()
    {
        $fallback = url(trim(config('admin.admin_route_prefix', 'admin'), '/').'/module/offers/sections');
        try { $url = route('offers.admin.sections.index'); } catch (\Throwable $e) { $url = $fallback; }

        return [
            'offers' => [
                'position'   => 48,
                'url'        => $url,
                'title'      => __('Offers'),
                'icon'       => 'fa fa-gift',
                'permission' => 'offers_view',
                'children'   => [
                    'offers_sections' => [
                        'url'        => $url,
                        'title'      => __('Sections'),
                        'permission' => 'offers_view',
                    ],
                ],
            ],
        ];
    }
}
