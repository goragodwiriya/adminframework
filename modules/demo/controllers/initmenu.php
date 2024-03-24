<?php
/**
 * @filesource modules/demo/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Initmenu;

use Kotchasan\Http\Request;

/**
 * Init Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        if ($login) {
            // รายการเมนูย่อย
            $submenus = array(
                array(
                    'text' => 'Typography',
                    'url' => 'index.php?module=demo&amp;page=typography'
                ),
                array(
                    'text' => 'Message',
                    'url' => 'index.php?module=demo&amp;page=message'
                ),
                array(
                    'text' => 'Form &amp; Form Component',
                    'url' => 'index.php?module=demo&amp;page=form',
                    'target' => '_self'
                ),
                array(
                    'text' => 'Button',
                    'url' => 'index.php?module=demo&amp;page=button',
                    'target' => '_self'
                ),
                array(
                    'text' => 'District Amphur Province',
                    'url' => 'index.php?module=demo-multiselect'
                ),
                array(
                    'text' => 'Auto Complete',
                    'url' => 'index.php?module=demo-autocomplete'
                ),
                array(
                    'text' => 'Ajax Upload',
                    'url' => 'index.php?module=demo-upload'
                ),
                array(
                    'text' => 'Signature Pad',
                    'url' => 'index.php?module=demo-signature'
                ),
                array(
                    'text' => 'Prompt Pay',
                    'url' => 'index.php?module=demo-promptpay'
                ),
                array(
                    'text' => 'Graphs',
                    'url' => 'index.php?module=demo&amp;page=graphs'
                ),
                array(
                    'text' => 'Table',
                    'url' => 'index.php?module=demo-table'
                ),
                array(
                    'text' => 'Event Calendar',
                    'url' => 'index.php?module=demo-calendar'
                ),
                array(
                    'text' => 'Grid',
                    'url' => 'index.php?module=demo&amp;page=grid'
                ),
                array(
                    'text' => 'Tabs',
                    'url' => 'index.php?module=demo-tabs'
                ),
                array(
                    'text' => 'Api',
                    'url' => 'index.php?module=demo-api'
                ),
                array(
                    'text' => 'Icons',
                    'url' => WEB_URL.'skin/index.html',
                    'target' => '_blank'
                )
            );
            // สร้างเมนูบนสุดก่อนเมนูสมาชิก
            $menu->addTopLvlMenu('demo', 'Demo', null, $submenus, 'member');
        }
    }
}
