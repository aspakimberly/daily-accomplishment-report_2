<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showAdminLogin(): View
    {
        return $this->loginView();
    }

    public function showLogin(): View
    {
        return $this->loginView();
    }

    public function showRegister(): View
    {
        return view('admin.placeholder', [
            'title' => 'Admin Registration',
            'message' => 'The registration screen has not been restored yet.',
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        return back()->with('error', 'Admin registration is not available yet.');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $user = User::query()
                ->where('email', $validated['email'])
                ->first();
        } catch (QueryException) {
            return back()->with('error', 'The users table is not available yet.');
        }

        if (!$user || !User::isAdminRole($user->role)) {
            return back()->withErrors([
                'email' => 'No admin account matches that email address.',
            ])->withInput();
        }

        $request->session()->put('otp_email', $user->email);

        return redirect()
            ->route($this->verifyOtpRoute($request))
            ->with('status', 'OTP delivery is not configured yet. The controller has been restored.');
    }

    public function showVerifyOtp(Request $request): View
    {
        return view('admin.placeholder', [
            'title' => 'Verify OTP',
            'message' => 'OTP verification UI has not been restored yet.',
            'email' => $request->session()->get('otp_email'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        return redirect()
            ->route($this->loginRoute($request))
            ->with('error', 'OTP verification is not available yet.');
    }

    private function loginView(): View
    {
        return view('super_admin.super_adminSignin');
    }

    private function loginRoute(Request $request): string
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return 'admin.login';
        }

        return 'super_admin.superAdmin.login';
    }

    private function verifyOtpRoute(Request $request): string
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return 'admin.verify-otp';
        }

        return 'super_admin.superAdmin.verify-otp';
    }
}
