<?php
/**
 * @filesource modules/demo/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Init;

/**
 * Init Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller
{

    /**
     * รายการ permission ของโมดูล
     *
     * @param array $permissions
     *
     * @return array
     */
    public static function updatePermissions($permissions)
    {
        // ตัวอย่างการกำหนด permission ของโมดูล
        $permissions['can_view'] = 'สามารถเปิดดูโมดูลได้';
        return $permissions;
    }
}
