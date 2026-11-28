<?php
/**
 * @filesource modules/demo/controllers/playground.php
 *
 * api/demo/playground — ปลายทางเล็ก ๆ ที่หน้าตัวอย่าง component ต้องเรียก
 * (templates/demo/elements.html · richtext.html · components.html)
 *
 *   GET  api/demo/playground/lookup?q=   ค้นรายการให้ autocomplete และ tags (ตอบ [{value, text}])
 *   GET  api/demo/playground/product     รายละเอียดสินค้า 1 รายการให้ตารางรายการสินค้า (line items)
 *   GET  api/demo/playground/tasks       งานเบื้องหลังตัวอย่างให้ task monitor
 *   POST api/demo/playground/inline      บันทึกค่าที่แก้ในที่ (edit in place)
 *   POST api/demo/playground/submit      รับฟอร์มแล้วส่งสิ่งที่ได้รับกลับมาแสดง (ไม่บันทึกอะไร)
 *
 * ทุกปลายทางตรวจการล็อกอินและ CSRF (ตัวที่เป็น POST) เหมือนของจริง แต่ไม่เขียนอะไรลงฐานข้อมูล
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Playground;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ของหน้าตัวอย่าง component
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/playground/lookup?q=
     *
     * ตอบเป็นรายการ {value, text} ซึ่งเป็นรูปแบบเดียวที่ autocomplete ของช่องข้อความ
     * และช่อง tags ที่ใช้ data-source เป็น URL เข้าใจ — endpoint เดียวใช้ได้ทั้งสองช่อง
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function lookup(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);
            if (!$this->authenticateRequest($request)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $q = $request->get('q')->topic();
            $result = [];
            foreach (Model::countries() as $code => $name) {
                if ($q === '' || mb_stripos($name, $q) !== false || stripos($code, $q) === 0) {
                    $result[] = ['value' => $code, 'text' => $name];
                }
            }

            return $this->successResponse(array_slice($result, 0, 10), 'Search completed');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET api/demo/playground/product?product=<รหัสสินค้า>&qty=<จำนวน>
     *
     * ตารางรายการสินค้าส่งค่าของช่องที่มี data-role มาเป็นพารามิเตอร์ แล้วเติมแถวด้วยทุกฟิลด์ที่ตอบกลับ
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function product(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);
            if (!$this->authenticateRequest($request)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $item = Model::product($request->get('product')->filter('A-Za-z0-9-'));
            if (!$item) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            $qty = max(1, $request->get('qty', 1)->toInt());

            return $this->successResponse([
                'sku' => $item['sku'],
                'name' => $item['name'],
                'quantity' => $qty,
                'unit_price' => $item['price'],
                'subtotal' => round($qty * $item['price'], 2)
            ], 'OK');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET api/demo/playground/tasks — งานตัวอย่างที่ task monitor ถามซ้ำเป็นช่วง ๆ
     *
     * ความคืบหน้าคำนวณจากเวลาปัจจุบัน จึงเปลี่ยนไปเองทุกครั้งที่ถาม (รอบละ 60 วินาที)
     * โดยไม่ต้องมีงานจริงรันอยู่เบื้องหลัง — รูปแบบคำตอบ {data: {active: [...]}} ตรงกับ
     * ค่าตั้งต้นของ data-items="data.active"
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function tasks(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);
            if (!$this->authenticateRequest($request)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            return $this->successResponse(['active' => Model::tasks(time())], 'OK');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST api/demo/playground/inline — ค่าที่แก้ในที่ (EditInPlaceManager ส่ง value กับ field)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function inline(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);
            $this->validateCsrfToken($request);
            if (!$this->authenticateRequest($request)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $value = $request->post('value')->topic();
            if ($value === '') {
                return $this->errorResponse('Please fill in', 400);
            }

            // ค่าที่ตอบกลับคือสิ่งที่ช่องจะแสดงต่อ — ทดลองพิมพ์คำว่า error เพื่อดูการคืนค่าเดิมเมื่อบันทึกไม่ผ่าน
            if (strtolower($value) === 'error') {
                return $this->errorResponse('This is sample data, nothing was changed', 400);
            }

            return $this->successResponse(['value' => $value], 'This is sample data, nothing was changed');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST api/demo/playground/submit — รับฟอร์มแล้วแสดงสิ่งที่ได้รับ
     *
     * ตอบ modal ที่วนแสดงทุกฟิลด์ที่ถึงเซิร์ฟเวอร์ จึงเห็นได้ว่าแต่ละชนิดช่องส่งค่ารูปแบบไหน
     * (ช่อง tags ส่งข้อความคั่นด้วยจุลภาค · select หลายค่าส่งเป็นอาร์เรย์ · ช่องที่ไม่ได้ติ๊กไม่ถูกส่ง)
     * ค่าถูกส่งกลับตามที่ได้รับและหน้า posted.html แสดงด้วย data-text (textContent) จึงเห็น HTML ของ
     * rich text เป็นข้อความดิบโดยไม่ถูกตีความ — ถ้าจะเก็บ HTML จริงให้อ่านด้วย ->detail() /
     * ->cleanHtml() ตามที่คู่มือ RTE อธิบาย
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function submit(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);
            $this->validateCsrfToken($request);
            if (!$this->authenticateRequest($request)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $fields = [];
            foreach ($request->getParsedBody() ?: [] as $name => $value) {
                if (!preg_match('/^[A-Za-z0-9_\-\[\]]{1,64}$/', (string) $name) || $name === '_token') {
                    continue;
                }
                if (is_array($value)) {
                    $value = implode(', ', array_map(function ($v) {
                        return is_scalar($v) ? (string) $v : '';
                    }, $value));
                }
                $text = is_scalar($value) ? (string) $value : '';
                $fields[] = [
                    'name' => (string) $name,
                    // ตัดความยาวเพื่อไม่ให้ HTML ของ RTE ทั้งก้อนล้น modal
                    'value' => mb_strlen($text) > 400 ? mb_substr($text, 0, 400).'…' : $text
                ];
            }
            // ไฟล์ที่ได้รับ: ชื่อ ชนิด ขนาด และขนาดภาพ — ช่องที่ครอป (data-crop) ต้องได้รูปที่ครอปแล้ว ไม่ใช่ต้นฉบับ
            // ไม่บันทึกไฟล์ไว้ที่ไหน PHP ลบไฟล์ชั่วคราวเองเมื่อจบคำขอ
            foreach ($request->getUploadedFiles() as $name => $file) {
                if (!$file instanceof \Kotchasan\Http\UploadedFile || !$file->hasUploadFile()) {
                    continue;
                }
                $info = [$file->getClientMediaType(), number_format($file->getSize()).' bytes'];
                $image = @getimagesize($file->getTempFileName());
                if ($image) {
                    $info[] = $image[0].'×'.$image[1].' px';
                }
                $fields[] = [
                    'name' => (string) $name,
                    'value' => $file->getClientFilename().' ('.implode(', ', $info).')'
                ];
            }

            return $this->successResponse([
                'fields' => $fields,
                'count' => count($fields),
                'actions' => [
                    [
                        'type' => 'modal',
                        'action' => 'open',
                        'template' => 'demo/posted.html',
                        'title' => '{LNG_Data received by the server}'
                    ]
                ]
            ], Language::get('This is sample data, nothing was changed'));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
