<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Send a success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
