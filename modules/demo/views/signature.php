<?php
/**
 * @filesource modules/demo/views/signature.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Signature;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-signature
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
            'signature' => 'off',
            /*
             * คลาสรับค่าจากการ submit ปกติแล้วควรจะเป็นชื่อเดียวกันกับ Controller
             * demo/model/signature/submit หมายถึงคลาสและเมธอด \Demo\Form\Signature::submit()
             */
            'action' => 'index.php/demo/model/signature/submit',
            /* ฟังก์ชั่น Javascript (common.js) สำหรับรับค่าที่ตอบกลับจาก Server หลังการ submit */
            'onsubmit' => 'doFormSubmit',
            /* form แบบ Ajax */
            'ajax' => true,
            /* เปิดการใช้งาน Token สำหรับรักษาความปลอดภัยของฟอร์ม */
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Signature Pad}'
        ));
        $fieldset->add('div', array(
            'class' => 'signature-component item center',
            'innerHTML' => '<canvas id="signature-pad" width="400" height="200"></canvas>'
        ));
        if (is_file(ROOT_PATH.DATA_FOLDER.'signature.png')) {
            $fieldset->add('div', array(
                'class' => 'item center',
                'innerHTML' => '<img src="'.WEB_URL.DATA_FOLDER.'signature.png">'
            ));
        }
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit center'
        ));
        // ปุ่มล้างข้อมูล
        $fieldset->add('button', array(
            'id' => 'approve_clear',
            'class' => 'button orange large icon-delete',
            'value' => '{LNG_Clear}',
            'disabled' => true
        ));
        // ปุ่ม Submit
        $fieldset->add('submit', array(
            'id' => 'approve_save',
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}',
            'disabled' => true
        ));
        // ข้อมูลลายเซ็นต์ สำหรับการ Submit
        $fieldset->add('hidden', array(
            'id' => 'signature',
            'value' => ''
        ));
        /* Javascript สำหรับ Auto Complete */
        $form->script('initDemoSignature();');
        // คืนค่า HTML
        return $form->render();
    }
}
