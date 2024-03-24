<?php
/**
 * @filesource modules/demo/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=demo-autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่า ตำบล อำเภอ จังหวัด จาก อำเภอ
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function amphur(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหาที่ส่งมา
                $value = $request->post('amphur')->topic();
                if ($value != '') {
                    $this->execute(array(
                        array('A.amphur', 'LIKE', $value.'%')
                    ));
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }

    /**
     * คืนค่า ตำบล อำเภอ จังหวัด จาก ตำบล
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function district(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหาที่ส่งมา
                $value = $request->post('district')->topic();
                if ($value != '') {
                    $this->execute(array(
                        array('D.district', 'LIKE', $value.'%')
                    ));
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }

    /**
     * ประมวลผลค่าที่ส่งมา และส่งค่ากลับเป็น JSON
     *
     * @param array $where
     *
     * @return JSON
     */
    public function execute($where)
    {
        // Query ข้อมูล
        $result = static::createQuery()
            ->select('P.province', 'P.id provinceID', 'A.amphur', 'A.id amphurID', 'D.district', 'D.id districtID', 'D.zipcode')
            ->from('province P')
            ->join('amphur A', 'INNER', array('A.province_id', 'P.id'))
            ->join('district D', 'INNER', array('D.amphur_id', 'A.id'))
            ->where($where)
            ->limit(50)
            ->cacheOn()
            ->toArray()
            ->execute();
        // คืนค่า JSON
        if (!empty($result)) {
            echo json_encode($result);
        }
    }

    /**
     * คืนค่า ตำบล อำเภอ จังหวัด จาก จังหวัด
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function province(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหาที่ส่งมา
                $value = $request->post('province')->topic();
                if ($value != '') {
                    $this->execute(array(
                        array('P.province', 'LIKE', $value.'%')
                    ));
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }

    /**
     * คืนค่า ตำบล อำเภอ จังหวัด จังหวัด จาก zipcode
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function zipcode(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหาที่ส่งมา
                $value = $request->post('zipcode')->topic();
                if ($value != '') {
                    $this->execute(array(
                        array('D.zipcode', 'LIKE', $value.'%')
                    ));
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }

    /**
     * รับค่าจากฟอร์ม (autocomplete.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member
        if ($request->initSession() && $request->isSafe() && Login::isMember()) {
            try {
                // รับค่าจากการ POST
                $save = array(
                    'provinceID' => $request->post('provinceID')->toInt(),
                    'amphurID' => $request->post('amphurID')->toInt(),
                    'districtID' => $request->post('districtID')->toInt()
                );
                // ดูค่าที่ส่งมา แสดงผลใน console ของ Browser
                //print_r($_POST);
                //print_r($save);
                $result = \Demo\Province\Model::find($save['provinceID'], $save['amphurID'], $save['districtID']);
                $ret['alert'] = var_export($result, true);
                // บันทึกลงฐานข้อมูล
                //$this->db()->update($this->getTableName('user'), $save['id'], $save);
                // คืนค่า
                //$ret['alert'] = Language::get('Saved successfully');
                // รีไดเร็คไปหน้าแสดงรายการข้อมูล ด้วยพารามิเตอร์ต่างๆของตารางที่เลือกไว้ พร้อมกับส่งค่าที่ส่งมากลับไปแสดงผล
                $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'demo-autocomplete') + $save);
                // เคลียร์
                $request->removeToken();
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            // แจ้งเตือนการ submit ไม่ถูกต้อง
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret, JSON_HEX_AMP);
    }

    /**
     * คืนค่า ตำบล และ zipcode สำหรับใส่ลงใน inputgroups
     * ต้องคืนค่า id และ district เสมอ (district คือ id ของ inputgroups)
     * ถ้ามีคอลัมน์อื่นคืนค่ามาด้วยจะถูกนำไปแสดงผลใน autocomplete เรียงตามลำดับ
     * ตามตัวอย่าง จะแสดง district zipcode ในรายการ autocomplete
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function inputgroups(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหา
                $search = $request->post('district')->topic();
                if ($search != '') {
                    $result = static::createQuery()
                        ->select('id', 'district', 'zipcode')
                        ->from('district')
                        ->where(array('district', 'LIKE', "%$search%"))
                        ->limit(20)
                        ->cacheOn()
                        ->toArray()
                        ->execute();
                    // คืนค่า JSON
                    if (!empty($result)) {
                        echo json_encode($result);
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
