<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('super_admin.superAdmin.dashboard');
    }

    public function dashboard(): View
    {
        return $this->placeholder('Dashboard');
    }

    public function users(): View
    {
        return $this->placeholder('Users');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        return back()->with('error', 'User management is not available yet.');
    }

    public function updateUser(Request $request, mixed $user): RedirectResponse
    {
        return back()->with('error', 'User updates are not available yet.');
    }

    public function resetUser(Request $request, mixed $user): RedirectResponse
    {
        return back()->with('error', 'Password reset is not available yet.');
    }

    public function reports(): View
    {
        return $this->placeholder('Reports');
    }

    public function updateReportStatus(Request $request, mixed $report): RedirectResponse
    {
        return back()->with('error', 'Report review is not available yet.');
    }

    public function archiveReportUser(Request $request, mixed $report): RedirectResponse
    {
        return back()->with('error', 'User archiving is not available yet.');
    }

    public function downloadReport(mixed $report): Response
    {
        return response('Report download is not available yet.', 501);
    }

    public function activityLogs(): View
    {
        return $this->placeholder('Activity Logs');
    }

    public function settings(): View
    {
        return $this->placeholder('Settings');
    }

    public function profile(): View
    {
        return $this->placeholder('Profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        return back()->with('error', 'Profile updates are not available yet.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($this->loginRoute($request));
    }

    private function placeholder(string $title): View
    {
        return view('admin.placeholder', [
            'title' => $title,
            'message' => 'The original controller was missing. This placeholder prevents route failures while the feature is rebuilt.',
        ]);
    }

    private function loginRoute(Request $request): string
    {
        if ($request->routeIs('admin.*') || $request->is('admin/*')) {
            return 'admin.login';
        }

        return 'super_admin.superAdmin.login';
    }
}
