<?php
/**
 * @filesource modules/demo/views/promptpay.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Promptpay;

use Kotchasan\Currency;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=demo-promptpay
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
        /* สร้าง DIV */
        $div = Html::create('div', array(
            'class' => 'setup_frm'
        ));
        $fieldset = $div->add('fieldset', array(
            'title' => 'Prompt Pay QR Code'
        ));
        // ชื่อผู้รับเงิน
        $name = 'กรกฎ วิริยะ';
        // เบอร์โทร
        $promptpay_id = '660868142004';
        // จำนวนเงิน
        $amount = 999;
        /*
        สร้าง payload สำหรับ Prompt Pay
        ผู้รับเงิน เบอร์โทรศัพท์ (+66)0868142004
        หรือ เลขบัตรประชาชน 13 หลัก
        และ จำนวนเงินที่ต้องชำระ (999)
        ถ้าไม่ระบุจำนวนเงิน จะสร้าง QR Code สำหรับรับชำระเงินได้หลายครั้งโดยกรอกจำนวนเงินเอง
         */
        $promptpay = \Kotchasan\Promptpay::create($promptpay_id, $amount);
        // payload สำหรับนำไปสร้าง QR Code
        $payload = $promptpay->payload();
        // สร้าง QR Code จากบริการของ Google ขนาด 300*300 พิกเซล
        $qr = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$payload;
        // แสดงผล QR Code
        $card = '<article class=qrpayment>';
        $card .= '<header><img src="'.WEB_URL.'modules/demo/img/thaiqrpayment.png" alt="Prompt Pay"></header>';
        $card .= '<img src="'.WEB_URL.'modules/demo/img/promptpay.png" alt="Prompt Pay" class=promptpay>';
        $card .= '<img src="'.$qr.'" alt="QR Code" class=qr>';
        $card .= '<footer>';
        $card .= '<div><small>ผู้รับเงิน</small>'.$name.'</div>';
        $card .= '<div><small>จำนวนเงิน</small>'.Currency::format($amount).' บาท</div>';
        $card .= '</footer>';
        $card .= '</article>';
        // แสดงผล QR
        $fieldset->add('div', array(
            'innerHTML' => $card
        ));
        // คืนค่า HTML
        return $div->render();
    }
}
