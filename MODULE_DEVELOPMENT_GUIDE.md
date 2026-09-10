# Module Development Guide — Pluggable Modules for Now.js / Kotchasan

คู่มือการเขียน "โมดูล" แบบถอด‑ใส่ได้ (pluggable) บน adminframework
(Now.js front‑end + Kotchasan/Gcms back‑end)

> **ทุกข้อในคู่มือนี้ตรวจสอบกับซอร์สจริงแล้ว** ตัวอย่างอ้างอิงจากโมดูลที่ใช้งานได้จริง 2 ตัว
> - `/mnt/Server/htdocs/now.js/inventory` → โมดูล `inventory` (CRUD + หมวดหมู่ + อัปโหลดรูป) และ `repair` (autocomplete + timeline)
> - `/mnt/Server/htdocs/now.js/borrow` → โมดูล `borrow` (ตารางหลายเงื่อนไข + LineItems + modal + สต็อก)
>
> และในโปรเจ็คนี้เองมี **`modules/demo/`** ให้เปิดดู/แก้เล่นได้ทันที (ดูข้อ 0.1)

---

## 0) กฎทอง (อ่านก่อน)

1. **ห้ามแก้โค้ดหลัก (core)** — ไม่แก้ `js/main.js`, `Now/*`, `Kotchasan/*`, `Gcms/*`, `modules/index/*`
   ทุกอย่างของโมดูลอยู่ใต้ `modules/<module>/` + `templates/<module>/` เท่านั้น
2. **โมดูลถูก auto‑discover ทั้งหมด ไม่ต้องแก้ไฟล์กลางเลย** (ดูข้อ 1.1) — ต่างจากคู่มือรุ่นเก่า
   ที่บอกให้เพิ่ม `<script>` ใน `index.html` **ปัจจุบันไม่ต้องทำแล้ว**
3. **ถอดโมดูล = ลบ 2 โฟลเดอร์** (`modules/<module>/`, `templates/<module>/`) และ drop ตารางถ้าต้องการ
   เมนู/สิทธิ์/route/CSS หายไปเอง เพราะทุกอย่างมาจากในโฟลเดอร์โมดูล
4. **HTML‑first**: เขียน HTML + `data-*` ให้มากที่สุด เพิ่ม JS เฉพาะเมื่อไม่มี `data-*` ทำแทนได้
5. **CSS‑reuse**: ใช้คลาสมาตรฐานที่มีอยู่ก่อนเสมอ เพิ่ม CSS เท่าที่จำเป็น และตั้ง prefix ชื่อโมดูล
6. **แก้ฐานข้อมูล = ต้องแก้ตัวติดตั้งด้วยเสมอ** ทั้ง `install/database.sql` (ติดตั้งใหม่)
   และ `install/upgrade2.php` (ปรับรุ่นของเดิม) ดูข้อ 8

---

## 0.1) โมดูล `demo` — ตัวอย่างที่รันได้ในโปรเจ็คนี้

โมดูลสาธิตที่ติดมากับ adminframework ครบทุกส่วนของคู่มือนี้ในขนาดเล็กที่สุด
และ **ไม่มีตารางในฐานข้อมูลเลย** (ข้อมูลทั้งหมดสร้างจาก `models/dashboard.php`
และ `models/records.php`) จึงเปิดใช้ได้ทันทีหลังติดตั้ง ไม่ต้องรันสคริปต์อะไรก่อน

| ไฟล์ | สาธิตอะไร |
|---|---|
| `modules/demo/admin.js` | `RouterManager.register()` ใน event `router:initialized` (ข้อ 3) |
| `modules/demo/controllers/init.php` | hook `initMenus` + `initDashboard` + `initDashboardBlocks` (ข้อ 2.2) |
| `modules/demo/models/dashboard.php` | รูปร่างของการ์ดและบล็อกที่หน้าแรกรับ |
| `modules/demo/controllers/quarterlysales.php` ฯลฯ | ข้อมูลของ `data-component="graph"` — URL มีขีดกลางได้ (`api/demo/quarterly-sales`) |
| `modules/demo/controllers/records.php` | สัญญาของ `data-table` (`data` + `meta` + `columns`) และ modal ที่เซิร์ฟเวอร์สั่งเปิด (ข้อ 4.5) |
| `modules/demo/controllers/events.php` | ข้อมูลของปฏิทิน (รับ `?start=&end=` ตามช่วงที่มองเห็น) |
| `modules/demo/controllers/settings.php` | `data-load-api` + `formErrorResponse()` (ข้อ 4.2) |
| `templates/demo/*.html` | โครงหน้ามาตรฐานตามข้อ 7 |

**ตัวอย่าง Dashboard ไม่มีหน้าของตัวเอง — มันไปอยู่บนหน้าแรก** การ์ดมาจาก
`initDashboard` และบล็อกมาจาก `initDashboardBlocks` หน้าแรก
(`templates/index.html`) ไม่รู้จักโมดูลไหนเลย แค่วน `data-for` ตามของที่ได้มา
ส่วนเมนู **ตัวอย่าง** เหลือแค่ตารางกับฟอร์ม และโผล่ให้ทุกคนที่ล็อกอินเห็น
(โมดูลจริงควรกันด้วยสิทธิ์)

---

## 0.2) บล็อกบนหน้าแรกมีสามชนิด

`initDashboardBlocks` คืนรายการที่มี `kind` เป็นตัวบอกชนิด หน้าแรกเลือกวาด
ด้วย `data-if` — ไม่ใส่ `kind` มาถือว่าเป็นกราฟ (เข้ากันได้กับของเดิม)

```php
// graph — ต้องมี type: bar | pie | line | donut | gauge
['kind' => 'graph', 'title' => …, 'type' => 'bar', 'url' => 'api/demo/quarterly-sales', 'size' => 'block6']
// table — ต้องมี id, และ API ต้องส่ง columns มาด้วย
['kind' => 'table', 'title' => …, 'id' => 'demoRecords', 'url' => 'api/demo/records', 'size' => 'block12']
// calendar — ต้องมี id เช่นกัน
['kind' => 'calendar', 'title' => …, 'id' => 'demoEvents', 'url' => 'api/demo/events', 'size' => 'block12']
```

**ทุกชนิดผูกค่าด้วย `data-attr` เท่านั้น ห้าม `{{block.url}}`** —
`processInterpolation()` ข้ามแอตทริบิวต์ `data-*` ทั้งหมด (ยกเว้น
`data-options-key` กับ `data-attr`) ค่าจึงค้างเป็นข้อความ `{{block.url}}`
แล้วบล็อกว่างเปล่าโดยไม่มีข้อผิดพลาดให้เห็น

**ตาราง** หน้าแรกไม่มี `<thead>` เขียนไว้ให้ (ไม่รู้จักคอลัมน์ของโมดูลไหน)
จึงเปิด `data-dynamic-columns="true"` แล้วให้ API ส่ง `columns` มาสร้างหัวตาราง
— คีย์ที่ `createDynamicHeaders()` อ่าน: `field label sort filter type format
formatter class cellClass options optionsKey template emptyText autoNumber
visible searchable i18n`

- `class` = คลาสของ `<th>` · `cellClass` = คลาสของ `<td>`
- `options` ของ `format: lookup` ต้อง cast เป็น object ในฝั่ง PHP เสมอ
  อาเรย์ที่คีย์เป็น 0,1,2 เรียงกันจะกลายเป็น JSON list แล้ว lookup เลื่อนทั้งคอลัมน์
