<?php
namespace Themes\BC\Core\Walkers;
class MenuWalker
{
    protected static $currentMenuItem;
    protected        $menu;
    protected $activeItems = [];

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    public function generate()
    {
        $items = json_decode($this->menu->items, true);
        if (!empty($items)) {
            echo '<ul class="main-menu menu-generated" style="color: #1a2b48;">';
            $this->generateTree($items);
            echo '</ul>';
        }
    }

    public function generateTree($items = [],$depth = 0,$parentKey = '')
    {
        $mega_menu = $this->options['enable_mega_menu'] ?? false;
        foreach ($items as $k=>$item) {

            $class = e($item['class'] ?? '');
            $url = $item['url'] ?? '';
            $item['target'] = $item['target'] ?? '';
            if (!isset($item['item_model']))
                continue;
            if (class_exists($item['item_model'])) {
                $itemClass = $item['item_model'];
                $itemObj = $itemClass::find($item['id']);
                if (empty($itemObj)) {
                    continue;
                }
                $url = $itemObj->getDetailUrl();
            }
            if ($this->checkCurrentMenu($item, $url))
            {
                $class .= ' active';
                $this->activeItems[] = $parentKey;
            }

            if (!empty($item['children'])) {
                ob_start();
                $this->generateTree($item['children'],$depth + 1,$parentKey.'_'.$k);
                $html = ob_get_clean();
                if(in_array($parentKey.'_'.$k,$this->activeItems)){
                    $class.=' active ';
                }
            }
            $class.= ($depth == 0 && !empty($item['mega_menu'])) ? ' -has-mega-menu' : null;
            $class.=' depth-'.($depth);
            printf('<li class="%s">', $class);
            $itemName = $item['name'];
            if (!empty($item['children'])) {
                $item['name'] .= ' <i class="caret fa fa-angle-down"></i>';
            }
            printf('<a style="color:#1a2b48;"  target="%s" href="%s" >%s</a>', e($item['target']), e($url), clean($item['name']));


            if (!empty($item['children'])) {
                if ($depth == 0 && !empty($item['mega_menu'])){
                    echo '<div class="mega mb-menu-none column-'. ($item['mega_columns'] ?? '') .' '.(!empty($item['mega_image_url']) ? '--has-mega-image' : '').'">';
                    echo '<ul class="subnav children-menu menu-dropdown">';
                        echo $html;
                    echo "</ul>";
                    if(!empty($item['mega_image_url'])){
                        echo '<div class="mega-image">';
                        echo '<img src="'.$item['mega_image_url'].'" alt="'. $itemName .'">';
                        echo '</div>';
                    }
                    echo '</div>';
                }else {
                    if (!empty($item['children'])) {
                        echo '<ul class="subnav children-menu menu-dropdown">';
                        echo $html;
                        echo "</ul>";
                    }
                }
            }

            echo '</li>';
        }
    }

    protected function checkCurrentMenu($item, $url = '')
    {

        if(trim($url,'/') == request()->path()){
            return true;
        }
        if (!static::$currentMenuItem)
            return false;
        if (empty($item['item_model']))
            return false;
        if (is_string(static::$currentMenuItem) and ($url == static::$currentMenuItem or $url == url(static::$currentMenuItem))) {
            return true;
        }
        if (is_object(static::$currentMenuItem) and get_class(static::$currentMenuItem) == $item['item_model'] && static::$currentMenuItem->id == $item['id']) {
            return true;
        }
        return false;
    }

    public static function setCurrentMenuItem($item)
    {
        static::$currentMenuItem = $item;
    }

    public static function getActiveMenu()
    {
        return static::$currentMenuItem;
    }
}
