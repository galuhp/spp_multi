<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function leave(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            abort(403, 'Sesi impersonate tidak ditemukan.');
        }

        $superAdmin = User::query()->findOrFail($impersonatorId);

        if (! $superAdmin->isSuperAdmin()) {
            abort(403, 'Akun asal bukan Super Admin.');
        }

        $returnUrl = $request->session()->get('impersonator_return_url', '/super-admin/tenants');

        $request->session()->forget([
            'impersonator_id',
            'impersonator_tenant_id',
            'impersonator_return_url',
        ]);

        Auth::guard('web')->login($superAdmin);
        $request->session()->put('password_hash_web', $superAdmin->getAuthPassword());
        $request->session()->regenerate();

        return redirect()->to($returnUrl);
    }
}
