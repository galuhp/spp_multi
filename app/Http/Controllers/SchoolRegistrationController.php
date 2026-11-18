<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ProfilSekolah;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\VerifySchoolRegistrationNotification;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SchoolRegistrationController extends Controller
{
    public function showForm(): View
    {
        return view('auth.register-school');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', 'string', 'in:SD,SMP,SMA,SMK'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'kota' => ['required', 'string', 'max:100'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $baseSlug = Str::slug($validated['nama_sekolah']);
        $slug = $baseSlug;
        $counter = 1;
        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        [$user, $tenant] = DB::transaction(function () use ($validated, $slug): array {
            $tenant = Tenant::create([
                'name' => $validated['nama_sekolah'],
                'slug' => $slug,
                'is_active' => false,
                'settings' => [
                    'jenjang' => $validated['jenjang'],
                    'npsn' => $validated['npsn'] ?? null,
                    'kota' => $validated['kota'],
                    'no_telp' => $validated['no_telp'] ?? null,
                ],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'username' => Str::before($validated['email'], '@').'_'.Str::lower(Str::random(4)),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Admin,
                'is_super_admin' => false,
            ]);

            $tenant->users()->attach($user->id, ['role' => 'admin']);

            return [$user, $tenant];
        });

        $user->notify(new VerifySchoolRegistrationNotification($tenant));

        return back()->with('status', 'Pendaftaran berhasil! Silakan cek email Anda untuk mengklik link verifikasi agar sekolah aktif.');
    }

    public function verify(Request $request, User $user, Tenant $tenant): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link verifikasi tidak valid atau sudah kedaluwarsa.');
        }

        if (! $user->tenants()->whereKey($tenant->getKey())->exists()) {
            abort(403, 'User tidak terdaftar pada sekolah ini.');
        }

        DB::transaction(function () use ($user, $tenant): void {
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if (! $tenant->is_active) {
                $tenant->update(['is_active' => true]);
            }

            TenantContext::set($tenant);

            if (! ProfilSekolah::query()->exists()) {
                $settings = $tenant->settings ?? [];
                ProfilSekolah::create([
                    'tenant_id' => $tenant->id,
                    'app_name' => $tenant->name,
                    'nama_sekolah' => $tenant->name,
                    'jenjang' => $settings['jenjang'] ?? 'SMK',
                    'npsn' => $settings['npsn'] ?? null,
                    'kota' => $settings['kota'] ?? null,
                    'no_telp' => $settings['no_telp'] ?? null,
                    'email' => $user->email,
                ]);
            }

            TenantContext::clear();
        });

        Auth::login($user);

        return redirect()->to("/admin/{$tenant->slug}");
    }
}
