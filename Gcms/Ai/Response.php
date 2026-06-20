<?php
/**
 * @filesource Gcms/Ai/Response.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms\Ai;

/**
 * Normalized AI response DTO
 *
 * Returned by every AI driver's chat() method so callers
 * never need to handle provider-specific response shapes.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Response implements \JsonSerializable
{
    /**
     * Whether the request succeeded
     *
     * @var bool
     */
    public $success = false;

    /**
     * Text content returned by the model
     *
     * @var string
     */
    public $content = '';

    /**
     * Chain-of-thought reasoning content (DeepSeek thinking mode).
     *
     * @var string
     */
    public $reasoningContent = '';

    /**
     * Generated images returned by the provider.
     * Each item is a normalized array such as:
     *   ['url' => 'https://...', 'b64_json' => '...', 'mime_type' => 'image/png']
     *
     * @var array
     */
    public $images = [];

    /**
     * Model identifier that produced this response
     *
     * @var string
     */
    public $model = '';

    /**
     * Number of input (prompt) tokens consumed
     *
     * @var int
     */
    public $inputTokens = 0;

    /**
     * Number of output (completion) tokens generated
     *
     * @var int
     */
    public $outputTokens = 0;

    /**
     * Error message when success is false
     *
     * @var string
     */
    public $error = '';

    /**
     * Raw decoded API response for debugging
     *
     * @var array
     */
    public $raw = [];

    /**
     * Create a failed Response with an error message
     *
     * @param string $message Error description
     * @param array  $raw     Raw API response if available
     *
     * @return self
     */
    public static function fromError($message, array $raw = [])
    {
        $r = new self();
        $r->success = false;
        $r->error = $message;
        $r->raw = $raw;
        return $r;
    }

    /**
     * JSON representation for API clients.
     * Deliberately OMITS $raw — the raw provider payload can contain internal
     * request detail (model, endpoint, reflected headers) and must never be
     * shipped to clients. Read ->raw directly for server-side logging only.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'content' => $this->content,
            'reasoningContent' => $this->reasoningContent,
            'images' => $this->images,
            'model' => $this->model,
            'inputTokens' => $this->inputTokens,
            'outputTokens' => $this->outputTokens,
            'error' => $this->error,
        ];
    }
}