- `data-table` (มาจาก `block.id`) **ห้ามซ้ำทั้งหน้า** เพราะเป็น key ใน
  `TableManager.state.tables` — ตั้งชื่อขึ้นต้นด้วยชื่อโมดูลเสมอ
- `<thead><tr></tr></thead><tbody></tbody>` ว่าง ๆ ต้องมีอยู่จริงในเทมเพลต
  ใส่ `<table>` เปล่าจะพังด้วย *Cannot set properties of null (setting 'innerHTML')*

**ปฏิทิน** ต่างจากกราฟและตารางตรงที่ **ไม่มี observer เฝ้า DOM** —
`EventCalendar` สแกนใหม่เฉพาะตอน `route:changed` / `modal:shown` /
`page:loaded` / `pageshow` / `visibilitychange` ซึ่งเกิด *ก่อน* ApiComponent
ได้คำตอบ ปฏิทินที่ `data-for` สร้างจึงไม่ถูก init เลย โมดูลที่ส่งบล็อกชนิดนี้
ต้องปลุกเองใน `admin.js` และต้องโหลดบันเดิลของปฏิทินเองด้วย (index.php
โหลดให้แค่ core/table/graph/serviceworker)

```js
EventManager.on('api:content-rendered', ({instance}) => {
  window.EventCalendar?.discoverCalendars(instance?.element || document);
});
```

> ตัวที่ส่งมากับ `EventManager` คือ `{instance, ...detail}` — **ไม่มีคีย์
> `element`** (มีเฉพาะใน `detail` ของ CustomEvent บน DOM) ต้องอ่านจาก
> `instance.element`

ฝั่ง API ของปฏิทินรับ `?start=YYYY-MM-DD&end=YYYY-MM-DD` ของช่วงที่มองเห็น
(ยิงใหม่ทุกครั้งที่เปลี่ยนเดือน) และคืนรายการนัดหมายในคีย์ `data`
รูปร่างต่อหนึ่งนัด: `{id, title, start, end}` + `allDay` `color` `location`
`description` `category` เป็นตัวเลือก — `allDay` เป็นจริงเสมอเว้นแต่ส่ง
`false` มาชัด ๆ

> **เอาไปทำงานจริง = ลบทิ้ง** — `rm -rf modules/demo templates/demo`
> เมนู การ์ด route และ API หายไปพร้อมกัน ไม่ต้องแก้ไฟล์กลางแม้แต่บรรทัดเดียว

---

## 1) โครงสร้างโมดูล

```
modules/<module>/
├── admin.js                    # front-end bootstrap: RouterManager.register() + helper
├── styles.css                  # (ออปชัน) CSS เฉพาะโมดูล
├── controllers/
│   ├── init.php                # hook: initMenus / initPermission  (auto-discovered)
│   ├── <list>.php              # ตาราง        extends \Gcms\Table
│   ├── <record>.php            # ฟอร์ม        extends \Gcms\Api
│   └── category.php            # (ออปชัน) หมวดหมู่ extends \Gcms\Category
└── models/
    ├── <list>.php              # query ของตาราง  extends \Kotchasan\Model
    └── <record>.php            # get/save record extends \Kotchasan\Model

templates/<module>/
├── *.html                      # หน้าเว็บของโมดูล (1 route = 1 ไฟล์)
└── styles.css                  # (ออปชัน) CSS ของเทมเพลต — โหลดอัตโนมัติเช่นกัน

modules/<module>/install/
├── database.sql                # ตารางของโมดูลนี้ — **ประกาศที่นี่ที่เดียว**
│                               #   ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
│                               #   (ประกาศสองที่ = ติดตั้งใหม่ล้ม "Table already exists")
├── upgrade.php                 # พาฐานเดิมมาถึงนิยามข้างบน (upgrade_core.php เรียกให้เอง)
└── language.php                # คำแปลที่โมดูลนี้ต้องใช้ — ดูข้อ 6.1

install/database.sql            # ตารางของ *โปรเจ็ค* ที่ไม่ได้เป็นของโมดูลไหน
install/upgrade2.php            # การปรับรุ่นเฉพาะของโปรเจ็ค (ตารางของโมดูลไม่ต้องมาที่นี่)
```

### 1.1 การ auto‑discovery (กลไกจริง — ไม่ต้องแก้ไฟล์กลาง)

| สิ่งที่ต้องการ | ใครไปหยิบมาให้ | เงื่อนไข |
|---|---|---|
| โหลด JS ของโมดูล | `index.php` วน `scandir('modules')` | มีไฟล์ `modules/<m>/admin.js` |
| โหลด CSS ของโมดูล | `index.php` | มีไฟล์ `modules/<m>/styles.css` |
| โหลด CSS ของเทมเพลต | `index.php` | มีไฟล์ `templates/<m>/styles.css` |
| เมนู | `\Index\Menus\Controller::getMenus()` → `\Gcms\Controller::initModule($menus,'initMenus',$login)` | มี `modules/<m>/controllers/init.php` ที่มีเมธอด `initMenus` |
| สิทธิ์ | `\Gcms\Controller::initModule($p,'initPermission',$login)` | เมธอด `initPermission` |
| route | `admin.js` เรียก `RouterManager.register()` ใน event `router:initialized` | — |

`index.php` ข้ามโมดูล `index` เสมอ (`$skipModules = ['index']`) และโหลด `admin.js` **ก่อน** `js/main.js`
โมดูลจึงผูก listener ทัน event `router:initialized` เสมอ

---

## 2) Back‑end

### 2.1 URL → Controller
```
api/<module>/<controller>/<method>
      │          │            └─ เมธอดใน class (ไม่ใส่ = index)
      │          └─ modules/<module>/controllers/<controller>.php
      └─ ชื่อโฟลเดอร์โมดูล
```
namespace ต้องเป็น `Ucfirst(module)\Ucfirst(controller)` เสมอ เช่น
`api/inventory/product/save` → `\Inventory\Product\Controller::save()`
→ ไฟล์ `modules/inventory/controllers/product.php`

**Autoloader** map ชื่อคลาสเป็นไฟล์แบบนี้ (`Kotchasan/load.php::getClassPath`)
```
\Inventory\Product\Controller  →  modules/inventory/controllers/product.php
\Inventory\Product\Model       →  modules/inventory/models/product.php
```
> ชื่อ segment สุดท้ายบอกโฟลเดอร์ (`Controller`→`controllers/`, `Model`→`models/`)
> **ถ้าไฟล์ไม่ตรงชื่อ จะ fatal ทันที** เช่น `namespace Borrow\Borrow` + `Model::get()`
> ต้องมี `modules/borrow/models/borrow.php`

### 2.2 `controllers/init.php` — เมนู + สิทธิ์

```php
namespace Inventory\Init;

use Gcms\Api as ApiController;

class Controller extends \Gcms\Controller
{
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = [
            'value' => 'can_manage_inventory',
            'text' => '{LNG_Can manage the} {LNG_Inventory}'
        ];
        return $permissions;
    }

    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login || !ApiController::hasPermission($login, 'can_manage_inventory')) {
            return $menus;
        }
        $children = [
            ['title' => '{LNG_List of} {LNG_Inventory}', 'url' => '/inventory', 'icon' => 'icon-list']
        ];
        // เมนูหลัก
        $menus = parent::insertMenuBefore($menus, [
            ['title' => '{LNG_Inventory}', 'icon' => 'icon-product', 'children' => $children]
        ], 'settings');
        // เมนูตั้งค่า (ไปอยู่ใต้ Settings)
        if (ApiController::hasPermission($login, 'can_config')) {
            $menus = parent::insertMenuChildren($menus, [
                ['title' => '{LNG_Module settings}', 'url' => '/inventory-settings', 'icon' => 'icon-cog']
            ], 'settings');
        }
        return $menus;
    }
}
```

