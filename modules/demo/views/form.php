<?php
/**
 * @filesource modules/demo/views/form.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Form;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=demo&page=form
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
        /* คำสั่งสร้างฟอร์ม */
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            /*
             * คลาสรับค่าจากการ submit ปกติแล้วควรจะเป็นชื่อเดียวกันกับ Controller
             * demo/model/form/submit หมายถึงคลาสและเมธอด \Demo\Form\Model::submit()
             */
            'action' => 'index.php/demo/model/form/submit',
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
            'title' => '{LNG_Login information}'
        ));
        /*
         * คำสั่งสร้าง input ชนิด text และ tag อื่นๆที่แวดล้อม
         */
        $fieldset->add('text', array(
            'id' => 'register_username',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-email',
            'label' => 'text',
            'comment' => '{LNG_Email address used for login or request a new password}',
            /* property ทั้งหมดสามารถกำหนดได้เหมือน HTML ปกติ */
            'maxlength' => 50,
            'autofocus' => true,
            'value' => 'test',
            /*
             * คำสั่ง Javascript สำหรับตรวจสอบการกรอกข้อมูล
             * และการตรวจสอบกับฐานข้อมูลที่ \Index\Checker\Model::username()
             */
            'validator' => array('keyup,change', 'checkUsername', 'index.php/index/model/checker/username')
        ));
        // password, repassword
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_To change your password, enter your password to match the two inputs} (แสดง comment เป็นกลุ่มทั้งแถว)'
        ));
        // password
        $groups->add('password', array(
            'id' => 'register_password',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => 'password',
            'placeholder' => '{LNG_Passwords must be at least four characters}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword'),
            'value' => 'test'
        ));
        // repassword
        $groups->add('password', array(
            'id' => 'register_repassword',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => 'password',
            'placeholder' => '{LNG_Enter your password again}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword'),
            'autocomplete' => 'off'
        ));
        /*
         * คำสั่งสร้าง input ชนิด text ที่มี checkbox
         */
        $fieldset->add('text', array(
            'id' => 'text_checkbox',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => 'text.checkbox',
            'value' => 'test',
            'checkbox' => true
        ));
        // date time
        $groups = $fieldset->add('groups', array(
            'label' => 'date.disabled + time',
            'for' => 'register_from_date'
        ));
        // date
        $groups->add('date', array(
            'id' => 'register_from_date',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-calendar',
            'value' => date('Y-m-05'),
            'placeholder' => '{LNG_Please select}',
            'comment' => 'แสดง comment แยกแต่ละรายการ',
            'disabled' => true
        ));
        // time
        $groups->add('time', array(
            'id' => 'register_from_time',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-clock',
            'value' => date('H:i'),
            'min' => '08:30',
            'max' => '17:30',
            'comment' => 'แสดง comment แยกแต่ละรายการ'
        ));
        $groups = $fieldset->add('groups');
        // datetime
        $groups->add('datetime', array(
            'id' => 'register_to_date',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-calendar',
            'label' => 'datetime',
            'value' => '2023-12-31 08:09'
        ));
        // date.min
        $groups->add('date', array(
            'id' => 'register_min_date',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-calendar',
            'label' => 'date.min',
            'placeholder' => '> '.date('Y-m-01'),
            'min' => date('Y-m-01')
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_User}'
        ));
        $groups = $fieldset->add('groups');
        // color1
        $groups->add('color', array(
            // input แบบแอเรย์
            'name' => 'register_color[]',
            // ID ไม่สามารถเป็นแอเรย์ได้
            'id' => 'register_color0',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'width50',
            'label' => 'color',
            'value' => '#47ABBE'
        ));
        // color2
        $groups->add('color', array(
            // input แบบแอเรย์
            'name' => 'register_color[]',
            // ID ไม่สามารถเป็นแอเรย์ได้
            'id' => 'register_color1',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'width50',
            'label' => 'color',
            'value' => '#FF0000'
        ));
        // address
        $fieldset->add('textarea', array(
            'name' => 'textarea_checkbox[]',
            'labelClass' => 'g-input icon-address',
            'itemClass' => 'item',
            'label' => 'textarea.checkbox',
            'placeholder' => '{LNG_Please fill in}',
            'rows' => 3,
            'maxlength' => 10,
            'checkbox' => true
        ));
        $groups = $fieldset->add('groups');
        // url
        $groups->add('url', array(
            'id' => 'register_url',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'width50',
            'label' => 'url'
        ));
        // email
        $groups->add('email', array(
            'id' => 'register_email',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'width50',
            'label' => 'email'
        ));
        $groups = $fieldset->add('groups');
        // provinceID
        $groups->add('select', array(
            'id' => 'register_provinceID',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width50',
            'label' => 'select',
            'options' => \Kotchasan\Province::all()
        ));
        // zipcode
        $groups->add('number', array(
            'id' => 'register_zipcode',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width50',
            'label' => 'number',
            'maxlength' => 10
        ));
        $groups = $fieldset->add('groups');
        // ตัวเลือกคล้าย select + text สามารถพิมพ์เพื่อเลือกรายการได้
        $groups->add('text', array(
            'id' => 'text_datalist_only',
            'label' => 'text.datalist.only',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'width50',
            /*
             * ไม่มีการระบุ text หมายความว่า จะต้องเลือกจาก datalist เท่านั้น
             */
            //'text' => '',
            /* datalist ระบุข้อมูลที่จะใช้เป็นตัวเลือก รูปแบบ array(key1 => value1, key2 => value2,.....) */
            'datalist' => \Kotchasan\Country::all()
        ));
        // ตัวเลือกคล้าย select + text สามารถพิมพ์เพื่อเลือกรายการได้
        $groups->add('text', array(
            'id' => 'text_datalist',
            'label' => 'text.datalist',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'width50',
            /*
             * มีการระบุ text หมายความว่า Input ตัวนี้จะยอมรับค่าที่กรอกด้วย สามารถรับค่าได้ที่ text_datalist_text (เติม _text ต่อจาก name หรือ id)
             */
            'text' => '',
            /* datalist ระบุข้อมูลที่จะใช้เป็นตัวเลือก รูปแบบ array(key1 => value1, key2 => value2,.....) */
            'datalist' => \Kotchasan\Country::all()
        ));
        /* ตัวเลือก select + checkbox */
        $fieldset->add('select', array(
            'id' => 'select_checkbox',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => 'select.checkbox',
            'placeholder' => 'กรุณาเลือกจังหวัด',
            'checkbox' => true,
            'options' => \Kotchasan\Province::all(),
            'value' => array(101, 102)
        ));
        // province
        $fieldset->add('checkboxgroups', array(
            'id' => 'register_province',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => 'checkboxgroups.multiline',
            'multiline' => true,
            'scroll' => true,
            'options' => \Kotchasan\Province::all()
        ));
        /* กลุ่มของ checkbox สามารถเลือกได้หลายตัว */
        $fieldset->add('checkboxgroups', array(
            'id' => 'register_permission',
            'label' => 'checkboxgroups',
            'labelClass' => 'g-input icon-list',
            /* options เป็น array(key1 => value1, key2 => value2,.....) */
            'options' => array('text_datalist' => 'text.datalist', 'select_checkbox' => 'select.checkbox'),
            'value' => array('text_datalist', 'select_checkbox')
        ));
        /* กลุ่มของ radio สามารถเลือกได้แค่ตัวเดียว */
        $fieldset->add('radiogroups', array(
            'id' => 'radiogroups_button',
            'label' => 'radiogroups.button',
            'labelClass' => 'g-input icon-share',
            'options' => array(0 => 'ไม่ใช่', 1 => 'Facebook', 2 => 'Google'),
            'value' => 1,
            'button' => true
        ));
        /* กลุ่มของ radio สามารถเลือกได้แค่ตัวเดียว */
        $fieldset->add('radiogroups', array(
            'id' => 'radiogroups_button_multiline',
            'label' => 'radiogroups.button.multiline',
            'labelClass' => 'g-input icon-share',
            'options' => array(0 => 'ไม่ใช่', 1 => 'Facebook', 2 => 'Google'),
            'value' => 1,
            'multiline' => true,
            'button' => true
        ));
        $groups = $fieldset->add('groups');
        /* input ชนิด Text ที่สามารถรับค่าตัวเลขและจุดทศนิยมสองหลัก ใช้สำหรับกรอกจำนวนเงิน มีค่าระหว่าง 100 - 200 */
        $groups->add('currency', array(
            'id' => 'register_amount',
            'labelClass' => 'g-input icon-money',
            'itemClass' => 'width50',
            'label' => 'currency',
            'unit' => 'THB',
            'placeholder' => '100 - 200',
            'min' => 100,
            'max' => 200
        ));
        // phone
        $groups->add('tel', array(
            'id' => 'register_phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => 'tel',
            'maxlength' => 32
        ));
        $groups = $fieldset->add('groups');
        // range1
        $groups->add('range', array(
            'id' => 'range1',
            /* input แบบแอเรย์ */
            'name' => 'range[1][range]',
            'itemClass' => 'width50',
            'label' => 'range.min.max',
            'max' => 200,
            'min' => 100,
            'value' => 150,
            'step' => 1
        ));
        // range2
        $groups->add('range', array(
            'id' => 'range2',
            'name' => 'range[2][range]',
            'itemClass' => 'width50',
            'label' => 'range.range',
            'max' => 100000,
            'min' => 0,
            'range' => true
        ));
        $groups = $fieldset->add('groups');
        // range3
        $groups->add('range', array(
            'id' => 'range3',
            'name' => 'range[3][range]',
            'itemClass' => 'width50',
            'label' => 'range.step',
            'max' => 1,
            'min' => 0,
            'value' => 0.5,
            'step' => 0.05
        ));
        // range4
        $groups->add('range', array(
            'id' => 'range4',
            'name' => 'range[4][range]',
            'itemClass' => 'width50',
            'label' => 'range.negative',
            'max' => 10,
            'min' => -10,
            'value' => 0,
            'step' => 0.5
        ));
        // inputgroups
        $fieldset->add('inputgroups', array(
            'id' => 'register_inputgroups',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => 'inputgroups',
            // ตัวเลือกของ inputgroups
            'options' => Language::get('MONTH_LONG'),
            // รายการที่ถูกเลือก
            'value' => array(1, 2)
        ));
        // inputgroups ที่ query ข้อมูลจากฐานข้อมูล
        $autocomplete = array(
            // URL สำหรับเรียกดูรายการข้อมูล
            'url' => WEB_URL.'index.php/demo/model/autocomplete/inputgroups'
        );
        $fieldset->add('inputgroups', array(
            // ID ของ inputgroups จะถูกส่งไปพร้อมกับ autocomplete เพื่อค้นหาข้อมูล
            'id' => 'district',
            'labelClass' => 'g-input icon-users',
            'itemClass' => 'item',
            'label' => 'inputgroups.autocomplete',
            'autocomplete' => $autocomplete,
            // รายการแสดงผลที่เลือกไว้เริ่มต้น id => district
            'value' => array(710107 => 'ลาดหญ้า', 100101 => 'พระบรมมหาราชวัง')
        ));
        // ckeditor
        $fieldset->add('ckeditor', array(
            'id' => 'register_ckeditor',
            'itemClass' => 'item',
            'height' => 300,
            'language' => LANGUAGE, // หรือใช้ Language::name()
            'toolbar' => 'Document',
            'upload' => false,
            'label' => 'ckeditor',
            'value' => '<p class=message>สามารถดูค่าที่ได้รับเมื่อมีการ submit form ที่ console ของ Browser</p>'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        /* ปุ่ม save */
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        /* input ชนิด hidden */
        $fieldset->add('hidden', array(
            'id' => 'register_id',
            'value' => $request->request('id')->toInt()
        ));
        // Javascript
        $form->script('initDemoForm();');
        // คืนค่า Form
        return $form->render();
    }
}
