<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $userRole = $user->role?->role_name;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Unauthorized — Required: ' . implode(' or ', $roles),
                'your_role' => $userRole,
            ], 403);
        }

        return $next($request);
    }
}