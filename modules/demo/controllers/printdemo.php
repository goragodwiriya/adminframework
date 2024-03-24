<?php
/**
 * @filesource modules/demo/controllers/printdemo.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Printdemo;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * export.php?module=demo-printdemo
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Controller สำหรับจัดการการพิมพ์
     * ฟังก์ชั่น คืนค่าเป็นข้อความ HTML ที่เป็นเนื้อหาของหน้าพิมพ์
     * หรือคืนค่า false ถ้าไม่สามารถพิมพ์ได้ (โปรแกรมจะส่งออกหน้า 404)
     *
     * @param Request $request
     *
     * @return string
     */
    public function export(Request $request)
    {
        /*
        เขียนคำสั่งเพื่อสร้างหน้า HTML สำหรับพิมพ์ที่นี่ และส่งคืนหน้า HTML กลับไป
        เช่น return __FILE__; // แสดง /public_html/modules/demo/controllers/printdemo.php ออกทางหน้าจอ
        ตัวอย่างด้านล่างเป็นการสร้างหน้า HTML สำหรับพิมพ์ อย่างง่าย
         */
        // สมาชิก
        $login = Login::isMember();
        // ตรวจสอบต้องเข้าระบบแล้ว
        if ($login) {
            // ค่าที่ส่งมา
            $id = $request->get('id')->toInt();
            // อ่านรายชื่อสมาชิกจากฐานข้อมูล
            $query = \Demo\Table\Model::toDataTable()
                ->order('id')
                ->cacheOn();
            // ข้อมูลตารางที่ต้องการ
            $table = '';
            foreach ($query->execute() as $n => $item) {
                $table .= '<tr'.($id == $item->id ? ' class=bg3' : '').'>';
                $table .= '<td class=center>'.($n + 1).'</td>';
                $table .= '<td>'.preg_replace('/[^\s]/', 'x', $item->name).'</td>';
                $table .= '<td class=center>'.Date::format($item->create_date, 'd M Y').'</td>';
                $table .= '</tr>';
            }
            // สร้างหน้าสำหรับพิมพ์
            return \Export\Index\View::toPrint(array(
                // template สำหรับพิมพ์ print.html
                '/{CONTENT}/' => file_get_contents(ROOT_PATH.'modules/demo/template/print.html'),
                // portrait (แนวตั้ง A4) หรือ landscape (แนวนอน A4)
                '/{ORIENTATION}/' => 'portrait',
                // ข้อความ title
                '/{TITLE}/' => 'ตัวอย่างหน้าสำหรับพิมพ์',
                // ข้อมูลที่จะใส่ลงใน template หากมีข้อมูลเพิ่มเติมอื่นๆ สามารถใส่ข้อความที่ต้องการลงใน template ได้
                '/%DETAIL1%/' => $table,
                '/%DETAIL2%/' => $table,
                '/%NAME%/' => $login['name']
            ));
        }
        // 404
        return false;
    }
}
