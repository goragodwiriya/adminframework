<?php
/**
 * @filesource Gcms/Chat/SettingsRepository.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gcms\Chat;

use Kotchasan\Config;

/**
 * Config-backed settings for AI chat runtime and admin UI.
 *
 * All settings are stored in settings/config.php via the standard
 * Kotchasan Config save flow. No database table is required.
 *
 * @since 1.0
 */
class SettingsRepository extends \Kotchasan\KBase
{
    /**
     * Connector settings from config.
     *
     * @return array
     */
    public function connector(): array
    {
        return [
            'ai_enabled' => !empty(self::$cfg->ai_enabled) ? 1 : 0,
            'ai_provider' => !empty(self::$cfg->ai_provider) ? strtolower(trim((string) self::$cfg->ai_provider)) : 'openai',
            'ai_connections' => !empty(self::$cfg->ai_connections) && is_array(self::$cfg->ai_connections) ? self::$cfg->ai_connections : []
        ];
    }

    /**
     * Message templates from config, falling back to class defaults.
     *
     * @return array
     */
    public function messages(): array
    {
        $result = [];
        foreach ($this->messageDefaults() as $key => $default) {
            $cfgKey = 'ai_chat_'.$key;
            $result[$key] = isset(self::$cfg->$cfgKey) ? (string) self::$cfg->$cfgKey : $default;
        }

        return $result;
    }

    /**
     * Persist message templates to config file.
     *
     * @param array $messages
     *
     * @return bool
     */
    public function saveMessages(array $messages): bool
    {
        $config = Config::load(ROOT_PATH.'settings/config.php');
        foreach (array_keys($this->messageDefaults()) as $key) {
            $cfgKey = 'ai_chat_'.$key;
            if (array_key_exists($key, $messages)) {
                $config->$cfgKey = trim((string) $messages[$key]);
            }
        }

        return Config::save($config, ROOT_PATH.'settings/config.php');
    }

    /**
     * Handoff workflow configuration from config.
     *
     * @return array
     */
    public function workflow(): array
    {
        return [
            'sla_minutes' => max(1, min(10080, (int) (self::$cfg->ai_chat_workflow_value ?? 60)))
        ];
    }

    /**
     * Persist workflow configuration to config file.
     *
     * @param array $workflow
     *
     * @return bool
     */
    public function saveWorkflow(array $workflow): bool
    {
        $config = Config::load(ROOT_PATH.'settings/config.php');
        $config->ai_chat_workflow_value = max(1, min(10080, (int) ($workflow['sla_minutes'] ?? 60)));

        return Config::save($config, ROOT_PATH.'settings/config.php');
    }

    /**
     * Format one configured message with placeholder replacement.
     *
     * @param string $key
     * @param array  $context
     *
     * @return string
     */
    public function formatMessage(string $key, array $context = []): string
    {
        $template = trim((string) ($this->messages()[$key] ?? ''));
        if ($template === '') {
            return '';
        }

        return strtr($template, $this->placeholders($context));
    }

    /**
     * @return array
     */
    private function messageDefaults(): array
    {
        return [
            'starter_message' => 'พร้อมช่วยค้นหาบทความ ดูรายละเอียดบทความ ตอบคำถามด่วน และส่งต่อเจ้าหน้าที่ ลองพิมพ์คำถามหรือเลือกคำสั่งด้านล่างได้เลย',
            'welcome_message' => 'สวัสดีครับ ตอนนี้ระบบ AI Chat core ถูกแยกให้รองรับ Web, LINE และ Telegram ได้จากแกนเดียวกัน และสามารถต่อเครื่องมือจากโมดูลอื่นเพิ่มได้ในอนาคต ถ้าต้องการดูความสามารถให้พิมพ์ว่า "ช่วยอะไรได้บ้าง"',
            'capability_message' => 'ตอนนี้ foundation ของ AI Chat ถูกออกแบบให้ขยายได้โดยแยกเป็น 3 ชั้นหลัก: 1) channel adapter สำหรับ Web, LINE, Telegram 2) chat orchestrator กลาง 3) tool registry สำหรับต่อความสามารถจากโมดูล โดยตอนนี้เริ่มเชื่อม skill จริงแล้วทั้งการค้นหาบทความและการเปิดรายละเอียดบทความจากโมดูลเอกสาร รวมถึงคำตอบด่วนที่ผู้ดูแลกำหนดเองได้',
            'escalation_created_message' => 'ผมบันทึกคำขอส่งต่อให้เจ้าหน้าที่แล้ว หมายเลขคำขอ #:id เจ้าหน้าที่จะเห็นข้อความล่าสุดและบริบทจากช่องทางนี้ทันที หากต้องการฝากข้อมูลติดต่อเพิ่ม ให้พิมพ์ต่อในแชตนี้ได้เลย',
            'ai_disabled_message' => 'AI connector is currently disabled.',
            'ai_unavailable_message' => 'AI chat is temporarily unavailable.',
            'ai_empty_response_message' => 'AI chat returned an empty response.',
            'fallback_help_message' => 'Current chat foundation supports a shared core for web, LINE, and Telegram, with future module tools added through the registry instead of hardcoding channel-specific logic.',
            'handoff_accepted_message' => 'เจ้าหน้าที่รับเรื่องคำขอ #:id แล้ว หากต้องการเพิ่มข้อมูล ให้ตอบกลับในช่องทางนี้ได้เลย',
            'handoff_closed_message' => 'เจ้าหน้าที่ปิดคำขอ #:id แล้ว ขอบคุณที่ติดต่อเข้ามา หากยังต้องการความช่วยเหลือเพิ่มเติมสามารถส่งข้อความใหม่ได้',
            'console_cleared_message' => 'Console cleared. Try asking about articles, quick answers, or current chat capabilities.'
        ];
    }

    /**
     * @param array $context
     *
     * @return array
     */
    private function placeholders(array $context): array
    {
        return [
            ':site_name' => trim(strip_tags((string) (self::$cfg->web_title ?? 'Website'))),
            ':id' => trim((string) ($context['id'] ?? '')),
            ':status' => trim((string) ($context['status'] ?? '')),
            ':channel' => trim((string) ($context['channel'] ?? '')),
            ':requester' => trim((string) ($context['requester'] ?? '')),
            ':message' => trim((string) ($context['message'] ?? ''))
        ];
    }
}
