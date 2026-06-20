<?php
/**
 * @filesource Gcms/Ai/Driver.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms\Ai;

/**
 * Abstract AI driver base class
 *
 * All provider-specific drivers extend this class.
 * Configuration is read from self::$cfg (set by KBase) and can be
 * overridden per-instance via the $config array passed to __construct().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
abstract class Driver extends \Kotchasan\KBase
{
    /**
     * Provider identifier from Gcms\Ai::driver().
     *
     * @var string
     */
    protected $provider = '';

    /**
     * API key for authentication (empty for local models)
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * API endpoint URL
     * Each concrete driver sets its own default; can be overridden for
     * local models such as Ollama (http://localhost:11434/v1) or
     * LM Studio (http://localhost:1234/v1).
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Model identifier to use for requests
     *
     * @var string
     */
    protected $model = '';

    /**
     * Maximum tokens to generate in a single response
     *
     * @var int
     */
    protected $maxTokens = 1024;

    /**
     * Sampling temperature (0.0–2.0; lower = more deterministic)
     *
     * @var float
     */
    protected $temperature = 0.7;

    /**
     * Initialise the driver, merging global config with per-call overrides.
     *
     * Supported keys in $config:
     *   api_key, api_url, model, max_tokens, temperature
     *
     * @param array $config Per-instance overrides
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['provider'])) {
            $this->provider = (string) $config['provider'];
        }
        if (!empty(self::$cfg->ai_api_key)) {
            $this->apiKey = self::$cfg->ai_api_key;
        }
        if (!empty(self::$cfg->ai_api_url)) {
            $this->apiUrl = self::$cfg->ai_api_url;
        }
        if (!empty(self::$cfg->ai_model)) {
            $this->model = self::$cfg->ai_model;
        }
        if (!empty(self::$cfg->ai_max_tokens)) {
            $this->maxTokens = (int) self::$cfg->ai_max_tokens;
        }
        if (isset(self::$cfg->ai_temperature)) {
            $this->temperature = (float) self::$cfg->ai_temperature;
        }
        // Per-instance overrides take priority over global config
        if (!empty($config['api_key'])) {
            $this->apiKey = $config['api_key'];
        }
        if (!empty($config['api_url'])) {
            $this->apiUrl = $config['api_url'];
        }
        if (!empty($config['model'])) {
            $this->model = $config['model'];
        }
        if (!empty($config['max_tokens'])) {
            $this->maxTokens = (int) $config['max_tokens'];
        }
        if (isset($config['temperature'])) {
            $this->temperature = (float) $config['temperature'];
        }
    }

    /**
     * Send a chat completion request to the provider.
     *
     * $messages follows the OpenAI messages format:
     *   [['role' => 'user', 'content' => '...']]
     *   ['role' => 'assistant', 'content' => '...']
     *   ['role' => 'system', 'content' => '...']   (optional; handled per-driver)
     *
     * Supported keys in $options:
     *   model, max_tokens, temperature, system (system prompt string)
     *
     * @param array $messages Conversation history
     * @param array $options  Per-call overrides
     *
     * @return Response
     */
    abstract public function chat(array $messages, array $options = []);

    /**
     * Generate image output from a prompt.
     *
     * Drivers that do not support images may inherit this default implementation.
     * Supported keys in $options depend on the provider and may include:
     *   model, size, count
     *
     * @param string $prompt  Image prompt
     * @param array  $options Per-call overrides
     *
     * @return Response
     */
    public function generateImage($prompt, array $options = [])
    {
        return Response::fromError('Image generation is not supported by the current AI provider.');
    }

    /**
     * Extract and validate an effective option value, falling back to the
     * instance property then to a hard default.
     *
     * @param array  $options  Per-call options array
     * @param string $key      Option key to look up
     * @param mixed  $default  Hard default when the property is also empty
     *
     * @return mixed
     */
    protected function option(array $options, $key, $default)
    {
        if (isset($options[$key]) && $options[$key] !== '') {
            return $options[$key];
        }
        $prop = str_replace('_', '', $key);
        if (!empty($this->$prop)) {
            return $this->$prop;
        }
        return $default;
    }

    /**
     * Send a JSON POST request and return the decoded response array.
     *
     * On cURL error or non-JSON body, returns an array with key 'error'.
     *
     * @param string $url     Full endpoint URL
     * @param array  $payload Data to JSON-encode and POST
     * @param array  $headers HTTP headers (key => value)
     *
     * @return array Decoded JSON or ['error' => '...']
     */
    protected function post($url, array $payload, array $headers)
    {
        // SSRF guard: only http/https, and never the cloud-metadata range.
        $urlError = self::validateRequestUrl($url);
        if ($urlError !== null) {
            return ['error' => $urlError];
        }

        $ch = new \Kotchasan\Curl();
        $ch->setOptions([
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Do not follow redirects — prevents a permitted host from 30x-ing
            // the request (carrying the API key) into an internal target.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        // Allow self-signed certs ONLY for loopback (local Ollama/LM Studio).
        if (self::isLoopbackHost($url)) {
            $ch->disableSslVerify();
        }
        $ch->setHeaders(array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ], $headers));
        $body = $ch->post($url, json_encode($payload));
        if ($ch->error() !== 0) {
            // Do not leak transport detail to callers; log server-side instead.
            error_log('AI request transport error: '.$ch->errorMessage());
            return ['error' => 'AI provider request failed'];
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log('AI request non-JSON response: '.substr((string) $body, 0, 500));
            $message = 'AI provider returned an invalid response';
            if (self::isLoopbackHost($url) && preg_match('#/api(?:/|$)#i', (string) $url)) {
                $message .= '. For Ollama, use http://localhost:11434/v1 (not /api)';
            }
            return ['error' => $message];
        }
        return $decoded;
    }

    /**
     * Validate an outbound request URL (SSRF guard).
     * Returns an error string if the URL is unsafe, or null if acceptable.
     *
     * @param string $url
     * @return string|null
     */
    protected static function validateRequestUrl($url)
    {
        $parts = parse_url((string) $url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'AI endpoint URL is invalid';
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return 'AI endpoint scheme is not allowed';
        }
        $host = $parts['host'];
        // Block the cloud-metadata / link-local range outright — never a valid
        // AI endpoint, and the prime SSRF target.
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);
        if (is_string($ip) && (strpos($ip, '169.254.') === 0 || $ip === '0.0.0.0')) {
            return 'AI endpoint host is not allowed';
        }
        return null;
    }

    /**
     * @param string $url
     * @return bool True if the URL host is loopback (localhost / 127.0.0.0/8 / ::1)
     */
    protected static function isLoopbackHost($url)
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        if ($host === 'localhost' || $host === '::1') {
            return true;
        }
        return (bool) preg_match('/^127\./', $host);
    }
}
