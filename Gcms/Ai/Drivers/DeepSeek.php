<?php
/**
 * @filesource Gcms/Ai/Drivers/DeepSeek.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms\Ai\Drivers;

use Gcms\Ai\Response;

/**
 * DeepSeek V4 chat driver (OpenAI-compatible + thinking mode).
 *
 * @see https://api-docs.deepseek.com/guides/thinking_mode
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class DeepSeek extends \Gcms\Ai\Driver
{
    /**
     * Legacy model IDs retired after 2026-07-24.
     *
     * @var array<string, array{model:string,thinking:bool}>
     */
    private static $legacyModels = [
        'deepseek-chat' => [
            'model' => 'deepseek-v4-flash',
            'thinking' => false
        ],
        'deepseek-reasoner' => [
            'model' => 'deepseek-v4-flash',
            'thinking' => true
        ]
    ];

    /**
     * @var string
     */
    protected $apiUrl = 'https://api.deepseek.com/v1';

    /**
     * @var string
     */
    protected $model = 'deepseek-v4-flash';

    /**
     * Enable chain-of-thought thinking mode.
     *
     * @var bool
     */
    protected $thinkingEnabled = false;

    /**
     * Thinking effort: high or max.
     *
     * @var string
     */
    protected $reasoningEffort = 'high';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (isset($config['thinking_enabled'])) {
            $this->thinkingEnabled = !empty($config['thinking_enabled']);
        }
        if (!empty($config['reasoning_effort'])) {
            $this->reasoningEffort = self::normalizeEffort($config['reasoning_effort']);
        }

        $resolved = self::resolveModel($this->model, $this->thinkingEnabled);
        $this->model = $resolved['model'];
        if ($resolved['thinking']) {
            $this->thinkingEnabled = true;
        }
    }

    /**
     * Normalize a model name and infer thinking mode from legacy IDs.
     *
     * @param string $model
     * @param bool   $thinkingEnabled
     *
     * @return array{model:string,thinking:bool}
     */
    public static function resolveModel($model, $thinkingEnabled = false)
    {
        $model = trim((string) $model);
        if ($model !== '' && isset(self::$legacyModels[$model])) {
            return self::$legacyModels[$model];
        }

        return [
            'model' => $model !== '' ? $model : 'deepseek-v4-flash',
            'thinking' => (bool) $thinkingEnabled
        ];
    }

    /**
     * @param mixed $effort
     *
     * @return string
     */
    public static function normalizeEffort($effort)
    {
        $effort = strtolower(trim((string) $effort));

        return $effort === 'max' ? 'max' : 'high';
    }

    /**
     * Send a chat completion request.
     *
     * Supported options:
     *   model, max_tokens, temperature, system,
     *   thinking (bool), thinking_enabled (bool), reasoning_effort (high|max)
     *
     * @param array $messages
     * @param array $options
     *
     * @return Response
     */
    public function chat(array $messages, array $options = [])
    {
        $model = trim((string) $this->option($options, 'model', $this->model));
        $maxTokens = (int) $this->option($options, 'max_tokens', $this->maxTokens);
        $temperature = (float) $this->option($options, 'temperature', $this->temperature);
        $thinkingEnabled = $this->resolveThinkingEnabled($options, $model);

        $resolved = self::resolveModel($model, $thinkingEnabled);
        $model = $resolved['model'];
        $thinkingEnabled = $resolved['thinking'] || $thinkingEnabled;
        $reasoningEffort = self::normalizeEffort(
            $this->option($options, 'reasoning_effort', $this->reasoningEffort)
        );

        $msgs = self::sanitizeMessages($messages);
        if (!empty($options['system'])) {
            array_unshift($msgs, ['role' => 'system', 'content' => (string) $options['system']]);
        }

        $payload = [
            'model' => $model,
            'messages' => $msgs,
            'max_tokens' => $maxTokens
        ];

        if ($thinkingEnabled) {
            $payload['thinking'] = ['type' => 'enabled'];
            $payload['reasoning_effort'] = $reasoningEffort;
        } else {
            $payload['temperature'] = $temperature;
        }

        $headers = ['Authorization' => 'Bearer '.$this->apiKey];
        $raw = $this->post($this->apiUrl.'/chat/completions', $payload, $headers);

        if (isset($raw['error'])) {
            $errMsg = is_array($raw['error']) ? ($raw['error']['message'] ?? json_encode($raw['error'])) : (string) $raw['error'];

            return Response::fromError($errMsg, $raw);
        }

        $message = $raw['choices'][0]['message'] ?? [];

        $r = new Response();
        $r->success = true;
        $r->raw = $raw;
        $r->model = $raw['model'] ?? $model;
        $r->content = isset($message['content']) ? (string) $message['content'] : '';
        $r->reasoningContent = isset($message['reasoning_content']) ? (string) $message['reasoning_content'] : '';
        $r->inputTokens = $raw['usage']['prompt_tokens'] ?? $raw['usage']['input_tokens'] ?? 0;
        $r->outputTokens = $raw['usage']['completion_tokens'] ?? $raw['usage']['output_tokens'] ?? 0;

        return $r;
    }

    /**
     * @param array  $options
     * @param string $model
     *
     * @return bool
     */
    private function resolveThinkingEnabled(array $options, $model)
    {
        if (array_key_exists('thinking', $options)) {
            return !empty($options['thinking']);
        }
        if (array_key_exists('thinking_enabled', $options)) {
            return !empty($options['thinking_enabled']);
        }
        if ($model !== '' && isset(self::$legacyModels[$model])) {
            return self::$legacyModels[$model]['thinking'];
        }

        return $this->thinkingEnabled;
    }

    /**
     * Remove reasoning_content from prior assistant turns — DeepSeek rejects it on input.
     *
     * @param array $messages
     *
     * @return array
     */
    private static function sanitizeMessages(array $messages)
    {
        $clean = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $item = $message;
            unset($item['reasoning_content']);
            $clean[] = $item;
        }

        return $clean;
    }
}
