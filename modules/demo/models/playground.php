<?php
/**
 * @filesource modules/demo/models/playground.php
 *
 * ข้อมูลตัวอย่างของ api/demo/playground — รายการประเทศ สินค้า และงานเบื้องหลัง
 * ทั้งหมดอยู่ในหน่วยความจำ ไม่มีตารางในฐานข้อมูล (เช่นเดียวกับ \Demo\Records\Model)
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Playground;

/**
 * Model ข้อมูลตัวอย่างของหน้า component
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @return array รหัส => ชื่อ
     */
    public static function countries()
    {
        return [
            'TH' => 'Thailand',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'MY' => 'Malaysia',
            'VN' => 'Vietnam',
            'ID' => 'Indonesia',
            'PH' => 'Philippines',
            'KR' => 'South Korea',
            'CN' => 'China',
            'IN' => 'India',
            'AU' => 'Australia',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'US' => 'United States',
            'CA' => 'Canada'
        ];
    }

    /**
     * @param string $sku รหัสสินค้า
     *
     * @return array|null
     */
    public static function product($sku)
    {
        $items = [
            'P-001' => ['sku' => 'P-001', 'name' => 'Wireless Mouse', 'price' => 350.00],
            'P-002' => ['sku' => 'P-002', 'name' => 'Mechanical Keyboard', 'price' => 2490.00],
            'P-003' => ['sku' => 'P-003', 'name' => 'USB-C Hub', 'price' => 890.50],
            'P-004' => ['sku' => 'P-004', 'name' => 'Monitor 27"', 'price' => 6990.00]
        ];

        return $items[strtoupper($sku)] ?? null;
    }

    /**
     * งานเบื้องหลังตัวอย่าง 3 งาน
     *
     * ความคืบหน้าวนทุก 60 วินาทีโดยเลื่อนเฟสต่างกันตามงาน งานแรกกำลังทำเสมอ
     * งานที่สองจะเป็น queued ในช่วงต้นของรอบ งานที่สามค้างที่ failed ในช่วงท้ายของรอบ
     * เพื่อให้เห็นทั้งสามสีของแถวโดยไม่ต้องมีงานจริง
     *
     * @param int $now timestamp
     *
     * @return array
     */
    public static function tasks($now)
    {
        $cycle = $now % 60;
        $tasks = [
            ['id' => 1, 'label' => 'Export orders to CSV', 'status' => 'running', 'progress' => (int) round($cycle / 60 * 100), 'message' => 'Step '.(1 + intdiv($cycle, 20)).' of 3'],
            ['id' => 2, 'label' => 'Resize uploaded images', 'status' => $cycle < 20 ? 'queued' : 'running', 'progress' => $cycle < 20 ? 0 : (int) round(($cycle - 20) / 40 * 100), 'message' => ''],
            ['id' => 3, 'label' => 'Send newsletter', 'status' => $cycle < 45 ? 'running' : 'failed', 'progress' => min(99, (int) round($cycle / 45 * 100)), 'message' => $cycle < 45 ? '' : 'SMTP server did not answer']
        ];

        return $tasks;
    }
}