**ตัวช่วยจัดเมนูใน `\Gcms\Controller`** (มีจริงทั้งหมด)
`insertMenuAfter`, `insertMenuBefore`, `insertMenuChildren`, `ensureMenuStructure`,
`insertMenuByKey`, `findMenuPosition`, `hasMenu`

> ⚠️ `insertMenuAfter/Before` ใช้ `array_splice` ภายใน — **key ที่เป็น string ของ item ที่แทรกจะหายไป**
> (กลายเป็น index ตัวเลข) key เดิมของ `$menus` เช่น `settings` ยังอยู่ครบ
> ท้ายสุด `getMenus()` คืน `array_values($menus)` อยู่แล้ว จึงไม่กระทบการแสดงผล
> แต่ **ห้ามพึ่ง key ของเมนูที่ตัวเองเพิ่งแทรก**

### 2.3 Controller ตาราง — `extends \Gcms\Table`

base class จัดการ pagination / sort / search / count / bulk action / export ให้แล้ว
เรา override เฉพาะที่ต้องการ

```php
class Controller extends \Gcms\Table
{
    // กัน SQL injection — ต้องใส่เสมอถ้าเปิดให้เรียงลำดับ
    protected $allowedSortColumns = ['id', 'topic', 'product_no', 'stock', 'is_active'];

    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, 'can_manage_inventory')) {
            return $this->errorResponse('Permission required', 403);
        }
        return true;                                  // true = ผ่าน
    }

    protected function getCustomParams(Request $request, $login): array
    {
        return ['category_id' => $request->get('category_id')->topic()];
    }

    protected function toDataTable($params, $login = null)     // WHERE/filter (ใช้ COUNT ด้วย)
    {
        return Model::toDataTable($params);
    }

    protected function toJoinQuery($inner, array $params, $login)  // (ออปชัน) JOIN หนัก ๆ
    {
        return null;                                  // null = ใช้ toDataTable() ตรง ๆ
    }

    protected function formatDatas(array $datas, $login = null): array
    {
        foreach ($datas as $item) { $item->image = ...; }   // $datas เป็น array ของ object
        return $datas;
    }

    protected function getFilters($params, $login = null)      // ตัวเลือกของคอลัมน์ filter
    {
        return ['category_id' => $category->toOptions('category_id')];
    }

    protected function getOptions(array $params, $login) { return []; }  // lookup อื่น ๆ
    protected function getColumns(array $params, $login) { return []; }  // นิยามคอลัมน์จากฝั่ง PHP
}
```

**Response ของ `index()`** ถูกห่อเป็น
```json
{"success":true,"data":{"data":[...],"columns":[],"filters":{},"options":{},"meta":{...}}}
```

**Bulk / row action** — `POST api/<m>/<c>/action` ส่ง `action` มา แล้ว base class เรียก
เมธอดตามชื่อ (`\Gcms\Table::actionToMethodName`)

| `action` ที่ส่งมา | เมธอดที่ถูกเรียก |
|---|---|
| `delete` | `handleDeleteAction($request, $login)` |
| `detail` | `handleDetailAction($request, $login)` |
| `send_password` | `handleSendPasswordAction($request, $login)` |
| `active_2` | `handleActive2Action($request, $login)` |

`action()` เรียก `validateCsrfToken()` + `authenticateRequest()` ให้แล้ว
แต่ **สิทธิ์ต้องเช็คเองในแต่ละ handler** (`checkAuthorization()` ใช้กับ `index()` เท่านั้น)

### 2.4 Controller ฟอร์ม — `extends \Gcms\Api`

```php
class Controller extends ApiController   // use Gcms\Api as ApiController;
{
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) return $this->errorResponse('Unauthorized', 401);
            if (!ApiController::hasPermission($login, 'can_manage_inventory')) {
                return $this->errorResponse('Permission required', 403);
            }
            return $this->successResponse([
                'data' => Model::get($request->get('id', 0)->toInt()),
                'options' => ['category_id' => $category->toOptions('category_id')]
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    public function save(Request $request)
    {
        ApiController::validateMethod($request, 'POST');
        $this->validateCsrfToken($request);                     // บังคับทุก POST
        $login = $this->authenticateRequest($request);
        if (!$login) return $this->redirectResponse('/login', 'Unauthorized', 401);
        if (!ApiController::canModify($login, ['can_manage_inventory'])) {
            return $this->errorResponse('Permission required', 403);
        }
        // ... validate
        if (!empty($errors)) return $this->formErrorResponse($errors, 400);
        // ... save
        return $this->redirectResponse('/inventory', 'Saved successfully', 200, 1000);
    }
}
```

**Helper ที่มีจริง** (`\Kotchasan\ApiController` + `\Gcms\Api`)

| กลุ่ม | เมธอด |
|---|---|
| ตอบกลับ | `successResponse($data,$msg,$code)` · `errorResponse($msg,$code,$e)` · `formErrorResponse($errors,$code)` · `validationErrorResponse($errors,$msg)` · `notificationResponse($msg)` · `redirectResponse($url,$msg,$code,$delay,$target)` |
| ตรวจสอบ | `validateMethod($request,'POST')` · `validateCsrfToken($request)` · `authenticateRequest($request)` · `validate($request,$rules)` |
| สิทธิ์ | `hasPermission($login,'can_x')` · `isSuperAdmin` · `isAdmin` · `isNotDemoMode` · `canModify($login,['can_x'])` |
| อื่น ๆ | `initLanguage($request)` · `getAvatarUrl($id)` |

`redirectResponse('reload', ...)` = สั่ง reload หน้า/ตารางปัจจุบัน
พารามิเตอร์ที่ 5 `$target` ใส่ `'table'` เพื่อ reload เฉพาะตาราง

### 2.5 Model — `extends \Kotchasan\Model`

**Query Builder** — `static::createQuery()` หรือ `\Kotchasan\Model::createQuery()`

```php
static::createQuery()
    ->select('V.id', 'V.topic', 'I.product_no')      // ← select ต้องมาก่อน first()
    ->from('inventory V')                            // ชื่อตารางไม่ต้องใส่ prefix
    ->join('inventory_items I', [['I.inventory_id', 'V.id']], 'LEFT')
    ->where([['V.is_active', 1]])
    ->where([['V.topic','LIKE',$kw], ['I.product_no','LIKE',$kw]], 'OR')
    ->whereNotExists('borrow_items S', [['S.borrow_id','B.id'], ['S.status','>',0]])
    ->groupBy('V.id')->orderBy('V.topic')->limit(20)
    ->fetchAll(true);            // true = array, false/ไม่ใส่ = object
```

