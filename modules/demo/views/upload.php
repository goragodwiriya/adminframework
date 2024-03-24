<?php
/**
 * @filesource modules/demo/views/upload.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Upload;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-upload
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตัวอย่างฟอร์ม
     *
     * @return string
     */
    public function render(Request $request)
    {
        /* คำสั่งสร้างฟอร์ม */
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/demo/model/upload/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => 'อัปโหลดรูปภาพพร้อมกับแสดงรูปตัวอย่างก่อนอัปโหลด'
        ));
        $fieldset->add('file', array(
            'id' => 'image_upload',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-thumbnail',
            'label' => '{LNG_Image}',
            'comment' => '{LNG_Upload :type files} {LNG_no larger than :size}',
            'dataPreview' => 'imgPicture',
            'previewSrc' => WEB_URL.'skin/img/noicon.png',
            'accept' => array('jpg', 'jpeg', 'png')
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => 'อัปโหลดไฟล์ได้ครั้งละหลายๆไฟล์'
        ));
        $fieldset->add('file', array(
            'name' => 'pdf_uploads[]',
            'id' => 'pdf_uploads',
            'labelClass' => 'g-input icon-gallery',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload :type files} {LNG_no larger than :size}',
            'accept' => array('jpg', 'jpeg', 'png'),
            'multiple' => true,
            'dataPreview' => 'multi_preview'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        /* ปุ่ม submit */
        $fieldset->add('submit', array(
            'class' => 'button save large icon-upload',
            'value' => '{LNG_Upload}'
        ));
        /* input ชนิด hidden */
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => time()
        ));
        $fieldset->add('p', array(
            'class' => 'comment',
            'innerHTML' => 'เนื่องจากมีการใช้ token ในการอัปโหลด ถ้าไม่มีข้อผิดพลาดการอัปโหลดจะต้องรีเฟรชหน้าเพจใหม่ถึงจะสามารถอัปโหลดได้อีกครั้ง'
        ));
        $fieldset->add('pre', array(
            'id' => 'uploadResult',
            'class' => 'margin-top-right-bottom-left'
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => 'jpg, jpeg, png',
            '/:size/' => \Kotchasan\Http\UploadedFile::getUploadSize()
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
