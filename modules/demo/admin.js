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
  RouterManager.register('/demo-elements', {
    template: 'demo/elements.html',
    title: '{LNG_Example} {LNG_Form elements}',
    requireAuth: true
  });
  RouterManager.register('/demo-richtext', {
    template: 'demo/richtext.html',
    title: '{LNG_Example} {LNG_Rich Text Editor}',
    requireAuth: true
  });
  RouterManager.register('/demo-components', {
    template: 'demo/components.html',
    title: '{LNG_Example} {LNG_Components}',
    requireAuth: true
  });
});

/**
 * ฟังก์ชันของหน้าตัวอย่าง (templates/demo/richtext.html และ components.html)
 *
 * data-on / data-script เรียกได้เฉพาะฟังก์ชันของแอปบน window และฟังก์ชันที่ลงทะเบียนด้วย
 * Now.registerFunction() — ชี้ตรงไปที่ NotificationManager, DialogManager, Modal ฯลฯ
 * ไม่ได้ (Now.resolveFunction() ปฏิเสธ global ของเฟรมเวิร์ก) จึงต้องมีตัวคั่นกลางแบบนี้
 */

/**
 * โหลด Rich Text Editor (richtext-editor.min.js/.css) เมื่อเปิดหน้าตัวอย่าง RTE
 *
 * ช่อง <textarea data-element="richtext"> อยู่ใน feature bundle ที่ core โหลดเอง แต่ตัว editor
 * เป็นอีกไฟล์ที่ core ไม่โหลดให้ — ช่องจะรอ window.RichTextEditor แล้วลองซ้ำทุก 100 ms ดังนั้น
 * แค่ต่อ <script> เข้าไปก็พอ ไม่ต้องรอ · หน้าอื่นจึงไม่ต้องจ่ายค่าโหลด editor 260 KB
 *
 * ใช้ ?v= ของ now.core.min.js ต่อท้าย เพราะ service worker เก็บ .js/.css ที่ติด ?v= ไว้ใน cache จนกว่าเลขจะเปลี่ยน
 *
 * @returns {void}
 */
window.demoLoadEditor = function () {
  if (window.RichTextEditor || document.getElementById('demo-rte-script')) {
    return;
  }
  const core = document.querySelector('script[src*="now.core.min.js"]');
  if (!core) {
    return;
  }
  const url = new URL(core.src, document.baseURI);
  const base = url.pathname.replace(/[^/]*$/, '');

  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = base + 'richtext-editor.min.css' + url.search;
  document.head.appendChild(link);

  const script = document.createElement('script');
  script.id = 'demo-rte-script';
  script.src = base + 'richtext-editor.min.js' + url.search;
  document.head.appendChild(script);
};

window.demoPage = {
  /**
   * @param {string} type success | error | warning | info | loading
   */
  notify(type) {
    const messages = {
      success: 'Saved successfully',
      error: 'Something went wrong',
      warning: 'Please check the data',
      info: 'This is a notice',
      loading: 'Working…'
    };
    const id = NotificationManager[type](Now.translate(messages[type]));
    // loading ไม่หายเอง — ผู้เรียกเป็นคนปิดเมื่องานเสร็จ
    if (type === 'loading') {
      setTimeout(() => NotificationManager.dismiss(id), 2500);
    }
  },

  /**
   * @param {string} kind alert | confirm | prompt
   */
  async dialog(kind) {
    const out = document.getElementById('demo_dialog_result');
    let answer;
    if (kind === 'alert') {
      answer = await DialogManager.alert(Now.translate('This is an alert'), Now.translate('Alert'));
    } else if (kind === 'confirm') {
      answer = await DialogManager.confirm(Now.translate('Do you want to continue?'), Now.translate('Confirm'));
    } else {
      answer = await DialogManager.prompt(Now.translate('What is your name?'), '', Now.translate('Prompt'));
    }
    if (out) {
      out.textContent = answer === null ? 'null' : String(answer);
    }
  },

  modal() {
    const modal = new Modal({
      title: Now.translate('Modal'),
      content: '<p>' + Now.translate('A modal can hold any HTML: text, a form, a table or another component') + '</p>',
      closeButton: true
    });
    modal.show();
  },

  /**
   * ตัวแก้ไขในหน้า RTE ที่ใช้ทดลอง API (id="rte_api")
   *
   * @returns {Object|null} instance ของช่อง เมื่อ editor พร้อมแล้ว
   */
  rteInstance() {
    const instance = ElementManager.getInstanceByElement(document.getElementById('rte_api'));
    if (!instance || typeof instance.getValue !== 'function') {
      NotificationManager.warning(Now.translate('The editor is still loading'));
      return null;
    }
    return instance;
  },

  rteSample() {
    const instance = this.rteInstance();
    if (instance) {
      instance.setValue('<h3>Sample</h3><p>Set from JavaScript with <b>setValue()</b>.</p><ul><li>One</li><li>Two</li></ul>');
    }
  },

  rteRead() {
    const instance = this.rteInstance();
    if (!instance) {
      return;
    }
    const out = document.getElementById('rte_out');
    const stats = document.getElementById('rte_stats');
    if (out) {
      out.value = instance.getValue();
    }
    const editor = instance._rteInstance;
    if (stats && editor) {
      stats.textContent = editor.getWordCount() + ' words · ' + editor.getCharacterCount() + ' characters';
    }
  },

  rteToggleReadonly() {
    const instance = this.rteInstance();
    if (instance && instance._rteInstance) {
      instance.setReadOnly(!instance._rteInstance.isReadOnly());
    }
  },

  rteClear() {
    const instance = this.rteInstance();
    if (instance) {
      instance.clear();
    }
  },

  /**
   * data-on-calculate ของตารางรายการสินค้า — รับทุกแถวแล้วคืนยอดย่อยของแต่ละแถวกับยอดรวม
   *
   * ค่าที่ส่งกลับที่ขึ้นต้นด้วย # คือ selector ของ element ที่จะถูกเติมค่า (LineItemsManager ทำให้เอง)
   *
   * @param {Object} data {items, instance}
   *
   * @returns {Object}
   */
  calculate(data) {
    const number = value => parseFloat(String(value ?? '').replace(/,/g, '')) || 0;
    let total = 0;
    const items = data.items.map(row => {
      const subtotal = Math.round(number(row.quantity) * number(row.unit_price) * 100) / 100;
      total += subtotal;
      return {subtotal};
    });
    return {items, '#demo_total': total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})};
  }
};
