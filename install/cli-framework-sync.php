<?php
/**
 * install/cli-framework-sync.php — ตรวจและบังคับให้ Now/ Kotchasan/ vite.config.js
 * ของทุกโปรเจ็คลูกเหมือน adminframework 100%
 *
 * ใช้:  php install/cli-framework-sync.php [--apply] [--force] [โปรเจ็ค ...]
 *   (ไม่ใส่อะไร)  รายงานว่าโปรเจ็คไหนต่างจากต้นฉบับตรงไหน — ไม่แก้ไฟล์
 *   --apply       คัดลอกต้นฉบับทับโปรเจ็คที่ต่าง (ข้ามโปรเจ็คที่มีไฟล์เกินหรือไฟล์ที่แก้ในโปรเจ็ค)
 *   --force       ใช้กับ --apply: ทับและลบแม้มีไฟล์เกินหรือไฟล์ที่แก้ในโปรเจ็ค
 *   โปรเจ็ค       ชื่อโฟลเดอร์ใต้ now.js/ (ไม่ใส่ = ทุกโปรเจ็คที่มีทั้ง Now/ และ Kotchasan/)
 *
 * ⚠️ เครื่องมือนี้เป็นของ "ฝั่งเรา" รันได้จาก adminframework เท่านั้น
 *
 * Now/ และ Kotchasan/ เป็นของกลาง แก้ที่ adminframework (หรือ nowjs) แล้วกระจายด้วยไฟล์นี้
 * ของที่เป็นของโปรเจ็คเดียวต้องไม่อยู่ในสองโฟลเดอร์นี้:
 *   - bundle ของโปรเจ็ค → js/components/<ชื่อ>/ + build.json แล้ว build ลง js/dist/
 *     (vite.config.js ที่เหมือนกันทุกโปรเจ็คหาเองจาก BUILD_TARGET=<ชื่อ>)
 *   - ชุดทดสอบของ Kotchasan → tests/Kotchasan/ ของโปรเจ็ค
 *
 * ไฟล์ที่ถูกทับหรือลบถูกเก็บไว้ที่ now.js/backup/framework-sync/<เวลา>/<โปรเจ็ค>/ เสมอ
 * (โปรเจ็คที่ไม่มี git กู้คืนได้จากที่นี่)
 *
 * แยกไฟล์ด้วยประวัติ git ของ adminframework และ nowjs:
 *   - ไฟล์ที่ต่างแต่เนื้อหาเคยอยู่ในประวัติ = รุ่นเก่า ทับได้
 *   - ไฟล์ใน Now/dist ที่ต่าง = ผล build ด้วย vite รุ่นอื่น ทับได้เสมอ (build core ที่ adminframework เท่านั้น)
 *   - ไฟล์ที่ต้นฉบับไม่มีแล้วแต่เคยมี = ต้นฉบับลบแล้ว ลบได้
 *   - ไฟล์ที่ต่าง แก้ทีหลังต้นฉบับ (mtime ใหม่กว่า) และไม่เคยอยู่ในประวัติ = งานที่ยังไม่ได้ backport
 *   - ไฟล์ที่ต้นฉบับไม่เคยมี = ของโปรเจ็คนี้
 * สองแบบหลัง --apply จะข้ามโปรเจ็คนั้นจนกว่าจะย้าย/นำเข้า adminframework แล้ว หรือยืนยันด้วย --force
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$base = dirname(__DIR__);
if (basename($base) !== 'adminframework') {
    fwrite(STDERR, "รันได้จาก adminframework เท่านั้น (ที่นี่คือ $base)\n");
    exit(1);
}
$root = dirname($base);
$items = ['Now', 'Kotchasan', 'vite.config.js'];

$options = ['apply' => false, 'force' => false];
$projects = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $options['apply'] = true;
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif ($arg[0] === '-') {
        fwrite(STDERR, "ไม่รู้จักตัวเลือก $arg\n");
        exit(1);
    } elseif (!is_dir("$root/$arg/Now") || !is_dir("$root/$arg/Kotchasan")) {
        fwrite(STDERR, "ไม่พบโปรเจ็ค $arg (ต้องมีทั้ง Now/ และ Kotchasan/)\n");
        exit(1);
    } else {
        $projects[] = $arg;
    }
}
if (empty($projects)) {
    foreach (scandir($root) as $name) {
        // สำเนาที่ตัวจัดการไฟล์สร้าง ("x (สำเนา)") มีช่องว่างในชื่อเสมอ
        if ($name[0] === '.' || $name === 'backup' || basename($base) === $name || preg_match('/\s/u', $name)) {
            continue;
        }
        if (is_dir("$root/$name/Now") && is_dir("$root/$name/Kotchasan")) {
            $projects[] = $name;
        }
    }
}

/**
 * ไฟล์ทั้งหมดใต้ $path เป็น [ชื่อสัมพัทธ์ => path จริง]
 * $path เป็นไฟล์เดี่ยวก็ได้ (คืนชื่อนั้นตัวเดียว)
 */
