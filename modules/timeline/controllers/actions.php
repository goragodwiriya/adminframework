<?php
/**
 * @filesource modules/timeline/controllers/actions.php
 *
 * POST /api/timeline/actions — ทางเดียวที่ Hub เขียนอะไรกลับเข้ามาในระบบนี้
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Timeline\Actions;

use Gcms\Api as ApiController;
use Gcms\Timeline\Provider;
use Kotchasan\ApiException;
use Kotchasan\Http\Request;

/**
 * @see TIMELINE-PROTOCOL.md §10
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function index(Request $request)
    {
        try {
            \Gcms\Timeline\Guard::check($request, 'POST');

            $body = json_decode((string) $request->getBody(), true);
            if (!is_array($body)) {
                throw new ApiException('ต้องส่ง body เป็น JSON', 400);
            }

            return $this->successResponse(Provider::action($body));
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), $e);
        } catch (\Throwable $e) {
            return $this->errorResponse('Timeline action ล้มเหลว', 500, $e);
        }
    }
}