> ⚠️ **กับดักที่พบบ่อย (API เปลี่ยนจาก Kotchasan รุ่นเก่า)**
>
> | เขียนผิด (แบบเก่า) | เขียนถูก (ปัจจุบัน) |
> |---|---|
> | `->first('A.*','B.name')` | `->select('A.*','B.name')->first()` |
> | `->first($arraySelect)` | `->select(...$arraySelect)->first()` |
> | `->first([$q0,'alias'])` (subquery หลายชุด) | ใช้หลาย query แยก หรือ `->select([$q,'alias'])->from(...)` (ต้องมี FROM) |
> | `->order('a','b')` | `->orderBy('a')->orderBy('b')` |
> | `->toArray()->execute()` | `->fetchAll(true)` |
> | `->notExists(...)` | `->whereNotExists(...)` |
> | `->andWhere($w,'OR')` | `->where($w,'OR')` |
> | `Sql::CONCAT(['a','_','b'],'id')` | `Sql::CONCAT(['a','b'],'id','_')` ← separator เป็น **arg ที่ 3** |
> | `first(true)` เพื่อเอาหลายแถว | `fetchAll(true)` — `first()` คืนแถวเดียวเสมอ |
>
> `first($x)`: ถ้า `$x` เป็น bool = "คืนเป็น array", ถ้าเป็น array = "params ของ prepared statement"
> **ไม่ใช่รายชื่อคอลัมน์**

**เขียนข้อมูล** — `\Kotchasan\DB::create()`

```php
$db = \Kotchasan\DB::create();
$id = $db->insert('inventory', $data);                       // คืน id
$db->update('inventory', [['id', $id]], $data);              // where เป็น array เสมอ
$db->delete('inventory_items', [['inventory_id', $id]], 0);  // limit 0 = ทั้งหมด (ค่าปริยาย 1!)
$row = $db->first('inventory', [['id', $id]]);               // คืน object|null
$n   = $db->nextId('category', [['type','unit']], 'category_id');
```
> `DB::delete()` มี `$limit = 1` เป็นค่าปริยาย — **ถ้าจะลบหลายแถวต้องส่ง `0`**
> `DB` **ไม่มี** `createQuery()` — ต้องใช้ `\Kotchasan\Model::createQuery()`

**Log** — `\Index\Log\Model::add($src_id, $module, $action, $topic, $member_id, $datas = null)`

### 2.6 Input (`$request->get|post|request('name')`)

`toInt()` `toFloat()` `toDouble()` `toBoolean()` `toString()` `toArray()`
`topic()` `text()` `textarea()` `email()` `phone()` `url()` `date()` `datetime()`
`filter('a-z0-9')` `number()`

รับ array: `$request->post('items', [])->toArray()`, `$request->post('ids', [])->toString()`

### 2.7 ความปลอดภัย (บังคับ)

- ทุก endpoint: `authenticateRequest()` แล้วเช็ค `hasPermission()`/`canModify()`
- ทุก POST: `validateCsrfToken()` (`\Gcms\Table::action()` ทำให้แล้ว)
- ทุกตารางที่เรียงลำดับได้: `$allowedSortColumns`
- ค่าที่มาจากผู้ใช้และต้องต่อเข้า SQL ตรง ๆ: ใช้ `->where([[...,'LIKE',$kw]])` แทน `Sql::create()`
  ถ้าเลี่ยงไม่ได้จริง ๆ ค่อย escape เอง
- การกระทำที่แก้ข้อมูลของผู้อื่น: ตรวจความเป็นเจ้าของก่อนเสมอ (ดู `\Borrow\My\Model::remove()`)

---

## 3) Front‑end: `admin.js`

```js
/**
 * modules/inventory/admin.js
 */
EventManager.on('router:initialized', () => {
  RouterManager.register('/inventory', {
    template: 'inventory/inventories.html',
    title: '{LNG_List of} {LNG_Inventory}',
    requireAuth: true
  });
  RouterManager.register('/inventory-edit', {
    template: 'inventory/product.html',
    title: '{LNG_Equipment}',
    menuPath: '/inventory',        // ให้เมนูแม่ยังไฮไลต์ตอนอยู่หน้าฟอร์ม
    requireAuth: true
  });
});

/** formatter ของคอลัมน์ ต้องเป็น global function */
function formatInventoryBarcode(cell, rawValue, rowData, attributes) {
  cell.innerHTML = `<span class="inventory-barcode"><img src="${rowData.barcode}"><span>${rawValue}</span></span>`;
}
```

`RouterManager.register(path, config)` — `template`, `title`, `requireAuth`, `requireGuest`, `menuPath`

**สิ่งที่ควรอยู่ใน `admin.js` เท่านั้น**: route + global formatter + ฟังก์ชันของ `data-on-load`
**สิ่งที่ไม่ควรเขียนเอง**: autocomplete, ตารางแถว, modal, การส่งฟอร์ม — เฟรมเวิร์กมีให้หมดแล้ว (ข้อ 4)

---

## 4) Front‑end: เลือกแพทเทิร์นตามรูปร่างข้อมูล

| ต้องการ | แพทเทิร์น | ตัวอย่างจริง |
|---|---|---|
| ตารางรายการ + ค้นหา/หน้า/เรียง/ปุ่มในแถว | `data-table` + `data-source` | `templates/inventory/inventories.html` |
| ฟอร์มเพิ่ม/แก้ไข 1 เรคคอร์ด | `data-form` + `data-load-api` | `templates/inventory/product.html` |
| ตาราง lookup แก้ทั้งตารางแล้วกดบันทึกครั้งเดียว | `data-editable-rows` + `data-dynamic-columns` | `templates/inventory/categories.html`, `items.html` |
| เอกสารที่มีรายการย่อย (ใบยืม/ใบสั่งซื้อ) | `data-line-items` | `templates/borrow/index.html` |
| การ์ดสรุปตัวเลข | `data-component="api"` + `data-text` | `templates/borrow/myborrow.html` |
| หน้าต่างซ้อน (modal) | server ตอบ `actions:[{type:'modal',template}]` | `templates/borrow/detail.html` |

### 4.1 ตาราง (`data-table`)

```html
<table class="table border fullwidth"
       data-table="inventories"
       data-source="api/inventory/inventories"
       data-default-sort="id desc" data-page-size="25"
       data-search-columns="topic,product_no"
       data-show-checkbox="true"
       data-actions='{"delete":"Delete"}'
       data-action-url="api/inventory/inventories/action"
       data-action-params="status,due"
       data-row-actions='{
          "edit":   {"title":"Edit","className":"btn btn-success icon-edit"},
          "delete": {"title":"Delete","className":"btn btn-danger icon-delete",
                     "confirm":"Do you want to delete ?","condition":"status == 0"}
       }'>
  <thead>
    <tr>
      <th data-field="topic" data-sort="topic">{LNG_Equipment}</th>
      <th data-field="product_no" data-formatter="formatInventoryBarcode" class="center">…</th>
      <th data-field="category_id" data-filter="true" data-type="select"
          data-show-all="true" data-all-value="" data-format="lookup">{LNG_Category}</th>
      <th data-field="is_active" class="center"
          data-template="<button class='btn-table-action' data-action='active' data-value='${is_active}'></button>">…</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
```

| attribute | ความหมาย |
|---|---|
| `data-field` | ชื่อฟิลด์ใน `data[]` ของ response |
| `data-sort` | เปิดให้เรียง (ต้องอยู่ใน `$allowedSortColumns` ฝั่ง PHP) |
| `data-filter="true" data-type="select"` | สร้าง dropdown filter จาก `filters['<field>']` ของ response |
| `data-show-all="true" data-all-value=""` | ให้ TableManager เติมตัวเลือก "ทั้งหมด" ให้เอง — **PHP ไม่ต้องใส่มาเอง** |
| `data-format="lookup"` | แสดง `text` ของตัวเลือกแทนค่าดิบ (อ่านจาก `filters`/`options`) |
| `data-format` | `number` `currency` `percent` `date` `datetime` `time` `boolean` `filesize` `lookup` |
| `data-formatter="fnName"` | เรียก global function `fnName(cell, rawValue, rowData, attributes)` |
| `data-template` | HTML สั้น ๆ ที่แทน `${field}` ด้วยค่าในแถว (ค่าถูก escape ให้แล้ว) |
| `data-cell-class` | class ของ `<td>` (ต่างจาก `class` ที่เป็นของ `<th>`) |
| `data-attr="data:items"` | ตารางแบบ **local** — อ่านข้อมูลจากฟอร์มแม่ แทนการยิง API |

