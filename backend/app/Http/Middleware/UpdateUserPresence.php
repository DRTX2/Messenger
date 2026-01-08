<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserPresence
{
    public function __construct(private readonly PresenceService $presenceService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $this->presenceService->updatePresence((int) auth()->id());
        }

        return $next($request);
    }
}
