<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Response::macro('jsonSuccess', function ($data, $statusCode = 200, $message = null) {
            $response = [
                'success' => true,
                'data' => $data,
            ];

            if ($message !== null) {
                $response['message'] = $message;
            }

            return response()->json($response, $statusCode);
        });

        Response::macro('jsonError', function ($message, $statusCode = 400, $data = []) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $response[$key] = $value;
                }
            }

            return response()->json($response, $statusCode);
        });
    }
}
