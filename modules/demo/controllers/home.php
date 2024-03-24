<?php
/**
 * @filesource modules/demo/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Home;

use Index\Home\Controller as Home;
use Kotchasan\Http\Request;

/**
 * Controller สำหรับการแสดงผลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นสร้าง card
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addCard(Request $request, $card, $login)
    {
        if ($login) {
            Home::renderCard($card, 'icon-cloud', 'Demo', rand(0, 100), 'Card ของโมดูล', 'index.php?module=demo&amp;page=typography');
            Home::renderCard($card, 'icon-calendar', '{LNG_Date}', \Kotchasan\Date::format(time(), 'd M'), '{LNG_today}', 'index.php');
            Home::renderCard($card, 'icon-star0', 'Kotchasan', VERSION, '{LNG_Version}', 'https://www.kotchasan.com/');
            Home::renderCard($card, 'icon-cloud', 'Demo', rand(0, 100), 'Card ของโมดูล', 'index.php?module=demo&amp;page=typography');
            Home::renderCard($card, 'icon-calendar', '{LNG_Date}', \Kotchasan\Date::format(time(), 'd M'), '{LNG_today}', 'index.php');
            Home::renderCard($card, 'icon-star0', 'Kotchasan', VERSION, '{LNG_Version}', 'https://www.kotchasan.com/');
            Home::renderCard($card, 'icon-cloud', 'Demo', rand(0, 100), 'Card ของโมดูล', 'index.php?module=demo&amp;page=typography');
            Home::renderCard($card, 'icon-calendar', '{LNG_Date}', \Kotchasan\Date::format(time(), 'd M'), '{LNG_today}', 'index.php');
            Home::renderCard($card, 'icon-star0', 'Kotchasan', VERSION, '{LNG_Version}', 'https://www.kotchasan.com/');
            Home::renderCard($card, 'icon-cloud', 'Demo', rand(0, 100), 'Card ของโมดูล', 'index.php?module=demo&amp;page=typography');
            Home::renderCard($card, 'icon-calendar', '{LNG_Date}', \Kotchasan\Date::format(time(), 'd M'), '{LNG_today}', 'index.php');
            Home::renderCard($card, 'icon-star0', 'Kotchasan', VERSION, '{LNG_Version}', 'https://www.kotchasan.com/');
        }
    }

    /**
     * ฟังก์ชั่นสร้าง เมนูด่วน
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addMenu(Request $request, $menu, $login)
    {
        if ($login) {
            Home::renderQuickMenu($menu, 'icon-design', '{LNG_Typography}', 'index.php?module=demo&amp;page=typography');
            Home::renderQuickMenu($menu, 'icon-chat', '{LNG_Message}', 'index.php?module=demo&amp;page=message');
            Home::renderQuickMenu($menu, 'icon-index', '{LNG_Form &amp; Form Component}', 'index.php?module=demo&amp;page=form', '_self');
            Home::renderQuickMenu($menu, 'icon-index', '{LNG_Button}', 'index.php?module=demo&amp;page=button', '_self');
            Home::renderQuickMenu($menu, 'icon-map', '{LNG_Listbox multi select}', 'index.php?module=demo&amp;page=multiselect&amp;provinceID=103&amp;amphurID=10301&amp;districtID=1030107');
            Home::renderQuickMenu($menu, 'icon-upload', '{LNG_Ajax Upload}', 'index.php?module=demo-upload');
            Home::renderQuickMenu($menu, 'icon-bars', '{LNG_Graphs}', 'index.php?module=demo&amp;page=graphs');
            Home::renderQuickMenu($menu, 'icon-table', '{LNG_Table}', 'index.php?module=demo-table');
            Home::renderQuickMenu($menu, 'icon-calendar', '{LNG_Event Calendar}', 'index.php?module=demo-calendar');
            Home::renderQuickMenu($menu, 'icon-menus', '{LNG_Tabs}', 'index.php?module=demo-tabs');
            Home::renderQuickMenu($menu, 'icon-template', '{LNG_Grid}', 'index.php?module=demo&amp;page=grid');
        }
    }
}
