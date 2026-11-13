<?php
/**
 * install/cli-testhardening.php — ช่องโหว่และบั๊กของ Kotchasan จากการสแกน 2026-11-12 ต้องไม่กลับมา
 *
 * ทุกข้อคือ PoC ที่เคยได้ผลกับโค้ดก่อนแก้ (kb handoff adminframework, H1-H4 M1-M9 L1-L8)
 *   H1 where(['token', ':qb_p0']) ชี้ไปค่าของเงื่อนไขอื่น · '?' ไม่ถูก bind
 *   H2 Language::save() เขียนค่าลงไฟล์ PHP ดิบ ๆ (RCE) · ชื่อภาษาเป็น path ได้
 *   H3 CSRF token "login" ลบ $_SESSION['login']
 *   H4 Http\Middleware\JwtMiddleware รับ secret ว่าง · HS512 ออกได้แต่ตรวจไม่ได้ · ไม่ตรวจ iss/aud
 *   M1 Sql::IN('x', ['U.password']) กลายเป็นชื่อคอลัมน์
 *   M2 operator / key ของ set() values() เขียนลง SQL ตรง ๆ
 *   M3-M9 L1-L8 ตามหัวข้อด้านล่าง
 *
 * ROOT_PATH เป็นโฟลเดอร์ชั่วคราว (ฐาน SQLite และไฟล์ภาษาอยู่ในนั้น) ไม่แตะไฟล์ของโปรเจ็ค
 *
 * ใช้:  php install/cli-testhardening.php
 * exit code 0 = ผ่านทั้งหมด
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$project = str_replace(['\\', 'install/cli-testhardening.php'], ['/', ''], __FILE__);
$work = sys_get_temp_dir().'/kotchasan-hardening-'.getmypid().'/';
@mkdir($work.'language', 0700, true);
@mkdir($work.'settings', 0700, true);
register_shutdown_function(function () use ($work) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($work);
});

define('ROOT_PATH', $work);
define('APP_PATH', $work);
define('BASE_PATH', '/');
define('DATA_FOLDER', 'datas/');
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
include $project.'Kotchasan/load.php';

use Kotchasan\Database;
use Kotchasan\Database\Sql;
use Kotchasan\Http\InputItem;
use Kotchasan\Http\Request;
use Kotchasan\Password;
use Kotchasan\Text;

$results = [];
$check = function ($name, $ok, $detail = '') use (&$results) {
    $results[] = (bool) $ok;
    echo ($ok ? '  ✔ ' : '  ✘ ').$name.($ok || $detail === '' ? '' : '  — '.$detail)."\n";
};
$throws = function (callable $fn, $class = \Throwable::class) {
    try {
        $fn();
    } catch (\Throwable $e) {
        return $e instanceof $class;
    }

    return false;
};

// ---------------------------------------------------------------------------
echo "H1 / M2  QueryBuilder\n";
Database::config(['default' => ['driver' => 'sqlite', 'database' => $work.'t.sqlite', 'prefix' => '']]);
$db = Database::create();
$db->raw('CREATE TABLE user (id INTEGER PRIMARY KEY, username TEXT, token TEXT, status INTEGER, note TEXT)');
$db->insert('user')->values(['id' => 1, 'username' => 'admin', 'token' => 'SECRET', 'status' => 1, 'note' => ''])->execute();
$db->insert('user')->values(['id' => 2, 'username' => 'bob', 'token' => 'bobtok', 'status' => 0, 'note' => ''])->execute();

$rows = $db->select('id')->from('user')->where([['username', 'bob'], ['token', ':qb_p0']])->fetchAll(true);
$check("where(['token', ':qb_p0']) เป็นค่า ไม่ชี้ไปค่าของ username", $rows === [], json_encode($rows));
$rows = $db->select('id')->from('user')->where(['username', 'admin'])->where(['token', ':qb_p0'])->fetchAll(true);
$check('where() ต่อกันด้วย :qb_p0 ไม่ได้แถวของ admin', $rows === [], json_encode($rows));
$db->insert('user')->values(['id' => 3, 'username' => '?', 'token' => ':x', 'status' => 0, 'note' => ''])->execute();
$rows = $db->select('id')->from('user')->where(['username', '?'])->where(['token', ':x'])->fetchAll(true);
$check("ค่า '?' และ ':x' ถูก bind เป็นค่า", $rows === [['id' => 3]], json_encode($rows));
$rows = $db->select('id')->from('user')->where(['id', Sql::raw(':want')])->fetchAll(true, [':want' => 2]);
$check("placeholder ของ execute() เขียนเป็น Sql::raw(':name')", $rows === [['id' => 2]], json_encode($rows));
$rows = $db->select('id')->from('user')->where([['username', 'bob'], ['note', '']])->fetchAll(true);
$check("['note', ''] ยังเป็นเงื่อนไข", $rows === [['id' => 2]], json_encode($rows));

$check("operator '= 1 OR 1=1 --' ถูกปฏิเสธ", $throws(function () use ($db) {
    $db->select('id')->from('user')->where(['id', '= 1 OR 1=1 --', 5])->fetchAll(true);
}, \InvalidArgumentException::class));
$rows = $db->select('id')->from('user')->where(['id', 'not in', [1, 3]])->fetchAll(true);
$check("operator 'not in' (ตัวเล็ก) ยังใช้ได้", $rows === [['id' => 2]], json_encode($rows));
$rows = $db->select('id')->from('user')->where(['username', 'LIKE', 'ad%'])->fetchAll(true);
$check('operator LIKE ยังใช้ได้', $rows === [['id' => 1]], json_encode($rows));
$check("having('status', '= 0 OR 1', 1) ถูกปฏิเสธ", $throws(function () use ($db) {
    $db->select('status')->from('user')->groupBy('status')->having('status', '= 0 OR 1', 1);
}, \InvalidArgumentException::class));
$rows = $db->select('status')->from('user')->groupBy('status')->having('status', '>=', 1)->fetchAll(true);
$check("having('status', '>=', 1) สามอาร์กิวเมนต์", $rows === [['status' => 1]], json_encode($rows));

$check("set(['status = 1, username' => ...]) ถูกปฏิเสธ", $throws(function () use ($db) {
    $db->update('user')->set(['status = 1, username' => 'x'])->where(['id', 2])->execute();
}, \InvalidArgumentException::class));
$check("values(['username) VALUES (...' => ...]) ถูกปฏิเสธ", $throws(function () use ($db) {
    $db->insert('user')->values(["username) VALUES ('a')-- " => 'x'])->execute();
}, \InvalidArgumentException::class));
$db->update('user')->set(['`note`' => 'n1', 'status' => 5])->where(['id', 2])->execute();
$row = $db->select('note', 'status')->from('user')->where(['id', 2])->first(true);
$check('set() คีย์ `note` และ status ยังใช้ได้', $row == ['note' => 'n1', 'status' => 5], json_encode($row));
$db->insert('user')->values(['id' => 4, 'username' => 'b o b', 'token' => '', 'status' => 7, 'note' => ''])->execute();
// Sql ที่มี binding ของตัวเอง ('b o b' มีช่องว่างจึงถูก bind): ชื่อ placeholder ต้องเปลี่ยนทั้งใน SQL และใน binding
$db->update('user')->set(['status' => Sql::NEXT('status', 'user', ['username', 'b o b'])])->where(['id', 2])->execute();
$row = $db->select('status')->from('user')->where(['id', 2])->first(true);
$check('set() ค่าเป็น Sql ที่มี binding', $row == ['status' => 8], json_encode($row));
$db->insert('user')->rows([
    ['id' => 10, 'username' => 'u10', 'token' => 't10', 'status' => 0, 'note' => ''],
    ['note' => '', 'status' => 0, 'token' => 't11', 'username' => 'u11', 'id' => 11]
])->execute();
$row = $db->select('username', 'token')->from('user')->where(['id', 11])->first(true);
$check('rows() แถวที่เรียงคีย์ต่างกันลงคอลัมน์ถูก', $row == ['username' => 'u11', 'token' => 't11'], json_encode($row));

// ---------------------------------------------------------------------------
echo "M1  Sql::quoteValue\n";
$sql = Sql::IN('username', ['U.password', '`user`.`token`', 'bob']);
$check('Sql::IN ค่ารูปคอลัมน์ไม่กลายเป็นชื่อคอลัมน์', strpos($sql->toSql(), 'password"') === false && strpos($sql->toSql(), '`token`') === false, $sql->toSql());
$rows = $db->select('id')->from('user')->where(['username', 'IN', ['U.password', 'bob']])->fetchAll(true);
$check("where IN ['U.password', 'bob'] ได้เฉพาะ bob", $rows === [['id' => 2]], json_encode($rows));
$rows = $db->select('id')->from('user')->where(Sql::IN('username', ['U.password', 'bob']))->fetchAll(true);
$check("where(Sql::IN(...['U.password', 'bob'])) ได้เฉพาะ bob", $rows === [['id' => 2]], json_encode($rows));

// ---------------------------------------------------------------------------
echo "H2  Language::save\n";
$err = \Kotchasan\Language::save([['id' => 0, 'key' => "Hello's", 'th' => "x'.print('INJECTED').'\\"]], 'php');
ob_start();
$loaded = include $work.'language/th.php';
$out = ob_get_clean();
$check('ค่าที่มี quote ไม่ถูกรันตอน include', $err === '' && $out === '', $out);
$check('ค่าและคีย์อ่านกลับมาได้ตรงตัว', is_array($loaded) && ($loaded["Hello's"] ?? null) === "x'.print('INJECTED').'\\", var_export($loaded, true));
$err = \Kotchasan\Language::save([['id' => 0, 'key' => 'K', '../settings/pwned' => 'v']], 'php');
$check('ชื่อภาษา ../settings/pwned ถูกปฏิเสธ ไม่มีไฟล์ถูกเขียน', $err !== '' && !is_file($work.'settings/pwned.php'), $err);
$check("ชนิดไฟล์อื่นนอกจาก php/json ถูกปฏิเสธ", \Kotchasan\Language::save([['id' => 0, 'key' => 'K', 'th' => 'v']], 'php5') !== '');

// ---------------------------------------------------------------------------
echo "H3  CSRF token\n";
$_SESSION = ['login' => ['id' => 1, 'status' => 1], 'cart' => ['a' => 1]];
$ok = Request::validateCsrfToken('login');
$check("validateCsrfToken('login') ไม่แตะ \$_SESSION['login']", !$ok && isset($_SESSION['login']));
$token = (new Request(false))->generateCsrfToken();
$check('token ที่สร้างเองยังใช้ได้', Request::validateCsrfToken($token));
(new Request(false))->removeCsrfToken('cart');
$check("removeCsrfToken('cart') ไม่ลบ \$_SESSION['cart']", isset($_SESSION['cart']));
$_SESSION = [];

// ---------------------------------------------------------------------------
echo "H4  JWT\n";
$check('JwtMiddleware(\'\') ถูกปฏิเสธ', $throws(function () {
    new \Kotchasan\Http\Middleware\JwtMiddleware('');
}, \InvalidArgumentException::class));
$mw = new \Kotchasan\Http\Middleware\JwtMiddleware('secret', 'HS512');
$payload = $mw->decodeToken($mw->generateToken(['sub' => 1]));
$check('HS512 ที่ออกเองตรวจผ่าน', ($payload['sub'] ?? null) === 1);
$other = new \Kotchasan\Http\Middleware\JwtMiddleware('secret', 'HS256');
$check('token HS512 ไม่ผ่านตัวตรวจ HS256', $throws(function () use ($other, $mw) {
    $other->decodeToken($mw->generateToken(['sub' => 1]));
}));
$aud = new \Kotchasan\Http\Middleware\JwtMiddleware('secret', ['issuer' => 'me', 'audience' => 'app']);
$check('ตรวจ iss/aud: token ของตัวเองผ่าน', ($aud->decodeToken($aud->generateToken(['sub' => 2]))['sub'] ?? null) === 2);
$check('ตรวจ iss/aud: token ที่ไม่มี iss ไม่ผ่าน', $throws(function () use ($aud) {
    $aud->decodeToken(\Kotchasan\Jwt::encode(['sub' => 2, 'aud' => 'app'], 'secret'));
}));
$check('Jwt::decode exp ที่เป็นข้อความถูกปฏิเสธ', \Kotchasan\Jwt::decode(\Kotchasan\Jwt::encode(['sub' => 1, 'exp' => 'soon'], 's'), 's') === null);
$check('Jwt::decode leeway', \Kotchasan\Jwt::decode(\Kotchasan\Jwt::encode(['sub' => 1, 'exp' => time() - 5], 's'), 's') === null
    && is_array(\Kotchasan\Jwt::decode(\Kotchasan\Jwt::encode(['sub' => 1, 'exp' => time() - 5], 's'), 's', ['HS256'], 30)));

// ---------------------------------------------------------------------------
echo "M3  Text::highlighter\n";
$html = Text::highlighter('[url]javascript:alert(1)[/url] [url=javascript&#58alert(1)]a[/url] [url=http://x.com/" onmouseover="y]b[/url]');
$check('ลิงก์ javascript: (ทั้งแบบ entity) ไม่เป็น <a>', stripos($html, 'href="javascript') === false && stripos($html, 'href="javascript&') === false, $html);
$check('href ที่มี " ถูก escape', strpos($html, '" onmouseover="') === false, $html);
$html = Text::highlighter('[url]https://example.com/a?b=1&amp;c=2[/url] [url=/page]p[/url]');
$check('ลิงก์ปกติยังทำงาน', strpos($html, '<a href="https://example.com/a?b=1&amp;c=2" target="_blank">') !== false
    && strpos($html, '<a href="'.WEB_URL.'page" target="_blank">p</a>') !== false, $html);

// ---------------------------------------------------------------------------
echo "M4  URL\n";
$check("InputItem->url() ปฏิเสธ javascript:// data:// file://", (new InputItem('javascript://%0aalert(1)'))->url() === ''
    && (new InputItem('data://text/plain,x'))->url() === '' && (new InputItem('file:///etc/passwd'))->url() === '');
$check('InputItem->url() เก็บ https และ mailto', (new InputItem(' https://x.com/a '))->url() === 'https://x.com/a'
    && (new InputItem('mailto:a@b.co'))->url() === 'mailto:a@b.co');

// ---------------------------------------------------------------------------
echo "M5  Validator::isFileSafe\n";
file_put_contents($work.'pad.txt', str_repeat('A', 5000)."<?php system(\$_GET[1]);");
$check('PHP หลัง 4 KB แรกถูกจับ', !\Kotchasan\Validator::isFileSafe(['tmp_name' => $work.'pad.txt'], [], false));
file_put_contents($work.'pad.txt', str_repeat('A', 1048570)."<?php x();");
$check('PHP คร่อมขอบ 1 MB ถูกจับ', !\Kotchasan\Validator::isFileSafe(['tmp_name' => $work.'pad.txt'], [], false));
file_put_contents($work.'x.svg', '<svg xmlns="http://www.w3.org/2000/svg" ONLOAD=alert(1)><text>x</text></svg>');
$check('SVG ONLOAD= (ตัวใหญ่ ไม่มี quote) ถูกจับ', \Kotchasan\Validator::validateImage(['tmp_name' => $work.'x.svg', 'name' => 'x.svg', 'size' => 1], ['svg'], 0, 0, false) === false);
file_put_contents($work.'ok.txt', "สวัสดี hello\n1,2,3\n");
$check('ข้อความธรรมดาผ่าน', \Kotchasan\Validator::isFileSafe(['tmp_name' => $work.'ok.txt'], [], false));

// ---------------------------------------------------------------------------
echo "M6 / L4 / L5  auth middleware\n";
$called = false;
$bearer = new \Kotchasan\Http\Middleware\BearerTokenAuthMiddleware(function ($token) use (&$called) {
    $called = true;

    return false;
});
$response = $bearer->handle(new Request(true));
$check('Bearer ไม่มี token: ไม่เรียก callback ตอบ 401', !$called && $response->getStatusCode() === 401);
$basic = \Kotchasan\Http\Middleware\BasicAuthMiddleware::withTokens(['abc', 0]);
$cb = (new ReflectionClass($basic))->getProperty('callback');
$cb->setAccessible(true);
$fn = $cb->getValue($basic);
$check("withTokens เทียบแบบ strict ('0' ไม่ตรงกับ 0, 'abc' ตรง)", $fn('u', 'abc') === true && $fn('u', '0') === false && $fn('u', '') === false);

$digest = new \Kotchasan\Http\Middleware\DigestAuthMiddleware(function ($user) {
    return $user === 'alice' ? md5('alice:API:wonder') : null;
}, 'API', 'test-secret');
$_SERVER['REQUEST_URI'] = '/api/x?y=1';
unset($_SERVER['HTTP_AUTHORIZATION']);
$challenge = $digest->handle(new Request(true))->getHeaderLine('WWW-Authenticate');
preg_match('/nonce="([^"]+)"/', $challenge, $n);
preg_match('/opaque="([^"]+)"/', $challenge, $o);
$authorize = function ($uri, $nonce) use ($o) {
    $ha2 = md5('GET:'.$uri);
    $resp = md5(md5('alice:API:wonder').':'.$nonce.':00000001:abc:auth:'.$ha2);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Digest username="alice", realm="API", nonce="'.$nonce.'", uri="'.$uri.'", qop=auth, nc=00000001, cnonce="abc", response="'.$resp.'", opaque="'.$o[1].'"';
};
$authorize('/api/x?y=1', $n[1]);
$check('Digest ที่ถูกต้องผ่าน', (new Request(true))->getAttribute('x') === null && $digest->handle(new Request(true)) instanceof Request);
$authorize('/api/other', $n[1]);
$check('Digest ที่ uri ไม่ตรงกับคำขอไม่ผ่าน', !($digest->handle(new Request(true)) instanceof Request));
$authorize('/api/x?y=1', time().'.'.str_repeat('a', 64));
$check('Digest nonce ที่ไม่ได้ออกเองไม่ผ่าน', !($digest->handle(new Request(true)) instanceof Request));
$old = (time() - 3600).'.'.hash_hmac('sha256', (time() - 3600).':API', hash_hmac('sha256', 'Kotchasan\\Http\\Middleware\\DigestAuthMiddleware nonce', 'test-secret', true));
$authorize('/api/x?y=1', $old);
$stale = $digest->handle(new Request(true));
$check('Digest nonce เก่าไม่ผ่าน และตอบ stale=true', !($stale instanceof Request) && strpos($stale->getHeaderLine('WWW-Authenticate'), 'stale=true') !== false);
unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REQUEST_URI']);

// ---------------------------------------------------------------------------
echo "M7  ค่าที่เป็น array ไม่ทำให้ล้ม\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['_method'] = ['x'];
$check('_method[]=x ไม่ทำให้ Request ล้ม', !$throws(function () {
    (new Request(true))->getMethod();
}));
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = [];
$_GET['lang'] = ['x'];
$check('?lang[]=x ไม่ทำให้ Language ล้ม', !$throws(function () {
    $ref = new ReflectionProperty(\Kotchasan\KBase::class, 'request');
    $ref->setAccessible(true);
    $ref->setValue(null, new Request(true));
    $languages = new ReflectionProperty(\Kotchasan\Language::class, 'languages');
    $languages->setAccessible(true);
    $languages->setValue(null, null);
    \Kotchasan\Language::get('x');
}));
$_GET = [];

// ---------------------------------------------------------------------------
echo "M8  Email / PHPMailer\n";
$check('ตรวจใบรับรอง SMTP เป็นค่าเริ่มต้น เว้นเครื่องนี้', \Kotchasan\Email::verifyServerCertificate('smtp.example.com')
    && \Kotchasan\Email::verifyServerCertificate('tls://smtp.example.com:587')
    && !\Kotchasan\Email::verifyServerCertificate('localhost') && !\Kotchasan\Email::verifyServerCertificate('127.0.0.1:25')
    && !\Kotchasan\Email::verifyServerCertificate('::1') && !\Kotchasan\Email::verifyServerCertificate('[::1]:25'));
include_once $project.'Kotchasan/PHPMailer/class.phpmailer.php';
$mail = new PHPMailer();
$mail->msgHTML('<img src="/etc/passwd"><img src="../../../etc/hosts"><img src="//host/share/x.png">');
$check('msgHTML ไม่แนบไฟล์ในเครื่องเมื่อไม่ระบุ basedir', count($mail->getAttachments()) === 0, json_encode(array_column($mail->getAttachments(), 0)));
file_put_contents($work.'logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$mail = new PHPMailer();
$mail->msgHTML('<img src="logo.png"><img src="../etc/passwd">', $work);
$check('msgHTML แนบได้เฉพาะไฟล์ใน basedir', count($mail->getAttachments()) === 1);
$check("validateAddress ไม่เรียกฟังก์ชันจากชื่อที่เป็นสตริง", PHPMailer::validateAddress('a@b.co', 'php') === true);
$check('addAttachment ปฏิเสธ phar:// และ UNC', !(new PHPMailer())->addAttachment('phar://x.phar/a') && !(new PHPMailer())->addAttachment('\\\\host\\share\\a'));

// ---------------------------------------------------------------------------
echo "M9  Template\n";
$check("'\$100' และ '\\1' ในค่าคงเป็นข้อความ", \Kotchasan\Template::pregReplace('/{CONTENT}/', 'Price $100 and \\1 ${2}', 'A {CONTENT} B') === 'A Price $100 and \\1 ${2} B');
$check('pattern ที่มีกลุ่มยังอ้าง $1 ได้', \Kotchasan\Template::pregReplace('/(<head>)(<\/head>)/', '$1X$2', '<head></head>') === '<head>X</head>');

// ---------------------------------------------------------------------------
echo "L1 / L2  Uri, Text\n";
$check('Uri::withParams กับ query ซ้อนสองชั้น', !$throws(function () {
    (string) \Kotchasan\Http\Uri::createFromUri('http://a/b?x[a][b]=1&y[]=2')->withParams(['page' => 2]);
}));
$check('Text::description ข้อความที่ไม่ใช่ UTF-8 ไม่หายทั้งก้อน', Text::description("abc \xff def") !== '' && Text::keywords("abc \xff def") !== '');

// ---------------------------------------------------------------------------
echo "L3  รหัสผ่าน\n";
$check('Text::password เก็บทุกอักขระ ตัดเฉพาะอักขระควบคุม', Text::password("p@ss word%^~,;: ไก่ไข่\x00\n") === 'p@ss word%^~,;: ไก่ไข่');
$key = 'k1';
$oldHash = Password::hash(Password::legacyInput('p@ss word%^~'), $key);
$ok = Password::verify('p@ss word%^~', $oldHash, '', $key, $outdated);
$check('hash ของรหัสแบบถูกตัด (รุ่นก่อน) เข้าไม่ได้ และบอกว่าเป็นรูปแบบเก่า ($outdated)', !$ok && $outdated === true);
$newHash = Password::hash('p@ss word%^~', $key);
$ok = Password::verify('p@ss word%^~', $newHash, '', $key, $outdated);
$check('hash ของรหัสเต็มเข้าได้ ไม่ใช่รูปแบบเก่า', $ok && $outdated === false);
$check('hash รหัสเต็ม: รหัสที่ตัดแล้วเหมือนกันแต่ต่างจริงเข้าไม่ได้', !Password::verify('p@ssword', $newHash, '', $key) && !Password::verify('p@ss  word%^~!', $newHash, '', $key));
$check('legacyInput คืน null เมื่อไม่มีอะไรถูกตัด', Password::legacyInput('Secret-1234') === null && Password::legacyInput('a b') === 'ab');
$code = Text::generateRandomString(32, 'ab');
$check('generateRandomString ใช้เฉพาะอักขระที่กำหนด', strlen($code) === 32 && trim($code, 'ab') === '' && Text::generateRandomString(4, '') === '');

// ---------------------------------------------------------------------------
echo "L6 / L7  ไฟล์ส่งออก, CORS\n";
$cd = \Kotchasan\Http\Response::contentDisposition("a\"b\r\nc;ไทย.csv");
$check('Content-Disposition ไม่มี " หรือขึ้นบรรทัดในชื่อ', $cd === 'attachment; filename="a_bc;___.csv"; filename*=UTF-8\'\''.rawurlencode('a"bc;ไทย.csv'), $cd);
$check('Xls::cell escape ค่าและแอตทริบิวต์', \Kotchasan\Xls::cell('td', ['value' => '<b>x</b>', 'style' => '"><script>', 'on"x' => 'y']) === '<td style="&quot;&gt;&lt;script&gt;">&lt;b&gt;x&lt;/b&gt;</td>');
$check('Csv::escapeFormula', \Kotchasan\Csv::escapeFormula('=HYPERLINK("x")') === "'=HYPERLINK(\"x\")" && \Kotchasan\Csv::escapeFormula('@SUM(1)') === "'@SUM(1)"
    && \Kotchasan\Csv::escapeFormula('-2+3+cmd|x') === "'-2+3+cmd|x" && \Kotchasan\Csv::escapeFormula('+66 81 234 5678') === '+66 81 234 5678'
    && \Kotchasan\Csv::escapeFormula('-12.5') === '-12.5' && \Kotchasan\Csv::escapeFormula(5) === 5);
$cors = (new \Kotchasan\Http\Response())->setCorsHeaders();
$check('setCorsHeaders() ไม่ส่ง Allow-Origin: * เอง', !$cors->hasHeader('Access-Control-Allow-Origin')
    && (new \Kotchasan\Http\Response())->setCorsHeaders(['Access-Control-Allow-Origin' => 'https://a.b'])->getHeaderLine('Access-Control-Allow-Origin') === 'https://a.b');

// ---------------------------------------------------------------------------
echo "load.php error handler\n";
// It replaces PHP's handler when DEBUG != 2: a warning silenced with @ must not reach the log
$probe = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
    'define("ROOT_PATH", '.var_export($work, true).'); define("LOG_DESTINATION", "LOG_FILE"); $_SERVER["HTTP_HOST"] = "localhost";'
    .' include '.var_export($project.'Kotchasan/load.php', true).'; @file_get_contents("/no/such/silenced"); file_get_contents("/no/such/shown");'
).' 2>&1');
$log = (string) @file_get_contents($work.'error_log.php');
$check('@ ไม่ถูกบันทึก ส่วน warning ปกติยังถูกบันทึก', strpos($log, 'silenced') === false && strpos($log, '/no/such/shown') !== false, $log);
// debug() left in the code: DEBUG 0 used to print <script>console.log(file : line)</script> after a JSON body
$debugOut = function ($level) use ($work, $project) {
    return (string) shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
        'define("ROOT_PATH", '.var_export($work, true).'); define("DEBUG", '.$level.'); $_SERVER["HTTP_HOST"] = "localhost";'
        .' include '.var_export($project.'Kotchasan/load.php', true).'; debug("probe"); \Kotchasan\Http\Response::makeJson(["ok" => true])->send();'
    ).' 2>&1');
};
$quiet = $debugOut(0);
$check('debug() เงียบเมื่อ DEBUG = 0 (JSON ไม่มี <script> ต่อท้าย) และยังทำงานเมื่อ DEBUG = 1', trim($quiet) === '{"ok":true}' && strpos($debugOut(1), '"_debug"') !== false, $quiet);

// ---------------------------------------------------------------------------
echo "Database::transaction()\n";
// It caught \Exception only: a TypeError in the callback left the transaction open
$tx = trim((string) shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
    'define("ROOT_PATH", '.var_export($work, true).'); $_SERVER["HTTP_HOST"] = "localhost";'
    .' include '.var_export($project.'Kotchasan/load.php', true).';'
    .' \Kotchasan\Database::config(["default" => ["driver" => "sqlite", "database" => ":memory:"]]);'
    .' $db = \Kotchasan\Database::create(); $db->raw("CREATE TABLE t (v INTEGER)");'
    .' try { $db->transaction(function ($db) { $db->raw("INSERT INTO t VALUES (1)"); strlen([]); }); } catch (\TypeError $e) {}'
    .' echo ($db->getConnection()->getDriver()->inTransaction() ? "open" : "closed")." ".$db->raw("SELECT COUNT(*) AS c FROM t")->fetch()->c;'
).' 2>&1'));
$check('TypeError ใน callback → rollback และปิด transaction', $tx === 'closed 0', $tx);

// ---------------------------------------------------------------------------
echo "Logger and LOG_DESTINATION\n";
// Logger::error()/warning()/info() always wrote error_log.php, whatever LOG_DESTINATION said
$logRun = function ($destination) use ($work, $project) {
    $root = $work.'logdest-'.$destination.'/';
    @mkdir($root, 0700, true);
    @unlink($root.'error_log.php');
    @unlink($root.'php.log');
    shell_exec(escapeshellarg(PHP_BINARY).' -d error_log='.escapeshellarg($root.'php.log').' -r '.escapeshellarg(
        'define("ROOT_PATH", '.var_export($root, true).'); define("LOG_DESTINATION", "'.$destination.'"); $_SERVER["HTTP_HOST"] = "localhost";'
        .' include '.var_export($project.'Kotchasan/load.php', true).'; \Kotchasan\Logger::error("probe-error"); \Kotchasan\Logger::info("probe-info");'
    ).' 2>&1');

    return [
        'file' => strpos((string) @file_get_contents($root.'error_log.php'), 'probe-error') !== false,
        'system' => strpos((string) @file_get_contents($root.'php.log'), 'probe-error') !== false && strpos((string) @file_get_contents($root.'php.log'), 'probe-info') !== false
    ];
};
$sys = $logRun('LOG_SYSTEM');
$file = $logRun('LOG_FILE');
$both = $logRun('LOG_BOTH');
$check('Logger::error()/info() ไปตาม LOG_DESTINATION (SYSTEM / FILE / BOTH)', !$sys['file'] && $sys['system'] && $file['file'] && !$file['system'] && $both['file'] && $both['system'], json_encode([$sys, $file, $both]));

// ---------------------------------------------------------------------------
echo "Curl protocols\n";
// cURL speaks file:// too: a URL from a user read files of the server
$secretFile = $work.'curl-secret.txt';
file_put_contents($secretFile, 'curl-secret-value');
$curl = new \Kotchasan\Curl();
$body = (string) $curl->get('file://'.$secretFile);
$check('Curl ไม่อ่าน file:// (รับเฉพาะ http/https ตามค่าเริ่มต้น)', strpos($body, 'curl-secret-value') === false && $curl->error() !== 0, $body);

// ---------------------------------------------------------------------------
echo "DB_CACHE\n";
// cache_expire is 0 in Kotchasan\Config, and CACHE_TTL 0 cached query results for ever
$ttl = trim((string) shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
    'define("ROOT_PATH", '.var_export($work.'nocfg/', true).'); define("DB_CACHE", true); $_SERVER["HTTP_HOST"] = "localhost";'
    .' include '.var_export($project.'Kotchasan/load.php', true).'; \Kotchasan::createWebApplication(); echo CACHE_TTL;'
).' 2>&1'));
$check('DB_CACHE โดยไม่ตั้ง cache_expire → CACHE_TTL 3600 (ไม่ใช่ 0 = ไม่หมดอายุ)', $ttl === '3600', $ttl);

// ---------------------------------------------------------------------------
echo "Sql dialect\n";
// Sql::$database_type stayed 'mysql' on every connection: NEXT()/POSITION() wrote backticks and LOCATE()
$dialect = trim((string) shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
    'define("ROOT_PATH", '.var_export($work, true).'); $_SERVER["HTTP_HOST"] = "localhost";'
    .' include '.var_export($project.'Kotchasan/load.php', true).';'
    .' \Kotchasan\Database::config(["default" => ["driver" => "sqlite", "database" => ":memory:"]]);'
    .' $db = \Kotchasan\Database::create(); $db->raw("CREATE TABLE t (id INTEGER, n TEXT)"); $db->raw("INSERT INTO t VALUES (4, \'abc\')");'
    .' echo \Kotchasan\Database\Sql::NEXT("id", "t", null, "next")->toSql()." | ";'
    .' echo $db->raw("SELECT ".\Kotchasan\Database\Sql::NEXT("id", "t", null, "next")->toSql().", ".\Kotchasan\Database\Sql::POSITION("b", "`n`", "p")->toSql()." FROM t")->fetch()->p;'
).' 2>&1'));
$check('Sql::NEXT()/POSITION() เขียนตามไดรเวอร์ของการเชื่อมต่อ (SQLite: "id", INSTR)', strpos($dialect, '`') === false && substr($dialect, -1) === '2', $dialect);

$failed = count(array_filter($results, function ($r) {return !$r;}));
echo "\n".($failed ? "$failed of ".count($results).' checks failed' : 'all '.count($results).' checks passed')."\n";
exit($failed ? 1 : 0);
