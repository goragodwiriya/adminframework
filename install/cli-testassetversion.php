<?php
/**
 * install/cli-testassetversion.php — ?v= ของไฟล์ที่ index.php โหลดต้องเปลี่ยนเมื่อไฟล์เปลี่ยน
 *
 * ไฟล์ .js/.css ถูกเก็บทั้งใน cache ของเบราว์เซอร์ (.htaccess: 1 สัปดาห์) และของ service worker
 * (ไฟล์ที่ติด ?v= ไม่ถามเซิร์ฟเวอร์อีกจนกว่า ?v= จะเปลี่ยน) เดิม ?v= คือค่า reversion ซึ่งเปลี่ยนเฉพาะตอน
 * บันทึกการตั้งค่า — sync Now/ หรือแก้ admin.js ของโมดูลแล้วผู้ใช้ยังได้ไฟล์เดิม (carbooking ดูเหมือนไม่ได้ sync)
 *
 * ตรวจบนสำเนาชั่วคราว (index.php + settings/config.php ของโปรเจ็คนี้ ไฟล์อื่นเป็นไฟล์เปล่า)
 * ไม่แตะเวลาแก้ไขของไฟล์ในโปรเจ็ค
 *
 * ใช้:  php install/cli-testassetversion.php [โฟลเดอร์โปรเจ็ค]   (ไม่ระบุ = โปรเจ็คนี้)
 * exit code : 0 = ผ่าน, 1 = ไม่ผ่าน
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$project = rtrim($argv[1] ?? dirname(__DIR__), '/');
$tmp = sys_get_temp_dir().'/nowjs-assetversion-'.getmypid();
$files = [
    'Now/dist/now.core.min.js', 'Now/dist/now.core.min.css', 'Now/dist/now.table.min.js', 'Now/dist/now.graph.min.js',
    'Now/dist/now.serviceworker.min.js', 'Now/dist/now.tabs.min.js', 'Now/css/fonts.css',
    'css/styles.css', 'js/main.js', 'js/global.js', 'modules/demo/admin.js'
];
@mkdir($tmp.'/settings', 0777, true);
copy($project.'/index.php', $tmp.'/index.php');
copy($project.'/settings/config.php', $tmp.'/settings/config.php');
$base = time() - 3600;
foreach ($files as $file) {
    @mkdir(dirname($tmp.'/'.$file), 0777, true);
    file_put_contents($tmp.'/'.$file, '');
    touch($tmp.'/'.$file, $base);
}

$render = function () use ($tmp) {
    $html = shell_exec('cd '.escapeshellarg($tmp).' && '.escapeshellarg(PHP_BINARY).' index.php 2>&1');
    preg_match_all('#(?:src|href)="([^"]+\.(?:js|css))\?v=([^"]*)"#', (string) $html, $m, PREG_SET_ORDER);
    $versions = [];
    foreach ($m as $hit) {
        $versions[$hit[1]] = $hit[2];
    }
    return $versions;
};

$failed = 0;
$check = function ($name, $pass, $detail = null) use (&$failed) {
    echo ($pass ? '  ✔ ' : '  ✘ ').$name.(!$pass && $detail !== null ? '  — '.json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '')."\n";
    if (!$pass) {
        $failed++;
    }
};

$first = $render();
$check('index.php loads its files with ?v=', isset($first['Now/dist/now.core.min.js'], $first['js/main.js'], $first['modules/demo/admin.js']), $first);
$check('every file has the same ?v= (main.js and Now.require() reuse it for others)', count(array_unique($first)) === 1, $first);

touch($tmp.'/modules/demo/admin.js', $base + 100);
clearstatcache();
$second = $render();
$check('a module\'s admin.js that changes gives a new ?v=', ($second['js/main.js'] ?? null) !== ($first['js/main.js'] ?? null), [$first['js/main.js'] ?? null, $second['js/main.js'] ?? null]);

touch($tmp.'/Now/dist/now.tabs.min.js', $base + 200);
clearstatcache();
$third = $render();
$check('a Now/dist file the page loads later (a feature bundle) gives a new ?v=', ($third['Now/dist/now.core.min.js'] ?? null) !== ($second['Now/dist/now.core.min.js'] ?? null), [$second['Now/dist/now.core.min.js'] ?? null, $third['Now/dist/now.core.min.js'] ?? null]);

$fourth = $render();
$check('nothing changed: the same ?v= as before', $fourth === $third, [$third, $fourth]);

$cfg = include $tmp.'/settings/config.php';
$check('?v= still starts with reversion (saving the settings still gives a new one)', strpos((string) ($fourth['js/main.js'] ?? ''), rawurlencode((string) ($cfg['reversion'] ?? ''))) === 0, $fourth['js/main.js'] ?? null);

shell_exec('rm -rf '.escapeshellarg($tmp));
echo $failed ? "\n$failed checks failed\n" : "\nall checks passed\n";
exit($failed ? 1 : 0);
