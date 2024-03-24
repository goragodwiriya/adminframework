<?php
/**
 * @filesource modules/index/views/province.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Province;

use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=province
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตาราง จังหวัด
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/province/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-location',
            'title' => '{LNG_Details of} {LNG_Province}'
        ));
        // ตาราง
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \Index\Province\Model::toDataTable(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* กำหนดให้ input ตัวแรก (id) รับค่าเป็นตัวเลขเท่านั้น */
            'onInitRow' => 'initFirstRowNumberOnly',
            'border' => true,
            'pmButton' => true,
            'showCaption' => false,
            'headers' => array(
                'id' => array(
                    'text' => '{LNG_ID}'
                ),
                'province' => array(
                    'text' => '{LNG_Province}'
                )
            )
        ));
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => $table->render()
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        $groups = $fieldset->add('groups-table');
        // submit
        $groups->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['id'] = Form::text(array(
            'name' => 'id[]',
            'labelClass' => 'g-input icon-number',
            'size' => 3,
            'maxlength' => 2,
            'value' => $item['id']
        ))->render();
        $item['province'] = Form::text(array(
            'name' => 'province[]',
            'labelClass' => 'g-input icon-edit',
            'maxlength' => 50,
            'value' => $item['province']
        ))->render();
        return $item;
    }
}
