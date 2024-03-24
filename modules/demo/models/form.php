<?php
/**
 * @filesource modules/demo/models/form.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Form;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=demo&page=form
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับค่าจากฟอร์ม (form.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // initSession ต้องมีเสมอ, isSafe ตรวจสอบความถูฏต้องของ token ที่ส่งมา, ตรวจสอบการ Login
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                /**
                 * รับค่าจากการ POST ที่ส่งมากับการ submit ฟอร์ม
                 *
                 * @source http://doc.kotchasan.com/class-Kotchasan.InputItem.html
                 */
                $save = array(
                    // รับค่าอีเมลและหมายเลขโทรศัพท์เท่านั้น
                    'username' => $request->post('register_username')->username(),
                    // รับค่าสำหรับ password อักขระทุกตัวไม่มีช่องว่าง
                    'password' => $request->post('register_password')->password(),
                    'repassword' => $request->post('register_repassword')->password(),
                    // รับค่าวันที่และเวลา
                    'from_date' => $request->post('register_from_date')->date(),
                    'from_time' => $request->post('register_from_time')->toString(),
                    // ตัวเลขมีจุดทศนิยม เช่น จำนวนเงิน
                    'double' => $request->post('register_amount')->toDouble(),
                    // float
                    'float' => $request->post('register_amount')->toFloat(),
                    // รับค่าแบบกำหนดเอง เช่น 0-9A-Z\# หมายถึงค่าสี #000 หรือ RED
                    // และเป็นการรับค่า Input แบบแอเรย์
                    'color' => $request->post('register_color', [])->filter('0-9A-Z\#'),
                    'sex' => $request->post('register_sex')->filter('fm'),
                    'sex' => $request->post('register_sex')->filter('fm'),
                    // รับค่าตัวเลขเท่านั้น คืนค่าเป็น string สามารถมี 0 นำหน้าได้ เช่นเบอร์โทรศัพท์ และยอมรับค่าว่าง (ไม่ระบุ)
                    'number' => $request->post('register_phone')->number(),
                    'provinceID' => $request->post('register_provinceID')->number(),
                    'zipcode' => $request->post('register_zipcode')->number(),
                    // รับค่าที่ส่งมาจาก textarea ป้องกันอักขระที่ไม่พึงประสงค์
                    'address' => $request->post('register_address')->textarea(),
                    // รับค่ารูปแบบ URL หรือ อีเมล
                    'url' => $request->post('register_url')->url(),
                    'email' => $request->post('register_email')->url(),
                    // ข้อความทั้งหมด ป้องกันอักขระที่ไม่พึงประสงค์ ตัวอย่างนี้เป็นค่ารับค่าจาก input ที่เป็นแอเรย์
                    'permission' => $request->post('register_permission', [])->topic(),
                    // true หรือ false
                    'social' => $request->post('register_social', [])->toBoolean(),
                    // รับค่าจาก CKEditor
                    'detail' => $request->post('register_detail')->detail(),
                    /* รับค่าจาก province ตัวเลขเท่านั้น เป็นแอเรย์ ไม่ระบุ คืนค่า 0 */
                    'province' => $request->post('register_province', [])->toInt(),
                    'inputgroups' => $request->post('register_inputgroups', [])->toInt(),
                    /* รับค่าจาก province ตัวเลขเท่านั้น เป็นแอเรย์ ยอมรับค่าว่าง */
                    'select.checkbox' => $request->post('select_checkbox', [])->number(),
                    /* รับค่าที่มาจาก datalist เท่านั้น */
                    'text.datalist.only' => $request->post('text_datalist_only')->filter('A-Z'),
                    /* รับค่าจาก text_datalist_text สามารถรับค่าที่กรอกจาก Input ตรงๆได้ */
                    'text.datalist_text' => $request->post('text_datalist_text')->topic(),
                    'text.datalist' => $request->post('text_datalist')->filter('A-Z'),
                    /* รับค่าจาก range และเป็น Input แบบแอเรย์หลายระดับ */
                    /* คำเตือน การรับค่า Input แบบแอเรย์หลายระดับจะมีลำดับของแอเรย์ไม่ตรงกับค่าที่กำหนดไว้ใน name ให้ตรวจสอบดูให้ดี */
                    /* toString() จะคืนค่าข้อความที่รับมาจาก $_POST หรือ $_GET ตรงๆ และยอมรับค่า NULL */
                    /* คำเตือน toString ไม่มีการป้องกันใดๆทั้งสิ้น ไม่ควรนำค่าที่รับได้ไปใช้ตรงๆ */
                    'range' => $request->post('range')->toString()
                );
                if ($save['username'] == '') {
                    /**
                     * error ไม่ได้กรอก username
                     * ret_ เป็นคีย์เวอร์ดเพื่อบอกว่าเป็นการส่งค่ากลับไปยัง input
                     * register_username ไอดีของ input ที่ต้องการส่งค่ากลับ
                     */
                    $ret['ret_register_username'] = 'Please fill in';
                }
                if (empty($ret)) {
                    // ดูค่าที่ส่งมา แสดงผลใน console ของ Browser
                    print_r($_POST);
                    print_r($save);
                    // บันทึกลงฐานข้อมูล (แก้ไข)
                    //$this->db()->update($this->getTableName('user'), $save['id'], $save);
                    // คืนค่าข้อความแจ้งเตือนสำเร็จ
                    $ret['alert'] = Language::get('Saved successfully');
                    // รีไดเร็คไปหน้าแสดงรายการข้อมูล ด้วยพารามิเตอร์ต่างๆของตารางที่เลือกไว้
                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'demo-table', 'id' => null));
                    // รีไดเร็คกลับไปหน้าตาราง
                    //$ret['location'] = WEB_URL.'index.php?module=demo-table';
                    // รีโหลดฟอร์ม
                    //$ret['location'] = 'reload';
                    // เคลียร์ token
                    $request->removeToken();
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            // แจ้งเตือนการ submit ไม่ถูกต้อง
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
