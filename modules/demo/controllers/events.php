<?php
/**
 * @filesource modules/demo/controllers/events.php
 *
 * GET api/demo/events — นัดหมายของบล็อกปฏิทินบนหน้าแรก
 *
 * EventCalendar ต่อ ?start=YYYY-MM-DD&end=YYYY-MM-DD ให้เองตามช่วงที่มองเห็น
 * และอ่านรายการนัดหมายจากคีย์ `data` ของคำตอบ (config.eventDataPath)
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Events;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API ปฏิทินตัวอย่าง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/events
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // ->date() คืน **null** เมื่อค่าว่างหรือแปลงไม่ได้ (Kotchasan\Text::date)
            // ไม่ใช่สตริงว่าง จึงต้องเช็คด้วย empty() ไม่งั้นเรียกตรง ๆ โดยไม่ผ่าน
            // ปฏิทินจะได้ช่วงวันที่เป็น null แล้ว strtotime() พังเงียบ ๆ
            $start = $request->get('start')->date();
            $end = $request->get('end')->date();
            if (empty($start) || empty($end)) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');
            }

            return $this->successResponse(Model::between($start, $end), 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
