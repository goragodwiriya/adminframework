<?php
/**
 * install/cli-testcleanhtml.php — HTML ที่ผู้ใช้เขียน ฝั่ง server ของ Kotchasan
 *
 * Now.js ไม่กรอง body ฝั่ง client (ปิดเป็นค่าเริ่มต้น) และ RichTextEditor ส่งเนื้อหาครบตามที่เขียน
 * server เป็นผู้ตัดสินว่าจะเก็บอะไร มีสามระดับ:
 *   ->detail()        เนื้อหาจาก editor ของผู้เขียนที่ได้รับอนุญาต เก็บทุกอย่างตามที่เขียน (CSS, JS, ฟอร์ม)
 *   ->detail(false)   เหมือนกันแต่ตัดสิ่งที่รัน JavaScript ได้ ที่เหลือ (CSS ฟอร์ม iframe) อยู่ครบ
 *   ->cleanHtml()     HTML จากคนที่ไม่ไว้ใจให้จัดหน้า (ความคิดเห็น ฟอร์มสาธารณะ) เหลือแค่การจัดรูปแบบ
 *
 * ใช้:  php install/cli-testcleanhtml.php
 * exit code 0 = ผ่านทั้งหมด
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-testcleanhtml.php'], ['/', ''], __FILE__));
include ROOT_PATH.'Kotchasan/load.php';

$results = [];
$check = function ($name, $ok, $detail = '') use (&$results) {
    $results[] = (bool) $ok;
    echo ($ok ? '  ✔ ' : '  ✘ ').$name.($ok || $detail === '' ? '' : '  — '.$detail)."\n";
};

if (!method_exists('Kotchasan\Text', 'cleanHtml') || !method_exists('Kotchasan\Http\InputItem', 'cleanHtml')) {
    $check('Kotchasan\Text::cleanHtml() และ Kotchasan\Http\InputItem::cleanHtml() มีอยู่', false, 'ยังไม่มีเมธอด');
    echo "\n1 of 1 checks failed\n";
    exit(1);
}

$clean = function ($html) {
    return \Kotchasan\Text::cleanHtml($html);
};

// สิ่งที่รันโค้ดได้ต้องหายไป
$cases = [
    'script' => ['<p>a</p><script>alert(1)</script><p>b</p>', '<p>a</p><p>b</p>'],
    'onerror' => ['<img src="x.png" onerror="alert(1)">', '<img src="x.png">'],
    'onclick uppercase' => ['<B ONCLICK="x()">y</B>', '<b>y</b>'],
    'javascript href' => ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'],
    'javascript href with tab entity' => ['<a href="java&#x09;script:alert(1)">x</a>', '<a>x</a>'],
    'javascript href with spaces and case' => ['<a href="  JaVaScRiPt:alert(1)">x</a>', '<a>x</a>'],
    'vbscript' => ['<a href="vbscript:msgbox(1)">x</a>', '<a>x</a>'],
    'data:text/html' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', '<a>x</a>'],
    'iframe from unknown host' => ['<iframe src="https://evil.example/x"></iframe><p>t</p>', '<p>t</p>'],
    'iframe javascript' => ['<iframe src="javascript:alert(1)"></iframe>', ''],
    'srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>" src="https://www.youtube.com/embed/a"></iframe>', null],
    'svg onload' => ['<svg onload="alert(1)"><circle r="1"/></svg><p>s</p>', '<p>s</p>'],
    'math' => ['<math><mi>x</mi></math><p>m</p>', '<p>m</p>'],
    'style tag' => ['<style>body{display:none}</style><p>c</p>', '<p>c</p>'],
    'form controls' => ['<form action="https://evil.example"><input name="p"><button formaction="x">b</button></form><p>f</p>', '<p>f</p>'],
    'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.example"><p>r</p>', '<p>r</p>'],
    'base' => ['<base href="https://evil.example/"><p>b</p>', '<p>b</p>'],
    'object/embed' => ['<object data="x.swf"></object><embed src="x.swf"><p>o</p>', '<p>o</p>'],
    'style expression' => ['<p style="width:expression(alert(1))">e</p>', '<p>e</p>'],
    'style url javascript' => ['<p style="background:url(javascript:alert(1))">u</p>', '<p>u</p>'],
    'php tag' => ['<p>a</p><?php echo 1; ?><p>b</p>', '<p>a</p><p>b</p>'],
    'details ontoggle' => ['<details open ontoggle="alert(1)">d</details>', '<details open>d</details>'],
];
foreach ($cases as $name => [$in, $expected]) {
    $out = $clean($in);
    if ($expected === null) {
        $check("ตัด $name", stripos($out, 'srcdoc') === false && stripos($out, '<script') === false, $out);
    } else {
        $check("ตัด $name", $out === $expected, $out);
    }
}

// การจัดรูปแบบของ editor ต้องอยู่ครบ
$keep = [
    'ข้อความไทยและแท็กพื้นฐาน' => '<p><b>ตัวหนา</b> <i>เอียง</i> <u>ขีดเส้น</u> ภาษาไทย</p>',
    'style ธรรมดา' => '<p style="text-align:center;color:#ff0000">c</p>',
    'ลิงก์ http(s) mailto tel ../ และ query' => '<a href="https://example.com/a?b=1&amp;c=2">a</a><a href="mailto:a@example.com">m</a><a href="tel:+6621234567">t</a><a href="../page?t=12:30">r</a>',
    'รูป data:image' => '<img src="data:image/png;base64,iVBORw0KGgo=" alt="">',
    'รูปและขนาด' => '<img src="https://example.com/a.jpg" alt="a" width="100" height="50">',
    'ตาราง' => '<table border="1"><tbody><tr><td colspan="2" style="text-align:center">t</td></tr></tbody></table>',
    'รายการ' => '<ul><li>a</li><li>b</li></ul><ol start="3"><li>c</li></ol>',
    'font ของ editor' => '<font color="#00f" face="Tahoma">f</font>',
    'heading blockquote pre code' => '<h2>h</h2><blockquote>q</blockquote><pre><code>x &lt; y</code></pre>',
    'video source track wbr picture' => '<video controls><source src="a.mp4" type="video/mp4"><track src="b.vtt"></video><p>a<wbr>b</p><picture><source srcset="a.webp"><img src="a.jpg"></picture><p>z</p>',
];
foreach ($keep as $name => $html) {
    $out = $clean($html);
    $check("คง $name", $out === $html, $out);
}

// YouTube/Vimeo ยังฝังได้ และได้ sandbox
$yt = $clean('<iframe src="https://www.youtube.com/embed/abc123" width="560" height="315" allowfullscreen></iframe>');
$check('คง iframe YouTube และใส่ sandbox', strpos($yt, 'src="https://www.youtube.com/embed/abc123"') !== false && strpos($yt, 'sandbox="') !== false, $yt);
$vimeo = $clean('<iframe src="https://player.vimeo.com/video/76979871"></iframe>');
$check('คง iframe Vimeo', strpos($vimeo, 'player.vimeo.com/video/76979871') !== false, $vimeo);
$maps = \Kotchasan\Text::cleanHtml('<iframe src="https://www.google.com/maps/embed?pb=x"></iframe>', ['www.google.com/maps/embed']);
$check('เพิ่ม host ของ iframe ได้ด้วยอาร์กิวเมนต์ที่สอง', strpos($maps, 'www.google.com/maps/embed') !== false, $maps);

// target=_blank ได้ rel=noopener noreferrer
$blank = $clean('<a href="https://example.com/" target="_blank">x</a>');
$check('target="_blank" ได้ rel="noopener noreferrer"', strpos($blank, 'rel="noopener noreferrer"') !== false, $blank);

// แท็กที่ไม่ปิดหรือ </body> หลงมาไม่ทำให้เนื้อหาหาย
$broken = $clean('<p>a<b>b</p></div></body><p>c</p>');
$check('มาร์กอัปเสียไม่ทำเนื้อหาส่วนหลังหาย', strpos($broken, 'c</p>') !== false && strpos($broken, 'a') !== false, $broken);

$check('สตริงว่างคืนสตริงว่าง', $clean('') === '' && $clean(null) === '');

// InputItem::cleanHtml = ทำความสะอาด แล้วตามด้วย detail() (แทน ->detail() ได้ตรงตัว)
$item = new \Kotchasan\Http\InputItem('<p onclick="x()">{LNG_Hello} \\o/</p><script>a()</script>');
$check('InputItem::cleanHtml ตัดสคริปต์แล้วแปลง { } \\ แบบ detail()', $item->cleanHtml() === '<p>&#x007B;LNG_Hello&#x007D; &#92;o/</p>', $item->cleanHtml());
$items = new \Kotchasan\Http\InputItem(['th' => '<p onclick="x()">ไทย</p>', 'en' => '<p>en</p><script>a()</script>']);
$check('InputItem::cleanHtml ทำกับอาร์เรย์ทีละภาษา', $items->cleanHtml() === ['th' => '<p>ไทย</p>', 'en' => '<p>en</p>'], json_encode($items->cleanHtml(), JSON_UNESCAPED_UNICODE));

// $request->post('detail', [])->cleanHtml() — ค่าที่ส่งมาเป็น array (แยกภาษา) ได้ Inputs ไม่ใช่ InputItem
// และ Inputs::__call เรียกได้เฉพาะเมธอดใน allowedMethods (gcms.in.th document/write ล้มด้วยเหตุนี้)
try {
    $inputs = new \Kotchasan\Http\Inputs(['th' => '<p onclick="x()">ไทย</p>', 'en' => '<p>en</p><script>a()</script>']);
    $out = $inputs->cleanHtml();
    $check('Inputs::cleanHtml (post ที่เป็น array) ใช้ได้', $out === ['th' => '<p>ไทย</p>', 'en' => '<p>en</p>'], json_encode($out, JSON_UNESCAPED_UNICODE));
} catch (\Throwable $e) {
    $check('Inputs::cleanHtml (post ที่เป็น array) ใช้ได้', false, $e->getMessage());
}
$_POST = ['detail' => ['th' => '<p>a</p><script>x()</script>']];
try {
    $request = new \Kotchasan\Http\Request();
    $out = $request->post('detail', [])->cleanHtml();
    $check('$request->post(\'detail\', [])->cleanHtml() ใช้ได้', $out === ['th' => '<p>a</p>'], json_encode($out, JSON_UNESCAPED_UNICODE));
} catch (\Throwable $e) {
    $check('$request->post(\'detail\', [])->cleanHtml() ใช้ได้', false, $e->getMessage());
}

// ---------------------------------------------------------------------------
// ทั้งสองตัวกรอง: URL และ entity ต้องกลับมาเหมือนเดิม (libxml เข้ารหัส href/src เป็น %XX
// และไม่รู้จัก entity ของ HTML5 — &dollar; เคยกลับมาเป็นข้อความ "&dollar;")
// ---------------------------------------------------------------------------
$plain = function ($html) {
    return str_replace(['&#x007B;', '&#x007D;', '&#92;'], ['{', '}', '\\'], $html);
};
$url = '<p><img alt="a" src="{WEBURL}datas/image/ภาพ ที่ 1.jpg" width="10"><a class="x" href="{WEBURL}file/เอกสาร.pdf?a=1&amp;b={x}">ลิงก์</a><a name="หัวข้อ 1"></a></p>';
$check('cleanHtml ไม่เข้ารหัส {WEBURL} และชื่อไฟล์ไทยใน href/src', $clean($url) === $url, $clean($url));
$check('detail(false) ไม่เข้ารหัส {WEBURL} และชื่อไฟล์ไทยใน href/src/action', $plain(\Kotchasan\Text::detail($url.'<form action="{WEBURL}ค้นหา"><input name="q"></form>', false)) === $url.'<form action="{WEBURL}ค้นหา"><input name="q"></form>', \Kotchasan\Text::detail($url, false));
$check('entity ของ HTML5 (&dollar; &lpar;) กลับมาเป็นตัวอักษร ไม่ใช่ข้อความ &dollar;', $clean('<p>&dollar;5 &lpar;x&rpar; &amp; &lt;b&gt;</p>') === '<p>$5 (x) &amp; &lt;b&gt;</p>', $clean('<p>&dollar;5 &lpar;x&rpar; &amp; &lt;b&gt;</p>'));
$check('attribute ที่ libxml ตั้งชื่อแปลก (c"x") ไม่ทำให้ล้ม', strpos($clean('<div c"false"="" class="z"><a href="x">l</a></div>'), '<a href="x">l</a>') !== false, $clean('<div c"false"="" class="z"><a href="x">l</a></div>'));

// ---------------------------------------------------------------------------
// detail(): เนื้อหาจาก editor เก็บตามที่เขียน — เจ้าของเลือก 2026-11-11 (เว็บที่โพสต์ AdSense มีจริง)
// ---------------------------------------------------------------------------
$article = '<style>.box > a { color: red }</style><div class="box" onclick="play()">x</div>'
    .'<ins class="adsbygoogle" data-ad-client="ca-pub-1"></ins><script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>'
    .'<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>'
    .'<form action="/s"><input name="q"><button onclick="go()">Go</button></form><a href="javascript:void(0)">l</a><!-- ad --><iframe src="https://drive.google.com/file/d/1/preview"></iframe>';
$check('detail() เก็บ style script ฟอร์ม handler และ iframe ครบทุกตัวอักษร', $plain(\Kotchasan\Text::detail($article)) === $article, \Kotchasan\Text::detail($article));
$check('detail() ยังตัด PHP tag และเข้ารหัส { } \\', \Kotchasan\Text::detail('<p>{a} \\ <?php echo 1; ?>b</p>') === '<p>&#x007B;a&#x007D; &#92; b</p>', \Kotchasan\Text::detail('<p>{a} \\ <?php echo 1; ?>b</p>'));

// ---------------------------------------------------------------------------
// detail(false): ตัดเฉพาะสิ่งที่รัน JavaScript ได้ ที่เหลืออยู่ครบ
// ---------------------------------------------------------------------------
$noJs = function ($html) use ($plain) {
    return $plain(\Kotchasan\Text::detail($html, false));
};
$expected = '<style>.box > a { color: red }</style><div class="box">x</div>'
    .'<ins class="adsbygoogle" data-ad-client="ca-pub-1"></ins>'
    .'<form action="/s"><input name="q"><button>Go</button></form><a>l</a><!-- ad --><iframe src="https://drive.google.com/file/d/1/preview"></iframe>';
$check('detail(false) ตัด script, on*, javascript: และคง style ฟอร์ม comment iframe ทุก host', $noJs($article) === $expected, $noJs($article));

$removed = [
    'script' => ['<p>a</p><script>alert(1)</script>', '<p>a</p>'],
    'onerror' => ['<img src="x.png" onerror="alert(1)">', '<img src="x.png">'],
    'javascript: ที่มี entity และ tab' => ['<a href="jav&Tab;ascript&colon;alert(1)">x</a>', '<a>x</a>'],
    'data:text/html' => ['<a href="data:text/html,x">x</a>', '<a>x</a>'],
    'srcdoc' => ['<iframe srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;" src="https://e.example/"></iframe>', '<iframe src="https://e.example/"></iframe>'],
    'formaction javascript:' => ['<form><button formaction="javascript:alert(1)">b</button></form>', '<form><button>b</button></form>'],
    'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://e.example/"><p>m</p>', '<meta content="0;url=https://e.example/"><p>m</p>'],
    'base' => ['<base href="https://e.example/"><p>b</p>', '<p>b</p>'],
    'object/embed' => ['<object data="x.swf"></object><embed src="x.swf"><p>o</p>', '<p>o</p>'],
    'svg ทั้งก้อน' => ['<svg><style><img src=x onerror=alert(1)></style></svg><p>s</p>', '<p>s</p>'],
    'math ทั้งก้อน' => ['<math><mtext><img src=x onerror=alert(1)></mtext></math><p>m</p>', '<p>m</p>'],
    'noscript ทั้งก้อน' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript><p>n</p>', '<p>n</p>'],
    'เนื้อหาใน iframe' => ['<iframe src="https://e.example/"><p title="</iframe><img src=x onerror=alert(1)>"></iframe>', '<iframe src="https://e.example/"></iframe>'],
    'style ที่ปิดไม่เหมือนเบราว์เซอร์' => ['<style></style ><img src=x onerror=alert(1)></style><p>t</p>', '<style></style><img src="x"><p>t</p>'],
    'style expression' => ['<style>a{x:expression(alert(1))}</style><p style="width:expression(alert(1))">e</p>', '<p>e</p>'],
    'comment ที่เบราว์เซอร์อ่านต่างออกไป' => ['<p>c</p><!--><img src=x onerror=alert(1)>-->', '<p>c</p>'],
    'attribute ของ body ที่หลงมา' => ['<body onload="alert(1)"><p>b</p>', '<p>b</p>'],
    'handler ในแท็กที่ไม่ปิด' => ['<p>ok</p><img src=x onerror=alert(1)', '<p>ok</p><img src="x">']
];
foreach ($removed as $name => [$in, $want]) {
    $out = $noJs($in);
    $check("detail(false) ตัด $name", $out === $want, $out);
}

$kept = [
    'style พร้อม @media และ >' => '<style>@media (max-width: 600px) { .a > b { display: none } } .c::before { content: "<" }</style>',
    'link ของ CSS และ meta ธรรมดา' => '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun"><meta itemprop="name" content="x">',
    'ฟอร์มและ control' => '<form action="/search" method="get"><label>q <input type="text" name="q" value="a"></label><select name="s"><option value="1" selected>one</option></select><textarea name="t">a &lt;b&gt;</textarea><button type="submit" formaction="/go">Go</button></form>',
    'iframe ของ Google Drive/Forms พร้อม sandbox ที่เขียนมา' => '<iframe src="https://docs.google.com/forms/d/e/x/viewform?embedded=true" width="640" height="800" sandbox="allow-forms allow-scripts">Loading</iframe>',
    'ลิงก์ ftp และ scheme อื่นที่ไม่รันโค้ด' => '<a href="ftp://ftp.example.com/a.zip">f</a><a href="line://ti/p/@x">l</a><a href="mailto:a@example.com">m</a>',
    'รูป data:image และ video' => '<img src="data:image/png;base64,iVBORw0KGgo=" alt=""><video controls poster="p.jpg"><source src="a.mp4" type="video/mp4"></video>',
    'class id data-* และ style attribute' => '<div id="a" class="b c" data-x="1" style="position: fixed; top: 0; background: url(a.png)">d</div>',
    'comment ธรรมดา' => '<p>a</p><!-- more --><p>b</p>'
];
foreach ($kept as $name => $html) {
    $want = str_replace('>Loading</iframe>', '></iframe>', $html);
    $out = $noJs($html);
    $check("detail(false) คง $name", $out === $want, $out);
}

// ธงส่งผ่าน InputItem และ Inputs (ค่าที่เป็น array)
$_POST = ['one' => '<p onclick="x()">a</p><script>b()</script><style>p{}</style>', 'many' => ['th' => '<b onclick="x()">ไทย</b>', 'en' => '<script>a()</script><i>en</i>']];
$request = new \Kotchasan\Http\Request();
$check('$request->post()->detail() เก็บ script', $request->post('one')->detail() === '<p onclick="x()">a</p><script>b()</script><style>p&#x007B;&#x007D;</style>', $request->post('one')->detail());
$check('$request->post()->detail(false) ตัด script คง style', $request->post('one')->detail(false) === '<p>a</p><style>p&#x007B;&#x007D;</style>', $request->post('one')->detail(false));
$check('$request->post(\'x\', [])->detail(false) ทำทีละรายการ', $request->post('many', [])->detail(false) === ['th' => '<b>ไทย</b>', 'en' => '<i>en</i>'], json_encode($request->post('many', [])->detail(false), JSON_UNESCAPED_UNICODE));

$failed = count(array_filter($results, function ($r) {return !$r;}));
echo "\n".($failed ? "$failed of ".count($results).' checks failed' : 'all '.count($results).' checks passed')."\n";
exit($failed ? 1 : 0);
