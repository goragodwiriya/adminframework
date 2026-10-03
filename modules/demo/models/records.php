<?php
/**
 * @filesource modules/demo/models/records.php
 *
 * แถวข้อมูลตัวอย่างของหน้าตารางสาธิต
 *
 * ตารางจริงคืนค่าจากฐานข้อมูลผ่าน \Gcms\Table ซึ่งจัดการแบ่งหน้า/เรียง/ค้นหา
 * ให้เสร็จสรรพ แต่โมดูลสาธิตไม่มีตารางในฐานข้อมูล จึงเก็บแถวไว้ในหน่วยความจำ
 * แล้วทำสามอย่างนั้นเองใน \Demo\Records\Controller เพื่อให้เห็นว่า
 * "สัญญา" ที่ TableManager ต้องการมีแค่ data + meta เท่านั้น
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Records;

/**
 * Model แถวตัวอย่างของโมดูล demo
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รายชื่อตัวอย่าง 48 แถว
     *
     * ทุกค่าคำนวณจากลำดับแถว ผลลัพธ์จึงเหมือนเดิมทุกครั้ง — การกดแบ่งหน้า
     * ไปมาแล้วได้ข้อมูลคนละชุดคือข้อผิดพลาดที่หาสาเหตุยากที่สุดของตาราง
     *
     * @return array
     */
    public static function all()
    {
        static $rows = null;
        if ($rows !== null) {
            return $rows;
        }

        $firstNames = ['Somchai', 'Suda', 'Anan', 'Kanya', 'Prasit', 'Malee', 'Nattapong', 'Ratchanee'];
        $lastNames = ['Jaidee', 'Sukjai', 'Wongsa', 'Thongdee', 'Srisuk', 'Chaiyo'];

        $rows = [];
        for ($i = 1; $i <= 48; ++$i) {
            $first = $firstNames[($i - 1) % count($firstNames)];
            $last = $lastNames[($i - 1) % count($lastNames)];
            $rows[] = [
                'id' => $i,
                'name' => $first.' '.$last,
                'username' => strtolower($first).$i.'@example.com',
                'phone' => sprintf('08%d-%03d-%04d', $i % 10, 100 + $i, 1000 + ($i * 7)),
                // 1 = ผู้ดูแล, 0 = สมาชิก — ตรงกับ data-options ของคอลัมน์ในเทมเพลต
                'status' => $i % 7 === 0 ? 1 : 0,
                'active' => $i % 5 === 0 ? 0 : 1,
                'create_date' => date('Y-m-d H:i:s', strtotime('-'.($i * 3).' day')),
                'lastvisited' => date('Y-m-d H:i:s', strtotime('-'.($i * 5).' hour'))
            ];
        }

        return $rows;
    }

    /**
     * แถวเดียวตามรหัส
     *
     * @param int $id
     *
     * @return array|null
     */
    public static function get($id)
    {
        foreach (self::all() as $row) {
            if ((int) $row['id'] === (int) $id) {
                return $row;
            }
        }

        return null;
    }
}
