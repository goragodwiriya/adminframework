<?php
/**
 * @filesource modules/index/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Autocomplete;

use Kotchasan\Http\Request;

/**
 * สำหรับ autocomplete
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
     * คืนค่าข้อมูล user สำหรับ autocomplete
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function user(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ข้อความค้นหาที่ส่งมา
                $value = $request->post('search')->topic();
                $result = static::createQuery()
                    ->select('U.id', 'U.name')
                    ->from('user U')
                    ->where(array('U.name', 'LIKE', '%'.$value.'%'))
                    ->order('U.name')
                    ->limit(20)
                    ->cacheOn()
                    ->toArray()
                    ->execute();
                // คืนค่า JSON
                if (!empty($result)) {
                    echo json_encode($result);
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