**รูปแบบ `filters` ที่ PHP ต้องคืน** — list ของ `{value, text}`
```php
return ['category_id' => $category->toOptions('category_id')];
// [['value'=>1,'text'=>'เครื่องใช้ไฟฟ้า'], ...]
```

**`data-row-actions` ต่อ 1 ปุ่ม**

| key | ผล |
|---|---|
| `title`, `className` | ข้อความ/คลาสของปุ่ม |
| `confirm` | ถามยืนยันก่อนทำ |
| `condition` | นิพจน์ต่อแถว ถ้าเป็นเท็จปุ่มจะไม่ถูกสร้าง เช่น `"status == 0 \|\| status == 1"`, `"!checked"` |
| `url` + `method` | ยิงไป endpoint นี้แทน `data-action-url` |
| `navigate: false` | ใช้คู่กับ `method:"get"` เพื่อ **ไม่** เปลี่ยนหน้า แต่ให้ ResponseHandler ทำงานกับ response (เช่นเปิด modal) |
| `params` | พารามิเตอร์เพิ่ม แทน `{field}` ด้วยค่าจากแถวได้ |

ตัวอย่างเปิด modal จากปุ่มในแถว (จาก `templates/borrow/order.html`)
```json
"delivery": {
  "title": "Delivery", "className": "btn btn-success icon-export",
  "url": "api/borrow/orderstatus/get", "method": "get", "navigate": false,
  "params": {"action":"delivery","borrow_id":"{borrow_id}","id":"{id}"},
  "condition": "remaining > 0"
}
```
ถ้าไม่ระบุ `url` payload ที่ส่งไป `data-action-url` คือ `{action, id, row}`
(bulk action ส่ง `{action, ids:[...]}`)

### 4.2 ฟอร์ม (`data-form`)

```html
<form data-form="inventoryProduct" action="api/inventory/product/save" method="post"
      data-ajax-submit="true" data-load-api="api/inventory/product/get"
      data-load-query-params="true" autocomplete="off" enctype="multipart/form-data">
  <input type="text" name="topic" data-attr="value:topic" required>
  <select name="is_active" data-options-key="is_active" data-attr="value:is_active"></select>
  <textarea name="detail" data-text="detail"></textarea>
  <input type="checkbox" class="switch" name="count_stock" value="1" data-attr="checked:count_stock">
  <div data-if="id == 0"> … แสดงเฉพาะรายการใหม่ … </div>
  <input type="hidden" name="id" data-attr="value:id">
</form>
```

| attribute | ความหมาย |
|---|---|
| `data-load-api` | GET มาเติมค่าให้ฟอร์ม โดยอ่าน `data.data` และ `data.options` |
| `data-load-query-params="true"` | ส่ง query string ของ URL ปัจจุบัน (`?id=5`) ต่อไปให้ API |
| `data-attr="value:field"` | ผูกค่าเข้า `value` (ใช้ `checked:field` กับ checkbox, `src:field` กับ `<img>`) |
| `data-text="field"` | ใส่ข้อความ / ค่าใน textarea |
| `data-options-key="key"` | เติม `<option>` จาก `options['key']` ของ response |
| `data-if="expr"` | ซ่อน/แสดงตามนิพจน์ |
| `data-on-load="fnName"` | เรียก global `fnName(element, data)` หลังโหลดข้อมูล (คืน function ไว้ cleanup ได้) |

**ช่องที่มี `data-options-key` หรือ `data-autocomplete`** จะถูกแปลงเป็น
"ช่องแสดงข้อความ + hidden ที่เก็บค่าจริง" โดยอัตโนมัติ (`TextElementFactory`)
ฝั่ง PHP จึงอ่านได้ทั้ง `$request->post('category_id')` (ค่า/ไอดี) และ
`$request->post('category_id_text')` (ข้อความที่พิมพ์) — ใช้ท่านี้เพื่อ "พิมพ์ชื่อใหม่แล้วสร้างหมวดหมู่ให้เลย"
```php
$save['category_id'] = \Inventory\Category\Controller::save('category_id', $request->post('category_id_text')->topic());
```

### 4.3 Autocomplete

```html
<input type="text" id="product_no" name="product_no" data-attr="value:product_no"
       data-autocomplete="true" data-source="api/repair/autocomplete/find"
       data-min-length="2" data-max-results="20">
```
- ฝั่ง JS ยิง `GET <source>?q=<คำค้น>` เสมอ
- **ฝั่ง PHP ต้องคืน array ตรง ๆ** ไม่ห่อ key อะไรอีก
```php
return $this->successResponse([
    ['value' => '0001', 'text' => '0001 : ASUS A550JX', 'topic' => 'ASUS A550JX'],
], 'Search completed');
```
- คีย์ที่ระบบอ่าน: `value` (ค่าที่ใส่ลงช่อง) / `text`|`label`|`name` (ข้อความที่แสดง)
  ฟิลด์อื่นที่แนบมาใช้กับ `data-fill` ได้

### 4.4 เอกสารที่มีรายการย่อย (`data-line-items`)

```html
<input type="text" id="borrow_product" data-role="product"
       data-autocomplete="true" data-source="api/borrow/autocomplete/find">
<input type="number" id="borrow_quantity" data-role="quantity" value="1" min="1">

<table data-line-items="items"
       data-detail-api="api/borrow/autocomplete/product"
       data-listen-select="[data-role='product']"
       data-api-params="[data-role]"
       data-merge="true" data-merge-key="product_no"
       data-allow-delete="true"
       data-attr="data:items">
  <thead><tr>
    <th data-field="topic" data-readonly="true">{LNG_Equipment}</th>
    <th data-field="quantity" data-type="number" data-min="1">{LNG_Quantity}</th>
    <th data-field="stock_text" data-display-only="true">{LNG_Stock}</th>
  </tr></thead>
  <tbody></tbody>
</table>
```
ลำดับการทำงาน
1. เลือกจาก autocomplete → เกิด event `change` บน `[data-role='product']`
2. LineItemsManager เก็บทุก input ที่ตรง `data-api-params` โดยใช้ **`data-role` เป็นชื่อพารามิเตอร์**
3. ยิง `GET <data-detail-api>?product=<ค่า>&quantity=<ค่า>` → เพิ่มแถวจาก `data` ที่ได้
4. ส่งฟอร์มจะได้ `items[0][topic]`, `items[0][quantity]`, … → PHP อ่านด้วย
   `$request->post('items', [])->toArray()`

| ชนิดคอลัมน์ | ผล |
|---|---|
| `data-type="number\|text\|currency\|select"` | เป็น input แก้ไขได้ (ส่งค่าไปกับฟอร์ม) |
| `data-readonly="true"` | input อ่านอย่างเดียว (ยังส่งค่าไปกับฟอร์ม) |
| `data-display-only="true"` | ข้อความเฉย ๆ (ไม่ส่งค่า) |

> `data-role` บน `<th>` **ไม่** ถูกคัดลอกไปยัง input ในแถว จึงไม่ชนกับช่องค้นหาด้านบน
> คอลัมน์ของ LineItems **ไม่รองรับ `data-formatter`** — ให้ API ส่งฟิลด์ที่จัดรูปแบบมาแล้ว (เช่น `stock_text`)

