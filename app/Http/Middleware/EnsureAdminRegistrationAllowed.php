<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRegistrationAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $loginRoute = $this->loginRoute($request);
        $hasAdmin = User::query()->whereIn('role', User::ADMIN_ROLES)->exists();

        // Allow bootstrapping the first admin account.
        if (!$hasAdmin) {
            return $next($request);
        }

        $userId = $request->session()->get('authenticated_user_id');
        if (!$userId) {
            return redirect()->route($loginRoute)->with('error', 'Admin registration is restricted.');
        }

        $user = User::find($userId);
        if (!$user || !User::isAdminRole($user->role)) {
            $request->session()->flush();
            return redirect()->route($loginRoute)->with('error', 'Unauthorized admin access.');
        }

        return $next($request);
    }

    private function loginRoute(Request $request): string
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return 'admin.login';
        }

        return 'super_admin.superAdmin.login';
    }
}
