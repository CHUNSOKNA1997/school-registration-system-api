<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PaywayCallbackService
{
    /**
     * Get the appropriate callback URL for PayWay pushback
     *
     * @param string $path The URL path (e.g., '/api/payway/webhook')
     * @return string The URL encoded in base64
     */
    public static function getCallbackUrl(string $path): string
    {
        // In production/staging, use the actual URL
        if (app()->environment('production', 'staging')) {
            $url = base64_encode(url($path));
            return $url;
        }

        // In development with explicit ngrok URL set
        if (config('services.ngrok.url')) {
            $ngrokUrl = rtrim(config('services.ngrok.url'), '/');
            $url = base64_encode($ngrokUrl . $path);

            return $url;
        }

        // Try to detect ngrok URL automatically
        $possibleNgrokUrl = self::detectNgrokUrl();
        if ($possibleNgrokUrl) {
            $url = base64_encode($possibleNgrokUrl . $path);

            return $url;
        }

        // Fallback to regular URL with warning logged
        Log::warning('PayWay: No ngrok URL detected. External services may not reach the callback.', [
            'path' => $path,
            'fallback_url' => url($path),
            'environment' => app()->environment(),
        ]);

        return base64_encode(url($path));
    }

    /**
     * Attempt to automatically detect ngrok URL from server variables
     *
     * @return string|null
     */
    protected static function detectNgrokUrl(): ?string
    {
        // Try to detect from X-Forwarded-Host header (ngrok sets this)
        if (
            isset($_SERVER['HTTP_X_FORWARDED_HOST']) &&
            strpos($_SERVER['HTTP_X_FORWARDED_HOST'], 'ngrok') !== false
        ) {
            $url = 'https://' . $_SERVER['HTTP_X_FORWARDED_HOST'];
            return $url;
        }

        // Check for ngrok in X-Original-Host header
        if (
            isset($_SERVER['HTTP_X_ORIGINAL_HOST']) &&
            strpos($_SERVER['HTTP_X_ORIGINAL_HOST'], 'ngrok') !== false
        ) {
            $url = 'https://' . $_SERVER['HTTP_X_ORIGINAL_HOST'];
            return $url;
        }

        return null;
    }
}
