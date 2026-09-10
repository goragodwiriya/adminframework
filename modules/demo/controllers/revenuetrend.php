<?php
/**
 * @filesource modules/demo/controllers/revenuetrend.php
 *
 * GET api/demo/revenue-trend — ข้อมูลกราฟเส้นบนหน้าแรก
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\RevenueTrend;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API กราฟรายได้ย้อนหลัง 12 เดือน (ข้อมูลตัวอย่าง)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/revenue-trend
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

            return $this->successResponse(\Demo\Dashboard\Model::revenueTrend(), 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
