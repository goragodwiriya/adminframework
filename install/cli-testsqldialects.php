<?php
/**
 * install/cli-testsqldialects.php — Kotchasan's SQL on every database driver
 *
 * The parts of Kotchasan that write different SQL per database, run against a real
 * server of each driver: DB (insert, delete with its default limit of one row),
 * UPDATE/DELETE with orderBy() and limit(), Sql::NEXT() and Sql::POSITION() (which
 * read the dialect of the connection) and Database::transaction() rolling back on an
 * \Error. Each driver gets a database of its own, dropped at the end.
 *
 * ใช้:  php install/cli-testsqldialects.php [driver ...] [--mysql=<conn>] [--pgsql=<conn>] [--sqlsrv=<conn>]
 *   driver   mysql pgsql sqlsrv sqlite (ไม่ระบุ = ทุกตัวที่มีบัญชี)
 *   <conn>   user:password@host:port หรือ env NOWTEST_MYSQL / NOWTEST_PGSQL / NOWTEST_SQLSRV
 *            mysql ไม่ระบุ = บัญชีใน settings/database.php · sqlite ไม่ต้องใช้บัญชี
 *            SQL Server ของเครื่องนี้: docker start kotchasan-mssql (ใบรับรอง self-signed จึงตั้ง trust_server_certificate)
 * exit code : 0 = ผ่านทุก driver ที่ได้ทดสอบ, 1 = มีข้อที่ไม่ผ่าน
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-testsqldialects.php'], ['/', ''], __FILE__));
$project = ROOT_PATH;
$work = sys_get_temp_dir().'/kotchasan-sqldialects-'.getmypid().'/';
@mkdir($work, 0700, true);

$drivers = ['mysql', 'pgsql', 'sqlsrv', 'sqlite'];
$options = ['mysql' => '', 'pgsql' => '', 'sqlsrv' => ''];
$want = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--(mysql|pgsql|sqlsrv)=(.*)$/', $arg, $m)) {
        $options[$m[1]] = $m[2];
    } elseif (in_array($arg, $drivers, true)) {
        $want[] = $arg;
    } else {
        fwrite(STDERR, "ไม่รู้จัก $arg\n");
        exit(1);
    }
}

/** user, password, host, port of a driver, or null when no account is given */
function account($driver, array $options)
{
    $spec = $options[$driver] !== '' ? $options[$driver] : (string) getenv('NOWTEST_'.strtoupper($driver));
    if ($spec === '' && $driver === 'mysql' && is_file(ROOT_PATH.'settings/database.php')) {
        $cfg = (include ROOT_PATH.'settings/database.php')['mysql'] ?? [];
        if (($cfg['dbdriver'] ?? 'mysql') === 'mysql' && isset($cfg['username'])) {
            return [$cfg['username'], $cfg['password'] ?? '', $cfg['hostname'] ?? 'localhost', (int) ($cfg['port'] ?? 3306)];
        }
    }
    if ($spec === '') {
        return null;
    }
    $at = strrpos($spec, '@');
    list($user, $pass) = array_pad(explode(':', $at === false ? $spec : substr($spec, 0, $at), 2), 2, '');
    list($host, $port) = array_pad(explode(':', $at === false ? 'localhost' : substr($spec, $at + 1), 2), 2, '');

    return [$user, $pass, $host === '' ? 'localhost' : $host, (int) ($port ?: ['mysql' => 3306, 'pgsql' => 5432, 'sqlsrv' => 1433][$driver])];
}

