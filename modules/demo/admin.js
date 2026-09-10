/**
 * modules/demo/admin.js
 *
 * ลงทะเบียน route และของที่ฝั่งเบราว์เซอร์ต้องเตรียมให้โมดูลสาธิต
 *
 * index.php วน scandir('modules') แล้วแทรก <script> ของไฟล์นี้ให้เองก่อน
 * js/main.js จึงผูก listener ทัน event router:initialized เสมอ — ไม่ต้องแก้
 * ไฟล์กลางแม้แต่บรรทัดเดียว
 */

/**
 * บันเดิลเสริมที่โมดูลนี้ต้องใช้ — โหลดเอง ไม่ฝากไว้กับ index.php
 *
 * index.php โหลดให้เฉพาะ core/table/graph/serviceworker ปฏิทินไม่ได้อยู่ในนั้น
 * ถ้าไปเพิ่มใน index.php ทุกโปรเจ็คที่รับ adminframework ไปจะต้องโหลด 57KB นี้
 * ทั้งที่ส่วนใหญ่ไม่ได้ใช้ และถอดโมดูลออกก็ยังโหลดค้างอยู่ — เอามาไว้ที่โมดูล
 * ลบโฟลเดอร์โมดูลทิ้งแล้วบันเดิลหายไปพร้อมกัน
 *
 * เส้นทางคำนวณจาก src ของไฟล์นี้ เพราะแอปติดตั้งในไดเรกทอรีย่อยได้
 * (eventcalendar.min.js เรียก init() ของตัวเองตอนโหลดจบ จึงไม่ต้องสั่งเพิ่ม)
 */
(() => {
  const src = document.currentScript?.src || '';
  const base = src.replace(/modules\/demo\/admin\.js.*$/, '');
  if (!base) return;

  const css = document.createElement('link');
  css.rel = 'stylesheet';
  css.href = `${base}Now/dist/eventcalendar.min.css`;
  document.head.appendChild(css);

  const js = document.createElement('script');
  js.src = `${base}Now/dist/eventcalendar.min.js`;
  document.head.appendChild(js);
})();

/**
 * ปลุกปฏิทินที่หน้าแรกเพิ่งวาดจาก data-for
 *
 * ต่างจาก TableManager กับ GraphComponent ที่เฝ้า DOM ด้วย observer เอง
 * EventCalendar สแกนใหม่เฉพาะตอน route:changed / modal:shown / page:loaded /
 * pageshow / visibilitychange ซึ่งเกิด **ก่อน** ที่ ApiComponent จะได้คำตอบ
 * จาก api/index/dashboard บล็อกปฏิทินที่ data-for สร้างจึงไม่ถูก init เลย
 *
 * api:content-rendered ยิงหลัง ApiComponent วาด data-for เสร็จ (ApiComponent.js
 * dispatch ท้าย renderContent) จึงเป็นจังหวะที่ถูกต้อง — สั่งซ้ำได้ไม่เสียหาย
 * เพราะ create() คืน instance เดิมถ้าปฏิทินตัวนั้นยังอยู่ในหน้า
 *
 * ⚠️ ตัวที่ส่งมากับ EventManager คือ {instance, ...detail} — ไม่มีคีย์ element
 * (มีเฉพาะใน detail ของ CustomEvent บน DOM) ต้องอ่านจาก instance.element
 */
EventManager.on('api:content-rendered', ({instance}) => {
  window.EventCalendar?.discoverCalendars(instance?.element || document);
});

EventManager.on('router:initialized', () => {
  // ไม่มี route ของ Dashboard — ตัวอย่าง Dashboard ไปอยู่บนหน้าแรกแล้ว
  // ผ่าน hook initDashboard/initDashboardBlocks ใน controllers/init.php
  RouterManager.register('/demo-table', {
    template: 'demo/table.html',
    title: '{LNG_Example} {LNG_Table}',
    requireAuth: true
  });
  RouterManager.register('/demo-form', {
    template: 'demo/settings.html',
    title: '{LNG_Example} {LNG_Form}',
    requireAuth: true
  });
});
