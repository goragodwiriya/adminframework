<?php
/**
 * @filesource modules/index/models/province.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Province;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=province
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่ารายชื่อจังหวัด
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function toJSON(Request $request)
    {
        // referer, ajax
        if ($request->isReferer() && $request->isAjax()) {
            echo json_encode(array(
                'province' => \Kotchasan\Province::all($request->post('country')->filter('A-Z'))
            ));
        }
    }

    /**
     * อ่านข้อมูล จังหวัด สำหรับใส่ลงในตาราง
     *
     * @return array
     */
    public static function toDataTable()
    {
        $result = static::createQuery()
            ->select('id', 'province')
            ->from('province')
            ->toArray()
            ->execute();
        if (empty($result)) {
            $result = array(
                array('id' => 10, 'province' => '')
            );
        }
        return $result;
    }

    /**
     * อ่านข้อมูล จังหวัด สำหรับใส่ลงใน select
     *
     * @return array
     */
    public static function toSelect()
    {
        $query = static::createQuery()
            ->select('id', 'province')
            ->from('province')
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->id] = $item->province;
        }
        return $result;
    }

    /**
     * บันทึก (province.php)
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
                $save = [];
                $details = $request->post('province', [])->topic();
                foreach ($request->post('id', [])->toInt() as $key => $value) {
                    if ($details[$key] != '') {
                        if (isset($save[$value])) {
                            $ret['ret_id_'.$key] = Language::replace('This :name already exist', array(':name' => 'ID'));
                        } else {
                            $save[$value] = $details[$key];
                        }
                    }
                }
                if (empty($ret)) {
                    // ชื่อตาราง
                    $table_name = $this->getTableName('province');
                    // db
                    $db = $this->db();
                    // ลบข้อมูลเดิม
                    $db->emptyTable($table_name);
                    // เพิ่มข้อมูลใหม่
                    foreach ($save as $id => $province) {
                        $db->insert($table_name, array(
                            'id' => $id,
                            'province' => $province
                        ));
                    }
                    // log
                    \Index\Log\Model::add('id', 'index', 'Save', '{LNG_Province}', $login['id']);
                    // คืนค่า
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = 'reload';
                    // เคลียร์
                    $request->removeToken();
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