### 4.5 Modal

ฝั่ง PHP ตอบ action มา
```php
return $this->successResponse([
    'data' => $data,
    'options' => ['status' => $statusOptions],
    'actions' => [[
        'type' => 'modal', 'action' => 'open',
        'template' => 'borrow/orderstatus.html',
        'title' => '{LNG_Delivery} : '.$index->topic
    ]]
], 'OK');
```
เทมเพลต modal เป็น **ชิ้นส่วน HTML ล้วน ๆ** ไม่มี sidebar/topbar เช่น `templates/borrow/orderstatus.html`

> ⚠️ **ข้อจำกัดสำคัญ**: ตอนเปิด modal ระบบ scan เฉพาะ `ElementManager` และ `FormManager`
> **ไม่ scan `TableManager`** → `data-table` ในเทมเพลต modal จะไม่ทำงาน
> ถ้าต้องแสดงรายการใน modal ให้ใช้ `data-for` แทน

```html
<tbody data-for="item in items">
  <template>
    <tr>
      <td data-text="item.topic"></td>
      <td class="center" data-text="item.num_requests"></td>
      <td><span class="borrow-status" data-class="'status' + item.status" data-text="item.status_text"></span></td>
    </tr>
  </template>
</tbody>
```
`data-for` ต้องเขียนเป็น `<ตัวแปร> in <array>` และ **ต้องมี `<template>` อยู่ข้างใน** เสมอ

### 4.6 actions อื่น ๆ ที่ ResponseHandler รู้จัก

```php
'actions' => [
    ['type' => 'notification', 'level' => 'success', 'message' => 'Saved successfully'],
    ['type' => 'redirect', 'url' => '/inventory', 'delay' => 1000],
    ['type' => 'redirect', 'url' => 'reload', 'target' => 'table'],
    ['type' => 'modal', 'action' => 'close']
]
```
ส่วนใหญ่ใช้ `redirectResponse()` / `formErrorResponse()` แทนได้เลย ไม่ต้องเขียน `actions` เอง

---

## 5) CSS

1. **สำรวจก่อนเขียน** คลาสที่มีให้แล้วใน `Now/css/`
   `card-groups` `content-body` `form-group` `form-control` `tablebody` `table border fullwidth`
   `btn` `btn-primary|secondary|success|warning|danger|info` `submit` `center` `right` `nowrap`
   `width25|33|50|60|75|80` `block3` `large6` `stat-card` `stat-header` `stat-value` `switch`
   `comment` `userinfo` `usericon` `two_line` `icon-*`
2. เพิ่ม CSS เท่าที่จำเป็นจริง ใส่ใน `modules/<module>/styles.css` (หรือ `templates/<module>/styles.css`)
3. **ตั้ง prefix ตามชื่อโมดูล** — `.inventory-barcode`, `.borrow-status` — กันชนกับ core/โมดูลอื่น
4. ใช้ CSS variable ของธีมแทนค่าคงที่ เช่น `var(--radius-sm, 4px)`, `var(--space-2, 8px)`

---

## 6) ภาษา (i18n)

- ในเทมเพลต: `<h1 data-i18n>{LNG_List of} {LNG_Inventory}</h1>`
- ใน PHP: `Language::get('Saved successfully')`, `Language::trans('{LNG_A} & {LNG_B}')`,
  `Language::replace('This :name already exist', [':name' => Language::get('Transaction No.')])`
- ชุดค่าคงที่แบบ array อยู่ใน `language/th.php` / `en.php` เช่น
  ```php
  'BORROW_STATUS' => [0 => 'รอตรวจสอบ', 1 => 'ไม่อนุมัติ', 2 => 'อนุมัติ', 3 => 'คืนแล้ว'],
  ```
  อ่านด้วย `Language::get('BORROW_STATUS')` หรือรายตัว `Language::get('BORROW_STATUS', null, 2)`
- **ทุกข้อความที่เพิ่มใหม่ต้องเติมลง `language/th.php` และ `language/th.json`**
  (`.php` คือแฟ้มที่ runtime อ่านจริง · `.json` เป็นแหล่งนำเข้าตาราง `language` ที่หน้าแก้ภาษาใช้)

### 6.1 แฟ้มคำแปลของโมดูล — `modules/<m>/install/language.php`

**คำแปลเป็นงานตอนออกแบบ ไม่ใช่ตอนผู้ใช้อัปเกรด**
แฟ้ม `language/th.php` ของรุ่นที่ปล่อยออกไปต้องครบ 100% อยู่แล้ว เพราะเราทดสอบก่อนปล่อยเสมอ
→ **ตัวปรับรุ่นไม่ต้องยุ่งกับแฟ้มภาษาเลย** สิ่งเดียวที่ `install/upgrade2.php` ทำคือ
`include install/language.php` เพื่อนำแฟ้มที่ครบแล้วเข้าตาราง `language` ให้หน้าแก้ภาษาใช้

โมดูลวางคำแปลของตัวเองไว้ที่ `modules/<m>/install/language.php` เพื่อให้โมดูล
**พกคำศัพท์ของตัวเองติดไปด้วยตอนถูกคัดลอกไปโปรเจ็คใหม่**

```php
return [
    'th' => [
        'Out of stock' => 'สินค้าหมด',
        'Brought forward' => 'ยอดยกมา',
    ]
];
```

**ห้ามใส่** `MONTH_LONG` / `MONTH_SHORT` / `YEAR_OFFSET` / `CURRENCY_UNITS` — สี่ตัวนั้น
เป็นค่าคงที่ของแฟ้มภาษาแกน (สองตัวแรกเป็นอาเรย์) โมดูลเป็นแค่ผู้ใช้ ไม่ใช่เจ้าของ

### 6.2 ตรวจให้ครบก่อนปล่อยรุ่น — `install/cli-language.php`

```bash
php install/cli-language.php          # รายงานคีย์ที่โค้ดเรียกแต่แฟ้มภาษายังไม่มี
php install/cli-language.php --fill   # เติมคีย์ที่ขาดจากแฟ้มคำแปลของโมดูล
```

เครื่องมือนี้เป็นของ **ฝั่งเรา** ไม่ได้ทำงานบนไซต์ผู้ใช้
ต้องรันให้ขึ้น `[ok] ไม่มีคีย์ที่ขาด` **ก่อนปล่อยรุ่นทุกครั้ง** — ถ้าปล่อยทั้งที่ขาด
ผู้ใช้จะเห็นข้อความภาษาอังกฤษโผล่ปนกลางหน้าจอโดยไม่มีข้อผิดพลาดให้เห็น
(`Language::get()` คืน *ตัวคีย์* เมื่อหาคำแปลไม่เจอ และตัวคีย์เป็นภาษาอังกฤษ)

> ตอนที่ `repair` รับ `modules/export` รุ่นใหม่ไป เครื่องมือนี้จับได้ว่าขาด 23 คีย์
> ซึ่งจะทำให้ใบรับซ่อมที่พิมพ์ออกมามีคำว่า *Brought forward* / *Grand total* ปนอยู่

> ⚠️ ตัวเติมของเครื่องมือ **แทรกต่อท้ายก่อน `);` ไม่เขียนแฟ้มใหม่ทั้งแฟ้ม**
> เพราะของเดิมมีค่าที่ต้อง escape (`"Don't have an account?"`) และค่าที่เป็นอาเรย์
> การอ่านมาแล้วเขียนกลับทั้งชุดเคยทำแฟ้มภาษาพังมาแล้ว — **แฟ้มภาษาพัง = ทั้งเว็บล่ม**

