<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Amrachraf6699\LaravelGeminiAi\Services\GeminiAiService as PackageGeminiAiService;

class GeminiAiService extends PackageGeminiAiService
{
    public function generateText(string $prompt, array $options = [])
    {
        try {
            $model = $options['model'] ?? config('gemini.models.text');
            $url = config('gemini.base_url')."/models/{$model}:generateContent?key=".config('gemini.api_key');
            $payload = [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ];

            if (! empty($options['generationConfig'])) {
                $payload['generationConfig'] = $options['generationConfig'];
            }

            $response = Http::connectTimeout((int) config('gemini.connect_timeout', 10))
                ->timeout((int) config('gemini.timeout', 120))
                ->retry((int) config('gemini.retries', 1), (int) config('gemini.retry_delay', 1000))
                ->post($url, $payload);

            $this->validateResponse($response);
            $data = $response->json();

            return ($options['raw'] ?? false)
                ? $data
                : $this->extractTextContent($data);
        } catch (\Throwable $exception) {
            Log::error('Gemini API Error (Text): '.$exception->getMessage());
            throw $exception;
        }
    }
}
