<?php
/**
 * @filesource modules/demo/controllers/init.php
 *
 * เมนูและการ์ดหน้าแรกของโมดูลสาธิต
 *
 * ไฟล์นี้คือจุดที่ทำให้โมดูล "เสียบแล้วใช้ได้เลย" — \Gcms\Controller::initModule()
 * เดินอ่าน modules/<ชื่อ>/controllers/init.php ของทุกโมดูลเอง ไม่มีไฟล์กลาง
 * ที่ต้องไปแก้ ลบโฟลเดอร์ modules/demo/ กับ templates/demo/ ทิ้ง เมนูและการ์ด
 * ก็หายไปพร้อมกัน
 *
 * โมดูลนี้ตั้งใจให้ทุกคนที่ล็อกอินเห็น เพราะเป็น "ตัวอย่าง" ที่ต้องเปิดดูได้ทันที
 * โมดูลจริงควรกันด้วยสิทธิ์ (ดู initPermission ในคู่มือ MODULE_DEVELOPMENT_GUIDE.md
 * ข้อ 2.2) และเมื่อจะเอาเฟรมเวิร์กไปทำงานจริง ให้ลบโมดูลนี้ทิ้งไปเลย
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Init;

/**
 * Init Controller ของโมดูลสาธิต
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เมนูของโมดูล
     *
     * แทรกต่อจากเมนู dashboard — ใช้ 'dashboard' เป็นหมุดเพราะเป็นคีย์เดียว
     * ที่มีอยู่เสมอ ส่วน 'settings' จะไม่มีเมื่อผู้ใช้ไม่มีสิทธิ์ can_config
     * แล้ว insertMenuBefore() จะเอาเมนูนี้ไปไว้บนสุดแทน (ดู \Gcms\Controller)
     *
     * @param array       $menus
     * @param mixed       $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        return parent::insertMenuAfter($menus, [
            [
                'title' => '{LNG_Example}',
                'icon' => 'icon-modules',
                'children' => [
                    [
                        'title' => '{LNG_Table}',
                        'url' => '/demo-table',
                        'icon' => 'icon-table'
                    ],
                    [
                        'title' => '{LNG_Form}',
                        'url' => '/demo-form',
                        'icon' => 'icon-edit'
                    ]
                ]
            ]
        ], 'dashboard');
    }

    /**
     * การ์ดของโมดูลที่ไปโผล่บนหน้าแรก
     *
     * นี่คือตัวอย่างของ hook initDashboard ที่ modules/index/controllers/dashboard.php
     * เรียกใช้ — หน้าแรกไม่รู้จักโมดูลนี้เลย แต่การ์ดยังไปปรากฏได้
     *
     * @param array       $cards
     * @param mixed       $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initDashboard($cards, $params = null, $login = null)
    {
        if (!$login) {
            return $cards;
        }
        foreach (\Demo\Dashboard\Model::cards() as $card) {
            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * บล็อกกราฟของโมดูลที่ไปโผล่บนหน้าแรก
     *
     * คู่กับ initDashboard — หน้าแรกได้ทั้งการ์ดและกราฟจากโมดูลโดยไม่รู้จัก
     * โมดูลไหนเลย ตัวอย่างของ Dashboard ทั้งหน้าจึงอยู่บนหน้าแรกได้
     * โดยไม่ต้องมีหน้าของตัวเองในเมนู
     *
     * @param array       $blocks
     * @param mixed       $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initDashboardBlocks($blocks, $params = null, $login = null)
    {
        if (!$login) {
            return $blocks;
        }
        foreach (\Demo\Dashboard\Model::blocks() as $block) {
            $blocks[] = $block;
        }

        return $blocks;
    }
}
