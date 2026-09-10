<?php
/**
 * install/cli-check-core.php — เทียบไฟล์แกนของโปรเจ็คนี้กับโปรเจ็คอื่น
 *
 * นโยบายที่ตกลงกันไว้: Now.js, Kotchasan, Gcms, modules/index, modules/export
 * และเครื่องมือใน install/ ต้อง "เหมือนกันทุกโปรเจ็ค 100%" ส่วนที่ต่างกันได้
 * มีเฉพาะของที่เป็นของโปรเจ็คนั้นจริง ๆ (Gcms/Config.php, install/database.sql,
 * install/upgrade2.php, settings/, โมดูลของตัวเอง)
 *
 * ไฟล์นี้ทำให้ "หนี้ backport" มองเห็นได้ด้วยคำสั่งเดียว แทนที่จะรู้ตัวตอนที่
 * โมดูลกลางถูก deploy ลงโปรเจ็คที่แกนเก่ากว่าแล้วพัง
 *
 * ใช้:  php install/cli-check-core.php [ที่อยู่โปรเจ็ค ...] [ตัวเลือก]
 *
 *   ไม่ระบุที่อยู่ = ค้นหาโปรเจ็คพี่น้องในโฟลเดอร์แม่ของโปรเจ็คนี้เอง
 *   --list         แสดงรายชื่อไฟล์ที่ต่างทั้งหมด (ปกติแสดง 15 รายการแรก)
 *   --quiet        แสดงเฉพาะบรรทัดสรุปของแต่ละโปรเจ็ค
 *
 * exit code : 0 = ไม่มีอะไรต่าง, 1 = มีโปรเจ็คที่แกนไม่ตรงกัน
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-check-core.php'], ['/', ''], __FILE__));

// รายการไฟล์/โฟลเดอร์ที่ถือว่าเป็น "แกน" และรายชื่อที่ยกเว้นเพราะเป็นของโปรเจ็คนั้นเอง
$CORE = ['Kotchasan', 'Gcms', 'Now', 'modules/index', 'modules/export', 'modules/inventory',
    'js/main.js', 'install', 'load.php', 'api.php', 'export.php'];

// โมดูลที่ "ไม่ใช่ทุกโปรเจ็คต้องมี" — โปรเจ็คที่ไม่ได้ติดตั้งไว้เลยไม่ถือว่าขาดไฟล์
//
// ⚠️ ต่างจาก $SKIP : $SKIP คือไฟล์ที่ไม่เทียบเลย ส่วนตรงนี้คือ "ถ้าไม่มีทั้งโมดูล
// ก็ข้ามทั้งโมดูล แต่ถ้ามีต้องเหมือนกันทุกไฟล์" — ไม่งั้นพอเพิ่ม modules/inventory
// เข้ารายการแกน โปรเจ็คอย่าง repair (ระบบแจ้งซ่อมล้วน ไม่มีคลังสินค้า) จะรายงาน
// ว่าขาดไฟล์นับสิบตลอดกาล จนคำว่า "ตรงกันทุกไฟล์" หมดความหมาย
$OPTIONAL = ['modules/inventory'];
$SKIP = ['Gcms/Config.php', 'install/database.sql', 'install/upgrade2.php',
    'install/settings', 'install/preflight-module.php', 'install/testdb-module.php', 'install/img',
    // สคีมารุ่นก่อนที่แต่ละโปรเจ็คคัดไว้ให้ชุดทดสอบ F3 ใช้ — ของใครของมัน
    'install/legacy'];

$paths = [];
$show_all = false;
$quiet = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--list') {
        $show_all = true;
    } elseif ($arg === '--quiet') {
        $quiet = true;
    } elseif (substr($arg, 0, 2) === '--') {
        fwrite(STDERR, "ไม่รู้จักตัวเลือก $arg\n");
        exit(1);
    } else {
        $paths[] = rtrim($arg, '/').'/';
    }
}

if (empty($paths)) {
    // ค้นหาโปรเจ็คพี่น้อง — โฟลเดอร์ที่มี Kotchasan/ + Now/js/ + modules/index/
    // ไม่ hardcode ที่อยู่ของเครื่องไหนไว้ในไฟล์ที่ส่งไปกับรุ่น
    $parent = dirname(rtrim(ROOT_PATH, '/')).'/';
    foreach (glob($parent.'*', GLOB_ONLYDIR) ?: [] as $dir) {
        $dir = rtrim($dir, '/').'/';
        if ($dir === ROOT_PATH) {
            continue;
        }
        if (is_dir($dir.'Kotchasan') && is_dir($dir.'Now/js') && is_dir($dir.'modules/index')) {
            $paths[] = $dir;
        }
    }
    if (empty($paths)) {
        fwrite(STDERR, "ไม่พบโปรเจ็คพี่น้องใน ".$parent." — ระบุที่อยู่โปรเจ็คเองได้\n");
        exit(1);
    }
}

$mine = coreFiles(ROOT_PATH, $CORE, $SKIP);
echo 'แกนของ '.ROOT_PATH.' — '.count($mine)." ไฟล์\n";

$exit = 0;
foreach ($paths as $path) {
    if (!is_dir($path)) {
        fwrite(STDERR, "ไม่พบโฟลเดอร์ $path\n");
        $exit = 1;
        continue;
    }
    $theirs = coreFiles($path, $CORE, $SKIP);

    // โมดูลที่เป็นตัวเลือก : ถ้าฝั่งใดฝั่งหนึ่งไม่ได้ติดตั้งไว้เลย ให้ข้ามทั้งโมดูล
    // ต้องตัดออกทั้งสองฝั่ง ไม่งั้นฝ่ายที่ไม่มีจะรายงานว่า "ขาด" และฝ่ายที่มี
    // จะรายงานว่า "เกิน" ซึ่งเป็นเสียงรบกวนทั้งคู่
    $expected = $mine;
    foreach ($OPTIONAL as $optional) {
        if (is_dir($path.$optional) && is_dir(ROOT_PATH.$optional)) {
            continue;
        }
        foreach (array_keys($expected) as $file) {
            if (strpos($file, $optional.'/') === 0) {
                unset($expected[$file]);
            }
        }
        foreach (array_keys($theirs) as $file) {
            if (strpos($file, $optional.'/') === 0) {
                unset($theirs[$file]);
            }
        }
    }

    $differ = [];
    $missing = [];
    foreach ($expected as $file => $hash) {
        if (!isset($theirs[$file])) {
            $missing[] = $file;
        } elseif ($theirs[$file] !== $hash) {
            $differ[] = $file;
        }
    }
    $extra = array_keys(array_diff_key($theirs, $expected));
    $total = count($differ) + count($missing) + count($extra);
    if ($total > 0) {
        $exit = 1;
    }
    echo "\n== $path\n";
    echo '   '.($total === 0 ? 'ตรงกันทุกไฟล์' : 'ต่าง '.count($differ).' · ขาด '.count($missing).' · เกิน '.count($extra))."\n";
    if ($quiet || $total === 0) {
        continue;
    }
    $lines = [];
    foreach ($differ as $file) {
        $lines[] = '     ต่าง  '.$file;
    }
    foreach ($missing as $file) {
        $lines[] = '     ขาด   '.$file;
    }
    foreach ($extra as $file) {
        $lines[] = '     เกิน  '.$file;
    }
    $limit = $show_all ? count($lines) : 15;
    echo implode("\n", array_slice($lines, 0, $limit))."\n";
    if (count($lines) > $limit) {
        echo '     ... อีก '.(count($lines) - $limit)." รายการ (ใส่ --list เพื่อดูทั้งหมด)\n";
    }
}

exit($exit);

/**
 * อ่านรายชื่อไฟล์แกนพร้อมค่าแฮชของโปรเจ็คหนึ่ง
 *
 * @param string $root โฟลเดอร์ของโปรเจ็ค (ลงท้ายด้วย /)
 * @param array  $core รายการไฟล์/โฟลเดอร์ที่ถือว่าเป็นแกน
 * @param array  $skip รายการที่ยกเว้น (เทียบจากที่อยู่ที่ตัดโฟลเดอร์โปรเจ็คออกแล้ว)
 *
 * @return array [ที่อยู่ในโปรเจ็ค => md5]
 */
function coreFiles($root, array $core, array $skip)
{
    $files = [];
    foreach ($core as $entry) {
        $full = $root.$entry;
        if (is_file($full)) {
            $files[$entry] = md5_file($full);
        } elseif (is_dir($full)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root)));
                foreach ($skip as $ignore) {
                    if ($relative === $ignore || strpos($relative, $ignore.'/') === 0) {
                        continue 2;
                    }
                }
                $files[$relative] = md5_file($file->getPathname());
            }
        }
    }
    ksort($files);

    return $files;
}
