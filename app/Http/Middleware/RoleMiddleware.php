<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-role authorization (proteksi di sisi backend, bukan hanya menyembunyikan menu).
 * Pemakaian di route:  ->middleware('role:admin')  |  ->middleware('role:siswa')  |  ->middleware('role:teknisi')
 * Beberapa role sekaligus: ->middleware('role:admin,teknisi')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Normalisasi: role 'user' dan 'siswa' dianggap sama
        $userRole = $user->role;
        $allowed = false;
        foreach ($roles as $r) {
            if ($r === $userRole) {
                $allowed = true;
                break;
            }
            // Jika middleware meminta 'siswa' atau 'user', keduanya diterima untuk role 'user' maupun 'siswa'
            if (in_array($r, ['siswa', 'user'], true) && in_array($userRole, ['siswa', 'user'], true)) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
