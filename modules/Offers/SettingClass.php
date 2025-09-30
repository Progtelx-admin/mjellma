<?php
namespace Modules\Offers;

use Modules\Core\Abstracts\BaseSettingsClass;
use Modules\Core\Models\Settings;

class SettingClass extends BaseSettingsClass
{
    public static function getSettingPages()
    {
        $configs = [
            'offers' => [
                'id'       => 'offers',
                'title'    => __("Offers Settings"),
                'position' => 48,
                'view'     => "offers::admin.settings.offers",
                "keys"     => [
                    'offers_disable',
                    // add more keys if needed
                ],
                'html_keys' => [],
                'filter_demo_mode' => [],
            ],
        ];
        // same filtered return pattern as Hotel
        return apply_filters(Hook::OFFERS_SETTING_CONFIG, $configs);
    }
}
