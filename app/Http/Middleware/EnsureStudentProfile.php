<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Student Portal identity & authorization foundation (Phase 8).
 *
 * Forces the Student Portal scope to come from the authenticated user's
 * linked Student — never from request input (a client cannot substitute
 * another student's ID via `student_id`).
 *
 * Contract:
 *   - unauthenticated                                  → 401 (handled by auth:sanctum)
 *   - authenticated non-Siswa (Guru/Admin/etc.)        → 403
 *   - authenticated Siswa without a linked Student     → 403 (explicit message)
 *   - authenticated Siswa with a linked Student        → passes, the resolved
 *     Student instance is exposed to downstream controllers via
 *     `$request->attributes->get('student_profile')`.
 */
class EnsureStudentProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        if (! $user->role || $user->role->name !== 'Siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        $student = $user->studentProfile;

        if ($student === null) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile is not linked to this account.',
                'data' => null,
            ], 403);
        }

        $request->attributes->set('student_profile', $student);

        return $next($request);
    }
}
