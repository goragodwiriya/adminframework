<?php
/**
 * @filesource modules/demo/views/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Autocomplete;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตัวอย่างฟอร์ม
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $provinceID = $request->request('provinceID')->toInt();
        $amphurID = $request->request('amphurID')->toInt();
        $districtID = $request->request('districtID')->toInt();
        // อ่านข้อมูล ตำบล อำเภอ จังหวัด จากค้าที่ส่งมา
        $province = \Demo\Province\Model::find($provinceID, $amphurID, $districtID);
        /* คำสั่งสร้างฟอร์ม */
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            /*
             * คลาสรับค่าจากการ submit ปกติแล้วควรจะเป็นชื่อเดียวกันกับ Controller
             * demo/model/autocomplete/submit หมายถึงคลาสและเมธอด \Demo\Form\Autocomplete::submit()
             */
            'action' => 'index.php/demo/model/autocomplete/submit',
            /* ฟังก์ชั่น Javascript (common.js) สำหรับรับค่าที่ตอบกลับจาก Server หลังการ submit */
            'onsubmit' => 'doFormSubmit',
            /* form แบบ Ajax */
            'ajax' => true,
            /* เปิดการใช้งาน Token สำหรับรักษาความปลอดภัยของฟอร์ม */
            'token' => true
        ));
        /*
         * คำสั่งสร้าง fieldset และ legend สำหรับจัดกลุ่ม input
         * <legend><span>...</span></legend>
         */
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Address}'
        ));
        // district
        $fieldset->add('text', array(
            'id' => 'search_district',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_District}',
            'value' => $province ? $province['district'] : ''
        ));
        // amphur
        $fieldset->add('text', array(
            'id' => 'search_amphur',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Amphur}',
            'value' => $province ? $province['amphur'] : ''
        ));
        // province
        $fieldset->add('text', array(
            'id' => 'search_province',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Province}',
            'value' => $province ? $province['province'] : ''
        ));
        // zipcode
        $fieldset->add('text', array(
            'id' => 'search_zipcode',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-location',
            'label' => '{LNG_Zipcode}',
            'value' => $province ? $province['zipcode'] : ''
        ));
        // district
        $fieldset->add('hidden', array(
            'id' => 'search_districtID',
            'name' => 'districtID',
            'value' => $districtID
        ));
        // amphur
        $fieldset->add('hidden', array(
            'id' => 'search_amphurID',
            'name' => 'amphurID',
            'value' => $amphurID
        ));
        // province
        $fieldset->add('hidden', array(
            'id' => 'search_provinceID',
            'name' => 'provinceID',
            'value' => $provinceID
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        /* ปุ่ม submit */
        $fieldset->add('submit', array(
            'class' => 'button save large',
            'value' => '{LNG_Save}'
        ));
        /* Javascript สำหรับ Auto Complete */
        $form->script('initDemoAutocomplete("search");');
        // คืนค่า HTML
        return $form->render();
    }
}
