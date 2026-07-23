<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Amrachraf6699\LaravelGeminiAi\Services\GeminiAiService as PackageGeminiAiService;

class GeminiAiService extends PackageGeminiAiService
{
    public function generateText(string $prompt, array $options = [])
    {
        $startedAt = microtime(true);
        $model = $options['model'] ?? config('gemini.models.text');
        $requestContext = [
            'model' => $model,
            'prompt_chars' => strlen($prompt),
            'prompt_hash' => hash('sha256', $prompt),
            'connect_timeout' => (int) config('gemini.connect_timeout', 10),
            'timeout' => (int) config('gemini.timeout', 120),
            'retries' => (int) config('gemini.retries', 1),
        ];

        try {
            $url = config('gemini.base_url')."/models/{$model}:generateContent?key=".config('gemini.api_key');
            $payload = [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ];

            if (! empty($options['generationConfig'])) {
                $payload['generationConfig'] = $options['generationConfig'];
            }

            $requestContext['payload_bytes'] = strlen(json_encode($payload, JSON_THROW_ON_ERROR));
            if (config('gemini.log_requests', true)) {
                Log::info('Gemini text request started', $requestContext);
            }

            $response = Http::connectTimeout((int) config('gemini.connect_timeout', 10))
                ->timeout((int) config('gemini.timeout', 120))
                ->retry((int) config('gemini.retries', 1), (int) config('gemini.retry_delay', 1000))
                ->post($url, $payload);

            $requestContext += [
                'http_status' => $response->status(),
                'response_bytes' => strlen($response->body()),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
            if (config('gemini.log_requests', true)) {
                Log::info('Gemini text response received', $requestContext);
            }

            $this->validateResponse($response);
            $data = $response->json();

            return ($options['raw'] ?? false)
                ? $data
                : $this->extractTextContent($data);
        } catch (\Throwable $exception) {
            Log::error('Gemini text request failed', $requestContext + [
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => get_class($exception),
                'error_code' => $exception->getCode(),
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