---

## 7) โครงหน้าเว็บมาตรฐาน

```html
<aside data-component="sidebar"></aside>
<div class="main-content">
  <div>
    <header data-component="topbar"></header>
    <main class="content">
      <div class="card-groups">
        <header>
          <div><h1 class="icon-product" data-i18n>{LNG_…}</h1><p data-i18n>{LNG_…}</p></div>
          <div><a class="btn btn-primary icon-new" href="/inventory-edit?id=0" data-i18n>{LNG_Add}</a></div>
        </header>
        <div class="content-body"> … เนื้อหา … </div>
      </div>
    </main>
  </div>
  <footer class="footer"><p class="center">&copy; 2026 …</p></footer>
</div>
```

---

## 8) ฐานข้อมูล + ตัวติดตั้ง (สำคัญมาก)

**ทุกครั้งที่แก้ schema ต้องแก้ 2 ที่เสมอ** มิฉะนั้นจะได้ระบบที่ "ติดตั้งใหม่ได้ แต่ปรับรุ่นพัง"
(หรือกลับกัน) — คู่นั้นคือ *นิยามตาราง* กับ *ตัวปรับรุ่น*

### 8.0 ⚠️ ไฟล์ไหนเก็บอะไร — กติกาเดียวใช้ทุกโปรเจ็ค

| ไฟล์ | เก็บอะไร | ใครเป็นเจ้าของ |
|---|---|---|
| `install/core.sql` | ตารางแกนของ Gcms (`user`, `category`, `logs`, `language`, …) | เหมือนกันทุกโปรเจ็ค |
| `modules/<m>/install/database.sql` | **ตารางที่โมดูล `<m>` เป็นเจ้าของ + ข้อมูลตั้งต้นของมัน** | โมดูล |
| `install/database.sql` | เฉพาะตารางที่ **ไม่มีโมดูลไหนเป็นเจ้าของ** | โปรเจ็ค |

คู่ของมันฝั่งตัวปรับรุ่น แบ่งตรงกันตัวต่อตัว

| ไฟล์ | ปรับรุ่นอะไร |
|---|---|
| `install/upgrade_core.php` | ตารางแกน **แล้วเรียก `upgrade.php` ของทุกโมดูลให้เอง** |
| `modules/<m>/install/upgrade.php` | ตารางของโมดูล `<m>` |
| `install/upgrade2.php` | เฉพาะของโปรเจ็ค + คุมลำดับการทำงานทั้งหมด |

**กฎ 3 ข้อที่พลาดแล้วเจ็บ**

1. **ห้ามประกาศตารางเดียวกันสองที่** — `schemaFiles()` อ่านทุกไฟล์ ติดตั้งใหม่จะล้มด้วย
   *"Table already exists"* และนิยามสองชุดจะค่อย ๆ ต่างกันจนไซต์ที่อัปเกรดคนละเส้นทาง
   ได้สคีมาไม่เหมือนกัน
2. **`schemaFiles()` รัน `install/database.sql` ของโปรเจ็ค _ก่อน_ ไฟล์ของโมดูล**
   → `INSERT` ที่ชี้ไปยังตารางของโมดูล **ต้องอยู่ในไฟล์ของโมดูล** ไม่งั้นวิ่งไปหาตาราง
   ที่ยังไม่ถูกสร้าง แล้วการติดตั้งใหม่ล้มทันที
3. **ในตัวปรับรุ่นของโมดูล ใช้ `ensureTable($db, $prefix, $table)`** อย่าเขียน
   `CREATE TABLE` ซ้ำลงไป — `ensureTable` อ่านนิยามจาก `database.sql` ของโมดูลเอง
   จึงมีนิยามชุดเดียวเสมอ

> ผลที่ได้คือ **"ถอดโมดูลออก = ไม่มีตารางของมัน · วางโมดูลลงไป = ได้ตารางครบ"**
> โดยไม่ต้องไปแก้ `install/database.sql` ของทุกโปรเจ็คที่รับโมดูลนั้นไป

