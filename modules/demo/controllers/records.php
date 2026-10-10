<?php
/**
 * @filesource modules/demo/controllers/records.php
 *
 * api/demo/records — ข้อมูลของตารางสาธิต (templates/demo/table.html)
 *
 * โมดูลที่มีตารางจริงควร extends \Gcms\Table ซึ่งทำการแบ่งหน้า/เรียง/ค้นหา/
 * ปุ่มกระทำให้เสร็จ เหลือแค่เขียน toDataTable() — ที่นี่ไม่ทำแบบนั้นเพราะ
 * โมดูลสาธิตตั้งใจไม่มีตารางในฐานข้อมูล จึงต้องประกอบคำตอบเอง และกลายเป็น
 * เอกสารอย่างดีว่า TableManager ต้องการอะไรบ้าง คือ
 *
 *   {success, data: {data: [...], columns, filters, options, meta}}
 *   meta = {page, pageSize, total, totalPages}
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Records;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ตารางสาธิต
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * คอลัมน์ที่ยอมให้เรียงลำดับ
     *
     * ตารางจริงต้องมีรายการนี้เสมอ เพราะค่า sort มาจากผู้ใช้แล้ววิ่งเข้า SQL
     * ตรง ๆ — ที่นี่ไม่มี SQL แต่คงกติกาเดิมไว้ให้เป็นตัวอย่างที่ลอกไปใช้ได้
     *
     * @var array
     */
    protected $allowedSortColumns = ['id', 'name', 'status', 'active', 'create_date', 'lastvisited'];

    /**
     * GET api/demo/records
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

            $search = $request->get('search')->topic();
            $status = $request->get('status')->toString();
            $active = $request->get('active')->toString();
            $page = max(1, $request->get('page', 1)->toInt());
            $pageSize = min(100, max(1, $request->get('pageSize', 10)->toInt()));

            $rows = [];
            foreach (Model::all() as $row) {
                if ($status !== '' && (int) $row['status'] !== (int) $status) {
                    continue;
                }
                if ($active !== '' && (int) $row['active'] !== (int) $active) {
                    continue;
                }
                if ($search !== '' && !self::matches($row, $search)) {
                    continue;
                }
                $rows[] = $row;
            }

            // ไม่ส่ง sort มา = เอาคนที่เพิ่งเข้าใช้ล่าสุดขึ้นก่อน บล็อกตารางบน
            // หน้าแรกจึงไม่ต้องผูก data-default-sort ติดไปกับเทมเพลตกลาง
            $sort = $request->get('sort')->toString();
            $rows = $this->sortRows($rows, $sort === '' ? 'lastvisited desc' : $sort);
            $total = count($rows);
            $totalPages = max(1, (int) ceil($total / $pageSize));

            return $this->successResponse([
                'data' => array_slice($rows, ($page - 1) * $pageSize, $pageSize),
                'columns' => self::columns(),
                'filters' => [],
                'options' => [],
                'meta' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'total' => $total,
                    'totalPages' => $totalPages
                ]
            ], 'Data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/demo/records/action — ปุ่มในแถวและปุ่มกระทำกับหลายแถว
     *
     * โมดูลสาธิตไม่เขียนอะไรลงฐานข้อมูล ทุกคำสั่งจึงตอบกลับเป็นข้อความแจ้งเตือน
     * แต่ยังตรวจ CSRF และสิทธิ์ครบเหมือนของจริง เพื่อไม่ให้ใครลอกโครงที่ขาด
     * การตรวจสอบไปใช้
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $action = $request->post('action')->filter('a-z_');

            if ($action === 'detail') {
                $item = Model::get($request->post('id')->toInt());
                if (!$item) {
                    return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
                }
                $item['status_text'] = Language::get($item['status'] == 1 ? 'Admin' : 'User');
                $item['active_text'] = Language::get($item['active'] == 1 ? 'Active' : 'Inactive');

                return $this->successResponse([
                    'data' => $item,
                    'actions' => [
                        [
                            'type' => 'modal',
                            'action' => 'open',
                            'template' => 'demo/record.html',
                            'title' => '{LNG_Details of} '.$item['name']
                        ]
                    ]
                ], 'OK');
            }

            if (in_array($action, ['delete', 'activate', 'inactive'], true)) {
                return $this->notificationResponse(Language::get('This is sample data, nothing was changed'));
            }

            return $this->errorResponse('Invalid action', 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * นิยามคอลัมน์ที่ส่งไปกับคำตอบ
     *
     * ใช้โดยตารางที่เปิด data-dynamic-columns เท่านั้น — TableManager สร้าง
     * <th> จากรายการนี้ให้เอง หน้าที่วางตารางจึงไม่ต้องรู้จักคอลัมน์ล่วงหน้า
     * (บล็อกตารางบนหน้าแรกใช้ทางนี้) ส่วน templates/demo/table.html เขียน
     * <thead> ไว้เองและไม่ได้เปิดแฟล็กนั้น รายการนี้จึงถูกมองข้ามไป
     *
     * `class` คือคลาสของ <th> ส่วน `cellClass` คือคลาสของ <td> ในแถว
     *
     * @return array
     */
    private static function columns()
    {
        return [
            [
                'field' => 'name',
                'label' => '{LNG_Name}',
                'sort' => 'name',
                'i18n' => true
            ],
            [
                'field' => 'username',
                'label' => '{LNG_Email}',
                'i18n' => true
            ],
            [
                'field' => 'status',
                'label' => '{LNG_Status}',
                'class' => 'center',
                'cellClass' => 'center',
                'format' => 'lookup',
                // cast เป็น object เสมอ — อาเรย์ PHP ที่คีย์เป็น 0,1,2 เรียงกัน
                // json_encode ออกมาเป็น ["User","Admin"] ไม่ใช่ {"0":...,"1":...}
                // กรณีนี้บังเอิญยังถูก เพราะดัชนีตรงกับค่า แต่พอค่าเป็น 1,2
                // (เช่นสถานะที่เริ่มจาก 1) การ lookup จะเลื่อนไปทั้งคอลัมน์เงียบ ๆ
                'options' => (object) ['0' => Language::get('User'), '1' => Language::get('Admin')],
                'i18n' => true
            ],
            [
                'field' => 'lastvisited',
                'label' => '{LNG_Last Visited}',
                'sort' => 'lastvisited',
                'class' => 'center',
                'cellClass' => 'center',
                'format' => 'datetime',
                'i18n' => true
            ]
        ];
    }

    /**
     * แถวนี้ตรงกับคำค้นหรือไม่
     *
     * @param array  $row
     * @param string $search
     *
     * @return bool
     */
    private static function matches($row, $search)
    {
        foreach (['name', 'username', 'phone'] as $field) {
            if (mb_stripos($row[$field], $search) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * เรียงลำดับตามค่า sort ที่ส่งมาในรูป "<คอลัมน์> <asc|desc>"
     *
     * @param array  $rows
     * @param string $sort
     *
     * @return array
     */
    private function sortRows($rows, $sort)
    {
        $parts = explode(' ', trim(preg_replace('/\s+/', ' ', $sort)));
        $column = isset($parts[0]) ? $parts[0] : '';
        if (!in_array($column, $this->allowedSortColumns, true)) {
            return $rows;
        }
        $desc = isset($parts[1]) && strtolower($parts[1]) === 'desc';

        usort($rows, function ($a, $b) use ($column, $desc) {
            $result = is_numeric($a[$column]) && is_numeric($b[$column])
                ? $a[$column] - $b[$column]
                : strcmp((string) $a[$column], (string) $b[$column]);

            return $desc ? -$result : $result;
        });

        return $rows;
    }
}
