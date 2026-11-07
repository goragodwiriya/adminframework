<?php
/**
 * @filesource modules/demo/controllers/quarterlysales.php
 *
 * GET api/demo/quarterly-sales — ข้อมูลกราฟแท่งบนหน้าแรก
 *
 * URL เขียนด้วยขีดกลางได้ Router แปลง quarterly-sales เป็น quarterlySales
 * แล้ว ApiController หาคลาส \Demo\QuarterlySales\Controller ให้เอง
 * (Kotchasan/Router.php::parseRoutes + Kotchasan/ApiController.php::index)
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\QuarterlySales;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API กราฟยอดขายรายไตรมาส (ข้อมูลตัวอย่าง)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/quarterly-sales
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

            return $this->successResponse(\Demo\Dashboard\Model::quarterlySales(), 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
