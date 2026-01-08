<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 400);
    }
}
