<?php
/**
 * @filesource modules/demo/controllers/settings.php
 *
 * api/demo/settings — ฟอร์มสาธิต (templates/demo/settings.html)
 *
 * แสดงครบวงจรของฟอร์ม: data-load-api ดึงค่ามาเติม → ผู้ใช้แก้ → POST ไปที่
 * /update → ตรวจค่า → ตอบกลับ ฟอร์มนี้ **ไม่บันทึกอะไรจริง** แต่ตรวจค่าจริง
 * เพื่อให้เห็นทั้งเส้นทางที่สำเร็จและเส้นทางที่ formErrorResponse() ทำงาน
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ฟอร์มสาธิต
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/demo/settings — ค่าที่เอาไปเติมในฟอร์ม
     *
     * FormManager อ่าน data.data ไปใส่ช่องต่าง ๆ และอ่าน data.options
     * ไปเติม <option> ของช่องที่มี data-options-key
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

            return $this->successResponse([
                'data' => (object) [
                    'site_name' => self::$cfg->web_title,
                    'site_description' => self::$cfg->web_description,
                    'language' => 'th',
                    'timezone' => self::$cfg->timezone,
                    'date_format' => 'd/m/Y',
                    'theme' => 'auto',
                    'primary_color' => '#4d96ff',
                    'items_per_page' => 25,
                    'enable_animations' => 1,
                    'email_notifications' => 1,
                    'push_notifications' => 0,
                    'notification_sound' => 'chime',
                    'notification_frequency' => 'instant',
                    'debug_mode' => 0,
                    'cache_duration' => 60,
                    'api_rate_limit' => 120,
                    'allowed_domains' => 'example.com,nowjs.net',
                    'maintenance_mode' => 0
                ],
                'options' => self::options()
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/demo/settings/update — ตรวจค่าที่ส่งมา
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function update(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // ตรวจค่าจริง แม้จะไม่บันทึก — คีย์ของ $errors คือ name ของช่องในฟอร์ม
            // FormManager จึงชี้ข้อความผิดพลาดไปที่ช่องนั้นได้ตรงตัว
            $errors = [];
            if ($request->post('site_name')->topic() === '') {
                $errors['site_name'] = Language::get('Please fill in');
            }
            $itemsPerPage = $request->post('items_per_page')->toInt();
            if ($itemsPerPage < 5 || $itemsPerPage > 100) {
                $errors['items_per_page'] = Language::replace('Please enter a number between :min and :max', [
                    ':min' => 5,
                    ':max' => 100
                ]);
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            return $this->notificationResponse(Language::get('This is sample data, nothing was changed'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ตัวเลือกของช่องที่เป็น select
     *
     * รูปแบบที่ฝั่ง JS ต้องการคือรายการของ {value, text} เหมือนกันทุกที่
     * ทั้ง options ของฟอร์มและ filters ของตาราง
     *
     * @return array
     */
    private static function options()
    {
        $options = [
            'languages' => [],
            'timezones' => [],
            'date_formats' => [],
            'notification_sounds' => []
        ];

        foreach (Language::installedLanguage() as $item) {
            $options['languages'][] = ['value' => $item, 'text' => $item];
        }
        foreach (['Asia/Bangkok', 'Asia/Tokyo', 'Europe/London', 'UTC'] as $item) {
            $options['timezones'][] = ['value' => $item, 'text' => $item];
        }
        foreach (['d/m/Y', 'Y-m-d', 'd M Y'] as $item) {
            $options['date_formats'][] = ['value' => $item, 'text' => date($item)];
        }
        foreach (['none' => 'None', 'chime' => 'Chime', 'bell' => 'Bell'] as $value => $text) {
            $options['notification_sounds'][] = ['value' => $value, 'text' => Language::get($text)];
        }

        return $options;
    }
}
