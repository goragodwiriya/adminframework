<?php
/**
 * @filesource modules/demo/models/signature.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Signature;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=demo-signature
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับค่าจากฟอร์ม (signature.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member
        if ($request->initSession() && $request->isSafe() && Login::isMember()) {
            try {
                // รับค่าจากการ signature มาจากการ submit
                $img = str_replace('data:image/png;base64,', '', $request->post('signature')->toString());
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                // ไดเร็คทอรี่เก็บไฟล์
                $dir = ROOT_PATH.DATA_FOLDER;
                // save image
                file_put_contents($dir.'signature.png', $data);
                clearstatcache($dir.'signature.png');
                // คืนค่า
                $ret['alert'] = Language::get('Saved successfully');
                // โหลดหน้าใหม่
                $ret['location'] = 'reload';
                // เคลียร์
                $request->removeToken();
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            // แจ้งเตือนการ submit ไม่ถูกต้อง
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret, JSON_HEX_AMP);
    }
}
