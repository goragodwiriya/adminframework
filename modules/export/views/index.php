<?php
/**
 * @filesource modules/export/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Export\Index;

use Kotchasan\Language;
use Kotchasan\Template;

/**
 * ส่งออกข้อมูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View
{
    /**
     * ส่งออกข้อมูลเป็น HTML หรือ หน้าสำหรับพิมพ์
     *
     * @param array $content
     */
    public static function toPrint($contents)
    {
        if (is_file(ROOT_PATH.DATA_FOLDER.'images/logo.png')) {
            $contents['/{LOGO}/'] = WEB_URL.DATA_FOLDER.'images/logo.png';
        }
        $contents['/{LANGUAGE}/'] = Language::name();
        $contents['/{WEBURL}/'] = WEB_URL;
        $template = Template::createFromFile(ROOT_PATH.'modules/export/views/print.html');
        $template->add($contents);
        return Language::trans($template->render());
    }
}
