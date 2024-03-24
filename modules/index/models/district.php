<?php
/**
 * @filesource modules/index/models/district.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\District;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=district
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูล ตำบล สำหรับใส่ลงในตาราง
     *
     * @param int $amphur_id
     *
     * @return array
     */
    public static function toDataTable($amphur_id)
    {
        $result = static::createQuery()
            ->select('id', 'district', 'zipcode')
            ->from('district')
            ->where(array('amphur_id', $amphur_id))
            ->toArray()
            ->execute();
        if (empty($result)) {
            $result = array(
                array('id' => '', 'district' => '', 'zipcode' => '')
            );
        }
        return $result;
    }

    /**
     * อ่านข้อมูล ตำบล สำหรับใส่ลงใน select
     *
     * @param int $amphur_id
     *
     * @return array
     */
    public static function toSelect($amphur_id)
    {
        $query = static::createQuery()
            ->select('id', 'district')
            ->from('district')
            ->where(array('amphur_id', $amphur_id))
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->id] = $item->district;
        }
        return $result;
    }

    /**
     * บันทึก (district.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // referer, token, admin
        if ($request->initSession() && $request->isSafe()) {
            if ($login = Login::isAdmin()) {
                // ค่าที่ส่งมา
                $details = $request->post('district', [])->topic();
                $zipcode = $request->post('zipcode', [])->topic();
                $amphur_id = $request->post('amphur_id')->toInt();
                if ($amphur_id > 0) {
                    $id_exists = [];
                    $save = [];
                    foreach ($request->post('id', [])->toInt() as $key => $value) {
                        if ($details[$key] != '') {
                            if (isset($id_exists[$value])) {
                                $ret['ret_id_'.$key] = Language::replace('This :name already exist', array(':name' => 'ID'));
                            } else {
                                $id_exists[$value] = $value;
                                $save[$key] = array(
                                    'id' => $value,
                                    'amphur_id' => $amphur_id,
                                    'district' => $details[$key],
                                    'zipcode' => empty($zipcode[$key]) ? null : $zipcode[$key]
                                );
                            }
                        }
                    }
                    if (empty($ret)) {
                        // ชื่อตาราง
                        $table_name = $this->getTableName('district');
                        // db
                        $db = $this->db();
                        // ลบข้อมูลเดิม
                        $db->delete($table_name, array(
                            array('amphur_id', $amphur_id)
                        ), 0);
                        // ปรับปรุงตาราง
                        $db->optimizeTable($table_name);
                        // เพิ่มข้อมูลใหม่
                        foreach ($save as $item) {
                            $db->insert($table_name, $item);
                        }
                        // log
                        \Index\Log\Model::add('id', 'index', 'Save', '{LNG_District}', $login['id']);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
