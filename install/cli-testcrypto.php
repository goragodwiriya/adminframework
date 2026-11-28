<?php
/**
 * install/cli-testcrypto.php — ค่าที่เข้ารหัสต้องแก้ไม่ได้ และที่เก็บชั่วคราวต้องไม่รับไฟล์ของคนอื่น
 * (Now.js audit 2026-11-10 ส่วนที่ HANDOFF ยังไม่ได้ตรวจ · เจ้าของสั่ง "แก้ไขที่ค้างทุกข้อ ให้สมบูรณ์")
 *
 * Kotchasan\Password::encode/decode เป็น AES-256-CBC ไม่มี MAC และ IV อยู่ในข้อความ —
 * ใครถือลิงก์ดาวน์โหลด (modules/download, id=<encode(json)>) แก้ IV แล้วเปลี่ยน 16 ไบต์แรกของ
 * ข้อความได้โดยไม่รู้กุญแจ
 *   - encode() ต่อ HMAC-SHA256 (กุญแจแยกจากกุญแจเข้ารหัส) · decode() ตรวจ MAC ทุกครั้งที่มี
 *   - ค่ารุ่นเก่า (ไม่มี MAC) ยังถอดได้ เพราะมีค่าที่เก็บไว้ในฐานข้อมูล (license ของ assets)
 *     · decode($s, $key, true) ไม่รับรุ่นเก่า — ใช้กับค่าที่มาจากผู้ใช้ เช่นลิงก์ดาวน์โหลด
 * SecurityMiddleware เก็บสถิติ rate limit ด้วย serialize ใน /tmp/rate_limits ที่ทุกคนในเครื่องเขียนได้
 *   → unserialize ของไฟล์ที่ผู้อื่นวางไว้ = PHP object injection · เปลี่ยนเป็น JSON ในโฟลเดอร์ของผู้ใช้นี้ (0700)
 * FileCache ที่ไม่มี DATA_FOLDER ใช้ /tmp/kotchasan_cache ร่วมกันทั้งเครื่อง
 *   → ใช้โฟลเดอร์ของผู้ใช้นี้ (0700) และไม่อ่านไฟล์ cache ที่ผู้ใช้อื่นเป็นเจ้าของ
 *
 * ใช้:  php install/cli-testcrypto.php
 * exit code : 0 = ผ่าน, 1 = ไม่ผ่าน
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$root = dirname(__DIR__);
require $root.'/Kotchasan/Password.php';
require $root.'/Kotchasan/Cache/CacheInterface.php';
require $root.'/Kotchasan/Cache/FileCache.php';
foreach (['Kotchasan/Http/Middleware/MiddlewareInterface.php', 'Kotchasan/Http/Middleware/SecurityMiddleware.php'] as $file) {
    if (is_file($root.'/'.$file)) {
        require_once $root.'/'.$file;
    }
}

use Kotchasan\Password;

$failed = 0;
$check = function ($name, $pass, $detail = null) use (&$failed) {
    echo ($pass ? '  ✔ ' : '  ✘ ').$name.(!$pass && $detail !== null ? '  — '.json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '')."\n";
    if (!$pass) {
        $failed++;
    }
};
$decode = function (...$args) {
    try {
        return Password::decode(...$args);
    } catch (\Exception $e) {
        return 'EXCEPTION';
    }
};
$key = 'test-password-key-'.bin2hex(random_bytes(4));

echo "Password\n";
$plain = json_encode(['file' => '/home/site/datas/file/a.pdf', 'mime' => 'application/pdf', 'name' => 'a.pdf']);
$token = Password::encode($plain, $key);
$check('encode/decode round trip', $decode($token, $key) === $plain);
$check('the strict decode reads what encode() writes', $decode($token, $key, true) === $plain);

// flip bits of the IV so the first block reads {"file":"/etc/pa…
$raw = base64_decode($token);
$parts = explode('::', $raw);
$target = '{"file":"/etc/pa';
$known = substr($plain, 0, 16);
$iv = $parts[1];
$forgedIv = '';
for ($i = 0; $i < 16; $i++) {
    $forgedIv .= chr(ord($iv[$i]) ^ ord($known[$i]) ^ ord($target[$i]));
}
$parts[1] = $forgedIv;
$forged = base64_encode(implode('::', $parts));
$result = $decode($forged, $key);
$check('a token whose IV was changed is refused (it decrypted to the attacker\'s first 16 bytes)', $result === 'EXCEPTION' || $result === false, is_string($result) ? substr($result, 0, 40) : $result);

$parts = explode('::', $raw);
$parts[0] = substr($parts[0], 0, -6).($parts[0][strlen($parts[0]) - 6] === 'A' ? 'B' : 'A').substr($parts[0], -5);
$result = $decode(base64_encode(implode('::', $parts)), $key);
$check('a token whose ciphertext was changed is refused', $result === 'EXCEPTION' || $result === false, is_string($result) ? substr($result, 0, 40) : $result);

$check('another key cannot read it', in_array($decode($token, $key.'x'), ['EXCEPTION', false], true));

// a value written before this change (stored in a database)
$legacyIv = Password::uniqid(16);
$legacy = base64_encode(openssl_encrypt('stored licence', 'aes-256-cbc', $key, 0, $legacyIv).'::'.$legacyIv);
$check('a value written before (no MAC) still decodes', $decode($legacy, $key) === 'stored licence');
$check('but not where a MAC is required (values that come from users)', $decode($legacy, $key, true) === 'EXCEPTION');

echo "SecurityMiddleware rate limit store\n";
if (class_exists('Kotchasan\\Http\\Middleware\\SecurityMiddleware')) {
    $middleware = (new \ReflectionClass('Kotchasan\\Http\\Middleware\\SecurityMiddleware'))->newInstanceWithoutConstructor();
    $prop = new \ReflectionProperty($middleware, 'maxRequestsPerMinute');
    $prop->setAccessible(true);
    $prop->setValue($middleware, 100);
    $prefix = new \ReflectionProperty($middleware, 'rateLimitPrefix');
    $prefix->setAccessible(true);
    $prefix->setValue($middleware, 'cli_test_');
    $method = new \ReflectionMethod($middleware, 'checkRateLimit');
    $method->setAccessible(true);
    $ip = '198.51.100.'.random_int(1, 254);
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        $check('APCu is used (no file store to check)', true);
    } else {
        $method->invoke($middleware, $ip);
        $method->invoke($middleware, $ip);
        $found = array_merge(glob(sys_get_temp_dir().'/rate_limits*/cli_test_'.md5($ip)) ?: []);
        $file = $found[0] ?? null;
        $content = $file ? file_get_contents($file) : '';
        $check('requests are stored as JSON, not serialize()', $file && is_array(json_decode($content, true)) && count(json_decode($content, true)) === 2, $content);
        $check('in a folder only this user can open', $file && (fileperms(dirname($file)) & 0077) === 0, $file ? substr(sprintf('%o', fileperms(dirname($file))), -4) : null);
        if ($file) {
            @unlink($file);
        }
    }
}

echo "FileCache\n";
$cache = new \Kotchasan\Cache\FileCache(null, 60, false);
$path = (new \ReflectionProperty($cache, 'path'));
$path->setAccessible(true);
$dir = $path->getValue($cache);
$check('without DATA_FOLDER the cache is not the shared /tmp/kotchasan_cache', rtrim($dir, '/') !== sys_get_temp_dir().'/kotchasan_cache', $dir);
$check('and only this user can open it', is_dir($dir) && (fileperms($dir) & 0077) === 0, substr(sprintf('%o', @fileperms($dir)), -4));
$cache->set('cli_test_key', ['a' => 1]);
$check('a value this user wrote is read back', $cache->get('cli_test_key') === ['a' => 1]);
$cache->delete('cli_test_key');

echo $failed ? "\n$failed checks failed\n" : "\nall checks passed\n";
exit($failed ? 1 : 0);
