<?php
/**
 * @filesource modules/demo/models/table.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Table;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

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
     * อ่านข้อมูลสำหรับใส่ลงในตาราง
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id', 'name', 'active', 'social', 'phone', 'status', 'create_date')
            ->from('user');
    }

    /**
     * รับค่าจากตาราง (table.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, สมาชิก
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            // รับค่าจากการ POST
            $action = $request->post('action')->toString();
            if ($action == 'preview') {
                // แสดง Modal ฟอร์ม
                $ret['modal'] = Language::trans(\Demo\Preview\View::create()->render($request));
            } else {
                // ดูค่าที่ส่งมา แสดงผลใน console ของ Browser
                print_r($_POST);
            }
        }
        if (!empty($ret)) {
            // คืนค่า JSON
            echo json_encode($ret);
        }
    }
}
