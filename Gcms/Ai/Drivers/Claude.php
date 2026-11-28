<?php
/**
 * @filesource Gcms/Ai/Drivers/Claude.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms\Ai\Drivers;

use Gcms\Ai\Response;

/**
 * Anthropic Claude chat completion driver
 *
 * Uses the Anthropic Messages API:
 *   https://api.anthropic.com/v1/messages
 *
 * API reference: https://docs.anthropic.com/en/api/messages
 *
 * Models (2026-11): claude-opus-5-5 (default), claude-sonnet-5-5, claude-haiku-5-5, claude-fable-5-2
 *
 * Current models reject sampling parameters (temperature/top_p/top_k → HTTP 400), so none is sent.
 * Thinking is adaptive and on by default; depth is set with the "effort" option
 * (low | medium | high | xhigh | max → output_config.effort). Responses may carry thinking blocks —
 * only text blocks become Response::$content. stop_reason "refusal" becomes an error response.
 * Opus 5.5 / Opus 5 / Sonnet 5.5 / Fable 5.2 / Fable 5.1 / Mythos 5.2 requests opt into server-side refusal fallbacks
 * (beta server-side-fallback-2026-07-01, fallbacks: "default").
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Claude extends \Gcms\Ai\Driver
{
    /**
     * Anthropic Messages API endpoint
     *
     * @var string
     */
    protected $apiUrl = 'https://api.anthropic.com/v1/messages';

    /**
     * Default model
     *
     * @var string
     */
    protected $model = 'claude-opus-5-5';

    /**
     * Anthropic API version header value
     *
     * @var string
     */
    protected $anthropicVersion = '2023-06-01';

    /**
     * Models that accept server-side refusal fallbacks (fallbacks: "default" with beta server-side-fallback-2026-07-01)
     */
    const FALLBACK_MODELS = ['claude-opus-5-5', 'claude-opus-5', 'claude-sonnet-5-5', 'claude-fable-5-2', 'claude-fable-5-1', 'claude-mythos-5-2'];

    /**
     * Send a chat completion request to the Claude API.
     *
     * The system prompt must NOT appear in the messages array for Claude;
     * it is extracted and sent as a top-level field instead.
     *
     * Supported extra options:
     *   model, max_tokens, effort, system (system prompt string) — temperature is not sent (current models reject it)
     *
     * @param array $messages OpenAI-format messages array
     * @param array $options  Per-call overrides
     *
     * @return Response
     */
    public function chat(array $messages, array $options = [])
    {
        $request = $this->buildChatRequest($messages, $options);
        $raw = $this->post($request['url'], $request['payload'], $request['headers']);

        return $this->parseChatResponse($raw, $this->lastHttpStatus, $request['model']);
    }

    /**
     * Build the Messages API request without sending it.
     *
     * @param array $messages
     * @param array $options
     *
     * @return array ['url' => string, 'payload' => array, 'headers' => array, 'model' => string]
     */
    protected function buildChatRequest(array $messages, array $options = [])
    {
        $model = isset($options['model']) && $options['model'] !== '' ? $options['model'] : $this->model;
        $maxTokens = isset($options['max_tokens']) ? (int) $options['max_tokens'] : $this->maxTokens;

        // Extract system prompt — Claude requires it at the top level, not in messages
        $systemText = '';
        if (!empty($options['system'])) {
            $systemText = $options['system'];
        }

        $msgs = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                $systemText = ($systemText !== '' ? $systemText."\n" : '').$msg['content'];
                continue;
            }
            $msgs[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? ''
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $msgs
        ];

        if ($systemText !== '') {
            $payload['system'] = $systemText;
        }

        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->anthropicVersion
        ];
        $this->applyModelOptions($model, $options, $payload, $headers);

        return [
            'url' => $this->apiUrl,
            'payload' => $payload,
            'headers' => $headers,
            'model' => $model
        ];
    }

    /**
     * Turn a Messages API payload into a Response.
     *
     * @param array  $raw
     * @param int    $status
     * @param string $model
     *
     * @return Response
     */
    protected function parseChatResponse(array $raw, $status, $model)
    {
        if (isset($raw['error'])) {
            $errMsg = is_array($raw['error']) ? ($raw['error']['message'] ?? json_encode($raw['error'])) : (string) $raw['error'];
            return $this->errorResponse($errMsg, $raw, $status);
        }

        $r = new Response();
        $r->success = true;
        $r->raw = $raw;
        $r->model = $raw['model'] ?? $model;
        if (($raw['stop_reason'] ?? '') === 'refusal') {
            $why = $raw['stop_details']['explanation'] ?? ($raw['stop_details']['category'] ?? '');
            return $this->errorResponse('The model declined this request'.($why !== '' ? ': '.$why : ''), $raw, $status);
        }
        // content is a list of blocks (thinking, text, ...) — only text is the answer
        $text = '';
        foreach ($raw['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }
        $r->content = $text;
        $r->inputTokens = $raw['usage']['input_tokens'] ?? 0;
        $r->outputTokens = $raw['usage']['output_tokens'] ?? 0;
        // Anthropic uses 'max_tokens' as stop_reason when truncated; normalise to 'length'.
        $r->finishReason = ($raw['stop_reason'] ?? 'end_turn') === 'max_tokens' ? 'length' : 'stop';

        return $r;
    }

    /**
     * Stream a chat completion via the Anthropic Messages streaming API.
     * Event types are distinguished by the `type` field in each SSE data line.
     *
     * @param array         $messages
     * @param array         $options
     * @param callable|null $onDelta  function(string $text): void
     *
     * @return Response
     */
    public function streamChat(array $messages, array $options = [],  ? callable $onDelta = null)
    {
        $model = isset($options['model']) && $options['model'] !== '' ? $options['model'] : $this->model;
        $maxTokens = isset($options['max_tokens']) ? (int) $options['max_tokens'] : $this->maxTokens;

        $systemText = '';
        if (!empty($options['system'])) {
            $systemText = $options['system'];
        }

        $msgs = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                $systemText = ($systemText !== '' ? $systemText."\n" : '').$msg['content'];
                continue;
            }
            $msgs[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? ''
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $msgs,
            'stream' => true
        ];
        if ($systemText !== '') {
            $payload['system'] = $systemText;
        }

        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->anthropicVersion
        ];
        $this->applyModelOptions($model, $options, $payload, $headers);

        $content = '';
        $modelSeen = $model;
        $inputTokens = 0;
        $outputTokens = 0;
        $finishReason = 'stop';

        $result = $this->streamSse($this->apiUrl, $payload, $headers, function ($data) use (&$content, &$modelSeen, &$inputTokens, &$outputTokens, &$finishReason, $onDelta) {
            $json = json_decode($data, true);
            if (!is_array($json) || !isset($json['type'])) {
                return;
            }
            switch ($json['type']) {
            case 'message_start' :
                $modelSeen = $json['message']['model'] ?? $modelSeen;
                $inputTokens = (int) ($json['message']['usage']['input_tokens'] ?? $inputTokens);
                $outputTokens = (int) ($json['message']['usage']['output_tokens'] ?? $outputTokens);
                break;
            case 'content_block_delta':
                if (($json['delta']['type'] ?? '') === 'text_delta') {
                    $text = (string) ($json['delta']['text'] ?? '');
                    if ($text !== '') {
                        $content .= $text;
                        if ($onDelta !== null) {
                            $onDelta($text);
                        }
                    }
                }
                break;
            case 'message_delta':
                // Cumulative output token count is reported here.
                if (isset($json['usage']['output_tokens'])) {
                    $outputTokens = (int) $json['usage']['output_tokens'];
                }
                // Anthropic signals token truncation via stop_reason 'max_tokens'.
                if (($json['delta']['stop_reason'] ?? '') === 'max_tokens') {
                    $finishReason = 'length';
                } elseif (($json['delta']['stop_reason'] ?? '') === 'refusal') {
                    $finishReason = 'refusal';
                }
                break;
            }
        });

        if (isset($result['error'])) {
            return $this->errorResponse($result['error'], [], $result['status']);
        }
        if (($result['status'] ?? 0) >= 400) {
            $raw = isset($result['error_body']) ? (json_decode($result['error_body'], true) ?: []): [];
            $errMsg = $raw['error']['message'] ?? 'AI provider returned HTTP '.$result['status'];
            return $this->errorResponse($errMsg, $raw, $result['status']);
        }

        if ($finishReason === 'refusal') {
            return $this->errorResponse('The model declined this request', [], $result['status'] ?? 200);
        }
        $r = new Response();
        $r->success = true;
        $r->model = $modelSeen;
        $r->content = $content;
        $r->inputTokens = $inputTokens;
        $r->outputTokens = $outputTokens;
        $r->finishReason = $finishReason;

        return $r;
    }

    /**
     * Per-model request fields: effort, and server-side refusal fallbacks on the models that support them.
     *
     * @param string $model
     * @param array  $options  effort => low|medium|high|xhigh|max
     * @param array  $payload
     * @param array  $headers
     */
    protected function applyModelOptions($model, array $options, array &$payload, array &$headers)
    {
        if (!empty($options['effort']) && in_array($options['effort'], ['low', 'medium', 'high', 'xhigh', 'max'], true)) {
            $payload['output_config'] = ['effort' => $options['effort']];
        }
        if (in_array((string) $model, self::FALLBACK_MODELS, true)) {
            $headers['anthropic-beta'] = 'server-side-fallback-2026-07-01';
            $payload['fallbacks'] = 'default';
        }
    }
}
