<?php
/**
 * @filesource modules/demo/models/dashboard.php
 *
 * ตัวเลขตัวอย่างที่โมดูล demo ส่งขึ้นไปแสดงบนหน้าแรก
 *
 * โมดูล demo **ไม่มีตารางในฐานข้อมูล** ตัวเลขทุกตัวที่นี่จึงสร้างขึ้นเอง
 * เพื่อให้ผู้ที่หยิบเฟรมเวิร์กไปใช้เห็นรูปร่างของข้อมูลที่ฝั่ง JS ต้องการ
 * โดยไม่ต้องติดตั้งตารางอะไรเลย ตอนเขียนโมดูลจริงให้แทนที่เมธอดในไฟล์นี้
 * ด้วย query จริง — รูปร่างของค่าที่คืนออกไปเหมือนเดิมทุกประการ
 *
 * ค่าที่ได้ผูกกับเดือนปัจจุบัน (ดู sample()) ตัวเลขจึงคงที่ทั้งเดือนแล้วขยับ
 * เมื่อขึ้นเดือนใหม่ — กราฟดูมีชีวิตแต่ไม่กระโดดทุกครั้งที่กดรีเฟรช
 * และ **ไม่แตะ mt_srand** ซึ่งเป็นสถานะร่วมของทั้งคำขอ (โค้ดความปลอดภัย
 * ที่สุ่มโทเค็นอยู่ในคำขอเดียวกันจะกลายเป็นค่าที่เดาได้)
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Dashboard;

use Kotchasan\Currency;
use Kotchasan\Language;

/**
 * Model ข้อมูลตัวอย่างของโมดูล demo
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ตัวเลขดิบของการ์ดทั้งสี่ใบ (ใช้ต่อใน cards())
     *
     * ค่าที่เป็นตัวเงิน/จำนวน จัดรูปแบบมาจากฝั่ง PHP แล้ว เพราะเทมเพลตผูกค่า
     * ด้วย data-text ตรง ๆ ไม่ได้แปลงตัวเลขให้
     *
     * @return array
     */
    public static function overview()
    {
        $customers = self::sample('customers', 800, 1600);
        $revenue = self::sample('revenue', 250000, 900000);
        $won = self::sample('won', 8, 30);
        $lost = self::sample('lost', 2, 15);

        return [
            'total_customers' => number_format($customers),
            'customer_growth' => self::sample('customer_growth', -8, 24),
            'active_deals' => number_format(self::sample('deals', 20, 90)),
            'pipeline_value' => Currency::format(self::sample('pipeline', 1000000, 5000000), 0),
            'revenue_this_month' => Currency::format($revenue, 0),
            'revenue_growth' => self::sample('revenue_growth', -12, 30),
            'win_rate_this_month' => (int) round($won * 100 / max(1, $won + $lost)),
            'won_deals_this_month' => $won,
            'lost_deals_this_month' => $lost
        ];
    }

    /**
     * ยอดขายรายไตรมาส — ข้อมูลของกราฟแท่ง
     *
     * รูปร่างที่ GraphComponent ต้องการคือ "อาเรย์ของ series"
     * [{name, data: [{label, value, color?}, ...]}, ...]
     * (Now/js/GraphRenderer.js ตรวจสองคีย์นี้ตรง ๆ ขาดคีย์ไหนไปโยน error ทันที)
     *
     * @return array
     */
    public static function quarterlySales()
    {
        $thisYear = (int) date('Y');
        $series = [];
        foreach ([$thisYear - 1, $thisYear] as $year) {
            $data = [];
            foreach ([1, 2, 3, 4] as $quarter) {
                $data[] = [
                    'label' => 'Q'.$quarter,
                    'value' => self::sample('sales'.$year.$quarter, 400000, 1800000)
                ];
            }
            $series[] = [
                'name' => (string) $year,
                'data' => $data
            ];
        }

        return $series;
    }

    /**
     * สัดส่วนดีลในแต่ละขั้น — ข้อมูลของกราฟวงกลม
     *
     * @return array
     */
    public static function pipelineStats()
    {
        $stages = [
            'Lead' => '#4D96FF',
            'Qualified' => '#6BCB77',
            'Proposal' => '#FFB562',
            'Negotiation' => '#A66CFF',
            'Won' => '#00C2A8',
            'Lost' => '#FF6B6B'
        ];

        $data = [];
        foreach ($stages as $stage => $color) {
            $data[] = [
                'label' => Language::get($stage),
                'value' => self::sample('stage'.$stage, 3, 40),
                'color' => $color
            ];
        }

        return [
            [
                'name' => Language::get('Number of deals'),
                'data' => $data
            ]
        ];
    }

    /**
     * รายได้ย้อนหลัง 12 เดือน — ข้อมูลของกราฟเส้น
     *
     * ชื่อเดือนอ่านจาก MONTH_SHORT ของแฟ้มภาษา กราฟจึงเปลี่ยนภาษาตามผู้ใช้
     * (GraphComponent โหลดข้อมูลใหม่เองเมื่อเกิด event locale:changed)
     *
     * @return array
     */
    public static function revenueTrend()
    {
        $data = [];
        for ($i = 11; $i >= 0; --$i) {
            $time = strtotime('-'.$i.' month', strtotime(date('Y-m-01')));
            $data[] = [
                // อาร์กิวเมนต์ที่ 3 ของ get() คือ "ดัชนีในอาเรย์" ส่วนที่ 2 คือค่าสำรอง
                // เมื่อแฟ้มภาษาของไซต์นั้นไม่มี MONTH_SHORT จะได้ชื่อเดือนอังกฤษแทน
                // ไม่ใช่ตัวอักษรเดี่ยว ๆ ที่ได้จากการ index สตริง
                'label' => Language::get('MONTH_SHORT', date('M', $time), (int) date('n', $time)),
                'value' => self::sample('trend'.date('Ym', $time), 180000, 950000)
            ];
        }

        return [
            [
                'name' => Language::get('Revenue'),
                'data' => $data
            ]
        ];
    }

    /**
     * การ์ดของโมดูลนี้ที่ไปโผล่บนหน้าแรกของระบบ (hook initDashboard)
     *
     * หน้าแรกไม่รู้จักโมดูล demo เลย — ถอดโฟลเดอร์โมดูลออกการ์ดหายไปเอง
     * ดู modules/index/controllers/dashboard.php ประกอบ
     *
     * `url` เป็น null เพราะตัวเลขตัวอย่างไม่มีหน้าให้เจาะดูต่อ — หน้าแรก
     * ถอดแอตทริบิวต์ href ออกให้เอง การ์ดจึงไม่ใช่ลิงก์ที่กดแล้วไม่ไปไหน
     * ส่วน `class` เป็น positive/negative ให้บรรทัดล่างเป็นสีเขียว/แดง
     *
     * @return array
     */
    public static function cards()
    {
        $overview = self::overview();
        $fromLastMonth = Language::get('From last month');

        return [
            [
                'title' => Language::get('Total customers'),
                'value' => $overview['total_customers'],
                'unit' => '',
                'icon' => 'icon-users',
                'url' => null,
                'hint' => $overview['customer_growth'].'% '.$fromLastMonth,
                'class' => $overview['customer_growth'] >= 0 ? 'positive' : 'negative'
            ],
            [
                'title' => Language::get('Active deals'),
                'value' => $overview['active_deals'],
                'unit' => '',
                'icon' => 'icon-create',
                'url' => null,
                'hint' => Language::get('Value').' '.$overview['pipeline_value'].' '.Language::get('Baht'),
                'class' => 'positive'
            ],
            [
                'title' => Language::get('Revenue').' ('.Language::get('This month').')',
                'value' => $overview['revenue_this_month'],
                'unit' => Language::get('Baht'),
                'icon' => 'icon-wallet',
                'url' => null,
                'hint' => $overview['revenue_growth'].'% '.$fromLastMonth,
                'class' => $overview['revenue_growth'] >= 0 ? 'positive' : 'negative'
            ],
            [
                'title' => Language::get('Win rate'),
                'value' => $overview['win_rate_this_month'],
                'unit' => '%',
                'icon' => 'icon-stats',
                'url' => null,
                'hint' => Language::get('Won').' '.$overview['won_deals_this_month']
                    .' | '.Language::get('Lost').' '.$overview['lost_deals_this_month'],
                'class' => 'positive'
            ]
        ];
    }

    /**
     * บล็อกของโมดูลนี้ที่ไปโผล่บนหน้าแรก (hook initDashboardBlocks)
     *
     * `kind` บอกชนิดของบล็อก หน้าแรกเลือกวาดด้วย data-if — ทุกชนิดส่งแค่
     * "ปลายทางของข้อมูล + ความกว้าง" ตัวข้อมูลจริงแต่ละบล็อกไปดึงเองทีหลัง
     * หน้าแรกจึงไม่ต้องรู้ว่าโมดูลไหนมีอะไร
     *
     *   graph     ต้องมี type (bar/pie/line/donut/gauge) — ดู GraphComponent
     *   table     ต้องมี id (ค่าของ data-table ห้ามซ้ำทั้งหน้า) API ต้องส่ง
     *             columns มาด้วย เพราะหน้าแรกไม่มี <thead> เขียนไว้ให้
     *   calendar  ต้องมี id เช่นกัน และโมดูลต้องโหลดบันเดิลปฏิทินเอง
     *             พร้อมสั่ง discoverCalendars() — ดู modules/demo/admin.js
     *
     * @return array
     */
    public static function blocks()
    {
        return [
            [
                'kind' => 'graph',
                'title' => Language::get('Quarterly sales'),
                'type' => 'bar',
                'url' => 'api/demo/quarterly-sales',
                'size' => 'block6'
            ],
            [
                'kind' => 'graph',
                'title' => Language::get('Pipeline proportion'),
                'type' => 'pie',
                'url' => 'api/demo/pipeline-stats',
                'size' => 'block6'
            ],
            [
                'kind' => 'graph',
                'title' => Language::get('Revenue in the last 12 months'),
                'type' => 'line',
                'url' => 'api/demo/revenue-trend',
                'size' => 'block12'
            ],
            [
                'kind' => 'table',
                'title' => Language::get('Last Active Users'),
                // id ขึ้นต้นด้วยชื่อโมดูลเสมอ — สองโมดูลที่ส่งตารางมาหน้าเดียวกัน
                // แล้วใช้ id ซ้ำ จะทับกันใน TableManager.state.tables
                'id' => 'demoRecords',
                'url' => 'api/demo/records',
                'size' => 'block12'
            ],
            [
                'kind' => 'calendar',
                'title' => Language::get('Calendar'),
                'id' => 'demoEvents',
                'url' => 'api/demo/events',
                'size' => 'block12'
            ]
        ];
    }

    /**
     * ตัวเลขตัวอย่างที่คงที่ทั้งเดือน
     *
     * ไม่ใช่ตัวสุ่มจริง — เป็นค่าที่คำนวณจาก (เดือนปัจจุบัน + ชื่อชุดข้อมูล)
     * ค่าเดิมจึงออกมาเท่าเดิมทุกครั้งภายในเดือนเดียวกัน ทำให้แคชฝั่ง JS
     * (data-cache ของหน้า Dashboard) ไม่ทำให้ตัวเลขกระพริบไปมา
     *
     * @param string $salt ชื่อชุดข้อมูล เช่น 'revenue'
     * @param int    $min  ค่าต่ำสุดที่ยอมรับ
     * @param int    $max  ค่าสูงสุดที่ยอมรับ
     *
     * @return int
     */
    private static function sample($salt, $min, $max)
    {
        $n = abs((int) crc32(date('Ym').'|'.$salt));

        return $min + ($n % (($max - $min) + 1));
    }
}
