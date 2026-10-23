/**
 * modules/demo/admin.js
 *
 * ลงทะเบียน route และของที่ฝั่งเบราว์เซอร์ต้องเตรียมให้โมดูลสาธิต
 *
 * index.php วน scandir('modules') แล้วแทรก <script> ของไฟล์นี้ให้เองก่อน
 * js/main.js จึงผูก listener ทัน event router:initialized เสมอ — ไม่ต้องแก้
 * ไฟล์กลางแม้แต่บรรทัดเดียว
 */

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
