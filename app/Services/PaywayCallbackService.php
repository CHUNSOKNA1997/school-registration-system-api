<?php

namespace App\Services;

class PaywayCallbackService
{
    /**
     * Get the appropriate callback URL for PayWay pushback
     *
     * @param string $path The URL path (e.g., '/api/v1/webhooks/payway')
     * @return string The URL encoded in base64
     */
    public static function getCallbackUrl(string $path): string
    {
        // Highest priority: explicit NGROK_URL from configuration.
        $configuredNgrokUrl = self::normalizeBaseUrl((string) config('services.ngrok.url', ''));
        if ($configuredNgrokUrl !== null) {
            return base64_encode(self::buildAbsoluteUrl($configuredNgrokUrl, $path));
        }

        // In production/staging, use the actual app URL.
        if (app()->environment('production', 'staging')) {
            return base64_encode(url($path));
        }

        // Try to detect ngrok URL automatically
        $possibleNgrokUrl = self::detectNgrokUrl();
        if ($possibleNgrokUrl) {
            return base64_encode(self::buildAbsoluteUrl($possibleNgrokUrl, $path));
        }

        // Fallback to regular URL
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

    protected static function normalizeBaseUrl(string $url): ?string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return null;
        }

        return rtrim($trimmed, '/');
    }

    protected static function buildAbsoluteUrl(string $baseUrl, string $path): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        return rtrim($baseUrl, '/') . $normalizedPath;
    }
}
