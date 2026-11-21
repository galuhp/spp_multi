<?php

namespace App\Http\Middleware;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PengeluaranSekolah;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWebTenantContext
{
    /**
     * Resolve TenantContext for authenticated non-Filament web routes (e.g. print/export/portal)
     * before SubstituteBindings runs so BelongsToTenant models do not 404.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (TenantContext::id() !== null) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $tenant = $this->resolveTenant($request, $user);

        if ($tenant !== null) {
            TenantContext::set($tenant);
        }

        return $next($request);
    }

    protected function resolveTenant(Request $request, User $user): ?Tenant
    {
        if ($request->filled('tenant')) {
            $tenant = Tenant::query()
                ->where('slug', (string) $request->query('tenant'))
                ->where('is_active', true)
                ->first();

            if ($tenant && $user->canAccessTenant($tenant)) {
                return $tenant;
            }
        }

        $preferredTenantId = $this->resolveTenantIdFromRouteOrQuery($request);

        if ($preferredTenantId !== null) {
            $tenant = Tenant::query()
                ->whereKey($preferredTenantId)
                ->where('is_active', true)
                ->first();

            if ($tenant && $user->canAccessTenant($tenant)) {
                return $tenant;
            }

            abort(403);
        }

        return $user->isSuperAdmin()
            ? Tenant::query()->where('is_active', true)->orderBy('id')->first()
            : $user->tenants()->where('is_active', true)->orderBy('tenants.id')->first();
    }

    protected function resolveTenantIdFromRouteOrQuery(Request $request): ?int
    {
        $route = $request->route();

        $routeModels = [
            'pembayaran' => Pembayaran::class,
            'gabungan' => PembayaranGabungan::class,
            'pengeluaran' => PengeluaranSekolah::class,
            'siswa' => Siswa::class,
            'jurusan' => Jurusan::class,
            'kelas' => Kelas::class,
            'spp' => Spp::class,
        ];

        if ($route !== null) {
            foreach ($routeModels as $param => $modelClass) {
                $rawValue = $route->parameter($param);

                if (is_numeric($rawValue)) {
                    $tenantId = $modelClass::withoutGlobalScope('tenant')
                        ->whereKey((int) $rawValue)
                        ->value('tenant_id');

                    if ($tenantId !== null) {
                        return (int) $tenantId;
                    }
                }
            }
        }

        if ($request->filled('siswa_id') && is_numeric($request->query('siswa_id'))) {
            $tenantId = Siswa::withoutGlobalScope('tenant')
                ->whereKey((int) $request->query('siswa_id'))
                ->value('tenant_id');

            if ($tenantId !== null) {
                return (int) $tenantId;
            }
        }

        return null;
    }
}