function listFiles($path, $prefix)
{
    if (is_file($path)) {
        return [$prefix => $path];
    }
    $files = [];
    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                function ($file) {
                    return !in_array($file->getFilename(), ['node_modules', '.git'], true);
                }
            )
        );
        foreach ($iterator as $file) {
            $files[$prefix.'/'.substr($file->getPathname(), strlen($path) + 1)] = $file->getPathname();
        }
    }
    ksort($files);
    return $files;
}

/**
 * เนื้อหาไฟล์นี้เคยเป็นของต้นฉบับที่ path เดียวกันหรือไม่ (ค้นในประวัติ git ของ $repos)
 * ใช้แยกไฟล์รุ่นเก่า/ไฟล์ที่ต้นฉบับลบไปแล้ว ออกจากงานที่ทำในโปรเจ็คนั้นเอง
 */
function knownInHistory($repos, $rel, $file)
{
    $hash = trim((string) shell_exec('git hash-object '.escapeshellarg($file).' 2>/dev/null'));
    if ($hash === '') {
        return false;
    }
    foreach ($repos as $repo) {
        $found = shell_exec('git -C '.escapeshellarg($repo).' log --all --format=%h -1 --find-object='.$hash.' -- '.escapeshellarg($rel).' 2>/dev/null');
        if (trim((string) $found) !== '') {
            return true;
        }
    }
    return false;
}

/**
 * เทียบโปรเจ็คกับต้นฉบับ คืน ['missing', 'differ', 'local', 'extra', 'removed'] (แต่ละช่องเป็นรายการไฟล์)
 * local   = ไฟล์ที่ต่าง แก้ทีหลังต้นฉบับ และไม่เคยมีในประวัติต้นฉบับ (น่าจะเป็นงานของโปรเจ็คนี้ อยู่ใน differ ด้วย)
 * extra   = ไฟล์ที่ต้นฉบับไม่มีและไม่เคยมี (ของโปรเจ็คนี้)
 * removed = ไฟล์ที่ต้นฉบับเคยมีแล้วลบไป (ลบได้)
 */
function compareProject($base, $target, $items, $repos)
{
    $result = ['missing' => [], 'differ' => [], 'local' => [], 'extra' => [], 'removed' => []];
    foreach ($items as $item) {
        $source = listFiles("$base/$item", $item);
        $copy = listFiles("$target/$item", $item);
        foreach ($source as $rel => $file) {
            if (!isset($copy[$rel])) {
                $result['missing'][] = $rel;
            } elseif (filesize($file) !== filesize($copy[$rel]) || md5_file($file) !== md5_file($copy[$rel])) {
                $result['differ'][] = $rel;
                // Now/dist เป็นผล build: npm run build ในโปรเจ็คลูกได้ไฟล์ต่างจากต้นฉบับเพราะรุ่นของ
                // vite/rolldown ต่างกัน ไม่ใช่งานของโปรเจ็ค ซอร์สตัวจริงอยู่ใน Now/js ซึ่งตรวจแยกอยู่แล้ว
                if (strpos($rel, 'Now/dist/') !== 0 && filemtime($copy[$rel]) > filemtime($file) && !knownInHistory($repos, $rel, $copy[$rel])) {
                    $result['local'][] = $rel;
                }
            }
        }
        foreach (array_diff_key($copy, $source) as $rel => $file) {
            $result[knownInHistory($repos, $rel, $file) ? 'removed' : 'extra'][] = $rel;
        }
    }
    return $result;
}

/**
 * พิมพ์รายการไฟล์ไม่เกิน 15 บรรทัดต่อหัวข้อ
 */
function printFiles($label, $files)
{
    foreach (array_slice($files, 0, 15) as $rel) {
        echo "        $label  $rel\n";
    }
    if (count($files) > 15) {
        echo '        '.$label.'  ... อีก '.(count($files) - 15)." ไฟล์\n";
    }
}

/**
 * ย้ายไฟล์เดิมไปที่สำรองก่อนทับหรือลบ
 */
function backupFile($file, $backup)
{
    if (!is_dir(dirname($backup))) {
        mkdir(dirname($backup), 0777, true);
    }
    copy($file, $backup);
}

