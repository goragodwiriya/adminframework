<?php
/**
 * @filesource modules/demo/views/table.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Demo\Table;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;

/**
 * module=demo-table
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงตาราง
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สถานะสมาชิก
        $member_status = array(-1 => '{LNG_all items}');
        foreach (self::$cfg->member_status as $key => $value) {
            $member_status[$key] = '{LNG_'.$value.'}';
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        /* การใช้งานตารางและคำอธิบายเพิ่มเติมสามารถดูได้ที่ \Kotchasan\DataTable */
        $table = new DataTable(array(
            /* ID ของ DIV ที่ครอบตารางอยู่ (ไม่ระบุก็ได้) */
            //'id' => 'id_demo_table',
            /* Class ของตาราง (ไม่ระบุก็ได้) */
            //'class' => 'class_demo_table',
            /* แสดงผล อธิบายการทำงานของ Query */
            //'explain' => true,
            /* ตารางกว้าง 100% */
            //'fullwidth' => true,
            /* แสดงเส้นกรอบ */
            //'border' => true,
            /* แสดงตารางแบบ responsive */
            //'responsive'=>true,
            /* แสดง Query ที่ประมวลผลบน console ของ Browser */
            //'debug' => true,
            /* ปิดการทำงาน Javascript ของตาราง */
            //'enableJavascript' => false,
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Demo\Table\Model::toDataTable(),
            /* แอเรย์ของข้อมูล ถ้าไม่ใช้ Model */
            //'datas' => array(array('id' => 1, 'name' => 'One'), array('id' => 2, 'name' => 'Two')),
            /* ตัวเลือกจำนวนการแสดงผลรายการต่อหน้า */
            //'entriesList' => array(10, 20, 30, 40, 50, 100),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('table_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('table_sort', 'id desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'visited', 'website'),
            /* คอลัมน์ที่ต้องการ ถ้าแตกต่างจาก Model */
            //'fields' => array('id', 'name', 'username'),
            /* ฟิลเตอร์เริ่มต้น เพิ่มเติมจาก Model */
            //'defaultFilters' => array('id', 1),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name'),
            /* การค้นหาที่กำหนดเอง ตารางจะไม่ค้นหาข้อมูลให้ ต้องเขียน Query ลงใน Model ด้วยตัวเอง แต่ยังสามารถแสดงช่องค้นหาได้ */
            //'autoSearch' => false,
            /* ซ่อนฟอร์มค้นหา (ถึงจะระบุ searchColumns มา) */
            //'searchForm' => false,
            /* ไม่แสดง Checkbox */
            //'hideCheckbox' => true,
            /* คอลัมน์ที่แสดง checkbox*/
            //'checkCol' => 0,
            /* แสดงปุ่ม เพิ่ม-ลบ แถวของตาราง */
            //'pmButton' => true,
            /* แสดง Caption ของตาราง */
            'showCaption' => true,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/demo/model/table/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'save',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'sendpassword' => '{LNG_Get new password}',
                        'active_1' => '{LNG_Can login}',
                        'active_0' => '{LNG_Unable to login}',
                        'delete' => '{LNG_Delete}'
                    )
                ),
                array(
                    'class' => 'button icon-print print border',
                    'href' => 'export.php?module=demo-table&typ=print',
                    'target' => 'export',
                    'text' => '{LNG_Print}'
                )
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                'status' => array(
                    'name' => 'status',
                    'default' => -1,
                    'text' => '{LNG_Member status}',
                    'options' => $member_status,
                    'value' => $request->request('status', -1)->toInt()
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Name}',
                    'sort' => 'name'
                ),
                'active' => array(
                    'text' => '',
                    'colspan' => 2
                ),
                'phone' => array(
                    'text' => '{LNG_Phone}',
                    'class' => 'center'
                ),
                'status' => array(
                    'text' => '{LNG_Member status}',
                    'class' => 'center'
                ),
                'create_date' => array(
                    'text' => '{LNG_Created}',
                    'class' => 'center'
                ),
                'lastvisited' => array(
                    'text' => '{LNG_Last login} ({LNG_times})',
                    'class' => 'center',
                    'sort' => 'lastvisited'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'active' => array(
                    'class' => 'center'
                ),
                'social' => array(
                    'class' => 'center'
                ),
                'phone' => array(
                    'class' => 'center'
                ),
                'status' => array(
                    'class' => 'center'
                ),
                'create_date' => array(
                    'class' => 'center'
                ),
                'lastvisited' => array(
                    'class' => 'center'
                )
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'print' => array(
                    'class' => 'icon-print button print',
                    'text' => '{LNG_Print}',
                    'submenus' => array(
                        'porpdf' => array(
                            'class' => 'icon-pdf',
                            /* เรียกไปยัง export.php ที่โมดูลที่กำหนด เช่น \Demo\Printdemo\Controller::export */
                            'href' => 'export.php?module=demo-printdemo&id=:id',
                            /* บังคับให้เปิดเป็นหน้าใหม่ */
                            'target' => '_export',
                            'text' => 'Pdf'
                        ),
                        'porprint' => array(
                            'class' => 'icon-print',
                            'href' => 'export.php?module=demo-printdemo&id=:id',
                            'target' => '_export',
                            'text' => 'Print'
                        )
                    )
                ),
                'preview' => array(
                    'class' => 'icon-info button orange notext',
                    'id' => ':id',
                    'title' => '{LNG_Show}'
                ),
                'edit' => array(
                    'class' => 'icon-edit button green',
                    /* เรียกไปยัง index.php?module=demo */
                    'href' => $uri->createBackUri(array('module' => 'demo', 'page' => 'form', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิ่ม */
            'addNew' => array(
                'class' => 'float_button icon-register',
                'href' => $uri->createBackUri(array('module' => 'demo', 'page' => 'form')),
                'title' => '{LNG_Register}'
            )
        ));
        // save cookie
        setcookie('table_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
        setcookie('table_sort', $table->sort, time() + 3600 * 24 * 365, '/');
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $create_date = date('Ymd', strtotime($item['create_date']));
        // สร้าง Barcode
        $item['create_date'] = '<img style="max-width:none" alt="" src="data:image/png;base64,'.base64_encode(\Kotchasan\Barcode::create($create_date, 34, 9)->toPng()).'">';
        // สถานะการเข้าระบบ (แสดงไอคอน)
        if ($item['active'] == 1) {
            $item['active'] = '<a id=access_'.$item['id'].' class="icon-valid notext access" title="{LNG_Can login}"></a>';
        } else {
            $item['active'] = '<a id=access_'.$item['id'].' class="icon-valid notext disabled" title="{LNG_Unable to login}"></a>';
        }
        if ($item['social'] == 1) {
            $item['social'] = '<span class="icon-facebook notext"></span>';
        } elseif ($item['social'] == 2) {
            $item['social'] = '<span class="icon-google notext"></span>';
        } else {
            $item['social'] = '';
        }
        // สถานะสมาชิก
        $item['status'] = isset(self::$cfg->member_status[$item['status']]) ? '<span class=status'.$item['status'].'>{LNG_'.self::$cfg->member_status[$item['status']].'}</span>' : '';
        // เบอร์โทร (ลิงค์)
        $item['phone'] = self::showPhone($item['phone']);
        // แทนที่ชื่อด้วย x
        $item['name'] = preg_replace('/[^\s]/', 'x', $item['name']);
        // คืนค่ากลับไปแสดงผล
        return $item;
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param string $btn
     * @param array $attributes
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        // ปุ่ม preview ตาม key ของ button
        if ($btn == 'preview') {
            // คืนค่า false ถ้าไม่แสดงปุ่ม, คืนค่า $attributes แสดงปุ่ม
            return $item['id'] == 1 ? false : $attributes;
        }
        return $attributes;
    }
}
