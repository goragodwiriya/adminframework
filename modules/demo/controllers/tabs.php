<?php
/**
 * @filesource modules/demo/controllers/tabs.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Tabs;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-tabs
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Controller สำหรับคัดเลือกหน้าของโมดูล demo
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = 'Tabs';
        // เลือกเมนู
        $this->menu = 'demo';
        // สมาชิก
        if (Login::isMember()) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-home">{LNG_Home}</span></li>');
            $header = $section->add('header', array(
                'innerHTML' => '<h2 class="icon-menus">'.$this->title.'</h2>'
            ));
            // เมนู tab
            $tab = new \Kotchasan\Tab('accordient_menu', 'index.php?module=demo-tabs');
            $tab->add('upload', '{LNG_Ajax Upload}');
            $tab->add('lms', '{LNG_Listbox multi select}');
            $tab->add('table', '{LNG_Table}');
            $header->appendChild($tab->render($request->request('tab')->filter('a-z')));
            // tab ที่เลือก
            switch ($tab->getSelect()) {
                case 'upload':
                    $className = 'Demo\Upload\View';
                    break;
                case 'table':
                    $className = 'Demo\Table\View';
                    break;
                default:
                    $className = 'Demo\Multiselect\View';
                    break;
            }
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // โหลดฟอร์ม (View)
            $div->appendChild(createClass($className)->render($request));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