// nowjs คือต้นทางของ Now/ — ต่างกันแปลว่า adminframework ยังไม่ได้รับรุ่นล่าสุด (หรือกลับกัน)
$upstream = dirname($root).'/nowjs';
if (is_dir("$upstream/Now")) {
    $diff = compareProject($base, $upstream, ['Now'], []);
    $count = count($diff['missing']) + count($diff['differ']) + count($diff['extra']);
    echo $count === 0 ? "[ok]    nowjs/Now ตรงกับ adminframework/Now\n" : "[ต่าง]  nowjs/Now ต่างจาก adminframework/Now $count ไฟล์ (sync ต้นทางเอง เครื่องมือนี้ไม่แตะ nowjs)\n";
    printFiles('ขาด', $diff['missing']);
    printFiles('ต่าง', $diff['differ']);
    printFiles('เกิน', $diff['extra']);
}

// ประวัติที่ใช้ตัดสินว่าไฟล์ที่ต่าง/เกินเคยเป็นของต้นฉบับหรือไม่
$repos = array_filter([$base, $upstream], function ($repo) {
    return is_dir("$repo/.git");
});
// ชื่อโฟลเดอร์สำรองเป็นเวลาท้องถิ่นตาม settings/config.php (CLI ไม่ได้โหลด Kotchasan จึงไม่ถูกตั้งให้)
$config = is_file("$base/settings/config.php") ? include "$base/settings/config.php" : [];
date_default_timezone_set(empty($config['timezone']) ? 'Asia/Bangkok' : $config['timezone']);
$stamp = date('Ymd-His');
$failed = 0;
foreach ($projects as $project) {
    $target = "$root/$project";
    $diff = compareProject($base, $target, $items, $repos);
    if (empty($diff['missing']) && empty($diff['differ']) && empty($diff['extra']) && empty($diff['removed'])) {
        echo "[ok]    $project\n";
        continue;
    }
    $summary = [];
    foreach (['missing' => 'ขาด', 'differ' => 'ต่าง', 'local' => 'แก้ในโปรเจ็ค', 'removed' => 'ต้นฉบับลบแล้ว', 'extra' => 'เกิน'] as $key => $label) {
        if (!empty($diff[$key])) {
            $summary[] = $label.' '.count($diff[$key]);
        }
    }
    $blocked = !empty($diff['extra']) || !empty($diff['local']);
    if ($options['apply'] && (!$blocked || $options['force'])) {
        $backup = "$root/backup/framework-sync/$stamp/$project";
        foreach (array_merge($diff['missing'], $diff['differ']) as $rel) {
            if (is_file("$target/$rel")) {
                backupFile("$target/$rel", "$backup/$rel");
            } elseif (!is_dir(dirname("$target/$rel"))) {
                mkdir(dirname("$target/$rel"), 0777, true);
            }
            copy("$base/$rel", "$target/$rel");
            // mtime เท่าต้นฉบับ รอบหน้าจึงแยกได้ว่าไฟล์ไหนถูกแก้ในโปรเจ็คนี้ทีหลัง
            touch("$target/$rel", filemtime("$base/$rel"));
        }
        foreach (array_merge($diff['removed'], $diff['extra']) as $rel) {
            backupFile("$target/$rel", "$backup/$rel");
            unlink("$target/$rel");
            // ลบโฟลเดอร์ที่ว่างแล้วไล่ขึ้นไป แต่ไม่ลบ Now/ Kotchasan/ เอง
            for ($dir = dirname("$target/$rel"); !in_array(substr($dir, strlen($target) + 1), $items, true) && count(scandir($dir)) === 2; $dir = dirname($dir)) {
                rmdir($dir);
            }
        }
        echo "[แก้]   $project — ".implode(' · ', $summary)." (สำรองไว้ที่ backup/framework-sync/$stamp/$project)\n";
        continue;
    }
    $failed++;
    echo ($options['apply'] ? '[ข้าม]  ' : '[ต่าง]  ')."$project — ".implode(' · ', $summary)."\n";
    printFiles('ขาด', $diff['missing']);
    printFiles('ต่าง', array_diff($diff['differ'], $diff['local']));
    printFiles('แก้ในโปรเจ็ค', $diff['local']);
    printFiles('ต้นฉบับลบแล้ว', $diff['removed']);
    printFiles('เกิน', $diff['extra']);
    if (!empty($diff['extra'])) {
        echo "        → ไฟล์เกินเป็นของโปรเจ็คนี้: ย้าย bundle ไป js/components/<ชื่อ>/ ชุดทดสอบไป tests/Kotchasan/\n";
    }
    if (!empty($diff['local'])) {
        echo "        → ไฟล์ที่แก้ในโปรเจ็ค: นำเข้า adminframework ก่อน หรือยืนยันทับด้วย --apply --force\n";
    }
}

exit($failed > 0 ? 1 : 0);
