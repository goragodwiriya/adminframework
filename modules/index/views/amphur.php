<?php
/**
 * @filesource modules/index/views/amphur.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Amphur;

use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=amphur
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตาราง อำเภอ
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // รายการที่ต้องการ
        $province_id = $request->request('province_id')->toInt();
        $province = \Index\Province\Model::toSelect();
        $province_id = isset($province[$province_id]) ? $province_id : \Kotchasan\ArrayTool::getFirstKey($province);
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/amphur/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-location',
            'title' => '{LNG_Details of} {LNG_Amphur}'
        ));
        $groups = $fieldset->add('groups-table');
        // province_id
        $groups->add('select', array(
            'id' => 'province_id',
            'label' => '{LNG_Province}',
            'options' => $province,
            'value' => $province_id
        ));
        // ตาราง
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \Index\Amphur\Model::toDataTable($province_id),
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
                'amphur' => array(
                    'text' => '{LNG_Amphur}'
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
        // Javascript
        $form->script('initSelectChange("amphur", ["province_id"]);');
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
            'maxlength' => 5,
            'value' => $item['id']
        ))->render();
        $item['amphur'] = Form::text(array(
            'name' => 'amphur[]',
            'labelClass' => 'g-input icon-edit',
            'maxlength' => 50,
            'value' => $item['amphur']
        ))->render();
        return $item;
    }
}
