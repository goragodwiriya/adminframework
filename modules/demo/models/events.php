<?php
/**
 * @filesource modules/demo/models/events.php
 *
 * นัดหมายตัวอย่างของบล็อกปฏิทินบนหน้าแรก
 *
 * EventCalendar ยิงคำขอมาพร้อมช่วงวันที่ที่มองเห็นอยู่ (?start=&end=) ทุกครั้ง
 * ที่เปลี่ยนเดือน/สัปดาห์ — ของจริงต้องใส่ช่วงนี้ลงใน WHERE เสมอ ไม่อย่างนั้น
 * ปฏิทินของระบบที่มีนัดหมายสะสมหลายปีจะดึงทั้งตารางมาทุกครั้งที่กดลูกศร
 * ที่นี่จึงสร้างนัดหมายตามช่วงที่ขอมาเช่นกัน เพื่อให้เป็นตัวอย่างที่ลอกได้ตรง ๆ
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Events;

use Kotchasan\Language;

/**
 * Model นัดหมายตัวอย่างของโมดูล demo
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * นัดหมายภายในช่วงวันที่ที่ปฏิทินขอมา
     *
     * รูปร่างที่ EventCalendar ต้องการต่อหนึ่งนัดหมายคือ
     * {id, title, start, end} และมี allDay, color, location, description
     * เป็นตัวเลือก — `allDay` เป็นจริงเสมอเว้นแต่ส่ง false มาชัด ๆ
     * (js/components/EventCalendar.js::normalizeEvents)
     *
     * @param string $start วันแรกของช่วง (Y-m-d)
     * @param string $end   วันสุดท้ายของช่วง (Y-m-d)
     *
     * @return array
     */
    public static function between($start, $end)
    {
        $from = strtotime($start.' 00:00:00');
        $to = strtotime($end.' 23:59:59');
        if ($from === false || $to === false || $from > $to) {
            return [];
        }

        // แม่แบบของนัดหมาย: วันที่เท่าไรของเดือน, ยาวกี่วัน, ชื่อ, สี, เวลา
        $templates = [
            [3, 0, 'Meeting', '#4285F4', '09:00', '10:30'],
            [8, 2, 'Training', '#34A853', null, null],
            [15, 0, 'Delivery', '#FBBC04', '13:00', '16:00'],
            [22, 4, 'Campaign', '#8E24AA', null, null],
            [27, 0, 'Report', '#EA4335', '17:00', '18:00']
        ];

        $events = [];
        // เดินทีละเดือนตั้งแต่เดือนของ $start ถึงเดือนของ $end — ช่วงที่ปฏิทิน
        // ขอมาคาบเกี่ยวสองเดือนเสมอ (สัปดาห์แรก/สุดท้ายของตารางเดือน)
        $cursor = strtotime(date('Y-m-01', $from));
        $lastMonth = strtotime(date('Y-m-01', $to));
        while ($cursor <= $lastMonth) {
            $daysInMonth = (int) date('t', $cursor);
            foreach ($templates as $i => $template) {
                list($day, $span, $topic, $color, $startTime, $endTime) = $template;
                if ($day > $daysInMonth) {
                    continue;
                }
                $startDate = date('Y-m-', $cursor).sprintf('%02d', $day);
                $endDate = date('Y-m-d', strtotime($startDate.' +'.$span.' day'));

                // ตัดนัดหมายที่ไม่คาบเกี่ยวช่วงที่ขอมาออก
                if (strtotime($endDate.' 23:59:59') < $from || strtotime($startDate) > $to) {
                    continue;
                }

                $allDay = $startTime === null;
                $events[] = [
                    'id' => date('Ym', $cursor).'-'.$i,
                    'title' => Language::get($topic),
                    'start' => $allDay ? $startDate : $startDate.' '.$startTime.':00',
                    'end' => $allDay ? $endDate : $startDate.' '.$endTime.':00',
                    'allDay' => $allDay,
                    'color' => $color
                ];
            }
            $cursor = strtotime('+1 month', $cursor);
        }

        return $events;
    }
}