/** PDO on the server itself, to create and drop the test database */
function serverPdo($driver, array $a)
{
    list($user, $pass, $host, $port) = $a;
    $dsn = [
        'mysql' => "mysql:host=$host;port=$port",
        'pgsql' => "pgsql:host=$host;port=$port;dbname=postgres",
        'sqlsrv' => "sqlsrv:Server=$host,$port;Database=master;TrustServerCertificate=1"
    ][$driver];

    return new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function dropDatabase($driver, PDO $pdo, $name)
{
    if ($driver === 'mysql') {
        $pdo->exec("DROP DATABASE IF EXISTS `$name`");
    } elseif ($driver === 'pgsql') {
        $pdo->exec("DROP DATABASE IF EXISTS \"$name\" WITH (FORCE)");
    } elseif ($pdo->query("SELECT DB_ID('$name')")->fetchColumn() !== null) {
        $pdo->exec("ALTER DATABASE [$name] SET SINGLE_USER WITH ROLLBACK IMMEDIATE");
        $pdo->exec("DROP DATABASE [$name]");
    }
}

// One request of a session stored in the database (two of them run at the same time)
if (isset($_SERVER['SQLDIALECT_SESSION'])) {
    $job = json_decode($_SERVER['SQLDIALECT_SESSION'], true);
    include $project.'Kotchasan/load.php';
    \Kotchasan\Database::config(['default' => $job['config']]);
    ini_set('session.use_cookies', '0');
    ini_set('session.use_strict_mode', '0');
    $handler = new \Kotchasan\Session();
    session_set_save_handler([$handler, '_open'], [$handler, '_close'], [$handler, '_read'], [$handler, '_write'], [$handler, '_destroy'], [$handler, '_gc']);
    session_id($job['id']);
    $start = microtime(true);
    session_start();
    $waited = microtime(true) - $start;
    $_SESSION[$job['key']] = 1;
    usleep($job['hold']);
    session_write_close();
    echo json_encode(['waited' => $waited, 'keys' => array_keys($_SESSION)]);
    exit;
}

// One child process per driver: Kotchasan keeps its connections in static state
if (isset($_SERVER['SQLDIALECT_CHILD'])) {
    $job = json_decode($_SERVER['SQLDIALECT_CHILD'], true);
    include $project.'Kotchasan/load.php';
    \Kotchasan\Database::config(['default' => $job['config']]);
    $results = [];
    $check = function ($name, $ok, $detail = '') use (&$results) {
        $results[] = [$name, (bool) $ok, $ok ? '' : (string) $detail];
    };
    try {
        $database = \Kotchasan\Database::create();
        $pk = [
            'mysql' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => 'SERIAL PRIMARY KEY',
            'sqlsrv' => 'INT IDENTITY(1,1) PRIMARY KEY',
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT'
        ][$job['driver']];
        $database->raw('CREATE TABLE '.$database->getTableName('t')." (id $pk, v VARCHAR(10))");
        $db = \Kotchasan\DB::create($database);
        $ids = [];
        foreach (['a', 'b', 'b', 'z', 'z', 'z'] as $v) {
            $ids[] = $db->insert('t', ['v' => $v]);
        }
        $rows = function () use ($database) {
            return implode(',', array_map(function ($r) {
                return $r->id.$r->v;
            }, $database->createQuery()->select('id', 'v')->from('t')->orderBy('id')->fetchAll()));
        };
        $check('DB::insert() คืน id 1–6', $ids === [1, 2, 3, 4, 5, 6], json_encode($ids));

        $deleted = $db->delete('t', [['v', 'b']]);
        $check('DB::delete() ลบหนึ่งแถวตามค่าเริ่มต้น (PostgreSQL เคยโยน RuntimeException)', $deleted === 1 && $rows() === '1a,3b,4z,5z,6z', $deleted.' '.$rows());

        $query = $database->createQuery()->update('t')->set(['v' => 'y'])->where(['v', 'z'])->orderBy('id', 'DESC')->limit(2);
        $changed = $query->execute()->rowCount();
        $check('update()->orderBy(DESC)->limit(2) แก้สองแถวท้าย', $changed === 2 && $rows() === '1a,3b,4z,5y,6y', $query->toSql().' → '.$rows());

        $query = $database->createQuery()->delete('t')->where(['v', 'y'])->orderBy('id')->limit(1);
        $gone = $query->execute()->rowCount();
        $check('delete()->orderBy()->limit(1) ลบแถวแรกตามลำดับ', $gone === 1 && $rows() === '1a,3b,4z,6y', $query->toSql().' → '.$rows());

        $table = $database->getTableName('t');
        $next = $database->raw('SELECT '.\Kotchasan\Database\Sql::NEXT('id', $table, null, 'next')->toSql().', '
            .\Kotchasan\Database\Sql::POSITION('z', '`v`', 'p')->toSql().' FROM '.$table.' WHERE id = 4')->fetch();
        $check('Sql::NEXT() และ Sql::POSITION() รันได้ (เขียนตามไดรเวอร์ของการเชื่อมต่อ)', $next && (int) $next->next === 7 && (int) $next->p === 1, json_encode($next));

        // INSERT IGNORE went to every driver: a syntax error on all but MySQL
        $database->raw('CREATE TABLE '.$database->getTableName('u').' (name VARCHAR(20) PRIMARY KEY)');
        $database->insert('u')->values(['name' => 'Books'])->execute();
        try {
            $added = $database->createQuery()->insert('u')->ignore()->rows([['name' => 'Books'], ['name' => 'Toys']])->execute()->rowCount();
            $total = (int) $database->createQuery()->selectCount()->from('u')->first()->count;
            $check('insert()->ignore() ข้ามแถวที่ซ้ำ', $job['driver'] !== 'sqlsrv' && $added === 1 && $total === 2, "added $added total $total");
        } catch (\RuntimeException $e) {
            $check('insert()->ignore() บน SQL Server โยน RuntimeException ที่บอกเหตุผล', $job['driver'] === 'sqlsrv' && strpos($e->getMessage(), 'SQL Server') !== false, $e->getMessage());
        }

        // MAX(CAST(id AS ...)): MySQL takes SIGNED, not INT
        $nextId = $db->nextId('t');
        $check('DB::nextId() คืนค่าสูงสุดบวกหนึ่ง', $nextId === 7, var_export($nextId, true));

        try {
            $database->transaction(function ($database) {
                $database->raw('DELETE FROM '.$database->getTableName('t'));
                strlen([]);
            });
        } catch (\TypeError $e) {
        }
        $open = $database->getConnection()->getDriver()->inTransaction();
        $check('transaction(): TypeError ใน callback → rollback', !$open && $rows() === '1a,3b,4z,6y', ($open ? 'still open ' : '').$rows());

        // SELECT row count: PDOStatement::rowCount() is 0 on SQLite and -1 on SQL Server
        $result = $database->raw('SELECT id FROM '.$table.' ORDER BY id');
        $firstId = (int) $result->fetch()->id;
        $counted = count($result);
        $restIds = implode(',', array_map(function ($r) {
            return $r->id;
        }, $result->fetchAll()));
        $none = $database->raw('SELECT id FROM '.$table.' WHERE id = 0');
        $check('count()/rowCount() ของ SELECT นับแถวได้ และอ่านแถวต่อได้ครบ', $firstId === 1 && $counted === 4 && $restIds === '3,4,6' && $none->isEmpty() && !$database->raw('SELECT id FROM '.$table)->isEmpty(), "$firstId $counted [$restIds]");

        // Session in the database: createTable() in the SQL of the driver, a lock
        // from read to close, and writes that keep the query cache
        $session = new \Kotchasan\Session();
        $check('Session::createTable() สร้างตารางได้ (และเรียกซ้ำได้)', $session->createTable() && $session->createTable());
        \Kotchasan\Database::configureCache(['driver' => 'memory'], 60);
        $database->createQuery()->select('id')->from('t')->cacheOn()->fetchAll();
        $session->_write('cache-probe', 'x|i:1;');
        $database->createQuery()->select('id')->from('t')->cacheOn()->fetchAll();
        $hits = (\Kotchasan\Database::getQueryCache()->getStats()['hits'] ?? 0);
        $check('การเขียน session ไม่ล้างแคชของคิวรี', $hits >= 1, 'hits '.$hits);
        $a = proc_open([PHP_BINARY, __FILE__], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pa, null, ['SQLDIALECT_SESSION' => json_encode(['config' => $job['config'], 'id' => 'locktest', 'key' => 'a', 'hold' => 1500000])] + array_diff_key(getenv(), ['SQLDIALECT_CHILD' => 1]));
        usleep(500000);
        $b = proc_open([PHP_BINARY, __FILE__], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pb, null, ['SQLDIALECT_SESSION' => json_encode(['config' => $job['config'], 'id' => 'locktest', 'key' => 'b', 'hold' => 0])] + array_diff_key(getenv(), ['SQLDIALECT_CHILD' => 1]));
        $outA = stream_get_contents($pa[1]).stream_get_contents($pa[2]);
        $outB = stream_get_contents($pb[1]).stream_get_contents($pb[2]);
        proc_close($a);
        proc_close($b);
        $stored = (string) $session->_read('locktest');
        $session->_close();
        $second = json_decode($outB, true);
        $check('สองคำขอของ session เดียวพร้อมกัน: คำขอที่สองรอ และค่าของทั้งสองคำขออยู่ครบ', is_array($second) && $second['waited'] > 0.6 && strpos($stored, 'a|') !== false && strpos($stored, 'b|') !== false, trim($outA.' '.$outB).' stored '.$stored);
    } catch (\Throwable $e) {
        $check('ไม่มี exception', false, get_class($e).': '.$e->getMessage());
    }
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    exit;
}

$failed = 0;
$tested = [];
foreach ($want ?: $drivers as $driver) {
    $name = 'kotchasan_'.getmypid().'_dialect';
    $pdo = null;
    if ($driver === 'sqlite') {
        $config = ['driver' => 'sqlite', 'database' => $work.$name.'.sqlite', 'prefix' => 'app'];
    } else {
        $a = account($driver, $options);
        if ($a === null) {
            echo "[skip] $driver — ไม่มีบัญชี (--$driver= หรือ NOWTEST_".strtoupper($driver).")\n";
            continue;
        }
        try {
            $pdo = serverPdo($driver, $a);
        } catch (PDOException $e) {
            echo "[fail] $driver — ต่อเซิร์ฟเวอร์ {$a[2]}:{$a[3]} ไม่ได้: ".$e->getMessage()."\n";
            $failed++;
            continue;
        }
        dropDatabase($driver, $pdo, $name);
        $pdo->exec($driver === 'mysql' ? "CREATE DATABASE `$name` DEFAULT CHARACTER SET utf8mb4" : ($driver === 'pgsql' ? "CREATE DATABASE \"$name\"" : "CREATE DATABASE [$name]"));
        $config = ['driver' => $driver, 'host' => $a[2], 'port' => $a[3], 'database' => $name, 'username' => $a[0], 'password' => $a[1], 'prefix' => 'app'];
        if ($driver === 'sqlsrv') {
            $config['trust_server_certificate'] = true;
        }
    }
    $env = ['SQLDIALECT_CHILD' => json_encode(['driver' => $driver, 'config' => $config])] + getenv();
    $proc = proc_open([PHP_BINARY, __FILE__], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    proc_close($proc);
    if ($pdo !== null) {
        dropDatabase($driver, $pdo, $name);
    } else {
        @unlink($config['database']);
    }
    $results = json_decode($out, true);
    echo str_repeat('=', 60)."\n$driver\n";
    if (!is_array($results)) {
        echo "  ✘ ไม่มีผลจาก process ลูก: ".trim($out.' '.$err)."\n";
        $failed++;
        continue;
    }
    $tested[] = $driver;
    foreach ($results as list($check, $ok, $detail)) {
        echo '  '.($ok ? '✔' : '✘')." $check".($ok ? '' : " — $detail")."\n";
        $failed += $ok ? 0 : 1;
    }
}
@rmdir($work);
echo "\n".($failed ? "$failed ข้อไม่ผ่าน" : 'ผ่านทั้งหมด').' · ทดสอบ: '.(implode(' ', $tested) ?: '-')."\n";
exit($failed ? 1 : 0);
