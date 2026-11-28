<?php
/**
 * install/cli-testinputs.php — Kotchasan\Http\Inputs เรียกเมธอดของ InputItem ได้ทุกตัว
 *
 * ค่าจาก $request->post()/get() ที่ส่งมาเป็น array (เช่นเนื้อหาแยกภาษา) ได้ Inputs ไม่ใช่ InputItem
 * Inputs::__call ส่งชื่อเมธอดต่อให้ทุกรายการ เดิมยอมเฉพาะชื่อในรายการที่พิมพ์ไว้ตายตัว
 * ซึ่งตกหล่น (cleanHtml ทำให้ document/write ของ gcms.in.th ล้ม, datetime, getValue ...)
 * ตอนนี้ทุกเมธอด public ที่ไม่ใช่ static และไม่ใช่ magic ของ InputItem เรียกได้
 * ส่วนชื่อที่ Inputs มีเอง (isEmpty, filter, toArray, toJson ...) คงความหมายของทั้งชุด
 *
 * ใช้:  php install/cli-testinputs.php
 * exit code 0 = ผ่านทั้งหมด
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-testinputs.php'], ['/', ''], __FILE__));
include ROOT_PATH.'Kotchasan/load.php';

use Kotchasan\Http\InputItem;
use Kotchasan\Http\Inputs;
use Kotchasan\Http\Request;

$results = [];
$check = function ($name, $ok, $detail = '') use (&$results) {
    $results[] = (bool) $ok;
    echo ($ok ? '  ✔ ' : '  ✘ ').$name.($ok || $detail === '' ? '' : '  — '.$detail)."\n";
};

$values = [
    'th' => '<p onclick="x()">ไทย 12:30 2026-11-11</p><script>a()</script>',
    'en' => ' Hello {LNG_x} \\o/ 42 ',
    'n' => '3.5'
];
$_POST = ['multi' => $values, 'nested' => ['a' => ['b' => '7']]];
$request = new Request();

// อาร์กิวเมนต์สำหรับเมธอดที่ต้องมี
$args = ['cut' => [5]];

// ชื่อที่ Inputs มีเป็นเมธอดของตัวเอง ทำงานกับทั้งชุด ไม่ผ่าน __call
$own = [];
foreach ((new ReflectionClass(Inputs::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
    $own[strtolower($m->getName())] = true;
}

$tested = 0;
foreach ((new ReflectionClass(InputItem::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    $name = $method->getName();
    if ($method->isStatic() || strpos($name, '__') === 0 || isset($own[strtolower($name)])) {
        continue;
    }
    $a = $args[$name] ?? [];
    $expected = [];
    foreach ($values as $k => $v) {
        $expected[$k] = (new InputItem($v, 'POST'))->$name(...$a);
    }
    try {
        $out = $request->post('multi', [])->$name(...$a);
        $check("Inputs->$name() = InputItem->$name() ทีละรายการ", $out == $expected, json_encode($out, JSON_UNESCAPED_UNICODE));
    } catch (\Throwable $e) {
        $check("Inputs->$name() = InputItem->$name() ทีละรายการ", false, $e->getMessage());
    }
    ++$tested;
}
$check("ทดสอบเมธอดของ InputItem ครบ ($tested ตัว)", $tested >= 40, (string) $tested);

// array ซ้อน: เรียกต่อลงไปทุกชั้น
try {
    $out = $request->post('nested', [])->toInt();
    $check('array ซ้อนเรียกต่อทุกชั้น', $out === ['a' => ['b' => 7]], json_encode($out));
} catch (\Throwable $e) {
    $check('array ซ้อนเรียกต่อทุกชั้น', false, $e->getMessage());
}

// เมธอดของ Inputs เองยังมีความหมายของทั้งชุด
$inputs = $request->post('multi', []);
$check('Inputs->isEmpty() ยังหมายถึง "ไม่มีรายการ"', $inputs->isEmpty() === false && (new Inputs([]))->isEmpty() === true);
$check('Inputs->toArray() ยังคืนค่าเดิมทั้งชุด', $inputs->toArray() === $values, json_encode($inputs->toArray(), JSON_UNESCAPED_UNICODE));

// สิ่งที่ต้องเรียกไม่ได้ผ่าน __call: magic method และ static ของ InputItem, ชื่อที่ไม่มีใน InputItem
foreach (['__toString', '__set_state', 'nope'] as $name) {
    try {
        $inputs->$name([]);
        $check("Inputs->$name() ถูกปฏิเสธ", false, 'ไม่มี exception');
    } catch (\BadMethodCallException $e) {
        $check("Inputs->$name() ถูกปฏิเสธ", true);
    } catch (\Throwable $e) {
        $check("Inputs->$name() ถูกปฏิเสธ", false, get_class($e).': '.$e->getMessage());
    }
}

$failed = count(array_filter($results, function ($r) {return !$r;}));
echo "\n".($failed ? "$failed of ".count($results).' checks failed' : 'all '.count($results).' checks passed')."\n";
exit($failed ? 1 : 0);
