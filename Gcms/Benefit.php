<?php
/**
 * @filesource Gcms/Benefit.php
 *
 * สิทธิ์ครั้งเดียวต่อคน (one-time benefit) — กันการสมัครหลายบัญชีเพื่อรับของฟรีซ้ำ
 * เช่น เครดิตทดลองใช้ คูปองสมาชิกใหม่ ช่วงทดลองฟรี · ระบบใดก็ใช้ได้ แยกกันด้วยชื่อสิทธิ์ ($purpose)
 *
 *   $r = \Gcms\Benefit::claim($userId, 'signup_credit', $request->getClientIp());
 *   if ($r['ok']) { …ให้สิทธิ์… } else { …แจ้งเหตุผลตาม $r['reason']… }
 *
 * สัญญาณที่ตรวจ (ปฏิเสธเมื่อเจออย่างใดอย่างหนึ่ง):
 *   claimed     ผู้ใช้นี้เคยรับสิทธิ์นี้แล้ว
 *   device      อุปกรณ์เดียวกัน (cookie gdev ที่ server ออกให้ · HttpOnly · 2 ปี) เคยรับให้บัญชีอื่นแล้ว
 *   email       อีเมลเดียวกันหลังปรับรูปแบบ (ตัวพิมพ์เล็ก · ตัด +tag · Gmail ตัดจุด) เคยรับแล้ว
 *   ip          IP เดียวกันรับครบ benefit_ip_limit ครั้งใน benefit_ip_hours ชั่วโมงแล้ว
 *   disposable  อีเมลจากผู้ให้บริการอีเมลชั่วคราว (benefit_block_disposable)
 *   unverified  ยังไม่ได้ยืนยันอีเมล (benefit_require_verified)
 *
 * ค่าตั้งอยู่ใน settings/config.php (ดู DEFAULTS) · benefit_guard = 0 ปิดการตรวจ: ให้สิทธิ์ทุกคน แต่ยังบันทึกไว้
 * ตาราง {prefix}_benefit สร้างเองเมื่อใช้ครั้งแรก (โปรเจ็คไม่ต้องแก้ตัวติดตั้ง) — gcms.in.th อยู่ในฐานของแต่ละเว็บ
 * คลาสนี้ไม่มีข้อความภาษา: คืนรหัสเหตุผล ให้ระบบที่เรียกใช้แปลงเป็นข้อความเอง
 *
 * ไฟล์นี้ต้องเหมือนกันใน adminframework, gcms15.0.0, gcms.in.th และ theme.gcms.in.th
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gcms;

use Kotchasan\DB;

class Benefit extends \Kotchasan\KBase
{
    const DEVICE_COOKIE = 'gdev';

    const DEFAULTS = [
        'benefit_guard' => 1,
        'benefit_ip_limit' => 2,
        'benefit_ip_hours' => 24,
        'benefit_block_disposable' => 1,
        'benefit_require_verified' => 0
    ];

    /** ผู้ให้บริการอีเมลชั่วคราวที่พบบ่อย (เพิ่มเองได้ใน config benefit_disposable_domains) */
    const DISPOSABLE = [
        '10minutemail.com', '20minutemail.com', 'byom.de', 'dispostable.com', 'emailondeck.com', 'fakeinbox.com',
        'getairmail.com', 'getnada.com', 'guerrillamail.com', 'guerrillamail.net', 'guerrillamailblock.com', 'grr.la',
        'mail.tm', 'mailcatch.com', 'maildrop.cc', 'mailinator.com', 'mailnesia.com', 'mintemail.com', 'moakt.com',
        'mohmal.com', 'mytemp.email', 'sharklasers.com', 'spam4.me', 'spamgourmet.com', 'temp-mail.io', 'temp-mail.org',
        'tempail.com', 'tempmail.com', 'tempmail.dev', 'tempmailo.com', 'tempr.email', 'throwawaymail.com', 'trashmail.com',
        'trashmail.de', 'yopmail.com', 'yopmail.fr', 'yopmail.net'
    ];

    /** @var bool */
    private static $tableReady = false;

    /** @var string|null device id ของคำขอนี้ */
    private static $device = null;

    /** ค่าตั้ง (รวมค่าตั้งต้น) */
    public static function settings()
    {
        $out = [];
        foreach (self::DEFAULTS as $k => $v) {
            $out[$k] = isset(self::$cfg->{$k}) && self::$cfg->{$k} !== '' ? (int) self::$cfg->{$k} : $v;
        }
        $extra = self::$cfg->benefit_disposable_domains ?? [];
        $out['benefit_disposable_domains'] = is_array($extra) ? $extra : array_filter(array_map('trim', explode(',', (string) $extra)));

        return $out;
    }

    /**
     * id ของอุปกรณ์ (cookie gdev) — ออกให้ใหม่ถ้ายังไม่มี
     * เรียกตอนผู้ใช้เปิดหน้าที่เกี่ยวข้อง (ก่อนสมัคร/ล็อกอิน) เพื่อให้บัญชีที่สมัครจากเครื่องเดียวกันได้ id เดียวกัน
     *
     * @param bool $create
     *
     * @return string 32 hex หรือ '' (ยังไม่มีและไม่ได้สร้าง)
     */
    public static function deviceId($create = true)
    {
        if (self::$device !== null) {
            return self::$device;
        }
        $id = (string) ($_COOKIE[self::DEVICE_COOKIE] ?? '');
        if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
            $id = '';
            if ($create && !headers_sent()) {
                $id = bin2hex(random_bytes(16));
                $base = defined('WEB_URL') ? (parse_url(WEB_URL, PHP_URL_PATH) ?: '/') : '/';
                setcookie(self::DEVICE_COOKIE, $id, [
                    'expires' => time() + 2 * 365 * 86400, 'path' => $base,
                    'secure' => defined('WEB_URL') && strpos(WEB_URL, 'https://') === 0, 'httponly' => true, 'samesite' => 'Lax'
                ]);
            }
        }
        self::$device = $id;

        return $id;
    }

    /**
     * อีเมลในรูปแบบที่ใช้เทียบ: ตัวพิมพ์เล็ก · ตัด +tag · gmail.com/googlemail.com ตัดจุดในชื่อ
     *
     * @return string '' เมื่อไม่ใช่อีเมล
     */
    public static function emailKey($email)
    {
        $email = strtolower(trim((string) $email));
        if (!preg_match('/^([^@\s]+)@([a-z0-9.-]+\.[a-z]{2,})$/', $email, $m)) {
            return '';
        }
        list(, $local, $domain) = $m;
        $local = explode('+', $local, 2)[0];
        if ($domain === 'googlemail.com') {
            $domain = 'gmail.com';
        }
        if ($domain === 'gmail.com') {
            $local = str_replace('.', '', $local);
        }

        return mb_substr($local.'@'.$domain, 0, 190);
    }

    public static function isDisposable($email)
    {
        $domain = strtolower((string) substr(strrchr((string) $email, '@') ?: '', 1));

        return $domain !== '' && in_array($domain, array_merge(self::DISPOSABLE, self::settings()['benefit_disposable_domains']), true);
    }

    /**
     * ตรวจสิทธิ์โดยไม่บันทึก
     *
     * @param int         $userId
     * @param string      $purpose a-z0-9_ ไม่เกิน 40
     * @param string|null $ip
     *
     * @return array ['ok' => bool, 'reason' => ''|claimed|device|email|ip|disposable|unverified]
     */
    public static function check($userId, $purpose, $ip = null)
    {
        self::ensureTable();
        $s = self::settings();
        $db = DB::create();
        $purpose = self::purpose($purpose);
        if ($db->exists('benefit', [['purpose', $purpose], ['user_id', (int) $userId]])) {
            return ['ok' => false, 'reason' => 'claimed'];
        }
        if (!$s['benefit_guard']) {
            return ['ok' => true, 'reason' => ''];
        }
        $user = $db->first('user', [['id', (int) $userId]], ['username', 'activatecode']);
        $email = $user ? (string) $user->username : '';
        if ($s['benefit_block_disposable'] && self::isDisposable($email)) {
            return ['ok' => false, 'reason' => 'disposable'];
        }
        if ($s['benefit_require_verified'] && $user && (string) $user->activatecode !== '') {
            return ['ok' => false, 'reason' => 'unverified'];
        }
        $device = self::deviceId();
        if ($device !== '' && $db->exists('benefit', [['purpose', $purpose], ['device', $device], ['user_id', '!=', (int) $userId]])) {
            return ['ok' => false, 'reason' => 'device'];
        }
        $key = self::emailKey($email);
        if ($key !== '' && $db->exists('benefit', [['purpose', $purpose], ['email_key', $key], ['user_id', '!=', (int) $userId]])) {
            return ['ok' => false, 'reason' => 'email'];
        }
        $ip = self::ip($ip);
        if ($ip !== '' && $s['benefit_ip_limit'] > 0) {
            $since = date('Y-m-d H:i:s', time() - max(1, $s['benefit_ip_hours']) * 3600);
            if ($db->count('benefit', [['purpose', $purpose], ['ip', $ip], ['created_at', '>', $since]]) >= $s['benefit_ip_limit']) {
                return ['ok' => false, 'reason' => 'ip'];
            }
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * ตรวจแล้วบันทึกว่าผู้ใช้ได้รับสิทธิ์ (ทำทีละคำขอต่อสิทธิ์ — สองบัญชีจากเครื่องเดียวกันกดพร้อมกันก็ได้แค่บัญชีเดียว)
     *
     * @return array ['ok' => bool, 'reason' => …] — ok = ให้สิทธิ์ได้ และบันทึกแล้ว
     */
    public static function claim($userId, $purpose, $ip = null)
    {
        self::ensureTable();
        $db = DB::create();
        $lock = 'gbenefit_'.self::purpose($purpose);
        $res = $db->raw('SELECT GET_LOCK(?, 10) AS `l`', [$lock]);
        $row = $res ? $res->fetch() : null;
        if ((int) (is_array($row) ? ($row['l'] ?? 0) : ($row->l ?? 0)) !== 1) {
            return ['ok' => false, 'reason' => 'busy'];
        }
        try {
            $r = self::check($userId, $purpose, $ip);
            if ($r['ok']) {
                $user = $db->first('user', [['id', (int) $userId]], ['username']);
                $db->insert('benefit', [
                    'purpose' => self::purpose($purpose), 'user_id' => (int) $userId, 'device' => self::deviceId(),
                    'email_key' => self::emailKey($user ? $user->username : ''), 'ip' => self::ip($ip), 'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $r;
        } finally {
            $db->raw('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    /** ผู้ใช้เคยได้รับสิทธิ์นี้แล้วหรือยัง */
    public static function claimed($userId, $purpose)
    {
        self::ensureTable();

        return DB::create()->exists('benefit', [['purpose', self::purpose($purpose)], ['user_id', (int) $userId]]);
    }

    private static function purpose($purpose)
    {
        $purpose = strtolower((string) $purpose);
        if (!preg_match('/^[a-z0-9_]{1,40}$/', $purpose)) {
            throw new \InvalidArgumentException('Benefit purpose must be a-z0-9_ (max 40)');
        }

        return $purpose;
    }

    private static function ip($ip)
    {
        $ip = (string) ($ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''));

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /** สร้างตารางเมื่อยังไม่มี (ครั้งเดียวต่อคำขอ) */
    private static function ensureTable()
    {
        if (self::$tableReady) {
            return;
        }
        $db = DB::create();
        $table = $db->getTableName('benefit');
        $db->raw('CREATE TABLE IF NOT EXISTS `'.$table.'` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purpose` varchar(40) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device` char(32) NOT NULL DEFAULT \'\',
  `email_key` varchar(190) NOT NULL DEFAULT \'\',
  `ip` varchar(45) NOT NULL DEFAULT \'\',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purpose_user` (`purpose`, `user_id`),
  KEY `purpose_device` (`purpose`, `device`),
  KEY `purpose_email` (`purpose`, `email_key`),
  KEY `purpose_ip` (`purpose`, `ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        self::$tableReady = true;
    }
}