### 8.1 `install/database.sql` — สำหรับติดตั้งใหม่
- ชื่อตารางใช้ placeholder `{prefix}_<module>_<name>`
- ใส่ PRIMARY KEY / UNIQUE / INDEX / AUTO_INCREMENT **ไว้ใน `CREATE TABLE` เลย**
- `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
- ข้อมูลตั้งต้น (หมวดหมู่ ฯลฯ) ใส่เป็น `INSERT INTO` ต่อท้าย

```sql
CREATE TABLE `{prefix}_borrow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `borrow_no` varchar(20) NOT NULL,
  `borrower_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `borrow_no` (`borrow_no`),
  KEY `idx_borrower` (`borrower_id`,`borrow_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 8.2 `install/upgrade2.php` — สำหรับปรับรุ่นของเดิม

เขียนเป็นบล็อกต่อ 1 ตาราง วางไว้ก่อนคอมเมนต์ `// บันทึก settings/config.php`
**ต้อง idempotent** (รันซ้ำได้โดยไม่พัง) และผลลัพธ์ต้องเหมือน `database.sql` เป๊ะ

```php
// =========================================================
// borrow
// =========================================================
$table_borrow = $db_config['prefix'].'_borrow';
if (!$db->tableExists($table_borrow)) {
    $db->query("CREATE TABLE IF NOT EXISTS `$table_borrow` ( … )");
    $content[] = '<li class="correct">borrow: สร้างตารางใหม่</li>';
} else {
    if (!$db->indexExists($table_borrow, 'PRIMARY')) { … }
    if (!isAutoIncrement($db, $table_borrow, 'id')) { … }
    if (!$db->isColumnType($table_borrow, 'x', 'varchar(10)')) { … }
    if (convertToUtf8mb4($db, $table_borrow)) { … }
    $content[] = '<li class="correct">borrow อัปเกรดสำเร็จ</li>';
}
```

**ตัวช่วยที่ใช้ได้**

| ฟังก์ชัน | ที่มา | ใช้ทำอะไร |
|---|---|---|
| `$db->tableExists($t)` `fieldExists($t,$f)` `indexExists($t,$i)` `isColumnType($t,$f,'varchar(10)')` | `install/db.php` | ตรวจก่อนแก้ |
| `$db->query($sql)` `customQuery($sql)` | `install/db.php` | รัน SQL |
| `isAutoIncrement($db,$t,$col)` | นิยามท้าย `upgrade2.php` | เช็ค AUTO_INCREMENT |
| `columnInfo($db,$t,$col)` | นิยามท้าย `upgrade2.php` | อ่าน `Default`/`Null`/`Type` |
| `convertToUtf8mb4($db,$t)` | นิยามท้าย `upgrade2.php` | แปลง charset |

> `isColumnType()` เทียบ **ชนิดคอลัมน์อย่างเดียว** ไม่ดู `DEFAULT`
> ถ้าต้องแก้ค่า default ให้ใช้ `columnInfo()` แล้วเทียบ `->Default` เอง

**เปลี่ยนชื่อคอลัมน์ ต้องย้ายข้อมูลด้วย** (ตัวอย่าง `inuse` → `is_active`)
```php
if (!$db->fieldExists($table_inventory, 'is_active')) {
    $db->query("ALTER TABLE `$t` ADD `is_active` TINYINT(1) NULL");
    if ($db->fieldExists($t, 'inuse')) {
        $db->query("UPDATE `$t` SET `is_active` = `inuse`");
    } else {
        $db->query("UPDATE `$t` SET `is_active` = 1");
    }
    $db->query("ALTER TABLE `$t` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1");
}
if ($db->fieldExists($t, 'inuse')) {
    $db->query("ALTER TABLE `$t` DROP COLUMN `inuse`");
}
```

### 8.3 ค่ากำหนดของโมดูล (config)

แก้ **3 ที่**
1. `Gcms/Config.php` — ประกาศ property + ค่าปริยาย (กัน notice ตอนยังไม่ได้ตั้งค่า)
2. `install/settings/config.php` — ค่าเริ่มต้นของการติดตั้งใหม่
3. `install/upgrade2.php` — เติมให้ระบบเดิมที่ยังไม่มีคีย์นี้
   ```php
   if (!isset($config['borrow_prefix'])) { $config['borrow_prefix'] = 'B%Y%M-'; }
   ```

หน้าตั้งค่าของโมดูลอ่าน/เขียนผ่าน `\Gcms\Config`
```php
$config = Config::load(ROOT_PATH.'settings/config.php');
$config->borrow_prefix = $request->post('borrow_prefix')->topic();
Config::save($config, ROOT_PATH.'settings/config.php');
```

### 8.4 เลขที่เอกสารอัตโนมัติ

```php
$no = \Index\Number\Model::get($id, self::$cfg->borrow_no, 'borrow', 'borrow_no', self::$cfg->borrow_prefix);
//                          ^id  ^รูปแบบ %04d          ^ตาราง  ^คอลัมน์      ^prefix (รองรับ %Y %M)
```
ต้องมีตาราง `{prefix}_number (type, prefix, auto_increment, updated_at)`

---

## 9) Checklist: เพิ่มโมดูลใหม่

- [ ] `modules/<m>/install/database.sql` — ตาราง `{prefix}_<module>_*` พร้อม index/PK ครบ
      **ประกาศที่นี่ที่เดียว** ห้ามซ้ำใน `install/database.sql` ของโปรเจ็ค
- [ ] `modules/<m>/install/upgrade.php` — บล็อกปรับรุ่นของทุกตารางที่เพิ่ม/แก้ (idempotent)
- [ ] `modules/<m>/install/language.php` — คำแปลของโมดูล (ดูข้อ 6.1) มิฉะนั้นหน้าจอจะเป็นอังกฤษปนไทยเงียบ ๆ
- [ ] `Gcms/Config.php` + `install/settings/config.php` — ค่ากำหนดของโมดูล (ถ้ามี)
- [ ] `modules/<m>/controllers/init.php` — `initMenus` + `initPermission`
- [ ] `controllers/*.php` + `models/*.php` — ตาราง (`\Gcms\Table`) / ฟอร์ม (`\Gcms\Api`)
- [ ] `templates/<m>/*.html` — เลือกแพทเทิร์นตามข้อ 4, ใช้คลาสเดิมตามข้อ 5
- [ ] `modules/<m>/admin.js` — `RouterManager.register(...)` + formatter
- [ ] ตรวจ: รัน `php install/cli-testdb.php` ให้ผ่านครบ 8 ชุด (F3 คือชุดที่พิสูจน์ตัวปรับรุ่น)
- [ ] ตรวจ: `php -l` ทุกไฟล์, `node --check` ทุก JS, JSON ใน `data-row-actions` parse ผ่าน
- [ ] ตรวจ: ทุก endpoint มี auth + permission + CSRF, ทุกตารางมี `$allowedSortColumns`
- [ ] ตรวจ: ชื่อฟิลด์ที่เทมเพลตใช้ มีอยู่จริงใน response

## 10) Checklist: ถอดโมดูล

- [ ] ลบ `modules/<module>/` และ `templates/<module>/`
- [ ] (ถ้าต้องการ) drop ตาราง `{prefix}_<module>_*` และลบคีย์ config ของโมดูล
> เมนู สิทธิ์ route JS CSS หายเองทั้งหมด เพราะมาจากในโฟลเดอร์โมดูลล้วน ๆ

---

## 11) Naming conventions

| สิ่งของ | รูปแบบ | ตัวอย่าง |
|---|---|---|
| โฟลเดอร์โมดูล | lower‑case | `modules/inventory/` |
| namespace | `Ucfirst(module)\Ucfirst(file)` | `Inventory\Product` |
| ไฟล์ controller/model | ตัวพิมพ์เล็ก ตรงกับ namespace ส่วนที่ 2 | `controllers/product.php` |
| API URL | `api/<module>/<controller>/<method>` | `api/inventory/product/save` |
| ตาราง DB | `{prefix}_<module>_<name>` | `{prefix}_inventory_items` |
| สิทธิ์ | `can_<verb>_<module>` | `can_manage_inventory`, `can_approve_borrow` |
| route | `/<module>` `/<module>-<page>` | `/inventory-edit`, `/borrow-myborrow` |
| template | `templates/<module>/<page>.html` | `templates/inventory/product.html` |
| คลาส CSS | `<module>-<name>` | `.inventory-barcode`, `.borrow-status` |
| คีย์ config | `<module>_<name>` | `inventory_w`, `borrow_prefix` |

---

## 12) วิธีทดสอบโดยไม่ต้องเปิดเบราว์เซอร์

สร้างสคริปต์ CLI ที่ boot เฟรมเวิร์กแล้วเรียก Controller ตรง ๆ

```php
<?php
chdir('/path/to/project');
$_SERVER['HTTP_HOST']='localhost'; $_SERVER['REQUEST_URI']='/api';
$_SERVER['SCRIPT_NAME']='/api.php'; $_SERVER['REQUEST_METHOD']='GET';
session_start();
include 'load.php';
Kotchasan::createWebApplication('Gcms\Config');
$cfg = \Kotchasan\Config::create();

// ปลอมการล็อกอินด้วย JWT และ CSRF token
$jwt  = \Kotchasan\Jwt::encode(['sub'=>1,'exp'=>time()+3600], $cfg->jwt_secret);
$csrf = bin2hex(random_bytes(32));
$_SESSION[$csrf] = ['times'=>0,'expired'=>time()+3600,'created'=>time()];

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$jwt;
$_SERVER['HTTP_X_CSRF_TOKEN']  = $csrf;
$_SERVER['REQUEST_METHOD'] = 'POST';
$request = (new \Kotchasan\Http\Request())->withParsedBody(['id'=>0, 'topic'=>'ทดสอบ']);

$c = new \Inventory\Product\Controller();
echo (string) $c->save($request)->getBody(), "\n";
```

ใช้ตรวจได้ครบทั้ง query ที่ generate, สิทธิ์, validation, และรูปร่าง response
(ดู SQL ที่สร้างจริงด้วย `$query->toSql()`)

---

**อ้างอิงโค้ดจริง**
`modules/inventory/*` · `modules/borrow/*` · `modules/repair/*` (โปรเจกต์ inventory) ·
`Gcms/Table.php` · `Gcms/Api.php` · `Gcms/Controller.php` · `Gcms/Category.php` ·
`Kotchasan/ApiController.php` · `Kotchasan/QueryBuilder/QueryBuilder.php` · `Kotchasan/DB.php` ·
`Now/js/RouterManager.js` · `Now/js/TableManager.js` · `Now/js/FormManager.js` ·
`Now/js/LineItemsManager.js` · `Now/js/TextElementFactory.js` · `Now/js/TemplateManager.js` ·
`Now/js/ResponseHandler.js` · `Now/js/Modal.js`
