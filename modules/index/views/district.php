<?php
/**
 * @filesource modules/index/views/district.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\District;

use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=district
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตาราง ตำบล
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
        $amphur_id = $request->request('amphur_id')->toInt();
        $amphur = \Index\Amphur\Model::toSelect($province_id);
        $amphur_id = isset($amphur[$amphur_id]) ? $amphur_id : \Kotchasan\ArrayTool::getFirstKey($amphur);
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/district/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-location',
            'title' => '{LNG_Details of} {LNG_District}'
        ));
        $groups = $fieldset->add('groups-table');
        // province_id
        $groups->add('select', array(
            'id' => 'province_id',
            'label' => '{LNG_Province}',
            'labelClass' => 'width',
            'options' => $province,
            'value' => $province_id
        ));
        // amphur_id
        $groups->add('select', array(
            'id' => 'amphur_id',
            'label' => '{LNG_Amphur}',
            'labelClass' => 'width',
            'options' => $amphur,
            'value' => $amphur_id
        ));
        // ตาราง
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \Index\District\Model::toDataTable($amphur_id),
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
                'district' => array(
                    'text' => '{LNG_District}'
                ),
                'zipcode' => array(
                    'text' => '{LNG_Zipcode}'
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
        $form->script('initSelectChange("district", ["province_id", "amphur_id"]);');
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
            'maxlength' => 7,
            'value' => $item['id']
        ))->render();
        $item['district'] = Form::text(array(
            'name' => 'district[]',
            'labelClass' => 'g-input icon-edit',
            'maxlength' => 50,
            'value' => $item['district']
        ))->render();
        $item['zipcode'] = Form::text(array(
            'name' => 'zipcode[]',
            'labelClass' => 'g-input icon-edit',
            'maxlength' => 10,
            'value' => $item['zipcode']
        ))->render();
        return $item;
    }
}
