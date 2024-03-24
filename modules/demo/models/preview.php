<?php
/**
 * @filesource modules/demo/models/preview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Preview;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * ตารางสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับค่าจากฟอร์ม (preview.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // รับค่าจากการ POST
                $save = array(
                    'username' => $request->post('register_username')->url(),
                    'id' => $request->post('register_id')->toInt()
                );
                // ดูค่าที่ส่งมา แสดงผลใน console ของ Browser
                //print_r($save);
                if ($save['username'] == '') {
                    // ไม่ได้กรอก
                    $ret['ret_register_username'] = 'Please fill in';
                } elseif (!Validator::email($save['username'])) {
                    // ไม่ใช่อีเมล
                    $ret['ret_register_username'] = 'this';
                }
                if (empty($ret)) {
                    // บันทึกลงฐานข้อมูล
                    //$this->db()->update($this->getTableName('user'), $save['id'], $save);
                    // คืนค่า
                    $ret['alert'] = Language::get('Saved successfully');
                    // ปิด Modal
                    $ret['modal'] = 'close';
                    // reload ตาราง
                    $ret['location'] = 'reload';
                    // เคลียร์
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
