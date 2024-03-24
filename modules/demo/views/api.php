<?php
/**
 * @filesource modules/demo/views/api.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Api;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-api
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตัวอย่างการเรียกใช้ API
     *
     * @param Request $request
     * @param array $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        /* เรียกไปยัง API */
        $me = \Gcms\Api::me($login);
        /* คำสั่งสร้างฟอร์ม */
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            /* ไม่ต้องเรียกใช้ Javascript */
            'ajax' => false,
            'token' => false
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Api demo}'
        ));
        // url
        $fieldset->add('text', array(
            'id' => 'api_url',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-world',
            'label' => 'Api url',
            'value' => WEB_URL.'api.php/v1/user/me',
            'readonly' => true
        ));
        // result
        $fieldset->add('textarea', array(
            'id' => 'api_result',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-file',
            'label' => 'Result',
            'rows' => 8,
            'value' => var_export($me, true),
            'readonly' => true
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
