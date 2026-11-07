<?php
/**
 * @filesource modules/demo/controllers/pipelinestats.php
 *
 * GET api/demo/pipeline-stats — ข้อมูลกราฟวงกลมบนหน้าแรก
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\PipelineStats;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API กราฟสัดส่วนดีล (ข้อมูลตัวอย่าง)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/pipeline-stats
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

            return $this->successResponse(\Demo\Dashboard\Model::pipelineStats(), 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
