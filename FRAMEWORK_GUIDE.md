# Now.js Admin Framework - ส่วนสำคัญและวิธีการใช้งาน

## 📋 สารบัญ
1. [ภาพรวมโปรเจค](#ภาพรวมโปรเจค)
2. [สถาปัตยกรรมหลัก](#สถาปัตยกรรมหลัก)
3. [รูปแบบการออกแบบ (Design Patterns)](#รูปแบบการออกแบบ)
4. [วิธีการสร้าง UI](#วิธีการสร้าง-ui)
5. [API Endpoints](#api-endpoints)
6. [ตัวอย่างการใช้งาน](#ตัวอย่างการใช้งาน)
7. [Best Practices](#best-practices)

---

## ภาพรวมโปรเจค

### Now.js Framework คืออะไร?
**Now.js** คือ JavaScript Framework แบบเบา (Lightweight) ที่ออกแบบมาสำหรับสร้าง Admin Systems และ Web Applications ที่มีความซับซ้อน

**เป้าหมายหลัก:**
- ❌ ลด JavaScript code ให้น้อยที่สุด (use HTML + data attributes)
- ✅ ใช้ HTML เป็นหลัก (Declarative approach)
- ✅ Auto-binding ระหว่าง HTML กับ JavaScript
- ✅ Built-in Authentication & Routing
- ✅ Reactive State Management

### โครงสร้างโปรเจค
```
adminframework/
├── modules/                  # ฟีเจอร์ต่าง ๆ (users, profiles, etc.)
│   └── index/
│       ├── controllers/      # API Endpoints
│       └── models/           # Business Logic & Database
├── templates/               # HTML Templates
│   ├── users.html           # ตัวอย่าง: Table Pattern
│   ├── profile.html         # ตัวอย่าง: Form Pattern
│   └── settings/
│       ├── categories.html  # ตัวอย่าง: Editable Table
│       ├── category.html    # ตัวอย่าง: Form with Table
│       ├── languages.html   # ตัวอย่าง: Table + Filter
│       └── language.html    # ตัวอย่าง: Complex Form
├── Now/                     # Framework Core
│   ├── Now.js              # Main framework file
│   ├── js/                 # Component managers
│   │   ├── TableManager.js
│   │   ├── FormManager.js
│   │   ├── ApiService.js
│   │   └── ... (30+ managers)
│   └── css/                # Styling
├── Gcms/                   # System Controllers (AI, Emails, etc.)
└── Kotchasan/              # Base Framework Classes
```

---

## สถาปัตยกรรมหลัก

### 1. Data Flow Architecture
```
┌──────────────────┐
│   HTML Template  │  ← Define structure & data-attributes
│  (templates/)    │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│  JavaScript Mgrs │  ← Auto-bind & handle interactions
│  (FormManager,   │  ← Send API requests
│   TableManager)  │
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│  API Endpoints   │  ← api/index/{module}/{action}
│  (controllers/)  │  ← Validate & Process
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│   Models         │  ← Query Database
│   (models/)      │  ← Business Logic
└────────┬─────────┘
         │
         ↓
┌──────────────────┐
│    Database      │  ← MySQL/PostgreSQL/SQLite/MSSQL
└──────────────────┘
```

### 2. Component Architecture

#### Core Components (in `/Now/js/`)
| Component | ไฟล์ | ใช้สำหรับ |
|-----------|------|----------|
| **TableManager** | TableManager.js | เรนเดอร์ & ควบคุมตาราง |
| **FormManager** | FormManager.js | จัดการ Form submissions & validation |
| **ReactiveManager** | ReactiveManager.js | State & UI binding (data-attr) |
| **ApiService** | ApiService.js | HTTP requests & JWT auth |
| **ElementFactory** | *ElementFactory.js (11 types) | สร้าง form elements |
| **SyncManager** | SyncManager.js | Real-time data syncing |
| **EventManager** | EventManager.js | Event handling & delegation |

### 3. MVC Pattern Implementation

```php
// CONTROLLER: modules/index/controllers/users.php
class Controller extends \Gcms\Table {
    protected $allowedSortColumns = ['id', 'name', 'department'];
    
    protected function getCustomParams(Request $request, $login): array {
        return ['status' => $request->get('status')->number()];
    }
    
    protected function toDataTable($params, $login = null) {
        // Build query with filters, search, sort
    }
}

// MODEL: modules/index/models/users.php
class Model extends \Kotchasan\Model {
    public static function toDataTable($params) {
        // Execute query
        return static::createQuery()
            ->select('id', 'name', 'status')
            ->from('user')
            ->where($where)
            ->groupBy('U.id');
    }
}

// VIEW: templates/users.html
<table class="table" 
       data-table="users" 
       data-source="api/index/users"
       data-default-sort="created_at desc">
```

---

## รูปแบบการออกแบบ (Design Patterns)

### กฎการแยกไฟล์ HTML (HTML Separation Rule)

**กฎของระบบ:** แยก Table และ Form ออกเป็นคนละไฟล์เสมอ — ห้ามเขียน `<table>` กับ
`<form>` ที่มี fields จริงไว้ในหน้าเดียวกัน

- Pattern 1 (Simple Table), Pattern 3 (Table + Modal), Pattern 5 (Table with
  Filters) ล้วนแยกไฟล์: ตารางอยู่ไฟล์หนึ่ง (เช่น `devices.html`), ฟอร์ม
  create/edit อยู่อีกไฟล์หนึ่ง (เช่น `deviceedit.html`, โหลดเข้า Modal)
- **ข้อยกเว้นเดียว:** Pattern 2 (Editable Table / `data-editable-rows="true"`)
  ที่ `<form>` ต้องครอบ `<table>` ในไฟล์เดียวกันจริงๆ เพราะเป็นการแก้ไขทุก
  แถวพร้อมกันแล้ว submit เป็นก้อนเดียว — ใช้ pattern นี้**เท่าที่จำเป็นเท่านั้น**
  (ข้อมูลน้อย คอลัมน์ไม่เยอะ) และใช้เพราะมันทำให้ user ใช้งานง่ายขึ้นเท่านั้น
  ไม่ใช่ default choice

---

### Pattern 1: Simple Table (ตารางที่ไม่มี Edit)

**เมื่อใช้:** ข้อมูลอ่านอย่างเดียว, ไม่มีการแก้ไขโดยตรง

**ตัวอย่าง:** `templates/users.html`

```html
<table class="table border fullwidth" 
       data-table="users"                      <!-- ชื่อตาราง -->
       data-source="api/index/users"           <!-- Endpoint เรียกข้อมูล -->
       data-default-sort="created_at desc"    <!-- Default sorting -->
       data-page-size="25"                     <!-- จำนวนแถวต่อหน้า -->
       data-search-columns="name,phone"        <!-- Columns ที่ค้นหา -->
       data-show-checkbox="true">              <!-- Show checkbox -->
  
  <thead>
    <tr>
      <th data-field="id" data-sort="id">ID</th>
      <th data-field="name" 
          data-sort="name"
          data-template="<span>${name}</span>">Name</th>
      <th data-field="active" 
          data-cell-class="center" 
          data-sort="active"
          data-template="<button data-action='active'></button>">Active</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
```

**สำคัญ:**
- `data-field`: ชื่อ field ที่ตรงกับ API response
- `data-sort`: ระบุ sort column
- `data-template`: HTML template สำหรับ cell content
- `data-format`: Format เช่น "datetime", "lookup" เป็นต้น

---

### Pattern 2: Editable Table (ตารางที่แก้ไขได้)

**เมื่อใช้:** ข้อมูลน้อย, คอลัมน์ไม่เยอะ, สามารถแก้ไขแล้ว Save ได้ทันที

**ตัวอย่าง:** `templates/settings/categories.html`

```html
<form data-form="settings" 
      action="api/index/categories/save" 
      method="post" 
      data-ajax-submit="true"
      data-load-api="api/index/categories/get"
      data-load-query-params="true">
  
  <fieldset class="grid">
    <div class="tablebody">
      <table class="table border fullwidth" 
             data-table="indexCategories" 
             data-editable-rows="true"           <!-- ✨ Key: Enable editing -->
             data-attr="data:options"             <!-- Link to form data -->
             data-dynamic-columns="true">        <!-- Auto-generate headers -->
        
        <tbody></tbody>
      </table>
    </div>
  </fieldset>
  
  <fieldset class="submit">
    <button type="submit" class="btn btn-primary">Save</button>
    <input type="hidden" name="type" data-attr="value:type">
  </fieldset>
</form>
```

**API Response Format:**
```json
{
  "success": true,
  "message": "Data loaded",
  "data": {
    "type": "department",
    "title": "Department",
    "options": {
      "columns": [
        {"field": "id", "label": "ID", "cellElement": "text", "size": 5},
        {"field": "th", "label": "Thai", "cellElement": "text"}
      ],
      "data": [
        {"id": "1", "th": "Sales"},
        {"id": "2", "th": "IT"}
      ]
    }
  }
}
```

**สำคัญ:**
- `data-editable-rows="true"`: เปิด Inline editing mode
- `data-dynamic-columns="true"`: Headers มาจาก API (columns array)
- `data-attr="data:options"`: Bind form data.options เข้า table

---

### Pattern 3: Table + Modal (ตารางที่มี Modal สำหรับ Create/Edit)

**เมื่อใช้:** ข้อมูลเยอะ, complex fields, modal reusable จากหลายจุด

**ตัวอย่างจริง:** `templates/documents/tag.html` + `templates/documents/tagedit.html`
(ตัวอย่างประกอบ — ไม่มีไฟล์นี้จริงในโปรเจกต์นี้; ตัวอย่างที่มีจริงและ verify แล้ว
คือ `templates/iot/devices.html` + `templates/iot/deviceedit.html`, ดูหัวข้อ
"วิธีเปิด Modal" ด้านล่าง ซึ่งเติมช่องว่างที่ตัวอย่าง tag/tagedit ไม่ได้อธิบายไว้:
**อะไรทำให้ Modal เปิดขึ้นมาตอนกด Edit/Add**)

#### ไฟล์ 1: ตารางหลัก (`tag.html`)

```html
<!-- กำหนด data-table="tags" — ID นี้สำคัญมาก ต้องใช้ให้ตรงกับ PHP action -->
<table class="table border fullwidth"
       data-table="tags"
       data-source="api/documents/tag"
       data-default-sort="name asc"
       data-page-size="25"
       data-search-columns="name"
       data-action-url="api/documents/tag/action"
       data-row-actions='{
         "edit":   {"title":"Edit",   "className":"btn btn-success icon-edit",
                    "modal": {"template":"documents/tagedit.html", "title":"Edit Tag"}},
         "delete": {"title":"Delete", "className":"btn btn-danger icon-delete",
                    "confirm":"Delete this tag?"}
       }'>
  <thead>
    <tr>
      <th data-field="id"           data-sort="id"   data-i18n>ID</th>
      <th data-field="name"         data-sort="name"
          data-template="<span class='tag-chip' style='--color:#${color_hex}'>${name}</span>"
          data-i18n>Name</th>
      <th data-field="usage_count"  class="center" data-cell-class="center" data-i18n>Usage</th>
      <th data-field="creator_name" data-i18n>Created By</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Toolbar "Add" button — NOT a table row, so data-row-actions doesn't apply.
     Hits the exact same action=edit endpoint with id=0. See "วิธีเปิด Modal" below
     for why this is the correct/only way to trigger the same modal from outside a row. -->
<button class="btn btn-primary icon-new"
        data-action="click.prevent:requestApi"
        data-api-url="api/documents/tag/action"
        data-api-method="post"
        data-param-action="edit"
        data-param-id="0"
        data-i18n>Add Tag</button>
```

#### ไฟล์ 2: Form ใน Modal (`tagedit.html`)

```html
<!-- data-form ไม่จำเป็นต้องตรงกับ data-table แต่ควรตั้งชื่อสื่อความหมาย -->
<form data-form="tags"
      data-validate="true"
      action="api/documents/tagedit/save"
      method="post"
      data-ajax-submit="true">
  <fieldset>
    <div>
      <label for="name" data-i18n>Name</label>
      <span class="form-control icon-tag">
        <input type="text" id="name" name="name" maxlength="50" required
               data-attr="value:name" autocomplete="off">
      </span>
    </div>
    <div>
      <label for="color_hex" data-i18n>Color</label>
      <span class="form-control">
        <!-- data-attr แปลง color_hex (ไม่มี #) → value จริง (มี #) -->
        <input type="color" id="color_hex" name="color_hex"
               data-attr="value:'#' + color_hex">
      </span>
    </div>
  </fieldset>
  <fieldset class="submit right">
    <button type="submit" class="btn btn-primary icon-save" data-i18n>Save</button>
    <input type="hidden" name="id" data-attr="value:id">
  </fieldset>
</form>
```

#### วิธีเปิด Modal (การ trigger — verify แล้วจาก `Now/js/EventSystemManager.js` +
`Now/js/ResponseHandler.js`, ต้นฉบับเดิมของหัวข้อนี้ไม่ได้อธิบายจุดนี้ไว้)

มี **ปุ่ม 2 แบบที่เปิด modal เดียวกัน** และต้องเขียนต่างกัน เพราะ trigger คนละกลไก:

1. **ปุ่มในแถวตาราง (row-action, เช่น "Edit")** — render จาก `data-row-actions`
   JSON เท่านั้น TableManager แนบ click listener ของตัวเองให้เสมอ
   (`_executeRowAction` → POST ไปที่ `data-action-url`) — ใส่ `data-action="click.prevent:..."`
   ใน `attrs` ของ row-action **ไม่มีผล เพราะ listener ของ TableManager ทับอยู่แล้ว**
   ให้ตั้งค่า template/title ของ modal ผ่าน key `"modal"` ใน JSON โดยตรงแทน (ดูตัวอย่าง
   `"edit": {..., "modal": {"template":..., "title":...}}` ด้านบน)

2. **ปุ่มนอกตาราง (toolbar, เช่น "Add")** — ไม่ใช่แถวตาราง ไม่มี TableManager มาแนบ
   listener ให้ ใช้กลไกทั่วไป `data-action="click.prevent:requestApi"` แทน (ดู
   "Button Actions (API Calls)" ในหัวข้อ Advanced Features) — **ต้องยิงไปที่ endpoint
   เดียวกับปุ่ม Edit** (`api/documents/tag/action`) พร้อม `data-param-action="edit"
   data-param-id="0"` เพื่อ reuse handler ตัวเดียวกัน — id=0 แปลว่า "ยังไม่มี record"

ทั้งสองทางจบที่ PHP handler เดียวกัน (`handleEditAction`), server ตอบกลับ
`actions: [{"type":"modal","action":"open","template":...,"title":...}]` เสมอ —
กรณี row-action, `modal` config ฝั่ง client (จาก step 1) จะ**ชนะ**ค่า template/title
ที่ server ส่งมาโดยอัตโนมัติ (`ResponseHandler.handleModalShow()` ให้ priority กับ
`context.modalConfig` ก่อน) กรณีปุ่ม toolbar ที่ไม่มี client config, ค่าจาก server
จะถูกใช้แทน — เขียน response ให้ใส่ template/title มาด้วยเสมอ จะได้ใช้ได้ทั้งสองทาง
โดยไม่ต้องมี handler แยก:

```php
// controllers/tag.php — action=edit (ทั้งปุ่ม toolbar id=0 และ row-action id=X)
protected function handleEditAction(Request $request, $login)
{
    $id = $request->request('id')->toInt();

    if ($id > 0) {
        $tag = Model::find($id);
        if (!$tag) {
            return $this->errorResponse('Tag not found', 404);
        }
        $data = ['id' => $tag->id, 'name' => $tag->name, 'color_hex' => $tag->color_hex];
        $title = 'Edit Tag';
    } else {
        $data = ['id' => 0, 'name' => '', 'color_hex' => ''];
        $title = 'Add Tag';
    }

    $data['actions'] = [
        ['type' => 'modal', 'action' => 'open', 'template' => 'documents/tagedit.html', 'title' => $title]
    ];

    return $this->successResponse($data, 'OK');
}
```

`requestApi` ไม่ต้องเขียน JS เพิ่มเลย — `window.httpAction` (ใน `Now/js/HttpClient.js`)
รัน `ResponseHandler.process()` ให้อัตโนมัติทุก response อยู่แล้ว

#### ไฟล์ 3: PHP Controller (`controllers/tagedit.php`) — ปิด Modal หลัง Save

```php
$actions = [
    // 1. แสดง notification ก่อน
    [
        'type'    => 'notification',
        'level'   => 'success',
        'message' => $message,
    ],
    // 2. Reload เฉพาะตาราง — target ต้องตรงกับ data-table="tags" ในหน้าหลัก
    //    ResponseHandler จะเรียก TableManager.loadTableData('tags', {force: true})
    //    ถ้าไม่พบตาราง (เช่น เปิด form เดี่ยว) จะ fallback เป็น window.location.reload()
    [
        'type'   => 'redirect',
        'url'    => 'reload',
        'target' => 'tags',   // ← ต้องใส่ชื่อ data-table จริง ไม่ใช่ 'table' ทั่วไป
    ],
    // 3. ปิด Modal หลังบันทึกสำเร็จ
    [
        'type'   => 'modal',
        'action' => 'close',
    ],
];
```

#### กฎสำคัญของ Pattern นี้

| สิ่งที่ต้องจำ | รายละเอียด |
|---|---|
| `target` ใน PHP | ต้องใส่ค่า `data-table` จริงๆ (เช่น `'tags'`) **ไม่ใช่** keyword `'table'` |
| ลำดับ actions | `notification` → `redirect reload` → `modal close` |
| `'table'` ใช้ไม่ได้ | `ResponseHandler.reloadTableById('table')` จะหา table ไม่เจอ → fallback โหลดทั้งหน้า |
| fallback behavior | ถ้า table ไม่ได้อยู่บนหน้า (เปิด form เดี่ยว) จะ `window.location.reload()` โดยอัตโนมัติ |
| row-action กับ `data-action` | ใช้ด้วยกันไม่ได้ — row-action ต้องตั้ง modal ผ่าน key `"modal"` เท่านั้น (ดูด้านบน) |
| ปุ่ม Add นอกตาราง | ใช้ `requestApi` ยิง endpoint เดียวกับ Edit, `id=0` |
| **`toDataTable()` ห้ามมี `orderBy()`** | `Gcms\Table::executeDataTable()` ห่อ query ของ `toDataTable()` เป็น `COUNT(*)` subquery เสมอ — ถ้ามี `orderBy()` ติดมาด้วยจะได้ SQL error ("... ASC) AS `Q` LIMIT 1") เพราะ subquery มี ORDER BY ติดมาผิดที่ ตัวจริงที่ production ใช้ (`modules/index/models/users.php`) **ไม่มี** `orderBy()` ใน `toDataTable()` เลย — การ sort ทำโดย `executeDataTable()` เองจาก `data-default-sort`/คลิกหัวคอลัมน์ ไม่ต้องเขียนเอง |

#### Flow การทำงาน

```
User คลิก Save
  → FormManager POST → api/documents/tagedit/save
  → PHP คืน actions[]
  → ResponseHandler.process(response)
      → executeAction(notification) → แสดง toast
      → executeAction(redirect: reload, target: 'tags')
          → tryHandleReload()
          → TableManager.loadTableData('tags', {force: true})  ← reload เฉพาะตาราง
      → executeAction(modal: close) → ปิด modal
```

---

### Pattern 4: Complex Form (Form ที่ไม่มีตารางหรือ fields เยอะ)

**เมื่อใช้:** Fields ซับซ้อน, หลายพื้นที่, validation เยอะ, file uploads

**ตัวอย่าง:** `templates/profile.html`

```html
<form data-form="profile" 
      data-validate="true"
      data-reset="true"
      action="api/index/profile/save" 
      method="post" 
      data-ajax-submit="true"
      data-load-api="api/index/profile/get"
      data-load-query-params="true"
      data-on-load="initProfile">           <!-- Custom JS callback -->
  
  <!-- Account Section -->
  <fieldset>
    <legend>Account Information</legend>
    <div>
      <label for="username">Username</label>
      <span class="form-control icon-user">
        <input type="text" 
               id="username" 
               name="username" 
               required 
               maxlength="50"
               data-attr="value:username"     <!-- Bind to form data.username -->
               autocomplete="off">
      </span>
    </div>
  </fieldset>
  
  <!-- Personal Section -->
  <fieldset>
    <legend>Personal Information</legend>
    <div class="form-group">
      <div class="width50">
        <label for="name">Full Name</label>
        <span class="form-control">
          <input type="text" 
                 id="name" 
                 name="name"
                 data-attr="value:name" 
                 required>
        </span>
      </div>
      <div class="width50">
        <label for="department">Department</label>
        <span class="form-control">
          <select id="department" 
                  name="department"
                  data-attr="value:metas['department'][0]"  <!-- Nested data -->
                  data-options-key="department">            <!-- Options from API -->
            <option value="">Not specified</option>
          </select>
        </span>
      </div>
    </div>
  </fieldset>
  
  <!-- File Upload -->
  <fieldset>
    <label for="avatar">Avatar</label>
    <span class="form-control">
      <input type="file" 
             id="avatar" 
             name="avatar" 
             data-files="avatar"                    <!-- File upload manager -->
             data-preview="true"                    <!-- Show preview -->
             data-allow-remove-existing="true"      <!-- Can remove old file -->
             data-action-url="api/index/profile/remove-avatar"
             accept="image/*">
    </span>
  </fieldset>
  
  <!-- Conditional Section (ใช้ Admin only) -->
  <fieldset data-if="isSuperAdmin">               <!-- Show only if isSuperAdmin -->
    <legend>Admin Only</legend>
    <select id="permission" 
            name="permission"
            data-element="tags"                    <!-- Multi-select tags -->
            data-attr="value:permission"
            data-options-key="permission">
      <option value="">Select permissions</option>
    </select>
  </fieldset>
  
  <!-- Submit -->
  <fieldset class="submit">
    <button type="submit" class="btn btn-primary">Save</button>
    <input type="hidden" name="id" data-attr="value:id">
  </fieldset>
</form>

<script>
function initProfile() {
  // Custom initialization if needed
  console.log('Profile form loaded');
}
</script>
```

**สำคัญ:**
- `data-form`: ชื่อ form (unique identifier)
- `data-validate="true"`: Enable validation
- `data-ajax-submit="true"`: Submit via AJAX (no page reload)
- `data-load-api`: API เรียกข้อมูล
- `data-load-query-params="true"`: ส่ง URL query params ไป API (เช่น ?id=1)
- `data-attr="value:fieldName"`: Bind form field กับ data object
- `data-if="condition"`: Show/hide based on condition
- `data-element="tags"`: Special element type
- `data-options-key="name"`: Get options from API response.options.name

---

### Pattern 5: Table with Filters (ตารางที่มี External Filter Form)

**เมื่อใช้:** ต้องมี Filter/Search ที่ซับซ้อน

**ตัวอย่าง:** `templates/settings/languages.html`

```html
<!-- External Filter Form -->
<form data-table-filter="languages" class="table_nav">  <!-- data-table-filter matches table -->
  <div>
    <label for="pageSize">Show</label>
    <span class="form-control">
      <select name="pageSize">
        <option value="10">10 entries</option>
        <option value="25">25 entries</option>
        <option value="50">50 entries</option>
      </select>
    </span>
  </div>
  <input type="search" name="search" placeholder="Search...">
</form>

<!-- Table (linked to filter form) -->
<table class="table border fullwidth" 
       data-table="languages"               <!-- Must match data-table-filter -->
       data-source="api/index/languages" 
       data-default-sort="id desc" 
       data-page-size="25"
       data-show-checkbox="true"
       data-actions='{"delete":"Delete"}'
       data-action-url="api/index/languages/action"
       data-action-button="Process|btn-success"
       data-row-actions='{"edit": {"label": "Edit","className": "btn btn-success"}}'
       data-dynamic-columns="true">
  
  <tbody></tbody>
</table>
```

---

## Module Routing Convention

### Frontend Route Registration (admin.js)

**Location:** `modules/{moduleName}/admin.js`

Each module must define its frontend routes in an `admin.js` file using `RouterManager.register()` inside the `router:initialized` event listener.

**Purpose of Module Structure:**
- Each module can be added/removed independently
- Route definitions stay with the module code
- styles.css can be included for module-specific styling
- Both files can be directly included in index.html via script/link tags

**Route Naming Convention:**
- Use **hyphenated single-level routes** (e.g., `/documents-search`)
- **NOT** nested paths with slashes (e.g., ❌ `/documents/search`)
- Reason: Avoids conflicts with file path resolution and HTTP routing edge cases
- Examples: `/documents`, `/document-create`, `/my-borrows`, `/locations-search`

**Pattern:**
```javascript
EventManager.on('router:initialized', () => {
    RouterManager.register('/route-name', {
        template: 'modulename/template.html',
        title: '{LNG_Page Title}',
        requireAuth: true/false
    });
    
    // Additional routes...
});
```

**Example: Booking Module (admin.js)**
```javascript
EventManager.on('router:initialized', () => {
    RouterManager.register('/rooms', {
        template: 'booking/catalog.html',
        title: '{LNG_All rooms}',
        requireAuth: true
    });

    RouterManager.register('/booking', {
        template: 'booking/booking.html',
        title: '{LNG_Book a room}',
        requireAuth: true
    });

    RouterManager.register('/my-bookings', {
        template: 'booking/my-bookings.html',
        title: '{LNG_My bookings}',
        requireAuth: true
    });
    
    // ... more routes
});
```

**Routes vs URL Params:**
- Use routes for **main navigation pages** (list, create form, search)
- Use **URL query params** for detail/edit pages (e.g., `/document?id=5`)
- This keeps route count manageable while supporting detail pages

**Module Configuration Files:**

1. **admin.js** (Required for routing modules)
   - Define all frontend routes via `RouterManager.register()`
   - Can include module-specific JavaScript initialization functions (e.g. `data-on-load` callbacks, custom cell formatters)
   - Loaded by index.html via `<script src="modules/{moduleName}/admin.js"></script>`

2. **styles.css** (Optional, for module styling)
   - Module-specific CSS not present in the main framework styles
   - Loaded by index.html via `<link rel="stylesheet" href="modules/{moduleName}/styles.css">`

### Wiring a Module into index.html

Both files are included directly in `index.html`. This is what makes a module
pluggable — adding a module is two lines, removing it is deleting those two lines.

```html
<head>
  <!-- ... framework + custom CSS ... -->
  <!-- Module CSS -->
  <link rel="stylesheet" href="modules/documents/styles.css">
</head>
<body>
  <!-- Framework Scripts (define window.EventManager, window.RouterManager) -->
  <script src="Now/dist/now.core.min.js"></script>
  <script src="Now/dist/now.table.min.js"></script>
  <script src="Now/dist/now.graph.min.js"></script>
  <!-- App Scripts -->
  <script src="js/main.js"></script>
  <!-- Module Scripts (must come AFTER now.core.min.js) -->
  <script src="modules/documents/admin.js"></script>
</body>
```

**Ordering rule:** `admin.js` must be loaded **after** `Now/dist/now.core.min.js`,
because that bundle is what exposes `window.EventManager` and `window.RouterManager`.
The module script only *registers a listener* for `router:initialized` at parse
time; the framework fires that event later (during `Now.init()` inside `js/main.js`),
so the listener is always in place before the event fires regardless of whether the
module script is before or after `js/main.js` — the only hard requirement is "after
now.core.min.js".

**Routes defined this way are equivalent to the inline `router.routes` entries in
`js/main.js`.** Core application routes live inline in `main.js`; pluggable module
routes live in the module's `admin.js`. Never add module routes to `js/main.js` —
that defeats the purpose of the modular structure.

---

## วิธีการสร้าง UI

### Step 1: สร้าง HTML Template

```html
<!-- templates/mymodule/employees.html -->
<aside data-component="sidebar"></aside>
<div class="main-content">
  <header data-component="topbar"></header>
  <main class="content">
    <div class="card-groups">
      <header>
        <h1 class="icon-users">Employees</h1>
        <a class="btn btn-primary" href="/mymodule/employee?id=0">Add Employee</a>
      </header>
      <div class="content-body">
        <div class="tablebody">
          <table class="table border fullwidth" 
                 data-table="employees"
                 data-source="api/mymodule/employees"
                 data-default-sort="id desc"
                 data-page-size="25">
            <thead>
              <tr>
                <th data-field="id" data-sort="id">ID</th>
                <th data-field="name" data-sort="name">Name</th>
                <th data-field="department">Department</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<footer class="footer">
  <p class="center">&copy; 2026</p>
</footer>
```

### Step 2: สร้าง API Controller

```php
// modules/mymodule/controllers/employees.php
<?php
namespace MyModule\Employees;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

class Controller extends \Gcms\Table
{
    protected $allowedSortColumns = ['id', 'name', 'department', 'created_at'];

    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::isSuperAdmin($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        return true;
    }

    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'department' => $request->get('department')->topic()
        ];
    }

    protected function toDataTable($params, $login = null)
    {
        $where = [];
        if ($params['department'] !== '') {
            $where[] = ['department', $params['department']];
        }

        return \MyModule\Employees\Model::toDataTable($where);
    }
}
```

### Step 3: สร้าง Model

```php
// modules/mymodule/models/employees.php
<?php
namespace MyModule\Employees;

use Kotchasan\Database\Sql;

class Model extends \Kotchasan\Model
{
    public static function toDataTable($where = [])
    {
        return static::createQuery()
            ->select(
                'E.id',
                'E.name',
                'E.department',
                'E.created_at'
            )
            ->from('employee E')
            ->where($where)
            ->orderBy('E.id DESC');
    }
}
```

### Step 4: ตั้งค่า Routing (ถ้า custom routes)

```php
// modules/mymodule/controllers/init.php
<?php
namespace MyModule\Init;

class Controller
{
    public static function initMenu(&$menus, &$languages)
    {
        // Add menu item
        $menus[] = [
            'text' => '{LNG_Employees}',
            'icon' => 'icon-users',
            'url' => '/mymodule/employees'
        ];
        return $menus;
    }
}
```

---

## API Endpoints

### Request/Response Format

**Endpoint Convention:** `api/{module}/{action}`

**Response Format (Success):**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    "id": 1,
    "name": "John",
    "email": "john@example.com"
  },
  "options": {
    "department": [
      {"value": "1", "text": "Sales"},
      {"value": "2", "text": "IT"}
    ]
  }
}
```

**Response Format (Error):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "name": "Name is required",
    "email": "Email format is invalid"
  }
}
```

### Common Endpoints Pattern

```
GET  /api/index/users                    → List users with pagination/sort/filter
GET  /api/index/users?search=john        → Search users
GET  /api/index/profile/get?id=1         → Get single user details
POST /api/index/profile/save              → Create/update user (AJAX)
POST /api/index/users/action              → Bulk actions (delete, activate, etc)
```

### Authentication

```php
// API Controller base class (/Gcms/Api.php)

// Check authorization
if (!ApiController::isSuperAdmin($login)) {
    return $this->errorResponse('Forbidden', 403);
}

// Check permission
if (!ApiController::hasPermission($login, ['can_config'])) {
    return $this->errorResponse('No permission', 403);
}

// Get current user from token
$login = $this->authenticateRequest($request);
if (!$login) {
    return $this->errorResponse('Unauthorized', 401);
}
```

---

## ตัวอย่างการใช้งาน

### Example 1: สร้าง Employee Management Module

#### 1.1 Database Schema
```sql
CREATE TABLE employee (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(100),
  department VARCHAR(50),
  salary DECIMAL(10,2),
  hire_date DATE,
  active INT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

#### 1.2 Template: `templates/mymodule/employees.html`
```html
<aside data-component="sidebar"></aside>
<div class="main-content">
  <header data-component="topbar"></header>
  <main class="content">
    <div class="card-groups">
      <header>
        <div>
          <h1 class="icon-users">Employees</h1>
          <p>Manage employee information</p>
        </div>
        <div>
          <a class="btn btn-primary icon-new" href="/mymodule/employee?id=0">Add Employee</a>
        </div>
      </header>
      <div class="content-body">
        <div class="tablebody">
          <table class="table border fullwidth" 
                 data-table="employees"
                 data-source="api/mymodule/employees"
                 data-default-sort="created_at desc"
                 data-page-size="25"
                 data-show-checkbox="true"
                 data-actions='{"delete":"Delete"}'
                 data-action-url="api/mymodule/employees/action"
                 data-action-button="Process|btn-success"
                 data-row-actions='{"edit": {"title": "Edit", "className": "btn btn-success icon-edit"}}'>
            
            <thead>
              <tr>
                <th data-field="id" data-sort="id">ID</th>
                <th data-field="name" data-sort="name">Name</th>
                <th data-field="email">Email</th>
                <th data-field="department" data-sort="department" data-filter="true" data-type="select" data-format="lookup">Department</th>
                <th data-field="salary" data-format="number">Salary</th>
                <th data-field="hire_date" data-format="datetime">Hire Date</th>
                <th data-field="active" data-cell-class="center" data-template="<button class='btn-table-action' data-action='active'></button>">Active</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
```

#### 1.3 Controller: `modules/mymodule/controllers/employees.php`
```php
<?php
namespace MyModule\Employees;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

class Controller extends \Gcms\Table
{
    protected $allowedSortColumns = ['id', 'name', 'department', 'created_at'];

    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::isSuperAdmin($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        return true;
    }

    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'department' => $request->get('department')->topic()
        ];
    }

    protected function toDataTable($params, $login = null)
    {
        $where = [];
        if ($params['department'] !== '') {
            $where[] = ['department', $params['department']];
        }

        return \MyModule\Employees\Model::toDataTable($where);
    }
}
```

#### 1.4 Model: `modules/mymodule/models/employees.php`
```php
<?php
namespace MyModule\Employees;

class Model extends \Kotchasan\Model
{
    public static function toDataTable($where = [])
    {
        return static::createQuery()
            ->select(
                'E.id',
                'E.name',
                'E.email',
                'E.department',
                'E.salary',
                'E.hire_date',
                'E.active',
                'E.created_at'
            )
            ->from('employee E')
            ->where($where)
            ->orderBy('E.created_at DESC');
    }

    public static function get($id)
    {
        return static::createQuery()
            ->from('employee')
            ->where(['id', $id])
            ->first();
    }

    public static function save($id, $data)
    {
        $db = \Kotchasan\DB::create();
        
        if ($id) {
            $db->update('employee', ['id' => $id], $data);
        } else {
            $db->insert('employee', $data);
            $id = $db->getInsertId();
        }
        
        return $id;
    }
}
```

---

### Example 2: Leave Request Form (Complex Form Pattern)

#### 2.1 Template: `templates/mymodule/leave-request.html`
```html
<aside data-component="sidebar"></aside>
<div class="main-content">
  <header data-component="topbar"></header>
  <main class="content">
    <form data-form="leaveRequest"
          data-validate="true"
          action="api/mymodule/leave/save"
          method="post"
          data-ajax-submit="true"
          data-load-api="api/mymodule/leave/get"
          data-load-query-params="true">
      
      <div class="card-groups">
        <header>
          <h1 class="icon-calendar">Leave Request</h1>
        </header>
        <div class="content-body">
          
          <fieldset>
            <legend>Request Details</legend>
            <div class="form-group">
              <div class="width50">
                <label for="start_date">Start Date</label>
                <span class="form-control icon-calendar">
                  <input type="date" 
                         id="start_date" 
                         name="start_date"
                         data-attr="value:start_date"
                         required>
                </span>
              </div>
              <div class="width50">
                <label for="end_date">End Date</label>
                <span class="form-control icon-calendar">
                  <input type="date" 
                         id="end_date" 
                         name="end_date"
                         data-attr="value:end_date"
                         required>
                </span>
              </div>
            </div>

            <div>
              <label for="leave_type">Leave Type</label>
              <span class="form-control icon-menus">
                <select id="leave_type" 
                        name="leave_type"
                        data-attr="value:leave_type"
                        data-options-key="leave_type"
                        required>
                  <option value="">Select leave type</option>
                </select>
              </span>
            </div>

            <div>
              <label for="reason">Reason</label>
              <span class="form-control icon-edit">
                <textarea rows="4" 
                          id="reason" 
                          name="reason"
                          data-text="reason"></textarea>
              </span>
            </div>
          </fieldset>

          <fieldset>
            <legend>Supporting Documents</legend>
            <div>
              <label for="attachment">Attachment (Optional)</label>
              <span class="form-control icon-file">
                <input type="file" 
                       id="attachment" 
                       name="attachment"
                       data-files="attachment"
                       data-preview="true"
                       data-action-url="api/mymodule/leave/remove-attachment"
                       accept=".pdf,.doc,.docx,.jpg,.png">
              </span>
            </div>
          </fieldset>

          <fieldset class="submit">
            <button type="submit" class="btn btn-primary icon-save">Submit</button>
            <input type="hidden" name="id" data-attr="value:id">
          </fieldset>
        </div>
      </div>
    </form>
  </main>
</div>
```

---

## Best Practices

### ✅ DO:

1. **ใช้ HTML + data-attributes เป็นหลัก**
   ```html
   <!-- ✅ Good -->
   <input type="text" name="name" data-attr="value:name" required>
   
   <!-- ❌ Avoid -->
   <input type="text" id="name">
   <script>
     document.getElementById('name').value = data.name;
   </script>
   ```

2. **ใช้ Template สำหรับ Complex markup**
   ```html
   <!-- ✅ Good -->
   <th data-field="name"
       data-template="<span class='${status}'>${name}</span>">

   <!-- ❌ Avoid writing JS to render -->
   ```

3. **ใช้ Endpoint patterns ที่สอดคล้องกัน**
   ```
   ✅ /api/index/users          (GET = list)
   ✅ /api/index/users          (POST with id = update)
   ✅ /api/index/profile/get    (GET one)
   ✅ /api/index/profile/save   (POST save)
   ```

4. **ใช้ data-options-key สำหรับ dynamic options**
   ```html
   <select data-options-key="department">
     <!-- Options จะ auto-populate จาก API response.options.department -->
   </select>
   ```

5. **Validate เฉพาะ Input, ไม่ Validate Internal Logic**
   ```php
   // ✅ Good - Validate user input
   if (empty($request->post('name')->text())) {
       $errors['name'] = 'Name is required';
   }

   // ❌ Avoid - Business logic validation at input level
   ```

### ❌ DON'T:

1. **ไม่ต้องเขียน JS เมื่อ data-* attributes ทำได้**
   ```javascript
   // ❌ Bad
   document.querySelectorAll('[name=active]').forEach(el => {
       el.addEventListener('click', () => {
           fetch('/api/users/toggle-active', {...});
       });
   });

   // ✅ Good - ใช้ data attributes
   ```

2. **ไม่ต้อง Manipulate DOM directly**
   ```javascript
   // ❌ Bad
   document.querySelector('table').innerHTML = renderTable();

   // ✅ Good - ให้ TableManager ทำ
   ```

3. **ไม่ต้องสร้าง Helper ที่ not reusable**
   ```php
   // ❌ Bad - ทำแต่ครั้งเดียว
   function formatEmployeeName($id) { ... }

   // ✅ Good - ใช้ data-template
   ```

4. **ไม่ต้อง Hard-code API endpoints ใน JavaScript**
   ```javascript
   // ❌ Bad
   fetch('/api/users').then(...)

   // ✅ Good - เอา endpoint มาจาก HTML
   ```

5. **ไม่ต้องสร้าง Form validation ใน JavaScript**
   ```html
   <!-- ✅ Good - ใช้ HTML5 + data-validate -->
   <input type="email" required>

   <!-- ❌ Bad - JS validation -->
   ```

---

## UI Pages และ Common Patterns

### Pattern Types

#### 1. List/Table Pages
**ลักษณะ:** แสดงข้อมูลหลายแถว ค้นหา กรอง เรียงลำดับ

**ตัวอย่างการใช้:**
- ตารางผู้ใช้ (users)
- ตารางห้องประชุม (rooms)
- ตารางการจองรอการอนุมัติ (approvals)

**รูปแบบโครงสร้าง:**
```html
<table class="table"
       data-table="tableName"
       data-source="api/endpoint"
       data-default-sort="field desc"
       data-page-size="25"
       data-search-columns="col1,col2,col3"
       data-show-checkbox="true"                   <!-- Show select all/items -->
       data-actions='{...}'                        <!-- Bulk actions -->
       data-action-url="api/bulk-action"
       data-row-actions='{...}'>                   <!-- Per-row actions -->
  <thead>
    <tr>
      <th data-field="id" data-sort="id">ID</th>
      <th data-field="name" data-sort="name" data-template="...">Name</th>
      <th data-field="status" data-filter="true" data-type="select">Status</th>
      <th data-field="date" data-format="datetime">Date</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
```

**Advanced Features:**
- `data-filter="true"` - Column can be filtered
- `data-formatter="functionName"` - Use JS function to format cell
- `data-template="<html>"` - Use template with ${field} placeholders
- Bulk actions - Select rows + action + button
- Row actions - Edit/Delete/View per row
- Conditional actions - `"condition": "${can_edit == true}"`

---

#### 2. Form Pages
**ลักษณะ:** แฟบอร์มสำหรับสร้าง/แก้ไขข้อมูล

**ตัวอย่างการใช้:**
- แฟบอร์มสร้าง/แก้ไขผู้ใช้ (profile)
- แฟบอร์มจองห้อง (booking form)
- แฟบอร์มตั้งค่าระบบ (settings)

**รูปแบบโครงสร้าง:**
```html
<form data-form="formName"
      action="api/endpoint"
      method="post"
      data-ajax-submit="true"                      <!-- Send via AJAX -->
      data-validate="true"                         <!-- Enable HTML5 validation -->
      data-reset="true"                            <!-- Reset after submit -->
      data-load-api="api/get-endpoint"             <!-- Load initial data -->
      data-load-query-params="true"                <!-- Load from URL params (id=123) -->
      data-on-load="functionName">                 <!-- Call JS after load -->
  
  <fieldset>
    <legend>Section 1</legend>
    <div>
      <label for="field1">Field 1</label>
      <input type="text" id="field1" name="field1" data-attr="value:field_name">
    </div>
  </fieldset>
  
  <!-- Conditional fieldset -->
  <fieldset data-if="isSuperAdmin">
    <legend>Admin Only</legend>
    <!-- ... -->
  </fieldset>
  
  <!-- Disabled fieldset -->
  <fieldset data-attr="disabled:!can_edit">
    <legend>Editable Only When Permitted</legend>
    <!-- ... -->
  </fieldset>
  
  <fieldset class="submit">
    <button type="submit">Save</button>
  </fieldset>
</form>
```

**Input Element Examples:**
```html
<!-- Text input -->
<input type="text" name="name" data-attr="value:field_name" required maxlength="100">

<!-- Email -->
<input type="email" name="email" required>

<!-- Number -->
<input type="number" name="count" min="1" max="999">

<!-- Date/Time -->
<input type="date" name="date">
<input type="time" name="time">

<!-- Select dropdown -->
<select name="category" data-options-key="categories" data-attr="value:category_id">
  <option value="">Please select</option>
</select>

<!-- Multi-select -->
<select name="tags" multiple data-options-key="tags" data-attr="value:tags">
</select>

<!-- Checkbox -->
<input type="checkbox" id="agree" name="agree" class="switch" value="1" data-attr="checked:is_active">
<label for="agree">Agree</label>

<!-- File upload -->
<input type="file" name="document"
       data-files="document"                       <!-- File type identifier -->
       data-preview="true"                         <!-- Show preview -->
       data-allow-remove-existing="true"           <!-- Can remove uploaded file -->
       data-action-url="api/remove-file"           <!-- API to delete -->
       accept=".pdf,.doc">

<!-- Textarea -->
<textarea name="description" rows="4" data-text="description_field"></textarea>

<!-- Password -->
<input type="password" name="password"
       data-element="password"                     <!-- Special handling for password -->
       data-password-strength="bar"                <!-- Show strength indicator -->
       autocomplete="new-password">

<!-- Confirm password -->
<input type="password" name="confirm"
       data-target-password="password">            <!-- Validate matches password field -->

<!-- Tags input -->
<input type="text" name="tags" data-element="tags">
```

---

#### 3. Editable Table Pages
**ลักษณะ:** ตารางที่สามารถแก้ไขแถวได้โดยตรง

**ตัวอย่างการใช้:**
- จัดการหมวดหมู่ (categories)
- จัดการภาษา/การแปล (language translations)

**รูปแบบโครงสร้าง:**
```html
<form data-form="editForm"
      action="api/save"
      data-ajax-submit="true"
      data-load-api="api/get"
      data-load-query-params="true">
  
  <table class="table"
         data-table="editableTable"
         data-editable-rows="true"                 <!-- ✨ Enable editing -->
         data-attr="data:options"                  <!-- Bind to data -->
         data-dynamic-columns="true">              <!-- Auto-generate columns -->
    <tbody></tbody>
  </table>
  
  <button type="submit">Save All</button>
</form>
```

**ส่วนสำคัญ:**
- `data-editable-rows="true"` - Enable inline editing
- `data-dynamic-columns="true"` - Columns generated from data
- Form wraps table - Save all changes at once
- `data-attr="data:options"` - Binds table to data.options

---

#### 4. API Component Pages
**ลักษณะ:** หน้าที่ load ข้อมูลจาก API แล้วแสดง

**ตัวอย่างการใช้:**
- Dashboard statistics
- Calendar view
- Catalog grid

**รูปแบบโครงสร้าง:**
```html
<section data-component="api"
         data-endpoint="api/dashboard/stats"       <!-- API endpoint -->
         data-cache="true"                         <!-- Cache response -->
         data-cache-time="60000">                  <!-- Cache for 60 seconds -->
  
  <!-- Stat cards (show if data exists) -->
  <div data-if="stats && stats.length > 0">
    <div class="stat-card" data-for="stat in stats">
      <h3 data-text="stat.title"></h3>
      <div data-text="stat.value"></div>
    </div>
  </div>
  
  <!-- Empty state -->
  <div data-if="!stats || stats.length === 0">
    <p>No data available</p>
  </div>
</section>
```

**Loop Pattern:**
```html
<div data-for="item in items">
  <template>
    <!-- Template for each item -->
    <div data-text="item.name"></div>
  </template>
</div>
```

---

#### 5. Multi-Section Form Pages
**ลักษณะ:** ฟอร์มที่มีหลายส่วนที่แยกออกจากกัน

**ตัวอย่างการใช้:**
- User profile (Account + Personal + Contact + Address)
- Settings pages (General + Security + Social + Cache)

**รูปแบบโครงสร้าง:**
```html
<form data-form="settings"
      action="api/save"
      data-load-api="api/get"
      data-on-load="initializeSettings">
  
  <!-- Section 1: General -->
  <fieldset>
    <legend>General</legend>
    <!-- ... -->
  </fieldset>
  
  <!-- Section 2: Grouped with attribute -->
  <fieldset data-settings-group="security">
    <legend>Security</legend>
    <!-- ... -->
  </fieldset>
  
  <!-- Section 3: Conditional -->
  <fieldset data-if="options.department.length > 0">
    <legend>Department Settings</legend>
    <!-- ... -->
  </fieldset>
  
  <!-- Section 4: Editable only -->
  <fieldset data-attr="disabled:!can_edit">
    <legend>Editable Info</legend>
    <!-- ... -->
  </fieldset>
  
  <!-- Submit -->
  <fieldset class="submit">
    <button type="submit">Save</button>
  </fieldset>
</form>
```

---

#### 6. Authentication Pages
**ลักษณะ:** หน้า Login, Register, Password Reset

**ตัวอย่างการใช้:**
- Login page
- Register page
- Forgot password

**Login Form Pattern:**
```html
<form data-form="login"
      action="api/index/auth/login"
      method="post"
      data-ajax-submit="true"
      data-redirect="/"                            <!-- Redirect after login -->
      data-auto-fill-intended-url="true"           <!-- Go to intended page -->
      data-load-api="api/index/config/login"
      data-load-cache="true"
      data-load-cache-time="300000">
  
  <fieldset>
    <div>
      <label for="username">Username/Email</label>
      <input type="text" id="username" name="username"
             data-persist="local"                  <!-- Save to localStorage -->
             data-persist-key="login_username"
             data-persist-ttl-days="30">           <!-- Keep for 30 days -->
    </div>
    
    <div>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password">
    </div>
    
    <div>
      <input type="checkbox" id="remember" class="switch" name="remember"
             data-persist="local"
             data-persist-key="login_remember">
      <label for="remember">Remember Me</label>
    </div>
  </fieldset>
  
  <!-- Social login (conditional) -->
  <fieldset data-if="data.user_register === 1 || data.demo_mode === 1">
    <div class="social-login-buttons">
      <div data-attr="data-username:data.google_client_id" data-social-provider="google"></div>
      <button type="button" data-attr="data-username:data.line_channel_id"
              data-social-provider="line">LINE</button>
      <button type="button" data-attr="data-username:data.facebook_appId"
              data-social-provider="facebook">Facebook</button>
    </div>
  </fieldset>
</form>
```

---

#### 7. Special Components

**Slideshow:**
```html
<div class="now-slideshow"
     data-slideshow
     data-effect="fade"                            <!-- fade, slide -->
     data-autoplay="false"
     data-height="400px"
     data-show-indicators="true"
     data-show-nav="true"
     data-for="item in gallery">
  <template>
    <div class="now-slide">
      <img src="{{item.url}}" alt="{{item.title}}" loading="lazy">
    </div>
  </template>
</div>
```

**Filter Form (Table Navigation):**
```html
<form data-table-filter="tableName" class="table_nav">
  <div>
    <label>Show</label>
    <select name="pageSize">
      <option value="10">10 entries</option>
      <option value="25">25 entries</option>
    </select>
  </div>
  
  <div>
    <label>Status</label>
    <select name="status" data-attr="value:status">
      <option value="">All</option>
    </select>
  </div>
  
  <div>
    <label>From</label>
    <input type="date" name="from">
  </div>
  
  <div>
    <label>To</label>
    <input type="date" name="to">
  </div>
  
  <input type="search" name="search" placeholder="Search...">
</form>
```

---

## Data Binding และ Conditional Display

### Data Binding Syntax

```html
<!-- Bind to simple field -->
<input type="text" name="field" data-attr="value:fieldName">
<!-- Binds to: data.fieldName -->

<!-- Bind to nested object -->
<input type="text" name="meta" data-attr="value:metas['department'][0]">
<!-- Binds to: data.metas.department[0] -->

<!-- Bind text content -->
<span data-text="user.name"></span>
<!-- Shows: data.user.name -->

<!-- Bind with expression -->
<span data-text="status == 1 ? 'Active' : 'Inactive'"></span>

<!-- Bind checkbox state -->
<input type="checkbox" name="active" data-attr="checked:is_active">
<!-- Checks if: data.is_active == true -->

<!-- Bind select -->
<select name="status" data-attr="value:status">
  <option value="0">Inactive</option>
  <option value="1">Active</option>
</select>

<!-- Bind dynamic class -->
<div data-class="active:is_active,disabled:!can_edit">
<!-- Adds "active" class if is_active=true -->
<!-- Adds "disabled" class if can_edit=false -->

<!-- Bind dynamic style -->
<div data-style="backgroundColor:color_code,opacity:opacity">
</div>
```

### Conditional Display

```html
<!-- Show if condition true -->
<div data-if="isSuperAdmin">
  Admin only section
</div>

<!-- Show if multiple conditions -->
<div data-if="status === 'approved' && !is_locked">
  Editable section
</div>

<!-- Show/hide with NOT operator -->
<div data-if="!error">
  Success message
</div>

<!-- Show in loop -->
<div data-for="item in items">
  <div data-if="item.is_active">
    Active item: <span data-text="item.name"></span>
  </div>
</div>
```

### Conditional Fieldset Disabling

```html
<!-- Disable entire fieldset based on condition -->
<fieldset data-attr="disabled:!can_edit">
  <legend>Editable Section</legend>
  <!-- All inputs disabled if can_edit = false -->
</fieldset>

<!-- Disable button -->
<button type="submit" data-attr="disabled:!isValid">
  Save
</button>
```

---

## Data Attribute Reference

### Table Attributes
| Attribute | ค่า | ใช้สำหรับ |
|-----------|------|----------|
| `data-table` | string | ชื่อ unique ของตาราง |
| `data-source` | URL | API endpoint |
| `data-editable-rows` | true/false | Enable inline editing |
| `data-dynamic-columns` | true/false | Headers จาก API |
| `data-default-sort` | column desc | Default sorting |
| `data-page-size` | number | Items per page |
| `data-show-checkbox` | true/false | Show checkboxes |
| `data-search-columns` | col1,col2 | Searchable columns |
| `data-actions` | JSON | Bulk actions |
| `data-row-actions` | JSON | Per-row actions |

### Column (th) Attributes
| Attribute | ค่า | ใช้สำหรับ |
|-----------|------|----------|
| `data-field` | string | Field name from API |
| `data-sort` | column | Sortable column |
| `data-filter` | true/false | Show filter dropdown |
| `data-format` | datetime/number/lookup | Format cell value |
| `data-template` | HTML | Custom cell HTML |
| `data-type` | select/text | Filter type |

### Form Attributes
| Attribute | ค่า | ใช้สำหรับ |
|-----------|------|----------|
| `data-form` | string | Form identifier |
| `data-validate` | true/false | Enable validation |
| `data-ajax-submit` | true/false | AJAX submission |
| `data-load-api` | URL | API to load data |
| `data-load-query-params` | true/false | Pass URL params |
| `data-attr` | path:field | Bind to data |
| `data-if` | condition | Show/hide |
| `data-element` | type | Special element |
| `data-options-key` | name | Options from API |

---

## Advanced Features

### 1. Button Actions (API Calls)

```html
<!-- Simple API call -->
<button type="button"
        data-action="click.prevent:requestApi"     <!-- Prevent default, call API -->
        data-api-url="api/endpoint"                <!-- API URL -->
        data-api-method="post"                     <!-- HTTP method -->
        data-param-id="0"                          <!-- Optional params, one per data-param-<name> -->
        data-confirm="Are you sure?">              <!-- Confirm before action -->
  Process
</button>

<!-- With loading and success feedback -->
<button type="button"
        data-action="click.prevent:requestApi"
        data-api-url="api/cache/clear"
        data-api-method="post"
        data-loading-text="Clearing..."             <!-- Text while loading -->
        data-notify-success="true">                 <!-- Show success message -->
  Clear Cache
</button>
```

**Passing params — `data-param-<name>`, NOT `data-api-params`:** each POST/PUT/PATCH
body field is its own `data-param-<name>="value"` attribute (verified against
`EventSystemManager.collectRequestParams()` — it scans `element.dataset` for keys
starting with `param` and camelCase→snake_case's the rest, e.g. `data-param-driverSlug`
→ `driver_slug`). A single `data-api-params="{...}"` JSON-blob attribute is **not**
read anywhere by `requestApi` — that string belongs to a different, unrelated feature
(`LineItemsManager`'s CSS-selector config) and silently does nothing here. Values
wrapped in `{fieldName}` are resolved from the closest matching form field, the
element's own dataset, or the URL query string (see `resolveRequestFieldValue()`).

For GET requests the same `data-param-*` attributes become the query string instead
of the body — same syntax, `requestApi` decides based on `data-api-method`.

Every `requestApi` response is automatically piped through `ResponseHandler.process()`
(via `window.httpAction`, see `Now/js/HttpClient.js`) — server-returned
`actions: [...]` (notification / redirect / modal open-close / etc., see Pattern 3
below) run without any extra JS.

<!-- Navigation -->
<a href="/page" data-action="click.prevent:navigate">
  Go to Page
</a>

<!-- Toggle class -->
<button data-action="click.prevent:toggleClass"
        data-toggle-target="#element"
        data-toggle-class="active">
  Toggle
</button>
```

### 2. Local Storage Persistence

```html
<!-- Save to localStorage automatically -->
<input type="text"
       name="username"
       data-persist="local"                        <!-- Use localStorage -->
       data-persist-key="login_username"           <!-- Storage key -->
       data-persist-ttl-days="30">                 <!-- Expire after 30 days -->

<!-- Save checkbox state -->
<input type="checkbox"
       name="remember"
       data-persist="local"
       data-persist-key="login_remember">
```

### 3. Dynamic Styling and Attributes

```html
<!-- Dynamic background image -->
<div data-style="backgroundImage:url({{data.logoUrl}})"></div>

<!-- Dynamic multiple styles -->
<div data-style="backgroundColor:bgColor,color:textColor,opacity:opacity">
</div>

<!-- Dynamic attributes -->
<a data-attr="href:item.url,title:item.title">
  Link
</a>

<!-- Dynamic class with multiple conditions -->
<div data-class="active:is_active,disabled:!can_edit,highlight:is_featured">
</div>
```

### 4. Social Login Integration

```html
<div data-attr="data-username:data.google_client_id"
     data-social-provider="google">
</div>

<button type="button"
        data-attr="data-username:data.facebook_appId"
        data-social-provider="facebook">
  Login with Facebook
</button>

<button type="button"
        data-attr="data-username:data.line_channel_id"
        data-social-provider="line">
  Login with LINE
</button>

<div data-attr="data-username:data.telegram_bot_username"
     data-social-provider="telegram">
</div>
```

### 5. Password Strength and Validation

```html
<!-- Password with strength indicator -->
<input type="password"
       name="password"
       data-element="password"
       data-password-strength="bar"                <!-- Show strength bar -->
       data-password-criteria-list="true"          <!-- Show criteria list -->
       autocomplete="new-password">

<!-- Confirm password (must match) -->
<input type="password"
       name="confirm_password"
       data-element="password"
       data-target-password="password">            <!-- Validate against "password" field -->

<!-- Password validation message -->
<div class="comment" id="result_password">
  Password must be at least 8 characters long and contain uppercase and lowercase
</div>
```

### 6. File Upload with Preview

```html
<input type="file"
       name="avatar"
       data-files="avatar"                         <!-- File type identifier -->
       data-preview="true"                         <!-- Show preview -->
       data-allow-remove-existing="true"           <!-- Allow removal -->
       data-action-url="api/remove-avatar"         <!-- API to delete file -->
       data-file-reference="url"                   <!-- Reference field for preview -->
       accept="image/*">

<!-- Multiple files -->
<input type="file"
       name="documents"
       data-files="documents"
       multiple
       data-preview="true"
       data-allow-remove-existing="true"
       data-action-url="api/remove-document">
```

### 7. Custom Element Types

```html
<!-- Tags input (multiple values) -->
<input type="text"
       name="permissions"
       data-element="tags"
       data-options-key="available_permissions">

<!-- Password element with special handling -->
<input type="password"
       name="password"
       data-element="password"
       data-password-strength="bar">
```

### 8. Select with Dynamic Options

```html
<!-- Options loaded from API/data -->
<select name="department"
        data-options-key="departments"             <!-- Get options from data.departments -->
        data-attr="value:selected_department">
  <option value="">Please select</option>
</select>

<!-- With format -->
<select name="department"
        data-options-key="department"
        data-format="lookup">
  <!-- Automatically formats display value -->
</select>
```

### 9. Cache Configuration

```html
<!-- API component with caching -->
<div data-component="api"
     data-endpoint="api/data"
     data-cache="true"                            <!-- Enable cache -->
     data-cache-time="60000">                     <!-- Cache for 60 seconds -->
  <!-- Content -->
</div>

<!-- Form data loading with cache -->
<form data-form="editForm"
      data-load-api="api/get"
      data-load-cache="true"
      data-load-cache-time="300000">
  <!-- Fields -->
</form>
```

---

## Event Handling และ Validation

### Input Events

```html
<!-- On change event -->
<input type="text"
       name="search"
       data-on-change="handleSearch"               <!-- Call JS function -->
       data-debounce="300">                        <!-- Debounce 300ms -->

<!-- On keyup event -->
<input type="text"
       data-on-keyup="updatePreview">

<!-- On focus -->
<input type="text" data-on-focus="highlightField">
```

### Form Validation

```html
<!-- HTML5 Built-in Validation -->
<input type="email" required>                      <!-- Required -->
<input type="text" minlength="3" maxlength="50">  <!-- Length -->
<input type="number" min="1" max="100">            <!-- Range -->
<input type="text" pattern="[A-Z0-9]+">            <!-- Pattern -->

<!-- Enable framework validation -->
<form data-form="myForm" data-validate="true">
  <!-- Validation runs on submit -->
</form>

<!-- Validation messages -->
<div id="result_email" class="comment">
  Please enter a valid email address
</div>
```

### Row Actions with Conditions

```html
<table data-table="items"
       data-row-actions='{
         "view": {
           "title": "View",
           "className": "btn btn-info icon-search"
         },
         "edit": {
           "title": "Edit",
           "className": "btn btn-success icon-edit",
           "condition": "${can_edit == true}"     <!-- Only show if condition true -->
         },
         "delete": {
           "title": "Delete",
           "className": "btn btn-danger icon-delete",
           "confirm": "Are you sure to delete?"   <!-- Ask for confirmation -->
         }
       }'>
  <!-- ... -->
</table>
```

---

## Internationalization (i18n)

### Translation Keys

```html
<!-- Simple label translation -->
<label for="name" data-i18n>Full Name</label>

<!-- Using LNG_ key prefix -->
<label for="email" data-i18n>{LNG_Email}</label>

<!-- Placeholder translation -->
<input placeholder="{LNG_Type to search}...">

<!-- Button text -->
<button class="btn" data-i18n>Save Changes</button>

<!-- Conditional text translation -->
<span data-text="status == 1 ? '{LNG_Active}' : '{LNG_Inactive}'"></span>

<!-- Dynamic translation in template -->
<th data-template="<span>{LNG_${status_text}}</span>">
  Status
</th>
```

---

## Form Field Grouping dan Layout

### Two Column Layout

```html
<div class="form-group">
  <div class="width50">
    <label for="field1">Left Column</label>
    <input type="text" id="field1" name="field1">
  </div>
  <div class="width50">
    <label for="field2">Right Column</label>
    <input type="text" id="field2" name="field2">
  </div>
</div>
```

### Three Column Layout

```html
<div class="form-group">
  <div class="width33">
    <!-- 1/3 width -->
  </div>
  <div class="width33">
    <!-- 1/3 width -->
  </div>
  <div class="width33">
    <!-- 1/3 width -->
  </div>
</div>
```

### Helper Text and Comments

```html
<div>
  <label for="password">Password</label>
  <input type="password" id="password" name="password">
  <div class="comment" id="result_password">
    Password must be at least 8 characters long
  </div>
</div>

<!-- Highlighted example -->
<div class="comment">
  <em>example@email.com</em>
</div>
```

---

## Table Column Formatting

### Format Types

```html
<!-- Datetime format -->
<th data-field="created_at" data-format="datetime">Created</th>

<!-- Date format -->
<th data-field="birth_date" data-format="date">Birth Date</th>

<!-- Number format -->
<th data-field="count" data-format="number">Count</th>

<!-- Lookup format (convert ID to name) -->
<th data-field="department_id" data-format="lookup">Department</th>

<!-- Custom formatter function -->
<th data-field="room_id" data-formatter="formatRoomWithImage">Room</th>

<!-- Cell class -->
<th data-field="price" class="center" data-cell-class="center">Price</th>
```

### Template Variables in Cells

```html
<!-- Basic field -->
<th data-template="${name}">Name</th>

<!-- Multiple fields combined -->
<th data-template="<span>${name} (${role})</span>">User</th>

<!-- Conditional template -->
<th data-template="<span class='${status > 0 ? 'active' : 'inactive'}'>${status_text}</span>">
  Status
</th>

<!-- Link template -->
<th data-template="<a href='/user/${id}'>${name}</a>">User</th>

<!-- Action button template -->
<th data-template="<button class='btn-table-action' data-action='active' data-value='${active}'></button>">
  Active
</th>
```

---

## สรุป

Now.js Framework ออกแบบให้:
- 📝 **Write HTML**, not JavaScript
- 🔗 **Declarative**, not Imperative
- ⚡ **Fast Development** with automatic binding
- 🔒 **Secure** with built-in auth & validation
- 📱 **Responsive** by default

### Key Principles:
1. HTML เป็นหลัก (Structure + Configuration)
2. data-* attributes ทำหน้าที่ทั้ง configuration และ bindings
3. Framework managers (TableManager, FormManager, etc) handle all interactions
4. API endpoints ปฏิบัติตาม standard patterns
5. Models ควรเป็นเพียง Database access layer
6. Controllers ควร focus ที่ validation และ authorization

---

---

## Best Practices

### ✅ Do's

**1. HTML First, JavaScript Last**
```html
<!-- ✅ Good: Use data-attributes -->
<input type="text" name="search" data-on-change="debounce:handleSearch">

<!-- ❌ Avoid: Writing JS when declarative is available -->
```

**2. Use Semantic HTML**
```html
<!-- ✅ Good -->
<fieldset>
  <legend>Section Title</legend>
  <label for="field">Label</label>
  <input id="field" name="field">
</fieldset>

<!-- ❌ Avoid -->
<div class="fieldset">
  <span>Section Title</span>
  <span>Label</span>
  <input>
</div>
```

**3. Leverage Framework Features**
```html
<!-- ✅ Good: Use built-in components -->
<table data-table="items" data-source="api/items">
  <!-- Framework handles pagination, sorting, search -->
</table>

<!-- ❌ Avoid: Building from scratch -->
```

**4. Group Related Fields**
```html
<!-- ✅ Good: Logical fieldsets -->
<fieldset>
  <legend>Contact Information</legend>
  <!-- contact fields -->
</fieldset>

<!-- ❌ Avoid: Random field placement -->
```

**5. Provide Validation Feedback**
```html
<!-- ✅ Good: Clear validation messages -->
<input type="email" id="email" required>
<div class="comment" id="result_email">
  Please enter a valid email address
</div>

<!-- ❌ Avoid: Silent validation -->
```

**6. Use i18n for All User-Facing Text**
```html
<!-- ✅ Good -->
<label data-i18n>Username</label>
<button class="btn" data-i18n>Save</button>

<!-- ❌ Avoid: Hardcoded English text -->
```

**7. Cache API Responses When Appropriate**
```html
<!-- ✅ Good: Config data doesn't change often -->
<form data-load-api="api/config"
      data-load-cache="true"
      data-load-cache-time="300000">
  <!-- ... -->
</form>

<!-- ❌ Avoid: Fetching unchanging data repeatedly -->
```

**8. Use Conditional Display for Permission-Based Content**
```html
<!-- ✅ Good -->
<fieldset data-if="isSuperAdmin">
  <legend>Admin Only</legend>
  <!-- ... -->
</fieldset>

<!-- ❌ Avoid: Server rendering all variants -->
```

---

### ❌ Don'ts

**1. Don't Write JavaScript for UI Tasks**
```html
<!-- ❌ Bad: Unnecessary JS -->
<form id="myForm">
  <input id="username">
  <input id="password">
  <button id="submitBtn">Login</button>
</form>

<script>
  document.getElementById('submitBtn').addEventListener('click', function() {
    // Manual handling
  });
</script>

<!-- ✅ Good: Use framework -->
<form data-form="login" data-ajax-submit="true">
  <input name="username">
  <input name="password">
  <button type="submit">Login</button>
</form>
```

**2. Don't Disable Built-in Validation**
```html
<!-- ❌ Bad: Bypassing validation -->
<input type="email" required>
<script>
  form.addEventListener('submit', (e) => {
    e.preventDefault(); // Bypass validation
  });
</script>

<!-- ✅ Good: Use HTML5 validation -->
<input type="email" required>
```

**3. Don't Create Massive Forms**
```html
<!-- ❌ Bad: 50 fields in one form -->
<form>
  <!-- ... 50 fields -->
</form>

<!-- ✅ Good: Logical sections in fieldsets -->
<form>
  <fieldset>
    <legend>Basic Info</legend>
    <!-- 5-10 fields -->
  </fieldset>
  <fieldset>
    <legend>Contact Info</legend>
    <!-- 5-10 fields -->
  </fieldset>
</form>
```

**4. Don't Hardcode Data**
```html
<!-- ❌ Bad: Hardcoded options -->
<select name="status">
  <option value="1">Active</option>
  <option value="2">Inactive</option>
  <option value="3">Suspended</option>
</select>

<!-- ✅ Good: Load from API -->
<select name="status" data-options-key="statuses">
  <option value="">Please select</option>
</select>
```

**5. Don't Mix Concerns**
```html
<!-- ❌ Bad: Business logic in template -->
<table data-table="users">
  <th data-template="${role == 1 ? 'Admin' : (role == 2 ? 'User' : 'Guest')}">
</table>

<!-- ✅ Good: Use formatter function or data format -->
<table data-table="users">
  <th data-field="role" data-format="lookup">Role</th>
</table>
```

---

### Performance Tips

**1. Use Pagination**
```html
<!-- Good: Paginated table -->
<table data-page-size="25">
  <!-- Shows 25 items, pagination controls -->
</table>

<!-- Avoid: Showing 1000+ rows -->
```

**2. Lazy Load Images**
```html
<!-- Good -->
<img src="{{item.url}}" loading="lazy">

<!-- Avoid -->
<img src="{{item.url}}">
```

**3. Cache API Responses**
```html
<!-- Good: Cache config data -->
<form data-load-cache="true" data-load-cache-time="300000">
  <!-- Load once, use 5 minutes -->
</form>

<!-- Avoid: Fetching every time -->
```

**4. Use Debouncing for Search**
```html
<!-- Good: Debounce search input -->
<input type="search"
       name="query"
       data-on-keyup="debounce:300:handleSearch">

<!-- Avoid: Search on every keystroke -->
```

---

### Security Tips

**1. Always Validate on Server**
```html
<!-- HTML5 validation is for UX only -->
<input type="email" required>

<!-- ✅ Server must validate again -->
```

**2. Use CSRF Tokens**
```html
<!-- Framework handles CSRF automatically for AJAX -->
<!-- Ensure your API validates tokens -->
```

**3. Sanitize User Input**
```html
<!-- Data binding handles escaping -->
<span data-text="user_input"></span>
<!-- User input is escaped automatically -->
```

**4. Don't Store Sensitive Data in localStorage**
```html
<!-- ❌ Bad: Don't persist sensitive data -->
<input data-persist="local" name="password">

<!-- ✅ Good: Only persist non-sensitive preferences -->
<input data-persist="local" name="theme_preference">
```

**5. Use Proper Input Types**
```html
<!-- ✅ Good: Type-specific inputs -->
<input type="email" name="email">
<input type="password" name="password">
<input type="number" name="age">

<!-- ❌ Avoid: Everything as text -->
```

---

### Code Organization Tips

**1. Group Related Fields**
- Account Information → username, password
- Personal Information → name, birth date
- Contact Information → phone, email
- Address Information → street, city, country

**2. Use Consistent Naming**
- Attributes: `snake_case` (name="first_name")
- Data attributes: `camelCase` (data-onLoad)
- IDs: `kebab-case` (id="user-form")

**3. Keep Fieldsets Focused**
- One fieldset per logical section
- 5-10 fields per fieldset ideal
- Clear, descriptive legend

**4. Reuse Patterns**
- Table with filters pattern
- Form with multiple sections pattern
- API component with conditional display pattern

---

**Document Version:** 2.0  
**Last Updated:** 2026-06-28  
**Framework:** Now.js v1.0.0

**Sections Added in v2.0:**
- UI Pages and Common Patterns (7 pattern types)
- Data Binding and Conditional Display
- Advanced Features (9 subsections)
- Event Handling and Validation
- Internationalization
- Form Field Grouping and Layout
- Table Column Formatting
- Comprehensive Best Practices
- Performance, Security, and Code Organization Tips
