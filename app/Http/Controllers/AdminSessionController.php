<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Services\AuditLogger;
use App\Services\MasterAdminSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminSessionController extends Controller
{
    public function store(AdminLoginRequest $request, MasterAdminSession $adminSession, AuditLogger $audits)
    {
        $password = $request->validated('password');

        if (! is_string($password) || ! $adminSession->attempt($password)) {
            $audits->record('admin.login_failed', 'anonymous', 'Admin login attempt');

            throw ValidationException::withMessages([
                'password' => 'The admin password is invalid.',
            ]);
        }

        $request->session()->regenerate(true);
        $adminSession->authenticate($request);
        $audits->admin('admin.login_succeeded');

        return to_route('admin.index');
    }

    public function destroy(Request $request, MasterAdminSession $adminSession, AuditLogger $audits)
    {
        $audits->admin('admin.logout');
        $adminSession->forget($request);
        $request->session()->regenerate(true);

        return to_route('home');
    }
}
